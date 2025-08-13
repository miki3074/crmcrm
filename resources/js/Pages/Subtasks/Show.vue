<script setup>
import { ref, onMounted } from 'vue'
import { usePage, Head } from '@inertiajs/vue3'
import axios from 'axios'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const { props } = usePage()
const subtaskId = props.id
const subtask = ref(null)

const fetchSubtask = async () => {
    const { data } = await axios.get(`/api/subtasks/${subtaskId}`)
    subtask.value = data
}

onMounted(fetchSubtask)
</script>

<template>
    <Head title="Подзадача" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-200">
                Подзадача: {{ subtask?.title ?? 'Загрузка...' }}
            </h2>
        </template>

        <div class="max-w-4xl mx-auto py-10 px-4 space-y-3">
            <div v-if="subtask">
                <p><strong>Автор:</strong> {{ subtask.creator?.name }}</p>
                <p><strong>Исполнитель:</strong> {{ subtask.executor?.name }}</p>
                <p><strong>Дата начала:</strong> {{ subtask.start_date }}</p>
                <p><strong>Дата окончания:</strong> {{ subtask.due_date }}</p>
                <p><strong>Задача:</strong> {{ subtask.task?.title }}</p>
                <p><strong>Проект:</strong> {{ subtask.task?.project?.name }}</p>
                <p><strong>Компания:</strong> {{ subtask.task?.project?.company?.name }}</p>
            </div>
            <div v-else class="text-gray-600 dark:text-gray-300">Загрузка...</div>
        </div>
    </AuthenticatedLayout>
</template>
