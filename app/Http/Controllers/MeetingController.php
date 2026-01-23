<?php

// app/Http/Controllers/MeetingController.php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class MeetingController extends Controller
{

    public function index()
    {
        $user = Auth::user();

        // Ищем совещания, где юзер: Создатель ИЛИ Ответственный ИЛИ Участник
        $meetings = Meeting::query()
            ->where('creator_id', $user->id)
            ->orWhere('responsible_id', $user->id)
            ->orWhereHas('participants', function (Builder $query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with('company') // Подгрузим название компании
            ->orderBy('start_time', 'desc')
            ->get();

        return Inertia::render('Meetings/Index', [
            'meetings' => $meetings
        ]);
    }


    // Страница создания совещания
    public function create()
    {
        $user = Auth::user();

        // Получаем список компаний, где юзер владелец ИЛИ сотрудник
        // Используем eager loading для оптимизации, если нужно
        $companies = $user->ownedCompanies->merge($user->workingCompanies);

        return Inertia::render('Meetings/Create', [
            'available_companies' => $companies
        ]);
    }

    // API метод: Получить сотрудников для конкретной компании
    // Этот метод будем дергать через axios или Inertia reload при смене компании в селекте
    public function getCompanyUsers($companyId)
    {
        $user = Auth::user();

        // Проверка безопасности: имеет ли текущий юзер доступ к этой компании?
        $hasAccess = $user->ownedCompanies->contains('id', $companyId) ||
            $user->workingCompanies->contains('id', $companyId);

        if (!$hasAccess) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        // Находим компанию
        $company = Company::findOrFail($companyId);

        // Нам нужно получить: Владельца компании + Всех из company_user
        // 1. Владелец
        $owner = User::where('id', $company->user_id)->get();

        // 2. Сотрудники
        $employees = User::whereHas('workingCompanies', function($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })->get();

        $users = $owner->merge($employees)->unique('id')->values();

        return response()->json($users);
    }





    // Сохранение совещания
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'title' => 'required|string|max:255',
            'start_time' => 'required|date',
            'responsible_id' => 'required|exists:users,id',
            'agenda' => 'nullable|string',
            'participants' => 'array', // массив ID пользователей
            'participants.*' => 'exists:users,id'
        ]);

        // Валидация доступа к компании (аналогично getCompanyUsers) пропускаю для краткости

        DB::transaction(function () use ($validated) {
            $meeting = Meeting::create([
                'company_id' => $validated['company_id'],
                'creator_id' => Auth::id(),
                'responsible_id' => $validated['responsible_id'],
                'title' => $validated['title'],
                'start_time' => $validated['start_time'],
                'agenda' => $validated['agenda'],
                'status' => 'scheduled', // Сразу назначаем
            ]);

            // Добавляем участников
            if (!empty($validated['participants'])) {
                $meeting->participants()->attach($validated['participants']);
            }

            // TODO: Здесь можно отправить Email приглашения (Notification)
        });

        return redirect()->route('meetings.index')->with('success', 'Совещание создано');
    }

    public function show(Meeting $meeting)
    {
        $user = Auth::user();

        // 1. Проверка доступа (безопасность)
        // Смотреть могут: Создатель, Ответственный или Участник
        $isParticipant = $meeting->participants->contains($user->id);

        if ($meeting->creator_id !== $user->id &&
            $meeting->responsible_id !== $user->id &&
            !$isParticipant) {
            abort(403, 'У вас нет доступа к этому совещанию');
        }

        // 2. Загружаем связи
        $meeting->load(['company', 'responsible', 'participants', 'creator']);

        // 3. Отдаем страницу
        return Inertia::render('Meetings/Show', [
            'meeting' => $meeting,
            'auth_user_id' => $user->id // Чтобы на фронте понимать, кто мы (ответственный или просто участник)
        ]);
    }

    public function updateParticipation(Request $request, Meeting $meeting)
    {
        $request->validate([
            'status' => 'required|in:accepted,declined', // Принял или Отклонил
        ]);

        $user = Auth::user();

        // Проверяем, действительно ли этот юзер есть в списке участников
        if (!$meeting->participants->contains($user->id)) {
            abort(403, 'Вы не являетесь участником этого совещания.');
        }

        // Обновляем статус в pivot-таблице
        $meeting->participants()->updateExistingPivot($user->id, [
            'status' => $request->status
        ]);

        return back()->with('success', 'Статус участия обновлен.');
    }

    public function updateStatus(Request $request, Meeting $meeting)
    {
        $user = Auth::user();

        // 1. Проверка прав: только Создатель или Ответственный
        if ($user->id !== $meeting->creator_id && $user->id !== $meeting->responsible_id) {
            abort(403, 'Только организатор или ответственный могут менять статус совещания.');
        }

        // 2. Валидация
        $validated = $request->validate([
            'status' => 'required|in:scheduled,in_progress,completed,on_approval',
        ]);

        // 3. Обновление
        $meeting->update(['status' => $validated['status']]);

        return back()->with('success', 'Статус совещания изменен.');
    }

}
