<script setup>
import { ref, onMounted, nextTick, watch } from "vue";
import { Head } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import axios from "axios";

// Состояние
const threads = ref([]);
const activeThread = ref(null);
const messages = ref([]);
const messagesContainer = ref(null);

const filters = ref({
    status: "all", // 'all', 'open', 'closed'
    search: "",
});

const newMessage = ref("");
const sending = ref(false);
const loadingThreads = ref(false);
const loadingMessages = ref(false);
const attachedFiles = ref([]);

// Форматирование даты
const formatTime = (date) => {
    return new Date(date).toLocaleString('ru-RU', {
        day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit'
    });
};

// Загрузка списка тикетов
const loadThreads = async () => {
    loadingThreads.value = true;
    try {
        const { data } = await axios.get("/api/support/admin/threads", {
            params: filters.value,
        });
        threads.value = data;
    } catch (e) {
        console.error(e);
    }
    loadingThreads.value = false;
};

// Debounce для поиска
let searchTimeout;
const handleSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(loadThreads, 300);
};

// Автоскролл
const scrollToBottom = async () => {
    await nextTick();
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
};

// Открыть тикет
const openThread = async (thread) => {
    activeThread.value = thread;
    messages.value = [];
    loadingMessages.value = true;

    try {
        const { data } = await axios.get(`/api/support/admin/threads/${thread.id}`);
        messages.value = data.messages;
        scrollToBottom();
    } catch (e) {
        console.error(e);
    }
    loadingMessages.value = false;
};

// Отправка сообщения
const sendMessage = async () => {
    if (!newMessage.value.trim() && !attachedFiles.value.length) return;

    const fd = new FormData();
    fd.append("message", newMessage.value);
    attachedFiles.value.forEach((f, i) => fd.append(`files[${i}]`, f));

    sending.value = true;
    try {
        const { data } = await axios.post(
            `/api/support/admin/threads/${activeThread.value.id}/messages`,
            fd,
            { headers: { "Content-Type": "multipart/form-data" } }
        );
        messages.value.push(data);
        newMessage.value = "";
        attachedFiles.value = [];
        scrollToBottom();
    } catch (e) {
        console.error(e);
        alert('Ошибка отправки');
    }
    sending.value = false;
};

// Файлы и вставка картинок (Ctrl+V)
const onFileChange = (e) => {
    attachedFiles.value.push(...Array.from(e.target.files));
    e.target.value = "";
};

const onPaste = (e) => {
    const items = e.clipboardData?.items || [];
    for (const item of items) {
        if (item.kind === 'file') {
            const file = item.getAsFile();
            if (file && file.type.startsWith('image/')) {
                attachedFiles.value.push(file);
                e.preventDefault();
            }
        }
    }
};

const removeFile = (index) => {
    attachedFiles.value.splice(index, 1);
};

// Действия с тикетом
const closeThread = async () => {
    if(!confirm('Закрыть этот тикет?')) return;
    await axios.post(`/api/support/admin/threads/${activeThread.value.id}/close`);
    activeThread.value.status = "closed";
    // Обновляем статус в списке без перезагрузки
    const t = threads.value.find(x => x.id === activeThread.value.id);
    if(t) t.status = 'closed';
};

const reopenThread = async () => {
    await axios.post(`/api/support/admin/threads/${activeThread.value.id}/reopen`);
    activeThread.value.status = "open";
    const t = threads.value.find(x => x.id === activeThread.value.id);
    if(t) t.status = 'open';
};

onMounted(loadThreads);
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Admin Support" />

        <!-- Убираем дефолтный header слота, используем свой лейаут -->
        <div class="h-[calc(100vh-65px)] flex bg-gray-50 dark:bg-slate-900 overflow-hidden">

            <!-- ЛЕВАЯ ПАНЕЛЬ: Список -->
            <div class="w-80 border-r border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex flex-col z-10">

                <!-- Шапка фильтров -->
                <div class="p-4 border-b border-gray-100 dark:border-slate-800 space-y-3">
                    <h2 class="font-bold text-lg text-slate-800 dark:text-white">Входящие</h2>

                    <!-- Статусы -->
                    <div class="flex bg-gray-100 dark:bg-slate-800 p-1 rounded-lg">
                        <button
                            v-for="st in ['all', 'open', 'closed']"
                            :key="st"
                            @click="filters.status = st; loadThreads()"
                            class="flex-1 text-xs font-medium py-1.5 rounded-md transition-colors capitalize"
                            :class="filters.status === st
                ? 'bg-white dark:bg-slate-700 shadow text-slate-900 dark:text-white'
                : 'text-slate-500 hover:text-slate-700 dark:text-slate-400'"
                        >
                            {{ st === 'all' ? 'Все' : (st === 'open' ? 'Открытые' : 'Архив') }}
                        </button>
                    </div>

                    <!-- Поиск -->
                    <div class="relative">
            <span class="absolute left-2.5 top-2 text-gray-400">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            </span>
                        <input
                            v-model="filters.search"
                            @input="handleSearch"
                            type="text"
                            placeholder="Поиск по теме или имени..."
                            class="w-full pl-9 pr-3 py-1.5 text-sm bg-gray-50 dark:bg-slate-800 border-gray-200 dark:border-slate-700 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>
                </div>

                <!-- Список тикетов -->
                <div class="flex-1 overflow-y-auto custom-scrollbar">
                    <div v-if="loadingThreads" class="flex justify-center p-4">
                        <svg class="animate-spin h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    </div>

                    <div v-else-if="!threads.length" class="text-center p-6 text-gray-400 text-sm">
                        Тикетов не найдено
                    </div>

                    <div
                        v-for="t in threads"
                        :key="t.id"
                        @click="openThread(t)"
                        class="p-3 border-b border-gray-50 dark:border-slate-800/50 cursor-pointer hover:bg-blue-50 dark:hover:bg-slate-800/50 transition-colors"
                        :class="activeThread?.id === t.id ? 'bg-blue-50 border-l-4 border-l-blue-500 dark:bg-slate-800' : 'border-l-4 border-l-transparent'"
                    >
                        <div class="flex justify-between items-start mb-1">
              <span class="font-semibold text-sm text-slate-800 dark:text-gray-200 truncate max-w-[70%]">
                {{ t.user?.name || 'Неизвестный' }}
              </span>
                            <span class="text-[10px] text-gray-400">{{ new Date(t.updated_at).toLocaleDateString() }}</span>
                        </div>

                        <div class="text-xs font-medium text-slate-600 dark:text-slate-400 mb-1 truncate">
                            {{ t.subject || 'Без темы' }}
                        </div>

                        <div class="flex items-center gap-2">
              <span
                  class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider"
                  :class="t.status === 'open' ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600'"
              >
                {{ t.status === 'open' ? 'Open' : 'Closed' }}
              </span>
                            <span class="text-[10px] text-gray-400">#{{ t.id }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ПРАВАЯ ПАНЕЛЬ: Чат -->
            <div class="flex-1 flex flex-col bg-slate-50 dark:bg-black/20">

                <div v-if="!activeThread" class="flex-1 flex flex-col items-center justify-center text-gray-400">
                    <svg class="w-16 h-16 mb-4 text-gray-300 dark:text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" /></svg>
                    <p>Выберите тикет из списка слева</p>
                </div>

                <template v-else>
                    <!-- Хедер чата -->
                    <div class="h-16 border-b border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-6 flex justify-between items-center shadow-sm">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-bold text-lg text-slate-800 dark:text-white">
                                    {{ activeThread.subject || 'Обращение' }}
                                </h3>
                                <span class="text-gray-400 text-sm">#{{ activeThread.id }}</span>
                            </div>
                            <div class="text-xs text-slate-500">
                                Клиент: <span class="font-medium text-blue-600">{{ activeThread.user.name }}</span>
                                <span class="mx-1">•</span>
                                {{ activeThread.user.email }}
                            </div>
                        </div>

                        <!-- Кнопки действий -->
                        <div>
                            <button
                                v-if="activeThread.status === 'open'"
                                @click="closeThread"
                                class="flex items-center gap-1 px-3 py-1.5 bg-white border border-red-200 text-red-600 rounded-md text-sm hover:bg-red-50 transition-colors shadow-sm"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                Завершить
                            </button>

                            <button
                                v-else
                                @click="reopenThread"
                                class="flex items-center gap-1 px-3 py-1.5 bg-white border border-green-200 text-green-600 rounded-md text-sm hover:bg-green-50 transition-colors shadow-sm"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                Переоткрыть
                            </button>
                        </div>
                    </div>

                    <!-- Сообщения -->
                    <div
                        ref="messagesContainer"
                        class="flex-1 overflow-y-auto p-6 space-y-4 custom-scrollbar"
                    >
                        <div v-if="loadingMessages" class="flex justify-center py-4">
                            <svg class="animate-spin h-6 w-6 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        </div>

                        <div
                            v-for="m in messages"
                            :key="m.id"
                            class="flex group"
                            :class="m.is_support ? 'justify-end' : 'justify-start'"
                        >
                            <!-- Аватарка (опционально) -->
                            <div v-if="!m.is_support" class="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center text-xs font-bold text-slate-500 mr-2 shrink-0">
                                {{ activeThread.user.name[0] }}
                            </div>

                            <div
                                class="max-w-[70%] rounded-2xl px-4 py-3 text-sm shadow-sm border"
                                :class="
                  m.is_support
                    ? 'bg-blue-600 text-white border-blue-600 rounded-br-none'
                    : 'bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 border-gray-100 dark:border-slate-700 rounded-bl-none'
                "
                            >
                                <div class="whitespace-pre-wrap break-words">{{ m.body }}</div>

                                <!-- Вложения -->
                                <div v-if="m.attachments?.length" class="mt-3 flex flex-wrap gap-2">
                                    <div v-for="file in m.attachments" :key="file.id" class="relative">
                                        <a :href="`/storage/${file.path}`" target="_blank" class="block overflow-hidden rounded border border-white/20 hover:opacity-90">
                                            <img
                                                v-if="file.mime_type?.startsWith('image')"
                                                :src="`/storage/${file.path}`"
                                                class="max-w-[150px] max-h-[120px] object-cover"
                                            />
                                            <div v-else class="flex items-center gap-2 px-2 py-1.5 bg-black/20 rounded">
                                                <span class="text-xs truncate max-w-[120px]">{{ file.original_name }}</span>
                                            </div>
                                        </a>
                                    </div>
                                </div>

                                <div
                                    class="text-[10px] mt-1 text-right opacity-70"
                                    :class="m.is_support ? 'text-blue-100' : 'text-slate-400'"
                                >
                                    {{ formatTime(m.created_at) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Область ввода -->
                    <div class="p-4 bg-white dark:bg-slate-900 border-t border-gray-200 dark:border-slate-800">

                        <!-- Превью файлов -->
                        <div v-if="attachedFiles.length" class="flex flex-wrap gap-2 mb-2 p-2 bg-gray-50 dark:bg-slate-800 rounded-lg">
                            <div v-for="(f, idx) in attachedFiles" :key="idx" class="flex items-center gap-1 bg-white dark:bg-slate-900 px-2 py-1 rounded border shadow-sm text-xs">
                                <span class="truncate max-w-[150px]">{{ f.name }}</span>
                                <button @click="removeFile(idx)" class="text-red-500 hover:text-red-700 font-bold">×</button>
                            </div>
                        </div>

                        <div class="flex items-end gap-3">
                            <label class="cursor-pointer p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-full transition-colors">
                                <input type="file" multiple class="hidden" @change="onFileChange" />
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                            </label>

                            <div class="flex-1 relative">
                <textarea
                    v-model="newMessage"
                    rows="1"
                    placeholder="Напишите ответ... (Ctrl+Enter для отправки)"
                    class="w-full max-h-40 py-2.5 px-4 bg-gray-100 dark:bg-slate-800 border-0 rounded-2xl focus:ring-1 focus:ring-blue-500 focus:bg-white dark:focus:bg-slate-900 resize-none overflow-hidden text-sm transition-all"
                    @input="e => { e.target.style.height = 'auto'; e.target.style.height = e.target.scrollHeight + 'px' }"
                    @keydown.enter.ctrl.prevent="sendMessage"
                    @paste="onPaste"
                ></textarea>
                            </div>

                            <button
                                @click="sendMessage"
                                :disabled="sending || (!newMessage.trim() && !attachedFiles.length)"
                                class="bg-blue-600 hover:bg-blue-700 text-white rounded-full p-2.5 shadow-lg disabled:opacity-50 disabled:shadow-none transition-all"
                            >
                                <svg v-if="sending" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <svg v-else class="w-5 h-5 translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                            </button>
                        </div>

                        <div class="text-[10px] text-gray-400 mt-1 pl-12">
                            Поддерживается вставка изображений (Ctrl+V)
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Тонкий скроллбар */
.custom-scrollbar::-webkit-scrollbar {
    width: 5px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 20px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #334155;
}
</style>
