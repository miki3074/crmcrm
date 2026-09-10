<script setup>
import { ref, onMounted, computed } from 'vue'
import { usePage, Head, router } from '@inertiajs/vue3'
import axios from 'axios'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { useToast } from '@/Composables/useToast'
import ToastContainer from '@/Components/ToastContainer.vue'

import TaskHero from '../AAA/Components/Task/TaskHero.vue'
import TaskStats from '../AAA/Components/Task/TaskStats.vue'
import TaskSubtasks from '../AAA/Components/Task/TaskSubtasks.vue'
import TaskSidebar from '../AAA/Components/Task/TaskSidebar.vue'
import TaskFilesHub from '../AAA/Components/Task/TaskFilesHub.vue'
import TaskResults from '../AAA/Components/Task/TaskResults.vue'
import AddResultModal from '../AAA/Components/Task/AddResultModal.vue'
import TaskActionModals from '../AAA/Components/Task/Modals/TaskActionModals.vue'
import TaskPersonnelModals from '../AAA/Components/Task/Modals/TaskPersonnelModals.vue'
import ConfirmDialog from '../AAA/Components/Task/ConfirmDialog.vue'

const { props } = usePage()
const taskId = props.id
const user = props.auth?.user
const toast = useToast()

const task = ref(null)
const companyEmployees = ref([])
const loading = ref(true)
const loadError = ref('')

// Отдельные состояния для форм в модалках (TaskActionModals) — чтобы
// показывать ошибку и блокировать кнопку именно там, где идёт действие,
// а не перезагружать всю страницу спиннером на каждую мелочь.
const actionSaving = ref(false)
const actionError = ref('')

const progressSaving = ref(false)

const fileToDelete = ref(null)

const showResultModal = ref(false)
const resultSaving = ref(false)
const resultError = ref('')

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

// Файлы, которые пользователь сам загрузил в эту задачу — только их можно
// прикрепить к своему результату.
const myFiles = computed(() => {
    if (!task.value || !user) return []
    return (task.value.files || []).filter(f => f.user_id === user.id)
})

// silent: true — фоновое обновление данных после мелких действий, без
// полноэкранного спиннера и без повторного запроса списка сотрудников
// (он не меняется от этих действий).
const fetchTask = async ({ silent = false } = {}) => {
    if (!silent) {
        loading.value = true
        loadError.value = ''
    }

    try {
        const { data } = await axios.get(`/api/tasks/${taskId}`)
        task.value = data

        if (!silent) {
            const { data: employees } = await axios.get(`/api/projects/${data.project.id}/employees`)
            companyEmployees.value = employees
        }
    } catch (error) {
        console.error(error)
        if (!silent) {
            loadError.value = error.response?.data?.message || 'Не удалось загрузить задачу'
        } else {
            toast.error('Не удалось обновить данные задачи')
        }
    } finally {
        if (!silent) loading.value = false
    }
}

const closeModal = key => {
    modals.value[key] = false
    actionError.value = ''
}

const updateTask = async form => {
    actionSaving.value = true
    actionError.value = ''
    try {
        await axios.put(`/api/tasks/${taskId}`, form)
        modals.value.edit = false
        await fetchTask({ silent: true })
        toast.success('Задача обновлена')
    } catch (error) {
        actionError.value = error.response?.data?.message || 'Не удалось сохранить изменения'
    } finally {
        actionSaving.value = false
    }
}

const saveDescription = async description => {
    actionSaving.value = true
    actionError.value = ''
    try {
        await axios.patch(`/api/tasks/${taskId}/description`, { description })
        modals.value.description = false
        await fetchTask({ silent: true })
        toast.success('Описание сохранено')
    } catch (error) {
        actionError.value = error.response?.data?.message || 'Не удалось сохранить описание'
    } finally {
        actionSaving.value = false
    }
}

const updateProgress = async progress => {
    const previous = task.value.progress
    progressSaving.value = true
    task.value.progress = progress

    try {
        await axios.patch(`/api/tasks/${taskId}/progress`, { progress })
    } catch (error) {
        task.value.progress = previous
        toast.error(error.response?.data?.message || 'Не удалось обновить прогресс')
    } finally {
        progressSaving.value = false
    }
}

const deleteTask = async () => {
    actionSaving.value = true
    actionError.value = ''
    try {
        await axios.delete(`/api/tasks/${taskId}`)
        window.history.back()
    } catch (error) {
        actionError.value = error.response?.data?.message || 'Не удалось удалить задачу'
        actionSaving.value = false
    }
}

const finishTask = async () => {
    try {
        await axios.patch(`/api/tasks/${taskId}/complete`)
        await fetchTask({ silent: true })
        toast.success('Задача завершена')
    } catch (error) {
        toast.error(error.response?.data?.message || 'Не удалось завершить задачу')
    }
}

const uploadFiles = async files => {
    const data = new FormData()
    ;[...files].forEach(file => data.append('files[]', file))

    try {
        await axios.post(`/api/tasks/${taskId}/files`, data)
        await fetchTask({ silent: true })
        toast.success('Файлы загружены')
    } catch (error) {
        toast.error(error.response?.data?.message || 'Не удалось загрузить файлы')
    }
}

const requestDeleteFile = id => {
    fileToDelete.value = id
}

const confirmDeleteFile = async () => {
    const id = fileToDelete.value
    fileToDelete.value = null

    try {
        await axios.delete(`/api/tasks/files/${id}`)
        await fetchTask({ silent: true })
        toast.success('Файл удалён')
    } catch (error) {
        toast.error(error.response?.data?.message || 'Не удалось удалить файл')
    }
}

const addResult = async ({ text, file_id }) => {
    resultSaving.value = true
    resultError.value = ''
    try {
        const { data } = await axios.post(`/api/tasks/${taskId}/results`, { text, file_id })
        task.value.results = [data.result, ...(task.value.results || [])]
        showResultModal.value = false
        toast.success('Результат добавлен')
    } catch (error) {
        resultError.value = error.response?.data?.message
            || Object.values(error.response?.data?.errors || {})[0]?.[0]
            || 'Не удалось добавить результат'
    } finally {
        resultSaving.value = false
    }
}

const openResultFile = file => {
    window.open(`/api/public/files/${file.id}`, '_blank')
}

const createSubtask = async form => {
    actionSaving.value = true
    actionError.value = ''
    try {
        await axios.post(`/api/tasks/${taskId}/subtasks`, form)
        modals.value.subtask = false
        await fetchTask({ silent: true })
        toast.success('Подзадача создана')
    } catch (error) {
        actionError.value = error.response?.data?.message || 'Не удалось создать подзадачу'
    } finally {
        actionSaving.value = false
    }
}

const handlePersonnelChange = async ({ type, oldId, newId }) => {
    const endpoint = type === 'executor' ? 'executor' : 'responsible'
    try {
        await axios.patch(`/api/tasks/${taskId}/${endpoint}`, { replace_user_id: oldId, user_id: newId })
        modals.value[type] = false
        await fetchTask({ silent: true })
        toast.success('Состав обновлён')
    } catch (error) {
        toast.error(error.response?.data?.message || 'Не удалось выполнить замену')
    }
}

const handlePersonnelAdd = async ({ type, ids, singleId }) => {
    try {
        if (type === 'watcher') {
            await axios.post(`/api/tasks/${taskId}/watchers`, { user_id: singleId })
            modals.value.addWatcher = false
        } else {
            const endpoint = type === 'executor' ? 'executors' : 'responsibles'
            await axios.post(`/api/tasks/${taskId}/${endpoint}/add`, { user_ids: ids })
            modals.value[`add${type.charAt(0).toUpperCase()}${type.slice(1)}`] = false
        }
        await fetchTask({ silent: true })
        toast.success('Участник добавлен')
    } catch (error) {
        toast.error(error.response?.data?.message || 'Не удалось добавить участника')
    }
}

// Точечное действие: убираем участника из локального состояния сразу
// (оптимистично), без перезагрузки всей задачи. Если запрос упадёт —
// откатываем обратно и показываем тост с ошибкой.
const handlePersonnelRemove = async ({ role, id }) => {
    const endpoint = role === 'watcherstask' ? 'watchers' : role
    const previous = task.value[role] || []
    task.value[role] = previous.filter(u => u.id !== id)

    try {
        await axios.delete(`/api/tasks/${taskId}/${endpoint}`, { data: { user_id: id } })
        toast.success('Участник удалён')
    } catch (error) {
        task.value[role] = previous
        toast.error(error.response?.data?.message || 'Не удалось удалить участника')
    }
}

const onStartWork = async emittedId => {
    try {
        const { data } = await axios.post(`/api/tasks/${emittedId || task.value?.id || taskId}/start`)
        task.value = data.task
        toast.success('Задача взята в работу')
    } catch (error) {
        toast.error(error.response?.data?.message || 'Не удалось начать работу')
    }
}

onMounted(() => fetchTask())
</script>

<template>
    <Head :title="task?.title || 'Задача'" />
    <AuthenticatedLayout>
        <main class="min-h-screen bg-zinc-50/70 dark:bg-zinc-950">
            <div class="mx-auto max-w-[1480px] px-3 py-3 sm:px-5 lg:px-6">
                <div v-if="loading" class="grid min-h-[55vh] place-items-center">
                    <div class="flex items-center gap-3 text-sm font-semibold text-zinc-500">
                        <span class="h-5 w-5 animate-spin rounded-full border-2 border-zinc-300 border-t-cyan-600"></span>
                        Загрузка задачи
                    </div>
                </div>

                <div v-else-if="loadError" class="rounded-2xl border border-rose-200 bg-rose-50 p-6 text-center">
                    <p class="font-semibold text-rose-700">{{ loadError }}</p>
                    <button
                        type="button"
                        class="mt-3 rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white
                               focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400 focus-visible:ring-offset-2"
                        @click="fetchTask()"
                    >
                        Повторить
                    </button>
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

                    <section class="mt-3 grid grid-cols-1 gap-3 lg:grid-cols-[minmax(0,1fr)_320px]">
                        <div class="min-w-0 space-y-3">
                            <TaskStats :task="task" :saving="progressSaving" @updateProgress="updateProgress" />

                            <TaskFilesHub :task="task" :loading="false" :can-upload="perms.canUpload"
                                :current-user="user" @uploadFiles="uploadFiles" @deleteFile="requestDeleteFile" @refresh="() => fetchTask({ silent: true })" />

                            <TaskResults :results="task.results || []" :can-add="perms.canManageTask" @add="showResultModal = true" @openFile="openResultFile" />

                            <TaskSubtasks :subtasks="task.subtasks" :can-create="perms.canCreateSubtask" @create="modals.subtask = true" />
                        </div>

                        <aside class="min-w-0"><TaskSidebar :task="task" /></aside>
                    </section>
                </template>
            </div>
        </main>

        <TaskActionModals :modals="modals" :task="task" :employees="companyEmployees" :loading="actionSaving" :error="actionError"
            @close="closeModal" @update="updateTask" @saveDescription="saveDescription"
            @deleteTask="deleteTask" @createSubtask="createSubtask" />
        <TaskPersonnelModals :modals="modals" :task="task" :employees="companyEmployees"
            @close="closeModal" @change="handlePersonnelChange"
            @add="handlePersonnelAdd" @remove="handlePersonnelRemove" />

        <ConfirmDialog
            :show="fileToDelete !== null"
            title="Удалить файл?"
            message="Файл будет удалён без возможности восстановления."
            @confirm="confirmDeleteFile"
            @close="fileToDelete = null"
        />

        <AddResultModal
            :show="showResultModal"
            :my-files="myFiles"
            :loading="resultSaving"
            :error="resultError"
            @submit="addResult"
            @close="showResultModal = false"
        />

        <ToastContainer />
    </AuthenticatedLayout>
</template>
