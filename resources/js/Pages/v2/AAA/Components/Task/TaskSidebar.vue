<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import TaskChecklists from '@/Components/TaskChecklists.vue'
import TaskChat from '@/Components/TaskChat.vue'

defineProps({ task: Object })

const page = usePage()
const userId = computed(() => page.props.auth.user ? page.props.auth.user.id : null)

// Хелпер для получения инициалов
const getInitials = (name) => {
    if (!name) return '?'
    const parts = name.trim().split(' ')
    if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase()
    return name.slice(0, 2).toUpperCase()
}

// Хелпер для цвета фона аватара
const getAvatarColor = (name) => {
    const colors = [
        'from-red-500 to-red-600', 'from-orange-500 to-orange-600',
        'from-amber-500 to-amber-600', 'from-green-500 to-green-600',
        'from-teal-500 to-teal-600', 'from-blue-500 to-blue-600',
        'from-indigo-500 to-indigo-600', 'from-purple-500 to-purple-600',
        'from-pink-500 to-pink-600'
    ]
    if (!name) return colors[0]
    let hash = 0
    for (let i = 0; i < name.length; i++) {
        hash = name.charCodeAt(i) + ((hash << 5) - hash)
    }
    return colors[Math.abs(hash) % colors.length]
}

// Форматирование ролей
const roleLabels = {
    executor: 'Исполнитель',
    responsible: 'Ответственный',
    watcher: 'Наблюдатель'
}
</script>

<template>
    <div class="space-y-6">

        <!-- Блок: Команда и Инфо (Современный дизайн) -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-2xl transition-all duration-300">

            <!-- Декоративная полоса сверху -->
            <div class="h-1.5 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500"></div>

            <!-- Заголовок блока -->
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white shadow-lg">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-800 dark:text-white">Детали задачи</h3>
                </div>
            </div>

            <div class="p-6 space-y-6">

                <!-- Секция: Исполнители -->
                <div class="group">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-1 h-4 bg-gradient-to-b from-emerald-500 to-teal-500 rounded-full"></div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">Исполнители</h4>
                    </div>

                    <div v-if="task?.executors?.length" class="space-y-2">
                        <div v-for="user in task.executors" :key="user.id"
                             class="flex items-center gap-3 p-3 rounded-xl bg-gradient-to-br from-slate-50 to-white dark:from-slate-800 dark:to-slate-900 border border-slate-200 dark:border-slate-700 hover:border-emerald-300 dark:hover:border-emerald-700 transition-all group/user">

                            <div class="relative">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br shadow-lg flex items-center justify-center text-white text-sm font-bold"
                                     :class="getAvatarColor(user.name)">
                                    {{ getInitials(user.name) }}
                                </div>
                                <div class="absolute -bottom-1 -right-1 w-3.5 h-3.5 bg-emerald-400 rounded-full border-2 border-white dark:border-slate-800"></div>
                            </div>

                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-slate-800 dark:text-white truncate">{{ user.name }}</p>
                                <p class="text-xs text-slate-500">Основной исполнитель</p>
                            </div>

                            <span class="text-xs px-2 py-1 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300">
                                🔨 В работе
                            </span>
                        </div>
                    </div>

                    <div v-else class="p-4 bg-slate-50 dark:bg-slate-700/30 rounded-xl text-center border border-dashed border-slate-200 dark:border-slate-700">
                        <p class="text-sm text-slate-400">Исполнители не назначены</p>
                    </div>
                </div>

                <!-- Секция: Ответственные -->
                <div class="pt-4 border-t border-slate-100 dark:border-slate-700">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-1 h-4 bg-gradient-to-b from-blue-500 to-indigo-500 rounded-full"></div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">Ответственные</h4>
                    </div>

                    <div v-if="task?.responsibles?.length" class="flex flex-wrap gap-2">
                        <div v-for="user in task.responsibles" :key="user.id"
                             class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-950/30 dark:to-indigo-950/30 border border-blue-200 dark:border-blue-800 hover:shadow-md transition-all">
                            <div class="w-5 h-5 rounded-full bg-gradient-to-br from-blue-500 to-indigo-500 flex items-center justify-center text-white text-[8px] font-bold">
                                {{ getInitials(user.name) }}
                            </div>
                            <span class="text-xs font-medium text-blue-700 dark:text-blue-300">{{ user.name }}</span>
                        </div>
                    </div>
                    <div v-else class="text-sm text-slate-400 italic">—</div>
                </div>

                <!-- Секция: Наблюдатели -->
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-1 h-4 bg-gradient-to-b from-purple-500 to-pink-500 rounded-full"></div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">Наблюдатели</h4>
                    </div>

                    <div v-if="task?.watcherstask?.length" class="flex flex-wrap gap-2">
                        <div v-for="user in task.watcherstask" :key="user.id"
                             class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-950/30 dark:to-pink-950/30 border border-purple-200 dark:border-purple-800 hover:shadow-md transition-all">
                            <div class="w-5 h-5 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white text-[8px] font-bold">
                                {{ getInitials(user.name) }}
                            </div>
                            <span class="text-xs font-medium text-purple-700 dark:text-purple-300">{{ user.name }}</span>
                        </div>
                    </div>
                    <div v-else class="text-sm text-slate-400 italic">—</div>
                </div>

                <!-- Контрагенты -->
                <div v-if="task?.producers?.length" class="pt-4 border-t border-slate-100 dark:border-slate-700">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-1 h-4 bg-gradient-to-b from-amber-500 to-orange-500 rounded-full"></div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">Контрагенты</h4>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <span v-for="p in task.producers" :key="p.id"
                              class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-950/30 dark:to-orange-950/30 border border-amber-200 dark:border-amber-800">
                            <span class="text-lg">🏭</span>
                            <span class="text-sm font-medium text-amber-700 dark:text-amber-300">{{ p.name }}</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Блок: Чек-листы (Современный дизайн) -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-2xl transition-all duration-300">
            <div class="h-1.5 bg-gradient-to-r from-emerald-500 to-teal-500"></div>

            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-emerald-500 to-teal-500 flex items-center justify-center text-white shadow-lg">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-800 dark:text-white">Чек-листы</h3>
                </div>
            </div>

            <div class="p-6">
                <TaskChecklists
                    :user-id="userId"
                    :task-id="task.id"
                    :executors="task.executors"
                    :responsibles="task.responsibles"
                    :creator="task.creator"
                />
            </div>
        </div>

        <!-- Блок: Чат (Современный дизайн) -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-2xl transition-all duration-300">
            <div class="h-1.5 bg-gradient-to-r from-blue-500 to-indigo-500"></div>

            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-500 flex items-center justify-center text-white shadow-lg">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-800 dark:text-white">Комментарии</h3>
                </div>
            </div>

            <div class="p-6">
                <TaskChat
                    :task-id="task.id"
                    :can-chat="true"
                    :members="[...(task.executors||[]), ...(task.responsibles||[]), task.creator]"
                />
            </div>
        </div>

        <!-- Краткая статистика (дополнительно) -->
        <div class="bg-gradient-to-br from-indigo-600 to-purple-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="flex items-center gap-2 mb-4">
                <div class="w-8 h-8 rounded-lg bg-white/20 backdrop-blur-sm flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
                <h4 class="text-sm font-medium opacity-90">Быстрая статистика</h4>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="bg-white/20 backdrop-blur-sm rounded-xl p-3">
                    <div class="text-2xl font-bold">{{ task?.subtasks?.length || 0 }}</div>
                    <div class="text-xs opacity-80">Подзадач</div>
                </div>
                <div class="bg-white/20 backdrop-blur-sm rounded-xl p-3">
                    <div class="text-2xl font-bold">{{ task?.files?.length || 0 }}</div>
                    <div class="text-xs opacity-80">Файлов</div>
                </div>
                <div class="bg-white/20 backdrop-blur-sm rounded-xl p-3">
                    <div class="text-2xl font-bold">{{ task?.executors?.length || 0 }}</div>
                    <div class="text-xs opacity-80">Участников</div>
                </div>
                <div class="bg-white/20 backdrop-blur-sm rounded-xl p-3">
                    <div class="text-2xl font-bold">{{ task?.comments?.length || 0 }}</div>
                    <div class="text-xs opacity-80">Комментариев</div>
                </div>
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

.sidebar-block {
    animation: slideIn 0.3s ease-out forwards;
}

/* Задержки для анимации */
.sidebar-block:nth-child(1) { animation-delay: 0.1s; }
.sidebar-block:nth-child(2) { animation-delay: 0.2s; }
.sidebar-block:nth-child(3) { animation-delay: 0.3s; }

/* Плавные переходы */
* {
    transition: all 0.2s ease;
}

/* Кастомный скроллбар для чата */
::-webkit-scrollbar {
    width: 4px;
}

::-webkit-scrollbar-track {
    background: transparent;
}

::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 20px;
}

.dark ::-webkit-scrollbar-thumb {
    background: #475569;
}
</style>
