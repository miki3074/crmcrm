<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
    show: Boolean,
    managers: Array, // [{id, name}, ...]
    loading: Boolean,
    error: String,
    currentUserId: Number
})

const emit = defineEmits(['close', 'submit'])

const emptyForm = () => ({
    name: '',
    manager_ids: [],
    start_date: new Date().toISOString().slice(0, 10),
    duration_days: ''
})

const form = ref(emptyForm())

// Компонент не размонтируется между открытиями (v-if внутри самого
// компонента), поэтому без явного сброса форма хранила бы значения
// от прошлого созданного проекта.
watch(() => props.show, (val) => {
    if (val) {
        form.value = emptyForm()
    }
})

const onSubmit = () => emit('submit', form.value)
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-zinc-900/60 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

        <!-- Content -->
        <div class="relative w-full max-w-lg bg-white dark:bg-zinc-800 rounded-3xl shadow-2xl p-8 transform transition-all">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-zinc-800 dark:text-white">Новый проект</h3>
                <button @click="$emit('close')" class="p-2 rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-700 transition">✕</button>
            </div>

            <p v-if="error" class="mb-4 p-3 rounded-xl bg-rose-50 text-rose-600 text-sm font-medium border border-rose-100">{{ error }}</p>

            <form @submit.prevent="onSubmit" class="space-y-5">
                <div>
                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">Название</label>
                    <input v-model="form.name" required placeholder="Например: CRM Система" class="w-full px-4 py-3 rounded-xl bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 focus:ring-2 focus:ring-cyan-500 outline-none transition" />
                </div>

                <div>
                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">Руководители</label>
                    <div class="max-h-32 overflow-y-auto p-3 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900 space-y-2">
                        <label v-for="m in managers" :key="m.id" class="flex items-center gap-3 cursor-pointer p-1 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-lg">
                            <input type="checkbox" :value="m.id" v-model="form.manager_ids" class="w-5 h-5 text-cyan-600 rounded focus:ring-cyan-500 border-gray-300" />
                            <span class="text-sm text-zinc-700 dark:text-zinc-200">{{ m.id === currentUserId ? 'Я' : m.name }}</span>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">Дата старта</label>
                        <input type="date" v-model="form.start_date" required class="w-full px-4 py-3 rounded-xl bg-zinc-50 border border-zinc-200 focus:ring-cyan-500 outline-none" />
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">Дней</label>
                        <input type="number" v-model="form.duration_days" required min="1" placeholder="30" class="w-full px-4 py-3 rounded-xl bg-zinc-50 border border-zinc-200 focus:ring-cyan-500 outline-none" />
                    </div>
                </div>

                <div class="pt-4 flex justify-end gap-3">
                    <button type="button" @click="$emit('close')" class="px-5 py-3 rounded-xl text-zinc-600 hover:bg-zinc-100 font-medium transition">Отмена</button>
                    <button type="submit" :disabled="loading" class="px-6 py-3 rounded-xl bg-cyan-600 text-white font-bold shadow-lg shadow-cyan-200 hover:bg-cyan-700 hover:shadow-xl transition disabled:opacity-70">
                        {{ loading ? 'Создание...' : 'Создать проект' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
