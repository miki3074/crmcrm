<!-- Partials/SearchOverlay.vue -->
<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps(['companies', 'summary'])
const emit = defineEmits(['close'])
const q = ref('')

const results = computed(() => {
    const query = q.value.toLowerCase().trim()
    if (!query) return null

    return {
        companies: props.companies.filter(c => c.name.toLowerCase().includes(query)),
        tasks: props.summary.all_tasks?.filter(t => t.title.toLowerCase().includes(query)) || [],
        projects: props.summary.managing_projects?.filter(p => p.name.toLowerCase().includes(query)) || []
    }
})

const closeOnEsc = (e) => { if (e.key === 'Escape') emit('close') }
onMounted(() => window.addEventListener('keydown', closeOnEsc))
onUnmounted(() => window.removeEventListener('keydown', closeOnEsc))
</script>

<template>
    <div class="fixed inset-0 z-[200] flex flex-col p-4 sm:p-20 bg-slate-950/80 backdrop-blur-xl animate-in fade-in duration-200">
        <div class="mx-auto w-full max-w-3xl flex flex-col max-h-full">
            <div class="relative group">
                <input v-model="q" autofocus placeholder="Что вы ищете?"
                       class="w-full px-8 py-6 text-xl bg-white dark:bg-slate-900 border-none rounded-3xl shadow-2xl outline-none focus:ring-4 focus:ring-indigo-500/20 text-slate-800 dark:text-white" />
                <button @click="emit('close')" class="absolute right-6 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">Esc</button>
            </div>

            <div class="mt-6 overflow-y-auto space-y-8 pr-2">
                <template v-if="results">
                    <!-- Компании -->
                    <div v-if="results.companies.length">
                        <h4 class="px-4 mb-2 text-xs font-black text-slate-500 uppercase tracking-widest">Организации</h4>
                        <div class="space-y-1">
                            <div v-for="c in results.companies" :key="c.id" @click="router.visit(`/companies/${c.id}`)"
                                 class="p-4 rounded-2xl hover:bg-white/10 text-white cursor-pointer transition">🏢 {{ c.name }}</div>
                        </div>
                    </div>
                    <!-- Задачи -->
                    <div v-if="results.tasks.length">
                        <h4 class="px-4 mb-2 text-xs font-black text-slate-500 uppercase tracking-widest">Задачи</h4>
                        <div class="space-y-1">
                            <div v-for="t in results.tasks" :key="t.id" @click="router.visit(`/tasks/${t.id}`)"
                                 class="p-4 rounded-2xl hover:bg-white/10 text-white cursor-pointer transition">✅ {{ t.title }}</div>
                        </div>
                    </div>
                </template>
                <div v-else-if="q.length" class="text-center py-20 text-slate-500 italic text-xl">Ничего не найдено</div>
                <div v-else class="text-center py-20 text-slate-500 italic text-xl font-light">Начните вводить название...</div>
            </div>
        </div>
    </div>
</template>
