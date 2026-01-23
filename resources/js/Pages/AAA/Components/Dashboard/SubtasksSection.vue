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

    // Если массив — группируем
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

    return data // Если уже объект
})

const hasSubtasks = computed(() => {
    return Array.isArray(props.subtasks) ? props.subtasks.length > 0 : Object.keys(props.subtasks).length > 0
})
</script>

<template>
    <div class="p-6 rounded-3xl border border-slate-200 dark:border-slate-800 transition-all shadow-sm bg-white/70 dark:bg-slate-900/70 backdrop-blur-md">

        <div class="flex items-center justify-between mb-6">

            <span v-if="Array.isArray(subtasks)" class="px-2 py-1 text-xs font-bold rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500">
                {{ subtasks.length }}
            </span>
        </div>

        <div v-if="!hasSubtasks" class="py-10 text-center text-slate-400 italic text-sm">
            Подзадач пока нет
        </div>

        <div v-else class="space-y-8">
            <!-- Группировка по Компании -->
            <div v-for="(projects, companyName) in groupedSubtasks" :key="companyName" class="relative pl-4 border-l-2 border-indigo-200 dark:border-indigo-900/50">
<!--                <h4 class="text-sm font-bold uppercase tracking-wider text-slate-400 mb-4 bg-slate-50 dark:bg-slate-800/50 inline-block px-2 py-1 rounded">-->
<!--                    🏢 {{ companyName }}-->
<!--                </h4>-->

                <div class="space-y-6">
                    <!-- Группировка по Проекту -->
                    <div v-for="(tasks, projectName) in projects" :key="projectName">

                        <!-- Группировка по Родительской задаче -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div v-for="(subs, taskTitle) in tasks" :key="taskTitle"
                                 class="bg-white/50 dark:bg-slate-800/50 rounded-2xl p-4 border border-slate-100 dark:border-slate-700 hover:border-indigo-200 dark:hover:border-indigo-800 transition-colors">

                                <div class="flex items-center gap-2 mb-3 pb-2 border-b border-slate-100 dark:border-slate-700/50">
                                    <span class="text-xs font-semibold text-indigo-500 bg-indigo-50 dark:bg-indigo-900/20 px-2 py-0.5 rounded">
                                        {{ projectName }}
                                    </span>
                                    <span class="text-sm font-bold text-slate-700 dark:text-slate-200 truncate" :title="taskTitle">
                                        ✅ {{ taskTitle }}
                                    </span>
                                </div>

                                <!-- Список подзадач -->
                                <div class="space-y-2">
                                    <div v-for="st in subs" :key="st.id"
                                         @click="router.visit(`/tasks/${st.task_id}`)"
                                         class="group flex items-center justify-between p-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-700 hover:shadow-md cursor-pointer transition-all">

                                        <div class="flex items-center gap-2 overflow-hidden">
                                            <div class="w-1.5 h-1.5 rounded-full bg-slate-300 group-hover:bg-indigo-500 transition-colors"></div>
                                            <span class="text-sm text-slate-600 dark:text-slate-300 truncate font-medium group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                                {{ st.title }}
                                            </span>
                                        </div>

                                        <span class="text-[10px] text-slate-400 whitespace-nowrap ml-2">
                                            {{ st.due_date || '—' }}
                                        </span>
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
