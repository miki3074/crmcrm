<?php

namespace App\Jobs;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use App\Notifications\NewCommentNotification;
use App\Notifications\CommentReplyNotification;
use App\Notifications\UserMentionedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Exception\UnexpectedResponseException;

class SendCommentNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    protected $task;
    protected $comment;
    protected $userId;
    protected $type;

    public function __construct(Task $task, TaskComment $comment, int $userId, string $type)
    {
        $this->task = $task;
        $this->comment = $comment;
        $this->userId = $userId;
        $this->type = $type;
    }

    public function handle()
    {
        $user = User::find($this->userId);
        if (!$user || !$user->email) {
            return;
        }

        try {
            switch ($this->type) {
                case 'reply':
                    $user->notify(new CommentReplyNotification($this->task, $this->comment));
                    break;
                case 'mention':
                    $user->notify(new UserMentionedNotification($this->task, $this->comment));
                    break;
                case 'new':
                    $user->notify(new NewCommentNotification($this->task, $this->comment));
                    break;
            }
        } catch (UnexpectedResponseException $e) {
            Log::warning("Email not sent to {$user->email}: " . $e->getMessage());
        } catch (TransportException $e) {
            Log::warning("Transport error for {$user->email}: " . $e->getMessage());
        } catch (\Exception $e) {
            Log::warning("Failed to send email to {$user->email}: " . $e->getMessage());
        }
    }
}
