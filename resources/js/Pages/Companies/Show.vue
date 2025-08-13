<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { Head, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const company = ref(null)
const managers = ref([])
const showProjectModal = ref(false)
const { props } = usePage()
const companyId = props.id
const today = new Date()

const projectForm = ref({
    name: '',
    manager_id: '',
    start_date: new Date().toISOString().slice(0, 10),
    duration_days: '',
})

const fetchCompany = async () => {
    const { data } = await axios.get(`/api/companies/${companyId}`)
    company.value = data
}

const fetchManagers = async () => {
    const { data } = await axios.get('/api/users/managers')
    managers.value = data
}

const createProject = async () => {
    await axios.get('/sanctum/csrf-cookie')
    await axios.post('/api/projects', {
        ...projectForm.value,
        company_id: companyId
    })
    showProjectModal.value = false
    projectForm.value = {
        name: '',
        manager_id: '',
        start_date: new Date().toISOString().slice(0, 10),
        duration_days: '',
    }
    await fetchCompany()
}

const daysLeft = (startDate, duration) => {
    const start = new Date(startDate)
    const end = new Date(start)
    end.setDate(start.getDate() + duration)
    const diff = Math.ceil((end - today) / (1000 * 60 * 60 * 24))
    return diff
}

onMounted(() => {
    fetchCompany()
})
</script>

<template>
    <Head title="Компания" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Компания: {{ company?.name ?? 'Компания' }}
            </h2>
        </template>

       <div class="max-w-5xl mx-auto py-10 px-4" 
     v-if="props.auth.roles.includes('admin') || company?.user_id === props.auth.user.id">
    <button @click="() => { showProjectModal = true; fetchManagers() }"
        class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
        Создать проект
    </button>
</div>


        <div v-if="company?.projects?.length" class="mt-8 max-w-5xl mx-auto">
    <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">Проекты компании</h3>
    <div class="space-y-4">
        <div v-for="project in company.projects" :key="project.id"
             class="p-4 border rounded bg-white dark:bg-gray-800 shadow hover:cursor-pointer"
     @click="$inertia.visit(`/projects/${project.id}`)">
            <h4 class="text-xl font-semibold text-gray-900 dark:text-white mb-1">{{ project.name }}</h4>
            <p class="text-sm text-gray-700 dark:text-gray-300">
                <strong>Руководитель:</strong> {{ project.manager?.name ?? '—' }}
            </p>
            <p class="text-sm text-gray-700 dark:text-gray-300">
                <strong>Дата начала:</strong> {{ project.start_date }}
            </p>
            <p class="text-sm text-gray-700 dark:text-gray-300">
                <strong>Осталось дней:</strong> {{ daysLeft(project.start_date, project.duration_days) }}
            </p>
        </div>
    </div>
</div>


        <!-- Модалка создания проекта -->
        <div v-if="showProjectModal"
             class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
            <div class="bg-white dark:bg-gray-800 p-6 rounded shadow w-full max-w-lg">
                <h2 class="text-xl font-bold mb-4 text-gray-800 dark:text-white">Создание проекта</h2>
                <form @submit.prevent="createProject">
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1">Название проекта</label>
                        <input v-model="projectForm.name" class="w-full border rounded px-3 py-2" required />
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1">Руководитель проекта</label>
                        <select v-model="projectForm.manager_id" class="w-full border rounded px-3 py-2" required>
                            <option disabled value="">Выберите менеджера</option>
                            <option v-for="manager in managers" :key="manager.id" :value="manager.id">
                                {{ manager.name }}
                            </option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1">Дата начала</label>
                        <input type="date" v-model="projectForm.start_date"
                               class="w-full border rounded px-3 py-2" required />
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1">Длительность (в днях)</label>
                        <input type="number" v-model="projectForm.duration_days"
                               class="w-full border rounded px-3 py-2" required />
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="showProjectModal = false"
                                class="bg-gray-500 text-white px-4 py-2 rounded">
                            Отмена
                        </button>
                        <button type="submit"
                                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                            Создать
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
