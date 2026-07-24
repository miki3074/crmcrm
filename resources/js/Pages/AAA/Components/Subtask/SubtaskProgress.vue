<script setup>
import { computed } from 'vue'
import axios from 'axios'

const props = defineProps(['subtask', 'user'])
const emit = defineEmits(['refresh']) // обновляем через родителя, чтобы получить новые данные

const canUpdateProgress = computed(() => {
    const { subtask, user } = props
    if (!subtask || !user) return false
    const isCreator = subtask.creator_id === user.id
    const isExecutor = (subtask.executors || []).some(e => e.id === user.id)
    const isResponsible = (subtask.responsibles || []).some(r => r.id === user.id)
    // Упростим: если ты участник - можешь менять
    return isCreator || isExecutor || isResponsible
})

const updateProgress = async (val) => {
    if (!canUpdateProgress.value) return
    // Оптимистичное обновление UI (можно сделать, но лучше дождаться сервера для точности)
    await axios.patch(`/api/subtasks/${props.subtask.id}/progress`, { progress: val })
    emit('refresh')
}
</script>

<template>
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Прогресс подзадачи</h3>
            <span class="text-sm text-gray-500">Выполнено {{ subtask.progress ?? 0 }}%</span>
        </div>

        <div class="grid grid-cols-2 gap-3 mb-3">
            <div>
                <p class="text-gray-500 text-sm">Начало</p>
                <p class="font-medium dark:text-white">{{ subtask.start_date }}</p>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Окончание</p>
                <p class="font-medium dark:text-white">{{ subtask.due_date }}</p>
            </div>
        </div>

        <div class="mt-2 flex space-x-1 select-none">
            <div v-for="n in 10" :key="n"
                 @click="canUpdateProgress ? updateProgress(n*10) : null"
                 class="h-2.5 flex-1 rounded transition"
                 :class="{
                'cursor-pointer hover:opacity-80': canUpdateProgress,
                'bg-green-600': (subtask.progress ?? 0) >= n * 10,
                'bg-gray-200 dark:bg-slate-800': (subtask.progress ?? 0) < n * 10
             }"
            />
        </div>
    </div>
</template>
