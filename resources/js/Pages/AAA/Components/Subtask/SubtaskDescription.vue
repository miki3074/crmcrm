<script setup>
import { ref } from 'vue'
import axios from 'axios'

const props = defineProps(['subtask', 'user'])
const emit = defineEmits(['refresh'])

const showModal = ref(false)
const text = ref('')

const openModal = () => {
    text.value = props.subtask.description || ''
    showModal.value = true
}

const save = async () => {
    await axios.patch(`/api/subtasks/${props.subtask.id}/description`, { description: text.value })
    emit('refresh')
    showModal.value = false
}
</script>

<template>
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 relative group">
        <div class="flex justify-between items-start mb-2">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Описание</h3>
            <button v-if="user.id === subtask.creator_id" @click="openModal" class="text-blue-500 text-sm hover:underline">✏ Редактировать</button>
        </div>

        <div v-if="subtask.description" class="rounded-xl border border-slate-100 bg-slate-50 p-3 dark:bg-slate-800 rounded-lg whitespace-pre-line break-words dark:text-white">
            {{ subtask.description }}
        </div>
        <p v-else class="text-gray-400 text-sm italic">Нет описания</p>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm flex items-center justify-center z-50">
        <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 shadow-2xl dark:border-slate-700 w-full max-w-lg">
            <h3 class="text-base font-bold mb-3 dark:text-white">Описание подзадачи</h3>
            <textarea v-model="text" class="w-full border rounded-lg px-3 py-2 h-32 dark:bg-slate-800 dark:text-white"></textarea>
            <div class="mt-4 flex justify-end gap-2">
                <button @click="showModal = false" class="px-4 py-2 border rounded-lg text-gray-600">Закрыть</button>
                <button @click="save" class="px-4 py-2 bg-indigo-600 text-white rounded-lg">Сохранить</button>
            </div>
        </div>
    </div>
</template>
