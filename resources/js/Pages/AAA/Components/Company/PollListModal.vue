<!-- resources/js/components/PollListModal.vue -->
<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import PollViewModal from './PollViewModal.vue'

const props = defineProps({
    companyId: Number
})

const emit = defineEmits(['close'])

// Состояния
const isLoading = ref(false)
const isDeleting = ref({})
const polls = ref([])
const selectedPollId = ref(null)
const showPollViewModal = ref(false)

// Загрузка опросов
const loadPolls = async () => {
    isLoading.value = true
    try {
        const response = await axios.get(`/api/companies/${props.companyId}/polls`)
        polls.value = response.data

        console.log('📊 Доступные опросы:', polls.value)
        console.log('📊 Количество:', polls.value.length)
    } catch (error) {
        console.error('Ошибка загрузки опросов:', error)
        alert('Не удалось загрузить опросы')
    } finally {
        isLoading.value = false
    }
}

// Открыть опрос
const openPoll = (pollId) => {
    selectedPollId.value = pollId
    showPollViewModal.value = true
}

// Удалить опрос
const deletePoll = async (pollId, event) => {
    event.stopPropagation()

    const poll = polls.value.find(p => p.id === pollId)
    if (!confirm(`Вы уверены, что хотите удалить опрос "${poll?.title || '#' + pollId}"?\n\nЭто действие необратимо. Будут удалены все ответы и комментарии.`)) {
        return
    }

    isDeleting.value[pollId] = true
    try {
        await axios.delete(`/api/polls/${pollId}`)
        await loadPolls()
        alert('Опрос успешно удален')
    } catch (error) {
        console.error('Ошибка удаления опроса:', error)
        alert(error.response?.data?.message || 'Ошибка при удалении опроса')
    } finally {
        isDeleting.value[pollId] = false
    }
}

// Обновить список после закрытия опроса
const handlePollUpdated = () => {
    showPollViewModal.value = false
    loadPolls()
}

// Форматирование даты
const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('ru-RU', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

// Подсчет прогресса
const getProgress = (poll) => {
    if (poll.participants_count === 0) return 0
    return Math.round((poll.responded_count / poll.participants_count) * 100)
}

// Статус опроса
const getStatusBadge = (status) => {
    if (status === 'active') {
        return { class: 'bg-emerald-500/20 text-emerald-700 dark:text-emerald-300', label: 'Активен' }
    }
    return { class: 'bg-slate-500/20 text-slate-700 dark:text-slate-300', label: 'Закрыт' }
}

// Проверка, ответил ли пользователь
const hasUserResponded = (poll) => {
    return poll.participants?.some(p => p.has_responded) || false
}

onMounted(() => {
    loadPolls()
})
</script>

<template>
    <div class="fixed inset-0 z-[70] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
        <div class="w-full max-w-5xl rounded-2xl bg-white dark:bg-slate-800 shadow-2xl flex flex-col max-h-[90vh]">
            <!-- Заголовок -->
            <div class="p-6 border-b dark:border-slate-700 flex justify-between items-center">
                <h3 class="text-2xl font-bold flex items-center gap-2">
                    <span>📊</span> Мои опросы
                    <span class="text-sm font-normal text-slate-500 ml-2">({{ polls.length }})</span>
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
                    <p class="mt-4 text-slate-500">Загрузка опросов...</p>
                </div>

                <div v-else-if="polls.length === 0" class="text-center py-10">
                    <div class="text-6xl mb-4">📋</div>
                    <h4 class="text-xl font-semibold text-slate-600 dark:text-slate-400">Нет доступных опросов</h4>
                    <p class="text-slate-500 mt-2">Вы не участвуете ни в одном опросе или опросы еще не созданы</p>
                
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div
                        v-for="poll in polls"
                        :key="poll.id"
                        class="border dark:border-slate-700 rounded-xl p-5 hover:shadow-lg transition cursor-pointer hover:border-indigo-500 relative"
                        @click="openPoll(poll.id)"
                    >
                        <!-- Кнопка удаления -->
                        <button
                            v-if="poll.can_delete"
                            @click="deletePoll(poll.id, $event)"
                            :disabled="isDeleting[poll.id]"
                            class="absolute top-3 right-3 p-1.5 rounded-lg bg-red-500/10 hover:bg-red-500/20 text-red-500 hover:text-red-600 transition disabled:opacity-50"
                            title="Удалить опрос"
                        >
                            <span v-if="isDeleting[poll.id]" class="inline-block w-4 h-4 border-2 border-red-500 border-t-transparent rounded-full animate-spin"></span>
                            <span v-else>🗑️</span>
                        </button>

                        <div class="flex justify-between items-start mb-3 pr-8">
                            <h4 class="text-lg font-bold text-slate-800 dark:text-white line-clamp-2">
                                {{ poll.title }}
                            </h4>
                            <span
                                :class="getStatusBadge(poll.status).class"
                                class="px-2 py-1 rounded-full text-xs font-medium whitespace-nowrap ml-2"
                            >
                                {{ getStatusBadge(poll.status).label }}
                            </span>
                        </div>

                        <p v-if="poll.description" class="text-sm text-slate-600 dark:text-slate-400 line-clamp-2 mb-3">
                            {{ poll.description }}
                        </p>

                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500">Участников:</span>
                                <span class="font-medium">{{ poll.participants_count }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500">Ответило:</span>
                                <span class="font-medium">{{ poll.responded_count }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500">Проблем:</span>
                                <span class="font-medium">{{ poll.problems_count || 0 }}</span>
                            </div>
                        </div>

                        <!-- Прогресс -->
                        <div class="mt-3">
                            <div class="flex justify-between text-xs text-slate-500 mb-1">
                                <span>Прогресс</span>
                                <span>{{ getProgress(poll) }}%</span>
                            </div>
                            <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2">
                                <div
                                    class="bg-indigo-600 h-2 rounded-full transition-all duration-500"
                                    :style="{ width: getProgress(poll) + '%' }"
                                ></div>
                            </div>
                        </div>

                        <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                            <div class="text-xs text-slate-400">
                                Создан: {{ formatDate(poll.created_at) }}
                                <span v-if="poll.creator" class="ml-2">
                                    автором: {{ poll.creator.name }}
                                </span>
                            </div>
                            <div>
                                <!-- Индикатор ответа пользователя -->
                                <span v-if="poll.is_participant && hasUserResponded(poll)"
                                      class="text-xs bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 px-2 py-1 rounded-full">
                                    ✅ Вы ответили
                                </span>
                                <span v-else-if="poll.is_participant && !hasUserResponded(poll)"
                                      class="text-xs bg-amber-500/20 text-amber-700 dark:text-amber-300 px-2 py-1 rounded-full">
                                    ⏳ Ожидает ответа
                                </span>
                                <span v-else-if="!poll.is_participant"
                                      class="text-xs bg-slate-500/20 text-slate-600 dark:text-slate-400 px-2 py-1 rounded-full">
                                    👤 Наблюдатель
                                </span>
                            </div>
                        </div>

                        <button
                            class="mt-3 w-full px-4 py-2 rounded-lg bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 font-medium transition text-sm"
                        >
                            Перейти к опросу →
                        </button>
                    </div>
                </div>
            </div>

            <!-- Футер -->
            <div class="p-6 border-t dark:border-slate-700 flex justify-between items-center">

                <button
                    @click="$emit('close')"
                    class="px-5 py-2 rounded-xl bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 transition font-medium"
                >
                    Закрыть
                </button>
            </div>
        </div>
    </div>

    <!-- Модалка просмотра опроса -->
    <PollViewModal
        v-if="showPollViewModal && selectedPollId"
        :poll-id="selectedPollId"
        @close="handlePollUpdated"
        @poll-deleted="handlePollUpdated"
    />
</template>
