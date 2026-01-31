<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const errorText = ref('')

const props = defineProps({
    taskId: { type: Number, required: true },
    executors: { type: Array, default: () => [] },
    responsibles: { type: Array, default: () => [] },
    creator: { type: Object, default: () => null },
    // 🔥 Важно: нам нужно знать ID текущего пользователя для проверки прав на фронте
    userId: { type: Number, required: true }
})

const list = ref([])
const loading = ref(false)
const showModal = ref(false)
const isEditing = ref(false) // Режим редактирования
const editingId = ref(null)  // ID редактируемой записи

const form = ref({
    title: '',
    assigned_to: '',
    important: false,
    files: [],
})

// Сброс формы
const resetForm = () => {
    form.value = { title: '', assigned_to: '', important: false, files: [] }
    errorText.value = ''
    isEditing.value = false
    editingId.value = null
}

const load = async () => {
    loading.value = true
    try {
        const { data } = await axios.get(`/api/tasks/${props.taskId}/checklists`)
        list.value = data
    } finally {
        loading.value = false
    }
}

// Открытие модалки для создания
const openCreateModal = () => {
    resetForm()
    showModal.value = true
}

// Открытие модалки для редактирования
const openEditModal = (item) => {
    resetForm()
    isEditing.value = true
    editingId.value = item.id

    // Заполняем форму текущими данными
    form.value.title = item.title
    form.value.assigned_to = item.assigned_to || ''
    form.value.important = !!item.important
    // Файлы при редактировании обычно не предзаполняются в input type=file

    showModal.value = true
}

const submit = async () => {
    errorText.value = ''

    // FormData нужна для отправки файлов (если они есть)
    const fd = new FormData()
    fd.append('title', form.value.title)
    if (form.value.assigned_to) fd.append('assigned_to', form.value.assigned_to)
    fd.append('important', form.value.important ? 1 : 0)

    // Прикрепляем файлы (только новые)
    if (form.value.files && form.value.files.length) {
        for (let f of form.value.files) fd.append('files[]', f)
    }

    try {
        if (isEditing.value) {
            // 🔥 Редактирование (метод PUT)
            // Laravel иногда капризничает с PUT через FormData, поэтому используем _method
            fd.append('_method', 'PUT')
            await axios.post(`/api/checklists/${editingId.value}`, fd, {
                headers: { 'Content-Type': 'multipart/form-data' },
            })
        } else {
            // 🔥 Создание
            await axios.post(`/api/tasks/${props.taskId}/checklists`, fd, {
                headers: { 'Content-Type': 'multipart/form-data' },
            })
        }

        showModal.value = false
        resetForm()
        await load()
    } catch (e) {
        if (e.response?.status === 403) {
            errorText.value = 'У вас нет прав на выполнение этого действия.'
        } else if (e.response?.status === 422) {
            const data = e.response.data
            errorText.value = data.message || Object.values(data.errors || {})[0]?.[0] || 'Ошибка валидации.'
        } else {
            errorText.value = 'Произошла ошибка.'
        }
    }
}

// Удаление
const remove = async (id) => {
    if (!confirm('Вы уверены, что хотите удалить этот пункт?')) return

    try {
        await axios.delete(`/api/checklists/${id}`)
        await load()
    } catch (e) {
        alert('Не удалось удалить: ' + (e.response?.data?.message || 'Ошибка сервера'))
    }
}

const toggle = async (item) => {
    await axios.patch(`/api/checklists/${item.id}/toggle`)
    // Локальное обновление для скорости интерфейса
    item.completed = !item.completed
}

// Проверка прав на фронтенде (чтобы скрыть кнопки)
const canManage = (item) => {
    // Если created_by null -> могут все
    if (!item.created_by) return true
    // Иначе только тот, чей ID совпадает с props.userId
    return item.created_by === props.userId
}

onMounted(load)
</script>

<template>
    <div class="mt-6">
        <h3 class="text-lg font-semibold mb-2">Чек-листы</h3>

        <ul v-if="list.length" class="space-y-2">
            <li v-for="c in list" :key="c.id" class="group flex items-start justify-between bg-gray-50 p-2 rounded hover:bg-gray-100">

                <div class="flex items-center gap-2 flex-1">
                    <input type="checkbox" :checked="c.completed" @change="toggle(c)" class="cursor-pointer" />

                    <div class="flex flex-col">
            <span :class="{'font-bold text-red-600': c.important, 'line-through text-gray-400': c.completed}">
                {{ c.title }}
            </span>
                        <span class="text-xs text-gray-500 flex gap-2">
                <span v-if="c.assignee">👤 {{ c.assignee.name }}</span>
                <span v-if="c.creator">✍️ {{ c.creator.name }}</span>
                <span v-if="!c.creator" class="italic">(Общий)</span>
            </span>
                        <!-- Отображение файлов, если нужно -->
                        <div v-if="c.files && c.files.length" class="flex gap-1 mt-1">
                            <span v-for="f in c.files" :key="f.id" class="text-xs text-blue-500">📎 Файл</span>
                        </div>
                    </div>
                </div>

                <!-- Кнопки действий (показываем только если есть права) -->
                <div v-if="canManage(c)" class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button @click="openEditModal(c)" class="text-blue-600 hover:text-blue-800 text-sm">
                        ✏️
                    </button>
                    <button @click="remove(c.id)" class="text-red-600 hover:text-red-800 text-sm">
                        🗑️
                    </button>
                </div>

            </li>
        </ul>
        <p v-else class="text-gray-500">Нет чек-листов</p>

        <button class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition" @click="openCreateModal">
            Добавить чек-лист
        </button>

        <!-- Модальное окно -->
        <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center" style="z-index: 999">
            <div class="bg-white rounded p-6 w-96 shadow-xl">
                <h4 class="text-lg font-semibold mb-4">
                    {{ isEditing ? 'Редактировать пункт' : 'Новый пункт чек-листа' }}
                </h4>

                <p v-if="errorText" class="text-sm text-rose-600 mb-2 bg-rose-50 p-1 rounded">{{ errorText }}</p>

                <input required v-model="form.title" type="text" placeholder="Название" class="w-full border rounded mb-3 p-2 focus:ring-2 focus:ring-indigo-500 outline-none" />

                <label class="block text-sm text-gray-700 mb-1">Ответственный</label>
                <select v-model="form.assigned_to" class="w-full border rounded mb-3 p-2">
                    <option value="">— Не назначен —</option>
                    <option v-for="e in executors" :key="'exec-'+e.id" :value="e.id">{{ e.name }}</option>
                    <option v-for="r in responsibles" :key="'resp-'+r.id" :value="r.id">{{ r.name }}</option>
                    <option v-if="creator" :value="creator.id">{{ creator.name }}</option>
                </select>

                <label class="flex items-center gap-2 mb-4 cursor-pointer">
                    <input type="checkbox" v-model="form.important" class="w-4 h-4 text-indigo-600" />
                    <span class="text-sm">Пометить как "Важно"</span>
                </label>

                <!-- Поле файлов показываем только при создании, или если вы доделаете загрузку при редактировании -->
                <div class="mb-4">
                    <label class="block text-sm text-gray-700 mb-1">Прикрепить файлы</label>
                    <input type="file" multiple @change="e => form.files = Array.from(e.target.files)" class="w-full text-sm text-gray-500" />
                </div>

                <div class="flex justify-end gap-2 border-t pt-3">
                    <button @click="showModal = false" class="px-3 py-1 text-gray-600 hover:bg-gray-100 rounded">Отмена</button>
                    <button @click="submit" class="px-3 py-1 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                        {{ isEditing ? 'Сохранить изменения' : 'Создать' }}
                    </button>
                </div>
            </div>
        </div>

    </div>
</template>
