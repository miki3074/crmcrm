<script setup>
import { ref, watch } from 'vue'
import axios from 'axios'

const props = defineProps({
    show: Boolean,
    companyId: [Number, String],
})

const emit = defineEmits(['close'])

const isLoading = ref(false)
const isSending = ref(false)
const projectsData = ref([])

const selectedTaskIds = ref([])
const selectedSubtaskIds = ref([])

const loadStagnantItems = async () => {
    isLoading.value = true
    projectsData.value = []
    selectedTaskIds.value = []
    selectedSubtaskIds.value = []

    try {
        const res = await axios.get(`/api/companies/${props.companyId}/stagnant-items`)
        projectsData.value = res.data

        res.data.forEach(project => {
            project.tasks.forEach(t => {
                selectedTaskIds.value.push(t.id)
                t.subtasks.forEach(s => selectedSubtaskIds.value.push(s.id))
            })
        })
    } catch (e) {
        alert('Ошибка загрузки данных компании')
    } finally {
        isLoading.value = false
    }
}

watch(() => props.show, val => {
    if (val) loadStagnantItems()
})

const toggleProject = project => {
    const pTaskIds = project.tasks.map(t => t.id)
    const pSubtaskIds = project.tasks.flatMap(t => t.subtasks.map(s => s.id))

    const allSelected = pTaskIds.every(id => selectedTaskIds.value.includes(id))

    if (allSelected) {
        selectedTaskIds.value = selectedTaskIds.value.filter(id => !pTaskIds.includes(id))
        selectedSubtaskIds.value = selectedSubtaskIds.value.filter(id => !pSubtaskIds.includes(id))
    } else {
        selectedTaskIds.value = [...new Set([...selectedTaskIds.value, ...pTaskIds])]
        selectedSubtaskIds.value = [...new Set([...selectedSubtaskIds.value, ...pSubtaskIds])]
    }
}

const sendCompanyReminders = async () => {
    if (!selectedTaskIds.value.length && !selectedSubtaskIds.value.length) return alert('Ничего не выбрано')

    isSending.value = true
    try {
        const res = await axios.post(`/api/companies/${props.companyId}/remind-stagnant`, {
            task_ids: selectedTaskIds.value,
            subtask_ids: selectedSubtaskIds.value
        })
        alert(res.data.message)
        emit('close')
    } catch (e) {
        alert('Ошибка при отправке')
    } finally {
        isSending.value = false
    }
}
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[60] grid place-items-center bg-zinc-950/60 p-3 backdrop-blur-sm">
        <div class="flex max-h-[88vh] w-full max-w-2xl flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-2xl dark:border-zinc-700 dark:bg-zinc-900">
            <header class="flex items-center justify-between border-b border-zinc-200 px-4 py-3 dark:border-zinc-800">
                <div>
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-white">Напоминания о задачах</h3>
                    <p class="text-xs text-zinc-500">Выберите задачи с прогрессом 0%</p>
                </div>
                <button
                    type="button"
                    class="icon-button focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400"
                    @click="emit('close')"
                >
                    ✕
                </button>
            </header>

            <div class="min-h-0 flex-1 overflow-y-auto p-3">
                <div v-if="isLoading" class="py-16 text-center text-sm text-zinc-500">Загрузка…</div>
                <div v-else-if="!projectsData.length" class="py-16 text-center text-sm text-zinc-500">Нет задач с нулевым прогрессом</div>
                <div v-else class="space-y-2">
                    <section v-for="project in projectsData" :key="project.id" class="rounded-lg border border-zinc-200 dark:border-zinc-800">
                        <div class="flex items-center justify-between border-b border-zinc-100 px-3 py-2 dark:border-zinc-800">
                            <span class="truncate text-sm font-semibold text-zinc-800 dark:text-zinc-100">{{ project.name }}</span>
                            <button
                                type="button"
                                class="text-xs font-medium text-cyan-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400 dark:text-cyan-400"
                                @click="toggleProject(project)"
                            >
                                Выбрать всё
                            </button>
                        </div>
                        <div class="space-y-1 p-2">
                            <div v-for="task in project.tasks" :key="task.id" class="rounded-md px-2 py-1.5 hover:bg-zinc-50 dark:hover:bg-zinc-800/60">
                                <label class="flex cursor-pointer items-center gap-2 text-sm">
                                    <input v-model="selectedTaskIds" type="checkbox" :value="task.id" class="rounded border-zinc-300 text-cyan-600" />
                                    <span class="truncate">{{ task.title }}</span>
                                </label>
                                <div v-if="task.subtasks?.length" class="ml-5 mt-1 space-y-1 border-l border-zinc-200 pl-3 dark:border-zinc-700">
                                    <label v-for="sub in task.subtasks" :key="sub.id" class="flex cursor-pointer items-center gap-2 text-xs text-zinc-500">
                                        <input v-model="selectedSubtaskIds" type="checkbox" :value="sub.id" class="h-3.5 w-3.5 rounded border-zinc-300 text-cyan-600" />
                                        <span class="truncate">{{ sub.title }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <footer class="flex items-center justify-between border-t border-zinc-200 px-4 py-3 dark:border-zinc-800">
                <span class="text-xs text-zinc-500">Выбрано: {{ selectedTaskIds.length + selectedSubtaskIds.length }}</span>
                <div class="flex gap-2">
                    <button type="button" class="action-secondary" @click="emit('close')">Отмена</button>
                    <button
                        type="button"
                        class="action-primary"
                        :disabled="isSending || (!selectedTaskIds.length && !selectedSubtaskIds.length)"
                        @click="sendCompanyReminders"
                    >
                        {{ isSending ? 'Отправка…' : 'Отправить' }}
                    </button>
                </div>
            </footer>
        </div>
    </div>
</template>

<style scoped>
.action-primary { @apply inline-flex h-9 items-center justify-center gap-1.5 rounded-lg bg-cyan-600 px-3 text-xs font-semibold text-white transition hover:bg-cyan-700 disabled:cursor-not-allowed disabled:opacity-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400; }
.action-secondary { @apply inline-flex h-9 items-center justify-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 text-xs font-medium text-zinc-700 transition hover:bg-zinc-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800; }
.icon-button { @apply grid h-8 w-8 place-items-center rounded-lg text-sm text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-white; }
</style>
