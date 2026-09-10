<script setup>
import { computed, ref } from 'vue'
import PollCreateModal from './PollCreateModal.vue'
import PollListModal from './PollListModal.vue'
import CompanyReminderModal from './CompanyReminderModal.vue'

const props = defineProps({
    company: Object,
    isOwner: Boolean,
    isAdmin: Boolean,
    canCreate: Boolean
})

defineEmits(['create', 'openMembers'])

const showPollCreateModal = ref(false)
const showPollListModal = ref(false)
const showCompanyReminderModal = ref(false)

// Единственный источник правды о роли — проп isOwner (вычисляется в
// Show.vue) и company.user_role, которые бэкенд действительно отдаёт.
// company.is_owner бэкендом никогда не возвращается — раньше здесь были
// обращения к несуществующему полю, которые не могли сработать.
const canCreatePoll = computed(() => {
    return props.company?.is_member === true || props.isOwner === true
})

const userRole = computed(() => {
    if (props.isOwner) return 'owner'
    return props.company?.user_role || null
})

const canCreateProject = computed(() => {
    return props.isOwner || userRole.value === 'manager'
})

const openPollCreateModal = () => {
    showPollCreateModal.value = true
}

const openPollListModal = () => {
    showPollListModal.value = true
}

const handlePollCreated = () => {
    showPollCreateModal.value = false
}
</script>

<template>
    <section class="rounded-xl border border-zinc-200 bg-white p-3 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 sm:p-4">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
            <div class="flex min-w-0 flex-1 items-center gap-3">
                <img v-if="company?.logo" :src="`/storage/${company.logo}`" alt="Logo" class="h-12 w-12 shrink-0 rounded-lg border border-zinc-200 object-cover dark:border-zinc-700" />
                <div v-else class="grid h-12 w-12 shrink-0 place-items-center rounded-lg bg-zinc-900 text-white dark:bg-white dark:text-zinc-900">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M5 21V7l7-4 7 4v14M9 9h.01M15 9h.01M9 13h.01M15 13h.01M9 17h.01M15 17h.01" />
                    </svg>
                </div>

                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="truncate text-xl font-semibold tracking-tight text-zinc-950 dark:text-white sm:text-2xl">{{ company?.name || 'Компания' }}</h1>
                        <span class="rounded bg-zinc-100 px-1.5 py-0.5 text-[10px] font-medium text-zinc-500 dark:bg-zinc-800">#{{ company?.id }}</span>
                        <span v-if="isOwner" class="rounded bg-amber-50 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">Владелец</span>
                        <span v-else-if="userRole === 'manager'" class="rounded bg-cyan-50 px-1.5 py-0.5 text-[10px] font-semibold text-cyan-700 dark:bg-cyan-950/40 dark:text-cyan-300">Менеджер</span>
                        <span v-else-if="company?.is_member" class="rounded bg-emerald-50 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">Участник</span>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button" class="action-secondary" @click="openPollListModal">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V9m4 8V5m4 12v-4M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    <span>Опросы</span>
                </button>

                <button v-if="canCreatePoll" type="button" class="action-secondary" @click="openPollCreateModal">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m7-7H5" />
                    </svg>
                    <span>Опрос</span>
                </button>

                <button
                    v-if="isOwner || userRole === 'manager'"
                    type="button"
                    class="action-secondary"
                    title="Напомнить о задачах с нулевым прогрессом"
                    @click="showCompanyReminderModal = true"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <span class="hidden sm:inline">Напомнить</span>
                </button>

                <button v-if="canCreateProject" type="button" class="action-primary" @click="$emit('create')">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m7-7H5" />
                    </svg>
                    Новый проект
                </button>
            </div>
        </div>
    </section>

    <CompanyReminderModal
        :show="showCompanyReminderModal"
        :company-id="company?.id"
        @close="showCompanyReminderModal = false"
    />

    <PollCreateModal v-if="showPollCreateModal" :company-id="company?.id" @close="showPollCreateModal = false" @created="handlePollCreated" />
    <PollListModal v-if="showPollListModal" :company-id="company?.id" @close="showPollListModal = false" />
</template>

<style scoped>
.action-primary { @apply inline-flex h-9 items-center justify-center gap-1.5 rounded-lg bg-cyan-600 px-3 text-xs font-semibold text-white transition hover:bg-cyan-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50; }
.action-secondary { @apply inline-flex h-9 items-center justify-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 text-xs font-medium text-zinc-700 transition hover:bg-zinc-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400 focus-visible:ring-offset-2 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800; }
.icon-button { @apply grid h-8 w-8 place-items-center rounded-lg text-sm text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-white; }
</style>
