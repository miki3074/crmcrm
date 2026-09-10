<script setup>
const props = defineProps({
    show: Boolean,
    title: { type: String, default: 'Подтвердите действие' },
    message: { type: String, default: '' },
    confirmText: { type: String, default: 'Удалить' },
    cancelText: { type: String, default: 'Отмена' },
    danger: { type: Boolean, default: true },
})

const emit = defineEmits(['confirm', 'close'])

const vAutofocus = {
    mounted: el => el.focus(),
}
</script>

<template>
    <Transition name="fade">
        <div v-if="show" class="fixed inset-0 z-[120] flex items-center justify-center bg-zinc-950/60 p-4 backdrop-blur-sm" @click.self="emit('close')">
            <div
                v-autofocus
                role="dialog"
                aria-modal="true"
                :aria-label="title"
                tabindex="-1"
                class="w-full max-w-sm rounded-2xl border border-zinc-100 bg-white p-5 shadow-xl dark:border-zinc-800 dark:bg-zinc-900"
                @keydown.esc="emit('close')"
            >
                <h3 class="text-base font-bold text-zinc-900 dark:text-white" :class="danger ? 'text-rose-600 dark:text-rose-400' : ''">
                    {{ title }}
                </h3>
                <p v-if="message" class="mt-1.5 text-sm text-zinc-500 dark:text-zinc-400">{{ message }}</p>

                <div class="mt-5 flex justify-end gap-2">
                    <button
                        type="button"
                        class="rounded-xl px-4 py-2 text-sm font-semibold text-zinc-600 transition hover:bg-zinc-100
                               focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400 dark:text-zinc-300 dark:hover:bg-zinc-800"
                        @click="emit('close')"
                    >
                        {{ cancelText }}
                    </button>
                    <button
                        type="button"
                        class="rounded-xl px-4 py-2 text-sm font-semibold text-white transition
                               focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                        :class="danger ? 'bg-rose-600 hover:bg-rose-700 focus-visible:ring-rose-400' : 'bg-cyan-600 hover:bg-cyan-700 focus-visible:ring-cyan-400'"
                        @click="emit('confirm')"
                    >
                        {{ confirmText }}
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
