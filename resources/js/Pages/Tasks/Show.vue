<script setup>
import { ref, onMounted, computed } from 'vue'
import { usePage, Head, router } from '@inertiajs/vue3'
import axios from 'axios'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

import TaskHero from '../AAA/Components/Task/TaskHero.vue'
import TaskStats from '../AAA/Components/Task/TaskStats.vue'
import TaskSubtasks from '../AAA/Components/Task/TaskSubtasks.vue'
import TaskSidebar from '../AAA/Components/Task/TaskSidebar.vue'
import TaskDocumentApproval from '@/Pages/AAA/Components/Task/TaskDocumentApproval.vue'
import TaskActionModals from '../AAA/Components/Task/Modals/TaskActionModals.vue'
import TaskPersonnelModals from '../AAA/Components/Task/Modals/TaskPersonnelModals.vue'

const { props } = usePage()
const taskId = props.id
const user = props.auth?.user

const task = ref(null)
const companyEmployees = ref([])
const loading = ref(true)
const loadError = ref('')

const modals = ref({
    edit: false, description: false, delete: false, subtask: false,
    executor: false, responsible: false,
    addExecutor: false, addResponsible: false, addWatcher: false, manage: false
})

const perms = computed(() => {
    if (!task.value || !user) return {}

    const isOwner = user.id === task.value.project?.company?.user_id
    const isCreator = user.id === task.value.creator?.id
    const isExec = task.value.executors?.some(e => e.id === user.id)
    const isResp = task.value.responsibles?.some(r => r.id === user.id)
    const isProjExec = task.value.project?.executors?.some(e => e.id === user.id)
    const isProjMgr = task.value.project?.managers?.some(m => m.id === user.id)

    return {
        canCreateSubtask: isOwner || isCreator || isResp || isExec || isProjMgr || isProjExec,
        canUpdate: isOwner || isProjMgr || isProjExec,
        canDelete: isOwner,
        canUpload: isExec || isResp || isProjExec || isOwner,
        canFinish: task.value.progress === 100 && !task.value.completed && !task.value.subtasks?.some(s => !s.completed),
        canManageMembers: isOwner || isProjMgr || isProjExec,
        canManageTask: isCreator || isExec || isResp
    }
})

const fetchTask = async () => {
    loading.value = true
    loadError.value = ''
    try {
        const { data } = await axios.get(`/api/tasks/${taskId}`)
        task.value = data
        const { data: employees } = await axios.get(`/api/projects/${data.project.id}/employees`)
        companyEmployees.value = employees
    } catch (error) {
        console.error(error)
        loadError.value = error.response?.data?.message || 'Не удалось загрузить задачу'
    } finally {
        loading.value = false
    }
}

const updateTask = async form => { await axios.put(`/api/tasks/${taskId}`, form); modals.value.edit = false; await fetchTask() }
const saveDescription = async description => { await axios.patch(`/api/tasks/${taskId}/description`, { description }); modals.value.description = false; await fetchTask() }
const updateProgress = async progress => { await axios.patch(`/api/tasks/${taskId}/progress`, { progress }); task.value.progress = progress }
const deleteTask = async () => { await axios.delete(`/api/tasks/${taskId}`); window.history.back() }
const finishTask = async () => { await axios.patch(`/api/tasks/${taskId}/complete`); await fetchTask() }
const uploadFiles = async files => { const data = new FormData(); [...files].forEach(file => data.append('files[]', file)); await axios.post(`/api/tasks/${taskId}/files`, data); await fetchTask() }
const deleteFile = async id => { if (confirm('Удалить файл?')) { await axios.delete(`/api/tasks/files/${id}`); await fetchTask() } }
const createSubtask = async form => { await axios.post(`/api/tasks/${taskId}/subtasks`, form); modals.value.subtask = false; await fetchTask() }

const handlePersonnelChange = async ({ type, oldId, newId }) => {
    const endpoint = type === 'executor' ? 'executor' : 'responsible'
    await axios.patch(`/api/tasks/${taskId}/${endpoint}`, { replace_user_id: oldId, user_id: newId })
    modals.value[type] = false
    await fetchTask()
}

const handlePersonnelAdd = async ({ type, ids, singleId }) => {
    if (type === 'watcher') {
        await axios.post(`/api/tasks/${taskId}/watchers`, { user_id: singleId })
        modals.value.addWatcher = false
    } else {
        const endpoint = type === 'executor' ? 'executors' : 'responsibles'
        await axios.post(`/api/tasks/${taskId}/${endpoint}/add`, { user_ids: ids })
        modals.value[`add${type.charAt(0).toUpperCase()}${type.slice(1)}`] = false
    }
    await fetchTask()
}

const handlePersonnelRemove = async ({ role, id }) => {
    const endpoint = role === 'watcherstask' ? 'watchers' : role
    await axios.delete(`/api/tasks/${taskId}/${endpoint}`, { data: { user_id: id } })
    await fetchTask()
}

const onStartWork = async emittedId => {
    try {
        const { data } = await axios.post(`/api/tasks/${emittedId || task.value?.id || taskId}/start`)
        task.value = data.task
    } catch (error) {
        alert(error.response?.data?.message || 'Не удалось начать работу')
    }
}

onMounted(fetchTask)
</script>

<template>
    <Head :title="task?.title || 'Задача'" />
    <AuthenticatedLayout>
        <main class="min-h-screen bg-slate-50/70 dark:bg-slate-950">
            <div class="mx-auto max-w-[1480px] px-3 py-3 sm:px-5 lg:px-6">
                <div v-if="loading" class="grid min-h-[55vh] place-items-center">
                    <div class="flex items-center gap-3 text-sm font-semibold text-slate-500">
                        <span class="h-5 w-5 animate-spin rounded-full border-2 border-slate-300 border-t-indigo-600"></span>
                        Загрузка задачи
                    </div>
                </div>

                <div v-else-if="loadError" class="rounded-2xl border border-rose-200 bg-rose-50 p-6 text-center">
                    <p class="font-semibold text-rose-700">{{ loadError }}</p>
                    <button class="mt-3 rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white" @click="fetchTask">Повторить</button>
                </div>

                <template v-else-if="task">
                    <TaskHero
                        :task="task" :perms="perms"
                        @startWork="onStartWork" @edit="modals.edit = true" @delete="modals.delete = true"
                        @description="modals.description = true" @back="router.visit(`/projects/${task.project_id}`)"
                        @finish="finishTask" @changeExecutor="modals.executor = true"
                        @changeResponsible="modals.responsible = true" @addExecutor="modals.addExecutor = true"
                        @addResponsible="modals.addResponsible = true" @addWatcher="modals.addWatcher = true"
                        @manageMembers="modals.manage = true"
                    />

                    <section class="mt-3 grid grid-cols-1 gap-3 xl:grid-cols-[minmax(0,1fr)_320px]">
                        <div class="min-w-0 space-y-3">
                            <TaskStats :task="task" :loading="loading" :can-upload="perms.canUpload"
                                @updateProgress="updateProgress" @uploadFiles="uploadFiles" @deleteFile="deleteFile" />

                            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-5">
                                <TaskDocumentApproval :task="task" :current-user="user" @refresh="fetchTask" />
                            </div>

                            <TaskSubtasks :subtasks="task.subtasks" :can-create="perms.canCreateSubtask" @create="modals.subtask = true" />
                        </div>

                        <aside class="min-w-0"><TaskSidebar :task="task" /></aside>
                    </section>
                </template>
            </div>
        </main>

        <TaskActionModals :modals="modals" :task="task" :employees="companyEmployees"
            @close="key => modals[key] = false" @update="updateTask" @saveDescription="saveDescription"
            @deleteTask="deleteTask" @createSubtask="createSubtask" />
        <TaskPersonnelModals :modals="modals" :task="task" :employees="companyEmployees"
            @close="key => modals[key] = false" @change="handlePersonnelChange"
            @add="handlePersonnelAdd" @remove="handlePersonnelRemove" />
    </AuthenticatedLayout>
</template>
