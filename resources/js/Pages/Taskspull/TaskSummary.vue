<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import axios from 'axios'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link } from '@inertiajs/vue3'

const props = defineProps({
    auth: Object
})

const loading = ref(true)
const summaryData = ref([])
const availableUsers = ref([])
const isOwner = ref(false)

// --- Основные фильтры запроса (влияют на запрос к БД) ---
const queryFilters = ref({
    mode: 'my_tasks', // 'my_tasks', 'author', 'owner'
    user_id: ''
})

// --- Визуальные фильтры (работают с уже загруженными данными) ---
const viewFilters = ref({
    type: 'all', // 'all', 'task', 'subtask'
    columns: {
        in_work: true,
        overdue: true,
        completed: true
    }
})

// Загрузка данных
const fetchSummary = async () => {
    loading.value = true
    try {
        const params = {
            mode: queryFilters.value.mode,
            user_id: queryFilters.value.user_id || undefined
        }
        const { data } = await axios.get('/api/tasks/summary', { params })

        summaryData.value = data.summary
        isOwner.value = data.is_owner

        if (!queryFilters.value.user_id) {
            availableUsers.value = data.users
        }
    } catch (e) {
        console.error(e)
    } finally {
        loading.value = false
    }
}

// Слежение за фильтрами запроса
watch(() => queryFilters.value.mode, () => {
    queryFilters.value.user_id = ''
    fetchSummary()
})
watch(() => queryFilters.value.user_id, fetchSummary)
onMounted(fetchSummary)

// --- Логика фильтрации данных на клиенте ---
const processedData = computed(() => {
    if (!summaryData.value) return [];

    return summaryData.value.map(userRow => {
        // Функция фильтрации по типу (задача/подзадача)
        const typeFilterFn = (item) => {
            if (viewFilters.value.type === 'all') return true;
            if (viewFilters.value.type === 'task') return !item.is_subtask && !item.task_id; // Условие зависит от вашей структуры
            if (viewFilters.value.type === 'subtask') return item.is_subtask || item.task_id;
            return true;
        };

        // Возвращаем новый объект с отфильтрованными массивами
        return {
            ...userRow,
            filteredTasks: {
                in_work: userRow.tasks.in_work.filter(typeFilterFn),
                overdue: userRow.tasks.overdue.filter(typeFilterFn),
                completed: userRow.tasks.completed.filter(typeFilterFn)
            }
        }
    })
})

// Форматирование
const formatDate = (date) => {
    if (!date) return '—'
    return new Date(date).toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit' })
}

// Переключение видимости колонок
const toggleColumn = (col) => {
    viewFilters.value.columns[col] = !viewFilters.value.columns[col];
}
</script>

<template>
    <Head title="Сводка задач" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-bold text-2xl text-slate-800 dark:text-white leading-tight flex items-center gap-2">
                    📊 Сводный пул
                </h2>
            </div>
        </template>

        <div class="py-8 bg-slate-50 dark:bg-slate-900 min-h-screen">
            <div class="max-w-[90rem] mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Блок управления (Фильтры) -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">

                    <div class="flex flex-col xl:flex-row gap-6 justify-between">

                        <!-- 1. Режим загрузки данных -->
                        <div class="flex flex-col gap-2">
                            <span class="text-xs font-bold uppercase text-slate-400 tracking-wider">Область видимости</span>
                            <div class="flex bg-slate-100 dark:bg-slate-700/50 p-1 rounded-lg self-start">
                                <button
                                    v-for="mode in [
                                        { key: 'my_tasks', label: 'Мои задачи' },
                                        { key: 'author', label: 'Я — Автор' },
                                        { key: 'owner', label: 'Все задачи', show: isOwner }
                                    ]"
                                    :key="mode.key"
                                    v-show="mode.show !== false"
                                    @click="queryFilters.mode = mode.key"
                                    class="px-4 py-2 rounded-md text-sm font-bold transition-all duration-200"
                                    :class="queryFilters.mode === mode.key
                                        ? 'bg-white dark:bg-slate-600 shadow-sm text-indigo-600 dark:text-indigo-400'
                                        : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'"
                                >
                                    {{ mode.label }}
                                </button>
                            </div>
                        </div>

                        <!-- 2. Исполнитель -->
                        <div class="flex flex-col gap-2 min-w-[200px]">
                            <span class="text-xs font-bold uppercase text-slate-400 tracking-wider">Исполнитель</span>
                            <select
                                v-model="queryFilters.user_id"
                                class="border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm py-2.5"
                            >
                                <option value="">Все сотрудники</option>
                                <option v-for="u in availableUsers" :key="u.id" :value="u.id">{{ u.name }}</option>
                            </select>
                        </div>

                        <div class="w-px bg-slate-200 dark:bg-slate-700 hidden xl:block"></div>

                        <!-- 3. Визуальные фильтры (Тип) -->
                        <div class="flex flex-col gap-2">
                            <span class="text-xs font-bold uppercase text-slate-400 tracking-wider">Тип сущности</span>
                            <div class="flex bg-slate-100 dark:bg-slate-700/50 p-1 rounded-lg self-start">
                                <button
                                    @click="viewFilters.type = 'all'"
                                    class="px-3 py-2 rounded-md text-sm font-bold transition"
                                    :class="viewFilters.type === 'all' ? 'bg-white dark:bg-slate-600 shadow-sm text-slate-800 dark:text-white' : 'text-slate-500'"
                                >Все</button>
                                <button
                                    @click="viewFilters.type = 'task'"
                                    class="px-3 py-2 rounded-md text-sm font-bold transition"
                                    :class="viewFilters.type === 'task' ? 'bg-white dark:bg-slate-600 shadow-sm text-slate-800 dark:text-white' : 'text-slate-500'"
                                >Только задачи</button>
                                <button
                                    @click="viewFilters.type = 'subtask'"
                                    class="px-3 py-2 rounded-md text-sm font-bold transition"
                                    :class="viewFilters.type === 'subtask' ? 'bg-white dark:bg-slate-600 shadow-sm text-slate-800 dark:text-white' : 'text-slate-500'"
                                >Только подзадачи</button>
                            </div>
                        </div>

                        <!-- 4. Визуальные фильтры (Статусы/Колонки) -->
                        <div class="flex flex-col gap-2">
                            <span class="text-xs font-bold uppercase text-slate-400 tracking-wider">Отображать статус</span>
                            <div class="flex gap-2">
                                <button
                                    @click="toggleColumn('in_work')"
                                    class="px-3 py-2 rounded-lg text-sm font-bold border transition-colors flex items-center gap-2"
                                    :class="viewFilters.columns.in_work
                                        ? 'bg-blue-50 border-blue-200 text-blue-700 dark:bg-blue-900/30 dark:border-blue-700 dark:text-blue-300'
                                        : 'bg-white border-dashed border-slate-300 text-slate-400 hover:border-slate-400'"
                                >
                                    <span class="w-2 h-2 rounded-full bg-current"></span> В работе
                                </button>
                                <button
                                    @click="toggleColumn('overdue')"
                                    class="px-3 py-2 rounded-lg text-sm font-bold border transition-colors flex items-center gap-2"
                                    :class="viewFilters.columns.overdue
                                        ? 'bg-rose-50 border-rose-200 text-rose-700 dark:bg-rose-900/30 dark:border-rose-700 dark:text-rose-300'
                                        : 'bg-white border-dashed border-slate-300 text-slate-400 hover:border-slate-400'"
                                >
                                    <span class="w-2 h-2 rounded-full bg-current"></span> Просрочено
                                </button>
                                <button
                                    @click="toggleColumn('completed')"
                                    class="px-3 py-2 rounded-lg text-sm font-bold border transition-colors flex items-center gap-2"
                                    :class="viewFilters.columns.completed
                                        ? 'bg-emerald-50 border-emerald-200 text-emerald-700 dark:bg-emerald-900/30 dark:border-emerald-700 dark:text-emerald-300'
                                        : 'bg-white border-dashed border-slate-300 text-slate-400 hover:border-slate-400'"
                                >
                                    <span class="w-2 h-2 rounded-full bg-current"></span> Завершено
                                </button>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Таблица -->
                <div class="bg-white dark:bg-slate-800 shadow-xl shadow-slate-200/50 dark:shadow-none rounded-3xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <div v-if="loading" class="p-12 text-center text-slate-500 animate-pulse">Загрузка данных...</div>

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                            <thead class="bg-slate-50 dark:bg-slate-900/50">
                            <tr>
                                <th class="px-6 py-5 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-64">
                                    Исполнитель
                                </th>
                                <!-- Динамические заголовки -->
                                <th v-if="viewFilters.columns.in_work" class="px-6 py-5 text-left text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider min-w-[250px]">
                                    В работе
                                </th>
                                <th v-if="viewFilters.columns.overdue" class="px-6 py-5 text-left text-xs font-bold text-rose-600 dark:text-rose-400 uppercase tracking-wider min-w-[250px]">
                                    Просроченные
                                </th>
                                <th v-if="viewFilters.columns.completed" class="px-6 py-5 text-left text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider min-w-[250px]">
                                    Завершенные
                                </th>
                                <th class="px-6 py-5 text-center text-xs font-bold text-slate-500 uppercase tracking-wider w-24">
                                    Всего
                                </th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-800">
                            <tr v-for="item in processedData" :key="item.user.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">

                                <!-- Исполнитель -->
                                <td class="px-6 py-4 align-top">
                                    <div class="flex items-center gap-3 sticky left-0">
                                        <div class="h-10 w-10 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center text-indigo-700 dark:text-indigo-300 font-bold border-2 border-white dark:border-slate-700 shadow-sm">
                                            {{ item.user.name.charAt(0) }}
                                        </div>
                                        <div>
                                            <div class="text-sm font-bold text-slate-900 dark:text-white leading-tight">{{ item.user.name }}</div>
                                            <div class="text-[10px] text-slate-400 uppercase font-bold mt-0.5">ID: {{ item.user.id }}</div>
                                        </div>
                                    </div>
                                </td>

                                <!-- В работе -->
                                <td v-if="viewFilters.columns.in_work" class="px-6 py-4 align-top bg-blue-50/30 dark:bg-blue-900/10 border-x border-slate-100 dark:border-slate-700/50">
                                    <div class="mb-3 flex items-center justify-between">
                                        <span class="text-xs font-bold text-blue-600 bg-blue-100 dark:bg-blue-900/40 px-2 py-0.5 rounded-full">
                                            {{ item.filteredTasks.in_work.length }}
                                        </span>
                                    </div>
                                    <div class="space-y-2">
                                        <div v-for="task in item.filteredTasks.in_work" :key="task.id + (task.is_subtask ? 's':'t')"
                                             class="group relative bg-white dark:bg-slate-700 p-3 rounded-xl border border-blue-100 dark:border-slate-600 hover:shadow-md hover:border-blue-300 transition-all duration-200">

                                            <div class="flex items-start justify-between gap-2">
                                                <a :href="task.link" class="font-semibold text-emerald-800 dark:text-emerald-300 hover:underline block truncate decoration-emerald-800/30">
                                        <span v-if="task.is_subtask" class="text-[10px] bg-white/50 dark:bg-gray-600 px-1 rounded mr-1 border border-emerald-200 dark:border-gray-500">
                                            ↳ Подзадача
                                        </span>
                                                    {{ task.title }}
                                                </a>
                                            </div>

                                            <div class="mt-2 flex items-center justify-between text-[10px] text-slate-500">
                                                <span class="flex items-center gap-1 bg-slate-100 dark:bg-slate-600 px-1.5 py-0.5 rounded">
                                                    📅 {{ formatDate(task.due_date) }}
                                                </span>
                                                <span class="truncate max-w-[100px] opacity-75">{{ task.project_name }}</span>
                                            </div>
                                        </div>
                                        <div v-if="item.filteredTasks.in_work.length === 0" class="text-xs text-slate-400 italic text-center py-4">Пусто</div>
                                    </div>
                                </td>

                                <!-- Просроченные -->
                                <td v-if="viewFilters.columns.overdue" class="px-6 py-4 align-top bg-rose-50/30 dark:bg-rose-900/10 border-r border-slate-100 dark:border-slate-700/50">
                                    <div class="mb-3 flex items-center justify-between">
                                        <span class="text-xs font-bold text-rose-600 bg-rose-100 dark:bg-rose-900/40 px-2 py-0.5 rounded-full">
                                            {{ item.filteredTasks.overdue.length }}
                                        </span>
                                    </div>
                                    <div class="space-y-2">
                                        <div v-for="task in item.filteredTasks.overdue" :key="task.id"
                                             class="group bg-white dark:bg-slate-700 p-3 rounded-xl border-l-4 border-l-rose-500 border border-y-rose-100 border-r-rose-100 dark:border-slate-600 hover:shadow-md transition-all duration-200">

                                            <a :href="task.link" class="font-semibold text-emerald-800 dark:text-emerald-300 hover:underline block truncate decoration-emerald-800/30">
                                        <span v-if="task.is_subtask" class="text-[10px] bg-white/50 dark:bg-gray-600 px-1 rounded mr-1 border border-emerald-200 dark:border-gray-500">
                                            ↳ Подзадача
                                        </span>
                                                {{ task.title }}
                                            </a>

                                            <div class="mt-1 flex flex-col gap-1 text-[10px]">
                                                <div class="font-bold text-rose-600">Опоздание! Срок: {{ formatDate(task.due_date) }}</div>
                                                <div class="text-slate-400">{{ task.project_name }}</div>
                                            </div>
                                        </div>
                                        <div v-if="item.filteredTasks.overdue.length === 0" class="text-xs text-slate-400 italic text-center py-4">Нет просрочек</div>
                                    </div>
                                </td>

                                <!-- Завершенные -->
                                <td v-if="viewFilters.columns.completed" class="px-6 py-4 align-top">
                                    <div class="mb-3 flex items-center justify-between">
                                        <span class="text-xs font-bold text-emerald-600 bg-emerald-100 dark:bg-emerald-900/40 px-2 py-0.5 rounded-full">
                                            {{ item.filteredTasks.completed.length }}
                                        </span>
                                    </div>
                                    <div class="space-y-2 max-h-[300px] overflow-y-auto pr-2 custom-scrollbar">
                                        <div v-for="task in item.filteredTasks.completed" :key="task.id"
                                             class="group flex items-center justify-between bg-slate-50 dark:bg-slate-700/50 p-2 rounded-lg border border-slate-100 dark:border-slate-700 hover:bg-white dark:hover:bg-slate-700 transition-colors">

                                            <div class="flex flex-col min-w-0">
                                                <a :href="task.link" class="font-semibold text-emerald-800 dark:text-emerald-300 hover:underline block truncate decoration-emerald-800/30">
                                        <span v-if="task.is_subtask" class="text-[10px] bg-white/50 dark:bg-gray-600 px-1 rounded mr-1 border border-emerald-200 dark:border-gray-500">
                                            ↳ Подзадача
                                        </span>
                                                    {{ task.title }}
                                                </a>
                                                <span class="text-[9px] text-slate-400">{{ task.project_name }}</span>
                                            </div>
                                            <div class="text-emerald-500 ml-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            </div>
                                        </div>
                                        <div v-if="item.filteredTasks.completed.length === 0" class="text-xs text-slate-400 italic text-center py-4">Нет завершенных</div>
                                    </div>
                                </td>

                                <!-- Итог -->
                                <td class="px-6 py-4 align-top text-center">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-200 font-bold text-xs border border-slate-200 dark:border-slate-600">
                                        {{
                                            item.filteredTasks.in_work.length +
                                            item.filteredTasks.overdue.length +
                                            item.filteredTasks.completed.length
                                        }}
                                    </span>
                                </td>
                            </tr>

                            <tr v-if="processedData.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-slate-400">
                                        <svg class="w-12 h-12 mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        <p class="font-medium">Нет данных по выбранным фильтрам</p>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
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
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #475569;
}
</style>
