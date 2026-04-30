<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Подтверждение регистрации</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .card {
            background: #ffffff;
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
            border: 1px solid #e2e8f0;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .code {
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            margin: 30px 0;
        }
        .code-number {
            font-size: 48px;
            font-weight: 700;
            letter-spacing: 8px;
            color: #0284c7;
            font-family: monospace;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 12px;
        }
        h1 {
            color: #1e293b;
            font-size: 24px;
            margin-bottom: 10px;
        }
        p {
            color: #475569;
        }
        .warning {
            background: #fef3c7;
            border-radius: 12px;
            padding: 15px;
            margin-top: 20px;
            font-size: 14px;
            color: #92400e;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="header">
            <div style="font-size: 48px;">✉️</div>
            <h1>Подтверждение регистрации</h1>
            <p>Здравствуйте, <strong>{{ $name }}</strong>!</p>
            <p>Для завершения регистрации введите код подтверждения</p>
        </div>

        <div class="code">
            <p style="margin-bottom: 15px; color: #64748b;">Ваш код подтверждения:</p>
            <div class="code-number">{{ $code }}</div>
            <p style="margin-top: 15px; font-size: 14px; color: #64748b;">Код действителен в течение 15 минут</p>
        </div>

        <p>Если вы не регистрировались в системе, просто проигнорируйте это письмо.</p>

        <div class="warning">
            ⚠️ <strong>Важно!</strong> Никому не сообщайте этот код. Сотрудники службы поддержки никогда не запрашивают код подтверждения.
        </div>

        <div class="footer">
            <p>С уважением,<br>Команда системы управления проектами</p>
            <p style="font-size: 11px;">Это автоматическое сообщение, пожалуйста, не отвечайте на него.</p>
        </div>
    </div>
</div>
</body>
</html>
