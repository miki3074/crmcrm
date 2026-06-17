<!-- resources/js/components/PollCreateModal.vue -->
<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps({
    companyId: Number
})

const emit = defineEmits(['close', 'created'])

// Состояния
const isLoading = ref(false)
const isSubmitting = ref(false)
const users = ref([])
const form = ref({
    title: '',
    description: '',
    participants: []
})

// Обработка выбора всех участников
const selectAll = () => {
    form.value.participants = users.value.map(u => u.id)
}

const deselectAll = () => {
    form.value.participants = []
}

// Загрузка участников компании
const loadUsers = async () => {
    isLoading.value = true
    try {
        const response = await axios.get(`/api/companies/${props.companyId}/users`)
        users.value = response.data
        // По умолчанию выбираем всех
        form.value.participants = users.value.map(u => u.id)
    } catch (error) {
        console.error('Ошибка загрузки участников:', error)
        alert('Не удалось загрузить участников компании')
    } finally {
        isLoading.value = false
    }
}

// Создание опроса
const createPoll = async () => {
    if (!form.value.title.trim()) {
        alert('Введите название опроса')
        return
    }

    if (form.value.participants.length === 0) {
        alert('Выберите хотя бы одного участника')
        return
    }

    isSubmitting.value = true
    try {
        const response = await axios.post('/api/polls', {
            company_id: props.companyId,
            title: form.value.title,
            description: form.value.description,
            participants: form.value.participants
        })

        emit('created', response.data)
        alert('Опрос успешно создан!')
    } catch (error) {
        console.error('Ошибка создания опроса:', error)
        alert(error.response?.data?.message || 'Ошибка при создании опроса')
    } finally {
        isSubmitting.value = false
    }
}

onMounted(() => {
    loadUsers()
})
</script>

<template>
    <div class="fixed inset-0 z-[70] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
        <div class="w-full max-w-2xl rounded-2xl bg-white dark:bg-slate-800 shadow-2xl flex flex-col max-h-[90vh]">
            <!-- Заголовок -->
            <div class="p-6 border-b dark:border-slate-700 flex justify-between items-center">
                <h3 class="text-2xl font-bold flex items-center gap-2">
                    <span>📋</span> Создать опрос
                </h3>
                <button
                    @click="$emit('close')"
                    class="text-slate-400 hover:text-slate-600 dark:hover:text-white transition text-2xl"
                >
                    ✕
                </button>
            </div>

            <!-- Тело -->
            <div class="p-6 overflow-y-auto flex-1">
                <div v-if="isLoading" class="text-center py-10">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mx-auto"></div>
                    <p class="mt-4 text-slate-500">Загрузка участников...</p>
                </div>

                <div v-else>
                    <form @submit.prevent="createPoll">
                        <!-- Название -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2 text-slate-700 dark:text-slate-300">
                                Название опроса *
                            </label>
                            <input
                                v-model="form.title"
                                type="text"
                                class="w-full px-4 py-2 rounded-xl border dark:border-slate-600 bg-white dark:bg-slate-900 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                                placeholder="Введите название опроса"
                                required
                            />
                        </div>

                        <!-- Описание -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2 text-slate-700 dark:text-slate-300">
                                Описание
                            </label>
                            <textarea
                                v-model="form.description"
                                rows="3"
                                class="w-full px-4 py-2 rounded-xl border dark:border-slate-600 bg-white dark:bg-slate-900 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                                placeholder="Опишите цель опроса..."
                            ></textarea>
                        </div>

                        <!-- Участники -->
                        <div class="mb-4">
                            <div class="flex justify-between items-center mb-2">
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                                    Участники * ({{ form.participants.length }} выбрано)
                                </label>
                                <div class="flex gap-2">
                                    <button
                                        type="button"
                                        @click="selectAll"
                                        class="text-xs bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 px-3 py-1 rounded-lg hover:bg-indigo-500/20 transition"
                                    >
                                        Выбрать всех
                                    </button>
                                    <button
                                        type="button"
                                        @click="deselectAll"
                                        class="text-xs bg-slate-500/10 text-slate-600 dark:text-slate-400 px-3 py-1 rounded-lg hover:bg-slate-500/20 transition"
                                    >
                                        Снять всех
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-48 overflow-y-auto p-3 border dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-900/50">
                                <label
                                    v-for="user in users"
                                    :key="user.id"
                                    class="flex items-center gap-2 p-2 rounded-lg hover:bg-white dark:hover:bg-slate-800 transition cursor-pointer"
                                >
                                    <input
                                        type="checkbox"
                                        :value="user.id"
                                        v-model="form.participants"
                                        class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500"
                                    />
                                    <span class="text-sm">{{ user.name }}</span>
                                </label>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Футер -->
            <div class="p-6 border-t dark:border-slate-700 flex justify-end gap-3">
                <button
                    @click="$emit('close')"
                    class="px-5 py-2 rounded-xl bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 transition font-medium"
                >
                    Отмена
                </button>
                <button
                    @click="createPoll"
                    :disabled="isSubmitting || !form.title.trim() || form.participants.length === 0"
                    class="px-6 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                >
                    <span v-if="isSubmitting" class="animate-spin inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full"></span>
                    {{ isSubmitting ? 'Создание...' : 'Создать опрос' }}
                </button>
            </div>
        </div>
    </div>
</template>
