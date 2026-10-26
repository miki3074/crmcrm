<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
    items: {
        type: Array,
        default: () => [],
    },
})

const KIND_LABELS = {
    task: 'Задача',
    subtask: 'Подзадача',
    klient_task: 'Задача клиента',
    deal: 'Сделка',
    media_plan: 'Медиаплан',
}

// Тип различается подписью, а не цветом — бейджи нейтральные
const KIND_BADGE = 'bg-zinc-100 text-zinc-600 dark:bg-white/[0.06] dark:text-zinc-300'

const KIND_COLORS = {
    task: KIND_BADGE,
    subtask: KIND_BADGE,
    klient_task: KIND_BADGE,
    deal: KIND_BADGE,
    media_plan: KIND_BADGE,
}

const dayDiff = value => {
    if (!value) {
        return null
    }

    const date = new Date(value)

    if (Number.isNaN(date.getTime())) {
        return null
    }

    const now = new Date()
    const todayMid = new Date(now.getFullYear(), now.getMonth(), now.getDate())
    const dueMid = new Date(date.getFullYear(), date.getMonth(), date.getDate())

    return Math.round((dueMid - todayMid) / 86400000)
}

const dueDiffLabel = item => {
    const diff = dayDiff(item.due_date)

    if (diff === null) {
        return null
    }

    if (diff === 0) {
        return 'сегодня'
    }

    return diff > 0 ? `+${diff} дн.` : `${diff} дн.`
}

const BOARD_COLUMN_DEFS = [
    { id: 'overdue', label: 'Просрочены', color: 'rose' },
    { id: 'today', label: 'На сегодня', color: 'lime' },
    { id: 'thisWeek', label: 'На этой неделе', color: 'cyan' },
    { id: 'nextWeek', label: 'На следующей неделе', color: 'teal' },
    { id: 'none', label: 'Без срока', color: 'zinc' },
    { id: 'later', label: 'Больше двух недель', color: 'indigo' },
    { id: 'done', label: 'Завершены', color: 'slate' },
]

// Цвет нижней линии заголовка колонки
const columnColors = {
    rose: 'border-rose-500',
    lime: 'border-amber-500',
    cyan: 'border-blue-600 dark:border-blue-500',
    teal: 'border-blue-300 dark:border-blue-800',
    zinc: 'border-zinc-300 dark:border-zinc-600',
    indigo: 'border-zinc-400 dark:border-zinc-500',
    slate: 'border-emerald-500',
}

const bucketFor = item => {
    if (Number(item.progress || 0) >= 100) {
        return 'done'
    }

    const diff = dayDiff(item.due_date)

    if (diff === null) {
        return 'none'
    }

    if (diff < 0) {
        return 'overdue'
    }

    if (diff === 0) {
        return 'today'
    }

    if (diff <= 7) {
        return 'thisWeek'
    }

    if (diff <= 14) {
        return 'nextWeek'
    }

    return 'later'
}

const boardColumns = computed(() => {
    const buckets = Object.fromEntries(
        BOARD_COLUMN_DEFS.map(def => [def.id, []]),
    )

    props.items.forEach(item => {
        buckets[bucketFor(item)].push(item)
    })

    return BOARD_COLUMN_DEFS.map(def => ({
        ...def,
        items: buckets[def.id],
    }))
})
</script>

<template>
    <div class="-mx-1 overflow-x-auto pb-1">
        <div class="flex min-w-max gap-3 px-1">
            <div v-for="col in boardColumns" :key="col.id" class="w-60 shrink-0">
                <div
                    class="mb-2 flex items-center justify-between gap-2 border-b-2 px-1 pb-2 text-xs font-semibold text-zinc-700 dark:text-zinc-200"
                    :class="columnColors[col.color]"
                >
                    <span class="truncate">{{ col.label }}</span>
                    <span class="ml-2 shrink-0 rounded bg-zinc-100 px-1.5 py-0.5 text-[11px] font-medium tabular-nums text-zinc-600 dark:bg-white/[0.06] dark:text-zinc-300">
                        {{ col.items.length }}
                    </span>
                </div>

                <div class="space-y-2">
                    <button
                        v-for="item in col.items"
                        :key="item.id"
                        type="button"
                        class="group block w-full rounded-md border border-zinc-200 bg-white p-3 text-left transition-colors
                               hover:border-zinc-300 hover:bg-zinc-50
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60
                               dark:border-white/[0.08] dark:bg-zinc-900 dark:hover:border-white/15 dark:hover:bg-white/[0.03]"
                        @click="router.visit(item.link)"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <span
                                class="shrink-0 rounded px-1.5 py-0.5 text-[11px] font-medium"
                                :class="KIND_COLORS[item.kind]"
                            >
                                {{ KIND_LABELS[item.kind] }}
                            </span>

                            <span class="truncate text-[11px] text-zinc-500 dark:text-zinc-400">
                                {{ item.project }}
                            </span>
                        </div>

                        <h4 class="mt-2 line-clamp-2 text-sm font-medium text-zinc-900 group-hover:text-blue-700 dark:text-zinc-100 dark:group-hover:text-blue-400">
                            {{ item.title }}
                        </h4>

                        <div class="mt-2.5 flex items-center justify-between">
                            <span
                                v-if="dueDiffLabel(item)"
                                class="rounded px-1.5 py-0.5 text-[11px] font-medium tabular-nums"
                                :class="
                                    col.id === 'overdue'
                                        ? 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300'
                                        : 'bg-zinc-100 text-zinc-600 dark:bg-white/[0.06] dark:text-zinc-400'
                                "
                            >
                                {{ dueDiffLabel(item) }}
                            </span>
                            <span v-else />

                            <span class="text-[11px] font-medium tabular-nums text-zinc-500 dark:text-zinc-400">
                                {{ Number(item.progress || 0) }}%
                            </span>
                        </div>
                    </button>

                    <p
                        v-if="!col.items.length"
                        class="rounded-md border border-dashed border-zinc-200 py-4 text-center text-xs text-zinc-500 dark:border-white/10 dark:text-zinc-400"
                    >
                        Пусто
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
