<script setup>
import { ref, computed } from 'vue'

const props = defineProps(['project'])
const showClientModal = ref(false)
const activeClient = ref(null)
const expandedSections = ref(['managers', 'team', 'clients'])

// --- Helpers ---

const daysLeft = (startDate, duration) => {
    if (!startDate || !duration) return null
    const start = new Date(startDate)
    const end = new Date(start)
    end.setDate(start.getDate() + Number(duration))
    const diff = Math.ceil((end - new Date()) / (1000 * 60 * 60 * 24))
    return diff
}

const getDeadlineStatus = (days) => {
    if (days === null) return { color: 'slate', label: 'Не указан', icon: '📅' }
    if (days < 0) return { color: 'rose', label: 'Просрочен', icon: '⚠️' }
    if (days <= 3) return { color: 'amber', label: 'Срочно', icon: '🔥' }
    if (days <= 7) return { color: 'blue', label: 'Скоро', icon: '⏰' }
    return { color: 'emerald', label: 'В норме', icon: '✅' }
}

const getDeadlineColor = (days) => {
    const status = getDeadlineStatus(days)
    const colors = {
        rose: 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/30 dark:text-rose-300 dark:border-rose-800',
        amber: 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/30 dark:text-amber-300 dark:border-amber-800',
        blue: 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/30 dark:text-blue-300 dark:border-blue-800',
        emerald: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-300 dark:border-emerald-800',
        slate: 'bg-slate-50 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700'
    }
    return colors[status.color] || colors.slate
}

const formatDate = (date) => {
    if (!date) return '—'
    return new Date(date).toLocaleDateString('ru-RU', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    })
}

const getInitials = (name) => {
    if (!name) return '?'
    return name.split(' ').map(p => p[0]).slice(0, 2).join('').toUpperCase()
}

const openClientModal = (client) => {
    activeClient.value = client
    showClientModal.value = true
}

const toggleSection = (section) => {
    if (expandedSections.value.includes(section)) {
        expandedSections.value = expandedSections.value.filter(s => s !== section)
    } else {
        expandedSections.value.push(section)
    }
}

// Статистика проекта
const projectStats = computed(() => {
    const totalTasks = props.project?.tasks?.length || 0
    const completedTasks = props.project?.tasks?.filter(t => t.status === 'completed').length || 0
    const progress = totalTasks > 0 ? Math.round((completedTasks / totalTasks) * 100) : 0

    return {
        totalTasks,
        completedTasks,
        progress,
        managersCount: props.project?.managers?.length || 0,
        executorsCount: props.project?.executors?.length || 0,
        clientsCount: props.project?.clients?.length || 0
    }
})
</script>

<template>
    <div class="space-y-8 animate-fade-in">

        <!-- Секция с прогрессом -->
        <div class="bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-950/30 dark:to-purple-950/30 rounded-2xl p-6 border border-indigo-100 dark:border-indigo-900/50">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300">Прогресс проекта</h3>
                <span class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ projectStats.progress }}%</span>
            </div>

            <!-- Прогресс бар -->
            <div class="w-full h-2.5 bg-white dark:bg-slate-700 rounded-full overflow-hidden mb-4">
                <div class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full transition-all duration-1000"
                     :style="{ width: projectStats.progress + '%' }"></div>
            </div>

            <!-- Статистика -->
            <div class="grid grid-cols-3 gap-2 text-center">
                <div class="bg-white/50 dark:bg-slate-800/50 rounded-lg p-2">
                    <div class="text-lg font-bold text-slate-800 dark:text-white">{{ projectStats.totalTasks }}</div>
                    <div class="text-[10px] text-slate-500">Всего задач</div>
                </div>
                <div class="bg-white/50 dark:bg-slate-800/50 rounded-lg p-2">
                    <div class="text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ projectStats.completedTasks }}</div>
                    <div class="text-[10px] text-slate-500">Завершено</div>
                </div>
                <div class="bg-white/50 dark:bg-slate-800/50 rounded-lg p-2">
                    <div class="text-lg font-bold text-amber-600 dark:text-amber-400">{{ projectStats.totalTasks - projectStats.completedTasks }}</div>
                    <div class="text-[10px] text-slate-500">Осталось</div>
                </div>
            </div>
        </div>

        <!-- Основная информация -->
        <div class="space-y-6">

            <!-- Менеджеры -->
            <div v-if="project.managers?.length" class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div @click="toggleSection('managers')"
                     class="flex items-center justify-between p-5 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-slate-900 dark:text-white">Руководители</h3>
                            <p class="text-xs text-slate-500">{{ project.managers.length }} {{ project.managers.length === 1 ? 'человек' : 'человека' }}</p>
                        </div>
                    </div>
                    <svg class="w-5 h-5 text-slate-400 transition-transform duration-300"
                         :class="{ 'rotate-180': expandedSections.includes('managers') }"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>

                <Transition name="slide">
                    <div v-if="expandedSections.includes('managers')" class="px-5 pb-5 space-y-3">
                        <div v-for="m in project.managers" :key="m.id"
                             class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 dark:bg-slate-700/50 border border-slate-100 dark:border-slate-700 group hover:border-indigo-200 dark:hover:border-indigo-700 transition-all">
                            <div class="relative">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white text-sm font-bold">
                                    {{ getInitials(m.name) }}
                                </div>
                                <div class="absolute -bottom-1 -right-1 w-3 h-3 bg-emerald-400 rounded-full border-2 border-white dark:border-slate-800"></div>
                            </div>
                            <div class="flex-1">
                                <div class="font-semibold text-slate-800 dark:text-white text-sm">{{ m.name }}</div>
                                <div class="text-xs text-slate-500 flex items-center gap-2">
                                    <span class="flex items-center gap-1">
                                        <span>📧</span>
                                        {{ m.email || 'Нет email' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>

            <!-- Команда -->
            <div v-if="project.executors?.length" class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div @click="toggleSection('team')"
                     class="flex items-center justify-between p-5 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-slate-900 dark:text-white">Команда проекта</h3>
                            <p class="text-xs text-slate-500">{{ project.executors.length }} {{ project.executors.length === 1 ? 'участник' : 'участников' }}</p>
                        </div>
                    </div>
                    <svg class="w-5 h-5 text-slate-400 transition-transform duration-300"
                         :class="{ 'rotate-180': expandedSections.includes('team') }"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>

                <Transition name="slide">
                    <div v-if="expandedSections.includes('team')" class="px-5 pb-5">
                        <div class="grid grid-cols-2 gap-2">
                            <div v-for="e in project.executors" :key="e.id"
                                 class="flex items-center gap-2 p-2 rounded-lg bg-slate-50 dark:bg-slate-700/30 border border-slate-100 dark:border-slate-700 hover:border-emerald-200 dark:hover:border-emerald-800 transition-all">
                                <div class="w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-700 dark:text-emerald-300 text-xs font-bold">
                                    {{ getInitials(e.name) }}
                                </div>
                                <span class="text-xs font-medium text-slate-700 dark:text-slate-300 truncate">{{ e.name }}</span>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>

            <!-- Контрагенты -->
            <div v-if="project.clients?.length" class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div @click="toggleSection('clients')"
                     class="flex items-center justify-between p-5 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 dark:text-amber-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-slate-900 dark:text-white">Контрагенты</h3>
                            <p class="text-xs text-slate-500">{{ project.clients.length }} {{ project.clients.length === 1 ? 'компания' : 'компаний' }}</p>
                        </div>
                    </div>
                    <svg class="w-5 h-5 text-slate-400 transition-transform duration-300"
                         :class="{ 'rotate-180': expandedSections.includes('clients') }"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>

                <Transition name="slide">
                    <div v-if="expandedSections.includes('clients')" class="px-5 pb-5 space-y-3">
                        <div v-for="c in project.clients" :key="c.id"
                             @click="openClientModal(c)"
                             class="group cursor-pointer p-4 rounded-xl bg-gradient-to-br from-slate-50 to-white dark:from-slate-800 dark:to-slate-900 border border-slate-200 dark:border-slate-700 hover:border-amber-300 dark:hover:border-amber-700 transition-all">

                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 dark:text-amber-400">
                                        <span class="text-lg">{{ c.organization_name?.charAt(0) || c.name?.charAt(0) || '🏢' }}</span>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-slate-900 dark:text-white text-sm">{{ c.organization_name || c.name }}</h4>
                                        <p class="text-xs text-slate-500">{{ c.name }}</p>
                                    </div>
                                </div>
                                <svg class="w-4 h-4 text-slate-400 group-hover:text-amber-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </div>

                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <div class="p-2 rounded-lg bg-slate-100 dark:bg-slate-700/50">
                                    <span class="text-slate-500">📧</span>
                                    <span class="ml-1 text-slate-700 dark:text-slate-300">{{ c.email || '—' }}</span>
                                </div>
                                <div class="p-2 rounded-lg bg-slate-100 dark:bg-slate-700/50">
                                    <span class="text-slate-500">📞</span>
                                    <span class="ml-1 text-slate-700 dark:text-slate-300">{{ c.phone || '—' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>

            <!-- Мета информация -->
            <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-5 border border-slate-200 dark:border-slate-700">
                <div class="space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-500 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Создан
                        </span>
                        <span class="font-medium text-slate-700 dark:text-slate-300">{{ formatDate(project.created_at) }}</span>
                    </div>

                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-500 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            ID проекта
                        </span>
                        <span class="font-mono text-xs bg-white dark:bg-slate-700 px-2 py-1 rounded text-indigo-600 dark:text-indigo-400">
                            #{{ project.id }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL (Современный дизайн) -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95">

            <div v-if="showClientModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <!-- Backdrop с эффектом стекла -->
                <div class="absolute inset-0 bg-slate-900/40 dark:bg-black/60 backdrop-blur-md" @click="showClientModal = false"></div>

                <!-- Content -->
                <div class="relative w-full max-w-md bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden transform transition-all">

                    <!-- Декоративный градиент -->
                    <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-amber-500 via-orange-500 to-red-500"></div>

                    <!-- Modal Header -->
                    <div class="relative px-6 py-6 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-500 flex items-center justify-center text-white text-2xl font-bold shadow-lg">
                                {{ activeClient?.organization_name?.charAt(0) || activeClient?.name?.charAt(0) || '🏢' }}
                            </div>
                            <div class="flex-1">
                                <h2 class="text-xl font-bold text-slate-900 dark:text-white leading-tight">{{ activeClient?.organization_name || activeClient?.name }}</h2>
                                <p class="text-sm text-slate-500 mt-1">{{ activeClient?.name || 'Представитель' }}</p>
                            </div>
                            <button @click="showClientModal = false"
                                    class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 flex items-center justify-center text-slate-500 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 space-y-5">
                        <!-- Контактная информация -->
                        <div class="space-y-3">
                            <div class="flex items-center gap-3 p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700">
                                <div class="w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <div class="text-xs text-slate-500 mb-1">Email</div>
                                    <div class="text-sm font-medium text-slate-800 dark:text-slate-200 select-all">{{ activeClient?.email || 'Не указан' }}</div>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700">
                                <div class="w-10 h-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <div class="text-xs text-slate-500 mb-1">Телефон</div>
                                    <div class="text-sm font-medium text-slate-800 dark:text-slate-200 select-all">{{ activeClient?.phone || 'Не указан' }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Заметки -->
                        <div v-if="activeClient?.notes" class="space-y-2">
                            <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider flex items-center gap-2">
                                <span>📝</span>
                                Заметки
                            </h4>
                            <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-700 text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                                {{ activeClient.notes }}
                            </div>
                        </div>

                        <!-- Дополнительная информация -->
                        <div class="pt-4 border-t border-slate-100 dark:border-slate-800">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-500">ID клиента</span>
                                <span class="font-mono text-indigo-600 dark:text-indigo-400">#{{ activeClient?.id }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="p-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800">
                        <button @click="showClientModal = false"
                                class="w-full py-3 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-semibold text-sm transition-all shadow-lg shadow-indigo-500/30 hover:shadow-xl">
                            Закрыть
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

    </div>
</template>

<style scoped>
/* Анимации для секций */
.slide-enter-active,
.slide-leave-active {
    transition: all 0.3s ease;
}

.slide-enter-from {
    opacity: 0;
    transform: translateY(-10px);
}

.slide-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}

/* Анимация появления */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fadeIn 0.5s ease-out;
}

/* Плавные переходы */
* {
    transition: background-color 0.2s ease, border-color 0.2s ease, transform 0.2s ease;
}

/* Эффект стекла */
.backdrop-blur-md {
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

/* Кастомный скроллбар */
::-webkit-scrollbar {
    width: 4px;
}

::-webkit-scrollbar-track {
    background: transparent;
}

::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 20px;
}

.dark ::-webkit-scrollbar-thumb {
    background: #475569;
}
</style>
