<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            background: #fff1f2;
            padding: 40px 20px;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .email-header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            padding: 40px 30px;
            text-align: center;
            position: relative;
        }
        .email-header h1 {
            color: white;
            font-size: 28px;
            margin: 0;
            font-weight: 700;
        }
        .email-header p {
            color: rgba(255,255,255,0.9);
            margin-top: 10px;
        }
        .email-body {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 20px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 20px;
        }
        .content {
            color: #4b5563;
            margin-bottom: 30px;
        }
        .content p {
            margin-bottom: 15px;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white !important;
            text-decoration: none;
            padding: 14px 35px;
            border-radius: 50px;
            font-weight: 600;
            margin: 20px 0;
            transition: transform 0.3s;
        }
        .button:hover {
            transform: translateY(-2px);
        }
        .divider {
            height: 1px;
            background: #e5e7eb;
            margin: 30px 0;
        }
        .footer {
            background: #f9fafb;
            padding: 20px 30px;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
        }
        .footer a {
            color: #4f46e5;
            text-decoration: none;
        }
        @media (max-width: 600px) {
            .email-body {
                padding: 25px 20px;
            }
            .email-header {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
<div class="email-container">
    <div class="email-header">
        <h1>CRM НПО Энерготех</h1>
        <p>Система управления проектами</p>
    </div>

    <div class="email-body">
        <div class="greeting">
            {{ $greeting ?? 'Здравствуйте!' }}
        </div>

        <div class="content">
            {!! $content !!}
        </div>

        @if(isset($actionUrl))
            <div style="text-align: center;">
                <a href="{{ $actionUrl }}" class="button">
                    {{ $actionText ?? 'Перейти' }}
                </a>
            </div>
        @endif
    </div>

    <div class="footer">
        <p>© {{ date('Y') }} CRM НПО Энерготех. Все права защищены.</p>
        <p style="margin-top: 10px;">
            Это автоматическое сообщение, пожалуйста, не отвечайте на него.
        </p>
        <p style="margin-top: 10px;">
            <a href="{{ config('app.url') }}">{{ config('app.url') }}</a>
        </p>
    </div>
</div>
</body>
</html>
