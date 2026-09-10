<script setup>
import { router } from '@inertiajs/vue3'
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'

const props = defineProps({
    projects: { type: Array, default: () => [] },
    loading: Boolean,
    // Проставляется родителем сразу после создания проекта — чтобы он
    // автоматически выделился в списке, а не терялся среди остальных.
    selectProjectId: { type: [Number, String], default: null }
})

const selectedProjectId = ref(null)
const isDesktop = ref(true)

const syncViewport = () => {
    isDesktop.value = window.innerWidth >= 1024
}

onMounted(() => {
    syncViewport()
    window.addEventListener('resize', syncViewport)
})

onBeforeUnmount(() => window.removeEventListener('resize', syncViewport))

watch(() => props.projects, projects => {
    if (!projects?.some(project => project.id === selectedProjectId.value)) {
        selectedProjectId.value = projects?.[0]?.id ?? null
    }
}, { immediate: true })

watch(() => props.selectProjectId, id => {
    if (id != null && props.projects.some(project => project.id === id)) {
        selectedProjectId.value = id
    }
})

const activeProject = computed(() =>
    props.projects.find(project => project.id === selectedProjectId.value) ?? null
)

const daysLeft = project => {
    if (!project?.start_date || !project?.duration_days) return null
    const end = new Date(project.start_date)
    end.setDate(end.getDate() + Number(project.duration_days))
    return Math.ceil((end - new Date()) / 86400000)
}

const deadlineLabel = project => {
    const days = daysLeft(project)
    if (days === null) return 'Без срока'
    if (days < 0) return `Просрочен ${Math.abs(days)} дн.`
    if (days === 0) return 'Сегодня'
    return `${days} дн.`
}

const deadlineClass = project => {
    const days = daysLeft(project)
    if (days === null) return 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400'
    if (days < 0) return 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300'
    if (days <= 7) return 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300'
    return 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300'
}

const initials = name => name?.split(' ').filter(Boolean).map(part => part[0]).slice(0, 2).join('').toUpperCase() || '?'

const priorityMeta = priority => ({
    high: ['Высокий', 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300'],
    medium: ['Средний', 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300'],
    low: ['Низкий', 'bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300']
}[priority] || [priority || '—', 'bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300'])

const statusMeta = status => ({
    new: ['Новая', 'bg-sky-500'],
    in_work: ['В работе', 'bg-cyan-500'],
    done: ['Готово', 'bg-emerald-500']
}[status] || [status || '—', 'bg-zinc-400'])

const selectProject = project => {
    if (isDesktop.value) selectedProjectId.value = project.id
    else router.visit(`/projects/${project.id}`)
}
</script>

<template>
    <section class="space-y-3">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Проекты</h2>
                <p class="text-xs text-zinc-500">Быстрый обзор проектов и задач</p>
            </div>
            <span class="rounded-md bg-zinc-100 px-2 py-1 text-xs font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                {{ projects.length }}
            </span>
        </div>

        <div v-if="loading" class="grid min-h-[460px] gap-3 lg:grid-cols-[280px_1fr] animate-pulse">
            <div class="space-y-2 rounded-xl border border-zinc-200 bg-white p-2 dark:border-zinc-800 dark:bg-zinc-900">
                <div v-for="i in 5" :key="i" class="h-20 rounded-lg bg-zinc-100 dark:bg-zinc-800" />
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900" />
        </div>

        <div v-else-if="!projects.length" class="rounded-xl border border-dashed border-zinc-300 py-16 text-center text-sm text-zinc-500 dark:border-zinc-700">
            Проектов пока нет
        </div>

        <div v-else class="grid min-h-[520px] overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900 lg:grid-cols-[300px_minmax(0,1fr)]">
            <aside class="border-b border-zinc-200 p-2 dark:border-zinc-800 lg:border-b-0 lg:border-r">
                <div class="max-h-[520px] space-y-1 overflow-y-auto pr-1 custom-scrollbar">
                    <div
                        v-for="project in projects"
                        :key="project.id"
                        role="button"
                        tabindex="0"
                        class="group w-full cursor-pointer rounded-lg border px-3 py-2.5 text-left transition
                               focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400"
                        :class="selectedProjectId === project.id && isDesktop
                            ? 'border-cyan-200 bg-cyan-50 dark:border-cyan-900 dark:bg-cyan-950/30'
                            : 'border-transparent hover:bg-zinc-50 dark:hover:bg-zinc-800/70'"
                        @click="selectProject(project)"
                        @keydown.enter="selectProject(project)"
                        @keydown.space.prevent="selectProject(project)"
                    >
                        <div class="flex items-start gap-2">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="truncate text-sm font-semibold text-zinc-800 dark:text-zinc-100">{{ project.name }}</span>
                                    <span class="shrink-0 rounded px-1.5 py-0.5 text-[10px] font-semibold" :class="deadlineClass(project)">
                                        {{ deadlineLabel(project) }}
                                    </span>
                                </div>
                                <div class="mt-1 flex items-center justify-between text-[11px] text-zinc-500">
                                    <span>{{ project.tasks?.length || 0 }} задач</span>
                                    <span>{{ project.start_date || 'Без даты' }}</span>
                                </div>
                                <div class="mt-2 flex items-center justify-between">
                                    <div class="flex -space-x-1.5">
                                        <span
                                            v-for="manager in project.managers?.slice(0, 3)"
                                            :key="manager.id"
                                            class="grid h-6 w-6 place-items-center rounded-full border-2 border-white bg-zinc-200 text-[9px] font-bold text-zinc-700 dark:border-zinc-900 dark:bg-zinc-700 dark:text-zinc-200"
                                            :title="manager.name"
                                        >{{ initials(manager.name) }}</span>
                                    </div>
                                    <button
                                        type="button"
                                        class="rounded text-xs text-cyan-600 opacity-0 transition group-hover:opacity-100 group-focus-visible:opacity-100 focus:opacity-100
                                               focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400 dark:text-cyan-400"
                                        @click.stop="router.visit(`/projects/${project.id}`)"
                                    >
                                        Открыть →
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>

            <div v-if="activeProject" class="hidden min-w-0 flex-col lg:flex">
                <header class="flex items-center justify-between border-b border-zinc-200 px-4 py-3 dark:border-zinc-800">
                    <div class="min-w-0">
                        <h3 class="truncate text-base font-semibold text-zinc-900 dark:text-white">{{ activeProject.name }}</h3>
                        <p class="text-xs text-zinc-500">{{ activeProject.tasks?.length || 0 }} задач · старт {{ activeProject.start_date || 'не указан' }}</p>
                    </div>
                    <button class="rounded-lg bg-zinc-900 px-3 py-2 text-xs font-semibold text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900" @click="router.visit(`/projects/${activeProject.id}`)">
                        Открыть проект
                    </button>
                </header>

                <div class="min-h-0 flex-1 overflow-auto">
                    <table class="w-full table-fixed text-left">
                        <thead class="sticky top-0 z-10 bg-zinc-50/95 text-[10px] uppercase tracking-wide text-zinc-500 backdrop-blur dark:bg-zinc-900/95">
                            <tr>
                                <th class="w-[48%] px-4 py-2.5 font-semibold">Задача</th>
                                <th class="w-[17%] px-3 py-2.5 font-semibold">Приоритет</th>
                                <th class="w-[18%] px-3 py-2.5 font-semibold">Статус</th>
                                <th class="w-[17%] px-3 py-2.5 font-semibold">Срок</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            <tr v-if="!activeProject.tasks?.length">
                                <td colspan="4" class="px-4 py-16 text-center text-sm text-zinc-500">В проекте пока нет задач</td>
                            </tr>
                            <tr
                                v-for="task in activeProject.tasks"
                                :key="task.id"
                                class="cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/60"
                                @click="router.visit(`/tasks/${task.id}`)"
                            >
                                <td class="px-4 py-3">
                                    <p class="truncate text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ task.title }}</p>
                                    <p class="mt-0.5 truncate text-[11px] text-zinc-400">{{ task.description || 'Без описания' }}</p>
                                </td>
                                <td class="px-3 py-3">
                                    <span class="rounded px-1.5 py-1 text-[10px] font-semibold" :class="priorityMeta(task.priority)[1]">{{ priorityMeta(task.priority)[0] }}</span>
                                </td>
                                <td class="px-3 py-3">
                                    <span class="inline-flex items-center gap-1.5 text-xs text-zinc-600 dark:text-zinc-300">
                                        <span class="h-1.5 w-1.5 rounded-full" :class="statusMeta(task.status)[1]" />
                                        {{ statusMeta(task.status)[0] }}
                                    </span>
                                </td>
                                <td class="truncate px-3 py-3 text-xs text-zinc-500">{{ task.due_date || '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.custom-scrollbar { scrollbar-width: thin; scrollbar-color: rgb(203 213 225) transparent; }
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgb(203 213 225); border-radius: 999px; }
</style>
