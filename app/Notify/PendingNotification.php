<?php

namespace App\Notify;

use App\Jobs\DeliverNotification;
use App\Models\SiteNotification;

class PendingNotification
{
    private array $recipients = [];
    private array $except = [];
    private ?string $title = null;
    private string $message = '';
    private ?string $link = null;
    private ?int $actorId = null;
    private bool $collapse = false;

    public function __construct(private string $type)
    {
    }

    public function to(iterable|int|string|null $userIds): self
    {
        $ids = is_iterable($userIds) ? $userIds : [$userIds];

        foreach ($ids as $id) {
            $this->recipients[] = $id;
        }

        return $this;
    }

    public function title(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function message(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function link(?string $link): self
    {
        $this->link = $link;

        return $this;
    }

    public function from(?int $actorId): self
    {
        $this->actorId = $actorId;

        return $this;
    }

    public function except(iterable $userIds): self
    {
        foreach ($userIds as $id) {
            $this->except[] = (int) $id;
        }

        return $this;
    }

    /**
     * Пока у получателя есть непрочитанное уведомление этого типа с той же
     * ссылкой — заменить его новым, а не копить (сообщения чата и т.п.).
     */
    public function collapse(bool $collapse = true): self
    {
        $this->collapse = $collapse;

        return $this;
    }

    public function send(): void
    {
        $recipientIds = collect($this->recipients)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->reject(fn ($id) => $id === (int) $this->actorId || in_array($id, $this->except, true))
            ->values()
            ->all();

        if (empty($recipientIds)) {
            return;
        }

        [$category, $event] = self::splitType($this->type);

        $byChannel = NotificationPreferences::resolve($recipientIds, $this->type);

        $this->storeOnSite($byChannel['site'] ?? [], $category, $event);

        $external = array_filter([
            'push' => $byChannel['push'] ?? [],
            'telegram' => $byChannel['telegram'] ?? [],
            'mail' => $byChannel['mail'] ?? [],
        ]);

        if (empty($external)) {
            return;
        }

        $payload = [
            'type' => $this->type,
            'category' => $category,
            'title' => $this->title,
            'message' => $this->message,
            'link' => $this->link,
            'actor_id' => $this->actorId,
        ];

        // Без воркера очереди (QUEUE_CONNECTION=sync) отправляем после ответа,
        // чтобы запрос пользователя не ждал push/Telegram/почту.
        if (config('queue.default') === 'sync') {
            DeliverNotification::dispatchAfterResponse($payload, $external);
        } else {
            DeliverNotification::dispatch($payload, $external);
        }
    }

    /**
     * 'task.file_added' → ['task', 'file_added']; без точки — категория system.
     */
    public static function splitType(string $type): array
    {
        return str_contains($type, '.')
            ? explode('.', $type, 2)
            : ['system', $type];
    }

    private function storeOnSite(array $userIds, string $category, string $event): void
    {
        if (empty($userIds)) {
            return;
        }

        if ($this->collapse && $this->link !== null) {
            // Удаляем и создаём заново, чтобы у уведомления был новый id
            // и всплывающее окно показало его снова
            SiteNotification::whereIn('user_id', $userIds)
                ->where('category', $category)
                ->where('type', $event)
                ->where('link', $this->link)
                ->whereNull('read_at')
                ->delete();
        }

        $now = now();

        SiteNotification::insert(array_map(fn ($userId) => [
            'user_id' => $userId,
            'actor_id' => $this->actorId,
            'category' => $category,
            'type' => $event,
            'title' => $this->title,
            'message' => $this->message,
            'link' => $this->link,
            'created_at' => $now,
            'updated_at' => $now,
        ], $userIds));
    }
}
