<script setup>
const props = defineProps({
    task: Object,
    loading: Boolean,
    canUpload: Boolean
})

const emit = defineEmits(['updateProgress', 'uploadFiles', 'deleteFile'])

const handleFile = (e) => emit('uploadFiles', e.target.files)
</script>

<template>
    <div class="space-y-6">
        <!-- Даты и Прогресс -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                <div>
                    <div class="text-xs text-gray-500 uppercase font-bold mb-1">Начало</div>
                    <div class="font-mono text-gray-900 dark:text-white">{{ task?.start_date || '—' }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 uppercase font-bold mb-1">Срок</div>
                    <div class="font-mono text-gray-900 dark:text-white">{{ task?.due_date || '—' }}</div>
                </div>
                <div>
                    <div class="flex justify-between text-xs text-gray-500 uppercase font-bold mb-2">
                        <span>Прогресс</span>
                        <span>{{ task?.progress || 0 }}%</span>
                    </div>
                    <div class="h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden mb-3">
                        <div class="h-full bg-emerald-500 transition-all duration-500" :style="{ width: (task?.progress || 0) + '%' }"></div>
                    </div>
                    <div class="flex gap-1">
                        <button v-for="n in 11" :key="n" @click="$emit('updateProgress', (n-1)*10)"
                                class="flex-1 h-2 rounded-sm bg-gray-200 dark:bg-gray-700 hover:bg-emerald-400 transition"
                                :class="{ '!bg-emerald-500': (task?.progress || 0) >= (n-1)*10 }"
                                :title="`${(n-1)*10}%`" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Файлы -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-bold text-gray-900 dark:text-white">📎 Файлы</h3>
                <div v-if="canUpload" class="relative">
                    <input type="file" multiple @change="handleFile" class="absolute inset-0 opacity-0 cursor-pointer" />
                    <button class="text-xs bg-indigo-50 text-indigo-600 px-3 py-1.5 rounded-lg font-bold hover:bg-indigo-100">
                        + Загрузить
                    </button>
                </div>
            </div>

            <div v-if="loading" class="animate-pulse space-y-2">
                <div class="h-8 bg-gray-100 rounded w-full"></div>
            </div>
            <div v-else-if="!task?.files?.length" class="text-sm text-gray-400 italic">Нет файлов</div>
            <div v-else class="flex flex-wrap gap-2">
                <div v-for="f in task.files" :key="f.id" class="flex items-center gap-2 px-3 py-2 rounded-xl bg-gray-50 dark:bg-gray-700/50 border border-gray-100 dark:border-gray-700">
                    <a :href="`/api/tasks/files/${f.id}`" class="text-sm font-medium text-blue-600 hover:underline truncate max-w-[150px]">
                        {{ f.file_name }}
                    </a>
                    <button v-if="canUpload" @click="$emit('deleteFile', f.id)" class="text-gray-400 hover:text-rose-500 transition">✕</button>
                </div>
            </div>
        </div>
    </div>
</template>
