<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';

// Состояние активной вкладки
const activeTab = ref('functional');

const tabs = [
    { id: 'functional', name: 'Функциональные характеристики' },
    { id: 'installation', name: 'Установка и эксплуатация' },
    { id: 'manual', name: 'Руководство пользователя' },
];

// Данные о вашей CRM (на основе вашего описания)
const appName = "Планшет CRM-система"; // ЗАМЕНИТЕ НА ВАШЕ НАЗВАНИЕ
</script>

<template>
    <Head title="Документация для реестра ПО" />

    <div class="min-h-screen bg-gray-50 py-10 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto bg-white shadow-lg rounded-lg overflow-hidden">

            <!-- Заголовок -->
            <div class="bg-blue-600 px-6 py-4">
                <h1 class="text-2xl font-bold text-white">Документация ПО «{{ appName }}»</h1>

            </div>

            <!-- Навигация (Табы) -->
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex" aria-label="Tabs">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        @click="activeTab = tab.id"
                        :class="[
                            activeTab === tab.id
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            'w-1/3 py-4 px-1 text-center border-b-2 font-medium text-sm sm:text-base transition duration-150'
                        ]"
                    >
                        {{ tab.name }}
                    </button>
                </nav>
            </div>

            <!-- Контент -->
            <div class="p-6 sm:p-8 text-gray-800 leading-relaxed">

                <!-- РАЗДЕЛ 1: Функциональные характеристики -->
                <div v-show="activeTab === 'functional'">
                    <h2 class="text-xl font-bold mb-4 text-gray-900">1. Описание функциональных характеристик</h2>

                    <p class="mb-4">
                        Программное обеспечение «{{ appName }}» представляет собой CRM-систему для комплексного управления компаниями, проектами и задачами. Система предназначена для автоматизации бизнес-процессов, контроля исполнительской дисциплины и организации совместной работы сотрудников.
                    </p>

                    <h3 class="font-bold text-lg mt-6 mb-2">Основные функциональные возможности:</h3>
                    <ul class="list-disc pl-5 space-y-2">
                        <li>
                            <strong>Управление структурой компании:</strong> Создание профилей компаний, добавление сотрудников, назначение ролей («Сотрудник», «Менеджер»), визуализация иерархии и связей через интерактивную схему.
                        </li>
                        <li>
                            <strong>Управление проектами и задачами:</strong>
                            <ul class="list-circle pl-5 mt-1 text-gray-600 text-sm">
                                <li>Создание проектов и вложенных задач.</li>
                                <li>Назначение ролей в проекте: Руководитель, Исполнитель, Наблюдатель.</li>
                                <li>Пул задач: фильтрация по статусам (В работе, Завершенные, Просроченные).</li>
                                <li>Чек-листы, подзадачи и встроенный чат внутри каждой задачи.</li>
                            </ul>
                        </li>
                        <li>
                            <strong>Календарь и планирование:</strong> Личный и корпоративный календарь, создание событий, отображение сроков задач (timeline).
                        </li>
                        <li>
                            <strong>База клиентов (CRM):</strong> Добавление контрагентов в проекты, автоматический поиск и автозаполнение реквизитов по ИНН.
                        </li>
                        <li>
                            <strong>Файловое хранилище:</strong> Загрузка и хранение документов компании, прикрепление файлов к задачам и проектам.
                        </li>
                        <li>
                            <strong>Техническая поддержка:</strong> Встроенный модуль для обратной связи с поддержкой.
                        </li>
                    </ul>

                    <div class="mt-8 p-4 bg-gray-100 rounded text-sm">
                        <p><strong>Стек технологий:</strong> PHP (Laravel), JavaScript (Vue.js), Inertia.js, MySQL/PostgreSQL.</p>
                    </div>
                </div>

                <!-- РАЗДЕЛ 2: Установка и эксплуатация -->
                <div v-show="activeTab === 'installation'">
                    <h2 class="text-xl font-bold mb-4 text-gray-900">2. Информация, необходимая для установки</h2>

                    <div class="mb-6">
                        <h3 class="font-bold text-md mb-2">Вариант А: Облачное использование (SaaS)</h3>
                        <p class="mb-2">
                            Установка программного обеспечения на оборудование пользователя не требуется. Доступ осуществляется через сеть Интернет.
                        </p>
                        <p><strong>Требования к рабочему месту пользователя:</strong></p>
                        <ul class="list-disc pl-5 text-sm space-y-1 mt-1">
                            <li>Современный веб-браузер: Google Chrome (версия 80+), Mozilla Firefox, Safari, Яндекс.Браузер.</li>
                            <li>Подключение к сети Интернет.</li>
                            <li>Операционная система: Windows, macOS, Linux, iOS или Android.</li>
                        </ul>
                    </div>

                    <div class="mb-6 border-t pt-4">
                        <h3 class="font-bold text-md mb-2">Вариант Б: Установка </h3>

                        <code class="block bg-gray-800 text-green-400 p-3 rounded text-sm overflow-x-auto">
                            # Системные требования:<br>
                            - PHP >= 8.1<br>
                            - Composer<br>
                            - Node.js & NPM<br>
                            - MySQL <br><br>
                            # Команды развертывания:<br>
                            git clone https://github.com/miki3074/crmcrm.git<br>
                            composer install<br>
                            npm install && npm run build<br>
                            cp .env.example .env <br>
                            php artisan key:generate
                            <hr/>
                            Фаил env <br/><br/>
                            <span>DB_CONNECTION=mysql <br>
                            DB_HOST=127.0.0.1 <br>
                            DB_PORT=3306 <br>
                            DB_DATABASE=crm_db      <-- Имя базы, которую вы создали <br>
                            DB_USERNAME=root          <br>
                            DB_PASSWORD=              <br>
                            </span>
                            <hr/><br/>
                            php artisan migrate <br/>
                            php artisan serve <br/>
                            npm run dev
                        </code>
                    </div>
                </div>

                <!-- РАЗДЕЛ 3: Руководство пользователя -->
                <div v-show="activeTab === 'manual'">
                    <h2 class="text-xl font-bold mb-4 text-gray-900">3. Информация для эксплуатации (Руководство)</h2>

                    <p class="mb-4">
                        Для начала работы в системе необходимо пройти авторизацию, используя логин и пароль, выданные администратором.
                    </p>

                    <h3 class="font-bold text-lg mt-4 mb-2">Основыне модули:</h3>

                    <div class="space-y-4">
                        <div class="bg-white border border-gray-200 p-4 rounded shadow-sm">
                            <h4 class="font-bold text-blue-600">Создание Компании и Проекта</h4>
                            <p class="text-sm text-gray-700 mt-1">
                                На главной странице. Нажмите кнопку «Создать». Введите название компании.
                                Внутри компании нажмите на кнопку «Новый проект» и создайте новый проект, назначив руководителя и длительность проекта.
                            </p>
                        </div>

                        <div class="bg-white border border-gray-200 p-4 rounded shadow-sm">
                            <h4 class="font-bold text-blue-600">Работа с Задачами</h4>
                            <p class="text-sm text-gray-700 mt-1">
                                В проекте нажмите «+ Новая задача». Укажите название задачи и назначте исполнителя и ответсвенного так же выберете приоритет и дату дедлайна.
                                Используйте вкладку «Чат» внутри задачи для обсуждения.
                                Для контроля выполнения используйте «Чек-листы» и статусы задачи.
                            </p>
                        </div>

                        <div class="bg-white border border-gray-200 p-4 rounded shadow-sm">
                            <h4 class="font-bold text-blue-600">Работа с календарем</h4>
                            <p class="text-sm text-gray-700 mt-1">
                                На главной задаче нажмите на кнопку «Календарь».
                                Внутри страницы будет виден список ваших задач в календаре.
                                При нажатии на клетку дня в календаре, можно создать события для себя или для компании
                            </p>
                        </div>

                        <div class="bg-white border border-gray-200 p-4 rounded shadow-sm">
                            <h4 class="font-bold text-blue-600">Работа с хранилищем</h4>
                            <p class="text-sm text-gray-700 mt-1">
                                На главной задаче нажмите на кнопку «Хранилище».
                                Внутри странице будет виден список ваших компаний.
                                При переходе в компанию, можно будет назачить менеджера, которые могут добовлять файлы в хранилище
                           Добовления файлов можно сделать доступ кто может видеть и скачивать файлы из выбранной компании
                            </p>
                        </div>

                        <div class="bg-white border border-gray-200 p-4 rounded shadow-sm">
                            <h4 class="font-bold text-blue-600">Добавление клиентов (Поиск по ИНН)</h4>
                            <p class="text-sm text-gray-700 mt-1">
                                При добавлении контрагента введите его ИНН в поле поиска. Система автоматически загрузит название, адрес.
                            </p>
                        </div>
                    </div>

                    <!-- ВАЖНО: Ссылки на полные PDF -->
                    <div class="mt-8 bg-yellow-50 border-l-4 border-yellow-400 p-4">
                        <h4 class="font-bold text-yellow-800">Полная документация</h4>
                        <p class="text-sm text-yellow-700 mt-2">
                            Скачать подробные инструкции в формате PDF:
                        </p>

                    </div>
                </div>

            </div>

            <!-- Футер -->
            <div class="bg-gray-100 px-6 py-4 text-center">
                <p class="text-sm text-gray-500">© {{ new Date().getFullYear() }} {{ appName }}. Все права защищены.</p>
                <p class="text-xs text-gray-400 mt-1">Разработано на Laravel + Vue </p>
            </div>

        </div>
    </div>
</template>

<style scoped>
.list-circle {
    list-style-type: circle;
}
</style>
