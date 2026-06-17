<?php

namespace App\Notifications;

use App\Models\Poll;
use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PollParticipantAddedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $poll;
    protected $company;
    protected $addedBy;

    public function __construct(Poll $poll, Company $company, $addedBy)
    {
        $this->poll = $poll;
        $this->company = $company;
        $this->addedBy = $addedBy;
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
            ->subject("👥 Вас добавили в опрос в компании {$companyName}")
            ->greeting("Здравствуйте, {$notifiable->name}!")
            ->line("Вас добавили в опрос **{$this->poll->title}** в компании **{$companyName}**.")
            ->line("**Добавил:** {$this->addedBy->name}")
            ->line("**Опрос создан:** {$this->poll->creator->name}")
            ->line("**Описание:** " . ($this->poll->description ?: 'Без описания'))
            ->line("**Всего участников:** {$this->poll->participants()->count()}")
            ->action('Перейти к опросу', $pollUrl)
            ->line('Пожалуйста, примите участие в опросе, ваше мнение важно!')
            ->salutation('С уважением, команда CRM.');
    }
}
