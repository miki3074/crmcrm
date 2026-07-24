<script setup>
import { computed, ref } from 'vue'
import axios from 'axios'
import PollCreateModal from './PollCreateModal.vue'
import PollListModal from './PollListModal.vue'

const props = defineProps({
    company: Object,
    isOwner: Boolean,
    isAdmin: Boolean,
    canCreate: Boolean
})

const emit = defineEmits(['create', 'openMembers'])

// Состояния для модалок опросов
const showPollCreateModal = ref(false)
const showPollListModal = ref(false)
const showCompanyReminderModal = ref(false)
const isLoading = ref(false)
const isSending = ref(false)
const projectsData = ref([])

// Выбранные ID
const selectedTaskIds = ref([])
const selectedSubtaskIds = ref([])

// Проверяем, может ли пользователь создавать опросы
// Теперь проверяем is_member ИЛИ is_owner
const canCreatePoll = computed(() => {
    return props.company?.is_member === true || props.company?.is_owner === true || props.isOwner === true
})

// Проверяем роль пользователя
const userRole = computed(() => {
    if (props.isOwner || props.company?.is_owner) return 'owner'
    return props.company?.user_role || null
})

const canCreateProject = computed(() => {
    return props.isOwner || props.company?.is_owner || userRole.value === 'manager'
})

// Открыть модалку создания опроса
const openPollCreateModal = () => {
    showPollCreateModal.value = true
}

// Открыть модалку списка опросов
const openPollListModal = () => {
    showPollListModal.value = true
}

// Открыть модалку напоминаний
const openCompanyReminderModal = async () => {
    showCompanyReminderModal.value = true
    isLoading.value = true
    try {
        const res = await axios.get(`/api/companies/${props.company.id}/stagnant-items`)
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

// Выбрать/снять всё в конкретном проекте
const toggleProject = (project) => {
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
        const res = await axios.post(`/api/companies/${props.company.id}/remind-stagnant`, {
            task_ids: selectedTaskIds.value,
            subtask_ids: selectedSubtaskIds.value
        })
        alert(res.data.message)
        showCompanyReminderModal.value = false
    } catch (e) {
        alert('Ошибка при отправке')
    } finally {
        isSending.value = false
    }
}

// Обработчик успешного создания опроса
const handlePollCreated = () => {
    showPollCreateModal.value = false
}
</script>

<template>
    <section class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-4">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
            <div class="flex min-w-0 flex-1 items-center gap-3">
                <img v-if="company?.logo" :src="`/storage/${company.logo}`" alt="Logo" class="h-12 w-12 shrink-0 rounded-lg border border-slate-200 object-cover dark:border-slate-700" />
                <div v-else class="grid h-12 w-12 shrink-0 place-items-center rounded-lg bg-slate-900 text-lg text-white dark:bg-white dark:text-slate-900">🏢</div>

                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="truncate text-xl font-semibold tracking-tight text-slate-950 dark:text-white sm:text-2xl">{{ company?.name || 'Компания' }}</h1>
                        <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-500 dark:bg-slate-800">#{{ company?.id }}</span>
                        <span v-if="isOwner || company?.is_owner" class="rounded bg-amber-50 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">Владелец</span>
                        <span v-else-if="userRole === 'manager'" class="rounded bg-indigo-50 px-1.5 py-0.5 text-[10px] font-semibold text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300">Менеджер</span>
                        <span v-else-if="company?.is_member" class="rounded bg-emerald-50 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">Участник</span>
                    </div>
                  
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button class="action-secondary" @click="openPollListModal">📊 <span>Опросы</span></button>
                <button v-if="canCreatePoll" class="action-secondary" @click="openPollCreateModal">＋ <span>Опрос</span></button>
                <button v-if="isOwner || company?.is_owner || userRole === 'manager'" class="action-secondary" title="Напомнить о задачах с нулевым прогрессом" @click="openCompanyReminderModal">🔔 <span class="hidden sm:inline">Напомнить</span></button>
                <button v-if="canCreateProject" class="action-primary" @click="$emit('create')">＋ Новый проект</button>
            </div>
        </div>
    </section>

    <div v-if="showCompanyReminderModal" class="fixed inset-0 z-[60] grid place-items-center bg-slate-950/60 p-3 backdrop-blur-sm">
        <div class="flex max-h-[88vh] w-full max-w-2xl flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-900">
            <header class="flex items-center justify-between border-b border-slate-200 px-4 py-3 dark:border-slate-800">
                <div>
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">Напоминания о задачах</h3>
                    <p class="text-xs text-slate-500">Выберите задачи с прогрессом 0%</p>
                </div>
                <button class="icon-button" @click="showCompanyReminderModal = false">✕</button>
            </header>

            <div class="min-h-0 flex-1 overflow-y-auto p-3">
                <div v-if="isLoading" class="py-16 text-center text-sm text-slate-500">Загрузка…</div>
                <div v-else-if="!projectsData.length" class="py-16 text-center text-sm text-slate-500">Нет задач с нулевым прогрессом</div>
                <div v-else class="space-y-2">
                    <section v-for="project in projectsData" :key="project.id" class="rounded-lg border border-slate-200 dark:border-slate-800">
                        <div class="flex items-center justify-between border-b border-slate-100 px-3 py-2 dark:border-slate-800">
                            <span class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ project.name }}</span>
                            <button class="text-xs font-medium text-indigo-600 dark:text-indigo-400" @click="toggleProject(project)">Выбрать всё</button>
                        </div>
                        <div class="space-y-1 p-2">
                            <div v-for="task in project.tasks" :key="task.id" class="rounded-md px-2 py-1.5 hover:bg-slate-50 dark:hover:bg-slate-800/60">
                                <label class="flex cursor-pointer items-center gap-2 text-sm">
                                    <input v-model="selectedTaskIds" type="checkbox" :value="task.id" class="rounded border-slate-300 text-indigo-600" />
                                    <span class="truncate">{{ task.title }}</span>
                                </label>
                                <div v-if="task.subtasks?.length" class="ml-5 mt-1 space-y-1 border-l border-slate-200 pl-3 dark:border-slate-700">
                                    <label v-for="sub in task.subtasks" :key="sub.id" class="flex cursor-pointer items-center gap-2 text-xs text-slate-500">
                                        <input v-model="selectedSubtaskIds" type="checkbox" :value="sub.id" class="h-3.5 w-3.5 rounded border-slate-300 text-indigo-600" />
                                        <span class="truncate">{{ sub.title }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <footer class="flex items-center justify-between border-t border-slate-200 px-4 py-3 dark:border-slate-800">
                <span class="text-xs text-slate-500">Выбрано: {{ selectedTaskIds.length + selectedSubtaskIds.length }}</span>
                <div class="flex gap-2">
                    <button class="action-secondary" @click="showCompanyReminderModal = false">Отмена</button>
                    <button class="action-primary" :disabled="isSending || (!selectedTaskIds.length && !selectedSubtaskIds.length)" @click="sendCompanyReminders">
                        {{ isSending ? 'Отправка…' : 'Отправить' }}
                    </button>
                </div>
            </footer>
        </div>
    </div>

    <PollCreateModal v-if="showPollCreateModal" :company-id="company?.id" @close="showPollCreateModal = false" @created="handlePollCreated" />
    <PollListModal v-if="showPollListModal" :company-id="company?.id" @close="showPollListModal = false" />
</template>

<style scoped>
.action-primary { @apply inline-flex h-9 items-center justify-center gap-1.5 rounded-lg bg-indigo-600 px-3 text-xs font-semibold text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50; }
.action-secondary { @apply inline-flex h-9 items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800; }
.icon-button { @apply grid h-8 w-8 place-items-center rounded-lg text-sm text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-white; }
</style>
