<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Exception\UnexpectedResponseException;

trait HandlesEmailErrors
{
    /**
     * Безопасная отправка уведомления с обработкой ошибок
     */
    protected function safeNotify($user, $notification)
    {
        if (!$user || !$user->email) {
            return false;
        }

        try {
            $user->notify($notification);
            return true;
        } catch (UnexpectedResponseException $e) {
            // Ошибка SMTP (несуществующий ящик, заблокирован и т.д.)
            Log::warning("Email not sent to {$user->email}: " . $e->getMessage());
        } catch (TransportException $e) {
            // Ошибка транспорта
            Log::warning("Transport error for {$user->email}: " . $e->getMessage());
        } catch (\Exception $e) {
            // Любые другие ошибки
            Log::warning("Failed to send email to {$user->email}: " . $e->getMessage());
        }

        return false;
    }

    /**
     * Безопасная отправка уведомления множеству пользователей
     */
    protected function safeNotifyMany($users, $notification)
    {
        foreach ($users as $user) {
            $this->safeNotify($user, $notification);
        }
    }

    /**
     * Безопасная отправка уведомления с дополнительным контекстом
     */
    protected function safeNotifyWithContext($user, $notification, array $context = [])
    {
        if (!$user || !$user->email) {
            return false;
        }

        try {
            $user->notify($notification);
            return true;
        } catch (UnexpectedResponseException $e) {
            Log::warning("Email not sent to {$user->email}", array_merge($context, [
                'error' => $e->getMessage()
            ]));
        } catch (TransportException $e) {
            Log::warning("Transport error for {$user->email}", array_merge($context, [
                'error' => $e->getMessage()
            ]));
        } catch (\Exception $e) {
            Log::warning("Failed to send email to {$user->email}", array_merge($context, [
                'error' => $e->getMessage()
            ]));
        }

        return false;
    }
}
