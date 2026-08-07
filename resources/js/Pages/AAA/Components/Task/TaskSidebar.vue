<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import TaskChecklists from '@/Components/TaskChecklists.vue'
import TaskChat from '@/Components/TaskChat.vue'


const props = defineProps({
    task: {
        type: Object,
        required: true,
    },
})

const page = usePage()

const userId = computed(() => {
    return page.props.auth.user?.id ?? null
})

const taskKlients = computed(() => {
    return props.task?.klients ?? []
})

const getInitials = (name) => {
    if (!name) return '?'

    const parts = name.trim().split(/\s+/)

    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase()
    }

    return name.slice(0, 2).toUpperCase()
}

const getAvatarColor = (name) => {
    const colors = [
        'bg-red-100 text-red-600',
        'bg-orange-100 text-orange-600',
        'bg-amber-100 text-amber-600',
        'bg-green-100 text-green-600',
        'bg-teal-100 text-teal-600',
        'bg-blue-100 text-blue-600',
        'bg-indigo-100 text-indigo-600',
        'bg-purple-100 text-purple-600',
        'bg-pink-100 text-pink-600',
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
    <div class="space-y-3">

        <!-- Клиенты задачи -->
<div
    v-if="taskKlients.length > 0"
    class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-gray-100 dark:border-slate-800 overflow-hidden"
>
    <!-- Заголовок -->
    <div
        class="px-6 py-3 border-b border-gray-100 dark:border-slate-800 flex items-center justify-between bg-gray-50/50 dark:bg-gray-700/30"
    >
        <div class="flex items-center gap-2">
            <svg
                class="w-5 h-5 text-gray-400"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m8-4a4 4 0 11-8 0 4 4 0 018 0zm-8 0a3 3 0 11-6 0 3 3 0 016 0z"
                />
            </svg>

            <h3 class="font-bold text-gray-800 dark:text-gray-200">
                Клиенты
            </h3>

            <span
                class="inline-flex items-center justify-center min-w-6 h-6 px-2 text-xs font-bold rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300"
            >
                {{ taskKlients.length }}
            </span>
        </div>
    </div>

    <!-- Список -->
    <div class="divide-y divide-gray-100 dark:divide-slate-800">
        <a
            v-for="klient in taskKlients"
            :key="klient.id"
            :href="route('klients.show', klient.id)"
            class="flex items-center gap-4 px-5 py-4 hover:bg-gray-50 dark:hover:bg-slate-800/50 transition"
        >
            <!-- Аватар -->
            <div
                class="w-10 h-10 shrink-0 rounded-full flex items-center justify-center text-sm font-bold"
                :class="getAvatarColor(klient.name)"
            >
                {{ getInitials(klient.name) }}
            </div>

            <!-- Информация -->
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                    <p
                        class="font-semibold text-gray-900 dark:text-gray-100 truncate"
                    >
                        {{ klient.name }}
                    </p>

                    <span
                        v-if="klient.status"
                        class="shrink-0 px-2 py-0.5 text-[10px] font-semibold rounded-full bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-gray-300"
                    >
                        {{ klient.status }}
                    </span>
                </div>

                <div
                    class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-500 dark:text-gray-400"
                >
                    <span v-if="klient.company">
                        {{ klient.company.name }}
                    </span>

                    <span v-if="klient.phone">
                        {{ klient.phone }}
                    </span>

                    <span v-if="klient.email">
                        {{ klient.email }}
                    </span>
                </div>
            </div>

            <!-- Стрелка -->
            <svg
                class="w-5 h-5 shrink-0 text-gray-400"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 5l7 7-7 7"
                />
            </svg>
        </a>
    </div>
</div>


        <!-- Блок: Чеклисты -->
         123
        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-gray-100 dark:border-slate-800 overflow-hidden">
            <div class="px-6 py-2.5 border-b border-gray-100 dark:border-slate-800 flex items-center justify-between bg-gray-50/50 dark:bg-gray-700/30">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                    <h3 class="font-bold text-gray-800 dark:text-gray-200">Чек-листы</h3>
                </div>
            </div>
            <div class="p-4">
                <!-- Теперь userId передается корректно -->
                 
                <TaskChecklists
                    :user-id="userId"
                    :task-id="task.id"
                    :executors="task.executors"
                    :responsibles="task.responsibles"
                    :creator="task.creator"
                />
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-slate-800">
            <TaskChat
                :task-id="task.id"
                :can-chat="true"
                :members="[...(task.executors||[]), ...(task.responsibles||[]), task.creator]"
            />
        </div>

    </div>
</template>
