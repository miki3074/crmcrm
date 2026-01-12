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

// --- Логика защиты паролем ---
const isLocked = ref(true) // По умолчанию заблокировано
const passwordInput = ref('')
const authError = ref('')

async function unlock() {
    try {
        await axios.post('/api/chat/auth', { password: passwordInput.value })
        isLocked.value = false
        authError.value = ''
        await loadChats() // Загружаем чаты после успеха
    } catch (e) {
        authError.value = 'Неверный пароль'
        passwordInput.value = ''
    }
}
// -----------------------------

onMounted(async () => {
    // Пробуем загрузить чаты. Если сервер вернет 403, значит нужна авторизация.
    try {
        await loadChats()
        isLocked.value = false // Если загрузилось, значит сессия уже активна
    } catch (e) {
        if (e.response && e.response.status === 403) {
            isLocked.value = true
        }
    }
})

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
    try {
        messages.value = (await axios.get(`/api/chats/${chat.id}`)).data
        await nextTick()
        scrollToBottom()
    } catch (e) {
        if (e.response.status === 403) location.reload(); // Если сессия истекла
    }
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
        <!-- Модальное окно пароля -->
        <div v-if="isLocked" class="fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white p-6 rounded-lg shadow-xl w-96 text-center">
                <h2 class="text-xl font-bold mb-4">Введите пароль доступа</h2>
                <input
                    v-model="passwordInput"
                    @keyup.enter="unlock"
                    type="password"
                    placeholder="Пароль..."
                    class="w-full border border-gray-300 rounded px-3 py-2 mb-2 focus:ring-2 focus:ring-blue-500 outline-none"
                    autofocus
                >
                <p v-if="authError" class="text-red-500 text-sm mb-2">{{ authError }}</p>
                <button
                    @click="unlock"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition"
                >
                    Войти
                </button>
            </div>
        </div>

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
                    <!-- Исправленный цикл (удален вложенный v-for) -->
                    <div
                        v-for="c in chats"
                        :key="c.id"
                        class="p-3 cursor-pointer hover:bg-gray-100 border-b border-gray-100 flex justify-between items-center"
                        :class="{'bg-gray-200 font-semibold': activeChat?.id === c.id}"
                        @click="selectChat(c)"
                    >
                        <span class="truncate">{{ c.title }}</span>
                        <button @click.stop="deleteChat(c)" class="ml-2 text-red-500 hover:text-red-700">❌</button>
                    </div>
                </div>
            </aside>

            <!-- Main Chat Area -->
            <main class="flex-1 flex flex-col relative">
                <!-- Блокируем контент визуально, если нет пароля (на всякий случай) -->
                <div v-if="isLocked" class="absolute inset-0 bg-white z-10"></div>

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
