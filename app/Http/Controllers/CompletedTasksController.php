<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Task;
use App\Models\Subtask;
use App\Models\Company; // 👈 Добавили модель Company
use Illuminate\Support\Facades\Auth;

class CompletedTasksController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Получаем ID компаний, которыми владеет текущий пользователь
        $ownedCompanyIds = Company::where('user_id', $user->id)->pluck('id');

        // --- 1. ЗАВЕРШЕННЫЕ ЗАДАЧИ ---
        $tasksQuery = Task::withoutGlobalScope('not_completed')
            ->where('completed', true)
            ->with(['project:id,name', 'creator:id,name', 'executors:id,name', 'responsibles:id,name']);

        // Логика фильтрации:
        // Показываем задачу, ЕСЛИ:
        // 1. Пользователь участвует в ней (автор, исполнитель, ответственный)
        // 2. ИЛИ Пользователь является владельцем компании, к которой относится задача
        $tasksQuery->where(function ($q) use ($user, $ownedCompanyIds) {
            $q->where('creator_id', $user->id)
                ->orWhereHas('executors', fn($sq) => $sq->where('users.id', $user->id))
                ->orWhereHas('responsibles', fn($sq) => $sq->where('users.id', $user->id))
                ->orWhereHas('watcherstask', fn($sq) => $sq->where('users.id', $user->id))
                // 👇 Добавляем условие для владельца: если задача принадлежит моей компании — показываем
                ->orWhereIn('company_id', $ownedCompanyIds);
        });



        $tasks = $tasksQuery->orderBy('completed_at', 'desc')->get();

        // --- 2. ЗАВЕРШЕННЫЕ ПОДЗАДАЧИ ---
        $subtasksQuery = Subtask::where('completed', true)
            ->with(['task:id,title,project_id,company_id', 'task.project:id,name', 'creator:id,name', 'executors:id,name']);

        $subtasksQuery->where(function ($q) use ($user, $ownedCompanyIds) {
            $q->where('creator_id', $user->id)
                ->orWhereHas('executors', fn($sq) => $sq->where('users.id', $user->id))
                ->orWhereHas('responsibles', fn($sq) => $sq->where('users.id', $user->id))
                // 👇 Добавляем условие для владельца через родительскую задачу
                ->orWhereHas('task', fn($tq) => $tq->whereIn('company_id', $ownedCompanyIds));
        });

        $subtasks = $subtasksQuery->orderBy('completed_at', 'desc')->get();

        return Inertia::render('Tasks/CompletedList', [
            'tasks' => $tasks,
            'subtasks' => $subtasks
        ]);
    }
}
