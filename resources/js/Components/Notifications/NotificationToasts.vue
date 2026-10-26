<script setup>
import { useNotifications } from '@/Composables/useNotifications'

const { state, openToast, dismissToast, holdToast, releaseToast } = useNotifications()

const CATEGORY_LABELS = {
    task: 'Задача',
    subtask: 'Подзадача',
    chat: 'Чат',
    meeting: 'Совещание',
    klient: 'Клиенты',
    system: 'Система',
}
</script>

<template>
    <!-- Над кнопкой поддержки (она в bottom-6 right-6) -->
    <div
        class="pointer-events-none fixed inset-x-4 bottom-24 z-[9990] flex flex-col items-end gap-2 sm:inset-x-auto sm:right-6"
        aria-live="polite"
    >
        <TransitionGroup
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="translate-y-2 opacity-0"
            leave-active-class="transition duration-150 ease-in"
            leave-to-class="opacity-0"
        >
            <div
                v-for="item in state.toasts"
                :key="item.id"
                role="button"
                tabindex="0"
                class="pointer-events-auto group flex w-full cursor-pointer items-start gap-3 rounded-lg border border-zinc-200 bg-white p-3 shadow-lg transition-colors
                       hover:border-zinc-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60
                       dark:border-white/10 dark:bg-zinc-900 dark:hover:border-white/20 sm:w-[360px]"
                @click="openToast(item)"
                @keydown.enter="openToast(item)"
                @mouseenter="holdToast(item.id)"
                @mouseleave="releaseToast(item.id)"
            >
                <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-blue-600 dark:bg-blue-400" aria-hidden="true" />

                <div class="min-w-0 flex-1">
                    <p class="text-[11px] font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        {{ CATEGORY_LABELS[item.category] || 'Уведомление' }}<template v-if="item.actor"> · {{ item.actor.name }}</template>
                    </p>
                    <p class="mt-0.5 truncate text-sm font-semibold text-zinc-900 dark:text-zinc-50">
                        {{ item.title || CATEGORY_LABELS[item.category] }}
                    </p>
                    <p class="mt-0.5 line-clamp-2 text-sm text-zinc-600 dark:text-zinc-400">
                        {{ item.message }}
                    </p>
                </div>

                <button
                    type="button"
                    aria-label="Скрыть"
                    class="shrink-0 rounded p-1 text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-700
                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60
                           dark:text-zinc-500 dark:hover:bg-white/[0.06] dark:hover:text-zinc-200"
                    @click.stop="dismissToast(item.id)"
                >
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>
