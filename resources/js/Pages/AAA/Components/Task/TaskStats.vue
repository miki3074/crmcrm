<script setup>
import { computed, ref, watch } from 'vue'

const props = defineProps({
    task: {
        type: Object,
        default: () => ({})
    },
    saving: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits([
    'updateProgress'
])

// Короткая вспышка после успешного сохранения прогресса — заметная
// обратная связь без перерисовки всего блока.
const justSaved = ref(false)
watch(() => props.saving, (isSaving, wasSaving) => {
    if (wasSaving && !isSaving) {
        justSaved.value = true
        setTimeout(() => { justSaved.value = false }, 700)
    }
})

/*
|--------------------------------------------------------------------------
| Прогресс
|--------------------------------------------------------------------------
*/

const progress = computed(() => {
    const value = Number(props.task?.progress ?? 0)

    return Math.min(100, Math.max(0, value))
})

const progressColor = computed(() => {
    if (progress.value < 30) {
        return 'bg-zinc-400'
    }

    if (progress.value < 70) {
        return 'bg-cyan-500'
    }

    return 'bg-emerald-500'
})

const progressTextColor = computed(() => {
    if (progress.value < 30) {
        return 'text-zinc-600 dark:text-zinc-300'
    }

    if (progress.value < 70) {
        return 'text-cyan-600 dark:text-cyan-400'
    }

    return 'text-emerald-600 dark:text-emerald-400'
})

const progressStatus = computed(() => {
    if (progress.value === 100) {
        return 'Завершено'
    }

    if (progress.value >= 70) {
        return 'Почти готово'
    }

    if (progress.value >= 30) {
        return 'В работе'
    }

    if (progress.value > 0) {
        return 'Начато'
    }

    return 'Не начато'
})

const isOverdue = computed(() => {
    if (!props.task?.due_date || props.task?.completed) {
        return false
    }

    const dueDate = new Date(props.task.due_date)

    if (Number.isNaN(dueDate.getTime())) {
        return false
    }

    dueDate.setHours(23, 59, 59, 999)

    return dueDate < new Date()
})

/*
|--------------------------------------------------------------------------
| Даты
|--------------------------------------------------------------------------
*/

const formatDate = (isoString) => {
    if (!isoString) {
        return 'Не указано'
    }

    const date = new Date(isoString)

    if (Number.isNaN(date.getTime())) {
        return isoString
    }

    return new Intl.DateTimeFormat('ru-RU', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    }).format(date)
}
</script>

<template>
    <section
        class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800"
    >
        <div
            class="grid grid-cols-1 divide-y divide-zinc-100 lg:grid-cols-[minmax(0,1fr)_minmax(280px,0.65fr)] lg:divide-x lg:divide-y-0 dark:divide-zinc-700"
        >
            <!-- Временная шкала -->
            <div class="p-5 sm:p-4">
                <div
                    class="mb-3 flex items-center justify-between gap-4"
                >
                    <div
                        class="flex min-w-0 items-center gap-3"
                    >
                        <div
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-cyan-50 text-cyan-600 dark:bg-cyan-500/10 dark:text-cyan-400"
                        >
                            <svg
                                class="h-4 w-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                />
                            </svg>
                        </div>

                        <div class="min-w-0">
                            <h3
                                class="font-semibold text-zinc-900 dark:text-white"
                            >
                                Сроки выполнения
                            </h3>

                            <p
                                class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400"
                            >
                                Период работы над задачей
                            </p>
                        </div>
                    </div>

                    <span
                        v-if="isOverdue"
                        class="shrink-0 rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-600 dark:bg-rose-500/10 dark:text-rose-400"
                    >
                        Просрочено
                    </span>
                </div>

                <div
                    class="relative grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-3 rounded-xl border border-zinc-100 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900/40"
                >
                    <div class="min-w-0">
                        <span
                            class="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-zinc-400"
                        >
                            Начало
                        </span>

                        <span
                            class="block truncate text-sm font-semibold text-zinc-700 dark:text-zinc-200 sm:text-base"
                            :title="formatDate(task?.start_date)"
                        >
                            {{ formatDate(task?.start_date) }}
                        </span>
                    </div>

                    <div
                        class="flex min-w-10 items-center text-zinc-300 dark:text-zinc-600"
                    >
                        <span
                            class="h-px w-3 bg-current sm:w-6"
                        ></span>

                        <svg
                            class="h-4 w-4 shrink-0"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                    </div>

                    <div class="min-w-0 text-right">
                        <span
                            class="mb-1 block text-[11px] font-semibold uppercase tracking-wider"
                            :class="
                                isOverdue
                                    ? 'text-rose-500'
                                    : 'text-zinc-400'
                            "
                        >
                            Срок
                        </span>

                        <span
                            class="block truncate text-sm font-semibold sm:text-base"
                            :class="
                                isOverdue
                                    ? 'text-rose-600 dark:text-rose-400'
                                    : 'text-zinc-900 dark:text-white'
                            "
                            :title="formatDate(task?.due_date)"
                        >
                            {{ formatDate(task?.due_date) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Прогресс -->
            <div class="p-5 sm:p-4">
                <div
                    class="mb-4 flex items-start justify-between gap-4"
                >
                    <div>
                        <h3
                            class="font-semibold text-zinc-900 dark:text-white"
                        >
                            Прогресс
                        </h3>

                        <p
                            class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400"
                        >
                            {{ progressStatus }}
                        </p>
                    </div>

                    <span
                        class="flex items-center gap-2 text-2xl font-black tracking-tight transition-all duration-300"
                        :class="[progressTextColor, justSaved ? 'scale-110' : '']"
                    >
                        <svg v-if="saving" class="h-4 w-4 animate-spin text-current opacity-60" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg v-else-if="justSaved" class="h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                        {{ progress }}%
                    </span>
                </div>

                <div
                    class="mb-4 h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-700"
                >
                    <div
                        class="h-full rounded-full transition-all duration-500"
                        :class="progressColor"
                        :style="{ width: `${progress}%` }"
                    ></div>
                </div>

                <div class="grid grid-cols-11 gap-1" role="group" aria-label="Быстрая установка прогресса">
                    <button
                        v-for="number in 11"
                        :key="number"
                        type="button"
                        :disabled="saving"
                        class="group relative h-9 rounded-md transition duration-200 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 dark:focus:ring-offset-zinc-800"
                        :class="[
                            progress >= (number - 1) * 10
                                ? progressColor
                                : 'bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-700 dark:hover:bg-zinc-600',
                            progress === (number - 1) * 10
                                ? 'scale-105 ring-2 ring-cyan-400 ring-offset-1 dark:ring-offset-zinc-800'
                                : ''
                        ]"
                        :aria-label="`Установить прогресс ${(number - 1) * 10}%`"
                        :title="`Установить ${(number - 1) * 10}%`"
                        @click="
                            emit(
                                'updateProgress',
                                (number - 1) * 10
                            )
                        "
                    >
                        <span
                            class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-2 hidden -translate-x-1/2 whitespace-nowrap rounded-md bg-zinc-900 px-2 py-1 text-[10px] font-medium text-white shadow-lg group-hover:block"
                        >
                            {{ (number - 1) * 10 }}%
                        </span>
                    </button>
                </div>

                <div
                    class="mt-2 flex justify-between text-[11px] font-medium text-zinc-400"
                >
                    <span>0%</span>
                    <span>50%</span>
                    <span>100%</span>
                </div>
            </div>
        </div>
    </section>
</template>
