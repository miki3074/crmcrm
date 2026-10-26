<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;
use Throwable;

/**
 * Ключи Web Push. Генерируются один раз на сервер; если их сменить,
 * все существующие push-подписки перестанут работать.
 */
class GenerateVapidKeys extends Command
{
    protected $signature = 'notify:vapid';
    protected $description = 'Сгенерировать VAPID-ключи для push-уведомлений';

    public function handle()
    {
        try {
            $keys = VAPID::createVapidKeys();
        } catch (Throwable $e) {
            $this->error('Не удалось создать ключи: ' . $e->getMessage());
            $this->line('На Windows задайте переменную окружения OPENSSL_CONF (путь к openssl.cnf) и повторите.');

            return self::FAILURE;
        }

        $this->info('Добавьте в .env:');
        $this->line('VAPID_PUBLIC_KEY=' . $keys['publicKey']);
        $this->line('VAPID_PRIVATE_KEY=' . $keys['privateKey']);

        return self::SUCCESS;
    }
}
