<script setup>
import { ref, onMounted, computed } from 'vue'
import { usePage, Head } from '@inertiajs/vue3'
import axios from 'axios'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

// Новые компоненты
import TaskHero from '../AAA/Components/Task/TaskHero.vue'
import TaskStats from '../AAA/Components/Task/TaskStats.vue'
import TaskSubtasks from '../AAA/Components/Task/TaskSubtasks.vue'
import TaskSidebar from '../AAA/Components/Task/TaskSidebar.vue'
import TaskChat from '@/Components/TaskChat.vue'
import TaskChecklists from '@/Components/TaskChecklists.vue'
import TaskActionModals from '../AAA/Components/Task/Modals/TaskActionModals.vue'
import TaskPersonnelModals from '../AAA/Components/Task/Modals/TaskPersonnelModals.vue'
import TaskDocumentApproval from "@/Pages/AAA/Components/Task/TaskDocumentApproval.vue";

const { props } = usePage()
const taskId = props.id
const user = props.auth?.user

// --- STATE ---
const task = ref(null)
const companyEmployees = ref([])
const loading = ref(true)
const activeTab = ref('details') // details, subtasks, files, activity

// Состояние модалок
const modals = ref({
    edit: false, description: false, delete: false, subtask: false,
    executor: false, responsible: false,
    addExecutor: false, addResponsible: false, addWatcher: false, manage: false
})

// --- PERMISSIONS ---
const perms = computed(() => {
    if(!task.value) return {}
    const isOwner = user.id === task.value.project?.company?.user_id
    const isCreator = user.id === task.value.creator?.id
    const isExec = task.value.executors?.some(e => e.id === user.id)
    const isResp = task.value.responsibles?.some(r => r.id === user.id)
    const isProjExec = task.value.project?.executors?.some(e => e.id === user.id)
    const isProjMgr = task.value.project?.managers?.some(m => m.id === user.id)

    const canManageTask = isCreator || isExec || isResp
    const canManageMembers = isOwner || isProjMgr || isProjExec

    return {
        canCreateSubtask: isOwner || isCreator || isResp || isExec || isProjMgr || isProjExec,
        canUpdate: isOwner || isProjMgr || isProjExec,
        canDelete: isOwner || isProjMgr || isProjExec,
        canUpload: isExec || isResp || isProjExec || isOwner,
        canFinish: (task.value.progress === 100) && !task.value.completed && !task.value.subtasks?.some(s => !s.completed),
        canManageMembers,
        canManageTask
    }
})

// --- STATISTICS ---
const taskStats = computed(() => {
    if (!task.value) return {}
    const totalSubtasks = task.value.subtasks?.length || 0
    const completedSubtasks = task.value.subtasks?.filter(s => s.completed).length || 0
    const subtaskProgress = totalSubtasks > 0 ? Math.round((completedSubtasks / totalSubtasks) * 100) : 0
    const filesCount = task.value.files?.length || 0

    return {
        totalSubtasks,
        completedSubtasks,
        subtaskProgress,
        filesCount,
        daysLeft: task.value.due_date ? Math.ceil((new Date(task.value.due_date) - new Date()) / (1000 * 60 * 60 * 24)) : null
    }
})

// --- API FETCH ---
const fetchTask = async () => {
    loading.value = true
    try {
        const { data } = await axios.get(`/api/tasks/${taskId}`)
        task.value = data
        const empRes = await axios.get(`/api/projects/${data.project.id}/employees`)
        companyEmployees.value = empRes.data
    } catch(e) { console.error(e) }
    finally { loading.value = false }
}

// --- ACTIONS ---
const updateTask = async (form) => {
    await axios.put(`/api/tasks/${taskId}`, form)
    modals.value.edit = false; await fetchTask()
}
const saveDescription = async (desc) => {
    await axios.patch(`/api/tasks/${taskId}/description`, { description: desc })
    modals.value.description = false; await fetchTask()
}
const updateProgress = async (val) => {
    await axios.patch(`/api/tasks/${taskId}/progress`, { progress: val })
    task.value.progress = val
}
const deleteTask = async () => {
    await axios.delete(`/api/tasks/${taskId}`)
    window.history.back()
}
const finishTask = async () => {
    await axios.patch(`/api/tasks/${taskId}/complete`)
    await fetchTask()
}
const uploadFiles = async (files) => {
    const fd = new FormData()
    for(let i=0; i<files.length; i++) fd.append('files[]', files[i])
    await axios.post(`/api/tasks/${taskId}/files`, fd)
    await fetchTask()
}
const deleteFile = async (id) => {
    if(confirm('Удалить?')) { await axios.delete(`/api/tasks/files/${id}`); await fetchTask() }
}
const createSubtask = async (form) => {
    await axios.post(`/api/tasks/${taskId}/subtasks`, form)
    modals.value.subtask = false; await fetchTask()
}

// --- PERSONNEL ACTIONS ---
const handlePersonnelChange = async ({ type, oldId, newId }) => {
    const endpoint = type === 'executor' ? 'executor' : 'responsible'
    await axios.patch(`/api/tasks/${taskId}/${endpoint}`, { replace_user_id: oldId, user_id: newId })
    modals.value[type] = false; await fetchTask()
}
const handlePersonnelAdd = async ({ type, ids, singleId }) => {
    if (type === 'watcher') {
        await axios.post(`/api/tasks/${taskId}/watchers`, { user_id: singleId })
        modals.value.addWatcher = false
    } else {
        const endpoint = type === 'executor' ? 'executors' : 'responsibles'
        await axios.post(`/api/tasks/${taskId}/${endpoint}/add`, { user_ids: ids })
        modals.value['add' + type.charAt(0).toUpperCase() + type.slice(1)] = false
    }
    await fetchTask()
}
const handlePersonnelRemove = async ({ role, id }) => {
    const endpoint = role === 'watcherstask' ? 'watchers' : role
    await axios.delete(`/api/tasks/${taskId}/${endpoint}`, { data: { user_id: id } })
    await fetchTask()
}

const onStartWork = async (emittedId) => {
    const id = emittedId || task.value?.id || taskId;
    if (!id) return;
    try {
        const { data } = await axios.post(`/api/tasks/${id}/start`)
        task.value = data.task
    } catch (e) {
        console.error(e)
        alert(e.response?.data?.message || 'Ошибка')
    }
}

onMounted(fetchTask)
</script>

<template>
    <Head :title="task?.title || 'Задача'" />
    <AuthenticatedLayout>

        <!-- Состояние загрузки -->
        <div v-if="loading" class="min-h-screen flex items-center justify-center">
            <div class="relative">
                <div class="w-20 h-20 border-4 border-indigo-200 dark:border-indigo-900 border-t-indigo-600 dark:border-t-indigo-400 rounded-full animate-spin"></div>
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="w-8 h-8 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-full animate-pulse"></div>
                </div>
            </div>
        </div>

        <!-- Основной контент -->
        <div v-else-if="task" class="min-h-screen bg-gradient-to-b from-slate-50 to-white dark:from-slate-900 dark:to-slate-950">

            <!-- Hero секция с параллакс-эффектом -->
            <div class="relative mb-8">
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-600 opacity-90"></div>
                <div class="absolute inset-0 bg-[url('/img/grid.svg')] opacity-10"></div>

                <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <TaskHero
                        :task="task"
                        :perms="perms"
                        :stats="taskStats"
                        @startWork="onStartWork"
                        @edit="modals.edit = true"
                        @delete="modals.delete = true"
                        @description="modals.description = true"
                        @back="() => $inertia.visit(`/projects/${task.project_id}`)"
                        @finish="finishTask"
                        @changeExecutor="modals.executor = true"
                        @changeResponsible="modals.responsible = true"
                        @addExecutor="modals.addExecutor = true"
                        @addResponsible="modals.addResponsible = true"
                        @addWatcher="modals.addWatcher = true"
                        @manageMembers="modals.manage = true"
                    />
                </div>
            </div>

            <!-- Основной контент -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12 -mt-8">

                <!-- Табы навигации -->
                <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-2xl shadow-lg border border-slate-200/50 dark:border-slate-700/50 p-1 mb-6">
                    <div class="flex gap-1">
                        <button @click="activeTab = 'details'"
                                class="flex-1 px-6 py-3 rounded-xl text-sm font-medium transition-all duration-300 relative overflow-hidden group"
                                :class="activeTab === 'details'
                                    ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-lg shadow-indigo-500/30'
                                    : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700'">
                            <span class="relative z-10 flex items-center justify-center gap-2">
                                <span>📋</span>
                                Детали
                            </span>
                        </button>
                        <button @click="activeTab = 'subtasks'"
                                class="flex-1 px-6 py-3 rounded-xl text-sm font-medium transition-all duration-300"
                                :class="activeTab === 'subtasks'
                                    ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-lg shadow-indigo-500/30'
                                    : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700'">
                            <span class="flex items-center justify-center gap-2">
                                <span>📌</span>
                                Подзадачи
                                <span v-if="task.subtasks?.length"
                                      class="px-1.5 py-0.5 text-xs rounded-full"
                                      :class="activeTab === 'subtasks' ? 'bg-white/30' : 'bg-slate-200 dark:bg-slate-700'">
                                    {{ task.subtasks.length }}
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
                                <span v-if="task.files?.length"
                                      class="px-1.5 py-0.5 text-xs rounded-full"
                                      :class="activeTab === 'files' ? 'bg-white/30' : 'bg-slate-200 dark:bg-slate-700'">
                                    {{ task.files.length }}
                                </span>
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

                <!-- Сетка контента -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <!-- Левая колонка (2/3) -->
                    <div class="lg:col-span-2 space-y-6">

                        <!-- Stats & Files (всегда видно) -->
                        <TaskStats
                            :task="task"
                            :loading="loading"
                            :can-upload="perms.canUpload"
                            @updateProgress="updateProgress"
                            @uploadFiles="uploadFiles"
                            @deleteFile="deleteFile"
                        />

                        <!-- Динамический контент по табам -->
                        <Transition name="fade" mode="out-in">
                            <!-- Таб деталей -->
                            <div v-if="activeTab === 'details'" key="details" class="space-y-6">
                                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                                    <div class="p-6">
                                        <div class="flex items-center gap-3 mb-4">
                                            <div class="w-1 h-6 bg-gradient-to-b from-indigo-500 to-purple-500 rounded-full"></div>
                                            <h3 class="text-lg font-semibold text-slate-800 dark:text-white">Описание задачи</h3>
                                        </div>
                                        <div class="prose prose-slate dark:prose-invert max-w-none">
                                            <p class="text-slate-600 dark:text-slate-300 whitespace-pre-line">
                                                {{ task.description || 'Нет описания' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                                    <div class="p-6">
                                        <TaskDocumentApproval
                                            :task="task"
                                            :current-user="user"
                                            @refresh="fetchTask"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Таб подзадач -->
                            <div v-else-if="activeTab === 'subtasks'" key="subtasks">
                                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                                    <div class="p-6">
                                        <div class="flex items-center justify-between mb-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-1 h-6 bg-gradient-to-b from-indigo-500 to-purple-500 rounded-full"></div>
                                                <h3 class="text-lg font-semibold text-slate-800 dark:text-white">Подзадачи</h3>
                                            </div>
                                            <span class="text-sm text-slate-500">
                                                Прогресс: {{ taskStats.completedSubtasks }}/{{ taskStats.totalSubtasks }}
                                            </span>
                                        </div>
                                        <TaskSubtasks
                                            :subtasks="task.subtasks"
                                            :can-create="perms.canCreateSubtask"
                                            @create="modals.subtask = true"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Таб файлов -->
                            <div v-else-if="activeTab === 'files'" key="files">
                                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                                    <div class="p-6">
                                        <div class="flex items-center gap-3 mb-4">
                                            <div class="w-1 h-6 bg-gradient-to-b from-indigo-500 to-purple-500 rounded-full"></div>
                                            <h3 class="text-lg font-semibold text-slate-800 dark:text-white">Все файлы</h3>
                                        </div>
                                        <div v-if="!task.files?.length" class="text-center py-8">
                                            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                                                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z" />
                                                </svg>
                                            </div>
                                            <p class="text-slate-500">Файлы не загружены</p>
                                        </div>
                                        <div v-else class="grid grid-cols-2 gap-3">
                                            <div v-for="file in task.files" :key="file.id"
                                                 class="group p-4 bg-slate-50 dark:bg-slate-700/30 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-indigo-300 transition-all cursor-pointer"
                                                 @click="downloadFile(file.file_path, file.file_path.split('/').pop())">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z" />
                                                        </svg>
                                                    </div>
                                                    <div class="flex-1 min-w-0">
                                                        <p class="text-sm font-medium text-slate-700 dark:text-slate-300 truncate">
                                                            {{ file.file_path.split('/').pop() }}
                                                        </p>
                                                        <p class="text-xs text-slate-500">
                                                            {{ new Date(file.created_at).toLocaleDateString() }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Таб активности -->
                            <div v-else-if="activeTab === 'activity'" key="activity">
                                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                                    <div class="p-6 text-center py-12">
                                        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                                            <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <h4 class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">История активности</h4>
                                        <p class="text-xs text-slate-400">Здесь будет отображаться история изменений задачи</p>
                                    </div>
                                </div>
                            </div>
                        </Transition>
                    </div>

                    <!-- Правая колонка (1/3) - Sidebar -->
                    <div class="lg:col-span-1">
                        <div class="sticky top-24 space-y-6">
                            <TaskSidebar :task="task" :stats="taskStats" />

                            <!-- Быстрые действия -->
                            <div class="bg-gradient-to-br from-indigo-600 to-purple-600 rounded-2xl shadow-xl p-6 text-white">
                                <h4 class="font-medium mb-4 opacity-90 flex items-center gap-2">
                                    <span>⚡</span>
                                    Быстрые действия
                                </h4>
                                <div class="space-y-2">
                                    <button v-if="perms.canCreateSubtask"
                                            @click="modals.subtask = true"
                                            class="w-full px-4 py-3 bg-white/20 hover:bg-white/30 rounded-xl text-sm font-medium transition-all flex items-center gap-3 backdrop-blur-sm">
                                        <span>➕</span>
                                        Добавить подзадачу
                                    </button>
                                    <button v-if="perms.canUpload"
                                            @click="$refs.fileInput.click()"
                                            class="w-full px-4 py-3 bg-white/20 hover:bg-white/30 rounded-xl text-sm font-medium transition-all flex items-center gap-3 backdrop-blur-sm">
                                        <span>📎</span>
                                        Загрузить файл
                                    </button>
                                    <button v-if="perms.canFinish"
                                            @click="finishTask"
                                            class="w-full px-4 py-3 bg-white/20 hover:bg-white/30 rounded-xl text-sm font-medium transition-all flex items-center gap-3 backdrop-blur-sm">
                                        <span>✅</span>
                                        Завершить задачу
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ошибка доступа -->
        <div v-else class="min-h-screen flex items-center justify-center">
            <div class="text-center">
                <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-rose-100 dark:bg-rose-900/30 flex items-center justify-center text-4xl">
                    🔒
                </div>
                <h2 class="text-2xl font-bold text-slate-800 dark:text-white mb-2">Нет доступа</h2>
                <p class="text-slate-500">У вас нет прав для просмотра этой задачи</p>
            </div>
        </div>

        <!-- MODALS -->
        <TaskActionModals
            :modals="modals"
            :task="task"
            :employees="companyEmployees"
            @close="(key) => modals[key] = false"
            @update="updateTask"
            @saveDescription="saveDescription"
            @deleteTask="deleteTask"
            @createSubtask="createSubtask"
        />

        <TaskPersonnelModals
            :modals="modals"
            :task="task"
            :employees="companyEmployees"
            @close="(key) => modals[key] = false"
            @change="handlePersonnelChange"
            @add="handlePersonnelAdd"
            @remove="handlePersonnelRemove"
        />

        <!-- Скрытый input для загрузки файлов -->
        <input ref="fileInput" type="file" multiple class="hidden" @change="uploadFiles($event.target.files)">

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

.animate-slideIn {
    animation: slideIn 0.3s ease-out;
}

/* Эффект стекла */
.backdrop-blur-xl {
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
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
</style>
