<?php

namespace App\Jobs;

use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Exception\UnexpectedResponseException;

class SendTaskAssignedNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    protected $task;
    protected $userIds;

    public function __construct(Task $task, array $userIds)
    {
        $this->task = $task;
        $this->userIds = $userIds;
    }

    public function handle()
    {
        foreach ($this->userIds as $userId) {
            $user = User::find($userId);
            if (!$user || !$user->email) {
                continue;
            }

            try {
                $user->notify(new TaskAssignedNotification($this->task));
            } catch (UnexpectedResponseException $e) {
                Log::warning("Email not sent to {$user->email} for task {$this->task->id}: " . $e->getMessage());
            } catch (TransportException $e) {
                Log::warning("Transport error for {$user->email}: " . $e->getMessage());
            } catch (\Exception $e) {
                Log::warning("Failed to send email to {$user->email}: " . $e->getMessage());
            }
        }
    }
}
