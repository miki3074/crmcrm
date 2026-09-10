<script setup>
import { ref, onMounted, onBeforeUnmount, computed } from 'vue'
import { Head, usePage, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import axios from 'axios'

// Компоненты
import StatCard from './AAA/Components/Dashboard/StatCard.vue'
import CompanySection from './AAA/Components/Dashboard/CompanySection.vue'
import TasksSummary from './AAA/Components/Dashboard/TasksSummary.vue'
import SubtasksSection from './AAA/Components/Dashboard/SubtasksSection.vue'
import TasksBoard from './AAA/Components/Dashboard/TasksBoard.vue'
import ActivityFeed from './AAA/Components/Dashboard/ActivityFeed.vue'
import SearchOverlay from './AAA/Components/Dashboard/SearchOverlay.vue'
import CreateCompanyModal from './AAA/Components/Dashboard/CreateCompanyModal.vue'
import EmailVerificationModal from './AAA/Components/Dashboard/EmailVerificationModal.vue'

const { props } = usePage()
const isAdmin = computed(() => props.auth?.roles?.includes('admin'))
const userId = props.auth?.user?.id
const userEmail = props.auth?.user?.email
const emailVerified = ref(props.auth?.user?.email_verified_at !== null)

const companies = ref([])
const activities = ref([])
const summary = ref({
    managing_projects: [],
    my_projects: [],
    all_tasks: [],
    all_subtasks: [],
    klient_tasks: [],
    media_plans: [],
    klient_deals: [],
    due_today: [],
    overdue: []
})
const loading = ref(true)
const isSearchOpen = ref(false)
const showCreateModal = ref(false)
const activeTab = ref('tasks')

const tabs = computed(() => [
    { id: 'tasks', label: 'Задачи', count: summary.value.all_tasks?.length },
    { id: 'subtasks', label: 'Подзадачи', count: undefined },
    { id: 'klient_tasks', label: 'Задачи клиентов', count: summary.value.klient_tasks?.length },
    { id: 'deals', label: 'Сделки', count: summary.value.klient_deals?.length },
    { id: 'media_plans', label: 'Медиапланы', count: summary.value.media_plans?.length },
])

const panelView = ref('list')

const flattenSubtasks = subtasks => {
    if (Array.isArray(subtasks)) {
        return subtasks
    }

    const result = []

    Object.values(subtasks || {}).forEach(projects => {
        Object.values(projects || {}).forEach(tasks => {
            Object.values(tasks || {}).forEach(items => {
                result.push(...items)
            })
        })
    })

    return result
}

const boardItems = computed(() => {
    const items = []

    ;(summary.value.all_tasks || []).forEach(t => items.push({
        id: `task-${t.id}`,
        title: t.title,
        project: t.project?.name || 'Без проекта',
        due_date: t.due_date,
        progress: t.progress,
        link: t.link || `/tasks/${t.id}`,
        kind: 'task',
    }))

    flattenSubtasks(summary.value.all_subtasks).forEach(s => items.push({
        id: `subtask-${s.id}`,
        title: s.title,
        project: s.task?.project?.name || 'Без проекта',
        due_date: s.due_date,
        progress: s.progress ?? s.completion_percentage ?? 0,
        link: s.link || `/subtasks/${s.id}`,
        kind: 'subtask',
    }))

    ;(summary.value.klient_tasks || []).forEach(t => items.push({
        id: `klient_task-${t.id}`,
        title: t.title,
        project: t.project?.name || 'Без проекта',
        due_date: t.due_date,
        progress: t.progress,
        link: t.link || `/tasks/${t.id}`,
        kind: 'klient_task',
    }))

    ;(summary.value.klient_deals || []).forEach(t => items.push({
        id: `deal-${t.id}`,
        title: t.title,
        project: t.project?.name || 'Без проекта',
        due_date: t.due_date,
        progress: t.progress,
        link: t.link || `/tasks/${t.id}`,
        kind: 'deal',
    }))

    ;(summary.value.media_plans || []).forEach(t => items.push({
        id: `media_plan-${t.id}`,
        title: t.title,
        project: t.project?.name || 'Без проекта',
        due_date: t.due_date,
        progress: t.progress,
        link: t.link || `/tasks/${t.id}`,
        kind: 'media_plan',
    }))

    return items
})

const showEmailVerificationModal = ref(!emailVerified.value)

const fetchData = async () => {
    loading.value = true
    try {
        const [compRes, sumRes] = await Promise.all([
            axios.get('/api/companies'),
            axios.get('/api/dashboard/summary')
        ])
        companies.value = compRes.data
        summary.value = sumRes.data
    } catch (e) {
        console.error("Ошибка загрузки", e)
    } finally {
        loading.value = false
    }

    // Отдельно — чтобы сбой ленты событий не ронял остальной дашборд
    try {
        const { data } = await axios.get('/api/dashboard/activities')
        activities.value = data
    } catch (e) {
        console.error("Ошибка загрузки событий", e)
    }
}

// Точечное удаление — сразу убираем из списка, откатываем при ошибке.
const removeActivity = async id => {
    const previous = activities.value
    activities.value = activities.value.filter(a => a.id !== id)
    try {
        await axios.delete(`/api/dashboard/activities/${id}`)
    } catch (e) {
        activities.value = previous
    }
}

const clearActivities = async () => {
    const previous = activities.value
    activities.value = []
    try {
        await axios.delete('/api/dashboard/activities')
    } catch (e) {
        activities.value = previous
    }
}

const handleEmailVerified = () => {
    emailVerified.value = true
    router.reload({ only: ['auth'] })
}

const handleSearchKeydown = (e) => {
    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
        e.preventDefault()
        isSearchOpen.value = true
    }
}

onMounted(() => {
    fetchData()
    window.addEventListener('keydown', handleSearchKeydown)
})

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleSearchKeydown)
})
</script>

<template>
    <Head title="Рабочий стол" />

    <AuthenticatedLayout>
        <div class="min-h-full bg-zinc-50 dark:bg-[#0a0b0f]">
            <div class="mx-auto max-w-[1600px] space-y-5 px-4 py-6 sm:px-6 lg:px-8">

                <EmailVerificationModal
                    :show="showEmailVerificationModal"
                    :user-email="userEmail"
                    :is-verified="emailVerified"
                    @close="showEmailVerificationModal = false"
                    @verified="handleEmailVerified"
                />

                <!-- Заголовок панели -->
                <header class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-zinc-500 dark:text-zinc-400">Рабочая панель</p>
                        <h1 class="mt-0.5 text-xl font-bold text-zinc-900 dark:text-white">Обзор пространства</h1>
                    </div>
                    <div class="flex items-center gap-2 text-xs font-medium">
                        <span class="rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-zinc-600 dark:border-white/5 dark:bg-zinc-900/60 dark:text-zinc-300">{{ summary.all_tasks?.length || 0 }} задач</span>
                        <span class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-amber-700 dark:border-amber-900/50 dark:bg-amber-500/10 dark:text-amber-300">{{ summary.due_today?.length || 0 }} сегодня</span>
                        <span class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-rose-700 dark:border-rose-900/50 dark:bg-rose-500/10 dark:text-rose-300">{{ summary.overdue?.length || 0 }} просрочено</span>
                    </div>
                </header>

                <!-- Поиск -->
                <button
                    type="button"
                    @click="isSearchOpen = true"
                    class="group flex w-full items-center gap-3 rounded-xl border border-zinc-200 bg-white px-4 py-3 text-left transition cursor-pointer hover:border-zinc-300
                           focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400 focus-visible:ring-offset-2
                           dark:border-white/5 dark:bg-zinc-900/60 dark:hover:border-white/10 dark:focus-visible:ring-offset-zinc-900"
                >
                    <svg class="h-5 w-5 shrink-0 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                    </svg>
                    <span class="flex-1 text-sm text-zinc-500 dark:text-zinc-400">Поиск по всему пространству...</span>
                    <kbd class="hidden sm:inline-block rounded-md border border-zinc-200 bg-zinc-50 px-2 py-1 text-[10px] font-semibold uppercase text-zinc-500 dark:border-white/10 dark:bg-white/5 dark:text-zinc-400">Ctrl K</kbd>
                </button>

                <!-- Быстрые действия -->
                <div class="grid grid-cols-2 gap-2.5 md:grid-cols-3 lg:grid-cols-6">
                    <StatCard title="Календарь" icon="calendar" color="violet" @click="router.visit('/calendar')" />
                    <StatCard title="База знаний" icon="folder" color="blue" @click="router.visit('/knowledge')" />
                    <StatCard v-if="isAdmin" title="Сотрудники" icon="users" color="indigo" @click="router.visit('/employees')" />
                    <StatCard v-if="isAdmin" title="Клиенты" icon="clients" color="amber" @click="router.visit('/klients')" />
                    <StatCard title="Схема" icon="map" color="emerald" @click="router.visit('/mapdiagram')" />
                    <StatCard v-if="isAdmin" title="Создать" icon="plus" color="rose" @click="showCreateModal = true" />
                </div>

                <!-- Скелетон при первой загрузке -->
                <template v-if="loading">
                    <div class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1fr)_280px_320px]" aria-hidden="true">
                        <div class="h-40 animate-pulse rounded-2xl border border-zinc-200 bg-white dark:border-white/5 dark:bg-zinc-900/60" />
                        <div class="h-40 animate-pulse rounded-2xl border border-zinc-200 bg-white dark:border-white/5 dark:bg-zinc-900/60" />
                        <div class="h-40 animate-pulse rounded-2xl border border-zinc-200 bg-white dark:border-white/5 dark:bg-zinc-900/60" />
                    </div>
                    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_380px]" aria-hidden="true">
                        <div class="h-64 animate-pulse rounded-xl border border-zinc-200 bg-white dark:border-white/5 dark:bg-zinc-900/60" />
                        <div class="h-64 animate-pulse rounded-xl border border-zinc-200 bg-white dark:border-white/5 dark:bg-zinc-900/60" />
                    </div>
                </template>

                <template v-else>
                    <!-- Компании -->
                    <CompanySection :companies="companies" :user-id="userId" :is-admin="isAdmin" :projects="summary.my_projects" @refresh="fetchData" />

                    <!-- Основной контент: Задачи и Проекты -->
                    <div :class="['grid grid-cols-1 gap-6', panelView === 'list' ? 'xl:grid-cols-[1fr_380px]' : '']">

                        <div class="flex flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-white/5 dark:bg-zinc-900/60">
                            <!-- Табы + переключение вида -->
                            <div class="m-3 flex flex-wrap items-center gap-2">
                                <div v-if="panelView === 'list'" class="flex flex-1 flex-wrap gap-1 rounded-lg bg-zinc-100/70 p-1 dark:bg-black/20">
                                    <button v-for="tab in tabs"
                                            :key="tab.id"
                                            @click="activeTab = tab.id"
                                            :class="[
                                            'flex-1 flex items-center justify-center gap-1.5 py-2 px-2 rounded-md text-xs font-semibold transition-all whitespace-nowrap',
                                            'focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400',
                                            activeTab === tab.id ? 'bg-white text-zinc-900 shadow-sm dark:bg-white/10 dark:text-white' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300'
                                        ]"
                                    >
                                        {{ tab.label }}
                                        <span v-if="tab.count" class="rounded-md bg-zinc-200/70 px-1.5 py-0.5 text-[10px] font-bold text-zinc-600 dark:bg-white/10 dark:text-zinc-300">
                                            {{ tab.count }}
                                        </span>
                                    </button>
                                </div>
                                <h3 v-else class="flex-1 px-1 text-sm font-bold text-zinc-900 dark:text-white">
                                    Доска
                                </h3>

                                <div class="flex shrink-0 rounded-lg bg-zinc-100/70 p-1 dark:bg-black/20">
                                    <button
                                        type="button"
                                        title="Список по категориям"
                                        class="flex items-center justify-center rounded-md p-1.5 transition
                                               focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400"
                                        :class="
                                            panelView === 'list'
                                                ? 'bg-white text-zinc-700 shadow-sm dark:bg-white/10 dark:text-white'
                                                : 'text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300'
                                        "
                                        @click="panelView = 'list'"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                        </svg>
                                    </button>

                                    <button
                                        type="button"
                                        title="Доска по срокам"
                                        class="flex items-center justify-center rounded-md p-1.5 transition
                                               focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400"
                                        :class="
                                            panelView === 'board'
                                                ? 'bg-white text-zinc-700 shadow-sm dark:bg-white/10 dark:text-white'
                                                : 'text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300'
                                        "
                                        @click="panelView = 'board'"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h3a1 1 0 011 1v14a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM10 5a1 1 0 011-1h3a1 1 0 011 1v8a1 1 0 01-1 1h-3a1 1 0 01-1-1V5zM16 5a1 1 0 011-1h3a1 1 0 011 1v11a1 1 0 01-1 1h-3a1 1 0 01-1-1V5z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="flex-1 px-5 pb-5">
                                <TasksBoard v-if="panelView === 'board'" :items="boardItems" />

                                <Transition v-else name="fade" mode="out-in">
                                    <div v-if="activeTab === 'tasks'" key="tasks">
                                        <TasksSummary :tasks="summary.all_tasks" title="Мои текущие задачи" :show-filters="true" />
                                    </div>
                                    <div v-else-if="activeTab === 'subtasks'" key="subtasks">
                                        <SubtasksSection :subtasks="summary.all_subtasks" />
                                    </div>
                                    <div v-else-if="activeTab === 'klient_tasks'" key="klient_tasks">
                                        <TasksSummary :tasks="summary.klient_tasks" title="Задачи клиентов" :show-filters="true" />
                                    </div>
                                    <div v-else-if="activeTab === 'deals'" key="deals">
                                        <TasksSummary :tasks="summary.klient_deals" title="Сделки" :show-filters="true" />
                                    </div>
                                    <div v-else key="media_plans">
                                        <TasksSummary :tasks="summary.media_plans" title="Медиапланы" :show-filters="true" />
                                    </div>
                                </Transition>
                            </div>
                        </div>

                        <aside v-if="panelView === 'list'" class="space-y-3">
                            <ActivityFeed :activities="activities" @remove="removeActivity" @clear="clearActivities" />
                            <TasksSummary :tasks="summary.due_today" title="Сегодня" variant="warning" compact />
                            <TasksSummary :tasks="summary.overdue" title="Просрочено" variant="danger" compact />
                        </aside>
                    </div>
                </template>
            </div>
        </div>

        <SearchOverlay v-if="isSearchOpen" @close="isSearchOpen = false" :companies="companies" :summary="summary" />
        <CreateCompanyModal v-if="showCreateModal" @close="showCreateModal = false" @created="fetchData" />
    </AuthenticatedLayout>
</template>

<style>
.fade-enter-active, .fade-leave-active { transition: all 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(4px); }
</style>
