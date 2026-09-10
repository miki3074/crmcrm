<script setup>
import { useToast } from '@/Composables/useToast'

const { toasts, remove } = useToast()

const styles = {
    success: 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/40 dark:text-emerald-300',
    error: 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-300',
    info: 'border-cyan-200 bg-cyan-50 text-cyan-700 dark:border-cyan-900/50 dark:bg-cyan-950/40 dark:text-cyan-300',
}

const icons = {
    success: 'M5 13l4 4L19 7',
    error: 'M6 18L18 6M6 6l12 12',
    info: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
}
</script>

<template>
    <Teleport to="body">
        <div
            class="pointer-events-none fixed inset-x-0 bottom-4 z-[300] flex flex-col items-center gap-2 px-4 sm:inset-x-auto sm:right-4 sm:items-end"
            aria-live="polite"
        >
            <TransitionGroup name="toast">
                <div
                    v-for="t in toasts"
                    :key="t.id"
                    role="status"
                    class="pointer-events-auto flex w-full max-w-sm items-start gap-2 rounded-xl border px-4 py-3 text-sm font-medium shadow-lg backdrop-blur"
                    :class="styles[t.type] || styles.info"
                >
                    <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="icons[t.type] || icons.info" />
                    </svg>
                    <span class="flex-1">{{ t.message }}</span>
                    <button
                        type="button"
                        class="shrink-0 rounded opacity-60 transition hover:opacity-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-current"
                        aria-label="Закрыть уведомление"
                        @click="remove(t.id)"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<style scoped>
.toast-enter-active, .toast-leave-active { transition: all 0.25s ease; }
.toast-enter-from { opacity: 0; transform: translateY(8px); }
.toast-leave-to { opacity: 0; transform: translateY(8px) scale(0.95); }
</style>
