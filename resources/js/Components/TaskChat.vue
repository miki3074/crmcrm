<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import axios from 'axios'
const props = defineProps({
  taskId: { type: Number, required: true },
  canChat: { type: Boolean, default: true }, // прокинем с сервера/политики при желании
   members: {
    type: Array,
    default: () => []
  }
})





const comments = ref([])
const body = ref('')

const mentionOpen = ref(false)
const mentionSearch = ref('')
const mentionList = ref([])
const caretIndex = ref(0)

let timer = null

const fetchComments = async () => {
  const { data } = await axios.get(`/api/tasks/${props.taskId}/comments`, { withCredentials: true })
  comments.value = data
}

const send = async () => {
  if (!body.value.trim()) return
  await axios.get('/sanctum/csrf-cookie')
  await axios.post(`/api/tasks/${props.taskId}/comments`, { body: body.value }, { withCredentials: true })
  body.value = ''
  await fetchComments()
}

const remove = async (commentId) => {
  await axios.delete(`/api/task-comments/${commentId}`)
  await fetchComments()
}


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
    // ищем последние слово после @
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
  const pos = caretIndex.value
  const text = body.value

  // заменяем паттерн @текст
  body.value = text.replace(/@([а-яА-Яa-zA-Z0-9_]*)$/, '@' + user.name + ' ')

  mentionOpen.value = false
}

const highlightMentions = (text) => {
  return text.replace(
    /@([A-Za-z0-9_]+)/g,
    '<span class="text-indigo-600 font-semibold">@$1</span>'
  )
}



onMounted(async () => {
  await fetchComments()
  timer = setInterval(fetchComments, 10000) // простое авто-обновление
})
onBeforeUnmount(() => { if (timer) clearInterval(timer) })
</script>

<template>
  <div class="rounded-2xl border bg-white dark:bg-gray-800 p-5 mt-8">
    <div class="mb-4 flex items-center justify-between">
      <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Комментарии</h3>
    </div>

    <div class="space-y-3 max-h-80 overflow-y-auto pr-1">
      <div v-for="c in comments" :key="c.id" class="flex gap-3">
        <div class="flex-1">
          <div class="flex items-center gap-2">
            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ c.user?.name ?? '—' }}</div>
            <div class="text-xs text-gray-500">{{ new Date(c.created_at).toLocaleString() }}</div>
            <!-- <button
              v-if="$page.props.auth.user && c.user && c.user.id === $page.props.auth.user.id"
              class="ml-auto text-xs text-red-600 hover:underline"
              @click="remove(c.id)"
            >Удалить</button> -->
          </div>
         <div class="mt-1 text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap"
     v-html="highlightMentions(c.body)">
</div>

        </div>
      </div>

      <div v-if="!comments.length" class="text-sm text-gray-500">Пока нет сообщений.</div>
    </div>

    <div v-if="canChat" class="mt-4">
     <textarea
  v-model="body"
  rows="2"
  placeholder="Напишите комментарий…"
  class="w-full rounded-lg border bg-white/80 dark:bg-gray-700 px-3 py-2 text-sm dark:text-white relative"
  @input="onInput"
  @click="onInput"
  @keyup="onInput"
/>

      <div class="mt-2 flex justify-end">
        <button
          @click="send"
          :disabled="!body.trim()"
          class="px-4 py-2 rounded-lg bg-indigo-600 text-white disabled:opacity-40"
        >Отправить</button>
      </div>
    </div>

    <div v-else class="mt-3 text-xs text-gray-500">
      У вас нет прав на участие в чате этой задачи.
    </div>


<!-- Модалка упоминаний -->
<div
  v-if="mentionOpen && mentionList.length"
  class="absolute bg-white dark:bg-gray-700 border dark:border-gray-600 rounded-lg shadow-lg w-64 mt-1 z-50"
>
  <div
    v-for="m in mentionList"
    :key="m.id"
    class="px-3 py-2 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600"
    @click="selectMention(m)"
  >
    @{{ m.name }}
  </div>
</div>



  </div>
</template>
