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
        return 'bg-cyan-500'
    }

    if (value >= 35) {
        return 'bg-amber-500'
    }

    return 'bg-zinc-400'
}

const getContainerClass = () => {
    if (!props.compact) {
        return ''
    }

    if (props.variant === 'danger') {
        return 'border-rose-200 dark:border-rose-900/70'
    }

    if (props.variant === 'warning') {
        return 'border-amber-200 dark:border-amber-900/70'
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
                ? 'overflow-hidden rounded-2xl border bg-white shadow-sm dark:bg-zinc-900/60'
                : '',
            getContainerClass(),
            compact && !getContainerClass()
                ? 'border-zinc-200 dark:border-white/5'
                : '',
        ]"
    >
        <header
            :class="[
                'flex items-center justify-between gap-3',
                compact
                    ? 'border-b border-zinc-100 px-4 py-3 dark:border-white/5'
                    : 'mb-3',
            ]"
        >
            <div class="flex min-w-0 items-center gap-2">
                <span
                    v-if="variant === 'danger'"
                    class="h-2 w-2 shrink-0 rounded-full
                           bg-rose-500"
                />

                <span
                    v-else-if="variant === 'warning'"
                    class="h-2 w-2 shrink-0 rounded-full
                           bg-amber-500"
                />

                <h3
                    class="truncate text-sm font-bold
                           text-zinc-900 dark:text-white"
                >
                    {{ title }}
                </h3>

                <span
                    class="rounded-md bg-zinc-100 px-1.5
                           py-0.5 text-[10px] font-bold
                           text-zinc-500 dark:bg-white/5"
                >
                    {{ currentTasks.length }}
                </span>
            </div>

            <div
                v-if="showFilters"
                class="flex rounded-lg bg-zinc-100 p-0.5
                       dark:bg-white/5"
            >
                <button
                    type="button"
                    class="rounded-md px-2.5 py-1
                           text-[10px] font-bold transition"
                    :class="
                        filter === 'active'
                            ? 'bg-white text-cyan-600 shadow-sm dark:bg-white/10'
                            : 'text-zinc-400'
                    "
                    @click="filter = 'active'"
                >
                    В работе
                </button>

                <button
                    type="button"
                    class="rounded-md px-2.5 py-1
                           text-[10px] font-bold transition"
                    :class="
                        filter === 'completed'
                            ? 'bg-white text-emerald-600 shadow-sm dark:bg-white/10'
                            : 'text-zinc-400'
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
                    ? 'divide-y divide-zinc-100 dark:divide-white/5'
                    : 'grid grid-cols-1 gap-2 lg:grid-cols-2',
            ]"
        >
            <button
                v-for="task in visibleTasks"
                :key="task.id"
                type="button"
                :class="[
                    'group w-full text-left transition',
                    compact
                        ? 'px-4 py-3 hover:bg-zinc-50 dark:hover:bg-white/5'
                        : 'rounded-xl border border-zinc-100 bg-zinc-50/60 p-3 hover:border-cyan-300 hover:bg-white hover:shadow-sm dark:border-white/5 dark:bg-white/[0.03]',
                ]"
                @click="router.visit(task.link || `/tasks/${task.id}`)"
            >
                <div class="flex items-start gap-3">
                    <div class="min-w-0 flex-1">
                        <p
                            class="truncate text-[10px]
                                   font-bold uppercase tracking-wide
                                   text-cyan-500"
                        >
                            {{
                                task.project?.name ||
                                'Без проекта'
                            }}
                        </p>

                        <h4
                            class="mt-0.5 line-clamp-1 text-sm
                                   font-semibold text-zinc-700
                                   group-hover:text-cyan-600
                                   dark:text-zinc-300"
                        >
                            {{ task.title }}
                        </h4>
                    </div>

                    <span
                        class="shrink-0 rounded-md bg-zinc-100
                               px-2 py-1 text-[10px] font-bold
                               text-zinc-500 dark:bg-white/5"
                    >
                        {{ formatDate(task.due_date) }}
                    </span>
                </div>

                <div class="mt-2 flex items-center gap-2">
                    <div
                        class="h-1.5 flex-1 overflow-hidden
                               rounded-full bg-zinc-200
                               dark:bg-white/10"
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
                        class="w-8 text-right text-[10px]
                               font-black text-zinc-500"
                    >
                        {{ Number(task.progress || 0) }}%
                    </span>
                </div>
            </button>
        </div>

        <div
            v-else
            :class="[
                'text-center text-sm text-zinc-400',
                compact ? 'px-4 py-8' : 'py-12',
            ]"
        >
            Задач не найдено
        </div>
    </section>
</template>