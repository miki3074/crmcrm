<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const messages = ref([])
const selectedMessage = ref(null)
const sidebarOpen = ref(false)
const loading = ref(true)

const fetchHistory = async () => {
  const { data } = await axios.get('/api/support/history')
  messages.value = data.data
  loading.value = false
}

const openSidebar = async (message) => {
  selectedMessage.value = message
  sidebarOpen.value = true

  if (message.has_unread) {
    await axios.post(`/api/support/read/${message.id}`)
    message.has_unread = false // убираем маркер локально
  }
}


const closeSidebar = () => {
  sidebarOpen.value = false
  selectedMessage.value = null
}

const sendReply = async () => {
  if (!selectedMessage.value.newReply?.trim() && !selectedMessage.value.newFile) {
    return alert('Введите текст или прикрепите файл')
  }

  const formData = new FormData()
  formData.append('support_message_id', selectedMessage.value.id)
  formData.append('reply', selectedMessage.value.newReply || '')
  if (selectedMessage.value.newFile) {
    formData.append('file', selectedMessage.value.newFile)
  }

  try {
    const { data } = await axios.post('/api/support/reply', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    selectedMessage.value.replies.push(data.reply)

    selectedMessage.value.newReply = ''
    selectedMessage.value.newFile = null

  } catch (err) {
    console.error(err)
    alert('Ошибка при отправке ответа')
  }
}







onMounted(fetchHistory)
</script>

<template>
  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-2xl font-semibold text-slate-800 dark:text-slate-100 flex items-center gap-2">
        💬 История обращений
      </h2>
    </template>

    <div class="max-w-6xl mx-auto p-6 relative">
      <!-- Список обращений -->
      <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="m in messages"
          :key="m.id"
          @click="openSidebar(m)"
          class="cursor-pointer p-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm hover:shadow-md transition"
        >
          <div class="flex justify-between items-center mb-2">
  <h3 class="font-semibold text-slate-800 dark:text-slate-100">
    Обращение №{{ m.id }}
  </h3>

  <div class="flex items-center gap-2">

    <!-- 🟦 Индикатор непрочитанного -->
    <span
      v-if="m.has_unread"
      class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"
      title="Есть новые сообщения"
    ></span>

    <span
      class="px-2 py-1 text-xs rounded-full"
      :class="m.status === 'closed' ? 'bg-gray-200 text-gray-700' : 'bg-blue-100 text-blue-700'"
    >
      {{ m.status === 'closed' ? 'Завершено' : 'Открыто' }}
    </span>

  </div>
</div>

          <p class="text-slate-600 dark:text-slate-300 line-clamp-3">
            {{ m.message }}
          </p>
        </div>
      </div>

      <!-- Затемнение фона -->
      <transition name="fade">
        <div
          v-if="sidebarOpen"
          class="fixed inset-0 bg-black/40 z-40"
          @click="closeSidebar"
        ></div>
      </transition>

      <!-- Сайдбар -->
      <transition name="slide">
        <div
          v-if="sidebarOpen && selectedMessage"
          class="fixed right-0 top-0 h-full w-full sm:w-[1020px] bg-white dark:bg-slate-900 shadow-xl z-50 border-l border-slate-200 dark:border-slate-700 flex flex-col"
        >
          <!-- Шапка -->
          <div class="p-4 border-b dark:border-slate-700 flex justify-between items-center">
            <h3 class="font-semibold text-lg text-slate-800 dark:text-slate-100">
              Обращение №{{ selectedMessage.id }}
            </h3>
            <button @click="closeSidebar" class="text-slate-500 hover:text-slate-800">✕</button>
          </div>

          <div v-if="selectedMessage.attachments?.length" class="mt-3 space-y-3">
  <div
    v-for="file in selectedMessage.attachments"
    :key="file.id"
    class="flex flex-col gap-2"
  >
    <!-- Картинки -->
    <img
      v-if="file.mime_type.startsWith('image')"
      :src="`/storage/${file.path}`"
      class="rounded-lg max-w-96 border"
    />

    <!-- Видео -->
    <video
      v-else-if="file.mime_type.startsWith('video')"
      controls
      class="rounded-lg max-w-full border"
    >
      <source :src="`/storage/${file.path}`" />
    </video>

    <!-- Остальные файлы -->
    <a
      v-else
      :href="`/storage/${file.path}`"
      target="_blank"
      class="text-blue-600 underline"
    >
      📎 {{ file.original_name }}
    </a>
  </div>
</div>


          <!-- Основное содержимое -->
          <div class="p-4 flex-1 overflow-y-auto space-y-4">
            <div class="bg-slate-50 dark:bg-slate-800 rounded-lg p-3">
                 <!-- <h3 class="font-semibold text-lg text-slate-800 dark:text-slate-100">
              Обращение №{{ selectedMessage.id }}
            </h3> -->
              <p style="    overflow-wrap: break-word;
    width: 58%;"  class="text-slate-700 dark:text-slate-200 whitespace-pre-line">
                {{ selectedMessage.message }}
              </p>
              
            </div>

           <div v-if="selectedMessage.replies.length" class="space-y-3 px-2">
  <transition-group name="fade" tag="div">
    <div
      v-for="r in selectedMessage.replies"
      :key="r.id"
      class="flex"
      :class="r.user?.roles?.some(role => role.name === 'support')
              ? 'justify-end'
              : 'justify-start'"
    >

      <!-- Сообщение пользователя -->
      <div
        v-if="!r.user?.roles?.some(role => role.name === 'support')"
        class="max-w-[75%] mt-5 bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-100 rounded-2xl px-4 py-2 shadow-sm"
      >
        <p style="overflow-wrap: break-word" class="text-sm whitespace-pre-line">{{ r.reply }}</p>
        <p class="text-xs text-slate-400 mt-1 text-right">
          {{ new Date(r.created_at).toLocaleTimeString() }}
        </p>


<div v-if="r.attachment" class="mt-2">

  <img
    v-if="r.attachment.mime_type.startsWith('image')"
    :src="`/storage/${r.attachment.path}`"
    class="rounded-lg max-w-xs border"
  />

  <a
    v-else
    :href="`/storage/${r.attachment.path}`"
    class="text-blue-400 underline"
    target="_blank"
  >
    📎 {{ r.attachment.original_name }}
  </a>

</div>



      </div>

      <!-- Сообщение техподдержки -->
      <!-- Сообщение техподдержки -->
<div
  v-else
  class="max-w-[75%] mt-5 bg-blue-600 text-white rounded-2xl px-4 py-2 shadow-sm"
>
  <p class="flex items-center gap-1 text-sm">
    🛠 <strong>Техподдержка:</strong>
  </p>

  <!-- Текст -->
  <p v-if="r.reply" style="overflow-wrap: break-word" class="whitespace-pre-line text-sm mt-1">
    {{ r.reply }}
  </p>

  <!-- Вложение -->
  <div v-if="r.attachment" class="mt-2">

    <!-- Фото -->
    <img
      v-if="r.attachment.mime_type.startsWith('image')"
      :src="`/storage/${r.attachment.path}`"
      class="rounded-lg max-w-xs border"
    />

    <!-- Видео -->
    <video
      v-else-if="r.attachment.mime_type.startsWith('video')"
      controls
      class="rounded-lg max-w-xs border"
    >
      <source :src="`/storage/${r.attachment.path}`" />
    </video>

    <!-- Документ -->
    <a
      v-else
      :href="`/storage/${r.attachment.path}`"
      class="text-blue-200 underline block"
      target="_blank"
    >
      📎 {{ r.attachment.original_name }}
    </a>

  </div>

  <p class="text-xs text-blue-100 mt-1 text-right">
    {{ new Date(r.created_at).toLocaleTimeString() }}
  </p>
</div>


    </div>
  </transition-group>
</div>


          </div>

          <!-- Форма ответа -->
          <div v-if="selectedMessage.status !== 'closed'" class="p-4 border-t dark:border-slate-700 space-y-2">
  
  <textarea
    v-model="selectedMessage.newReply"
    placeholder="Напишите ответ..."
    rows="2"
    class="w-full border border-slate-300 dark:border-slate-600 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-500 dark:bg-slate-800 dark:text-slate-100"
  ></textarea>

  <!-- Выбор файла -->
  <input
    type="file"
    @change="selectedMessage.newFile = $event.target.files[0]"
    accept="image/*,.jpg,.jpeg,.png,.webp,.gif,.pdf"
    class="text-sm text-slate-700 dark:text-slate-300"
  />

  <button
    @click="sendReply"
    class="w-full bg-blue-600 text-white rounded-lg py-2 text-sm hover:bg-blue-700 transition"
  >
    Отправить
  </button>

</div>

        </div>
      </transition>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

.slide-enter-active, .slide-leave-active {
  transition: transform 0.3s ease;
}
.slide-enter-from {
  transform: translateX(100%);
}
.slide-leave-to {
  transform: translateX(100%);
}
</style>
