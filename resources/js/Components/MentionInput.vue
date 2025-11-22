<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  modelValue: String,
  users: Array // [{id, name}]
})

const emit = defineEmits(['update:modelValue', 'select'])

const text = ref(props.modelValue)
const showList = ref(false)
const filtered = ref([])
const cursorPos = ref(0)

watch(() => props.modelValue, v => text.value = v)

const onInput = (e) => {
  text.value = e.target.value
  emit('update:modelValue', text.value)

  const pos = e.target.selectionStart
  cursorPos.value = pos

  const triggerIndex = text.value.lastIndexOf('@', pos - 1)

  if (triggerIndex !== -1) {
    const query = text.value.slice(triggerIndex + 1, pos)
    filtered.value = props.users.filter(u =>
      u.name.toLowerCase().includes(query.toLowerCase())
    )
    showList.value = filtered.value.length > 0
  } else {
    showList.value = false
  }
}

const selectUser = (user) => {
  const pos = cursorPos.value
  const before = text.value.substring(0, text.value.lastIndexOf('@', pos - 1))
  const after = text.value.substring(pos)

  text.value = `${before}@${user.name} ${after}`
  emit('update:modelValue', text.value)

  showList.value = false
  emit('select', user)
}
</script>

<template>
  <div class="relative">
    <textarea
      :value="text"
      @input="onInput"
      rows="3"
      class="w-full border rounded-lg px-3 py-2 dark:bg-gray-700 dark:text-white"
    ></textarea>

    <!-- Dropdown -->
    <div
      v-if="showList"
      class="absolute left-0 top-full mt-1 bg-white dark:bg-gray-800 border rounded shadow-lg z-20 w-full max-h-40 overflow-auto"
    >
      <div
        v-for="u in filtered"
        :key="u.id"
        @click="selectUser(u)"
        class="px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer"
      >
        @{{ u.name }}
      </div>
    </div>
  </div>
</template>
