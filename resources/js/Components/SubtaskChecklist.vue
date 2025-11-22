<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'

const props = defineProps({
  subtaskId: Number,
  checklist: {
    type: Array,
    default: () => []
  },
  executors: {
    type: Array,
    default: () => []
  },
  responsibles: {
    type: Array,
    default: () => []
  },
  canWrite: Boolean,
})

const list = computed(() => props.checklist ?? [])

const newItem = ref('')
const newResponsible = ref(null)

const emit = defineEmits(['updated'])

const addItem = async () => {
  if (!newItem.value.trim()) return

  const { data } = await axios.post(`/api/subtasks/${props.subtaskId}/checklist`, {
    title: newItem.value,
    responsible_id: newResponsible.value,
  })

  emit('updated', { type: 'add', item: data })

  newItem.value = ''
  newResponsible.value = null
}

const toggle = async (item) => {
  const { data } = await axios.patch(`/api/subtask-checklist/${item.id}/toggle`)
  emit('updated', { type: 'toggle', id: item.id, completed: data.completed })
}

const removeItem = async (id) => {
  if (!confirm('Удалить?')) return
  await axios.delete(`/api/subtask-checklist/${id}`)
  emit('updated', { type: 'delete', id })
}
</script>

<template>
  <div class="mt-6 p-4 bg-white dark:bg-slate-800 rounded-xl shadow">
    <h3 class="text-lg font-semibold mb-3">📝 Чек-лист</h3>

    <p v-if="list.length === 0" class="text-gray-500 text-sm">
      Пусто.
    </p>

    <div class="space-y-3" v-else>
      <div v-for="item in list" :key="item.id"
           class="p-3 border rounded-lg dark:border-slate-700 flex justify-between items-center">

        <div class="flex items-center gap-3">
          <input type="checkbox"
                 :checked="item.completed"
                 @change="toggle(item)"
                 class="w-5 h-5"/>

          <div>
            <p :class="item.completed ? 'line-through text-gray-400' : ''">
              {{ item.title }}
            </p>

            <p v-if="item.responsible" class="text-xs text-gray-500">
              👤 {{ item.responsible.name }}
            </p>
          </div>
        </div>

        <!-- <button v-if="canWrite"
                @click="removeItem(item.id)"
                class="text-red-600 text-sm hover:underline">
          ✖
        </button> -->
      </div>
    </div>

    <div v-if="canWrite" class="mt-4">
      <input v-model="newItem"
             class="w-full border rounded-lg px-3 py-2 dark:bg-slate-700 dark:text-white"
             placeholder="Новый пункт..."/>

      <select v-model="newResponsible"
              class="w-full border rounded-lg px-3 py-2 mt-2 dark:bg-slate-700 dark:text-white">
        <option :value="null">Без ответственного</option>

        <option v-for="u in [...executors, ...responsibles]" :key="u.id" :value="u.id">
          {{ u.name }}
        </option>
      </select>

      <button @click="addItem"
              class="mt-2 bg-indigo-600 text-white px-4 py-2 rounded-lg">
        ➕ Добавить
      </button>
    </div>
  </div>
</template>
