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
        <h3 class="text-xl font-extrabold text-slate-800 dark:text-slate-100 flex items-center gap-2">
            🚀 Проекты под моим управлением
        </h3>

        <!-- СПИСОК (ПРЕВЬЮ, МАКС 6 ШТ) -->
        <div class="space-y-6">
            <div v-for="(projs, companyName) in groupedLimited" :key="companyName"
                 class="bg-white/60 dark:bg-slate-900/60 backdrop-blur-md p-5 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-xl transition-shadow duration-300">

                <p class="text-xs font-black uppercase tracking-widest text-indigo-500 mb-4 flex justify-between">
                    <span>{{ companyName }}</span>
                    <span class="opacity-50">{{ projs.length }} показано</span>
                </p>

                <div class="space-y-3">
                    <div v-for="p in projs" :key="p.id"
                         @click="router.visit(`/projects/${p.id}`)"
                         class="group relative p-3 bg-white dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 rounded-2xl hover:bg-indigo-50 dark:hover:bg-indigo-900/20 hover:border-indigo-200 transition cursor-pointer">

                        <div class="flex justify-between items-start mb-2">
                            <span class="text-sm font-bold text-slate-700 dark:text-slate-200 leading-tight">{{ p.name }}</span>
                            <span class="text-xs text-slate-400 group-hover:translate-x-1 transition">→</span>
                        </div>

                        <div class="flex items-center gap-3 text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 border-t border-slate-100 dark:border-slate-700/50 pt-2">
                            <div class="flex items-center gap-1" title="Дата создания">
                                <span>📅</span><span>{{ formatDate(p.created_at) }}</span>
                            </div>
                            <div class="flex items-center gap-1" :class="{'text-rose-500 font-medium': !p.duration_days}" title="Дедлайн">
                                <span>🏁</span><span>{{ calculateDeadline(p.start_date, p.duration_days) }}</span>
                            </div>
                            <div class="ml-auto flex items-center gap-1 bg-slate-100 dark:bg-slate-700 px-1.5 py-0.5 rounded text-indigo-600 dark:text-indigo-300 font-bold">
                                <span>📝</span><span>{{ p.tasks_count || p.tasks?.length || 0 }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- КНОПКА ПОКАЗАТЬ ВСЕ -->
        <button
            v-if="hiddenCount > 0"
            @click="isModalOpen = true"
            class="w-full py-3 rounded-2xl border-2 border-dashed border-indigo-300 dark:border-indigo-700 text-indigo-600 dark:text-indigo-400 font-bold hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition text-sm uppercase tracking-wide"
        >
            Показать остальные проекты (+{{ hiddenCount }})
        </button>

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
