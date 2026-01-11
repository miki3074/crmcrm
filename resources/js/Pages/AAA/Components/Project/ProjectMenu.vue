<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'
import { router } from '@inertiajs/vue3' // Используем router вместо $inertia для Composition API

const props = defineProps(['project', 'user', 'employees'])
const emit = defineEmits(['refresh'])

// --- State ---
const activeModal = ref(null) // Один стейт для управления текущей модалкой
const nameForm = ref('');
const budgetForm = ref('');
const descForm = ref('')
const selectedUser = ref(null);
const selectedUsers = ref([])
const replaceForm = ref({ old: null, new: null })

// --- Permissions ---
const isOwner = computed(() => props.project.company?.user_id === props.user.id)
const isManager = computed(() => props.project.managers?.some(m => m.id === props.user.id))
const isExecutor = computed(() => props.project.executors?.some(e => e.id === props.user.id))
const isInitiator = computed(() => props.project.initiator_id === props.user.id)

const canEdit = computed(() => isOwner.value || isManager.value || isExecutor.value)
const canEditBudget = computed(() => isOwner.value)
const canManageTeam = computed(() => isOwner.value || isManager.value || isInitiator.value)
const canDelete = computed(() => isOwner.value || isInitiator.value)

// --- Actions ---

const openModal = (type) => {
    activeModal.value = type
    // Init forms data
    if (type === 'name') nameForm.value = props.project.name
    if (type === 'budget') budgetForm.value = props.project.budget
    if (type === 'desc') descForm.value = props.project.description
    selectedUser.value = null
    selectedUsers.value = []
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
    if(!confirm('Вы уверены? Это действие нельзя отменить.')) return
    await axios.delete(`/api/projects/${props.project.id}`)
    window.location.href = '/' // или router.visit('/')
}

const addRole = async (role) => {
    const payload = role === 'executors' ? { user_ids: selectedUsers.value } : { user_id: selectedUser.value }
    const url = role === 'executors' ? 'executors' : role
    try {
        await axios.post(`/api/projects/${props.project.id}/${url}`, payload)
        emit('refresh'); closeModal()
    } catch(e) { alert(e.response?.data?.message || 'Ошибка') }
}

const replaceManager = async () => {
    try {
        await axios.post(`/api/projects/${props.project.id}/replace-manager`, {
            old_manager_id: replaceForm.value.old,
            new_manager_id: replaceForm.value.new
        })
        emit('refresh'); closeModal()
    } catch(e) { alert('Ошибка') }
}

const removeMember = async (role, id) => {
    if(role === 'manager' && props.project.managers.length <= 1) return alert('В проекте должен остаться хотя бы один руководитель')
    await axios.delete(`/api/projects/${props.project.id}/members`, { data: { user_id: id, role }})
    emit('refresh')
}
</script>

<template>
    <div class="space-y-6">

        <!-- 1. TOP TOOLBAR -->
        <div class="flex flex-wrap items-center justify-between gap-4 p-1">

            <!-- Навигация -->
            <button v-if="project.company"
                    @click="router.visit(`/companies/${project.company.id}`)"
                    class="flex items-center gap-2 text-sm font-medium   transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Назад к компании
            </button>

            <!-- Основные действия -->
            <div class="flex flex-wrap items-center gap-2">
                <button v-if="canEdit" @click="openModal('name')" class="btn-secondary">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                    Название
                </button>
                <button v-if="canEditBudget" @click="openModal('budget')" class="btn-secondary">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Бюджет
                </button>
                <button v-if="canEdit" @click="openModal('desc')" class="btn-secondary">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Описание
                </button>
                <div class="h-6 w-px bg-slate-200 dark:bg-slate-700 mx-1"></div>
                <button v-if="canDelete" @click="deleteProject" class="btn-danger">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    Удалить
                </button>
            </div>
        </div>

        <!-- 2. TEAM MANAGEMENT PANEL -->
        <div v-if="canManageTeam" class="bg-slate-50 dark:bg-slate-800/50 rounded-2xl p-5 border border-slate-200 dark:border-slate-700">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
                <h4 class="text-sm uppercase tracking-wider text-slate-500 font-bold flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    Управление командой
                </h4>
                <button @click="openModal('manageList')" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700 hover:underline">
                    Показать всех участников
                </button>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <button @click="openModal('addManager')" class="btn-team border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-900/20 dark:border-emerald-800 dark:text-emerald-400">
                    <span class="text-lg">👑</span> Руководитель
                </button>
                <button @click="openModal('replaceManager')" class="btn-team border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-900/20 dark:border-amber-800 dark:text-amber-400">
                    <span class="text-lg">🔄</span> Сменить рук.
                </button>
                <button @click="openModal('addExecutor')" class="btn-team border-indigo-200 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-900/20 dark:border-indigo-800 dark:text-indigo-400">
                    <span class="text-lg">🛠</span> Исполнитель
                </button>
                <button @click="openModal('addWatcher')" class="btn-team border-purple-200 bg-purple-50 text-purple-700 hover:bg-purple-100 dark:bg-purple-900/20 dark:border-purple-800 dark:text-purple-400">
                    <span class="text-lg">👀</span> Наблюдатель
                </button>
            </div>
        </div>

        <!-- 3. MODALS (Transition Wrapper) -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div v-if="activeModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="closeModal"></div>

                <!-- Modal Content -->
                <div class="relative w-full max-w-md bg-white dark:bg-slate-800 rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">

                    <!-- Header -->
                    <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-800">
                        <h3 class="font-bold text-lg text-slate-800 dark:text-white">
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
                        <button @click="closeModal" class="text-slate-400 hover:text-slate-600 transition">✕</button>
                    </div>

                    <!-- Body -->
                    <div class="p-6 overflow-y-auto custom-scrollbar">

                        <!-- Name Form -->
                        <div v-if="activeModal === 'name'">
                            <input v-model="nameForm" type="text" class="input-primary" placeholder="Введите название проекта" autofocus />
                            <div class="modal-actions">
                                <button @click="closeModal" class="btn-ghost">Отмена</button>
                                <button @click="saveName" class="btn-primary">Сохранить</button>
                            </div>
                        </div>

                        <!-- Budget Form -->
                        <div v-if="activeModal === 'budget'">
                            <div class="relative">
                                <span class="absolute left-3 top-2.5 text-slate-500">₽</span>
                                <input v-model="budgetForm" type="number" class="input-primary pl-8" placeholder="0.00" />
                            </div>
                            <div class="modal-actions">
                                <button @click="closeModal" class="btn-ghost">Отмена</button>
                                <button @click="saveBudget" class="btn-primary">Сохранить</button>
                            </div>
                        </div>

                        <!-- Description Form -->
                        <div v-if="activeModal === 'desc'">
                            <textarea v-model="descForm" rows="5" class="input-primary" placeholder="Опишите задачи проекта..."></textarea>
                            <div class="modal-actions">
                                <button @click="closeModal" class="btn-ghost">Отмена</button>
                                <button @click="saveDesc" class="btn-primary">Сохранить</button>
                            </div>
                        </div>

                        <!-- Single User Select (Manager/Watcher) -->
                        <div v-if="['addManager', 'addWatcher'].includes(activeModal)">
                            <select v-model="selectedUser" class="input-primary">
                                <option :value="null">Выберите сотрудника...</option>
                                <option v-for="e in employees" :value="e.id" :key="e.id">{{e.name}} ({{e.email}})</option>
                            </select>
                            <div class="modal-actions">
                                <button @click="closeModal" class="btn-ghost">Отмена</button>
                                <button @click="addRole(activeModal === 'addManager' ? 'add-manager' : 'watchers')" class="btn-primary" :disabled="!selectedUser">Добавить</button>
                            </div>
                        </div>

                        <!-- Multiple User Select (Executors) -->
                        <div v-if="activeModal === 'addExecutor'">
                            <div class="border border-slate-200 dark:border-slate-700 rounded-xl max-h-60 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-700">
                                <label v-for="e in employees" :key="e.id" class="flex items-center gap-3 p-3 hover:bg-slate-50 dark:hover:bg-slate-700/50 cursor-pointer transition">
                                    <input type="checkbox" v-model="selectedUsers" :value="e.id" class="w-4 h-4 text-indigo-600 rounded focus:ring-indigo-500">
                                    <div class="text-sm">
                                        <div class="font-medium text-slate-700 dark:text-slate-200">{{e.name}}</div>
                                        <div class="text-xs text-slate-400">{{e.email}}</div>
                                    </div>
                                </label>
                            </div>
                            <div class="modal-actions">
                                <button @click="closeModal" class="btn-ghost">Отмена</button>
                                <button @click="addRole('executors')" class="btn-primary" :disabled="!selectedUsers.length">Добавить выбранных</button>
                            </div>
                        </div>

                        <!-- Replace Manager -->
                        <div v-if="activeModal === 'replaceManager'">
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Текущий руководитель</label>
                            <select v-model="replaceForm.old" class="input-primary mb-4">
                                <option :value="null">Выберите кого заменить</option>
                                <option v-for="m in project.managers" :value="m.id" :key="m.id">{{m.name}}</option>
                            </select>

                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Новый руководитель</label>
                            <select v-model="replaceForm.new" class="input-primary">
                                <option :value="null">Выберите преемника</option>
                                <option v-for="e in employees" :value="e.id" :key="e.id">{{e.name}}</option>
                            </select>

                            <div class="modal-actions">
                                <button @click="closeModal" class="btn-ghost">Отмена</button>
                                <button @click="replaceManager" class="btn-primary bg-amber-500 hover:bg-amber-600" :disabled="!replaceForm.old || !replaceForm.new">Заменить</button>
                            </div>
                        </div>

                        <!-- Manage Members List -->
                        <div v-if="activeModal === 'manageList'" class="space-y-6">
                            <!-- Managers -->
                            <div>
                                <h4 class="text-xs font-bold text-emerald-600 uppercase mb-2">Руководители</h4>
                                <div v-if="!project.managers?.length" class="text-sm text-slate-400 italic">Нет данных</div>
                                <ul class="space-y-2">
                                    <li v-for="m in project.managers" :key="m.id" class="member-item">
                                        <span class="font-medium text-slate-700 dark:text-slate-200">{{ m.name }}</span>
                                        <button @click="removeMember('manager', m.id)" class="btn-remove">Исключить</button>
                                    </li>
                                </ul>
                            </div>

                            <!-- Executors -->
                            <div>
                                <h4 class="text-xs font-bold text-indigo-600 uppercase mb-2">Исполнители</h4>
                                <div v-if="!project.executors?.length" class="text-sm text-slate-400 italic">Нет данных</div>
                                <ul class="space-y-2">
                                    <li v-for="e in project.executors" :key="e.id" class="member-item">
                                        <span class="font-medium text-slate-700 dark:text-slate-200">{{ e.name }}</span>
                                        <button @click="removeMember('executor', e.id)" class="btn-remove">Исключить</button>
                                    </li>
                                </ul>
                            </div>

                            <!-- Watchers -->
                            <div>
                                <h4 class="text-xs font-bold text-purple-600 uppercase mb-2">Наблюдатели</h4>
                                <div v-if="!project.watchers?.length" class="text-sm text-slate-400 italic">Нет данных</div>
                                <ul class="space-y-2">
                                    <li v-for="w in project.watchers" :key="w.id" class="member-item">
                                        <span class="font-medium text-slate-700 dark:text-slate-200">{{ w.name }}</span>
                                        <button @click="removeMember('watcher', w.id)" class="btn-remove">Исключить</button>
                                    </li>
                                </ul>
                            </div>

                            <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-700 flex justify-end">
                                <button @click="closeModal" class="btn-ghost">Закрыть</button>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </Transition>

    </div>
</template>

<style scoped>
/* Buttons */
.btn-secondary {
    @apply flex items-center gap-2 px-3 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm font-medium text-slate-700 dark:text-slate-200 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-700 transition-all active:scale-95;
}
.btn-danger {
    @apply flex items-center gap-2 px-3 py-2 bg-white dark:bg-slate-800 border border-rose-200 dark:border-rose-900 rounded-lg text-sm font-medium text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 transition-all active:scale-95;
}
.btn-team {
    @apply flex items-center justify-center gap-2 py-4 px-2 rounded-xl border border-dashed font-semibold text-sm transition-all shadow-sm hover:shadow-md hover:border-solid active:scale-95;
}
.btn-primary {
    @apply px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium shadow-md shadow-indigo-500/20 transition-colors disabled:opacity-50 disabled:cursor-not-allowed;
}
.btn-ghost {
    @apply px-4 py-2 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 font-medium transition-colors;
}
.btn-remove {
    @apply text-xs font-semibold text-rose-500 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 px-2 py-1 rounded transition-colors;
}

/* Inputs */
.input-primary {
    @apply w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition-all placeholder:text-slate-400;
}

/* Utils */
.modal-actions {
    @apply flex justify-end gap-3 mt-6 pt-4 border-t border-slate-100 dark:border-slate-700;
}
.member-item {
    @apply flex justify-between items-center bg-slate-50 dark:bg-slate-800/50 px-3 py-2 rounded-lg border border-slate-100 dark:border-slate-700;
}
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { @apply bg-slate-300 rounded-full; }
</style>
