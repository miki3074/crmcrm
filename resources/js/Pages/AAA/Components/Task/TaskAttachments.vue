<script setup>
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref
} from 'vue'

import { VueFilesPreview } from 'vue-files-preview'
import 'vue-files-preview/lib/style.css'
import axios from 'axios'

const props = defineProps({
    task: {
        type: Object,
        default: () => ({})
    },

    loading: {
        type: Boolean,
        default: false
    },

    canUpload: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits([
    'uploadFiles',
    'deleteFile',
    'refresh'
])

const fileInput = ref(null)
const isDragging = ref(false)
const submittingId = ref(null)

/*
|--------------------------------------------------------------------------
| Просмотрщик
|--------------------------------------------------------------------------
*/

const showViewer = ref(false)
const currentFile = ref(null)
const previewLoading = ref(false)
const previewError = ref('')

const files = computed(() => {
    return Array.isArray(props.task?.files)
        ? props.task.files
        : []
})

/*
|--------------------------------------------------------------------------
| Информация о файлах
|--------------------------------------------------------------------------
*/

const getFileName = (file) => {
    if (file?.file_name) {
        return file.file_name
    }

    if (file?.name) {
        return file.name
    }

    if (file?.file_path) {
        return file.file_path.split('/').pop()
    }

    return 'Файл'
}

const getRawFileExtension = (file) => {
    const fileName = getFileName(file)
    const parts = fileName.split('.')

    if (parts.length < 2) {
        return ''
    }

    return parts.pop()?.toLowerCase() ?? ''
}

const getFileExtension = (file) => {
    const extension = getRawFileExtension(file)

    return extension
        ? extension.toUpperCase()
        : 'FILE'
}

const getFileIcon = (file) => {
    const extension = getRawFileExtension(file)

    if (
        [
            'jpg',
            'jpeg',
            'png',
            'gif',
            'webp',
            'svg',
            'bmp',
            'ico'
        ].includes(extension)
    ) {
        return '🖼️'
    }

    if (extension === 'pdf') {
        return '📕'
    }

    if (
        [
            'doc',
            'docx',
            'txt',
            'rtf',
            'odt',
            'md',
            'json',
            'xml',
            'log'
        ].includes(extension)
    ) {
        return '📄'
    }

    if (
        [
            'xls',
            'xlsx',
            'xlsm',
            'csv',
            'ods',
            'fods'
        ].includes(extension)
    ) {
        return '📊'
    }

    if (
        [
            'ppt',
            'pptx',
            'odp',
            'pps',
            'ppsx'
        ].includes(extension)
    ) {
        return '📽️'
    }

    if (
        [
            'zip',
            'rar',
            '7z',
            'tar',
            'gz'
        ].includes(extension)
    ) {
        return '📦'
    }

    if (
        [
            'mp3',
            'wav',
            'ogg',
            'flac',
            'm4a',
            'aac',
            'opus',
            'wma'
        ].includes(extension)
    ) {
        return '🎵'
    }

    if (
        [
            'mp4',
            'avi',
            'mov',
            'mkv',
            'webm',
            'mpeg',
            'flv',
            'wmv'
        ].includes(extension)
    ) {
        return '🎬'
    }

    if (extension === 'epub') {
        return '📚'
    }

    if (extension === 'msg') {
        return '✉️'
    }

    return '📎'
}

const formatFileSize = (size) => {
    const bytes = Number(size)

    if (!bytes || Number.isNaN(bytes)) {
        return ''
    }

    if (bytes < 1024) {
        return `${bytes} B`
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`
    }

    if (bytes < 1024 * 1024 * 1024) {
        return `${(
            bytes /
            (1024 * 1024)
        ).toFixed(1)} MB`
    }

    return `${(
        bytes /
        (1024 * 1024 * 1024)
    ).toFixed(1)} GB`
}

const getFileSize = (file) => {
    return formatFileSize(
        file?.size ??
        file?.file_size ??
        file?.filesize
    )
}

/*
|--------------------------------------------------------------------------
| Согласование
|--------------------------------------------------------------------------
*/

const canSubmitForApproval = (file) => {
    return props.canUpload && (!file?.status || file.status === 'none')
}

const submitForApproval = async (file) => {
    if (!canSubmitForApproval(file) || submittingId.value) {
        return
    }

    submittingId.value = file.id

    try {
        await axios.put(`/api/files/${file.id}/submit-approval`)
        emit('refresh')
    } catch (error) {
        alert(
            error?.response?.data?.message ||
            'Не удалось отправить файл на согласование'
        )
    } finally {
        submittingId.value = null
    }
}

/*
|--------------------------------------------------------------------------
| URL файлов
|--------------------------------------------------------------------------
*/

const getFilePreviewUrl = (file) => {
    if (!file?.id) {
        return ''
    }

    /*
     * Сервер должен вернуть сам файл, а не JSON.
     * Content-Disposition желательно установить как inline.
     */
    return `/api/tasks/files/${file.id}`
}

const getFileDownloadUrl = (file) => {
    if (!file?.id) {
        return ''
    }

    return `/api/tasks/files/${file.id}?download=1`
}

const currentFileName = computed(() => {
    if (!currentFile.value) {
        return ''
    }

    return getFileName(currentFile.value)
})

const currentFileExtension = computed(() => {
    if (!currentFile.value) {
        return ''
    }

    return getFileExtension(currentFile.value)
})

const currentFileIcon = computed(() => {
    if (!currentFile.value) {
        return '📎'
    }

    return getFileIcon(currentFile.value)
})

const currentFileSize = computed(() => {
    if (!currentFile.value) {
        return ''
    }

    return getFileSize(currentFile.value)
})

const currentFileUrl = computed(() => {
    if (!currentFile.value) {
        return ''
    }

    return getFilePreviewUrl(currentFile.value)
})

const currentDownloadUrl = computed(() => {
    if (!currentFile.value) {
        return ''
    }

    return getFileDownloadUrl(currentFile.value)
})

/*
|--------------------------------------------------------------------------
| Загрузка файлов
|--------------------------------------------------------------------------
*/

const handleFileInput = (event) => {
    uploadFiles(event.target.files)

    // Можно повторно выбрать тот же файл.
    event.target.value = ''
}

const handleDrop = (event) => {
    isDragging.value = false

    if (!props.canUpload || props.loading) {
        return
    }

    uploadFiles(event.dataTransfer.files)
}

const uploadFiles = (selectedFiles) => {
    if (
        !selectedFiles?.length ||
        props.loading ||
        !props.canUpload
    ) {
        return
    }

    emit('uploadFiles', selectedFiles)
}

const openFileDialog = () => {
    if (!props.canUpload || props.loading) {
        return
    }

    fileInput.value?.click()
}

/*
|--------------------------------------------------------------------------
| Просмотр файла
|--------------------------------------------------------------------------
*/

const openFileViewer = async (file) => {
    previewLoading.value = true
    previewError.value = ''
    currentFile.value = file
    showViewer.value = true

    document.body.style.overflow = 'hidden'

    await nextTick()
}

const closeFileViewer = () => {
    showViewer.value = false
    currentFile.value = null
    previewLoading.value = false
    previewError.value = ''

    document.body.style.overflow = ''
}

const handlePreviewRendered = () => {
    previewLoading.value = false
    previewError.value = ''
}

const handlePreviewError = (error) => {
    previewLoading.value = false

    previewError.value =
        error?.message ||
        'Не удалось открыть файл для предпросмотра.'
}

const reloadPreview = async () => {
    if (!currentFile.value) {
        return
    }

    const file = currentFile.value

    currentFile.value = null
    previewLoading.value = true
    previewError.value = ''

    await nextTick()

    currentFile.value = file
}

const downloadFile = (file) => {
    const url = getFileDownloadUrl(file)

    if (!url) {
        return
    }

    const link = document.createElement('a')

    link.href = url
    link.download = getFileName(file)
    link.rel = 'noopener'

    document.body.appendChild(link)
    link.click()
    link.remove()
}

const handleKeydown = (event) => {
    if (
        event.key === 'Escape' &&
        showViewer.value
    ) {
        closeFileViewer()
    }
}

/*
|--------------------------------------------------------------------------
| Даты
|--------------------------------------------------------------------------
*/

const formatDate = (isoString) => {
    if (!isoString) {
        return 'Не указано'
    }

    const date = new Date(isoString)

    if (Number.isNaN(date.getTime())) {
        return isoString
    }

    return new Intl.DateTimeFormat('ru-RU', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    }).format(date)
}

/*
|--------------------------------------------------------------------------
| Lifecycle
|--------------------------------------------------------------------------
*/

onMounted(() => {
    window.addEventListener('keydown', handleKeydown)
})

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeydown)
    document.body.style.overflow = ''
})
</script>

<template>
    <div>
        <!-- Файлы -->
        <section
            class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800"
        >
            <!-- Заголовок -->
            <div
                class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-2.5 dark:border-slate-700"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-400"
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
                                d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828L18 9.828a4 4 0 00-5.656-5.656L5.757 10.76a6 6 0 108.486 8.486L20.5 13"
                            />
                        </svg>
                    </div>

                    <div>
                        <div class="flex items-center gap-2">
                            <h3
                                class="font-semibold text-slate-900 dark:text-white"
                            >
                                Вложения
                            </h3>

                            <span
                                class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-500 dark:bg-slate-700 dark:text-slate-300"
                            >
                                {{ files.length }}
                            </span>
                        </div>

                        <p
                            class="text-xs text-slate-500 dark:text-slate-400"
                        >
                            Документы и материалы задачи
                        </p>
                    </div>
                </div>

                <button
                    v-if="canUpload"
                    type="button"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="loading"
                    @click="openFileDialog"
                >
                    <svg
                        v-if="loading"
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
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                        />
                    </svg>

                    <svg
                        v-else
                        class="h-4 w-4"
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

                    {{ loading ? 'Загрузка...' : 'Добавить' }}
                </button>
            </div>

            <!-- Компактная загрузка -->
            <div
                v-if="canUpload"
                class="px-5 pt-4"
            >
                <input
                    ref="fileInput"
                    type="file"
                    multiple
                    class="hidden"
                    accept=".pdf,.doc,.docx,.xls,.xlsx,.xlsm,.csv,.ods,.ppt,.pptx,.odp,.pps,.ppsx,.zip,.rar,.7z,.jpg,.jpeg,.png,.gif,.webp,.svg,.bmp,.ico,.txt,.md,.json,.xml,.log,.html,.css,.js,.ts,.vue,.php,.py,.mp3,.wav,.ogg,.flac,.m4a,.aac,.mp4,.avi,.mov,.mkv,.webm,.epub,.msg"
                    :disabled="loading"
                    @change="handleFileInput"
                />

                <button
                    type="button"
                    class="flex w-full items-center justify-center gap-3 rounded-xl border border-dashed px-3 py-2.5 text-left transition disabled:cursor-not-allowed disabled:opacity-60"
                    :class="
                        isDragging
                            ? 'border-blue-500 bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300'
                            : 'border-slate-300 bg-slate-50/70 text-slate-500 hover:border-blue-400 hover:bg-blue-50/50 dark:border-slate-600 dark:bg-slate-900/30 dark:text-slate-400 dark:hover:border-blue-500 dark:hover:bg-blue-500/5'
                    "
                    :disabled="loading"
                    @click="openFileDialog"
                    @dragenter.prevent="isDragging = true"
                    @dragover.prevent="isDragging = true"
                    @dragleave.prevent="isDragging = false"
                    @drop.prevent="handleDrop"
                >
                    <svg
                        class="h-4 w-4 shrink-0"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6H16a5 5 0 011 9.9M12 12v9m0-9l-3 3m3-3l3 3"
                        />
                    </svg>

                    <span class="min-w-0 text-sm">
                        <strong
                            class="font-semibold text-slate-700 dark:text-slate-200"
                        >
                            Выберите файлы
                        </strong>

                        <span class="hidden sm:inline">
                            или перетащите их сюда
                        </span>
                    </span>
                </button>
            </div>

            <!-- Список файлов -->
            <div class="p-5">
                <div
                    v-if="files.length"
                    class="compact-scrollbar max-h-80 space-y-2 overflow-y-auto overscroll-contain pr-1"
                >
                    <article
                        v-for="file in files"
                        :key="file.id"
                        class="group rounded-xl border border-slate-100 bg-slate-50/60 p-3 transition hover:border-blue-200 hover:bg-blue-50/50 dark:border-slate-700 dark:bg-slate-900/30 dark:hover:border-blue-500/40 dark:hover:bg-blue-500/5"
                    >
                    <div class="flex items-center gap-3">
                        <!-- Иконка -->
                        <button
                            type="button"
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-2xl shadow-sm ring-1 ring-slate-200 transition group-hover:scale-105 dark:bg-slate-800 dark:ring-slate-700"
                            title="Открыть файл"
                            @click="openFileViewer(file)"
                        >
                            {{ getFileIcon(file) }}
                        </button>

                        <!-- Информация -->
                        <button
                            type="button"
                            class="min-w-0 flex-1 text-left"
                            @click="openFileViewer(file)"
                        >
                            <span
                                class="block truncate text-sm font-semibold text-slate-700 transition group-hover:text-blue-600 dark:text-slate-200 dark:group-hover:text-blue-400"
                                :title="getFileName(file)"
                            >
                                {{ getFileName(file) }}
                            </span>

                            <span
                                class="mt-1 flex items-center gap-2 text-[11px] font-medium text-slate-400"
                            >
                                <span>
                                    {{ getFileExtension(file) }}
                                </span>

                                <template v-if="getFileSize(file)">
                                    <span
                                        class="h-1 w-1 rounded-full bg-slate-300 dark:bg-slate-600"
                                    ></span>

                                    <span>
                                        {{ getFileSize(file) }}
                                    </span>
                                </template>
                            </span>
                        </button>

                        <!-- Действия -->
                        <div
                            class="flex shrink-0 items-center gap-1"
                        >
                            <button
                                type="button"
                                class="rounded-lg p-2 text-slate-400 transition hover:bg-white hover:text-blue-600 hover:shadow-sm dark:hover:bg-slate-700 dark:hover:text-blue-400"
                                title="Открыть"
                                @click="openFileViewer(file)"
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
                            </button>

                            <button
                                type="button"
                                class="rounded-lg p-2 text-slate-400 transition hover:bg-white hover:text-blue-600 hover:shadow-sm dark:hover:bg-slate-700 dark:hover:text-blue-400"
                                title="Скачать"
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
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                                    />
                                </svg>
                            </button>

                            <button
                                v-if="canUpload"
                                type="button"
                                class="rounded-lg p-2 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-500/10 dark:hover:text-rose-400"
                                title="Удалить"
                                @click="emit('deleteFile', file.id)"
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
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>

                        <button
                            v-if="canSubmitForApproval(file)"
                            type="button"
                            class="mt-2 inline-flex w-full shrink-0 items-center justify-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto"
                            :disabled="submittingId === file.id"
                            @click="submitForApproval(file)"
                        >
                            <svg
                                v-if="submittingId === file.id"
                                class="h-3.5 w-3.5 animate-spin"
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
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                                />
                            </svg>

                            <svg
                                v-else
                                class="h-3.5 w-3.5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>

                            {{ submittingId === file.id ? 'Отправка...' : 'На согласование' }}
                        </button>
                    </article>
                </div>

                <!-- Пустое состояние -->
                <div
                    v-else
                    class="flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-200 px-5 py-8 text-center dark:border-slate-700"
                >
                    <div
                        class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400 dark:bg-slate-700/60"
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
                                stroke-width="1.5"
                                d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828L18 9.828a4 4 0 00-5.656-5.656L5.757 10.76a6 6 0 108.486 8.486L20.5 13"
                            />
                        </svg>
                    </div>

                    <p
                        class="text-sm font-semibold text-slate-600 dark:text-slate-300"
                    >
                        Файлов пока нет
                    </p>

                    <p
                        v-if="canUpload"
                        class="mt-1 text-xs text-slate-400"
                    >
                        Добавьте необходимые документы и материалы
                    </p>
                </div>
            </div>
        </section>

        <!-- Модальный просмотрщик -->
        <Teleport to="body">
            <Transition name="viewer">
                <div
                    v-if="showViewer"
                    class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/80 p-2 backdrop-blur-sm sm:p-5"
                    @click.self="closeFileViewer"
                >
                    <div
                        class="flex h-[96vh] w-full max-w-7xl flex-col overflow-hidden rounded-2xl border border-white/10 bg-white shadow-xl dark:bg-slate-900"
                    >
                        <!-- Заголовок -->
                        <header
                            class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-200 px-3 py-2.5 dark:border-slate-700"
                        >
                            <div
                                class="flex min-w-0 items-center gap-3"
                            >
                                <div
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-xl dark:bg-slate-800"
                                >
                                    {{ currentFileIcon }}
                                </div>

                                <div class="min-w-0">
                                    <h3
                                        class="truncate text-sm font-semibold text-slate-900 dark:text-white"
                                        :title="currentFileName"
                                    >
                                        {{ currentFileName }}
                                    </h3>

                                    <p
                                        class="mt-0.5 flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400"
                                    >
                                        <span>
                                            {{ currentFileExtension }}
                                        </span>

                                        <template v-if="currentFileSize">
                                            <span
                                                class="h-1 w-1 rounded-full bg-slate-300 dark:bg-slate-600"
                                            ></span>

                                            <span>
                                                {{ currentFileSize }}
                                            </span>
                                        </template>
                                    </p>
                                </div>
                            </div>

                            <div
                                class="flex shrink-0 items-center gap-1 sm:gap-2"
                            >
                                <button
                                    type="button"
                                    class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-blue-600 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-blue-400"
                                    title="Перезагрузить просмотр"
                                    @click="reloadPreview"
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
                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                        />
                                    </svg>
                                </button>

                                <a
                                    :href="currentDownloadUrl"
                                    :download="currentFileName"
                                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-700"
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
                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                                        />
                                    </svg>

                                    <span class="hidden sm:inline">
                                        Скачать
                                    </span>
                                </a>

                                <button
                                    type="button"
                                    class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white"
                                    title="Закрыть"
                                    @click="closeFileViewer"
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
                                            d="M6 18L18 6M6 6l12 12"
                                        />
                                    </svg>
                                </button>
                            </div>
                        </header>

                        <!-- Просмотр -->
                        <main
                            class="relative min-h-0 flex-1 overflow-hidden bg-slate-100 dark:bg-slate-950"
                        >
                            <!-- Индикатор загрузки -->
                            <div
                                v-if="previewLoading && !previewError"
                                class="pointer-events-none absolute inset-0 z-20 flex items-center justify-center bg-white/80 backdrop-blur-sm dark:bg-slate-950/80"
                            >
                                <div
                                    class="flex flex-col items-center gap-3"
                                >
                                    <svg
                                        class="h-9 w-9 animate-spin text-blue-600"
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
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                                        />
                                    </svg>

                                    <p
                                        class="text-sm font-medium text-slate-600 dark:text-slate-300"
                                    >
                                        Подготовка предпросмотра...
                                    </p>
                                </div>
                            </div>

                            <!-- Ошибка -->
                            <div
                                v-if="previewError"
                                class="absolute inset-0 z-30 flex items-center justify-center overflow-auto p-4"
                            >
                                <div
                                    class="w-full max-w-md rounded-2xl border border-rose-200 bg-white p-4 text-center shadow-xl dark:border-rose-500/20 dark:bg-slate-900"
                                >
                                    <div
                                        class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-rose-50 text-2xl dark:bg-rose-500/10"
                                    >
                                        ⚠️
                                    </div>

                                    <h4
                                        class="font-semibold text-slate-900 dark:text-white"
                                    >
                                        Не удалось открыть файл
                                    </h4>

                                    <p
                                        class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400"
                                    >
                                        {{ previewError }}
                                    </p>

                                    <div
                                        class="mt-5 flex flex-wrap justify-center gap-2"
                                    >
                                        <button
                                            type="button"
                                            class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
                                            @click="reloadPreview"
                                        >
                                            Повторить
                                        </button>

                                        <a
                                            :href="currentDownloadUrl"
                                            :download="currentFileName"
                                            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700"
                                        >
                                            Скачать файл
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- vue-files-preview -->
                            <div
                                v-if="
                                    currentFile &&
                                    currentFileUrl &&
                                    !previewError
                                "
                                class="file-preview-container h-full w-full overflow-auto bg-white dark:bg-slate-950"
                            >
                                <VueFilesPreview
                                    :key="
                                        `${currentFile.id}-${currentFileUrl}`
                                    "
                                    :url="currentFileUrl"
                                    :name="currentFileName"
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
    </div>
</template>

<style scoped>
.compact-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: rgb(203 213 225) transparent;
}

.compact-scrollbar::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

.compact-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.compact-scrollbar::-webkit-scrollbar-thumb {
    background: rgb(203 213 225);
    border-radius: 9999px;
}

.compact-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgb(148 163 184);
}

:global(.dark) .compact-scrollbar {
    scrollbar-color: rgb(71 85 105) transparent;
}

:global(.dark) .compact-scrollbar::-webkit-scrollbar-thumb {
    background: rgb(71 85 105);
}

.viewer-enter-active,
.viewer-leave-active {
    transition: opacity 180ms ease;
}

.viewer-enter-active > div,
.viewer-leave-active > div {
    transition:
        transform 180ms ease,
        opacity 180ms ease;
}

.viewer-enter-from,
.viewer-leave-to {
    opacity: 0;
}

.viewer-enter-from > div,
.viewer-leave-to > div {
    opacity: 0;
    transform: scale(0.97) translateY(8px);
}

/*
|--------------------------------------------------------------------------
| Стили контейнера vue-files-preview
|--------------------------------------------------------------------------
*/

.file-preview-container {
    scrollbar-width: thin;
    scrollbar-color: rgb(203 213 225) transparent;
}

.file-preview-container::-webkit-scrollbar {
    width: 7px;
    height: 7px;
}

.file-preview-container::-webkit-scrollbar-thumb {
    background: rgb(203 213 225);
    border-radius: 9999px;
}

.file-preview-container :deep(.vue-files-preview) {
    min-height: 100%;
}

.file-preview-container :deep(iframe) {
    min-height: 80vh;
    width: 100%;
    border: 0;
}

.file-preview-container :deep(img) {
    max-width: 100%;
}

:global(.dark) .file-preview-container {
    scrollbar-color: rgb(71 85 105) transparent;
}
</style>
