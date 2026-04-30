<?php

namespace App\Notifications;

use App\Models\CalendarEvent;
use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Carbon\Carbon;

class EventCreatedNotification extends Notification
{
    use Queueable;

    protected $event;
    protected $company;

    public function __construct(CalendarEvent $event, Company $company)
    {
        $this->event = $event;
        $this->company = $company;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $formattedStart = Carbon::parse($this->event->start_date)->format('d.m.Y H:i');
        $formattedEnd = Carbon::parse($this->event->end_date)->format('d.m.Y H:i');

        $mailMessage = (new MailMessage)
            ->subject("📅 Новое событие в компании: {$this->event->title}")
            ->greeting("Здравствуйте, {$notifiable->name}!")
            ->line("В компании **{$this->company->name}** создано новое событие.")
            ->line("**Событие:** {$this->event->title}")
            ->line("**Дата и время:** {$formattedStart} - {$formattedEnd}");

        if ($this->event->description) {
            $mailMessage->line("**Описание:**")
                ->line($this->event->description);
        }

        if ($this->event->location) {
            $mailMessage->line("**Место проведения:** {$this->event->location}");
        }

        $mailMessage->action("Перейти к событию", url("/calendar/events/{$this->event->id}"))
            ->salutation("С уважением, команда системы управления проектами");

        return $mailMessage;
    }
}
