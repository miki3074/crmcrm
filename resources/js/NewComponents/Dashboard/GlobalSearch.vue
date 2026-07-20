<script setup>
import BaseModal from './BaseModal.vue'
import { router } from '@inertiajs/vue3'
defineProps({ open: Boolean, query: String, results: Object, hasResults: Boolean })
const emit = defineEmits(['close'])
const sections = [
  { key: 'companies', label: 'Компании', path: item => `/companies2/${item.id}`, title: item => item.name, hint: () => '' },
  { key: 'projects', label: 'Проекты', path: item => `/projects2/${item.id}`, title: item => item.name, hint: item => item.company?.name || '' },
  { key: 'tasks', label: 'Задачи', path: item => `/tasks2/${item.id}`, title: item => item.title, hint: item => item.project?.name || '' },
  { key: 'subtasks', label: 'Подзадачи', path: item => `/tasks2/${item.task_id}`, title: item => item.title, hint: item => item.task?.project?.name || '' },
]
</script>

<template>
  <BaseModal :open="open" :title="`Поиск: ${query}`" max-width="max-w-4xl" @close="emit('close')">
    <div v-if="!hasResults" class="py-12 text-center text-xs text-slate-500">Ничего не найдено</div>
    <div v-else class="space-y-5">
      <section v-for="section in sections" v-show="results?.[section.key]?.length" :key="section.key">
        <h3 class="mb-2 text-[10px] font-semibold uppercase tracking-wide text-slate-600">{{ section.label }}</h3>
        <div class="grid gap-2 sm:grid-cols-2">
          <button v-for="item in results?.[section.key]" :key="item.id" class="rounded-lg border border-white/10 bg-[#141414] p-3 text-left hover:border-blue-500/40" @click="router.visit(section.path(item))"><p class="truncate text-xs text-slate-200">{{ section.title(item) }}</p><p v-if="section.hint(item)" class="mt-1 truncate text-[10px] text-slate-600">{{ section.hint(item) }}</p></button>
        </div>
      </section>
    </div>
  </BaseModal>
</template>
