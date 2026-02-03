<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
const props = defineProps(['project'])


const showModal = ref(false)
const CHAR_LIMIT = 150

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

const showCompletedModal = ref(false)
const completedTasks = ref([])
const completedSubtasks = ref([])

const makeLink = (task, isSubtask = false) => {
    return isSubtask ? `/subtasks/${task.id}` : `/tasks/${task.id}`
}

onMounted(async () => {
    try {
    const { data } = await axios.get(
        `/api/projects/${props.project.id}/completed-tasks`
    )

        completedTasks.value = data.tasks.map(t => ({ ...t, link: makeLink(t) }))
        completedSubtasks.value = data.subtasks.map(s => ({ ...s, link: makeLink(s, true) }))
    } catch (e) {
        console.error('Ошибка загрузки завершенных задач:', e)
    }
})


const goTo = (url) => {
    if (!url) {
        console.warn('Ссылка не задана')
        return
    }
    showCompletedModal.value = false
    router.visit(url)
}

</script>

<template>
    <!-- Карточка -->
    <div class="rounded-3xl border border-slate-100 bg-white dark:bg-slate-800 dark:border-slate-700 p-6 shadow-sm sticky top-6">

        <!-- Заголовок -->
        <div class="flex items-center gap-2 border-b border-slate-100 dark:border-slate-700 pb-4 mb-4">
            <div class="p-2 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg text-indigo-600 dark:text-indigo-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <h3 class="font-bold text-slate-800 dark:text-white">Кратко о проекте</h3>
        </div>

        <!-- Список характеристик -->
        <div class="space-y-4">
            <!-- Компания -->
            <div class="flex items-start justify-between group">
                <div class="flex items-center gap-2 text-sm text-slate-500">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    <span>Компания</span>
                </div>
                <span class="text-sm font-semibold text-slate-800 dark:text-slate-200 text-right truncate max-w-[150px]">
                    {{ project.company?.name || '—' }}
                </span>
            </div>

            <!-- Инициатор -->
            <div class="flex items-start justify-between group">
                <div class="flex items-center gap-2 text-sm text-slate-500">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    <span>Инициатор</span>
                </div>
                <span class="text-sm font-semibold text-slate-800 dark:text-slate-200 text-right truncate max-w-[150px]">
                    {{ project.initiator?.name || '—' }}
                </span>
            </div>

            <!-- Длительность -->
            <div class="flex items-start justify-between group">
                <div class="flex items-center gap-2 text-sm text-slate-500">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Длительность</span>
                </div>
                <span class="text-sm font-semibold text-slate-800 dark:text-slate-200">
                    {{ project.duration_days }} дн.
                </span>
            </div>

            <!-- Бюджет -->
            <div class="flex items-start justify-between group">
                <div class="flex items-center gap-2 text-sm text-slate-500">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    <span>Бюджет</span>
                </div>
                <span class="text-sm font-bold text-emerald-600 dark:text-emerald-400">
                    {{ formatMoney(project.budget) }}
                </span>
            </div>
        </div>

        <!-- Описание (Превью) -->
        <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-700">
            <div class="flex items-center gap-2 mb-2">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path></svg>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Описание</span>
            </div>

            <div class="relative">
                <p class="text-sm text-slate-600 dark:text-slate-300 whitespace-pre-line leading-relaxed break-words">
                    {{ truncatedDescription }}
                </p>

                <!-- Кнопка "Читать далее" -->
                <button
                    v-if="isLongDescription"
                    @click="showModal = true"
                    class="mt-2 text-xs font-bold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300 flex items-center gap-1 transition-colors"
                >
                    Читать полностью
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Модальное окно (Полное описание) -->
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <!-- Фон -->
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showModal = false"></div>

            <!-- Контент -->
            <div class="relative w-full max-w-2xl bg-white dark:bg-slate-800 rounded-2xl shadow-2xl flex flex-col max-h-[85vh] transform transition-all animate-in zoom-in-95 duration-200">

                <!-- Хедер модалки -->
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center bg-slate-50/50 dark:bg-slate-800">
                    <h3 class="font-bold text-lg text-slate-800 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Описание проекта
                    </h3>
                    <button @click="showModal = false" class="p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-400 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <!-- Тело модалки (скролл) -->
                <div class="p-6 overflow-y-auto custom-scrollbar">
                    <div class="prose prose-sm prose-slate dark:prose-invert max-w-none whitespace-pre-line leading-relaxed">
                        {{ project.description }}
                    </div>
                </div>

                <!-- Футер модалки -->
                <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 rounded-b-2xl flex justify-end">
                    <button @click="showModal = false" class="px-4 py-2 bg-white border border-slate-300 dark:bg-slate-700 dark:border-slate-600 text-slate-700 dark:text-white rounded-lg font-medium hover:bg-slate-50 dark:hover:bg-slate-600 transition-colors shadow-sm">
                        Закрыть
                    </button>
                </div>
            </div>
        </div>
    </Transition>

    <div class="mt-8">
        <button
            @click="showCompletedModal = true"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg
               bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold
               transition shadow"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2l4-4M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            Завершённые задачи проекта
            <span v-if="completedTasks.length || completedSubtasks.length"
                  class="ml-1 text-xs bg-white/20 px-2 py-0.5 rounded-full">
            {{ completedTasks.length + completedSubtasks.length }}
        </span>
        </button>
    </div>

    <!-- Модалка -->
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="showCompletedModal"
             class="fixed inset-0 z-50 flex items-center justify-center p-4">

            <!-- Фон -->
            <div class="absolute inset-0 bg-slate-900/60" @click="showCompletedModal = false"></div>

            <!-- Контент -->
            <div class="relative w-full max-w-xl bg-white dark:bg-slate-800
                  rounded-2xl shadow-2xl max-h-[80vh] flex flex-col z-10"
                 @click.stop>

                <!-- Header -->
                <div class="px-6 py-4 border-b flex justify-between items-center">
                    <h3 class="font-bold text-lg">Завершённые задачи проекта</h3>
                    <button @click="showCompletedModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <!-- Body -->
                <div class="p-6 overflow-y-auto space-y-6">

                    <div v-if="!completedTasks.length && !completedSubtasks.length" class="text-sm text-slate-400">
                        Пока нет завершённых задач
                    </div>

                    <!-- Задачи -->
                    <div v-if="completedTasks.length">
                        <h4 class="text-sm font-semibold mb-2">Задачи</h4>
                        <ul class="space-y-2">
                            <li v-for="task in completedTasks" :key="task.id">
                                <a :href="task.link" class="text-indigo-600 hover:underline">
                                    ✔ {{ task.title }}
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Подзадачи -->
                    <div v-if="completedSubtasks.length">
                        <h4 class="text-sm font-semibold mb-2">Подзадачи</h4>
                        <ul class="space-y-2">
                            <li v-for="sub in completedSubtasks" :key="sub.id">
                                <a :href="sub.link" class="text-emerald-600 hover:underline">
                                    ✔ {{ sub.title }}
                                    <span class="text-slate-400 text-xs">
  ({{ sub.task && sub.task.title ? sub.task.title : 'Без задачи' }})
</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t flex justify-end">
                    <button @click="showCompletedModal = false" class="px-4 py-2 rounded-lg bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600">
                        Закрыть
                    </button>
                </div>

            </div>
        </div>
    </Transition>


</template>

<style scoped>
/* Стили для скроллбара внутри модалки */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    @apply bg-slate-300 dark:bg-slate-600 rounded-full;
}
</style>
