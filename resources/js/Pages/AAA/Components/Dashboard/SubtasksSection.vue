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
            class="rounded-md border border-dashed
                   border-zinc-200 py-14 text-center
                   text-sm text-zinc-500
                   dark:border-white/10 dark:text-zinc-400"
        >
            Подзадач пока нет
        </div>

        <div v-else class="space-y-3">
            <section
                v-for="group in groupedSubtasks"
                :key="`${group.projectName}-${group.taskTitle}`"
                class="overflow-hidden rounded-md border
                       border-zinc-200
                       dark:border-white/[0.08]"
            >
                <header
                    class="flex flex-col gap-1 border-b
                           border-zinc-200 bg-zinc-50
                           px-3 py-2 dark:border-white/[0.06]
                           dark:bg-white/[0.02] sm:flex-row
                           sm:items-center sm:gap-2"
                >
                    <span
                        class="max-w-full truncate text-xs
                               font-medium text-zinc-500
                               dark:text-zinc-400"
                    >
                        {{ group.projectName }}
                    </span>

                    <span
                        class="hidden text-zinc-300 dark:text-zinc-600 sm:block"
                    >
                        /
                    </span>

                    <h4
                        class="min-w-0 flex-1 truncate text-xs
                               font-semibold text-zinc-800
                               dark:text-zinc-200"
                    >
                        {{ group.taskTitle }}
                    </h4>

                    <span
                        class="text-[11px] tabular-nums text-zinc-500 dark:text-zinc-400"
                    >
                        {{ group.items.length }}
                    </span>
                </header>

                <div
                    class="divide-y divide-zinc-100
                           dark:divide-white/[0.06]"
                >
                    <button
                        v-for="subtask in group.items"
                        :key="subtask.id"
                        type="button"
                        class="group flex w-full items-center
                               gap-3 px-3 py-2.5 text-left
                               transition-colors hover:bg-zinc-50
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-blue-500/60
                               dark:hover:bg-white/[0.03]"
                        @click="
                            router.visit(
                                `/subtasks/${subtask.id}`,
                            )
                        "
                    >
                        <span
                            class="h-1.5 w-1.5 shrink-0 rounded-full"
                            :class="
                                getProgress(subtask) >= 100
                                    ? 'bg-emerald-500'
                                    : 'bg-zinc-400 dark:bg-zinc-500'
                            "
                        />

                        <span
                            class="min-w-0 flex-1 truncate
                                   text-sm
                                   text-zinc-800
                                   group-hover:text-blue-700
                                   dark:text-zinc-200
                                   dark:group-hover:text-blue-400"
                        >
                            {{ subtask.title }}
                        </span>

                        <span
                            class="hidden shrink-0 text-xs
                                   text-zinc-500 dark:text-zinc-400 sm:block"
                        >
                            {{ group.companyName }}
                        </span>

                        <span
                            class="shrink-0 rounded border
                                   border-zinc-200 px-1.5 py-0.5
                                   text-[11px] font-medium tabular-nums
                                   text-zinc-600
                                   dark:border-white/10 dark:text-zinc-400"
                        >
                            {{ formatDate(subtask.due_date) }}
                        </span>

                        <span
                            class="w-9 shrink-0 text-right
                                   text-[11px] font-medium tabular-nums
                                   text-zinc-600 dark:text-zinc-400"
                        >
                            {{ getProgress(subtask) }}%
                        </span>
                    </button>
                </div>
            </section>
        </div>
    </div>
</template>
