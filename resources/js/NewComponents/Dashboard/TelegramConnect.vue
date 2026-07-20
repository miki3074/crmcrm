<script setup>
import { ref } from 'vue'
import axios from 'axios'

const props = defineProps({ initialId: [String, Number] })
const telegramId = ref(props.initialId ?? null)
const editing = ref(false)
const chatId = ref('')
const saving = ref(false)
const message = ref('')

const save = async () => {
  if (!chatId.value.trim()) return
  saving.value = true
  message.value = ''
  try {
    await axios.get('/sanctum/csrf-cookie')
    const { data } = await axios.post('/api/user/save-chat-id', { chat_id: chatId.value }, { withCredentials: true })
    telegramId.value = data.chat_id
    message.value = data.message || 'Telegram подключён'
    editing.value = false
  } catch (e) {
    message.value = e.response?.data?.message || 'Не удалось сохранить Chat ID'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="rounded-xl border border-white/10 bg-[#1d1d1d] p-3">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <p class="text-xs font-medium text-slate-200">Telegram</p>
        <p class="mt-0.5 text-[11px]" :class="telegramId ? 'text-emerald-400' : 'text-slate-500'">
          {{ telegramId ? `Подключён · ID ${telegramId}` : 'Уведомления пока не подключены' }}
        </p>
      </div>
      <div class="flex items-center gap-2">
        <a href="https://t.me/UserInfeBot" target="_blank" class="rounded-md border border-white/10 px-2.5 py-1.5 text-[11px] text-slate-300 hover:bg-white/5">Получить ID</a>
        <button class="rounded-md bg-white px-2.5 py-1.5 text-[11px] font-medium text-black hover:bg-slate-200" @click="editing = !editing">{{ telegramId ? 'Изменить' : 'Подключить' }}</button>
      </div>
    </div>
    <div v-if="editing" class="mt-3 flex gap-2">
      <input v-model="chatId" class="min-w-0 flex-1 rounded-md border border-white/10 bg-[#141414] px-3 py-2 text-xs text-white outline-none focus:border-blue-500" placeholder="Chat ID" @keyup.enter="save" />
      <button :disabled="saving" class="rounded-md bg-blue-600 px-3 py-2 text-xs text-white disabled:opacity-50" @click="save">{{ saving ? '...' : 'Сохранить' }}</button>
    </div>
    <p v-if="message" class="mt-2 text-[11px] text-slate-400">{{ message }}</p>
  </div>
</template>
