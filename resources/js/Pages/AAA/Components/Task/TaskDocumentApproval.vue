<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'

const props = defineProps({
    task: Object,
    currentUser: Object,
})

const emit = defineEmits(['refresh'])

// === СОСТОЯНИЕ ===
const uploading = ref(false)
const requiresApproval = ref(true)

// Модальное окно отказа
const rejectModalOpen = ref(false)
const fileToReject = ref(null)
const rejectComment = ref('')

// Комментарии
const newComment = ref('')
const commentingFileId = ref(null)
const isSendingComment = ref(false)

// Разворачивание старых комментариев
const expandedComments = ref(new Set())

// === ПРАВА ===
const isExecutor = computed(() =>
    props.task.executors?.some(user => user.id === props.currentUser.id)
)

const isResponsible = computed(() =>
    props.task.responsibles?.some(user => user.id === props.currentUser.id)
)

const isCreator = computed(() =>
    props.task.creator_id === props.currentUser.id
)

const canReplaceFile = computed(() =>
    isExecutor.value || isResponsible.value
)

// === СПИСКИ ФАЙЛОВ ===
const activeFiles = computed(() =>
    props.task.files?.filter(file =>
        ['pending', 'rejected'].includes(file.status)
    ) || []
)

const pendingFiles = computed(() =>
    props.task.files?.filter(file => file.status === 'pending') || []
)

const rejectedFiles = computed(() =>
    props.task.files?.filter(file => file.status === 'rejected') || []
)

const approvedFiles = computed(() =>
    props.task.files?.filter(file => file.status === 'approved') || []
)

const regularFiles = computed(() =>
    props.task.files?.filter(file => file.status === 'none') || []
)

// === FORMATTERS ===
const formatDate = (dateString) => {
    if (!dateString) return 'Дата не указана'

    return new Date(dateString).toLocaleDateString('ru-RU', {
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    })
}

const getFileName = (file) => {
    if (file.file_name?.trim()) {
        return file.file_name
    }

    if (file.file_path) {
        return file.file_path.split('/').pop()
    }

    return 'Документ без названия'
}

const getFileIcon = (file) => {
    const filename = getFileName(file)
    const ext = filename.split('.').pop()?.toLowerCase()

    if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'].includes(ext)) return '🖼️'
    if (ext === 'pdf') return '📕'
    if (['doc', 'docx', 'txt', 'rtf', 'odt'].includes(ext)) return '📘'
    if (['xls', 'xlsx', 'csv', 'ods'].includes(ext)) return '📊'
    if (['ppt', 'pptx', 'odp'].includes(ext)) return '📙'
    if (['zip', 'rar', '7z', 'tar', 'gz'].includes(ext)) return '📦'
    if (['mp3', 'wav', 'ogg', 'flac', 'm4a', 'aac', 'opus', 'wma'].includes(ext)) return '🎵'
    if (['mp4', 'avi', 'mov', 'mkv', 'webm'].includes(ext)) return '🎬'

    return '📄'
}

const isAudioFile = (file) => {
    const ext = getFileName(file).split('.').pop()?.toLowerCase()

    return ['mp3', 'wav', 'ogg', 'flac', 'm4a', 'aac', 'opus', 'wma'].includes(ext)
}

const isImageFile = (file) => {
    const ext = getFileName(file).split('.').pop()?.toLowerCase()

    return ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'].includes(ext)
}

const getStatusBadge = (status) => {
    switch (status) {
        case 'approved':
            return {
                text: 'Согласовано',
                icon: '✅',
                cardClasses:
                    'border-emerald-200 bg-emerald-50/60 dark:border-emerald-900 dark:bg-emerald-900/10',
                badgeClasses:
                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
            }

        case 'rejected':
            return {
                text: 'На доработке',
                icon: '🛑',
                cardClasses:
                    'border-rose-200 bg-rose-50/60 dark:border-rose-900 dark:bg-rose-900/10',
                badgeClasses:
                    'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
            }

        default:
            return {
                text: 'Ждёт проверки',
                icon: '⏳',
                cardClasses:
                    'border-amber-200 bg-amber-50/60 dark:border-amber-900 dark:bg-amber-900/10',
                badgeClasses:
                    'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
            }
    }
}

// === ЗАГРУЗКА ===
const handleFileUpload = async (event) => {
    const input = event.target
    const files = Array.from(input.files || [])

    if (!files.length) return

    uploading.value = true

    const formData = new FormData()

    files.forEach(file => {
        formData.append('files[]', file, file.name)
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
        requiresApproval.value = true
        emit('refresh')
    } catch (error) {
        console.error('Ошибка загрузки:', error)
        console.error('Ответ Laravel:', error.response?.data)

        const errors = error.response?.data?.errors

        if (errors) {
            const messages = []

            Object.entries(errors).forEach(([field, fieldMessages]) => {
                const match = field.match(/^files\.(\d+)$/)
                const index = match ? Number(match[1]) : null

                const filename =
                    index !== null && files[index]
                        ? files[index].name
                        : field

                fieldMessages.forEach(message => {
                    messages.push(`${filename}: ${message}`)
                })
            })

            alert(messages.join('\n'))
        } else {
            alert(
                error.response?.data?.message ||
                'Ошибка загрузки файлов'
            )
        }
    } finally {
        uploading.value = false
    }
}

// === ДЕЙСТВИЯ С ФАЙЛАМИ ===
const deleteFile = async (id) => {
    if (!confirm('Удалить файл безвозвратно?')) return

    try {
        await axios.delete(`/api/tasks/files/${id}`)
        emit('refresh')
    } catch (error) {
        console.error(error)
        alert('Не удалось удалить файл')
    }
}

const approve = async (file) => {
    if (!confirm(`Утвердить документ "${getFileName(file)}"?`)) return

    try {
        await axios.put(`/api/files/${file.id}/approve`)
        emit('refresh')
    } catch (error) {
        console.error(error)
        alert('Не удалось согласовать файл')
    }
}

const openRejectModal = (file) => {
    fileToReject.value = file
    rejectComment.value = ''
    rejectModalOpen.value = true
}

const closeRejectModal = () => {
    rejectModalOpen.value = false
    fileToReject.value = null
    rejectComment.value = ''
}

const submitReject = async () => {
    if (!rejectComment.value.trim() || !fileToReject.value) return

    try {
        await axios.put(
            `/api/files/${fileToReject.value.id}/reject`,
            {
                comment: rejectComment.value.trim(),
            }
        )

        closeRejectModal()
        emit('refresh')
    } catch (error) {
        console.error(error)
        alert('Не удалось вернуть файл на доработку')
    }
}

const handleReplace = async (event, fileId) => {
    const input = event.target
    const file = input.files?.[0]

    if (!file) return

    const fileSize = (file.size / 1024 / 1024).toFixed(2)

    if (!confirm(
        `Заменить файл на "${file.name}" (${fileSize} МБ)?\n\n` +
        'При замене файла будут удалены все комментарии и замечания.'
    )) {
        input.value = ''
        return
    }

    const formData = new FormData()
    formData.append('file', file)

    try {
        await axios.post(
            `/api/files/${fileId}/replace`,
            formData
        )

        input.value = ''
        emit('refresh')
    } catch (error) {
        console.error(error)
        alert(
            error.response?.data?.message ||
            'Ошибка при замене файла'
        )
    }
}

// === КОММЕНТАРИИ ===
const canComment = (file) => {
    const isParticipant =
        isExecutor.value ||
        isResponsible.value ||
        isCreator.value

    return isParticipant && file.status === 'rejected'
}

const addFileComment = async (fileId) => {
    const comment = newComment.value.trim()

    if (!comment) return

    const file = activeFiles.value.find(item => item.id === fileId)

    if (!file || file.status !== 'rejected') {
        alert('Комментарии можно оставлять только к файлам на доработке')
        return
    }

    commentingFileId.value = fileId
    isSendingComment.value = true

    try {
        await axios.post(`/api/files/${fileId}/comments`, {
            comment,
            type: 'feedback',
        })

        newComment.value = ''
        commentingFileId.value = null
        emit('refresh')
    } catch (error) {
        console.error(error)
        alert('Ошибка добавления комментария')
    } finally {
        isSendingComment.value = false
    }
}

const deleteComment = async (commentId) => {
    if (!confirm('Удалить комментарий?')) return

    try {
        await axios.delete(`/api/file-comments/${commentId}`)
        emit('refresh')
    } catch (error) {
        console.error(error)
        alert('Ошибка удаления комментария')
    }
}

const toggleComment = (id) => {
    const updated = new Set(expandedComments.value)

    if (updated.has(id)) {
        updated.delete(id)
    } else {
        updated.add(id)
    }

    expandedComments.value = updated
}

const openFile = (file) => {
    window.open(`/api/tasks/files/${file.id}`, '_blank')
}
</script>

<template>
    <div class="space-y-5">
        <!-- КОМПАКТНАЯ ЗОНА ЗАГРУЗКИ -->
        <section
            class="relative overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition hover:border-blue-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-800"
        >
            <input
                type="file"
                multiple
                class="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0 disabled:cursor-not-allowed"
                accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.rar,.mp3,.wav,.ogg,.flac,.m4a,.aac,.jpg,.jpeg,.png,.gif,.webp"
                :disabled="uploading"
                @change="handleFileUpload"
            >

            <div class="flex min-h-[100px] items-center gap-4 p-4 sm:p-5">
                <div
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-300"
                >
                    <svg
                        v-if="uploading"
                        class="h-6 w-6 animate-spin"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        />
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                        />
                    </svg>

                    <svg
                        v-else
                        class="h-6 w-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 16V4m0 0L8 8m4-4 4 4M4 15v3a2 2 0 002 2h12a2 2 0 002-2v-3"
                        />
                    </svg>
                </div>

                <div class="min-w-0 flex-1">
                    <h2 class="font-semibold text-gray-900 dark:text-white">
                        {{ uploading ? 'Загрузка файлов…' : 'Добавить документы' }}
                    </h2>

                    <p class="mt-1 text-xs leading-5 text-gray-500 dark:text-gray-400">
                        Нажмите на блок или перетащите несколько файлов
                    </p>

                    <p class="truncate text-[11px] text-gray-400">
                        PDF, Office, архивы, аудио и изображения · до 100 МБ
                    </p>
                </div>

                <div
                    class="hidden shrink-0 rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white sm:block"
                >
                    Выбрать файлы
                </div>
            </div>
        </section>

        <!-- СЧЁТЧИКИ -->
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                <div class="text-xs text-gray-500 dark:text-gray-400">В работе</div>
                <div class="mt-1 text-xl font-bold text-gray-900 dark:text-white">
                    {{ activeFiles.length }}
                </div>
            </div>

            <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-3 dark:border-amber-900 dark:bg-amber-900/10">
                <div class="text-xs text-amber-700 dark:text-amber-300">Ожидают</div>
                <div class="mt-1 text-xl font-bold text-amber-700 dark:text-amber-300">
                    {{ pendingFiles.length }}
                </div>
            </div>

            <div class="rounded-xl border border-rose-200 bg-rose-50/60 p-3 dark:border-rose-900 dark:bg-rose-900/10">
                <div class="text-xs text-rose-700 dark:text-rose-300">На доработке</div>
                <div class="mt-1 text-xl font-bold text-rose-700 dark:text-rose-300">
                    {{ rejectedFiles.length }}
                </div>
            </div>

            <div class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-3 dark:border-emerald-900 dark:bg-emerald-900/10">
                <div class="text-xs text-emerald-700 dark:text-emerald-300">Согласовано</div>
                <div class="mt-1 text-xl font-bold text-emerald-700 dark:text-emerald-300">
                    {{ approvedFiles.length }}
                </div>
            </div>
        </div>

        <!-- ДВЕ КОЛОНКИ -->
        <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-2">
            <!-- АКТИВНЫЕ ДОКУМЕНТЫ -->
            <section
                class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
            >
                <header
                    class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-gray-700"
                >
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white">
                            Документы в работе
                        </h3>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                            Ожидают проверки или требуют исправлений
                        </p>
                    </div>

                    <span
                        class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-bold text-gray-700 dark:bg-gray-700 dark:text-gray-200"
                    >
                        {{ activeFiles.length }}
                    </span>
                </header>

                <div class="max-h-[700px] overflow-y-auto p-4 custom-scrollbar">
                    <div
                        v-if="activeFiles.length"
                        class="space-y-3"
                    >
                        <article
                            v-for="file in activeFiles"
                            :key="file.id"
                            class="rounded-xl border p-4 transition hover:shadow-sm"
                            :class="getStatusBadge(file.status).cardClasses"
                        >
                            <div class="flex flex-col gap-4">
                                <div class="flex items-start gap-3">
                                    <button
                                        type="button"
                                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-2xl shadow-sm dark:bg-gray-700"
                                        title="Открыть файл"
                                        @click="openFile(file)"
                                    >
                                        {{ getFileIcon(file) }}
                                    </button>

                                    <div class="min-w-0 flex-1">
                                        <div v-if="isAudioFile(file)">
                                            <button
                                                type="button"
                                                class="block max-w-full truncate text-left text-sm font-bold text-gray-900 hover:text-blue-600 dark:text-white"
                                                :title="getFileName(file)"
                                                @click="openFile(file)"
                                            >
                                                {{ getFileName(file) }}
                                            </button>

                                            <audio
                                                :src="`/api/tasks/files/${file.id}`"
                                                controls
                                                controlslist="nodownload"
                                                class="mt-2 h-9 w-full"
                                                preload="metadata"
                                            >
                                                Ваш браузер не поддерживает аудиоплеер
                                            </audio>
                                        </div>

                                        <div v-else-if="isImageFile(file)">
                                            <button
                                                type="button"
                                                class="block max-w-full truncate text-left text-sm font-bold text-gray-900 hover:text-blue-600 dark:text-white"
                                                :title="getFileName(file)"
                                                @click="openFile(file)"
                                            >
                                                {{ getFileName(file) }}
                                            </button>

                                            <img
                                                :src="`/api/tasks/files/${file.id}`"
                                                :alt="getFileName(file)"
                                                class="mt-2 max-h-36 max-w-full cursor-pointer rounded-lg object-cover transition hover:opacity-90"
                                                @click="openFile(file)"
                                            >
                                        </div>

                                        <a
                                            v-else
                                            :href="`/api/tasks/files/${file.id}`"
                                            target="_blank"
                                            class="block truncate text-sm font-bold text-gray-900 hover:text-blue-600 hover:underline dark:text-white"
                                            :title="getFileName(file)"
                                        >
                                            {{ getFileName(file) }}
                                        </a>

                                        <div class="mt-1 flex flex-wrap items-center gap-1.5 text-[11px] text-gray-500 dark:text-gray-400">
                                            <span>{{ file.user?.name || 'Неизвестный' }}</span>
                                            <span>•</span>
                                            <span>{{ formatDate(file.created_at) }}</span>
                                            <template v-if="file.size">
                                                <span>•</span>
                                                <span>{{ (file.size / 1024 / 1024).toFixed(2) }} МБ</span>
                                            </template>
                                        </div>
                                    </div>

                                    <span
                                        class="shrink-0 rounded-full px-2 py-1 text-[10px] font-bold"
                                        :class="getStatusBadge(file.status).badgeClasses"
                                    >
                                        {{ getStatusBadge(file.status).icon }}
                                        {{ getStatusBadge(file.status).text }}
                                    </span>
                                </div>

                                <!-- ДЕЙСТВИЯ -->
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    <template
                                        v-if="(isExecutor || isResponsible || isCreator) && file.status === 'pending'"
                                    >
                                        <button
                                            type="button"
                                            class="btn-action bg-emerald-600 text-white hover:bg-emerald-700"
                                            @click="approve(file)"
                                        >
                                            ✔ Принять
                                        </button>

                                        <button
                                            type="button"
                                            class="btn-action bg-rose-500 text-white hover:bg-rose-600"
                                            @click="openRejectModal(file)"
                                        >
                                            ✖ Вернуть
                                        </button>
                                    </template>

                                    <div
                                        v-if="canReplaceFile && file.status === 'rejected'"
                                    >
                                        <input
                                            :id="`replace-${file.id}`"
                                            type="file"
                                            class="hidden"
                                            accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.rar,.mp3,.wav,.ogg,.flac,.m4a,.aac,.jpg,.jpeg,.png,.gif,.webp"
                                            @change="event => handleReplace(event, file.id)"
                                        >

                                        <label
                                            :for="`replace-${file.id}`"
                                            class="btn-action cursor-pointer bg-blue-600 text-white hover:bg-blue-700"
                                        >
                                            🔄 Заменить
                                        </label>
                                    </div>

                                    <button
                                        v-if="(isExecutor || isResponsible || isCreator) && file.status !== 'approved'"
                                        type="button"
                                        class="btn-action bg-white text-rose-600 ring-1 ring-rose-200 hover:bg-rose-50 dark:bg-gray-800 dark:ring-rose-900 dark:hover:bg-rose-900/20"
                                        @click="deleteFile(file.id)"
                                    >
                                        🗑 Удалить
                                    </button>
                                </div>

                                <!-- КОММЕНТАРИИ -->
                                <div
                                    v-if="file.comments?.length"
                                    class="space-y-2 border-t border-black/5 pt-3 dark:border-white/10"
                                >
                                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                        💬 Комментарии: {{ file.comments.length }}
                                    </div>

                                    <div
                                        v-for="comment in file.comments"
                                        :key="comment.id"
                                        class="rounded-lg bg-white/70 p-3 text-sm dark:bg-gray-800/70"
                                    >
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="font-medium text-gray-700 dark:text-gray-200">
                                                        {{ comment.user?.name || 'Неизвестный' }}
                                                    </span>

                                                    <span class="text-[11px] text-gray-400">
                                                        {{ formatDate(comment.created_at) }}
                                                    </span>

                                                    <span
                                                        v-if="comment.type === 'rejection'"
                                                        class="rounded-full bg-rose-100 px-2 py-0.5 text-[10px] text-rose-700 dark:bg-rose-900/30 dark:text-rose-300"
                                                    >
                                                        Отказ
                                                    </span>

                                                    <span
                                                        v-else-if="comment.type === 'feedback'"
                                                        class="rounded-full bg-blue-100 px-2 py-0.5 text-[10px] text-blue-700 dark:bg-blue-900/30 dark:text-blue-300"
                                                    >
                                                        Замечание
                                                    </span>
                                                </div>

                                                <p class="mt-1 whitespace-pre-wrap break-words text-gray-600 dark:text-gray-400">
                                                    {{ comment.comment }}
                                                </p>
                                            </div>

                                            <button
                                                v-if="comment.user_id === currentUser.id"
                                                type="button"
                                                class="rounded p-1 text-gray-400 hover:bg-rose-50 hover:text-rose-500 dark:hover:bg-rose-900/20"
                                                title="Удалить комментарий"
                                                @click="deleteComment(comment.id)"
                                            >
                                                ✕
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- ДОБАВЛЕНИЕ КОММЕНТАРИЯ -->
                                <div
                                    v-if="canComment(file)"
                                    class="rounded-xl border border-amber-200 bg-amber-50/70 p-3 dark:border-amber-800 dark:bg-amber-900/20"
                                >
                                    <div class="mb-2 text-xs text-amber-700 dark:text-amber-300">
                                        Оставьте замечание для исполнителя
                                    </div>

                                    <div class="flex flex-col gap-2 sm:flex-row">
                                        <input
                                            v-model="newComment"
                                            type="text"
                                            placeholder="Напишите замечание..."
                                            class="min-w-0 flex-1 rounded-lg border border-amber-300 px-3 py-2 text-sm focus:border-transparent focus:ring-2 focus:ring-amber-500 dark:border-amber-700 dark:bg-gray-700 dark:text-white"
                                            @keydown.enter="addFileComment(file.id)"
                                        >

                                        <button
                                            type="button"
                                            class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-amber-600 disabled:cursor-not-allowed disabled:opacity-50"
                                            :disabled="isSendingComment && commentingFileId === file.id"
                                            @click="addFileComment(file.id)"
                                        >
                                            {{
                                                isSendingComment && commentingFileId === file.id
                                                    ? 'Отправка...'
                                                    : 'Отправить'
                                            }}
                                        </button>
                                    </div>
                                </div>

                                <!-- СТАРАЯ ПРИЧИНА ОТКАЗА -->
                                <div
                                    v-if="
                                        file.status === 'rejected' &&
                                        file.rejection_reason &&
                                        !file.comments?.length
                                    "
                                    class="rounded-lg border border-rose-200 bg-white/60 p-3 text-sm text-rose-800 dark:border-rose-800 dark:bg-gray-800/60 dark:text-rose-300"
                                >
                                    <span class="font-bold">Причина возврата:</span>

                                    <span class="ml-1 whitespace-pre-wrap">
                                        {{
                                            expandedComments.has(file.id)
                                                ? file.rejection_reason
                                                : file.rejection_reason.slice(0, 100)
                                        }}
                                    </span>

                                    <span
                                        v-if="
                                            !expandedComments.has(file.id) &&
                                            file.rejection_reason.length > 100
                                        "
                                    >
                                        ...
                                    </span>

                                    <button
                                        v-if="file.rejection_reason.length > 100"
                                        type="button"
                                        class="ml-2 text-xs font-bold text-rose-600 hover:underline dark:text-rose-300"
                                        @click="toggleComment(file.id)"
                                    >
                                        {{
                                            expandedComments.has(file.id)
                                                ? 'Свернуть'
                                                : 'Читать далее'
                                        }}
                                    </button>
                                </div>
                            </div>
                        </article>
                    </div>

                    <div
                        v-else
                        class="flex min-h-[260px] flex-col items-center justify-center px-4 text-center"
                    >
                        <div class="text-4xl">🎉</div>
                        <div class="mt-3 font-semibold text-gray-700 dark:text-gray-200">
                            Нет документов в работе
                        </div>
                        <p class="mt-1 max-w-xs text-sm text-gray-400">
                            Все добавленные документы уже обработаны
                        </p>
                    </div>
                </div>
            </section>

            <!-- СОГЛАСОВАННЫЕ ДОКУМЕНТЫ -->
            <section
                class="overflow-hidden rounded-2xl border border-emerald-200 bg-white shadow-sm dark:border-emerald-900 dark:bg-gray-800"
            >
                <header
                    class="flex items-center justify-between border-b border-emerald-100 bg-emerald-50/60 px-5 py-4 dark:border-emerald-900 dark:bg-emerald-900/10"
                >
                    <div>
                        <h3 class="flex items-center gap-2 font-bold text-gray-900 dark:text-white">
                            <span>✅</span>
                            Согласованные
                        </h3>

                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                            Проверенные и принятые документы
                        </p>
                    </div>

                    <span
                        class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300"
                    >
                        {{ approvedFiles.length }}
                    </span>
                </header>

                <div class="max-h-[700px] overflow-y-auto p-4 custom-scrollbar">
                    <div
                        v-if="approvedFiles.length"
                        class="space-y-2"
                    >
                        <article
                            v-for="file in approvedFiles"
                            :key="file.id"
                            class="group flex items-center gap-3 rounded-xl border border-gray-100 bg-gray-50/70 p-3 transition hover:border-emerald-200 hover:bg-emerald-50/60 dark:border-gray-700 dark:bg-gray-900/30 dark:hover:border-emerald-800"
                        >
                            <button
                                type="button"
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white text-xl shadow-sm dark:bg-gray-700"
                                title="Открыть файл"
                                @click="openFile(file)"
                            >
                                {{ getFileIcon(file) }}
                            </button>

                            <div class="min-w-0 flex-1">
                                <a
                                    :href="`/api/tasks/files/${file.id}`"
                                    target="_blank"
                                    class="block truncate text-sm font-semibold text-gray-800 hover:text-emerald-700 dark:text-gray-100 dark:hover:text-emerald-300"
                                    :title="getFileName(file)"
                                >
                                    {{ getFileName(file) }}
                                </a>

                                <div class="mt-1 flex flex-wrap items-center gap-1.5 text-[11px] text-gray-400">
                                    <span>{{ file.user?.name || 'Неизвестный' }}</span>
                                    <span>•</span>
                                    <span>{{ formatDate(file.updated_at || file.created_at) }}</span>

                                    <template v-if="file.size">
                                        <span>•</span>
                                        <span>{{ (file.size / 1024 / 1024).toFixed(2) }} МБ</span>
                                    </template>
                                </div>
                            </div>

                            <span
                                class="hidden shrink-0 rounded-full bg-emerald-100 px-2 py-1 text-[10px] font-bold uppercase text-emerald-700 sm:inline-flex dark:bg-emerald-900/40 dark:text-emerald-300"
                            >
                                Принят
                            </span>

                            <a
                                :href="`/api/tasks/files/${file.id}`"
                                target="_blank"
                                class="rounded-lg p-2 text-gray-400 transition hover:bg-white hover:text-blue-600 dark:hover:bg-gray-700"
                                title="Открыть файл"
                            >
                                <svg
                                    class="h-4 w-4"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M14 3h7m0 0v7m0-7L10 14M5 5h5M5 5v14h14v-5"
                                    />
                                </svg>
                            </a>
                        </article>
                    </div>

                    <div
                        v-else
                        class="flex min-h-[260px] flex-col items-center justify-center px-4 text-center"
                    >
                        <div
                            class="flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 text-2xl text-gray-400 dark:bg-gray-700"
                        >
                            ✓
                        </div>

                        <div class="mt-3 font-semibold text-gray-600 dark:text-gray-300">
                            Пока нет согласованных файлов
                        </div>

                        <p class="mt-1 max-w-xs text-sm text-gray-400">
                            После принятия документы автоматически появятся здесь
                        </p>
                    </div>
                </div>
            </section>
        </div>

        <!-- ОБЫЧНЫЕ ФАЙЛЫ -->


        <!-- МОДАЛЬНОЕ ОКНО ОТКАЗА -->
        <div
            v-if="rejectModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm"
            @click.self="closeRejectModal"
        >
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl dark:bg-gray-800">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    Вернуть на доработку
                </h3>

                <p class="mt-1 text-sm text-gray-500">
                    Укажите, что именно нужно исправить.
                </p>

                <textarea
                    v-model="rejectComment"
                    class="mt-4 h-32 w-full resize-none rounded-xl border border-gray-300 p-3 text-sm focus:border-rose-500 focus:ring-2 focus:ring-rose-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    placeholder="Например: неверная дата в шапке документа..."
                    autofocus
                />

                <div class="mt-4 flex justify-end gap-3">
                    <button
                        type="button"
                        class="rounded-lg px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                        @click="closeRejectModal"
                    >
                        Отмена
                    </button>

                    <button
                        type="button"
                        class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-bold text-white shadow transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="!rejectComment.trim()"
                        @click="submitReject"
                    >
                        Вернуть документ
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.btn-action {
    @apply inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold shadow-sm transition-all hover:-translate-y-0.5 active:translate-y-0;
}

.custom-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: rgb(209 213 219) transparent;
}

.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    border-radius: 9999px;
    background: rgb(209 213 219);
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgb(156 163 175);
}

:global(.dark) .custom-scrollbar {
    scrollbar-color: rgb(75 85 99) transparent;
}

:global(.dark) .custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgb(75 85 99);
}
</style>
