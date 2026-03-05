<script setup>
import { computed } from 'vue'

const props = defineProps({
    task: Object,
    loading: Boolean,
    canUpload: Boolean
})

const emit = defineEmits(['updateProgress', 'uploadFiles', 'deleteFile'])

const handleFile = (e) => emit('uploadFiles', e.target.files)

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

// Цвет прогресса
const progressColor = computed(() => {
    const p = props.task?.progress || 0
    if (p < 30) return 'from-gray-400 to-gray-500'
    if (p < 70) return 'from-blue-500 to-indigo-500'
    return 'from-emerald-500 to-teal-500'
})

// Статус дедлайна
const deadlineStatus = computed(() => {
    if (!props.task?.due_date) return null
    const diff = Math.ceil((new Date(props.task.due_date) - new Date()) / (1000 * 60 * 60 * 24))
    if (diff < 0) return { label: 'Просрочено', color: 'rose', icon: '⚠️' }
    if (diff <= 3) return { label: 'Срочно', color: 'amber', icon: '🔥' }
    if (diff <= 7) return { label: 'Скоро', color: 'blue', icon: '⏰' }
    return null
})

// Прогресс подзадач
const subtaskProgress = computed(() => {
    if (!props.task?.subtasks?.length) return 0
    const completed = props.task.subtasks.filter(s => s.completed).length
    return Math.round((completed / props.task.subtasks.length) * 100)
})

// Функции для файлов
const getFileName = (file) => {
    if (file.file_name) return file.file_name
    if (file.file_path) return file.file_path.split('/').pop()
    return 'Файл'
}

const getFileIcon = (filename) => {
    if (!filename) return '📎'
    const ext = filename.split('.').pop().toLowerCase()
    if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(ext)) return '🖼️'
    if (['pdf'].includes(ext)) return '📕'
    if (['doc', 'docx', 'txt', 'rtf'].includes(ext)) return '📄'
    if (['xls', 'xlsx', 'csv'].includes(ext)) return '📊'
    if (['ppt', 'pptx'].includes(ext)) return '📽️'
    if (['zip', 'rar', '7z', 'tar', 'gz'].includes(ext)) return '📦'
    if (['mp3', 'wav', 'ogg'].includes(ext)) return '🎵'
    if (['mp4', 'avi', 'mov', 'mkv'].includes(ext)) return '🎬'
    return '📎'
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
</script>

<template>
    <div class="space-y-6">

        <!-- Блок 1: Сроки и Прогресс (современный дизайн) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Карточка: Временная шкала -->
            <div class="group relative bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-xl transition-all duration-300">
                <!-- Декоративная полоса -->
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-500 to-indigo-500"></div>

                <div class="p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-500 flex items-center justify-center text-white shadow-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-slate-800 dark:text-white">Временная шкала</h3>
                    </div>

                    <!-- Timeline -->
                    <div class="relative mt-6">
                        <!-- Линия времени -->
                        <div class="absolute top-1/2 left-0 w-full h-0.5 bg-slate-200 dark:bg-slate-700 -translate-y-1/2"></div>

                        <div class="relative flex items-center justify-between">
                            <!-- Старт -->
                            <div class="relative flex flex-col items-center">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-emerald-500 to-teal-500 flex items-center justify-center text-white shadow-lg mb-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <span class="text-xs text-slate-500 font-medium mb-1">Старт</span>
                                <span class="text-sm font-semibold text-slate-800 dark:text-white whitespace-nowrap">
                                    {{ formatDate(task?.start_date) }}
                                </span>
                            </div>



                            <!-- Дедлайн -->
                            <div class="relative flex flex-col items-center">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-amber-500 to-orange-500 flex items-center justify-center text-white shadow-lg mb-2"
                                     :class="{ 'animate-pulse-slow': deadlineStatus }">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <span class="text-xs text-slate-500 font-medium mb-1">Дедлайн</span>
                                <span class="text-sm font-semibold whitespace-nowrap"
                                      :class="deadlineStatus ? `text-${deadlineStatus.color}-600 dark:text-${deadlineStatus.color}-400` : 'text-slate-800 dark:text-white'">
                                    {{ formatDate(task?.due_date) }}
                                </span>
                                <span v-if="deadlineStatus"
                                      class="absolute -top-2 -right-2 px-2 py-0.5 rounded-full text-[10px] font-bold"
                                      :class="`bg-${deadlineStatus.color}-100 text-${deadlineStatus.color}-700 dark:bg-${deadlineStatus.color}-900/30 dark:text-${deadlineStatus.color}-300`">
                                    {{ deadlineStatus.icon }} {{ deadlineStatus.label }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Карточка: Прогресс -->
            <div class="group relative bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-xl transition-all duration-300">
                <!-- Декоративная полоса -->
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-emerald-500 to-teal-500"></div>

                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-emerald-500 to-teal-500 flex items-center justify-center text-white shadow-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-slate-800 dark:text-white">Прогресс</h3>
                        </div>
                        <div class="relative">
                            <span class="text-3xl font-bold bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-transparent">
                                {{ task?.progress || 0 }}%
                            </span>
                        </div>
                    </div>

                    <!-- Прогресс-бар с шагами -->
                    <div class="space-y-3">
                        <!-- Основной прогресс -->
                        <div class="relative h-3 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                            <div class="absolute top-0 left-0 h-full rounded-full bg-gradient-to-r transition-all duration-1000"
                                 :class="progressColor"
                                 :style="{ width: (task?.progress || 0) + '%' }">
                            </div>

                            <!-- Маркеры -->
                            <div class="absolute top-0 left-0 w-full h-full flex justify-between px-1">
                                <div v-for="i in 10" :key="i"
                                     class="w-0.5 h-full bg-white/30"
                                     :style="{ left: (i * 10) + '%' }">
                                </div>
                            </div>
                        </div>

                        <!-- Подсказки прогресса -->
                        <div class="flex justify-between text-xs text-slate-500">
                            <span>Начало</span>
                            <span class="flex items-center gap-1">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                Подзадачи: {{ subtaskProgress }}%
                            </span>
                            <span>Завершение</span>
                        </div>
                    </div>

                    <!-- Кнопки быстрого прогресса -->
                    <div class="grid grid-cols-5 gap-1 mt-4">
                        <button v-for="n in 5" :key="n"
                                @click="$emit('updateProgress', n * 20)"
                                class="py-1.5 text-xs font-medium rounded-lg transition-all"
                                :class="(task?.progress || 0) >= n * 20
                                    ? 'bg-gradient-to-r from-emerald-500 to-teal-500 text-white shadow-md'
                                    : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-600'">
                            {{ n * 20 }}%
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Блок 2: Файлы (современный дизайн) -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
            <!-- Декоративная полоса -->
            <div class="h-1 w-full bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500"></div>

            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white shadow-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-slate-800 dark:text-white">Вложения</h3>
                            <p class="text-xs text-slate-500 mt-0.5">{{ task?.files?.length || 0 }} файлов</p>
                        </div>
                    </div>

                    <!-- Сортировка (опционально) -->
                    <select class="px-3 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-xs">
                        <option>По дате</option>
                        <option>По размеру</option>
                        <option>По имени</option>
                    </select>
                </div>

                <!-- Сетка файлов -->
                <div v-if="task?.files?.length" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 mb-6">
                    <div v-for="f in task.files" :key="f.id"
                         class="group relative bg-gradient-to-br from-slate-50 to-white dark:from-slate-800 dark:to-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-xl hover:scale-105 transition-all duration-300">

                        <!-- Превью для изображений -->
                        <div class="aspect-square relative">
                            <div class="absolute inset-0 flex flex-col items-center justify-center p-4">
                                <span class="text-5xl mb-2 filter drop-shadow-lg transform group-hover:scale-110 transition-transform">
                                    {{ getFileIcon(getFileName(f)) }}
                                </span>

                                <span class="text-xs font-medium text-slate-700 dark:text-slate-300 text-center line-clamp-2 mt-2">
                                    {{ getFileName(f) }}
                                </span>

                                <span class="text-[10px] text-slate-400 mt-1">
                                    {{ getFileSize(f.file_size) }}
                                </span>
                            </div>

                            <!-- Оверлей при наведении -->
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-900/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end justify-between p-3">
                                <a :href="`/api/tasks/files/${f.id}`" target="_blank"
                                   class="p-1.5 rounded-lg bg-white/20 backdrop-blur-sm text-white hover:bg-white/30 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                </a>

                                <button v-if="canUpload"
                                        @click="$emit('deleteFile', f.id)"
                                        class="p-1.5 rounded-lg bg-white/20 backdrop-blur-sm text-white hover:bg-rose-500/50 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Пустое состояние -->
                <div v-else-if="!canUpload"
                     class="flex flex-col items-center justify-center py-12 text-center">
                    <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                        <svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                        </svg>
                    </div>
                    <p class="text-sm text-slate-500">Нет прикрепленных файлов</p>
                </div>

                <!-- Загрузчик файлов -->
                <div v-if="canUpload" class="relative group">
                    <input type="file" multiple @change="handleFile"
                           class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                           :disabled="loading"
                           accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.zip,.rar" />

                    <div class="relative overflow-hidden rounded-xl border-2 border-dashed transition-all duration-300"
                         :class="loading
                             ? 'border-indigo-300 bg-indigo-50 dark:bg-indigo-950/30'
                             : 'border-slate-300 dark:border-slate-600 group-hover:border-indigo-400 group-hover:bg-indigo-50 dark:group-hover:bg-indigo-950/30'">

                        <!-- Прогресс загрузки (можно добавить позже) -->
                        <div v-if="loading" class="absolute top-0 left-0 h-full bg-indigo-500/20 animate-pulse" style="width: 60%"></div>

                        <div class="relative py-8 flex flex-col items-center justify-center text-center">
                            <div v-if="loading" class="mb-3">
                                <svg class="w-10 h-10 text-indigo-500 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                            <div v-else class="mb-3 text-slate-400 group-hover:text-indigo-500 transition-colors">
                                <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                            </div>

                            <p class="text-sm font-medium text-slate-600 dark:text-slate-300">
                                <span class="text-indigo-600 dark:text-indigo-400 font-semibold hover:underline">Нажмите</span>
                                <span v-if="!loading"> или перетащите файлы сюда</span>
                            </p>
                            <p class="text-xs text-slate-400 mt-1">
                                Поддерживаются: PDF, DOC, XLS, JPG, PNG, ZIP (макс. 50 МБ)
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Анимации */
@keyframes pulse-slow {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.05); opacity: 0.8; }
}

.animate-pulse-slow {
    animation: pulse-slow 2s ease-in-out infinite;
}

/* Прогресс-бар */
@keyframes progressPulse {
    0% { opacity: 0.5; }
    50% { opacity: 1; }
    100% { opacity: 0.5; }
}

/* Ограничение текста */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Адаптивность */
@media (max-width: 640px) {
    .grid-cols-5 {
        grid-template-columns: repeat(5, 1fr);
    }
}
</style>
