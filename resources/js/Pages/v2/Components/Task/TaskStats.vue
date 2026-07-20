<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
    task: Object,
    loading: Boolean,
    canUpload: Boolean
})

const emit = defineEmits(['updateProgress', 'uploadFiles', 'deleteFile'])

// Состояние для просмотрщика
const showViewer = ref(false)
const currentFile = ref(null)

const handleFile = (e) => emit('uploadFiles', e.target.files)

const getFileName = (file) => {
    if (file.file_name) return file.file_name
    if (file.file_path) return file.file_path.split('/').pop()
    return 'Файл'
}

const getFileIcon = (filename) => {
    if (!filename) return '📎'
    const ext = filename.split('.').pop().toLowerCase()

    if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'].includes(ext)) return '🖼️'
    if (['pdf'].includes(ext)) return '📕'
    if (['doc', 'docx', 'txt', 'rtf', 'odt'].includes(ext)) return '📄'
    if (['xls', 'xlsx', 'csv', 'ods'].includes(ext)) return '📊'
    if (['ppt', 'pptx', 'odp'].includes(ext)) return '📽️'
    if (['zip', 'rar', '7z', 'tar', 'gz'].includes(ext)) return '📦'
    if (['mp3', 'wav', 'ogg', 'flac', 'm4a', 'aac', 'opus', 'wma'].includes(ext)) return '🎵'
    if (['mp4', 'avi', 'mov', 'mkv', 'webm'].includes(ext)) return '🎬'
    return '📎'
}

// 🔥 Computed для текущего файла
const currentFileUrl = computed(() => {
    if (!currentFile.value) return ''
    return `/api/tasks/files/${currentFile.value.id}`
})

const currentFileName = computed(() => {
    if (!currentFile.value) return ''
    return getFileName(currentFile.value)
})

const currentFileIcon = computed(() => {
    if (!currentFile.value) return '📄'
    return getFileIcon(getFileName(currentFile.value))
})

const currentFileType = computed(() => {
    if (!currentFile.value) return ''
    const name = getFileName(currentFile.value)
    const ext = name.split('.').pop().toLowerCase()
    const types = {
        'pdf': 'PDF документ',
        'doc': 'Word документ',
        'docx': 'Word документ',
        'xls': 'Excel таблица',
        'xlsx': 'Excel таблица',
        'ppt': 'PowerPoint презентация',
        'pptx': 'PowerPoint презентация',
        'jpg': 'Изображение',
        'jpeg': 'Изображение',
        'png': 'Изображение',
        'gif': 'Изображение',
        'mp3': 'Аудиофайл',
        'wav': 'Аудиофайл',
        'mp4': 'Видеофайл',
        'zip': 'Архив',
        'rar': 'Архив'
    }
    return types[ext] || ext.toUpperCase() + ' файл'
})

const currentFileSize = computed(() => {
    if (!currentFile.value || !currentFile.value.size) return '—'
    const size = currentFile.value.size
    if (size < 1024) return size + ' B'
    if (size < 1024 * 1024) return (size / 1024).toFixed(1) + ' KB'
    return (size / (1024 * 1024)).toFixed(1) + ' MB'
})

// 🔥 Проверяем, можно ли отобразить в iframe
const canIframeView = computed(() => {
    if (!currentFile.value) return false
    const name = getFileName(currentFile.value)
    const ext = name.split('.').pop().toLowerCase()
    // PDF, изображения и текстовые файлы можно отобразить в iframe
    const viewable = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'txt']
    return viewable.includes(ext)
})

// 🔥 Открыть файл в просмотрщике
const openFileViewer = (file) => {
    // Открываем в новой вкладке
    window.open(`/file/view/${file.id}`, '_blank');
};
// 🔥 Закрыть просмотрщик
const closeViewer = () => {
    showViewer.value = false
    currentFile.value = null
    document.body.style.overflow = ''
}

// Форматирование даты
const formatDate = (isoString) => {
    if (!isoString) return '—'
    const date = new Date(isoString)
    if (isNaN(date.getTime())) return isoString
    return new Intl.DateTimeFormat('ru-RU', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    }).format(date)
}

const progressColor = computed(() => {
    const p = props.task?.progress || 0
    if (p < 30) return 'bg-gray-400'
    if (p < 70) return 'bg-blue-500'
    return 'bg-emerald-500'
})
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>



<template>
    <div class="space-y-6">

        <!-- Блок 1: Сроки и Прогресс -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Карточка: Сроки (Timeline) -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col justify-between">
                <h3 class="font-bold text-gray-800 dark:text-gray-200 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    Временная шкала
                </h3>

                <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-700/30 rounded-xl p-4 border border-gray-100 dark:border-gray-700">
                    <!-- Start Date -->
                    <div class="flex flex-col">
                        <span class="text-xs text-gray-400 uppercase font-bold tracking-wider mb-1">Начало</span>
                        <!-- 2️⃣ Применяем функцию здесь -->
                        <span class="text-sm sm:text-base font-medium text-gray-700 dark:text-gray-200 whitespace-nowrap">
                            {{ formatDate(task?.start_date) }}
                        </span>
                    </div>

                    <!-- Arrow Icon -->
                    <div class="text-gray-300 dark:text-gray-500 px-2">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                    </div>

                    <!-- Due Date -->
                    <div class="flex flex-col items-end">
                        <span class="text-xs text-rose-400 uppercase font-bold tracking-wider mb-1">Срок</span>
                        <!-- 3️⃣ И здесь -->
                        <span class="text-sm sm:text-base font-medium text-gray-900 dark:text-white whitespace-nowrap" :class="{'text-rose-500': !task?.completed}">
                            {{ formatDate(task?.due_date) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Карточка: Прогресс -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col justify-between">
                <div class="flex justify-between items-end mb-4">
                    <h3 class="font-bold text-gray-800 dark:text-gray-200 flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                        Прогресс
                    </h3>
                    <span class="text-2xl font-black transition-colors duration-300"
                          :class="(task?.progress || 0) === 100 ? 'text-emerald-500' : 'text-blue-600'">
                        {{ task?.progress || 0 }}%
                    </span>
                </div>

                <div class="flex gap-1.5 h-12 w-full">
                    <button
                        v-for="n in 11"
                        :key="n"
                        @click="$emit('updateProgress', (n-1)*10)"
                        class="flex-1 rounded-md transition-all duration-300 relative group overflow-hidden"
                        :class="[
                            (task?.progress || 0) >= (n-1)*10
                                ? progressColor
                                : 'bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600',
                            (task?.progress || 0) === (n-1)*10 ? 'ring-2 ring-offset-2 ring-blue-400 dark:ring-offset-gray-800 z-10 scale-110' : ''
                        ]"
                    >
                    </button>
                </div>
                <div class="flex justify-between mt-2 text-xs text-gray-400 font-medium px-1">
                    <span>0%</span>
                    <span>50%</span>
                    <span>100%</span>
                </div>
            </div>
        </div>

        <!-- Блок 2: Файлы (код без изменений, для полноты) -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
            <h3 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span class="text-xl">📎</span> Вложения
            </h3>

            <div v-if="task?.files?.length" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 mb-6">
                <div v-for="f in task.files" :key="f.id" class="group relative aspect-square bg-gray-50 dark:bg-gray-700/50 rounded-2xl border border-gray-100 dark:border-gray-700 flex flex-col items-center justify-center p-4 transition hover:shadow-md hover:border-blue-200 dark:hover:border-blue-500/30">

                    <!-- Кнопка удаления -->
                    <button
                        v-if="canUpload"
                        @click="$emit('deleteFile', f.id)"
                        class="absolute top-2 right-2 p-1.5 rounded-full bg-white dark:bg-gray-800 text-gray-400 hover:text-rose-500 shadow-sm opacity-0 group-hover:opacity-100 transition-all transform hover:scale-110"
                        title="Удалить файл"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>

                    <!-- 🔥 Кнопка просмотра -->
                    <button
                        @click="openFileViewer(f)"
                        class="absolute top-2 left-2 p-1.5 rounded-full bg-white dark:bg-gray-800 text-gray-400 hover:text-blue-500 shadow-sm opacity-0 group-hover:opacity-100 transition-all transform hover:scale-110"
                        title="Открыть в просмотрщике"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </button>

                    <!-- Иконка файла -->
                    <div class="text-4xl mb-2 filter drop-shadow-sm transition-transform group-hover:scale-110">
                        {{ getFileIcon(getFileName(f)) }}
                    </div>

                    <div class="text-xs text-center font-medium text-gray-600 dark:text-gray-300 line-clamp-2 break-all">
                        {{ getFileName(f) }}
                    </div>

                    <!-- Кнопки действий под файлом -->
                    <div class="flex gap-1 mt-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <a :href="`/api/tasks/files/${f.id}`" target="_blank"
                           class="text-xs px-2 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded transition"
                           title="Скачать файл">
                            ⬇️ Скачать
                        </a>
                        <button
                            @click="openFileViewer(f)"
                            class="text-xs px-2 py-1 bg-green-500 hover:bg-green-600 text-white rounded transition"
                            title="Открыть в просмотрщике">
                            👁️ Просмотр
                        </button>
                    </div>
                </div>
            </div>

            <div v-else-if="!canUpload" class="text-center py-8 text-gray-400 text-sm">
                Нет прикрепленных файлов
            </div>

            <div v-if="canUpload" class="relative group">
                <input
                    type="file"
                    multiple
                    @change="handleFile"
                    accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.rar,.mp3,.wav,.ogg,.flac,.m4a,.aac"
                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                    :disabled="loading"
                />

                <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-8 flex flex-col items-center justify-center text-center transition-colors group-hover:border-blue-500 group-hover:bg-blue-50 dark:group-hover:bg-gray-700/50">
                    <div v-if="loading" class="animate-spin mb-2">
                        <svg class="w-8 h-8 text-blue-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    </div>
                    <div v-else class="text-gray-400 group-hover:text-blue-500 transition-colors mb-2">
                        <svg class="w-10 h-10 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                    </div>

                    <p class="text-sm font-medium text-gray-600 dark:text-gray-300">
                        <span class="text-blue-600 hover:underline">Нажмите</span> или перетащите файлы сюда
                    </p>
                </div>
            </div>
        </div>

        <!-- 🔥 Модальное окно для просмотра файлов -->
        <div v-if="showViewer" class="fixed inset-0 z-[100] bg-black/80 backdrop-blur-sm flex items-center justify-center p-4" @click.self="closeViewer">
            <div class="bg-white dark:bg-gray-800 rounded-2xl w-full max-w-6xl max-h-[90vh] flex flex-col shadow-2xl overflow-hidden">
                <!-- Заголовок модалки -->
                <div class="flex items-center justify-between p-4 border-b dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="text-xl">{{ currentFileIcon }}</span>
                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 truncate" :title="currentFileName">
                            {{ currentFileName }}
                        </h3>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <a :href="currentFileUrl" download class="px-3 py-1.5 text-xs bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                            Скачать
                        </a>
                        <button @click="closeViewer" class="p-2 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg transition">
                            <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                </div>

                <!-- Содержимое - iframe -->
                <div class="flex-1 min-h-[500px] bg-gray-100 dark:bg-gray-900">
                    <iframe
                        :src="currentFileUrl"
                        class="w-full h-full border-0"
                        v-if="canIframeView"
                    ></iframe>
                    <div v-else class="flex flex-col items-center justify-center h-full p-8 text-center">
                        <div class="text-6xl mb-4">{{ currentFileIcon }}</div>
                        <h4 class="text-lg font-medium text-gray-700 dark:text-gray-300 mb-2">Предварительный просмотр недоступен</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400 max-w-md">
                            Файлы этого типа не могут быть отображены в браузере. Пожалуйста, скачайте файл для просмотра.
                        </p>
                        <a :href="currentFileUrl" download class="mt-4 px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                            Скачать файл
                        </a>
                    </div>
                </div>

                <!-- Информация о файле в футере -->
                <div class="p-3 border-t dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 flex justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>Тип: {{ currentFileType || 'Неизвестно' }}</span>
                    <span>Размер: {{ currentFileSize || '—' }}</span>
                </div>
            </div>
        </div>

    </div>
</template>
