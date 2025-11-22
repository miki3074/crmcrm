<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskCalendarController extends Controller
{
     public function index(Request $request)
    {
        $user = $request->user();

        $filter = $request->query('filter');

        $q = Task::query()
            ->with([
                'project:id,name,company_id',
                'company:id,name,user_id',
                'executors:id',
                'responsibles:id',
                'watchers:id'
            ])
            ->whereNotNull('start_date')
            ->whereNotNull('due_date');

        // 🔥Фильтр по доступу
        $q->where(function($x) use ($user) {

            $x->where('creator_id', $user->id);                       // Я создал задачу
            $x->orWhere('company_id', $user->company_id);             // Я владелец / сотрудник компании

            // Я руководитель проекта
             $x->orWhereHas('project.managers', fn($q) =>
        $q->where('users.id', $user->id)
    );

            // Я исполнитель проекта
            $x->orWhereHas('project.executors', fn($q) =>
                $q->where('users.id', $user->id)
            );

            // Я наблюдатель проекта
            $x->orWhereHas('project.watchers', fn($q) =>
                $q->where('users.id', $user->id)
            );

            // Я исполнитель задачи
            $x->orWhereHas('executors', fn($q) =>
                $q->where('users.id', $user->id)
            );

            // Я ответственный задачи
            $x->orWhereHas('responsibles', fn($q) =>
                $q->where('users.id', $user->id)
            );

            // Я наблюдатель задачи
            $x->orWhereHas('watchers', fn($q) =>
                $q->where('users.id', $user->id)
            );
        });

        // Фильтрация по панели
        if ($filter === 'my') {
            $q->where('creator_id', $user->id);
        }

        return $q->get()->map(function($t){
    $isOverdue = strtotime($t->due_date) < time();

    return [
        'id'          => 'task_'.$t->id,
        'task_id'     => $t->id,
        'title'       => $t->title,
        'start'       => $t->start_date,
        'end'         => $t->due_date,
        'priority'    => $t->priority,
        'project'     => $t->project?->name ?? null,
        'company'     => $t->company?->name ?? null,
        'is_overdue'  => $isOverdue,
    ];
});

    }
}
