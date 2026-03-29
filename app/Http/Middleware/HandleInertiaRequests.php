<?php

namespace App\Http\Middleware;

use App\Models\Message;
use App\Models\SupportMessagetwo;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        // return [
        //     ...parent::share($request),
        //     'auth' => [
        //         'user' => $request->user(),
        //     ],
        // ];

        return array_merge(parent::share($request), [
        'auth' => [
            'user' => $request->user()
                ? $request->user()->only(['id','name','email','telegram_chat_id'])
                : null,
            'roles' => $request->user()
                ? $request->user()->getRoleNames() // Spatie Permission
                : [],
        ],

            'unreadSupportCount' => function () use ($request) {
                $user = $request->user();
                if (!$user) return 0;

                // Если пользователь — обычный клиент:
                // Ищем непрочитанные сообщения от поддержки (is_support = 1)
                // в тех тредах, которые ПРИНАДЛЕЖАТ этому пользователю
                return SupportMessagetwo::whereHas('thread', function($query) use ($user) {
                    $query->where('user_id', $user->id); // Предполагаем, что в таблице threads есть user_id владельца
                })
                    ->where('is_support', 1) // Сообщения именно от поддержки
                    ->whereNull('read_at')
                    ->count();
            },

            'unreadChatCount' => function () use ($request) {
                $user = $request->user();
                if (!$user) return 0;

                // Считаем личные сообщения
                $private = \App\Models\Message::where('receiver_id', $user->id)
                    ->whereNull('group_id')
                    ->where('is_read', false)
                    ->count();

                // Считаем групповые сообщения
                $groupCount = 0;
                $groups = $user->chatGroups()->withPivot('last_read_at')->get();
                foreach ($groups as $group) {
                    $lastRead = $group->pivot->last_read_at;
                    $groupCount += \App\Models\Message::where('group_id', $group->id)
                        ->where('sender_id', '!=', $user->id) // не мои сообщения
                        ->when($lastRead, function($q) use ($lastRead) {
                            $q->where('created_at', '>', $lastRead);
                        })
                        ->count();
                }

                return $private + $groupCount;
            },


    ]);




    }
}
