<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps(['projects'])

const LIMIT = 4
const isModalOpen = ref(false)
const selectedCompanyFilter = ref('all') // 'all' или имя компании

// --- HELPER FUNCTIONS ---

const formatDate = (dateString) => {
    if (!dateString) return '—'
    return new Date(dateString).toLocaleDateString('ru-RU', {
        day: '2-digit', month: '2-digit', year: 'numeric'
    })
}

const calculateDeadline = (startDate, duration) => {
    if (!startDate || !duration) return '—'
    const date = new Date(startDate)
    date.setDate(date.getDate() + duration)
    return date.toLocaleDateString('ru-RU', {
        day: '2-digit', month: '2-digit', year: 'numeric'
    })
}

// Функция группировки массива проектов
const groupProjects = (list) => {
    return list.reduce((acc, p) => {
        const cName = p.company?.name || 'Без компании'
        if (!acc[cName]) acc[cName] = []
        acc[cName].push(p)
        return acc
    }, {})
}

// --- LOGIC FOR MAIN VIEW ---

// 1. Берем только первые N проектов для превью
const limitedList = computed(() => {
    return props.projects.slice(0, LIMIT)
})

// 2. Группируем этот урезанный список
const groupedLimited = computed(() => {
    return groupProjects(limitedList.value)
})

// 3. Считаем, сколько скрыто
const hiddenCount = computed(() => {
    return Math.max(0, props.projects.length - LIMIT)
})

// --- LOGIC FOR MODAL ---

// Список всех уникальных компаний для фильтра
const companyNames = computed(() => {
    const names = new Set(props.projects.map(p => p.company?.name || 'Без компании'))
    return Array.from(names).sort()
})

// Отфильтрованные и сгруппированные проекты для модалки
const groupedAllFiltered = computed(() => {
    let list = props.projects

    if (selectedCompanyFilter.value !== 'all') {
        list = list.filter(p => (p.company?.name || 'Без компании') === selectedCompanyFilter.value)
    }

    return groupProjects(list)
})
</script>

<template>
    <div class="space-y-6">

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-tasks" style="margin-right: 8px; color:#9c4dff;"></i> Проекты под моим умправлением</h3>
                <i class="fas fa-ellipsis-h"></i>
            </div>

            <div class="project-list">
                <div v-for="(projs, companyName) in groupedLimited" :key="companyName">
                    <!-- Заголовок компании -->
                    <div class="company-section" style="padding: 12px 16px 4px 16px;">
                        <p style="font-size: 11px; font-weight: 800; letter-spacing: 0.5px; text-transform: uppercase; color: #9c4dff; display: flex; justify-content: space-between; margin: 0;">
                            <span>Компания: {{ companyName }}</span>
                            <span style="opacity: 0.5;">{{ projs.length }} показано</span>
                        </p>
                    </div>

                    <!-- Проекты компании -->
                    <div v-for="p in projs" :key="p.id"
                         class="project-item"
                         @click="router.visit(`/projects/${p.id}`)"
                         style="cursor: pointer;">

                        <!-- Аватар/иконка проекта -->
                        <div class="company-avatar" style="background: #f1e6ff; color:#9c4dff;">
                            🌐
                        </div>

                        <!-- Информация о проекте -->
                        <div class="project-info">
                            <h4>{{ p.name }}</h4>
                            <p style="display: flex; gap: 8px; font-size: 11px;">
                                <span title="Дата создания">📅 {{ formatDate(p.created_at) }}</span>
                                <span title="Дедлайн" :style="{color: !p.duration_days ? '#f43f5e' : 'inherit'}">
                            🏁 {{ calculateDeadline(p.start_date, p.duration_days) }}
                        </span>
                            </p>
                        </div>

                        <!-- Прогресс и количество задач -->
                        <div class="project-progress">
                            <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 12px; background: #f1e6ff; padding: 2px 8px; border-radius: 12px; color: #9c4dff; font-weight: bold;">
                            📝 {{ p.tasks_count || p.tasks?.length || 0 }}
                        </span>
                                <!-- Здесь можно добавить прогресс, если есть данные о прогрессе -->
                                <!-- <span style="font-size:13px;">65%</span>
                                <div class="progress-bar"><div class="progress-fill" style="width:65%"></div></div> -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Кнопка "Все проекты" -->
            <div style="margin-top: 16px; text-align: center;" v-if="hiddenCount > 0">
        <span style="color: #9c4dff; font-weight: 500; cursor: pointer;" @click="isModalOpen = true">
            + Все проекты ({{ hiddenCount }}) <i class="fas fa-arrow-right"></i>
        </span>
            </div>
        </div>



        <!-- МОДАЛЬНОЕ ОКНО -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="isModalOpen = false"></div>

            <!-- Content -->
            <div class="relative bg-white dark:bg-slate-900 rounded-3xl w-full max-w-4xl max-h-[90vh] flex flex-col shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">

                <!-- Header -->
                <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-white dark:bg-slate-900 z-10">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Все проекты</h2>
                        <p class="text-sm text-slate-500">Всего: {{ props.projects.length }}</p>
                    </div>
                    <button @click="isModalOpen = false" class="p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                        <svg class="w-6 h-6 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <!-- Filters -->
                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-900/50 border-b border-slate-100 dark:border-slate-800 overflow-x-auto custom-scrollbar">
                    <div class="flex gap-2">
                        <button
                            @click="selectedCompanyFilter = 'all'"
                            class="px-4 py-2 rounded-full text-xs font-bold uppercase tracking-wider transition whitespace-nowrap"
                            :class="selectedCompanyFilter === 'all'
                                ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/30'
                                : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-indigo-50 dark:hover:bg-slate-700'"
                        >
                            Все компании
                        </button>
                        <button
                            v-for="name in companyNames"
                            :key="name"
                            @click="selectedCompanyFilter = name"
                            class="px-4 py-2 rounded-full text-xs font-bold uppercase tracking-wider transition whitespace-nowrap"
                            :class="selectedCompanyFilter === name
                                ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/30'
                                : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-indigo-50 dark:hover:bg-slate-700'"
                        >
                            {{ name }}
                        </button>
                    </div>
                </div>

                <!-- List (Scrollable) -->
                <div class="p-6 overflow-y-auto custom-scrollbar space-y-8">
                    <div v-if="Object.keys(groupedAllFiltered).length === 0" class="text-center py-10 text-slate-400">
                        Проекты не найдены
                    </div>

                    <div v-for="(projs, companyName) in groupedAllFiltered" :key="companyName">
                        <h4 class="text-sm font-black uppercase tracking-widest text-indigo-500 mb-4 sticky top-0 bg-white/90 dark:bg-slate-900/90 backdrop-blur py-2 z-10">
                            {{ companyName }} <span class="text-slate-400 ml-2">({{ projs.length }})</span>
                        </h4>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div v-for="p in projs" :key="p.id"
                                 @click="router.visit(`/projects/${p.id}`)"
                                 class="group relative p-4 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl hover:border-indigo-400 hover:shadow-lg transition cursor-pointer flex flex-col justify-between h-32">

                                <div>
                                    <h5 class="font-bold text-slate-800 dark:text-white line-clamp-2">{{ p.name }}</h5>
                                </div>

                                <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 mt-2">
                                    <div class="flex flex-col">
                                        <span class="text-[10px] uppercase font-bold text-slate-400">Дедлайн</span>
                                        <span>{{ calculateDeadline(p.start_date, p.duration_days) }}</span>
                                    </div>
                                    <div class="bg-indigo-50 dark:bg-slate-700 text-indigo-600 dark:text-indigo-300 px-2 py-1 rounded-lg font-bold">
                                        {{ p.tasks_count || p.tasks?.length || 0 }} задач
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>

<style scoped>

@import "home.css";

.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    @apply bg-slate-300 dark:bg-slate-700 rounded-full;
}
</style>
