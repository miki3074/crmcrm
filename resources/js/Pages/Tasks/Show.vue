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

// Состояние модалок (объект вместо кучи переменных)
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

// --- API FETCH ---
const fetchTask = async () => {
    loading.value = true
    try {
        const { data } = await axios.get(`/api/tasks/${taskId}`)
        task.value = data
        // В задаче также полезна инфа о сотрудниках проекта, загрузим сразу
        const empRes = await axios.get(`/api/projects/${data.project.id}/employees`)
        companyEmployees.value = empRes.data
    } catch(e) { console.error(e) }
    finally { loading.value = false }
}

// --- ACTIONS (API Calls) ---
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
    // 1. Берем ID из события, или из загруженной задачи, или из props страницы
    const id = emittedId || task.value?.id || taskId;

    if (!id) {
        console.error("ID задачи не найден");
        return;
    }

    try {
        // Используем переменную id
        const { data } = await axios.post(`/api/tasks/${id}/start`)

        // Обновляем локальную задачу
        task.value = data.task
        // alert('Вы успешно взяли задачу в работу!') // Можно раскомментировать
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

        <div v-if="!loading && task">
            <!-- 1. HEADER -->
            <TaskHero
                :task="task"
                :perms="perms"
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

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-10 -mt-4">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- MAIN CONTENT -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Stats & Files -->
                        <TaskStats
                            :task="task"
                            :loading="loading"
                            :can-upload="perms.canUpload"
                            @updateProgress="updateProgress"
                            @uploadFiles="uploadFiles"
                            @deleteFile="deleteFile"
                        />

                        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                            <TaskDocumentApproval
                                :task="task"
                                :current-user="user"
                                @refresh="fetchTask"
                            />
                        </div>

                        <!-- Subtasks -->
                        <TaskSubtasks
                            :subtasks="task.subtasks"
                            :can-create="perms.canCreateSubtask"
                            @create="modals.subtask = true"
                        />

                        <!-- Chat -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                            <h3 class="font-bold mb-4 dark:text-white">💬 Чат</h3>
                            <TaskChat
                                :task-id="task.id"
                                :can-chat="true"
                                :members="[...(task.executors||[]), ...(task.responsibles||[]), task.creator]"
                            />
                        </div>
                    </div>

                    <!-- SIDEBAR -->
                    <div class="lg:col-span-1">
                        <TaskSidebar :task="task" />
                    </div>
                </div>
            </div>
        </div>
        <div v-else class="text-center py-20 text-gray-500">Загрузка задачи...</div>

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

    </AuthenticatedLayout>
</template>
