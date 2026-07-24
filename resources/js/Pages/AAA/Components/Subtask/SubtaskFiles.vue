<script setup>
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
} from 'vue'
import axios from 'axios'

import { VueFilesPreview } from 'vue-files-preview'
import 'vue-files-preview/lib/style.css'

const props = defineProps({
    subtask: {
        type: Object,
        required: true,
    },
    user: {
        type: Object,
        default: null,
    },
})

const emit = defineEmits(['refresh'])

const fileInput = ref(null)
const replaceInput = ref(null)

const revisionComment = ref('')
const activeFileId = ref(null)
const fileToReplaceId = ref(null)
const expandedComments = ref(new Set())

const isUploading = ref(false)
const replacingFileId = ref(null)
const deletingFileId = ref(null)
const approvingFileId = ref(null)
const revisionLoadingId = ref(null)

/*
|--------------------------------------------------------------------------
| Просмотр файла
|--------------------------------------------------------------------------
*/

const previewOpened = ref(false)
const previewFile = ref(null)
const previewLoading = ref(false)
const previewError = ref('')

const canUpload = computed(() => {
    const { subtask, user } = props

    if (!subtask || !user) {
        return false
    }

    return (
        Number(subtask.creator_id) === Number(user.id) ||
        (subtask.executors || []).some(
            executor => Number(executor.id) === Number(user.id),
        ) ||
        (subtask.responsibles || []).some(
            responsible => Number(responsible.id) === Number(user.id),
        )
    )
})

const isResponsible = computed(() => {
    const { subtask, user } = props

    if (!subtask || !user) {
        return false
    }

    return (subtask.responsibles || []).some(
        responsible => Number(responsible.id) === Number(user.id),
    )
})

const isExecutor = computed(() => {
    const { subtask, user } = props

    if (!subtask || !user) {
        return false
    }

    return (subtask.executors || []).some(
        executor => Number(executor.id) === Number(user.id),
    )
})

const files = computed(() => {
    return Array.isArray(props.subtask?.files)
        ? props.subtask.files
        : []
})

/*
|--------------------------------------------------------------------------
| Статусы
|--------------------------------------------------------------------------
|
| В вашей таблице имеются:
|
| status
| approval_status
|
| Поэтому проверяем оба поля.
|--------------------------------------------------------------------------
*/

const getFileStatus = file => {
    if (!file) {
        return 'pending'
    }

    if (
        file.status === 'revision' ||
        file.approval_status === 'revision'
    ) {
        return 'revision'
    }

    if (
        file.status === 'approved' ||
        file.approval_status === 'approved'
    ) {
        return 'approved'
    }

    return 'pending'
}

const canApprove = file => {
    const status = getFileStatus(file)

    return (
        (isResponsible.value || isExecutor.value) &&
        status !== 'revision' &&
        status !== 'approved'
    )
}

const canSendToRevision = file => {
    return (
        (isExecutor.value || isResponsible.value) &&
        getFileStatus(file) !== 'approved'
    )
}

const canDeleteFile = file => {
    if (!props.user || !file) {
        return false
    }

    return (
        Number(file.user_id) === Number(props.user.id) ||
        Number(props.subtask?.creator_id) === Number(props.user.id)
    )
}

const getStatusBadge = file => {
    const status = getFileStatus(file)

    const badges = {
        pending: {
            text: 'Ожидает согласования',
            icon: 'clock',
            class:
                'border-amber-200 bg-amber-50 text-amber-700 ' +
                'dark:border-amber-800/70 dark:bg-amber-950/40 dark:text-amber-300',
            dot: 'bg-amber-500',
        },

        approved: {
            text: 'Согласован',
            icon: 'approved',
            class:
                'border-emerald-200 bg-emerald-50 text-emerald-700 ' +
                'dark:border-emerald-800/70 dark:bg-emerald-950/40 dark:text-emerald-300',
            dot: 'bg-emerald-500',
        },

        revision: {
            text: 'Требуется доработка',
            icon: 'revision',
            class:
                'border-red-200 bg-red-50 text-red-700 ' +
                'dark:border-red-800/70 dark:bg-red-950/40 dark:text-red-300',
            dot: 'bg-red-500',
        },
    }

    return badges[status] || badges.pending
}

const getFileCardClass = file => {
    const status = getFileStatus(file)

    if (status === 'revision') {
        return [
            'border-red-200/90',
            'bg-gradient-to-br',
            'from-white',
            'to-red-50/60',
            'dark:border-red-900/70',
            'dark:from-gray-900',
            'dark:to-red-950/20',
        ]
    }

    if (status === 'approved') {
        return [
            'border-emerald-200/90',
            'bg-gradient-to-br',
            'from-white',
            'to-emerald-50/50',
            'dark:border-emerald-900/70',
            'dark:from-gray-900',
            'dark:to-emerald-950/20',
        ]
    }

    return [
        'border-gray-200',
        'bg-white',
        'dark:border-slate-800',
        'dark:bg-gray-900',
    ]
}

/*
|--------------------------------------------------------------------------
| Работа с именами и типами файлов
|--------------------------------------------------------------------------
*/

const getFileName = file => {
    return (
        file?.filename ||
        file?.original_name ||
        file?.name ||
        'Файл'
    )
}

const getFileExtension = filename => {
    if (!filename || !filename.includes('.')) {
        return ''
    }

    return filename.split('.').pop().toLowerCase()
}

const getFileType = filename => {
    const extension = getFileExtension(filename)

    if (
        ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'avif'].includes(
            extension,
        )
    ) {
        return 'image'
    }

    if (extension === 'pdf') {
        return 'pdf'
    }

    if (['doc', 'docx', 'rtf', 'odt'].includes(extension)) {
        return 'document'
    }

    if (['xls', 'xlsx', 'csv', 'ods'].includes(extension)) {
        return 'spreadsheet'
    }

    if (['ppt', 'pptx', 'odp'].includes(extension)) {
        return 'presentation'
    }

    if (['zip', 'rar', '7z', 'tar', 'gz'].includes(extension)) {
        return 'archive'
    }

    if (
        ['mp3', 'wav', 'ogg', 'flac', 'm4a', 'aac', 'opus', 'wma'].includes(
            extension,
        )
    ) {
        return 'audio'
    }

    if (
        ['mp4', 'avi', 'mov', 'mkv', 'webm', 'm4v'].includes(extension)
    ) {
        return 'video'
    }

    if (
        [
            'txt',
            'md',
            'json',
            'xml',
            'html',
            'css',
            'js',
            'ts',
            'vue',
            'php',
            'sql',
            'log',
        ].includes(extension)
    ) {
        return 'text'
    }

    return 'file'
}

const getFileTypeLabel = filename => {
    const types = {
        image: 'Изображение',
        pdf: 'PDF-документ',
        document: 'Документ',
        spreadsheet: 'Таблица',
        presentation: 'Презентация',
        archive: 'Архив',
        audio: 'Аудиозапись',
        video: 'Видео',
        text: 'Текстовый файл',
        file: 'Файл',
    }

    return types[getFileType(filename)] || 'Файл'
}

const getFileIconClass = filename => {
    const type = getFileType(filename)

    const classes = {
        image:
            'bg-violet-100 text-violet-600 dark:bg-violet-950/60 dark:text-violet-300',
        pdf:
            'bg-red-100 text-red-600 dark:bg-red-950/60 dark:text-red-300',
        document:
            'bg-blue-100 text-blue-600 dark:bg-blue-950/60 dark:text-blue-300',
        spreadsheet:
            'bg-emerald-100 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-300',
        presentation:
            'bg-orange-100 text-orange-600 dark:bg-orange-950/60 dark:text-orange-300',
        archive:
            'bg-amber-100 text-amber-600 dark:bg-amber-950/60 dark:text-amber-300',
        audio:
            'bg-pink-100 text-pink-600 dark:bg-pink-950/60 dark:text-pink-300',
        video:
            'bg-indigo-100 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-300',
        text:
            'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
        file:
            'bg-gray-100 text-gray-600 dark:bg-slate-900 dark:text-gray-300',
    }

    return classes[type] || classes.file
}

const getFileIconText = filename => {
    const icons = {
        image: 'IMG',
        pdf: 'PDF',
        document: 'DOC',
        spreadsheet: 'XLS',
        presentation: 'PPT',
        archive: 'ZIP',
        audio: 'MP3',
        video: 'MP4',
        text: 'TXT',
        file: 'FILE',
    }

    return icons[getFileType(filename)] || 'FILE'
}

/*
|--------------------------------------------------------------------------
| URL файлов
|--------------------------------------------------------------------------
*/

const getPreviewUrl = file => {
    return `/api/subtask-files/${file.id}/preview`
}

const getDownloadUrl = file => {
    return `/api/subtask-files/${file.id}/download`
}

const getThumbnailUrl = file => {
    if (getFileType(getFileName(file)) !== 'image') {
        return null
    }

    return getPreviewUrl(file)
}

const formatFileSize = bytes => {
    const size = Number(bytes)

    if (!size || size < 0) {
        return ''
    }

    if (size < 1024) {
        return `${size} B`
    }

    if (size < 1024 * 1024) {
        return `${(size / 1024).toFixed(1)} KB`
    }

    if (size < 1024 * 1024 * 1024) {
        return `${(size / 1024 / 1024).toFixed(2)} MB`
    }

    return `${(size / 1024 / 1024 / 1024).toFixed(2)} GB`
}

const formatDate = value => {
    if (!value) {
        return ''
    }

    const date = new Date(value)

    if (Number.isNaN(date.getTime())) {
        return ''
    }

    return new Intl.DateTimeFormat('ru-RU', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date)
}

/*
|--------------------------------------------------------------------------
| Онлайн-просмотр
|--------------------------------------------------------------------------
*/

const openPreview = async file => {
    previewFile.value = file
    previewError.value = ''
    previewLoading.value = true
    previewOpened.value = true

    document.body.style.overflow = 'hidden'

    await nextTick()
}

const closePreview = () => {
    previewOpened.value = false
    previewFile.value = null
    previewError.value = ''
    previewLoading.value = false

    document.body.style.overflow = ''
}

const handlePreviewRendered = () => {
    previewLoading.value = false
    previewError.value = ''
}

const handlePreviewError = error => {
    console.error('Ошибка просмотра файла:', error)

    previewLoading.value = false
    previewError.value =
        'Не удалось открыть файл в режиме онлайн-просмотра.'
}

const reloadPreview = async () => {
    const currentFile = previewFile.value

    previewFile.value = null
    previewError.value = ''
    previewLoading.value = true

    await nextTick()

    previewFile.value = currentFile
}

const downloadFile = file => {
    if (!file) {
        return
    }

    const anchor = document.createElement('a')

    anchor.href = getDownloadUrl(file)
    anchor.download = getFileName(file)

    document.body.appendChild(anchor)
    anchor.click()
    anchor.remove()
}

const openInNewTab = file => {
    if (!file) {
        return
    }

    window.open(
        getPreviewUrl(file),
        '_blank',
        'noopener,noreferrer',
    )
}

/*
|--------------------------------------------------------------------------
| Загрузка
|--------------------------------------------------------------------------
*/

const triggerUpload = () => {
    fileInput.value?.click()
}

const uploadFile = async event => {
    const selectedFile = event.target.files?.[0]

    if (!selectedFile) {
        return
    }

    const formData = new FormData()
    formData.append('file', selectedFile)

    isUploading.value = true

    try {
        await axios.post(
            `/api/subtasks/${props.subtask.id}/files`,
            formData,
            {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            },
        )

        emit('refresh')
    } catch (error) {
        alert(
            error.response?.data?.message ||
            'Произошла ошибка при загрузке файла.',
        )
    } finally {
        isUploading.value = false
        event.target.value = ''
    }
}

/*
|--------------------------------------------------------------------------
| Удаление
|--------------------------------------------------------------------------
*/

const deleteFile = async id => {
    if (!window.confirm('Удалить этот файл?')) {
        return
    }

    deletingFileId.value = id

    try {
        await axios.delete(`/api/subtask-files/${id}`)

        if (previewFile.value?.id === id) {
            closePreview()
        }

        emit('refresh')
    } catch (error) {
        alert(
            error.response?.data?.message ||
            'Произошла ошибка при удалении файла.',
        )
    } finally {
        deletingFileId.value = null
    }
}

/*
|--------------------------------------------------------------------------
| Замена файла
|--------------------------------------------------------------------------
*/

const triggerReplace = id => {
    fileToReplaceId.value = id
    replaceInput.value?.click()
}

const handleReplaceFile = async event => {
    const selectedFile = event.target.files?.[0]
    const fileId = fileToReplaceId.value

    if (!selectedFile || !fileId) {
        return
    }

    const formData = new FormData()
    formData.append('file', selectedFile)

    replacingFileId.value = fileId

    try {
        await axios.post(
            `/api/subtask-files/${fileId}/replace`,
            formData,
            {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            },
        )

        if (previewFile.value?.id === fileId) {
            closePreview()
        }

        emit('refresh')
    } catch (error) {
        alert(
            error.response?.data?.message ||
            'Произошла ошибка при обновлении файла.',
        )
    } finally {
        event.target.value = ''
        fileToReplaceId.value = null
        replacingFileId.value = null
    }
}

/*
|--------------------------------------------------------------------------
| Согласование
|--------------------------------------------------------------------------
*/

const approveFile = async fileId => {
    if (!window.confirm('Согласовать этот файл без доработок?')) {
        return
    }

    approvingFileId.value = fileId

    try {
        await axios.post(
            `/api/subtask-files/${fileId}/approve`,
        )

        emit('refresh')
    } catch (error) {
        alert(
            error.response?.data?.message ||
            'Произошла ошибка при согласовании файла.',
        )
    } finally {
        approvingFileId.value = null
    }
}

/*
|--------------------------------------------------------------------------
| Отправка на доработку
|--------------------------------------------------------------------------
*/

const openRevisionInput = fileId => {
    if (activeFileId.value === fileId) {
        activeFileId.value = null
        revisionComment.value = ''
        return
    }

    activeFileId.value = fileId

    const file = files.value.find(item => item.id === fileId)

    revisionComment.value = file?.revision_comment || ''
}

const closeRevisionInput = () => {
    activeFileId.value = null
    revisionComment.value = ''
}

const sendRevision = async fileId => {
    const comment = revisionComment.value.trim()

    if (!comment) {
        alert('Пожалуйста, укажите причину доработки.')
        return
    }

    revisionLoadingId.value = fileId

    try {
        await axios.post(
            `/api/subtask-files/${fileId}/revision`,
            {
                comment,
            },
        )

        closeRevisionInput()
        emit('refresh')
    } catch (error) {
        alert(
            error.response?.data?.message ||
            'Произошла ошибка при отправке на доработку.',
        )
    } finally {
        revisionLoadingId.value = null
    }
}

/*
|--------------------------------------------------------------------------
| Комментарии
|--------------------------------------------------------------------------
*/

const toggleComment = id => {
    const updated = new Set(expandedComments.value)

    if (updated.has(id)) {
        updated.delete(id)
    } else {
        updated.add(id)
    }

    expandedComments.value = updated
}

const showRevisionComment = file => {
    return (
        getFileStatus(file) === 'revision' &&
        Boolean(file.revision_comment)
    )
}

const getRevisionComment = file => {
    const comment = file?.revision_comment || ''

    if (
        expandedComments.value.has(file.id) ||
        comment.length <= 180
    ) {
        return comment
    }

    return `${comment.slice(0, 180)}...`
}

const handleKeydown = event => {
    if (event.key === 'Escape' && previewOpened.value) {
        closePreview()
    }
}

onMounted(() => {
    window.addEventListener('keydown', handleKeydown)
})

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeydown)
    document.body.style.overflow = ''
})
</script>

<template>
    <section
        class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm
               dark:border-slate-800 dark:bg-slate-900"
    >
        <!-- Шапка -->
        <header
            class="border-b border-gray-100 bg-gradient-to-r from-slate-50 via-white to-blue-50/70
                   px-5 py-5 dark:border-slate-800 dark:from-gray-800 dark:via-gray-800
                   dark:to-blue-950/20 sm:px-6"
        >
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex min-w-0 items-center gap-3">
                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl
                               bg-blue-600 text-white shadow-lg shadow-blue-600/20"
                    >
                        <svg
                            class="h-6 w-6"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828L18 9.828a4 4 0 10-5.657-5.657L5.757 10.757a6 6 0 108.486 8.486L20.5 13"
                            />
                        </svg>
                    </div>

                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <h3
                                class="truncate text-lg font-bold text-slate-900 dark:text-white"
                            >
                                Файлы подзадачи
                            </h3>

                            <span
                                class="rounded-full bg-gray-900 px-2.5 py-1 text-xs font-bold text-white
                                       dark:bg-white dark:text-gray-900"
                            >
                                {{ files.length }}
                            </span>
                        </div>

                        <p
                            class="mt-1 text-sm text-slate-500 dark:text-slate-400"
                        >
                            Просматривайте, согласовывайте и обновляйте файлы
                            прямо на странице
                        </p>
                    </div>
                </div>

                <button
                    v-if="canUpload"
                    type="button"
                    :disabled="isUploading"
                    class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl
                           bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white
                           shadow-lg shadow-blue-600/20 transition
                           hover:-translate-y-0.5 hover:bg-blue-700
                           disabled:cursor-not-allowed disabled:opacity-60"
                    @click="triggerUpload"
                >
                    <svg
                        v-if="!isUploading"
                        class="h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 4v16m8-8H4"
                        />
                    </svg>

                    <svg
                        v-else
                        class="h-5 w-5 animate-spin"
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

                    {{ isUploading ? 'Загрузка...' : 'Загрузить файл' }}
                </button>
            </div>

            <input
                ref="fileInput"
                type="file"
                class="hidden"
                accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.ppt,.pptx,.txt,.md,.json,.zip,.rar,.7z,.mp3,.wav,.ogg,.flac,.m4a,.aac,.opus,.jpg,.jpeg,.png,.gif,.webp,.svg,.mp4,.avi,.mov,.mkv,.webm"
                @change="uploadFile"
            />

            <input
                ref="replaceInput"
                type="file"
                class="hidden"
                accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.ppt,.pptx,.txt,.md,.json,.zip,.rar,.7z,.mp3,.wav,.ogg,.flac,.m4a,.aac,.opus,.jpg,.jpeg,.png,.gif,.webp,.svg,.mp4,.avi,.mov,.mkv,.webm"
                @change="handleReplaceFile"
            />
        </header>

        <!-- Список -->
        <div v-if="files.length" class="p-4 sm:p-4">
            <div
                class="files-scrollbar max-h-[680px] space-y-4 overflow-y-auto pr-1"
            >
                <article
                    v-for="file in files"
                    :key="file.id"
                    class="group relative overflow-hidden rounded-2xl border p-4
                           shadow-sm transition duration-200
                           hover:-translate-y-0.5 hover:shadow-md sm:p-5"
                    :class="getFileCardClass(file)"
                >
                    <div
                        class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between"
                    >
                        <!-- Информация -->
                        <div class="flex min-w-0 flex-1 gap-4">
                            <!-- Миниатюра изображения -->
                            <button
                                v-if="getThumbnailUrl(file)"
                                type="button"
                                class="relative h-20 w-20 shrink-0 overflow-hidden rounded-2xl
                                       border border-gray-200 bg-gray-100
                                       dark:border-slate-800 dark:bg-slate-900"
                                title="Открыть изображение"
                                @click="openPreview(file)"
                            >
                                <img
                                    :src="getThumbnailUrl(file)"
                                    :alt="getFileName(file)"
                                    class="h-full w-full object-cover transition duration-300
                                           group-hover:scale-105"
                                />

                                <span
                                    class="absolute inset-0 flex items-center justify-center
                                           bg-black/0 text-white opacity-0 transition
                                           hover:bg-black/35 hover:opacity-100"
                                >
                                    <svg
                                        class="h-6 w-6"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"
                                        />
                                    </svg>
                                </span>
                            </button>

                            <!-- Иконка обычного файла -->
                            <button
                                v-else
                                type="button"
                                class="flex h-16 w-16 shrink-0 items-center justify-center
                                       rounded-2xl text-[11px] font-black tracking-tight
                                       transition hover:scale-105"
                                :class="getFileIconClass(getFileName(file))"
                                title="Открыть предпросмотр"
                                @click="openPreview(file)"
                            >
                                {{ getFileIconText(getFileName(file)) }}
                            </button>

                            <div class="min-w-0 flex-1">
                                <div
                                    class="flex flex-col gap-2 sm:flex-row sm:items-start
                                           sm:justify-between"
                                >
                                    <div class="min-w-0">
                                        <button
                                            type="button"
                                            class="block max-w-full truncate text-left text-sm
                                                   font-bold text-gray-900 transition
                                                   hover:text-blue-600 dark:text-white
                                                   dark:hover:text-blue-400 sm:text-base"
                                            :title="getFileName(file)"
                                            @click="openPreview(file)"
                                        >
                                            {{ getFileName(file) }}
                                        </button>

                                        <div
                                            class="mt-1 flex flex-wrap items-center gap-x-2
                                                   gap-y-1 text-xs text-gray-500
                                                   dark:text-gray-400"
                                        >
                                            <span>
                                                {{
                                                    getFileTypeLabel(
                                                        getFileName(file),
                                                    )
                                                }}
                                            </span>

                                            <span
                                                v-if="file.file_size"
                                                class="h-1 w-1 rounded-full bg-gray-300
                                                       dark:bg-gray-600"
                                            />

                                            <span v-if="file.file_size">
                                                {{
                                                    formatFileSize(
                                                        file.file_size,
                                                    )
                                                }}
                                            </span>

                                            <span
                                                class="h-1 w-1 rounded-full bg-gray-300
                                                       dark:bg-gray-600"
                                            />

                                            <span>
                                                {{
                                                    formatDate(
                                                        file.updated_at,
                                                    )
                                                }}
                                            </span>
                                        </div>
                                    </div>

                                    <span
                                        class="inline-flex w-fit shrink-0 items-center gap-1.5
                                               rounded-full border px-2.5 py-1 text-xs
                                               font-semibold"
                                        :class="getStatusBadge(file).class"
                                    >
                                        <span
                                            class="h-1.5 w-1.5 rounded-full"
                                            :class="getStatusBadge(file).dot"
                                        />

                                        {{ getStatusBadge(file).text }}
                                    </span>
                                </div>

                                <div
                                    v-if="
                                        file.created_at !== file.updated_at &&
                                        getFileStatus(file) !== 'revision'
                                    "
                                    class="mt-2 inline-flex items-center gap-1 rounded-lg
                                           bg-blue-50 px-2 py-1 text-[11px] font-medium
                                           text-blue-600 dark:bg-blue-950/40
                                           dark:text-blue-300"
                                >
                                    <svg
                                        class="h-3.5 w-3.5"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M4 4v5h5M20 20v-5h-5M5.64 18.36A9 9 0 0118.36 5.64M18.36 18.36A9 9 0 005.64 5.64"
                                        />
                                    </svg>

                                    Файл обновлён
                                </div>

                                <div
                                    v-if="file.approved_at"
                                    class="mt-2 text-xs text-emerald-600
                                           dark:text-emerald-400"
                                >
                                    Согласован:
                                    {{ formatDate(file.approved_at) }}
                                </div>

                                <!-- Основные кнопки -->
                                <div
                                    class="mt-4 flex flex-wrap items-center gap-2"
                                >
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1.5 rounded-xl
                                               bg-gray-900 px-3 py-2 text-xs font-semibold
                                               text-white transition hover:bg-blue-600
                                               dark:bg-white dark:text-gray-900
                                               dark:hover:bg-blue-500 dark:hover:text-white"
                                        @click="openPreview(file)"
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
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                            />
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                            />
                                        </svg>

                                        Просмотр
                                    </button>

                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1.5 rounded-xl
                                               border border-gray-200 bg-white px-3 py-2
                                               text-xs font-semibold text-gray-700 transition
                                               hover:border-blue-300 hover:bg-blue-50
                                               hover:text-blue-600
                                               dark:border-slate-800 dark:bg-slate-900
                                               dark:text-gray-200 dark:hover:border-blue-700
                                               dark:hover:bg-blue-950/30
                                               dark:hover:text-blue-300"
                                        @click="downloadFile(file)"
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
                                                d="M12 10v6m0 0l-3-3m3 3l3-3M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                            />
                                        </svg>

                                        Скачать
                                    </button>

                                    <button
                                        v-if="canApprove(file)"
                                        type="button"
                                        :disabled="
                                            approvingFileId === file.id
                                        "
                                        class="inline-flex items-center gap-1.5 rounded-xl
                                               bg-emerald-600 px-3 py-2 text-xs font-semibold
                                               text-white transition hover:bg-emerald-700
                                               disabled:cursor-not-allowed
                                               disabled:opacity-60"
                                        @click="approveFile(file.id)"
                                    >
                                        <svg
                                            v-if="
                                                approvingFileId !== file.id
                                            "
                                            class="h-4 w-4"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>

                                        <svg
                                            v-else
                                            class="h-4 w-4 animate-spin"
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

                                        Согласовать
                                    </button>

                                    <button
                                        v-if="canUpload"
                                        type="button"
                                        :disabled="
                                            replacingFileId === file.id
                                        "
                                        class="inline-flex items-center gap-1.5 rounded-xl
                                               border border-gray-200 bg-white px-3 py-2
                                               text-xs font-semibold text-gray-700 transition
                                               hover:border-indigo-300 hover:bg-indigo-50
                                               hover:text-indigo-600
                                               disabled:cursor-not-allowed
                                               disabled:opacity-60
                                               dark:border-slate-800 dark:bg-slate-900
                                               dark:text-gray-200 dark:hover:border-indigo-700
                                               dark:hover:bg-indigo-950/30
                                               dark:hover:text-indigo-300"
                                        @click="triggerReplace(file.id)"
                                    >
                                        <svg
                                            class="h-4 w-4"
                                            :class="{
                                                'animate-spin':
                                                    replacingFileId ===
                                                    file.id,
                                            }"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M4 4v5h5M20 20v-5h-5M5.64 18.36A9 9 0 0118.36 5.64M18.36 18.36A9 9 0 005.64 5.64"
                                            />
                                        </svg>

                                        Обновить
                                    </button>

                                    <button
                                        v-if="canSendToRevision(file)"
                                        type="button"
                                        class="inline-flex items-center gap-1.5 rounded-xl
                                               px-3 py-2 text-xs font-semibold transition"
                                        :class="
                                            getFileStatus(file) ===
                                            'revision'
                                                ? 'bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-950/50 dark:text-red-300'
                                                : 'bg-amber-100 text-amber-700 hover:bg-amber-200 dark:bg-amber-950/50 dark:text-amber-300'
                                        "
                                        @click="
                                            openRevisionInput(file.id)
                                        "
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
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 13H9v-2.828l6.586-6.586z"
                                            />
                                        </svg>

                                        {{
                                            getFileStatus(file) ===
                                            'revision'
                                                ? 'Изменить замечание'
                                                : 'На доработку'
                                        }}
                                    </button>

                                    <button
                                        v-if="canDeleteFile(file)"
                                        type="button"
                                        :disabled="
                                            deletingFileId === file.id
                                        "
                                        class="ml-auto inline-flex h-9 w-9 items-center
                                               justify-center rounded-xl text-gray-400
                                               transition hover:bg-red-50 hover:text-red-600
                                               disabled:cursor-not-allowed
                                               disabled:opacity-50
                                               dark:hover:bg-red-950/40
                                               dark:hover:text-red-400"
                                        title="Удалить файл"
                                        @click="deleteFile(file.id)"
                                    >
                                        <svg
                                            v-if="
                                                deletingFileId !== file.id
                                            "
                                            class="h-4.5 w-4.5"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16"
                                            />
                                        </svg>

                                        <svg
                                            v-else
                                            class="h-4 w-4 animate-spin"
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
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Замечание -->
                    <div
                        v-if="showRevisionComment(file)"
                        class="mt-4 rounded-2xl border border-red-200 bg-red-50/80
                               p-4 dark:border-red-900/60 dark:bg-red-950/30"
                    >
                        <div
                            class="mb-2 flex items-center gap-2 text-xs font-bold
                                   uppercase tracking-wide text-red-700
                                   dark:text-red-300"
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
                                    d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.667 1.73-3L13.73 4c-.77-1.333-2.69-1.333-3.46 0L3.34 16c-.77 1.333.19 3 1.73 3z"
                                />
                            </svg>

                            Требуется доработка
                        </div>

                        <p
                            class="whitespace-pre-wrap break-words text-sm
                                   leading-6 text-red-800 dark:text-red-200"
                        >
                            {{ getRevisionComment(file) }}
                        </p>

                        <button
                            v-if="
                                file.revision_comment &&
                                file.revision_comment.length > 180
                            "
                            type="button"
                            class="mt-2 text-xs font-bold text-red-700
                                   hover:underline dark:text-red-300"
                            @click="toggleComment(file.id)"
                        >
                            {{
                                expandedComments.has(file.id)
                                    ? 'Свернуть'
                                    : 'Читать полностью'
                            }}
                        </button>
                    </div>

                    <!-- Форма замечания -->
                    <div
                        v-if="activeFileId === file.id"
                        class="animate-slide-down mt-4 rounded-2xl border
                               border-amber-200 bg-amber-50/70 p-4
                               dark:border-amber-900/60 dark:bg-amber-950/20"
                    >
                        <label
                            class="mb-2 block text-sm font-bold text-gray-800
                                   dark:text-gray-100"
                        >
                            Что необходимо исправить?
                        </label>

                        <textarea
                            v-model="revisionComment"
                            rows="4"
                            maxlength="5000"
                            class="w-full resize-y rounded-xl border border-amber-200
                                   bg-white p-3 text-sm text-gray-900 outline-none
                                   transition placeholder:text-gray-400
                                   focus:border-amber-400 focus:ring-4
                                   focus:ring-amber-100
                                   dark:border-amber-900/70 dark:bg-gray-900
                                   dark:text-white dark:focus:ring-amber-950/50"
                            placeholder="Подробно опишите замечания к файлу..."
                        />

                        <div
                            class="mt-3 flex flex-col-reverse gap-2
                                   sm:flex-row sm:justify-end"
                        >
                            <button
                                type="button"
                                class="rounded-xl px-4 py-2 text-sm font-semibold
                                       text-gray-600 transition hover:bg-white
                                       dark:text-gray-300 dark:hover:bg-gray-800"
                                @click="closeRevisionInput"
                            >
                                Отмена
                            </button>

                            <button
                                type="button"
                                :disabled="
                                    revisionLoadingId === file.id ||
                                    !revisionComment.trim()
                                "
                                class="inline-flex items-center justify-center gap-2
                                       rounded-xl bg-amber-500 px-4 py-2 text-sm
                                       font-bold text-white transition
                                       hover:bg-amber-600 disabled:cursor-not-allowed
                                       disabled:opacity-50"
                                @click="sendRevision(file.id)"
                            >
                                <svg
                                    v-if="
                                        revisionLoadingId === file.id
                                    "
                                    class="h-4 w-4 animate-spin"
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

                                Отправить на доработку
                            </button>
                        </div>
                    </div>
                </article>
            </div>
        </div>

        <!-- Пустое состояние -->
        <div v-else class="p-4 sm:p-10">
            <div
                class="flex min-h-64 flex-col items-center justify-center
                       rounded-2xl border-2 border-dashed border-gray-200
                       bg-gray-50/70 px-5 text-center
                       dark:border-slate-800 dark:bg-gray-900/40"
            >
                <div
                    class="mb-3 flex h-16 w-16 items-center justify-center
                           rounded-2xl bg-white text-gray-400 shadow-sm
                           dark:bg-slate-900 dark:text-gray-500"
                >
                    <svg
                        class="h-8 w-8"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.7"
                            d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828L18 9.828a4 4 0 10-5.657-5.657L5.757 10.757a6 6 0 108.486 8.486L20.5 13"
                        />
                    </svg>
                </div>

                <h4
                    class="text-base font-bold text-slate-900 dark:text-white"
                >
                    Файлов пока нет
                </h4>

                <p
                    class="mt-2 max-w-sm text-sm leading-6 text-gray-500
                           dark:text-gray-400"
                >
                    Добавленные файлы появятся здесь. Их можно будет
                    просматривать прямо в браузере.
                </p>

                <button
                    v-if="canUpload"
                    type="button"
                    class="mt-5 inline-flex items-center gap-2 rounded-xl
                           bg-blue-600 px-4 py-2.5 text-sm font-semibold
                           text-white transition hover:bg-blue-700"
                    @click="triggerUpload"
                >
                    <svg
                        class="h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 4v16m8-8H4"
                        />
                    </svg>

                    Загрузить первый файл
                </button>
            </div>
        </div>

        <!-- Модальное окно просмотра -->
        <Teleport to="body">
            <Transition name="preview-modal">
                <div
                    v-if="previewOpened && previewFile"
                    class="fixed inset-0 z-[9999] flex flex-col bg-gray-950/80
                           p-0 backdrop-blur-sm sm:p-4"
                    @click.self="closePreview"
                >
                    <div
                        class="flex h-full min-h-0 w-full flex-col overflow-hidden
                               bg-white shadow-2xl dark:bg-gray-900
                               sm:mx-auto sm:max-w-[1500px] sm:rounded-2xl"
                    >
                        <!-- Верхняя панель -->
                        <header
                            class="flex shrink-0 items-center justify-between gap-3
                                   border-b border-gray-200 bg-white px-4 py-3
                                   dark:border-slate-800 dark:bg-gray-900 sm:px-5"
                        >
                            <div class="flex min-w-0 items-center gap-3">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center
                                           justify-center rounded-xl text-[9px]
                                           font-black"
                                    :class="
                                        getFileIconClass(
                                            getFileName(previewFile),
                                        )
                                    "
                                >
                                    {{
                                        getFileIconText(
                                            getFileName(previewFile),
                                        )
                                    }}
                                </div>

                                <div class="min-w-0">
                                    <h4
                                        class="truncate text-sm font-bold
                                               text-slate-900 dark:text-white
                                               sm:text-base"
                                        :title="getFileName(previewFile)"
                                    >
                                        {{ getFileName(previewFile) }}
                                    </h4>

                                    <p
                                        class="mt-0.5 text-xs text-gray-500
                                               dark:text-gray-400"
                                    >
                                        {{
                                            getFileTypeLabel(
                                                getFileName(previewFile),
                                            )
                                        }}

                                        <template
                                            v-if="previewFile.file_size"
                                        >
                                            ·
                                            {{
                                                formatFileSize(
                                                    previewFile.file_size,
                                                )
                                            }}
                                        </template>
                                    </p>
                                </div>
                            </div>

                            <div class="flex shrink-0 items-center gap-1.5">
                                <button
                                    type="button"
                                    class="hidden h-10 items-center gap-2 rounded-xl
                                           px-3 text-sm font-semibold text-gray-600
                                           transition hover:bg-gray-100
                                           dark:text-gray-300
                                           dark:hover:bg-gray-800 sm:inline-flex"
                                    title="Открыть в новой вкладке"
                                    @click="openInNewTab(previewFile)"
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
                                            d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4m-6-4L20 4m0 0h-6m6 0v6"
                                        />
                                    </svg>

                                    Новая вкладка
                                </button>

                                <button
                                    type="button"
                                    class="inline-flex h-10 items-center gap-2 rounded-xl
                                           px-3 text-sm font-semibold text-gray-600
                                           transition hover:bg-gray-100
                                           dark:text-gray-300
                                           dark:hover:bg-gray-800"
                                    title="Скачать файл"
                                    @click="downloadFile(previewFile)"
                                >
                                    <svg
                                        class="h-5 w-5"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 10v6m0 0l-3-3m3 3l3-3M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                        />
                                    </svg>

                                    <span class="hidden sm:inline">
                                        Скачать
                                    </span>
                                </button>

                                <button
                                    type="button"
                                    class="flex h-10 w-10 items-center justify-center
                                           rounded-xl text-gray-500 transition
                                           hover:bg-red-50 hover:text-red-600
                                           dark:text-gray-400
                                           dark:hover:bg-red-950/40
                                           dark:hover:text-red-400"
                                    title="Закрыть"
                                    @click="closePreview"
                                >
                                    <svg
                                        class="h-6 w-6"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12"
                                        />
                                    </svg>
                                </button>
                            </div>
                        </header>

                        <!-- Просмотрщик -->
                        <main
                            class="relative min-h-0 flex-1 overflow-hidden
                                   bg-gray-100 dark:bg-gray-950"
                        >
                            <div
                                v-if="previewLoading"
                                class="absolute inset-0 z-20 flex flex-col
                                       items-center justify-center bg-white/90
                                       backdrop-blur-sm dark:bg-gray-900/90"
                            >
                                <div
                                    class="h-11 w-11 animate-spin rounded-full
                                           border-4 border-blue-100 border-t-blue-600
                                           dark:border-slate-800
                                           dark:border-t-blue-400"
                                />

                                <p
                                    class="mt-4 text-sm font-semibold
                                           text-slate-700 dark:text-slate-200"
                                >
                                    Подготавливаем файл…
                                </p>

                                <p
                                    class="mt-1 text-xs text-gray-500
                                           dark:text-gray-400"
                                >
                                    Большие документы могут загружаться дольше
                                </p>
                            </div>

                            <div
                                v-if="previewError"
                                class="absolute inset-0 z-30 flex items-center
                                       justify-center p-4"
                            >
                                <div
                                    class="w-full max-w-md rounded-2xl bg-white
                                           p-7 text-center shadow-xl
                                           dark:bg-slate-900"
                                >
                                    <div
                                        class="mx-auto flex h-14 w-14 items-center
                                               justify-center rounded-2xl bg-red-100
                                               text-red-600 dark:bg-red-950/60
                                               dark:text-red-300"
                                    >
                                        <svg
                                            class="h-7 w-7"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.667 1.73-3L13.73 4c-.77-1.333-2.69-1.333-3.46 0L3.34 16c-.77 1.333.19 3 1.73 3z"
                                            />
                                        </svg>
                                    </div>

                                    <h5
                                        class="mt-4 text-lg font-bold
                                               text-slate-900 dark:text-white"
                                    >
                                        Не удалось открыть файл
                                    </h5>

                                    <p
                                        class="mt-2 text-sm leading-6
                                               text-slate-500 dark:text-slate-400"
                                    >
                                        {{ previewError }}
                                    </p>

                                    <div
                                        class="mt-5 flex flex-col gap-2
                                               sm:flex-row sm:justify-center"
                                    >
                                        <button
                                            type="button"
                                            class="rounded-xl bg-blue-600 px-4 py-2.5
                                                   text-sm font-semibold text-white
                                                   transition hover:bg-blue-700"
                                            @click="reloadPreview"
                                        >
                                            Повторить
                                        </button>

                                        <button
                                            type="button"
                                            class="rounded-xl border border-gray-200
                                                   px-4 py-2.5 text-sm font-semibold
                                                   text-gray-700 transition
                                                   hover:bg-gray-50
                                                   dark:border-slate-800
                                                   dark:text-gray-200
                                                   dark:hover:bg-gray-700"
                                            @click="
                                                downloadFile(previewFile)
                                            "
                                        >
                                            Скачать файл
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="h-full w-full overflow-auto"
                                :class="{
                                    'opacity-0':
                                        previewLoading || previewError,
                                }"
                            >
                                <VueFilesPreview
                                    :key="previewFile.id"
                                    :url="getPreviewUrl(previewFile)"
                                    :name="getFileName(previewFile)"
                                    width="100%"
                                    height="100%"
                                    overflow="auto"
                                    @rendered="handlePreviewRendered"
                                    @error="handlePreviewError"
                                />
                            </div>
                        </main>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </section>
</template>

<style scoped>
.files-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: rgb(203 213 225) transparent;
}

.files-scrollbar::-webkit-scrollbar {
    width: 7px;
}

.files-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.files-scrollbar::-webkit-scrollbar-thumb {
    background: rgb(203 213 225);
    border-radius: 999px;
}

.files-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgb(148 163 184);
}

.animate-slide-down {
    animation: slide-down 0.2s ease-out forwards;
    transform-origin: top;
}

.preview-modal-enter-active,
.preview-modal-leave-active {
    transition: opacity 0.2s ease;
}

.preview-modal-enter-active > div,
.preview-modal-leave-active > div {
    transition:
        transform 0.2s ease,
        opacity 0.2s ease;
}

.preview-modal-enter-from,
.preview-modal-leave-to {
    opacity: 0;
}

.preview-modal-enter-from > div,
.preview-modal-leave-to > div {
    opacity: 0;
    transform: translateY(12px) scale(0.985);
}

@keyframes slide-down {
    from {
        opacity: 0;
        transform: translateY(-6px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

:deep(.vue-files-preview) {
    min-height: 100%;
}

:deep(.vue-files-preview-container) {
    min-height: 100%;
}

:deep(iframe) {
    border: 0;
}
</style>