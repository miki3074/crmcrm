<script setup>
import { ref } from 'vue'
import axios from 'axios'

const emit = defineEmits(['close', 'created'])
const form = ref({ name: '', logo: null })
const submitting = ref(false)
// 1. Добавляем переменную для хранения ошибок
const errors = ref({})

const onFile = (e) => form.value.logo = e.target.files[0]

const submit = async () => {
    submitting.value = true
    errors.value = {} // Очищаем ошибки перед новой отправкой

    const fd = new FormData()
    fd.append('name', form.value.name)
    if (form.value.logo) fd.append('logo', form.value.logo)

    try {
        await axios.post('/api/companies', fd, { headers: { 'Content-Type': 'multipart/form-data' }})
        emit('created')
        emit('close')
    } catch (e) {
        // 2. Ловим ошибки валидации от Laravel (статус 422)
        if (e.response && e.response.status === 422) {
            // Laravel возвращает ошибки в формате { errors: { field: ['message'] } }
            errors.value = e.response.data.errors
        } else {
            alert('Произошла неизвестная ошибка')
        }
    } finally {
        submitting.value = false
    }
}
</script>

<template>
    <div class="fixed inset-0 z-[100] grid place-items-center bg-slate-950/60 backdrop-blur-sm p-4">
        <div class="bg-white dark:bg-slate-900 w-full max-w-md p-8 rounded-[2rem] border dark:border-slate-800 shadow-2xl animate-in slide-in-from-bottom duration-300">
            <h3 class="text-2xl font-black text-slate-800 dark:text-white mb-6">Новая компания</h3>

            <form @submit.prevent="submit" class="space-y-6">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2 ml-1 tracking-widest">Название организации</label>

                    <!-- Добавили класс для красной обводки при ошибке -->
                    <input
                        v-model="form.name"
                        required
                        :class="{'border-red-500 focus:ring-red-500/20': errors.name}"
                        class="w-full px-5 py-4 rounded-2xl border dark:bg-slate-800 dark:border-slate-700 outline-none focus:ring-4 focus:ring-indigo-500/10 text-slate-800 dark:text-white transition-colors"
                        placeholder="Напр: Apple Inc."
                        @input="errors.name = null"
                    />

                    <!-- 3. Вывод текста ошибки -->
                    <div v-if="errors.name" class="text-red-500 text-sm mt-2 ml-1 font-medium">
                        {{ errors.name[0] }}
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2 ml-1 tracking-widest">Логотип (опционально)</label>
                    <div class="relative group">
                        <input type="file" @change="onFile" class="absolute inset-0 opacity-0 cursor-pointer" />

                        <!-- Тоже можно подсветить, если ошибка в файле -->
                        <div
                            :class="{'border-red-500': errors.logo}"
                            class="w-full py-10 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-2xl text-center group-hover:border-indigo-500 transition"
                        >
                            <span class="text-sm text-slate-400">{{ form.logo ? form.logo.name : 'Нажмите или перетащите файл' }}</span>
                        </div>
                    </div>
                    <!-- Ошибка для логотипа -->
                    <div v-if="errors.logo" class="text-red-500 text-sm mt-2 ml-1 font-medium">
                        {{ errors.logo[0] }}
                    </div>
                </div>

                <div class="flex gap-4 pt-4">
                    <button type="button" @click="emit('close')" class="flex-1 py-4 text-sm font-bold text-slate-400">Отмена</button>
                    <button type="submit" :disabled="submitting || !form.name" class="flex-1 py-4 text-sm font-bold bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-2xl hover:scale-105 transition disabled:opacity-50">
                        {{ submitting ? 'Создание...' : 'Создать' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
