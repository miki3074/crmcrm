<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { Head, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

import ProjectHeader from '../AAA/Components/Project/ProjectHeader.vue'
import ProjectMenu from '../AAA/Components/Project/ProjectMenu.vue'
import ProjectTasks from '../AAA/Components/Project/ProjectTasks.vue'
import ProjectSidebar from '../AAA/Components/Project/ProjectSidebar.vue'

const { props } = usePage()
const projectId = props.id

const project = ref(null)
const employees = ref([])
const loading = ref(true)
const loadError = ref('')

const fetchData = async () => {
    loading.value = true
    loadError.value = ''

    try {
        const [projectRes, employeesRes] = await Promise.all([
            axios.get(`/api/projects/${projectId}`),
            axios.get(`/api/projects/${projectId}/employees`)
        ])

        project.value = projectRes.data
        employees.value = employeesRes.data
    } catch (error) {
        console.error(error)
        loadError.value = 'Не удалось загрузить проект'
    } finally {
        loading.value = false
    }
}

const onRefresh = async () => {
    const { data } = await axios.get(`/api/projects/${projectId}`)
    project.value = data
}

onMounted(fetchData)
</script>

<template>
    <Head :title="project?.name ? `Проект — ${project.name}` : 'Проект'" />

    <AuthenticatedLayout>
        <main class="min-h-screen bg-slate-50/70 dark:bg-slate-950">
            <div class="mx-auto max-w-[1480px] px-3 py-3 sm:px-5 lg:px-6">
                <div v-if="loading" class="grid min-h-[55vh] place-items-center">
                    <div class="flex items-center gap-3 text-sm font-medium text-slate-500">
                        <span class="h-5 w-5 animate-spin rounded-full border-2 border-slate-300 border-t-indigo-600"></span>
                        Загрузка проекта
                    </div>
                </div>

                <div v-else-if="loadError" class="rounded-2xl border border-rose-200 bg-rose-50 p-6 text-center">
                    <p class="font-semibold text-rose-700">{{ loadError }}</p>
                    <button class="mt-3 rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white" @click="fetchData">
                        Повторить
                    </button>
                </div>

                <template v-else-if="project">
                    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex flex-col gap-3 p-4 lg:flex-row lg:items-start lg:justify-between">
                            <ProjectHeader :project="project" class="min-w-0 flex-1" />

                            <ProjectMenu
                                :project="project"
                                :user="props.auth.user"
                                :employees="employees"
                                @refresh="onRefresh"
                            />
                        </div>
                    </section>

                    <section class="mt-3 grid grid-cols-1 gap-3 xl:grid-cols-[minmax(0,1fr)_320px]">
                        <div class="min-w-0 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-5">
                            <ProjectTasks
                                :project="project"
                                :user="props.auth.user"
                                :employees="employees"
                                @refresh="onRefresh"
                            />
                        </div>

                        <aside class="min-w-0">
                            <ProjectSidebar :project="project" />
                        </aside>
                    </section>
                </template>
            </div>
        </main>
    </AuthenticatedLayout>
</template>
