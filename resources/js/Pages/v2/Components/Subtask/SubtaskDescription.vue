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
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-6 relative group">
        <div class="flex justify-between items-start mb-2">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Описание</h3>
            <button v-if="user.id === subtask.creator_id" @click="openModal" class="text-blue-500 text-sm hover:underline">✏ Редактировать</button>
        </div>

        <div v-if="subtask.description" class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg whitespace-pre-line break-words dark:text-white">
            {{ subtask.description }}
        </div>
        <p v-else class="text-gray-400 text-sm italic">Нет описания</p>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-xl w-full max-w-lg">
            <h3 class="text-lg font-semibold mb-3 dark:text-white">Описание подзадачи</h3>
            <textarea v-model="text" class="w-full border rounded-lg px-3 py-2 h-40 dark:bg-gray-700 dark:text-white"></textarea>
            <div class="mt-4 flex justify-end gap-2">
                <button @click="showModal = false" class="px-4 py-2 border rounded-lg text-gray-600">Закрыть</button>
                <button @click="save" class="px-4 py-2 bg-indigo-600 text-white rounded-lg">Сохранить</button>
            </div>
        </div>
    </div>
</template>
