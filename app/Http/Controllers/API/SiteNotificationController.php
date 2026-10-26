<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SiteNotification;
use Illuminate\Http\Request;

class SiteNotificationController extends Controller
{
    private const PAGE_SIZE = 30;

    // Список уведомлений текущего пользователя (постранично, от новых к старым)
    public function index(Request $request)
    {
        $validated = $request->validate([
            'category' => 'nullable|in:task,subtask,chat,meeting,klient',
            'unread' => 'nullable|boolean',
            'before_id' => 'nullable|integer',
        ]);

        $userId = $request->user()->id;

        $items = SiteNotification::where('user_id', $userId)
            ->when($validated['category'] ?? null, fn ($q, $category) => $q->where('category', $category))
            ->when($request->boolean('unread'), fn ($q) => $q->whereNull('read_at'))
            ->when($validated['before_id'] ?? null, fn ($q, $beforeId) => $q->where('id', '<', $beforeId))
            ->with('actor:id,name')
            ->orderByDesc('id')
            ->limit(self::PAGE_SIZE + 1)
            ->get();

        $hasMore = $items->count() > self::PAGE_SIZE;

        return response()->json([
            'data' => $items->take(self::PAGE_SIZE)->values(),
            'has_more' => $hasMore,
            'unread_count' => $this->unreadCount($userId),
        ]);
    }

    /**
     * Опрос для всплывающих уведомлений: счётчик + новые с id > after_id.
     * Первый вызов (без after_id) только сообщает latest_id, чтобы не
     * показывать всплывающими старые уведомления.
     */
    public function poll(Request $request)
    {
        $userId = $request->user()->id;
        $afterId = (int) $request->query('after_id', 0);

        $items = $afterId > 0
            ? SiteNotification::where('user_id', $userId)
                ->where('id', '>', $afterId)
                ->whereNull('read_at')
                ->with('actor:id,name')
                ->orderByDesc('id')
                ->limit(5)
                ->get()
            : collect();

        return response()->json([
            'unread_count' => $this->unreadCount($userId),
            'latest_id' => (int) SiteNotification::where('user_id', $userId)->max('id'),
            'items' => $items->values(),
        ]);
    }

    public function unread(Request $request)
    {
        return response()->json([
            'unread_count' => $this->unreadCount($request->user()->id),
        ]);
    }

    public function markRead(Request $request, $id)
    {
        SiteNotification::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'unread_count' => $this->unreadCount($request->user()->id),
        ]);
    }

    public function markAllRead(Request $request)
    {
        SiteNotification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['unread_count' => 0]);
    }

    public function destroy(Request $request, $id)
    {
        SiteNotification::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json([
            'unread_count' => $this->unreadCount($request->user()->id),
        ]);
    }

    public function clear(Request $request)
    {
        SiteNotification::where('user_id', $request->user()->id)->delete();

        return response()->json(['unread_count' => 0]);
    }

    private function unreadCount(int $userId): int
    {
        return SiteNotification::where('user_id', $userId)
            ->whereNull('read_at')
            ->count();
    }
}
