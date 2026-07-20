<script setup>
defineProps({
  open: Boolean,
  title: String,
  maxWidth: { type: String, default: 'max-w-3xl' },
})
const emit = defineEmits(['close'])
</script>

<template>
  <Teleport to="body">
    <div v-if="open" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6">
      <button class="absolute inset-0 bg-black/70 backdrop-blur-sm" aria-label="Закрыть" @click="emit('close')" />
      <section :class="['relative w-full overflow-hidden rounded-2xl border border-white/10 bg-[#181818] shadow-2xl', maxWidth]">
        <header class="flex items-center justify-between border-b border-white/10 px-5 py-4">
          <h2 class="text-sm font-semibold text-white">{{ title }}</h2>
          <button class="grid h-8 w-8 place-items-center rounded-lg text-slate-400 hover:bg-white/5 hover:text-white" @click="emit('close')">✕</button>
        </header>
        <div class="max-h-[78vh] overflow-y-auto p-5"><slot /></div>
      </section>
    </div>
  </Teleport>
</template>
