<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
defineProps({ groups: { type: Object, default: () => ({}) }, loading: Boolean })
const open = ref({})
const keyOf = (company, project) => `${company}:${project}`
const toggle = (company, project) => { const key = keyOf(company, project); open.value[key] = !open.value[key] }
</script>

<template>
  <section class="rounded-xl border border-white/10 bg-[#1b1b1b]">
    <header class="border-b border-white/10 px-4 py-3"><h2 class="text-xs font-semibold text-slate-100">Мои задачи</h2></header>
    <div v-if="loading" class="space-y-px bg-white/5"><div v-for="i in 5" :key="i" class="h-14 animate-pulse bg-[#1b1b1b]" /></div>
    <div v-else-if="!Object.keys(groups).length" class="px-4 py-10 text-center text-xs text-slate-500">Назначенных задач нет</div>
    <div v-else class="divide-y divide-white/5">
      <template v-for="(projects, company) in groups" :key="company">
        <div class="bg-white/[0.018] px-4 py-2 text-[10px] font-semibold uppercase tracking-wide text-slate-600">{{ company }}</div>
        <div v-for="(tasks, project) in projects" :key="project">
          <button class="flex w-full items-center justify-between px-4 py-3 text-left hover:bg-white/[0.025]" @click="toggle(company, project)">
            <span class="text-xs text-slate-300">{{ project }}</span>
            <span class="text-[10px] text-slate-600">{{ tasks.length }} · {{ open[keyOf(company, project)] ? '−' : '+' }}</span>
          </button>
          <div v-if="open[keyOf(company, project)]" class="border-t border-white/5 bg-[#171717]">
            <button v-for="task in tasks" :key="task.id" class="grid w-full grid-cols-[1fr_auto] items-center gap-4 border-b border-white/5 px-5 py-2.5 text-left last:border-0 hover:bg-white/[0.025]" @click="router.visit(`/tasks2/${task.id}`)">
              <div class="min-w-0"><p class="truncate text-[11px] text-slate-300">{{ task.title }}</p><p class="mt-1 text-[10px] text-slate-600">{{ task.start_date || '—' }} → {{ task.due_date || 'без срока' }}</p></div>
              <div class="flex items-center gap-2"><div class="h-1 w-20 overflow-hidden rounded bg-white/10"><div class="h-full bg-blue-500" :style="{ width: `${task.progress ?? 0}%` }" /></div><span class="w-7 text-right text-[10px] text-slate-600">{{ task.progress ?? 0 }}%</span></div>
            </button>
          </div>
        </div>
      </template>
    </div>
  </section>
</template>
