<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'

const props = defineProps({
    task: Object,
    currentUser: Object
})

const emit = defineEmits(['refresh'])

const uploading = ref(false)
// 👇 Новая переменная для чекбокса
const requiresApproval = ref(false)

const rejectModalOpen = ref(false)
const fileToReject = ref(null)
const rejectReason = ref('')

// === ПРАВА ===
const isExecutor = computed(() => props.task.executors?.some(u => u.id === props.currentUser.id))
const isResponsible = computed(() => props.task.responsibles?.some(u => u.id === props.currentUser.id))

// === РАЗДЕЛЕНИЕ ФАЙЛОВ ===
// Файлы, требующие согласования (pending, approved, rejected)
const approvalFiles = computed(() => {
    return props.task.files?.filter(f => f.status !== 'none') || []
})
// Обычные файлы (none)
const regularFiles = computed(() => {
    return props.task.files?.filter(f => f.status === 'none') || []
})

// === ЗАГРУЗКА ===
const handleFileUpload = async (e) => {
    const files = e.target.files
    if (!files.length) return

    uploading.value = true
    const fd = new FormData()
    for (let i = 0; i < files.length; i++) fd.append('files[]', files[i])

    // 👇 Передаем значение галочки
    fd.append('requires_approval', requiresApproval.value ? '1' : '0')

    try {
        await axios.post(`/api/tasks/${props.task.id}/files`, fd, {
            headers: { 'Content-Type': 'multipart/form-data' }
        })
        emit('refresh')
        e.target.value = null
        requiresApproval.value = false // Сбрасываем галочку
    } catch (err) {
        alert('Ошибка загрузки')
    } finally {
        uploading.value = false
    }
}

// === ДЕЙСТВИЯ (Одобрить/Вернуть/Удалить) ===
const approve = async (file) => {
    if(!confirm('Согласовать документ?')) return
    try { await axios.put(`/api/files/${file.id}/approve`); emit('refresh') } catch (e) {}
}

const openRejectModal = (file) => {
    fileToReject.value = file; rejectReason.value = ''; rejectModalOpen.value = true
}

const submitReject = async () => {
    if(!rejectReason.value) return
    try {
        await axios.put(`/api/files/${fileToReject.value.id}/reject`, { reason: rejectReason.value })
        rejectModalOpen.value = false; emit('refresh')
    } catch (e) {}
}

const deleteFile = async (id) => {
    if(!confirm('Удалить файл?')) return
    try { await axios.delete(`/api/tasks/files/${id}`); emit('refresh') } catch(e) {}
}

// Хелперы
const statusColor = (s) => {
    if(s === 'approved') return 'bg-green-100 text-green-700 border-green-200'
    if(s === 'rejected') return 'bg-red-100 text-red-700 border-red-200'
    return 'bg-amber-100 text-amber-700 border-amber-200'
}
const statusText = (s) => {
    if(s === 'approved') return '✅ Согласовано'
    if(s === 'rejected') return '❌ На доработке'
    return '⏳ Ждет проверки'
}

const expandedComments = ref([])

const toggleComment = (id) => {
    if (expandedComments.value.includes(id)) {
        // Если уже есть - удаляем (сворачиваем)
        expandedComments.value = expandedComments.value.filter(itemId => itemId !== id)
    } else {
        // Если нет - добавляем (разворачиваем)
        expandedComments.value.push(id)
    }
}

// Хелпер: нужно ли обрезать текст? (например, если он длиннее 60 символов)
const isLongText = (text) => text && text.length > 60


const handleReplace = async (event, fileId) => {
    const file = event.target.files[0]
    if (!file) return

    if (!confirm(`Заменить файл на "${file.name}" и отправить на повторную проверку?`)) {
        event.target.value = null // Сброс, если передумал
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
        alert('Ошибка при замене файла')
    }
}
</script>

<template>
    <div class="space-y-6">

        <!-- 1. ЗОНА ЗАГРУЗКИ (Только для исполнителей) -->
        <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg border border-dashed border-gray-300 dark:border-gray-600">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex-1">
                    <label class="cursor-pointer flex items-center gap-2 text-emerald-600 font-medium hover:underline">
                        <span>📤 Загрузить файлы</span>
                        <input type="file" multiple class="hidden"
                               accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx"
                               @change="handleFileUpload" :disabled="uploading">
                    </label>
                    <p class="text-xs text-gray-500 mt-1">PDF, Office (max 20MB)</p>
                </div>

                <!-- Галочка "На согласование" -->
                <div class="flex items-center gap-2">
                    <input type="checkbox" id="chkApprove" v-model="requiresApproval" class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500">
                    <label for="chkApprove" class="text-sm text-gray-700 dark:text-gray-300 select-none cursor-pointer">
                        Отправить на согласование
                    </label>
                </div>
            </div>
            <div v-if="uploading" class="text-xs text-center mt-2 text-gray-500">Загрузка...</div>
        </div>

        <!-- 2. БЛОК: ДОКУМЕНТЫ НА СОГЛАСОВАНИИ -->
        <div v-if="approvalFiles.length > 0">
            <h4 class="font-bold text-sm uppercase text-gray-500 mb-2 tracking-wider">📜 Документы на подпись</h4>
            <div class="space-y-3">
                <div v-for="file in approvalFiles" :key="file.id"
                     class="flex flex-col md:flex-row justify-between items-start md:items-center p-3 border rounded-lg bg-white dark:bg-gray-800 transition"
                     :class="statusColor(file.status)">

                    <div class="flex items-center gap-3">
                        <div class="text-2xl">📄</div>
                        <div>
                            <a :href="`/storage/${file.file_path}`" target="_blank" class="font-semibold hover:underline break-all">
                                {{ file.file_name }}
                            </a>
                            <div class="text-xs opacity-75 mt-0.5">
                                {{ file.user?.name }} • {{ new Date(file.created_at).toLocaleDateString() }}
                            </div>
                            <div v-if="file.status === 'rejected' && file.rejection_reason" class="mt-1">
                                <div class="text-xs font-bold text-red-800 bg-red-50 border border-red-100 p-2 rounded inline-block max-w-full break-words">
                                    <span class="mr-1">❗</span>

                                    <!-- Если текст короткий или развернут — показываем полностью -->
                                    <span v-if="!isLongText(file.rejection_reason) || expandedComments.includes(file.id)">
            {{ file.rejection_reason }}
        </span>

                                    <!-- Если текст длинный и свернут — показываем начало -->
                                    <span v-else>
            {{ file.rejection_reason.slice(0, 60) }}...
        </span>

                                    <!-- Кнопка переключения -->
                                    <button
                                        v-if="isLongText(file.rejection_reason)"
                                        @click.stop="toggleComment(file.id)"
                                        class="ml-1 text-blue-600 hover:text-blue-800 hover:underline focus:outline-none cursor-pointer"
                                    >
                                        {{ expandedComments.includes(file.id) ? 'Скрыть' : 'Ещё' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Внутри цикла v-for="file in approvalFiles" -->

                    <div class="mt-2 md:mt-0 flex items-center gap-2 self-end md:self-center">

                        <!-- Бейдж статуса -->
                        <span class="px-2 py-1 rounded text-xs font-bold border bg-white/60">
        {{ statusText(file.status) }}
    </span>

                        <!-- === БЛОК ДЛЯ СОГЛАСУЮЩЕГО (Ответственный) === -->
                        <!-- Показываем кнопки, если статус pending (после замены он снова станет pending) -->
                        <div v-if="isResponsible && file.status === 'pending'" class="flex gap-1">
                            <button @click="approve(file)" title="Одобрить" class="p-1.5 bg-green-600 text-white rounded hover:bg-green-700 text-xs flex items-center gap-1">
                                ✔ Принять
                            </button>
                            <button @click="openRejectModal(file)" title="Вернуть" class="p-1.5 bg-red-500 text-white rounded hover:bg-red-600 text-xs flex items-center gap-1">
                                ✖ Вернуть
                            </button>
                        </div>

                        <!-- === БЛОК ДЛЯ ИСПОЛНИТЕЛЯ (Исправление) === -->
                        <!-- Если файл отклонен (rejected) -->
                        <div v-if="isExecutor && file.status === 'rejected'" class="relative">
                            <!-- Скрытый инпут, уникальный для каждого файла -->
                            <input
                                type="file"
                                :id="'replace-' + file.id"
                                class="hidden"
                                accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx"
                                @change="(e) => handleReplace(e, file.id)"
                            >

                            <!-- Кнопка, которая нажимает на скрытый инпут -->
                            <label :for="'replace-' + file.id" class="cursor-pointer bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded text-xs shadow flex items-center gap-1 transition">
                                🔄 Исправить
                            </label>
                        </div>

                        <!-- Кнопка удаления (доступна, если не утверждено) -->
                        <button v-if="isExecutor && file.status !== 'approved'" @click="deleteFile(file.id)" class="text-gray-400 hover:text-red-500 px-2" title="Удалить">
                            🗑
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. БЛОК: ПРОЧИЕ ФАЙЛЫ (Обычные вложения) -->
<!--        <div v-if="regularFiles.length > 0">-->
<!--            <h4 class="font-bold text-sm uppercase text-gray-500 mb-2 tracking-wider mt-6">📎 Вложения</h4>-->
<!--            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">-->
<!--                <div v-for="file in regularFiles" :key="file.id" class="flex items-center justify-between p-2 border rounded bg-white dark:bg-gray-800 hover:shadow-sm">-->
<!--                    <div class="flex items-center gap-2 overflow-hidden">-->
<!--                        <span class="text-gray-400 text-xl">📎</span>-->
<!--                        <div class="truncate">-->
<!--                            <a :href="`/storage/${file.file_path}`" target="_blank" class="text-sm text-blue-600 hover:underline block truncate">-->
<!--                                {{ file.file_name }}-->
<!--                            </a>-->
<!--                            <span class="text-xs text-gray-400">{{ file.user?.name }}</span>-->
<!--                        </div>-->
<!--                    </div>-->
<!--                    <button v-if="isExecutor || isResponsible" @click="deleteFile(file.id)" class="text-gray-400 hover:text-red-500 p-1">🗑</button>-->
<!--                </div>-->
<!--            </div>-->
<!--        </div>-->

        <div v-if="!regularFiles.length && !approvalFiles.length" class="text-center text-gray-400 text-sm py-4">
            Нет файлов
        </div>

        <!-- Модалка отказа -->
        <div v-if="rejectModalOpen" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg w-full max-w-md shadow-2xl">
                <h3 class="font-bold mb-2 dark:text-white">Причина возврата</h3>
                <textarea v-model="rejectReason" class="w-full border p-2 rounded h-24 dark:bg-gray-700 dark:text-white" placeholder="Комментарий..."></textarea>
                <div class="flex justify-end gap-2 mt-4">
                    <button @click="rejectModalOpen = false" class="px-3 py-1 border rounded dark:text-gray-300">Отмена</button>
                    <button @click="submitReject" class="px-3 py-1 bg-red-600 text-white rounded">Вернуть</button>
                </div>
            </div>
        </div>

    </div>
</template>
