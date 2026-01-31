<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'

const props = defineProps({
    subtaskId: Number,
    checklist: { type: Array, default: () => [] },
    executors: { type: Array, default: () => [] },
    responsibles: { type: Array, default: () => [] },
    canWrite: Boolean,
    // Важно передать ID текущего пользователя из родителя
    userId: { type: Number, required: true }
})

const emit = defineEmits(['updated']) // Событие для обновления данных в родителе

const list = computed(() => props.checklist ?? [])
const newItem = ref('')
const newResponsible = ref(null)

// --- Логика редактирования ---
const showEditModal = ref(false)
const editForm = ref({ id: null, title: '', responsible_id: null })

const openEdit = (item) => {
    editForm.value = {
        id: item.id,
        title: item.title,
        responsible_id: item.responsible_id
    }
    showEditModal.value = true
}

const updateItem = async () => {
    if (!editForm.value.title.trim()) return

    try {
        const { data } = await axios.put(`/api/subtask-checklist/${editForm.value.id}`, {
            title: editForm.value.title,
            responsible_id: editForm.value.responsible_id
        })

        emit('updated', { type: 'update', item: data }) // Родитель должен обновить массив
        showEditModal.value = false
    } catch (e) {
        alert(e.response?.data?.message || 'Ошибка обновления')
    }
}

// --- Логика удаления ---
const removeItem = async (id) => {
    if (!confirm('Вы уверены, что хотите удалить этот пункт?')) return
    try {
        await axios.delete(`/api/subtask-checklist/${id}`)
        emit('updated', { type: 'delete', id })
    } catch (e) {
        alert(e.response?.data?.message || 'Ошибка удаления')
    }
}

// --- Логика создания ---
const addItem = async () => {
    if (!newItem.value.trim()) return
    try {
        const { data } = await axios.post(`/api/subtasks/${props.subtaskId}/checklist`, {
            title: newItem.value,
            responsible_id: newResponsible.value,
        })
        emit('updated', { type: 'add', item: data })
        newItem.value = ''
        newResponsible.value = null
    } catch (e) {
        console.error(e)
    }
}

const toggle = async (item) => {
    try {
        const { data } = await axios.patch(`/api/subtask-checklist/${item.id}/toggle`)
        emit('updated', { type: 'toggle', id: item.id, completed: data.completed })
    } catch (e) {
        console.error(e)
    }
}

// Хелпер проверки прав: владелец или пункт "общий"
const canManageItem = (item) => {
    if (!props.canWrite) return false; // Глобальный запрет на запись
    if (!item.creator_id) return true; // Если creator_id null, можно всем
    return item.creator_id === props.userId; // Иначе только создателю
}
</script>

<template>
    <div class="mt-6 p-4 bg-white dark:bg-slate-800 rounded-xl shadow">
        <h3 class="text-lg font-semibold mb-3 text-gray-800 dark:text-gray-100">📝 Чек-лист</h3>

        <p v-if="list.length === 0" class="text-gray-500 text-sm">
            Пусто.
        </p>

        <div class="space-y-3" v-else>
            <div v-for="item in list" :key="item.id"
                 class="group p-3 border rounded-lg dark:border-slate-700 flex justify-between items-start bg-gray-50 dark:bg-slate-700/50 hover:bg-white dark:hover:bg-slate-700 transition">

                <div class="flex items-start gap-3 w-full">
                    <input type="checkbox"
                           :checked="item.completed"
                           @change="toggle(item)"
                           class="mt-1 w-5 h-5 cursor-pointer text-indigo-600 rounded focus:ring-indigo-500"/>

                    <div class="flex-1">
                        <p :class="item.completed ? 'line-through text-gray-400' : 'text-gray-800 dark:text-gray-200'">
                            {{ item.title }}
                        </p>

                        <div class="flex gap-2 items-center mt-1">
                <span v-if="item.responsible" class="text-xs text-gray-500 bg-gray-100 dark:bg-slate-600 px-1.5 py-0.5 rounded">
                  👤 {{ item.responsible.name }}
                </span>
                            <span v-if="item.creator" class="text-[10px] text-gray-400">
                  (Созд: {{ item.creator.name }})
                </span>
                        </div>
                    </div>
                </div>

                <!-- Кнопки действий (видим только если есть права) -->
                <div v-if="canManageItem(item)" class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity ml-2">
                    <button @click="openEdit(item)" class="text-blue-500 hover:text-blue-700" title="Редактировать">
                        ✏️
                    </button>
                    <button @click="removeItem(item.id)" class="text-red-500 hover:text-red-700" title="Удалить">
                        🗑️
                    </button>
                </div>

            </div>
        </div>

        <!-- Форма добавления -->
        <div v-if="canWrite" class="mt-4 border-t pt-4 dark:border-slate-700">
            <input v-model="newItem"
                   class="w-full border rounded-lg px-3 py-2 dark:bg-slate-700 dark:text-white dark:border-slate-600 focus:ring-2 focus:ring-indigo-500 outline-none"
                   placeholder="Новый пункт..."/>

            <div class="flex gap-2 mt-2">
                <select v-model="newResponsible"
                        class="flex-1 border rounded-lg px-3 py-2 dark:bg-slate-700 dark:text-white dark:border-slate-600">
                    <option :value="null">Без ответственного</option>
                    <option v-for="u in [...executors, ...responsibles]" :key="u.id" :value="u.id">
                        {{ u.name }}
                    </option>
                </select>

                <button @click="addItem"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition">
                    ➕ Добавить
                </button>
            </div>
        </div>

        <!-- Модальное окно редактирования -->
        <div v-if="showEditModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-slate-800 p-6 rounded-lg w-96 shadow-xl">
                <h4 class="text-lg font-bold mb-4 text-gray-800 dark:text-white">Редактировать пункт</h4>

                <input v-model="editForm.title"
                       class="w-full border rounded mb-3 p-2 dark:bg-slate-700 dark:text-white dark:border-slate-600"
                       placeholder="Название" />

                <label class="block text-sm text-gray-500 mb-1">Ответственный</label>
                <select v-model="editForm.responsible_id" class="w-full border rounded mb-4 p-2 dark:bg-slate-700 dark:text-white dark:border-slate-600">
                    <option :value="null">Без ответственного</option>
                    <option v-for="u in [...executors, ...responsibles]" :key="u.id" :value="u.id">
                        {{ u.name }}
                    </option>
                </select>

                <div class="flex justify-end gap-2">
                    <button @click="showEditModal = false" class="px-3 py-1 text-gray-500 hover:bg-gray-100 dark:hover:bg-slate-700 rounded">
                        Отмена
                    </button>
                    <button @click="updateItem" class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Сохранить
                    </button>
                </div>
            </div>
        </div>

    </div>
</template>
