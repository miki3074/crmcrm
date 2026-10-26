<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import { Link } from '@inertiajs/vue3'
import ConfirmDialog from '@/Pages/AAA/Components/Task/ConfirmDialog.vue'

const emit = defineEmits(['close', 'update:unread'])

const CATEGORIES = [
    { id: 'all', label: 'Все' },
    { id: 'task', label: 'Задачи' },
    { id: 'subtask', label: 'Подзадачи' },
    { id: 'chat', label: 'Чат' },
    { id: 'meeting', label: 'Совещания' },
    { id: 'klient', label: 'Клиенты' },
]

const CATEGORY_LABELS = {
    task: 'Задача',
    subtask: 'Подзадача',
    chat: 'Чат',
    meeting: 'Совещание',
    klient: 'Клиенты',
    system: 'Система',
}

// Типы, которые стоит выделить цветом; остальные — нейтральные
const TYPE_TONES = {
    task_deleted: 'danger',
    subtask_deleted: 'danger',
    member_removed: 'danger',
    file_rejected: 'warning',
    agenda_remark: 'warning',
    file_review_assigned: 'accent',
    mentioned: 'accent',
    task_completed: 'success',
    subtask_completed: 'success',
    // Клиенты
    klient_access_revoked: 'danger',
    klient_deal_unassigned: 'danger',
    klient_task_unassigned: 'danger',
    klient_task_cancelled: 'danger',
    media_plan_deleted: 'danger',
    klient_task_overdue: 'danger',
    klient_deal_overdue: 'danger',
    klient_task_due_today: 'warning',
    klient_deal_due_soon: 'warning',
    klient_access_granted: 'accent',
    klient_deal_assigned: 'accent',
    klient_task_assigned: 'accent',
    media_plan_item_assigned: 'accent',
    klient_task_completed: 'success',
}

const TONE_CLASSES = {
    neutral: 'bg-zinc-100 text-zinc-600 dark:bg-white/[0.06] dark:text-zinc-300',
    accent: 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300',
    success: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
    warning: 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
    danger: 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300',
}

const items = ref([])
const loading = ref(true)
const loadingMore = ref(false)
const hasMore = ref(false)
const unreadCount = ref(0)
const category = ref('all')
const onlyUnread = ref(false)
const showClearConfirm = ref(false)

const setUnread = value => {
    unreadCount.value = value
    emit('update:unread', value)
}

const fetchPage = async (beforeId = null) => {
    const params = {}
    if (category.value !== 'all') params.category = category.value
    if (onlyUnread.value) params.unread = 1
    if (beforeId) params.before_id = beforeId

    const { data } = await axios.get('/api/notifications', { params })
    hasMore.value = data.has_more
    setUnread(data.unread_count)

    return data.data
}

const load = async () => {
    loading.value = true
    try {
        items.value = await fetchPage()
    } catch (e) {
        console.error('Ошибка загрузки уведомлений', e)
    } finally {
        loading.value = false
    }
}

const loadMore = async () => {
    if (!items.value.length) return
    loadingMore.value = true
    try {
        const next = await fetchPage(items.value[items.value.length - 1].id)
        items.value.push(...next)
    } catch (e) {
        console.error('Ошибка загрузки уведомлений', e)
    } finally {
        loadingMore.value = false
    }
}

watch([category, onlyUnread], load)

const open = async item => {
    if (!item.read_at) {
        item.read_at = new Date().toISOString()
        try {
            const { data } = await axios.post(`/api/notifications/${item.id}/read`)
            setUnread(data.unread_count)
        } catch (e) {
            item.read_at = null
        }
    }

    if (item.link) {
        emit('close')
        router.visit(item.link)
    }
}

const markAllRead = async () => {
    const previous = items.value.map(i => i.read_at)
    const now = new Date().toISOString()
    items.value.forEach(i => { i.read_at = i.read_at || now })
    try {
        const { data } = await axios.post('/api/notifications/read-all')
        setUnread(data.unread_count)
    } catch (e) {
        items.value.forEach((i, idx) => { i.read_at = previous[idx] })
    }
}

const remove = async item => {
    const previous = items.value
    items.value = items.value.filter(i => i.id !== item.id)
    try {
        const { data } = await axios.delete(`/api/notifications/${item.id}`)
        setUnread(data.unread_count)
    } catch (e) {
        items.value = previous
    }
}

const clearAll = async () => {
    showClearConfirm.value = false
    const previous = items.value
    items.value = []
    hasMore.value = false
    try {
        const { data } = await axios.delete('/api/notifications')
        setUnread(data.unread_count)
    } catch (e) {
        items.value = previous
    }
}

const dayKey = value => {
    const date = new Date(value)
    return new Date(date.getFullYear(), date.getMonth(), date.getDate()).getTime()
}

const dayLabel = key => {
    const now = new Date()
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate()).getTime()
    const diff = Math.round((today - key) / 86400000)

    if (diff === 0) return 'Сегодня'
    if (diff === 1) return 'Вчера'

    return new Intl.DateTimeFormat('ru-RU', { day: 'numeric', month: 'long', year: 'numeric' }).format(new Date(key))
}

const groups = computed(() => {
    const result = []
    items.value.forEach(item => {
        const key = dayKey(item.created_at)
        let group = result[result.length - 1]
        if (!group || group.key !== key) {
            group = { key, label: dayLabel(key), items: [] }
            result.push(group)
        }
        group.items.push(item)
    })
    return result
})

const formatTime = value => {
    const date = new Date(value)
    if (Number.isNaN(date.getTime())) return ''

    const diffMin = Math.floor((Date.now() - date) / 60000)
    if (diffMin < 1) return 'только что'
    if (diffMin < 60) return `${diffMin} мин. назад`

    return new Intl.DateTimeFormat('ru-RU', { hour: '2-digit', minute: '2-digit' }).format(date)
}

const toneFor = item => TONE_CLASSES[TYPE_TONES[item.type] || 'neutral']

const handleKeydown = event => {
    if (event.key === 'Escape' && !showClearConfirm.value) emit('close')
}

onMounted(() => {
    load()
    window.addEventListener('keydown', handleKeydown)
})

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeydown)
})
</script>

<template>
    <Teleport to="body">
        <div
            class="fixed inset-0 z-[110] flex justify-center bg-zinc-950/50 px-3 py-4 backdrop-blur-[2px] sm:py-12"
            @click.self="emit('close')"
        >
            <div
                class="flex max-h-full w-full max-w-2xl flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-xl dark:border-white/10 dark:bg-zinc-900"
                role="dialog"
                aria-modal="true"
                aria-labelledby="notifications-title"
            >
                <!-- Шапка -->
                <header class="flex items-center justify-between gap-3 border-b border-zinc-200 px-5 py-4 dark:border-white/[0.06]">
                    <div class="min-w-0">
                        <h2 id="notifications-title" class="text-base font-semibold text-zinc-900 dark:text-zinc-50">Уведомления</h2>
                        <p class="text-xs tabular-nums text-zinc-500 dark:text-zinc-400">
                            Непрочитанных: {{ unreadCount }}
                        </p>
                    </div>

                    <div class="flex shrink-0 items-center gap-1">
                        <button
                            v-if="unreadCount > 0"
                            type="button"
                            class="rounded-md px-2 py-1 text-xs font-medium text-blue-700 transition-colors hover:bg-blue-50
                                   focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60 dark:text-blue-400 dark:hover:bg-blue-500/10"
                            @click="markAllRead"
                        >
                            Прочитать все
                        </button>

                        <Link
                            href="/notifications/settings"
                            aria-label="Настройки уведомлений"
                            title="Настройки уведомлений"
                            class="flex h-8 w-8 items-center justify-center rounded-md text-zinc-500 transition-colors hover:bg-zinc-100 hover:text-zinc-900
                                   focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60 dark:text-zinc-400 dark:hover:bg-white/[0.06] dark:hover:text-zinc-100"
                            @click="emit('close')"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </Link>

                        <button
                            type="button"
                            aria-label="Закрыть"
                            class="flex h-8 w-8 items-center justify-center rounded-md text-zinc-500 transition-colors hover:bg-zinc-100 hover:text-zinc-900
                                   focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60 dark:text-zinc-400 dark:hover:bg-white/[0.06] dark:hover:text-zinc-100"
                            @click="emit('close')"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </header>

                <!-- Фильтры -->
                <div class="flex flex-wrap items-center gap-2 border-b border-zinc-200 px-5 py-3 dark:border-white/[0.06]">
                    <div class="flex flex-1 flex-wrap gap-1 rounded-md bg-zinc-100 p-1 dark:bg-white/[0.04]">
                        <button
                            v-for="cat in CATEGORIES"
                            :key="cat.id"
                            type="button"
                            class="flex-1 whitespace-nowrap rounded px-2.5 py-1 text-xs font-medium transition-colors
                                   focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60"
                            :class="
                                category === cat.id
                                    ? 'bg-white text-zinc-900 shadow-sm ring-1 ring-zinc-200 dark:bg-zinc-800 dark:text-zinc-50 dark:ring-white/10'
                                    : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-200'
                            "
                            @click="category = cat.id"
                        >
                            {{ cat.label }}
                        </button>
                    </div>

                    <label class="flex cursor-pointer select-none items-center gap-2 text-xs font-medium text-zinc-600 dark:text-zinc-400">
                        <input
                            v-model="onlyUnread"
                            type="checkbox"
                            class="h-4 w-4 rounded border-zinc-300 text-blue-600 focus:ring-2 focus:ring-blue-500/40 dark:border-white/20 dark:bg-zinc-950"
                        />
                        Только непрочитанные
                    </label>
                </div>

                <!-- Список -->
                <main class="min-h-0 flex-1 overflow-y-auto">
                    <div v-if="loading" class="space-y-px p-2" aria-hidden="true">
                        <div v-for="n in 5" :key="n" class="flex gap-3 rounded-md px-3 py-3">
                            <div class="h-8 w-8 shrink-0 animate-pulse rounded-md bg-zinc-100 dark:bg-white/[0.06]" />
                            <div class="flex-1 space-y-2 pt-1">
                                <div class="h-3 w-1/3 animate-pulse rounded bg-zinc-100 dark:bg-white/[0.06]" />
                                <div class="h-3 w-2/3 animate-pulse rounded bg-zinc-100 dark:bg-white/[0.06]" />
                            </div>
                        </div>
                    </div>

                    <div v-else-if="!items.length" class="flex min-h-64 flex-col items-center justify-center px-6 py-12 text-center">
                        <div class="flex h-10 w-10 items-center justify-center rounded-md border border-zinc-200 text-zinc-500 dark:border-white/10 dark:text-zinc-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </div>
                        <p class="mt-4 text-sm font-semibold text-zinc-900 dark:text-zinc-100">Уведомлений нет</p>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ onlyUnread ? 'Все уведомления прочитаны.' : 'Здесь появятся события по вашим задачам, клиентам, чатам и совещаниям.' }}
                        </p>
                    </div>

                    <div v-else class="pb-2">
                        <section v-for="group in groups" :key="group.key">
                            <h3 class="sticky top-0 z-10 border-b border-zinc-100 bg-white/95 px-5 py-2 text-[11px] font-medium uppercase tracking-wider text-zinc-500 backdrop-blur dark:border-white/[0.04] dark:bg-zinc-900/95 dark:text-zinc-400">
                                {{ group.label }}
                            </h3>

                            <div class="divide-y divide-zinc-100 dark:divide-white/[0.06]">
                                <div
                                    v-for="item in group.items"
                                    :key="item.id"
                                    role="button"
                                    tabindex="0"
                                    class="group relative flex cursor-pointer items-start gap-3 px-5 py-3 text-left transition-colors
                                           hover:bg-zinc-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-blue-500/60
                                           dark:hover:bg-white/[0.03]"
                                    :class="!item.read_at ? 'bg-blue-50/40 dark:bg-blue-500/[0.04]' : ''"
                                    @click="open(item)"
                                    @keydown.enter="open(item)"
                                    @keydown.space.prevent="open(item)"
                                >
                                    <span
                                        v-if="!item.read_at"
                                        class="absolute left-2 top-1/2 h-1.5 w-1.5 -translate-y-1/2 rounded-full bg-blue-600 dark:bg-blue-400"
                                        aria-label="Не прочитано"
                                    />

                                    <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-md" :class="toneFor(item)">
                                        <svg v-if="item.category === 'chat'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                        </svg>
                                        <svg v-else-if="item.category === 'meeting'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 7V3m8 4V3M5 11h14M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z" />
                                        </svg>
                                        <svg v-else-if="item.category === 'klient'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                        <svg v-else-if="item.category === 'subtask'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 6h11M9 12h11M9 18h11M5 6h.01M5 12h.01M5 18h.01" />
                                        </svg>
                                        <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </span>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-baseline gap-2">
                                            <p
                                                class="min-w-0 flex-1 truncate text-sm"
                                                :class="item.read_at ? 'font-medium text-zinc-700 dark:text-zinc-300' : 'font-semibold text-zinc-900 dark:text-zinc-50'"
                                            >
                                                {{ item.title || CATEGORY_LABELS[item.category] }}
                                            </p>
                                            <span class="shrink-0 text-[11px] tabular-nums text-zinc-500 dark:text-zinc-400">
                                                {{ formatTime(item.created_at) }}
                                            </span>
                                        </div>

                                        <p class="mt-0.5 line-clamp-2 text-sm text-zinc-600 dark:text-zinc-400">
                                            {{ item.message }}
                                        </p>

                                        <p class="mt-1 text-[11px] text-zinc-500 dark:text-zinc-500">
                                            {{ CATEGORY_LABELS[item.category] }}<template v-if="item.actor"> · {{ item.actor.name }}</template>
                                        </p>
                                    </div>

                                    <button
                                        type="button"
                                        aria-label="Удалить уведомление"
                                        title="Удалить уведомление"
                                        class="shrink-0 rounded p-1 text-zinc-400 opacity-0 transition hover:bg-rose-50 hover:text-rose-600
                                               group-hover:opacity-100 group-focus-visible:opacity-100 focus:opacity-100
                                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-500/60
                                               dark:text-zinc-500 dark:hover:bg-rose-500/10 dark:hover:text-rose-400"
                                        @click.stop="remove(item)"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </section>

                        <div v-if="hasMore" class="flex justify-center pt-3">
                            <button
                                type="button"
                                :disabled="loadingMore"
                                class="rounded-md border border-zinc-300 px-3 py-1.5 text-xs font-medium text-zinc-700 transition-colors hover:bg-zinc-50
                                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60 disabled:opacity-50
                                       dark:border-white/10 dark:text-zinc-300 dark:hover:bg-white/[0.04]"
                                @click="loadMore"
                            >
                                {{ loadingMore ? 'Загрузка...' : 'Показать ещё' }}
                            </button>
                        </div>
                    </div>
                </main>

                <!-- Подвал -->
                <footer
                    v-if="items.length"
                    class="flex justify-end border-t border-zinc-200 bg-zinc-50 px-5 py-3 dark:border-white/[0.06] dark:bg-white/[0.02]"
                >
                    <button
                        type="button"
                        class="rounded-md px-2 py-1 text-xs font-medium text-zinc-500 transition-colors hover:bg-rose-50 hover:text-rose-700
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60 dark:text-zinc-400 dark:hover:bg-rose-500/10 dark:hover:text-rose-300"
                        @click="showClearConfirm = true"
                    >
                        Очистить всё
                    </button>
                </footer>
            </div>
        </div>
    </Teleport>

    <ConfirmDialog
        :show="showClearConfirm"
        title="Удалить все уведомления?"
        message="Все уведомления будут удалены без возможности восстановления."
        confirm-text="Удалить"
        @confirm="clearAll"
        @close="showClearConfirm = false"
    />
</template>
