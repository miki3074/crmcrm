<!-- resources/js/components/PollViewModal.vue -->
<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'

const props = defineProps({
    pollId: Number
})

const emit = defineEmits(['close', 'poll-deleted'])

// Состояния
const isLoading = ref(false)
const isSubmitting = ref(false)
const isCommenting = ref({})
const isEditing = ref({})
const isDeleting = ref({})
const isDeletingPoll = ref(false)
const poll = ref(null)
const showRespondForm = ref(false)
const newComments = ref({})
const editingItems = ref({})
const userResponses = ref([
    { problem: '', solution: '' }
])

// Состояния для добавления участников
const showAddParticipantsModal = ref(false)
const availableUsers = ref([])
const selectedNewParticipants = ref([])
const isAddingParticipants = ref(false)
const isLoadingAvailable = ref(false)

// Загрузка опроса
const loadPoll = async () => {
    isLoading.value = true
    try {
        const response = await axios.get(`/api/polls/${props.pollId}`)
        poll.value = response.data

        showRespondForm.value = poll.value.is_participant && poll.value.status === 'active'
        userResponses.value = [{ problem: '', solution: '' }]

        if (poll.value.problems) {
            poll.value.problems.forEach(problem => {
                newComments.value[problem.id] = ''
            })
        }
    } catch (error) {
        console.error('Ошибка загрузки опроса:', error)
        alert('Не удалось загрузить опрос')
    } finally {
        isLoading.value = false
    }
}

// Открыть модалку добавления участников
const openAddParticipantsModal = async () => {
    if (!poll.value?.can_manage) {
        alert('У вас нет прав для добавления участников')
        return
    }

    showAddParticipantsModal.value = true
    isLoadingAvailable.value = true
    selectedNewParticipants.value = []

    try {
        const response = await axios.get(`/api/polls/${props.pollId}/available-participants`)
        availableUsers.value = response.data

        if (availableUsers.value.length === 0) {
            alert('Все участники компании уже добавлены в опрос')
        }
    } catch (error) {
        console.error('Ошибка загрузки доступных участников:', error)
        alert('Не удалось загрузить доступных участников')
    } finally {
        isLoadingAvailable.value = false
    }
}

// Добавить выбранных участников
const addParticipants = async () => {
    if (selectedNewParticipants.value.length === 0) {
        alert('Выберите хотя бы одного участника')
        return
    }

    if (!confirm(`Добавить ${selectedNewParticipants.value.length} участников в опрос?`)) {
        return
    }

    isAddingParticipants.value = true
    try {
        const response = await axios.post(`/api/polls/${props.pollId}/add-participants`, {
            participants: selectedNewParticipants.value
        })

        let message = response.data.message
        if (response.data.added) {
            message += `\n✅ Добавлены: ${response.data.added}`
        }
        if (response.data.already_exist) {
            message += `\n⚠️ Уже участвуют: ${response.data.already_exist}`
        }

        alert(message)
        showAddParticipantsModal.value = false
        selectedNewParticipants.value = []
        await loadPoll()
    } catch (error) {
        console.error('Ошибка добавления участников:', error)
        alert(error.response?.data?.message || 'Ошибка при добавлении участников')
    } finally {
        isAddingParticipants.value = false
    }
}

// Выбрать всех доступных участников
const selectAllAvailable = () => {
    selectedNewParticipants.value = availableUsers.value.map(u => u.id)
}

// Снять всех
const deselectAllAvailable = () => {
    selectedNewParticipants.value = []
}

// Добавить новую пару проблема-решение
const addResponseField = () => {
    userResponses.value.push({ problem: '', solution: '' })
}

// Удалить пару проблема-решение
const removeResponseField = (index) => {
    if (userResponses.value.length <= 1) {
        alert('Должен быть хотя бы один ответ')
        return
    }
    userResponses.value.splice(index, 1)
}

// Отправить несколько ответов
const submitResponses = async () => {
    const hasEmpty = userResponses.value.some(
        r => !r.problem.trim() || !r.solution.trim()
    )

    if (hasEmpty) {
        alert('Заполните все поля: проблему и решение для каждого ответа')
        return
    }

    isSubmitting.value = true
    try {
        await axios.post(`/api/polls/${props.pollId}/respond-multiple`, {
            responses: userResponses.value
        })

        alert('Все ответы успешно сохранены!')
        userResponses.value = [{ problem: '', solution: '' }]
        await loadPoll()
    } catch (error) {
        console.error('Ошибка отправки ответов:', error)
        alert(error.response?.data?.message || 'Ошибка при отправке ответов')
    } finally {
        isSubmitting.value = false
    }
}

// Добавить комментарий
const addComment = async (problemId) => {
    const commentText = newComments.value[problemId]?.trim()
    if (!commentText) {
        alert('Введите текст комментария')
        return
    }

    isCommenting.value[problemId] = true
    try {
        await axios.post(`/api/polls/problems/${problemId}/comment`, {
            comment: commentText
        })
        newComments.value[problemId] = ''
        await loadPoll()
    } catch (error) {
        console.error('Ошибка добавления комментария:', error)
        alert(error.response?.data?.message || 'Ошибка при добавлении комментария')
    } finally {
        isCommenting.value[problemId] = false
    }
}

// Редактировать комментарий
const editComment = async (commentId) => {
    const newText = editingItems.value[`comment_${commentId}`]?.trim()
    if (!newText) {
        alert('Введите текст комментария')
        return
    }

    isEditing.value[`comment_${commentId}`] = true
    try {
        await axios.put(`/api/polls/comments/${commentId}`, {
            comment: newText
        })
        editingItems.value[`comment_${commentId}`] = ''
        await loadPoll()
    } catch (error) {
        console.error('Ошибка редактирования комментария:', error)
        alert(error.response?.data?.message || 'Ошибка при редактировании комментария')
    } finally {
        isEditing.value[`comment_${commentId}`] = false
    }
}

// Удалить комментарий
const deleteComment = async (commentId) => {
    if (!confirm('Вы уверены, что хотите удалить этот комментарий?')) return

    isDeleting.value[`comment_${commentId}`] = true
    try {
        await axios.delete(`/api/polls/comments/${commentId}`)
        await loadPoll()
    } catch (error) {
        console.error('Ошибка удаления комментария:', error)
        alert(error.response?.data?.message || 'Ошибка при удалении комментария')
    } finally {
        isDeleting.value[`comment_${commentId}`] = false
    }
}

// Редактировать проблему
const editProblem = async (problemId) => {
    const data = editingItems.value[`problem_${problemId}`]
    if (!data?.problem?.trim() || !data?.solution?.trim()) {
        alert('Заполните оба поля: проблему и решение')
        return
    }

    isEditing.value[`problem_${problemId}`] = true
    try {
        await axios.put(`/api/polls/problems/${problemId}`, {
            problem: data.problem,
            solution: data.solution
        })
        editingItems.value[`problem_${problemId}`] = null
        await loadPoll()
    } catch (error) {
        console.error('Ошибка редактирования проблемы:', error)
        alert(error.response?.data?.message || 'Ошибка при редактировании проблемы')
    } finally {
        isEditing.value[`problem_${problemId}`] = false
    }
}

// Удалить проблему
const deleteProblem = async (problemId) => {
    if (!confirm('Вы уверены, что хотите удалить эту проблему и все комментарии к ней?')) return

    isDeleting.value[`problem_${problemId}`] = true
    try {
        await axios.delete(`/api/polls/problems/${problemId}`)
        await loadPoll()
    } catch (error) {
        console.error('Ошибка удаления проблемы:', error)
        alert(error.response?.data?.message || 'Ошибка при удалении проблемы')
    } finally {
        isDeleting.value[`problem_${problemId}`] = false
    }
}

// Отметить проблему как решенную
const resolveProblem = async (problemId) => {
    if (!confirm('Отметить проблему как решенную?')) return

    try {
        await axios.post(`/api/polls/problems/${problemId}/resolve`)
        await loadPoll()
    } catch (error) {
        console.error('Ошибка:', error)
        alert('Ошибка при отметке решения')
    }
}

// Удалить опрос
const deletePoll = async () => {
    if (!confirm('Вы уверены, что хотите удалить этот опрос?\n\nЭто действие необратимо. Будут удалены все ответы и комментарии.')) {
        return
    }

    isDeletingPoll.value = true
    try {
        await axios.delete(`/api/polls/${props.pollId}`)
        alert('Опрос успешно удален')
        emit('poll-deleted')
        emit('close')
    } catch (error) {
        console.error('Ошибка удаления опроса:', error)
        alert(error.response?.data?.message || 'Ошибка при удалении опроса')
    } finally {
        isDeletingPoll.value = false
    }
}

// Закрыть опрос (для владельца)
const closePoll = async () => {
    if (!confirm('Закрыть опрос?')) return

    try {
        await axios.post(`/api/polls/${props.pollId}/close`)
        alert('Опрос закрыт')
        await loadPoll()
    } catch (error) {
        console.error('Ошибка закрытия опроса:', error)
        alert('Ошибка при закрытии опроса')
    }
}

// Открыть опрос заново
const reopenPoll = async () => {
    if (!confirm('Открыть опрос заново?')) return

    try {
        await axios.post(`/api/polls/${props.pollId}/reopen`)
        alert('Опрос переоткрыт')
        await loadPoll()
    } catch (error) {
        console.error('Ошибка переоткрытия опроса:', error)
        alert('Ошибка при переоткрытии опроса')
    }
}

// Начать редактирование комментария
const startEditComment = (comment) => {
    editingItems.value[`comment_${comment.id}`] = comment.comment
}

// Отменить редактирование
const cancelEdit = (id) => {
    editingItems.value[id] = null
}

// Начать редактирование проблемы
const startEditProblem = (problem) => {
    editingItems.value[`problem_${problem.id}`] = {
        problem: problem.problem,
        solution: problem.solution
    }
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

// Получить статус
const getStatusBadge = (status) => {
    if (status === 'active') {
        return { class: 'bg-emerald-500/20 text-emerald-700 dark:text-emerald-300', label: 'Активен' }
    }
    return { class: 'bg-slate-500/20 text-slate-700 dark:text-slate-300', label: 'Закрыт' }
}

// 🔥 COMPUTED - определяем здесь
const canComment = computed(() => {
    return poll.value?.is_participant || false
})

const isCreator = computed(() => {
    return poll.value?.is_creator || false
})

const canDeletePoll = computed(() => {
    return poll.value?.can_delete || false
})

const canManagePoll = computed(() => {
    return poll.value?.can_manage || false
})

// Проверка, является ли пользователь автором
const isAuthor = (item) => {
    return item.user_id === poll.value?.current_user_id
}

// Обработка Enter для комментариев
const handleCommentKeydown = (event, problemId) => {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault()
        addComment(problemId)
    }
}

onMounted(() => {
    loadPoll()
})
</script>

<template>
    <div class="fixed inset-0 z-[80] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
        <div class="w-full max-w-5xl rounded-2xl bg-white dark:bg-slate-800 shadow-2xl flex flex-col max-h-[90vh]">
            <!-- Заголовок -->
            <div class="p-6 border-b dark:border-slate-700 flex justify-between items-center">
                <h3 class="text-2xl font-bold flex items-center gap-2">
                    <span>📋</span> {{ poll?.title || 'Опрос' }}
                </h3>
                <div class="flex items-center gap-2">
                    <!-- Кнопка добавления участников -->
                    <button
                        v-if="canManagePoll && poll?.status === 'active'"
                        @click="openAddParticipantsModal"
                        class="px-3 py-1.5 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white transition text-sm font-medium flex items-center gap-1"
                    >
                        ➕ Добавить участников
                    </button>
<!--                    <button-->
<!--                        v-if="canDeletePoll"-->
<!--                        @click="deletePoll"-->
<!--                        :disabled="isDeletingPoll"-->
<!--                        class="px-3 py-1.5 rounded-lg bg-red-500 hover:bg-red-600 text-white transition text-sm font-medium disabled:opacity-50 flex items-center gap-1"-->
<!--                    >-->
<!--                        <span v-if="isDeletingPoll" class="animate-spin inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full"></span>-->
<!--                        <span v-else>🗑️ Удалить опрос</span>-->
<!--                    </button>-->
                    <button
                        @click="$emit('close')"
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-white transition text-2xl"
                    >
                        ✕
                    </button>
                </div>
            </div>

            <!-- Тело -->
            <div class="p-6 overflow-y-auto flex-1">
                <div v-if="isLoading" class="text-center py-10">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mx-auto"></div>
                    <p class="mt-4 text-slate-500">Загрузка опроса...</p>
                </div>

                <div v-else-if="poll">
                    <!-- Информация об опросе -->
                    <div class="mb-6">
                        <div class="flex flex-wrap items-center gap-3 mb-3">
                            <span
                                :class="getStatusBadge(poll.status).class"
                                class="px-3 py-1 rounded-full text-sm font-medium"
                            >
                                {{ getStatusBadge(poll.status).label }}
                            </span>
                            <span class="text-sm text-slate-500">
                                Создан: {{ formatDate(poll.created_at) }}
                            </span>
                            <span class="text-sm text-slate-500">
                                Автор: {{ poll.creator?.name || 'Неизвестен' }}
                            </span>
                        </div>

                        <p v-if="poll.description" class="text-slate-600 dark:text-slate-400">
                            {{ poll.description }}
                        </p>

<!--                        <div class="grid grid-cols-4 gap-4 mt-4">-->
<!--                            <div class="bg-slate-50 dark:bg-slate-900/50 rounded-xl p-3 text-center">-->
<!--                                <div class="text-2xl font-bold text-indigo-600">{{ poll.participants_count }}</div>-->
<!--                                <div class="text-xs text-slate-500">Участников</div>-->
<!--                            </div>-->
<!--                            <div class="bg-slate-50 dark:bg-slate-900/50 rounded-xl p-3 text-center">-->
<!--                                <div class="text-2xl font-bold text-emerald-600">{{ poll.responded_count }}</div>-->
<!--                                <div class="text-xs text-slate-500">Ответило</div>-->
<!--                            </div>-->
<!--                            <div class="bg-slate-50 dark:bg-slate-900/50 rounded-xl p-3 text-center">-->
<!--                                <div class="text-2xl font-bold text-amber-600">{{ poll.problems_count || 0 }}</div>-->
<!--                                <div class="text-xs text-slate-500">Проблем</div>-->
<!--                            </div>-->
<!--                            <div class="bg-slate-50 dark:bg-slate-900/50 rounded-xl p-3 text-center">-->
<!--                                <div class="text-2xl font-bold text-purple-600">{{ poll.participants_count - poll.responded_count }}</div>-->
<!--                                <div class="text-xs text-slate-500">Не ответили</div>-->
<!--                            </div>-->
<!--                        </div>-->

                        <!-- Кнопки действий -->
                        <div class="flex flex-wrap gap-2 mt-4">
                            <button
                                v-if="isCreator && poll.status === 'active'"
                                @click="closePoll"
                                class="px-4 py-2 rounded-xl bg-amber-500/20 hover:bg-amber-500/30 text-amber-700 dark:text-amber-400 font-medium transition text-sm"
                            >
                                🔒 Закрыть опрос
                            </button>
                            <button
                                v-if="isCreator && poll.status === 'closed'"
                                @click="reopenPoll"
                                class="px-4 py-2 rounded-xl bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-700 dark:text-emerald-400 font-medium transition text-sm"
                            >
                                🔓 Открыть заново
                            </button>
                        </div>

                        <!-- Информация о возможности отвечать -->

                    </div>

                    <!-- Форма ответа -->
                    <div v-if="showRespondForm && poll.status === 'active'" class="mb-6">
                        <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-xl p-4 border border-indigo-200 dark:border-indigo-800">
                            <div class="flex justify-between items-center mb-3">
                                <h4 class="font-bold text-indigo-700 dark:text-indigo-400">
                                    📝 Добавить проблему и решение
                                </h4>
                                <span class="text-sm text-indigo-600 dark:text-indigo-400">
                                    {{ userResponses.length }} проблема(ы)
                                </span>
                            </div>

                            <form @submit.prevent="submitResponses">
                                <div
                                    v-for="(response, index) in userResponses"
                                    :key="index"
                                    class="mb-4 p-3 bg-white/50 dark:bg-slate-800/50 rounded-lg border border-indigo-100 dark:border-indigo-900"
                                >
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-sm font-medium text-indigo-600 dark:text-indigo-400">
                                            Ответ #{{ index + 1 }}
                                        </span>
                                        <button
                                            type="button"
                                            @click="removeResponseField(index)"
                                            class="text-xs text-red-500 hover:text-red-700 dark:text-red-400 transition"
                                            :disabled="userResponses.length <= 1"
                                        >
                                            🗑️ Удалить
                                        </button>
                                    </div>

                                    <div class="mb-2">
                                        <label class="block text-sm font-medium mb-1 text-slate-700 dark:text-slate-300">
                                            Проблема *
                                        </label>
                                        <textarea
                                            v-model="response.problem"
                                            rows="2"
                                            class="w-full px-4 py-2 rounded-lg border dark:border-slate-600 bg-white dark:bg-slate-900 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                                            placeholder="Опишите проблему..."
                                            required
                                        ></textarea>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium mb-1 text-slate-700 dark:text-slate-300">
                                            Решение *
                                        </label>
                                        <textarea
                                            v-model="response.solution"
                                            rows="2"
                                            class="w-full px-4 py-2 rounded-lg border dark:border-slate-600 bg-white dark:bg-slate-900 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                                            placeholder="Предложите решение..."
                                            required
                                        ></textarea>
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-2 mt-3">
                                    <button
                                        type="button"
                                        @click="addResponseField"
                                        class="px-4 py-2 rounded-lg bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-700 dark:text-emerald-400 font-medium transition text-sm flex items-center gap-1"
                                    >
                                        ➕ Добавить еще
                                    </button>

                                    <button
                                        type="submit"
                                        :disabled="isSubmitting"
                                        class="px-6 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-bold transition disabled:opacity-50 flex items-center gap-2"
                                    >
                                        <span v-if="isSubmitting" class="animate-spin inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full"></span>
                                        {{ isSubmitting ? 'Отправка...' : 'Отправить все ответы' }}
                                    </button>
                                </div>


                            </form>
                        </div>
                    </div>

                    <!-- Список проблем -->
                    <div>
                        <h4 class="font-bold text-lg mb-3 flex items-center gap-2">
                            <span>💡</span> Все проблемы и решения
                            <span class="text-sm font-normal text-slate-500">
                                ({{ poll.problems?.length || 0 }})
                            </span>
                        </h4>

                        <div v-if="poll.problems?.length === 0" class="text-center py-6 text-slate-500">
                            Пока никто не ответил на опрос
                        </div>

                        <div v-else class="space-y-4">
                            <div
                                v-for="problem in poll.problems"
                                :key="problem.id"
                                class="border dark:border-slate-700 rounded-xl p-4 hover:shadow-md transition"
                            >
                                <!-- Заголовок проблемы -->
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <span class="font-semibold text-indigo-600 dark:text-indigo-400">
                                            {{ problem.user?.name || 'Неизвестный' }}
                                            <span v-if="isAuthor(problem)" class="text-xs text-indigo-400 ml-1">(Вы)</span>
                                        </span>
                                        <span class="text-xs text-slate-500 ml-2">
                                            {{ formatDate(problem.created_at) }}
                                        </span>
                                        <span v-if="problem.updated_at !== problem.created_at" class="text-xs text-slate-400 ml-1">
                                            (ред.)
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span
                                            v-if="problem.is_resolved"
                                            class="px-2 py-1 rounded-full text-xs bg-emerald-500/20 text-emerald-700 dark:text-emerald-300"
                                        >
                                            ✅ Решено
                                        </span>

                                        <button
                                            v-if="!problem.is_resolved && poll.is_participant && !isAuthor(problem)"
                                            @click="resolveProblem(problem.id)"
                                            class="text-xs bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 px-2 py-1 rounded-lg transition"
                                        >
                                            Отметить как решенное
                                        </button>
                                    </div>
                                </div>

                                <!-- Содержимое проблемы -->
                                <div v-if="editingItems[`problem_${problem.id}`]">
                                    <!-- ... редактирование ... -->
                                </div>
                                <div v-else>
                                    <div class="mb-2">
                                        <div class="text-sm font-medium text-slate-700 dark:text-slate-300">Проблема:</div>
                                        <p class="text-slate-600 dark:text-slate-400">{{ problem.problem }}</p>
                                    </div>

                                    <div class="mb-3">
                                        <div class="text-sm font-medium text-slate-700 dark:text-slate-300">Решение:</div>
                                        <p class="text-slate-600 dark:text-slate-400">{{ problem.solution }}</p>
                                    </div>
                                </div>

                                <!-- Комментарии -->
                                <div v-if="problem.comments?.length" class="mt-3 pl-4 border-l-2 border-indigo-200 dark:border-indigo-800">
                                    <div class="text-sm font-medium text-slate-600 dark:text-slate-400 mb-2">
                                        💬 Комментарии ({{ problem.comments.length }}):
                                    </div>
                                    <!-- ... комментарии ... -->
                                </div>

                                <!-- Добавление комментария -->
                                <div v-if="canComment && poll.status === 'active'" class="mt-3">
                                    <div class="flex gap-2 items-start">
                                        <div class="flex-1">
                                            <textarea
                                                v-model="newComments[problem.id]"
                                                @keydown="handleCommentKeydown($event, problem.id)"
                                                :disabled="isCommenting[problem.id]"
                                                rows="1"
                                                class="w-full px-3 py-2 rounded-lg border dark:border-slate-600 bg-white dark:bg-slate-900 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition text-sm resize-none"
                                                :placeholder="isCommenting[problem.id] ? 'Отправка...' : 'Напишите комментарий... (Enter для отправки)'"
                                            ></textarea>
                                        </div>
                                        <button
                                            @click="addComment(problem.id)"
                                            :disabled="isCommenting[problem.id] || !newComments[problem.id]?.trim()"
                                            class="px-4 py-2 rounded-lg bg-indigo-500 hover:bg-indigo-600 text-white font-medium transition text-sm disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1 whitespace-nowrap"
                                        >
                                            <span v-if="isCommenting[problem.id]" class="animate-spin inline-block w-3 h-3 border-2 border-white border-t-transparent rounded-full"></span>
                                            <span v-else>📨 Отправить</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Футер -->
            <div class="p-6 border-t dark:border-slate-700 flex justify-between items-center">
                <div class="text-sm text-slate-400">
                    <span v-if="poll?.is_participant">✅ Вы участник опроса</span>
                    <span v-else>👤 Вы не участвуете в этом опросе</span>
                    <span v-if="canManagePoll" class="ml-3 text-emerald-400">👑 Управление опросом</span>
                </div>
                <button
                    @click="$emit('close')"
                    class="px-5 py-2 rounded-xl bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 transition font-medium"
                >
                    Закрыть
                </button>
            </div>
        </div>
    </div>

    <!-- Модалка добавления участников -->
    <div v-if="showAddParticipantsModal" class="fixed inset-0 z-[90] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
        <div class="w-full max-w-2xl rounded-2xl bg-white dark:bg-slate-800 shadow-2xl flex flex-col max-h-[80vh]">
            <!-- Заголовок -->
            <div class="p-6 border-b dark:border-slate-700 flex justify-between items-center">
                <h3 class="text-xl font-bold flex items-center gap-2">
                    <span>👥</span> Добавить участников в опрос
                </h3>
                <button
                    @click="showAddParticipantsModal = false"
                    class="text-slate-400 hover:text-slate-600 dark:hover:text-white transition text-2xl"
                >
                    ✕
                </button>
            </div>

            <!-- Тело -->
            <div class="p-6 overflow-y-auto flex-1">
                <div v-if="isLoadingAvailable" class="text-center py-10">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-emerald-600 mx-auto"></div>
                    <p class="mt-4 text-slate-500">Загрузка доступных участников...</p>
                </div>

                <div v-else>
                    <div class="mb-4 p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg border border-emerald-200 dark:border-emerald-800">
                        <p class="text-sm text-emerald-700 dark:text-emerald-400">
                            <span class="font-bold">{{ availableUsers.length }}</span> участников доступно для добавления
                        </p>
                    </div>

                    <div v-if="availableUsers.length === 0" class="text-center py-6">
                        <div class="text-4xl mb-3">✅</div>
                        <p class="text-slate-500">Все участники компании уже добавлены в опрос</p>
                    </div>

                    <div v-else>
                        <div class="flex justify-between items-center mb-3">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                Выбрано: {{ selectedNewParticipants.length }}
                            </span>
                            <div class="flex gap-2">
                                <button
                                    @click="selectAllAvailable"
                                    class="text-xs bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 px-3 py-1 rounded-lg hover:bg-emerald-500/20 transition"
                                >
                                    Выбрать всех
                                </button>
                                <button
                                    @click="deselectAllAvailable"
                                    class="text-xs bg-slate-500/10 text-slate-600 dark:text-slate-400 px-3 py-1 rounded-lg hover:bg-slate-500/20 transition"
                                >
                                    Снять всех
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 max-h-60 overflow-y-auto p-3 border dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-900/50">
                            <label
                                v-for="user in availableUsers"
                                :key="user.id"
                                class="flex items-center gap-2 p-2 rounded-lg hover:bg-white dark:hover:bg-slate-800 transition cursor-pointer"
                            >
                                <input
                                    type="checkbox"
                                    :value="user.id"
                                    v-model="selectedNewParticipants"
                                    class="rounded border-slate-300 dark:border-slate-600 text-emerald-600 focus:ring-emerald-500"
                                />
                                <span class="text-sm">{{ user.name }}</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Футер -->
            <div class="p-6 border-t dark:border-slate-700 flex justify-end gap-3">
                <button
                    @click="showAddParticipantsModal = false"
                    class="px-5 py-2 rounded-xl bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 transition font-medium"
                >
                    Отмена
                </button>
                <button
                    @click="addParticipants"
                    :disabled="isAddingParticipants || selectedNewParticipants.length === 0"
                    class="px-6 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                >
                    <span v-if="isAddingParticipants" class="animate-spin inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full"></span>
                    {{ isAddingParticipants ? 'Добавление...' : `Добавить (${selectedNewParticipants.length})` }}
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
textarea {
    min-height: 38px;
    max-height: 150px;
}
</style>
