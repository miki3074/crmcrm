<?php

namespace App\Jobs;

use App\Models\Poll;
use App\Models\Company;
use App\Models\User;
use App\Notifications\PollParticipantAddedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Exception\UnexpectedResponseException;

class SendPollParticipantAddedNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    protected $poll;
    protected $company;
    protected $addedBy;
    protected $participantIds;

    public function __construct(Poll $poll, Company $company, User $addedBy, array $participantIds)
    {
        $this->poll = $poll;
        $this->company = $company;
        $this->addedBy = $addedBy;
        $this->participantIds = $participantIds;
    }

    public function handle()
    {
        foreach ($this->participantIds as $userId) {
            $user = User::find($userId);
            if (!$user || !$user->email) {
                continue;
            }

            try {
                $user->notify(new PollParticipantAddedNotification($this->poll, $this->company, $this->addedBy));
                Log::info("Poll participant notification sent to {$user->email} for poll {$this->poll->id}");
            } catch (UnexpectedResponseException $e) {
                Log::warning("Email not sent to {$user->email} for poll {$this->poll->id}: " . $e->getMessage());
            } catch (TransportException $e) {
                Log::warning("Transport error for {$user->email}: " . $e->getMessage());
            } catch (\Exception $e) {
                Log::warning("Failed to send email to {$user->email}: " . $e->getMessage());
            }
        }
    }
}
