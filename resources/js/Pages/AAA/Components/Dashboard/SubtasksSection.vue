<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
    subtasks: {
        type: [Array, Object],
        default: () => [],
    },
})

const normalizedSubtasks = computed(() => {
    if (Array.isArray(props.subtasks)) {
        return props.subtasks
    }

    const result = []

    Object.values(props.subtasks || {}).forEach(
        projects => {
            Object.values(projects || {}).forEach(
                tasks => {
                    Object.values(tasks || {}).forEach(
                        subtasks => {
                            result.push(...subtasks)
                        },
                    )
                },
            )
        },
    )

    return result
})

const groupedSubtasks = computed(() => {
    return normalizedSubtasks.value.reduce(
        (groups, subtask) => {
            const projectName =
                subtask.task?.project?.name ||
                'Без проекта'

            const taskTitle =
                subtask.task?.title ||
                'Без задачи'

            const key = `${projectName}-${taskTitle}`

            if (!groups[key]) {
                groups[key] = {
                    projectName,
                    taskTitle,
                    companyName:
                        subtask.task?.project?.company?.name ||
                        'Без компании',
                    items: [],
                }
            }

            groups[key].items.push(subtask)

            return groups
        },
        {},
    )
})

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
        year: '2-digit',
    })
}

const getProgress = subtask => {
    return Number(
        subtask.progress ??
        subtask.completion_percentage ??
        0,
    )
}
</script>

<template>
    <div>
        <div
            v-if="!normalizedSubtasks.length"
            class="rounded-xl border-2 border-dashed
                   border-slate-200 py-14 text-center
                   text-sm text-slate-400
                   dark:border-slate-800"
        >
            Подзадач пока нет
        </div>

        <div v-else class="space-y-3">
            <section
                v-for="group in groupedSubtasks"
                :key="`${group.projectName}-${group.taskTitle}`"
                class="overflow-hidden rounded-xl border
                       border-slate-200
                       dark:border-slate-800"
            >
                <header
                    class="flex flex-col gap-1 border-b
                           border-slate-100 bg-slate-50/70
                           px-3 py-2.5 dark:border-slate-800
                           dark:bg-slate-800/40 sm:flex-row
                           sm:items-center"
                >
                    <span
                        class="max-w-full truncate rounded-md
                               bg-indigo-100 px-2 py-1
                               text-[10px] font-bold
                               text-indigo-600
                               dark:bg-indigo-950/60
                               dark:text-indigo-300"
                    >
                        {{ group.projectName }}
                    </span>

                    <span
                        class="hidden text-slate-300 sm:block"
                    >
                        /
                    </span>

                    <h4
                        class="min-w-0 flex-1 truncate text-xs
                               font-bold text-slate-700
                               dark:text-slate-200"
                    >
                        {{ group.taskTitle }}
                    </h4>

                    <span
                        class="text-[10px] text-slate-400"
                    >
                        {{ group.items.length }}
                    </span>
                </header>

                <div
                    class="divide-y divide-slate-100
                           dark:divide-slate-800"
                >
                    <button
                        v-for="subtask in group.items"
                        :key="subtask.id"
                        type="button"
                        class="group flex w-full items-center
                               gap-3 px-3 py-2.5 text-left
                               transition hover:bg-indigo-50/50
                               dark:hover:bg-indigo-950/20"
                        @click="
                            router.visit(
                                `/subtasks/${subtask.id}`,
                            )
                        "
                    >
                        <span
                            class="h-2 w-2 shrink-0 rounded-full"
                            :class="
                                getProgress(subtask) >= 100
                                    ? 'bg-emerald-500'
                                    : 'bg-indigo-400'
                            "
                        />

                        <span
                            class="min-w-0 flex-1 truncate
                                   text-sm font-medium
                                   text-slate-700
                                   group-hover:text-indigo-600
                                   dark:text-slate-300"
                        >
                            {{ subtask.title }}
                        </span>

                        <span
                            class="hidden shrink-0 text-[10px]
                                   text-slate-400 sm:block"
                        >
                            {{ group.companyName }}
                        </span>

                        <span
                            class="shrink-0 rounded-md
                                   bg-slate-100 px-2 py-1
                                   text-[10px] font-bold
                                   text-slate-500
                                   dark:bg-slate-800"
                        >
                            {{ formatDate(subtask.due_date) }}
                        </span>

                        <span
                            class="w-8 shrink-0 text-right
                                   text-[10px] font-black
                                   text-slate-500"
                        >
                            {{ getProgress(subtask) }}%
                        </span>
                    </button>
                </div>
            </section>
        </div>
    </div>
</template>