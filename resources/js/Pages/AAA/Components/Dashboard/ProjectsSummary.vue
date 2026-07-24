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
        class="overflow-hidden rounded-2xl border
               border-slate-200 bg-white shadow-sm
               dark:border-slate-800 dark:bg-slate-900"
    >
        <header
            class="flex items-center justify-between
                   border-b border-slate-100 px-4 py-3
                   dark:border-slate-800"
        >
            <div>
                <h3
                    class="text-sm font-bold
                           text-slate-900 dark:text-white"
                >
                    Управляемые проекты
                </h3>

                <p class="text-[11px] text-slate-400">
                    {{ projects.length }} проектов
                </p>
            </div>

            <button
                v-if="projects.length"
                type="button"
                class="rounded-lg px-2.5 py-1.5
                       text-xs font-bold text-indigo-600
                       transition hover:bg-indigo-50
                       dark:hover:bg-indigo-950/40"
                @click="isModalOpen = true"
            >
                Открыть все
            </button>
        </header>

        <div
            v-if="visibleProjects.length"
            class="divide-y divide-slate-100
                   dark:divide-slate-800"
        >
            <button
                v-for="project in visibleProjects"
                :key="project.id"
                type="button"
                class="group block w-full px-4 py-3
                       text-left transition hover:bg-indigo-50/50
                       dark:hover:bg-indigo-950/20"
                @click="router.visit(`/projects/${project.id}`)"
            >
                <div class="flex items-start gap-3">
                    <span
                        class="mt-0.5 flex h-8 w-8 shrink-0
                               items-center justify-center rounded-lg
                               bg-indigo-100 text-xs font-black
                               text-indigo-600
                               dark:bg-indigo-950/60
                               dark:text-indigo-300"
                    >
                        {{ getTasksCount(project) }}
                    </span>

                    <div class="min-w-0 flex-1">
                        <p
                            class="truncate text-sm font-bold
                                   text-slate-800
                                   group-hover:text-indigo-600
                                   dark:text-white"
                        >
                            {{ project.name }}
                        </p>

                        <p
                            class="mt-0.5 truncate text-[11px]
                                   text-slate-400"
                        >
                            {{
                                project.company?.name ||
                                'Без компании'
                            }}
                        </p>
                    </div>

                    <div class="shrink-0 text-right">
                        <p
                            class="text-[10px] font-semibold
                                   text-slate-400"
                        >
                            Дедлайн
                        </p>

                        <p
                            class="mt-0.5 text-[11px]
                                   font-bold text-slate-600
                                   dark:text-slate-300"
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
                   text-slate-400"
        >
            Проектов нет
        </div>

        <button
            v-if="hiddenCount > 0"
            type="button"
            class="w-full border-t border-slate-100
                   px-4 py-2.5 text-xs font-bold
                   text-indigo-600 transition
                   hover:bg-indigo-50
                   dark:border-slate-800
                   dark:hover:bg-indigo-950/30"
            @click="isModalOpen = true"
        >
            Ещё {{ hiddenCount }} проектов
        </button>

        <Teleport to="body">
            <div
                v-if="isModalOpen"
                class="fixed inset-0 z-[100] flex items-center
                       justify-center bg-slate-950/60 p-4
                       backdrop-blur-sm"
                @click.self="isModalOpen = false"
            >
                <div
                    class="flex max-h-[90vh] w-full max-w-5xl
                           flex-col overflow-hidden rounded-2xl
                           bg-white shadow-2xl dark:bg-slate-900"
                >
                    <header
                        class="flex flex-col gap-3 border-b
                               border-slate-100 px-5 py-4
                               dark:border-slate-800 sm:flex-row
                               sm:items-center"
                    >
                        <div class="min-w-0 flex-1">
                            <h2
                                class="text-lg font-bold
                                       text-slate-900 dark:text-white"
                            >
                                Все управляемые проекты
                            </h2>

                            <p class="text-xs text-slate-400">
                                Найдено:
                                {{ filteredProjects.length }}
                            </p>
                        </div>

                        <input
                            v-model="search"
                            type="search"
                            class="w-full rounded-xl border
                                   border-slate-200 px-4 py-2.5
                                   text-sm outline-none
                                   focus:border-indigo-400
                                   dark:border-slate-700
                                   dark:bg-slate-800
                                   dark:text-white sm:max-w-xs"
                            placeholder="Поиск проекта..."
                        />

                        <button
                            type="button"
                            class="rounded-lg p-2
                                   hover:bg-slate-100
                                   dark:hover:bg-slate-800"
                            @click="isModalOpen = false"
                        >
                            ✕
                        </button>
                    </header>

                    <div
                        class="flex gap-2 overflow-x-auto border-b
                               border-slate-100 px-5 py-3
                               dark:border-slate-800"
                    >
                        <button
                            type="button"
                            class="whitespace-nowrap rounded-lg
                                   px-3 py-1.5 text-xs font-bold"
                            :class="
                                selectedCompanyFilter === 'all'
                                    ? 'bg-indigo-600 text-white'
                                    : 'bg-slate-100 text-slate-500 dark:bg-slate-800'
                            "
                            @click="selectedCompanyFilter = 'all'"
                        >
                            Все компании
                        </button>

                        <button
                            v-for="companyName in companyNames"
                            :key="companyName"
                            type="button"
                            class="whitespace-nowrap rounded-lg
                                   px-3 py-1.5 text-xs font-bold"
                            :class="
                                selectedCompanyFilter === companyName
                                    ? 'bg-indigo-600 text-white'
                                    : 'bg-slate-100 text-slate-500 dark:bg-slate-800'
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
                            class="rounded-xl border
                                   border-slate-200 p-3 text-left
                                   transition hover:border-indigo-400
                                   hover:bg-indigo-50/40
                                   dark:border-slate-700
                                   dark:hover:bg-indigo-950/20"
                            @click="
                                router.visit(
                                    `/projects/${project.id}`,
                                )
                            "
                        >
                            <p
                                class="line-clamp-2 text-sm
                                       font-bold text-slate-800
                                       dark:text-white"
                            >
                                {{ project.name }}
                            </p>

                            <p
                                class="mt-1 truncate text-xs
                                       text-slate-400"
                            >
                                {{
                                    project.company?.name ||
                                    'Без компании'
                                }}
                            </p>

                            <div
                                class="mt-3 flex items-center
                                       justify-between text-[11px]"
                            >
                                <span class="text-slate-500">
                                    {{ calculateDeadline(project) }}
                                </span>

                                <span
                                    class="rounded-md bg-indigo-100
                                           px-2 py-1 font-bold
                                           text-indigo-600"
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