<?php

namespace App\Notifications;

use App\Models\SupportThread;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class NewSupportTicketNotification extends Notification
{
    use Queueable;

    protected $thread;
    protected $user;

    public function __construct(SupportThread $thread, User $user)
    {
        $this->thread = $thread;
        $this->user = $user;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isAssigned = $this->thread->support_user_id === $notifiable->id;

        $mailMessage = (new MailMessage)
            ->subject("🆕 Новый тикет поддержки #{$this->thread->id}: {$this->thread->subject}")
            ->greeting("Здравствуйте, {$notifiable->name}!")
            ->line("Поступил новый запрос в службу поддержки.")
            ->line("**Номер тикета:** #{$this->thread->id}")
            ->line("**Клиент:** {$this->user->name} ({$this->user->email})")
            ->line("**Тема:** {$this->thread->subject}");

        // Получаем первое сообщение
        $firstMessage = $this->thread->messages()->orderBy('id')->first();
        if ($firstMessage && $firstMessage->body) {
            $mailMessage->line("**Сообщение:**")
                ->line($firstMessage->body);
        }

        if ($isAssigned) {
            $mailMessage->line("")
                ->line("⚠️ **Этот тикет назначен на вас!**");
        }

        $mailMessage->action("Перейти к тикету", url("/support/tickets/{$this->thread->id}"))
            ->line("Пожалуйста, ответьте на запрос как можно скорее.")
            ->salutation("С уважением, служба поддержки");

        return $mailMessage;
    }
}
