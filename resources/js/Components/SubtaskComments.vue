<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'

const props = defineProps({
  subtaskId: Number,
  comments: Array,
  canWrite: Boolean,
  members: { type: Array, default: () => [] } // [{id,name}]
})

const emit = defineEmits(['updated'])

/* ---------------------- ДАННЫЕ ---------------------- */
const newComment = ref('')
const editingId = ref(null)
const editText = ref('')

const showMentionList = ref(false)
const mentionSearch = ref('')
const cursorPosition = ref(0)

/* ---------------------- ЛОГИКА ПОИСКА ПО @ ---------------------- */
const onInput = (e) => {
    const value = e.target.value
    const pos = e.target.selectionStart
    cursorPosition.value = pos

    const beforeCursor = value.slice(0, pos)
    // Ищем @ + буквы/цифры/подчёркивание (кириллица тоже)
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

/* ---------------------- ВСТАВКА @ИМЕНИ В КУРСОР ---------------------- */
const selectMention = (user) => {
    const pos = cursorPosition.value
    const text = newComment.value

    // Сохраняем в тексте как @Имя_Фамилия (без пробелов)
    const safeName = user.name.replace(/\s+/g, '_')
    const tag = `@${safeName}`

    const before = text.slice(0, pos).replace(/@([\p{L}\d_]*)$/u, tag + ' ')
    const after = text.slice(pos)

    newComment.value = before + after

    showMentionList.value = false
    mentionSearch.value = ''

    // ставим курсор после вставленного упоминания
    setTimeout(() => {
        const el = document.querySelector('#subtask-comment-input')
        if (el) {
            el.selectionStart = el.selectionEnd = before.length
            el.focus()
        }
    }, 0)
}



const highlightMentions = (text) => {
    return text.replace(
        /@([\p{L}\d_]+)/gu,
        (match, p1) => {
            const display = p1.replace(/_/g, ' ') // вернули пробелы
            return `<span class="mention">@${display}</span>`
        }
    )
}




/* ---------------------- ДОБАВЛЕНИЕ КОММЕНТАРИЯ ---------------------- */
const addComment = async () => {
    if (!newComment.value.trim()) return

    // Ищем все @токены
    const mentionMatches = newComment.value.match(/@([\p{L}\d_]+)/gu) || []

    const mentions = mentionMatches
        .map(m => {
            // убираем @ и возвращаем пробелы
            const raw = m.substring(1) // "Булат_Раисович_Хамзин"
            const name = raw.replace(/_/g, ' ') // "Булат Раисович Хамзин"
            return props.members.find(u => u.name === name)
        })
        .filter(Boolean)
        .map(u => u.id)

    const { data } = await axios.post(`/api/subtasks/${props.subtaskId}/comments`, {
        comment: newComment.value,
        mentions
    })

    emit('updated', { type: 'add', comment: data })
    newComment.value = ''
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

    <h3 class="text-lg font-semibold mb-3">💬 Комментарии</h3>

    <!-- Нет комментариев -->
    <p v-if="!comments || comments.length === 0" class="text-gray-500 text-sm">
      Пока нет комментариев.
    </p>

    <!-- Список -->
    <div v-if="comments && comments.length" class="space-y-4">
      <div
        v-for="c in comments"
        :key="c.id"
        class="p-3 border dark:border-slate-700 rounded-lg"
      >
        <div class="flex justify-between">
          <strong>{{ c.user.name }}</strong>
          <span class="text-xs text-gray-500">
            {{ new Date(c.created_at).toLocaleString('ru-RU') }}
          </span>
        </div>

        <!-- Режим редактирования -->
        <div v-if="editingId === c.id">
          <textarea
            v-model="editText"
            class="w-full border rounded-lg px-2 py-1 mt-2 dark:bg-slate-700 dark:text-white"
            rows="2"
          ></textarea>

          <div class="mt-2 flex gap-2">
            <button @click="saveEdit(c.id)" class="px-3 py-1 bg-green-600 text-white rounded-lg text-sm">
              Сохранить
            </button>
            <button @click="editingId = null" class="px-3 py-1 bg-gray-400 text-white rounded-lg text-sm">
              Отмена
            </button>
          </div>
        </div>

        <!-- Просмотр -->
        <div v-else>
          <p class="mt-2 whitespace-pre-line" v-html="highlightMentions(c.comment)"></p>


          <!-- <div v-if="canWrite" class="mt-2 flex gap-2 text-sm">
            <button @click="startEdit(c)" class="text-blue-600 hover:underline">
              ✏ Редактировать
            </button>

            <button @click="deleteComment(c.id)" class="text-red-600 hover:underline">
              🗑 Удалить
            </button>
          </div> -->
        </div>
      </div>
    </div>

    <!-- Ввод комментария -->
    <div v-if="canWrite" class="mt-4 relative">

      <textarea
        id="subtask-comment-input"
        v-model="newComment"
        @input="onInput"
        @click="onInput"
        placeholder="Написать комментарий и отметить человека через @..."
        class="w-full border rounded-lg px-3 py-2 dark:bg-slate-700 dark:text-white"
        rows="3"
      ></textarea>

      <!-- Список @упоминаний -->
      <div
        v-if="showMentionList && filteredMembers.length"
        class="absolute left-0 mt-1 bg-white dark:bg-slate-700 border rounded-lg shadow p-2 max-h-40 overflow-auto z-50 w-64"
      >
        <div
          v-for="m in filteredMembers"
          :key="m.id"
          class="p-1 cursor-pointer hover:bg-gray-200 dark:hover:bg-slate-600"
          @click="selectMention(m)"
        >
          @{{ m.name }}
        </div>
      </div>

      <button
        @click="addComment"
        class="mt-3 bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700"
      >
        ➤ Добавить
      </button>

    </div>

  </div>




</template>
<style scoped>

:deep(.mention) {
    background: rgba(99, 102, 241, 0.15);
    color: #4f46e5;
    padding: 2px 4px;
    border-radius: 4px;
    font-weight: 600;
}



</style>
