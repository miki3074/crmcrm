{{-- resources/views/file-viewer.blade.php --}}
    <!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $fileName }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #f0f2f5;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .header {
            background: #fff;
            padding: 12px 24px;
            border-bottom: 1px solid #e4e7ec;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
            z-index: 10;
            flex-wrap: wrap;
            gap: 8px;
        }
        .header-left {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }
        .header .file-icon {
            font-size: 24px;
        }
        .header h2 {
            font-size: 16px;
            font-weight: 500;
            color: #1a1a2e;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .header-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .header-actions a, .header-actions button {
            padding: 6px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: none;
            cursor: pointer;
        }
        .btn-download {
            background: #1a73e8;
            color: #fff;
        }
        .btn-download:hover {
            background: #1557b0;
        }
        .btn-yandex {
            background: #ffcc00;
            color: #1a1a2e;
        }
        .btn-yandex:hover {
            background: #e6b800;
        }
        .btn-google {
            background: #4285f4;
            color: #fff;
        }
        .btn-google:hover {
            background: #3367d6;
        }
        .btn-close {
            background: #e4e7ec;
            color: #333;
        }
        .btn-close:hover {
            background: #d1d5db;
        }
        .container {
            flex: 1;
            padding: 20px;
            overflow: hidden;
        }
        .file-wrapper {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            width: 100%;
            height: 100%;
            overflow: hidden;
            position: relative;
        }
        iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        .viewer-content {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100%;
            padding: 40px;
            text-align: center;
            background: #fafafa;
        }
        .viewer-content .icon {
            font-size: 80px;
            margin-bottom: 24px;
        }
        .viewer-content h3 {
            font-size: 22px;
            color: #1a1a2e;
            margin-bottom: 8px;
        }
        .viewer-content p {
            color: #666;
            max-width: 500px;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .viewer-content .btn-group {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: center;
        }
        .viewer-content .btn-group a,
        .viewer-content .btn-group button {
            padding: 10px 24px;
            border-radius: 8px;
            border: none;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary {
            background: #1a73e8;
            color: #fff;
        }
        .btn-primary:hover {
            background: #1557b0;
        }
        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
            flex-direction: column;
            gap: 16px;
        }
        .loading-spinner {
            width: 48px;
            height: 48px;
            border: 4px solid #e4e7ec;
            border-top-color: #1a73e8;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        @media (max-width: 768px) {
            .header {
                padding: 10px 16px;
            }
            .header h2 {
                font-size: 14px;
                max-width: 150px;
            }
            .header-actions a, .header-actions button {
                font-size: 12px;
                padding: 4px 12px;
            }
            .viewer-content .icon {
                font-size: 48px;
            }
            .viewer-content h3 {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
<div class="header">
    <div class="header-left">
            <span class="file-icon">
                @if(in_array($fileExt, ['doc', 'docx', 'odt', 'rtf']))
                    📘
                @elseif(in_array($fileExt, ['xls', 'xlsx', 'ods']))
                    📊
                @elseif(in_array($fileExt, ['ppt', 'pptx', 'odp']))
                    📙
                @elseif(in_array($fileExt, ['pdf']))
                    📕
                @elseif(in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp']))
                    🖼️
                @else
                    📄
                @endif
            </span>
        <h2 title="{{ $fileName }}">{{ $fileName }}</h2>
    </div>
    <div class="header-actions">
        @if($viewType === 'yandex')
            <button onclick="openInYandex()" class="btn-yandex">
                📄 Яндекс Документы
            </button>
            <button onclick="openInGoogle()" class="btn-google">
                📄 Google Docs
            </button>
        @endif
        <a href="{{ $fileUrl }}" download="{{ $fileName }}" class="btn-download">
            ⬇️ Скачать
        </a>
        <button onclick="window.close()" class="btn-close">✕ Закрыть</button>
    </div>
</div>
<div class="container">
    <div class="file-wrapper">
        @if($viewType === 'direct')
            <iframe src="{{ $fileUrl }}"></iframe>
        @elseif($viewType === 'yandex')
            {{-- Показываем сообщение с кнопками просмотра --}}
            <div class="viewer-content">
                <div class="icon">📄</div>
                <h3>Просмотр документа</h3>
                <p>
                    Файл <strong>{{ $fileName }}</strong> можно открыть через онлайн-просмотрщик.
                    Выберите удобный способ просмотра ниже.
                </p>
                <div class="btn-group">
                    <button onclick="openInYandex()" class="btn-yandex" style="font-size:16px;padding:12px 32px;">
                        📄 Яндекс Документы
                    </button>
                    <button onclick="openInGoogle()" class="btn-google" style="font-size:16px;padding:12px 32px;">
                        📄 Google Docs
                    </button>
                </div>
                <div style="margin-top:20px;font-size:13px;color:#999;">
                    Или <a href="{{ $fileUrl }}" download="{{ $fileName }}">скачайте файл</a> для просмотра в программе
                </div>
            </div>
        @else
            {{-- Неподдерживаемый формат --}}
            <div class="viewer-content">
                <div class="icon">📄</div>
                <h3>Предварительный просмотр недоступен</h3>
                <p>
                    Файлы с расширением <strong>.{{ $fileExt }}</strong> не поддерживаются для просмотра в браузере.
                </p>
                <div class="btn-group">
                    <a href="{{ $fileUrl }}" download="{{ $fileName }}" class="btn-primary">
                        ⬇️ Скачать файл
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>

<script>
    function openInYandex() {
        const fileUrl = '{{ $fileUrl }}';
        const fileName = '{{ $fileName }}';
        const viewerUrl = `https://docviewer.yandex.com/view/0/?url=${encodeURIComponent(fileUrl)}&name=${encodeURIComponent(fileName)}`;
        window.open(viewerUrl, '_blank');
    }

    function openInGoogle() {
        const fileUrl = '{{ $fileUrl }}';
        const viewerUrl = `https://docs.google.com/viewer?url=${encodeURIComponent(fileUrl)}&embedded=true`;
        window.open(viewerUrl, '_blank');
    }

    // Если открытие через Яндекс или Google не работает, предлагаем скачать
    document.addEventListener('DOMContentLoaded', function() {
        @if($viewType === 'yandex')
        // Через 5 секунд показываем дополнительный совет
        setTimeout(() => {
            const container = document.querySelector('.viewer-content .btn-group');
            if (container) {
                const note = document.createElement('div');
                note.style.marginTop = '12px';
                note.style.fontSize = '13px';
                note.style.color = '#999';
                note.innerHTML = '💡 Если просмотр не открывается, попробуйте <a href="{{ $fileUrl }}" download="{{ $fileName }}">скачать файл</a>';
                container.parentNode.appendChild(note);
            }
        }, 5000);
        @endif
    });
</script>
</body>
</html>
