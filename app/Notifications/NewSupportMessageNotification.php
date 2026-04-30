<?php

namespace App\Notifications;

use App\Models\SupportThread;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class NewSupportMessageNotification extends Notification
{
    use Queueable;

    protected $thread;
    protected $message;
    protected $sender;
    protected $isSupport;

    public function __construct(SupportThread $thread, $message, User $sender, bool $isSupport)
    {
        $this->thread = $thread;
        $this->message = $message;
        $this->sender = $sender;
        $this->isSupport = $isSupport;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $senderType = $this->isSupport ? 'сотрудник поддержки' : 'клиент';
        $subject = $this->isSupport
            ? "💬 Новый ответ поддержки в тикете #{$this->thread->id}"
            : "📩 Новое сообщение от клиента в тикете #{$this->thread->id}";

        $mailMessage = (new MailMessage)
            ->subject($subject)
            ->greeting("Здравствуйте, {$notifiable->name}!")
            ->line("Новое сообщение в тикете поддержки.")
            ->line("**Тикет:** #{$this->thread->id} - {$this->thread->subject}")
            ->line("**Отправитель:** {$this->sender->name} ({$senderType})");

        if ($this->message->body) {
            $mailMessage->line("**Сообщение:**")
                ->line($this->message->body);
        }

        // Проверяем наличие вложений
        $attachmentsCount = $this->message->attachments()->count();
        if ($attachmentsCount > 0) {
            $mailMessage->line("**Вложения:** {$attachmentsCount} файл(ов)");
        }

        $mailMessage->action("Перейти к тикету", url("/support/tickets/{$this->thread->id}"))
            ->salarium("С уважением, служба поддержки");

        return $mailMessage;
    }
}
