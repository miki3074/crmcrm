<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
    task: Object,
    perms: Object
})

const emit = defineEmits([
    'edit', 'delete', 'description', 'back', 'finish',
    'changeExecutor', 'changeResponsible', 'addExecutor', 'addResponsible',
    'addWatcher', 'manageMembers',
    'startWork'
])

/* --- Логика цветов и бейджей --- */
const priorityBadge = (p) => {
    if (p === 'high') return 'bg-rose-50 text-rose-600 ring-rose-500/20'
    if (p === 'medium') return 'bg-amber-50 text-amber-600 ring-amber-500/20'
    return 'bg-emerald-50 text-emerald-600 ring-emerald-500/20'
}
const priorityLabel = (p) => (p === 'high' ? 'Высокая' : p === 'medium' ? 'Средняя' : 'Обычная')

const statusBadge = computed(() => {
    if (props.task?.completed) return { text: 'Завершена', icon: '✅', class: 'bg-emerald-100 text-emerald-700 ring-emerald-600/20' }
    if (props.task?.status === 'in_work') return { text: 'В работе', icon: '⚙️', class: 'bg-sky-100 text-sky-700 ring-sky-600/20' }
    return { text: 'Новая', icon: '🆕', class: 'bg-gray-100 text-gray-600 ring-gray-500/20' }
})

/* --- Логика описания --- */
const MAX_LENGTH = 140
const showDescriptionModal = ref(false)

const isLongDescription = computed(() => props.task?.description?.length > MAX_LENGTH)

const shortDescription = computed(() => {
    if (!props.task?.description) return ''
    if (!isLongDescription.value) return props.task.description
    return props.task.description.slice(0, MAX_LENGTH) + '…'
})
</script>

<template>
    <div class="group relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden mb-8 transition-all hover:shadow-2xl">

        <!-- Верхняя цветная полоска (индикатор) -->
        <div class="h-2 w-full bg-gradient-to-r from-sky-500 via-indigo-500 to-fuchsia-500"></div>

        <div class="p-6 sm:p-8">
            <div class="flex flex-col xl:flex-row gap-8">

                <!-- ЛЕВАЯ КОЛОНКА: Контент -->
                <div class="flex-1 min-w-0">

                    <!-- Хлебные крошки / Мета -->
                    <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-4 font-medium">
                        <button @click="$emit('back')" class="hover:text-indigo-600 transition flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                            Назад
                        </button>
                        <span class="text-gray-300">/</span>
                        <span class="flex items-center gap-1">
                            📁 {{ task?.project?.name || 'Без проекта' }}
                        </span>
                        <span class="text-gray-300">/</span>
                        <span class="flex items-center gap-1">
                             👤 {{ task?.creator?.name || 'Неизвестный' }}
                        </span>
                    </div>

                    <!-- Заголовок и Статус -->
                    <div class="mb-6">
                        <div class="flex flex-wrap items-start gap-3 mb-2">
                            <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-900 dark:text-white tracking-tight leading-tight">
                                {{ task?.title || 'Загрузка...' }}
                            </h1>
                        </div>

                        <div class="flex flex-wrap gap-2 mt-3">
                            <span class="badge ring-1" :class="statusBadge.class">
                                <span class="mr-1.5">{{ statusBadge.icon }}</span> {{ statusBadge.text }}
                            </span>

                            <span class="badge ring-1" :class="priorityBadge(task?.priority)">
                                ⚡ {{ priorityLabel(task?.priority) }}
                            </span>

                            <span v-if="task?.is_watcher" class="badge bg-indigo-50 text-indigo-700 ring-1 ring-indigo-500/20">
                                👁 Вы наблюдатель
                            </span>
                        </div>
                    </div>

                    <!-- Описание -->
                    <div v-if="task?.description" class="relative group/desc">
                        <div class="pl-4 border-l-4 border-gray-200 dark:border-gray-600 py-1">
                            <h3 class="text-xs font-bold uppercase text-gray-400 mb-2 tracking-wider">Описание</h3>
                            <p class="text-gray-600 dark:text-gray-300 whitespace-pre-line text-base leading-relaxed">
                                {{ shortDescription }}
                            </p>
                            <button
                                v-if="isLongDescription"
                                @click="showDescriptionModal = true"
                                class="mt-2 text-sm font-semibold text-indigo-600 hover:text-indigo-500 hover:underline transition-colors flex items-center gap-1"
                            >
                                Читать полностью
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ПРАВАЯ КОЛОНКА: Панель управления -->
                <div class="w-full xl:w-80 flex flex-col gap-5 flex-shrink-0">

                    <!-- Основное действие (Взять в работу / Завершить) -->
                    <div v-if="perms.canManageTask">
                        <button
                            v-if="!task?.completed && task?.status === 'new'"
                            @click="$emit('startWork', task.id)"
                            class="main-btn bg-gradient-to-r from-sky-500 to-blue-600 shadow-sky-200 dark:shadow-none hover:shadow-sky-300"
                        >
                            🚀 Взять в работу
                        </button>

                        <button
                            v-else-if="perms.canFinish && !task?.completed"
                            @click="$emit('finish')"
                            class="main-btn bg-gradient-to-r from-emerald-500 to-teal-600 shadow-emerald-200 dark:shadow-none hover:shadow-emerald-300"
                        >
                            ✅ Завершить задачу
                        </button>

                        <div v-else-if="task?.progress === 100 && !task?.completed" class="p-3 bg-amber-50 text-amber-700 rounded-xl border border-amber-200 text-sm font-medium flex gap-2">
                            ⚠️ <span>Нельзя завершить: закройте подзадачи.</span>
                        </div>

                        <div v-if="task?.completed" class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-100 dark:border-gray-600 text-center">
                            <div class="text-emerald-600 font-bold mb-1">Задача выполнена</div>
                            <div class="text-xs text-gray-500">{{ new Date(task.completed_at).toLocaleDateString() }}</div>
                        </div>
                    </div>

                    <!-- Действия с задачей -->
                    <div class="grid grid-cols-2 gap-2">
                        <button v-if="perms.canUpdate" @click="$emit('edit')" class="btn-secondary text-blue-600 bg-blue-50 hover:bg-blue-100">
                            ✏️ Изменить
                        </button>
                        <button v-if="perms.canDelete" @click="$emit('delete')" class="btn-secondary text-rose-600 bg-rose-50 hover:bg-rose-100">
                            🗑 Удалить
                        </button>
                        <button @click="$emit('description')" class="col-span-2 btn-secondary text-gray-600 bg-gray-50 hover:bg-gray-100">
                            📝 Редактировать описание
                        </button>
                    </div>

                    <!-- Управление участниками (Сгруппировано) -->
                    <div v-if="perms.canManageMembers" class="bg-gray-50 dark:bg-gray-700/30 rounded-xl p-4 border border-gray-100 dark:border-gray-700/50">
                        <h4 class="text-xs font-bold uppercase text-gray-400 mb-3 tracking-wider flex items-center gap-2">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                            Участники
                        </h4>
                        <div class="space-y-2">
                            <div class="grid grid-cols-2 gap-2">
                                <button @click="$emit('changeExecutor')" class="btn-xs bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 shadow-sm border border-gray-200 dark:border-gray-600 hover:border-blue-400">👷 Сменить Исп.</button>
                                <button @click="$emit('changeResponsible')" class="btn-xs bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 shadow-sm border border-gray-200 dark:border-gray-600 hover:border-indigo-400">👨‍💼 Сменить Отв.</button>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <button @click="$emit('addExecutor')" class="btn-xs text-emerald-600 bg-emerald-50 hover:bg-emerald-100">➕ Исп.</button>
                                <button @click="$emit('addResponsible')" class="btn-xs text-teal-600 bg-teal-50 hover:bg-teal-100">➕ Отв.</button>
                            </div>
                            <button @click="$emit('addWatcher')" class="w-full btn-xs text-purple-600 bg-purple-50 hover:bg-purple-100">👁 Добавить наблюдателя</button>
                            <button @click="$emit('manageMembers')" class="w-full btn-xs text-gray-600 bg-gray-100 hover:bg-gray-200 mt-1">⚙️ Управление правами</button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>


    <!-- МОДАЛЬНОЕ ОКНО ОПИСАНИЯ (Обновленное) -->
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95"
    >
        <div
            v-if="showDescriptionModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showDescriptionModal = false"></div>

            <!-- Content -->
            <div class="relative bg-white dark:bg-gray-800 w-full max-w-2xl rounded-2xl shadow-2xl flex flex-col max-h-[85vh]">
                <div class="flex justify-between items-center px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="font-bold text-lg text-gray-800 dark:text-white">Полное описание</h3>
                    <button @click="showDescriptionModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition bg-gray-100 dark:bg-gray-700 rounded-full p-1">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto custom-scrollbar">
                    <div class="prose prose-sm sm:prose-base dark:prose-invert max-w-none text-gray-700 dark:text-gray-300 whitespace-pre-line leading-relaxed">
                        {{ task.description }}
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-750 rounded-b-2xl flex justify-end">
                    <button
                        @click="showDescriptionModal = false"
                        class="px-5 py-2.5 rounded-xl bg-white border border-gray-300 text-gray-700 font-medium hover:bg-gray-50 transition shadow-sm"
                    >
                        Закрыть
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
/* Утилиты для красоты */
.badge {
    @apply inline-flex items-center px-3 py-1 rounded-full text-xs font-bold shadow-sm transition-all;
}

.main-btn {
    @apply w-full py-3.5 rounded-xl text-white font-bold text-base transition-all transform hover:-translate-y-0.5 active:translate-y-0 flex items-center justify-center gap-2;
}

.btn-secondary {
    @apply py-2.5 px-4 rounded-xl text-sm font-semibold transition-colors flex items-center justify-center gap-2;
}

.btn-xs {
    @apply py-2 px-2 rounded-lg text-xs font-semibold transition-all text-center flex items-center justify-center;
}

/* Scrollbar для модалки */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 20px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #475569;
}
</style>
