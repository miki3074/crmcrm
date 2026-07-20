<script setup>
import { ref, watch } from 'vue'
import BaseModal from './BaseModal.vue'
const props = defineProps({ open: Boolean, company: Object, submitting: Boolean, error: String })
const emit = defineEmits(['close', 'submit'])
const password = ref('')
watch(() => props.open, value => { if (!value) password.value = '' })
</script>

<template>
  <BaseModal :open="open" title="Удаление компании" max-width="max-w-md" @close="emit('close')">
    <p class="text-xs leading-5 text-slate-400">Компания <strong class="text-slate-200">{{ company?.name }}</strong>, все её проекты и задачи будут удалены. Введите пароль для подтверждения.</p>
    <input v-model="password" type="password" autocomplete="new-password" class="mt-4 w-full rounded-lg border border-white/10 bg-[#111] px-3 py-2.5 text-sm text-white outline-none focus:border-rose-500" placeholder="Пароль" @keyup.enter="emit('submit', password)" />
    <p v-if="error" class="mt-2 text-[11px] text-rose-400">{{ error }}</p>
    <div class="mt-5 flex justify-end gap-2"><button class="rounded-md border border-white/10 px-3 py-2 text-xs text-slate-400" @click="emit('close')">Отмена</button><button :disabled="submitting || !password.trim()" class="rounded-md bg-rose-600 px-4 py-2 text-xs font-semibold text-white disabled:opacity-50" @click="emit('submit', password)">{{ submitting ? 'Удаление...' : 'Удалить' }}</button></div>
  </BaseModal>
</template>
