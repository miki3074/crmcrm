<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import { Head, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

// Импорт компонентов
import ProjectHeader from '../AAA/Components/Project/ProjectHeader.vue'
import ProjectMenu from '../AAA/Components/Project/ProjectMenu.vue'
import ProjectTasks from '../AAA/Components/Project/ProjectTasks.vue'
import ProjectSidebar from '../AAA/Components/Project/ProjectSidebar.vue'

const { props } = usePage()
const projectId = props.id

const project = ref(null)
const employees = ref([])
const loading = ref(true)
const activeTab = ref('tasks') // tasks, files, activity

const fetchData = async () => {
    try {
        const [projectRes, employeesRes] = await Promise.all([
            axios.get(`/api/projects/${projectId}`),
            axios.get(`/api/projects/${projectId}/employees`)
        ])
        project.value = projectRes.data
        employees.value = employeesRes.data
    } catch (e) {
        console.error(e)
    } finally {
        loading.value = false
    }
}

const onRefresh = async () => {
    const { data } = await axios.get(`/api/projects/${projectId}`)
    project.value = data
}

// Прогресс проекта
const projectProgress = computed(() => {
    if (!project.value?.tasks) return 0
    const completed = project.value.tasks.filter(t => t.status === 'completed').length
    const total = project.value.tasks.length
    return total > 0 ? Math.round((completed / total) * 100) : 0
})

onMounted(fetchData)
</script>

<template>
    <Head :title="project?.name ? `Проект — ${project.name}` : 'Проект'"/>
    <AuthenticatedLayout>

        <!-- HERO SECTION С ДИНАМИЧЕСКИМ ГРАДИЕНТОМ -->
        <div v-if="project" class="relative mb-12">
            <!-- Анимированный фон -->
            <div class="absolute inset-0 bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-600 rounded-[2.5rem] shadow-2xl overflow-hidden">
                <!-- Шумовая текстура -->
                <div class="absolute inset-0 opacity-10 mix-blend-overlay"
                     style="background-image: url('data:image/svg+xml,%3Csvg viewBox=\'0 0 200 200\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cfilter id=\'noise\'%3E%3CfeTurbulence type=\'fractalNoise\' baseFrequency=\'0.65\' numOctaves=\'3\' stitchTiles=\'stitch\'/%3E%3C/filter%3E%3Crect width=\'100%25\' height=\'100%25\' filter=\'url(%23noise)\' opacity=\'0.5\'/%3E%3C/svg%3E')">
                </div>
                <!-- Анимированные частицы -->
                <div class="absolute top-0 left-0 w-64 h-64 bg-white/20 rounded-full blur-3xl animate-pulse-slow"></div>
                <div class="absolute bottom-0 right-0 w-96 h-96 bg-black/10 rounded-full blur-3xl animate-pulse-slower"></div>
            </div>

            <!-- Основной контент -->
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Стеклянная карточка -->
                <div class="bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/30 dark:border-slate-700/30 overflow-hidden transform transition-all duration-500 hover:scale-[1.01]">

                    <!-- Верхний акцент -->
                    <div class="h-1 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500"></div>

                    <div class="p-8 lg:p-10">
                        <div class="flex flex-col lg:flex-row lg:justify-between lg:items-start gap-8">

                            <!-- Левая часть с прогрессом -->
                            <div class="flex-1 space-y-6">
                                <!-- Заголовок и статус -->
                                <div class="space-y-4">
                                    <ProjectHeader :project="project"/>

                                    <!-- Прогресс бар -->
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-slate-600 dark:text-slate-400">Общий прогресс</span>
                                            <span class="font-semibold text-indigo-600 dark:text-indigo-400">{{ projectProgress }}%</span>
                                        </div>
                                        <div class="w-full h-2 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                            <div class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full transition-all duration-1000"
                                                 :style="{ width: projectProgress + '%' }"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Быстрая статистика -->
                                <div class="grid grid-cols-3 gap-4 pt-2">
                                    <div class="bg-indigo-50/50 dark:bg-indigo-950/30 rounded-xl p-3 text-center">
                                        <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ project.tasks?.length || 0 }}</div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400">Всего задач</div>
                                    </div>
                                    <div class="bg-emerald-50/50 dark:bg-emerald-950/30 rounded-xl p-3 text-center">
                                        <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                                            {{ project.tasks?.filter(t => t.status === 'completed').length || 0 }}
                                        </div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400">Завершено</div>
                                    </div>
                                    <div class="bg-amber-50/50 dark:bg-amber-950/30 rounded-xl p-3 text-center">
                                        <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">
                                            {{ project.managers?.length || 0 }}
                                        </div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400">Участников</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Правая часть с меню -->
                            <div class="lg:w-80 space-y-4">
                                <!-- Карточка с меню -->
                                <div class="bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-950/30 dark:to-purple-950/30 rounded-2xl p-6 shadow-inner">
                                    <ProjectMenu
                                        :project="project"
                                        :user="props.auth.user"
                                        :employees="employees"
                                        @refresh="onRefresh"
                                    />
                                </div>

                                <!-- Дата и время -->
                                <div class="flex items-center justify-between text-sm text-slate-500 dark:text-slate-400 bg-white/50 dark:bg-slate-800/50 rounded-xl px-4 py-2">
                                    <span class="flex items-center gap-2">
                                        <span>📅</span>
                                        Обновлено:
                                    </span>
                                    <span class="font-mono">{{ new Date().toLocaleDateString('ru-RU') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- BODY С ТАБАМИ -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">

            <!-- Состояние загрузки -->
            <div v-if="loading" class="flex justify-center items-center py-32">
                <div class="relative">
                    <div class="w-20 h-20 border-4 border-indigo-200 dark:border-indigo-900 border-t-indigo-600 dark:border-t-indigo-400 rounded-full animate-spin"></div>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="w-8 h-8 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-full animate-pulse"></div>
                    </div>
                </div>
            </div>

            <!-- Основной контент -->
            <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <!-- Левая колонка (2/3) - Табы и задачи -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Табы навигации -->
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-slate-100 dark:border-slate-700 p-1">
                        <div class="flex gap-1">
                            <button @click="activeTab = 'tasks'"
                                    class="flex-1 px-6 py-3 rounded-xl text-sm font-medium transition-all duration-300 relative overflow-hidden group"
                                    :class="activeTab === 'tasks'
                                        ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-lg shadow-indigo-500/30'
                                        : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700'">
                                <span class="relative z-10 flex items-center justify-center gap-2">
                                    <span>📋</span>
                                    Задачи
                                    <span class="px-1.5 py-0.5 text-xs rounded-full"
                                          :class="activeTab === 'tasks' ? 'bg-white/30' : 'bg-slate-200 dark:bg-slate-700'">
                                        {{ project?.tasks?.length || 0 }}
                                    </span>
                                </span>
                            </button>
                            <button @click="activeTab = 'files'"
                                    class="flex-1 px-6 py-3 rounded-xl text-sm font-medium transition-all duration-300"
                                    :class="activeTab === 'files'
                                        ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-lg shadow-indigo-500/30'
                                        : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700'">
                                <span class="flex items-center justify-center gap-2">
                                    <span>📁</span>
                                    Файлы
                                </span>
                            </button>
                            <button @click="activeTab = 'activity'"
                                    class="flex-1 px-6 py-3 rounded-xl text-sm font-medium transition-all duration-300"
                                    :class="activeTab === 'activity'
                                        ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-lg shadow-indigo-500/30'
                                        : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700'">
                                <span class="flex items-center justify-center gap-2">
                                    <span>📊</span>
                                    Активность
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Контент табов -->
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-100 dark:border-slate-700 overflow-hidden">
                        <!-- Заголовок секции -->
                        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700 bg-gradient-to-r from-indigo-50/50 to-transparent dark:from-indigo-950/20">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-1 h-6 bg-gradient-to-b from-indigo-500 to-purple-500 rounded-full"></div>
                                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                                        {{ activeTab === 'tasks' ? 'Задачи проекта' : activeTab === 'files' ? 'Файлы проекта' : 'История активности' }}
                                    </h2>
                                </div>

                                <!-- Кнопка создания (для задач) -->
                                <button v-if="activeTab === 'tasks'"
                                        class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl text-sm font-medium hover:shadow-lg hover:shadow-indigo-500/30 transition-all hover:scale-105 active:scale-95">
                                    + Новая задача
                                </button>
                            </div>
                        </div>

                        <!-- Компонент задач с анимацией появления -->
                        <Transition name="fade" mode="out-in">
                            <div v-if="activeTab === 'tasks'" key="tasks" class="p-6">
                                <ProjectTasks
                                    :project="project"
                                    :user="props.auth.user"
                                    :employees="employees"
                                    @refresh="onRefresh"
                                />
                            </div>
                            <div v-else-if="activeTab === 'files'" key="files" class="p-12 text-center text-slate-400">
                                <span class="text-6xl mb-4 block opacity-30">📁</span>
                                <p class="text-lg font-medium">Файлы появятся здесь</p>
                                <p class="text-sm mt-2">Загрузите файлы, связанные с проектом</p>
                            </div>
                            <div v-else-if="activeTab === 'activity'" key="activity" class="p-12 text-center text-slate-400">
                                <span class="text-6xl mb-4 block opacity-30">📊</span>
                                <p class="text-lg font-medium">История активности пуста</p>
                                <p class="text-sm mt-2">Здесь будет отображаться активность команды</p>
                            </div>
                        </Transition>
                    </div>
                </div>

                <!-- Правая колонка (1/3) - Sidebar с информацией -->
                <div class="space-y-6">
                    <!-- Основной сайдбар -->
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-100 dark:border-slate-700 overflow-hidden sticky top-24 transition-all duration-300 hover:shadow-2xl">
                        <!-- Декоративная полоса -->
                        <div class="h-1 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500"></div>

                        <div class="p-6">
                            <!-- Заголовок сайдбара -->
                            <div class="flex items-center gap-3 mb-6">
                                <div class="w-1 h-8 bg-gradient-to-b from-indigo-500 to-purple-500 rounded-full"></div>
                                <div>
                                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
                                        Детали проекта
                                    </h3>
                                    <p class="text-xs text-slate-500 mt-0.5">Полная информация</p>
                                </div>
                            </div>

                            <!-- Компонент сайдбара -->
                            <ProjectSidebar :project="project"/>

                            <!-- Дополнительная информация -->
                            <div class="mt-6 pt-6 border-t border-slate-100 dark:border-slate-700">
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-slate-500">Создан</span>
                                        <span class="font-medium text-slate-700 dark:text-slate-300">
                                            {{ new Date(project.created_at).toLocaleDateString('ru-RU') }}
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-slate-500">ID проекта</span>
                                        <span class="font-mono text-xs bg-slate-100 dark:bg-slate-700 px-2 py-1 rounded">
                                            #{{ project.id }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Быстрые действия -->
                    <div class="bg-gradient-to-br from-indigo-600 to-purple-600 rounded-2xl shadow-xl p-6 text-white">
                        <h4 class="font-medium mb-4 opacity-90">Быстрые действия</h4>
                        <div class="space-y-2">
                            <button class="w-full px-4 py-3 bg-white/20 hover:bg-white/30 rounded-xl text-sm font-medium transition-all flex items-center gap-3 backdrop-blur-sm">
                                <span>➕</span>
                                Добавить задачу
                            </button>
                            <button class="w-full px-4 py-3 bg-white/20 hover:bg-white/30 rounded-xl text-sm font-medium transition-all flex items-center gap-3 backdrop-blur-sm">
                                <span>👥</span>
                                Пригласить участника
                            </button>
                            <button class="w-full px-4 py-3 bg-white/20 hover:bg-white/30 rounded-xl text-sm font-medium transition-all flex items-center gap-3 backdrop-blur-sm">
                                <span>📊</span>
                                Отчет по проекту
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>

<style scoped>
/* Анимации для табов */
.fade-enter-active,
.fade-leave-active {
    transition: all 0.3s ease;
}

.fade-enter-from {
    opacity: 0;
    transform: translateX(20px);
}

.fade-leave-to {
    opacity: 0;
    transform: translateX(-20px);
}

/* Анимации для градиента */
@keyframes gradient {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

@keyframes pulse-slow {
    0%, 100% { opacity: 0.2; transform: scale(1); }
    50% { opacity: 0.3; transform: scale(1.1); }
}

@keyframes pulse-slower {
    0%, 100% { opacity: 0.1; transform: scale(1); }
    50% { opacity: 0.2; transform: scale(1.2); }
}

.animate-pulse-slow {
    animation: pulse-slow 8s ease-in-out infinite;
}

.animate-pulse-slower {
    animation: pulse-slower 12s ease-in-out infinite;
}

.bg-gradient-to-r {
    background-size: 200% 200%;
    animation: gradient 15s ease infinite;
}

/* Эффект стекла */
.backdrop-blur-xl {
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
}

/* Улучшенные тени */
.shadow-2xl {
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

/* Плавные переходы */
* {
    transition: background-color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
}

/* Кастомный скроллбар */
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

/* Адаптивность */
@media (max-width: 1024px) {
    .sticky {
        position: relative;
        top: 0;
    }
}
</style>
