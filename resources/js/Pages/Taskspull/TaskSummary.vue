<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import axios from 'axios'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head } from '@inertiajs/vue3'

const props = defineProps({
    auth: Object
})

const loading = ref(true)
const summaryData = ref([])
const availableUsers = ref([])
const isOwner = ref(false)
const expandedGroups = ref(new Set()) // Хранит ключи раскрытых списков

// --- НОВЫЕ ПЕРЕМЕННЫЕ ДЛЯ ОТЧЕТА ---
const showReportModal = ref(false)
const filterOptions = ref({
    companies: [],
    projects: []
})
const reportForm = ref({
    mode: 'my_tasks', // my_tasks, author, owner
    user_id: '',
    company_id: '',
    project_id: ''
})

// --- Основные фильтры ---
const queryFilters = ref({
    mode: 'my_tasks',
    user_id: ''
})

// --- Визуальные фильтры ---
const viewFilters = ref({
    type: 'all',
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

        // Заполняем списки для модального окна (если бэкенд их пришлет)
        // Если бэкенд не присылает, можно вычленить их из summaryData,
        // но лучше запросить отдельно. Ниже в контроллере я добавлю их возврат.
        if (data.meta) {
            filterOptions.value.companies = data.meta.companies
            filterOptions.value.projects = data.meta.projects
            if (!queryFilters.value.user_id) availableUsers.value = data.users
        } else if (!queryFilters.value.user_id) {
            availableUsers.value = data.users
        }

    } catch (e) {
        console.error(e)
    } finally {
        loading.value = false
    }
}

watch(() => queryFilters.value.mode, () => {
    queryFilters.value.user_id = ''
    fetchSummary()
})
watch(() => queryFilters.value.user_id, fetchSummary)
onMounted(fetchSummary)

// --- Логика фильтрации данных ---
const processedData = computed(() => {
    if (!summaryData.value) return [];

    return summaryData.value.map(userRow => {
        const typeFilterFn = (item) => {
            if (viewFilters.value.type === 'all') return true;
            if (viewFilters.value.type === 'task') return !item.is_subtask && !item.task_id;
            if (viewFilters.value.type === 'subtask') return item.is_subtask || item.task_id;
            return true;
        };

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

const formatDate = (date) => {
    if (!date) return '—'
    return new Date(date).toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit' })
}

const toggleColumn = (col) => {
    viewFilters.value.columns[col] = !viewFilters.value.columns[col];
}

// --- Логика "Показать еще" ---
// Создаем уникальный ключ для каждого списка: ID_юзера + тип_колонки
const getExpandKey = (userId, columnType) => `${userId}_${columnType}`

const toggleExpand = (userId, columnType) => {
    const key = getExpandKey(userId, columnType)
    if (expandedGroups.value.has(key)) {
        expandedGroups.value.delete(key)
    } else {
        expandedGroups.value.add(key)
    }
}

// Возвращает либо 10 задач, либо все, если раскрыто
const getVisibleTasks = (tasks, userId, columnType) => {
    const key = getExpandKey(userId, columnType)
    if (expandedGroups.value.has(key)) {
        return tasks
    }
    return tasks.slice(0, 10)
}

// --- ЛОГИКА ОТЧЕТА ---
const openReportModal = () => {
    // Сброс формы при открытии
    reportForm.value = {
        mode: queryFilters.value.mode, // наследуем текущий режим
        user_id: '',
        company_id: '',
        project_id: ''
    }
    showReportModal.value = true
}

const downloadReport = () => {
    // Формируем URL для скачивания
    const params = new URLSearchParams({
        mode: reportForm.value.mode,
        user_id: reportForm.value.user_id,
        company_id: reportForm.value.company_id,
        project_id: reportForm.value.project_id
    })

    // Открываем в новом окне для скачивания файла
    window.location.href = `/api/tasks/report/export?${params.toString()}`
    showReportModal.value = false
}

// Фильтруем проекты зависимо от выбранной компании в модалке
const availableProjectsForReport = computed(() => {
    if (!reportForm.value.company_id) return filterOptions.value.projects;
    return filterOptions.value.projects.filter(p => p.company_id == reportForm.value.company_id)
})
</script>

<template>
    <Head title="Сводка задач" />
    <AuthenticatedLayout>
<!--        <template #header>-->
<!--            <div class="flex items-center justify-between">-->
<!--                <h2 class="font-bold text-2xl text-slate-800 dark:text-white leading-tight flex items-center gap-2">-->
<!--                    📊 Сводный пул-->
<!--                </h2>-->

<!--                <button @click="openReportModal" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-bold shadow-lg shadow-indigo-500/30 transition flex items-center gap-2">-->
<!--                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>-->
<!--                    Сформировать отчет-->
<!--                </button>-->
<!--            </div>-->
<!--        </template>-->

        <div class="py-8 bg-slate-50 dark:bg-slate-900 min-h-screen">
            <!-- max-w-full чтобы использовать всю ширину, px-2 для небольших отступов по краям экрана -->
            <div class="w-full px-2 sm:px-4 lg:px-6 space-y-6">

                <!-- Блок управления (Фильтры) - без изменений -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
                    <div class="flex flex-col xl:flex-row gap-6 justify-between">
                        <!-- 1. Режим -->
                        <div class="flex flex-col gap-2">
                            <span class="text-xs font-bold uppercase text-slate-400 tracking-wider">Область видимости</span>
                            <div class="flex bg-slate-100 dark:bg-slate-700/50 p-1 rounded-lg self-start">
                                <button v-for="mode in [{ key: 'my_tasks', label: 'Мои задачи' }, { key: 'author', label: 'Я — Автор' }, { key: 'owner', label: 'Все задачи сотрудников', show: isOwner }]" :key="mode.key" v-show="mode.show !== false" @click="queryFilters.mode = mode.key" class="px-4 py-2 rounded-md text-sm font-bold transition-all duration-200" :class="queryFilters.mode === mode.key ? 'bg-white dark:bg-slate-600 shadow-sm text-indigo-600 dark:text-indigo-400' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'">{{ mode.label }}</button>
                            </div>
                        </div>
                        <!-- 2. Исполнитель -->
                        <div class="flex flex-col gap-2 min-w-[200px]">
                            <span class="text-xs font-bold uppercase text-slate-400 tracking-wider">Исполнитель</span>
                            <select v-model="queryFilters.user_id" class="border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm py-2.5">
                                <option value="">Все сотрудники</option>
                                <option v-for="u in availableUsers" :key="u.id" :value="u.id">{{ u.name }}</option>
                            </select>
                        </div>
                        <div class="w-px bg-slate-200 dark:bg-slate-700 hidden xl:block"></div>
                        <!-- 3. Тип -->
                        <div class="flex flex-col gap-2">
                            <span class="text-xs font-bold uppercase text-slate-400 tracking-wider">Тип сущности</span>
                            <div class="flex bg-slate-100 dark:bg-slate-700/50 p-1 rounded-lg self-start">
                                <button @click="viewFilters.type = 'all'" class="px-3 py-2 rounded-md text-sm font-bold transition" :class="viewFilters.type === 'all' ? 'bg-white dark:bg-slate-600 shadow-sm text-slate-800 dark:text-white' : 'text-slate-500'">Все</button>
                                <button @click="viewFilters.type = 'task'" class="px-3 py-2 rounded-md text-sm font-bold transition" :class="viewFilters.type === 'task' ? 'bg-white dark:bg-slate-600 shadow-sm text-slate-800 dark:text-white' : 'text-slate-500'">Задачи</button>
                                <button @click="viewFilters.type = 'subtask'" class="px-3 py-2 rounded-md text-sm font-bold transition" :class="viewFilters.type === 'subtask' ? 'bg-white dark:bg-slate-600 shadow-sm text-slate-800 dark:text-white' : 'text-slate-500'">Подзадачи</button>
                            </div>
                        </div>
                        <!-- 4. Колонки -->
                        <div class="flex flex-col gap-2">
                            <span class="text-xs font-bold uppercase text-slate-400 tracking-wider">Отображать статус</span>
                            <div class="flex gap-2">
                                <button @click="toggleColumn('in_work')" class="px-3 py-2 rounded-lg text-sm font-bold border transition-colors flex items-center gap-2" :class="viewFilters.columns.in_work ? 'bg-blue-50 border-blue-200 text-blue-700 dark:bg-blue-900/30 dark:border-blue-700 dark:text-blue-300' : 'bg-white border-dashed border-slate-300 text-slate-400 hover:border-slate-400'"><span class="w-2 h-2 rounded-full bg-current"></span> В работе</button>
                                <button @click="toggleColumn('overdue')" class="px-3 py-2 rounded-lg text-sm font-bold border transition-colors flex items-center gap-2" :class="viewFilters.columns.overdue ? 'bg-rose-50 border-rose-200 text-rose-700 dark:bg-rose-900/30 dark:border-rose-700 dark:text-rose-300' : 'bg-white border-dashed border-slate-300 text-slate-400 hover:border-slate-400'"><span class="w-2 h-2 rounded-full bg-current"></span> Просрочено</button>
                                <button @click="toggleColumn('completed')" class="px-3 py-2 rounded-lg text-sm font-bold border transition-colors flex items-center gap-2" :class="viewFilters.columns.completed ? 'bg-emerald-50 border-emerald-200 text-emerald-700 dark:bg-emerald-900/30 dark:border-emerald-700 dark:text-emerald-300' : 'bg-white border-dashed border-slate-300 text-slate-400 hover:border-slate-400'"><span class="w-2 h-2 rounded-full bg-current"></span> Завершено</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Таблица -->
                <div class="bg-white dark:bg-slate-800 shadow-xl shadow-slate-200/50 dark:shadow-none rounded-3xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <div v-if="loading" class="p-12 text-center text-slate-500 animate-pulse">Загрузка данных...</div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full divide-y divide-slate-200 dark:divide-slate-700 table-fixed">
                            <thead class="bg-slate-50 dark:bg-slate-900/50">
                            <tr>
                                <!-- 1. ИСПОЛНИТЕЛЬ: w-52 (немного уменьшили, чтобы дать место задачам) -->
                                <th class="px-4 py-5 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-52">
                                    Исполнитель
                                </th>
                                <!-- 2. ОСНОВНЫЕ КОЛОНКИ: Без width, делят место поровну -->
                                <th v-if="viewFilters.columns.in_work" class="px-3 py-5 text-left text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider">
                                    В работе
                                </th>
                                <th v-if="viewFilters.columns.overdue" class="px-3 py-5 text-left text-xs font-bold text-rose-600 dark:text-rose-400 uppercase tracking-wider">
                                    Просроченные
                                </th>
                                <th v-if="viewFilters.columns.completed" class="px-3 py-5 text-left text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">
                                    Завершенные
                                </th>
                                <!-- 3. ИТОГ: w-16 (минимальная ширина) -->
                                <th class="px-2 py-5 text-center text-xs font-bold text-slate-500 uppercase tracking-wider w-16">
                                    Всего
                                </th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-800">
                            <tr v-for="item in processedData" :key="item.user.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">

                                <!-- Исполнитель -->
                                <td class="px-4 py-4 align-top">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center text-indigo-700 dark:text-indigo-300 font-bold border-2 border-white dark:border-slate-700 shadow-sm shrink-0">
                                            {{ item.user.name.charAt(0) }}
                                        </div>
                                        <div class="overflow-hidden min-w-0"> <!-- min-w-0 важен для truncate внутри flex -->
                                            <div class="text-sm font-bold text-slate-900 dark:text-white leading-tight truncate" :title="item.user.name">
                                                {{ item.user.name }}
                                            </div>
                                            <div class="text-[10px] text-slate-400 uppercase font-bold mt-0.5">ID: {{ item.user.id }}</div>
                                        </div>
                                    </div>
                                </td>

                                <!-- В работе -->
                                <td v-if="viewFilters.columns.in_work" class="px-2 py-4 align-top bg-blue-50/30 dark:bg-blue-900/10 border-x border-slate-100 dark:border-slate-700/50">
                                    <div class="mb-3 flex items-center justify-between px-1">
                                        <span class="text-xs font-bold text-blue-600 bg-blue-100 dark:bg-blue-900/40 px-2 py-0.5 rounded-full">
                                            {{ item.filteredTasks.in_work.length }}
                                        </span>
                                    </div>
                                    <div class="space-y-2">
                                        <div v-for="task in getVisibleTasks(item.filteredTasks.in_work, item.user.id, 'in_work')"
                                             :key="task.id + (task.is_subtask ? 's':'t')"
                                             class="group relative bg-white dark:bg-slate-700 p-2.5 rounded-xl border border-blue-100 dark:border-slate-600 hover:shadow-md hover:border-blue-300 transition-all duration-200 w-full max-w-full"> <!-- max-w-full -->

                                            <!-- FLEX + MIN-W-0: Ключевой момент для работы truncate -->
                                            <div class="flex items-start gap-1 w-full min-w-0">
                                                <a :href="task.link"
                                                   :title="task.title"
                                                   class="font-semibold text-emerald-800 dark:text-emerald-300 hover:underline block truncate decoration-emerald-800/30 text-sm flex-1 min-w-0">
                                                    <span v-if="task.is_subtask" class="inline-block text-[10px] bg-white/50 dark:bg-gray-600 px-1 rounded mr-1 border border-emerald-200 dark:border-gray-500 align-middle">
                                                        ↳
                                                    </span>
                                                    {{ task.title }}
                                                </a>
                                            </div>

                                            <div class="mt-2 flex items-center justify-between text-[10px] text-slate-500">
                                                <span class="flex items-center gap-1 bg-slate-100 dark:bg-slate-600 px-1.5 py-0.5 rounded whitespace-nowrap shrink-0">
                                                    📅 {{ formatDate(task.due_date) }}
                                                </span>
                                                <div class="flex flex-col items-end min-w-0 max-w-[45%] ml-2">
    <span class="text-[9px] font-bold text-slate-400 truncate w-full text-right" :title="task.company_name">
        {{ task.company_name }}
    </span>
                                                    <span class="truncate w-full text-right opacity-75" :title="task.project_name">
        {{ task.project_name }}
    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <button v-if="item.filteredTasks.in_work.length > 10" @click="toggleExpand(item.user.id, 'in_work')" class="w-full py-2 text-xs font-bold text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors dashed-border mt-2">
                                            {{ expandedGroups.has(getExpandKey(item.user.id, 'in_work')) ? 'Свернуть' : `Еще (${item.filteredTasks.in_work.length - 10})` }}
                                        </button>
                                        <div v-if="item.filteredTasks.in_work.length === 0" class="text-xs text-slate-400 italic text-center py-4">Пусто</div>
                                    </div>
                                </td>

                                <!-- Просроченные -->
                                <td v-if="viewFilters.columns.overdue" class="px-2 py-4 align-top bg-rose-50/30 dark:bg-rose-900/10 border-r border-slate-100 dark:border-slate-700/50">
                                    <div class="mb-3 flex items-center justify-between px-1">
                                        <span class="text-xs font-bold text-rose-600 bg-rose-100 dark:bg-rose-900/40 px-2 py-0.5 rounded-full">
                                            {{ item.filteredTasks.overdue.length }}
                                        </span>
                                    </div>
                                    <div class="space-y-2">
                                        <div v-for="task in getVisibleTasks(item.filteredTasks.overdue, item.user.id, 'overdue')"
                                             :key="task.id"
                                             class="group bg-white dark:bg-slate-700 p-2.5 rounded-xl border-l-4 border-l-rose-500 border border-y-rose-100 border-r-rose-100 dark:border-slate-600 hover:shadow-md transition-all duration-200 w-full max-w-full">

                                            <div class="flex items-start gap-1 w-full min-w-0">
                                                <a :href="task.link"
                                                   :title="task.title"
                                                   class="font-semibold text-emerald-800 dark:text-emerald-300 hover:underline block truncate decoration-emerald-800/30 text-sm flex-1 min-w-0">
                                                    <span v-if="task.is_subtask" class="inline-block text-[10px] bg-white/50 dark:bg-gray-600 px-1 rounded mr-1 border border-emerald-200 dark:border-gray-500 align-middle">
                                                        ↳
                                                    </span>
                                                    {{ task.title }}
                                                </a>
                                            </div>

                                            <div class="mt-1 flex flex-col gap-1 text-[10px]">
                                                <div class="font-bold text-rose-600 whitespace-nowrap">Опоздание! Срок: {{ formatDate(task.due_date) }}</div>
                                                <div class="text-slate-400 truncate w-full flex items-center gap-1 text-[9px]" :title="`${task.company_name} -> ${task.project_name}`">
                                                    <span class="font-bold text-slate-500 shrink-0">{{ task.company_name }}</span>
                                                    <span class="text-slate-300">•</span>
                                                    <span class="truncate">{{ task.project_name }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <button v-if="item.filteredTasks.overdue.length > 10" @click="toggleExpand(item.user.id, 'overdue')" class="w-full py-2 text-xs font-bold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-lg transition-colors dashed-border mt-2">
                                            {{ expandedGroups.has(getExpandKey(item.user.id, 'overdue')) ? 'Свернуть' : `Еще (${item.filteredTasks.overdue.length - 10})` }}
                                        </button>
                                        <div v-if="item.filteredTasks.overdue.length === 0" class="text-xs text-slate-400 italic text-center py-4">Нет просрочек</div>
                                    </div>
                                </td>

                                <!-- Завершенные -->
                                <td v-if="viewFilters.columns.completed" class="px-2 py-4 align-top">
                                    <div class="mb-3 flex items-center justify-between px-1">
                                        <span class="text-xs font-bold text-emerald-600 bg-emerald-100 dark:bg-emerald-900/40 px-2 py-0.5 rounded-full">
                                            {{ item.filteredTasks.completed.length }}
                                        </span>
                                    </div>
                                    <div class="space-y-2">
                                        <div v-for="task in getVisibleTasks(item.filteredTasks.completed, item.user.id, 'completed')"
                                             :key="task.id"
                                             class="group flex items-center justify-between bg-slate-50 dark:bg-slate-700/50 p-2 rounded-lg border border-slate-100 dark:border-slate-700 hover:bg-white dark:hover:bg-slate-700 transition-colors w-full max-w-full">

                                            <div class="flex flex-col min-w-0 w-full">
                                                <div class="flex items-start w-full min-w-0">
                                                    <a :href="task.link"
                                                       :title="task.title"
                                                       class="font-semibold text-emerald-800 dark:text-emerald-300 hover:underline block truncate decoration-emerald-800/30 text-sm flex-1 min-w-0">
                                                        <span v-if="task.is_subtask" class="inline-block text-[10px] bg-white/50 dark:bg-gray-600 px-1 rounded mr-1 border border-emerald-200 dark:border-gray-500 align-middle">
                                                            ↳
                                                        </span>
                                                        {{ task.title }}
                                                    </a>
                                                </div>
                                                <span class="text-[9px] text-slate-400 truncate mt-0.5 flex gap-1" :title="`${task.company_name} | ${task.project_name}`">
    <span class="font-semibold text-slate-500">{{ task.company_name }}</span>
    <span>:</span>
    <span class="truncate">{{ task.project_name }}</span>
</span>
                                            </div>
                                            <div class="text-emerald-500 ml-1 shrink-0">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            </div>
                                        </div>

                                        <button v-if="item.filteredTasks.completed.length > 10" @click="toggleExpand(item.user.id, 'completed')" class="w-full py-2 text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 rounded-lg transition-colors dashed-border mt-2">
                                            {{ expandedGroups.has(getExpandKey(item.user.id, 'completed')) ? 'Свернуть' : `Еще (${item.filteredTasks.completed.length - 10})` }}
                                        </button>
                                        <div v-if="item.filteredTasks.completed.length === 0" class="text-xs text-slate-400 italic text-center py-4">Нет завершенных</div>
                                    </div>
                                </td>

                                <!-- Итог -->
                                <td class="px-2 py-4 align-top text-center">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-200 font-bold text-xs border border-slate-200 dark:border-slate-600">
                                        {{
                                            item.filteredTasks.in_work.length +
                                            item.filteredTasks.overdue.length +
                                            item.filteredTasks.completed.length
                                        }}
                                    </span>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>


        <!-- МОДАЛЬНОЕ ОКНО ОТЧЕТА -->
        <div v-if="showReportModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm transition-opacity">
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-lg w-full p-6 space-y-6 border border-slate-200 dark:border-slate-700">
                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-bold text-slate-800 dark:text-white">Параметры отчета</h3>
                    <button @click="showReportModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <!-- 1. Режим отчета -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Чьи задачи выгружать?</label>
                        <select v-model="reportForm.mode" class="w-full border-slate-300 dark:border-slate-600 dark:bg-slate-700 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="my_tasks">Только мои задачи</option>
                            <option value="author">Где я — автор</option>
                            <option v-if="isOwner" value="owner">Все задачи компании</option>
                        </select>
                    </div>

                    <!-- 2. Выбор сотрудника (Скрыт если "Мои задачи") -->
                    <div v-if="reportForm.mode !== 'my_tasks'">
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Фильтр по сотруднику (необязательно)</label>
                        <select v-model="reportForm.user_id" class="w-full border-slate-300 dark:border-slate-600 dark:bg-slate-700 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Все сотрудники</option>
                            <option v-for="u in availableUsers" :key="u.id" :value="u.id">{{ u.name }}</option>
                        </select>
                    </div>

                    <div class="border-t border-slate-200 dark:border-slate-700 my-4"></div>

                    <!-- 3. Фильтр по Компании -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Компания</label>
                        <select v-model="reportForm.company_id" class="w-full border-slate-300 dark:border-slate-600 dark:bg-slate-700 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Все компании</option>
                            <option v-for="c in filterOptions.companies" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>

                    <!-- 4. Фильтр по Проекту -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Проект</label>
                        <select v-model="reportForm.project_id" class="w-full border-slate-300 dark:border-slate-600 dark:bg-slate-700 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Все проекты</option>
                            <option v-for="p in availableProjectsForReport" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button @click="showReportModal = false" class="px-4 py-2 text-slate-600 dark:text-slate-300 font-bold hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition">Отмена</button>
                    <button @click="downloadReport" class="px-4 py-2 bg-indigo-600 text-white font-bold rounded-lg shadow-lg hover:bg-indigo-700 transition flex items-center gap-2">
                        <span>Скачать CSV</span>
                    </button>
                </div>
            </div>
        </div>


    </AuthenticatedLayout>
</template>

<style scoped>
.dashed-border {
    border: 1px dashed currentColor;
    opacity: 0.7;
}
.dashed-border:hover {
    opacity: 1;
    border-style: solid;
}
</style>
--- END OF FILE text/plain ---
