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

const KIND_COLORS = {
    task: 'bg-cyan-100 text-cyan-600 dark:bg-cyan-500/10 dark:text-cyan-300',
    subtask: 'bg-violet-100 text-violet-600 dark:bg-violet-500/10 dark:text-violet-300',
    klient_task: 'bg-amber-100 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300',
    deal: 'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300',
    media_plan: 'bg-indigo-100 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-300',
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

const columnColors = {
    rose: 'bg-rose-500',
    lime: 'bg-lime-500',
    cyan: 'bg-cyan-500',
    teal: 'bg-teal-500',
    zinc: 'bg-zinc-400',
    indigo: 'bg-indigo-500',
    slate: 'bg-slate-500',
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
            <div v-for="col in boardColumns" :key="col.id" class="w-56 shrink-0">
                <div
                    class="mb-2 flex items-center justify-between rounded-lg px-3 py-1.5 text-xs font-bold text-white"
                    :class="columnColors[col.color]"
                >
                    <span class="truncate">{{ col.label }}</span>
                    <span class="ml-2 shrink-0 rounded-full bg-white/25 px-1.5 py-0.5 text-[10px]">
                        {{ col.items.length }}
                    </span>
                </div>

                <div class="space-y-2">
                    <button
                        v-for="item in col.items"
                        :key="item.id"
                        type="button"
                        class="group block w-full rounded-xl border border-zinc-100 bg-zinc-50/60 p-3 text-left transition
                               hover:border-cyan-300 hover:bg-white hover:shadow-sm
                               dark:border-white/5 dark:bg-white/[0.03]"
                        @click="router.visit(item.link)"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <span
                                class="shrink-0 rounded-md px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide"
                                :class="KIND_COLORS[item.kind]"
                            >
                                {{ KIND_LABELS[item.kind] }}
                            </span>

                            <span class="truncate text-[10px] font-bold uppercase tracking-wide text-zinc-400">
                                {{ item.project }}
                            </span>
                        </div>

                        <h4 class="mt-1.5 line-clamp-2 text-sm font-semibold text-zinc-700 group-hover:text-cyan-600 dark:text-zinc-300">
                            {{ item.title }}
                        </h4>

                        <div class="mt-2 flex items-center justify-between">
                            <span
                                v-if="dueDiffLabel(item)"
                                class="rounded-md px-1.5 py-0.5 text-[10px] font-bold"
                                :class="
                                    col.id === 'overdue'
                                        ? 'bg-rose-100 text-rose-600 dark:bg-rose-500/10 dark:text-rose-300'
                                        : 'bg-zinc-100 text-zinc-500 dark:bg-white/5 dark:text-zinc-400'
                                "
                            >
                                {{ dueDiffLabel(item) }}
                            </span>
                            <span v-else />

                            <span class="text-[10px] font-black text-zinc-400">
                                {{ Number(item.progress || 0) }}%
                            </span>
                        </div>
                    </button>

                    <p
                        v-if="!col.items.length"
                        class="rounded-lg border border-dashed border-zinc-200 py-4 text-center text-[11px] text-zinc-400 dark:border-white/10"
                    >
                        Пусто
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
