<script setup>
import { ref, computed, nextTick } from 'vue'
import axios from 'axios'

const props = defineProps({
    subtaskId: Number,
    comments: Array,
    canWrite: Boolean,
    members: { type: Array, default: () => [] }
})

const emit = defineEmits(['updated'])

/* ---------------------- ДАННЫЕ ---------------------- */
const newComment = ref('')
const editingId = ref(null)
const editText = ref('')

// Меншны
const showMentionList = ref(false)
const mentionSearch = ref('')
const cursorPosition = ref(0)

// Ответы (Reply)
const replyingTo = ref(null) // Объект комментария, на который отвечаем
const textareaRef = ref(null) // Ref для фокуса

/* ---------------------- ЛОГИКА REPLY (ОТВЕТ) ---------------------- */
const startReply = (comment) => {
    replyingTo.value = comment
    // Фокус на поле ввода
    nextTick(() => {
        textareaRef.value?.focus()
    })
}

const cancelReply = () => {
    replyingTo.value = null
}

/* ---------------------- ЛОГИКА ПОИСКА ПО @ ---------------------- */
const onInput = (e) => {
    const value = e.target.value
    newComment.value = value // важно обновлять v-model вручную при @input событии если используем e.target.value
    const pos = e.target.selectionStart
    cursorPosition.value = pos

    const beforeCursor = value.slice(0, pos)
    const match = beforeCursor.match(/@([\p{L}\d_]*)$/u)

    if (match) {
        mentionSearch.value = match[1].toLowerCase()
        showMentionList.value = true
    } else {
        showMentionList.value = false
    }
}

const filteredMembers = computed(() => {
    if (!mentionSearch.value) return props.members
    return props.members.filter(m =>
        m.name.toLowerCase().includes(mentionSearch.value)
    )
})

/* ---------------------- ВСТАВКА @ИМЕНИ ---------------------- */
const selectMention = (user) => {
    const pos = cursorPosition.value
    const text = newComment.value

    const safeName = user.name.replace(/\s+/g, '_')
    const tag = `@${safeName}`

    const before = text.slice(0, pos).replace(/@([\p{L}\d_]*)$/u, tag + ' ')
    const after = text.slice(pos)

    newComment.value = before + after

    showMentionList.value = false
    mentionSearch.value = ''

    nextTick(() => {
        if (textareaRef.value) {
            textareaRef.value.focus()
            textareaRef.value.selectionStart = textareaRef.value.selectionEnd = before.length
        }
    })
}

const highlightMentions = (text) => {
    if (!text) return ''
    return text.replace(
        /@([\p{L}\d_]+)/gu,
        (match, p1) => {
            const display = p1.replace(/_/g, ' ')
            return `<span class="mention text-indigo-600 bg-indigo-50 px-1 rounded font-semibold">@${display}</span>`
        }
    )
}

/* ---------------------- ДОБАВЛЕНИЕ КОММЕНТАРИЯ ---------------------- */
const addComment = async () => {
    if (!newComment.value.trim()) return

    // Парсим меншны
    const mentionMatches = newComment.value.match(/@([\p{L}\d_]+)/gu) || []
    const mentions = mentionMatches
        .map(m => {
            const raw = m.substring(1)
            const name = raw.replace(/_/g, ' ')
            return props.members.find(u => u.name === name)
        })
        .filter(Boolean)
        .map(u => u.id)

    const payload = {
        comment: newComment.value,
        mentions,
        parent_id: replyingTo.value ? replyingTo.value.id : null
    }

    try {
        const { data } = await axios.post(`/api/subtasks/${props.subtaskId}/comments`, payload)
        emit('updated', { type: 'add', comment: data })

        newComment.value = ''
        cancelReply() // Сброс режима ответа
    } catch (e) {
        console.error(e)
        alert('Ошибка при отправке')
    }
}

/* ---------------------- РЕДАКТИРОВАНИЕ ---------------------- */
const startEdit = (comment) => {
    editingId.value = comment.id
    editText.value = comment.comment
}

const saveEdit = async (id) => {
    if (!editText.value.trim()) return

    const { data } = await axios.patch(`/api/subtask-comments/${id}`, {
        comment: editText.value
    })

    emit('updated', { type: 'update', comment: data })
    editingId.value = null
    editText.value = ''
}

/* ---------------------- УДАЛЕНИЕ ---------------------- */
const deleteComment = async (id) => {
    if (!confirm('Удалить комментарий?')) return
    await axios.delete(`/api/subtask-comments/${id}`)
    emit('updated', { type: 'delete', id })
}
</script>

<template>
    <div class="mt-4 bg-white dark:bg-slate-800 p-4 rounded-xl shadow relative">

        <h3 class="text-lg font-semibold mb-3 dark:text-white">💬 Комментарии</h3>

        <!-- Нет комментариев -->
        <p v-if="!comments || comments.length === 0" class="text-gray-500 text-sm">
            Пока нет комментариев.
        </p>

        <!-- Список -->
        <div v-if="comments && comments.length" class="space-y-4 max-h-[500px] overflow-y-auto pr-2 custom-scrollbar">
            <div
                v-for="c in comments"
                :key="c.id"
                class="group p-3 border dark:border-slate-700 rounded-lg bg-gray-50/50 dark:bg-slate-700/30"
            >
                <div class="flex justify-between items-start mb-1">
                    <div class="flex items-center gap-2">
                        <strong class="text-sm text-gray-900 dark:text-gray-100">{{ c.user?.name || 'Неизвестный' }}</strong>
                        <span class="text-xs text-gray-500">
                    {{ new Date(c.created_at).toLocaleString('ru-RU') }}
                </span>
                    </div>

                    <!-- Кнопки действий -->
                    <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <!-- Кнопка Ответить -->
                        <button
                            v-if="canWrite"
                            @click="startReply(c)"
                            class="text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 flex items-center gap-1"
                            title="Ответить"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 17 4 12 9 7"/><path d="M20 18v-2a4 4 0 0 0-4-4H4"/></svg>
                        </button>

                        <!-- <button v-if="canWrite && c.user_id === $page.props.auth.user.id" @click="startEdit(c)" class="text-xs text-blue-600">✏</button> -->
                        <!-- <button v-if="canWrite" @click="deleteComment(c.id)" class="text-xs text-red-600">🗑</button> -->
                    </div>
                </div>

                <!-- Цитата (если есть родитель) -->
                <div v-if="c.parent" class="mb-2 pl-2 border-l-2 border-indigo-300 dark:border-indigo-600">
                    <div class="text-xs text-indigo-600 dark:text-indigo-400 font-medium">
                        {{ c.parent.user?.name }}:
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 truncate italic">
                        {{ c.parent.comment }}
                    </div>
                </div>

                <!-- Режим редактирования -->
                <div v-if="editingId === c.id">
          <textarea
              v-model="editText"
              class="w-full border rounded-lg px-2 py-1 mt-2 dark:bg-slate-700 dark:text-white dark:border-slate-600"
              rows="2"
          ></textarea>
                    <div class="mt-2 flex gap-2">
                        <button @click="saveEdit(c.id)" class="px-3 py-1 bg-green-600 text-white rounded-lg text-xs">Сохранить</button>
                        <button @click="editingId = null" class="px-3 py-1 bg-gray-400 text-white rounded-lg text-xs">Отмена</button>
                    </div>
                </div>

                <!-- Просмотр -->
                <div v-else>
                    <p
                        class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-line leading-relaxed"
                        v-html="highlightMentions(c.comment)"
                    ></p>
                </div>
            </div>
        </div>

        <!-- Область ввода -->
        <div v-if="canWrite" class="mt-4 relative">

            <!-- Панель "В ответ..." -->
            <div
                v-if="replyingTo"
                class="flex items-center justify-between bg-indigo-50 dark:bg-slate-700/50 border border-indigo-100 dark:border-slate-600 rounded-t-lg px-3 py-2 text-xs mb-[-1px] z-10 relative"
            >
                <div class="flex flex-col overflow-hidden mr-2">
            <span class="font-semibold text-indigo-700 dark:text-indigo-300">
                В ответ {{ replyingTo.user?.name }}
            </span>
                    <span class="truncate text-gray-500 dark:text-gray-400 max-w-xs italic">{{ replyingTo.comment }}</span>
                </div>
                <button @click="cancelReply" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    ✕
                </button>
            </div>

            <textarea
                id="subtask-comment-input"
                ref="textareaRef"
                v-model="newComment"
                @input="onInput"
                @click="onInput"
                :placeholder="replyingTo ? 'Напишите ответ...' : 'Написать комментарий (@ для упоминания)...'"
                class="w-full border px-3 py-2 dark:bg-slate-700 dark:text-white dark:border-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all"
                :class="replyingTo ? 'rounded-b-lg border-t-0' : 'rounded-lg'"
                rows="3"
            ></textarea>

            <!-- Список @упоминаний -->
            <div
                v-if="showMentionList && filteredMembers.length"
                class="absolute bottom-full left-0 mb-1 bg-white dark:bg-slate-700 border dark:border-slate-600 rounded-lg shadow-xl p-0 max-h-40 overflow-auto z-50 w-64"
            >
                <div
                    v-for="m in filteredMembers"
                    :key="m.id"
                    class="px-3 py-2 cursor-pointer hover:bg-indigo-50 dark:hover:bg-slate-600 text-sm border-b dark:border-slate-600 last:border-0 flex items-center gap-2 text-gray-800 dark:text-gray-200"
                    @click="selectMention(m)"
                >
                    <span class="font-bold text-indigo-500">@</span> {{ m.name }}
                </div>
            </div>

            <div class="mt-2 flex justify-end">
                <button
                    @click="addComment"
                    :disabled="!newComment.trim()"
                    class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 disabled:opacity-50 text-sm font-medium transition-colors"
                >
                    {{ replyingTo ? '➤ Ответить' : '➤ Отправить' }}
                </button>
            </div>

        </div>

    </div>
</template>

<style scoped>
/* Скроллбар для списка комментариев, если их много */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 20px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #475569;
}
</style>
