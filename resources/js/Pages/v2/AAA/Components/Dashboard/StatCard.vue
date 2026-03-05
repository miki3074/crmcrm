<!-- Partials/StatCard.vue -->
<script setup>
import { computed } from 'vue'

const props = defineProps({
    title: {
        type: String,
        required: true
    },
    icon: {
        type: String,
        required: true
    },
    color: {
        type: String,
        default: 'indigo',
        validator: (value) => ['violet', 'blue', 'indigo', 'amber', 'emerald', 'rose', 'purple', 'cyan'].includes(value)
    },
    count: {
        type: [Number, String],
        default: null
    },
    description: {
        type: String,
        default: null
    },
    action: {
        type: Function,
        default: null
    }
})

const colorClasses = computed(() => {
    const colors = {
        violet: {
            light: 'from-violet-500 to-purple-500',
            medium: 'bg-violet-50 dark:bg-violet-950/30',
            border: 'border-violet-200 dark:border-violet-800',
            text: 'text-violet-600 dark:text-violet-400',
            shadow: 'shadow-violet-200/50 dark:shadow-violet-900/20'
        },
        blue: {
            light: 'from-blue-500 to-cyan-500',
            medium: 'bg-blue-50 dark:bg-blue-950/30',
            border: 'border-blue-200 dark:border-blue-800',
            text: 'text-blue-600 dark:text-blue-400',
            shadow: 'shadow-blue-200/50 dark:shadow-blue-900/20'
        },
        indigo: {
            light: 'from-indigo-500 to-indigo-600',
            medium: 'bg-indigo-50 dark:bg-indigo-950/30',
            border: 'border-indigo-200 dark:border-indigo-800',
            text: 'text-indigo-600 dark:text-indigo-400',
            shadow: 'shadow-indigo-200/50 dark:shadow-indigo-900/20'
        },
        amber: {
            light: 'from-amber-500 to-orange-500',
            medium: 'bg-amber-50 dark:bg-amber-950/30',
            border: 'border-amber-200 dark:border-amber-800',
            text: 'text-amber-600 dark:text-amber-400',
            shadow: 'shadow-amber-200/50 dark:shadow-amber-900/20'
        },
        emerald: {
            light: 'from-emerald-500 to-teal-500',
            medium: 'bg-emerald-50 dark:bg-emerald-950/30',
            border: 'border-emerald-200 dark:border-emerald-800',
            text: 'text-emerald-600 dark:text-emerald-400',
            shadow: 'shadow-emerald-200/50 dark:shadow-emerald-900/20'
        },
        rose: {
            light: 'from-rose-500 to-pink-500',
            medium: 'bg-rose-50 dark:bg-rose-950/30',
            border: 'border-rose-200 dark:border-rose-800',
            text: 'text-rose-600 dark:text-rose-400',
            shadow: 'shadow-rose-200/50 dark:shadow-rose-900/20'
        },
        purple: {
            light: 'from-purple-500 to-pink-500',
            medium: 'bg-purple-50 dark:bg-purple-950/30',
            border: 'border-purple-200 dark:border-purple-800',
            text: 'text-purple-600 dark:text-purple-400',
            shadow: 'shadow-purple-200/50 dark:shadow-purple-900/20'
        },
        cyan: {
            light: 'from-cyan-500 to-blue-500',
            medium: 'bg-cyan-50 dark:bg-cyan-950/30',
            border: 'border-cyan-200 dark:border-cyan-800',
            text: 'text-cyan-600 dark:text-cyan-400',
            shadow: 'shadow-cyan-200/50 dark:shadow-cyan-900/20'
        }
    }
    return colors[props.color] || colors.indigo
})
</script>

<template>
    <button
        @click="action"
        class="group relative overflow-hidden p-5 bg-white dark:bg-slate-900 rounded-2xl transition-all duration-500 hover:scale-105 hover:-translate-y-1 active:scale-95"
        :class="[
            `border ${colorClasses.border} shadow-lg ${colorClasses.shadow}`,
            'hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-slate-900'
        ]"
    >
        <!-- Градиентный фон при наведении -->
        <div
            class="absolute inset-0 opacity-0 group-hover:opacity-10 transition-opacity duration-700"
            :class="`bg-gradient-to-br ${colorClasses.light}`"
        ></div>

        <!-- Анимированные круги -->
        <div class="absolute -right-8 -top-8 w-24 h-24 rounded-full opacity-20 group-hover:scale-[2] transition-all duration-700"
             :class="`bg-gradient-to-br ${colorClasses.light}`">
        </div>
        <div class="absolute -left-8 -bottom-8 w-16 h-16 rounded-full opacity-10 group-hover:scale-[2] transition-all duration-700 delay-100"
             :class="`bg-gradient-to-br ${colorClasses.light}`">
        </div>

        <!-- Иконка с градиентным фоном -->
        <div class="relative z-10 mb-4">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-2xl transform group-hover:scale-110 group-hover:rotate-3 transition-all duration-300"
                 :class="[colorClasses.medium, colorClasses.text]">
                {{ icon }}
            </div>

            <!-- Счетчик если есть -->
            <span v-if="count"
                  class="absolute -top-2 -right-2 min-w-[1.5rem] h-5 px-1.5 rounded-full text-xs font-bold flex items-center justify-center bg-white dark:bg-slate-800 border shadow-sm"
                  :class="[colorClasses.border, colorClasses.text]">
                {{ count }}
            </span>
        </div>

        <!-- Контент -->
        <div class="relative z-10 space-y-1">
            <h3 class="font-semibold text-slate-800 dark:text-slate-200 text-base tracking-tight">
                {{ title }}
            </h3>

            <!-- Описание если есть -->
            <p v-if="description" class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2">
                {{ description }}
            </p>
        </div>

        <!-- Индикатор кликабельности -->
        <div class="absolute bottom-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
            <svg class="w-4 h-4 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </div>
    </button>
</template>

<style scoped>
/* Плавные переходы для всех анимаций */
button {
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

/* Эффект пульсации при наведении */
@keyframes pulse-glow {
    0%, 100% {
        opacity: 0.5;
    }
    50% {
        opacity: 0.8;
    }
}

.group:hover .absolute.rounded-full {
    animation: pulse-glow 2s ease-in-out infinite;
}

/* Ограничение текста */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
