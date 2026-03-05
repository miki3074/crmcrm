<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'

const errorText = ref('')
const successMessage = ref('')

const props = defineProps({
    taskId: { type: Number, required: true },
    executors: { type: Array, default: () => [] },
    responsibles: { type: Array, default: () => [] },
    creator: { type: Object, default: () => null },
    userId: { type: Number, required: true }
})

const list = ref([])
const loading = ref(false)
const showModal = ref(false)
const isEditing = ref(false)
const editingId = ref(null)
const submitting = ref(false)

const form = ref({
    title: '',
    assigned_to: '',
    important: false,
    files: [],
})

// Комбинированный список всех возможных исполнителей
const allAssignees = computed(() => {
    const assignees = []

    // Добавляем исполнителей
    if (props.executors?.length) {
        assignees.push(...props.executors.map(e => ({ ...e, type: 'executor' })))
    }

    // Добавляем ответственных
    if (props.responsibles?.length) {
        assignees.push(...props.responsibles.map(r => ({ ...r, type: 'responsible' })))
    }

    // Добавляем создателя, если его нет в списках
    if (props.creator && !assignees.some(a => a.id === props.creator.id)) {
        assignees.push({ ...props.creator, type: 'creator' })
    }

    return assignees
})

const resetForm = () => {
    form.value = {
        title: '',
        assigned_to: '',
        important: false,
        files: [],
    }
    errorText.value = ''
    successMessage.value = ''
    isEditing.value = false
    editingId.value = null
}

const load = async () => {
    loading.value = true
    try {
        const { data } = await axios.get(`/api/tasks/${props.taskId}/checklists`)
        list.value = data
    } catch (error) {
        handleError(error, 'Не удалось загрузить чек-листы')
    } finally {
        loading.value = false
    }
}

const openCreateModal = () => {
    resetForm()
    showModal.value = true
}

const openEditModal = (item) => {
    resetForm()
    isEditing.value = true
    editingId.value = item.id

    form.value.title = item.title
    form.value.assigned_to = item.assigned_to || ''
    form.value.important = !!item.important

    showModal.value = true
}

const handleError = (error, defaultMessage) => {
    if (error.response?.status === 403) {
        errorText.value = 'У вас нет прав на выполнение этого действия.'
    } else if (error.response?.status === 422) {
        const data = error.response.data
        errorText.value = data.message || Object.values(data.errors || {})[0]?.[0] || 'Проверьте правильность заполнения полей.'
    } else {
        errorText.value = error.response?.data?.message || defaultMessage || 'Произошла ошибка.'
    }
}

const submit = async () => {
    if (!form.value.title.trim()) {
        errorText.value = 'Название обязательно для заполнения'
        return
    }

    errorText.value = ''
    successMessage.value = ''
    submitting.value = true

    const fd = new FormData()
    fd.append('title', form.value.title.trim())

    if (form.value.assigned_to) {
        fd.append('assigned_to', form.value.assigned_to)
    }

    fd.append('important', form.value.important ? 1 : 0)

    if (form.value.files?.length) {
        form.value.files.forEach(file => {
            fd.append('files[]', file)
        })
    }

    try {
        if (isEditing.value) {
            fd.append('_method', 'PUT')
            await axios.post(`/api/checklists/${editingId.value}`, fd, {
                headers: { 'Content-Type': 'multipart/form-data' },
            })
            successMessage.value = 'Пункт успешно обновлен'
        } else {
            await axios.post(`/api/tasks/${props.taskId}/checklists`, fd, {
                headers: { 'Content-Type': 'multipart/form-data' },
            })
            successMessage.value = 'Пункт успешно создан'
        }

        showModal.value = false
        resetForm()
        await load()

        // Автоматически скрываем сообщение через 3 секунды
        setTimeout(() => {
            successMessage.value = ''
        }, 3000)

    } catch (error) {
        handleError(error, 'Не удалось сохранить пункт')
    } finally {
        submitting.value = false
    }
}

const remove = async (id) => {
    if (!confirm('Вы уверены, что хотите удалить этот пункт?')) return

    try {
        await axios.delete(`/api/checklists/${id}`)
        await load()
        successMessage.value = 'Пункт успешно удален'

        setTimeout(() => {
            successMessage.value = ''
        }, 3000)

    } catch (error) {
        alert('Не удалось удалить: ' + (error.response?.data?.message || 'Ошибка сервера'))
    }
}

const toggle = async (item) => {
    // Оптимистичное обновление
    const previousState = item.completed
    item.completed = !item.completed

    try {
        await axios.patch(`/api/checklists/${item.id}/toggle`)
    } catch (error) {
        // Откатываем изменение в случае ошибки
        item.completed = previousState
        alert('Не удалось изменить статус: ' + (error.response?.data?.message || 'Ошибка сервера'))
    }
}

const canManage = (item) => {
    if (!item.created_by) return true
    return item.created_by === props.userId
}

const formatFileSize = (bytes) => {
    if (!bytes) return '0 B'
    const k = 1024
    const sizes = ['B', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return `${parseFloat((bytes / Math.pow(k, i)).toFixed(1))} ${sizes[i]}`
}

onMounted(load)
</script>

<template>
    <div class="checklists-component mt-6">
        <!-- Заголовок с счетчиком -->
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">
                Чек-листы
                <span v-if="list.length" class="ml-2 text-sm font-normal text-gray-500">
                    ({{ list.filter(c => c.completed).length }}/{{ list.length }})
                </span>
            </h3>

            <button
                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                @click="openCreateModal"
            >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Добавить пункт
            </button>
        </div>

        <!-- Сообщение об успехе -->
        <div v-if="successMessage" class="mb-4 p-3 bg-green-50 text-green-700 rounded-lg border border-green-200">
            {{ successMessage }}
        </div>

        <!-- Состояние загрузки -->
        <div v-if="loading" class="flex justify-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
        </div>

        <!-- Список чек-листов -->
        <div v-else-if="list.length" class="space-y-2">
            <div
                v-for="c in list"
                :key="c.id"
                class="group relative bg-white border rounded-lg p-4 hover:shadow-md transition-shadow"
                :class="{ 'border-indigo-200 bg-indigo-50/30': c.important }"
            >
                <div class="flex items-start gap-3">
                    <!-- Чекбокс -->
                    <div class="flex-shrink-0 pt-0.5">
                        <input
                            type="checkbox"
                            :checked="c.completed"
                            @change="toggle(c)"
                            class="w-5 h-5 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500 cursor-pointer"
                            :disabled="loading"
                        />
                    </div>

                    <!-- Контент -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span
                                class="text-base"
                                :class="{
                                    'font-semibold text-indigo-700': c.important && !c.completed,
                                    'line-through text-gray-400': c.completed
                                }"
                            >
                                {{ c.title }}
                            </span>

                            <!-- Бейдж "Важно" -->
                            <span
                                v-if="c.important"
                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800"
                            >
                                Важно
                            </span>
                        </div>

                        <!-- Мета-информация -->
                        <div class="mt-2 flex flex-wrap items-center gap-3 text-xs text-gray-500">
                            <span v-if="c.assignee" class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                {{ c.assignee.name }}
                            </span>

                            <span v-if="c.creator" class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                {{ c.creator.name }}
                            </span>

                            <span v-if="!c.creator" class="italic">
                                Общий пункт
                            </span>
                        </div>

                        <!-- Файлы -->
                        <div v-if="c.files?.length" class="mt-3 flex flex-wrap gap-2">
                            <a
                                v-for="f in c.files"
                                :key="f.id"
                                :href="f.url"
                                target="_blank"
                                class="inline-flex items-center gap-1 px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded hover:bg-gray-200 transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 107.071 7.071l5.414-5.414"/>
                                </svg>
                                <span class="truncate max-w-[150px]">{{ f.name }}</span>
                                <span class="text-gray-400">({{ formatFileSize(f.size) }})</span>
                            </a>
                        </div>
                    </div>

                    <!-- Кнопки действий -->
                    <div
                        v-if="canManage(c)"
                        class="flex-shrink-0 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity"
                    >
                        <button
                            @click="openEditModal(c)"
                            class="p-1 text-gray-500 hover:text-indigo-600 rounded hover:bg-gray-100"
                            title="Редактировать"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                        <button
                            @click="remove(c.id)"
                            class="p-1 text-gray-500 hover:text-red-600 rounded hover:bg-gray-100"
                            title="Удалить"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Пустое состояние -->
        <div v-else class="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Чек-листов пока нет</h3>
            <p class="mt-1 text-sm text-gray-500">Начните с создания первого пункта</p>
        </div>

        <!-- Модальное окно -->
        <div
            v-if="showModal"
            class="fixed inset-0 z-50 overflow-y-auto"
            @click.self="showModal = false"
        >
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <!-- Затемнение -->
                <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>

                <!-- Модалка -->
                <div class="relative inline-block bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-semibold text-gray-900 mb-4">
                                    {{ isEditing ? 'Редактирование пункта' : 'Создание нового пункта' }}
                                </h3>

                                <!-- Ошибки -->
                                <div v-if="errorText" class="mb-4 p-3 bg-red-50 text-red-700 rounded-lg border border-red-200 text-sm">
                                    {{ errorText }}
                                </div>

                                <!-- Форма -->
                                <form @submit.prevent="submit" class="space-y-4">
                                    <!-- Название -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Название <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            v-model="form.title"
                                            type="text"
                                            required
                                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                                            placeholder="Введите название пункта"
                                            :disabled="submitting"
                                        />
                                    </div>

                                    <!-- Ответственный -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Ответственный
                                        </label>
                                        <select
                                            v-model="form.assigned_to"
                                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                                            :disabled="submitting"
                                        >
                                            <option value="">— Не назначен —</option>
                                            <option
                                                v-for="assignee in allAssignees"
                                                :key="assignee.id"
                                                :value="assignee.id"
                                            >
                                                {{ assignee.name }}
                                                <span v-if="assignee.type === 'executor'">(Исполнитель)</span>
                                                <span v-else-if="assignee.type === 'responsible'">(Ответственный)</span>
                                                <span v-else-if="assignee.type === 'creator'">(Создатель)</span>
                                            </option>
                                        </select>
                                    </div>

                                    <!-- Важность -->
                                    <div class="flex items-center">
                                        <input
                                            type="checkbox"
                                            v-model="form.important"
                                            class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                            :disabled="submitting"
                                            id="important"
                                        />
                                        <label for="important" class="ml-2 block text-sm text-gray-700">
                                            Пометить как важное
                                        </label>
                                    </div>

                                    <!-- Файлы -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Прикрепить файлы
                                        </label>
                                        <input
                                            type="file"
                                            multiple
                                            @change="e => form.files = Array.from(e.target.files)"
                                            class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                                            :disabled="submitting"
                                        />
                                        <p v-if="form.files.length" class="mt-1 text-xs text-gray-500">
                                            Выбрано файлов: {{ form.files.length }}
                                        </p>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Кнопки -->
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                        <button
                            @click="submit"
                            class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm"
                            :disabled="submitting"
                        >
                            <svg v-if="submitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ isEditing ? 'Сохранить' : 'Создать' }}
                        </button>
                        <button
                            @click="showModal = false"
                            class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm"
                            :disabled="submitting"
                        >
                            Отмена
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Анимации для модального окна */
.fixed {
    animation: fadeIn 0.2s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

/* Улучшенный скроллбар */
.checklists-component ::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.checklists-component ::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.checklists-component ::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

.checklists-component ::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>
