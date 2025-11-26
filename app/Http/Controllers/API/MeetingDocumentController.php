<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\MeetingDocument;
use Illuminate\Http\Request;

use App\Services\TelegramService;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;


class MeetingDocumentController extends Controller
{
    public function index(Request $request)
{
    $userId = auth()->id();

    // 1. пользователю доступны документы, где он — создатель
    // 2. участник задачи (ответственный, исполнитель, наблюдатель)
    // 3. участник подзадачи

    $query = MeetingDocument::with([
        'task:id,title',
        'subtask:id,title',
        'creator:id,name'
    ])
    ->where(function ($q) use ($userId) {

        // 1. Автор документа
        $q->where('created_by', $userId)

        // 2. Привязан к задаче, где есть пользователь
        ->orWhere(function ($q) use ($userId) {
            $q->whereNotNull('task_id')
              ->whereIn('task_id', function ($sub) use ($userId) {
                  $sub->select('task_id')->from('task_responsibles')->where('user_id', $userId);
              })
              ->orWhereIn('task_id', function ($sub) use ($userId) {
                  $sub->select('task_id')->from('task_executors')->where('user_id', $userId);
              })
              ->orWhereIn('task_id', function ($sub) use ($userId) {
                  $sub->select('task_id')->from('task_user_watchers')->where('user_id', $userId);
              });
        })

        // 3. Привязан к подзадаче, где есть пользователь
        ->orWhere(function ($q) use ($userId) {
            $q->whereNotNull('subtask_id')
              ->whereIn('subtask_id', function ($sub) use ($userId) {
                  $sub->select('subtask_id')->from('subtask_responsibles')->where('user_id', $userId);
              })
              ->orWhereIn('subtask_id', function ($sub) use ($userId) {
                  $sub->select('subtask_id')->from('subtask_executors')->where('user_id', $userId);
              });
        });
    })
    ->orderByDesc('document_date')
    ->orderByDesc('id');

     // ===== ФИЛЬТР: мои / чужие / все =====
    if ($request->filter === 'my') {
        $query->where('created_by', $userId);
    }

    if ($request->filter === 'others') {
        $query->where('created_by', '!=', $userId);
    }

    // ===== Фильтр по названию =====
    if ($request->filled('search')) {
        $query->where('title', 'like', "%{$request->search}%");
    }

    // ===== Фильтр по датам =====
    if ($request->filled('date_from')) {
        $query->whereDate('document_date', '>=', $request->date_from);
    }

    if ($request->filled('date_to')) {
        $query->whereDate('document_date', '<=', $request->date_to);
    }


    return $query->get();
}


    public function show(MeetingDocument $meetingDocument)
    {
        return $meetingDocument->load([
            'task:id,title',
            'subtask:id,title',
            'creator:id,name'
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type'         => 'required|in:agenda,protocol',
            'task_id'      => 'nullable|exists:tasks,id',
            'subtask_id'   => 'nullable|exists:subtasks,id',
            'title'        => 'nullable|string|max:255',
            'body'         => 'nullable|string',
            'document_date'=> 'nullable|date',
        ]);

        // Нельзя привязывать одновременно к задаче И подзадаче
        if (!empty($data['task_id']) && !empty($data['subtask_id'])) {
            return response()->json([
                'message' => 'Документ нельзя привязать одновременно к задаче и подзадаче.'
            ], 422);
        }

        // Кто создал
        $data['created_by'] = auth()->id();

        // Дата документа
        if (empty($data['document_date'])) {
            $data['document_date'] = now()->toDateString();
        }

        // Автоматическая нумерация ДЛЯ КАЖДОГО ПОЛЬЗОВАТЕЛЯ ОТДЕЛЬНО
        $data['number'] = MeetingDocument::where('created_by', auth()->id())
                ->where('type', $data['type'])
                ->max('number') + 1;

        $doc = MeetingDocument::create($data);

        $this->notifyUsersAboutDocument($doc);

        return response()->json(
            $doc->load(['task:id,title', 'subtask:id,title', 'creator:id,name']),
            201
        );
    }

public function update(Request $request, MeetingDocument $meetingDocument)
{
    if ($meetingDocument->created_by !== auth()->id()) {
        return response()->json(['message' => 'У вас нет прав для изменения этого документа'], 403);
    }

    $data = $request->validate([
        'title'        => 'nullable|string|max:255',
        'body'         => 'nullable|string',
        'document_date'=> 'nullable|date',
        'task_id'      => 'nullable|exists:tasks,id',
        'subtask_id'   => 'nullable|exists:subtasks,id',
    ]);

    if (!empty($data['task_id']) && !empty($data['subtask_id'])) {
        return response()->json([
            'message' => 'Документ нельзя привязать одновременно к задаче и подзадаче.'
        ], 422);
    }

    $meetingDocument->update($data);

    return $meetingDocument->fresh()->load([
        'task:id,title',
        'subtask:id,title',
        'creator:id,name'
    ]);
}


public function destroy(MeetingDocument $meetingDocument)
{
    if ($meetingDocument->created_by !== auth()->id()) {
        return response()->json(['message' => 'У вас нет прав для удаления этого документа'], 403);
    }

    $meetingDocument->delete();

    return response()->json(['message' => 'Документ удалён']);
}


public function pdf($id)
{
    $doc = MeetingDocument::with(['task', 'subtask', 'creator'])->findOrFail($id);

    // Проверяем доступ
    if (
        $doc->created_by !== auth()->id() &&
        !$this->userCanSeeDocument($doc)
    ) {
        return response()->json(['message' => 'Нет доступа'], 403);
    }

    $html = view('pdf.meeting_document', [
        'doc' => $doc
    ])->render();

    $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');

    return $pdf->download("document-{$doc->id}.pdf");
}


private function userCanSeeDocument(MeetingDocument $doc)
{
    $userId = auth()->id();

    // Автор — всегда доступ
    if ($doc->created_by == $userId) return true;

    // Привязан к задаче
    if ($doc->task_id) {
        if (DB::table('task_responsibles')->where('task_id', $doc->task_id)->where('user_id', $userId)->exists()) return true;
        if (DB::table('task_executors')->where('task_id', $doc->task_id)->where('user_id', $userId)->exists()) return true;
        if (DB::table('task_user_watchers')->where('task_id', $doc->task_id)->where('user_id', $userId)->exists()) return true;
    }

    // Привязан к подзадаче
    if ($doc->subtask_id) {
        if (DB::table('subtask_responsibles')->where('subtask_id', $doc->subtask_id)->where('user_id', $userId)->exists()) return true;
        if (DB::table('subtask_executors')->where('subtask_id', $doc->subtask_id)->where('user_id', $userId)->exists()) return true;
    }

    return false;
}



private function notifyUsersAboutDocument(MeetingDocument $doc)
{
    // Документ создан не к задаче и не к подзадаче → не уведомляем
    if (empty($doc->task_id) && empty($doc->subtask_id)) {
        return;
    }

    $creator = auth()->user();

    // ===== Участники задачи =====
    $taskUserIds = [];

    if ($doc->task_id) {
        $taskUserIds = array_merge(
            DB::table('task_responsibles')->where('task_id', $doc->task_id)->pluck('user_id')->toArray(),
            DB::table('task_executors')->where('task_id', $doc->task_id)->pluck('user_id')->toArray(),
            DB::table('task_user_watchers')->where('task_id', $doc->task_id)->pluck('user_id')->toArray()
        );
    }

    // ===== Участники подзадачи =====
    $subtaskUserIds = [];

    if ($doc->subtask_id) {
        $subtaskUserIds = array_merge(
            DB::table('subtask_responsibles')->where('subtask_id', $doc->subtask_id)->pluck('user_id')->toArray(),
            DB::table('subtask_executors')->where('subtask_id', $doc->subtask_id)->pluck('user_id')->toArray()
        );
    }

    // Объединяем уникальные ID
    $allUsers = array_unique(array_merge($taskUserIds, $subtaskUserIds));

    // Не уведомлять автора
    $allUsers = array_filter($allUsers, fn($id) => $id !== $creator->id);

    if (empty($allUsers)) {
        return;
    }

    // Загружаем пользователей с telegram_chat_id
    $users = User::whereIn('id', $allUsers)
        ->whereNotNull('telegram_chat_id')
        ->get();

    if ($users->isEmpty()) {
        return;
    }

    // Формируем текст уведомления
    $title = $doc->title ?: 'Без названия';
    $type = $doc->type === 'agenda' ? 'Повестка дня' : 'Протокол';
    $author = $creator->name;

    if ($doc->task_id) {
        $taskName = optional($doc->task)->title;
        $context = "к задаче: <b>{$taskName}</b>";
    } else {
        $subtaskName = optional($doc->subtask)->title;
        $context = "к подзадаче: <b>{$subtaskName}</b>";
    }

    $text = "📄 <b>Новый документ!</b>\n"
          . "{$type}: <b>{$title}</b>\n"
          . "{$context}\n"
          . "Автор: {$author}";

    // Отправляем уведомления всем
    foreach ($users as $user) {
        TelegramService::sendMessage($user->telegram_chat_id, $text);
    }
}


}
