<script setup>
import { router } from '@inertiajs/vue3'
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'

const props = defineProps({
    projects: Array,
    loading: Boolean
})

// Состояние
const selectedProjectId = ref(null)
const isMobile = ref(false)
const searchQuery = ref('')
const sortBy = ref('deadline')
const viewMode = ref('grid') // grid или list

// Определение мобильного устройства
const checkMobile = () => {
    isMobile.value = window.innerWidth < 1024
}

onMounted(() => {
    checkMobile()
    window.addEventListener('resize', checkMobile)
})

onUnmounted(() => {
    window.removeEventListener('resize', checkMobile)
})

// При загрузке страницы выбираем первый проект
watch(() => props.projects, (newVal) => {
    if (newVal?.length > 0 && !selectedProjectId.value) {
        selectedProjectId.value = newVal[0].id
    }
}, { immediate: true })

// Вычисляем активный проект
const activeProject = computed(() => {
    return props.projects?.find(p => p.id === selectedProjectId.value) || null
})

// Фильтрация и сортировка проектов
const filteredProjects = computed(() => {
    let filtered = [...(props.projects || [])]

    // Поиск
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        filtered = filtered.filter(p =>
            p.name.toLowerCase().includes(query) ||
            p.managers?.some(m => m.name.toLowerCase().includes(query))
        )
    }

    // Сортировка
    filtered.sort((a, b) => {
        switch(sortBy.value) {
            case 'deadline':
                const daysA = daysLeft(a.start_date, a.duration_days) ?? Infinity
                const daysB = daysLeft(b.start_date, b.duration_days) ?? Infinity
                return daysA - daysB
            case 'name':
                return a.name.localeCompare(b.name)
            case 'progress':
                return (b.progress || 0) - (a.progress || 0)
            default:
                return 0
        }
    })

    return filtered
})

// Helpers для проектов
const daysLeft = (startDate, duration) => {
    if (!startDate || !duration) return null
    const start = new Date(startDate)
    const end = new Date(start)
    end.setDate(start.getDate() + Number(duration))
    const today = new Date()
    return Math.ceil((end - today) / (1000 * 60 * 60 * 24))
}

const getProjectStatus = (days) => {
    if (days === null) return { label: 'Без срока', color: 'slate' }
    if (days < 0) return { label: 'Просрочен', color: 'rose' }
    if (days <= 3) return { label: 'Срочно', color: 'amber' }
    if (days <= 7) return { label: 'Скоро', color: 'blue' }
    return { label: 'В норме', color: 'emerald' }
}

const getStatusColor = (status) => {
    const colors = {
        rose: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300 border-rose-200 dark:border-rose-800',
        amber: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300 border-amber-200 dark:border-amber-800',
        blue: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 border-blue-200 dark:border-blue-800',
        emerald: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
        slate: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400 border-slate-200 dark:border-slate-700'
    }
    return colors[status] || colors.slate
}

const managerInitials = (name) => {
    if (!name) return '?'
    return name.split(' ').map(p => p[0]).slice(0, 2).join('').toUpperCase()
}

// Helpers для задач
const getPriorityColor = (priority) => {
    const colors = {
        high: 'rose',
        medium: 'amber',
        low: 'emerald'
    }
    return colors[priority] || 'slate'
}

const getPriorityLabel = (priority) => {
    const labels = {
        high: 'Высокий',
        medium: 'Средний',
        low: 'Низкий'
    }
    return labels[priority] || priority
}

const getStatusIcon = (status) => {
    const icons = {
        new: '🆕',
        in_work: '⚡',
        review: '🔍',
        completed: '✅',
        cancelled: '❌'
    }
    return icons[status] || '📋'
}

const handleProjectClick = (project) => {
    if (isMobile.value) {
        router.visit(`/projects/${project.id}`)
    } else {
        selectedProjectId.value = project.id
    }
}

// Статистика
const stats = computed(() => {
    const total = props.projects?.length || 0
    const overdue = props.projects?.filter(p => {
        const days = daysLeft(p.start_date, p.duration_days)
        return days !== null && days < 0
    }).length || 0
    const urgent = props.projects?.filter(p => {
        const days = daysLeft(p.start_date, p.duration_days)
        return days !== null && days >= 0 && days <= 3
    }).length || 0

    return { total, overdue, urgent }
})
</script>

<template>
    <div class="h-[calc(100vh-120px)] min-h-[700px] bg-slate-50 dark:bg-slate-900/50 rounded-3xl p-6">

        <!-- Header с статистикой и поиском -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div class="flex items-center gap-4">
                <div class="w-1 h-8 bg-gradient-to-b from-indigo-500 to-purple-500 rounded-full"></div>
                <div>
                    <h2 class="text-2xl font-light tracking-tight text-slate-800 dark:text-white">
                        Активные проекты
                        <span class="text-indigo-600 dark:text-indigo-400 font-semibold ml-2">{{ stats.total }}</span>
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        <span class="text-rose-500">{{ stats.overdue }}</span> просрочено

                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <!-- Поиск -->
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text"
                           v-model="searchQuery"
                           placeholder="Поиск проектов..."
                           class="pl-10 pr-4 py-2 w-64 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200/20 transition">
                </div>

                <!-- Сортировка -->
                <select v-model="sortBy"
                        class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200/20 transition">
                    <option value="deadline">По сроку</option>
                    <option value="name">По названию</option>

                </select>

                <!-- Переключение вида -->
                <div class="flex gap-1 p-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl">
                    <button @click="viewMode = 'grid'"
                            class="p-2 rounded-lg transition"
                            :class="viewMode === 'grid' ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600' : 'text-slate-400 hover:text-slate-600'">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                    </button>
                    <button @click="viewMode = 'list'"
                            class="p-2 rounded-lg transition"
                            :class="viewMode === 'list' ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600' : 'text-slate-400 hover:text-slate-600'">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Скелетон загрузки -->
        <div v-if="loading" class="grid grid-cols-1 lg:grid-cols-3 gap-6 h-full">
            <div class="lg:col-span-1 space-y-4">
                <div v-for="i in 4" :key="i" class="h-28 bg-white dark:bg-slate-800 rounded-2xl animate-pulse"></div>
            </div>
            <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-3xl animate-pulse"></div>
        </div>

        <!-- Основной контент -->
        <div v-else class="flex flex-col lg:flex-row gap-6 h-full overflow-hidden">

            <!-- Левая колонка - список проектов -->
            <div class="w-full lg:w-[35%] overflow-y-auto pr-2 space-y-3 custom-scrollbar">

                <div v-if="!filteredProjects.length"
                     class="flex flex-col items-center justify-center py-12 text-slate-400">
                    <span class="text-6xl mb-4 opacity-30">📋</span>
                    <p class="text-lg font-medium">Проекты не найдены</p>
                    <p class="text-sm">Попробуйте изменить параметры поиска</p>
                </div>

                <!-- Grid режим (карточки) -->
                <div v-if="viewMode === 'grid'" class="space-y-3">
                    <div v-for="project in filteredProjects"
                         :key="project.id"
                         @click="handleProjectClick(project)"
                         class="group relative overflow-hidden cursor-pointer transition-all duration-300"
                         style="padding: 9px"
                         :class="[
                             !isMobile && selectedProjectId === project.id
                                 ? 'scale-[1.02] z-10'
                                 : 'hover:scale-[1.01]'
                         ]">

                        <!-- Карточка проекта -->
                        <div class="relative p-5 bg-white dark:bg-slate-800 rounded-2xl border-2 transition-all"
                             :class="[
                                 !isMobile && selectedProjectId === project.id
                                     ? 'border-indigo-500 shadow-xl shadow-indigo-500/10'
                                     : 'border-slate-100 dark:border-slate-700 hover:border-indigo-200 dark:hover:border-indigo-800'
                             ]">

                            <!-- Декоративный индикатор выбора -->
                            <div v-if="!isMobile && selectedProjectId === project.id"
                                 class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-12 bg-gradient-to-b from-indigo-500 to-purple-500 rounded-r-full">
                            </div>

                            <div class="flex items-start gap-3">
                                <!-- Аватарка проекта -->
                                <div class="shrink-0 w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-100 to-purple-100 dark:from-indigo-900/30 dark:to-purple-900/30 flex items-center justify-center text-2xl">
                                    {{ project.icon || '📁' }}
                                </div>

                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-2">
                                        <h3 class="font-semibold text-slate-800 dark:text-white truncate"
                                            :class="!isMobile && selectedProjectId === project.id ? 'text-indigo-600 dark:text-indigo-400' : ''">
                                            {{ project.name }}
                                        </h3>

                                        <!-- Статус проекта -->
                                        <span class="shrink-0 text-[10px] font-bold px-2 py-1 rounded-full border"
                                              :class="getStatusColor(getProjectStatus(daysLeft(project.start_date, project.duration_days)).color)">
                                            {{ getProjectStatus(daysLeft(project.start_date, project.duration_days)).label }}
                                        </span>
                                    </div>

                                    <!-- Прогресс бар -->


                                    <!-- Мета информация -->
                                    <div class="flex items-center justify-between text-xs">
                                        <div class="flex items-center gap-2 text-slate-500">
                                            <span class="flex items-center gap-1">
                                                <span>📅</span>
                                                {{ project.start_date }}
                                            </span>
                                            <span class="flex items-center gap-1">
                                                <span>👥</span>
                                                {{ project.managers?.length || 0 }}
                                            </span>
                                        </div>

                                        <!-- Аватарки менеджеров -->
                                        <div class="flex -space-x-1.5">
                                            <div v-for="m in project.managers?.slice(0, 3)" :key="m.id"
                                                 class="w-6 h-6 rounded-full border-2 border-white dark:border-slate-800 bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white text-[8px] font-bold shadow-sm"
                                                 :title="m.name">
                                                {{ managerInitials(m.name) }}
                                            </div>
                                            <div v-if="project.managers?.length > 3"
                                                 class="w-6 h-6 rounded-full border-2 border-white dark:border-slate-800 bg-slate-200 dark:bg-slate-700 flex items-center justify-center text-[8px] font-bold text-slate-600 dark:text-slate-400">
                                                +{{ project.managers.length - 3 }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Индикатор для мобильных -->
                            <div v-if="isMobile"
                                 class="absolute bottom-3 right-3 text-xs text-indigo-500 opacity-0 group-hover:opacity-100 transition-opacity">
                                Перейти →
                            </div>
                        </div>
                    </div>
                </div>

                <!-- List режим (список) -->
                <div v-else-if="viewMode === 'list'" class="space-y-2">
                    <div v-for="project in filteredProjects"
                         :key="project.id"
                         @click="handleProjectClick(project)"
                         class="group cursor-pointer">

                        <div class="p-4 bg-white dark:bg-slate-800 rounded-xl border border-slate-100 dark:border-slate-700 hover:border-indigo-300 dark:hover:border-indigo-700 transition-all"
                             :class="!isMobile && selectedProjectId === project.id ? 'bg-indigo-50/50 dark:bg-indigo-900/20 border-indigo-300' : ''">

                            <div class="flex items-center gap-4">
                                <!-- Иконка статуса -->
                                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-indigo-100 to-purple-100 dark:from-indigo-900/30 dark:to-purple-900/30 flex items-center justify-center text-xl">
                                    {{ getProjectStatus(daysLeft(project.start_date, project.duration_days)).label === 'Просрочен' ? '⚠️' : '📁' }}
                                </div>

                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-3">
                                        <h3 class="font-medium text-slate-800 dark:text-white"
                                            :class="!isMobile && selectedProjectId === project.id ? 'text-indigo-600 dark:text-indigo-400' : ''">
                                            {{ project.name }}
                                        </h3>
                                        <span class="text-xs text-slate-400">{{ project.tasks?.length || 0 }} задач</span>
                                    </div>

                                    <div class="flex items-center gap-4 mt-1 text-xs text-slate-500">
                                        <span>Старт: {{ project.start_date }}</span>
                                        <span>Дедлайн: {{ daysLeft(project.start_date, project.duration_days) }} дн.</span>
                                    </div>
                                </div>

                                <!-- Аватарки -->
                                <div class="flex -space-x-1.5">
                                    <div v-for="m in project.managers?.slice(0, 3)" :key="m.id"
                                         class="w-6 h-6 rounded-full border-2 border-white dark:border-slate-800 bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white text-[8px] font-bold">
                                        {{ managerInitials(m.name) }}
                                    </div>
                                </div>

                                <!-- Индикатор выбора -->
                                <svg v-if="!isMobile && selectedProjectId === project.id"
                                     class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Правая колонка - детали проекта и задачи -->
            <div v-if="!isMobile"
                 class="flex-1 bg-white dark:bg-slate-800 rounded-3xl border border-slate-200 dark:border-slate-700 shadow-xl overflow-hidden flex flex-col">

                <!-- Детали выбранного проекта -->
                <div v-if="activeProject" class="h-full flex flex-col">

                    <!-- Хедер проекта с градиентом -->
                    <div class="relative p-8 bg-gradient-to-br from-indigo-500/5 to-purple-500/5 border-b border-slate-100 dark:border-slate-700">
                        <div class="flex items-start justify-between">
                            <div class="space-y-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-1 h-10 bg-gradient-to-b from-indigo-500 to-purple-500 rounded-full"></div>
                                    <div>
                                        <h2 class="text-3xl font-light tracking-tight text-slate-800 dark:text-white">
                                            {{ activeProject.name }}
                                        </h2>
                                        <p class="text-sm text-slate-500 mt-1">
                                            Создан {{ new Date(activeProject.created_at).toLocaleDateString('ru-RU') }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Теги проекта -->
                                <div class="flex flex-wrap gap-2">
                                    <span class="px-3 py-1 text-xs rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800">
                                        {{ activeProject.tasks?.length || 0 }} задач
                                    </span>
                                    <span class="px-3 py-1 text-xs rounded-full bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 border border-purple-200 dark:border-purple-800">
                                        {{ activeProject.managers?.length || 0 }} руководителей
                                    </span>
                                    <span class="px-3 py-1 text-xs rounded-full bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                                        Дедлайн: {{ daysLeft(activeProject.start_date, activeProject.duration_days) }} дн.
                                    </span>
                                </div>
                            </div>

                            <button @click="router.visit(`/projects/${activeProject.id}`)"
                                    class="group relative px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-medium shadow-lg shadow-indigo-500/30 hover:shadow-xl transition-all overflow-hidden">
                                <span class="relative flex items-center gap-2">
                                    Открыть проект
                                    <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                    </svg>
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Список задач -->
                    <div class="flex-1 overflow-y-auto p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-slate-700 dark:text-slate-300">Задачи проекта</h3>
                            <span class="text-sm text-slate-500">Всего: {{ activeProject.tasks?.length || 0 }}</span>
                        </div>

                        <div v-if="!activeProject.tasks?.length"
                             class="flex flex-col items-center justify-center py-16 text-slate-400">
                            <span class="text-6xl mb-4 opacity-30">✅</span>
                            <p class="text-lg font-medium">Задач пока нет</p>
                            <p class="text-sm">Создайте первую задачу в проекте</p>
                        </div>

                        <div v-else class="space-y-3">
                            <div v-for="task in activeProject.tasks"
                                 :key="task.id"
                                 @click="router.visit(`/tasks/${task.id}`)"
                                 class="group p-5 bg-slate-50 dark:bg-slate-900/50 hover:bg-white dark:hover:bg-slate-900 border border-slate-200 dark:border-slate-700 hover:border-indigo-300 dark:hover:border-indigo-700 rounded-xl transition-all cursor-pointer">

                                <div class="flex items-start gap-4">
                                    <!-- Статус иконка -->
                                    <div class="text-2xl">{{ getStatusIcon(task.status) }}</div>

                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start justify-between gap-4">
                                            <div>
                                                <h4 class="font-medium text-slate-800 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                                    {{ task.title }}
                                                </h4>
                                                <p v-if="task.description" class="text-sm text-slate-500 mt-1 line-clamp-1">
                                                    {{ task.description }}
                                                </p>
                                            </div>

                                            <!-- Приоритет -->
                                            <span class="shrink-0 text-xs px-3 py-1 rounded-full border"
                                                  :class="`border-${getPriorityColor(task.priority)}-200 bg-${getPriorityColor(task.priority)}-50 text-${getPriorityColor(task.priority)}-700`">
                                                {{ getPriorityLabel(task.priority) }}
                                            </span>
                                        </div>

                                        <!-- Мета информация задачи -->
                                        <div class="flex items-center gap-4 mt-3 text-xs text-slate-500">
                                            <span class="flex items-center gap-1">
                                                <span>📅</span>
                                                {{ task.due_date || 'Нет срока' }}
                                            </span>
                                            <span class="flex items-center gap-1">
                                                <span>👤</span>
                                                {{ task.assignee?.name || 'Не назначен' }}
                                            </span>
                                            <span class="flex items-center gap-1">
                                                <span>⏱️</span>
                                                {{ task.estimated_hours || 0 }}ч
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Состояние без выбранного проекта -->
                <div v-else class="flex items-center justify-center h-full">
                    <div class="text-center">
                        <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-4xl">
                            👈
                        </div>
                        <h3 class="text-xl font-medium text-slate-700 dark:text-slate-300 mb-2">Проект не выбран</h3>
                        <p class="text-slate-500">Выберите проект из списка слева,<br>чтобы увидеть его задачи</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Кастомный скроллбар */
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 20px;
}

.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #475569;
}

/* Анимации */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.fade-enter-active {
    animation: fadeIn 0.3s ease-out;
}

/* Ограничение текста */
.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Адаптивность */
@media (max-width: 1024px) {
    .custom-scrollbar {
        overflow-y: visible;
    }
}
</style>
