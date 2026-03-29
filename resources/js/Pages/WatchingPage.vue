<script setup>
import { ref, onMounted, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import axios from 'axios'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const loading = ref(true)
const watchingTasks = ref([])
const watchingProjects = ref([])
const completedTasks = ref([]) // Добавляем завершённые задачи
const completedSubtasks = ref([]) // Добавляем завершённые подзадачи

// Фильтры для задач
const taskFilter = ref('all') // all, in_progress, overdue, incomplete, completed
const searchQuery = ref('')

// Состояние фильтрации для каждого блока
const watchingTasksFilter = ref('in_progress')
const watchingTasksSearch = ref('')
const showCompletedTasks = ref(true) // Показывать ли завершённые задачи

// Приоритеты для бейджей
const prioBadge = (p) => ({
    low:    'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
    medium: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
    high:   'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
}[p] || 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300')

// Определение статуса задачи
const getTaskStatus = (task) => {
    // Завершённая
    if (task.completed === true || task.completed === 1) {
        return 'completed'
    }
    // Просроченная (due_date просрочен и прогресс < 100)
    if (task.due_date && new Date(task.due_date) < new Date() && task.progress < 100) {
        return 'overdue'
    }
    // В работе (прогресс >= 0 и прогресс < 100)
    if (task.progress >= 0 && task.progress < 100) {
        return 'in_progress'
    }
    // Не завершенная (прогресс = 100, но не завершена)
    if (task.progress === 100 && !task.completed) {
        return 'incomplete'
    }
    return 'unknown'
}

// Цвет обводки в зависимости от статуса
const getBorderColor = (task) => {
    const status = getTaskStatus(task)
    switch(status) {
        case 'in_progress':
            return 'border-blue-500 dark:border-blue-400 ring-blue-100 dark:ring-blue-900/30'
        case 'overdue':
            return 'border-red-500 dark:border-red-400 ring-red-100 dark:ring-red-900/30'
        case 'incomplete':
            return 'border-yellow-500 dark:border-yellow-400 ring-yellow-100 dark:ring-yellow-900/30'
        case 'completed':
            return 'border-emerald-500 dark:border-emerald-400 bg-emerald-50/30 dark:bg-emerald-900/5'
        default:
            return 'border-slate-200 dark:border-slate-700'
    }
}

// Фильтрация задач
const filterTasks = (tasks, filterType, searchText = '') => {
    let filtered = [...tasks]

    // Фильтр по статусу
    switch(filterType) {
        case 'in_progress':
            filtered = filtered.filter(t => {
                const isCompleted = t.completed === true || t.completed === 1
                const isOverdue = t.due_date && new Date(t.due_date) < new Date() && t.progress < 100
                const isInProgress = t.progress >= 0 && t.progress < 100
                return !isCompleted && !isOverdue && isInProgress
            })
            break
        case 'overdue':
            filtered = filtered.filter(t => {
                const isCompleted = t.completed === true || t.completed === 1
                const isOverdue = t.due_date && new Date(t.due_date) < new Date() && t.progress < 100
                return !isCompleted && isOverdue
            })
            break
        case 'incomplete':
            filtered = filtered.filter(t => {
                const isCompleted = t.completed === true || t.completed === 1
                const isIncomplete = t.progress === 100 && !isCompleted
                return !isCompleted && isIncomplete
            })
            break
        case 'completed':
            filtered = filtered.filter(t => t.completed === true || t.completed === 1)
            break
        case 'all':
        default:
            break
    }

    // Поиск по названию
    if (searchText.trim()) {
        const query = searchText.toLowerCase()
        filtered = filtered.filter(t =>
            t.title?.toLowerCase().includes(query) ||
            t.project?.name?.toLowerCase().includes(query) ||
            t.project?.company?.name?.toLowerCase().includes(query)
        )
    }

    return filtered
}

// Объединённые задачи (текущие + завершённые)
const allWatchingTasks = computed(() => {
    // Преобразуем завершённые задачи в формат, совместимый с watchingTasks
    const formattedCompletedTasks = completedTasks.value.map(task => ({
        id: task.id,
        title: task.title,
        due_date: task.due_date,
        progress: 100,
        completed: true,
        priority: 'low', // или можно добавить поле priority из данных
        project: {
            name: task.company,
            company: {
                name: task.company
            }
        }
    }))

    return [...watchingTasks.value, ...formattedCompletedTasks]
})

// Фильтрованные наблюдаемые задачи
const filteredWatchingTasks = computed(() => {
    return filterTasks(allWatchingTasks.value, watchingTasksFilter.value, watchingTasksSearch.value)
})

// Статистика по статусам
const tasksStats = computed(() => {
    const tasks = allWatchingTasks.value
    const now = new Date()

    return {
        all: tasks.length,
        in_progress: tasks.filter(t => {
            const isCompleted = t.completed === true || t.completed === 1
            const isOverdue = t.due_date && new Date(t.due_date) < now && t.progress < 100
            return !isCompleted && !isOverdue && t.progress < 100
        }).length,
        overdue: tasks.filter(t => {
            const isCompleted = t.completed === true || t.completed === 1
            const isOverdue = t.due_date && new Date(t.due_date) < now && t.progress < 100
            return !isCompleted && isOverdue
        }).length,
        incomplete: tasks.filter(t => {
            const isCompleted = t.completed === true || t.completed === 1
            return !isCompleted && t.progress === 100
        }).length,
        completed: tasks.filter(t => t.completed === true || t.completed === 1).length
    }
})

const formatDate = (date) => {
    if (!date) return '—'
    return new Date(date).toLocaleDateString('ru-RU', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    })
}

const getProgressColor = (progress, status) => {
    if (status === 'overdue') return 'bg-red-500'
    if (status === 'in_progress') return 'bg-blue-500'
    if (status === 'incomplete') return 'bg-yellow-500'
    if (status === 'completed') return 'bg-emerald-500'
    if (progress >= 80) return 'bg-emerald-500'
    if (progress >= 50) return 'bg-amber-500'
    return 'bg-indigo-500'
}

const fetchWatchingData = async () => {
    loading.value = true
    try {
        await axios.get('/sanctum/csrf-cookie')

        // Получаем данные с дашборда
        const { data } = await axios.get('/api/dashboard/summary', { withCredentials: true })

        // Извлекаем задачи и проекты из полученных данных
        watchingTasks.value = data.watching_tasks || []
        watchingProjects.value = data.watching_projects || []

        // Загружаем завершённые задачи для каждого проекта, где пользователь наблюдатель
        await fetchCompletedTasksForWatchingProjects()
    } catch (e) {
        console.error('Ошибка загрузки данных наблюдателя:', e)
    } finally {
        loading.value = false
    }
}

// Загрузка завершённых задач для проектов, за которыми наблюдает пользователь
const fetchCompletedTasksForWatchingProjects = async () => {
    try {
        const completedTasksPromises = watchingProjects.value.map(project =>
            axios.get(`/api/projects/${project.id}/completed-tasks`, { withCredentials: true })
                .catch(err => {
                    console.error(`Ошибка загрузки завершённых задач для проекта ${project.id}:`, err)
                    return { data: { tasks: [], subtasks: [] } }
                })
        )

        const results = await Promise.all(completedTasksPromises)

        // Собираем все завершённые задачи
        const allCompletedTasks = []
        const allCompletedSubtasks = []

        results.forEach(result => {
            allCompletedTasks.push(...(result.data.tasks || []))
            allCompletedSubtasks.push(...(result.data.subtasks || []))
        })

        completedTasks.value = allCompletedTasks
        completedSubtasks.value = allCompletedSubtasks
    } catch (e) {
        console.error('Ошибка загрузки завершённых задач:', e)
    }
}

const goToTask = (taskId) => {
    router.visit(`/tasks2/${taskId}`)
}

const goToProject = (projectId) => {
    router.visit(`/projects2/${projectId}`)
}

// Сброс фильтров
const resetFilters = () => {
    watchingTasksFilter.value = 'in_progress'
    watchingTasksSearch.value = ''
}

onMounted(() => {
    fetchWatchingData()
})
</script>

<template>
    <Head title="Наблюдаемое" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <!-- Заголовок страницы -->
            <div class="mb-8">

                <p class="text-slate-500 dark:text-slate-400 mt-2">
                    Задачи и проекты, за которыми вы следите
                </p>
            </div>

            <!-- Состояние загрузки -->
            <div v-if="loading" class="space-y-8">
                <div class="space-y-4">
                    <div class="h-8 w-48 bg-slate-200 dark:bg-slate-700 rounded-lg animate-pulse"></div>
                    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div v-for="i in 6" :key="i" class="h-40 rounded-2xl bg-slate-100 dark:bg-slate-800 animate-pulse"/>
                    </div>
                </div>
                <div class="space-y-4">
                    <div class="h-8 w-48 bg-slate-200 dark:bg-slate-700 rounded-lg animate-pulse"></div>
                    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div v-for="i in 6" :key="i" class="h-32 rounded-2xl bg-slate-100 dark:bg-slate-800 animate-pulse"/>
                    </div>
                </div>
            </div>

            <div v-else class="space-y-12">

                <!-- Блок: Наблюдаемые задачи с фильтрацией -->
                <div class="space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 dark:border-slate-700 pb-3">
                        <h2 class="text-xl font-bold text-slate-700 dark:text-slate-300 flex items-center gap-2">
                            <span class="text-2xl">📋</span>
                            Задачи
                            <span class="text-sm font-normal text-slate-500 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-full">
                {{ filteredWatchingTasks.length }} / {{ tasksStats.all }}
              </span>
                        </h2>

                        <!-- Кнопки фильтрации -->
                        <div class="flex flex-wrap gap-2">

                            <button
                                @click="watchingTasksFilter = 'in_progress'"
                                :class="[
                  'px-3 py-1.5 rounded-xl text-sm font-medium transition-all',
                  watchingTasksFilter === 'in_progress'
                    ? 'bg-blue-600 text-white shadow-md'
                    : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'
                ]"
                            >
                <span class="inline-flex items-center gap-1">
                  <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                  В работе ({{ tasksStats.in_progress }})
                </span>
                            </button>
                            <button
                                @click="watchingTasksFilter = 'overdue'"
                                :class="[
                  'px-3 py-1.5 rounded-xl text-sm font-medium transition-all',
                  watchingTasksFilter === 'overdue'
                    ? 'bg-red-600 text-white shadow-md'
                    : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'
                ]"
                            >
                <span class="inline-flex items-center gap-1">
                  <span class="w-2 h-2 rounded-full bg-red-500"></span>
                  Просроченные ({{ tasksStats.overdue }})
                </span>
                            </button>
                            <button
                                @click="watchingTasksFilter = 'incomplete'"
                                :class="[
                  'px-3 py-1.5 rounded-xl text-sm font-medium transition-all',
                  watchingTasksFilter === 'incomplete'
                    ? 'bg-yellow-600 text-white shadow-md'
                    : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'
                ]"
                            >
                <span class="inline-flex items-center gap-1">
                  <span class="w-2 h-2 rounded-full bg-yellow-500"></span>
                  Не завершённые ({{ tasksStats.incomplete }})
                </span>
                            </button>
                            <button
                                @click="watchingTasksFilter = 'completed'"
                                :class="[
                  'px-3 py-1.5 rounded-xl text-sm font-medium transition-all',
                  watchingTasksFilter === 'completed'
                    ? 'bg-emerald-600 text-white shadow-md'
                    : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'
                ]"
                            >
                <span class="inline-flex items-center gap-1">
                  <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                  Завершённые ({{ tasksStats.completed }})
                </span>
                            </button>

                            <button
                                @click="watchingTasksFilter = 'all'"
                                :class="[
                  'px-3 py-1.5 rounded-xl text-sm font-medium transition-all',
                  watchingTasksFilter === 'all'
                    ? 'bg-indigo-600 text-white shadow-md'
                    : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'
                ]"
                            >
                                Все ({{ tasksStats.all }})
                            </button>
                        </div>
                    </div>

                    <!-- Поиск по задачам -->
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input
                            v-model="watchingTasksSearch"
                            type="text"
                            placeholder="Поиск по названию задачи, проекту или компании..."
                            class="w-full pl-10 pr-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        />
                        <button
                            v-if="watchingTasksSearch"
                            @click="watchingTasksSearch = ''"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                        >
                            ✕
                        </button>
                    </div>

                    <!-- Список задач -->
                    <div v-if="!filteredWatchingTasks.length" class="text-center py-12 bg-slate-50 dark:bg-slate-800/30 rounded-2xl">

                        <p class="text-slate-500 dark:text-slate-400">
                            {{ watchingTasksFilter !== 'all' ? 'Нет задач с выбранным статусом' : 'Вы еще не наблюдаете ни за одной задачей' }}
                        </p>

                    </div>

                    <div v-else class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div
                            v-for="task in filteredWatchingTasks"
                            :key="task.id"
                            @click="goToTask(task.id)"
                            class="group rounded-2xl border-2 bg-white dark:bg-slate-900/60 p-5 hover:shadow-xl transition-all cursor-pointer"
                            :class="getBorderColor(task)"
                        >
                            <div class="flex items-start justify-between gap-2 mb-2">
                                <h3 class="font-semibold text-slate-700 dark:text-slate-200 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition line-clamp-2 flex-1">
                                    {{ task.title }}
                                </h3>
                                <span v-if="getTaskStatus(task) !== 'completed'" class="text-[10px] px-2 py-0.5 rounded-full whitespace-nowrap" :class="prioBadge(task.priority)">
                  {{ task.priority === 'high' ? 'Высокий' : task.priority === 'medium' ? 'Средний' : 'Низкий' }}
                </span>
                                <span v-else class="text-[10px] px-2 py-0.5 rounded-full whitespace-nowrap bg-emerald-100 text-emerald-700">
                  Завершена
                </span>
                            </div>

                            <!-- Статус задачи -->
                            <div class="mt-2">
                <span
                    class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full"
                    :class="{
                    'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300': getTaskStatus(task) === 'in_progress',
                    'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300': getTaskStatus(task) === 'overdue',
                    'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300': getTaskStatus(task) === 'incomplete',
                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300': getTaskStatus(task) === 'completed'
                  }"
                >
                  <span class="w-1.5 h-1.5 rounded-full" :class="{
                    'bg-blue-500': getTaskStatus(task) === 'in_progress',
                    'bg-red-500': getTaskStatus(task) === 'overdue',
                    'bg-yellow-500': getTaskStatus(task) === 'incomplete',
                    'bg-emerald-500': getTaskStatus(task) === 'completed'
                  }"></span>
                  {{
                        getTaskStatus(task) === 'in_progress' ? (task.progress === 0 ? 'Новая' : 'В работе') :
                            getTaskStatus(task) === 'overdue' ? 'Просрочена' :
                                getTaskStatus(task) === 'incomplete' ? 'Не завершена (100%)' :
                                    getTaskStatus(task) === 'completed' ? 'Завершена' : '—'
                    }}
                </span>
                            </div>

                            <div class="text-xs text-slate-400 space-y-1 mt-3">
                                <div class="flex items-center gap-1">
                                    <span class="font-medium">Компания:</span>
                                    <span class="truncate">{{ task.project?.company?.name || task.company || '—' }}</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <span class="font-medium">Проект:</span>
                                    <span class="truncate">{{ task.project?.name || '—' }}</span>
                                </div>
                                <div v-if="task.due_date" class="flex items-center gap-1">
                                    <span class="font-medium">Срок:</span>
                                    <span :class="{'text-red-500 font-semibold': getTaskStatus(task) === 'overdue'}">
                    {{ formatDate(task.due_date) }}
                  </span>
                                </div>
                            </div>

                            <!-- Прогресс (только для незавершённых задач) -->
                            <div v-if="getTaskStatus(task) !== 'completed'" class="mt-3">
                                <div class="flex justify-between text-[10px] text-slate-400 mb-1">
                                    <span>Прогресс</span>
                                    <span>{{ task.progress || 0 }}%</span>
                                </div>
                                <div class="h-1.5 rounded-full bg-slate-100 dark:bg-slate-700 overflow-hidden">
                                    <div
                                        class="h-full rounded-full transition-all duration-500"
                                        :class="getProgressColor(task.progress || 0, getTaskStatus(task))"
                                        :style="{ width: (task.progress || 0) + '%' }"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Кнопка сброса фильтров -->
                    <div v-if="watchingTasksFilter !== 'all' || watchingTasksSearch" class="flex justify-center pt-2">
                        <button
                            @click="resetFilters"
                            class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Сбросить все фильтры
                        </button>
                    </div>
                </div>

                <!-- Блок: Наблюдаемые проекты -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-700 pb-3">
                        <h2 class="text-xl font-bold text-slate-700 dark:text-slate-300 flex items-center gap-2">
                            <span class="text-2xl">🏗️</span>
                            Проекты
                            <span class="text-sm font-normal text-slate-500 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-full">
                {{ watchingProjects.length }}
              </span>
                        </h2>
                    </div>

                    <div v-if="!watchingProjects.length" class="text-center py-12 bg-slate-50 dark:bg-slate-800/30 rounded-2xl">

                        <p class="text-slate-500 dark:text-slate-400">Вы еще не наблюдаете ни за одним проектом</p>

                    </div>

                    <div v-else class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div
                            v-for="project in watchingProjects"
                            :key="project.id"
                            @click="goToProject(project.id)"
                            class="group rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/60 p-5 hover:shadow-lg hover:border-indigo-300 dark:hover:border-indigo-700 transition-all cursor-pointer"
                        >
                            <div class="flex items-start justify-between gap-2 mb-2">
                                <h3 class="font-semibold text-slate-700 dark:text-slate-200 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition line-clamp-2">
                                    {{ project.name }}
                                </h3>
                            </div>

                            <div class="text-xs text-slate-400 space-y-1 mt-2">
                                <div class="flex items-center gap-1">
                                    <span class="font-medium">Компания:</span>
                                    <span class="truncate">{{ project.company?.name || '—' }}</span>
                                </div>
                                <div v-if="project.managers?.length" class="flex items-center gap-1 flex-wrap">
                                    <span class="font-medium">Руководители:</span>
                                    <span>{{ project.managers.map(m => m.name).join(', ') }}</span>
                                </div>

                            </div>


                        </div>
                    </div>
                </div>



            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
