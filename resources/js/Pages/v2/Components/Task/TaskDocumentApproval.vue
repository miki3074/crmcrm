<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'

const props = defineProps({
    task: Object,
    currentUser: Object
})

const emit = defineEmits(['refresh'])

// === СОСТОЯНИЕ ===
const uploading = ref(false)
const requiresApproval = ref(true)

// Модалка отказа
const rejectModalOpen = ref(false)
const fileToReject = ref(null)
const rejectComment = ref('')

// Состояние для комментариев
const newComment = ref('')
const commentingFileId = ref(null)
const isSendingComment = ref(false)

// Логика разворачивания комментариев
const expandedComments = ref(new Set())

// === ПРАВА ===
const isExecutor = computed(() => props.task.executors?.some(u => u.id === props.currentUser.id))
const isResponsible = computed(() => props.task.responsibles?.some(u => u.id === props.currentUser.id))
const isCreator = computed(() => props.task.creator_id === props.currentUser.id)

// 🔥 Может ли пользователь заменять файл (исполнитель ИЛИ ответственный)
const canReplaceFile = computed(() => {
    return isExecutor.value || isResponsible.value
})

// === COMPUTED: Списки файлов ===
const approvalFiles = computed(() => props.task.files?.filter(f => f.status !== 'none') || [])
const regularFiles = computed(() => props.task.files?.filter(f => f.status === 'none') || [])

// === FORMATTERS ===
const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('ru-RU', {
        day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit'
    })
}

const getFileName = (file) => {
    if (file.file_name && file.file_name.trim() !== '') {
        return file.file_name;
    }
    if (file.file_path) {
        return file.file_path.split('/').pop();
    }
    return 'Документ без названия';
}

const getFileIcon = (file) => {
    const filename = getFileName(file)
    const ext = filename.split('.').pop().toLowerCase()

    if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'].includes(ext)) return '🖼️'
    if (['pdf'].includes(ext)) return '📕'
    if (['doc', 'docx', 'txt', 'rtf', 'odt'].includes(ext)) return '📘'
    if (['xls', 'xlsx', 'csv', 'ods'].includes(ext)) return '📊'
    if (['ppt', 'pptx', 'odp'].includes(ext)) return '📙'
    if (['zip', 'rar', '7z', 'tar', 'gz'].includes(ext)) return '📦'
    if (['mp3', 'wav', 'ogg', 'flac', 'm4a', 'aac', 'opus', 'wma'].includes(ext)) return '🎵'
    if (['mp4', 'avi', 'mov', 'mkv', 'webm'].includes(ext)) return '🎬'
    return '📄'
}

const isAudioFile = (file) => {
    const filename = getFileName(file)
    const ext = filename.split('.').pop().toLowerCase()
    return ['mp3', 'wav', 'ogg', 'flac', 'm4a', 'aac', 'opus', 'wma'].includes(ext)
}

const isImageFile = (file) => {
    const filename = getFileName(file)
    const ext = filename.split('.').pop().toLowerCase()
    return ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'].includes(ext)
}

// === UPLOAD ===
const handleFileUpload = async (event) => {
    const input = event.target
    const files = Array.from(input.files || [])

    if (!files.length) return

    uploading.value = true

    const formData = new FormData()

    files.forEach(file => {
        formData.append('files[]', file)
    })

    formData.append(
        'requires_approval',
        requiresApproval.value ? '1' : '0'
    )

    try {
        await axios.post(
            `/api/tasks/${props.task.id}/files`,
            formData
        )

        input.value = ''
        requiresApproval.value = false
        emit('refresh')
    } catch (error) {
        console.error('STATUS:', error.response?.status)
        console.error('RESPONSE:', error.response?.data)
        console.error('ERRORS:', error.response?.data?.errors)

        const errors = error.response?.data?.errors

        if (errors) {
            const message = Object.entries(errors)
                .map(([field, messages]) => {
                    return `${field}: ${messages.join(', ')}`
                })
                .join('\n')

            alert(message)
        } else {
            alert(error.response?.data?.message || 'Ошибка загрузки файлов')
        }
    } finally {
        uploading.value = false
    }
}

// === ACTIONS ===
const deleteFile = async (id) => {
    if(!confirm('Удалить файл безвозвратно?')) return
    try { await axios.delete(`/api/tasks/files/${id}`); emit('refresh') } catch(e) {}
}

const approve = async (file) => {
    if(!confirm(`Утвердить документ "${getFileName(file)}"?`)) return
    try { await axios.put(`/api/files/${file.id}/approve`); emit('refresh') } catch (e) {}
}

// Отказ
const openRejectModal = (file) => {
    fileToReject.value = file
    rejectComment.value = ''
    rejectModalOpen.value = true
}

const submitReject = async () => {
    if(!rejectComment.value.trim()) return
    try {
        await axios.put(`/api/files/${fileToReject.value.id}/reject`, { comment: rejectComment.value })
        rejectModalOpen.value = false
        emit('refresh')
    } catch (e) {}
}

// 🔥 Замена файла (доступна исполнителю и ответственному)
const handleReplace = async (event, fileId) => {
    const file = event.target.files[0]
    if (!file) return

    const fileName = file.name
    const fileSize = (file.size / 1024 / 1024).toFixed(2)

    if (!confirm(
        `Заменить файл на "${fileName}" (${fileSize} MB)?\n\n` +
        `⚠️ При замене файла будут удалены все комментарии и замечания.`
    )) {
        event.target.value = null
        return
    }

    const fd = new FormData()
    fd.append('file', file)

    try {
        await axios.post(`/api/files/${fileId}/replace`, fd, {
            headers: { 'Content-Type': 'multipart/form-data' }
        })
        emit('refresh')
    } catch (e) {
        alert('Ошибка при замене')
    }
}

// Добавление комментария к файлу
const addFileComment = async (fileId) => {
    if (!newComment.value.trim()) return

    const file = approvalFiles.value.find(f => f.id === fileId)
    if (!file) return

    if (file.status !== 'rejected') {
        alert('Комментарии можно оставлять только к файлам на доработке')
        return
    }

    isSendingComment.value = true
    try {
        await axios.post(`/api/files/${fileId}/comments`, {
            comment: newComment.value,
            type: 'feedback'
        })
        newComment.value = ''
        commentingFileId.value = null
        emit('refresh')
    } catch (err) {
        alert('Ошибка добавления комментария')
    } finally {
        isSendingComment.value = false
    }
}

// Удаление комментария
const deleteComment = async (commentId) => {
    if (!confirm('Удалить комментарий?')) return
    try {
        await axios.delete(`/api/file-comments/${commentId}`)
        emit('refresh')
    } catch (err) {
        alert('Ошибка удаления комментария')
    }
}

// === UI HELPERS ===
const toggleComment = (id) => {
    if (expandedComments.value.has(id)) expandedComments.value.delete(id)
    else expandedComments.value.add(id)
}

const getStatusBadge = (status) => {
    switch (status) {
        case 'approved': return { text: 'Согласовано', classes: 'bg-emerald-100 text-emerald-700 border-emerald-200', icon: '✅' }
        case 'rejected': return { text: 'На доработке', classes: 'bg-rose-100 text-rose-700 border-rose-200', icon: '🛑' }
        default: return { text: 'Ждет проверки', classes: 'bg-amber-50 text-amber-600 border-amber-200', icon: '⏳' }
    }
}

// Проверка, может ли пользователь комментировать
const canComment = (file) => {
    const isParticipant = isExecutor.value || isResponsible.value || isCreator.value
    return isParticipant && file.status === 'rejected'
}
</script>

<template>
    <div class="space-y-8">

        <!-- 1. ЗОНА ЗАГРУЗКИ -->
        <div class="relative group rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/50 hover:bg-blue-50 dark:hover:bg-gray-800 transition-colors p-6 text-center">
            <h1 class="font-semibold text-gray-700 dark:text-gray-200">Файлы для которых требуется согласование</h1>
            <input
                type="file" multiple class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.rar,.mp3,.wav,.ogg,.flac,.m4a,.aac,.jpg,.jpeg,.png,.gif,.webp"
                @change="handleFileUpload" :disabled="uploading"
            >

            <div v-if="uploading" class="animate-pulse flex flex-col items-center">
                <svg class="w-8 h-8 text-blue-500 mb-2 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                <span class="text-sm text-gray-500">Загрузка файлов...</span>
            </div>

            <div v-else class="flex flex-col items-center pointer-events-none">
                <div class="p-3 bg-white dark:bg-gray-700 rounded-full shadow-sm mb-3">
                    <svg class="w-6 h-6 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                </div>
                <h4 class="font-semibold text-gray-700 dark:text-gray-200">Нажмите или перетащите файлы</h4>
                <p class="text-xs text-gray-400 mt-1">PDF, Office, аудио, изображения (до 100MB)</p>
            </div>

            <!-- Переключатель -->
            <div class="hidden absolute bottom-4 left-1/2 -translate-x-1/2 z-20 flex items-center gap-2 bg-white dark:bg-gray-700 px-3 py-1.5 rounded-full shadow-md border border-gray-200 dark:border-gray-600">
                <input type="checkbox" id="chkApprove" v-model="requiresApproval" class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 cursor-pointer">
                <label for="chkApprove" class="text-xs font-medium text-gray-700 dark:text-gray-200 select-none cursor-pointer">
                    Требуется согласование
                </label>
            </div>
        </div>

        <!-- 2. ДОКУМЕНТЫ НА СОГЛАСОВАНИИ -->
        <div v-if="approvalFiles.length > 0">
            <h3 class="flex items-center gap-2 font-bold text-gray-800 dark:text-gray-200 mb-4">
                <span>📋</span> Документооборот123
                <span class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs px-2 py-0.5 rounded-full">{{ approvalFiles.length }}</span>
            </h3>

            <div class="grid gap-4">
                <div v-for="file in approvalFiles" :key="file.id"
                     class="group relative bg-white dark:bg-gray-800 border rounded-xl p-4 shadow-sm hover:shadow-md transition-shadow"
                     :class="getStatusBadge(file.status).classes">

                    <div class="flex flex-col sm:flex-row gap-4 justify-between">

                        <!-- Инфо о файле -->
                        <div class="flex gap-3 flex-1">
                            <div class="text-3xl select-none">{{ getFileIcon(file) }}</div>
                            <div class="min-w-0 flex-1">
                                <!-- Ссылка или плеер для аудио -->
                                <div v-if="isAudioFile(file)">
                                    <div class="font-bold truncate block text-gray-900 dark:text-white mb-2" :title="getFileName(file)">
                                        {{ getFileName(file) }}
                                    </div>
                                    <audio
                                        :src="`/api/tasks/files/${file.id}`"
                                        controls
                                        controlslist="nodownload"
                                        class="w-full max-w-md h-10"
                                        preload="metadata">
                                        Ваш браузер не поддерживает аудиоплеер
                                    </audio>
                                </div>

                                <!-- Для изображений показываем превью -->
                                <div v-else-if="isImageFile(file)" class="mb-2">
                                    <img
                                        :src="`/api/tasks/files/${file.id}`"
                                        :alt="getFileName(file)"
                                        class="max-h-32 rounded-lg object-cover cursor-pointer hover:opacity-90 transition"
                                        @click="window.open(`/api/tasks/files/${file.id}`, '_blank')"
                                    />
                                </div>

                                <!-- Для остальных файлов - ссылка -->
                                <a v-else
                                   :href="`/api/tasks/files/${file.id}`"
                                   target="_blank"
                                   class="font-bold hover:underline truncate block text-gray-900 dark:text-white"
                                   :title="getFileName(file)"
                                >
                                    {{ getFileName(file) }}
                                </a>

                                <div class="flex flex-wrap items-center gap-2 text-xs opacity-75 mt-1">
                                    <span>👤 {{ file.user?.name }}</span>
                                    <span>•</span>
                                    <span>{{ formatDate(file.created_at) }}</span>
                                    <span v-if="file.size" class="text-gray-400">• {{ (file.size / 1024 / 1024).toFixed(2) }} MB</span>
                                </div>

                                <!-- Статус бейдж (мобильный) -->
                                <div class="sm:hidden mt-2">
                                     <span class="inline-flex items-center px-2 py-1 rounded text-xs font-bold bg-white/50 border border-black/5">
                                        <span class="mr-1">{{ getStatusBadge(file.status).icon }}</span>
                                        {{ getStatusBadge(file.status).text }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Действия и Статус (Десктоп) -->
                        <div class="flex flex-col items-end gap-2">
                            <span class="hidden sm:inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-white/50 border border-black/5 shadow-sm">
                                <span class="mr-1.5">{{ getStatusBadge(file.status).icon }}</span>
                                {{ getStatusBadge(file.status).text }}
                            </span>

                            <div class="flex items-center gap-2 mt-auto">
                                <!-- Кнопки утверждения/отказа (для исполнителя, ответственного и создателя) -->
                                <div v-if="(isExecutor || isResponsible || isCreator) && file.status === 'pending'" class="flex gap-2">
                                    <button @click="approve(file)" class="btn-action bg-emerald-600 hover:bg-emerald-700 text-white">
                                        ✔ Принять
                                    </button>
                                    <button @click="openRejectModal(file)" class="btn-action bg-rose-500 hover:bg-rose-600 text-white">
                                        ✖ Вернуть
                                    </button>
                                </div>

                                <!-- 🔥 Замена файла (для исполнителя ИЛИ ответственного) -->
                                <div v-if="canReplaceFile && file.status === 'rejected'">
                                    <input
                                        type="file"
                                        :id="'replace-'+file.id"
                                        class="hidden"
                                        @change="(e) => handleReplace(e, file.id)"
                                        accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.rar,.mp3,.wav,.ogg,.flac,.m4a,.aac,.jpg,.jpeg,.png,.gif,.webp"
                                    >
                                    <label
                                        :for="'replace-'+file.id"
                                        class="btn-action bg-blue-600 hover:bg-blue-700 text-white cursor-pointer flex items-center gap-2"
                                        title="Заменить файл"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        🔄 Заменить
                                    </label>

                                </div>

                                <!-- Удалить (для исполнителя, ответственного и создателя) -->
                                <button
                                    v-if="(isExecutor || isResponsible || isCreator) && file.status !== 'approved'"
                                    @click="deleteFile(file.id)"
                                    class="p-1.5 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition"
                                    title="Удалить"
                                >
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- КОММЕНТАРИИ К ФАЙЛУ -->
                    <div class="mt-3">
                        <!-- Существующие комментарии -->
                        <div v-if="file.comments && file.comments.length > 0" class="space-y-2">
                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                💬 Комментарии ({{ file.comments.length }}):
                            </div>
                            <div v-for="comment in file.comments" :key="comment.id"
                                 class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 text-sm">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="font-medium text-gray-700 dark:text-gray-300">
                                                {{ comment.user?.name || 'Неизвестный' }}
                                            </span>
                                            <span class="text-xs text-gray-400">
                                                {{ formatDate(comment.created_at) }}
                                            </span>
                                            <span v-if="comment.type === 'rejection'"
                                                  class="text-xs px-2 py-0.5 bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-300 rounded-full">
                                                🛑 Отказ
                                            </span>
                                            <span v-else-if="comment.type === 'feedback'"
                                                  class="text-xs px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-full">
                                                💡 Замечание
                                            </span>
                                        </div>
                                        <p class="mt-1 text-gray-600 dark:text-gray-400 whitespace-pre-wrap">{{ comment.comment }}</p>
                                    </div>
                                    <button
                                        v-if="comment.user_id === currentUser.id"
                                        @click="deleteComment(comment.id)"
                                        class="text-gray-400 hover:text-red-500 transition text-xs ml-2 p-1 hover:bg-red-50 dark:hover:bg-red-900/20 rounded"
                                        title="Удалить комментарий"
                                    >
                                        ✕
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Форма добавления комментария -->
                        <div v-if="canComment(file)" class="mt-2">
                            <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-3">
                                <div class="text-xs text-amber-700 dark:text-amber-300 mb-2 flex items-center gap-1">
                                    <span>✏️</span>
                                    <span>Файл на доработке. Оставьте замечания для исполнителя:</span>
                                </div>
                                <div class="flex gap-2">
                                    <input
                                        type="text"
                                        v-model="newComment"
                                        placeholder="Напишите замечание..."
                                        class="flex-1 px-3 py-2 text-sm border border-amber-300 dark:border-amber-700 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                                        @keydown.enter="addFileComment(file.id)"
                                    />
                                    <button
                                        @click="addFileComment(file.id)"
                                        :disabled="isSendingComment"
                                        class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-sm font-medium transition disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        {{ isSendingComment ? '...' : 'Отправить' }}
                                    </button>
                                </div>
                                <div class="text-xs text-amber-600 dark:text-amber-400 mt-1">
                                    <kbd class="px-1 py-0.5 bg-amber-100 dark:bg-amber-800 rounded text-xs">Enter</kbd> - отправить
                                </div>
                            </div>
                        </div>

                        <div v-else-if="file.status !== 'rejected' && (isExecutor || isResponsible || isCreator)"
                             class="text-xs text-gray-400 italic mt-1">
                            💡 Комментарии можно оставлять только к файлам на доработке
                        </div>

                        <div v-else-if="!canComment(file) && file.status === 'rejected'"
                             class="text-xs text-gray-400 italic mt-1">
                            💡 Только участники задачи могут оставлять комментарии
                        </div>
                    </div>

                    <!-- Старый rejection_reason (для обратной совместимости) -->
                    <div v-if="file.status === 'rejected' && file.rejection_reason && (!file.comments || file.comments.length === 0)" class="mt-3 relative">
                        <div class="absolute -top-1.5 left-6 w-3 h-3 bg-rose-50 border-t border-l border-rose-200 rotate-45"></div>
                        <div class="bg-rose-50/80 border border-rose-200 text-rose-800 text-sm p-3 rounded-lg">
                            <span class="font-bold text-xs uppercase opacity-70 mr-2">Причина возврата:</span>
                            <span class="whitespace-pre-wrap">
                                {{ expandedComments.has(file.id) ? file.rejection_reason : file.rejection_reason.slice(0, 80) }}
                            </span>
                            <span v-if="!expandedComments.has(file.id) && file.rejection_reason.length > 80">...</span>

                            <button
                                v-if="file.rejection_reason.length > 80"
                                @click="toggleComment(file.id)"
                                class="ml-2 text-xs font-bold text-rose-600 hover:underline"
                            >
                                {{ expandedComments.has(file.id) ? 'Свернуть' : 'Читать далее' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- МОДАЛКА ОТКАЗА -->
        <div v-if="rejectModalOpen" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm flex items-center justify-center z-50 p-4 transition-opacity">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl w-full max-w-md shadow-2xl scale-100 transform transition-transform">
                <h3 class="font-bold text-lg mb-1 dark:text-white">Вернуть на доработку</h3>
                <p class="text-sm text-gray-500 mb-4">Укажите, что именно нужно исправить.</p>

                <textarea
                    v-model="rejectComment"
                    class="w-full border border-gray-300 dark:border-gray-600 rounded-xl p-3 h-32 text-sm focus:ring-2 focus:ring-rose-500 focus:border-rose-500 dark:bg-gray-700 dark:text-white"
                    placeholder="Например: Неверная дата в шапке документа..."
                    autofocus
                ></textarea>

                <div class="flex justify-end gap-3 mt-4">
                    <button @click="rejectModalOpen = false" class="px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 rounded-lg transition dark:text-gray-300 dark:hover:bg-gray-700">Отмена</button>
                    <button @click="submitReject" class="px-4 py-2 text-sm font-bold bg-rose-600 text-white rounded-lg shadow hover:bg-rose-700 transition">Вернуть документ</button>
                </div>
            </div>
        </div>

    </div>
</template>

<style scoped>
.btn-action {
    @apply px-3 py-1.5 rounded-lg text-xs font-bold shadow-sm transition-all transform hover:-translate-y-0.5 active:translate-y-0 flex items-center gap-1.5;
}
</style>
