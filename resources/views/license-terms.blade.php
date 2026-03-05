<!-- resources/views/license.blade.php -->

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Информация о стоимости и лицензировании | Планшет CRM-система</title>

    <!-- Подключаем Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Подключаем Font Awesome для иконок -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            min-height: 100vh;
            padding: 2rem 1rem;
        }

        .license-container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 2rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
        }

        .license-header {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            padding: 3rem 2rem;
            color: white;
        }

        .free-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            padding: 0.5rem 1.5rem;
            border-radius: 9999px;
            font-weight: 600;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .license-card {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .license-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .check-icon {
            color: #10b981;
            margin-right: 0.5rem;
        }

        .legal-notice {
            background: #f3f4f6;
            border-left: 4px solid #059669;
            padding: 1rem;
            border-radius: 0.5rem;
            font-size: 0.9rem;
        }

        .license-type {
            display: inline-block;
            padding: 0.25rem 1rem;
            background: #d1fae5;
            color: #065f46;
            border-radius: 9999px;
            font-weight: 600;
            font-size: 0.875rem;
        }
    </style>
</head>

<body>

<?php
$appName = "Планшет CRM-система";
$companyName = "НПО Энерготех";
$currentYear = date('Y');
?>

<div class="license-container">
    <!-- Шапка -->
    <div class="license-header relative overflow-hidden">
        <div class="absolute top-0 right-0 w-64 h-64 bg-white opacity-5 rounded-full -mr-32 -mt-32"></div>
        <div class="absolute bottom-0 left-0 w-48 h-48 bg-white opacity-5 rounded-full -ml-24 -mb-24"></div>

        <div class="relative z-10">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-3xl font-bold">
                    {{ $appName }}
                </h1>
                <span class="free-badge">
                    <i class="fas fa-check-circle mr-2"></i>
                    ПО распространяется бесплатно
                </span>
            </div>

            <p class="text-xl opacity-90 max-w-2xl">
                Информация о стоимости и условиях использования программного обеспечения
            </p>

            <div class="mt-6 flex items-center text-sm">
                <i class="fas fa-calendar-alt mr-2"></i>
                Дата актуализации: {{ date('d.m.Y') }}
            </div>
        </div>
    </div>

    <!-- Основной контент -->
    <div class="p-8">
        <!-- Крупный баннер о бесплатности -->
        <div class="bg-gradient-to-r from-emerald-50 to-teal-50 rounded-2xl p-8 mb-8 text-center border-2 border-emerald-200">
            <i class="fas fa-gift text-6xl text-emerald-600 mb-4"></i>
            <h2 class="text-3xl font-bold text-emerald-800 mb-2">Программное обеспечение предоставляется БЕСПЛАТНО</h2>
            <p class="text-lg text-emerald-700">На условиях открытой лицензии — без взимания платы с пользователей</p>

            <div class="mt-6 flex justify-center space-x-4">
                <span class="license-type">
                    <i class="fas fa-check-circle mr-1"></i>
                    Бессрочно
                </span>
                <span class="license-type">
                    <i class="fas fa-check-circle mr-1"></i>
                    Без ограничений
                </span>
                <span class="license-type">
                    <i class="fas fa-check-circle mr-1"></i>
                    Для любых целей
                </span>
            </div>
        </div>

        <!-- Основная информация - то, что требует Минцифры -->
        <div class="grid md:grid-cols-2 gap-6 mb-8">
            <!-- Пункт 4 "и" - Стоимость -->
            <div class="license-card">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-ruble-sign text-2xl text-emerald-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800">Стоимость ПО</h3>
                </div>

                <div class="space-y-4">
                    <div class="flex items-start">
                        <i class="fas fa-check-circle check-icon mt-1"></i>
                        <div>
                            <span class="font-semibold text-gray-800">Лицензия на использование:</span>
                            <span class="ml-2 text-emerald-600 font-bold text-xl">0 ₽ (ноль рублей)</span>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <i class="fas fa-check-circle check-icon mt-1"></i>
                        <div>
                            <span class="font-semibold text-gray-800">Техническая поддержка:</span>
                            <span class="ml-2 text-emerald-600 font-bold">Бесплатно</span>
                            <p class="text-sm text-gray-500 mt-1">Включена в объеме, указанном в документации</p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <i class="fas fa-check-circle check-icon mt-1"></i>
                        <div>
                            <span class="font-semibold text-gray-800">Обновления:</span>
                            <span class="ml-2 text-emerald-600 font-bold">Бесплатно</span>
                            <p class="text-sm text-gray-500 mt-1">Все версии и обновления предоставляются без взимания платы</p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <i class="fas fa-check-circle check-icon mt-1"></i>
                        <div>
                            <span class="font-semibold text-gray-800">Количество пользователей:</span>
                            <span class="ml-2 text-emerald-600 font-bold">Не ограничено</span>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <i class="fas fa-check-circle check-icon mt-1"></i>
                        <div>
                            <span class="font-semibold text-gray-800">Срок действия лицензии:</span>
                            <span class="ml-2 text-emerald-600 font-bold">Бессрочно</span>
                        </div>
                    </div>
                </div>

                <!-- Итоговая строка -->
                <div class="mt-6 p-4 bg-gray-50 rounded-xl">
                    <p class="text-center text-gray-700">
                        <i class="fas fa-info-circle text-emerald-600 mr-1"></i>
                        <span class="font-medium">ИТОГО: плата за использование ПО не взимается</span>
                    </p>
                </div>
            </div>

            <!-- Пункт 4 "к" - Открытая лицензия / безвозмездный договор -->
            <div class="license-card">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-file-contract text-2xl text-blue-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800">Условия использования</h3>
                </div>

                <div class="space-y-4">
                    <div class="flex items-start">
                        <i class="fas fa-check-circle check-icon mt-1"></i>
                        <div>
                            <span class="font-semibold text-gray-800">Тип лицензии:</span>
                            <span class="ml-2 text-blue-600 font-bold">Открытая лицензия</span>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <i class="fas fa-check-circle check-icon mt-1"></i>
                        <div>
                            <span class="font-semibold text-gray-800">Основание:</span>
                            <span class="ml-2 text-gray-700">Статья 1286.1 ГК РФ (открытая лицензия)</span>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <i class="fas fa-check-circle check-icon mt-1"></i>
                        <div>
                            <span class="font-semibold text-gray-800">Права пользователя:</span>
                            <ul class="list-disc pl-5 mt-2 text-gray-600 space-y-1">
                                <li>Устанавливать и использовать ПО без ограничений</li>
                                <li>Использовать для любых целей (личных, коммерческих)</li>
                                <li>Распространять копии ПО (с сохранением условий лицензии)</li>
                                <li>Получать обновления и новые версии</li>
                            </ul>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <i class="fas fa-check-circle check-icon mt-1"></i>
                        <div>
                            <span class="font-semibold text-gray-800">Ограничения:</span>
                            <ul class="list-disc pl-5 mt-2 text-gray-600 space-y-1">
                                <li>Запрещено изменять код программы (для сохранения целостности)</li>
                                <li>Запрещено взимать плату за перепродажу</li>
                                <li>Необходимо сохранять уведомление об авторских правах</li>
                            </ul>
                        </div>
                    </div>
                </div>

               
            </div>
        </div>

        <!-- Подтверждение безвозмездности (важно!) -->
        <div class="legal-notice mb-8">
            <div class="flex items-start">
                <i class="fas fa-balance-scale text-emerald-600 mt-1 mr-3 text-xl"></i>
                <div>
                    <h4 class="font-bold text-gray-800 mb-1">Правовое основание</h4>
                    <p class="text-gray-600">
                        Настоящим подтверждается, что программное обеспечение «{{ $appName }}» распространяется
                        <span class="font-bold text-emerald-600">на безвозмездной основе</span> в соответствии с условиями
                        открытой лицензии. Плата за использование, установку, обновление и техническую поддержку
                        <span class="font-bold underline">не взимается</span>.
                    </p>
                    <p class="text-gray-500 text-sm mt-2">
                        * Информация размещена во исполнение требований пунктов 4 «и» и 4 «к» Постановления Правительства РФ №1236.
                    </p>
                </div>
            </div>
        </div>

        <!-- Сравнение вариантов -->
        <div class="bg-gray-50 rounded-xl p-6 mb-8">
            <h3 class="font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-list-check text-emerald-600 mr-2"></i>
                Сводная информация о стоимости
            </h3>

            <table class="w-full">
                <thead>
                <tr class="border-b border-gray-200">
                    <th class="text-left py-2">Вид предоставления</th>
                    <th class="text-left py-2">Стоимость</th>
                    <th class="text-left py-2">Примечание</th>
                </tr>
                </thead>
                <tbody>
                <tr class="border-b border-gray-200">
                    <td class="py-3">Лицензия на ПО</td>
                    <td class="py-3 font-bold text-emerald-600">Бесплатно (0 ₽)</td>
                    <td class="py-3 text-gray-500 text-sm">Бессрочная, на неограниченное число пользователей</td>
                </tr>
                <tr class="border-b border-gray-200">
                    <td class="py-3">Техническая поддержка</td>
                    <td class="py-3 font-bold text-emerald-600">Бесплатно (0 ₽)</td>
                    <td class="py-3 text-gray-500 text-sm">Через встроенный модуль, email, телефон</td>
                </tr>
                <tr class="border-b border-gray-200">
                    <td class="py-3">Обновления (апдейты)</td>
                    <td class="py-3 font-bold text-emerald-600">Бесплатно (0 ₽)</td>
                    <td class="py-3 text-gray-500 text-sm">Все версии, включая мажорные</td>
                </tr>
                <tr>
                    <td class="py-3">Обучение</td>
                    <td class="py-3 font-bold text-emerald-600">Бесплатно (0 ₽)</td>
                    <td class="py-3 text-gray-500 text-sm">Документация, видеоуроки, вебинары</td>
                </tr>
                </tbody>
            </table>
        </div>

        <!-- Часто задаваемые вопросы -->
        <div class="bg-white rounded-xl p-6 border border-gray-200">
            <h3 class="font-bold text-gray-800 mb-4">Часто задаваемые вопросы о бесплатном ПО</h3>

            <div class="space-y-4">
                <div class="border-b border-gray-100 pb-4">
                    <p class="font-medium text-gray-800">❓ Действительно ли ПО полностью бесплатное?</p>
                    <p class="text-gray-600 mt-1">Да, 100% функций доступны бесплатно. Никаких платных подписок, скрытых платежей или триальных версий.</p>
                </div>

                <div class="border-b border-gray-100 pb-4">
                    <p class="font-medium text-gray-800">❓ Есть ли ограничение по времени?</p>
                    <p class="text-gray-600 mt-1">Нет. Лицензия бессрочная. Вы можете пользоваться программой неограниченное время.</p>
                </div>

                <div class="border-b border-gray-100 pb-4">
                    <p class="font-medium text-gray-800">❓ Можно ли использовать в коммерческих целях?</p>
                    <p class="text-gray-600 mt-1">Да, открытая лицензия разрешает коммерческое использование без взимания платы.</p>
                </div>

                <div>
                    <p class="font-medium text-gray-800">❓ Что будет, если я захочу поддержать разработчиков?</p>
                    <p class="text-gray-600 mt-1">Мы рады любой поддержке, но она остается на ваше усмотрение и не является обязательным условием использования ПО.</p>
                </div>
            </div>
        </div>


    </div>
</div>

</body>
</html>
