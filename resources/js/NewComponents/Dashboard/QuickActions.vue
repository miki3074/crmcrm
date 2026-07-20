<script setup>
import { router } from '@inertiajs/vue3'

defineProps({ isAdmin: Boolean })
const emit = defineEmits(['create-company'])

const actions = [
  { label: 'Календарь', hint: 'События и встречи', route: '/calendar', icon: '▦' },
  { label: 'Хранилище', hint: 'Файлы и документы', route: '/file-storage', icon: '◫' },
  { label: 'Визуальная схема', hint: 'Карта связей', route: '/mapdiagram', icon: '⌘' },
]
</script>

<template>
  <div class="flex flex-wrap items-center gap-2">
    <button
      v-for="action in actions"
      :key="action.route"
      class="group flex items-center gap-2 rounded-lg border border-white/10 bg-[#202020] px-3 py-2 text-left hover:border-white/20 hover:bg-[#262626]"
      @click="router.visit(action.route)"
    >
      <span class="grid h-7 w-7 place-items-center rounded-md bg-white/5 text-xs text-slate-300">{{ action.icon }}</span>
      <span>
        <span class="block text-xs font-medium text-slate-100">{{ action.label }}</span>
        <span class="hidden text-[10px] text-slate-500 sm:block">{{ action.hint }}</span>
      </span>
    </button>

    <button v-if="isAdmin" class="rounded-lg border border-white/10 bg-[#202020] px-3 py-2 text-xs text-slate-200 hover:bg-[#262626]" @click="router.visit('/employees')">Сотрудники</button>
    <button v-if="isAdmin" class="rounded-lg border border-white/10 bg-[#202020] px-3 py-2 text-xs text-slate-200 hover:bg-[#262626]" @click="router.visit('/clients')">Клиенты</button>
    <button v-if="isAdmin" class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-500" @click="emit('create-company')">+ Новая компания</button>
  </div>
</template>
