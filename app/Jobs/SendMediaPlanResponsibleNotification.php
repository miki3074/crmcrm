<?php

namespace App\Jobs;

use App\Models\MediaPlanItem;
use App\Models\User;
use App\Notifications\MediaPlanAssignedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Exception\UnexpectedResponseException;

class SendMediaPlanResponsibleNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $mediaPlanItemId;
    protected int $assignedUserId;

    public function __construct(
        int $mediaPlanItemId,
        int $assignedUserId
    ) {
        $this->mediaPlanItemId = $mediaPlanItemId;
        $this->assignedUserId = $assignedUserId;
    }

    public function handle(): void
    {
        $item = MediaPlanItem::query()
            ->with([
                'mediaPlan.klient:id,name',
                'mediaPlan.creator:id,name',
                'city:id,name',
                'radioStation:id,name,frequency',
            ])
            ->find($this->mediaPlanItemId);

        if (!$item) {
            Log::warning(
                "MediaPlanItem {$this->mediaPlanItemId} not found for notification."
            );

            return;
        }

        $user = User::find(
            $this->assignedUserId
        );

        if (!$user || !$user->email) {
            return;
        }

        try {
            $user->notify(
                new MediaPlanAssignedNotification(
                    $item
                )
            );
        } catch (UnexpectedResponseException $e) {
            Log::warning(
                "Email not sent to {$user->email}: "
                . $e->getMessage()
            );
        } catch (TransportException $e) {
            Log::warning(
                "Transport error for {$user->email}: "
                . $e->getMessage()
            );
        } catch (\Throwable $e) {
            Log::warning(
                "Failed to send media plan email to {$user->email}: "
                . $e->getMessage()
            );
        }
    }
}