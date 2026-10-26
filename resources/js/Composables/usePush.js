import { computed, reactive } from 'vue'
import axios from 'axios'
import { usePage } from '@inertiajs/vue3'

/*
 * Push-уведомления на этом устройстве (Web Push).
 *
 * ПК: Chrome, Edge, Firefox, Safari — уведомления системы (Windows/macOS).
 * Android: Chrome — сразу.
 * iPhone/iPad: iOS 16.4+, только после «Поделиться → На экран Домой»
 *              и запуска сайта с иконки на экране.
 */

const state = reactive({
    permission: typeof Notification !== 'undefined' ? Notification.permission : 'unsupported',
    subscribed: false,
    busy: false,
    error: '',
})

const isIos = () => /iphone|ipad|ipod/i.test(navigator.userAgent)
    || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1)

const isStandalone = () => window.matchMedia('(display-mode: standalone)').matches
    || window.navigator.standalone === true

const isSupported = () => 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window

const urlBase64ToUint8Array = base64 => {
    const padding = '='.repeat((4 - (base64.length % 4)) % 4)
    const raw = atob((base64 + padding).replace(/-/g, '+').replace(/_/g, '/'))
    return Uint8Array.from([...raw].map(c => c.charCodeAt(0)))
}

const getRegistration = async () => {
    await navigator.serviceWorker.register('/sw.js')
    return navigator.serviceWorker.ready
}

const refresh = async () => {
    if (!isSupported()) return
    state.permission = Notification.permission
    const registration = await getRegistration()
    state.subscribed = Boolean(await registration.pushManager.getSubscription())
}

const subscribe = async vapidPublicKey => {
    state.error = ''
    if (!vapidPublicKey) {
        state.error = 'На сервере не заданы ключи push-уведомлений.'
        return false
    }

    state.busy = true
    try {
        const permission = await Notification.requestPermission()
        state.permission = permission
        if (permission !== 'granted') {
            state.error = 'Уведомления запрещены в настройках браузера для этого сайта.'
            return false
        }

        const registration = await getRegistration()
        const subscription = await registration.pushManager.getSubscription()
            || await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
            })

        const json = subscription.toJSON()
        await axios.post('/api/push-subscriptions', {
            endpoint: json.endpoint,
            keys: json.keys,
            contentEncoding: (PushManager.supportedContentEncodings || ['aes128gcm'])[0],
        })

        state.subscribed = true
        return true
    } catch (e) {
        state.error = e.response?.data?.message || 'Не удалось включить push на этом устройстве.'
        return false
    } finally {
        state.busy = false
    }
}

const unsubscribe = async () => {
    state.error = ''
    state.busy = true
    try {
        const registration = await getRegistration()
        const subscription = await registration.pushManager.getSubscription()
        if (subscription) {
            await axios.delete('/api/push-subscriptions', { data: { endpoint: subscription.endpoint } })
            await subscription.unsubscribe()
        }
        state.subscribed = false
    } catch (e) {
        state.error = 'Не удалось отключить push.'
    } finally {
        state.busy = false
    }
}

const sendTest = async () => {
    state.error = ''
    try {
        await axios.post('/api/push-subscriptions/test')
        return true
    } catch (e) {
        state.error = e.response?.data?.message || 'Тестовое уведомление не отправлено.'
        return false
    }
}

export function usePush() {
    const page = usePage()

    return {
        state,
        supported: isSupported(),
        isIos: isIos(),
        isStandalone: isStandalone(),
        // На iPhone push работает только из установленного на экран «Домой» сайта
        needsIosInstall: computed(() => isIos() && !isStandalone()),
        refresh,
        subscribe: () => subscribe(page.props.vapidPublicKey),
        unsubscribe,
        sendTest,
    }
}
