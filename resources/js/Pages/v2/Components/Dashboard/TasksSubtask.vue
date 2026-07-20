<script setup>
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
    tasks: { type: Array, default: () => [] },
    subtasks: { type: Array, default: () => [] },
})

const activeType = ref('tasks')

const tabs = [
    { value: 'tasks', label: 'Задачи' },
    { value: 'subtasks', label: 'Подзадачи' },
]

const rows = computed(() => {
    const source = activeType.value === 'tasks' ? props.tasks : props.subtasks

    return source.map((item) => ({
        ...item,
        company_name: activeType.value === 'tasks'
            ? item.project?.company?.name || item.company?.name || 'Без компании'
            : item.task?.project?.company?.name || 'Без компании',
        project_name: activeType.value === 'tasks'
            ? item.project?.name || 'Без проекта'
            : item.task?.project?.name || 'Без проекта',
        parent_task_title: activeType.value === 'subtasks'
            ? item.task?.title || 'Без родительской задачи'
            : null,
    }))
})

const formatDate = (value, withTime = false) => {
    if (!value) return '—'

    return new Intl.DateTimeFormat('ru-RU', withTime ? {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    } : {
        day: '2-digit', month: 'short', year: 'numeric',
    }).format(new Date(value))
}

const getCreatorName = (item) =>
    item.creator?.name || item.creator_name || `Пользователь #${item.creator_id ?? '—'}`

const getResponsibleNames = (item) => {
    if (Array.isArray(item.responsibles) && item.responsibles.length) {
        return item.responsibles.map((user) => user.name).join(', ')
    }
    return item.responsible?.name || 'Не назначен'
}

const normalizedProgress = (item) => {
    const value = Number(item.progress || 0)
    return Math.max(0, Math.min(100, value))
}

const getStatus = (item) => {
    if (item.completed || normalizedProgress(item) >= 100) {
        return {
            label: 'Готово',
            classes: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300',
        }
    }

    const rawStatus = String(item.status || '').toLowerCase()
    if (['in_progress', 'in-progress', 'working', 'active', 'в работе'].includes(rawStatus) || normalizedProgress(item) > 0) {
        return {
            label: 'В работе',
            classes: 'bg-blue-100 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300',
        }
    }

    return {
        label: 'Не в работе',
        classes: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
    }
}

const openItem = (item) => {
    router.visit(activeType.value === 'tasks' ? `/tasks/${item.id}` : `/subtasks/${item.id}`)
}
</script>

<template>
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
        <header class="flex flex-col gap-4 border-b border-slate-200 px-4 py-4 dark:border-slate-800 md:flex-row md:items-center md:justify-between md:px-6">
            <div>
                <h2 class="text-lg font-semibold text-slate-950 dark:text-white">Работа</h2>
                <p class="mt-1 text-sm text-slate-500">
                    {{ rows.length }} {{ activeType === 'tasks' ? 'задач' : 'подзадач' }}
                </p>
            </div>

            <div class="inline-flex w-full rounded-xl bg-slate-100 p-1 dark:bg-slate-900 md:w-auto">
                <button
                    v-for="tab in tabs"
                    :key="tab.value"
                    type="button"
                    class="flex-1 rounded-lg px-5 py-2 text-sm font-medium transition md:flex-none"
                    :class="activeType === tab.value
                        ? 'bg-white text-slate-950 shadow-sm dark:bg-slate-800 dark:text-white'
                        : 'text-slate-500 hover:text-slate-900 dark:hover:text-slate-200'"
                    @click="activeType = tab.value"
                >
                    {{ tab.label }}
                </button>
            </div>
        </header>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1280px] border-collapse">
                <thead>
                <tr class="border-b border-slate-200 bg-slate-50/80 dark:border-slate-800 dark:bg-slate-900/50">
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Название</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Статус</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Компания</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Создал</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Ответственный</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Срок выполнения</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Прогресс</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Дата создания</th>
                </tr>
                </thead>

                <tbody>
                <tr
                    v-for="item in rows"
                    :key="`${activeType}-${item.id}`"
                    class="cursor-pointer border-b border-slate-100 transition last:border-b-0 hover:bg-slate-50 dark:border-slate-900 dark:hover:bg-slate-900/60"
                    @click="openItem(item)"
                >
                    <td class="px-4 py-3">
                        <div class="max-w-[280px] truncate font-medium text-slate-900 dark:text-white" :title="item.title">
                            {{ item.title }}
                        </div>
                        <div class="mt-1 flex items-center gap-2 text-xs text-slate-500">
                            <span>{{ item.project_name }}</span>
                            <template v-if="activeType === 'subtasks'">
                                <span>•</span>
                                <span class="max-w-[180px] truncate" :title="item.parent_task_title">
                                        {{ item.parent_task_title }}
                                    </span>
                            </template>
                        </div>
                    </td>

                    <td class="px-4 py-3">
                            <span class="inline-flex rounded-md px-2.5 py-1 text-xs font-semibold" :class="getStatus(item).classes">
                                {{ getStatus(item).label }}
                            </span>
                    </td>

                    <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{{ item.company_name }}</td>
                    <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{{ getCreatorName(item) }}</td>
                    <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                        <div class="max-w-[220px] truncate" :title="getResponsibleNames(item)">
                            {{ getResponsibleNames(item) }}
                        </div>
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-500">{{ formatDate(item.due_date) }}</td>

                    <td class="px-4 py-3">
                        <div class="flex min-w-[145px] items-center gap-3">
                            <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                                <div class="h-full rounded-full bg-indigo-500 transition-all" :style="{ width: `${normalizedProgress(item)}%` }"></div>
                            </div>
                            <span class="w-9 text-right text-xs font-semibold text-slate-500">
                                    {{ normalizedProgress(item) }}%
                                </span>
                        </div>
                    </td>

                    <td class="px-4 py-3 text-sm text-slate-500">{{ formatDate(item.created_at, true) }}</td>
                </tr>

                <tr v-if="!rows.length">
                    <td colspan="8" class="px-6 py-16 text-center">
                        <div class="text-base font-medium text-slate-700 dark:text-slate-200">
                            {{ activeType === 'tasks' ? 'Задачи не найдены' : 'Подзадачи не найдены' }}
                        </div>
                        <p class="mt-1 text-sm text-slate-500">В этом разделе пока нет записей.</p>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
