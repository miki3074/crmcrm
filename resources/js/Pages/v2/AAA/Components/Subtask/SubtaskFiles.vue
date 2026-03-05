<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'

const props = defineProps(['subtask', 'user'])
const emit = defineEmits(['refresh'])

// Ссылки на DOM элементы
const fileInput = ref(null)
const replaceInput = ref(null)

// Состояния
const revisionComment = ref('')
const activeFileId = ref(null)
const fileToReplaceId = ref(null)
const expandedComments = ref(new Set())
const uploading = ref(false)
const replacing = ref(false)
const showDeleteConfirm = ref(null)
const dragActive = ref(false)

// --- ПРАВА ДОСТУПА ---
const canUpload = computed(() => {
    const { subtask, user } = props
    if (!subtask || !user) return false
    return subtask.creator_id === user.id ||
        subtask.executors?.some(e => e.id === user.id) ||
        subtask.responsibles?.some(e => e.id === user.id)
})

const isResponsible = computed(() => {
    const { subtask, user } = props
    return subtask?.responsibles?.some(r => r.id === user.id)
})

// --- ФОРМАТИРОВАНИЕ ---
const formatFileSize = (bytes) => {
    if (!bytes) return '0 B'
    const k = 1024
    const sizes = ['B', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return `${parseFloat((bytes / Math.pow(k, i)).toFixed(1))} ${sizes[i]}`
}

const formatDate = (date) => {
    return new Date(date).toLocaleString('ru-RU', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const getFileIcon = (filename) => {
    const ext = filename.split('.').pop().toLowerCase()
    const icons = {
        pdf: '📄',
        doc: '📝', docx: '📝',
        xls: '📊', xlsx: '📊',
        ppt: '📽️', pptx: '📽️',
        jpg: '🖼️', jpeg: '🖼️', png: '🖼️', gif: '🖼️',
        zip: '🗜️', rar: '🗜️', '7z': '🗜️',
        mp3: '🎵', wav: '🎵',
        mp4: '🎬', avi: '🎬',
        txt: '📃',
        js: '📦', vue: '📦', php: '📦', html: '📦', css: '📦'
    }
    return icons[ext] || '📎'
}

// --- ЗАГРУЗКА ---
const uploadFile = async (e) => {
    const file = e.target.files[0]
    if (!file) return

    uploading.value = true
    const fd = new FormData()
    fd.append('file', file)

    try {
        await axios.post(`/api/subtasks/${props.subtask.id}/files`, fd, {
            headers: { 'Content-Type': 'multipart/form-data' }
        })
        emit('refresh')
    } catch (err) {
        alert(err.response?.data?.message || 'Ошибка загрузки')
    } finally {
        uploading.value = false
        e.target.value = ''
    }
}

// --- DRAG & DROP ---
const onDragEnter = (e) => {
    e.preventDefault()
    e.stopPropagation()
    dragActive.value = true
}

const onDragLeave = (e) => {
    e.preventDefault()
    e.stopPropagation()
    dragActive.value = false
}

const onDragOver = (e) => {
    e.preventDefault()
    e.stopPropagation()
}

const onDrop = async (e) => {
    e.preventDefault()
    e.stopPropagation()
    dragActive.value = false

    const file = e.dataTransfer.files[0]
    if (!file || !canUpload.value) return

    const fd = new FormData()
    fd.append('file', file)

    try {
        await axios.post(`/api/subtasks/${props.subtask.id}/files`, fd, {
            headers: { 'Content-Type': 'multipart/form-data' }
        })
        emit('refresh')
    } catch (err) {
        alert(err.response?.data?.message || 'Ошибка загрузки')
    }
}

// --- УДАЛЕНИЕ ---
const deleteFile = async (id) => {
    showDeleteConfirm.value = id
}

const confirmDelete = async (id) => {
    try {
        await axios.delete(`/api/subtask-files/${id}`)
        emit('refresh')
        showDeleteConfirm.value = null
    } catch (e) {
        alert('Ошибка удаления')
    }
}

// --- ЗАМЕНА ---
const triggerReplace = (id) => {
    fileToReplaceId.value = id
    replacing.value = true
    replaceInput.value.click()
}

const handleReplaceFile = async (e) => {
    const file = e.target.files[0]
    if (!file || !fileToReplaceId.value) return

    const fd = new FormData()
    fd.append('file', file)

    try {
        await axios.post(`/api/subtask-files/${fileToReplaceId.value}/replace`, fd, {
            headers: { 'Content-Type': 'multipart/form-data' }
        })
        emit('refresh')
    } catch (err) {
        alert(err.response?.data?.message || 'Ошибка обновления файла')
    } finally {
        e.target.value = ''
        fileToReplaceId.value = null
        replacing.value = false
    }
}

// --- ДОРАБОТКА ---
const openRevisionInput = (fileId) => {
    if (activeFileId.value === fileId) {
        activeFileId.value = null
    } else {
        activeFileId.value = fileId
        const file = props.subtask.files.find(f => f.id === fileId)
        revisionComment.value = file.revision_comment || ''
    }
}

const sendRevision = async (fileId) => {
    if (!revisionComment.value.trim()) {
        alert('Пожалуйста, укажите причину доработки')
        return
    }

    try {
        await axios.post(`/api/subtask-files/${fileId}/revision`, {
            comment: revisionComment.value
        })
        activeFileId.value = null
        revisionComment.value = ''
        emit('refresh')
    } catch (e) {
        alert(e.response?.data?.message || 'Ошибка отправки')
    }
}

// --- СВОРАЧИВАНИЕ ---
const toggleComment = (id) => {
    if (expandedComments.value.has(id)) {
        expandedComments.value.delete(id)
    } else {
        expandedComments.value.add(id)
    }
}
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-md transition-all">
        <!-- Заголовок -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-transparent dark:from-gray-700/50">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="p-1.5 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 107.071 7.071l5.414-5.414" />
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Файлы</h3>
                    <span v-if="subtask.files?.length" class="text-xs text-gray-500">
                        {{ subtask.files.length }} {{ subtask.files.length === 1 ? 'файл' :
                        subtask.files.length >= 2 && subtask.files.length <= 4 ? 'файла' : 'файлов' }}
                    </span>
                </div>

                <!-- Кнопка загрузки -->
                <div v-if="canUpload">
                    <input type="file" ref="fileInput" class="hidden" @change="uploadFile" />
                    <button
                        @click="$refs.fileInput.click()"
                        :disabled="uploading"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400 text-white text-sm font-medium rounded-xl shadow-sm transition-all transform hover:scale-105 active:scale-95"
                    >
                        <svg v-if="uploading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        {{ uploading ? 'Загрузка...' : 'Загрузить' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Скрытый инпут для замены -->
        <input type="file" ref="replaceInput" class="hidden" @change="handleReplaceFile" />

        <!-- Drag & Drop зона -->
        <div
            v-if="canUpload"
            class="relative"
            @dragenter="onDragEnter"
            @dragleave="onDragLeave"
            @dragover="onDragOver"
            @drop="onDrop"
        >
            <!-- Оверлей при перетаскивании -->
            <div
                v-if="dragActive"
                class="absolute inset-0 bg-indigo-50 dark:bg-indigo-900/20 border-2 border-dashed border-indigo-400 dark:border-indigo-500 rounded-lg z-10 flex items-center justify-center"
            >
                <div class="text-indigo-600 dark:text-indigo-400 font-medium">
                    Перетащите файл сюда
                </div>
            </div>

            <!-- Список файлов -->
            <div v-if="subtask.files?.length" class="p-6 space-y-3">
                <div
                    v-for="file in subtask.files"
                    :key="file.id"
                    class="relative bg-gray-50 dark:bg-gray-700/50 rounded-xl border transition-all hover:shadow-md"
                    :class="[
                        file.status === 'revision'
                            ? 'border-red-300 bg-red-50/50 dark:border-red-700 dark:bg-red-900/10'
                            : 'border-gray-200 dark:border-gray-600 hover:border-indigo-200 dark:hover:border-indigo-700'
                    ]"
                >
                    <!-- Основная информация -->
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-4">
                            <!-- Иконка и информация -->
                            <div class="flex items-start gap-3 flex-1 min-w-0">
                                <div class="flex-shrink-0 w-10 h-10 bg-white dark:bg-gray-800 rounded-xl flex items-center justify-center text-xl shadow-sm">
                                    {{ getFileIcon(file.filename) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <a
                                            :href="`/api/subtask-files/${file.id}/download`"
                                            class="text-sm font-medium text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 truncate max-w-xs"
                                            :title="file.filename"
                                        >
                                            {{ file.filename }}
                                        </a>
                                        <span class="text-xs text-gray-500">
                                            {{ formatFileSize(file.size) }}
                                        </span>
                                        <span v-if="file.created_at !== file.updated_at"
                                              class="inline-flex items-center gap-1 text-xs text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20 px-2 py-0.5 rounded-full">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                            обновлен
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-3 mt-1 text-xs text-gray-500">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            {{ formatDate(file.created_at) }}
                                        </span>
                                        <span v-if="file.uploader" class="flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            {{ file.uploader.name }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Панель действий -->
                            <div class="flex items-center gap-1 shrink-0">
                                <!-- Кнопка обновить -->
                                <button
                                    v-if="canUpload"
                                    @click="triggerReplace(file.id)"
                                    :disabled="replacing && fileToReplaceId === file.id"
                                    class="p-2 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition"
                                    title="Обновить файл"
                                >
                                    <svg v-if="replacing && fileToReplaceId === file.id" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                </button>

                                <!-- Кнопка на доработку -->
                                <button
                                    v-if="isResponsible"
                                    @click="openRevisionInput(file.id)"
                                    class="p-2 text-gray-500 rounded-lg transition"
                                    :class="file.status === 'revision'
                                        ? 'text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20'
                                        : 'text-amber-600 hover:bg-amber-50 dark:text-amber-400 dark:hover:bg-amber-900/20'"
                                    :title="file.status === 'revision' ? 'Изменить замечание' : 'Отправить на доработку'"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </button>

                                <!-- Кнопка удалить -->
                                <button
                                    v-if="canUpload"
                                    @click="deleteFile(file.id)"
                                    class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition"
                                    title="Удалить"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Подтверждение удаления -->
                        <div v-if="showDeleteConfirm === file.id" class="mt-3 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                            <p class="text-sm text-red-600 dark:text-red-400 mb-2">Удалить файл "{{ file.filename }}"?</p>
                            <div class="flex gap-2">
                                <button
                                    @click="confirmDelete(file.id)"
                                    class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-medium rounded-lg"
                                >
                                    Удалить
                                </button>
                                <button
                                    @click="showDeleteConfirm = null"
                                    class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-xs font-medium rounded-lg"
                                >
                                    Отмена
                                </button>
                            </div>
                        </div>

                        <!-- Комментарий доработки -->
                        <div v-if="file.status === 'revision' && file.revision_comment" class="mt-3 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                            <div class="flex items-center gap-2 mb-1">
                                <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <span class="text-xs font-semibold text-red-700 dark:text-red-300">Замечание:</span>
                            </div>
                            <p class="text-sm text-red-700 dark:text-red-300 whitespace-pre-wrap">
                                {{ expandedComments.has(file.id) || file.revision_comment.length <= 200
                                ? file.revision_comment
                                : file.revision_comment.slice(0, 200) + '...'
                                }}
                            </p>
                            <button
                                v-if="file.revision_comment.length > 200"
                                @click="toggleComment(file.id)"
                                class="mt-2 text-xs text-red-600 hover:text-red-700 dark:text-red-400 font-medium flex items-center gap-1"
                            >
                                {{ expandedComments.has(file.id) ? 'Свернуть' : 'Читать полностью' }}
                                <svg class="w-3 h-3" :class="{ 'rotate-180': expandedComments.has(file.id) }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                        </div>

                        <!-- Форма ввода замечания -->
                        <div v-if="activeFileId === file.id" class="mt-3 animate-slideDown">
                            <textarea
                                v-model="revisionComment"
                                class="w-full text-sm p-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-400 focus:border-amber-400 outline-none dark:bg-gray-800 dark:text-white transition"
                                placeholder="Опишите, что нужно исправить в этом файле..."
                                rows="3"
                            ></textarea>
                            <div class="flex justify-end gap-2 mt-2">
                                <button
                                    @click="activeFileId = null"
                                    class="px-3 py-1.5 text-sm text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200"
                                >
                                    Отмена
                                </button>
                                <button
                                    @click="sendRevision(file.id)"
                                    class="px-4 py-1.5 bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium rounded-lg transition shadow-sm"
                                >
                                    Отправить на доработку
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Пустое состояние -->
            <div v-else class="p-12 text-center">
                <div class="w-20 h-20 bg-gray-100 dark:bg-gray-700 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-10 h-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 107.071 7.071l5.414-5.414" />
                    </svg>
                </div>
                <p class="text-gray-500 dark:text-gray-400 text-sm mb-4">Файлы не загружены</p>
                <button
                    v-if="canUpload"
                    @click="$refs.fileInput.click()"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-900/20 dark:hover:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 text-sm font-medium rounded-xl transition"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Загрузить первый файл
                </button>
            </div>
        </div>

        <!-- Без прав на загрузку -->
        <div v-else-if="!subtask.files?.length" class="p-12 text-center">
            <p class="text-gray-500 dark:text-gray-400 text-sm">Файлов нет</p>
        </div>
    </div>
</template>

<style scoped>
/* Анимации */
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

/* Drag & Drop зона */
.border-dashed {
    transition: all 0.2s ease;
}

/* Плавные переходы */
.transition-all {
    transition: all 0.2s ease-in-out;
}

/* Анимация для иконок */
.hover\:scale-105:hover {
    transform: scale(1.05);
}

/* Кастомный скроллбар для длинных комментариев */
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
