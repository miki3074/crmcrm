<?php

namespace App\Http\Controllers;

use App\Models\Klient;
use App\Models\Company;
use App\Models\KlientDeal;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class KlientController extends Controller
{
    /**
     * Список клиентов, доступных пользователю.
     *
     *
     */

    //строгий доступ

//    public function index()
//    {
//        $userId = Auth::id();
//
//        $klients = Klient::with(['contactPersons', 'company'])
//            ->where(function ($query) use ($userId) {
//                $query->where('user_id', $userId) // 1. Я лично создал эту карточку
//                ->orWhereHas('allowedUsers', function ($q) use ($userId) {
//                    $q->where('users.id', $userId); // 2. Мне принудительно дали доступ (таблица klient_access)
//                });
//            })
//            ->latest()
//            ->paginate(12);
//
//        return Inertia::render('Klients/Index', [
//            'klients' => $klients
//        ]);
//    }

    public function index(Request $request)
    {
        $userId = Auth::id();
        $companyId = $request->input('company_id');
        $now = now();

        // 1. БАЗОВЫЙ ЗАПРОС КЛИЕНТОВ (для списка последних и счетчика клиентов)
        $baseKlientQuery = Klient::query()->where(function ($q) use ($userId) {
            $q->where('user_id', $userId)
                ->orWhereHas('allowedUsers', fn($sq) => $sq->where('users.id', $userId))
                ->orWhereHas('company', fn($sq) => $sq->where('user_id', $userId));
        });

        if ($companyId) {
            $baseKlientQuery->where('company_id', $companyId);
        }

        // 2. БАЗОВЫЙ ЗАПРОС СДЕЛОК (с учетом прав доступа к конкретным сделкам)
        // Мы считаем только те сделки, где:
        // - Вы создатель сделки
        // - Или вы в команде ответственных
        // - Или вы владелец клиента / компании (видите всё)
        $baseDealQuery = KlientDeal::query()->where(function ($q) use ($userId) {
            $q->where('creator_id', $userId) // Я создал сделку
            ->orWhereHas('responsibles', fn($sq) => $sq->where('users.id', $userId)) // Я ответственный
            ->orWhereHas('klient', function ($sq) use ($userId) {
                $sq->where('user_id', $userId) // Я владелец клиента
                ->orWhereHas('company', fn($ssq) => $ssq->where('user_id', $userId)); // Я владелец компании
            });
        });

        // Если выбрана компания, фильтруем и сделки тоже
        if ($companyId) {
            $baseDealQuery->whereHas('klient', fn($q) => $q->where('company_id', $companyId));
        }

        // 3. СТАТИСТИКА (теперь она точная!)
        $activeClientsCount = (clone $baseKlientQuery)->where('status', 'Действующий')->count();
        $newClientsMonth = (clone $baseKlientQuery)->where('created_at', '>=', $now->subMonth())->count();

        // Сделки "в работе" (используем наш защищенный $baseDealQuery)
        $workingDeals = (clone $baseDealQuery)->whereNotIn('status', ['Успешно', 'Отказ']);

        $dealsCount = $workingDeals->count();
        $dealsSum = $workingDeals->sum('total_amount');

        // 4. СПИСКИ ДЛЯ ДАШБОРДА
        $recentKlients = (clone $baseKlientQuery)->with('company')->latest()->take(4)->get();
        $totalKlientsCount = (clone $baseKlientQuery)->count();

        // 5. ВОРОНКА СДЕЛОК (также через защищенный запрос)
        $funnel = (clone $baseDealQuery)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        // 6. СПИСОК КОМПАНИЙ
        $myCompanies = Company::where('user_id', $userId)
            ->orWhereHas('users', fn($q) => $q->where('users.id', $userId))
            ->select('id', 'name')->get();

        return Inertia::render('Klients/Index', [
            'stats' => [
                'active_clients' => $activeClientsCount,
                'new_this_month' => $newClientsMonth,
                'deals_count' => $dealsCount,
                'deals_sum' => $dealsSum,
                'total_count' => $totalKlientsCount,
                'funnel' => $funnel,
            ],
            'recent_klients' => $recentKlients,
            'companies' => $myCompanies,
            'filters' => ['company_id' => $companyId ?? '']
        ]);
    }

    /**
     * Форма создания клиента.
     */
    public function create()
    {
        $userId = Auth::id();

        // 1. ПОЛУЧАЕМ КОМПАНИИ (где я владелец или сотрудник)
        $myCompanyIds = DB::table('companies')
            ->where('user_id', $userId)
            ->union(
                DB::table('company_user')->where('user_id', $userId)->select('company_id')
            )
            ->pluck('id')
            ->toArray();

        $companies = Company::whereIn('id', $myCompanyIds)->select('id', 'name')->get();

        // 2. ПОЛУЧАЕМ ПРОЕКТЫ
        // (в моих компаниях И (я участник ИЛИ у меня есть задачи в этом проекте))
        $projects = Project::whereIn('company_id', $myCompanyIds)
            ->where(function ($query) use ($userId) {
                $query->where('initiator_id', $userId)
                    ->orWhereHas('executors', fn($q) => $q->where('users.id', $userId))
                    ->orWhereHas('project_users', fn($q) => $q->where('users.id', $userId))
                    ->orWhereHas('tasks', function ($q) use ($userId) {
                        $q->where('creator_id', $userId)
                            ->orWhereHas('executors', fn($sq) => $sq->where('users.id', $userId))
                            ->orWhereHas('responsibles', fn($sq) => $sq->where('users.id', $userId));
                    });
            })
            ->select('id', 'name', 'company_id')
            ->get();

        // 3. ПОЛУЧАЕМ ЗАДАЧИ
        $tasks = Task::whereIn('project_id', $projects->pluck('id'))
            ->where(function ($query) use ($userId) {
                $query->where('creator_id', $userId)
                    ->orWhereHas('executors', fn($q) => $q->where('users.id', $userId))
                    ->orWhereHas('responsibles', fn($q) => $q->where('users.id', $userId));
            })
            ->select('id', 'title', 'project_id')
            ->get();

        // 4. ПОЛУЧАЕМ КОЛЛЕГ ДЛЯ ДОСТУПА
        // Находим всех юзеров, кто состоит в тех же компаниях, что и я
        $allUserIdsInMyCompanies = DB::table('company_user')
            ->whereIn('company_id', $myCompanyIds)
            ->pluck('user_id')
            ->toArray();

        $ownersOfMyCompanies = DB::table('companies')
            ->whereIn('id', $myCompanyIds)
            ->pluck('user_id')
            ->toArray();

        $mergedUserIds = array_unique(array_merge($allUserIdsInMyCompanies, $ownersOfMyCompanies));

        $colleagues = User::whereIn('id', $mergedUserIds)
            ->where('id', '!=', $userId) // исключаем себя
            ->select('id', 'name')
            ->get()
            ->map(function($u) use ($myCompanyIds) {
                // Для каждого коллеги находим ID компаний (из моих), в которых он тоже состоит
                $employeeIn = DB::table('company_user')
                    ->where('user_id', $u->id)
                    ->whereIn('company_id', $myCompanyIds)
                    ->pluck('company_id')
                    ->toArray();

                $ownerOf = DB::table('companies')
                    ->where('user_id', $u->id)
                    ->whereIn('id', $myCompanyIds)
                    ->pluck('id')
                    ->toArray();

                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'company_ids' => array_values(array_map('intval', array_unique(array_merge($employeeIn, $ownerOf))))
                ];
            });

        return Inertia::render('Klients/Create', [
            'companies'  => $companies,
            'projects'   => $projects,
            'tasks'      => $tasks,
            'colleagues' => $colleagues
        ]);
    }

    /**
     * Сохранение нового клиента.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'status'          => 'required|string',
            'company_id'      => 'nullable|exists:companies,id',
            'project_id'      => 'nullable|exists:projects,id',
            'task_id'         => 'nullable|exists:tasks,id',
            'segment'         => 'nullable|string',
            'rating'          => 'nullable|string|max:10',
            'phone'           => 'nullable|string',
            'email'           => 'nullable|email',
            'inn'             => 'nullable|string',
            'kpp'             => 'nullable|string',
            'ogrn'            => 'nullable|string',
            'legal_address'   => 'nullable|string',
            'actual_address'  => 'nullable|string',
            'industry'        => 'nullable|string',
            'messengers'      => 'nullable|array',
            'contact_persons' => 'nullable|array',
            'allowed_users'   => 'nullable|array',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            // 1. Создаем клиента
            $klient = Klient::create([
                ...$validated,
                'user_id' => Auth::id(),
            ]);

            // 2. Контактные лица
            if (!empty($validated['contact_persons'])) {
                foreach ($validated['contact_persons'] as $person) {
                    if (!empty($person['full_name'])) {
                        $klient->contactPersons()->create($person);
                    }
                }
            }

            // 3. Права доступа
            if (!empty($validated['allowed_users'])) {
                $klient->allowedUsers()->sync($validated['allowed_users']);
            }

            return redirect()->route('klients.index')->with('success', 'Клиент создан');
        });
    }

    /**
     * Просмотр карточки.
     */
    public function show(Klient $klient)
    {
        $this->authorize('view', $klient);

        $klient->load([
            'contactPersons',
            'company:id,name',
            'project:id,name',
            'task:id,title',
            'creator:id,name',
            'allowedUsers:id,name',
            'files.user:id,name',

//            'tasks' => fn($q) => $q->where('status', '!=', 'completed'),
            'tasks.responsible',
            'tasks.files',
            'deals',
            'deals.responsibles',
            'deals.items',
        ]);




        $availableResponsibles = collect([$klient->creator])
            ->merge($klient->allowedUsers)
            ->unique('id');

        return Inertia::render('Klients/Show', [
            'klient' => $klient,
            'auth_id' => Auth::id(),
            'contacts' => $klient->contactPersons,
            'activeTasks' => $klient->tasks,
            'availableResponsibles' => $availableResponsibles
        ]);
    }

    /**
     * Форма редактирования клиента.
     */
    public function edit(Klient $klient)
    {
        // 1. ПРОВЕРКА ПРАВ: только создатель
        if ($klient->user_id !== Auth::id()) {
            abort(403, 'Только создатель карточки может её редактировать.');
        }

        $userId = Auth::id();

        // 2. Получаем списки для выпадающих меню (как в методе create)
        $myCompanyIds = DB::table('companies')
            ->where('user_id', $userId)
            ->union(DB::table('company_user')->where('user_id', $userId)->select('company_id'))
            ->pluck('id')->toArray();



        // Коллеги для доступа
        $mergedUserIds = array_unique(array_merge(
            DB::table('company_user')->whereIn('company_id', $myCompanyIds)->pluck('user_id')->toArray(),
            DB::table('companies')->whereIn('id', $myCompanyIds)->pluck('user_id')->toArray()
        ));

        $colleagues = User::whereIn('id', $mergedUserIds)->where('id', '!=', $userId)->select('id', 'name')->get();

        // Загружаем текущие связи клиента
        $klient->load(['contactPersons', 'allowedUsers']);

        return Inertia::render('Klients/Edit', [
            'klient'     => $klient,

            'colleagues' => $colleagues
        ]);
    }

    /**
     * Обновление данных клиента.
     */
    public function update(Request $request, Klient $klient)
    {
        // 1. ПРОВЕРКА ПРАВ
        if ($klient->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'status'          => 'required|string',

            'segment'         => 'nullable|string',
            'rating'          => 'nullable|string|max:10',
            'phone'           => 'nullable|string',
            'email'           => 'nullable|email',
            'inn'             => 'nullable|string',
            'kpp'             => 'nullable|string',
            'ogrn'            => 'nullable|string',
            'legal_address'   => 'nullable|string',
            'actual_address'  => 'nullable|string',
            'industry'        => 'nullable|string',
            'messengers'      => 'nullable|array',
            'contact_persons' => 'nullable|array',
            'allowed_users'   => 'nullable|array',
        ]);

        return DB::transaction(function () use ($validated, $klient) {
            // 2. Обновляем основные данные
            $klient->update($validated);

            // 3. Обновляем контактных лиц (удаляем старых и создаем новых или обновляем)
            // Самый простой и надежный способ для динамических форм:
            $klient->contactPersons()->delete();
            if (!empty($validated['contact_persons'])) {
                foreach ($validated['contact_persons'] as $person) {
                    if (!empty($person['full_name'])) {
                        $klient->contactPersons()->create($person);
                    }
                }
            }

            // 4. Обновляем права доступа
            $klient->allowedUsers()->sync($validated['allowed_users'] ?? []);

            return redirect()->route('klients.show', $klient->id)->with('success', 'Данные клиента обновлены');
        });
    }



}
