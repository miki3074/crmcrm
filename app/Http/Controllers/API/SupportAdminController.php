<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SupportThread;
use App\Models\SupportMessagetwo;
use Illuminate\Http\Request;

class SupportAdminController extends Controller
{
    // список всех тикетов
    public function threads(Request $request)
    {
        $query = SupportThread::with([
            'user:id,name',
            'messages' => fn($q) => $q->latest()->limit(1)
        ])->orderByDesc('updated_at');

        // фильтры
        if ($request->status && $request->status != 'all') {
            $query->where('status', $request->status);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('subject', 'like', "%{$request->search}%")
                  ->orWhereHas('user', function ($qq) use ($request) {
                      $qq->where('name', 'like', "%{$request->search}%");
                  });
            });
        }

        return $query->get();
    }

    // просмотр тикета
    public function show(SupportThread $thread)
    {
        return $thread->load([
            'user:id,name',
            'messages.user:id,name',
            'messages.attachments',
        ]);
    }

    // ответ саппорта
    public function sendMessage(SupportThread $thread, Request $request)
    {
        $data = $request->validate([
            'message' => 'nullable|string',
            'files.*' => 'nullable|file|max:20480'
        ]);

        $msg = $thread->messages()->create([
            'user_id'    => auth()->id(),
            'body'       => $data['message'] ?? null,
            'is_support' => true,
        ]);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store('support', 'public');

                $msg->attachments()->create([
                    'path'          => $path,
                    'mime_type'     => $file->getMimeType(),
                    'original_name' => $file->getClientOriginalName(),
                    'size'          => $file->getSize(),
                ]);
            }
        }

        $thread->touch();

        return $msg->load('user:id,name', 'attachments');
    }

    // закрыть тикет
    public function close(SupportThread $thread)
    {
        $thread->update(['status' => 'closed']);
        return response()->json(['message' => 'Тикет закрыт']);
    }

    // открыть тикет
    public function reopen(SupportThread $thread)
    {
        $thread->update(['status' => 'open']);
        return response()->json(['message' => 'Тикет открыт']);
    }
}
