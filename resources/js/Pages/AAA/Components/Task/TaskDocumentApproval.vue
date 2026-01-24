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
const requiresApproval = ref(true) // Чекбокс "На согласование"

// Модалка отказа
const rejectModalOpen = ref(false)
const fileToReject = ref(null)
const rejectReason = ref('')

// Логика разворачивания комментариев
const expandedComments = ref(new Set()) // Используем Set для удобства

// === ПРАВА ===
const isExecutor = computed(() => props.task.executors?.some(u => u.id === props.currentUser.id))
const isResponsible = computed(() => props.task.responsibles?.some(u => u.id === props.currentUser.id))

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
    // 1. Если есть нормальное имя — возвращаем его
    if (file.file_name && file.file_name.trim() !== '') {
        return file.file_name;
    }

    // 2. Если имени нет, пытаемся вырезать из пути
    if (file.file_path) {
        return file.file_path.split('/').pop();
    }

    // 3. Если и пути нет — заглушка
    return 'Документ без названия';
};

const getFileIcon = (file) => {
    // Получаем имя через нашу новую функцию (это гарантирует строку)
    const filename = getFileName(file);

    const ext = filename.split('.').pop().toLowerCase();

    if (['pdf'].includes(ext)) return '📕';
    if (['doc', 'docx'].includes(ext)) return '📘';
    if (['xls', 'xlsx'].includes(ext)) return '📊';
    if (['ppt', 'pptx'].includes(ext)) return '📙';
    return '📄';
};

// === UPLOAD ===
const handleFileUpload = async (e) => {
    const files = e.target.files
    if (!files.length) return

    uploading.value = true
    const fd = new FormData()
    for (let i = 0; i < files.length; i++) fd.append('files[]', files[i])
    fd.append('requires_approval', requiresApproval.value ? '1' : '0')

    try {
        await axios.post(`/api/tasks/${props.task.id}/files`, fd, { headers: { 'Content-Type': 'multipart/form-data' } })
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
    if(!confirm('Удалить файл безвозвратно?')) return
    try { await axios.delete(`/api/tasks/files/${id}`); emit('refresh') } catch(e) {}
}

const approve = async (file) => {
    if(!confirm(`Утвердить документ "${file.file_name}"?`)) return
    try { await axios.put(`/api/files/${file.id}/approve`); emit('refresh') } catch (e) {}
}

// Отказ
const openRejectModal = (file) => {
    fileToReject.value = file
    rejectReason.value = ''
    rejectModalOpen.value = true
}

const submitReject = async () => {
    if(!rejectReason.value.trim()) return
    try {
        await axios.put(`/api/files/${fileToReject.value.id}/reject`, { reason: rejectReason.value })
        rejectModalOpen.value = false
        emit('refresh')
    } catch (e) {}
}

// Замена файла
const handleReplace = async (event, fileId) => {
    const file = event.target.files[0]
    if (!file) return
    if (!confirm(`Заменить файл на "${file.name}"? Статус сбросится на "Ожидает проверки".`)) {
        event.target.value = null
        return
    }

    const fd = new FormData()
    fd.append('file', file)

    try {
        await axios.post(`/api/files/${fileId}/replace`, fd, { headers: { 'Content-Type': 'multipart/form-data' } })
        emit('refresh')
    } catch (e) {
        alert('Ошибка при замене')
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
</script>

<template>
    <div class="space-y-8">

        <!-- 1. ЗОНА ЗАГРУЗКИ -->
        <div class="relative group rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/50 hover:bg-blue-50 dark:hover:bg-gray-800 transition-colors p-6 text-center">
            <h1 class="font-semibold text-gray-700 dark:text-gray-200">Файлы для которых требуется согласование</h1>
            <input
                type="file" multiple class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx"
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
                <p class="text-xs text-gray-400 mt-1">PDF, Office (до 20MB)</p>
            </div>

            <!-- Переключатель (Вынесен поверх инпута через z-index, чтобы был кликабельным) -->
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
                <span>📋</span> Документооборот
                <span class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs px-2 py-0.5 rounded-full">{{ approvalFiles.length }}</span>
            </h3>

            <div class="grid gap-4">
                <div v-for="file in approvalFiles" :key="file.id"
                     class="group relative bg-white dark:bg-gray-800 border rounded-xl p-4 shadow-sm hover:shadow-md transition-shadow"
                     :class="getStatusBadge(file.status).classes">

                    <div class="flex flex-col sm:flex-row gap-4 justify-between">

                        <!-- Инфо о файле -->
                        <div class="flex gap-3">
                            <div class="text-3xl select-none">{{ getFileIcon(file) }}</div>
                            <div class="min-w-0">
                                <a
                                    :href="`/storage/${file.file_path}`"
                                    target="_blank"
                                    class="font-bold hover:underline truncate block text-gray-900 dark:text-white"
                                    :title="getFileName(file)"
                                >
                                    <!-- Вызываем нашу безопасную функцию -->
                                    {{ getFileName(file) }}
                                </a>
                                <div class="flex flex-wrap items-center gap-2 text-xs opacity-75 mt-1">
                                    <span>👤 {{ file.user?.name }}</span>
                                    <span>•</span>
                                    <span>{{ formatDate(file.created_at) }}</span>
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
                                <!-- Для Ответственного: Кнопки решения -->
                                <div v-if="isResponsible && file.status === 'pending'" class="flex gap-2">
                                    <button @click="approve(file)" class="btn-action bg-emerald-600 hover:bg-emerald-700 text-white">
                                        ✔ Принять
                                    </button>
                                    <button @click="openRejectModal(file)" class="btn-action bg-rose-500 hover:bg-rose-600 text-white">
                                        ✖ Вернуть
                                    </button>
                                </div>

                                <!-- Для Исполнителя: Исправить -->
                                <div v-if="isExecutor && file.status === 'rejected'">
                                    <input type="file" :id="'replace-'+file.id" class="hidden" @change="(e) => handleReplace(e, file.id)">
                                    <label :for="'replace-'+file.id" class="btn-action bg-blue-600 hover:bg-blue-700 text-white cursor-pointer">
                                        🔄 Заменить
                                    </label>
                                </div>

                                <!-- Удалить (всем, если не утверждено) -->
                                <button
                                    v-if="(isExecutor || isResponsible) && file.status !== 'approved'"
                                    @click="deleteFile(file.id)"
                                    class="p-1.5 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition"
                                    title="Удалить"
                                >
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Комментарий отказа (выглядит как чат) -->
                    <div v-if="file.status === 'rejected' && file.rejection_reason" class="mt-3 relative">
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

        <!-- 3. ПРОЧИЕ ФАЙЛЫ (Опционально) -->
<!--        <div v-if="regularFiles.length > 0">-->
<!--            <h3 class="font-bold text-gray-500 text-sm uppercase tracking-wider mb-3">📎 Вложения</h3>-->
<!--            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">-->
<!--                <div v-for="file in regularFiles" :key="file.id" class="flex items-center gap-2 p-2 bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-lg shadow-sm hover:border-blue-300 transition group">-->
<!--                    <span class="text-xl">{{ getFileIcon(file.file_name) }}</span>-->
<!--                    <div class="min-w-0 flex-1">-->
<!--                        <a :href="`/storage/${file.file_path}`" target="_blank" class="text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 truncate block">-->
<!--                            {{ file.file_name }}-->
<!--                        </a>-->
<!--                    </div>-->
<!--                    <button @click="deleteFile(file.id)" class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-rose-500 p-1">✕</button>-->
<!--                </div>-->
<!--            </div>-->
<!--        </div>-->

        <!-- МОДАЛКА ОТКАЗА -->
        <div v-if="rejectModalOpen" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm flex items-center justify-center z-50 p-4 transition-opacity">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl w-full max-w-md shadow-2xl scale-100 transform transition-transform">
                <h3 class="font-bold text-lg mb-1 dark:text-white">Вернуть на доработку</h3>
                <p class="text-sm text-gray-500 mb-4">Укажите, что именно нужно исправить.</p>

                <textarea
                    v-model="rejectReason"
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
