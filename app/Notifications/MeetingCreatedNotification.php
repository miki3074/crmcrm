<?php

namespace App\Notifications;

use App\Models\Meeting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Carbon\Carbon;

class MeetingCreatedNotification extends Notification
{
    use Queueable;

    protected $meeting;
    protected $role; // 'responsible', 'participant'

    public function __construct(Meeting $meeting, string $role = 'participant')
    {
        $this->meeting = $meeting;
        $this->role = $role;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $roleText = $this->role === 'responsible' ? 'вы ответственный' : 'вы участник';
        $formattedDate = Carbon::parse($this->meeting->start_time)->format('d.m.Y H:i');

        $mailMessage = (new MailMessage)
            ->subject("📅 Новое совещание: {$this->meeting->title}")
            ->greeting("Здравствуйте, {$notifiable->name}!")
            ->line("Вас пригласили на совещание, где {$roleText}.")
            ->line("**Тема:** {$this->meeting->title}")
            ->line("**Дата и время:** {$formattedDate}")
            ->line("**Ответственный:** {$this->meeting->responsible->name}")
            ->action("Перейти к совещанию", url("/meetings/{$this->meeting->id}"));

        if ($this->meeting->agenda) {
            $mailMessage->line("**Повестка:**")
                ->line($this->meeting->agenda);
        }

        if ($this->meeting->task) {
            $mailMessage->line("**Связанная задача:** {$this->meeting->task->title}");
        }

        if ($this->meeting->subtask) {
            $mailMessage->line("**Связанная подзадача:** {$this->meeting->subtask->title}");
        }

        return $mailMessage
            ->line("Пожалуйста, подтвердите свое участие.")
            ->salutation("С уважением, команда системы управления проектами");
    }
}
