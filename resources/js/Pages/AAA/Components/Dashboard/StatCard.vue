<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        default: '',
    },
    icon: {
        type: String,
        default: 'folder',
    },
    // Оставлен для совместимости вызовов: карточки теперь монохромные,
    // акцентом выделяется только действие «Создать» (icon="plus").
    color: {
        type: String,
        default: 'blue',
    },
    // Счётчик справа вместо стрелки (например, непрочитанные уведомления)
    badge: {
        type: Number,
        default: 0,
    },
})

const neutralTile = 'bg-zinc-100 text-zinc-600 dark:bg-white/[0.06] dark:text-zinc-300'
const accentTile = 'bg-blue-600 text-white dark:bg-blue-500'
</script>

<template>
    <button
        type="button"
        class="group relative flex min-h-[56px] min-w-0 items-center gap-3
               rounded-lg border border-zinc-200 bg-white
               px-3 py-2.5 text-left shadow-sm transition-colors
               hover:border-zinc-300 hover:bg-zinc-50
               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-50
               dark:border-white/10 dark:bg-zinc-900 dark:shadow-none
               dark:hover:border-white/20 dark:hover:bg-zinc-800/60 dark:focus-visible:ring-offset-[#0a0b0f]"
    >
        <span
            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md"
            :class="icon === 'plus' ? accentTile : neutralTile"
        >
            <svg v-if="icon === 'calendar'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 7V3m8 4V3M5 11h14M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z" />
            </svg>

            <svg v-else-if="icon === 'folder'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 7a2 2 0 012-2h5l2 2h7a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z" />
            </svg>

            <svg v-else-if="icon === 'users'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 20h5v-2a4 4 0 00-5-4M9 20H2v-2a4 4 0 014-4h3m4-4a4 4 0 100-8 4 4 0 000 8zm6 0a3 3 0 100-6" />
            </svg>

            <svg v-else-if="icon === 'clients'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8.5 14.5A5.5 5.5 0 1114 9a5.5 5.5 0 01-5.5 5.5zM20 21a8 8 0 00-16 0" />
            </svg>

            <svg v-else-if="icon === 'map'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 18l-6 3V6l6-3 6 3 6-3v15l-6 3-6-3zm0 0V3m6 18V6" />
            </svg>

            <svg v-else-if="icon === 'bell'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>

            <svg v-else-if="icon === 'plus'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m7-7H5" />
            </svg>

            <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
        </span>

        <span class="min-w-0">
            <span class="block truncate text-sm font-medium text-zinc-800 dark:text-zinc-100">
                {{ title }}
            </span>

            <span v-if="subtitle" class="mt-0.5 block truncate text-xs text-zinc-500 dark:text-zinc-400">
                {{ subtitle }}
            </span>
        </span>

        <span
            v-if="badge > 0"
            class="ml-auto shrink-0 rounded-full bg-blue-600 px-1.5 py-0.5 text-[11px] font-semibold leading-none tabular-nums text-white dark:bg-blue-500"
            :aria-label="`Непрочитанных: ${badge}`"
        >
            {{ badge > 99 ? '99+' : badge }}
        </span>

        <svg
            v-else
            class="ml-auto hidden h-4 w-4 shrink-0 text-zinc-300 transition-colors
                   group-hover:text-zinc-500
                   dark:text-zinc-600 dark:group-hover:text-zinc-400 xl:block"
            fill="none" viewBox="0 0 24 24" stroke="currentColor"
        >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="m9 18 6-6-6-6" />
        </svg>
    </button>
</template>
