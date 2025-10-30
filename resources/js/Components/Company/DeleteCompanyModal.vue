<script setup>
import { ref } from 'vue'
const emit = defineEmits(['confirm'])

const open = ref(false)
const id = ref(null)
const password = ref('')
const error = ref('')

function openModal(companyId) {
  id.value = companyId
  password.value = ''
  error.value = ''
  open.value = true
}

function confirm() {
  if (!password.value.trim()) {
    error.value = 'Введите пароль.'
    return
  }
  emit('confirm', { id: id.value, password: password.value })
  open.value = false
}

defineExpose({ open: openModal })
</script>

<template>
  <dialog v-if="open" open class="p-4 rounded bg-white dark:bg-slate-900 max-w-md w-full">
    <h4 class="font-semibold mb-3">Подтвердите удаление</h4>
    <input v-model="password" type="password" placeholder="Пароль" class="w-full border rounded px-3 py-2 mb-2" />
    <p v-if="error" class="text-rose-600 text-sm mb-2">{{ error }}</p>
    <div class="flex gap-2 justify-end">
      <button class="px-3 py-2" @click="open = false">Отмена</button>
      <button class="px-3 py-2 bg-rose-600 text-white rounded" @click="confirm">Удалить</button>
    </div>
  </dialog>
</template>
