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
        // Получаем данные о компании (владельца)
        const companyResponse = await axios.get(`/api/companies/${props.companyId}`)
        const ownerId = companyResponse.data.user_id
        const companyName = companyResponse.data.name

        console.log(`🏢 Компания: ${companyName}`)
        console.log(`👑 Владелец ID: ${ownerId}`)

        // Получаем всех пользователей компании
        const response = await axios.get(`/api/companies/${props.companyId}/users`)

        console.log('📊 Данные от API:', response.data)

        // 🔥 Преобразуем данные, определяя владельца по ID из компании
        let formattedUsers = response.data.map(user => {
            const isOwner = user.id === ownerId

            return {
                id: user.id,
                name: user.name,
                email: user.email,
                role: isOwner ? 'owner' : (user.pivot?.role || user.role || 'member'),
                is_owner: isOwner
            }
        })

        // 🔥 Проверяем, есть ли владелец в списке
        const ownerExists = formattedUsers.some(u => u.id === ownerId)

        if (!ownerExists && ownerId) {
            // Если владельца нет в списке - добавляем
            try {
                const ownerResponse = await axios.get(`/api/users/${ownerId}`)
                formattedUsers.push({
                    id: ownerResponse.data.id,
                    name: ownerResponse.data.name,
                    email: ownerResponse.data.email,
                    role: 'owner',
                    is_owner: true
                })
                console.log('👑 Владелец добавлен вручную:', ownerResponse.data.name)
            } catch (error) {
                console.warn('Не удалось получить данные владельца:', error)
            }
        }

        console.log('📊 Преобразованные пользователи:', formattedUsers)

        users.value = formattedUsers

        // По умолчанию выбираем всех участников
        form.value.participants = users.value.map(u => u.id)

        console.log('✅ Загружено пользователей:', users.value.length)
        console.log('👑 Владелец компании:', users.value.find(u => u.is_owner)?.name || 'Не найден')
    } catch (error) {
        console.error('Ошибка загрузки:', error)
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
        emit('close')
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
    <div class="fixed inset-0 z-[70] flex items-center justify-center bg-slate-950/60 backdrop-blur-sm p-4">
        <div class="w-full max-w-2xl rounded-xl bg-white dark:bg-slate-800 shadow-2xl flex flex-col max-h-[88vh]">
            <!-- Заголовок -->
            <div class="px-4 py-3 border-b dark:border-slate-700 flex justify-between items-center">
                <h3 class="text-lg font-semibold flex items-center gap-2">
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
            <div class="p-4 overflow-y-auto flex-1">
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
                                class="w-full px-3 py-2 rounded-lg border dark:border-slate-600 bg-white dark:bg-slate-900 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
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
                                class="w-full px-3 py-2 rounded-lg border dark:border-slate-600 bg-white dark:bg-slate-900 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
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
                                    <span class="text-sm flex items-center gap-1">
                                        {{ user.name }}
                                        <!-- Владелец -->
                                        <span v-if="user.is_owner === true" class="text-amber-500" title="Владелец компании">

                                        </span>
                                        <!-- Менеджер -->
                                        <span v-else-if="user.role === 'manager'" class="text-xs text-blue-400 ml-1">

                                        </span>
                                        <!-- Сотрудник -->
                                        <span v-else-if="user.role === 'employee'" class="text-xs text-green-400 ml-1">

                                        </span>
                                    </span>
                                </label>
                            </div>

                            <div class="text-xs text-slate-400 mt-1 flex items-center gap-2 flex-wrap">

                            </div>
                        </div>

                        <!-- Информация о выбранных участниках -->
                        <div v-if="form.participants.length > 0" class="text-xs text-slate-400 mt-2 p-2 bg-slate-50 dark:bg-slate-900/30 rounded-lg">
                            Выбрано: <span class="font-medium text-slate-700 dark:text-slate-300">{{ form.participants.length }}</span> участников
                            <span class="ml-2">
                                ({{ users.filter(u => form.participants.includes(u.id)).map(u => u.name).join(', ') }})
                            </span>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Футер -->
            <div class="px-4 py-3 border-t dark:border-slate-700 flex justify-end gap-3">
                <button
                    @click="$emit('close')"
                    class="h-9 px-3 rounded-lg text-sm bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 transition font-medium"
                >
                    Отмена
                </button>
                <button
                    @click="createPoll"
                    :disabled="isSubmitting || !form.title.trim() || form.participants.length === 0"
                    class="h-9 px-3 rounded-lg text-sm bg-indigo-600 hover:bg-indigo-700 text-white font-bold transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                >
                    <span v-if="isSubmitting" class="animate-spin inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full"></span>
                    {{ isSubmitting ? 'Создание...' : 'Создать опрос' }}
                </button>
            </div>
        </div>
    </div>
</template>
