<script setup>
import { ref, watch } from 'vue'
import BaseModal from './BaseModal.vue'
const props = defineProps({ open: Boolean, submitting: Boolean })
const emit = defineEmits(['close', 'submit'])
const name = ref('')
const logo = ref(null)
watch(() => props.open, value => { if (!value) { name.value = ''; logo.value = null } })
const submit = () => { if (name.value.trim()) emit('submit', { name: name.value.trim(), logo: logo.value }) }
</script>

<template>
  <BaseModal :open="open" title="Новая компания" max-width="max-w-md" @close="emit('close')">
    <form class="space-y-4" @submit.prevent="submit">
      <label class="block"><span class="mb-1.5 block text-[11px] text-slate-400">Название *</span><input v-model="name" required class="w-full rounded-lg border border-white/10 bg-[#111] px-3 py-2.5 text-sm text-white outline-none focus:border-blue-500" /></label>
      <label class="block"><span class="mb-1.5 block text-[11px] text-slate-400">Логотип</span><input type="file" accept="image/*" class="w-full text-xs text-slate-500 file:mr-3 file:rounded-md file:border-0 file:bg-white/10 file:px-3 file:py-2 file:text-slate-300" @change="logo = $event.target.files?.[0] ?? null" /></label>
      <div class="flex justify-end gap-2 pt-2"><button type="button" class="rounded-md border border-white/10 px-3 py-2 text-xs text-slate-400" @click="emit('close')">Отмена</button><button :disabled="submitting || !name.trim()" class="rounded-md bg-blue-600 px-4 py-2 text-xs font-semibold text-white disabled:opacity-50">{{ submitting ? 'Создание...' : 'Создать' }}</button></div>
    </form>
  </BaseModal>
</template>
