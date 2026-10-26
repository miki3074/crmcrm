<script setup>
import {
    computed,
    ref,
} from 'vue'

import { router } from '@inertiajs/vue3'

const props = defineProps({
    projects: {
        type: Array,
        default: () => [],
    },
})

const LIMIT = 5

const isModalOpen = ref(false)
const selectedCompanyFilter = ref('all')
const search = ref('')

const visibleProjects = computed(() => {
    return props.projects.slice(0, LIMIT)
})

const hiddenCount = computed(() => {
    return Math.max(
        props.projects.length - LIMIT,
        0,
    )
})

const companyNames = computed(() => {
    return [
        ...new Set(
            props.projects.map(
                project =>
                    project.company?.name ||
                    'Без компании',
            ),
        ),
    ].sort()
})

const filteredProjects = computed(() => {
    const query = search.value.trim().toLowerCase()

    return props.projects.filter(project => {
        const companyName =
            project.company?.name || 'Без компании'

        const matchesCompany =
            selectedCompanyFilter.value === 'all' ||
            selectedCompanyFilter.value === companyName

        const matchesQuery =
            !query ||
            project.name
                ?.toLowerCase()
                .includes(query) ||
            companyName
                .toLowerCase()
                .includes(query)

        return matchesCompany && matchesQuery
    })
})

const calculateDeadline = project => {
    if (project.due_date) {
        return new Date(
            project.due_date,
        ).toLocaleDateString('ru-RU')
    }

    if (!project.start_date || !project.duration_days) {
        return 'Без срока'
    }

    const date = new Date(project.start_date)
    date.setDate(
        date.getDate() + Number(project.duration_days),
    )

    return date.toLocaleDateString('ru-RU')
}

const getTasksCount = project => {
    return (
        project.tasks_count ||
        project.tasks?.length ||
        0
    )
}
</script>

<template>
    <section
        class="overflow-hidden rounded-lg border
               border-zinc-200 bg-white shadow-sm
               dark:border-white/[0.08] dark:bg-zinc-900 dark:shadow-none"
    >
        <header
            class="flex items-center justify-between gap-3
                   border-b border-zinc-200 px-4 py-3
                   dark:border-white/[0.06]"
        >
            <div>
                <h3
                    class="text-sm font-semibold
                           text-zinc-900 dark:text-zinc-100"
                >
                    Мои проекты
                </h3>

                <p class="text-xs tabular-nums text-zinc-500 dark:text-zinc-400">
                    {{ projects.length }} проектов
                </p>
            </div>

            <button
                v-if="projects.length"
                type="button"
                class="rounded-md px-2 py-1
                       text-xs font-medium text-blue-700
                       transition-colors hover:bg-blue-50
                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60
                       dark:text-blue-400 dark:hover:bg-blue-500/10"
                @click="isModalOpen = true"
            >
                Открыть все
            </button>
        </header>

        <div
            v-if="visibleProjects.length"
            class="divide-y divide-zinc-100
                   dark:divide-white/[0.06]"
        >
            <button
                v-for="project in visibleProjects"
                :key="project.id"
                type="button"
                class="group block w-full px-4 py-3
                       text-left transition-colors hover:bg-zinc-50
                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-blue-500/60
                       dark:hover:bg-white/[0.03]"
                @click="router.visit(`/projects/${project.id}`)"
            >
                <div class="flex items-start gap-3">
                    <span
                        class="mt-0.5 flex h-7 min-w-[1.75rem] shrink-0
                               items-center justify-center rounded-md
                               border border-zinc-200 px-1 text-xs font-medium
                               tabular-nums text-zinc-600
                               dark:border-white/10
                               dark:text-zinc-300"
                    >
                        {{ getTasksCount(project) }}
                    </span>

                    <div class="min-w-0 flex-1">
                        <p
                            class="truncate text-sm font-medium
                                   text-zinc-900
                                   group-hover:text-blue-700
                                   dark:text-zinc-100
                                   dark:group-hover:text-blue-400"
                        >
                            {{ project.name }}
                        </p>

                        <p
                            class="mt-0.5 truncate text-xs
                                   text-zinc-500 dark:text-zinc-400"
                        >
                            {{
                                project.company?.name ||
                                'Без компании'
                            }}
                        </p>
                    </div>

                    <div class="shrink-0 text-right">
                        <p
                            class="text-[11px] text-zinc-500 dark:text-zinc-400"
                        >
                            Дедлайн
                        </p>

                        <p
                            class="mt-0.5 text-xs
                                   font-medium tabular-nums text-zinc-700
                                   dark:text-zinc-300"
                        >
                            {{ calculateDeadline(project) }}
                        </p>
                    </div>
                </div>
            </button>
        </div>

        <div
            v-else
            class="px-4 py-8 text-center text-sm
                   text-zinc-500 dark:text-zinc-400"
        >
            Проектов нет
        </div>

        <button
            v-if="hiddenCount > 0"
            type="button"
            class="w-full border-t border-zinc-200
                   px-4 py-2.5 text-xs font-medium tabular-nums
                   text-blue-700 transition-colors
                   hover:bg-zinc-50
                   focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-blue-500/60
                   dark:border-white/[0.06] dark:text-blue-400
                   dark:hover:bg-white/[0.03]"
            @click="isModalOpen = true"
        >
            Ещё {{ hiddenCount }} проектов
        </button>

        <Teleport to="body">
            <div
                v-if="isModalOpen"
                class="fixed inset-0 z-[100] flex items-center
                       justify-center bg-zinc-950/50 p-4
                       backdrop-blur-[2px]"
                @click.self="isModalOpen = false"
            >
                <div
                    class="flex max-h-[90vh] w-full max-w-5xl
                           flex-col overflow-hidden rounded-xl
                           border border-zinc-200 bg-white shadow-xl
                           dark:border-white/10 dark:bg-zinc-900"
                >
                    <header
                        class="flex flex-col gap-3 border-b
                               border-zinc-200 px-5 py-4
                               dark:border-white/[0.06] sm:flex-row
                               sm:items-center"
                    >
                        <div class="min-w-0 flex-1">
                            <h2
                                class="text-base font-semibold
                                       text-zinc-900 dark:text-zinc-50"
                            >
                                Все мои проекты
                            </h2>

                            <p class="text-xs tabular-nums text-zinc-500 dark:text-zinc-400">
                                Найдено:
                                {{ filteredProjects.length }}
                            </p>
                        </div>

                        <input
                            v-model="search"
                            type="search"
                            class="w-full rounded-md border
                                   border-zinc-300 bg-white px-3 py-2
                                   text-sm text-zinc-900 placeholder:text-zinc-400 outline-none
                                   focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20
                                   dark:border-white/10
                                   dark:bg-zinc-950
                                   dark:text-zinc-100 sm:max-w-xs"
                            placeholder="Поиск проекта..."
                        />

                        <button
                            type="button"
                            aria-label="Закрыть"
                            class="flex h-8 w-8 shrink-0 items-center justify-center self-end rounded-md
                                   text-zinc-500 transition-colors hover:bg-zinc-100 hover:text-zinc-900
                                   focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60
                                   dark:text-zinc-400 dark:hover:bg-white/[0.06] dark:hover:text-zinc-100 sm:self-auto"
                            @click="isModalOpen = false"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </header>

                    <div
                        class="flex gap-1.5 overflow-x-auto border-b
                               border-zinc-200 px-5 py-3
                               dark:border-white/[0.06]"
                    >
                        <button
                            type="button"
                            class="whitespace-nowrap rounded-md border
                                   px-2.5 py-1 text-xs font-medium transition-colors
                                   focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60"
                            :class="
                                selectedCompanyFilter === 'all'
                                    ? 'border-zinc-900 bg-zinc-900 text-white dark:border-zinc-100 dark:bg-zinc-100 dark:text-zinc-900'
                                    : 'border-zinc-200 text-zinc-600 hover:bg-zinc-50 dark:border-white/10 dark:text-zinc-400 dark:hover:bg-white/[0.04]'
                            "
                            @click="selectedCompanyFilter = 'all'"
                        >
                            Все компании
                        </button>

                        <button
                            v-for="companyName in companyNames"
                            :key="companyName"
                            type="button"
                            class="whitespace-nowrap rounded-md border
                                   px-2.5 py-1 text-xs font-medium transition-colors
                                   focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60"
                            :class="
                                selectedCompanyFilter === companyName
                                    ? 'border-zinc-900 bg-zinc-900 text-white dark:border-zinc-100 dark:bg-zinc-100 dark:text-zinc-900'
                                    : 'border-zinc-200 text-zinc-600 hover:bg-zinc-50 dark:border-white/10 dark:text-zinc-400 dark:hover:bg-white/[0.04]'
                            "
                            @click="
                                selectedCompanyFilter =
                                    companyName
                            "
                        >
                            {{ companyName }}
                        </button>
                    </div>

                    <div
                        class="grid grid-cols-1 gap-2
                               overflow-y-auto p-4 sm:grid-cols-2
                               lg:grid-cols-3"
                    >
                        <button
                            v-for="project in filteredProjects"
                            :key="project.id"
                            type="button"
                            class="group rounded-md border
                                   border-zinc-200 p-3 text-left
                                   transition-colors hover:border-zinc-300
                                   hover:bg-zinc-50
                                   focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60
                                   dark:border-white/[0.08]
                                   dark:hover:border-white/15
                                   dark:hover:bg-white/[0.03]"
                            @click="
                                router.visit(
                                    `/projects/${project.id}`,
                                )
                            "
                        >
                            <p
                                class="line-clamp-2 text-sm
                                       font-medium text-zinc-900 group-hover:text-blue-700
                                       dark:text-zinc-100 dark:group-hover:text-blue-400"
                            >
                                {{ project.name }}
                            </p>

                            <p
                                class="mt-1 truncate text-xs
                                       text-zinc-500 dark:text-zinc-400"
                            >
                                {{
                                    project.company?.name ||
                                    'Без компании'
                                }}
                            </p>

                            <div
                                class="mt-3 flex items-center
                                       justify-between text-xs tabular-nums"
                            >
                                <span class="text-zinc-500 dark:text-zinc-400">
                                    {{ calculateDeadline(project) }}
                                </span>

                                <span
                                    class="rounded border border-zinc-200
                                           px-1.5 py-0.5 font-medium
                                           text-zinc-600 dark:border-white/10 dark:text-zinc-300"
                                >
                                    {{ getTasksCount(project) }}
                                    задач
                                </span>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </section>
</template>
