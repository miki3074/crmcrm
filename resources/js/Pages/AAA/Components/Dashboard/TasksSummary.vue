<!-- Partials/TasksSummary.vue -->
<script setup>
import { router } from '@inertiajs/vue3'

const props = defineProps({
    tasks: Array,
    title: String,
    compact: Boolean,
    variant: { type: String, default: 'default' } // 'default' | 'danger'
})
</script>

<template>
    <div :class="['p-6 rounded-3xl border transition-all shadow-sm bg-white/70 dark:bg-slate-900/70 backdrop-blur-md',
                 variant === 'danger' ? 'border-rose-200 dark:border-rose-900/30' : 'border-slate-200 dark:border-slate-800']">
        <div class="flex items-center justify-between mb-6">
            <h3 :class="['text-lg font-bold', variant === 'danger' ? 'text-rose-600' : 'text-slate-800 dark:text-slate-100']">
                {{ title }}
            </h3>
            <span class="px-2 py-1 text-xs font-bold rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500">{{ tasks?.length || 0 }}</span>
        </div>

        <div :class="['grid gap-4', compact ? 'grid-cols-1' : 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3']">
            <div v-for="task in tasks" :key="task.id"
                 @click="router.visit(`/tasks/${task.id}`)"
                 class="group p-4 bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-2xl hover:shadow-lg transition-all cursor-pointer">
                <div class="flex flex-col gap-2">
                    <div class="flex items-start justify-between gap-2">
                        <p class="font-bold text-slate-700 dark:text-slate-200 leading-tight line-clamp-2 group-hover:text-indigo-500 transition">{{ task.title }}</p>
                    </div>

                    <!-- Прогресс -->
                    <div class="mt-2">
                        <div class="flex justify-between text-[10px] mb-1 font-bold text-slate-400 uppercase tracking-tighter">
                            <span>Прогресс</span>
                            <span>{{ task.progress || 0 }}%</span>
                        </div>
                        <div class="h-1.5 w-full bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                            <div class="h-full bg-indigo-500 rounded-full transition-all duration-500" :style="{ width: (task.progress || 0) + '%' }"></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between mt-2">
                        <span class="text-[10px] px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-500 font-bold uppercase truncate max-w-[100px]">
                            {{ task.project?.name }}
                        </span>
                        <span class="text-[10px] font-medium text-slate-400 italic">Срок: {{ task.due_date || '—' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="!tasks?.length" class="py-10 text-center text-slate-400 italic text-sm">
            Задач пока нет
        </div>
    </div>
</template>
