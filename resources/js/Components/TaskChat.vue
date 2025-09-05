<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import axios from 'axios'
const props = defineProps({
  taskId: { type: Number, required: true },
  canChat: { type: Boolean, default: true }, // прокинем с сервера/политики при желании
})

const comments = ref([])
const body = ref('')
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
            <button
              v-if="$page.props.auth.user && c.user && c.user.id === $page.props.auth.user.id"
              class="ml-auto text-xs text-red-600 hover:underline"
              @click="remove(c.id)"
            >Удалить</button>
          </div>
          <div class="mt-1 text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap">
            {{ c.body }}
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
        class="w-full rounded-lg border bg-white/80 dark:bg-gray-700 px-3 py-2 text-sm dark:text-white"
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
  </div>
</template>