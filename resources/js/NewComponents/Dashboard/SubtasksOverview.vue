<script setup>
import { router } from '@inertiajs/vue3'
defineProps({ groups: { type: Object, default: () => ({}) }, loading: Boolean })
</script>

<template>
  <section class="rounded-xl border border-white/10 bg-[#1b1b1b]">
    <header class="border-b border-white/10 px-4 py-3"><h2 class="text-xs font-semibold text-slate-100">Мои подзадачи</h2></header>
    <div v-if="loading" class="grid gap-px bg-white/5 sm:grid-cols-2"><div v-for="i in 4" :key="i" class="h-24 animate-pulse bg-[#1b1b1b]" /></div>
    <div v-else-if="!Object.keys(groups).length" class="px-4 py-10 text-center text-xs text-slate-500">Подзадач нет</div>
    <div v-else class="grid gap-px bg-white/5 lg:grid-cols-2">
      <div v-for="(projects, company) in groups" :key="company" class="bg-[#1b1b1b] p-4">
        <h3 class="mb-3 text-[11px] font-semibold text-slate-400">{{ company }}</h3>
        <div class="space-y-3">
          <div v-for="(tasks, project) in projects" :key="project">
            <p class="mb-1.5 text-[10px] uppercase tracking-wide text-slate-600">{{ project }}</p>
            <div v-for="(subtasks, task) in tasks" :key="task" class="mb-2 rounded-lg border border-white/10 bg-[#171717] p-2.5">
              <p class="mb-2 truncate text-[11px] text-slate-400">{{ task }}</p>
              <div class="flex flex-wrap gap-1.5">
                <button v-for="subtask in subtasks" :key="subtask.id" class="max-w-full truncate rounded border border-white/10 px-2 py-1 text-[10px] text-slate-400 hover:border-blue-500/50 hover:text-white" @click="router.visit(`/tasks2/${subtask.task_id}`)">{{ subtask.title }}</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
