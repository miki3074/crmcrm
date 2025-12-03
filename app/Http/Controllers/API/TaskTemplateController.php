<?php

// app/Http/Controllers/API/TaskTemplateController.php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\Producer;
use App\Models\TaskTemplate;
use App\Models\TaskTemplateFile;
use App\Models\Company;
use App\Models\Project;
use App\Models\User;
use App\Models\Task;
use App\Models\TaskFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;


class TaskTemplateController extends Controller
{
    /**
     * Список шаблонов, доступных пользователю
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $templates = TaskTemplate::with(['company:id,name', 'project:id,name', 'creator:id,name', 'files','producer:id,name','buyer:id,name'])
            ->where(function ($q) use ($user) {
                // шаблоны, где пользователь — создатель
                $q->where('creator_id', $user->id);

                // или принадлежит компании (через company_user)
                $q->orWhereIn('company_id', function ($sub) use ($user) {
                    $sub->select('company_id')
                        ->from('company_user')
                        ->where('user_id', $user->id);
                });

                // или является владельцем компании
                $q->orWhereIn('company_id', function ($sub) use ($user) {
                    $sub->select('id')
                        ->from('companies')
                        ->where('user_id', $user->id);
                });
            })
            ->orderByDesc('created_at')
            ->get();

        return response()->json($templates);
    }

    /**
     * Компании, в которых пользователь участвует + компании, где он владелец
     */
    public function companiesForUser(Request $request)
    {
        $user = $request->user();

        // мои компании (я владелец)
        $myCompanies = Company::where('user_id', $user->id)
            ->select('id', 'name', 'user_id')
            ->get();

        // компании, где я участник через company_user
        $memberCompanies = Company::whereIn('id', function ($q) use ($user) {
            $q->select('company_id')
                ->from('company_user')
                ->where('user_id', $user->id);
        })
            ->select('id', 'name', 'user_id')
            ->get();

        return response()->json([
            'owned'   => $myCompanies,
            'member'  => $memberCompanies,
        ]);
    }

    /**
     * Проекты выбранной компании (чтобы выбрать проект шаблона)
     */
    public function projectsByCompany(Request $request, Company $company)
    {
        $user = $request->user();

        // простая проверка, что пользователь имеет отношение к компании
        $hasAccess = $company->user_id === $user->id ||
            DB::table('company_user')
                ->where('company_id', $company->id)
                ->where('user_id', $user->id)
                ->exists();

        abort_unless($hasAccess, 403);

        $projects = $company->projects()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json($projects);
    }

    /**
     * Список сотрудников компании (для исполнителей/ответственных/наблюдателей)
     */
    public function employeesByCompany(Request $request, Company $company)
    {
        $user = $request->user();

        $hasAccess = $company->user_id === $user->id ||
            DB::table('company_user')
                ->where('company_id', $company->id)
                ->where('user_id', $user->id)
                ->exists();

        abort_unless($hasAccess, 403);

        // свои пользователи компании (из company_user + владелец)
        $userIds = DB::table('company_user')
            ->where('company_id', $company->id)
            ->pluck('user_id')
            ->push($company->user_id)
            ->unique()
            ->all();

        $employees = User::whereIn('id', $userIds)
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return response()->json($employees);
    }

    /**
     * Создание шаблона
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'company_id'       => 'required|exists:companies,id',
            'project_id'       => 'required|exists:projects,id',
            'title'            => 'required|string|max:255',
            'description'      => 'nullable|string',
            'executor_ids'     => 'array',
            'executor_ids.*'   => 'integer|exists:users,id',
            'responsible_ids'  => 'array',
            'responsible_ids.*'=> 'integer|exists:users,id',
            'watcher_ids'      => 'array',
            'watcher_ids.*'    => 'integer|exists:users,id',
            'due_in_days'      => 'nullable|integer|min:0',
            'priority'         => 'required|in:low,medium,high',
            'files.*'          => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:10240',
            'producer_id' => 'nullable|exists:producers,id',
            'buyer_id' => 'nullable|exists:buyers,id',
        ]);

        $company = Company::findOrFail($data['company_id']);

        // проверяем, что пользователь имеет право работать с этой компанией
        $hasAccess = $company->user_id === $user->id ||
            DB::table('company_user')
                ->where('company_id', $company->id)
                ->where('user_id', $user->id)
                ->exists();

        abort_unless($hasAccess, 403);

        // проверяем, что проект действительно принадлежит компании
        $project = Project::where('id', $data['project_id'])
            ->where('company_id', $company->id)
            ->firstOrFail();

        $template = TaskTemplate::create([
            'company_id'      => $company->id,
            'project_id'      => $project->id,
            'creator_id'      => $user->id,
            'title'           => $data['title'],
            'description'     => $data['description'] ?? null,
            'executor_ids'    => $data['executor_ids'] ?? [],
            'responsible_ids' => $data['responsible_ids'] ?? [],
            'watcher_ids'     => $data['watcher_ids'] ?? [],
            'due_in_days'     => $data['due_in_days'] ?? null,
            'priority'        => $data['priority'],
            'producer_id' => $data['producer_id'] ?? null,
            'buyer_id' => $data['buyer_id'] ?? null,
        ]);

        // файлы шаблона
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store('task_templates', 'public');

                $template->files()->create([
                    'file_path'   => $path,
                    'file_name'   => $file->getClientOriginalName(),
                    'mime_type'   => $file->getMimeType(),
                    'uploaded_by' => $user->id,
                ]);
            }
        }

        return response()->json($template->load(['company:id,name', 'project:id,name', 'files']), 201);
    }

    /**
     * Обновление шаблона (если нужно)
     */
    public function update(Request $request, TaskTemplate $template)
    {
        $user = $request->user();

        // только создатель или владелец компании
        $template->load('company');
        $hasAccess = $template->creator_id === $user->id ||
            $template->company->user_id === $user->id;

        abort_unless($hasAccess, 403);

        $data = $request->validate([
            'title'            => 'sometimes|required|string|max:255',
            'description'      => 'nullable|string',
            'executor_ids'     => 'array',
            'executor_ids.*'   => 'integer|exists:users,id',
            'responsible_ids'  => 'array',
            'responsible_ids.*'=> 'integer|exists:users,id',
            'watcher_ids'      => 'array',
            'watcher_ids.*'    => 'integer|exists:users,id',

            'producer_id'      => 'nullable|exists:producers,id',
            'buyer_id'         => 'nullable|exists:buyers,id',

            'due_in_days'      => 'nullable|integer|min:0',
            'priority'         => 'sometimes|required|in:low,medium,high',
            'files.*'          => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:10240',
        ]);

        $template->update($data);

        // новые файлы добавляем (старые не трогаем)
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store('task_templates', 'public');

                $template->files()->create([
                    'file_path'   => $path,
                    'file_name'   => $file->getClientOriginalName(),
                    'mime_type'   => $file->getMimeType(),
                    'uploaded_by' => $user->id,
                ]);
            }
        }

        return $template->fresh()->load(['company:id,name', 'project:id,name', 'files']);
    }

    /**
     * Удалить шаблон
     */
    public function destroy(Request $request, TaskTemplate $template)
    {
        $user = $request->user();
        $template->load('company');

        $hasAccess = $template->creator_id === $user->id ||
            $template->company->user_id === $user->id;

        abort_unless($hasAccess, 403);

        $template->delete();

        return response()->json(['message' => 'Шаблон удалён']);
    }

    /**
     * Удалить отдельный файл шаблона
     */
    public function deleteFile(Request $request, TaskTemplateFile $file)
    {
        $user = $request->user();
        $template = $file->template()->with('company')->firstOrFail();

        $hasAccess = $template->creator_id === $user->id ||
            $template->company->user_id === $user->id;

        abort_unless($hasAccess, 403);

        if ($file->file_path) {
            Storage::disk('public')->delete($file->file_path);
        }

        $file->delete();

        return response()->json(['success' => true]);
    }

    /**
     * СОЗДАТЬ ЗАДАЧУ ПО ШАБЛОНУ
     *
     * Фронт:
     *  - делает GET /api/task-templates → выводит список
     *  - кнопка "Создать задачу по шаблону"
     *  - модалка с полями (можно менять исполнителей, дедлайн и т.п.)
     *  - отправляет POST сюда
     */
    public function createTaskFromTemplate(Request $request, TaskTemplate $template)
    {
        $user = $request->user();
        $template->load(['company', 'project', 'files']);

        // проверяем доступ к компании
        $hasAccess = $template->company->user_id === $user->id ||
            DB::table('company_user')
                ->where('company_id', $template->company_id)
                ->where('user_id', $user->id)
                ->exists();

        abort_unless($hasAccess, 403);

        $data = $request->validate([
            'title'           => 'nullable|string|max:255',
            'description'     => 'nullable|string',
            'start_date'      => 'required|date',
            'due_date'        => 'nullable|date|after_or_equal:start_date',
            'executor_ids'    => 'array',
            'executor_ids.*'  => 'integer|exists:users,id',
            'responsible_ids' => 'array',
            'responsible_ids.*'=> 'integer|exists:users,id',
            'watcher_ids'     => 'array',
            'watcher_ids.*'   => 'integer|exists:users,id',
            'priority'        => 'nullable|in:low,medium,high',
        ]);

        // Заголовок: либо свой, либо из шаблона
        $title       = $data['title'] ?? $template->title;
        $description = $data['description'] ?? $template->description;

        // Исполнители/ответственные/наблюдатели:
        $executorIds    = $data['executor_ids']    ?? ($template->executor_ids ?? []);
        $responsibleIds = $data['responsible_ids'] ?? ($template->responsible_ids ?? []);
        $watcherIds     = $data['watcher_ids']     ?? ($template->watcher_ids ?? []);

        // Приоритет: можно override, если не передали — из шаблона
        $priority = $data['priority'] ?? $template->priority;

        // Дедлайн:
        $startDate = Carbon::parse($data['start_date']);

        if (!empty($data['due_date'])) {
            $dueDate = Carbon::parse($data['due_date']);
        } elseif (!empty($template->due_in_days)) {
            $dueDate = $startDate->copy()->addDays($template->due_in_days);
        } else {
            $dueDate = null;
        }

        // Создаём задачу (используем твою модель Task)
        $task = Task::create([
            'title'       => $title,
            'description' => $description,
            'priority'    => $priority,
            'start_date'  => $startDate->toDateString(),
            'due_date'    => $dueDate?->toDateString(),
            'project_id'  => $template->project_id,
            'company_id'  => $template->company_id,
            'creator_id'  => $user->id,
        ]);

        if (!empty($template->producer_id)) {
            $task->producers()->attach($template->producer_id);
        }

// === Привязка покупателя ===
        if (!empty($template->buyer_id)) {
            $task->buyers()->attach($template->buyer_id);
        }

        if (!empty($executorIds)) {
            $task->executors()->attach($executorIds);
        }

        if (!empty($responsibleIds)) {
            $task->responsibles()->attach($responsibleIds);
        }

        if (!empty($watcherIds)) {
            $task->watchers()->attach($watcherIds);
        }

        // При желании можно скопировать файлы шаблона в файлы задачи
        foreach ($template->files as $file) {
            // просто "подвязываем" тот же файл к задаче,
            // если ты хочешь реально копировать — можно Storage::copy()
            TaskFile::create([
                'task_id'   => $task->id,
                'file_path' => $file->file_path,
                'file_name' => $file->file_name,
                'mime_type' => $file->mime_type,
            ]);
        }

        return response()->json($task->load(['executors', 'responsibles', 'watchers']), 201);
    }

    public function duplicate(Request $request, TaskTemplate $template)
    {
        $user = $request->user();

        $template->load('company', 'project', 'files');

        // любой, кто видит шаблон, может дублировать
        $data = $request->validate([
            'title'           => 'required|string|max:255',
            'description'     => 'nullable|string',
            'executor_ids'    => 'array',
            'responsible_ids' => 'array',
            'watcher_ids'     => 'array',
            'priority'        => 'required|in:low,medium,high',
            'due_in_days'     => 'nullable|integer',
            'producer_id'     => 'nullable|exists:producers,id',
            'buyer_id'        => 'nullable|exists:buyers,id',

        ]);

        // создаём НОВЫЙ шаблон
        $new = TaskTemplate::create([
            'company_id'      => $template->company_id,
            'project_id'      => $template->project_id,
            'creator_id'      => $user->id, // ← Дублирующий становится создателем
            'title'           => $data['title'],
            'description'     => $data['description'] ?? null,
            'executor_ids'    => $data['executor_ids'] ?? [],
            'responsible_ids' => $data['responsible_ids'] ?? [],
            'watcher_ids'     => $data['watcher_ids'] ?? [],
            'priority'        => $data['priority'],
            'due_in_days'     => $data['due_in_days'] ?? null,
            'producer_id'     => $data['producer_id'] ?? null,
            'buyer_id'        => $data['buyer_id'] ?? null,
        ]);

        // нужно правильно преобразовывать строку "false" → false
        $copyFiles = filter_var($request->input('copy_files', true), FILTER_VALIDATE_BOOLEAN);

// копируем файлы, только если copy_files = true
        if ($copyFiles) {
            foreach ($template->files as $file) {
                $new->files()->create([
                    'file_path'   => $file->file_path,
                    'file_name'   => $file->file_name,
                    'mime_type'   => $file->mime_type,
                    'uploaded_by' => $user->id,
                ]);
            }
        }


        // сохранение новых файлов
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $newFile) {
                $path = $newFile->store('task_templates', 'public');

                $new->files()->create([
                    'file_path' => $path,
                    'file_name' => $newFile->getClientOriginalName(),
                    'mime_type' => $newFile->getMimeType(),
                    'uploaded_by' => $user->id,
                ]);
            }
        }


        return response()->json($new->load('company:id,name', 'project:id,name', 'files'), 201);
    }


}

