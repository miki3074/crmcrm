<?php

namespace App\Notifications;

use App\Models\MediaPlanItem;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MediaPlanAssignedNotification extends Notification
{
    use Queueable;

    protected MediaPlanItem $item;

    public function __construct(
        MediaPlanItem $item
    ) {
        $this->item = $item;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(
        object $notifiable
    ): MailMessage {
        $mediaPlan = $this->item->mediaPlan;

        $itemName = $this->getItemName();

        $mail = (new MailMessage)
            ->subject(
                "Вы назначены ответственным в медиаплане: {$mediaPlan->name}"
            )
            ->greeting(
                "Здравствуйте, {$notifiable->name}!"
            )
            ->line(
                'Вы назначены ответственным за активность в медиаплане.'
            )
            ->line(
                "**Клиент:** {$mediaPlan->klient->name}"
            )
            ->line(
                "**Медиаплан:** {$mediaPlan->name}"
            )
            ->line(
                "**Активность:** {$itemName}"
            );

        if ($this->item->format) {
            $mail->line(
                "**Формат:** {$this->item->format}"
            );
        }

        if ($this->item->start_date) {
            $mail->line(
                '**Начало:** '
                . $this->formatDate(
                    $this->item->start_date
                )
            );
        }

        if ($this->item->end_date) {
            $mail->line(
                '**Завершение:** '
                . $this->formatDate(
                    $this->item->end_date
                )
            );
        }

        if ($this->item->kpi) {
            $mail->line(
                "**KPI:** {$this->item->kpi}"
            );
        }

        $mail
            ->action(
                'Открыть медиаплан',
                route(
                    'media-plans.show',
                    $mediaPlan->id
                )
            )
            ->salutation(
                'С уважением, система управления проектами'
            );

        return $mail;
    }

    private function getItemName(): string
    {
        if (
            $this->item->type === 'radio'
            && $this->item->radioStation
        ) {
            $station =
                $this->item->radioStation;

            $name = $station->name;

            if ($station->frequency) {
                $name .=
                    " {$station->frequency} FM";
            }

            if ($this->item->city) {
                $name .=
                    " — {$this->item->city->name}";
            }

            return $name;
        }

        return $this->item
            ->platform_name
            ?: 'Активность медиаплана';
    }

    private function formatDate($date): string
    {
        return \Carbon\Carbon::parse(
            $date
        )->format('d.m.Y');
    }
}