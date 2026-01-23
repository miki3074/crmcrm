<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

const props = defineProps({
    tasks: { type: Array, default: () => [] },
    subtasks: { type: Array, default: () => [] }
});

const activeTab = ref('tasks'); // 'tasks' | 'subtasks'

// Форматирование даты (только число)
const formatDate = (dateString) => {
    if (!dateString) return null;
    return new Date(dateString).toLocaleDateString('ru-RU', {
        day: '2-digit', month: '2-digit', year: 'numeric'
    });
};

// Форматирование даты и времени
const formatDateTime = (dateString) => {
    if (!dateString) return '—';
    return new Date(dateString).toLocaleDateString('ru-RU', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
};

// Цвет приоритета
const getPriorityClass = (priority) => {
    switch (priority) {
        case 'high': return 'bg-rose-100 text-rose-600 dark:bg-rose-900/30 dark:text-rose-400';
        case 'medium': return 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400';
        default: return 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400';
    }
};

// Проверка: просрочена ли задача (если завершена позже дедлайна)
const isLate = (due_date, completed_at) => {
    if (!due_date || !completed_at) return false;
    return new Date(completed_at) > new Date(due_date); // Если завершили позже плана (учитывая время, можно скорректировать до конца дня)
};
</script>

<template>

    <AuthenticatedLayout>
        <Head title="Архив задач" />

        <div class="py-12 bg-slate-50 dark:bg-slate-900 min-h-screen transition-colors duration-300">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

                <div class="flex items-center justify-between mb-8">
                    <h1 class="text-3xl font-bold text-slate-800 dark:text-white tracking-tight">🗄️ Архив задач</h1>
                </div>

                <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-xl shadow-slate-200/50 dark:shadow-none rounded-3xl border border-slate-100 dark:border-slate-700">

                    <!-- Табы -->
                    <div class="flex border-b border-slate-200 dark:border-slate-700">
                        <button
                            @click="activeTab = 'tasks'"
                            class="relative flex-1 py-4 text-sm font-bold uppercase tracking-wider transition-all duration-300 focus:outline-none"
                            :class="activeTab === 'tasks'
                            ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50/50 dark:bg-slate-700/50'
                            : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700'"
                        >
                            Задачи
                            <span class="ml-2 px-2 py-0.5 rounded-full bg-slate-200 dark:bg-slate-600 text-xs">{{ tasks.length }}</span>
                            <div v-if="activeTab === 'tasks'" class="absolute bottom-0 left-0 w-full h-0.5 bg-indigo-500"></div>
                        </button>

                        <button
                            @click="activeTab = 'subtasks'"
                            class="relative flex-1 py-4 text-sm font-bold uppercase tracking-wider transition-all duration-300 focus:outline-none"
                            :class="activeTab === 'subtasks'
                            ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50/50 dark:bg-slate-700/50'
                            : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700'"
                        >
                            Подзадачи
                            <span class="ml-2 px-2 py-0.5 rounded-full bg-slate-200 dark:bg-slate-600 text-xs">{{ subtasks.length }}</span>
                            <div v-if="activeTab === 'subtasks'" class="absolute bottom-0 left-0 w-full h-0.5 bg-indigo-500"></div>
                        </button>
                    </div>

                    <!-- Содержимое: ЗАДАЧИ -->
                    <div v-if="activeTab === 'tasks'" class="animate-in fade-in slide-in-from-bottom-2 duration-300">
                        <div v-if="tasks.length === 0" class="flex flex-col items-center justify-center py-16 text-slate-400">
                            <svg class="w-16 h-16 mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                            <p>Нет завершенных задач</p>
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                                <thead class="bg-slate-50 dark:bg-slate-900/50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-1/3">Задача / Проект</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Сроки (План)</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Завершено (Факт)</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Участники</th>
                                </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-800">
                                <tr v-for="task in tasks" :key="task.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors group">

                                    <!-- Название и Проект -->
                                    <td class="px-6 py-4">
                                        <div class="flex items-start gap-3">
                                            <!-- Приоритет (полоска) -->
                                            <div class="w-1 h-10 rounded-full flex-shrink-0 mt-1" :class="getPriorityClass(task.priority).replace('text-', 'bg-').split(' ')[0]"></div>

                                            <div>
                                                <div class="text-sm font-bold text-slate-800 dark:text-white group-hover:text-indigo-600 transition-colors">
                                                    {{ task.title }}
                                                </div>
                                                <div class="flex items-center gap-2 mt-1">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-300">
                                                    {{ task.project?.name || 'Без проекта' }}
                                                </span>
                                                    <span class="text-[10px] uppercase font-bold tracking-wider px-1.5 py-0.5 rounded" :class="getPriorityClass(task.priority)">
                                                    {{ task.priority }}
                                                </span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Сроки (План) -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-xs text-slate-500 flex flex-col gap-1">
                                            <div class="flex items-center gap-1">
                                                <span class="w-12 font-medium text-slate-400">Старт:</span>
                                                <span class="text-slate-700 dark:text-slate-300">{{ formatDate(task.start_date) || '...' }}</span>
                                            </div>
                                            <div class="flex items-center gap-1">
                                                <span class="w-12 font-medium text-slate-400">Срок:</span>
                                                <span class="font-semibold text-slate-700 dark:text-slate-300">{{ formatDate(task.due_date) || 'Бессрочно' }}</span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Дата завершения (Факт) -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <div class="p-1.5 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            </div>
                                            <div class="flex flex-col">
                                            <span class="text-sm font-bold text-slate-700 dark:text-slate-200">
                                                {{ formatDateTime(task.completed_at) }}
                                            </span>
                                                <span v-if="isLate(task.due_date, task.completed_at)" class="text-[10px] text-rose-500 font-bold uppercase">
                                                С опозданием
                                            </span>
                                                <span v-else class="text-[10px] text-emerald-500 font-medium">
                                                Вовремя
                                            </span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Участники -->
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col gap-2">
                                            <!-- Ответственные -->
                                            <div v-if="task.responsibles?.length" class="flex items-center gap-2">
                                                <span class="text-[10px] uppercase font-bold text-slate-400 w-6 text-right" title="Ответственные">Отв:</span>
                                                <div class="flex -space-x-2">
                                                    <div v-for="user in task.responsibles" :key="user.id"
                                                         class="w-6 h-6 rounded-full bg-amber-100 dark:bg-amber-800 border-2 border-white dark:border-slate-800 flex items-center justify-center text-[9px] font-bold text-amber-700 dark:text-amber-200"
                                                         :title="'Ответственный: ' + user.name">
                                                        {{ user.name.charAt(0) }}
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Исполнители -->
                                            <div v-if="task.executors?.length" class="flex items-center gap-2">
                                                <span class="text-[10px] uppercase font-bold text-slate-400 w-6 text-right" title="Исполнители">Исп:</span>
                                                <div class="flex -space-x-2">
                                                    <div v-for="user in task.executors" :key="user.id"
                                                         class="w-6 h-6 rounded-full bg-indigo-100 dark:bg-indigo-800 border-2 border-white dark:border-slate-800 flex items-center justify-center text-[9px] font-bold text-indigo-700 dark:text-indigo-200"
                                                         :title="'Исполнитель: ' + user.name">
                                                        {{ user.name.charAt(0) }}
                                                    </div>
                                                </div>
                                            </div>
                                            <span v-if="!task.responsibles?.length && !task.executors?.length" class="text-xs text-slate-400 italic pl-8">Нет участников</span>
                                        </div>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Содержимое: ПОДЗАДАЧИ -->
                    <div v-if="activeTab === 'subtasks'" class="animate-in fade-in slide-in-from-bottom-2 duration-300">
                        <div v-if="subtasks.length === 0" class="flex flex-col items-center justify-center py-16 text-slate-400">
                            <svg class="w-16 h-16 mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                            <p>Нет завершенных подзадач</p>
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                                <thead class="bg-slate-50 dark:bg-slate-900/50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-1/3">Подзадача / Родитель</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Сроки</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Завершено</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Участники</th>
                                </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-800">
                                <tr v-for="st in subtasks" :key="st.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors group">

                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <div class="flex items-center gap-2">
                                                <svg class="w-4 h-4 text-slate-400 rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg>
                                                <div class="text-sm font-bold text-slate-800 dark:text-white group-hover:text-indigo-600 transition-colors">
                                                    {{ st.title }}
                                                </div>
                                            </div>
<!--                                            <div class="mt-1 pl-6">-->
<!--                                                <span class="text-[10px] text-slate-400 uppercase font-bold mr-1">В задаче:</span>-->
<!--                                                <span class="text-xs text-slate-600 dark:text-slate-300">{{ st.task?.title || 'Родитель удален' }}</span>-->
<!--                                            </div>-->
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-xs text-slate-500 flex flex-col gap-1">
                                            <div class="flex items-center gap-1">
                                                <span class="w-12 font-medium text-slate-400">Старт:</span>
                                                <span class="text-slate-700 dark:text-slate-300">{{ formatDate(st.start_date) || '...' }}</span>
                                            </div>
                                            <div class="flex items-center gap-1">
                                                <span class="w-12 font-medium text-slate-400">Срок:</span>
                                                <span class="font-semibold text-slate-700 dark:text-slate-300">{{ formatDate(st.due_date) || 'Бессрочно' }}</span>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold text-slate-700 dark:text-slate-200">
                                            {{ formatDateTime(st.completed_at) }}
                                        </div>
                                        <div v-if="isLate(st.due_date, st.completed_at)" class="text-[10px] text-rose-500 font-bold uppercase mt-0.5">
                                            С опозданием
                                        </div>
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="flex flex-col gap-2">
                                            <!-- Ответственные подзадачи -->
                                            <div v-if="st.responsibles?.length" class="flex items-center gap-2">
                                                <span class="text-[10px] uppercase font-bold text-slate-400 w-6 text-right">Отв:</span>
                                                <div class="flex -space-x-2">
                                                    <div v-for="user in st.responsibles" :key="user.id"
                                                         class="w-6 h-6 rounded-full bg-amber-100 dark:bg-amber-800 border-2 border-white dark:border-slate-800 flex items-center justify-center text-[9px] font-bold text-amber-700 dark:text-amber-200"
                                                         :title="'Ответственный: ' + user.name">
                                                        {{ user.name.charAt(0) }}
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Исполнители подзадачи -->
                                            <div v-if="st.executors?.length" class="flex items-center gap-2">
                                                <span class="text-[10px] uppercase font-bold text-slate-400 w-6 text-right">Исп:</span>
                                                <div class="flex -space-x-2">
                                                    <div v-for="user in st.executors" :key="user.id"
                                                         class="w-6 h-6 rounded-full bg-indigo-100 dark:bg-indigo-800 border-2 border-white dark:border-slate-800 flex items-center justify-center text-[9px] font-bold text-indigo-700 dark:text-indigo-200"
                                                         :title="'Исполнитель: ' + user.name">
                                                        {{ user.name.charAt(0) }}
                                                    </div>
                                                </div>
                                            </div>
                                            <span v-if="!st.responsibles?.length && !st.executors?.length" class="text-xs text-slate-400 italic pl-8">Нет участников</span>
                                        </div>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
