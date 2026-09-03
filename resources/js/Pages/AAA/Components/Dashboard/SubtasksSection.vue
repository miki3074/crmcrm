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
                   border-zinc-200 py-14 text-center
                   text-sm text-zinc-400
                   dark:border-white/5"
        >
            Подзадач пока нет
        </div>

        <div v-else class="space-y-3">
            <section
                v-for="group in groupedSubtasks"
                :key="`${group.projectName}-${group.taskTitle}`"
                class="overflow-hidden rounded-xl border
                       border-zinc-200
                       dark:border-white/5"
            >
                <header
                    class="flex flex-col gap-1 border-b
                           border-zinc-100 bg-zinc-50/70
                           px-3 py-2.5 dark:border-white/5
                           dark:bg-white/[0.03] sm:flex-row
                           sm:items-center"
                >
                    <span
                        class="max-w-full truncate rounded-md
                               bg-cyan-100 px-2 py-1
                               text-[10px] font-bold
                               text-cyan-600
                               dark:bg-cyan-500/10
                               dark:text-cyan-300"
                    >
                        {{ group.projectName }}
                    </span>

                    <span
                        class="hidden text-zinc-500 sm:block"
                    >
                        /
                    </span>

                    <h4
                        class="min-w-0 flex-1 truncate text-xs
                               font-bold text-zinc-700
                               dark:text-zinc-300"
                    >
                        {{ group.taskTitle }}
                    </h4>

                    <span
                        class="text-[10px] text-zinc-400"
                    >
                        {{ group.items.length }}
                    </span>
                </header>

                <div
                    class="divide-y divide-zinc-100
                           dark:divide-white/5"
                >
                    <button
                        v-for="subtask in group.items"
                        :key="subtask.id"
                        type="button"
                        class="group flex w-full items-center
                               gap-3 px-3 py-2.5 text-left
                               transition hover:bg-cyan-50/60
                               dark:hover:bg-cyan-500/10"
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
                                    : 'bg-cyan-400'
                            "
                        />

                        <span
                            class="min-w-0 flex-1 truncate
                                   text-sm font-medium
                                   text-zinc-700
                                   group-hover:text-cyan-600
                                   dark:text-zinc-500"
                        >
                            {{ subtask.title }}
                        </span>

                        <span
                            class="hidden shrink-0 text-[10px]
                                   text-zinc-400 sm:block"
                        >
                            {{ group.companyName }}
                        </span>

                        <span
                            class="shrink-0 rounded-md
                                   bg-zinc-100 px-2 py-1
                                   text-[10px] font-bold
                                   text-zinc-500
                                   dark:bg-white/5"
                        >
                            {{ formatDate(subtask.due_date) }}
                        </span>

                        <span
                            class="w-8 shrink-0 text-right
                                   text-[10px] font-black
                                   text-zinc-500"
                        >
                            {{ getProgress(subtask) }}%
                        </span>
                    </button>
                </div>
            </section>
        </div>
    </div>
</template>