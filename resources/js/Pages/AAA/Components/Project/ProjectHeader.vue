<script setup>
import { ref, computed } from 'vue'

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
    if (days === null) return 'text-slate-400'
    if (days > 7) return 'text-emerald-500'
    if (days >= 0) return 'text-amber-500'
    return 'text-rose-500'
}

const timeProgress = computed(() => {
    if (!props.project.start_date || !props.project.duration_days) return 0;
    const start = new Date(props.project.start_date).getTime();
    const duration = props.project.duration_days * 24 * 60 * 60 * 1000;
    const now = new Date().getTime();
    const elapsed = now - start;
    return Math.min(Math.max(Math.round((elapsed / duration) * 100), 0), 100);
});

const formatDate = (date) => date ? new Date(date).toLocaleDateString('ru-RU', { day: 'numeric', month: 'short', year: 'numeric' }) : '—'
const getInitials = (name) => name?.split(' ').map(p => p[0]).slice(0, 2).join('').toUpperCase() || '?'

const openClientModal = (client) => {
    activeClient.value = client
    showClientModal.value = true
}
</script>

<template>
    <div class="space-y-8 animate-fade-in pb-10">

        <!-- 1. HEADER SECTION -->
        <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6">
            <div class="space-y-3">
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-indigo-600 text-white">
                        Project
                    </span>
                    <span v-if="project.company" class="flex items-center gap-1 text-sm font-bold text-slate-400 uppercase tracking-tight">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        {{ project.company.name }}
                    </span>
                </div>
                <h1 class="text-4xl md:text-5xl font-black text-slate-900 dark:text-white tracking-tight leading-none">
                    {{ project.name }}
                </h1>
            </div>

            <div class="flex items-center gap-4">
                <div v-if="project.status" class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-sm flex items-center gap-3">
                    <div class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></div>
                    <span class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ project.status }}</span>
                </div>
            </div>
        </div>

        <!-- 2. BENTO METRICS GRID -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

            <!-- Основные даты -->
            <div class="md:col-span-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[2rem] p-8 shadow-sm flex flex-col justify-between overflow-hidden relative">
                <div class="flex flex-col md:flex-row justify-between gap-8 relative z-10">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Дата запуска</p>
                        <p class="text-2xl font-bold">{{ formatDate(project.start_date) }}</p>
                    </div>
                    <div class="md:text-right">
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Ожидаемое завершение</p>
                        <p class="text-2xl font-bold" :class="getDeadlineColor(daysLeft(project.start_date, project.duration_days))">
                            {{ daysLeft(project.start_date, project.duration_days) }} дней осталось
                        </p>
                    </div>
                </div>

                <!-- Прогресс бар времени -->
                <div class="mt-10 relative z-10">
                    <div class="flex justify-between text-[10px] font-bold uppercase mb-2">
                        <span class="text-slate-400">График проекта</span>
                        <span class="text-indigo-500">{{ timeProgress }}% времени пройдено</span>
                    </div>
                    <div class="h-3 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden p-0.5">
                        <div class="h-full bg-indigo-500 rounded-full transition-all duration-1000 ease-out shadow-[0_0_12px_rgba(99,102,241,0.5)]" :style="{ width: timeProgress + '%' }"></div>
                    </div>
                </div>
                <!-- Декоративный фон -->
                <div class="absolute -right-10 -bottom-10 text-slate-50 dark:text-slate-800/20 transform rotate-12">
                    <svg class="w-40 h-40" fill="currentColor" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>

            <!-- Длительность (Маленькая карточка) -->
            <div class="bg-indigo-600 rounded-[2rem] p-8 text-white shadow-xl shadow-indigo-500/20 flex flex-col justify-center items-center text-center">
                <p class="text-[10px] font-black uppercase tracking-widest opacity-60 mb-2">Общий срок</p>
                <p class="text-5xl font-black">{{ project.duration_days ?? '—' }}</p>
                <p class="text-sm font-bold mt-1 uppercase tracking-tighter opacity-80">календарных дней</p>
            </div>
        </div>

        <!-- 3. TEAM & CLIENTS SECTION -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

            <!-- TEAM CARD -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[2rem] p-8">
                <h3 class="text-xl font-bold mb-6 flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 flex items-center justify-center">👥</span>
                    Команда проекта
                </h3>

                <div class="space-y-6">
                    <!-- Менеджеры -->
                    <div v-if="project.managers?.length">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 pl-1">Руководители</p>
                        <div class="flex flex-wrap gap-3">
                            <div v-for="m in project.managers" :key="m.id" class="flex items-center gap-3 bg-slate-50 dark:bg-slate-800 p-2 pr-4 rounded-2xl border border-slate-100 dark:border-slate-700">
                                <div class="w-10 h-10 rounded-xl bg-white dark:bg-slate-700 shadow-sm flex items-center justify-center text-xs font-black text-indigo-600">
                                    {{ getInitials(m.name) }}
                                </div>
                                <span class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ m.name }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Исполнители -->
                    <div v-if="project.executors?.length">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 pl-1">Исполнители</p>
                        <div class="flex flex-wrap gap-2">
                            <div v-for="e in project.executors" :key="e.id"
                                 class="w-10 h-10 rounded-full border-2 border-white dark:border-slate-900 bg-slate-200 dark:bg-slate-700 flex items-center justify-center text-[10px] font-black hover:scale-110 transition-transform cursor-help"
                                 :title="e.name">
                                {{ getInitials(e.name) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CLIENTS CARD -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[2rem] p-8">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold flex items-center gap-3">
                        <span class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 flex items-center justify-center">🤝</span>
                        Контрагенты
                    </h3>
                    <span class="text-xs font-black text-slate-400">{{ project.clients?.length || 0 }}</span>
                </div>

                <div class="space-y-3">
                    <div v-for="c in project.clients" :key="c.id"
                         @click="openClientModal(c)"
                         class="group p-4 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-transparent hover:border-emerald-300 dark:hover:border-emerald-500 transition-all cursor-pointer flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-white dark:bg-slate-700 flex items-center justify-center text-xl shadow-sm group-hover:rotate-12 transition-transform">
                                🏢
                            </div>
                            <div>
                                <p class="font-bold text-slate-800 dark:text-slate-100 line-clamp-1">{{ c.organization_name || c.name }}</p>
                                <p class="text-xs text-slate-400">{{ c.email || 'Контакт не указан' }}</p>
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-slate-300 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL -->
        <Transition name="modal">
            <div v-if="showClientModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-md" @click="showClientModal = false"></div>
                <div class="relative w-full max-w-lg bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl overflow-hidden animate-in zoom-in-95 duration-200">
                    <div class="p-10">
                        <div class="flex justify-between items-start mb-8">
                            <div class="w-20 h-20 rounded-[2rem] bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-4xl">🏢</div>
                            <button @click="showClientModal = false" class="p-2 text-slate-400 hover:text-slate-600 transition-colors">✕</button>
                        </div>
                        <h2 class="text-3xl font-black text-slate-900 dark:text-white mb-2 leading-tight">{{ activeClient?.organization_name || activeClient?.name }}</h2>
                        <p class="text-slate-500 mb-8">{{ activeClient?.organization_name ? activeClient.name : 'Персональный контакт' }}</p>

                        <div class="grid grid-cols-1 gap-4 mb-8">
                            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-700">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Email</p>
                                <p class="font-bold select-all">{{ activeClient?.email || '—' }}</p>
                            </div>
                            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-700">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Телефон</p>
                                <p class="font-bold select-all">{{ activeClient?.phone || '—' }}</p>
                            </div>
                        </div>

                        <button @click="showClientModal = false" class="w-full py-4 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-2xl font-bold shadow-lg shadow-slate-900/20 active:scale-95 transition-all">
                            Закрыть карточку
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.3s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
