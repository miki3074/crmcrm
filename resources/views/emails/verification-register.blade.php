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
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
            text-decoration: none;
            padding: 14px 30px;
            border-radius: 12px;
            font-weight: 500;
            margin: 20px 0;
            text-align: center;
        }
        .warning {
            background: #fef3c7;
            border-radius: 12px;
            padding: 15px;
            margin-top: 20px;
            font-size: 14px;
            color: #92400e;
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
        .gradient-text {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="header">
            <div style="font-size: 48px;">📧</div>
            <h1>Подтверждение <span class="gradient-text">регистрации</span></h1>
            <p>Добро пожаловать в систему управления проектами!</p>
        </div>

        <p>Здравствуйте, <strong>{{ $user->name }}</strong>!</p>

        <p>Для завершения регистрации и активации вашего аккаунта, пожалуйста, подтвердите ваш email адрес.</p>

        <div style="text-align: center;">
            <a href="{{ $verificationUrl }}" class="button" style="color: white;">Подтвердить email</a>
        </div>

        <p>Или скопируйте ссылку в браузер:</p>
        <p style="word-break: break-all; background: #f1f5f9; padding: 10px; border-radius: 8px; font-size: 12px;">
            {{ $verificationUrl }}
        </p>

        <div class="warning">
            ⚠️ <strong>Важно!</strong> Если вы не регистрировались в системе, просто проигнорируйте это письмо.
        </div>

        <div class="footer">
            <p>С уважением,<br>Команда системы управления проектами</p>
            <p style="font-size: 11px;">Это автоматическое сообщение, пожалуйста, не отвечайте на него.</p>
        </div>
    </div>
</div>
</body>
</html>
