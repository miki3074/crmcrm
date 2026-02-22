<!-- resources/views/documentation.blade.php -->

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Документация ПО «Планшет CRM-система»</title>

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
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 2rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
        }

        .doc-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 3rem 2rem;
            color: white;
        }

        .doc-content {
            padding: 2rem;
        }

        .feature-card {
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }

        .feature-card:hover {
            transform: translateX(5px);
            border-left-color: #667eea;
            background-color: #f9fafb;
        }

        .code-block {
            background: #1a1e2c;
            border-radius: 1rem;
            padding: 1.5rem;
            color: #e2e8f0;
            font-family: 'Fira Code', monospace;
            font-size: 0.9rem;
            line-height: 1.6;
            overflow-x: auto;
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.3);
        }

        .code-keyword {
            color: #ff79c6;
        }

        .code-string {
            color: #50fa7b;
        }

        .code-comment {
            color: #6272a4;
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .badge-blue {
            background: #e0edff;
            color: #2563eb;
        }

        .badge-green {
            background: #dcfce7;
            color: #16a34a;
        }

        .badge-purple {
            background: #f3e8ff;
            color: #9333ea;
        }

        .badge-orange {
            background: #fff3e0;
            color: #ea580c;
        }

        .module-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .stat-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 1rem;
            padding: 1.5rem;
            text-align: center;
        }

        .tech-stack-item {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: #f1f5f9;
            border-radius: 9999px;
            margin: 0.25rem;
            font-size: 0.875rem;
            color: #334155;
        }
    </style>
</head>

<body>

<?php
$appName = "Планшет CRM-система";
$version = "2.0.0";
$lastUpdate = "2024";
?>

<div class="documentation-container">
    <!-- Шапка документации -->
    <div class="doc-header relative overflow-hidden">
        <div class="absolute top-0 right-0 w-64 h-64 bg-white opacity-5 rounded-full -mr-32 -mt-32"></div>
        <div class="absolute bottom-0 left-0 w-48 h-48 bg-white opacity-5 rounded-full -ml-24 -mb-24"></div>

        <div class="relative z-10">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center space-x-3">

                </div>
                <div class="flex space-x-2">

                </div>
            </div>

            <p class="text-xl opacity-90 max-w-3xl">
                 Документация по установке, настройке и эксплуатации CRM-системы для управления компаниями, проектами и задачами
            </p>


        </div>
    </div>

    <div class="doc-content">
        <!-- Быстрая статистика -->


        <!-- 1. Функциональные характеристики -->
        <section class="mb-12">
            <div class="flex items-center mb-6">
                <div class="w-12 h-12 bg-blue-100 rounded-2xl flex items-center justify-center mr-4">
                    <i class="fas fa-cogs text-2xl text-blue-600"></i>
                </div>
                <h2 class="text-3xl font-bold text-gray-800">1. Описание функциональных характеристик</h2>
            </div>

            <p class="text-gray-600 leading-relaxed mb-6 text-lg">
                Программное обеспечение «{{ $appName }}» представляет собой CRM-систему для комплексного управления компаниями,
                проектами и задачами. Система предназначена для автоматизации бизнес-процессов, контроля исполнительской
                дисциплины и организации совместной работы сотрудников.
            </p>

            <h3 class="text-xl font-semibold text-gray-800 mb-4">Основные функциональные возможности:</h3>

            <div class="space-y-4">
                <!-- Управление компанией -->
                <div class="feature-card bg-white border border-gray-200 p-5 rounded-xl shadow-sm">
                    <div class="flex items-start">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3 flex-shrink-0">
                            <i class="fas fa-building text-blue-600"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-800 mb-2">Управление структурой компании</h4>
                            <p class="text-gray-600">Создание профилей компаний, добавление сотрудников, назначение ролей («Сотрудник», «Менеджер»), визуализация иерархии и связей через интерактивную схему.</p>
                        </div>
                    </div>
                </div>

                <!-- Управление проектами -->
                <div class="feature-card bg-white border border-gray-200 p-5 rounded-xl shadow-sm">
                    <div class="flex items-start">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3 flex-shrink-0">
                            <i class="fas fa-tasks text-green-600"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-gray-800 mb-2">Управление проектами и задачами</h4>
                            <ul class="list-disc pl-5 text-gray-600 space-y-1">
                                <li>Создание проектов и вложенных задач</li>
                                <li>Назначение ролей в проекте: Руководитель, Исполнитель, Наблюдатель</li>
                                <li>Пул задач: фильтрация по статусам (В работе, Завершенные, Просроченные)</li>
                                <li>Чек-листы, подзадачи и встроенный чат внутри каждой задачи</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Календарь -->
                <div class="feature-card bg-white border border-gray-200 p-5 rounded-xl shadow-sm">
                    <div class="flex items-start">
                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-3 flex-shrink-0">
                            <i class="fas fa-calendar-alt text-purple-600"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-800 mb-2">Календарь и планирование</h4>
                            <p class="text-gray-600">Личный и корпоративный календарь, создание событий, отображение сроков задач (timeline).</p>
                        </div>
                    </div>
                </div>

                <!-- CRM -->
                <div class="feature-card bg-white border border-gray-200 p-5 rounded-xl shadow-sm">
                    <div class="flex items-start">
                        <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center mr-3 flex-shrink-0">
                            <i class="fas fa-users text-orange-600"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-800 mb-2">База клиентов (CRM)</h4>
                            <p class="text-gray-600">Добавление контрагентов в проекты, автоматический поиск и автозаполнение реквизитов по ИНН.</p>
                        </div>
                    </div>
                </div>

                <!-- Хранилище -->
                <div class="feature-card bg-white border border-gray-200 p-5 rounded-xl shadow-sm">
                    <div class="flex items-start">
                        <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center mr-3 flex-shrink-0">
                            <i class="fas fa-cloud-upload-alt text-red-600"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-800 mb-2">Файловое хранилище</h4>
                            <p class="text-gray-600">Загрузка и хранение документов компании, прикрепление файлов к задачам и проектам.</p>
                        </div>
                    </div>
                </div>

                <!-- Поддержка -->
                <div class="feature-card bg-white border border-gray-200 p-5 rounded-xl shadow-sm">
                    <div class="flex items-start">
                        <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center mr-3 flex-shrink-0">
                            <i class="fas fa-headset text-indigo-600"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-800 mb-2">Техническая поддержка</h4>
                            <p class="text-gray-600">Встроенный модуль для обратной связи с поддержкой.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Стек технологий -->
            <div class="mt-8 p-6 bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl border border-gray-200">
                <div class="flex items-center mb-4">
                    <i class="fas fa-code text-2xl text-gray-700 mr-3"></i>
                    <h4 class="font-bold text-gray-800">Стек технологий</h4>
                </div>
                <div class="flex flex-wrap">
                    <span class="tech-stack-item"><i class="fab fa-php text-blue-600 mr-1"></i> PHP (Laravel)</span>
                    <span class="tech-stack-item"><i class="fab fa-js text-yellow-500 mr-1"></i> JavaScript (Vue.js)</span>
                    <span class="tech-stack-item"><i class="fas fa-bolt text-purple-600 mr-1"></i> Inertia.js</span>
                    <span class="tech-stack-item"><i class="fas fa-database text-green-600 mr-1"></i> MySQL/PostgreSQL</span>
                    <span class="tech-stack-item"><i class="fab fa-node text-green-600 mr-1"></i> Node.js</span>
                    <span class="tech-stack-item"><i class="fab fa-npm text-red-600 mr-1"></i> NPM</span>
                </div>
            </div>
        </section>

        <hr class="my-10 border-2 border-gray-100">

        <!-- 2. Информация для установки -->
        <section class="mb-12">
            <div class="flex items-center mb-6">
                <div class="w-12 h-12 bg-green-100 rounded-2xl flex items-center justify-center mr-4">
                    <i class="fas fa-download text-2xl text-green-600"></i>
                </div>
                <h2 class="text-3xl font-bold text-gray-800">2. Информация, необходимая для установки</h2>
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
                                Веб-браузер: Chrome 80+, Firefox, Safari, Яндекс.Браузер
                            </li>
                            <li class="flex items-center text-gray-600">
                                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                <span>Подключение к Интернет</span>
                            </li>
                            <li class="flex items-center text-gray-600">
                                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                <span>ОС: Windows, macOS, Linux, iOS, Android</span>
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
                            <span class="font-semibold">Системные требования:</span>
                        </div>
                        <ul class="space-y-1 text-purple-600 text-sm ml-6">
                            <li>• PHP >= 8.1</li>
                            <li>• Composer</li>
                            <li>• Node.js & NPM</li>
                            <li>• MySQL</li>
                        </ul>
                    </div>

                    <div class="code-block">
                        <div><span class="text-green-400"># Команды развертывания:</span></div>
                        <div><span class="text-yellow-300">git clone</span> https://github.com/miki3074/crmcrm.git</div>
                        <div>composer install</div>
                        <div>npm install && npm run build</div>
                        <div>cp .env.example .env</div>
                        <div>php artisan key:generate</div>
                        <div class="my-3 text-gray-400">----------------------------------------</div>
                        <div><span class="text-green-400"># Настройка .env файла:</span></div>
                        <div>DB_CONNECTION=mysql</div>
                        <div>DB_HOST=127.0.0.1</div>
                        <div>DB_PORT=3306</div>
                        <div>DB_DATABASE=<span class="text-yellow-300">crm_db</span>  <span class="text-gray-500"># Имя вашей БД</span></div>
                        <div>DB_USERNAME=root</div>
                        <div>DB_PASSWORD=</div>
                        <div class="my-3 text-gray-400">----------------------------------------</div>
                        <div>php artisan migrate</div>
                        <div>php artisan serve</div>
                        <div>npm run dev</div>
                    </div>
                </div>
            </div>
        </section>

        <hr class="my-10 border-2 border-gray-100">

        <!-- 3. Информация для эксплуатации -->
        <section class="mb-12">
            <div class="flex items-center mb-6">
                <div class="w-12 h-12 bg-orange-100 rounded-2xl flex items-center justify-center mr-4">
                    <i class="fas fa-play-circle text-2xl text-orange-600"></i>
                </div>
                <h2 class="text-3xl font-bold text-gray-800">3. Информация, необходимая для эксплуатации</h2>
            </div>

            <p class="text-gray-600 mb-6 text-lg">
                Для начала работы в системе необходимо пройти авторизацию, используя логин и пароль, выданные администратором.
            </p>

            <h3 class="text-xl font-semibold text-gray-800 mb-4">Основные модули системы:</h3>

            <div class="module-grid">
                <!-- Создание компании -->
                <div class="bg-white border-2 border-gray-100 p-5 rounded-xl hover:border-blue-300 transition-colors">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-3">
                        <i class="fas fa-building text-blue-600 text-xl"></i>
                    </div>
                    <h4 class="font-bold text-gray-800 mb-2">Создание Компании и Проекта</h4>
                    <p class="text-gray-600 text-sm">На главной странице нажмите «Создать». Введите название компании. Внутри компании нажмите «Новый проект» и создайте проект, назначив руководителя и длительность.</p>
                </div>

                <!-- Работа с задачами -->
                <div class="bg-white border-2 border-gray-100 p-5 rounded-xl hover:border-green-300 transition-colors">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-3">
                        <i class="fas fa-tasks text-green-600 text-xl"></i>
                    </div>
                    <h4 class="font-bold text-gray-800 mb-2">Работа с Задачами</h4>
                    <p class="text-gray-600 text-sm">В проекте нажмите «+ Новая задача». Укажите название, назначьте исполнителя, приоритет и дедлайн. Используйте чат и чек-листы для контроля.</p>
                </div>

                <!-- Пулл задач -->
                <div class="bg-white border-2 border-gray-100 p-5 rounded-xl hover:border-purple-300 transition-colors">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-3">
                        <i class="fas fa-list-ul text-purple-600 text-xl"></i>
                    </div>
                    <h4 class="font-bold text-gray-800 mb-2">Пулл задач</h4>
                    <p class="text-gray-600 text-sm">В боковом меню нажмите «Пулл задач» для просмотра всех задач с фильтрацией по статусам: в работе, просроченные, завершенные.</p>
                </div>

                <!-- Календарь -->
                <div class="bg-white border-2 border-gray-100 p-5 rounded-xl hover:border-red-300 transition-colors">
                    <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center mb-3">
                        <i class="fas fa-calendar-alt text-red-600 text-xl"></i>
                    </div>
                    <h4 class="font-bold text-gray-800 mb-2">Работа с календарем</h4>
                    <p class="text-gray-600 text-sm">На главной нажмите «Календарь». Здесь отображаются задачи. При клике на ячейку можно создать событие для себя или компании.</p>
                </div>

                <!-- Хранилище -->
                <div class="bg-white border-2 border-gray-100 p-5 rounded-xl hover:border-yellow-300 transition-colors">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center mb-3">
                        <i class="fas fa-cloud-upload-alt text-yellow-600 text-xl"></i>
                    </div>
                    <h4 class="font-bold text-gray-800 mb-2">Работа с хранилищем</h4>
                    <p class="text-gray-600 text-sm">На главной нажмите «Хранилище». Выберите компанию, назначьте менеджера для загрузки файлов, настройте права доступа.</p>
                </div>

                <!-- Поиск по ИНН -->
                <div class="bg-white border-2 border-gray-100 p-5 rounded-xl hover:border-indigo-300 transition-colors">
                    <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mb-3">
                        <i class="fas fa-search text-indigo-600 text-xl"></i>
                    </div>
                    <h4 class="font-bold text-gray-800 mb-2">Добавление клиентов</h4>
                    <p class="text-gray-600 text-sm">При добавлении контрагента введите ИНН в поле поиска. Система автоматически загрузит название, адрес и реквизиты.</p>
                </div>
            </div>

            <!-- Блок с подсказками -->

        </section>

        <!-- Подвал -->

    </div>
</div>

</body>
</html>
