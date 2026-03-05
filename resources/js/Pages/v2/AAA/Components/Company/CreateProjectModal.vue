<script setup>
import { ref, watch, computed } from 'vue'

const props = defineProps({
    show: Boolean,
    managers: Array,
    loading: Boolean,
    error: String,
    currentUserId: Number
})

const emit = defineEmits(['close', 'submit'])

const form = ref({
    name: '',
    manager_ids: [],
    start_date: new Date().toISOString().slice(0, 10),
    duration_days: '',
    priority: 'medium',
    description: ''
})

// Валидация
const errors = ref({})

const isFormValid = computed(() => {
    return form.value.name.trim() &&
        form.value.duration_days > 0 &&
        form.value.manager_ids.length > 0
})

// При открытии сбрасываем форму
watch(() => props.show, (val) => {
    if (val) {
        form.value = {
            name: '',
            manager_ids: props.currentUserId ? [props.currentUserId] : [],
            start_date: new Date().toISOString().slice(0, 10),
            duration_days: '',
            priority: 'medium',
            description: ''
        }
        errors.value = {}
    }
})

const onSubmit = () => {
    if (!isFormValid.value) {
        if (!form.value.name.trim()) {
            errors.value.name = 'Введите название проекта'
        }
        if (!form.value.duration_days) {
            errors.value.duration = 'Укажите длительность'
        }
        if (form.value.manager_ids.length === 0) {
            errors.value.managers = 'Выберите хотя бы одного руководителя'
        }
        return
    }
    emit('submit', form.value)
}

// Цвета для приоритетов
const priorityColors = {
    low: 'emerald',
    medium: 'amber',
    high: 'rose'
}
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <!-- Backdrop с анимацией -->
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity animate-fadeIn"
             @click="$emit('close')"></div>

        <!-- Content с анимацией -->
        <div class="relative w-full max-w-lg bg-white dark:bg-slate-900 rounded-3xl shadow-2xl transform transition-all animate-slideUp overflow-hidden">

            <!-- Декоративный градиентный фон -->
            <div class="absolute top-0 left-0 w-full h-32 bg-gradient-to-br from-indigo-500/10 via-purple-500/10 to-pink-500/10"></div>

            <!-- Header с градиентом -->
            <div class="relative px-8 pt-8 pb-4 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-1 h-8 bg-gradient-to-b from-indigo-500 to-purple-500 rounded-full"></div>
                        <div>
                            <h3 class="text-2xl font-bold text-slate-800 dark:text-white">Создание проекта</h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Заполните информацию о новом проекте</p>
                        </div>
                    </div>

                    <button @click="$emit('close')"
                            class="p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition-all group">
                        <svg class="w-5 h-5 text-slate-400 group-hover:text-slate-600 dark:group-hover:text-slate-300"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Error Alert -->
            <div v-if="error"
                 class="mx-8 mt-4 p-4 bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800 rounded-xl">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-rose-100 dark:bg-rose-900 flex items-center justify-center">
                        <span class="text-rose-600 dark:text-rose-400 text-lg">⚠️</span>
                    </div>
                    <p class="text-sm font-medium text-rose-700 dark:text-rose-300">{{ error }}</p>
                </div>
            </div>

            <!-- Form -->
            <form @submit.prevent="onSubmit" class="p-8 space-y-6">
                <!-- Название проекта -->
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">
                        Название проекта <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-slate-400">📋</span>
                        </div>
                        <input v-model="form.name"
                               type="text"
                               placeholder="Например: CRM Система"
                               :class="[
                                   'w-full pl-10 pr-4 py-3 rounded-xl border transition-all',
                                   errors.name
                                       ? 'border-rose-300 bg-rose-50 dark:bg-rose-950/20 focus:ring-rose-500'
                                       : 'border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 focus:border-indigo-300 focus:ring focus:ring-indigo-200/20'
                               ]" />
                    </div>
                    <p v-if="errors.name" class="text-xs text-rose-500 mt-1">{{ errors.name }}</p>
                </div>

                <!-- Описание (опционально) -->
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">
                        Описание <span class="text-slate-400 text-xs font-normal">(необязательно)</span>
                    </label>
                    <textarea v-model="form.description"
                              rows="2"
                              placeholder="Краткое описание проекта..."
                              class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 focus:border-indigo-300 focus:ring focus:ring-indigo-200/20 transition resize-none">
                    </textarea>
                </div>

                <!-- Приоритет -->
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Приоритет</label>
                    <div class="flex gap-2">
                        <button v-for="priority in ['low', 'medium', 'high']" :key="priority"
                                type="button"
                                @click="form.priority = priority"
                                class="flex-1 px-4 py-2.5 rounded-xl border transition-all duration-200 text-sm font-medium capitalize"
                                :class="form.priority === priority
                                    ? `bg-${priorityColors[priority]}-500 text-white border-${priorityColors[priority]}-600 shadow-lg`
                                    : `bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:border-${priorityColors[priority]}-300`">
                            {{ priority === 'low' ? '🟢 Низкий' : priority === 'medium' ? '🟡 Средний' : '🔴 Высокий' }}
                        </button>
                    </div>
                </div>

                <!-- Руководители -->
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">
                        Руководители <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="max-h-40 overflow-y-auto p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 space-y-2 custom-scrollbar"
                             :class="{ 'border-rose-300 bg-rose-50 dark:bg-rose-950/20': errors.managers }">
                            <label v-for="m in managers"
                                   :key="m.id"
                                   class="flex items-center gap-3 p-2 rounded-lg cursor-pointer hover:bg-white dark:hover:bg-slate-800 transition group">
                                <input type="checkbox"
                                       :value="m.id"
                                       v-model="form.manager_ids"
                                       class="w-5 h-5 text-indigo-600 rounded focus:ring-indigo-500 border-slate-300" />
                                <div class="flex items-center gap-2 flex-1">
                                    <div class="w-6 h-6 rounded-full bg-gradient-to-br from-indigo-400 to-purple-400 flex items-center justify-center text-white text-xs font-bold">
                                        {{ m.name?.charAt(0) || '👤' }}
                                    </div>
                                    <span class="text-sm text-slate-700 dark:text-slate-200">
                                        {{ m.id === currentUserId ? 'Я' : m.name }}
                                    </span>
                                </div>
                            </label>
                        </div>
                    </div>
                    <p v-if="errors.managers" class="text-xs text-rose-500 mt-1">{{ errors.managers }}</p>
                </div>

                <!-- Даты -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Дата старта</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-slate-400">📅</span>
                            </div>
                            <input type="date"
                                   v-model="form.start_date"
                                   required
                                   class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 focus:border-indigo-300 focus:ring focus:ring-indigo-200/20 transition" />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">
                            Длительность <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-slate-400">⏱️</span>
                            </div>
                            <input type="number"
                                   v-model="form.duration_days"
                                   min="1"
                                   placeholder="30"
                                   :class="[
                                       'w-full pl-10 pr-4 py-3 rounded-xl border transition-all',
                                       errors.duration
                                           ? 'border-rose-300 bg-rose-50 dark:bg-rose-950/20 focus:ring-rose-500'
                                           : 'border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 focus:border-indigo-300 focus:ring focus:ring-indigo-200/20'
                                   ]" />
                        </div>
                        <p v-if="errors.duration" class="text-xs text-rose-500 mt-1">{{ errors.duration }}</p>
                    </div>
                </div>

                <!-- Дополнительная информация -->
                <div class="p-4 bg-indigo-50 dark:bg-indigo-950/30 rounded-xl border border-indigo-100 dark:border-indigo-900">
                    <div class="flex items-start gap-3">
                        <div class="w-6 h-6 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center text-indigo-600 dark:text-indigo-400 text-sm">
                            💡
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-indigo-700 dark:text-indigo-300 uppercase tracking-wider">Дедлайн</h4>
                            <p v-if="form.start_date && form.duration_days" class="text-sm text-indigo-600 dark:text-indigo-400 mt-1">
                                {{ new Date(new Date(form.start_date).getTime() + form.duration_days * 24 * 60 * 60 * 1000).toLocaleDateString('ru-RU', {
                                day: 'numeric',
                                month: 'long',
                                year: 'numeric'
                            }) }}
                            </p>
                            <p v-else class="text-sm text-indigo-400 italic">Укажите дату старта и длительность</p>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="pt-4 flex justify-end gap-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button"
                            @click="$emit('close')"
                            class="px-6 py-3 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 font-medium transition-all">
                        Отмена
                    </button>

                    <button type="submit"
                            :disabled="loading"
                            class="group relative px-8 py-3 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold shadow-lg shadow-indigo-500/30 hover:shadow-xl hover:shadow-indigo-500/40 hover:scale-105 active:scale-95 transition-all duration-300 disabled:opacity-70 disabled:cursor-not-allowed disabled:hover:scale-100 overflow-hidden">

                        <!-- Эффект свечения при наведении -->
                        <div class="absolute inset-0 bg-white/20 translate-y-full group-hover:translate-y-0 transition-transform duration-300"></div>

                        <span class="relative flex items-center gap-2">
                            <svg v-if="loading" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ loading ? 'Создание...' : 'Создать проект' }}</span>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>
/* Анимации */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fadeIn {
    animation: fadeIn 0.3s ease-out;
}

.animate-slideUp {
    animation: slideUp 0.4s cubic-bezier(0.23, 1, 0.32, 1);
}

/* Кастомный скроллбар */
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 20px;
}

.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #475569;
}

/* Стили для инпутов */
input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
    opacity: 1;
    height: 24px;
}

/* Фокус-состояния */
input:focus, textarea:focus {
    outline: none;
}

/* Hover эффекты */
button:not(:disabled) {
    cursor: pointer;
}

/* Адаптивность */
@media (max-width: 640px) {
    .animate-slideUp {
        animation: none;
    }
}
</style>
