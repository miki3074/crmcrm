<script setup>
import { router } from '@inertiajs/vue3'
defineProps({ dueToday: { type: Array, default: () => [] }, overdue: { type: Array, default: () => [] }, loading: Boolean })
</script>

<template>
  <div class="grid gap-4 lg:grid-cols-2">
    <section v-for="block in [{ title: 'Срок сегодня', items: dueToday, danger: false }, { title: 'Просрочено', items: overdue, danger: true }]" :key="block.title" class="rounded-xl border border-white/10 bg-[#1b1b1b]">
      <header class="flex items-center justify-between border-b border-white/10 px-4 py-3"><h2 class="text-xs font-semibold text-slate-100">{{ block.title }}</h2><span class="rounded px-1.5 py-0.5 text-[10px]" :class="block.danger ? 'bg-rose-500/10 text-rose-400' : 'bg-white/5 text-slate-500'">{{ block.items.length }}</span></header>
      <div v-if="loading" class="space-y-px bg-white/5"><div v-for="i in 3" :key="i" class="h-10 animate-pulse bg-[#1b1b1b]" /></div>
      <div v-else-if="!block.items.length" class="px-4 py-8 text-center text-[11px] text-slate-600">{{ block.danger ? 'Просроченных задач нет' : 'На сегодня всё спокойно' }}</div>
      <button v-for="task in block.items" v-else :key="task.id" class="flex w-full items-center justify-between gap-3 border-b border-white/5 px-4 py-2.5 text-left last:border-0 hover:bg-white/[0.025]" @click="router.visit(`/tasks2/${task.id}`)"><span class="truncate text-[11px]" :class="block.danger ? 'text-rose-300' : 'text-slate-300'">{{ task.title }}</span><span class="text-[10px] text-slate-600">Открыть →</span></button>
    </section>
  </div>
</template>
