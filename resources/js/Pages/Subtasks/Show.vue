<script setup>
import { ref, onMounted, computed } from 'vue'
import { usePage, Head } from '@inertiajs/vue3'
import axios from 'axios'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

// Импорт новых компонентов
import SubtaskHeader from '../AAA/Components/Subtask/SubtaskHeader.vue'
import SubtaskMembers from '../AAA/Components/Subtask/SubtaskMembers.vue'
import SubtaskProgress from '../AAA/Components/Subtask/SubtaskProgress.vue'
import SubtaskDescription from '../AAA/Components/Subtask/SubtaskDescription.vue'
import SubtaskFiles from '../AAA/Components/Subtask/SubtaskFiles.vue'
import SubtaskChildren from '../AAA/Components/Subtask/SubtaskChildren.vue'

import SubtaskComments from '@/Components/SubtaskComments.vue'
import SubtaskChecklist from '@/Components/SubtaskChecklist.vue'

const { props } = usePage()
const subtaskId = props.id
const user = props.auth?.user
const subtask = ref(null)

const fetchSubtask = async () => {
    try {
        const { data } = await axios.get(`/api/subtasks/${subtaskId}`)
        subtask.value = data
    } catch (e) {
        console.error("Ошибка загрузки подзадачи", e)
    }
}

// Функции обновления для дочерних компонентов
const onRefresh = async () => {
    await fetchSubtask()
}

// Логика комментариев и чеклиста осталась специфичной, можно оставить обработчики тут
// или перенести внутрь компонентов, если они сами умеют обновляться.
const onCommentsUpdated = ({ type, comment, id }) => {
    if (!subtask.value) return
    if (!subtask.value.comments) subtask.value.comments = []
    if (type === "add") subtask.value.comments.push(comment)
    if (type === "update") {
        const index = subtask.value.comments.findIndex(c => c.id === comment.id)
        if (index !== -1) subtask.value.comments[index] = comment
    }
    if (type === "delete") subtask.value.comments = subtask.value.comments.filter(c => c.id !== id)
}

const onChecklistUpdated = (e) => {
    if (!subtask.value) return
    if (!subtask.value.checklist) subtask.value.checklist = []
    if (e.type === 'add') subtask.value.checklist.push(e.item)
    if (e.type === 'toggle') {
        const item = subtask.value.checklist.find(i => i.id === e.id)
        if (item) item.completed = e.completed
    }
    if (e.type === 'delete') subtask.value.checklist = subtask.value.checklist.filter(i => i.id !== e.id)
}

const canWriteComments = computed(() => {
    if (!subtask.value || !user) return false
    const project = subtask.value.task?.project || {}

    return [
        subtask.value.creator_id === user.id,
        project.company?.user_id === user.id,
        (project.managers || []).some(item => item.id === user.id),
        (project.executors || []).some(item => item.id === user.id),
        (subtask.value.executors || []).some(item => item.id === user.id),
        (subtask.value.responsibles || []).some(item => item.id === user.id),
    ].some(Boolean)
})

const onStartWork = async (id) => {
    // id приходит из эмита кнопки, либо берем текущий subtaskId
    const targetId = id || subtaskId;

    if(!targetId) return;

    try {
        const { data } = await axios.post(`/api/subtasks/${targetId}/start`)
        // Обновляем данные на странице (можно просто обновить subtask.value.status, но лучше полная перезагрузка)
        alert('Вы взяли подзадачу в работу!');
        await fetchSubtask();
    } catch (e) {
        console.error(e)
        alert(e.response?.data?.message || 'Ошибка')
    }
}


onMounted(fetchSubtask)
</script>

<template>
    <Head :title="subtask?.title || 'Подзадача'" />

    <AuthenticatedLayout>
        <template #header>
            <SubtaskHeader
                v-if="subtask"
                :subtask="subtask"
                :user="user"
                @refresh="onRefresh"
                @startWork="onStartWork"
            />
        </template>

        <main class="min-h-screen bg-slate-50/70 px-3 py-4 dark:bg-slate-950 sm:px-5 lg:px-6">
            <div class="mx-auto max-w-[1480px]">
                <div
                    v-if="subtask"
                    class="grid items-start gap-4 xl:grid-cols-[minmax(0,1fr)_360px]"
                >
                    <section class="min-w-0 space-y-4">
                        <SubtaskDescription
                            :subtask="subtask"
                            :user="user"
                            @refresh="onRefresh"
                        />

                        <SubtaskProgress
                            :subtask="subtask"
                            :user="user"
                            @refresh="onRefresh"
                        />

                        <SubtaskFiles
                            :subtask="subtask"
                            :user="user"
                            @refresh="onRefresh"
                        />

                        <SubtaskChildren
                            :subtask="subtask"
                            :user="user"
                            @refresh="onRefresh"
                        />
                    </section>

                    <aside class="space-y-4 xl:sticky xl:top-4">
                        <SubtaskMembers
                            :subtask="subtask"
                            :user="user"
                            @refresh="onRefresh"
                        />

                        <SubtaskChecklist
                            :subtask-id="subtask.id"
                            :checklist="subtask.checklist"
                            :user-id="user?.id"
                            :executors="subtask.executors"
                            :responsibles="subtask.responsibles"
                            :can-write="canWriteComments"
                            @updated="onChecklistUpdated"
                        />

                        <SubtaskComments
                            :subtask-id="subtask.id"
                            :comments="subtask.comments"
                            :can-write="canWriteComments"
                            :members="[...(subtask.executors ?? []), ...(subtask.responsibles ?? [])]"
                            @updated="onCommentsUpdated"
                        />
                    </aside>
                </div>

                <div
                    v-else
                    class="grid min-h-[420px] place-items-center rounded-2xl border border-slate-200 bg-white text-center shadow-sm dark:border-slate-800 dark:bg-slate-900"
                >
                    <div>
                        <div class="mx-auto h-8 w-8 animate-spin rounded-full border-2 border-indigo-500 border-t-transparent" />
                        <p class="mt-4 text-sm font-semibold text-slate-700 dark:text-slate-200">
                            Загружаем подзадачу
                        </p>
                        <p class="mt-1 text-xs text-slate-400">
                            Или проверяем доступ к ней
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </AuthenticatedLayout>
</template>
