<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3'; // Убрали Link, так как он больше не нужен

const props = defineProps({
    tasks: Array,    // Изменили Object на Array (хотя Object тоже сработал бы)
    subtasks: Array
});

const activeTab = ref('tasks'); // 'tasks' или 'subtasks'

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('ru-RU', {
        day: '2-digit', month: '2-digit', year: 'numeric'
    });
};
</script>

<template>
    <Head title="Завершенные задачи" />

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                <h1 class="text-2xl font-bold mb-6">Архив задач</h1>

                <!-- Переключатель табов -->
                <div class="flex border-b border-gray-200 mb-6">
                    <button
                        @click="activeTab = 'tasks'"
                        :class="{'border-b-2 border-blue-500 text-blue-600': activeTab === 'tasks'}"
                        class="px-4 py-2 font-medium text-gray-600 hover:text-blue-500 focus:outline-none"
                    >
                        <!-- Заменили .total на .length -->
                        Задачи ({{ tasks.length }})
                    </button>
                    <button
                        @click="activeTab = 'subtasks'"
                        :class="{'border-b-2 border-blue-500 text-blue-600': activeTab === 'subtasks'}"
                        class="px-4 py-2 font-medium text-gray-600 hover:text-blue-500 focus:outline-none ml-4"
                    >
                        <!-- Заменили .total на .length -->
                        Подзадачи ({{ subtasks.length }})
                    </button>
                </div>

                <!-- Таблица Задач -->
                <div v-if="activeTab === 'tasks'">
                    <!-- Заменили .data.length на .length -->
                    <div v-if="tasks.length === 0" class="text-gray-500 text-center py-4">
                        Нет завершенных задач.
                    </div>
                    <table v-else class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Название</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Проект</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дата завершения</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ответственный</th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                        <!-- Убрали .data из цикла -->
                        <tr v-for="task in tasks" :key="task.id">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ task.name }}</div>
                                <div class="text-xs text-gray-500">Приоритет: {{ task.priority }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ task.project?.name || '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ formatDate(task.completed_at) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <span v-if="task.responsibles && task.responsibles.length">
                                    {{ task.responsibles[0].name }}
                                </span>
                                <span v-else>-</span>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                    <!-- Блок пагинации удален -->
                </div>

                <!-- Таблица Подзадач -->
                <div v-if="activeTab === 'subtasks'">
                    <!-- Заменили .data.length на .length -->
                    <div v-if="subtasks.length === 0" class="text-gray-500 text-center py-4">
                        Нет завершенных подзадач.
                    </div>
                    <table v-else class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Название</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Родительская задача</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дата завершения</th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                        <!-- Убрали .data из цикла -->
                        <tr v-for="subtask in subtasks" :key="subtask.id">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ subtask.name }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ subtask.task?.name || 'Задача удалена' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ formatDate(subtask.completed_at) }}
                            </td>
                        </tr>
                        </tbody>
                    </table>
                    <!-- Блок пагинации удален -->
                </div>

            </div>
        </div>
    </div>
</template>
