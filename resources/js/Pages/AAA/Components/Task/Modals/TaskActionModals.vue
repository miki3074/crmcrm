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

const vAutofocus = {
    mounted: el => el.focus(),
}
</script>

<template>
    <!-- 1. EDIT MODAL -->
    <div v-if="modals.edit" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div v-autofocus role="dialog" aria-modal="true" aria-label="Редактировать задачу" tabindex="-1" class="bg-white dark:bg-zinc-800 rounded-2xl p-6 w-full max-w-md shadow-xl" @keydown.esc="$emit('close', 'edit')">
            <h3 class="font-bold text-lg mb-4 dark:text-white">Редактировать задачу</h3>
            <p v-if="error" class="mb-3 rounded-lg bg-rose-50 px-3 py-2 text-sm font-medium text-rose-600 dark:bg-rose-950/40 dark:text-rose-400">{{ error }}</p>
            <form @submit.prevent="$emit('update', editForm)" class="space-y-3">
                <input v-model="editForm.title" class="w-full border rounded p-2 dark:bg-zinc-700 dark:text-white" required placeholder="Название"/>
                <input type="date" v-model="editForm.start_date" class="w-full border rounded p-2 dark:bg-zinc-700 dark:text-white"/>
                <input type="date" v-model="editForm.due_date" class="w-full border rounded p-2 dark:bg-zinc-700 dark:text-white"/>
                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" @click="$emit('close', 'edit')" class="px-4 py-2 rounded bg-zinc-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400">Отмена</button>
                    <button type="submit" :disabled="loading" class="px-4 py-2 rounded bg-cyan-600 text-white disabled:opacity-60 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400">
                        {{ loading ? 'Сохранение…' : 'Сохранить' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 2. DESCRIPTION MODAL -->
    <div v-if="modals.description" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div v-autofocus role="dialog" aria-modal="true" aria-label="Описание задачи" tabindex="-1" class="bg-white dark:bg-zinc-800 rounded-2xl p-6 w-full max-w-lg shadow-xl" @keydown.esc="$emit('close', 'description')">
            <h3 class="font-bold text-lg mb-4 dark:text-white">Описание</h3>
            <p v-if="error" class="mb-3 rounded-lg bg-rose-50 px-3 py-2 text-sm font-medium text-rose-600 dark:bg-rose-950/40 dark:text-rose-400">{{ error }}</p>
            <textarea v-model="descForm" class="w-full border rounded p-3 h-40 dark:bg-zinc-700 dark:text-white"></textarea>
            <div class="flex justify-end gap-2 mt-4">
                <button type="button" @click="$emit('close', 'description')" class="px-4 py-2 rounded bg-zinc-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400">Отмена</button>
                <button type="button" :disabled="loading" @click="$emit('saveDescription', descForm)" class="px-4 py-2 rounded bg-cyan-600 text-white disabled:opacity-60 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400">
                    {{ loading ? 'Сохранение…' : 'Сохранить' }}
                </button>
            </div>
        </div>
    </div>

    <!-- 3. SUBTASK MODAL -->
    <div v-if="modals.subtask" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div v-autofocus role="dialog" aria-modal="true" aria-label="Новая подзадача" tabindex="-1" class="bg-white dark:bg-zinc-800 rounded-2xl p-6 w-full max-w-md shadow-xl" @keydown.esc="$emit('close', 'subtask')">
            <h3 class="font-bold text-lg mb-4 dark:text-white">Новая подзадача</h3>
            <p v-if="error" class="mb-3 rounded-lg bg-rose-50 px-3 py-2 text-sm font-medium text-rose-600 dark:bg-rose-950/40 dark:text-rose-400">{{ error }}</p>
            <form @submit.prevent="$emit('createSubtask', subtaskForm)" class="space-y-3">
                <input v-model="subtaskForm.title" class="w-full border rounded p-2 dark:bg-zinc-700 dark:text-white" placeholder="Название" required/>
                <select v-model="subtaskForm.executor_id" class="w-full border rounded p-2 dark:bg-zinc-700 dark:text-white">
                    <option value="">Исполнитель</option>
                    <option v-for="u in employees" :key="u.id" :value="u.id">{{ u.name }}</option>
                </select>
                <select v-model="subtaskForm.responsible_id" class="w-full border rounded p-2 dark:bg-zinc-700 dark:text-white">
                    <option value="">Ответственный</option>
                    <option v-for="u in employees" :key="u.id" :value="u.id">{{ u.name }}</option>
                </select>
                <div class="grid grid-cols-2 gap-2">
                    <input type="date" v-model="subtaskForm.start_date" class="w-full border rounded p-2 dark:bg-zinc-700 dark:text-white" required/>
                    <input type="date" v-model="subtaskForm.due_date" class="w-full border rounded p-2 dark:bg-zinc-700 dark:text-white" required/>
                </div>
                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" @click="$emit('close', 'subtask')" class="px-4 py-2 rounded bg-zinc-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400">Отмена</button>
                    <button type="submit" :disabled="loading" class="px-4 py-2 rounded bg-emerald-600 text-white disabled:opacity-60 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400">
                        {{ loading ? 'Создание…' : 'Создать' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 4. DELETE MODAL -->
    <div v-if="modals.delete" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div v-autofocus role="dialog" aria-modal="true" aria-label="Удалить задачу" tabindex="-1" class="bg-white dark:bg-zinc-800 rounded-2xl p-6 w-full max-w-md shadow-xl" @keydown.esc="$emit('close', 'delete')">
            <h3 class="font-bold text-lg mb-2 text-rose-600">Удалить задачу?</h3>
            <p class="text-sm text-zinc-600 dark:text-zinc-300 mb-4">Это действие необратимо.</p>
            <p v-if="error" class="mb-3 rounded-lg bg-rose-50 px-3 py-2 text-sm font-medium text-rose-600 dark:bg-rose-950/40 dark:text-rose-400">{{ error }}</p>
            <div class="flex justify-end gap-2">
                <button type="button" @click="$emit('close', 'delete')" class="px-4 py-2 rounded bg-zinc-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400">Отмена</button>
                <button type="button" :disabled="loading" @click="$emit('deleteTask')" class="px-4 py-2 rounded bg-rose-600 text-white disabled:opacity-60 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400">
                    {{ loading ? 'Удаление…' : 'Удалить' }}
                </button>
            </div>
        </div>
    </div>
</template>
