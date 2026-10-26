<script setup>
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
} from 'vue'

import { router } from '@inertiajs/vue3'

const props = defineProps({
    companies: {
        type: Array,
        default: () => [],
    },
    summary: {
        type: Object,
        default: () => ({}),
    },
})

const emit = defineEmits(['close'])

const query = ref('')
const searchInput = ref(null)

const normalizedQuery = computed(() => {
    return query.value.trim().toLowerCase()
})

const results = computed(() => {
    const value = normalizedQuery.value

    if (!value) {
        return {
            companies: [],
            projects: [],
            tasks: [],
            subtasks: [],
        }
    }

    return {
        companies: props.companies.filter(company =>
            company.name
                ?.toLowerCase()
                .includes(value),
        ),

        projects: (
            props.summary.managing_projects || []
        ).filter(project =>
            project.name
                ?.toLowerCase()
                .includes(value),
        ),

        tasks: (
            props.summary.all_tasks || []
        ).filter(task =>
            task.title
                ?.toLowerCase()
                .includes(value),
        ),

        subtasks: Array.isArray(
            props.summary.all_subtasks,
        )
            ? props.summary.all_subtasks.filter(
                  subtask =>
                      subtask.title
                          ?.toLowerCase()
                          .includes(value),
              )
            : [],
    }
})

const totalResults = computed(() => {
    return (
        results.value.companies.length +
        results.value.projects.length +
        results.value.tasks.length +
        results.value.subtasks.length
    )
})

const close = () => {
    emit('close')
}

const visit = url => {
    close()
    router.visit(url)
}

const handleKeydown = event => {
    if (event.key === 'Escape') {
        close()
    }
}

onMounted(async () => {
    window.addEventListener(
        'keydown',
        handleKeydown,
    )

    await nextTick()
    searchInput.value?.focus()
})

onBeforeUnmount(() => {
    window.removeEventListener(
        'keydown',
        handleKeydown,
    )
})
</script>

<template>
    <Teleport to="body">
        <div
            class="fixed inset-0 z-[200] flex justify-center
                   bg-zinc-950/50 px-3 py-4
                   backdrop-blur-[2px] sm:py-16"
            @click.self="close"
        >
            <div
                class="flex max-h-full w-full max-w-2xl
                       flex-col overflow-hidden rounded-xl
                       border border-zinc-200 bg-white shadow-xl
                       dark:border-white/10 dark:bg-zinc-900"
            >
                <header
                    class="border-b border-zinc-200 p-3
                           dark:border-white/[0.06]"
                >
                    <div class="relative">
                        <svg
                            class="absolute left-3 top-1/2
                                   h-4 w-4 -translate-y-1/2
                                   text-zinc-400 dark:text-zinc-500"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.75"
                                d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"
                            />
                        </svg>

                        <input
                            ref="searchInput"
                            v-model="query"
                            type="search"
                            class="w-full rounded-md border
                                   border-zinc-300 bg-white
                                   py-2.5 pl-10 pr-16 text-[15px]
                                   text-zinc-900 placeholder:text-zinc-400 outline-none
                                   focus:border-blue-600
                                   focus:ring-2
                                   focus:ring-blue-600/20
                                   dark:border-white/10
                                   dark:bg-zinc-950
                                   dark:text-zinc-100"
                            placeholder="Поиск..."
                        />

                        <button
                            type="button"
                            class="absolute right-2.5 top-1/2
                                   -translate-y-1/2 rounded
                                   border border-zinc-200 bg-zinc-50 px-1.5 py-0.5
                                   text-[11px] font-medium
                                   text-zinc-500 transition-colors hover:text-zinc-800
                                   focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60
                                   dark:border-white/10 dark:bg-white/[0.04] dark:text-zinc-400 dark:hover:text-zinc-100"
                            @click="close"
                        >
                            ESC
                        </button>
                    </div>
                </header>

                <div
                    v-if="normalizedQuery"
                    class="border-b border-zinc-200
                           px-4 py-2 text-xs tabular-nums text-zinc-500
                           dark:border-white/[0.06] dark:text-zinc-400"
                >
                    Найдено результатов: {{ totalResults }}
                </div>

                <main class="min-h-0 flex-1 overflow-y-auto p-2">
                    <div
                        v-if="!normalizedQuery"
                        class="flex min-h-64 flex-col
                               items-center justify-center
                               text-center"
                    >
                        <div
                            class="flex h-10 w-10 items-center
                                   justify-center rounded-md
                                   border border-zinc-200 text-zinc-500
                                   dark:border-white/10 dark:text-zinc-400"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                            </svg>
                        </div>

                        <h3
                            class="mt-4 text-sm font-semibold
                                   text-zinc-900 dark:text-zinc-100"
                        >
                            Быстрый поиск
                        </h3>

                        <p
                            class="mt-1 max-w-sm text-sm
                                   text-zinc-500 dark:text-zinc-400"
                        >
                            Введите название компании, проекта,
                            задачи или подзадачи.
                        </p>
                    </div>

                    <div
                        v-else-if="totalResults === 0"
                        class="flex min-h-64 flex-col
                               items-center justify-center
                               text-center"
                    >
                        <p
                            class="text-sm font-semibold text-zinc-900
                                   dark:text-zinc-100"
                        >
                            Ничего не найдено
                        </p>

                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            Попробуйте изменить поисковый запрос.
                        </p>
                    </div>

                    <div v-else class="space-y-3">
                        <section
                            v-if="results.companies.length"
                        >
                            <h4
                                class="mb-1 px-2 pt-1 text-[11px]
                                       font-medium uppercase
                                       tracking-wider text-zinc-500 dark:text-zinc-400"
                            >
                                Компании
                            </h4>

                            <button
                                v-for="company in results.companies"
                                :key="company.id"
                                type="button"
                                class="flex w-full items-center gap-3
                                       rounded-md px-2 py-2
                                       text-left transition-colors
                                       hover:bg-zinc-100
                                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60
                                       dark:hover:bg-white/[0.05]"
                                @click="
                                    visit(
                                        `/companies/${company.id}`,
                                    )
                                "
                            >
                                <span
                                    class="flex h-8 w-8 shrink-0 items-center
                                           justify-center rounded-md
                                           border border-zinc-200 text-zinc-500
                                           dark:border-white/10 dark:text-zinc-400"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 21h18M5 21V7l7-4 7 4v14M9 9h.01M15 9h.01M9 13h.01M15 13h.01M9 17h.01M15 17h.01" />
                                    </svg>
                                </span>

                                <span
                                    class="truncate text-sm
                                           font-medium
                                           text-zinc-800
                                           dark:text-zinc-200"
                                >
                                    {{ company.name }}
                                </span>
                            </button>
                        </section>

                        <section
                            v-if="results.projects.length"
                        >
                            <h4
                                class="mb-1 px-2 pt-1 text-[11px]
                                       font-medium uppercase
                                       tracking-wider text-zinc-500 dark:text-zinc-400"
                            >
                                Проекты
                            </h4>

                            <button
                                v-for="project in results.projects"
                                :key="project.id"
                                type="button"
                                class="flex w-full items-center gap-3
                                       rounded-md px-2 py-2
                                       text-left transition-colors
                                       hover:bg-zinc-100
                                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60
                                       dark:hover:bg-white/[0.05]"
                                @click="
                                    visit(
                                        `/projects/${project.id}`,
                                    )
                                "
                            >
                                <span
                                    class="flex h-8 w-8 shrink-0 items-center
                                           justify-center rounded-md
                                           border border-zinc-200 text-zinc-500
                                           dark:border-white/10 dark:text-zinc-400"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 7a2 2 0 012-2h5l2 2h7a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z" />
                                    </svg>
                                </span>

                                <span class="min-w-0 flex-1">
                                    <span
                                        class="block truncate
                                               text-sm font-medium
                                               text-zinc-800
                                               dark:text-zinc-200"
                                    >
                                        {{ project.name }}
                                    </span>

                                    <span
                                        class="block truncate
                                               text-xs
                                               text-zinc-500 dark:text-zinc-400"
                                    >
                                        {{
                                            project.company?.name ||
                                            'Без компании'
                                        }}
                                    </span>
                                </span>
                            </button>
                        </section>

                        <section v-if="results.tasks.length">
                            <h4
                                class="mb-1 px-2 pt-1 text-[11px]
                                       font-medium uppercase
                                       tracking-wider text-zinc-500 dark:text-zinc-400"
                            >
                                Задачи
                            </h4>

                            <button
                                v-for="task in results.tasks"
                                :key="task.id"
                                type="button"
                                class="flex w-full items-center gap-3
                                       rounded-md px-2 py-2
                                       text-left transition-colors
                                       hover:bg-zinc-100
                                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60
                                       dark:hover:bg-white/[0.05]"
                                @click="
                                    visit(`/tasks/${task.id}`)
                                "
                            >
                                <span
                                    class="flex h-8 w-8 shrink-0 items-center
                                           justify-center rounded-md
                                           border border-zinc-200 text-zinc-500
                                           dark:border-white/10 dark:text-zinc-400"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </span>

                                <span class="min-w-0 flex-1">
                                    <span
                                        class="block truncate
                                               text-sm font-medium
                                               text-zinc-800
                                               dark:text-zinc-200"
                                    >
                                        {{ task.title }}
                                    </span>

                                    <span
                                        class="block truncate
                                               text-xs
                                               text-zinc-500 dark:text-zinc-400"
                                    >
                                        {{
                                            task.project?.name ||
                                            'Без проекта'
                                        }}
                                    </span>
                                </span>
                            </button>
                        </section>

                        <section
                            v-if="results.subtasks.length"
                        >
                            <h4
                                class="mb-1 px-2 pt-1 text-[11px]
                                       font-medium uppercase
                                       tracking-wider text-zinc-500 dark:text-zinc-400"
                            >
                                Подзадачи
                            </h4>

                            <button
                                v-for="subtask in results.subtasks"
                                :key="subtask.id"
                                type="button"
                                class="flex w-full items-center gap-3
                                       rounded-md px-2 py-2
                                       text-left transition-colors
                                       hover:bg-zinc-100
                                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60
                                       dark:hover:bg-white/[0.05]"
                                @click="
                                    visit(
                                        `/subtasks/${subtask.id}`,
                                    )
                                "
                            >
                                <span
                                    class="flex h-8 w-8 shrink-0 items-center
                                           justify-center rounded-md
                                           border border-zinc-200 text-zinc-500
                                           dark:border-white/10 dark:text-zinc-400"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 6h11M9 12h11M9 18h11M5 6h.01M5 12h.01M5 18h.01" />
                                    </svg>
                                </span>

                                <span
                                    class="truncate text-sm
                                           font-medium
                                           text-zinc-800
                                           dark:text-zinc-200"
                                >
                                    {{ subtask.title }}
                                </span>
                            </button>
                        </section>
                    </div>
                </main>
            </div>
        </div>
    </Teleport>
</template>
