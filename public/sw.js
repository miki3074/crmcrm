/*
 * Service worker: push-уведомления на ПК и телефоне.
 *
 * Если сайт открыт и активен — системное уведомление не показываем,
 * а просим страницу сразу обновить список (она покажет своё всплывающее окно).
 */

self.addEventListener('install', () => self.skipWaiting())
self.addEventListener('activate', event => event.waitUntil(self.clients.claim()))

self.addEventListener('push', event => {
    let data = {}
    try {
        data = event.data ? event.data.json() : {}
    } catch (e) {
        data = { title: 'Уведомление', body: event.data ? event.data.text() : '' }
    }

    event.waitUntil((async () => {
        const windows = await self.clients.matchAll({ type: 'window', includeUncontrolled: true })
        const activeWindow = windows.find(client => client.visibilityState === 'visible' && client.focused)

        if (activeWindow) {
            windows.forEach(client => client.postMessage({ type: 'notifications:refresh' }))
            return
        }

        await self.registration.showNotification(data.title || 'Уведомление', {
            body: data.body || '',
            icon: data.icon || '/icons/icon-192.png',
            badge: '/icons/icon-192.png',
            tag: data.tag || undefined,
            renotify: Boolean(data.tag),
            data: { url: data.url || '/dashboard' },
        })
    })())
})

self.addEventListener('notificationclick', event => {
    event.notification.close()
    const url = new URL(event.notification.data?.url || '/dashboard', self.location.origin).href

    event.waitUntil((async () => {
        const windows = await self.clients.matchAll({ type: 'window', includeUncontrolled: true })

        // Уже открытая вкладка сайта — переводим её на нужную страницу
        for (const client of windows) {
            if (new URL(client.url).origin === self.location.origin && 'focus' in client) {
                await client.focus()
                client.postMessage({ type: 'notifications:open', url })
                return
            }
        }

        await self.clients.openWindow(url)
    })())
})
