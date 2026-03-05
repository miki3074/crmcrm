<script setup>
import { ref, onMounted, computed } from 'vue'
import { Head, usePage, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import axios from 'axios'

// Импорт компонентов
import StatCard from './AAA/Components/Dashboard/StatCard.vue'
import TelegramBinding from './AAA/Components/Dashboard/TelegramBinding.vue'
import CompanySection from './AAA/Components/Dashboard/CompanySection.vue'
import ProjectsSummary from './AAA/Components/Dashboard/ProjectsSummary.vue'
import TasksSummary from './AAA/Components/Dashboard/TasksSummary.vue'
import SubtasksSection from './AAA/Components/Dashboard/SubtasksSection.vue'
import SearchOverlay from './AAA/Components/Dashboard/SearchOverlay.vue'
import CreateCompanyModal from './AAA/Components/Dashboard/CreateCompanyModal.vue'

const { props } = usePage()
const isAdmin = computed(() => props.auth?.roles?.includes('admin'))
const userId = props.auth?.user?.id

// Состояние
const companies = ref([])
const summary = ref({ managing_projects: [], all_tasks: [], all_subtasks: [], due_today: [], overdue: [] })
const loading = ref(true)
const searchQuery = ref('')
const isSearchOpen = ref(false)
const showCreateModal = ref(false)
const activeTab = ref('tasks')

// Загрузка данных
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
        console.error("Ошибка загрузки данных", e)
    } finally {
        loading.value = false
    }
}

const hasProjects = computed(() => {
    return props.summary?.managing_projects?.length > 0
})

const openSearch = () => { isSearchOpen.value = true }

const showWelcomeModal = ref(false)

onMounted(() => {
    fetchData()
    const hasSeenModal = sessionStorage.getItem('welcome_modal_shown')
    if (!hasSeenModal) {
        showWelcomeModal.value = true
        sessionStorage.setItem('welcome_modal_shown', 'true')
    }
})

// Обработчик горячих клавиш для поиска
onMounted(() => {
    const handleKeyDown = (e) => {
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
            e.preventDefault()
            openSearch()
        }
    }
    window.addEventListener('keydown', handleKeyDown)
    return () => window.removeEventListener('keydown', handleKeyDown)
})
</script>

<template>
    <Head title="Рабочий стол" />

    <AuthenticatedLayout>
        <!-- Минималистичный контейнер с воздухом -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12">

            <!-- Хедер с градиентом и приветствием -->
            <div class="relative mb-12">
                <!-- Декоративный градиентный фон -->
                <div class="absolute inset-0 bg-gradient-to-r from-indigo-50/50 via-transparent to-transparent dark:from-indigo-950/20 rounded-3xl -z-10"></div>

                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                    <div class="space-y-2">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-1 bg-gradient-to-b from-indigo-500 to-purple-500 rounded-full"></div>
                            <div>
                                <h1 class="text-3xl lg:text-4xl font-light tracking-tight text-slate-900 dark:text-white">
                                    Доброе утро,
                                    <span class="font-semibold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                                        {{ props.auth.user.name }}
                                    </span>
                                </h1>
                                <p class="text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-2">
                                    <span class="inline-block w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                    {{ new Date().toLocaleDateString('ru-RU', {
                                    weekday: 'long',
                                    day: 'numeric',
                                    month: 'long',
                                    year: 'numeric'
                                }) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <TelegramBinding :user="props.auth.user" />
                </div>
            </div>

            <!-- Поиск с эффектом стекла -->
            <div class="mb-10 group" @click="openSearch">
                <div class="relative cursor-pointer">
                    <div class="absolute inset-0 bg-gradient-to-r from-indigo-500/10 to-purple-500/10 rounded-2xl blur-xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                    <div class="relative flex items-center gap-4 px-6 py-4 bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl border border-slate-200/50 dark:border-slate-800/50 rounded-2xl shadow-lg shadow-slate-200/20 dark:shadow-slate-900/20 group-hover:shadow-xl transition-all duration-300">
                        <div class="text-slate-400 group-hover:text-indigo-500 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <span class="text-slate-400 flex-1 text-sm tracking-wide">Поиск компаний, проектов или задач...</span>
                        <div class="hidden sm:flex items-center gap-1 px-3 py-1.5 bg-slate-100 dark:bg-slate-800 rounded-lg text-xs font-mono text-slate-500 border border-slate-200 dark:border-slate-700">
                            <span>⌘</span>
                            <span>K</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Карточки действий с минималистичным дизайном -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-12">
                <StatCard
                    v-for="(card, index) in [
                {
                    title: 'Календарь',
                    icon: '📅',
                    color: 'violet',
                    description: 'Планы и события',
                    action: () => router.visit('/calendar')
                },
                {
                    title: 'Хранилище',
                    icon: '📂',
                    color: 'blue',
                    description: 'Файлы и документы',
                    action: () => router.visit('/file-storage')
                },
                ...(isAdmin ? [{
                    title: 'Сотрудники',
                    icon: '👥',
                    color: 'indigo',
                    description: 'Управление командой',
                    action: () => router.visit('/employees')
                }] : []),
                ...(isAdmin ? [{
                    title: 'Клиенты',
                    icon: '🤝',
                    color: 'amber',
                    description: 'База клиентов',
                    action: () => router.visit('/clients')
                }] : []),
                {
                    title: 'Схема',
                    icon: '🗺️',
                    color: 'emerald',
                    description: 'Визуализация проектов',
                    action: () => router.visit('/mapdiagram')
                },
                ...(isAdmin ? [{
                    title: 'Создать',
                    icon: '✨',
                    color: 'rose',
                    description: 'Новую компанию',
                    action: () => showCreateModal = true,
                    count: '+'
                }] : [])
            ]"
                    :key="index"
                    v-bind="card"
                />
            </div>

            <!-- Основной контент с сеткой -->
            <div class="space-y-8">

                <!-- Компании с новым дизайном -->
                <section class="relative">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="h-6 w-1 bg-gradient-to-b from-indigo-500 to-indigo-300 rounded-full"></div>
                        <h2 class="text-lg font-medium text-slate-700 dark:text-slate-300 tracking-wide">ВАШИ КОМПАНИИ</h2>
                    </div>
                    <CompanySection
                        :companies="companies"
                        :user-id="userId"
                        :is-admin="isAdmin"
                        @refresh="fetchData"
                        class="bg-white/50 dark:bg-slate-900/50 backdrop-blur-sm rounded-2xl border border-slate-200/50 dark:border-slate-800/50 p-6"
                    />
                </section>

                <!-- Асимметричная сетка для задач и проектов -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                    <!-- Левая колонка (занимает 2/3) -->
                    <div class="lg:col-span-2 space-y-8">

                        <!-- Табы с контентом -->
                        <div class="bg-white/50 dark:bg-slate-900/50 backdrop-blur-sm rounded-2xl border border-slate-200/50 dark:border-slate-800/50 overflow-hidden">
                            <!-- Минималистичные табы -->
                            <div class="flex px-6 pt-4 gap-8 border-b border-slate-100 dark:border-slate-800">
                                <button
                                    @click="activeTab = 'tasks'"
                                    class="relative pb-3 text-sm font-medium transition-colors"
                                    :class="activeTab === 'tasks'
                                        ? 'text-indigo-600 dark:text-indigo-400'
                                        : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                                >
                                    Задачи
                                    <span v-if="summary.all_tasks?.length"
                                          class="ml-2 text-xs px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800">
                                        {{ summary.all_tasks.length }}
                                    </span>
                                    <div v-if="activeTab === 'tasks'"
                                         class="absolute bottom-0 left-0 right-0 h-0.5 bg-gradient-to-r from-indigo-500 to-indigo-300 rounded-full">
                                    </div>
                                </button>
                                <button
                                    @click="activeTab = 'subtasks'"
                                    class="relative pb-3 text-sm font-medium transition-colors"
                                    :class="activeTab === 'subtasks'
                                        ? 'text-indigo-600 dark:text-indigo-400'
                                        : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                                >
                                    Подзадачи
                                    <span v-if="summary.all_subtasks?.length"
                                          class="ml-2 text-xs px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800">
                                        {{ summary.all_subtasks.length }}
                                    </span>
                                    <div v-if="activeTab === 'subtasks'"
                                         class="absolute bottom-0 left-0 right-0 h-0.5 bg-gradient-to-r from-indigo-500 to-indigo-300 rounded-full">
                                    </div>
                                </button>
                            </div>

                            <!-- Контент табов -->
                            <div class="p-6">
                                <Transition name="fade" mode="out-in">
                                    <div v-if="activeTab === 'tasks'" key="tasks">
                                        <TasksSummary :tasks="summary.all_tasks" />
                                    </div>
                                    <div v-else key="subtasks">
                                        <SubtasksSection :subtasks="summary.all_subtasks" />
                                    </div>
                                </Transition>
                            </div>
                        </div>

                        <!-- Компактные карточки сроков -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="bg-white/50 dark:bg-slate-900/50 backdrop-blur-sm rounded-xl border border-slate-200/50 dark:border-slate-800/50 p-5">
                                <h3 class="text-sm font-medium text-slate-500 dark:text-slate-400 mb-3 flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                    СЕГОДНЯ
                                </h3>
                                <TasksSummary :tasks="summary.due_today" compact />
                            </div>
                            <div class="bg-white/50 dark:bg-slate-900/50 backdrop-blur-sm rounded-xl border border-slate-200/50 dark:border-slate-800/50 p-5">
                                <h3 class="text-sm font-medium text-slate-500 dark:text-slate-400 mb-3 flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                                    ПРОСРОЧЕНО
                                </h3>
                                <TasksSummary :tasks="summary.overdue" compact variant="danger" />
                            </div>
                        </div>
                    </div>

                    <!-- Правая колонка (занимает 1/3) -->
                    <div class="lg:col-span-1">
                        <div class="bg-white/50 dark:bg-slate-900/50 backdrop-blur-sm rounded-2xl border border-slate-200/50 dark:border-slate-800/50 p-6 sticky top-6">

                            <ProjectsSummary :projects="summary.managing_projects" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Модальные окна -->
        <SearchOverlay v-if="isSearchOpen" @close="isSearchOpen = false" :companies="companies" :summary="summary" />
        <CreateCompanyModal v-if="showCreateModal" @close="showCreateModal = false" @created="fetchData" />
    </AuthenticatedLayout>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: all 0.2s ease-out;
}

.fade-enter-from {
    opacity: 0;
    transform: translateX(10px);
}

.fade-leave-to {
    opacity: 0;
    transform: translateX(-10px);
}

/* Плавное появление элементов */
section {
    animation: slideInUp 0.5s ease-out forwards;
    opacity: 0;
}

section:nth-child(1) { animation-delay: 0.1s; }
section:nth-child(2) { animation-delay: 0.2s; }
section:nth-child(3) { animation-delay: 0.3s; }

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Стилизация скроллбара */
::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

::-webkit-scrollbar-track {
    background: transparent;
}

::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 20px;
}

.dark ::-webkit-scrollbar-thumb {
    background: #475569;
}

::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

.dark ::-webkit-scrollbar-thumb:hover {
    background: #64748b;
}
</style>
