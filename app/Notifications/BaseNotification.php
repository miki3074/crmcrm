<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;

class BaseNotification extends Notification
{
    use Queueable;

    protected $subject;
    protected $greeting;
    protected $introLines = [];
    protected $actionText;
    protected $actionUrl;
    protected $outroLines = [];
    protected $salutation;
    protected $level = 'info'; // info, success, warning, error
    protected $userName;

    public function __construct()
    {
        $this->salutation = "С уважением,<br>Команда системы управления проектами";
    }

    /**
     * Установить тему письма
     */
    public function subject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * Установить приветствие
     */
    public function greeting($greeting)
    {
        $this->greeting = $greeting;
        return $this;
    }

    /**
     * Добавить строку в тело письма
     */
    public function line($line)
    {
        $this->introLines[] = $line;
        return $this;
    }

    /**
     * Добавить действие (кнопку)
     */
    public function action($text, $url)
    {
        $this->actionText = $text;
        $this->actionUrl = $url;
        return $this;
    }

    /**
     * Установить уровень уведомления
     */
    public function level($level)
    {
        $this->level = $level;
        return $this;
    }

    /**
     * Установить имя пользователя
     */
    public function forUser($userName)
    {
        $this->userName = $userName;
        return $this;
    }

    /**
     * Получить цвет для уровня
     */
    protected function getColor()
    {
        switch ($this->level) {
            case 'success':
                return '#10b981';
            case 'warning':
                return '#f59e0b';
            case 'error':
                return '#ef4444';
            default:
                return '#4f46e5';
        }
    }

    /**
     * Получить иконку для уровня
     */
    protected function getIcon()
    {
        switch ($this->level) {
            case 'success':
                return '✅';
            case 'warning':
                return '⚠️';
            case 'error':
                return '❌';
            default:
                return '📧';
        }
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mailMessage = (new MailMessage)
            ->subject($this->subject)
            ->greeting($this->greeting ?? "Здравствуйте, {$notifiable->name}!");

        // Добавляем основное содержимое
        foreach ($this->introLines as $line) {
            $mailMessage->line($line);
        }

        // Добавляем кнопку действия, если есть
        if ($this->actionText && $this->actionUrl) {
            $mailMessage->action($this->actionText, $this->actionUrl);
        }

        // Добавляем заключительные строки
        foreach ($this->outroLines as $line) {
            $mailMessage->line($line);
        }

        // Добавляем подпись
        $mailMessage->salutation($this->salutation);

        return $mailMessage;
    }

    /**
     * Создать HTML письмо для красивой верстки
     */
    public function toMailHtml(object $notifiable): string
    {
        $color = $this->getColor();
        $icon = $this->getIcon();
        $userName = $this->userName ?? $notifiable->name;
        $currentYear = Carbon::now()->year;

        // Формируем строки контента
        $contentHtml = '';
        foreach ($this->introLines as $line) {
            $contentHtml .= "<p style=\"margin: 0 0 16px; color: #475569; line-height: 1.6;\">{$line}</p>";
        }

        // Формируем кнопку действия
        $buttonHtml = '';
        if ($this->actionText && $this->actionUrl) {
            $buttonHtml = "
                <div style=\"text-align: center; margin: 32px 0;\">
                    <a href=\"{$this->actionUrl}\" style=\"display: inline-block; background: {$color}; color: white; text-decoration: none; padding: 12px 32px; border-radius: 12px; font-weight: 500;\">
                        {$this->actionText}
                    </a>
                </div>
            ";
        }

        // Формируем заключительные строки
        $outroHtml = '';
        foreach ($this->outroLines as $line) {
            $outroHtml .= "<p style=\"margin: 0 0 16px; color: #64748b; line-height: 1.6;\">{$line}</p>";
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$this->subject}</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #f8fafc;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="background: #ffffff; border-radius: 24px; padding: 40px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.02); border: 1px solid #e2e8f0;">

            <!-- Header -->
            <div style="text-align: center; margin-bottom: 30px;">
                <div style="font-size: 48px; margin-bottom: 16px;">{$icon}</div>
                <h1 style="color: #1e293b; font-size: 24px; margin: 0 0 8px;">{$this->subject}</h1>
                <div style="width: 60px; height: 3px; background: {$color}; margin: 16px auto; border-radius: 3px;"></div>
            </div>

            <!-- Content -->
            <div style="color: #475569;">
                <p style="margin: 0 0 24px; font-size: 16px;">Здравствуйте, <strong>{$userName}</strong>!</p>
                {$contentHtml}
                {$buttonHtml}
                {$outroHtml}
            </div>

            <!-- Footer -->
            <div style="margin-top: 32px; padding-top: 24px; border-top: 1px solid #e2e8f0; text-align: center;">
                <p style="margin: 0 0 8px; color: #64748b; font-size: 14px;">{$this->salutation}</p>
                <p style="margin: 0; color: #94a3b8; font-size: 12px;">
                    © {$currentYear} Система управления проектами. Все права защищены.
                </p>
                <p style="margin: 16px 0 0; color: #94a3b8; font-size: 11px;">
                    Это автоматическое сообщение, пожалуйста, не отвечайте на него.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
