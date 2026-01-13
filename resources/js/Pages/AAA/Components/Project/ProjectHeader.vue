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
    <div class="space-y-10 animate-fade-in text-slate-900 dark:text-slate-100 font-sans">

        <!-- 1. HEADER -->
        <div class="flex flex-col gap-6 md:flex-row md:items-start md:justify-between border-b border-slate-200 dark:border-slate-800 pb-8">
            <div class="space-y-2">
                <!-- Хлебные крошки / Бейдж -->
                <div class="flex items-center gap-3">
                    <div class="px-2 py-1 rounded text-[10px] font-bold uppercase tracking-widest bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                        Проект
                    </div>
                    <div v-if="project.company" class="flex items-center gap-1.5 text-sm font-medium text-slate-500 dark:text-slate-400">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        {{ project.company.name }}
                    </div>
                </div>

                <h1 class="text-4xl font-extrabold tracking-tight text-slate-900 dark:text-white leading-tight">
                    {{ project.name }}
                </h1>
            </div>

            <!-- Статус (Минималистичный) -->
            <div v-if="project.status" class="self-start md:self-center">
                <span class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700">
                    <span class="w-2 h-2 rounded-full bg-slate-400 mr-2"></span>
                    {{ project.status }}
                </span>
            </div>
        </div>

        <!-- 2. METRICS (Карточки с обводкой, без заливки) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

            <!-- Дата старта -->
            <div class="group p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-indigo-300 dark:hover:border-indigo-700 transition-colors">
                <div class="flex items-center gap-3 mb-2">
                    <div class="p-2 rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Старт</span>
                </div>
                <div class="text-xl font-bold text-slate-800 dark:text-slate-100 pl-1">
                    {{ formatDate(project.start_date) }}
                </div>
            </div>

            <!-- Дедлайн (Цветная карточка) -->
            <div class="p-5 rounded-2xl border transition-colors flex flex-col justify-center"
                 :class="getDeadlineColor(daysLeft(project.start_date, project.duration_days))">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-bold uppercase tracking-wider opacity-70">Дедлайн</span>
                    <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div class="text-xl font-bold">
                    <span v-if="daysLeft(project.start_date, project.duration_days) !== null">
                        {{ daysLeft(project.start_date, project.duration_days) }} дн.
                    </span>
                    <span v-else>—</span>
                </div>
                <div class="text-xs opacity-70 font-medium mt-1">Осталось до завершения</div>
            </div>

            <!-- Длительность -->
            <div class="group p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-slate-300 transition-colors">
                <div class="flex items-center gap-3 mb-2">
                    <div class="p-2 rounded-lg bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Срок</span>
                </div>
                <div class="text-xl font-bold text-slate-800 dark:text-slate-100 pl-1">
                    {{ project.duration_days ?? '—' }} дней
                </div>
            </div>
        </div>

        <!-- 3. TEAM -->
        <div class="grid md:grid-cols-2 gap-10">
            <!-- Менеджеры -->
            <div v-if="project.managers?.length">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider mb-4 border-l-4 border-indigo-500 pl-3">
                    Руководители
                </h3>
                <div class="flex flex-col gap-3">
                    <div v-for="m in project.managers" :key="m.id" class="flex items-center gap-4 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-transparent hover:border-slate-200 dark:hover:border-slate-700 transition-colors">
                        <div class="w-10 h-10 rounded-full bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-600 flex items-center justify-center text-sm font-bold">
                            {{ getInitials(m.name) }}
                        </div>
                        <div>
                            <div class="font-bold text-slate-800 dark:text-slate-200 text-sm">{{ m.name }}</div>
                            <div class="text-xs text-slate-500">Project Manager</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Команда -->
            <div v-if="project.executors?.length">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider mb-4 border-l-4 border-teal-500 pl-3">
                    Команда проекта
                </h3>
                <!-- Avatar Stack Large -->
                <div class="flex flex-wrap gap-4">
                    <div v-for="e in project.executors" :key="e.id" class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-teal-50 text-teal-700 dark:bg-teal-900/30 dark:text-teal-300 flex items-center justify-center text-xs font-bold border border-teal-100 dark:border-teal-800">
                            {{ getInitials(e.name) }}
                        </div>
                        <span class="text-sm text-slate-600 dark:text-slate-400 font-medium">{{ e.name }}</span>
                    </div>
                </div>
            </div>
        </div>

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
