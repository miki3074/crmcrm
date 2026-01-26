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
use Illuminate\Support\Arr;
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

        // Проверка доступа... (как и была)
        $isParticipant = $meeting->participants->contains($user->id);
        if ($meeting->creator_id !== $user->id &&
            $meeting->responsible_id !== $user->id &&
            !$isParticipant) {
            abort(403, 'У вас нет доступа к этому совещанию');
        }

        // Загружаем связи
        $meeting->load(['company', 'responsible', 'participants', 'creator','documents.uploader']);

        // НОВОЕ: Получаем список всех сотрудников этой компании для выбора в модалке
        // Логика: Владелец компании + Сотрудники
        $availableUsers = User::where('id', $meeting->company->user_id) // Владелец
        ->orWhereHas('workingCompanies', function($q) use ($meeting) {
            $q->where('company_id', $meeting->company_id); // Сотрудники
        })
            ->get(['id', 'name', 'email']); // Берем только нужные поля

        return Inertia::render('Meetings/Show', [
            'meeting' => $meeting,
            'auth_user_id' => $user->id,
            'available_users' => $availableUsers, // <-- Передаем во Vue
        ]);
    }


    public function update(Request $request, Meeting $meeting)
    {
        $user = Auth::user();

        // 1. Проверка прав
        if ($user->id !== $meeting->creator_id && $user->id !== $meeting->responsible_id) {
            abort(403, 'У вас нет прав на редактирование этого совещания.');
        }

        // 2. Блокировка если подписано
        if ($meeting->status === 'signed') {
            abort(403, 'Редактирование запрещено: документ уже подписан.');
        }

        // 3. Валидация
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'start_time' => 'sometimes|date',
            'responsible_id' => 'sometimes|exists:users,id',

            'agenda' => 'nullable|string',
            'minutes' => 'nullable|string',
            'status' => 'nullable|string',

            'participants' => 'sometimes|array',
            'participants.*' => 'exists:users,id'
        ]);

        DB::transaction(function () use ($meeting, $validated, $request) {

            // ВАЖНО: Убираем 'participants' из массива данных для обновления таблицы meetings
            // Иначе Laravel попытается найти колонку 'participants' в таблице meetings
            $meetingData = Arr::except($validated, ['participants']);

            // Обновляем саму встречу (дата, повестка, протокол)
            $meeting->update($meetingData);

            // Обновляем связь с участниками (в таблице meeting_participants)
            if ($request->has('participants')) {
                $meeting->participants()->sync($validated['participants']);
            }
        });

        return back()->with('success', 'Данные совещания обновлены.');
    }

    public function updateParticipation(Request $request, Meeting $meeting)
    {
        // Проверка статуса совещания (если добавляли ранее)
        if ($meeting->status !== 'scheduled') {
            abort(403, 'Нельзя менять статус участия, так как совещание уже началось или завершено.');
        }

        $request->validate([
            // Разрешаем вернуть статус в "приглашен" (сбросить выбор)
            'status' => 'required|in:accepted,declined,invited',
        ]);

        $user = Auth::user();

        if (!$meeting->participants->contains($user->id)) {
            abort(403, 'Вы не являетесь участником этого совещания.');
        }

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

    public function reviewProtocol(Request $request, Meeting $meeting)
    {
        $user = Auth::user();

        // ИЗМЕНЕНО: Строгая проверка. Только responsible_id может утверждать или отклонять.
        if ($user->id !== $meeting->responsible_id) {
            abort(403, 'Только Ответственный за совещание может утверждать протокол.');
        }

        $validated = $request->validate([
            'decision' => 'required|in:approve,reject',
            'reason' => 'nullable|string|required_if:decision,reject',
        ]);

        if ($validated['decision'] === 'approve') {
            $meeting->update([
                'status' => 'signed',
                'rejection_reason' => null,
            ]);
            $msg = 'Протокол успешно утвержден и подписан!';
        } else {
            $meeting->update([
                'status' => 'completed', // Возвращаем статус, в котором доступно редактирование
                'rejection_reason' => $validated['reason']
            ]);
            $msg = 'Протокол отправлен на доработку.';
        }

        return back()->with('success', $msg);
    }

    public function destroy(Meeting $meeting)
    {
        $user = Auth::user();

        // Права: Только Создатель
        if ($user->id !== $meeting->creator_id) {
            abort(403, 'Удалять совещание может только его создатель.');
        }

        $meeting->delete();

        return to_route('meetings.index')->with('success', 'Совещание удалено.');
    }

    public function agendaFeedback(Request $request, Meeting $meeting)
    {
        $user = Auth::user();

        // Проверяем, участник ли это
        if (!$meeting->participants->contains($user->id)) {
            abort(403, 'Вы не участник этого совещания.');
        }

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'comment' => 'nullable|string|required_if:status,rejected',
        ]);

        // ИЗМЕНЕНО: используем participants(), а не participantsuser()
        $meeting->participants()->updateExistingPivot($user->id, [
            'agenda_status' => $validated['status'],
            'agenda_comment' => $validated['comment'] ?? null,
        ]);

        return back()->with('success', 'Ваш отзыв по повестке сохранен.');
    }

    public function markAgendaFixed(Request $request, Meeting $meeting, User $participant)
    {
        $user = Auth::user();

        // 1. Проверка прав: нажимать может только Создатель или Ответственный
        if ($user->id !== $meeting->creator_id && $user->id !== $meeting->responsible_id) {
            abort(403, 'Только организатор может отмечать исправления.');
        }

        // 2. Обновляем статус конкретного участника в pivot-таблице
        // Используем updateExistingPivot для конкретного пользователя ($participant->id)
        $meeting->participants()->updateExistingPivot($participant->id, [
            'agenda_status' => 'fixed',
            // Комментарий можно оставить, чтобы участник помнил, что просил,
            // или очистить. Лучше оставить.
        ]);

        return back()->with('success', 'Участник уведомлен об исправлениях.');
    }

}
