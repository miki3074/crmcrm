<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'

const props = defineProps(['subtask', 'user'])
const emit = defineEmits(['refresh'])

const fileInput = ref(null)

const canUpload = computed(() => {
    // Упрощенная логика проверки, можно уточнить по вашему оригиналу
    const { subtask, user } = props
    if (!subtask || !user) return false
    return subtask.creator_id === user.id ||
        (subtask.executors||[]).some(e=>e.id === user.id) ||
        (subtask.responsibles||[]).some(e=>e.id === user.id)
})

const uploadFile = async (e) => {
    const file = e.target.files[0]
    if(!file) return
    const fd = new FormData()
    fd.append('file', file)
    try {
        await axios.post(`/api/subtasks/${props.subtask.id}/files`, fd, { headers: {'Content-Type': 'multipart/form-data'}})
        emit('refresh')
    } catch(err) { alert('Ошибка загрузки') }
}

const deleteFile = async (id) => {
    if(!confirm('Удалить?')) return
    await axios.delete(`/api/subtask-files/${id}`)
    emit('refresh')
}
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-6">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold dark:text-white">📎 Файлы</h3>
            <div v-if="canUpload">
                <input type="file" @change="uploadFile" class="hidden" ref="fileInput" />
                <button @click="$refs.fileInput.click()" class="px-3 py-1 bg-blue-600 text-white rounded text-sm">Загрузить</button>
            </div>
        </div>

        <ul v-if="subtask.files?.length" class="space-y-2">
            <li v-for="file in subtask.files" :key="file.id" class="flex justify-between items-center bg-gray-50 dark:bg-gray-700 p-2 rounded">
                <a :href="`/api/subtask-files/${file.id}/download`" class="text-blue-600 hover:underline text-sm">{{ file.filename }}</a>
                <button v-if="canUpload" @click="deleteFile(file.id)" class="text-red-500">❌</button>
            </li>
        </ul>
        <p v-else class="text-sm text-gray-500">Нет файлов</p>
    </div>
</template>
