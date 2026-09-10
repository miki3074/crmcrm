<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
    results: { type: Array, default: () => [] },
    canAdd: Boolean,
})

defineEmits(['add', 'openFile'])

const LIMIT = 3
const showAll = ref(false)

const visibleResults = computed(() => {
    return showAll.value ? props.results : props.results.slice(0, LIMIT)
})

const hiddenCount = computed(() => {
    return Math.max(props.results.length - LIMIT, 0)
})

const formatDate = value => {
    if (!value) return ''
    const date = new Date(value)
    if (Number.isNaN(date.getTime())) return value
    return new Intl.DateTimeFormat('ru-RU', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date)
}
</script>

<template>
    <div class="bg-white dark:bg-zinc-900 rounded-2xl p-4 shadow-sm border border-zinc-100 dark:border-zinc-800">
        <div class="mb-3 flex items-center justify-between">
            <h3 class="font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Результаты
                <span class="text-xs text-zinc-400 font-normal bg-zinc-100 dark:bg-zinc-800 px-2 py-0.5 rounded-full">{{ results.length }}</span>
            </h3>

            <button
                v-if="canAdd"
                type="button"
                class="group flex items-center gap-1 text-xs bg-cyan-50 text-cyan-600 px-3 py-1.5 rounded-lg font-bold transition hover:bg-cyan-100
                       focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400 dark:bg-cyan-500/10 dark:text-cyan-300"
                @click="$emit('add')"
            >
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m7-7H5" />
                </svg>
                Добавить результат
            </button>
        </div>

        <!-- Пустое состояние -->
        <div v-if="!results.length" class="flex flex-col items-center justify-center py-8 text-zinc-400 border-2 border-dashed border-zinc-100 dark:border-zinc-800 rounded-xl">
            <svg class="w-10 h-10 mb-2 opacity-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-sm">Результатов пока нет</span>
        </div>

        <div v-else class="space-y-3">
            <article
                v-for="result in visibleResults"
                :key="result.id"
                class="rounded-xl border border-zinc-100 bg-zinc-50/60 p-3.5 dark:border-zinc-800 dark:bg-zinc-800/40"
            >
                <p class="text-sm text-zinc-700 dark:text-zinc-300">
                    <span class="font-bold text-zinc-900 dark:text-white">{{ result.user?.name || 'Пользователь' }}</span>
                    добавил результат задачи от
                    <span class="font-semibold">{{ formatDate(result.created_at) }}</span>
                </p>

                <p class="mt-2 whitespace-pre-line text-sm text-zinc-600 dark:text-zinc-300">{{ result.text }}</p>

                <button
                    v-if="result.file"
                    type="button"
                    class="mt-3 inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-cyan-600 transition hover:border-cyan-300 hover:bg-cyan-50
                           focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-cyan-400 dark:hover:bg-cyan-500/10"
                    @click="$emit('openFile', result.file)"
                >
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828L18 9.828a4 4 0 00-5.656-5.656L5.757 10.76a6 6 0 108.486 8.486L20.5 13" />
                    </svg>
                    {{ result.file.file_name }}
                </button>
            </article>

            <div v-if="hiddenCount > 0 || showAll" class="flex justify-center pt-1">
                <button
                    type="button"
                    class="flex items-center gap-1 rounded-lg px-4 py-2 text-xs font-medium text-zinc-500 transition hover:bg-zinc-50 hover:text-cyan-600
                           focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400 dark:hover:bg-zinc-800"
                    @click="showAll = !showAll"
                >
                    <span v-if="!showAll">Ещё {{ hiddenCount }}</span>
                    <span v-else>Свернуть</span>
                    <svg :class="{ 'rotate-180': showAll }" class="h-3 w-3 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>
