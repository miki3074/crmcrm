<script setup>
import { ref, onMounted, computed } from 'vue'
import { Head, usePage, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import axios from 'axios'

// Импорт компонентов (создадим их ниже)
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
<!--        <template #header>-->
<!--            <div class="flex items-center justify-between">-->
<!--                <h2 class="text-2xl font-bold bg-gradient-to-r from-slate-800 to-slate-500 dark:from-white dark:to-slate-400 bg-clip-text text-transparent">-->
<!--                    Панель управления-->
<!--                </h2>-->
<!--                <div class="flex gap-2">-->
<!--                    <span v-for="role in props.auth.roles" :key="role"-->
<!--                          class="px-3 py-1 text-xs font-medium rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">-->
<!--                        {{ role }}-->
<!--                    </span>-->
<!--                </div>-->
<!--            </div>-->
<!--        </template>-->


        <!-- WELCOME MODAL -->
<!--        <div-->
<!--            v-if="showWelcomeModal"-->
<!--            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm"-->
<!--        >-->
<!--            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-xl w-full mx-4 shadow-2xl border border-slate-200 dark:border-slate-800">-->

<!--                &lt;!&ndash; Header &ndash;&gt;-->
<!--                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center">-->
<!--                    <h2 class="text-lg font-bold" >-->
<!--                        ⚠️ Важная информация-->
<!--                    </h2>-->
<!--                    <button-->
<!--                        @click="showWelcomeModal = false"-->
<!--                        class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-xl"-->
<!--                    >-->
<!--                        ✕-->
<!--                    </button>-->
<!--                </div>-->

<!--                &lt;!&ndash; Content &ndash;&gt;-->
<!--                <div class="px-6 py-5 space-y-4 text-sm text-slate-600 dark:text-slate-300">-->
<!--                    <p>-->
<!--                         Обновлен интерфейс рабочего стола.-->
<!--                    </p>-->

<!--                    <p>-->
<!--                        Если у вас возникнут трудности в работе с новой версией, вы всегда можете-->
<!--                        временно вернуться к прежнему интерфейсу.-->
<!--                    </p>-->

<!--                    <p class="text-xs text-slate-500 dark:text-slate-400">-->
<!--                        Мы будем рады вашей обратной связи — она поможет нам сделать систему лучше.-->
<!--                    </p>-->
<!--                </div>-->

<!--                <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-800 flex justify-between items-center">-->
<!--                    <button-->
<!--                        @click="router.visit('/dashboardold')"-->
<!--                        class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700-->
<!--               text-white font-semibold text-sm"-->
<!--                    >-->
<!--                        Перейти на старую версию-->
<!--                    </button>-->

<!--                    <button-->
<!--                        @click="showWelcomeModal = false"-->
<!--                        class="-->
<!--px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200-->
<!--               dark:bg-slate-800 dark:hover:bg-slate-700-->
<!--               text-slate-700 dark:text-slate-200 text-sm font-semibold-->
<!--"-->
<!--                    >-->
<!--                        Понятно-->
<!--                    </button>-->
<!--                </div>-->



<!--            </div>-->
<!--        </div>-->



        <div class=" mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-10">



            <!-- Интеграция с ТГ -->
            <TelegramBinding :user="props.auth.user" />

            <!-- Сетка быстрых действий -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <StatCard title="Календарь" icon="📅" color="purple" @click="router.visit('/calendar')" />
                <StatCard title="Хранилище" icon="📂" color="blue" @click="router.visit('/file-storage')" />
                <StatCard v-if="isAdmin" title="Сотрудники" icon="👥" color="indigo" @click="router.visit('/employees')" />
                <StatCard v-if="isAdmin" title="Клиенты" icon="🤝" color="orange" @click="router.visit('/clients')" />
                <StatCard title="Схема" icon="🗺️" color="emerald" @click="router.visit('/mapdiagram')" />
                <StatCard v-if="isAdmin" title="Создать" icon="➕" color="rose" @click="showCreateModal = true" />
            </div>

            <!-- Глобальный поиск -->
            <div class="relative group">
                <div @click="openSearch" class="w-full flex items-center gap-3 px-5 py-4 bg-white/50 dark:bg-slate-900/50 backdrop-blur-xl border border-slate-200 dark:border-slate-800 rounded-2xl cursor-text hover:border-slate-300 dark:hover:border-slate-700 transition-all shadow-sm">
                    <span class="text-slate-400 text-xl">🔍</span>
                    <span class="text-slate-400 flex-1">Поиск по компаниям, проектам или задачам...</span>
                    <kbd class="hidden sm:inline-block px-2 py-1 bg-slate-100 dark:bg-slate-800 rounded text-xs text-slate-500 border border-slate-200 dark:border-slate-700">⌘K</kbd>
                </div>
            </div>

            <!-- Секции данных -->
            <div class="space-y-12">
                <!-- Компании -->
                <CompanySection
                    :companies="companies"
                    :user-id="userId"
                    :is-admin="isAdmin"
                    @refresh="fetchData"
                />

                <!-- Проекты (Я руковожу) -->


                <!-- Задачи -->
<!--                <TasksSummary :tasks="summary.all_tasks" title="✅ Мои задачи" />-->
<!--                <SubtasksSection :subtasks="summary.all_subtasks" />-->



                <div class="grid grid-cols-1 lg:grid-cols-[7fr_3fr] gap-8 items-start">

                    <!-- ЛЕВАЯ КОЛОНКА (70%): Табы с задачами -->
                    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">

                        <!-- Шапка с кнопками переключения -->
                        <div class="flex items-center border-b border-slate-100 dark:border-slate-800">

                            <!-- Таб 1: Задачи -->
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

                            <!-- Разделитель -->
                            <div class="w-[1px] h-6 bg-slate-200 dark:bg-slate-700"></div>

                            <!-- Таб 2: Подзадачи -->
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

                        <!-- Контент табов -->
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

                    <!-- ПРАВАЯ КОЛОНКА (30%): Проекты -->
                    <!-- Если ProjectsSummary не имеет своих стилей контейнера, можно обернуть его в div с белым фоном, как слева -->

                    <div class="h-full">
                        <ProjectsSummary :projects="summary.managing_projects" />
                    </div>

                </div>


                <!-- Дедлайны -->
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
