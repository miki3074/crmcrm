<script setup>
import { ref, onMounted, computed } from 'vue'
import { Head, usePage, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import axios from 'axios'

// Компоненты
import StatCard from './AAA/Components/Dashboard/StatCard.vue'
import TelegramBinding from './AAA/Components/Dashboard/TelegramBinding.vue'
import CompanySection from './AAA/Components/Dashboard/CompanySection.vue'
import ProjectsSummary from './AAA/Components/Dashboard/ProjectsSummary.vue'
import TasksSummary from './AAA/Components/Dashboard/TasksSummary.vue'
import SubtasksSection from './AAA/Components/Dashboard/SubtasksSection.vue'
import SearchOverlay from './AAA/Components/Dashboard/SearchOverlay.vue'
import CreateCompanyModal from './AAA/Components/Dashboard/CreateCompanyModal.vue'
import EmailVerificationModal from './AAA/Components/Dashboard/EmailVerificationModal.vue'

const { props } = usePage()
const isAdmin = computed(() => props.auth?.roles?.includes('admin'))
const userId = props.auth?.user?.id
const userEmail = props.auth?.user?.email
const emailVerified = ref(props.auth?.user?.email_verified_at !== null)

const companies = ref([])
const summary = ref({
    managing_projects: [],
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
    { id: 'tasks', label: 'Задачи', icon: '✅', count: summary.value.all_tasks?.length },
    { id: 'subtasks', label: 'Подзадачи', icon: '📋', count: undefined },
    { id: 'klient_tasks', label: 'Задачи клиентов', icon: '🗂️', count: summary.value.klient_tasks?.length },
    { id: 'deals', label: 'Сделки', icon: '🤝', count: summary.value.klient_deals?.length },
    { id: 'media_plans', label: 'Медиапланы', icon: '📡', count: summary.value.media_plans?.length },
])

const showEmailVerificationModal = ref(!emailVerified.value) // Показываем если email не подтвержден

console.log('emailVerified.value:', emailVerified.value)
console.log('showEmailVerificationModal:', showEmailVerificationModal.value)
console.log('userEmail:', userEmail)
console.log('props.auth?.user?.email_verified_at:', props.auth?.user?.email_verified_at)

// Также можно добавить watch для отслеживания
import { watch } from 'vue'
watch(showEmailVerificationModal, (newVal) => {
    console.log('showEmailVerificationModal changed:', newVal)
})

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
}

const handleEmailVerified = () => {
    emailVerified.value = true
    // Обновляем данные пользователя
    router.reload({ only: ['auth'] })
}

onMounted(() => {
    fetchData()
    // Shortcut ⌘K
    window.addEventListener('keydown', (e) => {
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
            e.preventDefault()
            isSearchOpen.value = true
        }
    })
})
</script>

<template>
    <Head title="Рабочий стол" />

    <AuthenticatedLayout>
        <div class="relative min-h-full bg-zinc-50 dark:bg-[#08090d]">
            <!-- Фоновая сетка панели -->
            <div class="pointer-events-none absolute inset-0 opacity-[0.35] dark:opacity-100"
                 style="background-image: radial-gradient(rgba(56,189,248,0.08) 1px, transparent 1px); background-size: 22px 22px; mask-image: linear-gradient(to bottom, black, transparent 70%);" />

            <div class="relative mx-auto max-w-[1600px] space-y-6 px-4 py-6 sm:px-6 lg:px-8">

                <EmailVerificationModal
                    :show="showEmailVerificationModal"
                    :user-email="userEmail"
                    :is-verified="emailVerified"
                    @close="showEmailVerificationModal = false"
                    @verified="handleEmailVerified"
                />

                <!-- Верхняя панель: Телеграм -->
    <!--            <TelegramBinding :user="props.auth.user" />-->

                <!-- Заголовок панели -->
                <header class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-zinc-200 bg-white/70 px-5 py-4 dark:border-white/5 dark:bg-zinc-900/40">
                    <div class="flex items-center gap-3">
                        <span class="h-2 w-2 rounded-full bg-emerald-400 shadow-[0_0_10px_2px_rgba(52,211,153,0.6)]" />
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-cyan-600 dark:text-cyan-400">Рабочая панель</p>
                            <h1 class="text-lg font-bold text-zinc-900 dark:text-white">Обзор пространства</h1>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 text-[11px] font-bold uppercase tracking-wide text-zinc-400">
                        <span>{{ summary.all_tasks?.length || 0 }} задач</span>
                        <span class="h-3 w-px bg-zinc-300 dark:bg-white/10" />
                        <span class="text-amber-500">{{ summary.due_today?.length || 0 }} сегодня</span>
                        <span class="h-3 w-px bg-zinc-300 dark:bg-white/10" />
                        <span class="text-rose-500">{{ summary.overdue?.length || 0 }} просрочено</span>
                    </div>
                </header>

                <!-- Быстрые действия -->
                <div class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-6">
                    <StatCard title="Календарь" icon="📅" color="violet" @click="router.visit('/calendar')" />
                    <StatCard title="База знанйи" icon="📂" color="blue" @click="router.visit('/knowledge')" />
                    <StatCard v-if="isAdmin" title="Сотрудники" icon="👥" color="indigo" @click="router.visit('/employees')" />
                    <StatCard v-if="isAdmin" title="Клиенты" icon="🤝" color="amber" @click="router.visit('/klients')" />
                    <StatCard title="Схема" icon="🗺️" color="emerald" @click="router.visit('/mapdiagram')" />
                    <StatCard v-if="isAdmin" title="Создать" icon="➕" color="rose" @click="showCreateModal = true" />
                </div>

                <!-- Поиск -->
                <div @click="isSearchOpen = true"
                     class="group relative flex items-center gap-4 rounded-xl border border-zinc-200 bg-white px-5 py-3.5 shadow-sm transition-all cursor-pointer hover:border-cyan-300 hover:shadow-md dark:border-white/5 dark:bg-zinc-900/60 dark:hover:border-cyan-500/30">
                    <span class="text-lg text-zinc-400 transition-transform group-hover:scale-110 group-hover:text-cyan-500">🔍</span>
                    <span class="flex-1 font-medium text-zinc-400">Поиск по всему пространству...</span>
                    <kbd class="hidden sm:inline-block rounded-md border border-zinc-200 bg-zinc-100 px-2.5 py-1 text-[10px] font-bold uppercase text-zinc-500 dark:border-white/10 dark:bg-white/5 dark:text-zinc-400">⌘ K</kbd>
                </div>

                <!-- Компании -->
                <CompanySection :companies="companies" :user-id="userId" :is-admin="isAdmin" @refresh="fetchData" />

                <!-- Основной контент: Задачи и Проекты -->
                <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_380px]">

                    <div class="flex flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-white/5 dark:bg-zinc-900/40">
                        <!-- Табы -->
                        <div class="m-3 flex flex-wrap gap-1 rounded-lg border border-zinc-100 bg-zinc-50/60 p-1 dark:border-white/5 dark:bg-black/20">
                            <button v-for="tab in tabs"
                                    :key="tab.id"
                                    @click="activeTab = tab.id"
                                    :class="[
                                    'flex-1 flex items-center justify-center gap-2 py-2.5 px-2 rounded-md text-xs font-bold uppercase tracking-wide transition-all whitespace-nowrap',
                                    activeTab === tab.id ? 'bg-white text-cyan-600 shadow-sm dark:bg-cyan-500/10 dark:text-cyan-300' : 'text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300'
                                ]"
                            >
                                <span>{{ tab.icon }}</span>
                                {{ tab.label }}
                                <span v-if="tab.count" class="rounded-md bg-cyan-100 px-2 py-0.5 text-[10px] text-cyan-600 dark:bg-cyan-500/10 dark:text-cyan-300">
                                    {{ tab.count }}
                                </span>
                            </button>
                        </div>

                        <div class="flex-1 px-5 pb-5">
                            <Transition name="fade" mode="out-in">
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

                    <aside class="space-y-5">
                        <ProjectsSummary :projects="summary.managing_projects" />
                        <div class="space-y-3">
                            <TasksSummary :tasks="summary.due_today" title="Сегодня" variant="warning" compact />
                            <TasksSummary :tasks="summary.overdue" title="Просрочено" variant="danger" compact />
                        </div>
                    </aside>
                </div>
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
