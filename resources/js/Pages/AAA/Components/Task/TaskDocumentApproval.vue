<script setup>
import { computed, ref } from 'vue'
import axios from 'axios'

const props = defineProps({
    task: { type: Object, required: true },
    currentUser: { type: Object, default: null },
})

const emit = defineEmits(['refresh'])

const state = ref({
    uploading: false,
    replacing: null,
    deleting: null,
    approving: null,
})

const requiresApproval = ref(true)
const expandedComments = ref(new Set())
const comment = ref({ fileId: null, text: '', sending: false })
const rejection = ref({ open: false, file: null, text: '', sending: false })

const ACCEPT = '.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.rar,.7z,.mp3,.wav,.ogg,.flac,.m4a,.aac,.jpg,.jpeg,.png,.gif,.webp,.mp4,.webm'

const FILE_GROUPS = {
    image: ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'],
    audio: ['mp3', 'wav', 'ogg', 'flac', 'm4a', 'aac', 'opus', 'wma'],
    video: ['mp4', 'avi', 'mov', 'mkv', 'webm'],
    document: ['doc', 'docx', 'txt', 'rtf', 'odt'],
    table: ['xls', 'xlsx', 'csv', 'ods'],
    presentation: ['ppt', 'pptx', 'odp'],
    archive: ['zip', 'rar', '7z', 'tar', 'gz'],
    pdf: ['pdf'],
}

const FILE_ICONS = {
    image: '🖼️', audio: '🎵', video: '🎬', document: '📘',
    table: '📊', presentation: '📙', archive: '📦', pdf: '📕', file: '📄',
}

const STATUS = {
    pending: {
        text: 'Ждёт проверки', icon: '⏳',
        card: 'border-blue-200 bg-blue-50/60 dark:border-blue-900 dark:bg-blue-900/10',
        badge: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
    },
    rejected: {
        text: 'На доработке', icon: '🛑',
        card: 'border-rose-200 bg-rose-50/60 dark:border-rose-900 dark:bg-rose-900/10',
        badge: 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
    },
    replacement: {
        text: 'Файл заменён', icon: '🔄',
        card: 'border-indigo-200 bg-indigo-50/60 dark:border-indigo-900 dark:bg-indigo-900/10',
        badge: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300',
    },
    approved: {
        text: 'Согласовано', icon: '✅',
        card: 'border-emerald-200 bg-emerald-50/60 dark:border-emerald-900 dark:bg-emerald-900/10',
        badge: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
    },
    none: {
        text: 'Без согласования', icon: '📄',
        card: 'border-slate-200 bg-slate-50/60 dark:border-slate-700 dark:bg-slate-900/30',
        badge: 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
    },
}

const permissions = computed(() => {
    const id = Number(props.currentUser?.id)
    const task = props.task || {}
    const inList = list => list?.some(user => Number(user.id) === id)
    const participant = inList(task.executors) || inList(task.responsibles) || Number(task.creator_id) === id

    return {
        participant,
        canReplace: inList(task.executors) || inList(task.responsibles),
    }
})

const can = (action, file) => {
    const participant = permissions.value.participant
    const reviewable = ['pending', 'replacement'].includes(file.status)

    return {
        approve: participant && reviewable,
        reject: participant && reviewable,
        replace: permissions.value.canReplace && file.status === 'rejected',
        delete: participant && file.status !== 'approved',
        comment: participant && file.status === 'rejected',
    }[action]
}

const groupedFiles = computed(() => {
    const result = { newFiles: [], working: [], approved: [], regular: [] }

    for (const file of props.task?.files || []) {
        if (file.status === 'pending') result.newFiles.push(file)
        else if (['rejected', 'replacement'].includes(file.status)) result.working.push(file)
        else if (file.status === 'approved') result.approved.push(file)
        else result.regular.push(file)
    }

    return result
})

const sections = computed(() => [
    {
        key: 'newFiles', title: 'Новые', subtitle: 'Ожидают проверки', icon: '✦',
        color: 'blue', files: groupedFiles.value.newFiles,
        emptyTitle: 'Новых документов нет', emptyText: 'Загруженные документы появятся здесь',
    },
    {
        key: 'working', title: 'В работе', subtitle: 'Доработка и повторная проверка', icon: '⚙',
        color: 'indigo', files: groupedFiles.value.working,
        emptyTitle: 'Документов в работе нет', emptyText: 'Здесь будут возвращённые и заменённые файлы',
    },
    {
        key: 'approved', title: 'Согласованные', subtitle: 'Проверенные документы', icon: '✓',
        color: 'emerald', files: groupedFiles.value.approved,
        emptyTitle: 'Согласованных файлов нет', emptyText: 'После проверки документы появятся здесь',
    },
])

const stats = computed(() => sections.value.map(section => ({
    label: section.title,
    value: section.files.length,
    icon: section.icon,
    tone: {
        blue: 'border-blue-200 bg-blue-50/70 text-blue-700 dark:border-blue-900 dark:bg-blue-900/10 dark:text-blue-300',
        indigo: 'border-indigo-200 bg-indigo-50/70 text-indigo-700 dark:border-indigo-900 dark:bg-indigo-900/10 dark:text-indigo-300',
        emerald: 'border-emerald-200 bg-emerald-50/70 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-900/10 dark:text-emerald-300',
    }[section.color],
})))

const getFileName = file => file.file_name?.trim() || file.filename?.trim() || file.file_path?.split('/').pop() || 'Документ без названия'
const getExtension = file => getFileName(file).split('.').pop()?.toLowerCase() || ''
const getFileType = file => Object.entries(FILE_GROUPS).find(([, extensions]) => extensions.includes(getExtension(file)))?.[0] || 'file'
const getFileIcon = file => FILE_ICONS[getFileType(file)]
const getStatus = status => STATUS[status] || STATUS.none
const fileUrl = file => `/api/tasks/files/${file.id}`
const openFile = file => window.open(fileUrl(file), '_blank', 'noopener,noreferrer')

const formatSize = value => {
    const bytes = Number(value)
    if (!bytes) return ''
    if (bytes < 1024) return `${bytes} Б`
    if (bytes < 1024 ** 2) return `${(bytes / 1024).toFixed(1)} КБ`
    return `${(bytes / 1024 ** 2).toFixed(2)} МБ`
}

const formatDate = value => {
    const date = new Date(value)
    return value && !Number.isNaN(date.getTime())
        ? date.toLocaleString('ru-RU', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })
        : 'Дата не указана'
}

const request = async (callback, message, refresh = true) => {
    try {
        await callback()
        if (refresh) emit('refresh')
        return true
    } catch (error) {
        console.error(error)
        alert(error.response?.data?.message || message)
        return false
    }
}

const uploadFiles = async event => {
    const input = event.target
    const selected = [...(input.files || [])]
    if (!selected.length) return

    const data = new FormData()
    selected.forEach(file => data.append('files[]', file, file.name))
    data.append('requires_approval', requiresApproval.value ? '1' : '0')
    state.value.uploading = true

    try {
        await axios.post(`/api/tasks/${props.task.id}/files`, data)
        input.value = ''
        requiresApproval.value = true
        emit('refresh')
    } catch (error) {
        const errors = error.response?.data?.errors
        if (!errors) alert(error.response?.data?.message || 'Ошибка загрузки файлов')
        else {
            const messages = Object.entries(errors).flatMap(([field, items]) => {
                const index = Number(field.match(/^files\.(\d+)$/)?.[1])
                const name = Number.isInteger(index) && selected[index] ? selected[index].name : field
                return items.map(item => `${name}: ${item}`)
            })
            alert(messages.join('\n'))
        }
    } finally {
        state.value.uploading = false
    }
}

const runFileAction = async (type, file, config) => {
    if (config.confirm && !confirm(config.confirm)) return
    state.value[type] = file.id
    await request(config.request, config.error)
    state.value[type] = null
}

const deleteFile = file => runFileAction('deleting', file, {
    confirm: 'Удалить файл безвозвратно?',
    request: () => axios.delete(`/api/tasks/files/${file.id}`),
    error: 'Не удалось удалить файл',
})

const approveFile = file => runFileAction('approving', file, {
    confirm: `Согласовать документ «${getFileName(file)}»?`,
    request: () => axios.put(`/api/files/${file.id}/approve`),
    error: 'Не удалось согласовать файл',
})

const openRejectModal = file => rejection.value = { open: true, file, text: '', sending: false }
const closeRejectModal = () => rejection.value = { open: false, file: null, text: '', sending: false }

const rejectFile = async () => {
    const { file, sending } = rejection.value
    const text = rejection.value.text.trim()
    if (!file || !text || sending) return

    rejection.value.sending = true
    const success = await request(
        () => axios.put(`/api/files/${file.id}/reject`, { comment: text }),
        'Не удалось вернуть файл на доработку',
    )
    rejection.value.sending = false
    if (success) closeRejectModal()
}

const replaceFile = async (event, file) => {
    const input = event.target
    const selected = input.files?.[0]
    if (!selected) return

    if (!confirm(`Заменить файл на «${selected.name}» (${formatSize(selected.size)})?`)) {
        input.value = ''
        return
    }

    const data = new FormData()
    data.append('file', selected)
    state.value.replacing = file.id
    await request(() => axios.post(`/api/files/${file.id}/replace`, data), 'Ошибка при замене файла')
    state.value.replacing = null
    input.value = ''
}

const startComment = fileId => {
    if (comment.value.fileId !== fileId) comment.value = { fileId, text: '', sending: false }
}

const addComment = async file => {
    const text = comment.value.text.trim()
    if (!text || comment.value.sending || file.status !== 'rejected') return

    comment.value.sending = true
    const success = await request(
        () => axios.post(`/api/files/${file.id}/comments`, { comment: text, type: 'feedback' }),
        'Ошибка добавления комментария',
    )
    comment.value = success
        ? { fileId: null, text: '', sending: false }
        : { ...comment.value, sending: false }
}

const deleteComment = async id => {
    if (confirm('Удалить комментарий?')) {
        await request(() => axios.delete(`/api/file-comments/${id}`), 'Ошибка удаления комментария')
    }
}

const toggleComment = id => {
    const items = new Set(expandedComments.value)
    items.has(id) ? items.delete(id) : items.add(id)
    expandedComments.value = items
}

const sectionClasses = section => ({
    blue: {
        border: 'border-blue-200 dark:border-blue-900',
        header: 'border-blue-100 bg-blue-50/60 dark:border-blue-900 dark:bg-blue-900/10',
        icon: 'bg-blue-100 text-blue-600 dark:bg-blue-900/50 dark:text-blue-300',
        badge: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
    },
    indigo: {
        border: 'border-indigo-200 dark:border-indigo-900',
        header: 'border-indigo-100 bg-indigo-50/60 dark:border-indigo-900 dark:bg-indigo-900/10',
        icon: 'bg-indigo-100 text-indigo-600 dark:bg-indigo-900/50 dark:text-indigo-300',
        badge: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300',
    },
    emerald: {
        border: 'border-emerald-200 dark:border-emerald-900',
        header: 'border-emerald-100 bg-emerald-50/60 dark:border-emerald-900 dark:bg-emerald-900/10',
        icon: 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/50 dark:text-emerald-300',
        badge: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
    },
}[section.color])
</script>

<template>
    <div class="space-y-4">
        <section class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:border-indigo-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-800">
            <input
                type="file"
                multiple
                class="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0 disabled:cursor-not-allowed"
                :accept="ACCEPT"
                :disabled="state.uploading"
                @change="uploadFiles"
            >

            <div class="flex min-h-[88px] items-center gap-3 p-4">
                <div class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-300">
                    <span v-if="state.uploading" class="h-5 w-5 animate-spin rounded-full border-2 border-current border-t-transparent" />
                    <span v-else class="text-xl">↑</span>
                </div>

                <div class="min-w-0 flex-1">
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">
                        {{ state.uploading ? 'Загрузка файлов…' : 'Добавить документы' }}
                    </h2>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                        Нажмите на блок или перетащите файлы
                    </p>
                </div>

                  <span

                    class="hidden rounded-lg bg-indigo-600

                           px-3 py-2 text-xs font-bold

                           text-white sm:block"

                >

                    Выбрать

                </span>
            </div>
        </section>

        <div class="grid gap-3 sm:grid-cols-3">
            <div v-for="item in stats" :key="item.label" class="flex items-center gap-3 rounded-xl border p-3" :class="item.tone">
                <div class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-white/60 text-lg dark:bg-slate-800/40">{{ item.icon }}</div>
                <div>
                    <div class="text-xs font-medium opacity-80">{{ item.label }}</div>
                    <div class="mt-0.5 text-xl font-black">{{ item.value }}</div>
                </div>
            </div>
        </div>

        <div class="grid items-start gap-4 xl:grid-cols-3">
            <section
                v-for="section in sections"
                :key="section.key"
                class="overflow-hidden rounded-2xl border bg-white shadow-sm dark:bg-slate-800"
                :class="sectionClasses(section).border"
            >
                <header class="flex items-center justify-between border-b px-4 py-3" :class="sectionClasses(section).header">
                    <div class="flex items-center gap-2">
                        <span class="grid h-8 w-8 place-items-center rounded-lg" :class="sectionClasses(section).icon">{{ section.icon }}</span>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ section.title }}</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ section.subtitle }}</p>
                        </div>
                    </div>
                    <span class="count-badge" :class="sectionClasses(section).badge">{{ section.files.length }}</span>
                </header>

                <div class="custom-scrollbar max-h-[700px] overflow-y-auto p-3">
                    <div v-if="section.files.length" class="space-y-3">
                        <article
                            v-for="file in section.files"
                            :key="file.id"
                            class="rounded-xl border p-3 transition hover:shadow-sm"
                            :class="getStatus(file.status).card"
                        >
                            <div class="flex items-start gap-3">
                                <button type="button" class="file-icon" title="Открыть файл" @click="openFile(file)">
                                    {{ getFileIcon(file) }}
                                </button>

                                <div class="min-w-0 flex-1">
                                    <button type="button" class="block max-w-full truncate text-left text-sm font-bold text-slate-900 hover:text-indigo-600 dark:text-white" :title="getFileName(file)" @click="openFile(file)">
                                        {{ getFileName(file) }}
                                    </button>
                                    <div class="mt-1 flex flex-wrap items-center gap-1.5 text-[11px] text-slate-500 dark:text-slate-400">
                                        <span>{{ file.user?.name || 'Неизвестный' }}</span>
                                        <span>•</span>
                                        <span>{{ formatDate(file.updated_at || file.created_at) }}</span>
                                        <template v-if="file.size"><span>•</span><span>{{ formatSize(file.size) }}</span></template>
                                    </div>
                                </div>

                                <span class="status-badge" :class="getStatus(file.status).badge">
                                    {{ getStatus(file.status).icon }} {{ getStatus(file.status).text }}
                                </span>
                            </div>

                            <audio v-if="getFileType(file) === 'audio'" :src="fileUrl(file)" controls controlslist="nodownload" class="mt-3 h-9 w-full" preload="metadata" />
                            <img v-else-if="getFileType(file) === 'image'" :src="fileUrl(file)" :alt="getFileName(file)" class="mt-3 max-h-40 w-full cursor-pointer rounded-lg object-cover hover:opacity-90" @click="openFile(file)">

                            <div v-if="file.comments?.length" class="mt-3 space-y-2 border-t border-black/5 pt-3 dark:border-white/10">
                                <div class="text-xs font-semibold text-slate-500 dark:text-slate-400">💬 Комментарии: {{ file.comments.length }}</div>
                                <div v-for="item in file.comments" :key="item.id" class="rounded-lg bg-white/70 p-3 text-sm dark:bg-slate-800/70">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="font-semibold text-slate-700 dark:text-slate-200">{{ item.user?.name || 'Неизвестный' }}</span>
                                                <span class="text-[11px] text-slate-400">{{ formatDate(item.created_at) }}</span>
                                                <span v-if="item.type === 'rejection'" class="comment-badge bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300">Возврат</span>
                                                <span v-else-if="item.type === 'feedback'" class="comment-badge bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300">Замечание</span>
                                            </div>
                                            <p class="mt-1 whitespace-pre-wrap break-words text-slate-600 dark:text-slate-400">{{ item.comment }}</p>
                                        </div>
                                        <button v-if="Number(item.user_id) === Number(currentUser?.id)" type="button" class="rounded p-1 text-slate-400 hover:bg-rose-50 hover:text-rose-500" @click="deleteComment(item.id)">✕</button>
                                    </div>
                                </div>
                            </div>

                            <div v-if="file.status === 'rejected' && file.rejection_reason && !file.comments?.length" class="mt-3 rounded-lg border border-rose-200 bg-white/60 p-3 text-sm text-rose-800 dark:border-rose-800 dark:bg-slate-800/60 dark:text-rose-300">
                                <strong>Причина возврата:</strong>
                                <span class="ml-1 whitespace-pre-wrap">{{ expandedComments.has(file.id) ? file.rejection_reason : file.rejection_reason.slice(0, 100) }}</span>
                                <span v-if="!expandedComments.has(file.id) && file.rejection_reason.length > 100">...</span>
                                <button v-if="file.rejection_reason.length > 100" type="button" class="ml-2 text-xs font-bold text-rose-600 hover:underline" @click="toggleComment(file.id)">
                                    {{ expandedComments.has(file.id) ? 'Свернуть' : 'Читать далее' }}
                                </button>
                            </div>

                            <div v-if="can('comment', file)" class="mt-3 rounded-xl border border-amber-200 bg-amber-50/70 p-3 dark:border-amber-800 dark:bg-amber-900/20">
                                <div class="flex flex-col gap-2 sm:flex-row">
                                    <input
                                        :value="comment.fileId === file.id ? comment.text : ''"
                                        type="text"
                                        placeholder="Напишите замечание..."
                                        class="min-w-0 flex-1 rounded-lg border border-amber-300 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-amber-500 dark:border-amber-700 dark:bg-slate-800 dark:text-white"
                                        @focus="startComment(file.id)"
                                        @input="startComment(file.id); comment.text = $event.target.value"
                                        @keydown.enter="addComment(file)"
                                    >
                                    <button type="button" class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600 disabled:opacity-50" :disabled="comment.sending && comment.fileId === file.id" @click="addComment(file)">
                                        {{ comment.sending && comment.fileId === file.id ? 'Отправка...' : 'Отправить' }}
                                    </button>
                                </div>
                            </div>

                            <div v-if="section.key !== 'approved'" class="mt-3 flex flex-wrap justify-end gap-2">
                                <div v-if="can('replace', file)">
                                    <input :id="`replace-${file.id}`" type="file" class="hidden" :accept="ACCEPT" @change="replaceFile($event, file)">
                                    <label :for="`replace-${file.id}`" class="btn-action cursor-pointer bg-indigo-600 text-white hover:bg-indigo-700">
                                        {{ state.replacing === file.id ? 'Замена...' : '🔄 Заменить' }}
                                    </label>
                                </div>

                                <button v-if="can('approve', file)" type="button" class="btn-action bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-50" :disabled="state.approving === file.id" @click="approveFile(file)">
                                    {{ state.approving === file.id ? 'Согласование...' : '✔ Согласовать' }}
                                </button>

                                <button v-if="can('reject', file)" type="button" class="btn-action bg-rose-500 text-white hover:bg-rose-600" @click="openRejectModal(file)">✖ Вернуть</button>

                                <button v-if="can('delete', file)" type="button" class="btn-action bg-white text-rose-600 ring-1 ring-rose-200 hover:bg-rose-50 dark:bg-slate-800 dark:ring-rose-900 disabled:opacity-50" :disabled="state.deleting === file.id" @click="deleteFile(file)">
                                    {{ state.deleting === file.id ? 'Удаление...' : '🗑 Удалить' }}
                                </button>
                            </div>
                        </article>
                    </div>

                    <div v-else class="empty-state">
                        <div class="grid h-14 w-14 place-items-center rounded-full bg-slate-50 text-2xl text-slate-400 dark:bg-slate-900/40">{{ section.icon }}</div>
                        <div class="mt-3 font-semibold text-slate-600 dark:text-slate-300">{{ section.emptyTitle }}</div>
                        <p class="mt-1 text-sm text-slate-400">{{ section.emptyText }}</p>
                    </div>
                </div>
            </section>
        </div>

        <section v-if="groupedFiles.regular.length" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <header class="flex items-center justify-between border-b border-slate-100 px-4 py-3 dark:border-slate-700">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Обычные файлы</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Не требуют согласования</p>
                </div>
                <span class="count-badge">{{ groupedFiles.regular.length }}</span>
            </header>

            <div class="grid gap-2 p-3 sm:grid-cols-2 xl:grid-cols-3">
                <article v-for="file in groupedFiles.regular" :key="file.id" class="flex items-center gap-3 rounded-xl border border-slate-100 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-900/30">
                    <button type="button" class="file-icon h-10 w-10 text-xl" @click="openFile(file)">{{ getFileIcon(file) }}</button>
                    <div class="min-w-0 flex-1">
                        <button type="button" class="block max-w-full truncate text-left text-sm font-semibold text-slate-800 hover:text-indigo-600 dark:text-slate-100" @click="openFile(file)">{{ getFileName(file) }}</button>
                        <p class="mt-1 text-[11px] text-slate-400">{{ formatDate(file.created_at) }}<span v-if="file.size"> · {{ formatSize(file.size) }}</span></p>
                    </div>
                    <button v-if="can('delete', file)" type="button" class="rounded-lg p-2 text-slate-400 hover:bg-rose-50 hover:text-rose-500" @click="deleteFile(file)">🗑</button>
                </article>
            </div>
        </section>

        <Teleport to="body">
            <div v-if="rejection.open" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm" @click.self="closeRejectModal">
                <div class="w-full max-w-md rounded-2xl bg-white p-5 shadow-2xl dark:bg-slate-800">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Вернуть на доработку</h3>
                    <p class="mt-1 text-sm text-slate-500">Укажите, что именно необходимо исправить.</p>

                    <div v-if="rejection.file" class="mt-3 flex items-center gap-2 rounded-xl bg-slate-50 p-3 dark:bg-slate-700/60">
                        <span>{{ getFileIcon(rejection.file) }}</span>
                        <span class="min-w-0 truncate text-sm font-semibold text-slate-700 dark:text-slate-200">{{ getFileName(rejection.file) }}</span>
                    </div>

                    <textarea v-model="rejection.text" class="mt-4 h-28 w-full resize-none rounded-xl border border-slate-300 p-3 text-sm outline-none focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 dark:border-slate-600 dark:bg-slate-700 dark:text-white" placeholder="Например: неверная дата в документе..." autofocus />

                    <div class="mt-4 flex justify-end gap-2">
                        <button type="button" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700" @click="closeRejectModal">Отмена</button>
                        <button type="button" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-bold text-white hover:bg-rose-700 disabled:opacity-50" :disabled="!rejection.text.trim() || rejection.sending" @click="rejectFile">
                            {{ rejection.sending ? 'Отправка...' : 'Вернуть документ' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
.btn-action { @apply inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold shadow-sm transition hover:-translate-y-0.5 active:translate-y-0; }
.file-icon { @apply grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-white text-2xl shadow-sm dark:bg-slate-700; }
.status-badge { @apply shrink-0 rounded-full px-2 py-1 text-[10px] font-bold; }
.comment-badge { @apply rounded-full px-2 py-0.5 text-[10px]; }
.count-badge { @apply rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700 dark:bg-slate-700 dark:text-slate-200; }
.empty-state { @apply flex min-h-[240px] flex-col items-center justify-center px-4 text-center; }
.custom-scrollbar { scrollbar-width: thin; scrollbar-color: rgb(203 213 225) transparent; }
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { border-radius: 9999px; background: rgb(203 213 225); }
:global(.dark) .custom-scrollbar { scrollbar-color: rgb(71 85 105) transparent; }
:global(.dark) .custom-scrollbar::-webkit-scrollbar-thumb { background: rgb(71 85 105); }
</style>
