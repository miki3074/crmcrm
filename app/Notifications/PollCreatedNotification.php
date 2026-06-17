<?php

namespace App\Notifications;

use App\Models\Poll;
use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PollCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $poll;
    protected $company;

    public function __construct(Poll $poll, Company $company)
    {
        $this->poll = $poll;
        $this->company = $company;
    }

    /**
     * Get the notification's delivery channels.
     * Только email, без database
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        $pollUrl = url("/polls/{$this->poll->id}");
        $companyName = $this->company->name;

        return (new MailMessage)
            ->subject("📋 Новый опрос в компании {$companyName}")
            ->greeting("Здравствуйте, {$notifiable->name}!")
            ->line("В компании **{$companyName}** создан новый опрос.")
            ->line("**Название опроса:** {$this->poll->title}")
            ->line("**Создал:** {$this->poll->creator->name}")
            ->line("**Описание:** " . ($this->poll->description ?: 'Без описания'))
            ->line("**Участников:** {$this->poll->participants()->count()}")
            ->action('Перейти к опросу', $pollUrl)
            ->line('Пожалуйста, ответьте на опрос, поделитесь своими мыслями и предложениями!')
            ->salutation('С уважением, команда CRM.');
    }
}
