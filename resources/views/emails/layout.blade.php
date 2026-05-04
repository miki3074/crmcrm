<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Уведомление' }}</title>
    <style>
        /* Reset styles */
        body, table, td, p, a {
            margin: 0;
            padding: 0;
            border: 0;
            font-size: 100%;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
        }

        body {
            background-color: #f8fafc;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
        }

        .header {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            padding: 32px 24px;
            text-align: center;
        }

        .header-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }

        .header-title {
            color: #ffffff;
            font-size: 24px;
            font-weight: 700;
            margin: 0;
        }

        .content {
            padding: 32px 24px;
        }

        .greeting {
            font-size: 20px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 20px;
        }

        .message-box {
            background: #f8fafc;
            border-radius: 16px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #4f46e5;
        }

        .info-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .info-label {
            width: 120px;
            font-weight: 600;
            color: #475569;
        }

        .info-value {
            flex: 1;
            color: #1e293b;
        }

        .message-content {
            background: #ffffff;
            border-radius: 12px;
            padding: 16px;
            margin: 16px 0;
            border: 1px solid #e2e8f0;
            color: #334155;
            white-space: pre-wrap;
        }

        .button {
            display: inline-block;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: #ffffff;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 500;
            margin: 20px 0;
            text-align: center;
        }

        .footer {
            background: #f8fafc;
            padding: 24px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            font-size: 12px;
            color: #64748b;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-primary {
            background: #e0e7ff;
            color: #4f46e5;
        }

        .badge-success {
            background: #dcfce7;
            color: #16a34a;
        }

        .badge-warning {
            background: #fef3c7;
            color: #d97706;
        }

        .badge-danger {
            background: #fee2e2;
            color: #dc2626;
        }

        .attachments {
            margin-top: 16px;
            padding: 12px;
            background: #f1f5f9;
            border-radius: 12px;
        }

        @media (max-width: 600px) {
            .info-row {
                flex-direction: column;
            }
            .info-label {
                width: auto;
                margin-bottom: 4px;
            }
            .button {
                display: block;
                text-align: center;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="header-icon">{{ $icon ?? '📧' }}</div>
        <h1 class="header-title">{{ $title ?? 'Уведомление' }}</h1>
    </div>

    <div class="content">
        {{ $slot }}
    </div>

    <div class="footer">
        <p>© {{ date('Y') }} {{ config('app.name') }}. Все права защищены.</p>
        <p style="margin-top: 8px; font-size: 11px;">
            Это автоматическое сообщение, пожалуйста, не отвечайте на него.
        </p>
    </div>
</div>
</body>
</html>
