<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
    subtasks: Array,
    loading: Boolean,
    canCreate: Boolean
})

defineEmits(['create'])

const showAll = ref(false)
const filterStatus = ref('all') // all, active, completed
const sortBy = ref('deadline') // deadline, progress, title

// Фильтрация и сортировка
const filteredSubtasks = computed(() => {
    if (!props.subtasks) return []

    let filtered = [...props.subtasks]

    // Фильтр по статусу
    if (filterStatus.value === 'active') {
        filtered = filtered.filter(s => s.progress < 100)
    } else if (filterStatus.value === 'completed') {
        filtered = filtered.filter(s => s.progress === 100)
    }

    // Сортировка
    filtered.sort((a, b) => {
        switch(sortBy.value) {
            case 'deadline':
                return new Date(a.due_date) - new Date(b.due_date)
            case 'progress':
                return b.progress - a.progress
            case 'title':
                return a.title.localeCompare(b.title)
            default:
                return 0
        }
    })

    return filtered
})

const visibleSubtasks = computed(() => {
    if (showAll.value) return filteredSubtasks.value
    return filteredSubtasks.value.slice(0, 4)
})

const hasMore = computed(() => filteredSubtasks.value.length > 4)

const stats = computed(() => ({
    total: props.subtasks?.length || 0,
    completed: props.subtasks?.filter(s => s.progress === 100).length || 0,
    active: props.subtasks?.filter(s => s.progress < 100).length || 0
}))

// Хелперы
const getProgressColor = (progress) => {
    if (progress === 100) return 'bg-emerald-500'
    if (progress >= 75) return 'from-emerald-500 to-teal-500'
    if (progress >= 50) return 'from-blue-500 to-indigo-500'
    if (progress >= 25) return 'from-amber-500 to-orange-500'
    return 'from-rose-500 to-pink-500'
}

const getStatusBadge = (progress) => {
    if (progress === 100) return { text: 'Завершена', icon: '✅', class: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' }
    if (progress > 0) return { text: 'В работе', icon: '⚡', class: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' }
    return { text: 'Новая', icon: '🆕', class: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300' }
}

const getInitials = (name) => {
    if (!name) return '?'
    return name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase()
}

const formatDate = (dateStr) => {
    if (!dateStr) return '—'
    const date = new Date(dateStr)
    const today = new Date()
    const tomorrow = new Date(today)
    tomorrow.setDate(tomorrow.getDate() + 1)

    if (date.toDateString() === today.toDateString()) return 'Сегодня'
    if (date.toDateString() === tomorrow.toDateString()) return 'Завтра'

    return date.toLocaleDateString('ru-RU', { day: 'numeric', month: 'short' })
}

const isOverdue = (dateStr) => {
    if (!dateStr) return false
    return new Date(dateStr) < new Date() && new Date(dateStr).toDateString() !== new Date().toDateString()
}
</script>

<template>
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-2xl transition-all duration-300">

        <!-- Декоративная полоса сверху -->
        <div class="h-1.5 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500"></div>

        <!-- Заголовок и статистика -->
        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white shadow-lg">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-slate-800 dark:text-white">Подзадачи</h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            {{ stats.completed }} из {{ stats.total }} завершено
                        </p>
                    </div>
                </div>

                <!-- Кнопка создания -->
                <button v-if="canCreate"
                        @click="$emit('create')"
                        class="group relative px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-medium shadow-lg shadow-indigo-500/30 hover:shadow-xl hover:scale-105 transition-all overflow-hidden">
                    <span class="relative flex items-center gap-2">
                        <svg class="w-4 h-4 group-hover:rotate-90 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Новая подзадача
                    </span>
                </button>
            </div>
        </div>

        <!-- Фильтры и сортировка -->
        <div v-if="subtasks?.length" class="px-6 py-3 border-b border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex gap-1 p-1 bg-white dark:bg-slate-700 rounded-lg border border-slate-200 dark:border-slate-600">
                    <button @click="filterStatus = 'all'"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all"
                            :class="filterStatus === 'all' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-600'">
                        Все
                    </button>
                    <button @click="filterStatus = 'active'"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all"
                            :class="filterStatus === 'active' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-600'">
                        Активные
                    </button>
                    <button @click="filterStatus = 'completed'"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all"
                            :class="filterStatus === 'completed' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-600'">
                        Завершенные
                    </button>
                </div>

                <select v-model="sortBy"
                        class="px-3 py-1.5 rounded-lg bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-xs focus:border-indigo-300 focus:ring focus:ring-indigo-200/20 transition">
                    <option value="deadline">По сроку</option>
                    <option value="progress">По прогрессу</option>
                    <option value="title">По названию</option>
                </select>
            </div>
        </div>

        <!-- Пустое состояние -->
        <div v-if="!filteredSubtasks.length"
             class="flex flex-col items-center justify-center py-12 px-6 text-center">
            <div class="w-20 h-20 mb-4 rounded-full bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-700 dark:to-slate-600 flex items-center justify-center">
                <svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
            </div>
            <h4 class="text-lg font-semibold text-slate-700 dark:text-slate-300 mb-2">Нет подзадач</h4>
            <p class="text-sm text-slate-500 mb-4">Создайте первую подзадачу для отслеживания прогресса</p>
            <button v-if="canCreate" @click="$emit('create')"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-xl text-sm font-medium hover:bg-indigo-700 transition-all shadow-md">
                + Создать подзадачу
            </button>
        </div>

        <!-- Сетка подзадач -->
        <div v-else class="p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div v-for="s in visibleSubtasks" :key="s.id"
                     @click="router.visit(`/subtasks/${s.id}`)"
                     class="group relative bg-white dark:bg-slate-700 rounded-xl border-2 transition-all duration-300 overflow-hidden hover:shadow-xl hover:-translate-y-1 cursor-pointer"
                     :class="s.progress === 100
                         ? 'border-emerald-400 dark:border-emerald-600 bg-gradient-to-br from-emerald-50/50 to-transparent dark:from-emerald-900/10'
                         : 'border-slate-200 dark:border-slate-600 hover:border-indigo-300 dark:hover:border-indigo-600'">

                    <!-- Декоративная полоса прогресса -->
                    <div class="absolute top-0 left-0 w-full h-1"
                         :class="{
                             'bg-gradient-to-r from-emerald-500 to-teal-500': s.progress === 100,
                             'bg-gradient-to-r from-blue-500 to-indigo-500': s.progress >= 50 && s.progress < 100,
                             'bg-gradient-to-r from-amber-500 to-orange-500': s.progress < 50
                         }">
                    </div>

                    <!-- Индикатор просрочки -->
                    <div v-if="isOverdue(s.due_date) && s.progress < 100"
                         class="absolute top-2 right-2">
                        <span class="flex items-center gap-1 px-2 py-1 bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-300 rounded-lg text-[8px] font-bold">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                            ПРОСРОЧЕНО
                        </span>
                    </div>

                    <div class="p-4">
                        <!-- Заголовок -->
                        <div class="flex items-start justify-between gap-2 mb-3">
                            <h4 class="text-sm font-semibold text-slate-800 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors line-clamp-2 flex-1">
                                {{ s.title }}
                            </h4>

                            <!-- Статус бейдж -->
                            <span class="shrink-0 px-2 py-1 rounded-lg text-[8px] font-bold border"
                                  :class="getStatusBadge(s.progress).class">
                                {{ getStatusBadge(s.progress).icon }} {{ getStatusBadge(s.progress).text }}
                            </span>
                        </div>

                        <!-- Прогресс бар -->
                        <div class="mb-4">
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="text-slate-500">Прогресс</span>
                                <span class="font-semibold" :class="{
                                    'text-emerald-600': s.progress === 100,
                                    'text-blue-600': s.progress >= 50 && s.progress < 100,
                                    'text-amber-600': s.progress < 50
                                }">{{ s.progress }}%</span>
                            </div>
                            <div class="h-2 w-full bg-slate-100 dark:bg-slate-600 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500"
                                     :class="getProgressColor(s.progress)"
                                     :style="{ width: s.progress + '%' }">
                                </div>
                            </div>
                        </div>

                        <!-- Нижняя часть: исполнители и дата -->
                        <div class="flex items-center justify-between pt-3 border-t border-slate-100 dark:border-slate-600">
                            <!-- Аватарки исполнителей -->
                            <div class="flex items-center gap-2">
                                <div class="flex -space-x-2 overflow-hidden">
                                    <template v-if="s.executors?.length">
                                        <div v-for="exe in s.executors.slice(0, 3)" :key="exe.id"
                                             class="w-6 h-6 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white text-[8px] font-bold border-2 border-white dark:border-slate-700 shadow-sm"
                                             :title="exe.name">
                                            {{ getInitials(exe.name) }}
                                        </div>
                                        <div v-if="s.executors.length > 3"
                                             class="w-6 h-6 rounded-full bg-slate-200 dark:bg-slate-600 flex items-center justify-center text-[8px] font-bold text-slate-600 dark:text-slate-400 border-2 border-white dark:border-slate-700">
                                            +{{ s.executors.length - 3 }}
                                        </div>
                                    </template>
                                    <span v-else class="text-[10px] text-slate-400 italic ml-1">Нет исполнителей</span>
                                </div>
                            </div>

                            <!-- Дата с иконкой -->
                            <div class="flex items-center gap-1 text-[10px] font-medium"
                                 :class="isOverdue(s.due_date) && s.progress < 100 ? 'text-rose-600' : 'text-slate-500'">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span>{{ formatDate(s.due_date) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Кнопка "Показать еще" -->
            <div v-if="hasMore" class="flex justify-center mt-6 pt-4 border-t border-slate-100 dark:border-slate-700">
                <button @click="showAll = !showAll"
                        class="group px-6 py-2.5 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-950/30 dark:to-purple-950/30 rounded-xl text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:shadow-md transition-all flex items-center gap-2">
                    <span>{{ showAll ? 'Свернуть' : `Показать еще ${filteredSubtasks.length - 4}` }}</span>
                    <svg class="w-4 h-4 transition-transform duration-300"
                         :class="{ 'rotate-180': showAll }"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Анимации */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.grid > * {
    animation: slideIn 0.3s ease-out forwards;
}

.grid > *:nth-child(1) { animation-delay: 0.05s; }
.grid > *:nth-child(2) { animation-delay: 0.1s; }
.grid > *:nth-child(3) { animation-delay: 0.15s; }
.grid > *:nth-child(4) { animation-delay: 0.2s; }

/* Ограничение текста */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Пульсация для просрочки */
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Адаптивность */
@media (max-width: 640px) {
    .grid {
        gap: 12px;
    }
}
</style>
