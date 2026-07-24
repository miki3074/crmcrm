<script setup>
import { ref, computed } from 'vue'

const props = defineProps(['project'])
const showClientModal = ref(false)
const activeClient = ref(null)

const endDate = computed(() => {
    if (!props.project.start_date || !props.project.duration_days) return null
    const value = new Date(props.project.start_date)
    value.setDate(value.getDate() + Number(props.project.duration_days))
    return value
})

const daysLeft = computed(() => {
    if (!endDate.value) return null
    return Math.ceil((endDate.value - new Date()) / 86400000)
})

const timeProgress = computed(() => {
    if (!props.project.start_date || !props.project.duration_days) return 0
    const start = new Date(props.project.start_date).getTime()
    const duration = Number(props.project.duration_days) * 86400000
    return Math.min(100, Math.max(0, Math.round(((Date.now() - start) / duration) * 100)))
})

const deadlineClass = computed(() => {
    if (daysLeft.value === null) return 'text-slate-500'
    if (daysLeft.value < 0) return 'text-rose-600'
    if (daysLeft.value <= 7) return 'text-amber-600'
    return 'text-emerald-600'
})

const deadlineLabel = computed(() => {
    if (daysLeft.value === null) return 'Срок не задан'
    if (daysLeft.value < 0) return `Просрочено на ${Math.abs(daysLeft.value)} дн.`
    if (daysLeft.value === 0) return 'Завершение сегодня'
    return `${daysLeft.value} дн. осталось`
})

const team = computed(() => [
    ...(props.project.managers || []),
    ...(props.project.executors || [])
].filter((member, index, list) => list.findIndex(item => item.id === member.id) === index))

const formatDate = date => date
    ? new Date(date).toLocaleDateString('ru-RU', { day: '2-digit', month: 'short', year: 'numeric' })
    : '—'

const getInitials = name => name?.split(' ').map(part => part[0]).slice(0, 2).join('').toUpperCase() || '?'

const openClientModal = client => {
    activeClient.value = client
    showClientModal.value = true
}
</script>

<template>
    <div>
        <div class="flex flex-col gap-4">
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <span class="rounded-md bg-indigo-600 px-2 py-1 font-bold text-white">PROJECT</span>
                <span class="font-medium text-slate-500">{{ project.company?.name || 'Без компании' }}</span>
                <span v-if="project.status" class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                    {{ project.status }}
                </span>
            </div>

            <div>
                <h1 class="truncate text-2xl font-extrabold tracking-tight text-slate-950 dark:text-white sm:text-3xl">
                    {{ project.name }}
                </h1>
            </div>

            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                <div class="rounded-xl bg-slate-50 px-3 py-2.5 dark:bg-slate-800/70">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Старт</p>
                    <p class="mt-1 text-sm font-semibold text-slate-800 dark:text-slate-100">{{ formatDate(project.start_date) }}</p>
                </div>

                <div class="rounded-xl bg-slate-50 px-3 py-2.5 dark:bg-slate-800/70">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Срок</p>
                    <p class="mt-1 text-sm font-semibold" :class="deadlineClass">{{ deadlineLabel }}</p>
                </div>

                <div class="rounded-xl bg-slate-50 px-3 py-2.5 dark:bg-slate-800/70">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Прогресс времени</p>
                    <div class="mt-1.5 flex items-center gap-2">
                        <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                            <div class="h-full rounded-full bg-indigo-600" :style="{ width: `${timeProgress}%` }"></div>
                        </div>
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-200">{{ timeProgress }}%</span>
                    </div>
                </div>

                <div class="rounded-xl bg-slate-50 px-3 py-2.5 dark:bg-slate-800/70">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Команда</p>
                    <div class="mt-1 flex items-center">
                        <div class="flex -space-x-1.5">
                            <span
                                v-for="member in team.slice(0, 4)"
                                :key="member.id"
                                :title="member.name"
                                class="grid h-6 w-6 place-items-center rounded-full border-2 border-white bg-indigo-100 text-[9px] font-bold text-indigo-700 dark:border-slate-800 dark:bg-indigo-900/50 dark:text-indigo-200"
                            >
                                {{ getInitials(member.name) }}
                            </span>
                        </div>
                        <span class="ml-2 text-xs font-semibold text-slate-600 dark:text-slate-300">{{ team.length }}</span>
                    </div>
                </div>
            </div>

            <div v-if="project.clients?.length" class="flex flex-wrap items-center gap-1.5">
                <span class="mr-1 text-[10px] font-bold uppercase tracking-wide text-slate-400">Клиенты</span>
                <button
                    v-for="client in project.clients.slice(0, 5)"
                    :key="client.id"
                    class="rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-600 transition hover:border-indigo-300 hover:text-indigo-600 dark:border-slate-700 dark:text-slate-300"
                    @click="openClientModal(client)"
                >
                    {{ client.organization_name || client.name }}
                </button>
                <span v-if="project.clients.length > 5" class="text-xs font-medium text-slate-400">+{{ project.clients.length - 5 }}</span>
            </div>
        </div>

        <Transition name="modal">
            <div v-if="showClientModal" class="fixed inset-0 z-[90] grid place-items-center p-4">
                <div class="absolute inset-0 bg-slate-950/55 backdrop-blur-sm" @click="showClientModal = false"></div>
                <div class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl dark:border-slate-700 dark:bg-slate-900">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-indigo-600">Карточка клиента</p>
                            <h2 class="mt-1 text-xl font-bold text-slate-900 dark:text-white">{{ activeClient?.organization_name || activeClient?.name }}</h2>
                            <p class="mt-1 text-sm text-slate-500">{{ activeClient?.organization_name ? activeClient?.name : 'Персональный контакт' }}</p>
                        </div>
                        <button class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800" @click="showClientModal = false">✕</button>
                    </div>

                    <dl class="mt-5 grid gap-2">
                        <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800">
                            <dt class="text-[10px] font-bold uppercase text-slate-400">Email</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-800 dark:text-slate-100">{{ activeClient?.email || '—' }}</dd>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800">
                            <dt class="text-[10px] font-bold uppercase text-slate-400">Телефон</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-800 dark:text-slate-100">{{ activeClient?.phone || '—' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active { transition: opacity .18s ease; }
.modal-enter-from,
.modal-leave-to { opacity: 0; }
</style>
