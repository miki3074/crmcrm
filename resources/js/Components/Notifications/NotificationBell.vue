<script setup>
import { useNotifications } from '@/Composables/useNotifications'

const { state, open } = useNotifications()
</script>

<template>
    <button
        type="button"
        class="relative flex h-9 w-9 items-center justify-center rounded-md text-zinc-500 transition-colors hover:bg-zinc-100 hover:text-zinc-900
               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60
               dark:text-zinc-400 dark:hover:bg-white/[0.06] dark:hover:text-zinc-100"
        :aria-label="state.unreadCount ? `Уведомления, непрочитанных: ${state.unreadCount}` : 'Уведомления'"
        title="Уведомления"
        @click="open"
    >
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>

        <span
            v-if="state.unreadCount > 0"
            class="absolute -right-0.5 -top-0.5 min-w-[18px] rounded-full bg-blue-600 px-1 py-0.5 text-center text-[10px] font-semibold leading-none tabular-nums text-white ring-2 ring-white dark:bg-blue-500 dark:ring-gray-800"
        >
            {{ state.unreadCount > 99 ? '99+' : state.unreadCount }}
        </span>
    </button>
</template>
