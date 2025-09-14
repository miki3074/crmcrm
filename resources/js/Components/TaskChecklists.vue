<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps({
  taskId: Number,
  executor: Object,
  responsible: Object,
  creator: Object,
})

const list = ref([])
const loading = ref(false)
const showModal = ref(false)
const form = ref({
  title: '',
  assigned_to: '',
  important: false,
  files: [],
})

const load = async () => {
  loading.value = true
  try {
    const { data } = await axios.get(`/api/tasks/${props.taskId}/checklists`)
    list.value = data
  } finally {
    loading.value = false
  }
}

const submit = async () => {
  const fd = new FormData()
  fd.append('title', form.value.title)
  if (form.value.assigned_to) fd.append('assigned_to', form.value.assigned_to)
  fd.append('important', form.value.important ? 1 : 0)
  for (let f of form.value.files) {
    fd.append('files[]', f)
  }

  await axios.post(`/api/tasks/${props.taskId}/checklists`, fd)
  showModal.value = false
  form.value = { title: '', assigned_to: '', important: false, files: [] }
  await load()
}

const toggle = async (item) => {
  await axios.patch(`/api/checklists/${item.id}/toggle`)
  await load()
}

onMounted(load)
</script>

<template>
  <div class="mt-6">
    <h3 class="text-lg font-semibold mb-2">Чек-листы</h3>

    <ul v-if="list.length" class="space-y-2">
      <li v-for="c in list" :key="c.id" class="flex items-center gap-2">
        <input type="checkbox" :checked="c.completed" @change="toggle(c)" />
        <span :class="{'font-bold text-red-600': c.important}">{{ c.title }}</span>
        <span v-if="c.assignee" class="text-sm text-gray-500">({{ c.assignee.name }})</span>
      </li>
    </ul>
    <p v-else class="text-gray-500">Нет чек-листов</p>

    <button class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded" @click="showModal = true">
      Добавить чек-лист
    </button>

    <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center">
      <div class="bg-white rounded p-6 w-96">
        <h4 class="text-lg font-semibold mb-4">Новый пункт чек-листа</h4>
        <input v-model="form.title" type="text" placeholder="Название" class="w-full border mb-2 p-2" />

        <label class="block text-sm">Ответственный</label>
        <select v-model="form.assigned_to" class="w-full border mb-2 p-2">
          <option value="">—</option>
          <option :value="executor.id">{{ executor.name }}</option>
          <option :value="responsible.id">{{ responsible.name }}</option>
          <option :value="creator.id">{{ creator.name }}</option>
        </select>

        <label class="flex items-center gap-2 mb-2">
          <input type="checkbox" v-model="form.important" /> Важно
        </label>

        <input type="file" multiple @change="e => form.files = Array.from(e.target.files)" class="mb-4" />

        <div class="flex justify-end gap-2">
          <button @click="showModal = false" class="px-3 py-1 border">Отмена</button>
          <button @click="submit" class="px-3 py-1 bg-indigo-600 text-white">Сохранить</button>
        </div>
      </div>
    </div>
  </div>
</template>
