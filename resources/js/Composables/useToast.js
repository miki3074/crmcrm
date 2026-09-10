import { reactive } from 'vue'

const toasts = reactive([])
let idCounter = 0

function remove(id) {
    const index = toasts.findIndex(t => t.id === id)
    if (index !== -1) toasts.splice(index, 1)
}

function push(type, message, timeout = 4000) {
    const id = ++idCounter
    toasts.push({ id, type, message })
    if (timeout) {
        setTimeout(() => remove(id), timeout)
    }
    return id
}

export function useToast() {
    return {
        toasts,
        success: (message, timeout) => push('success', message, timeout),
        error: (message, timeout) => push('error', message, timeout),
        info: (message, timeout) => push('info', message, timeout),
        remove,
    }
}
