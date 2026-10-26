<?php

namespace App\Notify\Channels;

use App\Models\PushSubscription;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/**
 * Web Push: системные уведомления на ПК (Windows/macOS), Android
 * и iPhone (iOS 16.4+, сайт добавлен «На экран Домой»).
 */
class WebPushChannel
{
    public static function isConfigured(): bool
    {
        return (bool) config('notify.webpush.public_key') && (bool) config('notify.webpush.private_key');
    }

    /**
     * Может ли сервер шифровать push (нужна рабочая EC-криптография OpenSSL).
     * На Windows/XAMPP без переменной окружения OPENSSL_CONF — не может.
     */
    public static function canEncrypt(): bool
    {
        static $result = null;

        return $result ??= openssl_pkey_new([
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ]) !== false;
    }

    public static function send(array $userIds, array $payload): void
    {
        if (!self::isConfigured()) {
            return;
        }

        $subscriptions = PushSubscription::whereIn('user_id', $userIds)->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        $config = config('notify.webpush');

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => $config['subject'],
                'publicKey' => $config['public_key'],
                'privateKey' => $config['private_key'],
            ],
        ], ['TTL' => $config['ttl']]);

        $body = json_encode([
            'title' => $payload['title'] ?: config('app.name'),
            'body' => $payload['message'],
            'url' => $payload['link'] ?? '/dashboard',
            // Одинаковый tag заменяет предыдущее уведомление на устройстве
            'tag' => $payload['type'] . '|' . ($payload['link'] ?? ''),
            'icon' => $config['icon'],
        ], JSON_UNESCAPED_UNICODE);

        foreach ($subscriptions as $subscription) {
            $webPush->queueNotification(Subscription::create([
                'endpoint' => $subscription->endpoint,
                'publicKey' => $subscription->public_key,
                'authToken' => $subscription->auth_token,
                'contentEncoding' => $subscription->content_encoding,
            ]), $body);
        }

        foreach ($webPush->flush() as $report) {
            if ($report->isSubscriptionExpired()) {
                // Пользователь отписался или удалил браузер — подписка больше не нужна
                PushSubscription::where('endpoint_hash', PushSubscription::hashEndpoint($report->getEndpoint()))->delete();
            } elseif (!$report->isSuccess()) {
                Log::warning('Web Push не доставлен', ['reason' => $report->getReason()]);
            }
        }
    }
}
