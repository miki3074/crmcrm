<script setup>
import { Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

defineProps({
    meetings: Array,
});

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

// Хелперы для безопасного получения названий
const getProjectName = (meeting) => {
    // Проект берем из задачи ИЛИ из задачи подзадачи
    return meeting.task?.project?.name
        || meeting.subtask?.task?.project?.name
        || '-';
};

const getTaskTitle = (meeting) => {
    // Название задачи
    return meeting.task?.title
        || meeting.subtask?.task?.title
        || '-';
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="p-6 bg-gray-100 min-h-screen">
            <div class="max-w-7xl mx-auto">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold text-gray-800">Мои совещания</h1>
                    <Link :href="route('meetings.create')" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
                        + Создать совещание
                    </Link>
                </div>

                <div class="bg-white rounded shadow overflow-hidden overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="p-4 font-medium text-gray-600">Компания</th>
                            <th class="p-4 font-medium text-gray-600">Проект</th>
                            <th class="p-4 font-medium text-gray-600">Задача</th>
                            <th class="p-4 font-medium text-gray-600">Подзадача</th> <!-- Добавил колонку -->
                            <th class="p-4 font-medium text-gray-600">Тема</th>
                            <th class="p-4 font-medium text-gray-600">Дата начала</th>
                            <th class="p-4 font-medium text-gray-600">Статус</th>
                            <th class="p-4 font-medium text-gray-600">Действие</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-if="meetings.length === 0">
                            <td colspan="8" class="p-4 text-center text-gray-500">
                                У вас пока нет запланированных совещаний.
                            </td>
                        </tr>

                        <tr v-for="meeting in meetings" :key="meeting.id" class="border-b hover:bg-gray-50">
                            <!-- Компания -->
                            <td class="p-4 text-sm">{{ meeting.company?.name }}</td>

                            <!-- Проект (вычисляемый) -->
                            <td class="p-4 text-sm text-gray-600">
                                {{ getProjectName(meeting) }}
                            </td>

                            <!-- Задача (ссылка, если есть) -->
                            <td class="p-4 text-sm">
                            <span v-if="getTaskTitle(meeting) !== '-'">
                                {{ getTaskTitle(meeting) }}
                            </span>
                                <span v-else class="text-gray-400">-</span>
                            </td>

                            <!-- Подзадача -->
                            <td class="p-4 text-sm">
                             <span v-if="meeting.subtask" class="text-blue-600">
                                {{ meeting.subtask.title }}
                            </span>
                                <span v-else class="text-gray-400">-</span>
                            </td>

                            <!-- Тема совещания -->
                            <td class="p-4 font-semibold text-gray-800">{{ meeting.title }}</td>

                            <td class="p-4 text-sm whitespace-nowrap">{{ formatDate(meeting.start_time) }}</td>

                            <td class="p-4">
                            <span class="px-2 py-1 text-xs font-bold rounded"
                                  :class="{
                                      'bg-gray-200 text-gray-700': meeting.status === 'draft',
                                      'bg-yellow-100 text-yellow-800': meeting.status === 'scheduled',
                                      'bg-green-100 text-green-800': meeting.status === 'completed'
                                  }">
                                {{ statusLabels[meeting.status] || meeting.status }}
                            </span>
                            </td>

                            <td class="p-4">
                                <Link :href="route('meetings.show', meeting.id)" class="text-blue-600 hover:text-blue-900 font-medium hover:underline text-sm">
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
