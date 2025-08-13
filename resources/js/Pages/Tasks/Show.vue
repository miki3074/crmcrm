<script setup>
import { ref, onMounted } from 'vue'
import { usePage, Head } from '@inertiajs/vue3'
import { computed } from 'vue'
import axios from 'axios'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const { props } = usePage()
const taskId = props.id

const task = ref(null)


const user = usePage().props.auth.user

const canCreateSubtask = computed(() => {
    return task.value &&
        (task.value.responsible?.id === user.id || task.value.project?.manager?.id === user.id)
})


const fetchTask = async () => {
    const { data } = await axios.get(`/api/tasks/${taskId}`)
    task.value = data
}

const updateProgress = async (value) => {
    try {
        const { data } = await axios.patch(`/api/tasks/${taskId}/progress`, {
            progress: value
        })
        task.value.progress = data.progress
    } catch (error) {
        console.error('Ошибка при обновлении прогресса:', error)
        alert('Недостаточно прав для обновления прогресса задачи.')
    }
}


const selectedFiles = ref(null)

const handleFileChange = (e) => {
    selectedFiles.value = e.target.files
}

const uploadFiles = async () => {
    if (!selectedFiles.value || selectedFiles.value.length === 0) {
        alert('Пожалуйста, выберите файлы')
        return
    }

    const formData = new FormData()
    for (let i = 0; i < selectedFiles.value.length; i++) {
        formData.append('files[]', selectedFiles.value[i])
    }

    try {
        await axios.post(`/api/tasks/${taskId}/files`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        })

        alert('Файлы успешно загружены')
        selectedFiles.value = null
        await fetchTask() // обновляем задачу с новыми файлами
    } catch (err) {
        console.error('Ошибка при загрузке файлов:', err)
        alert('Ошибка при загрузке файлов')
    }
}

const showSubtaskModal = ref(false)
const subtaskForm = ref({
    title: '',
    executor_id: '',
    start_date: new Date().toISOString().slice(0, 10),
    due_date: '',
})
const companyEmployees = ref([])

const openSubtaskModal = async () => {
    const { data } = await axios.get(`/api/projects/${task.value.project.id}/employees`)
    companyEmployees.value = data
    showSubtaskModal.value = true
}

const createSubtask = async () => {
    try {
        await axios.post(`/api/tasks/${taskId}/subtasks`, {
            ...subtaskForm.value,
            task_id: taskId,
        })
        showSubtaskModal.value = false
        await fetchTask() // перезагружаем задачу с подзадачами
    } catch (err) {
        console.error('Ошибка при создании подзадачи:', err)
        alert('Ошибка при создании подзадачи')
    }
}




onMounted(() => {
    fetchTask()
})
</script>

<template>
    <Head title="Задача" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-200">
                Задача: {{ task?.title ?? 'Загрузка...' }}
            </h2>
        </template>







        

        <div class="max-w-4xl mx-auto py-10 px-4">
            <div v-if="task" class="space-y-3">
                <p style="color: aliceblue;"><strong>Автор:</strong> {{ task.creator?.name }}</p>
                <p style="color: aliceblue;"><strong>Исполнитель:</strong> {{ task.executor?.name }}</p>
                <p style="color: aliceblue;"><strong>Ответственный:</strong> {{ task.responsible?.name }}</p>
                <p style="color: aliceblue;"><strong>Приоритет: </strong> 
                    <span :class="{
                        'text-green-600': task.priority === 'low',
                        'text-yellow-600': task.priority === 'medium',
                        'text-red-600': task.priority === 'high',
                    }">{{ task.priority }}</span>
                </p>
                <p style="color: aliceblue;"><strong>Дата начала:</strong> {{ task.start_date }}</p>
                <p style="color: aliceblue;"><strong>Дата окончания:</strong> {{ task.due_date }}</p>
                <p style="color: aliceblue;"><strong>Компания:</strong> {{ task.project?.company?.name }}</p>
                <p style="color: aliceblue;"><strong>Проект:</strong> {{ task.project?.name }}</p>


<!-- Добавление новых файлов -->
<div class="mt-6">
    <p class="font-semibold mb-2" style="color: aliceblue;">Добавить файлы к задаче</p>
    <input type="file" multiple @change="handleFileChange" accept=".pdf,.doc,.docx,.xls,.xlsx"
        class="mb-2 block text-sm text-gray-600 dark:text-gray-300" />
    <button @click="uploadFiles"
        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
        Загрузить
    </button>
</div>



                <div v-if="task.files?.length">
                    <p class="font-semibold mt-4" style="color: aliceblue;">Файлы:</p>
                    <ul class="list-disc ml-6">
                        <li v-for="file in task.files" :key="file.id">
                            <a :href="`/storage/${file.file_path}`" target="_blank" class="text-blue-600 underline">
                                Скачать файл
                            </a>
                        </li>
                    </ul>
                </div>

<div>
    <h3 class="text-md font-semibold text-gray-800 dark:text-white">
        Прогресс задачи 
        <span class="text-sm text-gray-500">Выполнено {{ task.progress }}%</span>
    </h3>
    <div class="flex mt-2 space-x-1">
        <div
            v-for="n in 10"
            :key="n"
            @click="updateProgress(n * 10)"
            class="h-5 flex-1 cursor-pointer rounded transition"
            :class="{
                'bg-green-600': task.progress >= n * 10,
                'bg-gray-300': task.progress < n * 10
            }"
        ></div>
    </div>
</div>

<div v-if="canCreateSubtask" class="mt-6">
    <button @click="openSubtaskModal"
        class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
        Добавить подзадачу
    </button>
</div>



<div v-if="task.subtasks?.length" class="mt-10">
    <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">Подзадачи</h3>
    <div class="space-y-4">
        <div
            v-for="subtask in task.subtasks"
            :key="subtask.id"
            class="p-4 border rounded bg-white dark:bg-gray-800 shadow hover:cursor-pointer"
            @click="$inertia.visit(`/subtasks/${subtask.id}`)"
        >
            <h4 class="text-md font-bold text-gray-900 dark:text-white mb-1">{{ subtask.title }}</h4>
       
            <p class="text-sm text-gray-700 dark:text-gray-300">
                <strong>Исполнитель:</strong> {{ subtask.executor?.name ?? '—' }}
            </p>
            <p class="text-sm text-gray-700 dark:text-gray-300">
                <strong>Сроки:</strong> {{ subtask.start_date }} — {{ subtask.due_date }}
            </p>
        </div>
    </div>
</div>


<!-- Модалка создания подзадачи -->
<div v-if="showSubtaskModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 p-6 rounded shadow-lg w-full max-w-md">
        <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">Новая подзадача</h3>
        <form @submit.prevent="createSubtask">
            <div class="mb-4">
                <label class="block mb-1 text-sm">Название подзадачи</label>
                <input v-model="subtaskForm.title" class="w-full border px-3 py-2 rounded" required />
            </div>
            <div class="mb-4">
                <label class="block mb-1 text-sm">Исполнитель</label>
                <select v-model="subtaskForm.executor_id" class="w-full border px-3 py-2 rounded" required>
                    <option disabled value="">Выберите исполнителя</option>
                    <option v-for="user in companyEmployees" :key="user.id" :value="user.id">
                        {{ user.name }}
                    </option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block mb-1 text-sm">Дата начала</label>
                <input type="date" v-model="subtaskForm.start_date" class="w-full border px-3 py-2 rounded" required />
            </div>
            <div class="mb-4">
                <label class="block mb-1 text-sm">Дата окончания</label>
                <input type="date" v-model="subtaskForm.due_date" class="w-full border px-3 py-2 rounded" required />
            </div>
            <div class="flex justify-end gap-2">
                <button @click="showSubtaskModal = false" type="button"
                    class="px-4 py-2 bg-gray-500 text-white rounded">Отмена</button>
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                    Создать
                </button>
            </div>
        </form>
    </div>
</div>



            </div>
            <div v-else class="text-gray-600 dark:text-gray-300">Загрузка данных задачи...</div>
        </div>
    </AuthenticatedLayout>
</template>
