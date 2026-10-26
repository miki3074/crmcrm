<?php

namespace App\Http\Controllers;

use App\Models\NotificationPreference;
use App\Models\PushSubscription;
use App\Notify\Channels\WebPushChannel;
use App\Notify\NotificationPreferences;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class NotificationSettingsController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        // Типы, сгруппированные по категориям, в порядке config/notify.php
        $groups = [];
        foreach (config('notify.types', []) as $type => $meta) {
            if (!empty($meta['hidden'])) {
                continue;
            }

            $category = explode('.', $type, 2)[0];
            $groups[$category]['label'] = config("notify.categories.{$category}", $category);
            $groups[$category]['types'][] = ['type' => $type, 'label' => $meta['label']];
        }

        return Inertia::render('Profile/NotificationSettings', [
            'groups' => array_values(array_map(
                fn ($group, $category) => ['category' => $category] + $group,
                $groups,
                array_keys($groups)
            )),
            'channels' => collect(config('notify.channels'))
                ->map(fn ($meta, $key) => ['key' => $key, 'label' => $meta['label']])
                ->values(),
            'matrix' => NotificationPreferences::matrixFor($user->id),
            'telegramLinked' => !empty($user->telegram_chat_id),
            'push' => [
                'configured' => WebPushChannel::isConfigured(),
                'serverReady' => WebPushChannel::canEncrypt(),
                'devices' => PushSubscription::where('user_id', $user->id)->count(),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'preferences' => 'required|array',
            'preferences.*.type' => ['required', 'string', Rule::in(array_keys(config('notify.types', [])))],
            'preferences.*.channel' => ['required', 'string', Rule::in(NotificationPreferences::channels())],
            'preferences.*.enabled' => 'required|boolean',
        ]);

        $userId = $request->user()->id;
        $now = now();

        NotificationPreference::upsert(
            array_map(fn ($pref) => [
                'user_id' => $userId,
                'type' => $pref['type'],
                'channel' => $pref['channel'],
                'enabled' => (bool) $pref['enabled'],
                'created_at' => $now,
                'updated_at' => $now,
            ], $validated['preferences']),
            ['user_id', 'type', 'channel'],
            ['enabled', 'updated_at']
        );

        return response()->json([
            'matrix' => NotificationPreferences::matrixFor($userId),
        ]);
    }
}
