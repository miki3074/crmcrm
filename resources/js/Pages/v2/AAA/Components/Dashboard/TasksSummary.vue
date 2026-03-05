<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
    tasks: Array,
    title: {
        type: String,
        default: 'Мои задачи'
    },
    variant: {
        type: String,
        default: 'default' // 'default' | 'danger' | 'success'
    }
})

const isModalOpen = ref(false)
const LIMIT = 6

const displayedTasks = computed(() => props.tasks?.slice(0, LIMIT) || [])
const hasHiddenTasks = computed(() => (props.tasks?.length || 0) > LIMIT)
const hiddenCount = computed(() => (props.tasks?.length || 0) - LIMIT)

const openTask = (id) => {
    router.visit(`/tasks/${id}`)
}

// Цветовые схемы для разных вариантов
const variantStyles = {
    default: {
        header: 'text-slate-800 dark:text-slate-100',
        badge: 'bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-300',
        button: 'text-indigo-500 hover:text-indigo-600 dark:text-indigo-400',
        progress: {
            bg: 'bg-slate-100 dark:bg-slate-700',
            fill: 'bg-gradient-to-r from-indigo-500 to-indigo-400'
        }
    },
    danger: {
        header: 'text-rose-600 dark:text-rose-400',
        badge: 'bg-rose-100 dark:bg-rose-900/50 text-rose-600 dark:text-rose-400',
        button: 'text-rose-500 hover:text-rose-600',
        progress: {
            bg: 'bg-slate-100 dark:bg-slate-700',
            fill: 'bg-gradient-to-r from-rose-500 to-rose-400'
        }
    },
    success: {
        header: 'text-emerald-600 dark:text-emerald-400',
        badge: 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600 dark:text-emerald-400',
        button: 'text-emerald-500 hover:text-emerald-600',
        progress: {
            bg: 'bg-slate-100 dark:bg-slate-700',
            fill: 'bg-gradient-to-r from-emerald-500 to-emerald-400'
        }
    }
}

const currentStyle = computed(() => variantStyles[props.variant] || variantStyles.default)

// Приоритет задачи (для визуального акцента)
const getPriorityColor = (task) => {
    if (task.priority === 'high') return 'border-l-4 border-l-rose-500'
    if (task.priority === 'medium') return 'border-l-4 border-l-amber-500'
    if (task.priority === 'low') return 'border-l-4 border-l-emerald-500'
    return ''
}

// Форматирование даты
const formatDate = (date) => {
    if (!date) return '—'
    const d = new Date(date)
    const today = new Date()
    const tomorrow = new Date(today)
    tomorrow.setDate(tomorrow.getDate() + 1)

    if (d.toDateString() === today.toDateString()) return 'Сегодня'
    if (d.toDateString() === tomorrow.toDateString()) return 'Завтра'

    return d.toLocaleDateString('ru-RU', { day: 'numeric', month: 'short' })
}

// Цвет даты в зависимости от срока
const getDueDateColor = (dueDate) => {
    if (!dueDate) return 'text-slate-400'
    const d = new Date(dueDate)
    const today = new Date()
    if (d < today) return 'text-rose-500 font-medium'
    return 'text-slate-400'
}
</script>

<template>
    <div class="space-y-6">
        <!-- Заголовок с декоративной линией -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="h-8 w-1 bg-gradient-to-b rounded-full"
                     :class="{
                         'from-indigo-500 to-indigo-300': variant === 'default',
                         'from-rose-500 to-rose-300': variant === 'danger',
                         'from-emerald-500 to-emerald-300': variant === 'success'
                     }">
                </div>
                <h3 :class="['text-lg font-semibold', currentStyle.header]">
                    {{ title }}
                </h3>
                <span class="px-2.5 py-1 text-xs font-medium rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500">
                    {{ tasks?.length || 0 }}
                </span>
            </div>

            <button v-if="hasHiddenTasks"
                    @click="isModalOpen = true"
                    :class="['text-sm font-medium transition-all hover:underline flex items-center gap-1', currentStyle.button]">
                <span>Все задачи</span>
                <span class="text-lg">→</span>
            </button>
        </div>

        <!-- Сетка задач -->
        <div v-if="tasks?.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="task in displayedTasks"
                 :key="task.id"
                 @click="openTask(task.id)"
                 :class="[
                     'group relative bg-white dark:bg-slate-800/90 rounded-xl border border-slate-100 dark:border-slate-700/60 hover:border-indigo-200 dark:hover:border-indigo-700/50 hover:shadow-lg transition-all duration-300 cursor-pointer overflow-hidden',
                     getPriorityColor(task)
                 ]">

                <!-- Верхний градиент при ховере -->
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-indigo-500 to-indigo-300 opacity-0 group-hover:opacity-100 transition-opacity"></div>

                <div class="p-4">
                    <!-- Верхняя строка с проектом и датой -->
                    <div class="flex items-center justify-between mb-3">
                        <span class="inline-flex items-center gap-1 px-2 py-1 text-[10px] font-medium rounded-md bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-400"></span>
                            {{ task.project?.name || 'Без проекта' }}
                        </span>
                        <span :class="['text-[10px] font-medium', getDueDateColor(task.due_date)]">
                            {{ formatDate(task.due_date) }}
                        </span>
                    </div>

                    <!-- Название задачи -->
                    <h4 class="font-medium text-slate-800 dark:text-slate-200 leading-snug line-clamp-2 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors mb-4 min-h-[2.5rem]">
                        {{ task.title }}
                    </h4>

                    <!-- Прогресс бар -->
                    <div class="space-y-1.5">
                        <div class="flex justify-between text-[10px] font-medium">
                            <span class="text-slate-400">Прогресс</span>
                            <span class="text-slate-600 dark:text-slate-300">{{ task.progress || 0 }}%</span>
                        </div>
                        <div class="h-1.5 w-full bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500"
                                 :class="currentStyle.progress.fill"
                                 :style="{ width: (task.progress || 0) + '%' }"></div>
                        </div>
                    </div>

                    <!-- Мета информация -->
                    <div v-if="task.subtasks_count" class="mt-3 flex items-center gap-2 text-[10px] text-slate-400">
                        <span>📋 {{ task.subtasks_count }} подзадач</span>
                        <span class="w-1 h-1 bg-slate-300 rounded-full"></span>
                        <span>👤 {{ task.assignee?.name || 'Не назначен' }}</span>
                    </div>
                </div>
            </div>

            <!-- Карточка "Показать все" -->
            <button v-if="hasHiddenTasks"
                    @click="isModalOpen = true"
                    class="flex flex-col items-center justify-center p-6 rounded-xl border-2 border-dashed border-slate-200 dark:border-slate-700 hover:border-indigo-300 dark:hover:border-indigo-700 hover:bg-indigo-50/50 dark:hover:bg-indigo-900/10 transition-all group">
                <span class="text-3xl font-light text-slate-300 group-hover:text-indigo-400 mb-2">+{{ hiddenCount }}</span>
                <span class="text-xs font-medium text-slate-400 group-hover:text-indigo-500">Показать все задачи</span>
            </button>
        </div>

        <!-- Пустое состояние -->
        <div v-else class="py-16 flex flex-col items-center justify-center text-center">
            <div class="w-20 h-20 mb-4 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                <span class="text-3xl filter grayscale opacity-50">✅</span>
            </div>
            <p class="text-sm text-slate-400 mb-2">Задач пока нет</p>
            <p class="text-xs text-slate-300">Создайте новую задачу в проекте</p>
        </div>
    </div>

    <!-- Модальное окно со всеми задачами -->
    <Teleport to="body">
        <div v-if="isModalOpen"
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             @click="isModalOpen = false">

            <!-- Затемненный фон -->
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>

            <!-- Модалка -->
            <div class="relative w-full max-w-6xl max-h-[90vh] bg-white dark:bg-slate-900 rounded-2xl shadow-2xl overflow-hidden"
                 @click.stop>

                <!-- Шапка -->
                <div class="flex items-center justify-between p-6 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-3">
                        <div class="p-2 rounded-xl bg-indigo-100 dark:bg-indigo-900/30">
                            <span class="text-xl">✅</span>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-slate-900 dark:text-white">Все задачи</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">{{ title }}</p>
                        </div>
                    </div>
                    <button @click="isModalOpen = false"
                            class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Список задач -->
                <div class="overflow-y-auto p-6 max-h-[calc(90vh-120px)]">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        <div v-for="task in tasks"
                             :key="task.id"
                             @click="openTask(task.id); isModalOpen = false"
                             class="group p-4 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl hover:border-indigo-300 hover:shadow-md transition-all cursor-pointer">

                            <div class="flex items-start justify-between mb-2">
                                <span class="text-xs font-medium px-2 py-1 bg-white dark:bg-slate-700 rounded-md text-slate-500">
                                    {{ task.project?.name || 'Без проекта' }}
                                </span>
                                <span class="text-xs text-slate-400"> {{ formatDate(task.due_date) }}</span>
                            </div>

                            <h4 class="font-medium text-slate-800 dark:text-slate-200 text-sm mb-3 group-hover:text-indigo-600 line-clamp-2">
                                {{ task.title }}
                            </h4>

                            <div class="h-1 w-full bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-indigo-400"
                                     :style="{ width: (task.progress || 0) + '%' }"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all 0.2s ease;
}

.fade-slide-enter-from {
    opacity: 0;
    transform: translateY(10px);
}

.fade-slide-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}
</style>
