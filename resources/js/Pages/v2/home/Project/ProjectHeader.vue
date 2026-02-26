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
    // Используем плоские цвета (flat colors)
    if (days === null) return 'bg-slate-50 text-slate-600 border-slate-200'
    if (days > 7) return 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800'
    if (days >= 0) return 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:border-amber-800'
    return 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-900/30 dark:text-rose-300 dark:border-rose-800'
}

const formatDate = (date) => date ? new Date(date).toLocaleDateString('ru-RU', { day: 'numeric', month: 'long', year: 'numeric' }) : '—'
const getInitials = (name) => name?.split(' ').map(p => p[0]).slice(0, 2).join('').toUpperCase() || '?'

const openClientModal = (client) => {
    activeClient.value = client
    showClientModal.value = true
}
</script>

<template>


        <!-- Шапка проекта с названием, статусом и сроком -->
        <div class="project-header">
            <div class="project-title-block">
                <div class="project-icon-large"><i class="fas fa-cloud"></i></div>
                <div class="project-name">
                    <h1> {{ project.name }}</h1>
                    <div class="company-link">
                        <i class="fas fa-building"></i> {{ project.company.name }} · Инициатор: Дмитрий Ильин
                    </div>
                </div>
            </div>
            <div class="project-status-badge">
                <div class="status"><i class="fas fa-play-circle" style="color: #4ade80;"></i> В работе</div>
                <div class="status"><i class="far fa-calendar-alt"></i> Дедлайн:  {{ daysLeft(project.start_date, project.duration_days) }} дн.</div>
            </div>
        </div>

    <div class="key-metrics">
        <!-- Руководители проекта -->
        <div class="metric-card" v-if="project.managers?.length">
            <div class="metric-title"><i class="fas fa-user-tie"></i> Руководители</div>
            <div class="people-group">
                <div class="person-row" v-for="m in project.managers" :key="m.id">
                    <div class="person-avatar">{{ getInitials(m.name) }}</div>
                    <div class="person-info">
                        <h4>{{ m.name }}</h4>
                        <p>кто в компании</p>
                    </div>
                </div>

            </div>
        </div>

        <!-- Исполнители проекта (кратко) -->
        <div class="metric-card" v-if="project.executors?.length">
            <div class="metric-title"><i class="fas fa-users"></i> Исполнители (6)</div>
            <div class="people-group">
                <div class="person-row" v-for="e in project.executors" :key="e.id">
                    <div class="person-avatar">{{ getInitials(e.name) }}</div>
                    <div class="person-info">
                        <h4>{{ e.name }}</h4>
                        <p>кто в комапнии</p>
                    </div>
                </div>


            </div>
        </div>

        <!-- Статус и сроки проекта -->
        <div class="metric-card">
            <div class="metric-title"><i class="fas fa-calendar-check"></i> Статус и сроки</div>
            <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 15px;">
                <span class="status-chip"><i class="fas fa-play"></i> Активный</span>
                <span class="status-chip warning"><i class="fas fa-clock"></i> {{ daysLeft(project.start_date, project.duration_days) }} дней до конца</span>
            </div>
            <div class="date-info">
                <div class="date-row">
                    <span class="date-label">Начало:</span>
                    <span class="date-value"> {{ formatDate(project.start_date) }}</span>
                </div>

                <div class="date-row">
                    <span class="date-label">Длительность:</span>
                    <span class="date-value">{{ project.duration_days ?? '—' }} дней</span>
                </div>
            </div>
        </div>
    </div>


    <div class="space-y-10 animate-fade-in text-slate-900 dark:text-slate-100 font-sans">







        <!-- 2. METRICS (Карточки с обводкой, без заливки) -->


        <!-- 3. TEAM -->


        <!-- 4. CLIENTS -->
        <div v-if="project.clients?.length">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-5 flex items-center gap-2">
                Контрагенты
                <span class="bg-slate-100 dark:bg-slate-800 text-slate-500 text-xs px-2 py-1 rounded-full">{{ project.clients.length }}</span>
            </h3>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                <div v-for="c in project.clients" :key="c.id"
                     @click="openClientModal(c)"
                     class="group cursor-pointer bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 hover:border-indigo-500 dark:hover:border-indigo-500 transition-all duration-300"
                >
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 group-hover:bg-indigo-50 group-hover:text-indigo-600 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        </div>
                        <svg class="w-4 h-4 text-slate-300 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </div>

                    <h4 class="font-bold text-slate-900 dark:text-white truncate pr-2">{{ c.organization_name || c.name }}</h4>
                    <p class="text-sm text-slate-500 mt-1 truncate">{{ c.email || 'Email скрыт' }}</p>
                </div>
            </div>
        </div>

        <!-- MODAL (Clean Design) -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div v-if="showClientModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <!-- Backdrop with blur -->
                <div class="absolute inset-0 bg-slate-900/20 dark:bg-black/50 backdrop-blur-sm" @click="showClientModal = false"></div>

                <!-- Content -->
                <div class="relative w-full max-w-md bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden">

                    <!-- Modal Header -->
                    <div class="px-6 py-6 border-b border-slate-100 dark:border-slate-800 flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-900 dark:text-white leading-tight">{{ activeClient?.name }}</h2>
                            <p class="text-sm text-slate-500">{{ activeClient?.organization_name }}</p>
                        </div>
                        <button class="ml-auto text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition" @click="showClientModal = false">✕</button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 space-y-5">
                        <div class="grid grid-cols-1 gap-4">
                            <div class="p-3 rounded-lg border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
                                <span class="text-xs font-bold text-slate-400 uppercase tracking-wide block mb-1">Email</span>
                                <span class="text-sm font-medium text-slate-800 dark:text-slate-200 select-all">{{ activeClient?.email || '—' }}</span>
                            </div>
                            <div class="p-3 rounded-lg border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
                                <span class="text-xs font-bold text-slate-400 uppercase tracking-wide block mb-1">Телефон</span>
                                <span class="text-sm font-medium text-slate-800 dark:text-slate-200 select-all">{{ activeClient?.phone || '—' }}</span>
                            </div>
                        </div>

                        <div v-if="activeClient?.notes">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wide">Заметки</span>
                            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400 leading-relaxed p-3 bg-white dark:bg-slate-950 border border-slate-100 dark:border-slate-800 rounded-lg">
                                {{ activeClient.notes }}
                            </p>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="p-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800">
                        <button @click="showClientModal = false" class="w-full py-2.5 rounded-lg bg-slate-900 hover:bg-slate-800 dark:bg-white dark:hover:bg-slate-100 text-white dark:text-slate-900 font-semibold text-sm transition shadow-sm">
                            Закрыть
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

    </div>
</template>

<style scoped>
@import "main.css";
</style>
