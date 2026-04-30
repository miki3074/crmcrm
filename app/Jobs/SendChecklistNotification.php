<?php

namespace App\Jobs;

use App\Models\Task;
use App\Models\TaskChecklist;
use App\Models\User;
use App\Notifications\ChecklistAssignedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Exception\UnexpectedResponseException;

class SendChecklistNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    protected $checklist;
    protected $task;
    protected $assignedUserId;

    public function __construct(TaskChecklist $checklist, $assignedUserId = null)
    {
        $this->checklist = $checklist;
        $this->task = $checklist->task;
        $this->assignedUserId = $assignedUserId;
    }

    public function handle()
    {
        // Если есть конкретный назначенный пользователь
        if ($this->assignedUserId) {
            $user = User::find($this->assignedUserId);
            if ($user && $user->email) {
                try {
                    $user->notify(new ChecklistAssignedNotification($this->checklist));
                } catch (UnexpectedResponseException $e) {
                    Log::warning("Email not sent to {$user->email}: " . $e->getMessage());
                } catch (TransportException $e) {
                    Log::warning("Transport error for {$user->email}: " . $e->getMessage());
                } catch (\Exception $e) {
                    Log::warning("Failed to send email to {$user->email}: " . $e->getMessage());
                }
            }
        } else {
            // Отправляем всем участникам задачи
            $recipients = collect();
            $recipients = $recipients->merge($this->task->executors);
            $recipients = $recipients->merge($this->task->responsibles);
            $recipients = $recipients->merge($this->task->watcherstask);
            $recipients = $recipients->unique('id');

            foreach ($recipients as $user) {
                if ($user && $user->email) {
                    try {
                        $user->notify(new ChecklistAssignedNotification($this->checklist));
                    } catch (UnexpectedResponseException $e) {
                        Log::warning("Email not sent to {$user->email}: " . $e->getMessage());
                    } catch (TransportException $e) {
                        Log::warning("Transport error for {$user->email}: " . $e->getMessage());
                    } catch (\Exception $e) {
                        Log::warning("Failed to send email to {$user->email}: " . $e->getMessage());
                    }
                }
            }
        }
    }
}
