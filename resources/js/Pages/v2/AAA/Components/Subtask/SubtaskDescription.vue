<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'

const props = defineProps(['subtask', 'user'])
const emit = defineEmits(['refresh'])

const showModal = ref(false)
const text = ref('')
const saving = ref(false)
const error = ref('')
const isExpanded = ref(false)

// Проверка прав на редактирование
const canEdit = computed(() => {
    if (!props.user || !props.subtask) return false
    const userId = props.user.id
    const project = props.subtask.task?.project || {}

    return (
        userId === props.subtask.creator_id ||
        userId === project.company?.user_id ||
        project.managers?.some(m => m.id === userId) ||
        project.executors?.some(e => e.id === userId) ||
        props.subtask.executors?.some(e => e.id === userId) ||
        props.subtask.responsibles?.some(r => r.id === userId)
    )
})

// Подсчет символов
const charCount = computed(() => text.value?.length || 0)
const charLimit = 5000
const charProgress = computed(() => (charCount.value / charLimit) * 100)
const isNearLimit = computed(() => charCount.value > charLimit * 0.9)

// Форматирование текста с ссылками
const formatText = (text) => {
    if (!text) return ''

    // Замена ссылок на кликабельные
    const urlRegex = /(https?:\/\/[^\s]+)/g
    return text.replace(urlRegex, url =>
        `<a href="${url}" target="_blank" class="text-indigo-600 hover:text-indigo-800 underline break-all">${url}</a>`
    )
}

const openModal = () => {
    text.value = props.subtask.description || ''
    error.value = ''
    showModal.value = true
}

const save = async () => {
    if (text.value?.length > charLimit) {
        error.value = `Превышен лимит символов (максимум ${charLimit})`
        return
    }

    saving.value = true
    error.value = ''

    try {
        await axios.patch(`/api/subtasks/${props.subtask.id}/description`, {
            description: text.value || null
        })
        emit('refresh')
        showModal.value = false
    } catch (e) {
        error.value = e?.response?.data?.message || 'Ошибка при сохранении'
    } finally {
        saving.value = false
    }
}

const clearDescription = () => {
    if (confirm('Очистить описание?')) {
        text.value = ''
    }
}
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-md transition-all group">
        <!-- Заголовок -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-transparent dark:from-gray-700/50">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="p-1.5 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Описание</h3>
                    <span v-if="subtask.description" class="text-xs text-gray-500">
                        {{ subtask.description.length }} симв.
                    </span>
                </div>

                <!-- Кнопка редактирования (для всех с правами) -->
                <button
                    v-if="canEdit"
                    @click="openModal"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-indigo-600 hover:text-indigo-700 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-900/20 dark:text-indigo-400 dark:hover:bg-indigo-900/30 rounded-lg transition-all"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Редактировать
                </button>
            </div>
        </div>

        <!-- Контент -->
        <div class="p-6">
            <div v-if="subtask.description" class="relative">
                <!-- Текст с поддержкой ссылок -->
                <div
                    class="prose prose-sm max-w-none text-gray-700 dark:text-gray-300 whitespace-pre-wrap break-words"
                    :class="{ 'line-clamp-6': !isExpanded }"
                    v-html="formatText(subtask.description)"
                ></div>

                <!-- Кнопка "Показать ещё" для длинных описаний -->
                <button
                    v-if="subtask.description.length > 300"
                    @click="isExpanded = !isExpanded"
                    class="mt-2 text-sm text-indigo-600 hover:text-indigo-700 font-medium inline-flex items-center gap-1"
                >
                    <span>{{ isExpanded ? 'Свернуть' : 'Показать полностью' }}</span>
                    <svg
                        class="w-4 h-4 transition-transform"
                        :class="{ 'rotate-180': isExpanded }"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
            </div>

            <!-- Пустое состояние -->
            <div v-else class="flex flex-col items-center justify-center py-8 text-center">
                <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-3">
                    <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                    </svg>
                </div>
                <p class="text-gray-500 dark:text-gray-400 text-sm mb-3">Описание пока не добавлено</p>
                <button
                    v-if="canEdit"
                    @click="openModal"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-900/20 dark:hover:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 text-sm font-medium rounded-lg transition-all"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Добавить описание
                </button>
            </div>
        </div>
    </div>

    <!-- Модальное окно редактирования -->
    <Teleport to="body">
        <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto" @click.self="showModal = false">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <!-- Затемнение -->
                <div class="fixed inset-0 bg-black/50 transition-opacity"></div>

                <!-- Модалка -->
                <div class="relative inline-block bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-2xl sm:w-full">
                    <!-- Заголовок -->
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                            </svg>
                            Редактирование описания
                        </h3>
                        <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Форма -->
                    <form @submit.prevent="save" class="px-6 py-4">
                        <!-- Текстовое поле -->
                        <div class="mb-3">
                            <textarea
                                v-model="text"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white outline-none transition resize-none"
                                :class="{ 'border-amber-500': isNearLimit, 'border-red-500': charCount > charLimit }"
                                rows="8"
                                placeholder="Введите описание подзадачи..."
                                maxlength="5000"
                            ></textarea>

                            <!-- Счетчик символов -->
                            <div class="flex items-center justify-between mt-2 text-xs">
                                <div class="flex items-center gap-2 flex-1">
                                    <div class="flex-1 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                        <div
                                            class="h-full transition-all duration-300"
                                            :class="{
                                                'bg-green-500': charProgress < 70,
                                                'bg-amber-500': charProgress >= 70 && charProgress <= 90,
                                                'bg-red-500': charProgress > 90
                                            }"
                                            :style="{ width: `${Math.min(charProgress, 100)}%` }"
                                        ></div>
                                    </div>
                                    <span :class="{
                                        'text-amber-600': isNearLimit,
                                        'text-red-600': charCount > charLimit,
                                        'text-gray-500': !isNearLimit && charCount <= charLimit
                                    }">
                                        {{ charCount }}/{{ charLimit }}
                                    </span>
                                </div>

                                <!-- Кнопка очистки -->
                                <button
                                    v-if="text"
                                    type="button"
                                    @click="clearDescription"
                                    class="ml-2 text-gray-400 hover:text-red-500 transition"
                                    title="Очистить"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Ошибка -->
                        <div v-if="error" class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                            <p class="text-sm text-red-600 dark:text-red-400">{{ error }}</p>
                        </div>

                        <!-- Подсказки -->
                        <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg text-xs text-gray-500 dark:text-gray-400">
                            <p class="flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Ссылки автоматически становятся кликабельными
                            </p>
                        </div>

                        <!-- Кнопки -->
                        <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <button
                                type="button"
                                @click="showModal = false"
                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                            >
                                Отмена
                            </button>
                            <button
                                type="submit"
                                :disabled="saving || charCount > charLimit"
                                class="inline-flex items-center gap-2 px-6 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400 text-white rounded-lg transition"
                            >
                                <svg v-if="saving" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ saving ? 'Сохранение...' : 'Сохранить' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
/* Анимации */
.fixed {
    animation: fadeIn 0.2s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Ограничение количества строк */
.line-clamp-6 {
    display: -webkit-box;
    -webkit-line-clamp: 6;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Стили для ссылок в описании */
.prose a {
    color: #4f46e5;
    text-decoration: underline;
    text-decoration-thickness: 1px;
    text-underline-offset: 2px;
}

.prose a:hover {
    color: #4338ca;
}

.dark .prose a {
    color: #818cf8;
}

.dark .prose a:hover {
    color: #a5b4fc;
}

/* Плавные переходы */
.transition-all {
    transition: all 0.2s ease-in-out;
}

/* Анимация для счетчика */
.h-full {
    transition: width 0.3s ease;
}
</style>
