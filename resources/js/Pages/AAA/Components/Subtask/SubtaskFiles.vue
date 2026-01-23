<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'

// Пропсы: подзадача и текущий пользователь
const props = defineProps(['subtask', 'user'])
const emit = defineEmits(['refresh'])

// Ссылки на DOM элементы инпутов
const fileInput = ref(null)
const replaceInput = ref(null)

// Состояния
const revisionComment = ref('') // Текст комментария
const activeFileId = ref(null)  // ID файла, у которого открыто окно ввода замечания
const fileToReplaceId = ref(null) // ID файла, который мы сейчас заменяем
const expandedComments = ref(new Set()) // ID файлов, где нажат "Показать все"

// --- ПРАВА ДОСТУПА ---

// Может ли пользователь грузить/удалять файлы (Создатель, Исполнитель, Ответственный)
const canUpload = computed(() => {
    const { subtask, user } = props
    if (!subtask || !user) return false
    return subtask.creator_id === user.id ||
        (subtask.executors || []).some(e => e.id === user.id) ||
        (subtask.responsibles || []).some(e => e.id === user.id)
})

// Является ли пользователь ОТВЕТСТВЕННЫМ (только они могут слать на доработку)
const isResponsible = computed(() => {
    const { subtask, user } = props
    if (!subtask || !user || !subtask.responsibles) return false
    return subtask.responsibles.some(r => r.id === user.id)
})

// --- ЛОГИКА ЗАГРУЗКИ / УДАЛЕНИЯ ---

const uploadFile = async (e) => {
    const file = e.target.files[0]
    if (!file) return

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
        e.target.value = '' // Сброс инпута
    }
}

const deleteFile = async (id) => {
    if (!confirm('Удалить файл?')) return
    try {
        await axios.delete(`/api/subtask-files/${id}`)
        emit('refresh')
    } catch (e) {
        alert('Ошибка удаления')
    }
}

// --- ЛОГИКА ОБНОВЛЕНИЯ (ЗАМЕНЫ) ФАЙЛА ---

const triggerReplace = (id) => {
    fileToReplaceId.value = id
    replaceInput.value.click() // Программно кликаем по скрытому инпуту
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
        // Можно добавить уведомление (toast)
    } catch (err) {
        alert(err.response?.data?.message || 'Ошибка обновления файла')
    } finally {
        e.target.value = ''
        fileToReplaceId.value = null
    }
}

// --- ЛОГИКА ДОРАБОТКИ (REVISION) ---

const openRevisionInput = (fileId) => {
    if (activeFileId.value === fileId) {
        // Если кликнули второй раз — закрываем
        activeFileId.value = null
    } else {
        activeFileId.value = fileId
        // Если уже есть комментарий, подставляем его для редактирования
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

// --- ЛОГИКА СВОРАЧИВАНИЯ ТЕКСТА ---

const toggleComment = (id) => {
    if (expandedComments.value.has(id)) {
        expandedComments.value.delete(id)
    } else {
        expandedComments.value.add(id)
    }
}
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-6">

        <!-- Заголовок и кнопка загрузки -->
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-100">📎 Файлы</h3>

            <!-- Скрытый инпут для ЗАМЕНЫ файла -->
            <input type="file" ref="replaceInput" class="hidden" @change="handleReplaceFile" />

            <!-- Кнопка загрузки нового -->
            <div v-if="canUpload">
                <input type="file" @change="uploadFile" class="hidden" ref="fileInput" />
                <button
                    @click="$refs.fileInput.click()"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition shadow"
                >
                    + Загрузить
                </button>
            </div>
        </div>

        <!-- Список файлов -->
        <ul v-if="subtask.files?.length" class="space-y-3">
            <li v-for="file in subtask.files" :key="file.id"
                class="relative bg-gray-50 dark:bg-gray-700 p-3 rounded-lg border transition-all"
                :class="file.status === 'revision'
                    ? 'border-red-300 bg-red-50 dark:bg-red-900/10 dark:border-red-800'
                    : 'border-gray-200 dark:border-gray-600'"
            >
                <div class="flex justify-between items-start gap-4">

                    <!-- Информация о файле -->
                    <div class="flex flex-col overflow-hidden">
                        <a
                            :href="`/api/subtask-files/${file.id}/download`"
                            class="text-blue-600 dark:text-blue-400 hover:underline text-sm font-medium truncate"
                            :title="file.filename"
                        >
                            📄 {{ file.filename }}
                        </a>
                        <span class="text-[11px] text-gray-400 mt-1">
                            {{ new Date(file.updated_at).toLocaleString() }}
                            <span v-if="file.created_at !== file.updated_at" class="ml-1 text-gray-400/70"><strong style="color: green">(обновлен)</strong></span>
                        </span>
                    </div>

                    <!-- Панель действий -->
                    <div class="flex items-center gap-2 shrink-0">

                        <!-- 1. Кнопка ОБНОВИТЬ (доступна загрузчикам) -->
                        <button
                            v-if="canUpload"
                            @click="triggerReplace(file.id)"
                            class="p-1.5 text-gray-500 hover:text-blue-600 hover:bg-blue-100 dark:hover:bg-blue-900 rounded transition"
                            title="Обновить файл (Заменить)"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                        </button>

                        <!-- 2. Кнопка НА ДОРАБОТКУ (доступна ответственным) -->
                        <!-- Видна всегда, меняется цвет и текст в зависимости от статуса -->
                        <button
                            v-if="isResponsible"
                            @click="openRevisionInput(file.id)"
                            class="text-xs px-2 py-1.5 rounded font-medium transition"
                            :class="file.status === 'revision'
                                ? 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-200 hover:bg-red-200'
                                : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-200 hover:bg-yellow-200'"
                        >
                            {{ file.status === 'revision' ? 'Изменить замечание' : 'На доработку' }}
                        </button>

                        <!-- 3. Кнопка УДАЛИТЬ -->
                        <button
                            v-if="canUpload"
                            @click="deleteFile(file.id)"
                            class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-100 dark:hover:bg-red-900 rounded transition"
                            title="Удалить"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                </div>

                <!-- Блок "Требуется доработка" (Комментарий) -->
                <div
                    v-if="file.status === 'revision'"
                    class="max-w-4xl mx-auto py-8 px-4 mt-3 text-sm text-red-700 dark:text-red-300 bg-red-100 dark:bg-red-900/40 p-3 rounded-md border border-red-200 dark:border-red-800"
                >
                    <div class="font-bold text-xs uppercase tracking-wide mb-1 opacity-80">⚠ Требуется доработка</div>

                    <!-- Текст с обрезкой -->
                    <div class="whitespace-pre-wrap break-words text-sm">
                        {{
                            (expandedComments.has(file.id) || !file.revision_comment || file.revision_comment.length <= 150)
                                ? file.revision_comment
                                : file.revision_comment.slice(0, 150) + '...'
                        }}
                    </div>

                    <!-- Кнопка "Показать все" -->
                    <button
                        v-if="file.revision_comment && file.revision_comment.length > 150"
                        @click.prevent="toggleComment(file.id)"
                        class="mt-2 text-xs font-bold text-red-800 dark:text-red-200 hover:underline focus:outline-none flex items-center gap-1"
                    >
                        {{ expandedComments.has(file.id) ? 'Свернуть' : 'Читать полностью ↓' }}
                    </button>
                </div>

                <!-- Форма ввода замечания (показывается при клике "На доработку") -->
                <div v-if="activeFileId === file.id" class="mt-3 animate-slideDown">
                    <textarea
                        v-model="revisionComment"
                        class="w-full text-sm p-3 border rounded-lg shadow-sm focus:ring-2 focus:ring-yellow-400 outline-none dark:bg-gray-800 dark:text-white dark:border-gray-600"
                        placeholder="Опишите, что нужно исправить в этом файле..."
                        rows="3"
                    ></textarea>
                    <div class="flex justify-end gap-2 mt-2">
                        <button
                            @click="activeFileId = null"
                            class="px-3 py-1 text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                        >
                            Отмена
                        </button>
                        <button
                            @click="sendRevision(file.id)"
                            class="px-4 py-1.5 bg-yellow-500 hover:bg-yellow-600 text-white text-xs font-bold rounded shadow transition"
                        >
                            Отправить
                        </button>
                    </div>
                </div>

            </li>
        </ul>

        <!-- Пустое состояние -->
        <div v-else class="text-center py-8 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-lg">
            <p class="text-sm text-gray-500 mb-2">Файлов нет</p>
            <button
                v-if="canUpload"
                @click="$refs.fileInput.click()"
                class="text-blue-600 hover:underline text-sm"
            >
                Загрузить первый файл
            </button>
        </div>
    </div>
</template>

<style scoped>
/* Анимация для появления формы */
.animate-slideDown {
    animation: slideDown 0.2s ease-out forwards;
    transform-origin: top;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-5px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
