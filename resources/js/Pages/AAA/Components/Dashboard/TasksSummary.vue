<!-- Partials/TasksSummary.vue -->
<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
    tasks: Array,
    title: String,
    variant: { type: String, default: 'default' } // 'default' | 'danger'
})

// Состояние модального окна
const isModalOpen = ref(false)

// Логика отображения (максимум 6 задач для превью)
const LIMIT = 6
const displayedTasks = computed(() => props.tasks?.slice(0, LIMIT) || [])
const hasHiddenTasks = computed(() => (props.tasks?.length || 0) > LIMIT)
const hiddenCount = computed(() => (props.tasks?.length || 0) - LIMIT)

const openTask = (id) => {
    router.visit(`/tasks/${id}`)
}

// Функция для определения цвета прогресс-бара
const getProgressColor = (progress) => {
    if (progress >= 100) return 'bg-emerald-500';
    if (progress > 50) return 'bg-indigo-500';
    return 'bg-amber-500';
}
</script>

<template>
    <div :class="['relative p-6 rounded-3xl border transition-all shadow-sm bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl',
                 variant === 'danger' ? 'border-rose-200 dark:border-rose-900/30' : 'border-slate-200 dark:border-slate-800']">

        <!-- Заголовок блока -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <h3 :class="['text-xl font-bold tracking-tight', variant === 'danger' ? 'text-rose-600' : 'text-slate-800 dark:text-slate-100']">
                    {{ title }}
                </h3>
                <span class="px-2.5 py-0.5 text-xs font-extrabold rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 border border-slate-200 dark:border-slate-700">
                    {{ tasks?.length || 0 }}
                </span>
            </div>

            <!-- Кнопка "Показать все" (маленькая версия сверху) -->
            <button v-if="hasHiddenTasks"
                    @click="isModalOpen = true"
                    class="text-xs font-semibold text-indigo-500 hover:text-indigo-600 dark:text-indigo-400 transition hover:underline">
                См. все
            </button>
        </div>

        <!-- Сетка задач (3x2) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Карточки задач -->
            <div v-for="task in displayedTasks" :key="task.id"
                 @click="openTask(task.id)"
                 class="group relative flex flex-col justify-between p-4 bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700/60 rounded-2xl hover:shadow-lg hover:-translate-y-1 transition-all duration-300 cursor-pointer h-full">

                <div>
                    <div class="flex justify-between items-start mb-2">
                         <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 bg-slate-50 dark:bg-slate-700/50 px-2 py-0.5 rounded-md truncate max-w-[120px]">
                            {{ task.project?.name || 'Без проекта' }}
                        </span>
                        <span v-if="task.due_date" class="text-[10px] font-medium text-slate-400 whitespace-nowrap">
                            {{ task.due_date }}
                        </span>
                    </div>

                    <h4 class="font-bold text-slate-700 dark:text-slate-200 leading-snug line-clamp-2 group-hover:text-indigo-500 transition-colors mb-4">
                        {{ task.title }}
                    </h4>
                </div>

                <!-- Прогресс бар -->
                <div class="mt-auto">
                    <div class="flex justify-between text-[10px] mb-1.5 font-bold text-slate-400">
                        <span>Прогресс</span>
                        <span>{{ task.progress || 0 }}%</span>
                    </div>
                    <div class="h-1.5 w-full bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-500 shadow-[0_0_10px_rgba(99,102,241,0.4)]"
                             :class="getProgressColor(task.progress)"
                             :style="{ width: (task.progress || 0) + '%' }"></div>
                    </div>
                </div>
            </div>

            <!-- Блок "Показать еще", если задач > 6 -->
            <button v-if="hasHiddenTasks"
                    @click="isModalOpen = true"
                    class="flex flex-col items-center justify-center p-4 rounded-2xl border-2 border-dashed border-slate-200 dark:border-slate-700 text-slate-400 hover:border-indigo-300 hover:text-indigo-500 hover:bg-indigo-50/50 dark:hover:bg-slate-800 transition-all cursor-pointer h-full min-h-[140px]">
                <span class="text-2xl font-bold mb-1">+{{ hiddenCount }}</span>
                <span class="text-xs font-semibold uppercase tracking-wide">Показать все</span>
            </button>
        </div>

        <!-- Пустое состояние -->
        <div v-if="!tasks?.length" class="py-12 flex flex-col items-center justify-center text-slate-400">
            <svg class="w-12 h-12 mb-3 text-slate-200 dark:text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
            <p class="text-sm italic">Задач пока нет</p>
        </div>

        <!-- МОДАЛЬНОЕ ОКНО (Все задачи) -->
        <Teleport to="body">
            <div v-if="isModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <!-- Фон (Backdrop) -->
                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
                     @click="isModalOpen = false"></div>

                <!-- Контент модального окна -->
                <div class="relative w-full max-w-5xl max-h-[90vh] flex flex-col bg-white dark:bg-slate-900 rounded-3xl shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-200">

                    <!-- Шапка модального окна -->
                    <div class="flex items-center justify-between p-6 border-b border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900 z-10">
                        <h2 class="text-2xl font-bold text-slate-800 dark:text-white">
                            Все задачи: {{ title }}
                        </h2>
                        <button @click="isModalOpen = false" class="p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition text-slate-500">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <!-- Список с прокруткой -->
                    <div class="overflow-y-auto p-6 bg-slate-50/50 dark:bg-slate-900/50">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                            <div v-for="task in tasks" :key="'modal-'+task.id"
                                 @click="openTask(task.id)"
                                 class="group flex flex-col justify-between p-4 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl hover:border-indigo-300 dark:hover:border-indigo-700 hover:shadow-md transition-all cursor-pointer h-full">
                                <div>
                                    <div class="flex justify-between items-start mb-2">
                                        <span class="text-[10px] font-bold uppercase text-slate-400 truncate max-w-[120px]">
                                            {{ task.project?.name }}
                                        </span>
                                        <span v-if="task.due_date" class="text-[10px] text-slate-400">{{ task.due_date }}</span>
                                    </div>
                                    <h4 class="font-bold text-slate-700 dark:text-slate-200 leading-snug mb-3 group-hover:text-indigo-600 transition">{{ task.title }}</h4>
                                </div>

                                <div class="mt-2">
                                    <div class="h-1.5 w-full bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full"
                                             :class="getProgressColor(task.progress)"
                                             :style="{ width: (task.progress || 0) + '%' }"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Футер модального окна -->
                    <div class="p-4 border-t border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900 text-center">
                        <button @click="isModalOpen = false" class="px-6 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold rounded-xl transition">
                            Закрыть
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
