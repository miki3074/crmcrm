<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import ConfirmDialog from '../Task/ConfirmDialog.vue'

const props = defineProps({
    activities: { type: Array, default: () => [] },
})

const emit = defineEmits(['remove', 'clear'])

const LIMIT = 5
const showAll = ref(false)
const showClearConfirm = ref(false)

const visibleActivities = computed(() => {
    return showAll.value ? props.activities : props.activities.slice(0, LIMIT)
})

const hiddenCount = computed(() => {
    return Math.max(props.activities.length - LIMIT, 0)
})

const TYPE_META = {
    file_added: { color: 'violet', icon: 'file' },
    file_review_assigned: { color: 'amber', icon: 'check-circle' },
    result_added: { color: 'emerald', icon: 'check-circle' },
    subtask_created: { color: 'cyan', icon: 'list' },
    checklist_item_added: { color: 'cyan', icon: 'checklist' },
    mentioned: { color: 'rose', icon: 'at' },
    assigned_executor: { color: 'indigo', icon: 'user-plus' },
    assigned_responsible: { color: 'indigo', icon: 'user-plus' },
    assigned_watcher: { color: 'indigo', icon: 'user-plus' },
    title_changed: { color: 'zinc', icon: 'pencil' },
    description_changed: { color: 'zinc', icon: 'pencil' },
}

const metaFor = type => TYPE_META[type] || { color: 'zinc', icon: 'bell' }

const COLOR_CLASSES = {
    violet: 'bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-300',
    amber: 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300',
    emerald: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300',
    cyan: 'bg-cyan-50 text-cyan-600 dark:bg-cyan-500/10 dark:text-cyan-300',
    rose: 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-300',
    indigo: 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-300',
    zinc: 'bg-zinc-100 text-zinc-500 dark:bg-white/5 dark:text-zinc-400',
}

const formatDate = value => {
    if (!value) return ''
    const date = new Date(value)
    if (Number.isNaN(date.getTime())) return value

    const now = new Date()
    const diffMs = now - date
    const diffMin = Math.floor(diffMs / 60000)

    if (diffMin < 1) return 'только что'
    if (diffMin < 60) return `${diffMin} мин. назад`
    if (diffMin < 24 * 60) return `${Math.floor(diffMin / 60)} ч. назад`

    return new Intl.DateTimeFormat('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }).format(date)
}

const openTask = activity => {
    if (activity.task?.id) {
        router.visit(`/tasks/${activity.task.id}`)
    }
}

const confirmClearAll = () => {
    showClearConfirm.value = false
    emit('clear')
}
</script>

<template>
    <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-white/5 dark:bg-zinc-900/60">
        <header class="flex items-center justify-between border-b border-zinc-100 px-4 py-3 dark:border-white/5">
            <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-cyan-100 text-cyan-600 dark:bg-cyan-500/10 dark:text-cyan-300">
                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </span>
                <div>
                    <h2 class="text-sm font-bold text-zinc-900 dark:text-white">События</h2>
                    <p class="text-[11px] text-zinc-400">Что произошло в ваших задачах</p>
                </div>
            </div>

            <button
                v-if="activities.length"
                type="button"
                class="rounded-lg px-2.5 py-1.5 text-xs font-bold text-zinc-400 transition hover:bg-rose-50 hover:text-rose-600
                       focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400 dark:hover:bg-rose-500/10"
                @click="showClearConfirm = true"
            >
                Очистить всё
            </button>
        </header>

        <div v-if="visibleActivities.length" class="divide-y divide-zinc-100 dark:divide-white/5">
            <div
                v-for="activity in visibleActivities"
                :key="activity.id"
                role="button"
                tabindex="0"
                class="group flex w-full cursor-pointer items-start gap-3 px-4 py-3 text-left transition hover:bg-zinc-50 dark:hover:bg-white/5
                       focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400 focus-visible:ring-inset"
                @click="openTask(activity)"
                @keydown.enter="openTask(activity)"
                @keydown.space.prevent="openTask(activity)"
            >
                <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg" :class="COLOR_CLASSES[metaFor(activity.type).color]">
                    <svg v-if="metaFor(activity.type).icon === 'file'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828L18 9.828a4 4 0 00-5.656-5.656L5.757 10.76a6 6 0 108.486 8.486L20.5 13" />
                    </svg>
                    <svg v-else-if="metaFor(activity.type).icon === 'check-circle'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <svg v-else-if="metaFor(activity.type).icon === 'list'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <svg v-else-if="metaFor(activity.type).icon === 'checklist'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    <svg v-else-if="metaFor(activity.type).icon === 'at'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-3.5 7.125" />
                    </svg>
                    <svg v-else-if="metaFor(activity.type).icon === 'user-plus'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <svg v-else-if="metaFor(activity.type).icon === 'pencil'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </span>

                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-bold text-zinc-800 dark:text-white">{{ activity.task?.title || 'Задача' }}</p>
                    <p class="mt-0.5 truncate text-xs text-zinc-500 dark:text-zinc-400">{{ activity.message }}</p>
                </div>

                <span class="flex shrink-0 items-center gap-1.5">
                    <span class="text-[10px] font-semibold text-zinc-400">{{ formatDate(activity.created_at) }}</span>
                    <button
                        type="button"
                        aria-label="Удалить событие"
                        title="Удалить событие"
                        class="rounded p-1 text-zinc-300 opacity-0 transition hover:bg-rose-50 hover:text-rose-500 group-hover:opacity-100 group-focus-visible:opacity-100 focus:opacity-100
                               focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400 dark:hover:bg-rose-500/10"
                        @click.stop="emit('remove', activity.id)"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </span>
            </div>

            <div v-if="hiddenCount > 0 || showAll" class="flex justify-center py-2">
                <button
                    type="button"
                    class="flex items-center gap-1 rounded-lg px-4 py-1.5 text-xs font-medium text-zinc-500 transition hover:bg-zinc-50 hover:text-cyan-600
                           focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400 dark:hover:bg-white/5"
                    @click="showAll = !showAll"
                >
                    <span v-if="!showAll">Ещё {{ hiddenCount }}</span>
                    <span v-else>Свернуть</span>
                    <svg :class="{ 'rotate-180': showAll }" class="h-3 w-3 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
            </div>
        </div>

        <div v-else class="px-4 py-8 text-center text-sm text-zinc-400">
            Событий пока нет
        </div>
    </section>

    <ConfirmDialog
        :show="showClearConfirm"
        title="Очистить все события?"
        message="Лента событий будет очищена без возможности восстановления."
        confirm-text="Очистить"
        @confirm="confirmClearAll"
        @close="showClearConfirm = false"
    />
</template>
