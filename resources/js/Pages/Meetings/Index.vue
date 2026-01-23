<script setup>
import { Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
defineProps({
    meetings: Array,
});

// Функция для форматирования даты (можно вынести в утилиты)
const formatDate = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleString('ru-RU', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
};

const statusLabels = {
    'draft': 'Черновик',
    'scheduled': 'Назначено',
    'completed': 'Завершено',
    'on_approval': 'На согласовании',
    'signed': 'Подписано',
};
</script>

<template>
    <AuthenticatedLayout>
    <div class="p-6 bg-gray-100 min-h-screen">
        <div class="max-w-7xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Мои совещания</h1>

                <!-- Кнопка перехода к созданию -->
                <Link
                    :href="route('meetings.create')"
                    class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition"
                >
                    + Создать совещание
                </Link>
            </div>

            <div class="bg-white rounded shadow overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="p-4 font-medium text-gray-600">Компания</th>
                        <th class="p-4 font-medium text-gray-600">Тема</th>
                        <th class="p-4 font-medium text-gray-600">Дата начала</th>
                        <th class="p-4 font-medium text-gray-600">Статус</th>
                        <th class="p-4 font-medium text-gray-600">Действие</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-if="meetings.length === 0">
                        <td colspan="5" class="p-4 text-center text-gray-500">
                            У вас пока нет запланированных совещаний.
                        </td>
                    </tr>

                    <tr v-for="meeting in meetings" :key="meeting.id" class="border-b hover:bg-gray-50">
                        <td class="p-4">{{ meeting.company?.name }}</td>
                        <td class="p-4 font-semibold">{{ meeting.title }}</td>
                        <td class="p-4">{{ formatDate(meeting.start_time) }}</td>
                        <td class="p-4">
                                <span class="px-2 py-1 text-sm rounded bg-gray-200">
                                    {{ statusLabels[meeting.status] || meeting.status }}
                                </span>
                        </td>
                        <td class="p-4">
                            <!-- Сюда потом добавишь ссылку на просмотр/редактирование -->
                            <Link
                                :href="route('meetings.show', meeting.id)"
                                class="text-blue-600 hover:text-blue-900 font-medium hover:underline"
                            >
                                Просмотр
                            </Link>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </AuthenticatedLayout>
</template>
