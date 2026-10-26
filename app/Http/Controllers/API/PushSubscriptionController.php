<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use App\Notify\Channels\WebPushChannel;
use App\Notify\Notify;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PushSubscriptionController extends Controller
{
    // Сохранить подписку браузера/устройства текущего пользователя
    public function store(Request $request)
    {
        $validated = $request->validate([
            'endpoint' => 'required|url|max:2000',
            'keys.p256dh' => 'required|string|max:255',
            'keys.auth' => 'required|string|max:255',
            'contentEncoding' => 'nullable|in:aesgcm,aes128gcm',
        ]);

        // Один endpoint = одно устройство; если им раньше пользовался
        // другой аккаунт (общий ПК) — переподписываем на текущего
        PushSubscription::updateOrCreate(
            ['endpoint_hash' => PushSubscription::hashEndpoint($validated['endpoint'])],
            [
                'user_id' => $request->user()->id,
                'endpoint' => $validated['endpoint'],
                'public_key' => $validated['keys']['p256dh'],
                'auth_token' => $validated['keys']['auth'],
                'content_encoding' => $validated['contentEncoding'] ?? 'aes128gcm',
                'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
            ]
        );

        return response()->json(['subscribed' => true]);
    }

    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'endpoint' => 'required|string',
        ]);

        PushSubscription::where('user_id', $request->user()->id)
            ->where('endpoint_hash', PushSubscription::hashEndpoint($validated['endpoint']))
            ->delete();

        return response()->json(['subscribed' => false]);
    }

    // Тестовое уведомление себе — проверить, что push доходит
    public function test(Request $request)
    {
        if (!WebPushChannel::isConfigured() || !WebPushChannel::canEncrypt()) {
            return response()->json([
                'message' => 'Push на сервере не настроен — см. подсказку на странице.',
            ], 422);
        }

        Notify::type('system.test')
            ->to($request->user()->id)
            ->title('Проверка уведомлений')
            ->message('Push-уведомления работают на этом устройстве.')
            ->link('/notifications/settings')
            ->send();

        return response()->json(['sent' => true]);
    }
}
