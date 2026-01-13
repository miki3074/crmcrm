<script setup>
import TaskChecklists from '@/Components/TaskChecklists.vue'

import TaskChat from '@/Components/TaskChat.vue'

defineProps({ task: Object })

// Хелпер для получения инициалов (например, "Иван Иванов" -> "ИИ")
const getInitials = (name) => {
    if (!name) return '?'
    const parts = name.trim().split(' ')
    if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase()
    return name.slice(0, 2).toUpperCase()
}

// Хелпер для цвета фона аватара на основе имени (чтобы у разных людей были разные цвета)
const getAvatarColor = (name) => {
    const colors = [
        'bg-red-100 text-red-600', 'bg-orange-100 text-orange-600',
        'bg-amber-100 text-amber-600', 'bg-green-100 text-green-600',
        'bg-teal-100 text-teal-600', 'bg-blue-100 text-blue-600',
        'bg-indigo-100 text-indigo-600', 'bg-purple-100 text-purple-600',
        'bg-pink-100 text-pink-600'
    ]
    if (!name) return colors[0]
    let hash = 0
    for (let i = 0; i < name.length; i++) {
        hash = name.charCodeAt(i) + ((hash << 5) - hash)
    }
    return colors[Math.abs(hash) % colors.length]
}
</script>

<template>
    <div class="space-y-6">

        <!-- Блок: Команда и Инфо -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">

            <!-- Заголовок блока -->
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2 bg-gray-50/50 dark:bg-gray-700/30">
                <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <h3 class="font-bold text-gray-800 dark:text-gray-200">Детали задачи</h3>
            </div>

            <div class="p-6 space-y-6">

                <!-- Секция: Исполнители (Главные) -->
                <div>
                    <h4 class="text-xs font-bold uppercase text-gray-400 tracking-wider mb-3">🔨 Исполнители</h4>
                    <div v-if="task?.executors?.length" class="flex flex-wrap gap-3">
                        <div
                            v-for="user in task.executors"
                            :key="user.id"
                            class="flex items-center gap-2 px-3 py-1.5 rounded-full border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 shadow-sm"
                        >
                            <!-- Аватар -->
                            <div class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold shrink-0" :class="getAvatarColor(user.name)">
                                {{ getInitials(user.name) }}
                            </div>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ user.name }}</span>
                        </div>
                    </div>
                    <div v-else class="text-sm text-gray-400 italic">Не назначены</div>
                </div>

                <!-- Секция: Ответственные и Наблюдатели (Второстепенные) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-4 border-t border-gray-100 dark:border-gray-700">

                    <!-- Ответственные -->
                    <div>
                        <h4 class="text-xs font-bold uppercase text-gray-400 tracking-wider mb-2">👨‍💼 Ответственные</h4>
                        <div v-if="task?.responsibles?.length" class="flex -space-x-2 overflow-hidden py-1">
                            <div
                                v-for="user in task.responsibles"
                                :key="user.id"
                                class="flex items-center gap-2 px-3 py-1.5 rounded-full border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 shadow-sm"
                                :class="getAvatarColor(user.name)"
                                :title="user.name"
                            >
                                {{ user.name }}
                                <!-- Тултип при наведении -->
                                <span class="absolute bottom-full mb-1 left-1/2 -translate-x-1/2 px-2 py-1 bg-black text-white text-xs rounded opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50 transition-opacity">
                                    {{ user.name }}
                                </span>
                            </div>
                        </div>
                        <div v-else class="text-sm text-gray-400 italic">—</div>
                    </div>

                    <!-- Наблюдатели -->
                    <div>
                        <h4 class="text-xs font-bold uppercase text-gray-400 tracking-wider mb-2">👁 Наблюдатели</h4>
                        <div v-if="task?.watcherstask?.length" class="flex -space-x-2 overflow-hidden py-1">
                            <div
                                v-for="user in task.watcherstask"
                                :key="user.id"
                                class="flex items-center gap-2 px-3 py-1.5 rounded-full border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 shadow-sm"
                                :title="user.name"
                            >
                                {{ user.name }}
                                <span class="absolute bottom-full mb-1 left-1/2 -translate-x-1/2 px-2 py-1 bg-black text-white text-xs rounded opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50 transition-opacity">
                                    {{ user.name }}
                                </span>
                            </div>
                        </div>
                        <div v-else class="text-sm text-gray-400 italic">—</div>
                    </div>
                </div>

                <!-- Производители / Покупатели -->
                <div v-if="task?.producers?.length" class="pt-4 border-t border-gray-100 dark:border-gray-700">
                    <h4 class="text-xs font-bold uppercase text-gray-400 tracking-wider mb-3 flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                        Контрагенты
                    </h4>
                    <div class="flex flex-wrap gap-2">
                        <span
                            v-for="p in task.producers"
                            :key="p.id"
                            class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-100 dark:bg-indigo-900/30 dark:text-indigo-300 dark:border-indigo-800 transition hover:bg-indigo-100"
                        >
                            🏭 {{ p.name }}
                        </span>
                    </div>
                </div>

            </div>
        </div>

        <!-- Блок: Чеклисты -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-gray-50/50 dark:bg-gray-700/30">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                    <h3 class="font-bold text-gray-800 dark:text-gray-200">Чек-листы</h3>
                </div>
                <!-- Можно добавить сюда прогресс-бар, если есть данные -->
            </div>
            <div class="p-6">
                <TaskChecklists
                    :task-id="task.id"
                    :executors="task.executors"
                    :responsibles="task.responsibles"
                    :creator="task.creator"
                />
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">

            <TaskChat
                :task-id="task.id"
                :can-chat="true"
                :members="[...(task.executors||[]), ...(task.responsibles||[]), task.creator]"
            />
        </div>





    </div>
</template>
