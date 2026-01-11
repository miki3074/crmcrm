<script setup>
import { router } from '@inertiajs/vue3'

defineProps({
    subtasks: Array,
    loading: Boolean,
    canCreate: Boolean
})

defineEmits(['create'])

const getBorderClass = (progress) => {
    if (progress === 100) return 'border-emerald-500 bg-emerald-50/50 dark:bg-emerald-900/10'
    if (progress >= 50) return 'border-amber-400 bg-amber-50/50 dark:bg-amber-900/10'
    return 'border-gray-200 dark:border-gray-700 hover:border-indigo-300'
}
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 h-full">
        <div class="flex justify-between items-center mb-4">
            <h3 class="font-bold text-gray-900 dark:text-white">📌 Подзадачи</h3>
            <button v-if="canCreate" @click="$emit('create')" class="text-xs bg-emerald-50 text-emerald-600 px-3 py-1.5 rounded-lg font-bold hover:bg-emerald-100">
                + Добавить
            </button>
        </div>

        <div v-if="!subtasks?.length" class="text-sm text-gray-400 italic py-4 text-center">Нет подзадач</div>

        <div v-else class="grid grid-cols-1 gap-3">
            <div v-for="s in subtasks" :key="s.id"
                 @click="router.visit(`/subtasks/${s.id}`)"
                 class="group relative p-4 rounded-xl border-l-4 shadow-sm transition-all cursor-pointer hover:shadow-md bg-white dark:bg-gray-800"
                 :class="getBorderClass(s.progress)">

                <div class="flex justify-between items-start mb-1">
                    <span class="font-semibold text-gray-800 dark:text-gray-200 group-hover:text-indigo-600 transition">{{ s.title }}</span>
                    <span class="text-xs font-bold px-2 py-0.5 rounded bg-white/50 dark:bg-black/20">{{ s.progress }}%</span>
                </div>

                <div class="text-xs text-gray-500 flex justify-between">
                    <span>{{ s.executors?.map(e=>e.name).join(', ') || 'Нет исп.' }}</span>
                    <span class="font-mono">{{ s.due_date }}</span>
                </div>
            </div>
        </div>
    </div>
</template>
