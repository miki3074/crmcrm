<script setup>
import { ref } from 'vue'

const props = defineProps(['project'])
const showClientModal = ref(false)
const activeClient = ref(null)

// --- Helpers ---

const daysLeft = (startDate, duration) => {
    if (!startDate || !duration) return null
    const start = new Date(startDate)
    const end = new Date(start)
    end.setDate(start.getDate() + Number(duration))
    const diff = Math.ceil((end - new Date()) / (1000 * 60 * 60 * 24))
    return diff
}

const getDeadlineColor = (days) => {
    if (days === null) return 'bg-slate-100 text-slate-600 border-slate-200'
    if (days > 7) return 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-400 dark:border-emerald-800'
    if (days >= 0) return 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/20 dark:text-amber-400 dark:border-amber-800'
    return 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-900/20 dark:text-rose-400 dark:border-rose-800'
}

const formatMoney = (val) => val ? Number(val).toLocaleString('ru-RU') + ' ₽' : '—'
const formatDate = (date) => date ? new Date(date).toLocaleDateString('ru-RU', { day: 'numeric', month: 'long', year: 'numeric' }) : '—'
const getInitials = (name) => name?.split(' ').map(p => p[0]).slice(0, 2).join('').toUpperCase() || '?'

const openClientModal = (client) => {
    activeClient.value = client
    showClientModal.value = true
}
</script>

<template>
    <div class="space-y-8 animate-fade-in">

        <!-- 1. HEADER & STATUS -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200 dark:border-slate-700 pb-6">
            <div>
                <div class="flex items-center gap-3 mb-1">
                    <span class="px-2.5 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                        Проект
                    </span>
                    <span v-if="project.company" class="flex items-center gap-1 text-sm text-slate-500 font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        {{ project.company.name }}
                    </span>
                </div>
                <h1 class="text-3xl md:text-4xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                    {{ project.name }}
                </h1>
            </div>

            <div v-if="project.status" class="px-4 py-2 rounded-xl text-sm font-bold bg-white dark:bg-slate-800 shadow-sm border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200">
                Статус: {{ project.status }}
            </div>
        </div>

        <!-- 2. INFO GRID (Metrics) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Старт -->
            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700">
                <div class="text-xs font-bold text-slate-400 uppercase mb-1">Дата старта</div>
                <div class="font-semibold text-slate-700 dark:text-slate-200 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    {{ formatDate(project.start_date) }}
                </div>
            </div>

            <!-- Дедлайн (с цветным индикатором) -->
            <div class="p-4 rounded-2xl border transition-colors"
                 :class="getDeadlineColor(daysLeft(project.start_date, project.duration_days))">
                <div class="text-xs font-bold opacity-70 uppercase mb-1">Дедлайн</div>
                <div class="font-bold flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span v-if="daysLeft(project.start_date, project.duration_days) !== null">
                        {{ daysLeft(project.start_date, project.duration_days) }} дн. осталось
                    </span>
                    <span v-else>—</span>
                </div>
            </div>

            <!-- Бюджет -->


            <!-- Длительность -->
            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700">
                <div class="text-xs font-bold text-slate-400 uppercase mb-1">Срок</div>
                <div class="font-semibold text-slate-700 dark:text-slate-200">
                    {{ project.duration_days ?? '—' }} дней
                </div>
            </div>
        </div>

        <!-- 3. TEAM SECTION -->
        <div class="grid md:grid-cols-2 gap-8">
            <!-- Менеджеры -->
            <div v-if="project.managers?.length">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span> Руководители
                </h3>
                <div class="flex flex-wrap gap-3">
                    <div v-for="m in project.managers" :key="m.id" class="flex items-center gap-3 p-2 pr-4 rounded-full bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 shadow-sm">
                        <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-bold border-2 border-white dark:border-slate-800">
                            {{ getInitials(m.name) }}
                        </div>
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ m.name }}</span>
                    </div>
                </div>
            </div>

            <!-- Исполнители -->
            <div v-if="project.executors?.length">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-teal-500"></span> Команда
                </h3>
                <div class="flex items-center -space-x-2 overflow-hidden py-1">
                    <div v-for="e in project.executors" :key="e.id"
                         class="w-10 h-10 rounded-full bg-teal-50 text-teal-600 border-2 border-white dark:border-slate-900 flex items-center justify-center text-xs font-bold shadow-sm relative hover:z-10 hover:scale-110 transition-transform cursor-help"
                         :title="e.name"
                    >
                        {{ getInitials(e.name) }}
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. CLIENTS SECTION -->
        <div v-if="project.clients?.length" class="pt-4 border-t border-slate-100 dark:border-slate-800">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">👥 Клиенты и Контрагенты</h3>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="c in project.clients" :key="c.id"
                     @click="openClientModal(c)"
                     class="group relative p-5 bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-2xl shadow-sm hover:shadow-md hover:border-indigo-300 transition-all cursor-pointer"
                >
                    <div class="flex items-start justify-between mb-2">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        </div>
                        <span class="text-xs font-medium text-slate-400 group-hover:text-indigo-500 transition-colors">Подробнее ↗</span>
                    </div>
                    <h4 class="font-bold text-slate-800 dark:text-white truncate">{{ c.organization_name || c.name }}</h4>
                    <p class="text-xs text-slate-500 mt-1 truncate">{{ c.email || 'Нет email' }}</p>
                </div>
            </div>
        </div>

        <!-- MODAL -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showClientModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="showClientModal = false"></div>

                <!-- Content -->
                <div class="relative bg-white dark:bg-slate-900 rounded-3xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all p-6">
                    <button class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 transition" @click="showClientModal = false">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>

                    <div class="text-center mb-6">
                        <div class="w-16 h-16 mx-auto bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center mb-4">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        </div>
                        <h2 class="text-xl font-bold text-slate-800 dark:text-white">{{ activeClient?.name }}</h2>
                        <p class="text-sm text-slate-500">{{ activeClient?.organization_name }}</p>
                    </div>

                    <div class="space-y-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl p-4">
                        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-700 pb-2">
                            <span class="text-sm text-slate-500">Email</span>
                            <span class="text-sm font-medium text-slate-800 dark:text-slate-200 select-all">{{ activeClient?.email || '—' }}</span>
                        </div>
                        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-700 pb-2">
                            <span class="text-sm text-slate-500">Телефон</span>
                            <span class="text-sm font-medium text-slate-800 dark:text-slate-200 select-all">{{ activeClient?.phone || '—' }}</span>
                        </div>
                        <div v-if="activeClient?.notes" class="pt-2">
                            <span class="text-xs font-bold text-slate-400 uppercase">Заметки</span>
                            <p class="text-sm text-slate-700 dark:text-slate-300 mt-1 leading-relaxed">{{ activeClient.notes }}</p>
                        </div>
                    </div>

                    <button @click="showClientModal = false" class="mt-6 w-full py-3 rounded-xl bg-slate-900 dark:bg-slate-700 text-white font-medium hover:bg-slate-800 transition">
                        Закрыть
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>
