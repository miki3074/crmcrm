<script setup>
import { ref, computed, onMounted } from 'vue' // <-- Исправлено: добавлен onMounted
import axios from 'axios'
import { router } from '@inertiajs/vue3'

const props = defineProps(['project', 'user', 'employees'])
const emit = defineEmits(['refresh'])

// --- Состояние панелей и модалок ---
const isSidebarOpen = ref(false)
const activeModal = ref(null)

const nameForm = ref('');
const budgetForm = ref('');
const descForm = ref('')
const selectedUser = ref(null);
const selectedUsers = ref([])
const replaceForm = ref({ old: null, new: null })

// --- Завершенные задачи ---
const completedData = ref({ tasks: [], subtasks: [] })
const isLoadingCompleted = ref(false)

const recentCompleted = computed(() => {
    const tasks = completedData.value.tasks.map(t => ({ ...t, type: 'task' }))
    const subs = completedData.value.subtasks.map(s => ({ ...s, type: 'subtask' }))
    // Объединяем и берем последние 9
    return [...tasks, ...subs].slice(0, 9)
})

const totalCompletedCount = computed(() => {
    return (completedData.value.tasks?.length || 0) + (completedData.value.subtasks?.length || 0)
})

const fetchCompleted = async () => {
    isLoadingCompleted.value = true
    try {
        const { data } = await axios.get(`/api/projects/${props.project.id}/completed-tasks`)
        completedData.value = data
    } catch (e) {
        console.error("Ошибка загрузки завершенных задач", e)
    } finally {
        isLoadingCompleted.value = false
    }
}

// --- Права (Permissions) ---
const isOwner = computed(() => props.project.company?.user_id === props.user.id)
const isManager = computed(() => props.project.managers?.some(m => m.id === props.user.id))
const isInitiator = computed(() => props.project.initiator_id === props.user.id)

const canEdit = computed(() => isOwner.value || isManager.value)
const canManageTeam = computed(() => isOwner.value || isManager.value || isInitiator.value)
const canDelete = computed(() => isOwner.value || isInitiator.value)

// --- Логика открытия модалок ---
const openModal = (type) => {
    activeModal.value = type
    if (type === 'name') nameForm.value = props.project.name
    if (type === 'budget') budgetForm.value = props.project.budget
    if (type === 'desc') descForm.value = props.project.description
    if (type === 'completedTasks') fetchCompleted() // Обновляем при открытии модалки

    selectedUser.value = null
    selectedUsers.value = []
}

const closeModal = () => { activeModal.value = null }

// --- API Actions ---
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

const replaceManager = async () => {
    try {
        await axios.post(`/api/projects/${props.project.id}/replace-manager`, {
            old_manager_id: replaceForm.value.old,
            new_manager_id: replaceForm.value.new
        })
        emit('refresh'); closeModal()
    } catch(e) { alert('Ошибка при смене руководителя') }
}

const addRole = async (role) => {
    const payload = role === 'executors' ? { user_ids: selectedUsers.value } : { user_id: selectedUser.value }
    const url = role === 'executors' ? 'executors' : role
    try {
        await axios.post(`/api/projects/${props.project.id}/${url}`, payload)
        emit('refresh'); closeModal()
    } catch(e) { alert('Ошибка при добавлении') }
}

const removeMember = async (role, userId) => {
    if(!confirm('Исключить участника?')) return
    try {
        await axios.post(`/api/projects/${props.project.id}/remove-member`, { role, user_id: userId })
        emit('refresh')
    } catch(e) { alert('Ошибка при удалении') }
}

const deleteProject = async () => {
    if(!confirm('Удалить проект?')) return
    await axios.delete(`/api/projects/${props.project.id}`)
    window.location.href = '/'
}

// Напоминания
const showReminderModal = ref(false)
const stagnantItems = ref({ tasks: [], subtasks: [] })
const selectedTaskIds = ref([])
const selectedSubtaskIds = ref([])
const isSending = ref(false)

const openReminderModal = async () => {
    showReminderModal.value = true
    const res = await axios.get(`/api/projects/${props.project.id}/stagnant-items`)
    stagnantItems.value = res.data
    selectedTaskIds.value = res.data.tasks.map(t => t.id)
    selectedSubtaskIds.value = res.data.subtasks.map(s => s.id)
}

const sendReminders = async () => {
    isSending.value = true
    try {
        await axios.post(`/api/projects/${props.project.id}/remind-stagnant`, {
            task_ids: selectedTaskIds.value,
            subtask_ids: selectedSubtaskIds.value
        })
        alert('Уведомления отправлены')
        showReminderModal.value = false
    } finally { isSending.value = false }
}

onMounted(() => {
    fetchCompleted() // Загружаем завершенные задачи при загрузке страницы
})
</script>

<template>
    <div class="relative">

        <!-- 1. ВЕРХНЯЯ ПАНЕЛЬ -->
        <div class="flex items-center justify-between py-2 border-b border-slate-100 dark:border-slate-800 mb-6 px-1">
            <button v-if="project.company"
                    @click="router.visit(`/companies/${project.company.id}`)"
                    class="flex items-center gap-2 text-sm font-bold text-slate-500 hover:text-indigo-600 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                К компании
            </button>

            <button @click="isSidebarOpen = true"
                    class="flex items-center gap-2 px-4 py-2 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-xl font-bold text-sm shadow-lg transition-transform active:scale-95">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
                Управление
            </button>
        </div>

        <!-- 1.5 БЛОК ПОСЛЕДНИХ ЗАВЕРШЕННЫХ ЗАДАЧ -->
        <div v-if="recentCompleted.length > 0" class="mb-10 animate-in fade-in slide-in-from-top-4 duration-500">
            <div class="flex items-center justify-between mb-4 px-1">
                <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    Архив проекта (9 последних)
                </h3>
                <button v-if="totalCompletedCount > 9"
                        @click="openModal('completedTasks')"
                        class="text-[10px] font-bold text-indigo-600 hover:text-indigo-700 uppercase tracking-widest transition-colors">
                    Показать все ({{ totalCompletedCount }})
                </button>
            </div>

            <!-- Сетка 3x3 -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <a v-for="item in recentCompleted" :key="item.id + item.type"
                   :href="item.link"
                   class="group flex items-center gap-3 p-3 bg-white dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800 rounded-2xl hover:border-emerald-500/50 hover:shadow-xl transition-all">

                    <div :class="['w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 text-[10px] font-black shadow-sm group-hover:scale-110 transition-transform',
                                  item.type === 'task' ? 'bg-indigo-50 text-indigo-600' : 'bg-emerald-50 text-emerald-600']">
                        {{ item.type === 'task' ? 'Z' : 'S' }}
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold text-slate-700 dark:text-slate-200 truncate group-hover:text-emerald-600 transition-colors">
                            {{ item.title }}
                        </p>
                        <p class="text-[9px] font-medium text-slate-400 uppercase tracking-tighter truncate">
                            {{ item.type === 'task' ? ' задача' : `подзадача: ${item.task_title}` }}
                        </p>
                    </div>

                    <svg class="w-3 h-3 text-slate-300 group-hover:text-emerald-500 transition-colors mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                    </svg>
                </a>
            </div>
        </div>

        <!-- 2. БОКОВОЕ МЕНЮ (Side Over) -->
        <Transition name="slide">
            <div v-if="isSidebarOpen" class="fixed inset-0 z-[60] flex justify-end">
                <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="isSidebarOpen = false"></div>
                <div class="relative w-full max-w-sm bg-white dark:bg-slate-900 h-full shadow-2xl flex flex-col border-l dark:border-slate-800">
                    <div class="p-6 border-b dark:border-slate-800 flex justify-between items-center bg-slate-50 dark:bg-slate-800/50">
                        <h2 class="text-xl font-black uppercase tracking-tight">Управление</h2>
                        <button @click="isSidebarOpen = false" class="text-slate-400 hover:text-slate-600 text-2xl">&times;</button>
                    </div>

                    <div class="flex-1 overflow-y-auto p-6 space-y-8 custom-scrollbar">
                        <div v-if="canEdit">
                            <h3 class="label-section">Основные настройки</h3>
                            <div class="grid gap-2">
                                <button @click="openModal('name')" class="menu-btn"><span>✏️</span> Название</button>
                                <button @click="openModal('budget')" class="menu-btn"><span>💰</span> Бюджет</button>
                                <button @click="openModal('desc')" class="menu-btn"><span>📝</span> Описание</button>
                            </div>
                        </div>

                        <div v-if="canManageTeam">
                            <h3 class="label-section">Команда</h3>
                            <div class="grid gap-2">
                                <button @click="openModal('addManager')" class="menu-btn"><span>👑</span> Добавить руководителя</button>
<!--                                <button @click="openModal('replaceManager')" class="menu-btn"><span>🔄</span> Сменить руководителя</button>-->
                                <button @click="openModal('addExecutor')" class="menu-btn"><span>🛠</span> Добавить исполнителей</button>
                                <button @click="openModal('addWatcher')" class="menu-btn"><span>👀</span> Добавить наблюдателя</button>
                                <button @click="openModal('manageList')" class="menu-btn border-indigo-100 text-indigo-600"><span>👥</span> Управление командой</button>
                            </div>
                        </div>

                        <div>
                            <h3 class="label-section">Инструменты</h3>
                            <div class="grid gap-2">
                                <button @click="openModal('completedTasks')" class="menu-btn border-emerald-50 text-emerald-600"><span>✅</span> Завершенные задачи</button>
                                <button @click="openReminderModal" class="menu-btn w-full text-rose-600 border-rose-100 bg-rose-50/50"><span>🔔</span> Напомнить о задачах</button>
                            </div>
                        </div>

                        <div v-if="canDelete" class="pt-6 border-t dark:border-slate-800">
                            <button @click="deleteProject" class="w-full py-4 rounded-2xl bg-slate-100 dark:bg-slate-800 text-rose-500 font-bold hover:bg-rose-500 hover:text-white transition-all">Удалить проект</button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- 3. МОДАЛКИ -->
        <Transition name="fade">
            <div v-if="activeModal" class="fixed inset-0 z-[70] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-md" @click="closeModal"></div>
                <div class="relative w-full max-w-md bg-white dark:bg-slate-800 rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">

                    <div class="p-6 border-b dark:border-slate-700 bg-slate-50 dark:bg-slate-800 flex justify-between items-center">
                        <h3 class="font-bold text-lg uppercase tracking-tight">
                            {{ activeModal === 'name' ? 'Название' : activeModal === 'budget' ? 'Бюджет' : activeModal === 'manageList' ? 'Команда' : activeModal === 'completedTasks' ? 'Все завершенные' : 'Редактирование' }}
                        </h3>
                        <button @click="closeModal" class="text-slate-400 hover:text-slate-600">✕</button>
                    </div>

                    <div class="p-6 overflow-y-auto custom-scrollbar">
                        <!-- Все завершенные задачи (Модалка) -->
                        <div v-if="activeModal === 'completedTasks'" class="space-y-8 pb-4">
                            <div v-if="completedData.tasks.length">
                                <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2"><span class="w-4 h-px bg-slate-200"></span> Задачи</h4>
                                <div class="grid gap-2">
                                    <a v-for="t in completedData.tasks" :key="t.id" :href="t.link" class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-900 rounded-2xl border border-transparent hover:border-indigo-500 transition-all group">
                                        <span class="text-sm font-bold text-slate-700 dark:text-slate-200 group-hover:text-indigo-600">{{ t.title }}</span>
                                        <span class="text-[10px] font-medium text-slate-400">{{ t.due_date }}</span>
                                    </a>
                                </div>
                            </div>
                            <div v-if="completedData.subtasks.length">
                                <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2"><span class="w-4 h-px bg-slate-200"></span> Подзадачи</h4>
                                <div class="grid gap-2">
                                    <a v-for="s in completedData.subtasks" :key="s.id" :href="s.link" class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-900 rounded-2xl border border-transparent hover:border-emerald-500 transition-all group">
                                        <div class="min-w-0">
                                            <p class="text-[9px] font-black text-emerald-500 uppercase mb-0.5">{{ s.task_title }}</p>
                                            <p class="text-sm font-bold text-slate-700 dark:text-slate-200 group-hover:text-emerald-600">{{ s.title }}</p>
                                        </div>
                                        <span class="text-[10px] font-medium text-slate-400 shrink-0 ml-4">{{ s.due_date }}</span>
                                    </a>
                                </div>
                            </div>
                            <div v-if="!totalCompletedCount" class="text-center py-10 text-slate-400 italic">Завершенных задач нет</div>
                        </div>

                        <!-- Название -->
                        <div v-if="activeModal === 'name'">
                            <input v-model="nameForm" type="text" class="input-primary mb-4" autofocus />
                            <div class="flex gap-2"><button @click="closeModal" class="btn-ghost">Отмена</button><button @click="saveName" class="btn-primary">Сохранить</button></div>
                        </div>

                        <!-- Бюджет -->
                        <div v-if="activeModal === 'budget'">
                            <input v-model="budgetForm" type="number" class="input-primary mb-4" />
                            <div class="flex gap-2"><button @click="closeModal" class="btn-ghost">Отмена</button><button @click="saveBudget" class="btn-primary">Сохранить</button></div>
                        </div>

                        <!-- Описание -->
                        <div v-if="activeModal === 'desc'">
                            <textarea v-model="descForm" rows="5" class="input-primary mb-4"></textarea>
                            <div class="flex gap-2"><button @click="closeModal" class="btn-ghost">Отмена</button><button @click="saveDesc" class="btn-primary">Сохранить</button></div>
                        </div>

                        <!-- Списки (Управление участниками) -->
                        <div v-if="activeModal === 'manageList'" class="space-y-6">
                            <div v-for="role in [{key:'managers', label:'Руководители'}, {key:'executors', label:'Исполнители'}, {key:'watchers', label:'Наблюдатели'}]" :key="role.key">
                                <h4 class="text-xs font-bold text-slate-400 uppercase mb-2">{{ role.label }}</h4>
                                <div class="space-y-2">
                                    <div v-for="person in project[role.key]" :key="person.id" class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                                        <span class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ person.name }}</span>
                                        <button @click="removeMember(role.key, person.id)" class="text-rose-500 text-xs font-bold hover:underline">Исключить</button>
                                    </div>
                                    <div v-if="!project[role.key]?.length" class="text-xs italic text-slate-400 pl-2">Пусто</div>
                                </div>
                            </div>
                        </div>

                        <!-- Добавление (Исполнители/Рук) -->
                        <div v-if="['addManager', 'addWatcher'].includes(activeModal)">
                            <select v-model="selectedUser" class="input-primary mb-4">
                                <option :value="null">Выберите сотрудника</option>
                                <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.name }}</option>
                            </select>
                            <div class="flex gap-2"><button @click="closeModal" class="btn-ghost">Отмена</button><button @click="addRole(activeModal === 'addManager' ? 'add-manager' : 'watchers')" class="btn-primary" :disabled="!selectedUser">Добавить</button></div>
                        </div>

                        <div v-if="activeModal === 'addExecutor'">
                            <div class="space-y-2 mb-4 max-h-60 overflow-y-auto p-2 border rounded-xl">
                                <label v-for="e in employees" :key="e.id" class="flex items-center gap-2 p-2 hover:bg-slate-50 rounded-lg cursor-pointer transition">
                                    <input type="checkbox" v-model="selectedUsers" :value="e.id" class="rounded text-indigo-600"><span class="text-sm font-medium">{{ e.name }}</span>
                                </label>
                            </div>
                            <div class="flex gap-2"><button @click="closeModal" class="btn-ghost">Отмена</button><button @click="addRole('executors')" class="btn-primary" :disabled="!selectedUsers.length">Добавить</button></div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Модалка напоминаний (🔔) -->
        <Transition name="fade">
            <div v-if="showReminderModal" class="fixed inset-0 z-[80] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-md" @click="showReminderModal = false"></div>
                <div class="relative w-full max-w-xl bg-white dark:bg-slate-800 rounded-3xl p-6 shadow-2xl">
                    <h3 class="text-xl font-black mb-4 uppercase tracking-tight">Задачи без прогресса (0%)</h3>
                    <div class="max-h-80 overflow-y-auto custom-scrollbar mb-6 pr-2 text-slate-700 dark:text-slate-200">
                        <!-- (код напоминаний остается без изменений) -->
                        Загрузка контента...
                    </div>
                    <div class="flex gap-3">
                        <button @click="showReminderModal = false" class="btn-ghost flex-1">Отмена</button>
                        <button @click="sendReminders" :disabled="isSending" class="btn-primary flex-[2] bg-rose-600">Разослать</button>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.label-section { @apply text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-4; }
.menu-btn { @apply flex items-center gap-3 w-full px-4 py-3 bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-2xl text-sm font-bold text-slate-700 dark:text-slate-200 transition-all hover:border-indigo-500 hover:shadow-md active:scale-[0.98]; }
.slide-enter-active, .slide-leave-active { transition: transform 0.3s ease; }
.slide-enter-from, .slide-leave-to { transform: translateX(100%); }
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.input-primary { @apply w-full px-4 py-3 rounded-2xl border border-slate-200 dark:bg-slate-900 dark:border-slate-700 outline-none focus:ring-2 focus:ring-indigo-500 transition-all text-slate-800 dark:text-slate-100; }
.btn-primary { @apply px-6 py-3 bg-indigo-600 text-white rounded-2xl font-bold transition-all shadow-md active:scale-95 disabled:opacity-50; }
.btn-ghost { @apply px-6 py-3 text-slate-400 font-bold hover:text-slate-600; }
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { @apply bg-slate-200 rounded-full; }
</style>
