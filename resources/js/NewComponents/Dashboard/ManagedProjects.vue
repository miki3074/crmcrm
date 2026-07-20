<script setup>
import { router } from '@inertiajs/vue3'
defineProps({ groups: { type: Object, default: () => ({}) }, loading: Boolean })
</script>

<template>
  <section class="rounded-xl border border-white/10 bg-[#1b1b1b]">
    <header class="border-b border-white/10 px-4 py-3"><h2 class="text-xs font-semibold text-slate-100">Я руковожу</h2></header>
    <div v-if="loading" class="grid gap-px bg-white/5 sm:grid-cols-2"><div v-for="i in 4" :key="i" class="h-24 animate-pulse bg-[#1b1b1b]" /></div>
    <div v-else-if="!Object.keys(groups).length" class="px-4 py-10 text-center text-xs text-slate-500">Нет проектов в управлении</div>
    <div v-else class="divide-y divide-white/5">
      <div v-for="(projects, company) in groups" :key="company" class="px-4 py-3">
        <div class="mb-2 flex items-center justify-between"><h3 class="text-[11px] font-medium text-slate-400">{{ company }}</h3><span class="text-[10px] text-slate-600">{{ projects.length }}</span></div>
        <div class="flex flex-wrap gap-2">
          <button v-for="project in projects" :key="project.id" class="rounded-md border border-white/10 bg-[#202020] px-2.5 py-1.5 text-[11px] text-slate-300 hover:border-blue-500/50 hover:text-white" @click="router.visit(`/projects2/${project.id}`)">{{ project.name }}</button>
        </div>
      </div>
    </div>
  </section>
</template>
