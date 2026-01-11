<!-- Partials/TelegramBinding.vue -->
<script setup>
import { ref } from 'vue'
import axios from 'axios'

const props = defineProps(['user'])
const showInput = ref(false)
const chatId = ref('')
const saving = ref(false)
const localTelegramId = ref(props.user.telegram_chat_id)

const saveChatId = async () => {
    if (!chatId.value.trim()) return
    saving.value = true
    try {
        const { data } = await axios.post('/api/user/save-chat-id', { chat_id: chatId.value })
        localTelegramId.value = data.chat_id
        showInput.value = false
    } catch (e) {
        alert(e.response?.data?.message || 'Ошибка')
    } finally {
        saving.value = false
    }
}
</script>

<template>
    <div class="flex flex-wrap items-center justify-between p-4 bg-white/40 dark:bg-slate-900/40 backdrop-blur-md border border-slate-200 dark:border-slate-800 rounded-2xl gap-4">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-sky-100 dark:bg-sky-900/30 rounded-lg text-sky-600">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69.01-.03.01-.14-.07-.2-.08-.06-.19-.04-.27-.02-.12.02-1.96 1.25-5.54 3.69-.52.36-1 .53-1.42.52-.47-.01-1.37-.26-2.03-.48-.82-.27-1.47-.42-1.42-.88.03-.24.35-.49.96-.75 3.78-1.65 6.31-2.74 7.58-3.27 3.61-1.51 4.35-1.77 4.84-1.78.11 0 .35.03.5.16.12.1.16.23.18.33.02.08.03.23.01.35z"/></svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">Уведомления Telegram</p>
                <p class="text-xs text-slate-500" v-if="localTelegramId">ID: {{ localTelegramId }} (Привязан ✅)</p>
                <p class="text-xs text-slate-500" v-else>Подключите бота для получения задач</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <template v-if="!showInput">
                <a href="https://t.me/TrustCrmHelper_bot" target="_blank" class="px-4 py-2 text-xs font-bold text-white bg-sky-500 hover:bg-sky-600 rounded-xl transition shadow-lg shadow-sky-500/20">Запустить бота</a>
                <button @click="showInput = true" class="px-4 py-2 text-xs font-bold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700 transition">{{ localTelegramId ? 'Изменить ID' : 'Ввести ID' }}</button>
            </template>
            <div v-else class="flex items-center gap-2 animate-in fade-in zoom-in duration-200">
                <input v-model="chatId" type="text" placeholder="Chat ID" class="w-32 px-3 py-2 text-xs border rounded-xl dark:bg-slate-900 dark:border-slate-700 outline-none focus:ring-2 focus:ring-sky-500" />
                <button @click="saveChatId" :disabled="saving" class="px-3 py-2 text-xs bg-emerald-500 text-white rounded-xl hover:bg-emerald-600 disabled:opacity-50">OK</button>
                <button @click="showInput = false" class="text-xs text-slate-400">✕</button>
            </div>
        </div>
    </div>
</template>
