<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
    subtasks: {
        type: [Array, Object],
        default: () => []
    }
})

const groupedSubtasks = computed(() => {
    const data = props.subtasks
    if (!data) return {}

    if (Array.isArray(data)) {
        return data.reduce((acc, st) => {
            const companyName = st.task?.project?.company?.name || 'Без компании'
            const projectName = st.task?.project?.name || 'Без проекта'
            const parentTaskTitle = st.task?.title || 'Без задачи'

            if (!acc[companyName]) acc[companyName] = {}
            if (!acc[companyName][projectName]) acc[companyName][projectName] = {}
            if (!acc[companyName][projectName][parentTaskTitle]) acc[companyName][projectName][parentTaskTitle] = []

            if (!acc[companyName][projectName][parentTaskTitle].some(s => s.id === st.id)) {
                acc[companyName][projectName][parentTaskTitle].push(st)
            }

            return acc
        }, {})
    }
    return data
})

const hasSubtasks = computed(() => {
    return Array.isArray(props.subtasks) ? props.subtasks.length > 0 : Object.keys(props.subtasks).length > 0
})

// Вспомогательные функции для статусов
const getPriorityColor = (priority) => {
    const colors = {
        high: 'rose',
        medium: 'amber',
        low: 'emerald'
    }
    return colors[priority] || 'slate'
}

const getStatusColor = (status) => {
    const colors = {
        completed: 'emerald',
        'in-progress': 'blue',
        pending: 'amber',
        cancelled: 'rose'
    }
    return colors[status] || 'slate'
}
</script>

<template>
    <div class="space-y-6">
        <!-- Заголовок с счетчиком и градиентом -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-1 h-6 bg-gradient-to-b from-indigo-500 to-purple-500 rounded-full"></div>
                <h3 class="text-sm font-medium text-slate-700 dark:text-slate-300 tracking-wide">
                    ВСЕ ПОДЗАДАЧИ
                </h3>
            </div>
            <span v-if="Array.isArray(subtasks) && subtasks.length > 0"
                  class="px-3 py-1 text-xs font-mono bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-full border border-indigo-100 dark:border-indigo-800">
                {{ subtasks.length }} шт.
            </span>
        </div>

        <!-- Пустое состояние -->
        <div v-if="!hasSubtasks"
             class="relative py-16 px-6 text-center bg-gradient-to-br from-slate-50 to-white dark:from-slate-900/50 dark:to-slate-900/30 rounded-2xl border-2 border-dashed border-slate-200 dark:border-slate-800">
            <div class="text-5xl mb-4 opacity-30">📋</div>
            <p class="text-slate-400 dark:text-slate-500 text-sm font-medium">
                Здесь пока нет подзадач
            </p>
            <p class="text-xs text-slate-400 dark:text-slate-600 mt-1">
                Подзадачи появятся после создания задач
            </p>
        </div>

        <!-- Сетка подзадач -->
        <div v-else class="space-y-8">
            <div v-for="(projects, companyName) in groupedSubtasks"
                 :key="companyName"
                 class="relative">

                <!-- Заголовок компании с градиентом -->
<!--                <div class="flex items-center gap-2 mb-4 sticky top-0 bg-white/80 dark:bg-slate-900/80 backdrop-blur-sm z-10 py-2">-->
<!--                    <div class="w-1 h-5 bg-gradient-to-b from-indigo-400 to-indigo-600 rounded-full"></div>-->
<!--                    <span class="text-xs font-bold tracking-wider text-slate-400 uppercase">-->
<!--                        {{ companyName }}-->
<!--                    </span>-->
<!--                </div>-->

                <!-- Проекты внутри компании -->
                <div class="space-y-6 pl-4">
                    <div v-for="(tasks, projectName) in projects"
                         :key="projectName"
                         class="relative">

                        <!-- Название проекта -->
<!--                        <div class="flex items-center gap-2 mb-3">-->
<!--                            <span class="text-xs px-2 py-1 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-950/30 dark:to-purple-950/30 text-indigo-700 dark:text-indigo-300 rounded-md border border-indigo-100 dark:border-indigo-900/50 font-medium">-->
<!--                                {{ projectName }}-->
<!--                            </span>-->
<!--                        </div>-->

                        <!-- Карточки задач -->
                        <div class="grid grid-cols-1 gap-4">
                            <div v-for="(subs, taskTitle) in tasks"
                                 :key="taskTitle"
                                 class="group relative">

                                <!-- Заголовок родительской задачи -->
                                <div class="flex items-center gap-2 mb-3">
                                    <div class="w-4 h-4 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                                        <span class="text-[10px]">📌</span>
                                    </div>
                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300 truncate">
                                  Компания: {{ companyName }} <br/> В задаче:  {{ taskTitle }}
                                    </span>
                                    <span class="text-xs text-slate-400 dark:text-slate-500 ml-auto">
                                        {{ subs.length }} {{ subs.length === 1 ? 'подзадача' : 'подзадач' }}
                                    </span>
                                </div>

                                <!-- Сетка подзадач -->
                                <div class="grid grid-cols-1 gap-2 pl-6">
                                    <div v-for="st in subs"
                                         :key="st.id"
                                         @click="router.visit(`/subtasks/${st.id}`)"
                                         class="relative flex items-center gap-3 p-3 bg-white dark:bg-slate-900/50 rounded-xl border border-slate-100 dark:border-slate-800 hover:border-indigo-200 dark:hover:border-indigo-800 hover:shadow-lg hover:shadow-indigo-100/20 dark:hover:shadow-indigo-900/20 transition-all duration-300 cursor-pointer group/item">

                                        <!-- Индикатор статуса -->
                                        <div class="relative">
                                            <div class="w-2 h-2 rounded-full"
                                                 :class="{
                                                     'bg-emerald-500': st.status === 'completed',
                                                     'bg-blue-500': st.status === 'in-progress',
                                                     'bg-amber-500': st.status === 'pending',
                                                     'bg-rose-500': st.status === 'cancelled',
                                                     'bg-slate-400': !st.status
                                                 }">
                                            </div>
                                            <div class="absolute inset-0 rounded-full animate-ping opacity-20"
                                                 :class="{
                                                     'bg-emerald-500': st.status === 'completed',
                                                     'bg-blue-500': st.status === 'in-progress',
                                                     'bg-amber-500': st.status === 'pending',
                                                     'bg-rose-500': st.status === 'cancelled'
                                                 }">
                                            </div>
                                        </div>

                                        <!-- Контент подзадачи -->
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="text-sm font-medium text-slate-800 dark:text-slate-200 truncate group-hover/item:text-indigo-600 dark:group-hover/item:text-indigo-400 transition-colors">
                                                    {{ st.title }}
                                                </span>
                                                <!-- Приоритет -->
                                                <span v-if="st.priority"
                                                      class="text-[10px] px-2 py-0.5 rounded-full"
                                                      :class="{
                                                          'bg-rose-50 text-rose-700 dark:bg-rose-900/20 dark:text-rose-400': st.priority === 'high',
                                                          'bg-amber-50 text-amber-700 dark:bg-amber-900/20 dark:text-amber-400': st.priority === 'medium',
                                                          'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-400': st.priority === 'low'
                                                      }">
                                                    {{ st.priority === 'high' ? 'Высокий' : st.priority === 'medium' ? 'Средний' : 'Низкий' }}
                                                </span>
                                            </div>

                                            <!-- Мета-информация -->
                                            <div class="flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400">
                                                <span v-if="st.due_date" class="flex items-center gap-1">
                                                    <span class="text-[10px]">📅</span>
                                                    До: {{ new Date(st.due_date).toLocaleDateString('ru-RU', { day: 'numeric', month: 'short' }) }}
                                                </span>
                                                <span v-if="st.assignee" class="flex items-center gap-1">
                                                    <span class="text-[10px]">👤</span>
                                                    {{ st.assignee.name }}
                                                </span>
                                                <span v-if="st.estimated_hours" class="flex items-center gap-1">
                                                    <span class="text-[10px]">⏱️</span>
                                                    {{ st.estimated_hours }}ч
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Статус и стрелка -->
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs px-2 py-1 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                                                {{ st.status || 'Не начато' }}
                                            </span>
                                            <svg class="w-4 h-4 text-slate-400 group-hover/item:text-indigo-500 transition-colors"
                                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Плавные переходы */
.group-item {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Анимация появления карточек */
.grid > div {
    animation: fadeInUp 0.5s ease-out forwards;
    opacity: 0;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Задержки для анимации */
.grid > div:nth-child(1) { animation-delay: 0.1s; }
.grid > div:nth-child(2) { animation-delay: 0.15s; }
.grid > div:nth-child(3) { animation-delay: 0.2s; }
.grid > div:nth-child(4) { animation-delay: 0.25s; }
.grid > div:nth-child(5) { animation-delay: 0.3s; }
.grid > div:nth-child(6) { animation-delay: 0.35s; }

/* Стили для скролла в sticky заголовке */
.sticky {
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

/* Эффект свечения при наведении */
.group:hover .group-item {
    opacity: 0.7;
}

.group .group-item:hover {
    opacity: 1;
}
</style>
