<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'
import { router } from '@inertiajs/vue3'

const props = defineProps(['project', 'user', 'employees'])
const emit = defineEmits(['refresh'])

// --- State ---
const activeModal = ref(null)
const nameForm = ref('');
const budgetForm = ref('');
const descForm = ref('')
const selectedUser = ref(null);
const selectedUsers = ref([])
const replaceForm = ref({ old: null, new: null })
const searchQuery = ref('')
const activeTab = ref('managers')

// --- Permissions ---
const isOwner = computed(() => props.project.company?.user_id === props.user.id)
const isManager = computed(() => props.project.managers?.some(m => m.id === props.user.id))
const isExecutor = computed(() => props.project.executors?.some(e => e.id === props.user.id))
const isInitiator = computed(() => props.project.initiator_id === props.user.id)

const canEdit = computed(() => isOwner.value || isManager.value || isExecutor.value)
const canEditBudget = computed(() => isOwner.value)
const canManageTeam = computed(() => isOwner.value || isManager.value || isInitiator.value)
const canDelete = computed(() => isOwner.value || isInitiator.value)

// --- Computed ---
const filteredEmployees = computed(() => {
    if (!searchQuery.value) return props.employees || []
    const query = searchQuery.value.toLowerCase()
    return props.employees.filter(e =>
        e.name.toLowerCase().includes(query) ||
        e.email?.toLowerCase().includes(query)
    )
})

// --- Actions ---
const openModal = (type) => {
    activeModal.value = type
    nameForm.value = props.project.name
    budgetForm.value = props.project.budget
    descForm.value = props.project.description
    selectedUser.value = null
    selectedUsers.value = []
    searchQuery.value = ''
}

const closeModal = () => {
    activeModal.value = null
}

const saveName = async () => {
    await axios.patch(`/api/projects/${props.project.id}/name`, { name: nameForm.value })
    emit('refresh'); closeModal()
}

const saveBudget = async () => {
    await axios.patch(`/api/projects/${props.project.id}/budget`, { budget: budgetForm.value })
    emit('refresh'); closeModal()
}

const saveDesc = async () => {
    await axios.patch(`/api/projects/${props.project.id}/description`, { description: descForm.value })
    emit('refresh'); closeModal()
}

const deleteProject = async () => {
    if (!confirm('Вы уверены? Это действие нельзя отменить.')) return
    await axios.delete(`/api/projects/${props.project.id}`)
    router.visit('/')
}

const addRole = async (role) => {
    const payload = role === 'executors' ? { user_ids: selectedUsers.value } : { user_id: selectedUser.value }
    const url = role === 'executors' ? 'executors' : role === 'add-manager' ? 'add-manager' : 'watchers'
    try {
        await axios.post(`/api/projects/${props.project.id}/${url}`, payload)
        emit('refresh'); closeModal()
    } catch(e) {
        alert(e.response?.data?.message || 'Ошибка')
    }
}

const replaceManager = async () => {
    try {
        await axios.post(`/api/projects/${props.project.id}/replace-manager`, {
            old_manager_id: replaceForm.value.old,
            new_manager_id: replaceForm.value.new
        })
        emit('refresh'); closeModal()
    } catch(e) {
        alert('Ошибка')
    }
}

const removeMember = async (role, id) => {
    if (role === 'manager' && props.project.managers.length <= 1) {
        alert('В проекте должен остаться хотя бы один руководитель')
        return
    }
    await axios.delete(`/api/projects/${props.project.id}/members`, { data: { user_id: id, role }})
    emit('refresh')
}
</script>

<template>
    <div class="space-y-6">

        <!-- Панель навигации и действий -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700 overflow-hidden">

            <!-- Верхняя панель с навигацией -->
            <div class="flex flex-wrap items-center justify-between gap-4 p-4 bg-gradient-to-r from-slate-50 to-white dark:from-slate-800 dark:to-slate-800 border-b border-slate-100 dark:border-slate-700">

                <!-- Назад к компании -->
                <button v-if="project.company"
                        @click="router.visit(`/companies/${project.company.id}`)"
                        class="group flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition-all">
                    <svg class="w-4 h-4 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Назад к компании
                </button>

                <!-- Статистика проекта -->
                <div class="flex items-center gap-3">
                    <div class="px-3 py-1.5 bg-indigo-50 dark:bg-indigo-900/30 rounded-xl">
                        <span class="text-xs font-medium text-indigo-600 dark:text-indigo-400">
                            {{ project.tasks?.length || 0 }} задач
                        </span>
                    </div>
                    <div class="px-3 py-1.5 bg-emerald-50 dark:bg-emerald-900/30 rounded-xl">
                        <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400">
                            {{ project.managers?.length || 0 }} руководителей
                        </span>
                    </div>
                </div>
            </div>

            <!-- Основные действия -->
            <div class="p-4 grid grid-cols-2 sm:grid-cols-4 gap-2">
                <button v-if="canEdit" @click="openModal('name')"
                        class="action-button bg-gradient-to-br from-indigo-50 to-indigo-100 dark:from-indigo-950/30 dark:to-indigo-900/30 border-indigo-200 dark:border-indigo-800 group">
                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                    <span class="text-xs font-medium text-indigo-700 dark:text-indigo-300">Название</span>
                </button>

                <button v-if="canEditBudget" @click="openModal('budget')"
                        class="action-button bg-gradient-to-br from-emerald-50 to-emerald-100 dark:from-emerald-950/30 dark:to-emerald-900/30 border-emerald-200 dark:border-emerald-800 group">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-xs font-medium text-emerald-700 dark:text-emerald-300">Бюджет</span>
                </button>

                <button v-if="canEdit" @click="openModal('desc')"
                        class="action-button bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-950/30 dark:to-purple-900/30 border-purple-200 dark:border-purple-800 group">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span class="text-xs font-medium text-purple-700 dark:text-purple-300">Описание</span>
                </button>

                <button v-if="canDelete" @click="deleteProject"
                        class="action-button bg-gradient-to-br from-rose-50 to-rose-100 dark:from-rose-950/30 dark:to-rose-900/30 border-rose-200 dark:border-rose-800 group hover:border-rose-300">
                    <svg class="w-5 h-5 text-rose-600 dark:text-rose-400 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    <span class="text-xs font-medium text-rose-700 dark:text-rose-300">Удалить</span>
                </button>
            </div>
        </div>

        <!-- Панель управления командой -->
        <div v-if="canManageTeam" class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700 overflow-hidden">

            <!-- Заголовок -->
            <div class="p-4 border-b border-slate-100 dark:border-slate-700">
                <div class="flex items-center gap-3">
                    <div class="w-1 h-6 bg-gradient-to-b from-indigo-500 to-purple-500 rounded-full"></div>
                    <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-300">
                        Управление командой
                    </h4>
                </div>
            </div>

            <!-- Кнопки действий -->
            <div class="p-4 grid grid-cols-2 sm:grid-cols-4 gap-3">
                <button @click="openModal('addManager')"
                        class="team-button bg-gradient-to-br from-emerald-50 to-emerald-100 dark:from-emerald-950/30 dark:to-emerald-900/30 border border-emerald-200 dark:border-emerald-800 hover:border-emerald-400">
                    <span class="text-xl">👑</span>
                    <span class="text-xs font-medium text-emerald-700 dark:text-emerald-300">Руководитель</span>
                </button>

                <button @click="openModal('replaceManager')"
                        class="team-button bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-950/30 dark:to-amber-900/30 border border-amber-200 dark:border-amber-800 hover:border-amber-400">
                    <span class="text-xl">🔄</span>
                    <span class="text-xs font-medium text-amber-700 dark:text-amber-300">Сменить</span>
                </button>

                <button @click="openModal('addExecutor')"
                        class="team-button bg-gradient-to-br from-indigo-50 to-indigo-100 dark:from-indigo-950/30 dark:to-indigo-900/30 border border-indigo-200 dark:border-indigo-800 hover:border-indigo-400">
                    <span class="text-xl">🛠️</span>
                    <span class="text-xs font-medium text-indigo-700 dark:text-indigo-300">Исполнитель</span>
                </button>

                <button @click="openModal('addWatcher')"
                        class="team-button bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-950/30 dark:to-purple-900/30 border border-purple-200 dark:border-purple-800 hover:border-purple-400">
                    <span class="text-xl">👀</span>
                    <span class="text-xs font-medium text-purple-700 dark:text-purple-300">Наблюдатель</span>
                </button>
            </div>

            <!-- Краткий список участников -->
            <div class="p-4 pt-0">
                <button @click="openModal('manageList')"
                        class="w-full py-3 px-4 rounded-xl bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-700 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition-all flex items-center justify-center gap-2">
                    <span>👥</span>
                    Показать всех участников
                    <span class="text-xs bg-white dark:bg-slate-600 px-2 py-0.5 rounded-full">
                        {{ (project.managers?.length || 0) + (project.executors?.length || 0) + (project.watchers?.length || 0) }}
                    </span>
                </button>
            </div>
        </div>

        <!-- MODAL (Современный дизайн) -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95">

            <div v-if="activeModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <!-- Backdrop с эффектом стекла -->
                <div class="absolute inset-0 bg-slate-900/40 dark:bg-black/60 backdrop-blur-md" @click="closeModal"></div>

                <!-- Modal Content -->
                <div class="relative w-full max-w-lg bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden flex flex-col max-h-[85vh]">

                    <!-- Декоративная полоса сверху -->
                    <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500"></div>

                    <!-- Header -->
                    <div class="relative px-6 py-5 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white text-lg shadow-lg">
                                    {{
                                        activeModal === 'name' ? '📝' :
                                            activeModal === 'budget' ? '💰' :
                                                activeModal === 'desc' ? '📄' :
                                                    activeModal === 'addManager' ? '👑' :
                                                        activeModal === 'replaceManager' ? '🔄' :
                                                            activeModal === 'addExecutor' ? '🛠️' :
                                                                activeModal === 'addWatcher' ? '👀' :
                                                                    '👥'
                                    }}
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                                        {{
                                            activeModal === 'name' ? 'Изменить название' :
                                                activeModal === 'budget' ? 'Управление бюджетом' :
                                                    activeModal === 'desc' ? 'Редактировать описание' :
                                                        activeModal === 'addManager' ? 'Добавить руководителя' :
                                                            activeModal === 'replaceManager' ? 'Смена руководителя' :
                                                                activeModal === 'addExecutor' ? 'Добавить исполнителей' :
                                                                    activeModal === 'addWatcher' ? 'Добавить наблюдателя' :
                                                                        'Участники проекта'
                                        }}
                                    </h3>
                                    <p class="text-xs text-slate-500 mt-1">
                                        {{ activeModal === 'manageList' ? 'Управление участниками проекта' : 'Заполните информацию' }}
                                    </p>
                                </div>
                            </div>
                            <button @click="closeModal"
                                    class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 flex items-center justify-center text-slate-500 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="flex-1 overflow-y-auto custom-scrollbar p-6">

                        <!-- Name Form -->
                        <div v-if="activeModal === 'name'" class="space-y-4">
                            <div class="relative">
                                <span class="absolute left-3 top-3 text-slate-400">📋</span>
                                <input v-model="nameForm" type="text"
                                       class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:border-indigo-300 focus:ring focus:ring-indigo-200/20 transition"
                                       placeholder="Введите название проекта" autofocus />
                            </div>
                            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                                <button @click="closeModal" class="px-4 py-2 text-slate-500 hover:text-slate-700 font-medium transition">Отмена</button>
                                <button @click="saveName" class="px-6 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-medium shadow-lg shadow-indigo-500/30 hover:shadow-xl hover:scale-105 transition-all">
                                    Сохранить
                                </button>
                            </div>
                        </div>

                        <!-- Budget Form -->
                        <div v-if="activeModal === 'budget'" class="space-y-4">
                            <div class="relative">
                                <span class="absolute left-3 top-3 text-slate-400">₽</span>
                                <input v-model="budgetForm" type="number"
                                       class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:border-indigo-300 focus:ring focus:ring-indigo-200/20 transition"
                                       placeholder="0.00" />
                            </div>
                            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                                <button @click="closeModal" class="px-4 py-2 text-slate-500 hover:text-slate-700 font-medium transition">Отмена</button>
                                <button @click="saveBudget" class="px-6 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-medium shadow-lg shadow-indigo-500/30 hover:shadow-xl hover:scale-105 transition-all">
                                    Сохранить
                                </button>
                            </div>
                        </div>

                        <!-- Description Form -->
                        <div v-if="activeModal === 'desc'" class="space-y-4">
                            <textarea v-model="descForm" rows="5"
                                      class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:border-indigo-300 focus:ring focus:ring-indigo-200/20 transition"
                                      placeholder="Опишите задачи проекта..."></textarea>
                            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                                <button @click="closeModal" class="px-4 py-2 text-slate-500 hover:text-slate-700 font-medium transition">Отмена</button>
                                <button @click="saveDesc" class="px-6 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-medium shadow-lg shadow-indigo-500/30 hover:shadow-xl hover:scale-105 transition-all">
                                    Сохранить
                                </button>
                            </div>
                        </div>

                        <!-- Single User Select -->
                        <div v-if="['addManager', 'addWatcher'].includes(activeModal)" class="space-y-4">
                            <!-- Поиск -->
                            <div class="relative">
                                <span class="absolute left-3 top-3 text-slate-400">🔍</span>
                                <input v-model="searchQuery" type="text"
                                       class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:border-indigo-300 focus:ring focus:ring-indigo-200/20 transition"
                                       placeholder="Поиск сотрудников..." />
                            </div>

                            <!-- Список сотрудников -->
                            <div class="max-h-60 overflow-y-auto rounded-xl border border-slate-200 dark:border-slate-700 divide-y divide-slate-100 dark:divide-slate-800">
                                <div v-for="e in filteredEmployees" :key="e.id"
                                     @click="selectedUser = e.id"
                                     class="flex items-center gap-3 p-3 cursor-pointer transition"
                                     :class="selectedUser === e.id ? 'bg-indigo-50 dark:bg-indigo-900/30' : 'hover:bg-slate-50 dark:hover:bg-slate-800'">
                                    <div class="w-5 h-5 rounded border border-slate-300 dark:border-slate-600 flex items-center justify-center"
                                         :class="selectedUser === e.id ? 'bg-indigo-600 border-indigo-600' : ''">
                                        <svg v-if="selectedUser === e.id" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white text-xs font-bold">
                                        {{ e.name.charAt(0) }}
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ e.name }}</div>
                                        <div class="text-xs text-slate-400">{{ e.email }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                                <button @click="closeModal" class="px-4 py-2 text-slate-500 hover:text-slate-700 font-medium transition">Отмена</button>
                                <button @click="addRole(activeModal === 'addManager' ? 'add-manager' : 'watchers')"
                                        :disabled="!selectedUser"
                                        class="px-6 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-medium shadow-lg shadow-indigo-500/30 hover:shadow-xl hover:scale-105 transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100">
                                    Добавить
                                </button>
                            </div>
                        </div>

                        <!-- Multiple User Select -->
                        <div v-if="activeModal === 'addExecutor'" class="space-y-4">
                            <!-- Поиск -->
                            <div class="relative">
                                <span class="absolute left-3 top-3 text-slate-400">🔍</span>
                                <input v-model="searchQuery" type="text"
                                       class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:border-indigo-300 focus:ring focus:ring-indigo-200/20 transition"
                                       placeholder="Поиск сотрудников..." />
                            </div>

                            <!-- Список сотрудников с чекбоксами -->
                            <div class="max-h-60 overflow-y-auto rounded-xl border border-slate-200 dark:border-slate-700 divide-y divide-slate-100 dark:divide-slate-800">
                                <label v-for="e in filteredEmployees" :key="e.id"
                                       class="flex items-center gap-3 p-3 cursor-pointer transition hover:bg-slate-50 dark:hover:bg-slate-800">
                                    <input type="checkbox" v-model="selectedUsers" :value="e.id"
                                           class="w-4 h-4 text-indigo-600 rounded focus:ring-indigo-500">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white text-xs font-bold">
                                        {{ e.name.charAt(0) }}
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ e.name }}</div>
                                        <div class="text-xs text-slate-400">{{ e.email }}</div>
                                    </div>
                                </label>
                            </div>

                            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                                <button @click="closeModal" class="px-4 py-2 text-slate-500 hover:text-slate-700 font-medium transition">Отмена</button>
                                <button @click="addRole('executors')"
                                        :disabled="!selectedUsers.length"
                                        class="px-6 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-medium shadow-lg shadow-indigo-500/30 hover:shadow-xl hover:scale-105 transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100">
                                    Добавить ({{ selectedUsers.length }})
                                </button>
                            </div>
                        </div>

                        <!-- Replace Manager -->
                        <div v-if="activeModal === 'replaceManager'" class="space-y-4">
                            <div class="space-y-2">
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">Текущий руководитель</label>
                                <select v-model="replaceForm.old" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:border-indigo-300 focus:ring focus:ring-indigo-200/20 transition">
                                    <option :value="null">Выберите кого заменить</option>
                                    <option v-for="m in project.managers" :value="m.id" :key="m.id">{{ m.name }}</option>
                                </select>
                            </div>

                            <div class="space-y-2">
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">Новый руководитель</label>
                                <select v-model="replaceForm.new" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:border-indigo-300 focus:ring focus:ring-indigo-200/20 transition">
                                    <option :value="null">Выберите преемника</option>
                                    <option v-for="e in employees" :value="e.id" :key="e.id">{{ e.name }}</option>
                                </select>
                            </div>

                            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                                <button @click="closeModal" class="px-4 py-2 text-slate-500 hover:text-slate-700 font-medium transition">Отмена</button>
                                <button @click="replaceManager"
                                        :disabled="!replaceForm.old || !replaceForm.new"
                                        class="px-6 py-2 bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded-xl font-medium shadow-lg shadow-amber-500/30 hover:shadow-xl hover:scale-105 transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100">
                                    Заменить
                                </button>
                            </div>
                        </div>

                        <!-- Manage Members List -->
                        <div v-if="activeModal === 'manageList'" class="space-y-6">
                            <!-- Табы -->
                            <div class="flex gap-2 border-b border-slate-100 dark:border-slate-800">
                                <button @click="activeTab = 'managers'"
                                        class="px-4 py-2 text-sm font-medium transition-all relative"
                                        :class="activeTab === 'managers' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-500 hover:text-slate-700'">
                                    Руководители
                                    <span class="ml-2 text-xs px-1.5 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800">
                                        {{ project.managers?.length || 0 }}
                                    </span>
                                    <div v-if="activeTab === 'managers'"
                                         class="absolute bottom-0 left-0 right-0 h-0.5 bg-gradient-to-r from-indigo-500 to-purple-500"></div>
                                </button>
                                <button @click="activeTab = 'executors'"
                                        class="px-4 py-2 text-sm font-medium transition-all relative"
                                        :class="activeTab === 'executors' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-500 hover:text-slate-700'">
                                    Исполнители
                                    <span class="ml-2 text-xs px-1.5 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800">
                                        {{ project.executors?.length || 0 }}
                                    </span>
                                    <div v-if="activeTab === 'executors'"
                                         class="absolute bottom-0 left-0 right-0 h-0.5 bg-gradient-to-r from-indigo-500 to-purple-500"></div>
                                </button>
                                <button @click="activeTab = 'watchers'"
                                        class="px-4 py-2 text-sm font-medium transition-all relative"
                                        :class="activeTab === 'watchers' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-500 hover:text-slate-700'">
                                    Наблюдатели
                                    <span class="ml-2 text-xs px-1.5 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800">
                                        {{ project.watchers?.length || 0 }}
                                    </span>
                                    <div v-if="activeTab === 'watchers'"
                                         class="absolute bottom-0 left-0 right-0 h-0.5 bg-gradient-to-r from-indigo-500 to-purple-500"></div>
                                </button>
                            </div>

                            <!-- Managers List -->
                            <div v-if="activeTab === 'managers'" class="space-y-2">
                                <div v-if="!project.managers?.length" class="text-center py-8 text-slate-400">
                                    <span class="text-4xl mb-2 block opacity-30">👑</span>
                                    <p>Руководители не назначены</p>
                                </div>
                                <div v-for="m in project.managers" :key="m.id"
                                     class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 group hover:border-emerald-200 dark:hover:border-emerald-800 transition-all">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-emerald-500 to-teal-500 flex items-center justify-center text-white text-xs font-bold">
                                            {{ m.name.charAt(0) }}
                                        </div>
                                        <span class="font-medium text-slate-700 dark:text-slate-200">{{ m.name }}</span>
                                    </div>
                                    <button @click="removeMember('manager', m.id)"
                                            class="opacity-0 group-hover:opacity-100 px-3 py-1.5 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 text-xs font-medium transition-all">
                                        Исключить
                                    </button>
                                </div>
                            </div>

                            <!-- Executors List -->
                            <div v-if="activeTab === 'executors'" class="space-y-2">
                                <div v-if="!project.executors?.length" class="text-center py-8 text-slate-400">
                                    <span class="text-4xl mb-2 block opacity-30">🛠️</span>
                                    <p>Исполнители не назначены</p>
                                </div>
                                <div v-for="e in project.executors" :key="e.id"
                                     class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 group hover:border-indigo-200 dark:hover:border-indigo-800 transition-all">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white text-xs font-bold">
                                            {{ e.name.charAt(0) }}
                                        </div>
                                        <span class="font-medium text-slate-700 dark:text-slate-200">{{ e.name }}</span>
                                    </div>
                                    <button @click="removeMember('executor', e.id)"
                                            class="opacity-0 group-hover:opacity-100 px-3 py-1.5 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 text-xs font-medium transition-all">
                                        Исключить
                                    </button>
                                </div>
                            </div>

                            <!-- Watchers List -->
                            <div v-if="activeTab === 'watchers'" class="space-y-2">
                                <div v-if="!project.watchers?.length" class="text-center py-8 text-slate-400">
                                    <span class="text-4xl mb-2 block opacity-30">👀</span>
                                    <p>Наблюдатели не назначены</p>
                                </div>
                                <div v-for="w in project.watchers" :key="w.id"
                                     class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 group hover:border-purple-200 dark:hover:border-purple-800 transition-all">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white text-xs font-bold">
                                            {{ w.name.charAt(0) }}
                                        </div>
                                        <span class="font-medium text-slate-700 dark:text-slate-200">{{ w.name }}</span>
                                    </div>
                                    <button @click="removeMember('watcher', w.id)"
                                            class="opacity-0 group-hover:opacity-100 px-3 py-1.5 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 text-xs font-medium transition-all">
                                        Исключить
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

    </div>
</template>

<style scoped>
/* Action Buttons */
.action-button {
    @apply flex flex-col items-center justify-center gap-2 p-3 rounded-xl border transition-all duration-300 hover:shadow-lg hover:scale-105 active:scale-95;
}

/* Team Buttons */
.team-button {
    @apply flex flex-col items-center justify-center gap-2 p-4 rounded-xl transition-all duration-300 hover:shadow-lg hover:scale-105 active:scale-95;
}

/* Custom Scrollbar */
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 20px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #475569;
}

/* Animations */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.fade-enter-active,
.fade-leave-active {
    transition: all 0.3s ease;
}

.fade-enter-from {
    opacity: 0;
    transform: translateY(10px);
}

.fade-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}
</style>
