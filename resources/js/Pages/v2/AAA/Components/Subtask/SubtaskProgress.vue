<script setup>
import { computed, ref } from 'vue'
import axios from 'axios'

const props = defineProps(['subtask', 'user'])
const emit = defineEmits(['refresh'])

const isEditing = ref(false)
const tempProgress = ref(0)
const tempStartDate = ref('')
const tempDueDate = ref('')
const saving = ref(false)

// Права доступа
const canUpdateProgress = computed(() => {
    const { subtask, user } = props
    if (!subtask || !user) return false

    return (
        subtask.creator_id === user.id ||
        subtask.executors?.some(e => e.id === user.id) ||
        subtask.responsibles?.some(r => r.id === user.id)
    )
})

const canEditDates = computed(() => {
    const { subtask, user } = props
    if (!subtask || !user) return false

    return (
        subtask.creator_id === user.id ||
        subtask.task?.project?.managers?.some(m => m.id === user.id) ||
        subtask.task?.project?.company?.user_id === user.id
    )
})

// Форматирование дат
const formatDate = (date) => {
    if (!date) return 'Не указана'
    return new Date(date).toLocaleDateString('ru-RU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

const getProgressColor = (progress) => {
    if (progress < 30) return 'from-red-500 to-red-600'
    if (progress < 70) return 'from-amber-500 to-amber-600'
    return 'from-emerald-500 to-emerald-600'
}

const getProgressStatus = (progress) => {
    if (progress === 0) return 'Не начато'
    if (progress < 30) return 'Начато'
    if (progress < 70) return 'В процессе'
    if (progress < 100) return 'Почти готово'
    return 'Завершено'
}

// Обновление прогресса
const updateProgress = async (val) => {
    if (!canUpdateProgress.value || props.subtask.completed) return

    saving.value = true
    try {
        await axios.patch(`/api/subtasks/${props.subtask.id}/progress`, { progress: val })
        emit('refresh')
    } catch (e) {
        alert(e?.response?.data?.message || 'Ошибка обновления прогресса')
    } finally {
        saving.value = false
    }
}

// Обновление дат
const startEditing = () => {
    tempProgress.value = props.subtask.progress || 0
    tempStartDate.value = props.subtask.start_date?.split('T')[0] || ''
    tempDueDate.value = props.subtask.due_date?.split('T')[0] || ''
    isEditing.value = true
}

const saveDates = async () => {
    saving.value = true
    try {
        await axios.patch(`/api/subtasks/${props.subtask.id}/dates`, {
            start_date: tempStartDate.value || null,
            due_date: tempDueDate.value || null
        })
        emit('refresh')
        isEditing.value = false
    } catch (e) {
        alert(e?.response?.data?.message || 'Ошибка обновления дат')
    } finally {
        saving.value = false
    }
}

const cancelEditing = () => {
    isEditing.value = false
}
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-md transition-all">
        <!-- Заголовок -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-transparent dark:from-gray-700/50">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="p-1.5 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Прогресс и сроки</h3>
                </div>

                <!-- Кнопка редактирования дат -->
                <button v-if="canEditDates && !isEditing"
                        @click="startEditing"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-indigo-600 hover:text-indigo-700 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-900/20 dark:text-indigo-400 dark:hover:bg-indigo-900/30 rounded-lg transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                    Редактировать
                </button>
            </div>
        </div>

        <!-- Контент -->
        <div class="p-6 space-y-6">
            <!-- Прогресс с круговым индикатором -->
            <div class="flex items-center gap-6">
                <!-- Круговой прогресс -->
                <div class="relative w-24 h-24 flex-shrink-0">
                    <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                        <!-- Фон -->
                        <circle class="text-gray-200 dark:text-gray-700" stroke-width="8" stroke="currentColor" fill="transparent" r="40" cx="50" cy="50"/>
                        <!-- Прогресс -->
                        <circle class="transition-all duration-500"
                                :stroke="`url(#gradient-${subtask.id})`"
                                stroke-width="8"
                                stroke-linecap="round"
                                fill="transparent"
                                r="40"
                                cx="50"
                                cy="50"
                                :stroke-dasharray="251.2"
                                :stroke-dashoffset="251.2 * (1 - (subtask.progress || 0) / 100)"/>

                        <!-- Градиент -->
                        <defs>
                            <linearGradient :id="`gradient-${subtask.id}`" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" :stop-color="subtask.progress < 30 ? '#ef4444' : subtask.progress < 70 ? '#f59e0b' : '#10b981'" />
                                <stop offset="100%" :stop-color="subtask.progress < 30 ? '#dc2626' : subtask.progress < 70 ? '#d97706' : '#059669'" />
                            </linearGradient>
                        </defs>
                    </svg>
                    <!-- Процент в центре -->
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-2xl font-bold" :class="{
                            'text-red-600 dark:text-red-400': subtask.progress < 30,
                            'text-amber-600 dark:text-amber-400': subtask.progress >= 30 && subtask.progress < 70,
                            'text-emerald-600 dark:text-emerald-400': subtask.progress >= 70
                        }">
                            {{ subtask.progress || 0 }}%
                        </span>
                    </div>
                </div>

                <!-- Статус и информация -->
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Статус:</span>
                        <span class="px-2.5 py-1 text-xs font-medium rounded-full"
                              :class="{
                                  'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': subtask.progress < 30,
                                  'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400': subtask.progress >= 30 && subtask.progress < 70,
                                  'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400': subtask.progress >= 70 && subtask.progress < 100,
                                  'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': subtask.progress === 100
                              }">
                            {{ getProgressStatus(subtask.progress || 0) }}
                        </span>
                    </div>

                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        <span v-if="subtask.completed_at" class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Завершена: {{ formatDate(subtask.completed_at) }}
                        </span>
                        <span v-else-if="subtask.progress === 100" class="flex items-center gap-1 text-emerald-600">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Готово к завершению
                        </span>
                    </div>
                </div>
            </div>

            <!-- Даты (редактирование или просмотр) -->
            <div v-if="!isEditing" class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-start gap-3">
                    <div class="p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Начало</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ formatDate(subtask.start_date) }}
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <div class="p-2 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                        <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Окончание</p>
                        <p class="text-sm font-medium" :class="{
                            'text-red-600 dark:text-red-400': new Date(subtask.due_date) < new Date() && !subtask.completed,
                            'text-gray-900 dark:text-white': new Date(subtask.due_date) >= new Date() || subtask.completed
                        }">
                            {{ formatDate(subtask.due_date) }}
                            <span v-if="new Date(subtask.due_date) < new Date() && !subtask.completed"
                                  class="ml-2 text-xs bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 px-2 py-0.5 rounded-full">
                                Просрочено
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Редактирование дат -->
            <div v-else class="space-y-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Дата начала
                        </label>
                        <input type="date" v-model="tempStartDate"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Дата окончания
                        </label>
                        <input type="date" v-model="tempDueDate"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <button @click="cancelEditing"
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        Отмена
                    </button>
                    <button @click="saveDates"
                            :disabled="saving"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400 text-white rounded-lg transition">
                        <svg v-if="saving" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ saving ? 'Сохранение...' : 'Сохранить' }}
                    </button>
                </div>
            </div>

            <!-- Полоса прогресса (альтернативный вид) -->
            <div v-if="canUpdateProgress && !subtask.completed" class="space-y-2">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Изменить прогресс:</span>
                    <span class="text-xs text-gray-500">Кликните на ячейку</span>
                </div>

                <div class="flex gap-1 h-10">
                    <div v-for="n in 10" :key="n"
                         @click="updateProgress(n * 10)"
                         class="relative flex-1 group cursor-pointer"
                         :title="`${n * 10}%`">
                        <!-- Фон -->
                        <div class="absolute inset-0 bg-gray-200 dark:bg-gray-700 rounded-lg transition-all group-hover:opacity-80"
                             :class="{ 'opacity-0': (subtask.progress || 0) >= n * 10 }"></div>
                        <!-- Прогресс -->
                        <div class="absolute inset-0 rounded-lg transition-all"
                             :class="{
                                 'bg-gradient-to-r from-red-500 to-red-600': n * 10 <= 30,
                                 'bg-gradient-to-r from-amber-500 to-amber-600': n * 10 > 30 && n * 10 <= 70,
                                 'bg-gradient-to-r from-emerald-500 to-emerald-600': n * 10 > 70
                             }"
                             :style="{ width: (subtask.progress || 0) >= n * 10 ? '100%' : '0%' }"></div>
                        <!-- Номер -->
                        <span class="absolute inset-0 flex items-center justify-center text-xs font-medium text-white mix-blend-difference opacity-0 group-hover:opacity-100 transition">
                            {{ n * 10 }}%
                        </span>
                    </div>
                </div>

                <!-- Подписи -->
                <div class="flex justify-between text-xs text-gray-500 px-1">
                    <span>0%</span>
                    <span>25%</span>
                    <span>50%</span>
                    <span>75%</span>
                    <span>100%</span>
                </div>
            </div>

            <!-- Блок для завершенных задач -->
            <div v-if="subtask.completed" class="mt-4 p-4 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg border border-emerald-200 dark:border-emerald-800">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/40 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-emerald-800 dark:text-emerald-200">Подзадача завершена</p>
                        <p class="text-xs text-emerald-600 dark:text-emerald-400">
                            {{ formatDate(subtask.completed_at) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Анимации */
.transition-all {
    transition: all 0.2s ease-in-out;
}

/* Для кругового прогресса */
circle {
    transition: stroke-dashoffset 0.5s ease;
}

/* Hover эффекты */
.group:hover .group-hover\:opacity-100 {
    opacity: 1;
}

.group:hover .group-hover\:opacity-80 {
    opacity: 0.8;
}

/* Кастомные стили для дат */
input[type="date"] {
    color-scheme: light dark;
}
</style>
