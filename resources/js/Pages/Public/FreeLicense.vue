<script setup>
import { Head } from '@inertiajs/vue3';

const appName = "Планшет CRM-система"; // Замените на название вашего ПО
const companyName = ""; // Замените на название вашей компании-правообладателя
</script>

<template>
    <Head title="Лицензионные условия" />

    <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">

        <div class="sm:mx-auto sm:w-full sm:max-w-3xl">
            <!-- Логотип или Заголовок -->
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Лицензионные условия использования ПО
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Информация для пользователей и экспертных организаций
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-3xl">
            <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10 border-t-4 border-green-500">

                <!-- Блок о бесплатности (Главное для реестра) -->
                <div class="mb-8">
                    <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                        <svg class="w-6 h-6 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Стоимость использования
                    </h3>
                    <div class="bg-green-50 rounded-md p-4 border border-green-200">
                        <p class="text-lg text-green-900 font-semibold">
                            Программное обеспечение «{{ appName }}» предоставляется на безвозмездной основе (бесплатно).
                        </p>
                    </div>
                    <p class="mt-4 text-gray-700 leading-relaxed">
                        Правообладатель {{ companyName }} предоставляет пользователям право использования программного обеспечения на условиях <strong>простой (неисключительной) безвозмездной лицензии</strong>.
                    </p>
                    <ul class="mt-3 list-disc pl-5 text-gray-600 space-y-1">
                        <li>Стоимость лицензии: <strong>0 рублей</strong>.</li>
                        <li>Абонентская плата: <strong>отсутствует</strong>.</li>
                        <li>Скрытые платежи: <strong>отсутствуют</strong>.</li>
                    </ul>
                </div>

                <hr class="border-gray-200 my-6">

                <!-- Условия использования -->
                <div class="mb-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-3">Порядок получения доступа</h3>
                    <p class="text-gray-700 mb-2">
                        Для начала работы с системой не требуется заключение договора купли-продажи или оплата счетов. Доступ предоставляется:
                    </p>
                    <ul class="list-decimal pl-5 text-gray-600 space-y-2">
                        <li>
                            <strong>Свободная регистрация:</strong>  Пользователь самостоятельно регистрируется в системе и получает доступ.
                        </li>

                    </ul>
                </div>

                <!-- Отказ от ответственности (Юридическая база для бесплатного ПО) -->
                <div class="bg-gray-50 p-4 rounded text-sm text-gray-500">
                    <h4 class="font-bold text-gray-700 mb-1">Ограничение ответственности (AS IS)</h4>
                    <p>
                        Программное обеспечение предоставляется по принципу «как есть» (as is). Правообладатель не гарантирует, что функционал ПО будет отвечать всем ожиданиям пользователя, и не несет ответственности за возможные убытки, возникшие в результате использования данного ПО. Техническая поддержка предоставляется в рамках регламента компании.
                    </p>
                </div>

                <!-- Контакты -->


            </div>
        </div>
    </div>
</template>
