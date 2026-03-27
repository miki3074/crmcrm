<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
    tasks: { type: Array, default: () => [] },
    title: String,
    variant: String, // 'danger', 'warning', 'default'
    showFilters: Boolean,
    compact: Boolean
})

const filter = ref('active')
const currentTasks = computed(() => {
    if (!props.showFilters) return props.tasks
    if (filter.value === 'active') return props.tasks.filter(t => (t.progress || 0) < 100)
    if (filter.value === 'completed') return props.tasks.filter(t => (t.progress || 0) >= 100)
    return props.tasks
})

const getStatusStyles = (progress) => {
    if (progress >= 100) return 'bg-emerald-500'
    if (progress > 50) return 'bg-indigo-500'
    return 'bg-slate-300 dark:bg-slate-600'
}
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between px-2">
            <h3 class="font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                <span v-if="variant === 'danger'" class="w-2 h-2 rounded-full bg-rose-500 animate-ping"></span>
                {{ title }}
            </h3>

            <div v-if="showFilters" class="flex bg-slate-100 dark:bg-slate-800 p-1 rounded-lg text-[10px] font-bold uppercase">
                <button @click="filter = 'active'" :class="['px-3 py-1 rounded-md transition', filter === 'active' ? 'bg-white dark:bg-slate-700 shadow-sm text-indigo-600' : 'text-slate-400']">В работе</button>
                <button @click="filter = 'completed'" :class="['px-3 py-1 rounded-md transition', filter === 'completed' ? 'bg-white dark:bg-slate-700 shadow-sm text-emerald-600' : 'text-slate-400']">Готово</button>
            </div>
        </div>

        <div v-if="currentTasks.length" :class="['grid gap-3', compact ? 'grid-cols-1' : 'grid-cols-1 md:grid-cols-2']">
            <div v-for="task in currentTasks.slice(0, 6)" :key="task.id"
                 @click="router.visit(`/tasks/${task.id}`)"
                 class="group p-4 bg-slate-50/50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800 rounded-2xl hover:bg-white dark:hover:bg-slate-800 hover:shadow-md transition-all cursor-pointer">

                <div class="flex justify-between items-start gap-4">
                    <div class="space-y-1">
                        <div class="text-[10px] font-bold text-indigo-500 uppercase tracking-wider">{{ task.project?.name }}</div>
                        <h4 class="font-bold text-sm text-slate-700 dark:text-slate-200 group-hover:text-indigo-600 transition-colors line-clamp-1">{{ task.title }}</h4>
                    </div>
                    <div class="text-[10px] font-bold text-slate-400 whitespace-nowrap">{{ task.due_date }}</div>
                </div>

                <div class="mt-4 flex items-center gap-3">
                    <div class="flex-1 h-1 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                        <div class="h-full transition-all duration-500" :class="getStatusStyles(task.progress)" :style="{width: task.progress + '%'}"></div>
                    </div>
                    <span class="text-[10px] font-black text-slate-500">{{ task.progress }}%</span>
                </div>
            </div>
        </div>
        <div v-else class="text-center py-8 text-slate-400 text-sm italic">Задач не найдено</div>
    </div>
</template>
