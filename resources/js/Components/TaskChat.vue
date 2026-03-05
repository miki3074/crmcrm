<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick, computed, watch } from 'vue'
import axios from 'axios'
import { usePage } from '@inertiajs/vue3'

const props = defineProps({
    taskId: { type: Number, required: true },
    canChat: { type: Boolean, default: true },
    members: { type: Array, default: () => [] }
})

const page = usePage()
const currentUser = computed(() => page.props.auth.user)

// Состояния
const comments = ref([])
const body = ref('')
const loading = ref(false)
const sending = ref(false)
const error = ref('')
const unreadCount = ref(0)
const hasNewMessages = ref(false)

// --- Для Mention ---
const mentionOpen = ref(false)
const mentionSearch = ref('')
const mentionList = ref([])
const caretIndex = ref(0)
// ------------------

// --- Для Reply ---
const replyingTo = ref(null)
const textareaRef = ref(null)
// -----------------

// --- Для редактирования ---
const editingComment = ref(null)
const editBody = ref('')
// ------------------------

let timer = null
let lastMessageId = null
const commentsContainer = ref(null)

// Computed для текущего сообщения (решает проблему с v-model)
const currentMessage = computed({
    get: () => editingComment.value ? editBody.value : body.value,
    set: (value) => {
        if (editingComment.value) {
            editBody.value = value
        } else {
            body.value = value
        }
    }
})

// Вычисляемые свойства
const sortedComments = computed(() => {
    return [...comments.value].sort((a, b) => new Date(a.created_at) - new Date(b.created_at))
})

const messageCount = computed(() => comments.value.length)

// --- Загрузка комментариев ---
const fetchComments = async (showLoader = false) => {
    if (showLoader) loading.value = true
    try {
        const { data } = await axios.get(`/api/tasks/${props.taskId}/comments`, {
            withCredentials: true
        })

        // Проверяем новые сообщения
        if (comments.value.length && data.length > comments.value.length) {
            hasNewMessages.value = true
            unreadCount.value = data.length - comments.value.length
        }

        comments.value = data

        // Обновляем последний ID для проверки новых
        if (data.length) {
            lastMessageId = data[data.length - 1].id
        }
    } catch (e) {
        console.error("Ошибка загрузки комментариев", e)
        error.value = 'Не удалось загрузить сообщения'
    } finally {
        if (showLoader) loading.value = false
    }
}

// --- Отправка сообщения ---
const send = async () => {
    if (!body.value.trim() || sending.value) return

    sending.value = true
    error.value = ''

    await axios.get('/sanctum/csrf-cookie')

    const payload = {
        body: body.value,
        parent_id: replyingTo.value ? replyingTo.value.id : null,
        edit_id: editingComment.value ? editingComment.value.id : null
    }

    try {
        await axios.post(`/api/tasks/${props.taskId}/comments`, payload, {
            withCredentials: true
        })

        body.value = ''
        cancelReply()
        cancelEdit()
        await fetchComments()
        scrollToBottom()
        hasNewMessages.value = false
        unreadCount.value = 0

    } catch (e) {
        error.value = 'Не удалось отправить сообщение'
        console.error(e)
    } finally {
        sending.value = false
    }
}

// --- Редактирование ---
const startEdit = (comment) => {
    editingComment.value = comment
    editBody.value = comment.body
    replyingTo.value = null
    nextTick(() => textareaRef.value?.focus())
}

const cancelEdit = () => {
    editingComment.value = null
    editBody.value = ''
}

const saveEdit = async () => {
    if (!editBody.value.trim() || !editingComment.value) return

    try {
        await axios.put(`/api/task-comments/${editingComment.value.id}`, {
            body: editBody.value
        })
        await fetchComments()
        cancelEdit()
    } catch (e) {
        error.value = 'Не удалось сохранить изменения'
    }
}

// --- Удаление ---
const remove = async (commentId) => {
    if (!confirm('Удалить сообщение?')) return

    try {
        await axios.delete(`/api/task-comments/${commentId}`)
        await fetchComments()
    } catch (e) {
        error.value = 'Не удалось удалить сообщение'
    }
}

// --- Reply ---
const startReply = (comment) => {
    replyingTo.value = comment
    editingComment.value = null
    nextTick(() => textareaRef.value?.focus())
}

const cancelReply = () => {
    replyingTo.value = null
}

// --- Скролл ---
const scrollToBottom = (smooth = true) => {
    nextTick(() => {
        if (commentsContainer.value) {
            commentsContainer.value.scrollTo({
                top: commentsContainer.value.scrollHeight,
                behavior: smooth ? 'smooth' : 'auto'
            })
        }
    })
}

const scrollToComment = (commentId) => {
    const element = document.getElementById(`comment-${commentId}`)
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'center' })
        element.classList.add('highlight-pulse')
        setTimeout(() => element.classList.remove('highlight-pulse'), 2000)
    }
}

// --- Mention Logic ---
const onInput = (e) => {
    // Авто-высота
    e.target.style.height = 'auto'
    e.target.style.height = (e.target.scrollHeight) + 'px'

    const value = currentMessage.value
    const pos = e.target.selectionStart
    caretIndex.value = pos
    const char = value[pos - 1]

    if (char === '@') {
        mentionOpen.value = true
        mentionSearch.value = ''
        mentionList.value = props.members
        return
    }

    if (mentionOpen.value) {
        const match = value.slice(0, pos).match(/@([\p{L}0-9_]*)$/u)
        if (!match) {
            mentionOpen.value = false
            return
        }
        mentionSearch.value = match[1].toLowerCase()
        mentionList.value = props.members.filter(m =>
            m.name.toLowerCase().includes(mentionSearch.value)
        )
    }
}

const selectMention = (user) => {
    const text = currentMessage.value
    const newText = text.replace(/@([\p{L}0-9_]*)$/u, '@' + user.name.replace(/\s+/g, '_') + ' ')

    currentMessage.value = newText

    mentionOpen.value = false
    nextTick(() => textareaRef.value?.focus())
}

// --- Форматирование ---
const highlightMentions = (text) => {
    if (!text) return ''
    return text.replace(/@([\p{L}0-9_]+)/gu, (match, nameToken) => {
        const visibleName = nameToken.replace(/_/g, ' ')
        const user = props.members.find(m =>
            m.name.replace(/\s+/g, '_') === nameToken
        )
        return `<span class="inline-flex items-center gap-1 text-indigo-600 font-semibold bg-indigo-50 px-1.5 py-0.5 rounded-full text-xs">
            @${visibleName}
            ${user ? `<span class="w-1 h-1 bg-indigo-400 rounded-full"></span>` : ''}
        </span>`
    })
}

// --- Утилиты ---
const getInitials = (name) => {
    return name
        ? name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase()
        : '?'
}

const getAvatarColor = (id) => {
    const colors = [
        'bg-red-100 text-red-600',
        'bg-blue-100 text-blue-600',
        'bg-green-100 text-green-600',
        'bg-amber-100 text-amber-600',
        'bg-purple-100 text-purple-600',
        'bg-pink-100 text-pink-600',
        'bg-indigo-100 text-indigo-600'
    ]
    return colors[(id || 0) % colors.length]
}

const formatTime = (dateStr) => {
    const date = new Date(dateStr)
    const now = new Date()
    const yesterday = new Date(now)
    yesterday.setDate(yesterday.getDate() - 1)

    if (date.toDateString() === now.toDateString()) {
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    } else if (date.toDateString() === yesterday.toDateString()) {
        return 'Вчера ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    } else {
        return date.toLocaleDateString([], {
            day: 'numeric',
            month: 'short',
            hour: '2-digit',
            minute: '2-digit'
        })
    }
}

const isEdited = (comment) => {
    return comment.created_at !== comment.updated_at
}

// --- Отслеживание новых сообщений ---
watch(comments, (newComments, oldComments) => {
    if (oldComments?.length && newComments.length > oldComments.length) {
        if (!isUserAtBottom()) {
            hasNewMessages.value = true
            unreadCount.value = newComments.length - oldComments.length
        } else {
            scrollToBottom()
        }
    }
})

const isUserAtBottom = () => {
    if (!commentsContainer.value) return false
    const { scrollTop, scrollHeight, clientHeight } = commentsContainer.value
    return Math.abs(scrollHeight - clientHeight - scrollTop) < 10
}

const scrollToBottomIfNeeded = () => {
    if (isUserAtBottom()) {
        scrollToBottom()
        hasNewMessages.value = false
        unreadCount.value = 0
    }
}

// --- Жизненный цикл ---
onMounted(async () => {
    await fetchComments(true)
    scrollToBottom(false)

    // Периодическое обновление
    timer = setInterval(fetchComments, 10000)

    // Отслеживаем скролл
    commentsContainer.value?.addEventListener('scroll', scrollToBottomIfNeeded)
})

onBeforeUnmount(() => {
    if (timer) clearInterval(timer)
    commentsContainer.value?.removeEventListener('scroll', scrollToBottomIfNeeded)
})
</script>

<template>
    <div class="flex flex-col h-[600px] bg-gray-50 dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">

        <!-- Шапка чата -->
        <div class="px-5 py-3 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center shadow-sm z-10">
            <h3 class="font-bold text-gray-700 dark:text-gray-200 flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                </svg>
                Обсуждение задачи
            </h3>
            <div class="flex items-center gap-3">
                <span class="text-xs text-gray-400 font-medium">
                    {{ messageCount }} {{ messageCount === 1 ? 'сообщение' :
                    messageCount >= 2 && messageCount <= 4 ? 'сообщения' : 'сообщений' }}
                </span>
                <button
                    v-if="hasNewMessages"
                    @click="scrollToBottom()"
                    class="flex items-center gap-1 px-2 py-1 bg-indigo-100 text-indigo-700 rounded-full text-xs font-medium hover:bg-indigo-200 transition"
                >
                    <span class="w-5 h-5 bg-indigo-500 text-white rounded-full flex items-center justify-center text-[10px]">
                        {{ unreadCount }}
                    </span>
                    Новые сообщения ↓
                </button>
            </div>
        </div>

        <!-- Ошибка -->
        <div v-if="error" class="mx-4 mt-2 p-2 bg-red-50 text-red-600 text-xs rounded-lg border border-red-200">
            {{ error }}
        </div>

        <!-- Список сообщений -->
        <div
            ref="commentsContainer"
            class="flex-1 overflow-y-auto p-4 space-y-4 custom-scrollbar bg-gray-50/50 dark:bg-gray-900"
        >
            <!-- Загрузка -->
            <div v-if="loading" class="flex justify-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
            </div>

            <!-- Пустое состояние -->
            <div v-else-if="!comments.length" class="flex flex-col items-center justify-center h-full text-gray-400 text-sm opacity-60">
                <svg class="w-12 h-12 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                <span>Напишите первое сообщение...</span>
            </div>

            <!-- Сообщения -->
            <div
                v-for="c in sortedComments"
                :key="c.id"
                :id="`comment-${c.id}`"
                class="group flex gap-3 animate-fade-in-up"
            >
                <!-- Аватар -->
                <div class="flex-shrink-0 mt-1">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shadow-sm"
                         :class="getAvatarColor(c.user?.id || 0)"
                         :title="c.user?.name">
                        {{ getInitials(c.user?.name) }}
                    </div>
                </div>

                <!-- Контент -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-baseline gap-2 mb-0.5 flex-wrap">
                        <span class="text-sm font-bold text-gray-900 dark:text-gray-100 cursor-pointer hover:underline">
                            {{ c.user?.name || 'Неизвестный' }}
                        </span>
                        <span class="text-[10px] text-gray-400">{{ formatTime(c.created_at) }}</span>
                        <span v-if="isEdited(c)" class="text-[9px] text-gray-400 italic">(ред.)</span>
                    </div>

                    <!-- Карточка сообщения -->
                    <div class="relative bg-white dark:bg-gray-800 p-3 rounded-2xl rounded-tl-none border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-md transition-shadow">

                        <!-- Цитата (Reply) -->
                        <div v-if="c.parent" class="mb-2 pl-3 border-l-4 border-indigo-200 dark:border-indigo-700/50 py-1 bg-gray-50 dark:bg-gray-700/30 rounded-r-lg">
                            <div class="flex items-center gap-1 text-xs font-semibold text-indigo-600 dark:text-indigo-400">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 0 1 8 8v2M3 10l6 6m-6-6l6-6" />
                                </svg>
                                {{ c.parent.user?.name }}
                                <button
                                    @click="scrollToComment(c.parent.id)"
                                    class="ml-1 text-indigo-400 hover:text-indigo-600"
                                    title="Перейти к сообщению"
                                >
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                </button>
                            </div>
                            <div class="text-xs text-gray-500 line-clamp-2 mt-0.5">{{ c.parent.body }}</div>
                        </div>

                        <!-- Текст (обычный или редактирование) -->
                        <div v-if="editingComment?.id === c.id" class="flex flex-col gap-2">
                            <textarea
                                v-model="editBody"
                                rows="2"
                                class="w-full p-2 text-sm border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                                @input="onInput"
                                @keydown.ctrl.enter="saveEdit"
                                @keydown.esc="cancelEdit"
                            ></textarea>
                            <div class="flex justify-end gap-2">
                                <button
                                    @click="cancelEdit"
                                    class="px-3 py-1 text-xs text-gray-600 hover:bg-gray-100 rounded"
                                >
                                    Отмена
                                </button>
                                <button
                                    @click="saveEdit"
                                    class="px-3 py-1 text-xs bg-indigo-600 text-white rounded hover:bg-indigo-700"
                                    :disabled="!editBody.trim()"
                                >
                                    Сохранить
                                </button>
                            </div>
                        </div>
                        <div v-else class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap leading-relaxed break-words"
                             v-html="highlightMentions(c.body)">
                        </div>

                        <!-- Кнопки действий -->
                        <div class="absolute -top-3 right-2 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                            <button
                                v-if="canChat"
                                @click="startReply(c)"
                                class="p-1.5 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-full shadow-sm text-gray-500 hover:text-indigo-600 hover:border-indigo-200 transition"
                                title="Ответить"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 0 1 8 8v2M3 10l6 6m-6-6l6-6" />
                                </svg>
                            </button>

                            <button
                                v-if="currentUser && c.user_id === currentUser.id"
                                @click="startEdit(c)"
                                class="p-1.5 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-full shadow-sm text-gray-500 hover:text-blue-600 hover:border-blue-200 transition"
                                title="Редактировать"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>

                            <button
                                v-if="currentUser && c.user_id === currentUser.id"
                                @click="remove(c.id)"
                                class="p-1.5 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-full shadow-sm text-gray-500 hover:text-rose-600 hover:border-rose-200 transition"
                                title="Удалить"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Футер: Поле ввода -->
        <div v-if="canChat" class="bg-white dark:bg-gray-800 p-4 border-t border-gray-200 dark:border-gray-700 relative z-20">

            <!-- Панель ответа -->
            <transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0 translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 translate-y-2"
            >
                <div v-if="replyingTo" class="absolute bottom-full left-4 right-4 mb-2 bg-indigo-50 dark:bg-gray-700 p-3 rounded-lg border border-indigo-100 dark:border-indigo-500/30 shadow-lg flex justify-between items-center z-30">
                    <div class="flex items-center gap-3 overflow-hidden">
                        <div class="w-1 bg-indigo-500 h-8 rounded-full"></div>
                        <div class="flex flex-col text-sm">
                            <span class="font-bold text-indigo-700 dark:text-indigo-300">Ответ {{ replyingTo.user?.name }}</span>
                            <span class="text-gray-500 dark:text-gray-400 truncate max-w-xs text-xs">{{ replyingTo.body }}</span>
                        </div>
                    </div>
                    <button @click="cancelReply" class="p-1 hover:bg-black/5 rounded-full text-gray-400 hover:text-gray-600 transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </transition>

            <!-- Панель редактирования -->
            <transition name="slide-down">
                <div v-if="editingComment" class="absolute bottom-full left-4 right-4 mb-2 bg-blue-50 dark:bg-gray-700 p-3 rounded-lg border border-blue-100 dark:border-blue-500/30 shadow-lg flex justify-between items-center z-30">
                    <div class="flex items-center gap-3 overflow-hidden">
                        <div class="w-1 bg-blue-500 h-8 rounded-full"></div>
                        <span class="font-bold text-blue-700 dark:text-blue-300 text-sm">Редактирование сообщения</span>
                    </div>
                    <button @click="cancelEdit" class="p-1 hover:bg-black/5 rounded-full text-gray-400 hover:text-gray-600 transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </transition>

            <!-- Поле ввода + Кнопка -->
            <div class="relative flex items-end gap-2 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-xl p-2 focus-within:ring-2 focus-within:ring-indigo-500/50 focus-within:border-indigo-500 transition-all">
                <textarea
                    ref="textareaRef"
                    v-model="currentMessage"
                    rows="1"
                    :placeholder="replyingTo ? `Ответить ${replyingTo.user?.name}...` :
                                 editingComment ? 'Редактирование...' :
                                 'Написать сообщение...'"
                    class="w-full bg-transparent border-none focus:ring-0 text-sm text-gray-800 dark:text-gray-200 resize-none max-h-32 py-2.5 px-2 custom-scrollbar"
                    style="min-height: 40px;"
                    @input="onInput"
                    @keydown.ctrl.enter="editingComment ? saveEdit() : send()"
                    @keydown.esc="editingComment ? cancelEdit() : null"
                    :disabled="sending"
                ></textarea>

                <button
                    @click="editingComment ? saveEdit() : send()"
                    :disabled="sending || !(editingComment ? editBody.trim() : body.trim())"
                    class="mb-1 p-2 bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 dark:disabled:bg-gray-700 text-white rounded-lg shadow-sm transition-all transform hover:scale-105 active:scale-95 flex-shrink-0"
                >
                    <svg v-if="sending" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                </button>
            </div>

            <div class="text-[10px] text-gray-400 mt-2 ml-1 flex justify-between">
                <span><b>Ctrl + Enter</b> для отправки</span>
                <span>Используйте <b>@</b> для упоминания</span>
            </div>

            <!-- Модалка Mention -->
            <div v-if="mentionOpen && mentionList.length" class="absolute bottom-full left-4 mb-2 bg-white dark:bg-gray-800 border dark:border-gray-600 rounded-xl shadow-2xl w-64 max-h-56 overflow-y-auto z-50 custom-scrollbar">
                <div
                    v-for="m in mentionList"
                    :key="m.id"
                    @click="selectMention(m)"
                    class="px-4 py-2.5 cursor-pointer hover:bg-indigo-50 dark:hover:bg-gray-700 text-sm flex items-center gap-3 border-b border-gray-50 dark:border-gray-700/50 last:border-0 transition"
                >
                    <div class="w-7 h-7 rounded-full bg-gradient-to-tr from-indigo-100 to-purple-100 text-indigo-700 flex items-center justify-center text-xs font-bold shadow-sm">
                        {{ m.name[0] }}
                    </div>
                    <span class="font-medium text-gray-700 dark:text-gray-200">{{ m.name }}</span>
                </div>
            </div>
        </div>

        <div v-else class="p-4 bg-gray-50 dark:bg-gray-900 border-t dark:border-gray-700 text-center text-xs text-gray-500 italic">
            Чат доступен только участникам задачи.
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 5px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 20px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #4b5563;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.animate-fade-in-up {
    animation: fadeInUp 0.3s ease-out forwards;
}

@keyframes pulse {
    0%, 100% {
        background-color: transparent;
    }
    50% {
        background-color: rgba(99, 102, 241, 0.1);
    }
}
.highlight-pulse {
    animation: pulse 1s ease-in-out 2;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Анимации для панелей */
.slide-down-enter-active,
.slide-down-leave-active {
    transition: all 0.3s ease;
}
.slide-down-enter-from,
.slide-down-leave-to {
    opacity: 0;
    transform: translateY(10px);
}
</style>
