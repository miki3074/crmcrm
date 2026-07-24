<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'

const props = defineProps(['subtask', 'user'])
const emit = defineEmits(['refresh'])

// State for modals
const showExecutorModal = ref(false)
const showResponsibleModal = ref(false)
const showAddExecutorModal = ref(false)
const showAddResponsibleModal = ref(false)
const showManageMembers = ref(false)

// Data
const employees = ref([])
const selectedUsers = ref([])
const replaceUserId = ref(null)
const newUserId = ref(null)
const errorMsg = ref('')

// Computed
const canManageMembers = computed(() => {
    const u = props.user; const s = props.subtask
    if (!s || !u) return false
    return (
        u.id === s.task?.project?.company?.user_id ||
        (s.task?.project?.managers || []).some(m => m.id === u.id) ||
        (s.task?.project?.executors || []).some(e => e.id === u.id)
    )
})

const availableExecutors = computed(() => {
    const currentIds = (props.subtask.executors || []).map(e => e.id)
    return employees.value.filter(e => !currentIds.includes(e.id))
})
const availableResponsibles = computed(() => {
    const currentIds = (props.subtask.responsibles || []).map(r => r.id)
    return employees.value.filter(e => !currentIds.includes(e.id))
})

// Fetch logic
const fetchEmployees = async () => {
    if (employees.value.length) return
    const { data } = await axios.get(`/api/projects/${props.subtask.task.project.id}/employees`)
    employees.value = data
}

// === ИСПРАВЛЕНИЕ ЗДЕСЬ ===
// 1. Общая функция для сброса и загрузки данных
const prepareModalState = async () => {
    await fetchEmployees()
    replaceUserId.value = null
    newUserId.value = null
    selectedUsers.value = []
    errorMsg.value = ''
}

// 2. Отдельные функции для каждого действия
const openChangeExecutor = async () => {
    await prepareModalState()
    showExecutorModal.value = true
}

const openChangeResponsible = async () => {
    await prepareModalState()
    showResponsibleModal.value = true
}

const openAddExecutor = async () => {
    await prepareModalState()
    showAddExecutorModal.value = true
}

const openAddResponsible = async () => {
    await prepareModalState()
    showAddResponsibleModal.value = true
}
// =========================

// Actions
const addMembers = async (type) => {
    if (!selectedUsers.value.length) return
    try {
        const url = type === 'executor' ? 'executors/add' : 'responsibles/add'
        await axios.post(`/api/subtasks/${props.subtask.id}/${url}`, { user_ids: selectedUsers.value })
        emit('refresh')
        showAddExecutorModal.value = false; showAddResponsibleModal.value = false
    } catch (e) { alert('Ошибка') }
}

const changeMember = async (type) => {
    if (!replaceUserId.value || !newUserId.value) { errorMsg.value = 'Выберите обоих участников'; return }
    try {
        const url = type === 'executor' ? 'executor/change' : 'responsible/change'
        await axios.patch(`/api/subtasks/${props.subtask.id}/${url}`, { replace_user_id: replaceUserId.value, user_id: newUserId.value })
        emit('refresh')
        showExecutorModal.value = false; showResponsibleModal.value = false
    } catch (e) { errorMsg.value = e?.response?.data?.message }
}

const removeMember = async (type, id) => {
    try {
        const url = type === 'executor' ? 'executors' : 'responsibles'
        await axios.delete(`/api/subtasks/${props.subtask.id}/${url}`, { data: { user_id: id } })
        emit('refresh')
    } catch (e) { alert('Ошибка удаления') }
}
</script>

<template>
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="grid sm:grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-slate-500 dark:text-slate-400">Автор</p>
                <p class="font-medium text-slate-900 dark:text-white">{{ subtask.creator?.name }}</p>

                <p class="text-slate-500 dark:text-slate-400 mt-4">Исполнитель</p>
                <p class="font-medium text-slate-900 dark:text-white">
                    {{ subtask.executors?.length ? subtask.executors.map(e => e.name).join(', ') : '—' }}
                </p>

                <p class="text-slate-500 dark:text-slate-400 mt-4">Ответственный</p>
                <p class="font-medium text-slate-900 dark:text-white">
                    {{ subtask.responsibles?.length ? subtask.responsibles.map(r => r.name).join(', ') : '—' }}
                </p>
            </div>

            <!-- ИСПРАВЛЕНЫ ВЫЗОВЫ В ШАБЛОНЕ -->
            <div v-if="canManageMembers" class="flex flex-wrap gap-2 mt-4 content-start">
                <button @click="openChangeExecutor" class="px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded-md">
                    ✏️ Изменить исп.
                </button>
                <button @click="openChangeResponsible" class="px-3 py-1 bg-purple-500 hover:bg-purple-600 text-white rounded-md">
                    ✏️ Изменить отв.
                </button>
                <button @click="openAddExecutor" class="px-3 py-1 bg-emerald-500 hover:bg-emerald-600 text-white rounded-md">
                    ➕ Добавить исп.
                </button>
                <button @click="openAddResponsible" class="px-3 py-1 bg-amber-500 hover:bg-amber-600 text-white rounded-md">
                    ➕ Добавить отв.
                </button>
                <button @click="showManageMembers = true" class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg w-full sm:w-auto">
                    👥 Управление
                </button>
            </div>
        </div>
    </div>

    <!-- Модалки (код ниже без изменений, он верный) -->
    <!-- Add Executors -->
    <div v-if="showAddExecutorModal" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm flex items-center justify-center z-50">
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 w-full max-w-md">
            <h3 class="font-bold mb-3 dark:text-white">Добавить исполнителей</h3>
            <div class="max-h-60 overflow-y-auto border p-2 mb-3 dark:text-white">
                <label v-for="emp in employees" :key="emp.id" class="flex gap-2"><input type="checkbox" v-model="selectedUsers" :value="emp.id">{{ emp.name }}</label>
            </div>
            <div class="flex justify-end gap-2">
                <button @click="showAddExecutorModal=false" class="btn-cancel">Отмена</button>
                <button @click="addMembers('executor')" class="btn-ok">Добавить</button>
            </div>
        </div>
    </div>

    <!-- Add Responsibles -->
    <div v-if="showAddResponsibleModal" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm flex items-center justify-center z-50">
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 w-full max-w-md">
            <h3 class="font-bold mb-3 dark:text-white">Добавить ответственных</h3>
            <div class="max-h-60 overflow-y-auto border p-2 mb-3 dark:text-white">
                <label v-for="emp in employees" :key="emp.id" class="flex gap-2"><input type="checkbox" v-model="selectedUsers" :value="emp.id">{{ emp.name }}</label>
            </div>
            <div class="flex justify-end gap-2">
                <button @click="showAddResponsibleModal=false" class="btn-cancel">Отмена</button>
                <button @click="addMembers('responsible')" class="btn-ok">Добавить</button>
            </div>
        </div>
    </div>

    <!-- Change Executor Modal -->
    <div v-if="showExecutorModal" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm flex items-center justify-center z-50">
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 w-full max-w-md">
            <h3 class="font-bold mb-3 dark:text-white">Замена исполнителя</h3>
            <select v-model="replaceUserId" class="w-full border p-2 mb-2"><option :value="null">Кого заменить</option><option v-for="u in subtask.executors" :value="u.id" :key="u.id">{{u.name}}</option></select>
            <select v-model="newUserId" class="w-full border p-2 mb-2"><option :value="null">На кого</option><option v-for="u in availableExecutors" :value="u.id" :key="u.id">{{u.name}}</option></select>
            <p class="text-red-500 text-sm">{{ errorMsg }}</p>
            <div class="flex justify-end gap-2 mt-4"><button @click="showExecutorModal=false" class="btn-cancel">Отмена</button><button @click="changeMember('executor')" class="btn-ok">Сохранить</button></div>
        </div>
    </div>

    <!-- Change Responsible Modal -->
    <div v-if="showResponsibleModal" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm flex items-center justify-center z-50">
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 w-full max-w-md">
            <h3 class="font-bold mb-3 dark:text-white">Замена ответственного</h3>
            <select v-model="replaceUserId" class="w-full border p-2 mb-2"><option :value="null">Кого заменить</option><option v-for="u in subtask.responsibles" :value="u.id" :key="u.id">{{u.name}}</option></select>
            <select v-model="newUserId" class="w-full border p-2 mb-2"><option :value="null">На кого</option><option v-for="u in availableResponsibles" :value="u.id" :key="u.id">{{u.name}}</option></select>
            <p class="text-red-500 text-sm">{{ errorMsg }}</p>
            <div class="flex justify-end gap-2 mt-4"><button @click="showResponsibleModal=false" class="btn-cancel">Отмена</button><button @click="changeMember('responsible')" class="btn-ok">Сохранить</button></div>
        </div>
    </div>

    <!-- Manage Members -->
    <div v-if="showManageMembers" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm flex items-center justify-center z-50">
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 w-full max-w-3xl">
            <h3 class="font-bold mb-3 dark:text-white">Управление участниками</h3>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <h4 class="font-medium dark:text-white">Исполнители</h4>
                    <ul class="mt-2 space-y-2">
                        <li v-for="u in subtask.executors" :key="u.id" class="flex justify-between bg-gray-100 p-2 rounded">
                            <span>{{u.name}}</span>
                            <button @click="removeMember('executor', u.id)" class="text-red-500">❌</button>
                        </li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-medium dark:text-white">Ответственные</h4>
                    <ul class="mt-2 space-y-2">
                        <li v-for="u in subtask.responsibles" :key="u.id" class="flex justify-between bg-gray-100 p-2 rounded">
                            <span>{{u.name}}</span>
                            <button @click="removeMember('responsible', u.id)" class="text-red-500">❌</button>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="flex justify-end mt-4"><button @click="showManageMembers=false" class="btn-cancel">Закрыть</button></div>
        </div>
    </div>
</template>

<style scoped>
.btn-cancel { @apply px-4 py-2 border rounded-lg text-gray-600; }
.btn-ok { @apply px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg; }
</style>
