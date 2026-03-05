<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps(['subtask', 'user'])
const emit = defineEmits(['refresh'])

// Состояния модалок
const activeModal = ref(null) // 'executor-change', 'responsible-change', 'executor-add', 'responsible-add', 'manage'
const selectedUsers = ref([])
const replaceUserId = ref(null)
const newUserId = ref(null)
const errorMsg = ref('')
const loading = ref(false)
const searchQuery = ref('')

// Данные
const employees = ref([])
const project = ref(null)

// Получение данных
const fetchData = async () => {
    if (!props.subtask?.task?.project?.id) return

    loading.value = true
    try {
        const [employeesRes, projectRes] = await Promise.all([
            axios.get(`/api/projects/${props.subtask.task.project.id}/employees`),
            axios.get(`/api/projects/${props.subtask.task.project.id}`)
        ])
        employees.value = employeesRes.data
        project.value = projectRes.data
    } catch (e) {
        console.error('Ошибка загрузки данных:', e)
    } finally {
        loading.value = false
    }
}

// Права доступа
const canManageMembers = computed(() => {
    const u = props.user
    const s = props.subtask
    if (!s || !u) return false

    return (
        u.id === s.creator_id ||
        u.id === s.task?.project?.company?.user_id ||
        s.task?.project?.managers?.some(m => m.id === u.id) ||
        s.task?.project?.executors?.some(e => e.id === u.id)
    )
})

// Доступные для добавления
const availableExecutors = computed(() => {
    const currentIds = new Set(props.subtask.executors?.map(e => e.id) || [])
    return employees.value
        .filter(e => !currentIds.has(e.id))
        .filter(e => e.name.toLowerCase().includes(searchQuery.value.toLowerCase()))
})

const availableResponsibles = computed(() => {
    const currentIds = new Set(props.subtask.responsibles?.map(r => r.id) || [])
    return employees.value
        .filter(e => !currentIds.has(e.id))
        .filter(e => e.name.toLowerCase().includes(searchQuery.value.toLowerCase()))
})

// Форматирование
const getAvatarColor = (id) => {
    const colors = [
        'bg-red-100 text-red-600',
        'bg-blue-100 text-blue-600',
        'bg-green-100 text-green-600',
        'bg-amber-100 text-amber-600',
        'bg-purple-100 text-purple-600',
        'bg-pink-100 text-pink-600',
        'bg-indigo-100 text-indigo-600'
    ]
    return colors[(id || 0) % colors.length]
}

const getInitials = (name) => {
    return name
        ? name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase()
        : '?'
}

// Действия
const openModal = (type) => {
    activeModal.value = type
    selectedUsers.value = []
    replaceUserId.value = null
    newUserId.value = null
    errorMsg.value = ''
    searchQuery.value = ''

    if (type.includes('add') || type === 'manage') {
        fetchData()
    }
}

const closeModal = () => {
    activeModal.value = null
}

const addMembers = async (type) => {
    if (!selectedUsers.value.length) return

    loading.value = true
    try {
        const url = type === 'executor-add' ? 'executors/add' : 'responsibles/add'
        await axios.post(`/api/subtasks/${props.subtask.id}/${url}`, {
            user_ids: selectedUsers.value
        })
        emit('refresh')
        closeModal()
    } catch (e) {
        errorMsg.value = e?.response?.data?.message || 'Ошибка при добавлении'
    } finally {
        loading.value = false
    }
}

const changeMember = async (type) => {
    if (!replaceUserId.value || !newUserId.value) {
        errorMsg.value = 'Выберите обоих участников'
        return
    }

    loading.value = true
    try {
        const url = type === 'executor-change' ? 'executor/change' : 'responsible/change'
        await axios.patch(`/api/subtasks/${props.subtask.id}/${url}`, {
            replace_user_id: replaceUserId.value,
            user_id: newUserId.value
        })
        emit('refresh')
        closeModal()
    } catch (e) {
        errorMsg.value = e?.response?.data?.message || 'Ошибка при замене'
    } finally {
        loading.value = false
    }
}

const removeMember = async (type, id, name) => {
    if (!confirm(`Удалить ${name} из списка ${type === 'executor' ? 'исполнителей' : 'ответственных'}?`)) return

    loading.value = true
    try {
        const url = type === 'executor' ? 'executors' : 'responsibles'
        await axios.delete(`/api/subtasks/${props.subtask.id}/${url}`, {
            data: { user_id: id }
        })
        emit('refresh')
    } catch (e) {
        alert(e?.response?.data?.message || 'Ошибка удаления')
    } finally {
        loading.value = false
    }
}

// Получаем данные при монтировании
onMounted(() => {
    if (canManageMembers.value) {
        fetchData()
    }
})
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-md transition-all">
        <!-- Заголовок -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-transparent dark:from-gray-700/50">
            <div class="flex items-center gap-2">
                <div class="p-1.5 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Участники</h3>
            </div>
        </div>

        <!-- Контент -->
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Автор -->
                <div class="space-y-2">
                    <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400 text-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        <span>Автор</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shadow-sm"
                             :class="getAvatarColor(subtask.creator?.id)">
                            {{ getInitials(subtask.creator?.name) }}
                        </div>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ subtask.creator?.name }}
                        </span>
                    </div>
                </div>

                <!-- Исполнители -->
                <div class="space-y-2">
                    <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400 text-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span>Исполнители</span>
                        <span v-if="subtask.executors?.length" class="ml-auto text-xs bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded-full">
                            {{ subtask.executors.length }}
                        </span>
                    </div>
                    <div v-if="subtask.executors?.length" class="space-y-2">
                        <div v-for="executor in subtask.executors" :key="executor.id"
                             class="flex items-center gap-2 group">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shadow-sm flex-shrink-0"
                                 :class="getAvatarColor(executor.id)">
                                {{ getInitials(executor.name) }}
                            </div>
                            <span class="text-sm text-gray-700 dark:text-gray-300 truncate">
                                {{ executor.name }}
                            </span>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-400 italic">Не назначены</p>
                </div>

                <!-- Ответственные -->
                <div class="space-y-2">
                    <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400 text-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span>Ответственные</span>
                        <span v-if="subtask.responsibles?.length" class="ml-auto text-xs bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded-full">
                            {{ subtask.responsibles.length }}
                        </span>
                    </div>
                    <div v-if="subtask.responsibles?.length" class="space-y-2">
                        <div v-for="responsible in subtask.responsibles" :key="responsible.id"
                             class="flex items-center gap-2 group">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shadow-sm flex-shrink-0"
                                 :class="getAvatarColor(responsible.id)">
                                {{ getInitials(responsible.name) }}
                            </div>
                            <span class="text-sm text-gray-700 dark:text-gray-300 truncate">
                                {{ responsible.name }}
                            </span>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-400 italic">Не назначены</p>
                </div>
            </div>

            <!-- Кнопки управления -->
            <div v-if="canManageMembers" class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                <div class="flex flex-wrap gap-2">
                    <button @click="openModal('executor-change')"
                            class="inline-flex items-center gap-1.5 px-3 py-2 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/20 dark:hover:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-sm font-medium rounded-lg transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                        </svg>
                        Заменить исп.
                    </button>

                    <button @click="openModal('responsible-change')"
                            class="inline-flex items-center gap-1.5 px-3 py-2 bg-purple-50 hover:bg-purple-100 dark:bg-purple-900/20 dark:hover:bg-purple-900/30 text-purple-600 dark:text-purple-400 text-sm font-medium rounded-lg transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                        </svg>
                        Заменить отв.
                    </button>

                    <button @click="openModal('executor-add')"
                            class="inline-flex items-center gap-1.5 px-3 py-2 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-900/20 dark:hover:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 text-sm font-medium rounded-lg transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Добавить исп.
                    </button>

                    <button @click="openModal('responsible-add')"
                            class="inline-flex items-center gap-1.5 px-3 py-2 bg-amber-50 hover:bg-amber-100 dark:bg-amber-900/20 dark:hover:bg-amber-900/30 text-amber-600 dark:text-amber-400 text-sm font-medium rounded-lg transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Добавить отв.
                    </button>

                    <button @click="openModal('manage')"
                            class="inline-flex items-center gap-1.5 px-3 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Управление
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальные окна (Teleport для правильного позиционирования) -->
    <Teleport to="body">
        <!-- Добавление исполнителей -->
        <div v-if="activeModal === 'executor-add'" class="fixed inset-0 z-50 overflow-y-auto" @click.self="closeModal">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50 transition-opacity"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md transform transition-all">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Добавить исполнителей</h3>
                    </div>

                    <div class="p-6">
                        <!-- Поиск -->
                        <div class="mb-4">
                            <input type="text" v-model="searchQuery" placeholder="Поиск..."
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <!-- Список -->
                        <div class="max-h-60 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-lg divide-y dark:divide-gray-700">
                            <label v-for="emp in availableExecutors" :key="emp.id"
                                   class="flex items-center gap-3 px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                                <input type="checkbox" :value="emp.id" v-model="selectedUsers"
                                       class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                                <div class="flex items-center gap-2 flex-1">
                                    <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold shadow-sm"
                                         :class="getAvatarColor(emp.id)">
                                        {{ getInitials(emp.name) }}
                                    </div>
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ emp.name }}</span>
                                </div>
                            </label>
                            <div v-if="!availableExecutors.length" class="px-3 py-4 text-center text-gray-500 text-sm">
                                Нет доступных сотрудников
                            </div>
                        </div>

                        <!-- Ошибка -->
                        <div v-if="errorMsg" class="mt-3 p-2 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-sm rounded-lg">
                            {{ errorMsg }}
                        </div>

                        <!-- Кнопки -->
                        <div class="flex justify-end gap-3 mt-6">
                            <button @click="closeModal"
                                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                Отмена
                            </button>
                            <button @click="addMembers('executor-add')"
                                    :disabled="!selectedUsers.length || loading"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400 text-white rounded-lg transition">
                                <svg v-if="loading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ loading ? 'Добавление...' : 'Добавить' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Добавление ответственных (аналогично) -->
        <div v-if="activeModal === 'responsible-add'" class="fixed inset-0 z-50 overflow-y-auto" @click.self="closeModal">
            <!-- Аналогичная структура с адаптацией под ответственных -->
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50 transition-opacity"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Добавить ответственных</h3>
                    </div>

                    <div class="p-6">
                        <!-- Поиск -->
                        <div class="mb-4">
                            <input type="text" v-model="searchQuery" placeholder="Поиск..."
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <!-- Список -->
                        <div class="max-h-60 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-lg divide-y dark:divide-gray-700">
                            <label v-for="emp in availableResponsibles" :key="emp.id"
                                   class="flex items-center gap-3 px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                                <input type="checkbox" :value="emp.id" v-model="selectedUsers"
                                       class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                                <div class="flex items-center gap-2 flex-1">
                                    <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold shadow-sm"
                                         :class="getAvatarColor(emp.id)">
                                        {{ getInitials(emp.name) }}
                                    </div>
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ emp.name }}</span>
                                </div>
                            </label>
                            <div v-if="!availableResponsibles.length" class="px-3 py-4 text-center text-gray-500 text-sm">
                                Нет доступных сотрудников
                            </div>
                        </div>

                        <!-- Ошибка -->
                        <div v-if="errorMsg" class="mt-3 p-2 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-sm rounded-lg">
                            {{ errorMsg }}
                        </div>

                        <!-- Кнопки -->
                        <div class="flex justify-end gap-3 mt-6">
                            <button @click="closeModal"
                                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                Отмена
                            </button>
                            <button @click="addMembers('responsible-add')"
                                    :disabled="!selectedUsers.length || loading"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-amber-600 hover:bg-amber-700 disabled:bg-amber-400 text-white rounded-lg transition">
                                <svg v-if="loading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ loading ? 'Добавление...' : 'Добавить' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Замена исполнителя -->
        <div v-if="activeModal === 'executor-change'" class="fixed inset-0 z-50 overflow-y-auto" @click.self="closeModal">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50 transition-opacity"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Замена исполнителя</h3>
                    </div>

                    <div class="p-6 space-y-4">
                        <!-- Кого заменить -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Кого заменить
                            </label>
                            <select v-model="replaceUserId"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                                <option :value="null">Выберите сотрудника</option>
                                <option v-for="u in subtask.executors" :key="u.id" :value="u.id">
                                    {{ u.name }}
                                </option>
                            </select>
                        </div>

                        <!-- На кого -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                На кого
                            </label>
                            <select v-model="newUserId"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                                <option :value="null">Выберите сотрудника</option>
                                <option v-for="u in availableExecutors" :key="u.id" :value="u.id">
                                    {{ u.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Ошибка -->
                        <div v-if="errorMsg" class="p-2 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-sm rounded-lg">
                            {{ errorMsg }}
                        </div>

                        <!-- Кнопки -->
                        <div class="flex justify-end gap-3 pt-4">
                            <button @click="closeModal"
                                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                Отмена
                            </button>
                            <button @click="changeMember('executor-change')"
                                    :disabled="!replaceUserId || !newUserId || loading"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white rounded-lg transition">
                                <svg v-if="loading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ loading ? 'Сохранение...' : 'Сохранить' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Замена ответственного (аналогично) -->
        <div v-if="activeModal === 'responsible-change'" class="fixed inset-0 z-50 overflow-y-auto" @click.self="closeModal">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50 transition-opacity"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Замена ответственного</h3>
                    </div>

                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Кого заменить
                            </label>
                            <select v-model="replaceUserId"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                                <option :value="null">Выберите сотрудника</option>
                                <option v-for="u in subtask.responsibles" :key="u.id" :value="u.id">
                                    {{ u.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                На кого
                            </label>
                            <select v-model="newUserId"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                                <option :value="null">Выберите сотрудника</option>
                                <option v-for="u in availableResponsibles" :key="u.id" :value="u.id">
                                    {{ u.name }}
                                </option>
                            </select>
                        </div>

                        <div v-if="errorMsg" class="p-2 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-sm rounded-lg">
                            {{ errorMsg }}
                        </div>

                        <div class="flex justify-end gap-3 pt-4">
                            <button @click="closeModal"
                                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                Отмена
                            </button>
                            <button @click="changeMember('responsible-change')"
                                    :disabled="!replaceUserId || !newUserId || loading"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 disabled:bg-purple-400 text-white rounded-lg transition">
                                <svg v-if="loading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ loading ? 'Сохранение...' : 'Сохранить' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Управление участниками -->
        <div v-if="activeModal === 'manage'" class="fixed inset-0 z-50 overflow-y-auto" @click.self="closeModal">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50 transition-opacity"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-3xl">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Управление участниками</h3>
                    </div>

                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Исполнители -->
                            <div>
                                <h4 class="font-medium text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                    <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                                    Исполнители
                                    <span class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded-full">
                                        {{ subtask.executors?.length || 0 }}
                                    </span>
                                </h4>
                                <div class="space-y-2 max-h-60 overflow-y-auto pr-2">
                                    <div v-for="u in subtask.executors" :key="u.id"
                                         class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700/50 rounded-lg group hover:bg-gray-100 dark:hover:bg-gray-700">
                                        <div class="flex items-center gap-2">
                                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shadow-sm"
                                                 :class="getAvatarColor(u.id)">
                                                {{ getInitials(u.name) }}
                                            </div>
                                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ u.name }}</span>
                                        </div>
                                        <button @click="removeMember('executor', u.id, u.name)"
                                                class="opacity-0 group-hover:opacity-100 p-1 text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition"
                                                title="Удалить">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                    <div v-if="!subtask.executors?.length" class="text-center py-4 text-gray-500 text-sm">
                                        Нет исполнителей
                                    </div>
                                </div>
                            </div>

                            <!-- Ответственные -->
                            <div>
                                <h4 class="font-medium text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                    <span class="w-2 h-2 bg-purple-500 rounded-full"></span>
                                    Ответственные
                                    <span class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded-full">
                                        {{ subtask.responsibles?.length || 0 }}
                                    </span>
                                </h4>
                                <div class="space-y-2 max-h-60 overflow-y-auto pr-2">
                                    <div v-for="u in subtask.responsibles" :key="u.id"
                                         class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700/50 rounded-lg group hover:bg-gray-100 dark:hover:bg-gray-700">
                                        <div class="flex items-center gap-2">
                                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shadow-sm"
                                                 :class="getAvatarColor(u.id)">
                                                {{ getInitials(u.name) }}
                                            </div>
                                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ u.name }}</span>
                                        </div>
                                        <button @click="removeMember('responsible', u.id, u.name)"
                                                class="opacity-0 group-hover:opacity-100 p-1 text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition"
                                                title="Удалить">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                    <div v-if="!subtask.responsibles?.length" class="text-center py-4 text-gray-500 text-sm">
                                        Нет ответственных
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Кнопки -->
                        <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <button @click="closeModal"
                                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                Закрыть
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
/* Анимации */
.fixed {
    animation: fadeIn 0.2s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Плавные переходы */
.transition-all {
    transition: all 0.2s ease-in-out;
}

/* Кастомный скроллбар */
.overflow-y-auto::-webkit-scrollbar {
    width: 4px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

.dark .overflow-y-auto::-webkit-scrollbar-track {
    background: #374151;
}

.dark .overflow-y-auto::-webkit-scrollbar-thumb {
    background: #4b5563;
}
</style>
