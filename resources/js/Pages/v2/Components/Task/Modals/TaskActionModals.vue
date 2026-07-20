<script setup>
// Этот компонент содержит модалки: Редактировать, Описание, Удалить, Подзадача
import { ref, watch } from 'vue'

const props = defineProps({
    modals: Object, // { edit: bool, description: bool, delete: bool, subtask: bool }
    task: Object,
    employees: Array,
    loading: Boolean,
    error: String
})

const emit = defineEmits(['close', 'update', 'saveDescription', 'deleteTask', 'createSubtask'])

// Локальные формы, инициализируются при открытии
const editForm = ref({})
const subtaskForm = ref({})
const descForm = ref('')

watch(() => props.modals.edit, (val) => {
    if(val) editForm.value = { title: props.task.title, start_date: props.task.start_date, due_date: props.task.due_date }
})
watch(() => props.modals.description, (val) => {
    if(val) descForm.value = props.task.description
})
watch(() => props.modals.subtask, (val) => {
    if(val) subtaskForm.value = { title: '', executor_id: '', responsible_id: '', start_date: new Date().toISOString().slice(0,10), due_date: '' }
})
</script>

<template>
    <!-- 1. EDIT MODAL -->
    <div v-if="modals.edit" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md shadow-xl">
            <h3 class="font-bold text-lg mb-4 dark:text-white">Редактировать задачу</h3>
            <form @submit.prevent="$emit('update', editForm)" class="space-y-3">
                <input v-model="editForm.title" class="w-full border rounded p-2 dark:bg-gray-700 dark:text-white" required placeholder="Название"/>
                <input type="date" v-model="editForm.start_date" class="w-full border rounded p-2 dark:bg-gray-700 dark:text-white"/>
                <input type="date" v-model="editForm.due_date" class="w-full border rounded p-2 dark:bg-gray-700 dark:text-white"/>
                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" @click="$emit('close', 'edit')" class="px-4 py-2 rounded bg-gray-200">Отмена</button>
                    <button type="submit" class="px-4 py-2 rounded bg-blue-600 text-white">Сохранить</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 2. DESCRIPTION MODAL -->
    <div v-if="modals.description" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-lg shadow-xl">
            <h3 class="font-bold text-lg mb-4 dark:text-white">Описание</h3>
            <textarea v-model="descForm" class="w-full border rounded p-3 h-40 dark:bg-gray-700 dark:text-white"></textarea>
            <div class="flex justify-end gap-2 mt-4">
                <button @click="$emit('close', 'description')" class="px-4 py-2 rounded bg-gray-200">Отмена</button>
                <button @click="$emit('saveDescription', descForm)" class="px-4 py-2 rounded bg-indigo-600 text-white">Сохранить</button>
            </div>
        </div>
    </div>

    <!-- 3. SUBTASK MODAL -->
    <div v-if="modals.subtask" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md shadow-xl">
            <h3 class="font-bold text-lg mb-4 dark:text-white">Новая подзадача</h3>
            <form @submit.prevent="$emit('createSubtask', subtaskForm)" class="space-y-3">
                <input v-model="subtaskForm.title" class="w-full border rounded p-2 dark:bg-gray-700 dark:text-white" placeholder="Название" required/>
                <select v-model="subtaskForm.executor_id" class="w-full border rounded p-2 dark:bg-gray-700 dark:text-white">
                    <option value="">Исполнитель</option>
                    <option v-for="u in employees" :key="u.id" :value="u.id">{{ u.name }}</option>
                </select>
                <select v-model="subtaskForm.responsible_id" class="w-full border rounded p-2 dark:bg-gray-700 dark:text-white">
                    <option value="">Ответственный</option>
                    <option v-for="u in employees" :key="u.id" :value="u.id">{{ u.name }}</option>
                </select>
                <div class="grid grid-cols-2 gap-2">
                    <input type="date" v-model="subtaskForm.start_date" class="w-full border rounded p-2 dark:bg-gray-700 dark:text-white" required/>
                    <input type="date" v-model="subtaskForm.due_date" class="w-full border rounded p-2 dark:bg-gray-700 dark:text-white" required/>
                </div>
                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" @click="$emit('close', 'subtask')" class="px-4 py-2 rounded bg-gray-200">Отмена</button>
                    <button type="submit" :disabled="loading" class="px-4 py-2 rounded bg-emerald-600 text-white">Создать</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 4. DELETE MODAL -->
    <div v-if="modals.delete" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md shadow-xl">
            <h3 class="font-bold text-lg mb-2 text-rose-600">Удалить задачу?</h3>
            <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">Это действие необратимо.</p>
            <div class="flex justify-end gap-2">
                <button @click="$emit('close', 'delete')" class="px-4 py-2 rounded bg-gray-200">Отмена</button>
                <button @click="$emit('deleteTask')" class="px-4 py-2 rounded bg-rose-600 text-white">Удалить</button>
            </div>
        </div>
    </div>
</template>
