<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
    task: Object,
    perms: Object,
    stats: Object
})

const emit = defineEmits([
    'edit', 'delete', 'description', 'back', 'finish',
    'changeExecutor', 'changeResponsible', 'addExecutor', 'addResponsible',
    'addWatcher', 'manageMembers',
    'startWork'
])

const showDescriptionModal = ref(false)
const activeActionMenu = ref(null)

/* --- Статусы и приоритеты --- */
const priorityConfig = {
    high: { label: 'Высокий', icon: '🔴', class: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300 border-rose-200 dark:border-rose-800' },
    medium: { label: 'Средний', icon: '🟡', class: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300 border-amber-200 dark:border-amber-800' },
    low: { label: 'Низкий', icon: '🟢', class: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800' }
}

const statusConfig = computed(() => {
    if (props.task?.completed) return {
        label: 'Завершена',
        icon: '✅',
        class: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800'
    }
    if (props.task?.status === 'in_work') return {
        label: 'В работе',
        icon: '⚡',
        class: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 border-blue-200 dark:border-blue-800'
    }
    return {
        label: 'Новая',
        icon: '🆕',
        class: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700'
    }
})

const daysLeft = computed(() => {
    if (!props.task?.due_date) return null
    const diff = Math.ceil((new Date(props.task.due_date) - new Date()) / (1000 * 60 * 60 * 24))
    return diff
})

const deadlineStatus = computed(() => {
    if (!daysLeft.value) return null
    if (daysLeft.value < 0) return { label: 'Просрочено', class: 'text-rose-600 bg-rose-50 dark:bg-rose-900/20 dark:text-rose-400', icon: '⚠️' }
    if (daysLeft.value <= 3) return { label: 'Срочно', class: 'text-amber-600 bg-amber-50 dark:bg-amber-900/20 dark:text-amber-400', icon: '🔥' }
    if (daysLeft.value <= 7) return { label: 'Скоро', class: 'text-blue-600 bg-blue-50 dark:bg-blue-900/20 dark:text-blue-400', icon: '⏰' }
    return null
})

const getInitials = (name) => {
    if (!name) return '?'
    return name.split(' ').map(p => p[0]).slice(0, 2).join('').toUpperCase()
}
</script>

<template>
    <div class="group relative overflow-hidden">

        <!-- Декоративный фон с градиентом -->
        <div class="absolute inset-0 bg-gradient-to-br from-indigo-600/20 via-purple-600/20 to-pink-600/20 dark:from-indigo-950/50 dark:via-purple-950/50 dark:to-pink-950/50 blur-3xl"></div>

        <!-- Основная карточка -->
        <div class="relative bg-white/90 dark:bg-slate-900/90 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/20 dark:border-slate-700/50 overflow-hidden transition-all duration-500 hover:shadow-3xl">

            <!-- Верхняя декоративная полоса с анимацией -->
            <div class="h-1.5 w-full bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 bg-[length:200%_100%] animate-gradient"></div>

            <!-- Навигационная панель -->
            <div class="px-6 pt-6 pb-2 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center justify-between">
                    <!-- Хлебные крошки -->
                    <div class="flex items-center gap-2 text-sm">
                        <button @click="$emit('back')"
                                class="group/back flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-white dark:hover:bg-slate-800 transition-all">
                            <svg class="w-4 h-4 group-hover/back:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            <span>Назад</span>
                        </button>

                        <svg class="w-4 h-4 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>

                        <span class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 shadow-sm">
                            <span>📁</span>
                            <span class="truncate max-w-[150px]">{{ task?.project?.name || 'Без проекта' }}</span>
                        </span>
                    </div>

                    <!-- ID задачи -->
                    <div class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-xs font-mono text-slate-500 dark:text-slate-400">
                        #{{ task?.id }}
                    </div>
                </div>
            </div>

            <div class="p-6 lg:p-8">
                <div class="flex flex-col xl:flex-row gap-8">

                    <!-- ЛЕВАЯ КОЛОНКА: Основная информация -->
                    <div class="flex-1 min-w-0 space-y-6">

                        <!-- Заголовок и теги -->
                        <div class="space-y-4">
                            <div class="flex items-start gap-4">
                                <h1 class="text-3xl lg:text-4xl font-bold text-slate-900 dark:text-white tracking-tight break-words">
                                    {{ task?.title || 'Загрузка...' }}
                                </h1>
                            </div>

                            <!-- Теги статуса -->
                            <div class="flex flex-wrap gap-2">
                                <!-- Статус -->
                                <span class="px-3 py-1.5 rounded-xl text-xs font-bold border flex items-center gap-1.5"
                                      :class="statusConfig.class">
                                    <span>{{ statusConfig.icon }}</span>
                                    {{ statusConfig.label }}
                                </span>

                                <!-- Приоритет -->
                                <span class="px-3 py-1.5 rounded-xl text-xs font-bold border flex items-center gap-1.5"
                                      :class="priorityConfig[task?.priority]?.class">
                                    <span>{{ priorityConfig[task?.priority]?.icon }}</span>
                                    {{ priorityConfig[task?.priority]?.label }}
                                </span>

                                <!-- Дедлайн -->
                                <span v-if="deadlineStatus"
                                      class="px-3 py-1.5 rounded-xl text-xs font-bold border flex items-center gap-1.5 animate-pulse-slow"
                                      :class="deadlineStatus.class">
                                    <span>{{ deadlineStatus.icon }}</span>
                                    {{ deadlineStatus.label }}: {{ daysLeft > 0 ? daysLeft + ' дн.' : 'сегодня' }}
                                </span>

                                <!-- Наблюдатель -->
                                <span v-if="task?.is_watcher"
                                      class="px-3 py-1.5 rounded-xl text-xs font-bold border border-purple-200 dark:border-purple-800 bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 flex items-center gap-1.5">
                                    <span>👁</span>
                                    Вы наблюдатель
                                </span>
                            </div>
                        </div>

                        <!-- Мета информация (создатель, даты) -->
                        <div class="flex flex-wrap items-center gap-4 text-sm">
                            <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-800/50">
                                <span class="text-slate-400">👤</span>
                                <span class="text-slate-600 dark:text-slate-300">{{ task?.creator?.name || 'Неизвестный' }}</span>
                            </div>
                            <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-800/50">
                                <span class="text-slate-400">📅</span>
                                <span class="text-slate-600 dark:text-slate-300">Создано: {{ new Date(task?.created_at).toLocaleDateString() }}</span>
                            </div>
                        </div>

                        <!-- Описание -->
                        <div v-if="task?.description" class="relative group/desc">
                            <div class="p-5 rounded-2xl bg-gradient-to-br from-slate-50 to-white dark:from-slate-800/50 dark:to-slate-900 border border-slate-200 dark:border-slate-700">
                                <div class="flex items-center gap-2 mb-3">
                                    <div class="w-1 h-4 bg-gradient-to-b from-indigo-500 to-purple-500 rounded-full"></div>
                                    <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Описание</h3>
                                </div>

                                <p class="text-slate-600 dark:text-slate-300 whitespace-pre-line text-base leading-relaxed">
                                    {{ task.description.length > 200 ? task.description.slice(0, 200) + '...' : task.description }}
                                </p>

                                <button v-if="task.description.length > 200"
                                        @click="showDescriptionModal = true"
                                        class="mt-3 text-sm font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 flex items-center gap-1 transition-colors">
                                    Читать полностью
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Пустое описание -->
                        <div v-else class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-dashed border-slate-200 dark:border-slate-700 text-center">
                            <p class="text-sm text-slate-400">Описание отсутствует</p>
                            <button v-if="perms.canManageTask"
                                    @click="$emit('description')"
                                    class="mt-2 text-xs text-indigo-600 hover:text-indigo-700 font-medium">
                                + Добавить описание
                            </button>
                        </div>
                    </div>

                    <!-- ПРАВАЯ КОЛОНКА: Панель управления -->
                    <div class="w-full xl:w-96 space-y-4">

                        <!-- Прогресс и статистика -->
                        <div class="bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-950/30 dark:to-purple-950/30 rounded-2xl p-5 border border-indigo-100 dark:border-indigo-900/50">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Прогресс</span>
                                <span class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ task?.progress || 0 }}%</span>
                            </div>
                            <div class="w-full h-2.5 bg-white dark:bg-slate-700 rounded-full overflow-hidden mb-4">
                                <div class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full transition-all duration-1000"
                                     :style="{ width: (task?.progress || 0) + '%' }"></div>
                            </div>

                            <!-- Быстрая статистика -->
                            <div class="grid grid-cols-3 gap-2">
                                <div class="bg-white/50 dark:bg-slate-800/50 rounded-lg p-2 text-center">
                                    <div class="text-lg font-bold text-slate-800 dark:text-white">{{ stats?.totalSubtasks || 0 }}</div>
                                    <div class="text-[10px] text-slate-500">Подзадач</div>
                                </div>
                                <div class="bg-white/50 dark:bg-slate-800/50 rounded-lg p-2 text-center">
                                    <div class="text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ stats?.completedSubtasks || 0 }}</div>
                                    <div class="text-[10px] text-slate-500">Готово</div>
                                </div>
                                <div class="bg-white/50 dark:bg-slate-800/50 rounded-lg p-2 text-center">
                                    <div class="text-lg font-bold text-amber-600 dark:text-amber-400">{{ stats?.filesCount || 0 }}</div>
                                    <div class="text-[10px] text-slate-500">Файлов</div>
                                </div>
                            </div>
                        </div>

                        <!-- Основные действия -->
                        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                            <!-- Взять в работу / Завершить -->
                            <div class="p-4 border-b border-slate-100 dark:border-slate-700">
                                <button v-if="!task?.completed && task?.status === 'new' && perms.canManageTask"
                                        @click="$emit('startWork', task.id)"
                                        class="w-full py-3.5 px-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl font-medium shadow-lg shadow-blue-500/30 hover:shadow-xl transition-all flex items-center justify-center gap-2 group/start">
                                    <svg class="w-5 h-5 group-hover/start:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Взять в работу
                                </button>

                                <button v-else-if="perms.canFinish && !task?.completed"
                                        @click="$emit('finish')"
                                        class="w-full py-3.5 px-4 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white rounded-xl font-medium shadow-lg shadow-emerald-500/30 hover:shadow-xl transition-all flex items-center justify-center gap-2 group/finish">
                                    <svg class="w-5 h-5 group-hover/finish:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Завершить задачу
                                </button>

                                <div v-else-if="task?.progress === 100 && !task?.completed"
                                     class="p-3 bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded-xl text-sm border border-amber-200 dark:border-amber-800">
                                    <div class="flex items-center gap-2">
                                        <span>⚠️</span>
                                        <span>Нельзя завершить: есть незакрытые подзадачи</span>
                                    </div>
                                </div>

                                <div v-if="task?.completed"
                                     class="p-4 bg-emerald-50 dark:bg-emerald-900/30 rounded-xl text-center border border-emerald-200 dark:border-emerald-800">
                                    <div class="text-emerald-600 dark:text-emerald-400 font-bold mb-1">✅ Задача выполнена</div>
                                    <div class="text-xs text-emerald-500 dark:text-emerald-500">
                                        {{ new Date(task.completed_at).toLocaleDateString() }}
                                    </div>
                                </div>
                            </div>

                            <!-- Редактирование и удаление -->
                            <div class="p-4 border-b border-slate-100 dark:border-slate-700">
                                <div class="grid grid-cols-2 gap-2">
                                    <button v-if="perms.canUpdate"
                                            @click="$emit('edit')"
                                            class="px-4 py-2.5 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 font-medium hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-all flex items-center justify-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                        Изменить
                                    </button>
                                    <button v-if="perms.canDelete"
                                            @click="$emit('delete')"
                                            class="px-4 py-2.5 rounded-xl bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 font-medium hover:bg-rose-100 dark:hover:bg-rose-900/50 transition-all flex items-center justify-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Удалить
                                    </button>
                                </div>
                                <button v-if="perms.canManageTask"
                                        @click="$emit('description')"
                                        class="w-full mt-2 px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-700/50 text-slate-600 dark:text-slate-400 font-medium hover:bg-slate-100 dark:hover:bg-slate-700 transition-all flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Редактировать описание
                                </button>
                            </div>

                            <!-- Управление участниками -->
                            <div v-if="perms.canManageMembers" class="p-4">
                                <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    Участники
                                </h4>

                                <div class="space-y-2">
                                    <div class="grid grid-cols-2 gap-2">
                                        <button @click="$emit('changeExecutor')"
                                                class="px-3 py-2 rounded-xl bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-300 text-xs font-medium hover:border-blue-400 hover:text-blue-600 transition-all flex items-center justify-center gap-1.5">
                                            <span>👷</span>
                                            Сменить исп.
                                        </button>
                                        <button @click="$emit('changeResponsible')"
                                                class="px-3 py-2 rounded-xl bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-300 text-xs font-medium hover:border-indigo-400 hover:text-indigo-600 transition-all flex items-center justify-center gap-1.5">
                                            <span>👨‍💼</span>
                                            Сменить отв.
                                        </button>
                                    </div>

                                    <div class="grid grid-cols-2 gap-2">
                                        <button @click="$emit('addExecutor')"
                                                class="px-3 py-2 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 text-xs font-medium hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition-all flex items-center justify-center gap-1.5">
                                            <span>➕</span>
                                            Исполнителя
                                        </button>
                                        <button @click="$emit('addResponsible')"
                                                class="px-3 py-2 rounded-xl bg-teal-50 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 text-xs font-medium hover:bg-teal-100 dark:hover:bg-teal-900/50 transition-all flex items-center justify-center gap-1.5">
                                            <span>➕</span>
                                            Ответственного
                                        </button>
                                    </div>

                                    <button @click="$emit('addWatcher')"
                                            class="w-full px-3 py-2 rounded-xl bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 text-xs font-medium hover:bg-purple-100 dark:hover:bg-purple-900/50 transition-all flex items-center justify-center gap-1.5">
                                        <span>👁</span>
                                        Добавить наблюдателя
                                    </button>

                                    <button @click="$emit('manageMembers')"
                                            class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-700/50 text-slate-600 dark:text-slate-400 text-xs font-medium hover:bg-slate-100 dark:hover:bg-slate-700 transition-all flex items-center justify-center gap-1.5 mt-2">
                                        <span>⚙️</span>
                                        Управление правами
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Информация о сроках -->
                        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4">
                            <div class="flex items-center gap-2 text-sm">
                                <span class="text-slate-400">📅 Дата начала:</span>
                                <span class="font-medium text-slate-700 dark:text-slate-300">{{ new Date(task?.start_date).toLocaleDateString() }}</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm mt-2">
                                <span class="text-slate-400">⏰ Дедлайн:</span>
                                <span class="font-medium" :class="daysLeft < 0 ? 'text-rose-600' : 'text-slate-700 dark:text-slate-300'">
                                    {{ new Date(task?.due_date).toLocaleDateString() }}
                                    <span v-if="daysLeft" class="ml-2 text-xs">({{ daysLeft > 0 ? daysLeft + ' дн.' : 'сегодня' }})</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- МОДАЛЬНОЕ ОКНО ОПИСАНИЯ -->
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95">

        <div v-if="showDescriptionModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <!-- Backdrop с эффектом стекла -->
            <div class="absolute inset-0 bg-slate-900/40 dark:bg-black/60 backdrop-blur-md" @click="showDescriptionModal = false"></div>

            <!-- Content -->
            <div class="relative w-full max-w-2xl bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden flex flex-col max-h-[85vh]">

                <!-- Декоративная полоса -->
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500"></div>

                <!-- Header -->
                <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white text-lg shadow-lg">
                                📄
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white">Полное описание задачи</h3>
                                <p class="text-xs text-slate-500 mt-1">{{ task?.title }}</p>
                            </div>
                        </div>
                        <button @click="showDescriptionModal = false"
                                class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 flex items-center justify-center text-slate-500 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Body -->
                <div class="flex-1 overflow-y-auto custom-scrollbar p-6">
                    <div class="prose prose-sm sm:prose-base dark:prose-invert max-w-none text-slate-600 dark:text-slate-300 whitespace-pre-line leading-relaxed">
                        {{ task?.description }}
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 flex justify-end">
                    <button @click="showDescriptionModal = false"
                            class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-medium shadow-lg shadow-indigo-500/30 hover:shadow-xl transition-all">
                        Закрыть
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
/* Анимации */
@keyframes gradient {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

@keyframes pulse-slow {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.animate-gradient {
    animation: gradient 3s ease infinite;
    background-size: 200% 200%;
}

.animate-pulse-slow {
    animation: pulse-slow 2s ease-in-out infinite;
}

/* Кастомный скроллбар */
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 20px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #475569;
}

/* Эффект стекла */
.backdrop-blur-xl {
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
}

.backdrop-blur-md {
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}
</style>
