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

const { props } = usePage()
const isAdmin = computed(() => props.auth?.roles?.includes('admin'))
const userId = props.auth?.user?.id

const companies = ref([])
const summary = ref({ managing_projects: [], all_tasks: [], all_subtasks: [], due_today: [], overdue: [] })
const loading = ref(true)
const isSearchOpen = ref(false)
const showCreateModal = ref(false)
const activeTab = ref('tasks')

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
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

            <!-- Верхняя панель: Телеграм -->
            <TelegramBinding :user="props.auth.user" />

            <!-- Быстрые действия -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <StatCard title="Календарь" icon="📅" color="violet" @click="router.visit('/calendar')" />
                <StatCard title="Хранилище" icon="📂" color="blue" @click="router.visit('/file-storage')" />
                <StatCard v-if="isAdmin" title="Сотрудники" icon="👥" color="indigo" @click="router.visit('/employees')" />
                <StatCard v-if="isAdmin" title="Клиенты" icon="🤝" color="amber" @click="router.visit('/klients')" />
                <StatCard title="Схема" icon="🗺️" color="emerald" @click="router.visit('/mapdiagram')" />
                <StatCard v-if="isAdmin" title="Создать" icon="➕" color="rose" @click="showCreateModal = true" />
            </div>

            <!-- Поиск -->
            <div @click="isSearchOpen = true"
                 class="group relative flex items-center gap-4 px-6 py-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl cursor-pointer hover:ring-4 hover:ring-indigo-500/5 transition-all shadow-sm">
                <span class="text-xl group-hover:scale-110 transition-transform">🔍</span>
                <span class="text-slate-400 flex-1 font-medium">Поиск по всему пространству...</span>
                <kbd class="hidden sm:inline-block px-2.5 py-1 bg-slate-100 dark:bg-slate-800 rounded-lg text-[10px] font-bold text-slate-500 border border-slate-200 dark:border-slate-700 uppercase">⌘ K</kbd>
            </div>

            <!-- Компании -->
            <CompanySection :companies="companies" :user-id="userId" :is-admin="isAdmin" @refresh="fetchData" />

            <!-- Основной контент: Задачи и Проекты -->
            <div class="grid grid-cols-1 xl:grid-cols-[1fr_380px] gap-8">

                <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden flex flex-col">
                    <!-- Табы -->
                    <div class="flex p-2 bg-slate-50/50 dark:bg-slate-800/50 m-4 rounded-2xl border border-slate-100 dark:border-slate-800">
                        <button v-for="tab in [{id:'tasks', label:'Задачи', icon:'✅', count: summary.all_tasks?.length}, {id:'subtasks', label:'Подзадачи', icon:'📋'}]"
                                :key="tab.id"
                                @click="activeTab = tab.id"
                                :class="[
                                'flex-1 flex items-center justify-center gap-2 py-3 rounded-xl text-sm font-bold transition-all',
                                activeTab === tab.id ? 'bg-white dark:bg-slate-700 shadow-sm text-indigo-600 dark:text-indigo-400' : 'text-slate-500 hover:text-slate-700'
                            ]"
                        >
                            <span>{{ tab.icon }}</span>
                            {{ tab.label }}
                            <span v-if="tab.count" class="bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 px-2 py-0.5 rounded-md text-[10px]">
                                {{ tab.count }}
                            </span>
                        </button>
                    </div>

                    <div class="px-6 pb-6 flex-1">
                        <Transition name="fade" mode="out-in">
                            <div v-if="activeTab === 'tasks'" key="tasks">
                                <TasksSummary :tasks="summary.all_tasks" title="Мои текущие задачи" :show-filters="true" />
                            </div>
                            <div v-else key="subtasks">
                                <SubtasksSection :subtasks="summary.all_subtasks" />
                            </div>
                        </Transition>
                    </div>
                </div>



                <aside class="space-y-6">
                    <ProjectsSummary :projects="summary.managing_projects" />
                    <div class="space-y-4">
                        <TasksSummary :tasks="summary.due_today" title="Сегодня" variant="warning" compact />
                        <TasksSummary :tasks="summary.overdue" title="Просрочено" variant="danger" compact />
                    </div>
                </aside>
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
