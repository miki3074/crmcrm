<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
    show: Boolean,
    participants: { type: Array, default: () => [] },
    fileName: { type: String, default: '' },
    loading: Boolean,
    error: String,
})

const emit = defineEmits(['confirm', 'close'])

const selectedId = ref(null)

watch(() => props.show, val => {
    if (val) selectedId.value = null
})

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
                aria-label="Выбор согласующего"
                tabindex="-1"
                class="w-full max-w-sm rounded-2xl border border-zinc-100 bg-white p-5 shadow-xl dark:border-zinc-800 dark:bg-zinc-900"
                @keydown.esc="emit('close')"
            >
                <h3 class="text-base font-bold text-zinc-900 dark:text-white">Отправить на согласование</h3>
                <p v-if="fileName" class="mt-1 truncate text-sm text-zinc-500 dark:text-zinc-400">{{ fileName }}</p>

                <p v-if="error" class="mt-3 rounded-lg bg-rose-50 px-3 py-2 text-sm font-medium text-rose-600 dark:bg-rose-950/40 dark:text-rose-400">
                    {{ error }}
                </p>

                <div class="mt-4 max-h-64 space-y-1.5 overflow-y-auto">
                    <label
                        v-for="p in participants"
                        :key="p.id"
                        class="flex cursor-pointer items-center gap-3 rounded-xl border px-3 py-2.5 transition"
                        :class="selectedId === p.id
                            ? 'border-cyan-300 bg-cyan-50 dark:border-cyan-700 dark:bg-cyan-950/30'
                            : 'border-zinc-100 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/60'"
                    >
                        <input type="radio" name="reviewer" :value="p.id" v-model="selectedId" class="text-cyan-600 focus:ring-cyan-400" />
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold text-zinc-800 dark:text-zinc-100">{{ p.name }}</span>
                            <span class="block truncate text-[11px] text-zinc-400">{{ p.roles.join(', ') }}</span>
                        </span>
                    </label>

                    <p v-if="!participants.length" class="py-6 text-center text-sm text-zinc-400">
                        В задаче пока нет участников, которым можно назначить согласование.
                    </p>
                </div>

                <div class="mt-5 flex justify-end gap-2">
                    <button
                        type="button"
                        class="rounded-xl px-4 py-2 text-sm font-semibold text-zinc-600 transition hover:bg-zinc-100
                               focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400 dark:text-zinc-300 dark:hover:bg-zinc-800"
                        @click="emit('close')"
                    >
                        Отмена
                    </button>
                    <button
                        type="button"
                        :disabled="!selectedId || loading"
                        class="rounded-xl bg-cyan-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-cyan-700 disabled:cursor-not-allowed disabled:opacity-60
                               focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400 focus-visible:ring-offset-2"
                        @click="emit('confirm', selectedId)"
                    >
                        {{ loading ? 'Отправка…' : 'Отправить' }}
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
