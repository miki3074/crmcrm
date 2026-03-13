<script setup>
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
    tasks: { type: Array, default: () => [] },
    title: String,
    variant: { type: String, default: 'default' }, // 'default' | 'danger'
    showFilters: { type: Boolean, default: false } // Показывать ли переключатели (В работе / Завершенные)
})

// Если фильтры включены, по умолчанию показываем "В работе" (active), иначе "Все" (all)
const filter = ref(props.showFilters ? 'active' : 'all')

// Состояние модального окна
const isModalOpen = ref(false)

// Распределение задач
const allTasks = computed(() => props.tasks || [])
const activeTasks = computed(() => allTasks.value.filter(t => (t.progress || 0) < 100))
const completedTasks = computed(() => allTasks.value.filter(t => (t.progress || 0) >= 100))

// Список для отображения
const currentTasks = computed(() => {
    if (filter.value === 'active') return activeTasks.value
    if (filter.value === 'completed') return completedTasks.value
    return allTasks.value
})

const LIMIT = 6
const displayedTasks = computed(() => currentTasks.value.slice(0, LIMIT))
const hasHiddenTasks = computed(() => currentTasks.value.length > LIMIT)
const hiddenCount = computed(() => currentTasks.value.length - LIMIT)

const openTask = (id) => router.visit(`/tasks/${id}`)

const getProgressColor = (progress) => {
    if (progress >= 100) return 'bg-emerald-500';
    if (progress > 50) return 'bg-indigo-500';
    return 'bg-amber-500';
}
</script>

<template>
    <div :class="['relative p-6 rounded-3xl border transition-all shadow-sm bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl',
                 variant === 'danger' ? 'border-rose-200 dark:border-rose-900/30 shadow-rose-100/50' : 'border-slate-200 dark:border-slate-800']">

        <!-- Заголовок и фильтры -->
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
            <div class="flex items-center gap-3">
                <h3 :class="['text-xl font-bold tracking-tight', variant === 'danger' ? 'text-rose-600' : 'text-slate-800 dark:text-slate-100']">
                    {{ title }}
                </h3>
                <span class="px-2.5 py-0.5 text-xs font-extrabold rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 border border-slate-200 dark:border-slate-700">
                    {{ currentTasks.length }}
                </span>
            </div>

            <!-- Переключатель (Pills) - Показывается только если showFilters="true" -->
            <div v-if="showFilters" class="flex p-1 bg-slate-100/50 dark:bg-slate-800/50 rounded-xl w-fit border border-slate-200/50 dark:border-slate-700">
                <button @click="filter = 'active'"
                        :class="['px-3 py-1.5 text-xs font-bold rounded-lg transition-all',
                        filter === 'active' ? 'bg-white dark:bg-slate-700 shadow-sm text-indigo-600 dark:text-indigo-400' : 'text-slate-500 hover:text-slate-700']">
                    В работе ({{ activeTasks.length }})
                </button>
                <button @click="filter = 'completed'"
                        :class="['px-3 py-1.5 text-xs font-bold rounded-lg transition-all',
                        filter === 'completed' ? 'bg-white dark:bg-slate-700 shadow-sm text-emerald-600 dark:text-emerald-400' : 'text-slate-500 hover:text-slate-700']">
                   Не завершено ({{ completedTasks.length }})
                </button>
                <button @click="filter = 'all'"
                        :class="['px-3 py-1.5 text-xs font-bold rounded-lg transition-all',
                        filter === 'all' ? 'bg-white dark:bg-slate-700 shadow-sm text-slate-700 dark:text-slate-200' : 'text-slate-500 hover:text-slate-700']">
                    Все
                </button>
            </div>
        </div>

        <!-- Сетка задач -->
        <div v-if="currentTasks.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="task in displayedTasks" :key="task.id" @click="openTask(task.id)"
                 :class="['group flex flex-col justify-between p-4 bg-white dark:bg-slate-800 border rounded-2xl hover:shadow-lg hover:-translate-y-1 transition-all duration-300 cursor-pointer h-full',
                          task.progress >= 100 ? 'border-emerald-100 dark:border-emerald-900/20' : 'border-slate-100 dark:border-slate-700/60']">

                <div>
                    <div class="flex justify-between items-start mb-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 bg-slate-50 dark:bg-slate-700/50 px-2 py-0.5 rounded-md truncate max-w-[120px]">
                            {{ task.project?.name || 'Без проекта' }}
                        </span>
                        <div v-if="task.progress >= 100" class="text-emerald-500">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        </div>
                        <span v-else class="text-[10px] font-medium text-slate-400">{{ task.due_date || 'Нет срока' }}</span>
                    </div>
                    <h4 :class="['font-bold text-sm leading-snug line-clamp-2 mb-4 transition-colors',
                                 task.progress >= 100 ? 'text-slate-400 line-through' : 'text-slate-700 dark:text-slate-200 group-hover:text-indigo-500']">
                        {{ task.title }}
                    </h4>
                </div>

                <div class="mt-auto pt-2">
                    <div class="flex justify-between text-[10px] mb-1.5 font-bold text-slate-400">
                        <span>{{ task.progress >= 100 ? 'Выполнено' : 'Прогресс' }}</span>
                        <span>{{ task.progress || 0 }}%</span>
                    </div>
                    <div class="h-1.5 w-full bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-500" :class="getProgressColor(task.progress)" :style="{ width: (task.progress || 0) + '%' }"></div>
                    </div>
                </div>
            </div>

            <button v-if="hasHiddenTasks" @click="isModalOpen = true"
                    class="flex flex-col items-center justify-center p-4 rounded-2xl border-2 border-dashed border-slate-200 dark:border-slate-700 text-slate-400 hover:border-indigo-300 hover:text-indigo-500 transition-all h-full min-h-[140px]">
                <span class="text-2xl font-bold">+{{ hiddenCount }}</span>
                <span class="text-xs font-semibold uppercase">Показать все</span>
            </button>
        </div>

        <!-- Пустое состояние -->
        <div v-else class="py-12 flex flex-col items-center justify-center text-slate-400 border-2 border-dashed border-slate-100 dark:border-slate-800 rounded-2xl">
            <p class="text-sm italic">Задач не найдено</p>
        </div>

        <!-- Модалка (упрощенно) -->
        <Teleport to="body">
            <div v-if="isModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
                <div class="bg-white dark:bg-slate-900 w-full max-w-5xl max-h-[90vh] rounded-3xl overflow-hidden flex flex-col shadow-2xl">
                    <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center">
                        <h2 class="text-xl font-bold">Все задачи: {{ title }}</h2>
                        <button @click="isModalOpen = false" class="text-slate-400 hover:text-slate-600">Закрыть</button>
                    </div>
                    <div class="overflow-y-auto p-6 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 bg-slate-50/50 dark:bg-slate-900/50">
                        <!-- Тут те же карточки, что и в сетке -->
                        <div v-for="task in currentTasks" :key="'m-'+task.id" @click="openTask(task.id)" class="p-4 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 cursor-pointer">
                            <h4 class="font-bold text-sm" :class="{'line-through text-slate-400': task.progress >= 100}">{{ task.title }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
