<script setup>
import { ref, onMounted, provide } from 'vue'
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

import  SubtaskComments from '@/Components/SubtaskComments.vue'
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
    if (!subtask.value.comments) subtask.value.comments = []
    if (type === "add") subtask.value.comments.push(comment)
    if (type === "update") {
        const index = subtask.value.comments.findIndex(c => c.id === comment.id)
        if (index !== -1) subtask.value.comments[index] = comment
    }
    if (type === "delete") subtask.value.comments = subtask.value.comments.filter(c => c.id !== id)
}

const onChecklistUpdated = (e) => {
    if (!subtask.value.checklist) subtask.value.checklist = []
    if (e.type === 'add') subtask.value.checklist.push(e.item)
    if (e.type === 'toggle') {
        const item = subtask.value.checklist.find(i => i.id === e.id)
        if (item) item.completed = e.completed
    }
    if (e.type === 'delete') subtask.value.checklist = subtask.value.checklist.filter(i => i.id !== e.id)
}

const canWriteComments = () => {
    if (!subtask.value || !user) return false
    const project = subtask.value.task?.project || {}
    return (
        subtask.value.creator_id === user.id ||
        (project.managers || []).some(m => m.id === user.id) ||
        (project.executors || []).some(e => e.id === user.id) ||
        project.company?.user_id === user.id ||
        (subtask.value.executors || []).some(e => e.id === user.id) ||
        (subtask.value.responsibles || []).some(r => r.id === user.id)
    )
}

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
    <Head title="Подзадача" />
    <AuthenticatedLayout>
        <template #header>
            <!-- Хедер теперь отдельным компонентом -->
            <SubtaskHeader
                v-if="subtask"
                :subtask="subtask"
                :user="user"
                @refresh="onRefresh"
                @startWork="onStartWork"
            />
        </template>

        <div class="max-w-4xl mx-auto py-8 px-4">
            <div v-if="subtask" class="grid gap-6">

                <!-- Информация об участниках + Модалки управления -->
                <SubtaskMembers
                    :subtask="subtask"
                    :user="user"
                    @refresh="onRefresh"
                />

                <!-- Описание -->
                <SubtaskDescription
                    :subtask="subtask"
                    :user="user"
                    @refresh="onRefresh"
                />

                <!-- Прогресс и даты -->
                <SubtaskProgress
                    :subtask="subtask"
                    :user="user"
                    @refresh="onRefresh"
                />

                <!-- Файлы -->
                <SubtaskFiles
                    :subtask="subtask"
                    :user="user"
                    @refresh="onRefresh"
                />

                <!-- Дочерние подзадачи -->
                <SubtaskChildren
                    :subtask="subtask"
                    :user="user"
                    @refresh="onRefresh"
                />

                <!-- Чеклист и Комментарии (расположение рядом) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <SubtaskChecklist
                        :subtask-id="subtask.id"
                        :checklist="subtask.checklist"
                        :user-id="$page.props.auth.user.id"
                        :executors="subtask.executors"
                        :responsibles="subtask.responsibles"
                        :can-write="canWriteComments()"
                        @updated="onChecklistUpdated"
                    />

                    <SubtaskComments
                        :subtask-id="subtask.id"
                        :comments="subtask.comments"
                        :can-write="canWriteComments()"
                        :members="[...(subtask.executors ?? []), ...(subtask.responsibles ?? [])]"
                        @updated="onCommentsUpdated"
                    />
                </div>

            </div>
            <div v-else class="text-gray-600 dark:text-gray-300 text-center py-10">Загрузка...</div>
        </div>
    </AuthenticatedLayout>
</template>
