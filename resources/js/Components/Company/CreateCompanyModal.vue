<script setup>
import { ref } from 'vue'
const emit = defineEmits(['submit'])

const open = ref(false)
const name = ref('')
const logo = ref(null)
const onFile = e => (logo.value = e.target.files?.[0] ?? null)

const doSubmit = () => {
  emit('submit', { name: name.value, logo: logo.value })
  open.value = false
  name.value = ''; logo.value = null
}
</script>

<template>
  <div>
    <button class="px-3 py-2 rounded bg-slate-900 text-white" @click="open = true">Новая компания</button>

    <dialog v-if="open" open class="p-4 rounded bg-white dark:bg-slate-900">
      <h4 class="font-semibold mb-3">Создать компанию</h4>
      <input v-model="name" type="text" placeholder="Название" class="w-full border rounded px-3 py-2 mb-2" />
      <input type="file" @change="onFile" class="mb-4" />
      <div class="flex gap-2 justify-end">
        <button class="px-3 py-2" @click="open = false">Отмена</button>
        <button class="px-3 py-2 bg-emerald-600 text-white rounded" @click="doSubmit">Создать</button>
      </div>
    </dialog>
  </div>
</template>
