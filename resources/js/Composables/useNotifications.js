import { reactive } from 'vue'
import axios from 'axios'
import { router } from '@inertiajs/vue3'

/*
 * Общее состояние уведомлений для всех страниц: счётчик, окно со списком,
 * всплывающие карточки в правом нижнем углу. Один экземпляр на приложение.
 */

const POLL_MS = 20000
const TOAST_MS = 7000
const MAX_TOASTS = 3

const state = reactive({
    unreadCount: 0,
    isOpen: false,
    toasts: [],
})

let started = false
let latestId = null
let timer = null
const toastTimers = new Map()

const poll = async () => {
    try {
        const { data } = await axios.get('/api/notifications/poll', {
            params: latestId ? { after_id: latestId } : {},
        })

        state.unreadCount = data.unread_count

        // Первый опрос только запоминает последний id — старое не всплывает.
        // Пока открыто окно со списком, всплывающие не нужны.
        if (latestId !== null && !state.isOpen) {
            [...data.items].reverse().forEach(showToast)
        }

        latestId = Math.max(latestId ?? 0, data.latest_id)
    } catch (e) {
        if (e.response?.status === 401) {
            clearInterval(timer)
            started = false
        }
    }
}

const dismissToast = id => {
    clearTimeout(toastTimers.get(id))
    toastTimers.delete(id)
    state.toasts = state.toasts.filter(t => t.id !== id)
}

const scheduleDismiss = id => {
    clearTimeout(toastTimers.get(id))
    toastTimers.set(id, setTimeout(() => dismissToast(id), TOAST_MS))
}

const showToast = item => {
    if (state.toasts.some(t => t.id === item.id)) return

    state.toasts.push(item)
    while (state.toasts.length > MAX_TOASTS) {
        dismissToast(state.toasts[0].id)
    }
    scheduleDismiss(item.id)
}

// Наведение на карточку останавливает её исчезновение
const holdToast = id => clearTimeout(toastTimers.get(id))
const releaseToast = id => scheduleDismiss(id)

const markRead = async item => {
    if (item.read_at) return
    item.read_at = new Date().toISOString()
    try {
        const { data } = await axios.post(`/api/notifications/${item.id}/read`)
        state.unreadCount = data.unread_count
    } catch (e) {
        item.read_at = null
    }
}

const openToast = async item => {
    dismissToast(item.id)
    await markRead(item)
    if (item.link) router.visit(item.link)
}

const open = () => {
    state.isOpen = true
    state.toasts.forEach(t => dismissToast(t.id))
}

const close = () => {
    state.isOpen = false
}

const setUnread = value => {
    state.unreadCount = value
}

const onServiceWorkerMessage = event => {
    if (event.data?.type === 'notifications:refresh') poll()
    if (event.data?.type === 'notifications:open' && event.data.url) {
        router.visit(new URL(event.data.url).pathname + new URL(event.data.url).search)
    }
}

const start = () => {
    if (started) return
    started = true

    poll()
    timer = setInterval(poll, POLL_MS)

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') poll()
    })

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.addEventListener('message', onServiceWorkerMessage)
        navigator.serviceWorker.register('/sw.js').catch(() => {})
    }
}

export function useNotifications() {
    return {
        state,
        start,
        poll,
        open,
        close,
        setUnread,
        markRead,
        openToast,
        dismissToast,
        holdToast,
        releaseToast,
    }
}
