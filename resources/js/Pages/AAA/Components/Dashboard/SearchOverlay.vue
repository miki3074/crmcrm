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
                   bg-black/70 px-3 py-4
                   backdrop-blur-md sm:py-12"
            @click.self="close"
        >
            <div
                class="flex max-h-full w-full max-w-3xl
                       flex-col overflow-hidden rounded-2xl
                       bg-white shadow-2xl
                       dark:bg-zinc-900/60"
            >
                <header
                    class="border-b border-zinc-100 p-3
                           dark:border-white/5"
                >
                    <div class="relative">
                        <svg
                            class="absolute left-4 top-1/2
                                   h-5 w-5 -translate-y-1/2
                                   text-zinc-400"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"
                            />
                        </svg>

                        <input
                            ref="searchInput"
                            v-model="query"
                            type="search"
                            class="w-full rounded-xl border
                                   border-zinc-200 bg-zinc-50
                                   py-3.5 pl-12 pr-16 text-base
                                   text-zinc-900 outline-none
                                   focus:border-cyan-400
                                   focus:bg-white focus:ring-4
                                   focus:ring-cyan-100
                                   dark:border-white/10
                                   dark:bg-white/5
                                   dark:text-white"
                            placeholder="Поиск..."
                        />

                        <button
                            type="button"
                            class="absolute right-3 top-1/2
                                   -translate-y-1/2 rounded-md
                                   bg-zinc-200 px-2 py-1
                                   text-[10px] font-bold
                                   text-zinc-500
                                   dark:bg-white/10"
                            @click="close"
                        >
                            ESC
                        </button>
                    </div>
                </header>

                <div
                    v-if="normalizedQuery"
                    class="border-b border-zinc-100
                           px-4 py-2 text-xs text-zinc-400
                           dark:border-white/5"
                >
                    Найдено результатов: {{ totalResults }}
                </div>

                <main class="min-h-0 flex-1 overflow-y-auto p-3">
                    <div
                        v-if="!normalizedQuery"
                        class="flex min-h-64 flex-col
                               items-center justify-center
                               text-center"
                    >
                        <div
                            class="flex h-14 w-14 items-center
                                   justify-center rounded-2xl
                                   bg-cyan-100 text-cyan-600"
                        >
                            🔎
                        </div>

                        <h3
                            class="mt-4 font-bold
                                   text-zinc-800 dark:text-white"
                        >
                            Быстрый поиск
                        </h3>

                        <p
                            class="mt-1 max-w-sm text-sm
                                   text-zinc-400"
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
                            class="font-bold text-zinc-700
                                   dark:text-zinc-300"
                        >
                            Ничего не найдено
                        </p>

                        <p class="mt-1 text-sm text-zinc-400">
                            Попробуйте изменить поисковый запрос.
                        </p>
                    </div>

                    <div v-else class="space-y-4">
                        <section
                            v-if="results.companies.length"
                        >
                            <h4
                                class="mb-1 px-2 text-[10px]
                                       font-black uppercase
                                       tracking-widest text-zinc-400"
                            >
                                Компании
                            </h4>

                            <button
                                v-for="company in results.companies"
                                :key="company.id"
                                type="button"
                                class="flex w-full items-center gap-3
                                       rounded-xl px-3 py-2.5
                                       text-left transition
                                       hover:bg-cyan-50
                                       dark:hover:bg-cyan-500/10"
                                @click="
                                    visit(
                                        `/companies/${company.id}`,
                                    )
                                "
                            >
                                <span
                                    class="flex h-9 w-9 items-center
                                           justify-center rounded-lg
                                           bg-cyan-100"
                                >
                                    🏢
                                </span>

                                <span
                                    class="truncate text-sm
                                           font-semibold
                                           text-zinc-700
                                           dark:text-zinc-300"
                                >
                                    {{ company.name }}
                                </span>
                            </button>
                        </section>

                        <section
                            v-if="results.projects.length"
                        >
                            <h4
                                class="mb-1 px-2 text-[10px]
                                       font-black uppercase
                                       tracking-widest text-zinc-400"
                            >
                                Проекты
                            </h4>

                            <button
                                v-for="project in results.projects"
                                :key="project.id"
                                type="button"
                                class="flex w-full items-center gap-3
                                       rounded-xl px-3 py-2.5
                                       text-left transition
                                       hover:bg-cyan-50
                                       dark:hover:bg-cyan-500/10"
                                @click="
                                    visit(
                                        `/projects/${project.id}`,
                                    )
                                "
                            >
                                <span
                                    class="flex h-9 w-9 items-center
                                           justify-center rounded-lg
                                           bg-violet-100"
                                >
                                    🚀
                                </span>

                                <span class="min-w-0 flex-1">
                                    <span
                                        class="block truncate
                                               text-sm font-semibold
                                               text-zinc-700
                                               dark:text-zinc-300"
                                    >
                                        {{ project.name }}
                                    </span>

                                    <span
                                        class="block truncate
                                               text-[11px]
                                               text-zinc-400"
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
                                class="mb-1 px-2 text-[10px]
                                       font-black uppercase
                                       tracking-widest text-zinc-400"
                            >
                                Задачи
                            </h4>

                            <button
                                v-for="task in results.tasks"
                                :key="task.id"
                                type="button"
                                class="flex w-full items-center gap-3
                                       rounded-xl px-3 py-2.5
                                       text-left transition
                                       hover:bg-cyan-50
                                       dark:hover:bg-cyan-500/10"
                                @click="
                                    visit(`/tasks/${task.id}`)
                                "
                            >
                                <span
                                    class="flex h-9 w-9 items-center
                                           justify-center rounded-lg
                                           bg-emerald-100"
                                >
                                    ✓
                                </span>

                                <span class="min-w-0 flex-1">
                                    <span
                                        class="block truncate
                                               text-sm font-semibold
                                               text-zinc-700
                                               dark:text-zinc-300"
                                    >
                                        {{ task.title }}
                                    </span>

                                    <span
                                        class="block truncate
                                               text-[11px]
                                               text-zinc-400"
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
                                class="mb-1 px-2 text-[10px]
                                       font-black uppercase
                                       tracking-widest text-zinc-400"
                            >
                                Подзадачи
                            </h4>

                            <button
                                v-for="subtask in results.subtasks"
                                :key="subtask.id"
                                type="button"
                                class="flex w-full items-center gap-3
                                       rounded-xl px-3 py-2.5
                                       text-left transition
                                       hover:bg-cyan-50
                                       dark:hover:bg-cyan-500/10"
                                @click="
                                    visit(
                                        `/subtasks/${subtask.id}`,
                                    )
                                "
                            >
                                <span
                                    class="flex h-9 w-9 items-center
                                           justify-center rounded-lg
                                           bg-amber-100"
                                >
                                    ▪
                                </span>

                                <span
                                    class="truncate text-sm
                                           font-semibold
                                           text-zinc-700
                                           dark:text-zinc-300"
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