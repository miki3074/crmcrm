<script setup>
import { computed, ref, watch } from 'vue'

const props = defineProps({
    company: {
        type: Object,
        required: true,
    },
})

defineEmits(['close'])

const activeTab = ref('main')

watch(
    () => props.company?.id,
    () => {
        activeTab.value = 'main'
    },
)

const tabs = [
    { value: 'main', label: 'Главная' },
    { value: 'projects', label: 'Проекты' },
]

const ownerName = computed(() =>
    props.company.owner?.name ||
    props.company.user?.name ||
    props.company.owner_name ||
    props.company.user_name ||
    `Пользователь #${props.company.user_id ?? '—'}`
)

const formattedDate = computed(() => {
    if (!props.company.created_at) return '—'

    return new Intl.DateTimeFormat('ru-RU', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(props.company.created_at))
})

const projects = computed(() => props.company.projects || [])
</script>

<template>
    <aside class="border-t border-slate-200 bg-slate-50/70 dark:border-slate-800 dark:bg-slate-900/40 lg:border-l lg:border-t-0">
        <header class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Компания
                    </p>
                    <h2 class="mt-1 truncate text-lg font-semibold text-slate-950 dark:text-white">
                        {{ company.name }}
                    </h2>
                </div>

                <button
                    type="button"
                    class="grid h-9 w-9 shrink-0 place-items-center rounded-lg text-slate-500 transition hover:bg-slate-200 hover:text-slate-900 dark:hover:bg-slate-800 dark:hover:text-white"
                    aria-label="Закрыть панель"
                    @click="$emit('close')"
                >
                    ✕
                </button>
            </div>

            <nav class="mt-4 flex gap-1 rounded-xl bg-slate-200/70 p-1 dark:bg-slate-800">
                <button
                    v-for="tab in tabs"
                    :key="tab.value"
                    type="button"
                    class="flex-1 rounded-lg px-3 py-2 text-sm font-medium transition"
                    :class="activeTab === tab.value
                        ? 'bg-white text-slate-950 shadow-sm dark:bg-slate-700 dark:text-white'
                        : 'text-slate-500 hover:text-slate-900 dark:hover:text-slate-200'"
                    @click="activeTab = tab.value"
                >
                    {{ tab.label }}
                </button>
            </nav>
        </header>

        <div class="p-5">
            <a
                :href="`/companies/${company.id}`"
                class="mb-5 flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900"
            >
                Перейти в компанию
                <span aria-hidden="true">→</span>
            </a>

            <template v-if="activeTab === 'main'">
                <div class="flex items-center gap-3">
                    <div class="h-12 w-12 overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800">
                        <img
                            v-if="company.logo"
                            :src="`/storage/${company.logo}`"
                            :alt="company.name"
                            class="h-full w-full object-cover"
                        />
                        <div v-else class="grid h-full w-full place-items-center font-bold text-slate-500">
                            {{ company.name?.slice(0, 1)?.toUpperCase() || 'C' }}
                        </div>
                    </div>

                    <div class="min-w-0">
                        <div class="truncate font-semibold text-slate-900 dark:text-white">
                            {{ company.name }}
                        </div>
                        <div class="text-sm text-slate-500">
                            ID: {{ company.id }}
                        </div>
                    </div>
                </div>

                <dl class="mt-6 space-y-5">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Владелец компании
                        </dt>
                        <dd class="mt-1 text-sm font-medium text-slate-900 dark:text-white">
                            {{ ownerName }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Дата создания
                        </dt>
                        <dd class="mt-1 text-sm font-medium text-slate-900 dark:text-white">
                            {{ formattedDate }}
                        </dd>
                    </div>
                </dl>
            </template>

            <template v-else>
                <div v-if="projects.length" class="space-y-2">
                    <a
                        v-for="project in projects"
                        :key="project.id"
                        :href="`/projects/${project.id}`"
                        class="block rounded-xl border border-slate-200 bg-white p-4 transition hover:border-indigo-400 hover:shadow-sm dark:border-slate-700 dark:bg-slate-900"
                    >
                        <div class="font-medium text-slate-900 dark:text-white">
                            {{ project.name }}
                        </div>
                        <div class="mt-1 text-xs text-slate-500">
                            Проект #{{ project.id }}
                        </div>
                    </a>
                </div>

                <div
                    v-else
                    class="rounded-xl border border-dashed border-slate-300 px-4 py-10 text-center dark:border-slate-700"
                >
                    <div class="font-medium text-slate-700 dark:text-slate-200">
                        Проектов пока нет
                    </div>
                    <p class="mt-1 text-sm text-slate-500">
                        Здесь появятся проекты выбранной компании.
                    </p>
                </div>
            </template>
        </div>
    </aside>
</template>
