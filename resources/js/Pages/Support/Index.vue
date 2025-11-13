<script setup>
import { ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import axios from 'axios'

const props = defineProps({
  messages: Object,
  supportUsers: Array, 
})

// Создаём локальную копию для работы
const messages = ref(JSON.parse(JSON.stringify(props.messages)))
const selectedMessage = ref(null)
const replyForms = ref({})

const sendReply = async (id) => {
  const text = replyForms.value[id];
  if (!text || text.trim() === "") return;

  try {
    const { data } = await axios.post(`/support/messages/${id}/reply`, {
      reply: text,
    });

    // сервер возвращает reply с user.roles
    const newReply = data.reply;

    // Добавляем в список сообщений
    const msg = messages.value.data.find(m => m.id === id);
    if (msg) msg.replies.push(newReply);

    if (selectedMessage.value?.id === id) {
      selectedMessage.value.replies.push(newReply);
    }

    replyForms.value[id] = "";
  } catch (err) {
    console.error(err);
    alert("Ошибка при отправке ответа");
  }
};



const transfer = async (id) => {
  const newSupport = selectedMessage.value.newSupport
  if (!newSupport) return alert("Выберите сотрудника")

  try {
    await axios.post(`/support/messages/${id}/transfer`, {
      new_support_id: newSupport
    });

    alert("Обращение успешно передано!")

    // Локально обновляем
    const msg = messages.value.data.find(m => m.id === id)
    if (msg) {
      msg.assigned_support_id = newSupport
    }

  } catch (err) {
    console.error(err)
    alert("Ошибка при передаче обращения")
  }
}


const closeMessage = async (id) => {
  if (!confirm('Завершить обращение?')) return
  await axios.post(`/support/messages/${id}/close`)
  const msg = messages.value.data.find((m) => m.id === id)
  if (msg) msg.status = 'closed'
}
</script>


<template>
  <Head title="Техподдержка" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-2xl text-slate-800 dark:text-slate-200 flex items-center gap-2">
        🛠 Панель техподдержки
      </h2>

     
    </template>

    <div class="flex h-[80vh]  mx-auto bg-white dark:bg-slate-900 rounded-2xl shadow-md overflow-hidden border border-slate-200 dark:border-slate-700">
      <!-- ЛЕВАЯ ЧАСТЬ: Список обращений -->
      <div class="w-1/3 border-r border-slate-200 dark:border-slate-700 overflow-y-auto">
        <div class="p-4 border-b border-slate-200 dark:border-slate-700 font-semibold text-slate-700 dark:text-slate-300">
          Обращения пользователей
        </div>

        <div v-if="messages.data.length === 0" class="text-slate-500 text-center py-10">
          Нет обращений.
        </div>

        <div v-for="m in messages.data" :key="m.id"
          class="p-4 cursor-pointer border-b border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 transition"
          :class="selectedMessage?.id === m.id ? 'bg-blue-50 dark:bg-blue-900/40' : ''"
          @click="selectedMessage = m"
        >
          <div class="flex justify-between items-center">
            <h3 class="font-medium text-slate-800 dark:text-slate-100 truncate">
              {{ m.user.name }}
            </h3>
            <span class="text-xs text-slate-400">
              #{{ m.id }}
            </span>
          </div>
          <p class="text-sm text-slate-600 dark:text-slate-300 truncate mt-1">
            {{ m.message }}
          </p>
          <span class="text-xs text-slate-400 mt-1 block">
            {{ new Date(m.created_at).toLocaleString() }}
          </span>
        </div>
      </div>

      <!-- ПРАВАЯ ЧАСТЬ: Переписка -->
      <div class="flex-1 flex flex-col">
        <div v-if="!selectedMessage" class="flex-1 flex items-center justify-center text-slate-500">
          Выберите обращение из списка
        </div>

        <div v-else class="flex flex-col h-full">
          <!-- Заголовок -->
          <div class="p-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center">
  <div>
    <h3 class="font-semibold text-slate-800 dark:text-slate-100">
      {{ selectedMessage.user.name }}
    </h3>
    <p class="text-xs text-slate-500">{{ selectedMessage.user.email }}</p>
  </div>

  <div class="flex items-center gap-2">
    <!-- ВЫПАДАЮЩИЙ СПИСОК support-сотрудников -->
    <select
      v-model="selectedMessage.newSupport"
      class="border rounded-lg px-2 py-1 text-sm dark:bg-slate-800 dark:text-white"
    >
      <option disabled value="">Передать обращение...</option>
      <option v-for="u in props.supportUsers" :key="u.id" :value="u.id">
        {{ u.name }}
      </option>
    </select>

    <!-- Кнопка ПЕРЕДАТЬ -->
    <button
      @click="transfer(selectedMessage.id)"
      class="px-3 py-1 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700"
    >
      Передать
    </button>

    <!-- Кнопка ЗАВЕРШИТЬ -->
    <button
      @click="closeMessage(selectedMessage.id)"
      class="px-3 py-1 bg-emerald-600 text-white text-sm rounded-lg hover:bg-emerald-700"
    >
      Завершить
    </button>
  </div>

 
</div>


<!-- Исходное обращение -->
<div class="p-4 border-b border-slate-200 dark:border-slate-700">
  <p style="    overflow-wrap: break-word;
    width: 58%;" class="text-sm text-slate-700 dark:text-slate-300">
    {{ selectedMessage.message }}
  </p>

 <div v-if="selectedMessage.page_url" class="mt-2">
    <p
      :href="selectedMessage.page_url"
      target="_blank"
      class="  text-sm break-all"
    >
      🔗 Страница: {{ selectedMessage.page_url }}
 </p>
    </div>

  <div v-if="selectedMessage.attachments?.length" class="mt-3 space-y-2">
    <div 
      v-for="file in selectedMessage.attachments"
      :key="file.id"
      class="flex items-start gap-3"
    >
      <!-- Картинки -->
      <img 
        v-if="file.mime_type.startsWith('image')"
        :src="`/storage/${file.path}`"
        class="rounded-lg max-w-xs border"
      />

      <!-- Видео -->
      <video
        v-else-if="file.mime_type.startsWith('video')"
        controls
        class="rounded-lg max-w-xs border"
      >
        <source :src="`/storage/${file.path}`">
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
</div>


          <!-- Сообщения -->
          <div class="flex-1 overflow-y-auto p-4 space-y-3 bg-slate-50 dark:bg-slate-800">

            
            <div
  v-for="r in selectedMessage.replies"
  :key="r.id"
  class="flex"
  :class="r.user?.roles?.some(role => role.name === 'support') 
            ? 'justify-end' 
            : 'justify-start'"
>
  <div
    class="max-w-[70%] px-4 py-3 rounded-2xl shadow-sm transition"
    :class="r.user?.roles?.some(role => role.name === 'support')
        ? 'bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-br-none'
        : 'bg-slate-200 dark:bg-slate-700 text-slate-900 dark:text-slate-100 rounded-bl-none'"
    style="overflow-wrap: break-word"
  >
    <p class="text-xs opacity-70 mb-1">
      {{ r.user?.roles?.some(role => role.name === 'support') ? '🛠 Техподдержка' : r.user?.name }}
    </p>

    <p class="text-sm leading-relaxed">
      {{ r.reply }}
    </p>

    <p class="text-[10px] opacity-70 mt-1 text-right">
      {{ new Date(r.created_at).toLocaleTimeString().slice(0, 5) }}
    </p>
  </div>
</div>


          </div>

          <!-- Поле для ответа -->
          <div class="p-4 border-t border-slate-200 dark:border-slate-700 flex gap-2 bg-white dark:bg-slate-900">
            <input
              v-model="replyForms[selectedMessage.id]"
              type="text"
              placeholder="Введите ответ..."
              class="flex-1 border rounded-lg px-3 py-2 text-sm dark:bg-slate-800 dark:text-white focus:ring-2 focus:ring-blue-500"
            />
            <button
              @click="sendReply(selectedMessage.id)"
              class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm"
            >
              ➤
            </button>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

