<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps(['projects'])

// 1. Форматирование даты (например: 25.10.2023)
const formatDate = (dateString) => {
    if (!dateString) return '—'
    return new Date(dateString).toLocaleDateString('ru-RU', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    })
}

// 2. Расчет дедлайна (Дата начала + Длительность в днях)
const calculateDeadline = (startDate, duration) => {
    if (!startDate || !duration) return '—'
    const date = new Date(startDate)
    date.setDate(date.getDate() + duration) // Добавляем дни
    return date.toLocaleDateString('ru-RU', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    })
}

// Группировка
const grouped = computed(() => {
    return props.projects.reduce((acc, p) => {
        const cName = p.company?.name || 'Без компании'
        if (!acc[cName]) acc[cName] = []
        acc[cName].push(p)
        return acc
    }, {})
})
</script>

<template>
    <div class="space-y-6">
        <h3 class="text-xl font-extrabold text-slate-800 dark:text-slate-100 flex items-center gap-2">
            🚀 Проекты под моим управлением
        </h3>
        <div class="">
            <div v-for="(projs, companyName) in grouped" :key="companyName"
                 class="bg-white/60 dark:bg-slate-900/60 backdrop-blur-md p-5 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-xl transition-shadow duration-300">

                <p class="text-xs font-black uppercase tracking-widest text-indigo-500 mb-4 flex justify-between">
                    <span>{{ companyName }}</span>
                    <span class="opacity-50">{{ projs.length }} шт.</span>
                </p>

                <div class="space-y-3">
                    <div v-for="p in projs" :key="p.id"
                         @click="router.visit(`/projects/${p.id}`)"
                         class="group relative p-3 bg-white dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 rounded-2xl hover:bg-indigo-50 dark:hover:bg-indigo-900/20 hover:border-indigo-200 transition cursor-pointer">

                        <!-- Верх: Название -->
                        <div class="flex justify-between items-start mb-2">
                            <span class="text-sm font-bold text-slate-700 dark:text-slate-200 leading-tight">{{ p.name }}</span>
                            <span class="text-xs text-slate-400 group-hover:translate-x-1 transition">→</span>
                        </div>

                        <!-- Низ: Мета данные (Сетка из 3 колонок или флекс) -->
                        <div class="flex items-center gap-3 text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 border-t border-slate-100 dark:border-slate-700/50 pt-2">

                            <!-- Дата создания -->
                            <div class="flex items-center gap-1" title="Дата создания">
                                <span>📅</span>
                                <span>{{ formatDate(p.created_at) }}</span>
                            </div>

                            <!-- Дедлайн -->
                            <div class="flex items-center gap-1" :class="{'text-rose-500 font-medium': !p.duration_days}" title="Дедлайн (Старт + Длительность)">
                                <span>🏁</span>
                                <span>{{ calculateDeadline(p.start_date, p.duration_days) }}</span>
                            </div>

                            <!-- Кол-во задач -->
                            <!-- Используем p.tasks_count (если с бэка) или p.tasks.length (если массив) -->
                            <div class="ml-auto flex items-center gap-1 bg-slate-100 dark:bg-slate-700 px-1.5 py-0.5 rounded text-indigo-600 dark:text-indigo-300 font-bold">
                                <span>📝</span>
                                <span>{{ p.tasks_count || p.tasks?.length || 0 }}</span>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
