<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick, computed } from 'vue'
import axios from 'axios'
import { usePage } from '@inertiajs/vue3'

const props = defineProps({
    taskId: { type: Number, required: true },
    canChat: { type: Boolean, default: true },
    members: { type: Array, default: () => [] }
})

const page = usePage()
const currentUser = computed(() => page.props.auth.user) // Получаем текущего юзера

const comments = ref([])
const body = ref('')

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

let timer = null

const fetchComments = async () => {
    try {
        const { data } = await axios.get(`/api/tasks/${props.taskId}/comments`, { withCredentials: true })
        comments.value = data
    } catch (e) {
        console.error("Ошибка загрузки комментариев", e)
    }
}

const send = async () => {
    if (!body.value.trim()) return

    await axios.get('/sanctum/csrf-cookie')

    const payload = {
        body: body.value,
        parent_id: replyingTo.value ? replyingTo.value.id : null
    }

    try {
        await axios.post(`/api/tasks/${props.taskId}/comments`, payload, { withCredentials: true })
        body.value = ''
        cancelReply()
        await fetchComments()
        scrollToBottom()
    } catch (e) {
        alert('Не удалось отправить сообщение')
    }
}

// Удаление сообщения
const remove = async (commentId) => {
    if(!confirm('Удалить сообщение?')) return
    await axios.delete(`/api/task-comments/${commentId}`)
    await fetchComments()
}

// Скролл вниз
const commentsContainer = ref(null)
const scrollToBottom = () => {
    nextTick(() => {
        if (commentsContainer.value) {
            commentsContainer.value.scrollTop = commentsContainer.value.scrollHeight
        }
    })
}

// --- Reply ---
const startReply = (comment) => {
    replyingTo.value = comment
    nextTick(() => textareaRef.value?.focus())
}

const cancelReply = () => replyingTo.value = null
// -------------

// --- Mention Logic (Стандартная) ---
const onInput = (e) => {
    // 1. Логика авто-высоты (добавлено сюда)
    e.target.style.height = 'auto'
    e.target.style.height = (e.target.scrollHeight) + 'px'

    // 2. Старая логика упоминаний (@)
    const value = body.value
    const pos = e.target.selectionStart
    caretIndex.value = pos
    const char = value[pos - 1]

    if (char === '@') {
        mentionOpen.value = true; mentionSearch.value = ''; mentionList.value = props.members; return
    }
    if (mentionOpen.value) {
        const match = value.slice(0, pos).match(/@([а-яА-Яa-zA-Z0-9_]*)$/)
        if (!match) { mentionOpen.value = false; return }
        mentionSearch.value = match[1].toLowerCase()
        mentionList.value = props.members.filter((m) => m.name.toLowerCase().includes(mentionSearch.value))
    }
}
const selectMention = (user) => {
    const text = body.value
    body.value = text.replace(/@([а-яА-Яa-zA-Z0-9_]*)$/, '@' + user.name.replace(/\s+/g, '_') + ' ')
    mentionOpen.value = false
    nextTick(() => textareaRef.value?.focus())
}
const highlightMentions = (text) => {
    if (!text) return ''
    return text.replace(/@([\p{L}0-9_]+)/gu, (match, nameToken) => {
        const visibleName = nameToken.replace(/_/g, ' ')
        return `<span class="text-indigo-600 font-semibold bg-indigo-50 px-1 rounded">@${visibleName}</span>`
    })
}

// Хелпер для аватара
const getInitials = (name) => name ? name.split(' ').map(n=>n[0]).join('').slice(0,2).toUpperCase() : '?'
const getAvatarColor = (id) => {
    const colors = ['bg-red-100 text-red-600', 'bg-blue-100 text-blue-600', 'bg-green-100 text-green-600', 'bg-amber-100 text-amber-600', 'bg-purple-100 text-purple-600']
    return colors[id % colors.length]
}

// Дата: Сегодня в 14:30 или 12 янв
const formatTime = (dateStr) => {
    const date = new Date(dateStr)
    const now = new Date()
    const isToday = date.toDateString() === now.toDateString()
    return isToday
        ? date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
        : date.toLocaleDateString([], {day: 'numeric', month: 'short', hour: '2-digit', minute:'2-digit'})
}

onMounted(async () => {
    await fetchComments()
    scrollToBottom()
    timer = setInterval(fetchComments, 5000) // 5 сек для частого обновления
})
onBeforeUnmount(() => { if (timer) clearInterval(timer) })
</script>

<template>
    <div class="flex flex-col h-[600px] bg-gray-50 dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">

        <!-- Шапка чата -->
        <div class="px-5 py-3 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center shadow-sm z-10">
            <h3 class="font-bold text-gray-700 dark:text-gray-200 flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" /></svg>
                Обсуждение задачи
            </h3>
            <span class="text-xs text-gray-400 font-medium">{{ comments.length }} сообщений</span>
        </div>

        <!-- Список сообщений -->
        <div ref="commentsContainer" class="flex-1 overflow-y-auto p-4 space-y-4 custom-scrollbar bg-gray-50/50 dark:bg-gray-900">
            <div v-if="!comments.length" class="flex flex-col items-center justify-center h-full text-gray-400 text-sm opacity-60">
                <svg class="w-12 h-12 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                <span>Напишите первое сообщение...</span>
            </div>

            <div v-for="c in comments" :key="c.id" class="group flex gap-3 animate-fade-in-up">

                <!-- Аватар -->
                <div class="flex-shrink-0 mt-1">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shadow-sm"
                         :class="getAvatarColor(c.user?.id || 0)">
                        {{ getInitials(c.user?.name) }}
                    </div>
                </div>

                <!-- Контент -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-baseline gap-2 mb-0.5">
                        <span class="text-sm font-bold text-gray-900 dark:text-gray-100 cursor-pointer hover:underline">
                            {{ c.user?.name || 'Неизвестный' }}
                        </span>
                        <span class="text-[10px] text-gray-400">{{ formatTime(c.created_at) }}</span>
                    </div>

                    <!-- Карточка сообщения -->
                    <div class="relative bg-white dark:bg-gray-800 p-3 rounded-2xl rounded-tl-none border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-md transition-shadow">

                        <!-- Цитата (Reply) -->
                        <div v-if="c.parent" class="mb-2 pl-3 border-l-4 border-indigo-200 dark:border-indigo-700/50 py-1 bg-gray-50 dark:bg-gray-700/30 rounded-r-lg">
                            <div class="flex items-center gap-1 text-xs font-semibold text-indigo-600 dark:text-indigo-400">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 0 1 8 8v2M3 10l6 6m-6-6l6-6" /></svg>
                                {{ c.parent.user?.name }}
                            </div>
                            <div class="text-xs text-gray-500 truncate mt-0.5 max-w-[200px]">{{ c.parent.body }}</div>
                        </div>

                        <!-- Текст -->
                        <div class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap leading-relaxed break-words"
                             v-html="highlightMentions(c.body)">
                        </div>

                        <!-- Кнопки действий (всплывают при наведении) -->
                        <div class="absolute -top-3 right-2 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                            <button v-if="canChat" @click="startReply(c)" class="p-1.5 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-full shadow-sm text-gray-500 hover:text-indigo-600 hover:border-indigo-200 transition" title="Ответить">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 0 1 8 8v2M3 10l6 6m-6-6l6-6" /></svg>
                            </button>

                            <!-- Кнопка удаления (только для автора или админа) -->
                            <button
                                v-if="currentUser && c.user_id === currentUser.id"
                                @click="remove(c.id)"
                                class="p-1.5 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-full shadow-sm text-gray-500 hover:text-rose-600 hover:border-rose-200 transition"
                                title="Удалить"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Футер: Поле ввода -->
        <div v-if="canChat" class="bg-white dark:bg-gray-800 p-4 border-t border-gray-200 dark:border-gray-700 relative z-20">

            <!-- Панель ответа (плавающая над инпутом) -->
            <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 translate-y-2" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-2">
                <div v-if="replyingTo" class="absolute bottom-full left-4 right-4 mb-2 bg-indigo-50 dark:bg-gray-700 p-3 rounded-lg border border-indigo-100 dark:border-indigo-500/30 shadow-lg flex justify-between items-center z-30">
                    <div class="flex items-center gap-3 overflow-hidden">
                        <div class="w-1 bg-indigo-500 h-8 rounded-full"></div>
                        <div class="flex flex-col text-sm">
                            <span class="font-bold text-indigo-700 dark:text-indigo-300">Ответ {{ replyingTo.user?.name }}</span>
                            <span class="text-gray-500 dark:text-gray-400 truncate max-w-xs text-xs">{{ replyingTo.body }}</span>
                        </div>
                    </div>
                    <button @click="cancelReply" class="p-1 hover:bg-black/5 rounded-full text-gray-400 hover:text-gray-600 transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            </transition>

            <!-- Поле ввода + Кнопка -->
            <div class="relative flex items-end gap-2 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-xl p-2 focus-within:ring-2 focus-within:ring-indigo-500/50 focus-within:border-indigo-500 transition-all">
                <!-- ... внутри <template> ... -->

                <textarea
                    ref="textareaRef"
                    v-model="body"
                    rows="1"
                    :placeholder="replyingTo ? `Ответить ${replyingTo.user?.name}...` : 'Написать сообщение...'"
                    class="w-full bg-transparent border-none focus:ring-0 text-sm text-gray-800 dark:text-gray-200 resize-none max-h-32 py-2.5 px-2 custom-scrollbar"
                    style="min-height: 40px;"
                    @input="onInput"
                    @keyup="onInput"
                    @keydown.ctrl.enter="send"
                ></textarea>

                <!-- ... -->

                <button
                    @click="send"
                    :disabled="!body.trim()"
                    class="mb-1 p-2 bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 dark:disabled:bg-gray-700 text-white rounded-lg shadow-sm transition-all transform hover:scale-105 active:scale-95 flex-shrink-0"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                </button>
            </div>

            <div class="text-[10px] text-gray-400 mt-2 ml-1 flex justify-between">
                <span><b>Ctrl + Enter</b> для отправки</span>
                <span>Используйте <b>@</b> для упоминания</span>
            </div>

            <!-- Модалка Mention -->
            <div v-if="mentionOpen && mentionList.length" class="absolute bottom-full left-4 mb-2 bg-white dark:bg-gray-800 border dark:border-gray-600 rounded-xl shadow-2xl w-64 max-h-56 overflow-y-auto z-50 custom-scrollbar">
                <div v-for="m in mentionList" :key="m.id" @click="selectMention(m)" class="px-4 py-2.5 cursor-pointer hover:bg-indigo-50 dark:hover:bg-gray-700 text-sm flex items-center gap-3 border-b border-gray-50 dark:border-gray-700/50 last:border-0">
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
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
.dark .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #4b5563; }

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in-up {
    animation: fadeInUp 0.3s ease-out forwards;
}
</style>
