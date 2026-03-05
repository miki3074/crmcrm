<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'

const props = defineProps(['subtask', 'user'])
const emit = defineEmits(['refresh', 'startWork'])

const showEditSubtaskModal = ref(false)
const savingSubtask = ref(false)
const editError = ref('')
const editSubtaskForm = ref({ title: '', due_date: '' })

// Форматирование даты
const formatDate = (date) => {
    if (!date) return 'Не указан'
    return new Date(date).toLocaleDateString('ru-RU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

// Проверка на просрочку
const isOverdue = computed(() => {
    if (!props.subtask.due_date || props.subtask.completed) return false
    return new Date(props.subtask.due_date) < new Date()
})

// Permissions
const canUpdateProgress = computed(() => {
    const s = props.subtask
    const u = props.user
    if (!s || !u) return false
    const p = s.task?.project || {}
    return (
        s.executors?.some(e => e.id === u.id) ||
        s.responsibles?.some(r => r.id === u.id) ||
        p.executors?.some(e => e.id === u.id) ||
        p.managers?.some(m => m.id === u.id) ||
        p.company?.user_id === u.id ||
        s.creator_id === u.id
    )
})

const canComplete = computed(() => {
    return canUpdateProgress.value && props.subtask.progress === 100 && !props.subtask.completed
})

const canStartWork = computed(() => {
    if (props.subtask.completed || props.subtask.status === 'in_work') return false
    return props.subtask.executors?.some(e => e.id === props.user?.id)
})

const canDeleteSubtask = computed(() => {
    const userId = props.user?.id
    if (!userId) return false
    return (
        userId === props.subtask.creator_id ||
        userId === props.subtask.task?.project?.company?.user_id ||
        props.subtask.task?.project?.managers?.some(m => m.id === userId)
    )
})

const canEditSubtask = computed(() => {
    return canDeleteSubtask.value || props.subtask.task?.project?.executors?.some(e => e.id === props.user.id)
})

// Статус бейдж с улучшенным дизайном
const statusBadge = computed(() => {
    if (props.subtask.completed) {
        return {
            text: 'Завершена',
            icon: '✅',
            class: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/30'
        }
    }
    if (props.subtask.status === 'in_work') {
        return {
            text: 'В работе',
            icon: '⚡',
            class: 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/30'
        }
    }
    return {
        text: 'Новая',
        icon: '🆕',
        class: 'bg-gray-50 text-gray-600 border-gray-200 dark:bg-gray-500/10 dark:text-gray-400 dark:border-gray-500/30'
    }
})

// Actions
const completeSubtask = async () => {
    if (!confirm('Завершить подзадачу?')) return
    try {
        await axios.patch(`/api/subtasks/${props.subtask.id}/complete`)
        emit('refresh')
    } catch (e) {
        alert(e?.response?.data?.message || 'Ошибка при завершении')
    }
}

const deleteSubtask = async () => {
    if (!confirm('Вы уверены, что хотите удалить подзадачу? Это действие нельзя отменить.')) return
    try {
        await axios.delete(`/api/subtasks/${props.subtask.id}`, { withCredentials: true })
        alert('Подзадача успешно удалена')
        window.history.back()
    } catch (e) {
        alert(e?.response?.data?.message || 'Ошибка при удалении')
    }
}

const openEditSubtask = () => {
    editSubtaskForm.value.title = props.subtask.title
    editSubtaskForm.value.due_date = props.subtask.due_date?.split('T')[0] || ''
    showEditSubtaskModal.value = true
}

const updateSubtask = async () => {
    if (!editSubtaskForm.value.title.trim()) {
        editError.value = 'Название обязательно'
        return
    }

    savingSubtask.value = true
    editError.value = ''

    try {
        await axios.patch(`/api/subtasks/${props.subtask.id}/update`, editSubtaskForm.value)
        emit('refresh')
        showEditSubtaskModal.value = false
    } catch (e) {
        editError.value = e?.response?.data?.message || 'Ошибка при сохранении'
    } finally {
        savingSubtask.value = false
    }
}
</script>

<template>
    <div class="bg-gradient-to-r from-gray-50 to-white dark:from-gray-800/50 dark:to-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700 shadow-sm">
        <!-- Верхняя часть с заголовком и статусом -->
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-3 flex-wrap mb-2">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white break-words">
                        {{ subtask.title }}
                    </h1>

                    <!-- Статус бейдж -->
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 text-sm font-medium rounded-full border"
                          :class="statusBadge.class">
                        <span>{{ statusBadge.icon }}</span>
                        {{ statusBadge.text }}
                    </span>
                </div>

                <!-- Мета-информация -->
                <div class="flex flex-wrap items-center gap-4 text-sm">
                    <!-- Срок -->
                    <div class="flex items-center gap-1.5 text-gray-600 dark:text-gray-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span :class="{ 'text-red-600 dark:text-red-400 font-medium': isOverdue }">
                            {{ formatDate(subtask.due_date) }}
                        </span>
                        <span v-if="isOverdue" class="ml-1 text-red-600 dark:text-red-400">(просрочено)</span>
                    </div>

                    <!-- Дата завершения -->
                    <div v-if="subtask.completed_at" class="flex items-center gap-1.5 text-gray-600 dark:text-gray-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Завершена: {{ new Date(subtask.completed_at).toLocaleString() }}</span>
                    </div>

                    <!-- Прогресс (кружочек) -->
                    <div v-if="!subtask.completed" class="flex items-center gap-2">
                        <div class="flex items-center gap-1.5 px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded-full">
                            <div class="w-2 h-2 rounded-full"
                                 :class="{
                                    'bg-red-500': (subtask.progress ?? 0) < 30,
                                    'bg-amber-500': (subtask.progress ?? 0) >= 30 && (subtask.progress ?? 0) < 70,
                                    'bg-green-500': (subtask.progress ?? 0) >= 70
                                 }"/>
                            <span class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                {{ subtask.progress ?? 0 }}% выполнения
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Кнопки действий -->
            <div class="flex flex-wrap items-center gap-2 sm:flex-shrink-0">
                <!-- Кнопка "Взять в работу" -->
                <button
                    v-if="canStartWork"
                    @click="$emit('startWork', subtask.id)"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-gradient-to-r from-sky-500 to-sky-600 hover:from-sky-600 hover:to-sky-700 text-white text-sm font-medium rounded-xl shadow-sm transition-all transform hover:scale-105 active:scale-95"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Взять в работу
                </button>

                <!-- Кнопка "Завершить" -->
                <button
                    v-if="canComplete"
                    @click="completeSubtask"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white text-sm font-medium rounded-xl shadow-sm transition-all transform hover:scale-105 active:scale-95"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Завершить
                </button>

                <!-- Кнопка "Изменить" -->
                <button
                    v-if="canEditSubtask"
                    @click="openEditSubtask"
                    class="inline-flex items-center gap-1.5 px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-xl shadow-sm transition-all"
                    title="Редактировать"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    <span class="hidden sm:inline">Изменить</span>
                </button>

                <!-- Кнопка "Удалить" -->
                <button
                    v-if="canDeleteSubtask"
                    @click="deleteSubtask"
                    class="inline-flex items-center gap-1.5 px-3 py-2 bg-white dark:bg-gray-700 border border-red-300 dark:border-red-800 hover:bg-red-50 dark:hover:bg-red-900/20 text-red-600 dark:text-red-400 text-sm font-medium rounded-xl shadow-sm transition-all"
                    title="Удалить"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    <span class="hidden sm:inline">Удалить</span>
                </button>
            </div>
        </div>

        <!-- Дополнительная информация (если есть) -->
        <div v-if="subtask.description" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-600 dark:text-gray-400 whitespace-pre-wrap">
                {{ subtask.description }}
            </p>
        </div>

        <!-- Модальное окно редактирования -->
        <Teleport to="body">
            <div v-if="showEditSubtaskModal" class="fixed inset-0 z-50 overflow-y-auto" @click.self="showEditSubtaskModal = false">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                    <!-- Затемнение -->
                    <div class="fixed inset-0 bg-black/50 transition-opacity"></div>

                    <!-- Модалка -->
                    <div class="relative inline-block bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full">
                        <!-- Заголовок -->
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                <svg class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Редактирование подзадачи
                            </h3>
                        </div>

                        <!-- Форма -->
                        <form @submit.prevent="updateSubtask" class="px-6 py-4 space-y-4">
                            <!-- Название -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Название <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="editSubtaskForm.title"
                                    type="text"
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white outline-none transition"
                                    placeholder="Введите название"
                                />
                            </div>

                            <!-- Срок -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Срок выполнения
                                </label>
                                <input
                                    v-model="editSubtaskForm.due_date"
                                    type="date"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white outline-none transition"
                                />
                            </div>

                            <!-- Ошибка -->
                            <div v-if="editError" class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                                <p class="text-sm text-red-600 dark:text-red-400">{{ editError }}</p>
                            </div>

                            <!-- Кнопки -->
                            <div class="flex justify-end gap-3 pt-4">
                                <button
                                    type="button"
                                    @click="showEditSubtaskModal = false"
                                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                                >
                                    Отмена
                                </button>
                                <button
                                    type="submit"
                                    :disabled="savingSubtask"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400 text-white rounded-lg transition"
                                >
                                    <svg v-if="savingSubtask" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ savingSubtask ? 'Сохранение...' : 'Сохранить изменения' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
/* Анимация для модального окна */
.fixed {
    animation: fadeIn 0.2s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

/* Плавные переходы */
.transition-all {
    transition: all 0.2s ease-in-out;
}
</style>
