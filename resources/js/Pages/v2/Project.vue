<script setup>
import {ref, onMounted} from 'vue'
import axios from 'axios'
import {Head, usePage} from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

// Импорт компонентов
import ProjectHeader from './home/Project/ProjectHeader.vue'
import ProjectMenu from './home/Project/ProjectMenu.vue'
import ProjectTasks from './home/Project/ProjectTasks.vue'
import ProjectSidebar from './home/Project/ProjectSidebar.vue'

const {props} = usePage()
const projectId = props.id

const project = ref(null)
const employees = ref([])
const loading = ref(true)

const fetchData = async () => {
    try {
        // Параллельная загрузка проекта и сотрудников
        const [projectRes, employeesRes] = await Promise.all([
            axios.get(`/api/projects/${projectId}`),
            axios.get(`/api/projects/${projectId}/employees`)
        ])
        project.value = projectRes.data
        employees.value = employeesRes.data
    } catch (e) {
        console.error(e)
    } finally {
        loading.value = false
    }
}

// Функция обновления, которую передаем детям
const onRefresh = async () => {
    // Обновляем только проект, сотрудники редко меняются, но если надо - можно и их
    const {data} = await axios.get(`/api/projects/${projectId}`)
    project.value = data
}

onMounted(fetchData)
</script>

<template>
    <Head :title="project?.name ? `Проект — ${project.name}` : 'Проект'"/>
    <AuthenticatedLayout>
1234
        <!-- HERO SECTION -->
        <div v-if="project"
             class="rounded-3xl border border-slate-100 bg-white dark:bg-slate-800 dark:border-slate-700 p-6 shadow-sm  top-6">
            <div className="relative max-w-7xl mx-auto px-6 py-10 text-white">
                <div className="flex flex-col lg:flex-row lg:justify-between lg:items-start gap-8">

                    <!-- Левая часть: Инфо -->
                    <div className="flex-1 space-y-4">
                        <ProjectHeader :project="project"/>
                    </div>

                    <!-- Правая часть: Меню и Действия -->
<!--                    <div className="flex flex-col sm:items-end gap-6">-->
<!--                        <ProjectMenu-->
<!--                            :project="project"-->
<!--                            :user="props.auth.user"-->
<!--                            :employees="employees"-->
<!--                            @refresh="onRefresh"-->
<!--                        />-->
<!--                    </div>-->

                </div>
            </div>
        </div>

        <!-- BODY -->
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-4 pb-10" style="margin-top: 3%;">
            <div v-if="loading" className="text-center py-10">Загрузка...</div>

            <div v-else className="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div className="lg:col-span-2">
                    <!-- Задачи -->
                    <ProjectTasks
                        :project="project"
                        :user="props.auth.user"
                        :employees="employees"
                        @refresh="onRefresh"
                    />
                </div>

                <!-- Боковая панель -->
                <div className="space-y-4">
                    <ProjectSidebar :project="project"/>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
