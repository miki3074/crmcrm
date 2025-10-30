<script setup>
import { ref } from 'vue'
import axios from 'axios'

const props = defineProps({ initialId: [String, Number, null] })
const telegramId = ref(props.initialId ?? null)
const showInput = ref(false)
const chatId = ref('')
const saving = ref(false)

const saveChatId = async () => {
  if (!chatId.value.trim()) return alert('Введите Chat ID')
  try {
    saving.value = true
    await axios.get('/sanctum/csrf-cookie')
    const { data } = await axios.post('/api/user/save-chat-id', {
      chat_id: chatId.value,
    }, { withCredentials: true })
    telegramId.value = data.chat_id
    alert(data.message)
    showInput.value = false
  } catch (e) {
    alert(e.response?.data?.message || 'Ошибка при сохранении')
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <section class="border rounded p-4">
    <h3 class="text-lg font-semibold mb-2">Telegram</h3>
    <div v-if="telegramId" class="text-sm">Chat ID: <b>{{ telegramId }}</b></div>
    <div v-else class="text-sm text-slate-500">Chat ID не настроен</div>

    <div class="mt-3">
      <button class="px-3 py-2 rounded bg-slate-900 text-white" @click="showInput = !showInput">
        {{ showInput ? 'Скрыть' : 'Указать Chat ID' }}
      </button>
    </div>

    <div v-if="showInput" class="mt-3 flex gap-2">
      <input v-model="chatId" type="text" placeholder="123456789"
             class="border rounded px-3 py-2 flex-1">
      <button :disabled="saving" class="px-3 py-2 rounded bg-emerald-600 text-white"
              @click="saveChatId">
        Сохранить
      </button>
    </div>
  </section>
</template>
