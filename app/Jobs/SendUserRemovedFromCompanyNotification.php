<?php

namespace App\Jobs;

use App\Models\Company;
use App\Models\User;
use App\Notifications\UserRemovedFromCompanyNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Exception\UnexpectedResponseException;

class SendUserRemovedFromCompanyNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    protected $user;
    protected $company;

    public function __construct(User $user, Company $company)
    {
        $this->user = $user;
        $this->company = $company;
    }

    public function handle()
    {
        if (!$this->user || !$this->user->email) {
            return;
        }

        try {
            $this->user->notify(new UserRemovedFromCompanyNotification($this->company));
        } catch (UnexpectedResponseException $e) {
            Log::warning("Email not sent to {$this->user->email}: " . $e->getMessage());
        } catch (TransportException $e) {
            Log::warning("Transport error for {$this->user->email}: " . $e->getMessage());
        } catch (\Exception $e) {
            Log::warning("Failed to send email to {$this->user->email}: " . $e->getMessage());
        }
    }
}
