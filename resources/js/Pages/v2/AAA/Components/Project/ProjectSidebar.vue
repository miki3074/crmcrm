<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps(['project'])

const showModal = ref(false)
const showCompletedModal = ref(false)
const CHAR_LIMIT = 150
const completedTasks = ref([])
const completedSubtasks = ref([])
const activeInfoTab = ref('details') // details, stats, files

// Вычисляем, нужно ли обрезать текст
const isLongDescription = computed(() => {
    return props.project.description && props.project.description.length > CHAR_LIMIT
})

// Текст для превью
const truncatedDescription = computed(() => {
    const desc = props.project.description
    if (!desc) return 'Нет описания'
    if (!isLongDescription.value) return desc
    return desc.slice(0, CHAR_LIMIT) + '...'
})

// Форматирование денег
const formatMoney = (value) => {
    return value ? Number(value).toLocaleString('ru-RU') + ' ₽' : '—'
}

// Статистика проекта
const projectStats = computed(() => {
    const totalTasks = props.project.tasks?.length || 0
    const completed = completedTasks.value.length || 0
    const inProgress = totalTasks - completed

    return {
        totalTasks,
        completed,
        inProgress,
        progress: totalTasks > 0 ? Math.round((completed / totalTasks) * 100) : 0
    }
})

const makeLink = (task, isSubtask = false) => {
    return isSubtask ? `/subtasks/${task.id}` : `/tasks/${task.id}`
}

onMounted(async () => {
    try {
        const { data } = await axios.get(`/api/projects/${props.project.id}/completed-tasks`)
        completedTasks.value = data.tasks.map(t => ({ ...t, link: makeLink(t) }))
        completedSubtasks.value = data.subtasks.map(s => ({ ...s, link: makeLink(s, true) }))
    } catch (e) {
        console.error('Ошибка загрузки завершенных задач:', e)
    }
})

const goTo = (url) => {
    if (!url) return
    showCompletedModal.value = false
    router.visit(url)
}
</script>

<template>
    <div class="space-y-6">

        <!-- Основная карточка -->
        <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-200 dark:border-slate-700 shadow-xl overflow-hidden sticky top-24 transition-all duration-300 hover:shadow-2xl">

            <!-- Декоративная полоса сверху -->
            <div class="h-1.5 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500"></div>

            <!-- Заголовок с табами -->
            <div class="p-6 pb-0">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-1 h-8 bg-gradient-to-b from-indigo-500 to-purple-500 rounded-full"></div>
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
                            Информация о проекте
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">Детальная информация и статистика</p>
                    </div>
                </div>

                <!-- Табы навигации -->
                <div class="flex gap-1 border-b border-slate-100 dark:border-slate-700">
                    <button @click="activeInfoTab = 'details'"
                            class="px-4 py-2 text-sm font-medium transition-all relative"
                            :class="activeInfoTab === 'details' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-500 hover:text-slate-700'">
                        Детали
                        <div v-if="activeInfoTab === 'details'"
                             class="absolute bottom-0 left-0 right-0 h-0.5 bg-gradient-to-r from-indigo-500 to-purple-500"></div>
                    </button>
                    <button @click="activeInfoTab = 'stats'"
                            class="px-4 py-2 text-sm font-medium transition-all relative"
                            :class="activeInfoTab === 'stats' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-500 hover:text-slate-700'">
                        Статистика
                        <div v-if="activeInfoTab === 'stats'"
                             class="absolute bottom-0 left-0 right-0 h-0.5 bg-gradient-to-r from-indigo-500 to-purple-500"></div>
                    </button>
                    <button @click="activeInfoTab = 'files'"
                            class="px-4 py-2 text-sm font-medium transition-all relative"
                            :class="activeInfoTab === 'files' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-500 hover:text-slate-700'">
                        Файлы
                        <div v-if="activeInfoTab === 'files'"
                             class="absolute bottom-0 left-0 right-0 h-0.5 bg-gradient-to-r from-indigo-500 to-purple-500"></div>
                    </button>
                </div>
            </div>

            <!-- Контент табов -->
            <div class="p-6">
                <!-- Таб деталей -->
                <div v-if="activeInfoTab === 'details'" class="space-y-5">
                    <!-- Компания -->
                    <div class="flex items-center justify-between group p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <span class="text-sm text-slate-500">Компания</span>
                        </div>
                        <span class="text-sm font-semibold text-slate-800 dark:text-slate-200 text-right max-w-[150px] truncate">
                            {{ project.company?.name || '—' }}
                        </span>
                    </div>

                    <!-- Инициатор -->
                    <div class="flex items-center justify-between group p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center text-purple-600 dark:text-purple-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <span class="text-sm text-slate-500">Инициатор</span>
                        </div>
                        <span class="text-sm font-semibold text-slate-800 dark:text-slate-200 text-right max-w-[150px] truncate">
                            {{ project.initiator?.name || '—' }}
                        </span>
                    </div>

                    <!-- Длительность -->
                    <div class="flex items-center justify-between group p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <span class="text-sm text-slate-500">Длительность</span>
                        </div>
                        <span class="text-sm font-semibold text-slate-800 dark:text-slate-200">
                            {{ project.duration_days }} дн.
                        </span>
                    </div>

                    <!-- Бюджет -->
                    <div class="flex items-center justify-between group p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <span class="text-sm text-slate-500">Бюджет</span>
                        </div>
                        <span class="text-sm font-bold text-emerald-600 dark:text-emerald-400">
                            {{ formatMoney(project.budget) }}
                        </span>
                    </div>

                    <!-- Дата создания -->
                    <div class="flex items-center justify-between group p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 dark:text-amber-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <span class="text-sm text-slate-500">Создан</span>
                        </div>
                        <span class="text-sm font-semibold text-slate-800 dark:text-slate-200">
                            {{ new Date(project.created_at).toLocaleDateString('ru-RU') }}
                        </span>
                    </div>

                    <!-- Описание -->
                    <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="w-1 h-4 bg-gradient-to-b from-indigo-500 to-purple-500 rounded-full"></div>
                            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Описание</span>
                        </div>

                        <div class="relative bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4">
                            <p class="text-sm text-slate-600 dark:text-slate-300 whitespace-pre-line leading-relaxed">
                                {{ truncatedDescription }}
                            </p>

                            <!-- Кнопка "Читать далее" -->
                            <button v-if="isLongDescription"
                                    @click="showModal = true"
                                    class="mt-3 text-xs font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 flex items-center gap-1 transition-colors">
                                Читать полностью
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Таб статистики -->
                <div v-if="activeInfoTab === 'stats'" class="space-y-5">
                    <!-- Прогресс бар -->
                    <div class="bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-950/30 dark:to-purple-950/30 rounded-xl p-5">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Общий прогресс</span>
                            <span class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ projectStats.progress }}%</span>
                        </div>
                        <div class="w-full h-2.5 bg-white dark:bg-slate-700 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full transition-all duration-1000"
                                 :style="{ width: projectStats.progress + '%' }"></div>
                        </div>
                    </div>

                    <!-- Статистика задач -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4 text-center">
                            <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ projectStats.totalTasks }}</div>
                            <div class="text-xs text-slate-500 mt-1">Всего задач</div>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4 text-center">
                            <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ projectStats.completed }}</div>
                            <div class="text-xs text-slate-500 mt-1">Завершено</div>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4 text-center">
                            <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ projectStats.inProgress }}</div>
                            <div class="text-xs text-slate-500 mt-1">В работе</div>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4 text-center">
                            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ project.managers?.length || 0 }}</div>
                            <div class="text-xs text-slate-500 mt-1">Участников</div>
                        </div>
                    </div>

                    <!-- Кнопка завершенных задач -->
                    <button @click="showCompletedModal = true"
                            class="w-full mt-2 px-4 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-medium shadow-lg shadow-indigo-500/30 hover:shadow-xl hover:scale-[1.02] transition-all flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Завершенные задачи
                        <span v-if="completedTasks.length + completedSubtasks.length"
                              class="ml-1 text-xs bg-white/30 px-2 py-0.5 rounded-full">
                            {{ completedTasks.length + completedSubtasks.length }}
                        </span>
                    </button>
                </div>

                <!-- Таб файлов -->
                <div v-if="activeInfoTab === 'files'" class="text-center py-8">
                    <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                        <svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h4 class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Нет файлов</h4>
                    <p class="text-xs text-slate-400">Загрузите файлы, связанные с проектом</p>
                </div>
            </div>
        </div>

        <!-- Модальное окно (Полное описание) -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95">

            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <!-- Фон с эффектом стекла -->
                <div class="absolute inset-0 bg-slate-900/40 dark:bg-black/60 backdrop-blur-md" @click="showModal = false"></div>

                <!-- Контент -->
                <div class="relative w-full max-w-2xl bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden flex flex-col max-h-[85vh]">

                    <!-- Декоративная полоса -->
                    <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500"></div>

                    <!-- Хедер -->
                    <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white text-lg shadow-lg">
                                    📄
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Описание проекта</h3>
                                    <p class="text-xs text-slate-500 mt-1">Полная версия</p>
                                </div>
                            </div>
                            <button @click="showModal = false"
                                    class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 flex items-center justify-center text-slate-500 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Тело -->
                    <div class="p-6 overflow-y-auto custom-scrollbar">
                        <div class="prose prose-sm prose-slate dark:prose-invert max-w-none whitespace-pre-line leading-relaxed text-slate-600 dark:text-slate-300">
                            {{ project.description }}
                        </div>
                    </div>

                    <!-- Футер -->
                    <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 flex justify-end">
                        <button @click="showModal = false"
                                class="px-6 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-medium shadow-lg shadow-indigo-500/30 hover:shadow-xl transition-all">
                            Закрыть
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Модалка завершенных задач -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95">

            <div v-if="showCompletedModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <!-- Фон с эффектом стекла -->
                <div class="absolute inset-0 bg-slate-900/40 dark:bg-black/60 backdrop-blur-md" @click="showCompletedModal = false"></div>

                <!-- Контент -->
                <div class="relative w-full max-w-xl bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden flex flex-col max-h-[80vh]">

                    <!-- Декоративная полоса -->
                    <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-emerald-500 to-teal-500"></div>

                    <!-- Header -->
                    <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-500 flex items-center justify-center text-white text-lg shadow-lg">
                                    ✅
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Завершенные задачи</h3>
                                    <p class="text-xs text-slate-500 mt-1">
                                        Всего: {{ completedTasks.length + completedSubtasks.length }}
                                    </p>
                                </div>
                            </div>
                            <button @click="showCompletedModal = false"
                                    class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 flex items-center justify-center text-slate-500 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="flex-1 overflow-y-auto custom-scrollbar p-6">
                        <div v-if="!completedTasks.length && !completedSubtasks.length"
                             class="text-center py-12">
                            <span class="text-6xl mb-4 block opacity-30">✅</span>
                            <p class="text-slate-400">Пока нет завершенных задач</p>
                        </div>

                        <div v-else class="space-y-6">
                            <!-- Задачи -->
                            <div v-if="completedTasks.length">
                                <h4 class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 mb-3 flex items-center gap-2">
                                    <span class="w-1 h-4 bg-indigo-500 rounded-full"></span>
                                    Задачи
                                </h4>
                                <div class="space-y-2">
                                    <div v-for="task in completedTasks" :key="task.id"
                                         @click="goTo(task.link)"
                                         class="group p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-indigo-300 dark:hover:border-indigo-700 hover:shadow-lg transition-all cursor-pointer">
                                        <div class="flex items-start gap-3">
                                            <div class="w-6 h-6 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                            <div class="flex-1">
                                                <h5 class="font-medium text-slate-800 dark:text-white group-hover:text-indigo-600 transition-colors">
                                                    {{ task.title }}
                                                </h5>
                                                <p class="text-xs text-slate-500 mt-1">{{ task.description || 'Нет описания' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Подзадачи -->
                            <div v-if="completedSubtasks.length">
                                <h4 class="text-sm font-semibold text-emerald-600 dark:text-emerald-400 mb-3 flex items-center gap-2">
                                    <span class="w-1 h-4 bg-emerald-500 rounded-full"></span>
                                    Подзадачи
                                </h4>
                                <div class="space-y-2">
                                    <div v-for="sub in completedSubtasks" :key="sub.id"
                                         @click="goTo(sub.link)"
                                         class="group p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-emerald-300 dark:hover:border-emerald-700 hover:shadow-lg transition-all cursor-pointer">
                                        <div class="flex items-start gap-3">
                                            <div class="w-6 h-6 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                            <div class="flex-1">
                                                <h5 class="font-medium text-slate-800 dark:text-white group-hover:text-emerald-600 transition-colors">
                                                    {{ sub.title }}
                                                </h5>
                                                <p class="text-xs text-slate-500 mt-1">
                                                    Из задачи: {{ sub.task?.title || 'Без задачи' }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 flex justify-end">
                        <button @click="showCompletedModal = false"
                                class="px-6 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-xl font-medium shadow-lg shadow-emerald-500/30 hover:shadow-xl transition-all">
                            Закрыть
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
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

/* Анимации */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.fade-enter-active,
.fade-leave-active {
    transition: all 0.3s ease;
}

.fade-enter-from {
    opacity: 0;
    transform: translateY(10px);
}

.fade-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}

/* Эффект стекла */
.backdrop-blur-md {
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}
</style>
