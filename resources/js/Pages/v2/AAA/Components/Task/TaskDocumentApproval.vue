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
const rejectModalOpen = ref(false)
const fileToReject = ref(null)
const rejectReason = ref('')
const expandedComments = ref(new Set())
const activeFilter = ref('all') // all, pending, approved, rejected
const searchQuery = ref('')

// === ПРАВА ===
const isExecutor = computed(() => props.task.executors?.some(u => u.id === props.currentUser.id))
const isResponsible = computed(() => props.task.responsibles?.some(u => u.id === props.currentUser.id))
const isCreator = computed(() => props.task.creator?.id === props.currentUser.id)

// === COMPUTED: Списки файлов ===
const approvalFiles = computed(() => {
    let files = props.task.files?.filter(f => f.status !== 'none') || []

    // Фильтр по статусу
    if (activeFilter.value !== 'all') {
        files = files.filter(f => f.status === activeFilter.value)
    }

    // Поиск по имени
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        files = files.filter(f => getFileName(f).toLowerCase().includes(query))
    }

    // Сортировка: сначала ожидающие, потом по дате
    return files.sort((a, b) => {
        if (a.status === 'pending' && b.status !== 'pending') return -1
        if (a.status !== 'pending' && b.status === 'pending') return 1
        return new Date(b.created_at) - new Date(a.created_at)
    })
})

const regularFiles = computed(() => props.task.files?.filter(f => f.status === 'none') || [])

const stats = computed(() => {
    const files = props.task.files?.filter(f => f.status !== 'none') || []
    return {
        total: files.length,
        pending: files.filter(f => f.status === 'pending').length,
        approved: files.filter(f => f.status === 'approved').length,
        rejected: files.filter(f => f.status === 'rejected').length
    }
})

// === FORMATTERS ===
const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('ru-RU', {
        day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit'
    })
}

const getFileName = (file) => {
    if (file.file_name && file.file_name.trim() !== '') {
        return file.file_name
    }
    if (file.file_path) {
        return file.file_path.split('/').pop()
    }
    return 'Документ без названия'
}

const getFileIcon = (file) => {
    const filename = getFileName(file)
    const ext = filename.split('.').pop().toLowerCase()

    const icons = {
        pdf: '📕',
        doc: '📘', docx: '📘',
        xls: '📊', xlsx: '📊',
        ppt: '📙', pptx: '📙',
        jpg: '🖼️', jpeg: '🖼️', png: '🖼️', gif: '🖼️',
        txt: '📄',
        zip: '📦', rar: '📦', '7z': '📦'
    }
    return icons[ext] || '📄'
}

const getFileSize = (bytes) => {
    if (!bytes) return ''
    const units = ['B', 'KB', 'MB', 'GB']
    let size = bytes
    let unitIndex = 0
    while (size >= 1024 && unitIndex < units.length - 1) {
        size /= 1024
        unitIndex++
    }
    return `${size.toFixed(1)} ${units[unitIndex]}`
}

// === UPLOAD ===
const handleFileUpload = async (e) => {
    const files = e.target.files
    if (!files.length) return

    uploading.value = true
    const fd = new FormData()
    for (let i = 0; i < files.length; i++) fd.append('files[]', files[i])
    fd.append('requires_approval', requiresApproval.value ? '1' : '0')

    try {
        await axios.post(`/api/tasks/${props.task.id}/files`, fd, {
            headers: { 'Content-Type': 'multipart/form-data' }
        })
        emit('refresh')
        e.target.value = null
        requiresApproval.value = false
    } catch (err) {
        alert('Ошибка загрузки')
    } finally {
        uploading.value = false
    }
}

// === ACTIONS ===
const deleteFile = async (id) => {
    if (!confirm('Удалить файл безвозвратно?')) return
    try {
        await axios.delete(`/api/tasks/files/${id}`)
        emit('refresh')
    } catch(e) {}
}

const approve = async (file) => {
    if (!confirm(`Утвердить документ "${file.file_name}"?`)) return
    try {
        await axios.put(`/api/files/${file.id}/approve`)
        emit('refresh')
    } catch (e) {}
}

const openRejectModal = (file) => {
    fileToReject.value = file
    rejectReason.value = ''
    rejectModalOpen.value = true
}

const submitReject = async () => {
    if (!rejectReason.value.trim()) return
    try {
        await axios.put(`/api/files/${fileToReject.value.id}/reject`, { reason: rejectReason.value })
        rejectModalOpen.value = false
        emit('refresh')
    } catch (e) {}
}

const handleReplace = async (event, fileId) => {
    const file = event.target.files[0]
    if (!file) return
    if (!confirm(`Заменить файл на "${file.name}"?`)) {
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

const toggleComment = (id) => {
    if (expandedComments.value.has(id)) expandedComments.value.delete(id)
    else expandedComments.value.add(id)
}

const getStatusBadge = (status) => {
    const badges = {
        approved: {
            text: 'Согласовано',
            class: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
            icon: '✅'
        },
        rejected: {
            text: 'На доработке',
            class: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300 border-rose-200 dark:border-rose-800',
            icon: '🛑'
        },
        pending: {
            text: 'Ждет проверки',
            class: 'bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400 border-amber-200 dark:border-amber-800',
            icon: '⏳'
        }
    }
    return badges[status] || badges.pending
}
</script>

<template>
    <div class="space-y-6">

        <!-- Статистика документооборота -->
        <div v-if="approvalFiles.length > 0" class="grid grid-cols-4 gap-3">
            <div @click="activeFilter = 'all'"
                 class="p-3 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 cursor-pointer transition-all hover:shadow-md"
                 :class="{ 'ring-2 ring-indigo-500': activeFilter === 'all' }">
                <div class="text-2xl font-bold text-slate-800 dark:text-white">{{ stats.total }}</div>
                <div class="text-xs text-slate-500">Всего</div>
            </div>
            <div @click="activeFilter = 'pending'"
                 class="p-3 rounded-xl bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 cursor-pointer transition-all hover:shadow-md"
                 :class="{ 'ring-2 ring-amber-500': activeFilter === 'pending' }">
                <div class="text-2xl font-bold text-amber-700 dark:text-amber-300">{{ stats.pending }}</div>
                <div class="text-xs text-amber-600 dark:text-amber-400">Ожидают</div>
            </div>
            <div @click="activeFilter = 'approved'"
                 class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 cursor-pointer transition-all hover:shadow-md"
                 :class="{ 'ring-2 ring-emerald-500': activeFilter === 'approved' }">
                <div class="text-2xl font-bold text-emerald-700 dark:text-emerald-300">{{ stats.approved }}</div>
                <div class="text-xs text-emerald-600 dark:text-emerald-400">Согласовано</div>
            </div>
            <div @click="activeFilter = 'rejected'"
                 class="p-3 rounded-xl bg-rose-50 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-800 cursor-pointer transition-all hover:shadow-md"
                 :class="{ 'ring-2 ring-rose-500': activeFilter === 'rejected' }">
                <div class="text-2xl font-bold text-rose-700 dark:text-rose-300">{{ stats.rejected }}</div>
                <div class="text-xs text-rose-600 dark:text-rose-400">На доработке</div>
            </div>
        </div>

        <!-- Зона загрузки -->
        <div class="relative group">
            <div class="absolute inset-0 bg-gradient-to-r from-indigo-500/10 to-purple-500/10 rounded-2xl blur-xl opacity-0 group-hover:opacity-100 transition-opacity"></div>

            <div class="relative bg-white dark:bg-slate-800 rounded-2xl border-2 border-dashed border-slate-300 dark:border-slate-700 overflow-hidden transition-all group-hover:border-indigo-400 group-hover:bg-indigo-50/50 dark:group-hover:bg-indigo-950/20">

                <input type="file" multiple class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                       accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png"
                       @change="handleFileUpload" :disabled="uploading" />

                <div class="p-8 text-center">
                    <div v-if="uploading" class="flex flex-col items-center">
                        <div class="relative">
                            <div class="w-16 h-16 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"></div>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="w-8 h-8 bg-indigo-600 rounded-full animate-pulse"></div>
                            </div>
                        </div>
                        <p class="text-sm text-indigo-600 mt-4">Загрузка файлов...</p>
                    </div>

                    <div v-else class="flex flex-col items-center">
                        <div class="w-16 h-16 mb-4 rounded-full bg-gradient-to-br from-indigo-100 to-purple-100 dark:from-indigo-900/30 dark:to-purple-900/30 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                        </div>

                        <h4 class="text-lg font-semibold text-slate-800 dark:text-white mb-2">
                            Загрузить документы
                        </h4>

                        <p class="text-sm text-slate-500 max-w-md">
                            Нажмите или перетащите файлы для загрузки. Поддерживаются PDF, Word, Excel, PowerPoint, изображения
                        </p>

                        <!-- Переключатель -->
                        <div class="flex items-center gap-3 mt-4 p-2 bg-white dark:bg-slate-700 rounded-xl shadow-sm">
                            <div class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="chkApprove" v-model="requiresApproval" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 dark:bg-slate-600 rounded-full peer peer-checked:after:translate-x-full after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border after:border-slate-300 after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                            </div>
                            <label for="chkApprove" class="text-sm font-medium text-slate-700 dark:text-slate-300 select-none cursor-pointer">
                                Требуется согласование
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Поиск и фильтры -->
        <div v-if="approvalFiles.length > 0" class="flex flex-wrap items-center gap-4">
            <div class="relative flex-1 min-w-[200px]">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" v-model="searchQuery"
                       placeholder="Поиск документов..."
                       class="w-full pl-10 pr-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200/20 transition">
            </div>

            <select v-model="activeFilter"
                    class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200/20 transition">
                <option value="all">Все документы</option>
                <option value="pending">Ожидают проверки</option>
                <option value="approved">Согласованные</option>
                <option value="rejected">На доработке</option>
            </select>
        </div>

        <!-- Документы на согласовании -->
        <div v-if="approvalFiles.length > 0" class="space-y-4">
            <TransitionGroup name="slide">
                <div v-for="file in approvalFiles" :key="file.id"
                     class="group relative bg-white dark:bg-slate-800 rounded-xl border-2 shadow-lg overflow-hidden transition-all hover:shadow-xl"
                     :class="getStatusBadge(file.status).class">

                    <!-- Декоративная полоса слева -->
                    <div class="absolute left-0 top-0 bottom-0 w-1"
                         :class="{
                             'bg-emerald-500': file.status === 'approved',
                             'bg-rose-500': file.status === 'rejected',
                             'bg-amber-500': file.status === 'pending'
                         }">
                    </div>

                    <div class="p-5 pl-6">
                        <div class="flex flex-col lg:flex-row lg:items-center gap-4">

                            <!-- Иконка и информация -->
                            <div class="flex items-start gap-4 flex-1 min-w-0">
                                <div class="w-12 h-12 rounded-xl bg-white dark:bg-slate-700 shadow-md flex items-center justify-center text-3xl transform group-hover:scale-110 transition-transform">
                                    {{ getFileIcon(file) }}
                                </div>

                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <a :href="`/api/tasks/files/${file.id}`" target="_blank"
                                           class="text-lg font-semibold text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors truncate max-w-[300px]"
                                           :title="getFileName(file)">
                                            {{ getFileName(file) }}
                                        </a>

                                        <span class="px-2 py-0.5 rounded-full text-xs font-bold border"
                                              :class="getStatusBadge(file.status).class">
                                            <span class="mr-1">{{ getStatusBadge(file.status).icon }}</span>
                                            {{ getStatusBadge(file.status).text }}
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-4 text-xs text-slate-500 mt-2">
                                        <span class="flex items-center gap-1">
                                            <span>👤</span>
                                            {{ file.user?.name }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <span>📅</span>
                                            {{ formatDate(file.created_at) }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <span>📦</span>
                                            {{ getFileSize(file.file_size) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Действия -->
                            <div class="flex items-center gap-2 lg:ml-auto">
                                <!-- Для ответственного: кнопки решения -->
                                <template v-if="isResponsible && file.status === 'pending'">
                                    <button @click="approve(file)"
                                            class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition-all hover:scale-105 flex items-center gap-1">
                                        <span>✅</span>
                                        Принять
                                    </button>
                                    <button @click="openRejectModal(file)"
                                            class="px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition-all hover:scale-105 flex items-center gap-1">
                                        <span>🛑</span>
                                        Вернуть
                                    </button>
                                </template>

                                <!-- Для исполнителя: заменить -->
                                <template v-if="isExecutor && file.status === 'rejected'">
                                    <input type="file" :id="'replace-'+file.id" class="hidden" @change="(e) => handleReplace(e, file.id)">
                                    <label :for="'replace-'+file.id"
                                           class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition-all hover:scale-105 flex items-center gap-1 cursor-pointer">
                                        <span>🔄</span>
                                        Заменить
                                    </label>
                                </template>

                                <!-- Кнопка удалить -->
                                <button v-if="(isExecutor || isResponsible || isCreator) && file.status !== 'approved'"
                                        @click="deleteFile(file.id)"
                                        class="p-2 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 transition-all"
                                        title="Удалить">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Комментарий отказа -->
                        <Transition name="fade">
                            <div v-if="file.status === 'rejected' && file.rejection_reason" class="mt-4 ml-16">
                                <div class="relative p-4 bg-rose-50 dark:bg-rose-950/30 rounded-xl border border-rose-200 dark:border-rose-800">
                                    <div class="absolute -top-2 left-6 w-4 h-4 bg-rose-50 dark:bg-rose-950/30 border-t border-l border-rose-200 dark:border-rose-800 rotate-45"></div>
                                    <div class="flex items-start gap-2">
                                        <span class="text-rose-500">💬</span>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-rose-800 dark:text-rose-200 whitespace-pre-wrap">
                                                {{ expandedComments.has(file.id) ? file.rejection_reason : file.rejection_reason.slice(0, 150) }}
                                                <span v-if="!expandedComments.has(file.id) && file.rejection_reason.length > 150">...</span>
                                            </p>
                                            <button v-if="file.rejection_reason.length > 150"
                                                    @click="toggleComment(file.id)"
                                                    class="mt-2 text-xs font-bold text-rose-600 hover:text-rose-700 dark:text-rose-400 hover:underline">
                                                {{ expandedComments.has(file.id) ? 'Свернуть' : 'Читать далее' }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </Transition>
                    </div>
                </div>
            </TransitionGroup>
        </div>

        <!-- Пустое состояние -->
        <div v-else-if="approvalFiles.length === 0" class="text-center py-12">
            <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                <svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <h4 class="text-lg font-medium text-slate-700 dark:text-slate-300 mb-2">Нет документов</h4>
            <p class="text-sm text-slate-500">Загрузите документы для согласования</p>
        </div>

        <!-- Модалка отказа -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95">

            <div v-if="rejectModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <!-- Backdrop с эффектом стекла -->
                <div class="absolute inset-0 bg-slate-900/40 dark:bg-black/60 backdrop-blur-md" @click="rejectModalOpen = false"></div>

                <!-- Modal Content -->
                <div class="relative w-full max-w-md bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">

                    <!-- Декоративная полоса -->
                    <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-rose-500 to-pink-500"></div>

                    <!-- Header -->
                    <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-rose-500 to-pink-500 flex items-center justify-center text-white text-lg shadow-lg">
                                🛑
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white">Вернуть на доработку</h3>
                                <p class="text-xs text-slate-500 mt-1">Укажите причину возврата</p>
                            </div>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="p-6">
                        <textarea v-model="rejectReason"
                                  class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:border-rose-300 focus:ring focus:ring-rose-200/20 transition h-32"
                                  placeholder="Например: Неверная дата в документе, требуется исправление..."
                                  autofocus></textarea>
                    </div>

                    <!-- Footer -->
                    <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 flex justify-end gap-3">
                        <button @click="rejectModalOpen = false"
                                class="px-4 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                            Отмена
                        </button>
                        <button @click="submitReject" :disabled="!rejectReason.trim()"
                                class="px-6 py-2 rounded-xl bg-gradient-to-r from-rose-600 to-pink-600 text-white font-medium shadow-lg shadow-rose-500/30 hover:shadow-xl hover:scale-105 transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100">
                            Вернуть документ
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

    </div>
</template>

<style scoped>
/* Анимации для списка */
.slide-enter-active,
.slide-leave-active {
    transition: all 0.3s ease;
}

.slide-enter-from {
    opacity: 0;
    transform: translateX(-30px);
}

.slide-leave-to {
    opacity: 0;
    transform: translateX(30px);
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* Кастомный скроллбар */
::-webkit-scrollbar {
    width: 4px;
}

::-webkit-scrollbar-track {
    background: transparent;
}

::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 20px;
}

.dark ::-webkit-scrollbar-thumb {
    background: #475569;
}

/* Анимация для пульсации загрузки */
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
