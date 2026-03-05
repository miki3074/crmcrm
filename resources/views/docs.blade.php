<!-- resources/views/documentation.blade.php -->

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Официальная документация CRM-система «Планшет»</title>

    <!-- Подключаем Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Подключаем Font Awesome для иконок -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 1rem;
        }

        .documentation-container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 2rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
        }

        .doc-header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            padding: 3rem 2rem;
            color: white;
        }

        .doc-content {
            padding: 2rem;
        }

        /* Стили для печатных документов */
        .pdf-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: #dc2626;
            color: white;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }

        .warning-box {
            background: #fffbeb;
            border-left: 4px solid #f59e0b;
            padding: 1rem;
            border-radius: 0.5rem;
            margin: 1rem 0;
        }

        .table-of-contents {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 1rem;
            border: 1px solid #e2e8f0;
            margin-bottom: 2rem;
        }

        .table-of-contents a {
            color: #2563eb;
            text-decoration: none;
        }

        .table-of-contents a:hover {
            text-decoration: underline;
        }

        .role-tab {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: #e2e8f0;
            border-radius: 0.5rem 0.5rem 0 0;
            font-weight: 600;
            margin-right: 0.25rem;
        }

        .role-tab.active {
            background: #3b82f6;
            color: white;
        }

        .doc-table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }

        .doc-table th {
            background: #f1f5f9;
            padding: 0.75rem;
            text-align: left;
            font-weight: 600;
        }

        .doc-table td, .doc-table th {
            border: 1px solid #e2e8f0;
            padding: 0.75rem;
        }

        .doc-table tr:hover {
            background: #f8fafc;
        }

        .glossary-term {
            font-weight: 600;
            color: #2563eb;
        }

        .version-info {
            font-family: monospace;
            background: #1e293b;
            color: #e2e8f0;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            display: inline-block;
        }

        .admin-only {
            background: #fee2e2;
            border-left: 4px solid #dc2626;
            padding: 1rem;
            border-radius: 0.5rem;
            font-size: 0.9rem;
        }

        .admin-only i {
            color: #dc2626;
            margin-right: 0.5rem;
        }
    </style>
</head>

<body>

<?php
// ВНИМАНИЕ: ЗДЕСЬ ДОЛЖНО БЫТЬ НАЗВАНИЕ, УКАЗАННОЕ В СВИДЕТЕЛЬСТВЕ РОСПАТЕНТА!
$appName = "CRM-система «Планшет»"; // ИЗМЕНИТЕ НА ВАШЕ ТОЧНОЕ НАЗВАНИЕ
$certificateName = "CRM-система «Планшет» "; // ДОЛЖНО СОВПАДАТЬ СО СВИДЕТЕЛЬСТВОМ
$version = "2.0.0";
$lastUpdate = "2024";
$registryNumber = "XXXXXX"; // Если есть номер в реестре
?>

<div class="documentation-container">
    <!-- Шапка документации -->
    <div class="doc-header relative overflow-hidden">
        <div class="absolute top-0 right-0 w-64 h-64 bg-white opacity-5 rounded-full -mr-32 -mt-32"></div>
        <div class="absolute bottom-0 left-0 w-48 h-48 bg-white opacity-5 rounded-full -ml-24 -mb-24"></div>

        <div class="relative z-10">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-4xl font-bold">{{ $appName }}</h1>
                <div class="flex space-x-2">
                    <span class="version-info">Версия {{ $version }}</span>

                </div>
            </div>

            <p class="text-xl opacity-90 max-w-3xl mb-2">
                Официальная документация по установке, настройке и эксплуатации
            </p>

            <!-- Важно: указание соответствия свидетельству -->
            <div class="mt-4 inline-block bg-white/10 px-4 py-2 rounded-lg">
                <i class="fas fa-certificate mr-2"></i>
                Наименование соответствует свидетельству Роспатента: {{ $certificateName }}
            </div>

            <!-- Ссылки на PDF версии -->
            <div class="mt-6 flex flex-wrap gap-3">
                <a href="https://disk.yandex.ru/i/jS7rBfpSD_oOLQ" class="bg-white text-gray-900 px-4 py-2 rounded-lg font-medium hover:bg-gray-100 transition flex items-center">

                    Руководство пользователя (PDF)
                </a>

                <a href="https://disk.yandex.ru/i/NbpsAtU17Nm0cA" class="bg-white text-gray-900 px-4 py-2 rounded-lg font-medium hover:bg-gray-100 transition flex items-center">

                    Техническая документация (PDF)
                </a>
            </div>
        </div>
    </div>

    <div class="doc-content">
        <!-- Оглавление -->
        <div class="table-of-contents">
            <h2 class="text-xl font-bold mb-4 flex items-center">
                <i class="fas fa-list-ul mr-2 text-blue-600"></i>
                Оглавление документации
            </h2>
            <div class="grid md:grid-cols-3 gap-4">
                <ul class="space-y-2">
                    <li><a href="#section1"><i class="fas fa-chevron-right text-xs mr-2 text-blue-600"></i> 1. Функциональные характеристики</a></li>
                    <li><a href="#section2"><i class="fas fa-chevron-right text-xs mr-2 text-blue-600"></i> 2. Информация для установки</a></li>
                    <li><a href="#section3"><i class="fas fa-chevron-right text-xs mr-2 text-blue-600"></i> 3. Информация для эксплуатации</a></li>
                </ul>
                <ul class="space-y-2">

                    <li><a href="#errors"><i class="fas fa-chevron-right text-xs mr-2 text-blue-600"></i> 3.4. Возможные ошибки и решения</a></li>
                    <li><a href="#glossary"><i class="fas fa-chevron-right text-xs mr-2 text-blue-600"></i> 4. Глоссарий</a></li>

                </ul>

            </div>
        </div>

        <!-- 1. Функциональные характеристики (полностью переработано) -->
        <section id="section1" class="mb-12 scroll-mt-4">
            <div class="flex items-center mb-6">
                <div class="w-12 h-12 bg-blue-100 rounded-2xl flex items-center justify-center mr-4">
                    <i class="fas fa-cogs text-2xl text-blue-600"></i>
                </div>
                <h2 class="text-3xl font-bold text-gray-800">1. Функциональные характеристики</h2>
            </div>

            <!-- Общее описание -->
            <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
                <h3 class="text-lg font-semibold mb-3">1.1. Назначение системы</h3>
                <p class="text-gray-700 leading-relaxed">
                    Программное обеспечение {{ $appName }} предназначено для автоматизации управления компаниями,
                    проектами и задачами. Система обеспечивает централизованное хранение информации о клиентах,
                    проектах, документах, а также контроль исполнительской дисциплины.
                </p>

                <h3 class="text-lg font-semibold mt-4 mb-3">1.2. Объекты автоматизации</h3>
                <ul class="list-disc pl-5 text-gray-700 space-y-1">
                    <li>Бизнес-процессы управления проектами и задачами</li>
                    <li>Взаимодействие с клиентами (CRM)</li>
                    <li>Документооборот и файловое хранилище</li>
                    <li>Календарное планирование</li>
                    <li>Контроль исполнительской дисциплины</li>
                </ul>
            </div>

            <!-- Подробное описание всех функций с таблицей -->
            <h3 class="text-xl font-semibold mb-4">1.3. Детальное описание функциональных возможностей</h3>

            <div class="overflow-x-auto mb-6">
                <table class="doc-table">
                    <thead>
                    <tr>
                        <th>Модуль</th>
                        <th>Функция</th>
                        <th>Описание</th>
                        <th>Роли с доступом</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td class="font-medium">Управление компанией</td>
                        <td>Создание профилей</td>
                        <td>Создание и редактирование профилей компаний</td>
                        <td>Администратор</td>
                    </tr>
                    <tr>
                        <td class="font-medium">Управление компанией</td>
                        <td>Управление сотрудниками</td>
                        <td>Добавление сотрудников, назначение ролей</td>
                        <td>Владелец компании</td>
                    </tr>
                    <tr>
                        <td class="font-medium">Управление компанией</td>
                        <td>Визуализация структуры</td>
                        <td>Интерактивная схема иерархии и связей</td>
                        <td>Владелец компании</td>
                    </tr>
                    <tr>
                        <td class="font-medium">Проекты и задачи</td>
                        <td>Создание проектов</td>
                        <td>Создание проектов с указанием сроков, бюджета, ответственных</td>
                        <td>Владелец компании</td>
                    </tr>
                    <tr>
                        <td class="font-medium">Проекты и задачи</td>
                        <td>Управление задачами</td>
                        <td>Создание, назначение, контроль задач внутри проектов</td>
                        <td>Владелец компании, Менеджер</td>
                    </tr>
                    <tr>
                        <td class="font-medium">Проекты и задачи</td>
                        <td>Роли в проекте</td>
                        <td>Назначение ролей: Руководитель, Исполнитель, Наблюдатель</td>
                        <td>Владелец компании, Менеджер</td>
                    </tr>
                    <tr>
                        <td class="font-medium">Проекты и задачи</td>
                        <td>Пул задач</td>
                        <td>Фильтрация задач по статусам (В работе, Завершенные, Просроченные)</td>
                        <td>Владелец компании</td>
                    </tr>
                    <tr>
                        <td class="font-medium">Проекты и задачи</td>
                        <td>Чек-листы</td>
                        <td>Создание чек-листов и подзадач внутри основных задач</td>
                        <td>Все пользователи</td>
                    </tr>
                    <tr>
                        <td class="font-medium">Проекты и задачи</td>
                        <td>Коммуникация</td>
                        <td>Встроенный чат внутри каждой задачи</td>
                        <td>Все пользователи</td>
                    </tr>
                    <tr>
                        <td class="font-medium">Календарь</td>
                        <td>Личный календарь</td>
                        <td>Планирование личных событий и встреч</td>
                        <td>Все пользователи</td>
                    </tr>
                    <tr>
                        <td class="font-medium">Календарь</td>
                        <td>Корпоративный календарь</td>
                        <td>Общие события компании, собрания, дедлайны</td>
                        <td>Владелец компании</td>
                    </tr>
                    <tr>
                        <td class="font-medium">Календарь</td>
                        <td>Timeline</td>
                        <td>Визуализация сроков задач на временной шкале</td>
                        <td>Все пользователи</td>
                    </tr>
                    <tr>
                        <td class="font-medium">CRM</td>
                        <td>База клиентов</td>
                        <td>Добавление и редактирование контрагентов</td>
                        <td>Все пользователи</td>
                    </tr>
                    <tr>
                        <td class="font-medium">CRM</td>
                        <td>Поиск по ИНН</td>
                        <td>Автоматический поиск и заполнение реквизитов по ИНН</td>
                        <td>Все пользователи</td>
                    </tr>
                    <tr>
                        <td class="font-medium">Файловое хранилище</td>
                        <td>Загрузка файлов</td>
                        <td>Загрузка документов компании в хранилище</td>
                        <td>Владелец компании, назначенный сотрудник</td>
                    </tr>

                    <tr>
                        <td class="font-medium">Файловое хранилище</td>
                        <td>Права доступа</td>
                        <td>Настройка прав доступа к файлам и папкам</td>
                        <td>Администратор, Менеджер</td>
                    </tr>
                    <tr>
                        <td class="font-medium">Техподдержка</td>
                        <td>Обратная связь</td>
                        <td>Встроенный модуль для связи с поддержкой</td>
                        <td>Все пользователи</td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <!-- Стек технологий (оставляем как у вас) -->
            <div class="mt-8 p-6 bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl border border-gray-200">
                <div class="flex items-center mb-4">
                    <i class="fas fa-code text-2xl text-gray-700 mr-3"></i>
                    <h4 class="font-bold text-gray-800">1.4. Стек технологий</h4>
                </div>
                <div class="flex flex-wrap">
                    <span class="tech-stack-item"><i class="fab fa-php text-blue-600 mr-1"></i> PHP 8.1+ (Laravel 10.x)</span>
                    <span class="tech-stack-item"><i class="fab fa-js text-yellow-500 mr-1"></i> JavaScript ES6+ (Vue.js 3.x)</span>
                    <span class="tech-stack-item"><i class="fas fa-bolt text-purple-600 mr-1"></i> Inertia.js</span>
                    <span class="tech-stack-item"><i class="fas fa-database text-green-600 mr-1"></i> MySQL 8.0+ </span>
                    <span class="tech-stack-item"><i class="fab fa-node text-green-600 mr-1"></i> Node.js 18+</span>
                    <span class="tech-stack-item"><i class="fab fa-npm text-red-600 mr-1"></i> NPM 9+</span>
                    <span class="tech-stack-item"><i class="fas fa-lock text-gray-600 mr-1"></i> Laravel Sanctum (аутентификация)</span>
                </div>
            </div>
        </section>

        <hr class="my-10 border-2 border-gray-100">

        <!-- 2. Информация для установки (улучшаем) -->
        <section id="section2" class="mb-12">
            <div class="flex items-center mb-6">
                <div class="w-12 h-12 bg-green-100 rounded-2xl flex items-center justify-center mr-4">
                    <i class="fas fa-download text-2xl text-green-600"></i>
                </div>
                <h2 class="text-3xl font-bold text-gray-800">2. Информация для установки</h2>
            </div>

            <!-- Предупреждение о лицензиях -->
            <div class="warning-box mb-6">
                <i class="fas fa-info-circle text-amber-500 mr-2"></i>
                <span class="font-medium">Важно:</span> Все компоненты ПО используют открытые лицензии (MIT, BSD),
                не накладывающие ограничений на распространение в РФ. Полный список зависимостей (SBOM) доступен
                <a href="/docs/sbom.json" class="text-blue-600 underline">по ссылке</a>.
            </div>

            <div class="grid md:grid-cols-2 gap-6">
                <!-- Облачное использование -->
                <div class="bg-blue-50 p-6 rounded-xl border border-blue-200">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-cloud text-white"></i>
                        </div>
                        <h3 class="font-bold text-lg text-blue-800">Вариант А: Облачное использование (SaaS)</h3>
                    </div>
                    <p class="text-blue-700 mb-4">Установка на оборудование пользователя не требуется. Доступ через Интернет.</p>

                    <div class="bg-white p-4 rounded-lg">
                        <p class="font-semibold text-gray-700 mb-2">Требования к рабочему месту:</p>
                        <ul class="space-y-2">
                            <li class="flex items-center text-gray-600">
                                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                Веб-браузер: Chrome 80+, Firefox 90+, Safari 15+, Яндекс.Браузер 22+
                            </li>
                            <li class="flex items-center text-gray-600">
                                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                <span>Подключение к Интернет (скорость от 5 Мбит/с)</span>
                            </li>
                            <li class="flex items-center text-gray-600">
                                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                <span>ОС: Windows 10+, macOS 11+, Linux, iOS 14+, Android 10+</span>
                            </li>
                            <li class="flex items-center text-gray-600">
                                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                <span>Разрешение экрана: от 1280x720</span>
                            </li>
                        </ul>

                        <div class="mt-4 p-3 bg-gray-100 rounded-lg">
                            <p class="text-sm font-mono">
                                <i class="fas fa-link text-blue-500 mr-2"></i>
                                <a href="http://109.107.189.165/" class="text-blue-600 hover:underline" target="_blank">http://109.107.189.165/</a>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Локальная установка -->
                <div class="bg-purple-50 p-6 rounded-xl border border-purple-200">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-server text-white"></i>
                        </div>
                        <h3 class="font-bold text-lg text-purple-800">Вариант Б: Локальная установка</h3>
                    </div>

                    <div class="space-y-3 mb-4">
                        <div class="flex items-center text-purple-700">
                            <i class="fas fa-cog mr-2"></i>
                            <span class="font-semibold">Системные требования (сервер):</span>
                        </div>
                        <ul class="space-y-1 text-purple-600 text-sm ml-6">
                            <li>• Процессор: 2 ядра, 2+ GHz</li>
                            <li>• ОЗУ: 4 GB (рекомендуется 8 GB)</li>
                            <li>• Диск: 20 GB SSD</li>
                            <li>• ОС: Ubuntu 20.04+ / Debian 11+ / CentOS 8+</li>
                        </ul>
                        <div class="flex items-center text-purple-700 mt-2">
                            <i class="fas fa-cog mr-2"></i>
                            <span class="font-semibold">Программные требования:</span>
                        </div>
                        <ul class="space-y-1 text-purple-600 text-sm ml-6">
                            <li>• PHP >= 8.1 (с расширениями: BCMath, Ctype, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML)</li>
                            <li>• Composer 2.x</li>
                            <li>• Node.js 18+ & NPM 9+</li>
                            <li>• MySQL 8.0+ или PostgreSQL 14+</li>
                            <li>• Nginx / Apache</li>
                        </ul>
                    </div>

                    <div class="code-block">
                        <div><span class="text-green-400"># Полная инструкция по установке:</span></div>
                        <div class="my-2"><span class="text-yellow-300"># 1. Клонирование репозитория</span></div>
                        <div><span class="text-yellow-300">git clone</span> https://github.com/miki3074/crmcrm.git /var/www/crm</div>
                        <div>cd /var/www/crm</div>
                        <div class="my-2"><span class="text-yellow-300"># 2. Установка зависимостей PHP</span></div>
                        <div>composer install --no-dev --optimize-autoloader</div>
                        <div class="my-2"><span class="text-yellow-300"># 3. Установка зависимостей JS</span></div>
                        <div>npm install && npm run build</div>
                        <div class="my-2"><span class="text-yellow-300"># 4. Настройка окружения</span></div>
                        <div>cp .env.example .env</div>
                        <div>php artisan key:generate</div>
                        <div class="my-2"><span class="text-yellow-300"># 5. Настройка .env файла:</span></div>
                        <div>DB_CONNECTION=mysql</div>
                        <div>DB_HOST=127.0.0.1</div>
                        <div>DB_PORT=3306</div>
                        <div>DB_DATABASE=<span class="text-yellow-300">crm_db</span></div>
                        <div>DB_USERNAME=<span class="text-yellow-300">crm_user</span></div>
                        <div>DB_PASSWORD=<span class="text-yellow-300">secure_password</span></div>
                        <div class="my-2"><span class="text-yellow-300"># 6. Миграции и наполнение БД</span></div>
                        <div>php artisan migrate --force</div>
                        <div>php artisan db:seed --force</div>
                        <div class="my-2"><span class="text-yellow-300"># 7. Настройка веб-сервера</span></div>
                        <div># Настройте корневую директорию на /var/www/crm/public</div>
                        <div># Пример для Nginx в документации администратора (PDF)</div>
                        <div class="my-2"><span class="text-yellow-300"># 8. Запуск</span></div>
                        <div>php artisan optimize</div>
                        <div># Настройте supervisor для queue:work</div>
                    </div>


                </div>
            </div>
        </section>

        <hr class="my-10 border-2 border-gray-100">

        <!-- 3. Информация для эксплуатации (ПОЛНОСТЬЮ ПЕРЕРАБОТАНО) -->
        <section id="section3" class="mb-12">
            <div class="flex items-center mb-6">
                <div class="w-12 h-12 bg-orange-100 rounded-2xl flex items-center justify-center mr-4">
                    <i class="fas fa-play-circle text-2xl text-orange-600"></i>
                </div>
                <h2 class="text-3xl font-bold text-gray-800">3. Информация для эксплуатации</h2>
            </div>

            <!-- Общие сведения -->
            <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
                <h3 class="text-lg font-semibold mb-3">3.0. Общие сведения</h3>
                <p class="text-gray-700 mb-4">
                    Для начала работы в системе необходимо пройти авторизацию. Система поддерживает три роли пользователей с разными уровнями доступа:
                </p>
                <ul class="list-disc pl-5 text-gray-700 space-y-1">
                    <li><span class="font-medium">Сотрудник</span> - базовый доступ, работа с задачами, календарем, CRM</li>
                    <li><span class="font-medium">Менеджер</span> - назначение задач, управление клиентами</li>
                    <li><span class="font-medium">Администратор</span> - полный доступ, управление пользователями, настройки системы</li>
                </ul>
            </div>

            <!-- 3.1. Руководство пользователя -->
            <div id="user-guide" class="scroll-mt-4 mb-8">
                <div class="flex items-center mb-4">
                    <div class="role-tab active">
                        <i class="fas fa-user mr-2"></i>Руководство пользователя
                    </div>
                    <div class="role-tab">
                        <i class="fas fa-user-tie mr-2"></i>Менеджер
                    </div>
                </div>

                <h3 class="text-xl font-semibold mb-4">3.1. Интерфейс и навигация</h3>

                <div class="grid md:grid-cols-3 gap-6 mb-6">
                    <div class="bg-white border border-gray-200 p-4 rounded-lg">
                        <h4 class="font-bold mb-2">Главное меню</h4>
                        <p class="text-sm text-gray-600">Расположено вверху, содержит разделы:  Календарь, Клиенты, Хранилище, Схема, Сотрудники,.</p>
                    </div>
                    <div class="bg-white border border-gray-200 p-4 rounded-lg">
                        <h4 class="font-bold mb-2">Верхняя панель</h4>
                        <p class="text-sm text-gray-600">Уведомления, поиск по системе, профиль пользователя, быстрый переход.</p>
                    </div>
                    <div class="bg-white border border-gray-200 p-4 rounded-lg">
                        <h4 class="font-bold mb-2">Рабочая область</h4>
                        <p class="text-sm text-gray-600">Основная область для работы с выбранным разделом.</p>
                    </div>
                </div>


            </div>



            <!-- 3.3. Возможные ошибки и решения -->
            <div id="errors" class="scroll-mt-4 mb-8">
                <h3 class="text-xl font-semibold mb-4">3.4. Возможные ошибки и способы их устранения</h3>

                <div class="overflow-x-auto">
                    <table class="doc-table">
                        <thead>
                        <tr>
                            <th>Код/Тип ошибки</th>
                            <th>Описание</th>
                            <th>Причина</th>
                            <th>Решение</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>401 Unauthorized</td>
                            <td>Ошибка авторизации</td>
                            <td>Сессия истекла, неверные cookies</td>
                            <td>Выйти и заново авторизоваться. Очистить cookies браузера.</td>
                        </tr>
                        <tr>
                            <td>403 Forbidden</td>
                            <td>Нет доступа к разделу</td>
                            <td>У пользователя недостаточно прав</td>
                            <td>Обратиться к администратору для изменения роли.</td>
                        </tr>
                        <tr>
                            <td>404 Not Found</td>
                            <td>Страница не найдена</td>
                            <td>Неверный URL, объект удален</td>
                            <td>Проверить правильность ссылки, обновить страницу.</td>
                        </tr>
                        <tr>
                            <td>419 Page Expired</td>
                            <td>CSRF-токен истек</td>
                            <td>Долгая бездействие на форме</td>
                            <td>Обновить страницу и отправить форму заново.</td>
                        </tr>
                        <tr>
                            <td>500 Internal Server</td>
                            <td>Внутренняя ошибка сервера</td>
                            <td>Проблемы на стороне сервера</td>
                            <td>Сообщить администратору. Проверить логи в storage/logs.</td>
                        </tr>
                        <tr>
                            <td>502 Bad Gateway</td>
                            <td>Ошибка соединения</td>
                            <td>Проблемы с сервером, перезагрузка</td>
                            <td>Подождать 1-2 минуты и обновить страницу.</td>
                        </tr>
                        <tr>
                            <td>Ошибка загрузки файла</td>
                            <td>Файл не загружается</td>
                            <td>Превышен размер, неверный формат</td>
                            <td>Проверить ограничения: макс. размер 100MB, форматы: jpg, png, pdf, doc, xls, zip.</td>
                        </tr>
                        <tr>
                            <td>Ошибка поиска по ИНН</td>
                            <td>Данные не загружаются</td>
                            <td>Некорректный ИНН, проблемы с внешним API</td>
                            <td>Проверить ИНН (10/12 цифр). Заполнить реквизиты вручную.</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <hr class="my-10 border-2 border-gray-100">

        <!-- 4. Глоссарий -->
        <section id="glossary" class="mb-12">
            <div class="flex items-center mb-6">
                <div class="w-12 h-12 bg-indigo-100 rounded-2xl flex items-center justify-center mr-4">
                    <i class="fas fa-book text-2xl text-indigo-600"></i>
                </div>
                <h2 class="text-3xl font-bold text-gray-800">4. Глоссарий (словарь терминов)</h2>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <div class="p-3 border-b">
                    <span class="glossary-term">Компания</span> - юридическое лицо или ИП, зарегистрированное в системе, основная единица учета.
                </div>
                <div class="p-3 border-b">
                    <span class="glossary-term">Проект</span> - совокупность задач, объединенных общей целью, сроками и бюджетом.
                </div>
                <div class="p-3 border-b">
                    <span class="glossary-term">Задача</span> - единица работы, имеющая исполнителя, срок и статус выполнения.
                </div>
                <div class="p-3 border-b">
                    <span class="glossary-term">Подзадача</span> - часть задачи, элемент чек-листа.
                </div>
                <div class="p-3 border-b">
                    <span class="glossary-term">Роль</span> - набор прав доступа пользователя в системе.
                </div>
                <div class="p-3 border-b">
                    <span class="glossary-term">Администратор</span> - пользователь с полными правами на управление системой.
                </div>
                <div class="p-3 border-b">
                    <span class="glossary-term">Менеджер</span> - пользователь, управляющий проектами и задачами.
                </div>
                <div class="p-3 border-b">
                    <span class="glossary-term">Сотрудник</span> - пользователь с базовыми правами исполнителя.
                </div>
                <div class="p-3 border-b">
                    <span class="glossary-term">Дедлайн</span> - крайний срок выполнения задачи.
                </div>
                <div class="p-3 border-b">
                    <span class="glossary-term">Чек-лист</span> - список подзадач внутри основной задачи.
                </div>
                <div class="p-3 border-b">
                    <span class="glossary-term">Пул задач</span> - общий список всех задач с возможностью фильтрации.
                </div>
                <div class="p-3 border-b">
                    <span class="glossary-term">Контрагент</span> - клиент или партнер компании.
                </div>
                <div class="p-3 border-b">
                    <span class="glossary-term">ИНН</span> - идентификационный номер налогоплательщика.
                </div>
                <div class="p-3 border-b">
                    <span class="glossary-term">API</span> - программный интерфейс для интеграции с другими системами.
                </div>
            </div>
        </section>

        <hr class="my-10 border-2 border-gray-100">

        <!-- 5. Техническая поддержка -->



</div>

</body>
</html>
