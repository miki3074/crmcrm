<script setup>
import { computed } from 'vue'

const props = defineProps({
    company: Object,
    isOwner: Boolean,
    isAdmin: Boolean,
    canCreate: Boolean
})

const emit = defineEmits(['create', 'openMembers'])
</script>

<template>
    <div class="relative overflow-hidden rounded-3xl shadow-xl mb-8 group">
        <!-- Фон с градиентом и шумом -->
        <div class="absolute inset-0 bg-gradient-to-r from-indigo-600 via-purple-600 to-fuchsia-600 transition-all duration-1000 group-hover:scale-105"></div>
        <div class="absolute inset-0 bg-white/10 backdrop-blur-[1px]"></div>

        <div class="relative px-6 py-10 sm:px-10 text-white flex flex-col md:flex-row items-center md:items-start gap-6">
            <!-- Лого -->
            <div class="shrink-0 p-1 bg-white/30 backdrop-blur-md rounded-2xl">
                <img
                    v-if="company?.logo"
                    :src="`/storage/${company.logo}`"
                    alt="Logo"
                    class="w-20 h-20 rounded-xl object-cover bg-white"
                />
                <div v-else class="w-20 h-20 rounded-xl bg-white/20 flex items-center justify-center text-3xl">
                    🏢
                </div>
            </div>

            <!-- Инфо -->
            <div class="text-center md:text-left flex-1">
                {{ company?.name || 'У вас нет задач или проектов в этой компании...' }}
                <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-white drop-shadow-sm">
<!--                    {{ company?.name || 'У вас нет задач в этой компании...' }}-->
                </h1>
                <div class="flex flex-wrap items-center justify-center md:justify-start gap-3 mt-2 text-indigo-100">
                    <span class="px-2 py-0.5 rounded-md bg-white/20 text-xs font-mono">ID: {{ company?.id }}</span>
                    <span v-if="isOwner" class="px-2 py-0.5 rounded-full bg-amber-400/20 text-amber-100 border border-amber-400/30 text-xs font-bold uppercase tracking-wider">Владелец</span>
<!--                    <span v-else-if="isAdmin" class="px-2 py-0.5 rounded-full bg-rose-400/20 text-rose-100 border border-rose-400/30 text-xs font-bold uppercase tracking-wider">Админ</span>-->
                </div>
            </div>

            <!-- Кнопки -->
            <div class="flex flex-col sm:flex-row gap-3 mt-4 md:mt-0">
                <button
                    @click="$emit('openMembers')"
                    class="px-5 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 border border-white/20 backdrop-blur-md transition text-sm font-semibold flex items-center justify-center gap-2"
                >
                    👥 Участники
                </button>
                <button
                    v-if="canCreate"
                    @click="$emit('create')"
                    class="px-5 py-2.5 rounded-xl bg-white text-indigo-600 shadow-lg shadow-indigo-900/20 hover:shadow-xl hover:bg-indigo-50 transition text-sm font-bold flex items-center justify-center gap-2"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    Новый проект
                </button>
            </div>
        </div>
    </div>
</template>
