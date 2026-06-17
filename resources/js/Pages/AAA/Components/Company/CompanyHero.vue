<!-- resources/js/components/CompanyHeader.vue -->
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
    <div class="relative overflow-hidden rounded-3xl shadow-xl mb-8 group">
        <!-- Фон с градиентом и шумом -->
        <div class="absolute inset-0 bg-gradient-to-r from-indigo-600 via-purple-600 to-fuchsia-600 transition-all duration-1000 group-hover:scale-105"></div>
        <div class="absolute inset-0 bg-white/10 backdrop-blur-[1px]"></div>

        <div class="relative px-6 py-10 sm:px-10 text-white flex flex-col md:flex-row items-center md:items-start gap-6">
            <!-- Лого -->
            <div class="shrink-0 p-1 bg-white/30 backdrop-blur-md rounded-2xl">
                <img
                    v-if="company?.logo"
                    :src="`/storage/${company.logo}`"
                    alt="Logo"
                    class="w-20 h-20 rounded-xl object-cover bg-white"
                />
                <div v-else class="w-20 h-20 rounded-xl bg-white/20 flex items-center justify-center text-3xl">
                    🏢
                </div>
            </div>

            <!-- Инфо -->
            <div class="text-center md:text-left flex-1">
                <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-white drop-shadow-sm">
                    {{ company?.name || 'Компания' }}
                </h1>
                <div class="flex flex-wrap items-center justify-center md:justify-start gap-3 mt-2 text-indigo-100">
                    <span class="px-2 py-0.5 rounded-md bg-white/20 text-xs font-mono">ID: {{ company?.id }}</span>
                    <span v-if="isOwner || company?.is_owner" class="px-2 py-0.5 rounded-full bg-amber-400/20 text-amber-100 border border-amber-400/30 text-xs font-bold uppercase tracking-wider">Владелец</span>
                    <span v-else-if="userRole === 'manager'" class="px-2 py-0.5 rounded-full bg-blue-400/20 text-blue-100 border border-blue-400/30 text-xs font-bold uppercase tracking-wider">Менеджер</span>
                    <span v-else-if="company?.is_member" class="px-2 py-0.5 rounded-full bg-emerald-400/20 text-emerald-100 border border-emerald-400/30 text-xs font-bold uppercase tracking-wider">Участник</span>
                </div>
            </div>

            <!-- Кнопки -->
            <div class="flex flex-wrap justify-center gap-3 mt-4 md:mt-0">
                <!-- Кнопка опросов (доступна всем) -->
                <button
                    @click="openPollListModal"
                    class="px-5 py-2.5 rounded-xl bg-emerald-500/20 hover:bg-emerald-500/30 border border-emerald-500/30 backdrop-blur-md transition text-sm font-semibold text-emerald-100 flex items-center justify-center gap-2"
                >
                    <span>📊</span>
                    <span class="hidden lg:inline">Опросы</span>
                </button>

                <!-- Кнопка создания опроса (доступна всем участникам компании, включая владельца) -->
                <button
                    v-if="canCreatePoll"
                    @click="openPollCreateModal"
                    class="px-5 py-2.5 rounded-xl bg-white text-indigo-600 shadow-lg shadow-indigo-900/20 hover:shadow-xl hover:bg-indigo-50 transition text-sm font-bold flex items-center justify-center gap-2"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Новый опрос
                </button>

                <!-- Кнопка напоминаний (только для владельца и менеджеров) -->
                <button
                    v-if="isOwner || company?.is_owner || userRole === 'manager'"
                    @click="openCompanyReminderModal"
                    class="px-5 py-2.5 rounded-xl bg-rose-500/20 hover:bg-rose-500/30 border border-rose-500/30 backdrop-blur-md transition text-sm font-semibold text-rose-100 flex items-center justify-center gap-2"
                    title="Напомнить всем про задачи с 0% прогрессом"
                >
                    <span>🔔</span>
                    <span class="hidden lg:inline">Напомнить о задачах</span>
                </button>

                <!-- Кнопка создания проекта -->
                <button
                    v-if="canCreateProject"
                    @click="$emit('create')"
                    class="px-5 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 border border-white/20 backdrop-blur-md transition text-sm font-semibold flex items-center justify-center gap-2"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Новый проект
                </button>
            </div>
        </div>
    </div>

    <!-- Модалка напоминаний -->
    <div v-if="showCompanyReminderModal" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
        <div class="w-full max-w-3xl rounded-2xl bg-white dark:bg-slate-800 shadow-2xl flex flex-col max-h-[90vh]">
            <div class="p-6 border-b dark:border-slate-700 flex justify-between items-center">
                <h3 class="text-xl font-bold">Рассылка напоминаний (0% прогресс)</h3>
                <button @click="showCompanyReminderModal = false" class="text-slate-400 hover:text-white">✕</button>
            </div>

            <div class="p-6 overflow-y-auto">
                <div v-if="isLoading" class="text-center py-10">Загрузка проектов и задач...</div>

                <div v-else-if="projectsData.length === 0" class="text-center py-10 opacity-50">
                    Нет задач с 0% прогрессом во всей компании.
                </div>

                <div v-else class="space-y-8">
                    <div v-for="project in projectsData" :key="project.id" class="border dark:border-slate-700 rounded-xl p-4">
                        <div class="flex items-center justify-between mb-4 bg-slate-50 dark:bg-slate-900/50 p-2 rounded-lg">
                            <span class="font-bold text-indigo-400">Проект: {{ project.name }}</span>
                            <button @click="toggleProject(project)" class="text-xs bg-indigo-500/10 text-indigo-400 px-2 py-1 rounded">
                                Выбрать всё в проекте
                            </button>
                        </div>

                        <div class="space-y-3 pl-2">
                            <div v-for="task in project.tasks" :key="task.id" class="space-y-1">
                                <label class="flex items-center gap-2 cursor-pointer group">
                                    <input type="checkbox" :value="task.id" v-model="selectedTaskIds" class="rounded border-slate-700 text-indigo-600">
                                    <span class="text-sm font-medium group-hover:text-indigo-400">{{ task.title }}</span>
                                </label>

                                <div v-if="task.subtasks.length" class="pl-6 space-y-1 border-l border-slate-700 ml-2 mt-1">
                                    <label v-for="sub in task.subtasks" :key="sub.id" class="flex items-center gap-2 cursor-pointer group">
                                        <input type="checkbox" :value="sub.id" v-model="selectedSubtaskIds" class="rounded border-slate-700 text-rose-500 w-3 h-3">
                                        <span class="text-xs opacity-80 group-hover:text-rose-400">{{ sub.title }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-6 border-t dark:border-slate-700 flex justify-end gap-3">
                <button @click="showCompanyReminderModal = false" class="btn-ghost">Отмена</button>
                <button
                    @click="sendCompanyReminders"
                    :disabled="isSending || (!selectedTaskIds.length && !selectedSubtaskIds.length)"
                    class="bg-rose-600 hover:bg-rose-700 text-white px-6 py-2 rounded-xl font-bold transition disabled:opacity-50"
                >
                    {{ isSending ? 'Отправка...' : 'Отправить всем выбранным' }}
                </button>
            </div>
        </div>
    </div>

    <!-- Модалка создания опроса -->
    <PollCreateModal
        v-if="showPollCreateModal"
        :company-id="company?.id"
        @close="showPollCreateModal = false"
        @created="handlePollCreated"
    />

    <!-- Модалка списка опросов -->
    <PollListModal
        v-if="showPollListModal"
        :company-id="company?.id"
        @close="showPollListModal = false"
    />
</template>
