<!-- resources/views/license-terms.blade.php -->

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Лицензионные условия - Планшет CRM</title>

    <!-- Подключаем Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Подключаем Font Awesome для иконок -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <!-- Подключаем Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f6f9fc 0%, #e6f0f5 100%);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        /* Декоративные элементы фона */
        .bg-pattern {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image:
                radial-gradient(circle at 10% 20%, rgba(52, 152, 219, 0.03) 0%, transparent 20%),
                radial-gradient(circle at 90% 50%, rgba(46, 204, 113, 0.03) 0%, transparent 25%),
                radial-gradient(circle at 30% 80%, rgba(155, 89, 182, 0.03) 0%, transparent 30%);
            pointer-events: none;
            z-index: 0;
        }

        /* Анимации */
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }

        @keyframes glow {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.2); }
            50% { box-shadow: 0 0 20px 10px rgba(16, 185, 129, 0.1); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.2); }
        }

        .float-animation {
            animation: float 6s ease-in-out infinite;
        }

        .glow-animation {
            animation: glow 3s ease-in-out infinite;
        }

        .license-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
            transition: all 0.3s ease;
        }

        .license-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 50px -20px rgba(0, 0, 0, 0.2);
        }

        .feature-icon {
            transition: all 0.3s ease;
        }

        .feature-icon:hover {
            transform: scale(1.1) rotate(5deg);
        }

        .gradient-border {
            position: relative;
            background: linear-gradient(white, white) padding-box,
            linear-gradient(135deg, #10b981, #3b82f6) border-box;
            border: 2px solid transparent;
            border-radius: 1rem;
        }

        .badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.2);
        }
    </style>
</head>
<body class="antialiased">

<!-- Фоновый паттерн -->
<div class="bg-pattern"></div>

<?php
$appName = "Планшет CRM-система";
$companyName = "ООО «Планшет Технологии»"; // Добавлено название компании
$currentYear = date('Y');
?>

<div class="relative z-10 min-h-screen flex flex-col justify-center py-12 sm:px-6 lg:px-8">

    <!-- Декоративный элемент сверху -->
    <div class="absolute top-0 left-1/2 transform -translate-x-1/2 w-full max-w-7xl">
        <div class="w-64 h-64 bg-green-500 opacity-5 rounded-full blur-3xl float-animation"></div>
    </div>

    <div class="sm:mx-auto sm:w-full sm:max-w-3xl relative">
        <!-- Иконка замка или документа -->
        <div class="flex justify-center">
            <div class="inline-flex p-3 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl shadow-lg glow-animation">
                <i class="fas fa-file-contract text-4xl text-white"></i>
            </div>
        </div>

        <!-- Заголовок -->
        <h2 class="mt-6 text-center text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-gray-900 to-gray-700">
            Лицензионные условия
        </h2>
        <p class="mt-2 text-center text-lg text-gray-600">
            <i class="fas fa-check-circle text-green-500 mr-2"></i>
            Официальный документ для пользователей и экспертных организаций
        </p>


    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-3xl relative">
        <!-- Основная карточка -->
        <div class="license-card bg-white py-8 px-6 shadow-2xl sm:rounded-2xl sm:px-10 border border-gray-100 relative overflow-hidden">

            <!-- Декоративная линия сверху -->
            <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-green-500 via-green-400 to-blue-500"></div>

            <!-- Бейдж "Бесплатно" -->


            <!-- Блок о бесплатности -->
            <div class="mb-10 relative">
                <h3 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                    <div class="feature-icon w-12 h-12 bg-green-100 rounded-2xl flex items-center justify-center mr-4">
                        <i class="fas fa-coins text-2xl text-green-600"></i>
                    </div>
                    <span class="bg-gradient-to-r from-green-600 to-green-500 bg-clip-text text-transparent">
                        Стоимость использования
                    </span>
                </h3>

                <div class="gradient-border p-6 bg-gradient-to-br from-green-50 to-white">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center shadow-lg">
                                <i class="fas fa-check text-2xl text-white"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-xl font-bold text-green-800 mb-2">
                                Программное обеспечение «{{ $appName }}» предоставляется на безвозмездной основе
                            </p>
                            <p class="text-green-700">
                                Полностью бесплатное использование без ограничений по функционалу
                            </p>
                        </div>
                    </div>
                </div>

                <p class="mt-6 text-gray-700 leading-relaxed text-lg">
                    Правообладатель  предоставляет пользователям право использования программного обеспечения на условиях <span class="bg-green-100 text-green-800 px-2 py-1 rounded font-semibold">простой (неисключительной) безвозмездной лицензии</span>.
                </p>

                <!-- Сетка с преимуществами -->
                <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-100">
                        <div class="text-2xl font-bold text-green-600">0 ₽</div>
                        <div class="text-sm text-gray-600">Стоимость лицензии</div>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-100">
                        <div class="text-2xl font-bold text-green-600">0 ₽</div>
                        <div class="text-sm text-gray-600">Абонентская плата</div>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-100">
                        <div class="text-2xl font-bold text-green-600">0</div>
                        <div class="text-sm text-gray-600">Скрытых платежей</div>
                    </div>
                </div>
            </div>

            <!-- Разделитель с иконкой -->
            <div class="relative my-10">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-200"></div>
                </div>
                <div class="relative flex justify-center">
                    <span class="bg-white px-4 text-gray-400">
                        <i class="fas fa-gem text-green-500"></i>
                    </span>
                </div>
            </div>

            <!-- Условия использования -->
            <div class="mb-8">
                <h3 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                    <div class="feature-icon w-12 h-12 bg-blue-100 rounded-2xl flex items-center justify-center mr-4">
                        <i class="fas fa-key text-2xl text-blue-600"></i>
                    </div>
                    <span class="bg-gradient-to-r from-blue-600 to-blue-500 bg-clip-text text-transparent">
                        Порядок получения доступа
                    </span>
                </h3>

                <div class="bg-gradient-to-br from-blue-50 to-white p-6 rounded-xl border border-blue-100">
                    <p class="text-gray-700 mb-4 text-lg">
                        <i class="fas fa-check-circle text-blue-500 mr-2"></i>
                        Для начала работы с системой не требуется заключение договора купли-продажи или оплата счетов.
                    </p>

                    <div class="flex items-center space-x-4 bg-white p-4 rounded-lg border border-blue-100">
                        <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold text-xl flex-shrink-0">
                            1
                        </div>
                        <div>
                            <span class="font-bold text-gray-900">Свободная регистрация:</span>
                            <p class="text-gray-600">Пользователь самостоятельно регистрируется в системе и получает полный доступ ко всем функциям</p>
                        </div>
                    </div>

                    <!-- Дополнительный шаг для наглядности -->
                    <div class="mt-3 flex items-center space-x-4 bg-white p-4 rounded-lg border border-blue-100 opacity-75">
                        <div class="w-12 h-12 bg-gray-300 rounded-full flex items-center justify-center text-gray-600 font-bold text-xl flex-shrink-0">
                            2
                        </div>
                        <div>
                            <span class="font-bold text-gray-900">Начало работы:</span>
                            <p class="text-gray-600">После регистрации вы сразу можете создавать проекты и задачи</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Отказ от ответственности -->
            <div class="mt-8 relative">
                <div class="bg-gradient-to-br from-gray-50 to-white p-6 rounded-xl border border-gray-200 shadow-inner">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-gray-200 rounded-lg flex items-center justify-center">
                                <i class="fas fa-shield-alt text-gray-500"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <h4 class="font-bold text-gray-800 mb-2 text-lg">Ограничение ответственности (AS IS)</h4>
                            <p class="text-gray-600 leading-relaxed">
                                Программное обеспечение предоставляется по принципу «как есть» (as is). Правообладатель не гарантирует, что функционал ПО будет отвечать всем ожиданиям пользователя, и не несет ответственности за возможные убытки, возникшие в результате использования данного ПО. Техническая поддержка предоставляется в рамках регламента компании.
                            </p>
                        </div>
                    </div>
                </div>
            </div>




        </div>


    </div>

    <!-- Копирайт -->

</div>

</body>
</html>
