<script setup>
import { computed } from 'vue'

const props = defineProps({
    task: {
        type: Object,
        default: () => ({})
    }
})

const emit = defineEmits([
    'updateProgress'
])

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
        return 'bg-slate-400'
    }

    if (progress.value < 70) {
        return 'bg-blue-500'
    }

    return 'bg-emerald-500'
})

const progressTextColor = computed(() => {
    if (progress.value < 30) {
        return 'text-slate-600 dark:text-slate-300'
    }

    if (progress.value < 70) {
        return 'text-blue-600 dark:text-blue-400'
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
        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800"
    >
        <div
            class="grid grid-cols-1 divide-y divide-slate-100 lg:grid-cols-[minmax(0,1fr)_minmax(280px,0.65fr)] lg:divide-x lg:divide-y-0 dark:divide-slate-700"
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
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400"
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
                                class="font-semibold text-slate-900 dark:text-white"
                            >
                                Сроки выполнения
                            </h3>

                            <p
                                class="mt-0.5 text-xs text-slate-500 dark:text-slate-400"
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
                    class="relative grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-3 rounded-xl border border-slate-100 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/40"
                >
                    <div class="min-w-0">
                        <span
                            class="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-slate-400"
                        >
                            Начало
                        </span>

                        <span
                            class="block truncate text-sm font-semibold text-slate-700 dark:text-slate-200 sm:text-base"
                            :title="formatDate(task?.start_date)"
                        >
                            {{ formatDate(task?.start_date) }}
                        </span>
                    </div>

                    <div
                        class="flex min-w-10 items-center text-slate-300 dark:text-slate-600"
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
                                    : 'text-slate-400'
                            "
                        >
                            Срок
                        </span>

                        <span
                            class="block truncate text-sm font-semibold sm:text-base"
                            :class="
                                isOverdue
                                    ? 'text-rose-600 dark:text-rose-400'
                                    : 'text-slate-900 dark:text-white'
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
                            class="font-semibold text-slate-900 dark:text-white"
                        >
                            Прогресс
                        </h3>

                        <p
                            class="mt-0.5 text-xs text-slate-500 dark:text-slate-400"
                        >
                            {{ progressStatus }}
                        </p>
                    </div>

                    <span
                        class="text-2xl font-black tracking-tight"
                        :class="progressTextColor"
                    >
                        {{ progress }}%
                    </span>
                </div>

                <div
                    class="mb-4 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-700"
                >
                    <div
                        class="h-full rounded-full transition-all duration-500"
                        :class="progressColor"
                        :style="{ width: `${progress}%` }"
                    ></div>
                </div>

                <div class="grid grid-cols-11 gap-1">
                    <button
                        v-for="number in 11"
                        :key="number"
                        type="button"
                        class="group relative h-6 rounded-md transition duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-slate-800"
                        :class="[
                            progress >= (number - 1) * 10
                                ? progressColor
                                : 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600',
                            progress === (number - 1) * 10
                                ? 'scale-105 ring-2 ring-blue-400 ring-offset-1 dark:ring-offset-slate-800'
                                : ''
                        ]"
                        :title="`Установить ${(number - 1) * 10}%`"
                        @click="
                            emit(
                                'updateProgress',
                                (number - 1) * 10
                            )
                        "
                    >
                        <span
                            class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-2 hidden -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-[10px] font-medium text-white shadow-lg group-hover:block"
                        >
                            {{ (number - 1) * 10 }}%
                        </span>
                    </button>
                </div>

                <div
                    class="mt-2 flex justify-between text-[11px] font-medium text-slate-400"
                >
                    <span>0%</span>
                    <span>50%</span>
                    <span>100%</span>
                </div>
            </div>
        </div>
    </section>
</template>
