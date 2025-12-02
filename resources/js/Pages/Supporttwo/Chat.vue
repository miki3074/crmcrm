<script setup>
import { ref, onMounted } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import axios from 'axios'

const { props } = usePage()

const threads = ref([])
const activeThread = ref(null)
const messages = ref([])

const loadingThreads = ref(false)
const loadingMessages = ref(false)
const sending = ref(false)

const newSubject = ref('')
const newMessage = ref('')
const attachedFiles = ref([]) // File[]

const fetchThreads = async () => {
  loadingThreads.value = true
  const { data } = await axios.get('/api/support/threads')
  threads.value = data
  loadingThreads.value = false
}

const openThread = async (thread) => {
  activeThread.value = thread
  loadingMessages.value = true
  const { data } = await axios.get(`/api/support/threads/${thread.id}`)
  messages.value = data.messages
  loadingMessages.value = false
}

// создание первого обращения
const createThread = async () => {
  if (!newMessage.value.trim() && !attachedFiles.value.length) return

  const fd = new FormData()
  fd.append('subject', newSubject.value || '')
  fd.append('message', newMessage.value || '')
  attachedFiles.value.forEach((f, i) => fd.append(`files[${i}]`, f))

  sending.value = true
  const { data } = await axios.post('/api/support/threads', fd, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
  sending.value = false

  threads.value.unshift(data)
  activeThread.value = data
  messages.value = data.messages || []

  newSubject.value = ''
  newMessage.value = ''
  attachedFiles.value = []
}

// сообщение в существующем диалоге
const sendMessage = async () => {
  if (!activeThread.value) return createThread()

   if (activeThread.value.status === 'closed') {
        alert("Обращение закрыто. Нельзя отправлять сообщения.");
        return;
    }

  if (!newMessage.value.trim() && !attachedFiles.value.length) return

  const fd = new FormData()
  fd.append('message', newMessage.value || '')
  attachedFiles.value.forEach((f, i) => fd.append(`files[${i}]`, f))

  sending.value = true
  const { data } = await axios.post(
    `/api/support/threads/${activeThread.value.id}/messages`,
    fd,
    { headers: { 'Content-Type': 'multipart/form-data' } }
  )
  sending.value = false

  messages.value.push(data)
  newMessage.value = ''
  attachedFiles.value = []
}

// обработка выбора файлов
const onFileChange = (e) => {
  const files = Array.from(e.target.files || [])
  attachedFiles.value.push(...files)
  e.target.value = ''
}

// вставка картинок через Ctrl+V
const onPaste = (e) => {
  const items = e.clipboardData?.items || []
  for (const item of items) {
    if (item.kind === 'file') {
      const file = item.getAsFile()
      if (file && file.type.startsWith('image/')) {
        // картинка из буфера
        attachedFiles.value.push(file)
        e.preventDefault() // чтобы не вставлялся base64-текст
      }
    }
  }
}

// удаление файла из предпросмотра
const removeFile = (index) => {
  attachedFiles.value.splice(index, 1)
}

onMounted(fetchThreads)
</script>

<template>
  <AuthenticatedLayout>
    <Head title="Техподдержка" />

    <template #header>
      <h2 class="text-2xl font-semibold text-slate-800 dark:text-slate-100">
        🛠 Техподдержка
      </h2>
    </template>

    <div class="max-w-6xl mx-auto p-6 grid grid-cols-1 md:grid-cols-[260px,1fr] gap-4">
      <!-- ЛЕВАЯ КОЛОНКА: список диалогов -->
      <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-700 p-3 flex flex-col">
        <div class="flex items-center justify-between mb-3">
          <span class="font-semibold">Диалоги</span>
          <button
            class="text-xs px-2 py-1 rounded-lg bg-blue-600 text-white"
            @click="activeThread = null; messages = []"
          >
            Новое обращение
          </button>
        </div>

        <div class="flex-1 overflow-y-auto space-y-2">
          <div
            v-for="t in threads"
            :key="t.id"
            class="p-2 rounded-xl cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800"
            :class="activeThread && activeThread.id === t.id ? 'bg-slate-100 dark:bg-slate-800' : ''"
            @click="openThread(t)"
          >
            <div class="text-sm font-semibold">
              {{ t.subject || 'Без темы' }}
            </div>
            <div class="text-xs text-slate-500">
              {{ t.status === 'closed' ? 'Закрыто' : 'Открыто' }}
            </div>
          </div>

          <div v-if="!threads.length && !loadingThreads" class="text-xs text-slate-400">
            У вас пока нет обращений.
          </div>
        </div>
      </div>

      <!-- ПРАВАЯ КОЛОНКА: чат -->
      <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-700 flex flex-col">
        <!-- заголовок -->
        <div class="border-b border-slate-200 dark:border-slate-700 px-4 py-3 flex justify-between items-center">
          <div>
            <div class="font-semibold">
              {{ activeThread ? (activeThread.subject || 'Обращение') : 'Новое обращение' }}
            </div>
          </div>
        </div>

        <!-- сообщения -->
        <div class="flex-1 overflow-y-auto px-4 py-3 space-y-3">
          <div v-if="!activeThread && !messages.length" class="text-sm text-slate-500">
            Напишите сообщение в техподдержку. Вы можете прикрепить фото/видео,
            а изображения — вставлять по <strong>Ctrl + V</strong>.
          </div>

          <div
            v-for="m in messages"
            :key="m.id"
            class="flex"
            :class="m.is_support ? 'justify-end' : 'justify-start'"
          >
            <div
              class="max-w-[75%] rounded-2xl px-3 py-2 text-sm shadow-sm"
              :class="m.is_support
                ? 'bg-blue-600 text-white'
                : 'bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-slate-100'"
            >
              <div v-if="m.body" class="whitespace-pre-line break-words">
                {{ m.body }}
              </div>

              <!-- вложения -->
              <div v-if="m.attachments?.length" class="mt-2 space-y-1">
                <div
                  v-for="file in m.attachments"
                  :key="file.id"
                  class="text-xs"
                >
                  <!-- картинка -->
                  <img
                    v-if="file.mime_type?.startsWith('image')"
                    :src="`/storage/${file.path}`"
                    class="rounded-lg max-w-xs border"
                  />
                  <!-- видео -->
                  <video
                    v-else-if="file.mime_type?.startsWith('video')"
                    controls
                    class="rounded-lg max-w-xs border"
                  >
                    <source :src="`/storage/${file.path}`" />
                  </video>
                  <!-- прочие файлы -->
                  <a
                    v-else
                    :href="`/storage/${file.path}`"
                    target="_blank"
                    class="underline"
                  >
                    📎 {{ file.original_name }}
                  </a>
                </div>
              </div>

              <div class="text-[10px] mt-1 opacity-70 text-right">
                {{ new Date(m.created_at).toLocaleTimeString() }}
              </div>
            </div>
          </div>
        </div>

        <!-- ввод -->
        <div
  v-if="!activeThread || activeThread.status !== 'closed'"
  class="border-t border-slate-200 dark:border-slate-700 px-4 py-3 space-y-2"
>


          <!-- предпросмотр файлов -->
          <div v-if="attachedFiles.length" class="flex flex-wrap gap-2">
            <div
              v-for="(f, idx) in attachedFiles"
              :key="idx"
              class="flex items-center gap-1 px-2 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-xs"
            >
              <span class="truncate max-w-[140px]">{{ f.name }}</span>
              <button @click="removeFile(idx)" class="text-red-500">×</button>
            </div>
          </div>

          <textarea
            v-model="newMessage"
            rows="2"
            class="w-full border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm dark:bg-slate-800 dark:text-slate-100"
            placeholder="Напишите сообщение..."
            @paste="onPaste"
          ></textarea>

          <div class="flex justify-between items-center">
            <div class="flex items-center gap-2">
              <label class="text-xs px-2 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 cursor-pointer">
                📎 Прикрепить
                <input type="file" class="hidden" multiple @change="onFileChange" />
              </label>
              <span class="text-[11px] text-slate-400">
                Можно вставлять картинки по <b>Ctrl + V</b>
              </span>
            </div>

            <button
              class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm hover:bg-blue-700 disabled:opacity-50"
              :disabled="sending"
              @click="activeThread ? sendMessage() : createThread()"
            >
              {{ activeThread ? 'Отправить' : 'Создать обращение' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
