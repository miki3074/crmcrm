<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from 'axios'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head } from '@inertiajs/vue3'
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    auth: Object
})

const loading = ref(true)
const summaryData = ref([])
const availableUsers = ref([])
const isOwner = ref(false)

// Фильтры
const filters = ref({
    mode: 'my_tasks', // 'my_tasks', 'author', 'owner'
    user_id: ''
})

const fetchSummary = async () => {
    loading.value = true
    try {
        const params = {
            mode: filters.value.mode,
            user_id: filters.value.user_id || undefined
        }
        const { data } = await axios.get('/api/tasks/summary', { params })

        summaryData.value = data.summary
        isOwner.value = data.is_owner

        // Обновляем список пользователей для фильтра только при первой загрузке или смене режима
        // (упрощенно - берем из ответа)
        if (!filters.value.user_id) {
            availableUsers.value = data.users
        }
    } catch (e) {
        console.error(e)
    } finally {
        loading.value = false
    }
}

// Следим за изменениями фильтров
watch(() => filters.value.mode, () => {
    filters.value.user_id = '' // сброс юзера при смене режима
    fetchSummary()
})

watch(() => filters.value.user_id, () => {
    fetchSummary()
})

onMounted(() => {
    fetchSummary()
})

const formatDate = (date) => {
    if (!date) return '—'
    return new Date(date).toLocaleDateString('ru-RU')
}

// Классы для приоритета
const getPriorityClass = (p) => {
    if (p === 'high') return 'text-red-600 font-bold'
    if (p === 'medium') return 'text-amber-600'
    return 'text-green-600'
}
</script>

<template>
    <Head title="Сводка задач" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                📊 Сводный пул задач
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

                <!-- Панель управления -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <div class="flex flex-col sm:flex-row gap-4 justify-between items-center">

                        <!-- Режимы (Табы) -->
                        <div class="flex bg-gray-100 dark:bg-gray-700 p-1 rounded-lg">
                            <button
                                @click="filters.mode = 'my_tasks'"
                                :class="filters.mode === 'my_tasks' ? 'bg-white dark:bg-gray-600 shadow text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700'"
                                class="px-4 py-2 rounded-md text-sm font-medium transition"
                            >
                                Мои задачи
                            </button>
                            <button
                                @click="filters.mode = 'author'"
                                :class="filters.mode === 'author' ? 'bg-white dark:bg-gray-600 shadow text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700'"
                                class="px-4 py-2 rounded-md text-sm font-medium transition"
                            >
                                Я — Автор (РП)
                            </button>
                            <button
                                v-if="isOwner"
                                @click="filters.mode = 'owner'"
                                :class="filters.mode === 'owner' ? 'bg-white dark:bg-gray-600 shadow text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700'"
                                class="px-4 py-2 rounded-md text-sm font-medium transition"
                            >
                                Владелец (Все)
                            </button>
                        </div>

                        <!-- Фильтр по исполнителю -->
                        <div class="w-full sm:w-64">
                            <select
                                v-model="filters.user_id"
                                class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            >
                                <option value="">Все исполнители</option>
                                <option v-for="u in availableUsers" :key="u.id" :value="u.id">
                                    {{ u.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Таблица -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg overflow-x-auto">
                    <div v-if="loading" class="p-8 text-center text-gray-500">Загрузка данных...</div>

                    <table v-else class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-1/5">
                                Исполнитель
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-blue-600 dark:text-blue-400 uppercase tracking-wider w-1/4">
                                В работе
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-rose-600 dark:text-rose-400 uppercase tracking-wider w-1/4">
                                Просроченные
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-emerald-600 dark:text-emerald-400 uppercase tracking-wider w-1/4">
                                Завершенные
                            </th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Итог
                            </th>
                        </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="item in summaryData" :key="item.user.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">

                            <!-- Колонка: Исполнитель -->
                            <td class="px-6 py-4 align-top">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 flex-shrink-0">
                                        <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold">
                                            {{ item.user.name.charAt(0) }}
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ item.user.name }}</div>
                                    </div>
                                </div>
                            </td>

                            <!-- Колонка: В работе -->
                            <td class="px-6 py-4 align-top">
                                <div class="mb-2 font-bold text-blue-600">{{ item.stats.in_work_count }} шт.</div>
                                <div class="space-y-2">
                                    <!-- Key изменен, чтобы не путать ID задач и подзадач -->
                                    <div
                                        v-for="task in item.tasks.in_work"
                                        :key="(task.is_subtask ? 's' : 't') + task.id"
                                        class="bg-blue-50 dark:bg-blue-900/20 p-2 rounded border border-blue-100 dark:border-blue-800 text-xs"
                                    >
                                        <!-- Ссылка task.link берется с бэкенда -->
                                        <Link
                                            :href="(task.task_id || task.is_subtask) ? `/subtasks/${task.id}` : `/tasks/${task.id}`"
                                            class="font-semibold text-emerald-800 dark:text-emerald-300 hover:underline block truncate decoration-emerald-800/30"
                                        >
    <span v-if="task.task_id || task.is_subtask" class="text-[10px] bg-white/50 dark:bg-gray-600 px-1 rounded mr-1 border border-emerald-200 dark:border-gray-500">
        ↳ Подзадача
    </span>
                                            {{ task.title }}
                                        </Link>
                                        <div class="text-gray-500 mt-1 flex flex-col gap-0.5">
                                            <span>📅 Срок: {{ formatDate(task.due_date) }}</span>
                                            <span class="opacity-75">👤 Роль: {{ task.roles }}</span>
                                            <span class="text-[10px] text-gray-400">📁 {{ task.project_name }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Колонка: Просроченные -->
                            <td class="px-6 py-4 align-top">
                                <div class="mb-2 font-bold text-rose-600">{{ item.stats.overdue_count }} шт.</div>
                                <div class="space-y-2">
                                    <div
                                        v-for="task in item.tasks.overdue"
                                        :key="(task.is_subtask ? 's' : 't') + task.id"
                                        class="bg-rose-50 dark:bg-rose-900/20 p-2 rounded border border-rose-100 dark:border-rose-800 text-xs"
                                    >
                                        <a :href="task.link" class="font-semibold text-rose-800 dark:text-rose-300 hover:underline block truncate">
                                            <span v-if="task.is_subtask" class="text-[10px] bg-white/50 dark:bg-gray-600 px-1 rounded mr-1 border border-rose-200 dark:border-gray-500">
                                                ↳ Подзадача
                                            </span>
                                            {{ task.title }}
                                        </a>
                                        <div class="text-rose-800/70 mt-1 flex flex-col gap-0.5">
                                            <span class="font-bold">⚠️ Срок: {{ formatDate(task.due_date) }}</span>
                                            <span>👤 Роль: {{ task.roles }}</span>
                                            <span class="text-[10px] opacity-70">📁 {{ task.project_name }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Колонка: Завершенные -->
                            <td class="px-6 py-4 align-top">
                                <div class="mb-2 font-bold text-emerald-600">{{ item.stats.completed_count }} шт.</div>

                                <div class="space-y-2 max-h-48 overflow-y-auto pr-1 custom-scrollbar">
                                    <div
                                        v-for="task in item.tasks.completed"
                                        :key="(task.is_subtask ? 's' : 't') + task.id"
                                        class="bg-emerald-50 dark:bg-emerald-900/20 p-2 rounded border border-emerald-100 dark:border-emerald-800 text-xs opacity-75 hover:opacity-100 transition"
                                    >
                                        <a :href="task.link" class="font-semibold text-emerald-800 dark:text-emerald-300 hover:underline block truncate decoration-emerald-800/30">
                                            <span v-if="task.is_subtask" class="text-[10px] bg-white/50 dark:bg-gray-600 px-1 rounded mr-1 border border-emerald-200 dark:border-gray-500">
                                                ↳ Подзадача
                                            </span>
                                            {{ task.title }}
                                        </a>
                                        <div class="text-gray-500 mt-1 flex flex-col gap-0.5">
                                            <span>👤 Роль: {{ task.roles }}</span>
                                            <span class="text-[10px]">📁 {{ task.project_name }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Колонка: Итог -->
                            <td class="px-6 py-4 align-top text-center">
                                    <span class="inline-flex items-center justify-center px-3 py-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 font-bold text-sm">
                                        {{ item.stats.total }}
                                    </span>
                            </td>

                        </tr>

                        <tr v-if="summaryData.length === 0">
                            <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                                Задач по выбранным критериям не найдено.
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 20px;
}
</style>
