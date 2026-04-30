<?php

namespace App\Jobs;

use App\Models\Company;
use App\Models\User;
use App\Notifications\UserAttachedToCompanyNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Exception\UnexpectedResponseException;

class SendUserAttachedToCompanyNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    protected $user;
    protected $company;
    protected $role;

    public function __construct(User $user, Company $company, string $role)
    {
        $this->user = $user;
        $this->company = $company;
        $this->role = $role;
    }

    public function handle()
    {
        if (!$this->user || !$this->user->email) {
            return;
        }

        try {
            $this->user->notify(new UserAttachedToCompanyNotification($this->company, $this->role));
        } catch (UnexpectedResponseException $e) {
            Log::warning("Email not sent to {$this->user->email}: " . $e->getMessage());
        } catch (TransportException $e) {
            Log::warning("Transport error for {$this->user->email}: " . $e->getMessage());
        } catch (\Exception $e) {
            Log::warning("Failed to send email to {$this->user->email}: " . $e->getMessage());
        }
    }
}
