<script setup>
import { ref, onMounted, nextTick } from 'vue'
import axios from 'axios'
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

const chats = ref([])
const messages = ref([])
const activeChat = ref(null)
const input = ref('')
const loading = ref(false)
const chatContainer = ref(null)

onMounted(loadChats)

// Загружаем список чатов
async function loadChats() {
    chats.value = (await axios.get('/api/chats')).data
}

// Создать новый чат
async function newChat() {
    const chat = (await axios.post('/api/chats')).data
    chats.value.unshift(chat)
    selectChat(chat)
}

// Выбрать чат
async function selectChat(chat) {
    activeChat.value = chat
    messages.value = (await axios.get(`/api/chats/${chat.id}`)).data
    await nextTick()
    scrollToBottom()
}

// Отправка сообщения
async function send() {
    if (!input.value || loading.value || !activeChat.value) return;

    messages.value.push({ role: 'user', content: input.value });
    messages.value.push({ role: 'assistant', content: '' });

    loading.value = true;

    try {
        const res = await fetch('/api/chat/stream', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                chat_id: activeChat.value.id,
                message: input.value
            })
        });

        const data = await res.json();
        messages.value.at(-1).content = data.content;
    } catch (e) {
        messages.value.at(-1).content = '(Ошибка при отправке запроса)';
        console.error(e);
    }

    loading.value = false;
    input.value = '';
}




async function deleteChat(chat) {
    if (!confirm(`Удалить "${chat.title}"?`)) return;

    try {
        await axios.delete(`/api/chats/${chat.id}`);
        chats.value = chats.value.filter(c => c.id !== chat.id);

        if (activeChat.value?.id === chat.id) {
            activeChat.value = null;
            messages.value = [];
        }
    } catch (e) {
        console.error(e);
        alert('Ошибка при удалении чата');
    }
}



// Прокрутка чата вниз
function scrollToBottom() {
    nextTick(() => {
        if (chatContainer.value) {
            chatContainer.value.scrollTop = chatContainer.value.scrollHeight
        }
    })
}
</script>



<template>
    <AuthenticatedLayout>
    <div class="flex h-96 bg-gray-100">
        <!-- Sidebar -->
        <aside class="w-64 bg-white border-r border-gray-200 flex flex-col">
            <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                <h2 class="font-bold text-lg">Чаты</h2>
                <button @click="newChat" class="text-white bg-blue-500 hover:bg-blue-600 px-2 py-1 rounded">
                    ➕
                </button>
            </div>
            <div class="flex-1 overflow-y-auto">
                <div
                    v-for="c in chats"
                    :key="c.id"
                    @click="selectChat(c)"
                    class="p-3 cursor-pointer hover:bg-gray-100 border-b border-gray-100"
                    :class="{'bg-gray-200 font-semibold': activeChat?.id === c.id}"
                >

                    <div v-for="c in chats" :key="c.id" class="chat-item">
                        <span @click="selectChat(c)">{{ c.title }}</span>
                        <button @click="deleteChat(c)" class="ml-2 text-red-500">❌</button>
                    </div>

                </div>
            </div>
        </aside>

        <!-- Main Chat Area -->
        <main class="flex-1 flex flex-col">
            <div ref="chatContainer" class="flex-1 overflow-y-auto p-4 space-y-3">
                <div
                    v-for="(m, index) in messages"
                    :key="index"
                    :class="m.role === 'user' ? 'text-right' : 'text-left'"
                >
                    <div
                        :class="m.role === 'user'
              ? 'inline-block bg-blue-500 text-white'
              : 'inline-block bg-gray-200 text-gray-900'"
                        class="px-4 py-2 rounded-lg max-w-xs break-words"
                    >
                        {{ m.content }}
                    </div>
                </div>
            </div>

            <!-- Input Box -->
            <div class="p-4 border-t border-gray-200 flex items-center space-x-3">
                <input
                    v-model="input"
                    @keyup.enter="send"
                    :disabled="loading || !activeChat"
                    type="text"
                    placeholder="Напишите сообщение..."
                    class="flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
                />
                <button
                    @click="send"
                    :disabled="loading || !input || !activeChat"
                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg disabled:opacity-50"
                >
                    {{ loading ? '...' : 'Отправить' }}
                </button>
            </div>
        </main>
    </div>
    </AuthenticatedLayout>
</template>
