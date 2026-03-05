<script setup>
import { computed } from 'vue'

const props = defineProps({
    company: Object,
    isOwner: Boolean,
    isAdmin: Boolean,
    canCreate: Boolean
})

const emit = defineEmits(['create', 'openMembers'])

// Статистика компании (можно добавить реальные данные)
const stats = computed(() => ({
    projects: props.company?.projects_count || 0,
    members: props.company?.members_count || 0,
    tasks: props.company?.tasks_count || 0
}))

// Цветовая схема на основе названия компании
const companyColor = computed(() => {
    const colors = [
        'from-blue-600 to-indigo-600',
        'from-purple-600 to-pink-600',
        'from-emerald-600 to-teal-600',
        'from-amber-600 to-orange-600',
        'from-rose-600 to-red-600',
        'from-cyan-600 to-blue-600'
    ]
    const index = (props.company?.name?.length || 0) % colors.length
    return colors[index]
})
</script>

<template>
    <div class="relative overflow-hidden rounded-3xl shadow-2xl mb-8 group hover:shadow-3xl transition-all duration-500">
        <!-- Анимированный градиентный фон -->
        <div class="absolute inset-0 bg-gradient-to-br" :class="companyColor"></div>

        <!-- Шумовая текстура -->
        <div class="absolute inset-0 opacity-10 mix-blend-overlay"
             style="background-image: url('data:image/svg+xml,%3Csvg viewBox=\'0 0 200 200\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cfilter id=\'noise\'%3E%3CfeTurbulence type=\'fractalNoise\' baseFrequency=\'0.65\' numOctaves=\'3\' stitchTiles=\'stitch\'/%3E%3C/filter%3E%3Crect width=\'100%25\' height=\'100%25\' filter=\'url(%23noise)\' opacity=\'0.5\'/%3E%3C/svg%3E')">
        </div>

        <!-- Анимированные частицы -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute -top-1/2 -left-1/2 w-full h-full bg-white/5 rounded-full blur-3xl animate-pulse-slow"></div>
            <div class="absolute -bottom-1/2 -right-1/2 w-full h-full bg-black/5 rounded-full blur-3xl animate-pulse-slower"></div>
        </div>

        <!-- Декоративные элементы -->
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full blur-3xl transform translate-x-1/2 -translate-y-1/2"></div>
        <div class="absolute bottom-0 left-0 w-48 h-48 bg-black/10 rounded-full blur-3xl transform -translate-x-1/2 translate-y-1/2"></div>

        <!-- Основной контент -->
        <div class="relative px-8 py-12 sm:px-12 text-white">
            <!-- Верхний ряд с лого и тегами -->
            <div class="flex flex-col md:flex-row items-start gap-8">
                <!-- Логотип с улучшенным дизайном -->
                <div class="relative">
                    <div class="absolute inset-0 bg-white/30 rounded-2xl blur-xl opacity-50 group-hover:opacity-75 transition-opacity"></div>
                    <div class="relative p-1.5 bg-white/20 backdrop-blur-xl rounded-2xl border border-white/30 shadow-2xl">
                        <img v-if="company?.logo"
                             :src="`/storage/${company.logo}`"
                             alt="Logo"
                             class="w-24 h-24 rounded-xl object-cover bg-white/90"
                        />
                        <div v-else class="w-24 h-24 rounded-xl bg-gradient-to-br from-white/30 to-white/10 backdrop-blur-sm flex items-center justify-center text-4xl font-bold text-white/90">
                            {{ company?.name?.charAt(0) || '🏢' }}
                        </div>
                    </div>

                    <!-- Индикатор активности -->
                    <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-emerald-400 rounded-full border-4 border-white/30 animate-pulse"></div>
                </div>

                <!-- Информация о компании -->
                <div class="flex-1 space-y-4">
                    <div class="space-y-2">
                        <!-- Название и теги -->
                        <div class="flex flex-wrap items-center gap-3">
                            <h1 class="text-4xl sm:text-5xl font-black tracking-tight text-white drop-shadow-lg">
                                {{ company?.name || 'Без названия' }}
                            </h1>

                            <!-- Теги статуса -->
                            <div class="flex flex-wrap gap-2">
                                <span v-if="isOwner"
                                      class="px-3 py-1 rounded-full bg-amber-400/20 backdrop-blur-sm border border-amber-400/40 text-amber-100 text-xs font-bold uppercase tracking-wider">
                                    👑 Владелец
                                </span>
<!--                                <span v-else-if="isAdmin"-->
<!--                                      class="px-3 py-1 rounded-full bg-purple-400/20 backdrop-blur-sm border border-purple-400/40 text-purple-100 text-xs font-bold uppercase tracking-wider">-->
<!--                                    ⚡ Админ-->
<!--                                </span>-->
<!--                                <span class="px-3 py-1 rounded-full bg-white/20 backdrop-blur-sm border border-white/30 text-white/90 text-xs font-mono">-->
<!--                                    ID: {{ company?.id }}-->
<!--                                </span>-->
                            </div>
                        </div>

                        <!-- Описание компании (если есть) -->
                        <p v-if="company?.description"
                           class="text-white/80 text-sm max-w-2xl line-clamp-2">
                            {{ company.description }}
                        </p>
                    </div>

                    <!-- Статистика в современном стиле -->
                    <div class="flex flex-wrap gap-6 pt-2">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-white/20 backdrop-blur-sm rounded-xl">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </div>
                            <div>
                                <div class="text-2xl font-bold">{{ stats.projects }}</div>
                                <div class="text-xs text-white/70 uppercase tracking-wider">Проектов</div>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-white/20 backdrop-blur-sm rounded-xl">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                            <div>
                                <div class="text-2xl font-bold">{{ stats.members }}</div>
                                <div class="text-xs text-white/70 uppercase tracking-wider">Участников</div>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-white/20 backdrop-blur-sm rounded-xl">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                </svg>
                            </div>
                            <div>
                                <div class="text-2xl font-bold">{{ stats.tasks }}</div>
                                <div class="text-xs text-white/70 uppercase tracking-wider">Задач</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Нижний ряд с кнопками действий -->
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mt-8 pt-6 border-t border-white/20">
                <!-- Быстрые действия -->
                <div class="flex gap-2">
                    <button class="p-2 rounded-xl bg-white/10 hover:bg-white/20 backdrop-blur-sm border border-white/20 transition-all duration-300 group/btn">
                        <svg class="w-5 h-5 group-hover/btn:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                        </svg>
                    </button>
                    <button class="p-2 rounded-xl bg-white/10 hover:bg-white/20 backdrop-blur-sm border border-white/20 transition-all duration-300 group/btn">
                        <svg class="w-5 h-5 group-hover/btn:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </button>
                </div>

                <!-- Основные кнопки -->
                <div class="flex flex-wrap justify-center gap-3">
                    <button @click="$emit('openMembers')"
                            class="group/btn relative px-6 py-3 rounded-xl bg-white/10 hover:bg-white/20 backdrop-blur-xl border border-white/30 transition-all duration-300 overflow-hidden">
                        <!-- Эффект при наведении -->
                        <div class="absolute inset-0 bg-white/20 translate-y-full group-hover/btn:translate-y-0 transition-transform duration-300"></div>
                        <span class="relative flex items-center gap-2 text-sm font-semibold">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            Участники
                        </span>
                    </button>

                    <button v-if="canCreate"
                            @click="$emit('create')"
                            class="group/btn relative px-6 py-3 rounded-xl bg-white text-indigo-600 hover:text-indigo-700 shadow-2xl shadow-indigo-900/30 hover:shadow-indigo-900/40 transition-all duration-300 overflow-hidden">
                        <!-- Эффект свечения -->
                        <div class="absolute inset-0 bg-gradient-to-r from-indigo-400 to-purple-400 opacity-0 group-hover/btn:opacity-20 transition-opacity duration-300"></div>
                        <span class="relative flex items-center gap-2 text-sm font-bold">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Создать проект
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Кастомные анимации */
@keyframes pulse-slow {
    0%, 100% {
        opacity: 0.5;
        transform: scale(1);
    }
    50% {
        opacity: 0.8;
        transform: scale(1.05);
    }
}

@keyframes pulse-slower {
    0%, 100% {
        opacity: 0.3;
        transform: scale(1);
    }
    50% {
        opacity: 0.6;
        transform: scale(1.1);
    }
}

.animate-pulse-slow {
    animation: pulse-slow 8s ease-in-out infinite;
}

.animate-pulse-slower {
    animation: pulse-slower 12s ease-in-out infinite;
}

/* Эффект стекла для логотипа */
.backdrop-blur-xl {
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
}

/* Ограничение текста */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Анимация появления */
.group {
    animation: cardAppear 0.6s cubic-bezier(0.23, 1, 0.32, 1) forwards;
    opacity: 0;
    transform: translateY(20px);
}

@keyframes cardAppear {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Hover эффекты для кнопок */
.group\/btn {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.group\/btn:hover {
    transform: translateY(-2px);
}

/* Адаптивность для мобильных */
@media (max-width: 640px) {
    .group {
        animation: none;
        opacity: 1;
        transform: none;
    }
}
</style>
