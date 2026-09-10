<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
    show: Boolean,
    // Файлы, загруженные текущим пользователем в этой задаче
    myFiles: { type: Array, default: () => [] },
    loading: Boolean,
    error: String,
})

const emit = defineEmits(['submit', 'close'])

const text = ref('')
const fileId = ref(null)

watch(() => props.show, val => {
    if (val) {
        text.value = ''
        fileId.value = null
    }
})

const canSubmit = computed(() => text.value.trim().length > 0)

const onSubmit = () => {
    if (!canSubmit.value) return
    emit('submit', { text: text.value.trim(), file_id: fileId.value })
}

const vAutofocus = {
    mounted: el => el.focus(),
}
</script>

<template>
    <Transition name="fade">
        <div v-if="show" class="fixed inset-0 z-[120] flex items-center justify-center bg-zinc-950/60 p-4 backdrop-blur-sm" @click.self="emit('close')">
            <div
                role="dialog"
                aria-modal="true"
                aria-label="Добавить результат выполнения"
                tabindex="-1"
                class="w-full max-w-lg rounded-2xl border border-zinc-100 bg-white p-5 shadow-xl dark:border-zinc-800 dark:bg-zinc-900"
                @keydown.esc="emit('close')"
            >
                <h3 class="text-base font-bold text-zinc-900 dark:text-white">Добавить результат</h3>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Опишите, что было сделано по задаче.</p>

                <p v-if="error" class="mt-3 rounded-lg bg-rose-50 px-3 py-2 text-sm font-medium text-rose-600 dark:bg-rose-950/40 dark:text-rose-400">
                    {{ error }}
                </p>

                <form class="mt-4 space-y-4" @submit.prevent="onSubmit">
                    <div>
                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-zinc-500">Результат</label>
                        <textarea
                            v-autofocus
                            v-model="text"
                            rows="5"
                            required
                            maxlength="5000"
                            placeholder="Что было сделано, к чему пришли, ссылки, выводы…"
                            class="w-full rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-900 outline-none transition
                                   focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100
                                   dark:border-zinc-700 dark:bg-zinc-800 dark:text-white dark:focus:ring-0"
                        ></textarea>
                    </div>

                    <div v-if="myFiles.length">
                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-zinc-500">
                            Приложить свой файл <span class="font-normal normal-case text-zinc-400">(необязательно)</span>
                        </label>
                        <select
                            v-model="fileId"
                            class="w-full rounded-xl border border-zinc-200 bg-white px-4 py-2.5 text-sm text-zinc-900 outline-none transition
                                   focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100
                                   dark:border-zinc-700 dark:bg-zinc-800 dark:text-white dark:focus:ring-0"
                        >
                            <option :value="null">Без файла</option>
                            <option v-for="f in myFiles" :key="f.id" :value="f.id">{{ f.file_name }}</option>
                        </select>
                    </div>
                    <p v-else class="text-xs text-zinc-400">
                        Вы пока не загружали файлы в эту задачу — можно добавить результат только текстом.
                    </p>

                    <div class="flex justify-end gap-2 pt-1">
                        <button
                            type="button"
                            class="rounded-xl px-4 py-2 text-sm font-semibold text-zinc-600 transition hover:bg-zinc-100
                                   focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400 dark:text-zinc-300 dark:hover:bg-zinc-800"
                            @click="emit('close')"
                        >
                            Отмена
                        </button>
                        <button
                            type="submit"
                            :disabled="!canSubmit || loading"
                            class="rounded-xl bg-cyan-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-cyan-700 disabled:cursor-not-allowed disabled:opacity-60
                                   focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400 focus-visible:ring-offset-2"
                        >
                            {{ loading ? 'Сохранение…' : 'Добавить результат' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
