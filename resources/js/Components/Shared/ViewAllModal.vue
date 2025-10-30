<script setup>
const props = defineProps({
  modelValue: Boolean,
  title: String,
  items: Array,
})
const emit = defineEmits(['update:modelValue'])
</script>

<template>
  <dialog v-if="modelValue" open class="fixed inset-0 flex items-center justify-center bg-black/50 z-50">
    <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 w-full max-w-2xl max-h-[80vh] overflow-y-auto shadow-xl">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-slate-800 dark:text-slate-100">{{ title }}</h3>
        <button @click="emit('update:modelValue', false)" class="text-slate-500 hover:text-slate-800">✕</button>
      </div>

      <ul class="divide-y divide-slate-100 dark:divide-slate-700">
        <li v-for="item in items" :key="item.id" class="py-3">
          <div class="text-sm font-medium text-slate-800 dark:text-slate-200">{{ item.name || item.title }}</div>
          <div class="text-xs text-slate-500">{{ item.company?.name || item.project?.name }}</div>
        </li>
      </ul>
    </div>
  </dialog>
</template>
