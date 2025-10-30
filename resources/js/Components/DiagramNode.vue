<script setup>
defineProps({
  label: String,
  type: String,
  data: Object,
  onOpenDetails: Function,
})
</script>

<template>
  <div
    class="rounded-lg p-3 text-center shadow-sm border cursor-pointer select-none hover:shadow-md transition relative"
    :class="{
      'bg-blue-100 border-blue-300': type === 'company',
      'bg-green-100 border-green-300': type === 'project',
      'bg-yellow-100 border-yellow-300': type === 'task',
      'bg-orange-100 border-orange-300': type === 'subtask',
    }"
  >
    <div class="font-semibold text-gray-700 truncate">{{ label }}</div>

    <button
      class="absolute bottom-1 right-1 text-xs bg-white/70 border rounded px-2 py-0.5 hover:bg-white"
      @click.stop="onOpenDetails(data)"
    >
      Подробнее
    </button>
  </div>
</template>
