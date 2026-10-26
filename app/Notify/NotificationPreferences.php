<?php

namespace App\Notify;

use App\Models\NotificationPreference;

/**
 * Какие каналы включены у пользователя для типа уведомления.
 *
 * Порядок: явный выбор пользователя → 'defaults' типа в config/notify.php →
 * значение канала по умолчанию.
 */
class NotificationPreferences
{
    public static function channels(): array
    {
        return array_keys(config('notify.channels', []));
    }

    public static function defaultFor(string $type, string $channel): bool
    {
        $typeDefaults = config("notify.types.{$type}.defaults", []);

        if (array_key_exists($channel, $typeDefaults)) {
            return (bool) $typeDefaults[$channel];
        }

        return (bool) config("notify.channels.{$channel}.default", false);
    }

    /**
     * @return array<string, int[]> канал => id получателей, у которых он включён
     */
    public static function resolve(array $userIds, string $type): array
    {
        $overrides = NotificationPreference::whereIn('user_id', $userIds)
            ->where('type', $type)
            ->get()
            ->groupBy('user_id');

        $result = [];

        foreach (self::channels() as $channel) {
            $default = self::defaultFor($type, $channel);

            foreach ($userIds as $userId) {
                $override = $overrides->get($userId)?->firstWhere('channel', $channel);
                $enabled = $override ? $override->enabled : $default;

                if ($enabled) {
                    $result[$channel][] = $userId;
                }
            }
        }

        return $result;
    }

    /**
     * Полная матрица для страницы настроек: [type][channel] => bool.
     */
    public static function matrixFor(int $userId): array
    {
        $overrides = NotificationPreference::where('user_id', $userId)
            ->get()
            ->groupBy('type');

        $matrix = [];

        foreach (array_keys(config('notify.types', [])) as $type) {
            foreach (self::channels() as $channel) {
                $override = $overrides->get($type)?->firstWhere('channel', $channel);
                $matrix[$type][$channel] = $override ? $override->enabled : self::defaultFor($type, $channel);
            }
        }

        return $matrix;
    }
}
