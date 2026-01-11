<!-- Partials/StatCard.vue -->
<script setup>
defineProps(['title', 'icon', 'color'])
</script>

<template>
    <button class="group relative overflow-hidden p-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl hover:shadow-xl hover:-translate-y-1 transition-all duration-300 text-left">
        <!-- Декоративный фон при наведении -->
        <div class="absolute -right-4 -top-4 w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-full group-hover:scale-[3] transition-transform duration-500 opacity-50"></div>

        <div class="relative z-10 flex flex-col gap-3">
            <span class="text-2xl">{{ icon }}</span>
            <span class="font-semibold text-slate-700 dark:text-slate-200">{{ title }}</span>
        </div>
    </button>
</template>
