<script setup>
import { ref, onMounted, computed } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import axios from 'axios'

// Импорт новых компонентов
import CompanyHero from './home/Company/CompanyHero.vue'
import ProjectGrid from './home/Company/ProjectGrid.vue'
import ProjectDurationChart from './home/Company/ProjectDurationChart.vue'
import TaskProgressChart from './home/Company/TaskProgressChart.vue'
import CreateProjectModal from './home/Company/CreateProjectModal.vue'
import CompanyMembersModal from './home/Company/CompanyMembersModal.vue'

const { props } = usePage()
const companyId = props.id

// --- State ---
const loading = ref(true)
const company = ref(null)
const managers = ref([])
const showProjectModal = ref(false)
const showMembersModal = ref(false)
const submitLoading = ref(false)
const errorText = ref('')
const isMobile = ref(false)

// Данные для графиков
const selectedProject = ref(null)
const taskStats = ref([])
const loadingStats = ref(false)

// --- Permissions ---
const isOwner = computed(() => company.value?.user_id === props.auth?.user?.id)
const isCompanyManager = computed(() => company.value?.users?.some(u => u.id === props.auth?.user?.id && u.pivot?.role === 'manager'))
const canCreateProject = computed(() => isOwner.value || isCompanyManager.value)
const isAdmin = computed(() => props.auth?.roles?.includes('admin'))

// --- API Methods ---
const fetchCompany = async () => {
    loading.value = true
    try {
        const { data } = await axios.get(`/api/companies/${companyId}`)
        company.value = data
    } catch (e) { console.error(e) }
    finally { loading.value = false }
}

const fetchManagers = async () => {
    const { data } = await axios.get(`/api/users/managers?company_id=${companyId}`)
    managers.value = data
}

// Chart logic
const onProjectChartClick = async (projectName) => {
    const proj = company.value.projects.find(p => p.name === projectName)
    if (proj) {
        selectedProject.value = proj
        loadingStats.value = true
        try {
            const { data } = await axios.get(`/api/projects/${proj.id}/task-stats`)
            taskStats.value = data
        } finally { loadingStats.value = false }

        // Скролл к графику задач
        document.getElementById('task-stats-section')?.scrollIntoView({ behavior: 'smooth' })
    }
}

// Create Project logic
const openCreateModal = async () => {
    errorText.value = ''
    await fetchManagers()
    showProjectModal.value = true
}

const handleCreateProject = async (formData) => {
    submitLoading.value = true
    try {
        await axios.post('/api/projects', { ...formData, company_id: companyId })
        showProjectModal.value = false
        await fetchCompany() // refresh
    } catch (e) {
        errorText.value = e?.response?.data?.message || 'Ошибка создания'
    } finally {
        submitLoading.value = false
    }
}

onMounted(() => {
    fetchCompany()
    isMobile.value = window.innerWidth < 768
})
</script>

<template>
    <Head :title="company?.name || 'Компания'" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-12">
123

            <!-- 1. Hero Section -->
            <CompanyHero
                :company="company"
                :is-owner="isOwner"
                :is-admin="isAdmin"
                :can-create="canCreateProject"
                @create="openCreateModal"
                @open-members="showMembersModal = true"
            />


            <!-- 2. Projects Grid -->
            <ProjectGrid
                :projects="company?.projects"
                :loading="loading"
            />

            <!-- 3. Analytics Section (Desktop only) -->
            <div v-if="!isMobile && company?.projects?.length" class="space-y-8" style="display: none">
                <div class="flex items-center gap-4">
                    <div class="h-px flex-1 bg-slate-200 dark:bg-slate-700"></div>
                    <h2 class="text-xl font-bold text-slate-400 uppercase tracking-widest">Аналитика</h2>
                    <div class="h-px flex-1 bg-slate-200 dark:bg-slate-700"></div>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
                    <!-- График 1: Длительность -->
                    <ProjectDurationChart
                        :projects="company.projects"
                        @project-click="onProjectChartClick"
                    />

                    <!-- График 2: Задачи (появляется при выборе) -->
                    <div id="task-stats-section">
                        <TaskProgressChart
                            :stats="taskStats"
                            :loading="loadingStats"
                            :project-name="selectedProject?.name"
                        />
                    </div>
                </div>
            </div>

        </div>

        <!-- Modals -->
        <CreateProjectModal
            :show="showProjectModal"
            :managers="managers"
            :loading="submitLoading"
            :error="errorText"
            :current-user-id="props.auth.user.id"
            @close="showProjectModal = false"
            @submit="handleCreateProject"
        />

        <CompanyMembersModal
            :show="showMembersModal"
            :company-id="companyId"
            @close="showMembersModal = false"
        />

    </AuthenticatedLayout>
</template>

<style scoped>

</style>
