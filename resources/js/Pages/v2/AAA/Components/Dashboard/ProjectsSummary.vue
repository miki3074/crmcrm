<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps(['projects'])

const LIMIT = 6
const isModalOpen = ref(false)
const selectedCompanyFilter = ref('all')
const searchQuery = ref('')
const sortBy = ref('deadline') // deadline, name, tasks

// --- HELPER FUNCTIONS ---

const formatDate = (dateString) => {
    if (!dateString) return '—'
    return new Date(dateString).toLocaleDateString('ru-RU', {
        day: '2-digit', month: '2-digit', year: 'numeric'
    })
}

const calculateDeadline = (startDate, duration) => {
    if (!startDate || !duration) return '—'
    const date = new Date(startDate)
    date.setDate(date.getDate() + duration)
    return date.toLocaleDateString('ru-RU', {
        day: '2-digit', month: '2-digit', year: 'numeric'
    })
}

const getDeadlineStatus = (startDate, duration) => {
    if (!startDate || !duration) return 'unknown'

    const today = new Date()
    const deadline = new Date(startDate)
    deadline.setDate(deadline.getDate() + duration)

    const diffDays = Math.ceil((deadline - today) / (1000 * 60 * 60 * 24))

    if (diffDays < 0) return 'overdue'
    if (diffDays <= 3) return 'urgent'
    if (diffDays <= 7) return 'soon'
    return 'normal'
}

const getProgressColor = (progress) => {
    if (progress >= 75) return 'emerald'
    if (progress >= 50) return 'blue'
    if (progress >= 25) return 'amber'
    return 'rose'
}

const groupProjects = (list) => {
    return list.reduce((acc, p) => {
        const cName = p.company?.name || 'Без компании'
        if (!acc[cName]) acc[cName] = []
        acc[cName].push(p)
        return acc
    }, {})
}

const sortProjects = (list) => {
    const sorted = [...list]
    switch(sortBy.value) {
        case 'deadline':
            return sorted.sort((a, b) => {
                const deadlineA = a.start_date && a.duration_days ? new Date(a.start_date).getTime() + a.duration_days * 86400000 : Infinity
                const deadlineB = b.start_date && b.duration_days ? new Date(b.start_date).getTime() + b.duration_days * 86400000 : Infinity
                return deadlineA - deadlineB
            })
        case 'name':
            return sorted.sort((a, b) => a.name.localeCompare(b.name))
        case 'tasks':
            return sorted.sort((a, b) => (b.tasks_count || 0) - (a.tasks_count || 0))
        default:
            return sorted
    }
}

// --- MAIN VIEW ---

const limitedList = computed(() => {
    return sortProjects(props.projects).slice(0, LIMIT)
})

const groupedLimited = computed(() => {
    return groupProjects(limitedList.value)
})

const hiddenCount = computed(() => {
    return Math.max(0, props.projects.length - LIMIT)
})

// --- MODAL ---

const companyNames = computed(() => {
    const names = new Set(props.projects.map(p => p.company?.name || 'Без компании'))
    return Array.from(names).sort()
})

const filteredProjects = computed(() => {
    let list = [...props.projects]

    // Фильтр по компании
    if (selectedCompanyFilter.value !== 'all') {
        list = list.filter(p => (p.company?.name || 'Без компании') === selectedCompanyFilter.value)
    }

    // Поиск по названию
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        list = list.filter(p => p.name.toLowerCase().includes(query))
    }

    return sortProjects(list)
})

const groupedAllFiltered = computed(() => {
    return groupProjects(filteredProjects.value)
})

const stats = computed(() => {
    const total = props.projects.length
    const overdue = props.projects.filter(p => {
        if (!p.start_date || !p.duration_days) return false
        const deadline = new Date(p.start_date)
        deadline.setDate(deadline.getDate() + p.duration_days)
        return deadline < new Date()
    }).length

    const urgent = props.projects.filter(p => {
        if (!p.start_date || !p.duration_days) return false
        const deadline = new Date(p.start_date)
        deadline.setDate(deadline.getDate() + p.duration_days)
        const diffDays = Math.ceil((deadline - new Date()) / (1000 * 60 * 60 * 24))
        return diffDays > 0 && diffDays <= 3
    }).length

    return { total, overdue, urgent }
})
</script>

<template>
    <div class="space-y-8">
        <!-- Заголовок со статистикой -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-1 h-8 bg-gradient-to-b from-indigo-500 to-purple-500 rounded-full"></div>
                <div>
                    <h3 class="text-lg font-medium text-slate-800 dark:text-slate-100 tracking-wide">
                        Активные проекты
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        {{ stats.total }} всего • {{ stats.overdue }} просрочено
                    </p>
                </div>
            </div>

            <!-- Индикатор загрузки/обновления (опционально) -->
            <div class="flex items-center gap-2 text-xs text-slate-400">
                <span class="inline-block w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                Актуально
            </div>
        </div>

        <!-- Сетка проектов (превью) -->
        <div class="grid grid-cols-1 gap-6">
            <div v-for="(projs, companyName) in groupedLimited" :key="companyName"
                 class="group/card relative overflow-hidden">

                <!-- Декоративный фон -->
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-50/50 to-transparent dark:from-indigo-950/20 rounded-3xl -z-10"></div>

                <!-- Заголовок компании -->
                <div class="flex items-center gap-3 mb-4 px-2">
                    <div class="w-1 h-5 bg-gradient-to-b from-indigo-400 to-indigo-600 rounded-full"></div>
                    <span class="text-sm font-medium text-slate-500 dark:text-slate-400 tracking-wider uppercase">
                        {{ companyName }}
                    </span>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 ml-auto">
                        {{ projs.length }} проекта
                    </span>
                </div>

                <!-- Карточки проектов -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div v-for="p in projs" :key="p.id"
                         @click="router.visit(`/projects/${p.id}`)"
                         class="group relative p-5 bg-white dark:bg-slate-900/90 backdrop-blur-sm border border-slate-200/50 dark:border-slate-800 rounded-2xl hover:border-indigo-300 dark:hover:border-indigo-700 hover:shadow-xl hover:shadow-indigo-100/20 dark:hover:shadow-indigo-900/20 transition-all duration-300 cursor-pointer overflow-hidden">

                        <!-- Градиент при наведении -->
                        <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/5 to-purple-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>

                        <!-- Статус проекта -->
                        <div class="absolute top-3 right-3">
                            <div class="flex items-center gap-1">
                                <span class="inline-block w-2 h-2 rounded-full"
                                      :class="{
                                          'bg-rose-500 animate-pulse': getDeadlineStatus(p.start_date, p.duration_days) === 'overdue',
                                          'bg-amber-500': getDeadlineStatus(p.start_date, p.duration_days) === 'urgent',
                                          'bg-blue-500': getDeadlineStatus(p.start_date, p.duration_days) === 'soon',
                                          'bg-emerald-500': getDeadlineStatus(p.start_date, p.duration_days) === 'normal'
                                      }">
                                </span>
                            </div>
                        </div>

                        <!-- Основная информация -->
                        <div class="relative z-10">
                            <h4 class="text-base font-semibold text-slate-800 dark:text-slate-200 pr-6 line-clamp-2 mb-3 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                {{ p.name }}
                            </h4>

                            <!-- Прогресс бар (если есть) -->
                            <div v-if="p.progress" class="mb-3">
                                <div class="flex items-center justify-between text-xs mb-1">
                                    <span class="text-slate-500">Прогресс</span>
                                    <span class="font-medium" :class="`text-${getProgressColor(p.progress)}-600`">{{ p.progress }}%</span>
                                </div>
                                <div class="w-full h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-500"
                                         :class="`bg-${getProgressColor(p.progress)}-500`"
                                         :style="{ width: p.progress + '%' }">
                                    </div>
                                </div>
                            </div>

                            <!-- Мета информация -->
                            <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500 dark:text-slate-400">
                                <div class="flex items-center gap-1" title="Дата создания">
                                    <span class="text-[10px] opacity-60">📅</span>
                                    <span>{{ formatDate(p.created_at) }}</span>
                                </div>

                                <div class="flex items-center gap-1"
                                     :class="{
                                         'text-rose-600 font-medium': getDeadlineStatus(p.start_date, p.duration_days) === 'overdue',
                                         'text-amber-600 font-medium': getDeadlineStatus(p.start_date, p.duration_days) === 'urgent'
                                     }"
                                     :title="getDeadlineStatus(p.start_date, p.duration_days) === 'overdue' ? 'Просрочено' :
                                             getDeadlineStatus(p.start_date, p.duration_days) === 'urgent' ? 'Срочно' : 'Дедлайн'">
                                    <span class="text-[10px] opacity-60">🏁</span>
                                    <span>{{ calculateDeadline(p.start_date, p.duration_days) }}</span>
                                </div>

                                <div class="ml-auto flex items-center gap-1.5 px-2 py-1 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg text-indigo-600 dark:text-indigo-400 font-medium">
                                    <span class="text-[10px]">📋</span>
                                    <span>{{ p.tasks_count || p.tasks?.length || 0 }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Индикатор перехода -->
                        <div class="absolute bottom-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity">
                            <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Кнопка "Показать все" -->
        <button v-if="hiddenCount > 0"
                @click="isModalOpen = true"
                class="group relative w-full py-4 px-6 overflow-hidden">
            <!-- Фон с градиентом -->
            <div class="absolute inset-0 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-950/30 dark:to-purple-950/30 rounded-2xl border-2 border-dashed border-indigo-200 dark:border-indigo-800 group-hover:border-indigo-400 dark:group-hover:border-indigo-600 transition-colors"></div>

            <!-- Контент -->
            <div class="relative flex items-center justify-center gap-3">
                <span class="text-sm font-medium text-indigo-600 dark:text-indigo-400">
                    Показать все проекты
                </span>
                <span class="px-2 py-0.5 bg-indigo-500 text-white text-xs rounded-full">
                    +{{ hiddenCount }}
                </span>
                <svg class="w-4 h-4 text-indigo-400 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </div>
        </button>

        <!-- МОДАЛЬНОЕ ОКНО -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <!-- Backdrop с анимацией -->
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity animate-fadeIn"
                 @click="isModalOpen = false"></div>

            <!-- Content -->
            <div class="relative bg-white dark:bg-slate-900 rounded-3xl w-full max-w-6xl max-h-[90vh] flex flex-col shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden animate-slideUp">

                <!-- Header с градиентом -->
                <div class="relative p-6 border-b border-slate-100 dark:border-slate-800 bg-gradient-to-r from-indigo-50 to-white dark:from-indigo-950/30 dark:to-slate-900">
                    <div class="flex justify-between items-center">
                        <div>
                            <h2 class="text-2xl font-light tracking-tight text-slate-800 dark:text-white">
                                Все проекты
                                <span class="text-indigo-600 dark:text-indigo-400 font-semibold ml-2">{{ props.projects.length }}</span>
                            </h2>
                            <p class="text-sm text-slate-500 mt-1">
                                {{ stats.overdue }} просрочено
                            </p>
                        </div>

                        <button @click="isModalOpen = false"
                                class="p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition group">
                            <svg class="w-6 h-6 text-slate-400 group-hover:text-slate-600 dark:group-hover:text-slate-300"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Filters и поиск -->
                <div class="p-6 bg-slate-50 dark:bg-slate-900/50 border-b border-slate-100 dark:border-slate-800 space-y-4">
                    <!-- Поиск -->
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text"
                               v-model="searchQuery"
                               placeholder="Поиск проектов..."
                               class="w-full pl-10 pr-4 py-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:border-indigo-300 focus:ring focus:ring-indigo-200/20 transition">
                    </div>

                    <!-- Фильтры и сортировка -->
                    <div class="flex flex-wrap items-center gap-4">
                        <div class="flex gap-2 overflow-x-auto pb-2 custom-scrollbar">
                            <button v-for="name in ['all', ...companyNames]" :key="name"
                                    @click="selectedCompanyFilter = name"
                                    class="px-4 py-2 rounded-xl text-sm font-medium transition-all whitespace-nowrap"
                                    :class="selectedCompanyFilter === name
                                        ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-500/30'
                                        : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-indigo-50 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700'">
                                {{ name === 'all' ? 'Все компании' : name }}
                            </button>
                        </div>

                        <!-- Сортировка -->
                        <select v-model="sortBy"
                                class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200/20 transition">
                            <option value="deadline">По сроку</option>
                            <option value="name">По названию</option>
                            <option value="tasks">По задачам</option>
                        </select>
                    </div>
                </div>

                <!-- List (Scrollable) -->
                <div class="flex-1 p-6 overflow-y-auto custom-scrollbar">
                    <div v-if="Object.keys(groupedAllFiltered).length === 0"
                         class="flex flex-col items-center justify-center py-16 text-slate-400">
                        <span class="text-6xl mb-4 opacity-30">📋</span>
                        <p class="text-lg font-medium">Проекты не найдены</p>
                        <p class="text-sm">Попробуйте изменить параметры поиска</p>
                    </div>

                    <div v-for="(projs, companyName) in groupedAllFiltered" :key="companyName"
                         class="mb-8 last:mb-0">
                        <!-- Липкий заголовок компании -->
                        <div class="sticky top-0 bg-white/90 dark:bg-slate-900/90 backdrop-blur-sm py-3 z-10 mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-1 h-6 bg-gradient-to-b from-indigo-500 to-purple-500 rounded-full"></div>
                                <h4 class="text-sm font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">
                                    {{ companyName }}
                                </h4>
                                <span class="text-xs px-2 py-1 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-full">
                                    {{ projs.length }} {{ projs.length === 1 ? 'проект' : 'проектов' }}
                                </span>
                            </div>
                        </div>

                        <!-- Сетка проектов -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div v-for="p in projs" :key="p.id"
                                 @click="router.visit(`/projects/${p.id}`)"
                                 class="group relative p-5 bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-2xl hover:border-indigo-400 hover:shadow-xl transition-all duration-300 cursor-pointer">

                                <!-- Индикатор статуса -->
                                <div class="absolute top-3 left-3 flex gap-1">
                                    <span class="inline-block w-2 h-2 rounded-full"
                                          :class="{
                                              'bg-rose-500 animate-pulse': getDeadlineStatus(p.start_date, p.duration_days) === 'overdue',
                                              'bg-amber-500': getDeadlineStatus(p.start_date, p.duration_days) === 'urgent',
                                              'bg-emerald-500': getDeadlineStatus(p.start_date, p.duration_days) === 'normal'
                                          }">
                                    </span>
                                </div>

                                <div class="">
                                    <h5 class="font-semibold text-slate-800 dark:text-white mb-3 line-clamp-2 pr-6 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                        {{ p.name }}
                                    </h5>

                                    <div class="flex items-center justify-between text-xs">
<!--                                        <div class="space-y-1">-->
<!--                                            <div class="flex items-center gap-1 text-slate-500">-->
<!--                                                <span>📅</span>-->
<!--                                                <span>{{ formatDate(p.created_at) }}</span>-->
<!--                                            </div>-->
<!--                                            <div class="flex items-center gap-1"-->
<!--                                                 :class="getDeadlineStatus(p.start_date, p.duration_days) === 'overdue' ? 'text-rose-500' :-->
<!--                                                         getDeadlineStatus(p.start_date, p.duration_days) === 'urgent' ? 'text-amber-500' : 'text-slate-500'">-->
<!--                                                <span>🏁</span>-->
<!--                                                <span>{{ calculateDeadline(p.start_date, p.duration_days) }}</span>-->
<!--                                            </div>-->
<!--                                        </div>-->

<!--                                        <div class="flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 dark:bg-indigo-900/30 rounded-xl text-indigo-600 dark:text-indigo-400 font-medium">-->
<!--                                            <span>📋</span>-->
<!--                                            <span>{{ p.tasks_count || p.tasks?.length || 0 }}</span>-->
<!--                                        </div>-->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer с статистикой -->
                <div class="p-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50">
                    <div class="flex items-center justify-between text-sm text-slate-600 dark:text-slate-400">
                        <span>Всего проектов: <span class="font-bold text-indigo-600">{{ filteredProjects.length }}</span></span>
                        <span>Просрочено: <span class="font-bold text-rose-500">{{ stats.overdue }}</span></span>

                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Анимации */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fadeIn {
    animation: fadeIn 0.3s ease-out;
}

.animate-slideUp {
    animation: slideUp 0.4s ease-out;
}

/* Кастомный скроллбар */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
    height: 6px;
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

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

.dark .custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #64748b;
}

/* Анимация появления карточек */
.grid > * {
    animation: cardAppear 0.5s ease-out forwards;
    opacity: 0;
}

@keyframes cardAppear {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Задержки для карточек */
.grid > *:nth-child(1) { animation-delay: 0.1s; }
.grid > *:nth-child(2) { animation-delay: 0.15s; }
.grid > *:nth-child(3) { animation-delay: 0.2s; }
.grid > *:nth-child(4) { animation-delay: 0.25s; }
.grid > *:nth-child(5) { animation-delay: 0.3s; }
.grid > *:nth-child(6) { animation-delay: 0.35s; }

/* Ограничение текста */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
