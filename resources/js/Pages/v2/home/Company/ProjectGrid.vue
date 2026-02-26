<script setup>
import { router } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'

const props = defineProps({
    projects: Array, // Ожидаем массив проектов
    loading: Boolean
})

// Состояние выбранного проекта
const selectedProjectId = ref(null)

// При загрузке страницы, если проекты есть, выбираем первый автоматически (опционально)
watch(() => props.projects, (newVal) => {
    if (newVal && newVal.length > 0 && !selectedProjectId.value) {
        selectedProjectId.value = newVal[0].id
    }
}, { immediate: true })

// Вычисляем данные активного проекта
const activeProject = computed(() => {
    return props.projects?.find(p => p.id === selectedProjectId.value) || null
})

// Helpers для проектов
const daysLeft = (startDate, duration) => {
    if (!startDate || !duration) return null
    const start = new Date(startDate)
    const end = new Date(start)
    end.setDate(start.getDate() + Number(duration))
    const today = new Date()
    return Math.ceil((end - today) / (1000 * 60 * 60 * 24))
}

const getBadgeColor = (days) => {
    if (days === null) return 'bg-slate-100 text-slate-500'
    if (days > 7) return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
    if (days >= 0) return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'
    return 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300'
}

const managerInitials = (name) => name?.split(' ').map(p => p[0]).slice(0, 2).join('').toUpperCase() || '?'

// Helpers для ЗАДАЧ (Tasks)
const getPriorityColor = (priority) => {
    switch(priority) {
        case 'high': return 'text-rose-600 bg-rose-50 border-rose-200 dark:bg-rose-900/20 dark:border-rose-800 dark:text-rose-300';
        case 'medium': return 'text-amber-600 bg-amber-50 border-amber-200 dark:bg-amber-900/20 dark:border-amber-800 dark:text-amber-300';
        default: return 'text-slate-600 bg-slate-50 border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400';
    }
}

const getStatusLabel = (status) => {
    switch(status) {
        case 'new': return 'Новая';
        case 'in_work': return 'В работе';
        default: return status;
    }
}
</script>

<template>

    <!-- Две колонки: сотрудники и проекты с количеством задач -->
    <div class="two-col-grid">
        <!-- Карточка сотрудников (команда) -->
        <div class="team-card">
            <div class="card-header">
                <h3><i class="fas fa-users" style="color: #4361ee;"></i> Сотрудники компании</h3>
                <span class="badge-ghost">24 чел.</span>
            </div>
            <div class="member-list">
                <div class="member-item">
                    <div class="member-avatar">ДИ</div>
                    <div class="member-info">
                        <h4>Дмитрий Ильин</h4>
                        <p><i class="fas fa-briefcase"></i> Генеральный директор</p>
                    </div>
                    <span class="member-role">CEO</span>
                </div>




            </div>
            <div style="margin-top: 20px; text-align: center;">
                <span style="color: #4361ee; font-weight: 500; cursor: pointer;"><i class="fas-regular fa-arrow-right"></i> Все сотрудники (24) →</span>
            </div>
        </div>

        <!-- Карточка проектов с количеством задач внутри -->
        <div class="projects-card">
            <div class="card-header">
                <h3><i class="fas fa-tasks" style="color: #9c4dff;"></i> Проекты компании</h3>

            </div>
            <div class="project-list">
                <!-- Проект 1: с количеством задач -->
                <div class="project-item"

                     v-for="project in projects"
                     :key="project.id"
                     @click="router.visit(`/projects/${activeProject.id}`)"
                     style="cursor: pointer; padding: 16px; border-radius: 16px; transition: all 0.2s; margin-bottom: 8px;"
                     :style="{
         background: selectedProjectId === project.id ? 'white' : 'white',
         border: selectedProjectId === project.id ? '2px solid #4361ee' : '1px solid #e2e8f0',
         boxShadow: selectedProjectId === project.id ? '0 4px 12px rgba(67, 97, 238, 0.15)' : 'none'
     }"
                     @mouseenter="($event) => { if(selectedProjectId !== project.id) $event.currentTarget.style.borderColor = '#b1c3ff' }"
                     @mouseleave="($event) => { if(selectedProjectId !== project.id) $event.currentTarget.style.borderColor = '#e2e8f0' }">

                    <!-- Иконка проекта (из нового дизайна) -->
                    <div class="project-icon" style="width: 40px; height: 40px; background: #f0f4ff; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #4361ee; float: left; margin-right: 12px;">
                        <i class="fas fa-cloud"></i>
                    </div>

                    <!-- Информация о проекте -->
                    <div class="project-info" style="overflow: hidden;">
                        <h4 style="margin: 0 0 4px 0; font-size: 16px; font-weight: 600; color: #1e293b;">
                            {{ project.name }}
                        </h4>

                        <!-- Мета-информация (дата и ответственный) -->
                        <div class="project-meta" style="display: flex; gap: 16px; font-size: 12px; color: #64748b; margin-bottom: 8px;">
            <span>
                <i class="far fa-calendar-alt" style="margin-right: 4px;"></i>
                Старт: <span class="font-medium text-slate-700 dark:text-slate-300">{{ project.start_date }}</span>
            </span>
                            <span>
                <i class="fas fa-user" style="margin-right: 4px;"></i>
                                <!-- Берем первого менеджера или показываем "Не назначен" -->
                {{ project.managers?.[0]?.name || 'Не назначен' }}
            </span>
                        </div>

                        <!-- Бейдж с количеством задач (из нового дизайна) -->
                        <div class="task-count-badge" style="display: inline-flex; align-items: center; gap: 4px; background: #f0f4ff; padding: 4px 10px; border-radius: 20px; font-size: 12px;">
                            <i class="fas fa-check-circle" style="color: #4361ee;"></i>
                            <span style="color: #475569;">задачи</span>
                            <span class="task-count-number" style="font-weight: 700; color: #4361ee; margin-left: 2px;">
                {{ project.tasks?.length || 0 }}
            </span>
                        </div>

                        <!-- Дополнительная информация: дни до дедлайна и аватарки (из старого шаблона) -->
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 8px;">
                            <!-- Дни до дедлайна -->


                            <!-- Аватарки менеджеров -->
                            <div class="flex items-center -space-x-1.5 overflow-hidden">
                                <div v-for="m in project.managers?.slice(0, 3)" :key="m.id"
                                     style="width: 24px; height: 24px; border-radius: 50%; border: 2px solid white; background: #e0e7ff; color: #4361ee; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 700;">
                                    {{ managerInitials(m.name) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Очистка float -->
                    <div style="clear: both;"></div>
                </div>

            </div>
            <div style="margin-top: 22px; display: flex; justify-content: space-between; align-items: center;">
                <span class=""></span>
                <span style="color: #9c4dff; font-weight: 500; cursor: pointer;">Все проекты →</span>
            </div>
        </div>
    </div>

    <div class="recent-activity">
        <div class="activity-title">
            <i class="fas fa-clock" style="color:#4361ee;"></i> Последние задачи и события
        </div>
        <div class="activity-list">
            <div class="activity-item">
                <div class="activity-dot"></div>
                <div class="activity-text">Задача "Согласовать макеты" завершена в проекте Мобильное приложение</div>
                <div class="activity-time">2 часа назад</div>
            </div>
            <div class="activity-item">
                <div class="activity-dot" style="background: #f97316;"></div>
                <div class="activity-text">Добавлен новый сотрудник — Анна Соколова (тестировщик)</div>
                <div class="activity-time">вчера</div>
            </div>
            <div class="activity-item">
                <div class="activity-dot"></div>
                <div class="activity-text">В проекте CRM для ритейла создано 5 новых задач</div>
                <div class="activity-time">вчера</div>
            </div>
        </div>
    </div>



</template>

<style scoped>
@import "main.css";
/* Кастомный скроллбар для списка проектов */
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #e2e8f0;
    border-radius: 20px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #334155;
}
</style>



<!--<script setup>-->
<!--import { router } from '@inertiajs/vue3'-->

<!--const props = defineProps({-->
<!--    projects: Array,-->
<!--    loading: Boolean-->
<!--})-->

<!--// Helpers-->
<!--const daysLeft = (startDate, duration) => {-->
<!--    if (!startDate || !duration) return null-->
<!--    const start = new Date(startDate)-->
<!--    const end = new Date(start)-->
<!--    end.setDate(start.getDate() + Number(duration))-->
<!--    const today = new Date()-->
<!--    return Math.ceil((end - today) / (1000 * 60 * 60 * 24))-->
<!--}-->

<!--const getBadgeColor = (days) => {-->
<!--    if (days === null) return 'bg-slate-100 text-slate-500'-->
<!--    if (days > 7) return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'-->
<!--    if (days >= 0) return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'-->
<!--    return 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300'-->
<!--}-->

<!--const managerInitials = (name) => name?.split(' ').map(p => p[0]).slice(0, 2).join('').toUpperCase() || '?'-->
<!--</script>-->

<!--<template>-->
<!--    <div>-->
<!--        <div class="flex items-center justify-between mb-6 px-1">-->
<!--            <h2 class="text-xl font-bold text-slate-800 dark:text-white">🚀 Активные проекты</h2>-->
<!--            <span v-if="projects?.length" class="text-sm font-medium text-slate-500 bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded-lg">-->
<!--                {{ projects.length }}-->
<!--            </span>-->
<!--        </div>-->

<!--        &lt;!&ndash; Скелетон &ndash;&gt;-->
<!--        <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">-->
<!--            <div v-for="i in 3" :key="i" class="h-40 rounded-3xl bg-slate-100 dark:bg-slate-800 animate-pulse"></div>-->
<!--        </div>-->

<!--        &lt;!&ndash; Пустое состояние &ndash;&gt;-->
<!--        <div v-else-if="!projects?.length" class="text-center py-12 rounded-3xl border border-dashed border-slate-300 dark:border-slate-700">-->
<!--            <p class="text-slate-500">Проектов пока нет</p>-->
<!--        </div>-->

<!--        &lt;!&ndash; Сетка &ndash;&gt;-->
<!--        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">-->
<!--            <div-->
<!--                v-for="project in projects"-->
<!--                :key="project.id"-->
<!--                @click="router.visit(`/projects/${project.id}`)"-->
<!--                class="group relative bg-white dark:bg-slate-800 rounded-3xl p-6 border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 cursor-pointer overflow-hidden"-->
<!--            >-->
<!--                <div class="flex justify-between items-start mb-4">-->
<!--                    <h3 class="font-bold text-lg text-slate-800 dark:text-white line-clamp-1 group-hover:text-indigo-600 transition-colors">-->
<!--                        {{ project.name }}-->
<!--                    </h3>-->
<!--                    <span class="text-xs font-bold px-2 py-1 rounded-lg" :class="getBadgeColor(daysLeft(project.start_date, project.duration_days))">-->
<!--                        {{ daysLeft(project.start_date, project.duration_days) }} дн.-->
<!--                    </span>-->
<!--                </div>-->

<!--                <div class="space-y-3">-->
<!--                    <div class="flex items-center text-sm text-slate-500 dark:text-slate-400">-->
<!--                        <span class="mr-2">📅 Старт:</span>-->
<!--                        <span class="font-medium text-slate-700 dark:text-slate-200">{{ project.start_date }}</span>-->
<!--                    </div>-->

<!--                    &lt;!&ndash; Аватарки менеджеров &ndash;&gt;-->
<!--                    <div class="flex items-center -space-x-2 overflow-hidden py-1">-->
<!--                        <div-->
<!--                            v-for="m in project.managers?.slice(0, 4)"-->
<!--                            :key="m.id"-->
<!--                            class="w-8 h-8 rounded-full border-2 border-white dark:border-slate-800 bg-indigo-100 text-indigo-600 flex items-center justify-center text-[10px] font-bold"-->
<!--                            :title="m.name"-->
<!--                        >-->
<!--                            {{ managerInitials(m.name) }}-->
<!--                        </div>-->
<!--                        <div v-if="project.managers?.length > 4" class="w-8 h-8 rounded-full border-2 border-white bg-slate-100 flex items-center justify-center text-[10px] text-slate-500">-->
<!--                            +{{ project.managers.length - 4 }}-->
<!--                        </div>-->
<!--                    </div>-->
<!--                </div>-->

<!--                &lt;!&ndash; Декоративный элемент &ndash;&gt;-->
<!--                <div class="absolute -bottom-4 -right-4 w-20 h-20 bg-indigo-50 dark:bg-indigo-900/20 rounded-full blur-xl group-hover:bg-indigo-100 transition-colors"></div>-->
<!--            </div>-->
<!--        </div>-->
<!--    </div>-->
<!--</template>-->
