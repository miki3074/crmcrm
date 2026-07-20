<script setup>
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  title: String,
  companies: { type: Array, default: () => [] },
  userId: [String, Number],
  loading: Boolean,
  canDelete: Boolean,
})
const emit = defineEmits(['delete'])
const localQuery = ref('')
const limit = ref(7)
const filtered = computed(() => {
  const q = localQuery.value.trim().toLowerCase()
  return q ? props.companies.filter(item => (item.name ?? '').toLowerCase().includes(q)) : props.companies
})
</script>

<template>
  <section class="overflow-hidden rounded-xl border border-white/10 bg-[#1b1b1b]">
    <header class="flex flex-wrap items-center justify-between gap-3 border-b border-white/10 px-4 py-3">
      <div class="flex items-center gap-2">
        <h2 class="text-xs font-semibold text-slate-100">{{ title }}</h2>
        <span class="rounded bg-white/5 px-1.5 py-0.5 text-[10px] text-slate-500">{{ companies.length }}</span>
      </div>
      <input v-model="localQuery" class="w-44 rounded-md border border-white/10 bg-[#141414] px-2.5 py-1.5 text-[11px] text-white outline-none placeholder:text-slate-600 focus:border-blue-500" placeholder="Поиск..." />
    </header>

    <div v-if="loading" class="space-y-px bg-white/5">
      <div v-for="i in 5" :key="i" class="h-12 animate-pulse bg-[#1b1b1b]" />
    </div>

    <div v-else-if="!filtered.length" class="px-4 py-10 text-center text-xs text-slate-500">Компаний нет</div>

    <div v-else class="overflow-x-auto">
      <table class="w-full min-w-[620px] text-left">
        <thead class="border-b border-white/10 text-[10px] uppercase tracking-wide text-slate-600">
          <tr><th class="px-4 py-2 font-medium">Компания</th><th class="px-3 py-2 font-medium">Домен</th><th class="px-3 py-2 font-medium">Проекты</th><th class="px-3 py-2 font-medium">Владелец</th><th class="w-10" /></tr>
        </thead>
        <tbody class="divide-y divide-white/5">
          <tr v-for="company in filtered.slice(0, limit)" :key="company.id" class="group cursor-pointer hover:bg-white/[0.035]" @click="router.visit(`/companies2/${company.id}`)">
            <td class="px-4 py-2.5">
              <div class="flex items-center gap-2.5">
                <img v-if="company.logo" :src="`/storage/${company.logo}`" class="h-7 w-7 rounded-md object-cover" alt="" />
                <div v-else class="grid h-7 w-7 place-items-center rounded-md bg-white/5 text-[10px] font-semibold text-slate-400">{{ (company.name || 'C').slice(0, 1).toUpperCase() }}</div>
                <span class="max-w-[220px] truncate text-xs font-medium text-slate-200">{{ company.name }}</span>
              </div>
            </td>
            <td class="px-3 py-2.5 text-[11px] text-slate-500">{{ company.domain || '—' }}</td>
            <td class="px-3 py-2.5 text-[11px] text-slate-400">{{ company.projects?.length ?? 0 }}</td>
            <td class="px-3 py-2.5 text-[11px] text-slate-500">{{ String(company.user_id) === String(userId) ? 'Вы' : 'Другой пользователь' }}</td>
            <td class="px-2 py-2.5">
              <button v-if="canDelete && String(company.user_id) === String(userId)" class="invisible rounded p-1 text-slate-600 hover:bg-rose-500/10 hover:text-rose-400 group-hover:visible" @click.stop="emit('delete', company)">✕</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <button v-if="filtered.length > limit" class="w-full border-t border-white/10 px-4 py-2 text-[11px] text-slate-500 hover:bg-white/[0.025] hover:text-slate-300" @click="limit += 10">Показать ещё</button>
  </section>
</template>
