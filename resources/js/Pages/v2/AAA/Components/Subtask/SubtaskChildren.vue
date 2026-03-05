<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps(['subtask', 'user'])
const emit = defineEmits(['refresh'])

// Состояния
const showModal = ref(false)
const creating = ref(false)
const errorMsg = ref('')
const employees = ref([])
const searchQuery = ref('')
const expandedChildren = ref(new Set())
const selectedChild = ref(null)
const showDeleteConfirm = ref(false)

// Форма создания
const form = ref({
    title: '',
    description: '',
    due_date: '',
    executor_ids: [],
    responsible_ids: []
})

// Права доступа
const canCreateChild = computed(() => {
    const { subtask, user } = props
    if (!subtask || !user) return false
    const project = subtask.task?.project

    return (
        user.id === subtask.creator_id ||
        user.id === project?.company?.user_id ||
        project?.managers?.some(m => m.id === user.id) ||
        project?.executors?.some(e => e.id === user.id)
    )
})

const canManageChild = computed(() => {
    const { subtask, user } = props
    if (!subtask || !user) return false
    const project = subtask.task?.project

    return (
        user.id === subtask.creator_id ||
        user.id === project?.company?.user_id ||
        project?.managers?.some(m => m.id === user.id)
    )
})

// Фильтрация сотрудников
const filteredEmployees = computed(() => {
    if (!searchQuery.value) return employees.value
    return employees.value.filter(emp =>
        emp.name.toLowerCase().includes(searchQuery.value.toLowerCase())
    )
})

// Статистика
const completedCount = computed(() => {
    return props.subtask.children?.filter(c => c.completed).length || 0
})

const totalCount = computed(() => {
    return props.subtask.children?.length || 0
})

const progressPercent = computed(() => {
    if (totalCount.value === 0) return 0
    return Math.round((completedCount.value / totalCount.value) * 100)
})

// Форматирование
const formatDate = (date) => {
    if (!date) return 'Срок не указан'
    return new Date(date).toLocaleDateString('ru-RU', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    })
}

const getProgressColor = (progress) => {
    if (progress < 30) return 'bg-red-500'
    if (progress < 70) return 'bg-amber-500'
    return 'bg-emerald-500'
}

const getInitials = (name) => {
    return name
        ? name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase()
        : '?'
}

const getAvatarColor = (id) => {
    const colors = [
        'bg-red-100 text-red-600',
        'bg-blue-100 text-blue-600',
        'bg-green-100 text-green-600',
        'bg-amber-100 text-amber-600',
        'bg-purple-100 text-purple-600'
    ]
    return colors[(id || 0) % colors.length]
}

// Загрузка сотрудников
const fetchEmployees = async () => {
    if (employees.value.length > 0) return
    try {
        const projectId = props.subtask.task?.project?.id
        if (!projectId) return
        const { data } = await axios.get(`/api/projects/${projectId}/employees`)
        employees.value = data
    } catch (e) {
        console.error("Ошибка загрузки сотрудников", e)
    }
}

// Открытие модалки
const openModal = async () => {
    await fetchEmployees()
    form.value = {
        title: '',
        description: '',
        due_date: '',
        executor_ids: [],
        responsible_ids: []
    }
    searchQuery.value = ''
    errorMsg.value = ''
    showModal.value = true
}

// Создание подзадачи
const createChild = async () => {
    if (!form.value.title.trim()) {
        errorMsg.value = 'Название обязательно'
        return
    }

    creating.value = true
    errorMsg.value = ''

    try {
        await axios.post(`/api/subtasks/${props.subtask.id}/children`, form.value)
        emit('refresh')
        showModal.value = false
    } catch (e) {
        errorMsg.value = e?.response?.data?.message || 'Ошибка при создании подзадачи'
    } finally {
        creating.value = false
    }
}

// Удаление подзадачи
const confirmDelete = (child) => {
    selectedChild.value = child
    showDeleteConfirm.value = true
}

const deleteChild = async () => {
    if (!selectedChild.value) return

    try {
        await axios.delete(`/api/subtasks/${selectedChild.value.id}`)
        emit('refresh')
        showDeleteConfirm.value = false
        selectedChild.value = null
    } catch (e) {
        alert(e?.response?.data?.message || 'Ошибка при удалении')
    }
}

// Переключение развертывания
const toggleExpand = (childId) => {
    if (expandedChildren.value.has(childId)) {
        expandedChildren.value.delete(childId)
    } else {
        expandedChildren.value.add(childId)
    }
}

onMounted(() => {
    if (canCreateChild.value) {
        fetchEmployees()
    }
})
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-md transition-all">
        <!-- Заголовок -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-transparent dark:from-gray-700/50">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="p-1.5 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Вложенные подзадачи</h3>

                    <!-- Прогресс-бар -->
                    <div v-if="totalCount > 0" class="flex items-center gap-2 ml-2">
                        <div class="w-20 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div class="h-full bg-emerald-500 transition-all duration-300"
                                 :style="{ width: `${progressPercent}%` }"></div>
                        </div>
                        <span class="text-xs text-gray-500">
                            {{ completedCount }}/{{ totalCount }}
                        </span>
                    </div>
                </div>

                <!-- Кнопка добавления -->
                <button
                    v-if="canCreateChild"
                    @click="openModal"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-xl shadow-sm transition-all transform hover:scale-105 active:scale-95"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Создать
                </button>
            </div>
        </div>

        <!-- Список дочерних задач -->
        <div class="p-6">
            <div v-if="subtask.children?.length" class="space-y-3">
                <div v-for="child in subtask.children" :key="child.id"
                     class="group relative bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-600 hover:border-indigo-200 dark:hover:border-indigo-700 transition-all overflow-hidden">

                    <!-- Основная информация -->
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap mb-1">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white break-words">
                                        {{ child.title }}
                                    </span>

                                    <!-- Статус -->
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded-full"
                                          :class="child.completed
                                              ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                              : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'">
                                        <span class="w-1.5 h-1.5 rounded-full"
                                              :class="child.completed ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                                        {{ child.completed ? 'Завершена' : `${child.progress || 0}%` }}
                                    </span>
                                </div>

                                <!-- Мета-информация -->
                                <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                                    <!-- Срок -->
                                    <span class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        {{ formatDate(child.due_date) }}
                                    </span>

                                    <!-- Исполнители -->
                                    <span v-if="child.executors?.length" class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        {{ child.executors.length }} {{ child.executors.length === 1 ? 'исп.' : 'исп.' }}
                                    </span>

                                    <!-- Прогресс-бар для незавершенных -->
                                    <div v-if="!child.completed" class="flex items-center gap-2 flex-1 max-w-[200px]">
                                        <div class="flex-1 h-1.5 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden">
                                            <div class="h-full transition-all duration-300"
                                                 :class="getProgressColor(child.progress || 0)"
                                                 :style="{ width: `${child.progress || 0}%` }"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Аватары участников (если есть) -->
                                <div v-if="child.executors?.length || child.responsibles?.length"
                                     class="flex items-center gap-2 mt-2">
                                    <div class="flex -space-x-2">
                                        <div v-for="executor in child.executors?.slice(0, 3)" :key="executor.id"
                                             class="w-6 h-6 rounded-full ring-2 ring-white dark:ring-gray-800 flex items-center justify-center text-[10px] font-bold shadow-sm"
                                             :class="getAvatarColor(executor.id)"
                                             :title="executor.name">
                                            {{ getInitials(executor.name) }}
                                        </div>
                                        <div v-if="(child.executors?.length || 0) > 3"
                                             class="w-6 h-6 rounded-full ring-2 ring-white dark:ring-gray-800 bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-[10px] font-medium text-gray-600 dark:text-gray-400">
                                            +{{ (child.executors?.length || 0) - 3 }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Действия -->
                            <div class="flex items-center gap-1 shrink-0">
                                <!-- Кнопка раскрытия описания -->
                                <button v-if="child.description"
                                        @click="toggleExpand(child.id)"
                                        class="p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition"
                                        :title="expandedChildren.has(child.id) ? 'Свернуть' : 'Показать описание'">
                                    <svg class="w-4 h-4 transition-transform"
                                         :class="{ 'rotate-180': expandedChildren.has(child.id) }"
                                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>

                                <!-- Ссылка на подзадачу -->
                                <a :href="`/subtasks/${child.id}`"
                                   class="p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition"
                                   title="Открыть подзадачу">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                </a>

                                <!-- Кнопка удаления (для менеджеров) -->
                                <button v-if="canManageChild"
                                        @click="confirmDelete(child)"
                                        class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition"
                                        title="Удалить">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Описание (раскрывающееся) -->
                        <div v-if="child.description && expandedChildren.has(child.id)"
                             class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-600 animate-slideDown">
                            <p class="text-sm text-gray-600 dark:text-gray-400 whitespace-pre-wrap">
                                {{ child.description }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Пустое состояние -->
            <div v-else class="text-center py-12">
                <div class="w-20 h-20 bg-gray-100 dark:bg-gray-700 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-10 h-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
                <p class="text-gray-500 dark:text-gray-400 text-sm mb-3">Нет вложенных подзадач</p>
                <button v-if="canCreateChild"
                        @click="openModal"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-900/20 dark:hover:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 text-sm font-medium rounded-lg transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Создать первую подзадачу
                </button>
            </div>
        </div>
    </div>

    <!-- Модальное окно создания -->
    <Teleport to="body">
        <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto" @click.self="showModal = false">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <div class="fixed inset-0 bg-black/50 transition-opacity"></div>

                <div class="relative inline-block bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full">
                    <!-- Заголовок -->
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Создание вложенной подзадачи
                        </h3>
                    </div>

                    <!-- Форма -->
                    <form @submit.prevent="createChild" class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto custom-scrollbar">
                        <!-- Название -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Название <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.title"
                                type="text"
                                required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-700 dark:text-white outline-none transition"
                                placeholder="Введите название подзадачи"
                            />
                        </div>

                        <!-- Описание -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Описание
                            </label>
                            <textarea
                                v-model="form.description"
                                rows="3"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-700 dark:text-white outline-none transition resize-none"
                                placeholder="Введите описание (необязательно)"
                            ></textarea>
                        </div>

                        <!-- Дата -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Дата окончания
                            </label>
                            <input
                                v-model="form.due_date"
                                type="date"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-700 dark:text-white outline-none transition"
                            />
                        </div>

                        <!-- Поиск сотрудников -->
                        <div class="relative">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Поиск сотрудников
                            </label>
                            <div class="relative">
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    class="w-full px-3 py-2 pl-9 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-700 dark:text-white outline-none transition"
                                    placeholder="Введите имя для поиска..."
                                />
                                <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>

                        <!-- Исполнители -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Исполнители
                            </label>
                            <div class="max-h-40 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-lg divide-y dark:divide-gray-700">
                                <label v-for="emp in filteredEmployees" :key="emp.id"
                                       class="flex items-center gap-3 px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        v-model="form.executor_ids"
                                        :value="emp.id"
                                        class="w-4 h-4 text-emerald-600 rounded border-gray-300 focus:ring-emerald-500"
                                    />
                                    <div class="flex items-center gap-2 flex-1">
                                        <div class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold shadow-sm"
                                             :class="getAvatarColor(emp.id)">
                                            {{ getInitials(emp.name) }}
                                        </div>
                                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ emp.name }}</span>
                                    </div>
                                </label>
                                <div v-if="!filteredEmployees.length" class="px-3 py-4 text-center text-gray-500 text-sm">
                                    Нет сотрудников для выбора
                                </div>
                            </div>
                        </div>

                        <!-- Ответственные -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Ответственные
                            </label>
                            <div class="max-h-40 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-lg divide-y dark:divide-gray-700">
                                <label v-for="emp in filteredEmployees" :key="emp.id"
                                       class="flex items-center gap-3 px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        v-model="form.responsible_ids"
                                        :value="emp.id"
                                        class="w-4 h-4 text-amber-600 rounded border-gray-300 focus:ring-amber-500"
                                    />
                                    <div class="flex items-center gap-2 flex-1">
                                        <div class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold shadow-sm"
                                             :class="getAvatarColor(emp.id)">
                                            {{ getInitials(emp.name) }}
                                        </div>
                                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ emp.name }}</span>
                                    </div>
                                </label>
                                <div v-if="!filteredEmployees.length" class="px-3 py-4 text-center text-gray-500 text-sm">
                                    Нет сотрудников для выбора
                                </div>
                            </div>
                        </div>

                        <!-- Ошибка -->
                        <div v-if="errorMsg" class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                            <p class="text-sm text-red-600 dark:text-red-400">{{ errorMsg }}</p>
                        </div>

                        <!-- Кнопки -->
                        <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <button type="button"
                                    @click="showModal = false"
                                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                Отмена
                            </button>
                            <button type="submit"
                                    :disabled="creating || !form.title.trim()"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 disabled:bg-emerald-400 text-white rounded-lg transition">
                                <svg v-if="creating" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ creating ? 'Создание...' : 'Создать' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Модальное окно подтверждения удаления -->
    <Teleport to="body">
        <div v-if="showDeleteConfirm" class="fixed inset-0 z-50 overflow-y-auto" @click.self="showDeleteConfirm = false">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50 transition-opacity"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Подтверждение удаления</h3>
                    </div>

                    <div class="p-6">
                        <p class="text-gray-700 dark:text-gray-300 mb-4">
                            Вы уверены, что хотите удалить подзадачу
                            <span class="font-medium">"{{ selectedChild?.title }}"</span>?
                            Это действие нельзя отменить.
                        </p>

                        <div class="flex justify-end gap-3">
                            <button @click="showDeleteConfirm = false"
                                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                Отмена
                            </button>
                            <button @click="deleteChild"
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                                Удалить
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

.animate-slideDown {
    animation: slideDown 0.2s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Кастомный скроллбар */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 20px;
}

.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #4b5563;
}

/* Плавные переходы */
.transition-all {
    transition: all 0.2s ease-in-out;
}

/* Анимация для прогресс-бара */
.h-full {
    transition: width 0.3s ease;
}

/* Стили для аватаров */
.-space-x-2 > * {
    margin-left: -0.5rem;
}

.-space-x-2 > *:first-child {
    margin-left: 0;
}
</style>
