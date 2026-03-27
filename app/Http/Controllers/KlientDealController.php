<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Klient;
use App\Models\KlientDeal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use App\Models\KlientDealFile;
class KlientDealController extends Controller
{

    public function index(Request $request)
    {
        $userId = Auth::id();
        $companyId = $request->input('company_id');

        // 1. БАЗОВЫЙ ЗАПРОС СЛОЖНОГО ДОСТУПА
        $query = KlientDeal::query()->with(['klient.company', 'responsibles']);

        $query->where(function ($q) use ($userId) {
            $q->where('creator_id', $userId) // Я создал сделку
            ->orWhereHas('responsibles', fn($sq) => $sq->where('users.id', $userId)) // Я в команде сделки
            ->orWhereHas('klient', function ($sq) use ($userId) {
                $sq->where('user_id', $userId) // Я владелец клиента
                ->orWhereHas('company', fn($ssq) => $ssq->where('user_id', $userId)); // Я владелец компании
            });
        });

        // 2. Фильтр по компании
        if ($companyId) {
            $query->whereHas('klient', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            });
        }

        // 3. Данные для страницы
        $deals = $query->latest()->paginate(15)->withQueryString();

        // 4. Список компаний для фильтра
        $accessibleCompanies = Company::where('user_id', $userId)
            ->orWhereHas('users', fn($q) => $q->where('users.id', $userId))
            ->get();

        // 5. Общая статистика по всем видимым сделкам
        $stats = [
            'total_sum' => (clone $query)->sum('total_amount'),
            'count' => (clone $query)->count(),
        ];

        return Inertia::render('Klients/Deals/Index', [
            'deals' => $deals,
            'companies' => $accessibleCompanies,
            'stats' => $stats,
            'filters' => ['company_id' => $companyId ?? '']
        ]);
    }


    /**
     * Показать форму создания сделки
     */
    public function create(Klient $klient)
    {
        // Подгружаем всё, что нужно для селектов на странице создания
        $klient->load(['contactPersons', 'allowedUsers', 'creator', 'tasks' => function($q) {
            $q->where('status', '!=', 'completed'); // Только активные задачи для связки
        }]);

        // Собираем список всех, кто имеет доступ (Создатель + список доступа)
        $availableResponsibles = collect([$klient->creator])
            ->merge($klient->allowedUsers)
            ->unique('id')
            ->values();

        return Inertia::render('Klients/Deals/Create', [
            'klient' => $klient,
            'contacts' => $klient->contactPersons,
            'availableResponsibles' => $availableResponsibles,
            'activeTasks' => $klient->tasks
        ]);
    }

    /**
     * Сохранение сделки
     */
    public function store(Request $request, Klient $klient)
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'contact_person_id' => 'nullable|exists:klient_contact_persons,id',
            'responsible_ids'   => 'required|array',
            'task_ids'          => 'nullable|array',
            'deadline'          => 'nullable|date',
            'status'            => 'required|string',
            'attribute'         => 'required|string',
            'description'       => 'nullable|string',
            'items'             => 'required|array|min:1',
            'items.*.name'      => 'required|string',
            'items.*.quantity'  => 'required|numeric|min:0.1',
            'items.*.unit_price'=> 'required|numeric|min:0',
            'files.*'           => 'nullable|file|max:20480',
        ]);

        return DB::transaction(function () use ($validated, $klient, $request) {
            // 1. Создаем сделку
            // Исключаем лишние поля, которые не идут в таблицу deals напрямую
            $dealData = collect($validated)->except(['responsible_ids', 'task_ids', 'items', 'files'])->toArray();

            $deal = $klient->deals()->create([
                ...$dealData,
                'creator_id' => Auth::id(),
            ]);

            // 2. Добавляем товары и считаем сумму
            $totalDealAmount = 0;
            foreach ($validated['items'] as $itemData) {
                $item = $deal->items()->create($itemData);
                $totalDealAmount += $item->total_price;
            }
            $deal->update(['total_amount' => $totalDealAmount]);

            // 3. Связи
            $deal->responsibles()->sync($validated['responsible_ids']);
            if (!empty($validated['task_ids'])) {
                $deal->tasks()->sync($validated['task_ids']);
            }

            // 4. Файлы
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $path = $file->store('klient_deals/' . $deal->id);
                    $deal->files()->create([
                        'user_id' => Auth::id(),
                        'original_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'file_size' => $file->getSize(),
                    ]);
                }
            }

            // ПРАВКА: Перенаправляем в карточку клиента (в раздел сделок)
            return redirect()->route('klients.show', $klient->id)->with('success', 'Сделка успешно создана');
        });
    }

    /**
     * Просмотр конкретной сделки
     */
    public function show(KlientDeal $deal)
    {
        $this->authorize('view', $deal);

        $deal->load(['items', 'responsibles', 'tasks', 'files', 'contactPerson', 'klient']);
        return Inertia::render('Klients/Deals/Show', [
            'deal' => $deal
        ]);
    }

    public function updateStatus(Request $request, KlientDeal $deal)
    {
        // Проверяем, что статус можно менять только если он не "Успешно" и не "Отказ"
        $blockedStatuses = ['Успешно', 'Отказ'];

        if (in_array($deal->status, $blockedStatuses)) {
            return back()->with('error', 'Нельзя изменить статус завершенной сделки');
        }

        $request->validate([
            'status' => 'required|string'
        ]);

        // Дополнительная проверка: если новый статус - завершающий, то блокируем дальнейшие изменения
        $newStatus = $request->status;

        $deal->update([
            'status' => $newStatus
        ]);

        return back()->with('success', 'Статус сделки обновлен');
    }


    public function edit(KlientDeal $deal)
    {
        $deal->load(['items', 'responsibles', 'tasks', 'files', 'klient.contactPersons', 'klient.allowedUsers', 'klient.creator']);

        $klient = $deal->klient;

        // Список доступных для команды (создатель клиента + доступы)
        $availableResponsibles = collect([$klient->creator])
            ->merge($klient->allowedUsers)
            ->unique('id')
            ->values();

        // Задачи клиента (активные + те, что уже привязаны к этой сделке)
        $activeTasks = $klient->tasks()
            ->where(function($q) use ($deal) {
                $q->where('status', '!=', 'completed')
                    ->orWhereIn('id', $deal->tasks->pluck('id'));
            })->get();

        return Inertia::render('Klients/Deals/Edit', [
            'deal' => $deal,
            'klient' => $klient,
            'contacts' => $klient->contactPersons,
            'availableResponsibles' => $availableResponsibles,
            'activeTasks' => $activeTasks
        ]);
    }

    /**
     * Обновление сделки
     */
    public function update(Request $request, KlientDeal $deal)
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'contact_person_id' => 'nullable|exists:klient_contact_persons,id',
            'responsible_ids'   => 'required|array',
            'task_ids'          => 'nullable|array',
            'deadline'          => 'nullable|date',
            'status'            => 'required|string',
            'attribute'         => 'required|string',
            'description'       => 'nullable|string',
            'items'             => 'required|array|min:1',
            'items.*.name'      => 'required|string',
            'items.*.quantity'  => 'required|numeric|min:0.1',
            'items.*.unit_price'=> 'required|numeric|min:0',
            'new_files.*'       => 'nullable|file|max:20480',
        ]);

        return DB::transaction(function () use ($validated, $deal, $request) {
            // 1. Обновляем основные поля
            $dealData = collect($validated)->except(['responsible_ids', 'task_ids', 'items', 'new_files'])->toArray();
            $deal->update($dealData);

            // 2. Обновляем товары (удаляем старые, записываем новые)
            $deal->items()->delete();
            $totalAmount = 0;
            foreach ($validated['items'] as $itemData) {
                $item = $deal->items()->create($itemData);
                $totalAmount += $item->total_price;
            }
            $deal->update(['total_amount' => $totalAmount]);

            // 3. Обновляем команду и задачи (добавление/удаление юзеров тут)
            $deal->responsibles()->sync($validated['responsible_ids']);
            $deal->tasks()->sync($validated['task_ids'] ?? []);

            // 4. Загрузка новых файлов, если они есть
            if ($request->hasFile('new_files')) {
                foreach ($request->file('new_files') as $file) {
                    $path = $file->store('klient_deals/' . $deal->id);
                    $deal->files()->create([
                        'user_id' => Auth::id(),
                        'original_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'file_size' => $file->getSize(),
                    ]);
                }
            }

            return redirect()->route('klient-deals.show', $deal->id)->with('success', 'Сделка обновлена');
        });
    }

    /**
     * Удаление конкретного файла из сделки
     */
    public function destroyFile(KlientDealFile $file)
    {
        $userId = Auth::id();

        // Проверяем, что текущий пользователь является загрузившим файл
        if ($file->user_id !== $userId) {
            abort(403, 'Только пользователь, загрузивший файл, может его удалить');
        }

        // Удаляем физически
        if (Storage::exists($file->file_path)) {
            Storage::delete($file->file_path);
        }

        $file->delete();

        return back()->with('success', 'Файл удален');
    }


    public function uploadFiles(Request $request, KlientDeal $deal)
    {
        // Проверяем права через policy
        $this->authorize('view', $deal);

        // Дополнительная проверка на возможность загрузки файлов
        $userId = Auth::id();
        $canUpload = false;

        // Создатель сделки
        if ($deal->creator_id === $userId) {
            $canUpload = true;
        }
        // Член команды сделки
        elseif ($deal->responsibles()->where('user_id', $userId)->exists()) {
            $canUpload = true;
        }
        // Владелец компании клиента
        elseif ($deal->klient && $deal->klient->company && $deal->klient->company->user_id === $userId) {
            $canUpload = true;
        }

        if (!$canUpload) {
            abort(403, 'У вас нет прав на загрузку файлов для этой сделки');
        }

        $request->validate([
            'files' => 'required|array',
            'files.*' => 'file|max:20480' // 20MB max per file
        ]);

        $uploadedFiles = [];

        foreach ($request->file('files') as $file) {
            $path = $file->store('klient_deals/' . $deal->id);

            $dealFile = $deal->files()->create([
                'user_id' => Auth::id(),
                'original_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_size' => $file->getSize(),
            ]);

            $uploadedFiles[] = $dealFile;
        }

        return response()->json([
            'success' => true,
            'message' => 'Файлы успешно загружены',
            'files' => $uploadedFiles
        ]);
    }


}
