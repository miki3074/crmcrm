<script setup>
import { ref, onMounted, onBeforeUnmount, computed } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import axios from 'axios'

// Импорт новых компонентов
import CompanyHero from '../AAA/Components/Company/CompanyHero.vue'
import ProjectGrid from '../AAA/Components/Company/ProjectGrid.vue'
import ProjectDurationChart from '../AAA/Components/Company/ProjectDurationChart.vue'
import TaskProgressChart from '../AAA/Components/Company/TaskProgressChart.vue'
import CreateProjectModal from '../AAA/Components/Company/CreateProjectModal.vue'
import CompanyMembersModal from '../AAA/Components/Company/CompanyMembersModal.vue'

const { props } = usePage()
const companyId = props.id

// --- State ---
const loading = ref(true)
const loadError = ref('')
const company = ref(null)
const managers = ref([])
const managersLoaded = ref(false)
const showProjectModal = ref(false)
const showMembersModal = ref(false)
const submitLoading = ref(false)
const errorText = ref('')
const isMobile = ref(false)
// Id только что созданного проекта — чтобы ProjectGrid сразу выделил его в списке
const justCreatedProjectId = ref(null)

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
    loadError.value = ''
    try {
        const { data } = await axios.get(`/api/companies/${companyId}`)
        company.value = data
    } catch (e) {
        console.error(e)
        loadError.value = e?.response?.data?.message || 'Не удалось загрузить компанию. Проверьте соединение и попробуйте снова.'
    } finally {
        loading.value = false
    }
}

// Список менеджеров компании меняется редко — не дергаем API при каждом
// открытии модалки создания проекта, кэшируем на время жизни страницы.
const fetchManagers = async () => {
    if (managersLoaded.value) return
    const { data } = await axios.get(`/api/users/managers?company_id=${companyId}`)
    managers.value = data
    managersLoaded.value = true
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
        const { data } = await axios.post('/api/projects', { ...formData, company_id: companyId })
        showProjectModal.value = false
        await fetchCompany() // refresh
        justCreatedProjectId.value = data?.id ?? null
    } catch (e) {
        errorText.value = e?.response?.data?.message || 'Ошибка создания'
    } finally {
        submitLoading.value = false
    }
}

const syncViewport = () => {
    isMobile.value = window.innerWidth < 768
}

onMounted(() => {
    fetchCompany()
    syncViewport()
    window.addEventListener('resize', syncViewport)
})

onBeforeUnmount(() => {
    window.removeEventListener('resize', syncViewport)
})
</script>

<template>
    <Head :title="company?.name || 'Компания'" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-12">

            <!-- Ошибка загрузки компании -->
            <div v-if="loadError && !loading" class="rounded-xl border border-rose-200 bg-rose-50 p-8 text-center dark:border-rose-900/50 dark:bg-rose-950/20">
                <p class="text-sm font-medium text-rose-700 dark:text-rose-300">{{ loadError }}</p>
                <button
                    type="button"
                    class="mt-4 rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-700
                           focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400 focus-visible:ring-offset-2"
                    @click="fetchCompany"
                >
                    Повторить
                </button>
            </div>

            <template v-else>
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
                    :select-project-id="justCreatedProjectId"
                />
            </template>

            <!-- 3. Analytics Section (Desktop only) -->
            <!-- <div v-if="!isMobile && company?.projects?.length" class="space-y-8">
                <div class="flex items-center gap-4">
                    <div class="h-px flex-1 bg-slate-200 dark:bg-slate-700"></div>
                    <h2 class="text-xl font-bold text-slate-400 uppercase tracking-widest">Аналитика</h2>
                    <div class="h-px flex-1 bg-slate-200 dark:bg-slate-700"></div>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
                   
                    <ProjectDurationChart
                        :projects="company.projects"
                        @project-click="onProjectChartClick"
                    />

                    
                    <div id="task-stats-section">
                        <TaskProgressChart
                            :stats="taskStats"
                            :loading="loadingStats"
                            :project-name="selectedProject?.name"
                        />
                    </div>
                </div>
            </div> -->

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
