<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue'
import axios from 'axios'

const props = defineProps({
    taskId: { type: Number, required: true },
    canChat: { type: Boolean, default: true },
    members: { type: Array, default: () => [] }
})

const comments = ref([])
const body = ref('')

// --- Для Mention ---
const mentionOpen = ref(false)
const mentionSearch = ref('')
const mentionList = ref([])
const caretIndex = ref(0)
// ------------------

// --- Для Reply (НОВОЕ) ---
const replyingTo = ref(null) // объект комментария, на который отвечаем
const textareaRef = ref(null) // ссылка на DOM элемент textarea для фокуса
// -------------------------

let timer = null

const fetchComments = async () => {
    const { data } = await axios.get(`/api/tasks/${props.taskId}/comments`, { withCredentials: true })
    comments.value = data
}

const send = async () => {
    if (!body.value.trim()) return

    await axios.get('/sanctum/csrf-cookie')

    const payload = {
        body: body.value,
        parent_id: replyingTo.value ? replyingTo.value.id : null // Отправляем ID родителя
    }

    await axios.post(`/api/tasks/${props.taskId}/comments`, payload, { withCredentials: true })

    body.value = ''
    cancelReply() // Сбрасываем режим ответа
    await fetchComments()

    // Авто-скролл вниз (опционально)
    // scrollToBottom()
}

const remove = async (commentId) => {
    if(!confirm('Удалить сообщение?')) return
    await axios.delete(`/api/task-comments/${commentId}`)
    await fetchComments()
}

// --- Логика Reply (НОВОЕ) ---
const startReply = (comment) => {
    replyingTo.value = comment
    // Фокус в поле ввода
    nextTick(() => {
        textareaRef.value?.focus()
    })
}

const cancelReply = () => {
    replyingTo.value = null
}
// ----------------------------

// ... (Ваш код onInput, selectMention, highlightMentions остается без изменений) ...
const onInput = (e) => {
    const value = body.value
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
        const match = value.slice(0, pos).match(/@([а-яА-Яa-zA-Z0-9_]*)$/)
        if (!match) {
            mentionOpen.value = false
            return
        }
        mentionSearch.value = match[1].toLowerCase()
        mentionList.value = props.members.filter((m) =>
            m.name.toLowerCase().includes(mentionSearch.value)
        )
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
    return text.replace(
        /@([\p{L}0-9_]+)/gu,
        (match, nameToken) => {
            const visibleName = nameToken.replace(/_/g, ' ')
            return `<span class="text-indigo-600 font-semibold">@${visibleName}</span>`
        }
    )
}

onMounted(async () => {
    await fetchComments()
    timer = setInterval(fetchComments, 10000)
})
onBeforeUnmount(() => { if (timer) clearInterval(timer) })
</script>

<template>
    <div class="rounded-2xl border bg-white dark:bg-gray-800 p-5 mt-8">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Комментарии</h3>
        </div>

        <!-- Список сообщений -->
        <div class="space-y-4 max-h-80 overflow-y-auto pr-1 custom-scrollbar">
            <div v-for="c in comments" :key="c.id" class="flex gap-3 group">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <div class="text-sm font-bold text-gray-900 dark:text-white">{{ c.user?.name ?? '—' }}</div>
                        <div class="text-xs text-gray-500">{{ new Date(c.created_at).toLocaleString() }}</div>

                        <!-- Кнопка Ответить (появляется при наведении на сообщение) -->
                        <button
                            v-if="canChat"
                            @click="startReply(c)"
                            class="ml-2 text-xs text-indigo-500 opacity-0 group-hover:opacity-100 transition-opacity hover:underline flex items-center gap-1"
                            title="Ответить"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 17 4 12 9 7"/><path d="M20 18v-2a4 4 0 0 0-4-4H4"/></svg>
                            Ответить
                        </button>

                        <!-- Кнопка Удалить (опционально) -->
                        <!-- <button class="ml-auto text-xs text-red-600 opacity-0 group-hover:opacity-100" @click="remove(c.id)">Удалить</button> -->
                    </div>

                    <!-- Если это ответ, показываем цитату родителя -->
                    <div v-if="c.parent" class="mb-1 pl-2 border-l-2 border-indigo-300 dark:border-indigo-700 text-xs text-gray-500 bg-gray-50 dark:bg-gray-700/50 py-1 rounded-r">
                        <div class="font-medium flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 10h10a8 8 0 0 1 8 8v2M3 10l6 6m-6-6l6-6"/></svg>
                            {{ c.parent.user?.name }}:
                        </div>
                        <div class="truncate opacity-80 italic">{{ c.parent.body }}</div>
                    </div>

                    <!-- Тело сообщения -->
                    <div
                        class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap leading-relaxed"
                        v-html="highlightMentions(c.body)">
                    </div>
                </div>
            </div>

            <div v-if="!comments.length" class="text-sm text-gray-500">Пока нет сообщений.</div>
        </div>

        <!-- Область ввода -->
        <div v-if="canChat" class="mt-4 relative">

            <!-- Панель: "Ответ пользователю..." -->
            <div
                v-if="replyingTo"
                class="flex items-center justify-between bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-100 dark:border-indigo-800 rounded-t-lg px-3 py-2 text-xs mb-[-1px] z-10 relative"
            >
                <div class="flex flex-col overflow-hidden mr-2">
            <span class="font-semibold text-indigo-700 dark:text-indigo-300 flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 17 4 12 9 7"/><path d="M20 18v-2a4 4 0 0 0-4-4H4"/></svg>
                В ответ {{ replyingTo.user?.name }}
            </span>
                    <span class="truncate text-gray-500 dark:text-gray-400 max-w-xs">{{ replyingTo.body }}</span>
                </div>
                <button @click="cancelReply" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>

            <textarea
                ref="textareaRef"
                v-model="body"
                rows="2"
                :placeholder="replyingTo ? 'Напишите ответ...' : 'Напишите комментарий…'"
                class="w-full border bg-white/80 dark:bg-gray-700 px-3 py-2 text-sm dark:text-white focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all"
                :class="replyingTo ? 'rounded-b-lg border-t-0' : 'rounded-lg'"
                @input="onInput"
                @click="onInput"
                @keyup="onInput"
                @keydown.ctrl.enter="send"
            />

            <div class="mt-2 flex justify-end">
                <button
                    @click="send"
                    :disabled="!body.trim()"
                    class="px-4 py-2 rounded-lg bg-indigo-600 text-white disabled:opacity-40 text-sm font-medium hover:bg-indigo-700 transition"
                >
                    {{ replyingTo ? 'Ответить' : 'Отправить' }}
                </button>
            </div>

            <!-- Модалка упоминаний -->
            <div
                v-if="mentionOpen && mentionList.length"
                class="absolute bottom-full left-0 mb-2 bg-white dark:bg-gray-700 border dark:border-gray-600 rounded-lg shadow-xl w-64 max-h-48 overflow-y-auto z-50"
            >
                <div
                    v-for="m in mentionList"
                    :key="m.id"
                    class="px-3 py-2 cursor-pointer hover:bg-indigo-50 dark:hover:bg-gray-600 text-sm flex items-center gap-2"
                    @click="selectMention(m)"
                >
                    <div class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-bold">
                        {{ m.name[0] }}
                    </div>
                    {{ m.name }}
                </div>
            </div>

        </div>

        <div v-else class="mt-3 text-xs text-gray-500">
            У вас нет прав на участие в чате.
        </div>
    </div>
</template>
