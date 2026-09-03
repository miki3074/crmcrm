<script setup>
import { computed, ref } from 'vue'

import TaskAttachments from './TaskAttachments.vue'
import TaskDocumentApproval from './TaskDocumentApproval.vue'

const props = defineProps({
    task: {
        type: Object,
        default: () => ({})
    },

    loading: {
        type: Boolean,
        default: false
    },

    canUpload: {
        type: Boolean,
        default: false
    },

    currentUser: {
        type: Object,
        default: null
    }
})

const emit = defineEmits([
    'uploadFiles',
    'deleteFile',
    'refresh'
])

const active = ref(null)

const toggle = (panel) => {
    active.value = active.value === panel ? null : panel
}

const files = computed(() => {
    return Array.isArray(props.task?.files)
        ? props.task.files
        : []
})

const attachmentsCount = computed(() => files.value.length)

const approvalCount = computed(() => {
    return files.value.filter(
        (file) => file.status && file.status !== 'none'
    ).length
})

const pendingApprovalCount = computed(() => {
    return files.value.filter((file) =>
        ['pending', 'rejected', 'replacement'].includes(file.status)
    ).length
})
</script>

<template>
    <div class="space-y-3">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <button
                type="button"
                class="hub-tile"
                :class="active === 'attachments' ? 'hub-tile-active' : ''"
                @click="toggle('attachments')"
            >
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-400"
                >
                    <svg
                        class="h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828L18 9.828a4 4 0 00-5.656-5.656L5.757 10.76a6 6 0 108.486 8.486L20.5 13"
                        />
                    </svg>
                </div>

                <div class="min-w-0 flex-1 text-left">
                    <div class="flex items-center gap-2">
                        <h3 class="font-semibold text-slate-900 dark:text-white">
                            Вложения
                        </h3>
                    </div>

                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                        {{ attachmentsCount }} файлов
                    </p>
                </div>

                <svg
                    class="h-4 w-4 shrink-0 text-slate-400 transition-transform"
                    :class="active === 'attachments' ? 'rotate-180' : ''"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M19 9l-7 7-7-7"
                    />
                </svg>
            </button>

            <button
                type="button"
                class="hub-tile"
                :class="active === 'approval' ? 'hub-tile-active' : ''"
                @click="toggle('approval')"
            >
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                >
                    <svg
                        class="h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                </div>

                <div class="min-w-0 flex-1 text-left">
                    <div class="flex items-center gap-2">
                        <h3 class="font-semibold text-slate-900 dark:text-white">
                            Документы на согласование
                        </h3>
                    </div>

                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                        {{ approvalCount }} документов
                        <span
                            v-if="pendingApprovalCount"
                            class="ml-1 rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-700 dark:bg-amber-500/10 dark:text-amber-400"
                        >
                            {{ pendingApprovalCount }} на проверке
                        </span>
                    </p>
                </div>

                <svg
                    class="h-4 w-4 shrink-0 text-slate-400 transition-transform"
                    :class="active === 'approval' ? 'rotate-180' : ''"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M19 9l-7 7-7-7"
                    />
                </svg>
            </button>
        </div>

        <Transition name="panel">
            <div v-if="active === 'attachments'">
                <TaskAttachments
                    :task="task"
                    :loading="loading"
                    :can-upload="canUpload"
                    @uploadFiles="emit('uploadFiles', $event)"
                    @deleteFile="emit('deleteFile', $event)"
                    @refresh="emit('refresh')"
                />
            </div>
        </Transition>

        <Transition name="panel">
            <div v-if="active === 'approval'">
                <TaskDocumentApproval
                    :task="task"
                    :current-user="currentUser"
                    @refresh="emit('refresh')"
                />
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.hub-tile {
    @apply flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-blue-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-800 dark:hover:border-blue-500/40;
}

.hub-tile-active {
    @apply border-blue-400 shadow-md ring-2 ring-blue-100 dark:border-blue-500/60 dark:ring-blue-500/10;
}

.panel-enter-active,
.panel-leave-active {
    transition:
        opacity 180ms ease,
        transform 180ms ease;
}

.panel-enter-from,
.panel-leave-to {
    opacity: 0;
    transform: translateY(-6px);
}
</style>
