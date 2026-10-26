<script setup>
import {
    computed,
    ref,
} from 'vue'

import { router } from '@inertiajs/vue3'

const props = defineProps({
    tasks: {
        type: Array,
        default: () => [],
    },
    title: {
        type: String,
        default: 'Задачи',
    },
    variant: {
        type: String,
        default: 'default',
    },
    showFilters: {
        type: Boolean,
        default: false,
    },
    compact: {
        type: Boolean,
        default: false,
    },
})

const filter = ref('active')

const currentTasks = computed(() => {
    if (!props.showFilters) {
        return props.tasks
    }

    if (filter.value === 'active') {
        return props.tasks.filter(
            task => Number(task.progress || 0) < 100,
        )
    }

    if (filter.value === 'completed') {
        return props.tasks.filter(
            task => Number(task.progress || 0) >= 100,
        )
    }

    return props.tasks
})

const visibleTasks = computed(() => {
    return currentTasks.value.slice(
        0,
        props.compact ? 5 : 10,
    )
})

const getProgressColor = progress => {
    const value = Number(progress || 0)

    if (value >= 100) {
        return 'bg-emerald-500'
    }

    if (value >= 70) {
        return 'bg-blue-600 dark:bg-blue-500'
    }

    if (value >= 35) {
        return 'bg-blue-400 dark:bg-blue-400/80'
    }

    return 'bg-zinc-400 dark:bg-zinc-500'
}

const getContainerClass = () => {
    if (!props.compact) {
        return ''
    }

    if (props.variant === 'danger') {
        return 'border-rose-200 dark:border-rose-500/20'
    }

    if (props.variant === 'warning') {
        return 'border-amber-200 dark:border-amber-500/20'
    }

    return ''
}

const formatDate = value => {
    if (!value) {
        return 'Без срока'
    }

    const date = new Date(value)

    if (Number.isNaN(date.getTime())) {
        return value
    }

    return date.toLocaleDateString('ru-RU', {
        day: '2-digit',
        month: '2-digit',
    })
}
</script>

<template>
    <section
        :class="[
            compact
                ? 'overflow-hidden rounded-lg border bg-white shadow-sm dark:bg-zinc-900 dark:shadow-none'
                : '',
            getContainerClass(),
            compact && !getContainerClass()
                ? 'border-zinc-200 dark:border-white/[0.08]'
                : '',
        ]"
    >
        <header
            :class="[
                'flex items-center justify-between gap-3',
                compact
                    ? 'border-b border-zinc-200 px-4 py-3 dark:border-white/[0.06]'
                    : 'mb-4',
            ]"
        >
            <div class="flex min-w-0 items-center gap-2">
                <span
                    v-if="variant === 'danger'"
                    class="h-1.5 w-1.5 shrink-0 rounded-full
                           bg-rose-500"
                />

                <span
                    v-else-if="variant === 'warning'"
                    class="h-1.5 w-1.5 shrink-0 rounded-full
                           bg-amber-500"
                />

                <h3
                    class="truncate text-sm font-semibold
                           text-zinc-900 dark:text-zinc-100"
                >
                    {{ title }}
                </h3>

                <span
                    class="rounded bg-zinc-100 px-1.5
                           py-0.5 text-[11px] font-medium tabular-nums
                           text-zinc-600 dark:bg-white/[0.06] dark:text-zinc-300"
                >
                    {{ currentTasks.length }}
                </span>
            </div>

            <div
                v-if="showFilters"
                class="flex rounded-md bg-zinc-100 p-0.5
                       dark:bg-white/[0.04]"
            >
                <button
                    type="button"
                    class="rounded px-2.5 py-1
                           text-xs font-medium transition-colors
                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60"
                    :class="
                        filter === 'active'
                            ? 'bg-white text-zinc-900 shadow-sm ring-1 ring-zinc-200 dark:bg-zinc-800 dark:text-zinc-50 dark:ring-white/10'
                            : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200'
                    "
                    @click="filter = 'active'"
                >
                    В работе
                </button>

                <button
                    type="button"
                    class="rounded px-2.5 py-1
                           text-xs font-medium transition-colors
                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60"
                    :class="
                        filter === 'completed'
                            ? 'bg-white text-zinc-900 shadow-sm ring-1 ring-zinc-200 dark:bg-zinc-800 dark:text-zinc-50 dark:ring-white/10'
                            : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200'
                    "
                    @click="filter = 'completed'"
                >
                    Готово
                </button>
            </div>
        </header>

        <div
            v-if="visibleTasks.length"
            :class="[
                compact
                    ? 'divide-y divide-zinc-100 dark:divide-white/[0.06]'
                    : 'grid grid-cols-1 gap-2 lg:grid-cols-2',
            ]"
        >
            <button
                v-for="task in visibleTasks"
                :key="task.id"
                type="button"
                :class="[
                    'group w-full text-left transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60',
                    compact
                        ? 'px-4 py-3 hover:bg-zinc-50 focus-visible:ring-inset dark:hover:bg-white/[0.03]'
                        : 'rounded-md border border-zinc-200 bg-white p-3 hover:border-zinc-300 hover:bg-zinc-50 dark:border-white/[0.08] dark:bg-transparent dark:hover:border-white/15 dark:hover:bg-white/[0.03]',
                ]"
                @click="router.visit(task.link || `/tasks/${task.id}`)"
            >
                <div class="flex items-start gap-3">
                    <div class="min-w-0 flex-1">
                        <p
                            class="truncate text-xs
                                   text-zinc-500 dark:text-zinc-400"
                        >
                            {{
                                task.project?.name ||
                                'Без проекта'
                            }}
                        </p>

                        <h4
                            class="mt-0.5 line-clamp-1 text-sm
                                   font-medium text-zinc-900
                                   group-hover:text-blue-700
                                   dark:text-zinc-100
                                   dark:group-hover:text-blue-400"
                        >
                            {{ task.title }}
                        </h4>
                    </div>

                    <span
                        class="shrink-0 rounded border border-zinc-200
                               px-1.5 py-0.5 text-[11px] font-medium tabular-nums
                               text-zinc-600 dark:border-white/10 dark:text-zinc-400"
                    >
                        {{ formatDate(task.due_date) }}
                    </span>
                </div>

                <div class="mt-2.5 flex items-center gap-2">
                    <div
                        class="h-1 flex-1 overflow-hidden
                               rounded-full bg-zinc-100
                               dark:bg-white/[0.08]"
                    >
                        <div
                            class="h-full rounded-full transition-all"
                            :class="
                                getProgressColor(task.progress)
                            "
                            :style="{
                                width: `${Math.min(
                                    Number(task.progress || 0),
                                    100,
                                )}%`,
                            }"
                        />
                    </div>

                    <span
                        class="w-9 text-right text-[11px]
                               font-medium tabular-nums text-zinc-600 dark:text-zinc-400"
                    >
                        {{ Number(task.progress || 0) }}%
                    </span>
                </div>
            </button>
        </div>

        <div
            v-else
            :class="[
                'text-center text-sm text-zinc-500 dark:text-zinc-400',
                compact ? 'px-4 py-8' : 'py-12',
            ]"
        >
            Задач не найдено
        </div>
    </section>
</template>
