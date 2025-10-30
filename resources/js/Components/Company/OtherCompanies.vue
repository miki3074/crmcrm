<script setup>
const props = defineProps({
  items: { type: Array, default: () => [] },
  loading: Boolean,
  error: String,
})
const emit = defineEmits(['request-delete'])
</script>

<template>
  <section>
    <h3 class="text-lg font-semibold mb-2">Другие компании</h3>
    <div v-if="loading">Загрузка...</div>
    <div v-else-if="error" class="text-rose-600">{{ error }}</div>
    <ul v-else class="divide-y">
      <li v-for="c in items" :key="c.id" class="py-2 flex items-center justify-between">
        <span>{{ c.name }}</span>
        <button class="text-rose-600 hover:underline" @click="emit('request-delete', c.id)">Удалить</button>
      </li>
    </ul>
    <p v-if="!loading && !error && !items.length" class="text-slate-500">Пусто</p>
  </section>
</template>
