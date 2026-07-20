<script setup>
import { router } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'

const props = defineProps({
    projects: Array,
    loading: Boolean
})

// Состояние выбранного проекта
const selectedProjectId = ref(null)

// Определяем, является ли устройство мобильным (по ширине экрана)
const isMobile = ref(window.innerWidth < 1024)

// Слушаем изменение размера окна
window.addEventListener('resize', () => {
    isMobile.value = window.innerWidth < 1024
})

// При загрузке страницы, если проекты есть, выбираем первый автоматически
watch(() => props.projects, (newVal) => {
    if (newVal && newVal.length > 0 && !selectedProjectId.value) {
        selectedProjectId.value = newVal[0].id
    }
}, { immediate: true })

// Вычисляем данные активного проекта
const activeProject = computed(() => {
    return props.projects?.find(p => p.id === selectedProjectId.value) || null
})

// Helpers для проектов
const daysLeft = (startDate, duration) => {
    if (!startDate || !duration) return null
    const start = new Date(startDate)
    const end = new Date(start)
    end.setDate(start.getDate() + Number(duration))
    const today = new Date()
    return Math.ceil((end - today) / (1000 * 60 * 60 * 24))
}

const getBadgeColor = (days) => {
    if (days === null) return 'bg-slate-100 text-slate-500'
    if (days > 7) return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
    if (days >= 0) return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'
    return 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300'
}

const managerInitials = (name) => name?.split(' ').map(p => p[0]).slice(0, 2).join('').toUpperCase() || '?'

// Helpers для ЗАДАЧ
const getPriorityColor = (priority) => {
    switch(priority) {
        case 'high': return 'text-rose-600 bg-rose-50 border-rose-200 dark:bg-rose-900/20 dark:border-rose-800 dark:text-rose-300';
        case 'medium': return 'text-amber-600 bg-amber-50 border-amber-200 dark:bg-amber-900/20 dark:border-amber-800 dark:text-amber-300';
        default: return 'text-slate-600 bg-slate-50 border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400';
    }
}

const getStatusLabel = (status) => {
    switch(status) {
        case 'new': return 'Новая';
        case 'in_work': return 'В работе';
        default: return status;
    }
}

// Функция для обработки клика по проекту
const handleProjectClick = (project) => {
    if (isMobile.value) {
        // На мобильных устройствах переходим на страницу проекта
        router.visit(`/projects/${project.id}`)
    } else {
        // На десктопе просто выбираем проект
        selectedProjectId.value = project.id
    }
}
</script>

<template>
    <div class="h-[calc(100vh-100px)] min-h-[600px]">

        <!-- Заголовок -->
        <div class="flex items-center justify-between mb-4 px-1">
            <h2 class="text-xl font-bold text-slate-800 dark:text-white">🚀 Активные проекты</h2>
            <span v-if="projects?.length" class="text-sm font-medium text-slate-500 bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded-lg">
                Всего: {{ projects.length }}
            </span>
        </div>

        <!-- Скелетон (Loading) -->
        <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-3 gap-6 animate-pulse">
            <div class="col-span-1 space-y-4">
                <div v-for="i in 3" :key="i" class="h-32 bg-slate-100 dark:bg-slate-800 rounded-2xl"></div>
            </div>
            <div class="col-span-2 h-96 bg-slate-100 dark:bg-slate-800 rounded-2xl"></div>
        </div>

        <!-- Основной контент (Flex контейнер) -->
        <div v-else class="flex flex-col lg:flex-row gap-6 h-full overflow-hidden">

            <!-- ЛЕВАЯ КОЛОНКА (СПИСОК ПРОЕКТОВ) - на мобильных занимает 100% ширины -->
            <div class="w-full lg:w-[30%] overflow-y-auto pr-2 space-y-4 pb-10 custom-scrollbar">

                <div v-if="!projects?.length" class="text-center py-8 text-slate-400">Нет проектов</div>

                <div
                    v-for="project in projects"
                    :key="project.id"
                    @click="handleProjectClick(project)"
                    class="relative p-5 rounded-2xl border transition-all duration-200 cursor-pointer group"
                    :class="[
                        !isMobile && selectedProjectId === project.id
                            ? 'bg-white dark:bg-slate-800 border-indigo-500 ring-1 ring-indigo-500 shadow-md'
                            : 'bg-white dark:bg-slate-800 border-slate-100 dark:border-slate-700 hover:border-indigo-300'
                    ]"
                >
                    <div class="flex justify-between items-start mb-2">
                        <h3 class="font-bold text-slate-800 dark:text-white line-clamp-1"
                            :class="[!isMobile && selectedProjectId === project.id ? 'text-indigo-600' : '']">
                            {{ project.name }}
                        </h3>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full" :class="getBadgeColor(daysLeft(project.start_date, project.duration_days))">
                            {{ daysLeft(project.start_date, project.duration_days) }} дн.
                        </span>
                    </div>

                    <div class="text-xs text-slate-500 mb-3">
                        📅 Старт: <span class="font-medium text-slate-700 dark:text-slate-300">{{ project.start_date }}</span>
                    </div>

                    <!-- Аватарки -->
                    <div class="flex items-center -space-x-1.5 overflow-hidden">
                        <div
                            v-for="m in project.managers?.slice(0, 3)"
                            :key="m.id"
                            class="w-6 h-6 rounded-full border border-white dark:border-slate-800 bg-indigo-100 text-indigo-600 flex items-center justify-center text-[9px] font-bold"
                        >
                            {{ managerInitials(m.name) }}
                        </div>
                    </div>

                    <!-- Индикатор для мобильной версии, что это ссылка -->
                    <div v-if="isMobile" class="absolute bottom-3 right-3 text-xs text-indigo-500 opacity-0 group-hover:opacity-100 transition-opacity">
                        Перейти →
                    </div>
                </div>
            </div>

            <!-- ПРАВАЯ КОЛОНКА (ЗАДАЧИ ВЫБРАННОГО ПРОЕКТА) - скрыта на мобильных -->
            <div
                v-if="!isMobile"
                class="flex-1 bg-white dark:bg-slate-800 rounded-3xl border border-slate-100 dark:border-slate-700 shadow-sm overflow-hidden flex flex-col"
            >

                <div v-if="activeProject" class="h-full flex flex-col">
                    <!-- Хедер проекта -->
                    <div class="p-6 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center bg-slate-50/50 dark:bg-slate-900/50">
                        <div>
                            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">{{ activeProject.name }}</h2>
                            <p class="text-sm text-slate-500 mt-1">Список задач</p>
                        </div>
                        <button
                            @click="router.visit(`/projects/${activeProject.id}`)"
                            class="text-sm bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl transition-colors"
                        >
                            Перейти к проекту →
                        </button>
                    </div>

                    <!-- Таблица задач -->
                    <div class="flex-1 overflow-y-auto p-0">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-slate-50 dark:bg-slate-900/30 text-xs uppercase text-slate-500 sticky top-0 backdrop-blur-md">
                            <tr>
                                <th class="px-6 py-3 font-semibold">Название</th>
                                <th class="px-6 py-3 font-semibold">Приоритет</th>
                                <th class="px-6 py-3 font-semibold">Статус</th>
                                <th class="px-6 py-3 font-semibold">Дедлайн</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            <tr v-if="!activeProject.tasks || activeProject.tasks.length === 0">
                                <td colspan="4" class="px-6 py-12 text-center text-slate-500">
                                    В этом проекте пока нет задач
                                </td>
                            </tr>

                            <tr
                                v-for="task in activeProject.tasks"
                                :key="task.id"
                                @click="router.visit(`/tasks/${task.id}`)"
                                class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors group cursor-pointer"
                            >
                                <td class="px-6 py-4">
                                    <div class="font-medium text-slate-800 dark:text-slate-200">{{ task.title }}</div>
                                    <div class="text-xs text-slate-400 mt-0.5 line-clamp-1">{{ task.description }}</div>
                                </td>
                                <td class="px-6 py-4">
                <span class="text-[10px] font-bold px-2 py-1 rounded-md border" :class="getPriorityColor(task.priority)">
                    {{ task.priority?.toUpperCase() }}
                </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <span class="w-2 h-2 rounded-full mr-2" :class="task.status === 'in_work' ? 'bg-indigo-500' : 'bg-slate-300'"></span>
                                        <span class="text-sm text-slate-600 dark:text-slate-400">{{ getStatusLabel(task.status) }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-500 font-mono">
                                    {{ task.due_date }}
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Состояние если проект не выбран -->
                <div v-else class="flex items-center justify-center h-full text-slate-400">
                    <div class="text-center">
                        <p class="text-4xl mb-4">👈</p>
                        <p>Выберите проект слева,</p>
                        <p>чтобы увидеть задачи</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>

<style scoped>
/* Кастомный скроллбар для списка проектов */
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #e2e8f0;
    border-radius: 20px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #334155;
}
</style>


<!--<script setup>-->
<!--import { router } from '@inertiajs/vue3'-->
<!--import { ref, computed, watch } from 'vue'-->

<!--const props = defineProps({-->
<!--    projects: Array, // Ожидаем массив проектов-->
<!--    loading: Boolean-->
<!--})-->

<!--// Состояние выбранного проекта-->
<!--const selectedProjectId = ref(null)-->

<!--// При загрузке страницы, если проекты есть, выбираем первый автоматически (опционально)-->
<!--watch(() => props.projects, (newVal) => {-->
<!--    if (newVal && newVal.length > 0 && !selectedProjectId.value) {-->
<!--        selectedProjectId.value = newVal[0].id-->
<!--    }-->
<!--}, { immediate: true })-->

<!--// Вычисляем данные активного проекта-->
<!--const activeProject = computed(() => {-->
<!--    return props.projects?.find(p => p.id === selectedProjectId.value) || null-->
<!--})-->

<!--// Helpers для проектов-->
<!--const daysLeft = (startDate, duration) => {-->
<!--    if (!startDate || !duration) return null-->
<!--    const start = new Date(startDate)-->
<!--    const end = new Date(start)-->
<!--    end.setDate(start.getDate() + Number(duration))-->
<!--    const today = new Date()-->
<!--    return Math.ceil((end - today) / (1000 * 60 * 60 * 24))-->
<!--}-->

<!--const getBadgeColor = (days) => {-->
<!--    if (days === null) return 'bg-slate-100 text-slate-500'-->
<!--    if (days > 7) return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'-->
<!--    if (days >= 0) return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'-->
<!--    return 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300'-->
<!--}-->

<!--const managerInitials = (name) => name?.split(' ').map(p => p[0]).slice(0, 2).join('').toUpperCase() || '?'-->

<!--// Helpers для ЗАДАЧ (Tasks)-->
<!--const getPriorityColor = (priority) => {-->
<!--    switch(priority) {-->
<!--        case 'high': return 'text-rose-600 bg-rose-50 border-rose-200 dark:bg-rose-900/20 dark:border-rose-800 dark:text-rose-300';-->
<!--        case 'medium': return 'text-amber-600 bg-amber-50 border-amber-200 dark:bg-amber-900/20 dark:border-amber-800 dark:text-amber-300';-->
<!--        default: return 'text-slate-600 bg-slate-50 border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400';-->
<!--    }-->
<!--}-->

<!--const getStatusLabel = (status) => {-->
<!--    switch(status) {-->
<!--        case 'new': return 'Новая';-->
<!--        case 'in_work': return 'В работе';-->
<!--        default: return status;-->
<!--    }-->
<!--}-->
<!--</script>-->

<!--<template>-->
<!--    <div class="h-[calc(100vh-100px)] min-h-[600px]"> &lt;!&ndash; Фиксируем высоту для скролла &ndash;&gt;-->

<!--        &lt;!&ndash; Заголовок &ndash;&gt;-->
<!--        <div class="flex items-center justify-between mb-4 px-1">-->
<!--            <h2 class="text-xl font-bold text-slate-800 dark:text-white">🚀 Активные проекты</h2>-->
<!--            <span v-if="projects?.length" class="text-sm font-medium text-slate-500 bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded-lg">-->
<!--                Всего: {{ projects.length }}-->
<!--            </span>-->
<!--        </div>-->

<!--        &lt;!&ndash; Скелетон (Loading) &ndash;&gt;-->
<!--        <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-3 gap-6 animate-pulse">-->
<!--            <div class="col-span-1 space-y-4">-->
<!--                <div v-for="i in 3" :key="i" class="h-32 bg-slate-100 dark:bg-slate-800 rounded-2xl"></div>-->
<!--            </div>-->
<!--            <div class="col-span-2 h-96 bg-slate-100 dark:bg-slate-800 rounded-2xl"></div>-->
<!--        </div>-->

<!--        &lt;!&ndash; Основной контент (Flex контейнер) &ndash;&gt;-->
<!--        <div v-else class="flex flex-col lg:flex-row gap-6 h-full overflow-hidden">-->

<!--            &lt;!&ndash; ЛЕВАЯ КОЛОНКА (СПИСОК ПРОЕКТОВ) - 30% &ndash;&gt;-->
<!--            <div class="w-full lg:w-[30%] overflow-y-auto pr-2 space-y-4 pb-10 custom-scrollbar">-->

<!--                <div v-if="!projects?.length" class="text-center py-8 text-slate-400">Нет проектов</div>-->

<!--                <div-->
<!--                    v-for="project in projects"-->
<!--                    :key="project.id"-->
<!--                    @click="selectedProjectId = project.id"-->
<!--                    class="relative p-5 rounded-2xl border transition-all duration-200 cursor-pointer group"-->
<!--                    :class="[-->
<!--                        selectedProjectId === project.id-->
<!--                            ? 'bg-white dark:bg-slate-800 border-indigo-500 ring-1 ring-indigo-500 shadow-md'-->
<!--                            : 'bg-white dark:bg-slate-800 border-slate-100 dark:border-slate-700 hover:border-indigo-300'-->
<!--                    ]"-->
<!--                >-->
<!--                    <div class="flex justify-between items-start mb-2">-->
<!--                        <h3 class="font-bold text-slate-800 dark:text-white line-clamp-1" :class="{'text-indigo-600': selectedProjectId === project.id}">-->
<!--                            {{ project.name }}-->
<!--                        </h3>-->
<!--                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full" :class="getBadgeColor(daysLeft(project.start_date, project.duration_days))">-->
<!--                            {{ daysLeft(project.start_date, project.duration_days) }} дн.-->
<!--                        </span>-->
<!--                    </div>-->

<!--                    <div class="text-xs text-slate-500 mb-3">-->
<!--                        📅 Старт: <span class="font-medium text-slate-700 dark:text-slate-300">{{ project.start_date }}</span>-->
<!--                    </div>-->

<!--                    &lt;!&ndash; Аватарки (уменьшенные для списка) &ndash;&gt;-->
<!--                    <div class="flex items-center -space-x-1.5 overflow-hidden">-->
<!--                        <div-->
<!--                            v-for="m in project.managers?.slice(0, 3)"-->
<!--                            :key="m.id"-->
<!--                            class="w-6 h-6 rounded-full border border-white dark:border-slate-800 bg-indigo-100 text-indigo-600 flex items-center justify-center text-[9px] font-bold"-->
<!--                        >-->
<!--                            {{ managerInitials(m.name) }}-->
<!--                        </div>-->
<!--                    </div>-->
<!--                </div>-->
<!--            </div>-->

<!--            &lt;!&ndash; ПРАВАЯ КОЛОНКА (ЗАДАЧИ ВЫБРАННОГО ПРОЕКТА) &ndash;&gt;-->
<!--            <div class="flex-1 bg-white dark:bg-slate-800 rounded-3xl border border-slate-100 dark:border-slate-700 shadow-sm overflow-hidden flex flex-col">-->

<!--                <div v-if="activeProject" class="h-full flex flex-col">-->
<!--                    &lt;!&ndash; Хедер проекта &ndash;&gt;-->
<!--                    <div class="p-6 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center bg-slate-50/50 dark:bg-slate-900/50">-->
<!--                        <div>-->
<!--                            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">{{ activeProject.name }}</h2>-->
<!--                            <p class="text-sm text-slate-500 mt-1">Список задач</p>-->
<!--                        </div>-->
<!--                        <button-->
<!--                            @click="router.visit(`/projects/${activeProject.id}`)"-->
<!--                            class="text-sm bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl transition-colors"-->
<!--                        >-->
<!--                            Перейти к проекту →-->
<!--                        </button>-->
<!--                    </div>-->

<!--                    &lt;!&ndash; Таблица задач &ndash;&gt;-->
<!--                    <div class="flex-1 overflow-y-auto p-0">-->
<!--                        <table class="w-full text-left border-collapse">-->
<!--                            <thead class="bg-slate-50 dark:bg-slate-900/30 text-xs uppercase text-slate-500 sticky top-0 backdrop-blur-md">-->
<!--                            <tr>-->
<!--                                <th class="px-6 py-3 font-semibold">Название</th>-->
<!--                                <th class="px-6 py-3 font-semibold">Приоритет</th>-->
<!--                                <th class="px-6 py-3 font-semibold">Статус</th>-->
<!--                                <th class="px-6 py-3 font-semibold">Дедлайн</th>-->
<!--                            </tr>-->
<!--                            </thead>-->
<!--                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">-->
<!--                            <tr v-if="!activeProject.tasks || activeProject.tasks.length === 0">-->
<!--                                <td colspan="4" class="px-6 py-12 text-center text-slate-500">-->
<!--                                    В этом проекте пока нет задач-->
<!--                                </td>-->
<!--                            </tr>-->

<!--                            &lt;!&ndash; 👇 ИЗМЕНЕНИЯ ЗДЕСЬ 👇 &ndash;&gt;-->
<!--                            <tr-->
<!--                                v-for="task in activeProject.tasks"-->
<!--                                :key="task.id"-->
<!--                                @click="router.visit(`/tasks/${task.id}`)"-->
<!--                                class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors group cursor-pointer"-->
<!--                            >-->
<!--                                &lt;!&ndash; 👆 cursor-default заменен на cursor-pointer &ndash;&gt;-->
<!--                                &lt;!&ndash; 👆 добавлен @click. Проверьте, что путь `/tasks/${task.id}` соответствует вашим роутам в Laravel &ndash;&gt;-->

<!--                                <td class="px-6 py-4">-->
<!--                                    <div class="font-medium text-slate-800 dark:text-slate-200">{{ task.title }}</div>-->
<!--                                    <div class="text-xs text-slate-400 mt-0.5 line-clamp-1">{{ task.description }}</div>-->
<!--                                </td>-->
<!--                                <td class="px-6 py-4">-->
<!--                <span class="text-[10px] font-bold px-2 py-1 rounded-md border" :class="getPriorityColor(task.priority)">-->
<!--                    {{ task.priority?.toUpperCase() }}-->
<!--                </span>-->
<!--                                </td>-->
<!--                                <td class="px-6 py-4">-->
<!--                                    <div class="flex items-center">-->
<!--                                        <span class="w-2 h-2 rounded-full mr-2" :class="task.status === 'in_work' ? 'bg-indigo-500' : 'bg-slate-300'"></span>-->
<!--                                        <span class="text-sm text-slate-600 dark:text-slate-400">{{ getStatusLabel(task.status) }}</span>-->
<!--                                    </div>-->
<!--                                </td>-->
<!--                                <td class="px-6 py-4 text-sm text-slate-500 font-mono">-->
<!--                                    {{ task.due_date }}-->
<!--                                </td>-->
<!--                            </tr>-->
<!--                            </tbody>-->
<!--                        </table>-->
<!--                    </div>-->
<!--                </div>-->

<!--                &lt;!&ndash; Состояние если проект не выбран &ndash;&gt;-->
<!--                <div v-else class="flex items-center justify-center h-full text-slate-400">-->
<!--                    <div class="text-center">-->
<!--                        <p class="text-4xl mb-4">👈</p>-->
<!--                        <p>Выберите проект слева,</p>-->
<!--                        <p>чтобы увидеть задачи</p>-->
<!--                    </div>-->
<!--                </div>-->
<!--            </div>-->

<!--        </div>-->
<!--    </div>-->
<!--</template>-->

<!--<style scoped>-->
<!--/* Кастомный скроллбар для списка проектов */-->
<!--.custom-scrollbar::-webkit-scrollbar {-->
<!--    width: 4px;-->
<!--}-->
<!--.custom-scrollbar::-webkit-scrollbar-track {-->
<!--    background: transparent;-->
<!--}-->
<!--.custom-scrollbar::-webkit-scrollbar-thumb {-->
<!--    background-color: #e2e8f0;-->
<!--    border-radius: 20px;-->
<!--}-->
<!--.dark .custom-scrollbar::-webkit-scrollbar-thumb {-->
<!--    background-color: #334155;-->
<!--}-->
<!--</style>-->



<!--<script setup>-->
<!--import { router } from '@inertiajs/vue3'-->

<!--const props = defineProps({-->
<!--    projects: Array,-->
<!--    loading: Boolean-->
<!--})-->

<!--// Helpers-->
<!--const daysLeft = (startDate, duration) => {-->
<!--    if (!startDate || !duration) return null-->
<!--    const start = new Date(startDate)-->
<!--    const end = new Date(start)-->
<!--    end.setDate(start.getDate() + Number(duration))-->
<!--    const today = new Date()-->
<!--    return Math.ceil((end - today) / (1000 * 60 * 60 * 24))-->
<!--}-->

<!--const getBadgeColor = (days) => {-->
<!--    if (days === null) return 'bg-slate-100 text-slate-500'-->
<!--    if (days > 7) return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'-->
<!--    if (days >= 0) return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'-->
<!--    return 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300'-->
<!--}-->

<!--const managerInitials = (name) => name?.split(' ').map(p => p[0]).slice(0, 2).join('').toUpperCase() || '?'-->
<!--</script>-->

<!--<template>-->
<!--    <div>-->
<!--        <div class="flex items-center justify-between mb-6 px-1">-->
<!--            <h2 class="text-xl font-bold text-slate-800 dark:text-white">🚀 Активные проекты</h2>-->
<!--            <span v-if="projects?.length" class="text-sm font-medium text-slate-500 bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded-lg">-->
<!--                {{ projects.length }}-->
<!--            </span>-->
<!--        </div>-->

<!--        &lt;!&ndash; Скелетон &ndash;&gt;-->
<!--        <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">-->
<!--            <div v-for="i in 3" :key="i" class="h-40 rounded-3xl bg-slate-100 dark:bg-slate-800 animate-pulse"></div>-->
<!--        </div>-->

<!--        &lt;!&ndash; Пустое состояние &ndash;&gt;-->
<!--        <div v-else-if="!projects?.length" class="text-center py-12 rounded-3xl border border-dashed border-slate-300 dark:border-slate-700">-->
<!--            <p class="text-slate-500">Проектов пока нет</p>-->
<!--        </div>-->

<!--        &lt;!&ndash; Сетка &ndash;&gt;-->
<!--        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">-->
<!--            <div-->
<!--                v-for="project in projects"-->
<!--                :key="project.id"-->
<!--                @click="router.visit(`/projects/${project.id}`)"-->
<!--                class="group relative bg-white dark:bg-slate-800 rounded-3xl p-6 border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 cursor-pointer overflow-hidden"-->
<!--            >-->
<!--                <div class="flex justify-between items-start mb-4">-->
<!--                    <h3 class="font-bold text-lg text-slate-800 dark:text-white line-clamp-1 group-hover:text-indigo-600 transition-colors">-->
<!--                        {{ project.name }}-->
<!--                    </h3>-->
<!--                    <span class="text-xs font-bold px-2 py-1 rounded-lg" :class="getBadgeColor(daysLeft(project.start_date, project.duration_days))">-->
<!--                        {{ daysLeft(project.start_date, project.duration_days) }} дн.-->
<!--                    </span>-->
<!--                </div>-->

<!--                <div class="space-y-3">-->
<!--                    <div class="flex items-center text-sm text-slate-500 dark:text-slate-400">-->
<!--                        <span class="mr-2">📅 Старт:</span>-->
<!--                        <span class="font-medium text-slate-700 dark:text-slate-200">{{ project.start_date }}</span>-->
<!--                    </div>-->

<!--                    &lt;!&ndash; Аватарки менеджеров &ndash;&gt;-->
<!--                    <div class="flex items-center -space-x-2 overflow-hidden py-1">-->
<!--                        <div-->
<!--                            v-for="m in project.managers?.slice(0, 4)"-->
<!--                            :key="m.id"-->
<!--                            class="w-8 h-8 rounded-full border-2 border-white dark:border-slate-800 bg-indigo-100 text-indigo-600 flex items-center justify-center text-[10px] font-bold"-->
<!--                            :title="m.name"-->
<!--                        >-->
<!--                            {{ managerInitials(m.name) }}-->
<!--                        </div>-->
<!--                        <div v-if="project.managers?.length > 4" class="w-8 h-8 rounded-full border-2 border-white bg-slate-100 flex items-center justify-center text-[10px] text-slate-500">-->
<!--                            +{{ project.managers.length - 4 }}-->
<!--                        </div>-->
<!--                    </div>-->
<!--                </div>-->

<!--                &lt;!&ndash; Декоративный элемент &ndash;&gt;-->
<!--                <div class="absolute -bottom-4 -right-4 w-20 h-20 bg-indigo-50 dark:bg-indigo-900/20 rounded-full blur-xl group-hover:bg-indigo-100 transition-colors"></div>-->
<!--            </div>-->
<!--        </div>-->
<!--    </div>-->
<!--</template>-->
