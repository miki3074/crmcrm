<script setup>
import { computed } from 'vue'

const props = defineProps({
    task: Object,
    loading: Boolean,
    canUpload: Boolean
})

const emit = defineEmits(['updateProgress', 'uploadFiles', 'deleteFile'])

const handleFile = (e) => emit('uploadFiles', e.target.files)

// 1️⃣ Функция форматирования даты (Вариант 1: Native JS)
const formatDate = (isoString) => {
    if (!isoString) return '—'
    const date = new Date(isoString)

    // Если дата некорректная, вернем как есть
    if (isNaN(date.getTime())) return isoString

    // Настройка формата: "7 января 2026"
    return new Intl.DateTimeFormat('ru-RU', {
        day: 'numeric',
        month: 'long', // можно заменить на 'numeric' (01) или 'short' (янв.)
        year: 'numeric'
    }).format(date)
}

const progressColor = computed(() => {
    const p = props.task?.progress || 0
    if (p < 30) return 'bg-gray-400'
    if (p < 70) return 'bg-blue-500'
    return 'bg-emerald-500'
})


const getFileName = (file) => {
    if (file.file_name) return file.file_name
    if (file.file_path) return file.file_path.split('/').pop()
    return 'Файл'
}

const getFileIcon = (filename) => {
    if (!filename) return '📎'
    const ext = filename.split('.').pop().toLowerCase()
    if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) return '🖼️'
    if (['pdf'].includes(ext)) return '📕'
    if (['doc', 'docx', 'txt'].includes(ext)) return '📄'
    if (['xls', 'xlsx', 'csv'].includes(ext)) return '📊'
    if (['zip', 'rar'].includes(ext)) return '📦'
    return '📎'
}
</script>

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
                    <button
                        v-if="canUpload"
                        @click="$emit('deleteFile', f.id)"
                        class="absolute top-2 right-2 p-1.5 rounded-full bg-white dark:bg-gray-800 text-gray-400 hover:text-rose-500 shadow-sm opacity-0 group-hover:opacity-100 transition-all transform hover:scale-110"
                        title="Удалить файл"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>

                    <div class="text-4xl mb-2 filter drop-shadow-sm transition-transform group-hover:scale-110">
                        {{ getFileIcon(getFileName(f)) }}
                    </div>

                    <a :href="`/api/tasks/files/${f.id}`" target="_blank"
                       class="text-xs text-center font-medium text-gray-600 dark:text-gray-300 group-hover:text-blue-600 dark:group-hover:text-blue-400 line-clamp-2 break-all stretched-link"
                       :title="getFileName(f)">
                        {{ getFileName(f) }}
                    </a>
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
                    accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx"
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

    </div>
</template>
