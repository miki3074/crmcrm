<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Project;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use App\Models\ContractFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;

use App\Models\Task;
use App\Models\Subtask;

class ContractController extends Controller
{
    public function searchTasks(Request $request)
    {
        $query = $request->get('query');
        $userId = auth()->id();

        $tasks = Task::forUser($userId)
            ->when($query, fn($q) => $q->where('title', 'like', "%{$query}%"))
            ->select('id', 'title', 'project_id')
            ->with('project:id,name')
            ->limit(10)
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'label' => "Задача #{$t->id}: {$t->title}",
                'project' => $t->project->name ?? 'Без проекта',
                'type' => 'task'
            ]);

        $subtasks = Subtask::forUser($userId)
            ->when($query, fn($q) => $q->where('title', 'like', "%{$query}%"))
            ->select('id', 'title', 'task_id')
            ->with('task:id,title')
            ->limit(10)
            ->get()
            ->map(fn($st) => [
                'id' => $st->id,
                'label' => "Подзадача #{$st->id}: {$st->title} (в {$st->task->title})",
                'project' => 'Подзадача',
                'type' => 'subtask'
            ]);

        $results = $tasks->toBase()->merge($subtasks);

        return response()->json([
            'results' => $results
        ]);
    }

    public function index(Request $request)
    {
        $userId = auth()->id();

        $contracts = Contract::with('creator:id,name', 'files')
            ->where(function (Builder $query) use ($userId) {
                // 1. Контракты, созданные мной
                $query->where('created_by', $userId);

                // 2. Контракты в ЗАДАЧАХ
                $query->orWhereHas('task', function (Builder $q) use ($userId) {
                    // 👇 Везде добавляем 'users.' перед 'id'
                    $q->whereHas('executors', fn($u) => $u->where('users.id', $userId))
                        ->orWhereHas('responsibles', fn($u) => $u->where('users.id', $userId))
                        ->orWhereHas('project', function ($p) use ($userId) {
                            $p->whereHas('managers', fn($u) => $u->where('users.id', $userId))
                                ->orWhereHas('executors', fn($u) => $u->where('users.id', $userId));
                        });
                });

                // 3. Контракты в ПОДЗАДАЧАХ
                $query->orWhereHas('subtask', function (Builder $q) use ($userId) {
                    // 👇 Везде добавляем 'users.' перед 'id'
                    $q->whereHas('executors', fn($u) => $u->where('users.id', $userId))
                        ->orWhereHas('responsibles', fn($u) => $u->where('users.id', $userId))
                        ->orWhereHas('task.project', function ($p) use ($userId) {
                            $p->whereHas('managers', fn($u) => $u->where('users.id', $userId))
                                ->orWhereHas('executors', fn($u) => $u->where('users.id', $userId));
                        });
                });
            })
            ->orderByDesc('created_at')
            ->get();

        return response()->json($contracts);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'type'         => 'required|in:general,dealer,agency,sale,purchase',
            'counterparty' => 'nullable|string|max:255',
            'amount'       => 'nullable|numeric',
            'margin'       => 'nullable|numeric',
            'signed_at'    => 'nullable|date',
            'valid_until'  => 'nullable|date|after_or_equal:signed_at',
            'files.*'      => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:10240',
            'task_id'      => 'nullable|integer|exists:tasks,id',
            'subtask_id'   => 'nullable|integer|exists:subtasks,id',
        ]);

        $data['status'] = 'new';
        $data['created_by'] = auth()->id();

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('contracts', 'public');
            $data['file_path'] = $path;
            $data['file_name'] = $file->getClientOriginalName();
        }

        $contract = Contract::create($data);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store('contracts', 'public');
                $contract->files()->create([
                    'file_path'  => $path,
                    'file_name'  => $file->getClientOriginalName(),
                    'mime_type'  => $file->getMimeType(),
                    'uploaded_by'=> auth()->id(),
                ]);
            }
        }

        return response()->json($contract->load('creator:id,name'), 201);
    }

    public function update(Request $request, Contract $contract)
    {
        abort_unless($contract->created_by === auth()->id(), 403);

        $data = $request->validate([
            'title'        => 'sometimes|required|string|max:255',
            'type'         => 'sometimes|required|in:general,dealer,agency,sale,purchase',
            'counterparty' => 'nullable|string|max:255',
            'amount'       => 'nullable|numeric',
            'margin'       => 'nullable|numeric',
            'status'       => 'required|in:new,negotiation,signed,rejected',
            'signed_at'    => 'nullable|date',
            'valid_until'  => 'nullable|date|after_or_equal:signed_at',
            'task_id'      => 'nullable|integer|exists:tasks,id',
            'subtask_id'   => 'nullable|integer|exists:subtasks,id',
            'files.*'      => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:10240',
        ]);

        $contract->update($data);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store('contracts', 'public');
                $contract->files()->create([
                    'file_path'   => $path,
                    'file_name'   => $file->getClientOriginalName(),
                    'mime_type'   => $file->getMimeType(),
                    'uploaded_by' => auth()->id(),
                ]);
            }
        }

        return $contract->fresh()->load(['creator:id,name', 'files']);
    }

    public function destroy(Contract $contract)
    {
        abort_unless($contract->created_by === auth()->id(), 403);

        if ($contract->file_path) {
            Storage::disk('public')->delete($contract->file_path);
        }
        foreach($contract->files as $file) {
            Storage::disk('public')->delete($file->file_path);
        }
        $contract->files()->delete();

        $contract->delete();

        return response()->json(['message' => 'Договор удалён']);
    }

    public function move(Request $request, Contract $contract)
    {
        abort_unless($contract->created_by === auth()->id(), 403);

        $request->validate([
            'status' => 'required|in:new,negotiation,signed,rejected',
        ]);

        $contract->update([
            'status' => $request->status,
        ]);

        return response()->json(['success' => true]);
    }

    public function deleteFile(ContractFile $file)
    {
        $contract = $file->contract;
        abort_unless($contract->created_by === auth()->id(), 403);

        if ($file->file_path) {
            Storage::disk('public')->delete($file->file_path);
        }

        $file->delete();
        return response()->json(['success' => true]);
    }

    public function downloadFile(ContractFile $file)
    {
        $contract = $file->contract;

        if (!$this->userHasAccessToContract(auth()->id(), $contract)) {
            abort(403, 'У вас нет доступа к этому файлу');
        }

        $path = storage_path('app/public/' . $file->file_path);

        if (!file_exists($path)) {
            abort(404, "Файл не найден");
        }

        return response()->download($path, $file->file_name);
    }

    public function stats(Request $request)
    {
        // Фильтр по датам (например, отчет за текущий месяц или год)
        $startDate = $request->get('start_date', now()->startOfYear());
        $endDate   = $request->get('end_date', now()->endOfYear());

        // Базовый запрос: берем только заключенные (signed) договора
        $query = Contract::where('status', 'signed')
            ->whereBetween('signed_at', [$startDate, $endDate]);

        // 1. Общий оборот (сумма всех продаж и агентских договоров)
        $totalTurnover = (clone $query)
            ->whereIn('type', ['sale', 'agency'])
            ->sum('amount');

        // 2. Чистая прибыль (сумма margin)
        // Считаем маржу только с продаж и агентских (расходы не учитываем здесь)
        $totalMargin = (clone $query)
            ->whereIn('type', ['sale', 'agency'])
            ->sum('margin');

        // 3. Количество активных дилерских договоров (не зависит от даты подписания, зависит от valid_until)
        $activeDealerContracts = Contract::where('type', 'dealer')
            ->where('status', 'signed')
            ->where(function($q) {
                $q->whereNull('valid_until') // Бессрочные
                ->orWhere('valid_until', '>=', now()); // Или срок еще не истек
            })
            ->count();

        // 4. Разбивка по типам (для круговой диаграммы)
        $byType = (clone $query)
            ->selectRaw('type, sum(amount) as total_amount, sum(margin) as total_margin, count(*) as count')
            ->groupBy('type')
            ->get();

        return response()->json([
            'period' => [
                'start' => $startDate,
                'end' => $endDate
            ],
            'summary' => [
                'turnover' => $totalTurnover,   // Оборот
                'profit'   => $totalMargin,     // Прибыль (комиссия)
                'dealers_count' => $activeDealerContracts // Действующие дилерские соглашения
            ],
            'breakdown' => $byType // Детализация
        ]);
    }

    public function show(Contract $contract)
    {
        // Проверка прав (используем вспомогательный метод, который мы писали ранее)
        if (!$this->userHasAccessToContract(auth()->id(), $contract)) {
            abort(403, 'У вас нет доступа к этому договору');
        }

        // Возвращаем данные договора со всеми связями
        return response()->json(
            $contract->load(['creator:id,name', 'files', 'task:id,title,project_id', 'task.project:id,name', 'subtask:id,title'])
        );
    }

    public function download(Contract $contract)
    {
        // Проверка прав
        if (!$this->userHasAccessToContract(auth()->id(), $contract)) {
            abort(403, 'У вас нет доступа к этому файлу');
        }

        // Проверяем, есть ли файл
        if (!$contract->file_path) {
            abort(404, 'Файл не прикреплен к этому договору');
        }

        $path = storage_path('app/public/' . $contract->file_path);

        if (!file_exists($path)) {
            abort(404, "Файл физически отсутствует на диске");
        }

        // Скачиваем
        return response()->download($path, $contract->file_name ?? 'contract.pdf');
    }

    public function generateReport(Request $request)
    {
        $filterType = $request->get('filter_type', 'all');
        $targetId   = $request->get('target_id');
        $startDate  = $request->get('start_date');
        $endDate    = $request->get('end_date');

        // Жадная загрузка связей для оптимизации
        $query = Contract::with(['creator', 'task.project', 'subtask.task.project'])
            ->orderBy('signed_at', 'desc');

        // 1. Фильтр ПО ПРОЕКТУ
        if ($filterType === 'project' && $targetId) {
            $query->where(function ($q) use ($targetId) {
                // А. Договор привязан напрямую к задаче этого проекта
                $q->whereHas('task', function ($t) use ($targetId) {
                    $t->where('project_id', $targetId);
                })
                    // Б. ИЛИ Договор привязан к подзадаче, которая относится к задаче этого проекта
                    ->orWhereHas('subtask.task', function ($t) use ($targetId) {
                        $t->where('project_id', $targetId);
                    });
            });
        }

        // 2. Фильтр ПО ЗАДАЧЕ
        elseif ($filterType === 'task' && $targetId) {
            $query->where(function ($q) use ($targetId) {
                // А. Договор привязан к этой задаче
                $q->where('task_id', $targetId)
                    // Б. ИЛИ Договор привязан к любой подзадаче ЭТОЙ задачи
                    ->orWhereHas('subtask', function ($s) use ($targetId) {
                        $s->where('task_id', $targetId);
                    });
            });
        }

        // 3. Фильтр по датам
        if ($startDate && $endDate) {
            $query->whereBetween('signed_at', [$startDate, $endDate]);
        }

        $contracts = $query->get();

        $totalAmount = $contracts->sum('amount');
        $totalMargin = $contracts->sum('margin');

        // Генерация PDF
        $pdf = PDF::loadView('reports.contracts', [
            'contracts'   => $contracts,
            'filterType'  => $filterType,
            'totalAmount' => $totalAmount,
            'totalMargin' => $totalMargin,
            'dateRange'   => ($startDate && $endDate) ? "$startDate - $endDate" : 'За все время'
        ]);

        return $pdf->download('otchet_contracts_' . date('Y-m-d') . '.pdf');
    }

// Вспомогательный метод для поиска проектов (для выпадающего списка в отчете)
    public function searchProjects(Request $request)
    {
        $query = $request->get('query');
        $projects = Project::where('name', 'like', "%{$query}%")
            ->select('id', 'name')
            ->limit(10)
            ->get();

        return response()->json($projects);
    }

    /**
     * Вспомогательный метод для проверки доступа
     */
    private function userHasAccessToContract($userId, Contract $contract)
    {
        if ($contract->created_by === $userId) {
            return true;
        }

        // Проверка через Задачу
        if ($contract->task_id) {
            $task = $contract->task;
            if ($task) {
                $isExecutor = $task->executors->contains('id', $userId);
                $isResponsible = $task->responsibles->contains('id', $userId);

                $isProjectUser = false;
                $isProjectExecutor = false;
                if ($task->project) {
                    // 👇 ИСПРАВЛЕНО: managers вместо users
                    $isProjectUser = $task->project->managers->contains('id', $userId);
                    $isProjectExecutor = $task->project->executors->contains('id', $userId);
                }

                if ($isExecutor || $isResponsible || $isProjectUser || $isProjectExecutor) {
                    return true;
                }
            }
        }

        // Проверка через Подзадачу
        if ($contract->subtask_id) {
            $subtask = $contract->subtask;
            if ($subtask) {
                $isExecutor = $subtask->executors->contains('id', $userId);
                $isResponsible = $subtask->responsibles->contains('id', $userId);

                $isProjectAccess = false;
                if ($subtask->task && $subtask->task->project) {
                    $project = $subtask->task->project;
                    // 👇 ИСПРАВЛЕНО: managers вместо users
                    $isProjectAccess = $project->managers->contains('id', $userId) ||
                        $project->executors->contains('id', $userId);
                }

                if ($isExecutor || $isResponsible || $isProjectAccess) {
                    return true;
                }
            }
        }

        return false;
    }


}
