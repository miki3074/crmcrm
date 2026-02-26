<script setup>
import { ref, onMounted, computed } from 'vue'
import { Head, usePage, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import axios from 'axios'

// Импорт компонентов (создадим их ниже)
import StatCard from './home/Dashboard/StatCard.vue'
import TelegramBinding from './home/Dashboard/TelegramBinding.vue'
import CompanySection from './home/Dashboard/CompanySection.vue'
import ProjectsSummary from './home/Dashboard/ProjectsSummary.vue'
import TasksSummary from './home/Dashboard/TasksSummary.vue'
import SubtasksSection from './home/Dashboard/SubtasksSection.vue'
import SearchOverlay from './home/Dashboard/SearchOverlay.vue'
import CreateCompanyModal from './home/Dashboard/CreateCompanyModal.vue'

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




// Состояние активной вкладки ('tasks' по умолчанию)
const activeTab = ref('tasks')

const hasProjects = computed(() => {
    return props.summary.managing_projects && props.summary.managing_projects.length > 0
})

onMounted(fetchData)

// Обработка поиска (⌘K или клик)
const openSearch = () => { isSearchOpen.value = true }


const showWelcomeModal = ref(false)

onMounted(() => {
    const hasSeenModal = sessionStorage.getItem('welcome_modal_shown')

    if (!hasSeenModal) {
        showWelcomeModal.value = true
        sessionStorage.setItem('welcome_modal_shown', 'true')
    }
})


</script>

<template>
    <Head title="Рабочий стол" />

    <AuthenticatedLayout>

        <div class=" mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-10">

<!--            <TelegramBinding :user="props.auth.user" />-->

<!--            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">-->
<!--                <StatCard title="Календарь" icon="📅" color="purple" @click="router.visit('/calendar')" />-->
<!--                <StatCard title="Хранилище" icon="📂" color="blue" @click="router.visit('/file-storage')" />-->
<!--                <StatCard v-if="isAdmin" title="Сотрудники" icon="👥" color="indigo" @click="router.visit('/employees')" />-->
<!--                <StatCard v-if="isAdmin" title="Клиенты" icon="🤝" color="orange" @click="router.visit('/clients')" />-->
<!--                <StatCard title="Схема" icon="🗺️" color="emerald" @click="router.visit('/mapdiagram')" />-->
<!--                <StatCard v-if="isAdmin" title="Создать" icon="➕" color="rose" @click="showCreateModal = true" />-->
<!--            </div>-->
            <div class="header">
                <div class="logo-area">
                    <div class="logo-icon"><i class="fas fa-rocket"></i></div>
                    <div class="logo-text">
                        <h2>Orion CRM</h2>
                        <span>управление компаниями</span>
                    </div>
                </div>
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Поиск компаний, проектов, задач...">
                </div>
                <div class="user-profile">
                    <div class="notification-badge">
                        <i class="far fa-bell"></i>
                    </div>
                    <div class="avatar">
                        <div class="avatar-img">АП</div>
                        <span class="avatar-name">Алексей Павлов</span>
                        <i class="fas fa-chevron-down" style="font-size: 12px; color: #8192aa;"></i>
                    </div>
                </div>
            </div>
            <!-- Глобальный поиск -->
            <div class="relative group">
                <div @click="openSearch" class="w-full flex items-center gap-3 px-5 py-4 bg-white/50 dark:bg-slate-900/50 backdrop-blur-xl border border-slate-200 dark:border-slate-800 rounded-2xl cursor-text hover:border-slate-300 dark:hover:border-slate-700 transition-all shadow-sm">
                    <span class="text-slate-400 text-xl">🔍</span>
                    <span class="text-slate-400 flex-1">Поиск по компаниям, проектам или задачам...</span>
                    <kbd class="hidden sm:inline-block px-2 py-1 bg-slate-100 dark:bg-slate-800 rounded text-xs text-slate-500 border border-slate-200 dark:border-slate-700">⌘K</kbd>
                </div>
            </div>

            <div class="tabs">
                <div class="tab active"><i class="fas fa-th-large"></i> Главная</div>
                <div class="tab"><i class="fas fa-building"></i> Компании</div>
                <div class="tab"><i class="fas fa-tasks"></i> Проекты</div>
                <div class="tab"><i class="fas fa-check-circle"></i> Задачи</div>
                <div class="tab"><i class="fas fa-users"></i> Команда</div>
                <div class="tab"><i class="fas fa-chart-line"></i> Аналитика</div>
            </div>

            <div class="content">
                <!-- Приветственная строка и стата -->
                <div class="welcome-row">
                    <div class="greeting-card">
                        <h2>Доброе утро, <span>Алексей</span> 👋</h2>
                        <p><i class="fas fa-calendar-alt" style="color: #4361ee;"></i> 22 февраля 2026 · Понедельник</p>
                        <p style="margin-top: 16px; color: #3f5579;">✅ Выполнено 8 из 14 задач на сегодня. Отличный темп!</p>
                    </div>
                    <div class="stats-cards">
                        <div class="stat-item">
                            <div class="stat-title"><i class="fas fa-building" style="color:#4361ee;"></i> Компании</div>
                            <div class="stat-number">24</div>
                            <div class="stat-change"><i class="fas fa-arrow-up"></i> +3 на этой неделе</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-title"><i class="fas fa-tasks" style="color:#9c4dff;"></i> Проекты</div>
                            <div class="stat-number">41</div>
                            <div class="stat-change" style="background:#e0e7ff; color:#4338ca;">12 в работе</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-title"><i class="fas fa-check-double" style="color:#10b981;"></i> Задачи</div>
                            <div class="stat-number">156</div>
                            <div class="stat-change" style="background:#fee2e2; color:#b91c1c;">8 просрочено</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Секции данных -->
            <div class="space-y-12">

                <div class="dashboard-grid">
                <!-- Компании -->


                <CompanySection
                    :companies="companies"
                    :user-id="userId"
                    :is-admin="isAdmin"
                    @refresh="fetchData"
                />

                 <ProjectsSummary
                     :projects="summary.managing_projects"
                 />

                    <div class="grid grid-cols-1 lg:grid-cols-[7fr_3fr] gap-8 items-start">


                        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">


                            <div class="flex items-center border-b border-slate-100 dark:border-slate-800">


                                <button
                                    @click="activeTab = 'tasks'"
                                    class="flex-1 py-4 text-sm font-bold transition-all relative outline-none"
                                    :class="activeTab === 'tasks'
                        ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50/50 dark:bg-indigo-900/10'
                        : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50'"
                                >
                                    ✅ Мои задачи
                                    <span v-if="summary.all_tasks?.length" class="ml-2 bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-300 px-2 py-0.5 rounded-full text-xs">
                        {{ summary.all_tasks.length }}
                    </span>
                                    <div v-if="activeTab === 'tasks'" class="absolute bottom-0 left-0 w-full h-[2px] bg-indigo-600 dark:bg-indigo-400"></div>
                                </button>


                                <div class="w-[1px] h-6 bg-slate-200 dark:bg-slate-700"></div>


                                <button
                                    @click="activeTab = 'subtasks'"
                                    class="flex-1 py-4 text-sm font-bold transition-all relative outline-none"
                                    :class="activeTab === 'subtasks'
                        ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50/50 dark:bg-indigo-900/10'
                        : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50'"
                                >
                                    📋 Подзадачи
                                    <span v-if="summary.all_subtasks?.length" class="ml-2 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400 px-2 py-0.5 rounded-full text-xs">
                        {{ summary.all_subtasks.length }}
                    </span>
                                    <div v-if="activeTab === 'subtasks'" class="absolute bottom-0 left-0 w-full h-[2px] bg-indigo-600 dark:bg-indigo-400"></div>
                                </button>
                            </div>


                            <div class="p-6 min-h-[300px]">
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




                    </div>

                </div>


                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <TasksSummary :tasks="summary.due_today" title="🔔 Сроки сегодня" compact />
                    <TasksSummary :tasks="summary.overdue" title="⚠️ Просрочено" compact variant="danger" />
                </div>
            </div>
        </div>

        <!-- Модалки -->
        <SearchOverlay v-if="isSearchOpen" @close="isSearchOpen = false" :companies="companies" :summary="summary" />
        <CreateCompanyModal v-if="showCreateModal" @close="showCreateModal = false" @created="fetchData" />

    </AuthenticatedLayout>
</template>

<style scoped>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
}

body {
    background: #f4f6fb;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    padding: 20px;
}

/* главный контейнер — дашборд */
.dashboard {
    max-width: 1440px;
    width: 100%;
    background: #ffffff;
    border-radius: 32px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

/* шапка */
.header {
    padding: 24px 32px;
    background: white;
    border-bottom: 1px solid #eef2f6;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
}

.logo-area {
    display: flex;
    align-items: center;
    gap: 12px;
}

.logo-icon {
    background: linear-gradient(135deg, #4361ee, #9c4dff);
    width: 44px;
    height: 44px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
    box-shadow: 0 6px 12px rgba(67, 97, 238, 0.2);
}

.logo-text h2 {
    font-size: 20px;
    font-weight: 600;
    color: #1e293b;
    letter-spacing: -0.3px;
}

.logo-text span {
    font-size: 13px;
    color: #64748b;
    font-weight: 400;
}

.search-bar {
    background: #f8fafd;
    padding: 10px 20px;
    border-radius: 60px;
    display: flex;
    align-items: center;
    gap: 10px;
    border: 1px solid #e2e8f0;
    width: 300px;
    transition: 0.2s;
}

.search-bar:focus-within {
    border-color: #4361ee;
    box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
}

.search-bar i {
    color: #94a3b8;
    font-size: 16px;
}

.search-bar input {
    border: none;
    background: transparent;
    outline: none;
    width: 100%;
    font-size: 15px;
}

.user-profile {
    display: flex;
    align-items: center;
    gap: 20px;
}

.notification-badge {
    position: relative;
    font-size: 22px;
    color: #475569;
    cursor: pointer;
}

.notification-badge::after {
    content: '';
    position: absolute;
    top: 2px;
    right: 4px;
    width: 8px;
    height: 8px;
    background: #ef4444;
    border-radius: 50%;
    border: 2px solid white;
}

.avatar {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #f1f4f9;
    padding: 6px 12px 6px 6px;
    border-radius: 40px;
    cursor: pointer;
    transition: 0.2s;
}

.avatar:hover {
    background: #e6eaf0;
}

.avatar-img {
    width: 38px;
    height: 38px;
    background: linear-gradient(145deg, #4361ee, #6c5ce7);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 16px;
}

.avatar-name {
    font-weight: 500;
    color: #1e293b;
    font-size: 14px;
}

/* основная навигация (табы) */
.tabs {
    display: flex;
    gap: 8px;
    padding: 0 32px;
    background: white;
    border-bottom: 1px solid #eef2f6;
}

.tab {
    padding: 16px 20px;
    font-weight: 500;
    color: #64748b;
    cursor: pointer;
    border-bottom: 3px solid transparent;
    transition: 0.15s;
    font-size: 15px;
}

.tab i {
    margin-right: 8px;
    font-size: 15px;
}

.tab.active {
    color: #4361ee;
    border-bottom-color: #4361ee;
    font-weight: 600;
}

/* основное содержимое */
.content {
    padding: 32px;
    background: #f9fbfe;
}

/* карточки приветствия и статистики */
.welcome-row {
    display: flex;
    gap: 24px;
    flex-wrap: wrap;
    margin-bottom: 32px;
}

.greeting-card {
    background: linear-gradient(145deg, #ffffff, #f9f9ff);
    flex: 2;
    min-width: 280px;
    border-radius: 24px;
    padding: 28px 30px;
    box-shadow: 0 8px 20px -8px rgba(0, 27, 65, 0.1);
    border: 1px solid #ffffff80;
}

.greeting-card h2 {
    font-size: 26px;
    font-weight: 600;
    color: #0b1b33;
}

.greeting-card h2 span {
    color: #4361ee;
}

.greeting-card p {
    margin-top: 8px;
    color: #475569;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 15px;
}

.stats-cards {
    flex: 3;
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.stat-item {
    background: white;
    border-radius: 24px;
    padding: 20px 22px;
    flex: 1 1 140px;
    box-shadow: 0 5px 15px -8px #b0b9ce;
    border: 1px solid #eef2f6;
    transition: 0.15s;
}

.stat-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 25px -12px #a0abc0;
}

.stat-title {
    color: #64748b;
    font-size: 14px;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.stat-number {
    font-size: 32px;
    font-weight: 700;
    color: #1e293b;
}

.stat-change {
    margin-top: 8px;
    font-size: 13px;
    color: #10b981;
    background: #d1fae5;
    display: inline-block;
    padding: 4px 10px;
    border-radius: 30px;
}

/* секции с карточками компаний, проектов, задач */
.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
    gap: 24px;
    margin-top: 20px;
}

.card {
    background: white;
    border-radius: 26px;
    padding: 24px;
    box-shadow: 0 8px 24px -10px #cdd8e6;
    border: 1px solid #eaedf2;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 22px;
}

.card-header h3 {
    font-weight: 600;
    font-size: 18px;
    color: #1e293b;
}

.card-header i {
    color: #94a3b8;
    background: #f1f5f9;
    padding: 8px;
    border-radius: 50%;
    transition: 0.15s;
    cursor: pointer;
}

.card-header i:hover {
    background: #e0e7ff;
    color: #4361ee;
}

/* списки компаний / проектов */
.company-item, .project-item, .task-item {
    display: flex;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f0f3f7;
}

.company-item:last-child, .project-item:last-child, .task-item:last-child {
    border-bottom: none;
}

.company-avatar {
    width: 42px;
    height: 42px;
    background: #ecf2ff;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    color: #4361ee;
    margin-right: 14px;
}

.company-info h4, .project-info h4 {
    font-size: 16px;
    font-weight: 600;
    color: #0f182a;
}

.company-info p, .project-info p {
    font-size: 13px;
    color: #5f6c84;
    margin-top: 2px;
}

.company-meta, .project-meta {
    margin-left: auto;
    font-size: 13px;
    background: #f1f5f9;
    padding: 4px 12px;
    border-radius: 40px;
    color: #334155;
}

/* проекты — особый стиль */
.project-progress {
    margin-left: auto;
    display: flex;
    align-items: center;
    gap: 12px;
}

.progress-bar {
    width: 70px;
    height: 6px;
    background: #e4e9f2;
    border-radius: 10px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    width: 65%;
    background: #4361ee;
    border-radius: 10px;
}

.task-item {
    display: flex;
    align-items: center;
}

.task-check {
    margin-right: 15px;
    color: #cbd5e1;
    cursor: pointer;
    transition: 0.1s;
}

.task-check:hover {
    color: #4361ee;
}

.task-check.completed {
    color: #10b981;
}

.task-content {
    flex: 1;
}

.task-title {
    font-weight: 500;
    color: #1e293b;
}

.task-deadline {
    font-size: 12px;
    color: #8b9bb5;
    display: flex;
    align-items: center;
    gap: 4px;
    margin-top: 4px;
}

.task-priority {
    font-size: 11px;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 50px;
    background: #fee2e2;
    color: #b91c1c;
}

.priority-high {
    background: #fee2e2;
    color: #b91c1c;
}

.priority-medium {
    background: #fef9c3;
    color: #854d0e;
}

.priority-low {
    background: #dcfce7;
    color: #166534;
}

.task-more {
    margin-left: 10px;
    color: #94a3b8;
    cursor: pointer;
}

/* быстрые действия */
.quick-actions {
    margin-top: 32px;
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    background: white;
    padding: 18px 28px;
    border-radius: 40px;
    border: 1px solid #eef2f6;
}

.actions-left {
    display: flex;
    gap: 15px;
}

.action-btn {
    background: #f0f4fe;
    border: none;
    padding: 10px 22px;
    border-radius: 40px;
    font-weight: 500;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: 0.15s;
    border: 1px solid transparent;
}

.action-btn i {
    color: #4361ee;
}

.action-btn:hover {
    background: #e6edfd;
    border-color: #cddfff;
}

.date-badge {
    color: #475569;
    background: #f1f5f9;
    padding: 8px 22px;
    border-radius: 40px;
    font-size: 14px;
    font-weight: 500;
}

/* адаптивность */
@media (max-width: 850px) {
    .header {
        flex-direction: column;
        align-items: stretch;
    }
    .search-bar {
        width: 100%;
    }
    .tabs {
        overflow-x: auto;
        padding: 0 16px;
    }
}

/* Простая анимация плавного появления */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
