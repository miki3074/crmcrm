<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import axios from 'axios'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { usePush } from '@/Composables/usePush'

const props = defineProps({
    groups: { type: Array, required: true },
    channels: { type: Array, required: true },
    matrix: { type: Object, required: true },
    telegramLinked: { type: Boolean, default: false },
    push: { type: Object, required: true },
})

const matrix = reactive(JSON.parse(JSON.stringify(props.matrix)))
const saveState = ref('idle') // idle | saving | saved | error
const testSent = ref(false)

const device = usePush()
const pushServerReady = computed(() => props.push.configured && props.push.serverReady)

const channelDisabled = channel => channel === 'telegram' && !props.telegramLinked

// Сохраняем изменения пачкой, с небольшой задержкой после последнего клика
let pending = []
let saveTimer = null

const queueSave = changes => {
    pending.push(...changes)
    saveState.value = 'saving'
    clearTimeout(saveTimer)
    saveTimer = setTimeout(save, 500)
}

const save = async () => {
    const preferences = pending
    pending = []
    try {
        await axios.put('/api/notifications/preferences', { preferences })
        saveState.value = 'saved'
    } catch (e) {
        saveState.value = 'error'
    }
}

const toggle = (type, channel) => {
    matrix[type][channel] = !matrix[type][channel]
    queueSave([{ type, channel, enabled: matrix[type][channel] }])
}

const columnState = (group, channel) => {
    const values = group.types.map(t => matrix[t.type][channel])
    if (values.every(Boolean)) return 'all'
    if (values.some(Boolean)) return 'some'
    return 'none'
}

const toggleColumn = (group, channel) => {
    const enabled = columnState(group, channel) !== 'all'
    const changes = group.types.map(t => {
        matrix[t.type][channel] = enabled
        return { type: t.type, channel, enabled }
    })
    queueSave(changes)
}

const enablePush = async () => {
    testSent.value = false
    if (await device.subscribe()) router.reload({ only: ['push'] })
}

const disablePush = async () => {
    testSent.value = false
    await device.unsubscribe()
    router.reload({ only: ['push'] })
}

const sendTest = async () => {
    testSent.value = await device.sendTest()
}

onMounted(() => {
    device.refresh()
})
</script>

<template>
    <Head title="Настройки уведомлений" />

    <AuthenticatedLayout>
        <div class="min-h-full bg-zinc-50 dark:bg-[#0a0b0f]">
            <div class="mx-auto max-w-5xl space-y-6 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">

                <header class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Профиль</p>
                        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-50">Уведомления</h1>
                    </div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400" aria-live="polite">
                        <template v-if="saveState === 'saving'">Сохранение…</template>
                        <template v-else-if="saveState === 'saved'">Изменения сохранены</template>
                        <span v-else-if="saveState === 'error'" class="text-rose-600 dark:text-rose-400">Не удалось сохранить</span>
                    </p>
                </header>

                <!-- Push на этом устройстве -->
                <section class="rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-white/[0.08] dark:bg-zinc-900 dark:shadow-none">
                    <div class="flex flex-wrap items-start justify-between gap-4 border-b border-zinc-200 px-5 py-4 dark:border-white/[0.06]">
                        <div class="min-w-0">
                            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Push-уведомления на этом устройстве</h2>
                            <p class="mt-0.5 text-sm text-zinc-600 dark:text-zinc-400">
                                Системные уведомления на компьютере и телефоне — даже когда сайт закрыт.
                            </p>
                        </div>

                        <span
                            class="shrink-0 rounded-md border px-2 py-1 text-xs font-medium"
                            :class="device.state.subscribed
                                ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300'
                                : 'border-zinc-200 text-zinc-600 dark:border-white/10 dark:text-zinc-400'"
                        >
                            {{ device.state.subscribed ? 'Включены' : 'Выключены' }}
                        </span>
                    </div>

                    <div class="space-y-4 px-5 py-4">
                        <!-- Сервер не готов -->
                        <div v-if="!pushServerReady" class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2.5 text-sm text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300">
                            <template v-if="!props.push.configured">На сервере не заданы ключи push (VAPID_PUBLIC_KEY / VAPID_PRIVATE_KEY). Обратитесь к администратору.</template>
                            <template v-else>Сервер пока не может отправлять push: на Windows/XAMPP нужна переменная окружения OPENSSL_CONF. Обратитесь к администратору.</template>
                        </div>

                        <!-- Браузер не поддерживает -->
                        <p v-else-if="!device.supported && !device.needsIosInstall.value" class="text-sm text-zinc-600 dark:text-zinc-400">
                            Этот браузер не поддерживает push-уведомления. Используйте Chrome, Edge, Firefox или Safari последних версий.
                        </p>

                        <!-- iPhone: сначала установить на экран «Домой» -->
                        <div v-else-if="device.needsIosInstall.value" class="rounded-md border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-700 dark:border-white/[0.06] dark:bg-white/[0.02] dark:text-zinc-300">
                            <p class="font-medium text-zinc-900 dark:text-zinc-100">На iPhone push работает из установленного приложения:</p>
                            <ol class="mt-2 list-decimal space-y-1 pl-5">
                                <li>Откройте сайт в Safari (iOS 16.4 или новее).</li>
                                <li>Нажмите «Поделиться» → «На экран „Домой“».</li>
                                <li>Запустите сайт с иконки на экране и вернитесь на эту страницу.</li>
                            </ol>
                        </div>

                        <template v-else>
                            <p v-if="device.state.permission === 'denied'" class="rounded-md border border-rose-200 bg-rose-50 px-3 py-2.5 text-sm text-rose-700 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300">
                                Уведомления для этого сайта запрещены в браузере. Разрешите их в настройках сайта (значок замка в адресной строке) и обновите страницу.
                            </p>

                            <div class="flex flex-wrap items-center gap-2">
                                <button
                                    v-if="!device.state.subscribed"
                                    type="button"
                                    :disabled="device.state.busy || device.state.permission === 'denied'"
                                    class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700
                                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60 focus-visible:ring-offset-2
                                           disabled:cursor-not-allowed disabled:opacity-50 dark:focus-visible:ring-offset-zinc-900"
                                    @click="enablePush"
                                >
                                    {{ device.state.busy ? 'Подключение…' : 'Включить на этом устройстве' }}
                                </button>

                                <template v-else>
                                    <button
                                        type="button"
                                        class="rounded-md border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 transition-colors hover:bg-zinc-50
                                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60
                                               dark:border-white/10 dark:bg-transparent dark:text-zinc-300 dark:hover:bg-white/[0.04]"
                                        @click="sendTest"
                                    >
                                        Отправить тестовое
                                    </button>
                                    <button
                                        type="button"
                                        :disabled="device.state.busy"
                                        class="rounded-md px-3 py-2 text-sm font-medium text-zinc-600 transition-colors hover:bg-zinc-100 hover:text-zinc-900
                                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60 disabled:opacity-50
                                               dark:text-zinc-400 dark:hover:bg-white/[0.06] dark:hover:text-zinc-100"
                                        @click="disablePush"
                                    >
                                        Отключить на этом устройстве
                                    </button>
                                </template>
                            </div>

                            <p v-if="testSent" class="text-sm text-zinc-600 dark:text-zinc-400">
                                Тестовое уведомление отправлено. Если сайт открыт и активен, оно появится справа внизу на странице, иначе — в уведомлениях системы.
                            </p>
                        </template>

                        <p v-if="device.state.error" class="text-sm text-rose-600 dark:text-rose-400">{{ device.state.error }}</p>

                        <p class="text-xs tabular-nums text-zinc-500 dark:text-zinc-400">
                            Подключено устройств: {{ props.push.devices }}. Push включается отдельно на каждом компьютере и телефоне.
                        </p>
                    </div>
                </section>

                <!-- Матрица «тип × канал» -->
                <section
                    v-for="group in groups"
                    :key="group.category"
                    class="overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-white/[0.08] dark:bg-zinc-900 dark:shadow-none"
                >
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[560px] text-sm">
                            <thead>
                                <tr class="border-b border-zinc-200 dark:border-white/[0.06]">
                                    <th scope="col" class="px-5 py-3 text-left text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                        {{ group.label }}
                                    </th>
                                    <th
                                        v-for="channel in channels"
                                        :key="channel.key"
                                        scope="col"
                                        class="w-28 px-2 py-3 text-center"
                                    >
                                        <button
                                            type="button"
                                            :disabled="channelDisabled(channel.key)"
                                            class="inline-flex flex-col items-center gap-1 rounded px-1 py-0.5 text-xs font-medium text-zinc-600 transition-colors hover:text-zinc-900
                                                   focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60 disabled:cursor-not-allowed disabled:opacity-50
                                                   dark:text-zinc-400 dark:hover:text-zinc-100"
                                            :title="channelDisabled(channel.key) ? 'Telegram не привязан к аккаунту' : 'Включить/выключить для всего раздела'"
                                            @click="toggleColumn(group, channel.key)"
                                        >
                                            {{ channel.label }}
                                            <span class="text-[10px] font-normal text-zinc-400 dark:text-zinc-500">
                                                {{ columnState(group, channel.key) === 'all' ? 'все' : columnState(group, channel.key) === 'none' ? 'нет' : 'часть' }}
                                            </span>
                                        </button>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-white/[0.06]">
                                <tr v-for="item in group.types" :key="item.type" class="hover:bg-zinc-50 dark:hover:bg-white/[0.02]">
                                    <td class="px-5 py-2.5 text-zinc-800 dark:text-zinc-200">{{ item.label }}</td>
                                    <td v-for="channel in channels" :key="channel.key" class="px-2 py-2.5 text-center">
                                        <input
                                            type="checkbox"
                                            :checked="matrix[item.type][channel.key]"
                                            :disabled="channelDisabled(channel.key)"
                                            :aria-label="`${item.label}: ${channel.label}`"
                                            class="h-4 w-4 cursor-pointer rounded border-zinc-300 text-blue-600 focus:ring-2 focus:ring-blue-500/40
                                                   disabled:cursor-not-allowed disabled:opacity-40 dark:border-white/20 dark:bg-zinc-950"
                                            @change="toggle(item.type, channel.key)"
                                        />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <p v-if="!telegramLinked" class="text-xs text-zinc-500 dark:text-zinc-400">
                    Канал Telegram станет доступен после привязки Telegram к аккаунту.
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
