<script setup>
import { ref, onMounted, nextTick, watch } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import axios from 'axios'

const { props } = usePage()
const currentUser = props.auth.user // Предполагаем, что юзер доступен тут

const threads = ref([])
const activeThread = ref(null)
const messages = ref([])

const loadingThreads = ref(false)
const loadingMessages = ref(false)
const sending = ref(false)

const newSubject = ref('')
const newMessage = ref('')
const attachedFiles = ref([])
const messagesContainer = ref(null) // Для автоскролла

// Форматирование даты
const formatTime = (dateString) => {
    if (!dateString) return ''
    return new Date(dateString).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

const fetchThreads = async () => {
    loadingThreads.value = true
    try {
        const { data } = await axios.get('/api/support/threads')
        threads.value = data
    } catch (e) {
        console.error(e)
    }
    loadingThreads.value = false
}

const scrollToBottom = async () => {
    await nextTick()
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
    }
}

const openThread = async (thread) => {
    activeThread.value = thread
    messages.value = [] // Очистим пока грузим
    loadingMessages.value = true

    try {
        const { data } = await axios.get(`/api/support/threads/${thread.id}`)
        messages.value = data.messages
        scrollToBottom()
    } catch (e) {
        console.error(e)
    }
    loadingMessages.value = false
}

const createThread = async () => {
    if (!newMessage.value.trim() && !attachedFiles.value.length) return

    const fd = new FormData()
    fd.append('subject', newSubject.value || '')
    fd.append('message', newMessage.value || '')
    attachedFiles.value.forEach((f, i) => fd.append(`files[${i}]`, f))

    sending.value = true
    try {
        const { data } = await axios.post('/api/support/threads', fd, {
            headers: { 'Content-Type': 'multipart/form-data' },
        })

        threads.value.unshift(data)
        activeThread.value = data
        messages.value = data.messages || []

        newSubject.value = ''
        newMessage.value = ''
        attachedFiles.value = []
        scrollToBottom()
    } catch (e) {
        console.error(e)
    }
    sending.value = false
}

const sendMessage = async () => {
    if (!activeThread.value) return createThread()

    if (activeThread.value.status === 'closed') {
        alert("Обращение закрыто. Нельзя отправлять сообщения.")
        return
    }

    if (!newMessage.value.trim() && !attachedFiles.value.length) return

    const fd = new FormData()
    fd.append('message', newMessage.value || '')
    attachedFiles.value.forEach((f, i) => fd.append(`files[${i}]`, f))

    sending.value = true
    try {
        const { data } = await axios.post(
            `/api/support/threads/${activeThread.value.id}/messages`,
            fd,
            { headers: { 'Content-Type': 'multipart/form-data' } }
        )
        messages.value.push(data)
        newMessage.value = ''
        attachedFiles.value = []
        scrollToBottom()
    } catch (e) {
        console.error(e)
    }
    sending.value = false
}

const onFileChange = (e) => {
    const files = Array.from(e.target.files || [])
    attachedFiles.value.push(...files)
    e.target.value = ''
}

const onPaste = (e) => {
    const items = e.clipboardData?.items || []
    for (const item of items) {
        if (item.kind === 'file') {
            const file = item.getAsFile()
            if (file && file.type.startsWith('image/')) {
                attachedFiles.value.push(file)
                e.preventDefault()
            }
        }
    }
}

const removeFile = (index) => {
    attachedFiles.value.splice(index, 1)
}

// Сброс к созданию нового
const resetToNew = () => {
    activeThread.value = null
    messages.value = []
    newSubject.value = ''
}

onMounted(fetchThreads)
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Техподдержка" />

        <!-- Основной контейнер на всю высоту минус хедер -->
        <div class="h-[calc(100vh-80px)] max-w-7xl mx-auto p-4 md:p-6 grid grid-cols-1 md:grid-cols-[320px,1fr] gap-6 items-start">

            <!-- ЛЕВАЯ КОЛОНКА: Список диалогов -->
            <div class="h-full flex flex-col bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden">

                <!-- Шапка списка -->
                <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50 dark:bg-slate-900/50">
                    <h2 class="font-bold text-slate-700 dark:text-slate-200">Ваши обращения</h2>
                    <button
                        @click="resetToNew"
                        class="p-2 rounded-full bg-blue-100 text-blue-600 hover:bg-blue-200 dark:bg-blue-900/30 dark:text-blue-400 transition-colors"
                        title="Новое обращение"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>

                <!-- Список -->
                <div class="flex-1 overflow-y-auto p-3 space-y-2 custom-scrollbar">
                    <div v-if="loadingThreads" class="flex justify-center p-4">
                        <svg class="animate-spin h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    </div>

                    <div v-else-if="threads.length === 0" class="text-center py-10 text-slate-400 text-sm">
                        Нет активных диалогов.<br>Создайте новый!
                    </div>

                    <div
                        v-for="t in threads"
                        :key="t.id"
                        @click="openThread(t)"
                        class="group p-3 rounded-xl cursor-pointer transition-all duration-200 border border-transparent"
                        :class="activeThread?.id === t.id
              ? 'bg-blue-50 border-blue-100 dark:bg-blue-900/20 dark:border-blue-800'
              : 'hover:bg-slate-50 dark:hover:bg-slate-800'"
                    >
                        <div class="flex justify-between items-start mb-1">
              <span
                  class="text-xs font-medium px-2 py-0.5 rounded-md"
                  :class="t.status === 'open'
                  ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                  : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400'"
              >
                {{ t.status === 'open' ? 'В работе' : 'Закрыт' }}
              </span>
                            <span class="text-[10px] text-slate-400">
                #{{ t.id }}
              </span>
                        </div>

                        <div class="text-sm font-semibold text-slate-800 dark:text-slate-200 truncate mb-1">
                            {{ t.subject || 'Без темы' }}
                        </div>

<!--                        <div class="text-xs text-slate-500 dark:text-slate-400 truncate group-hover:text-slate-600 dark:group-hover:text-slate-300">-->
<!--                            {{ t.messages[0]?.body || 'Файл...' }}-->
<!--                        </div>-->
                    </div>
                </div>
            </div>

            <!-- ПРАВАЯ КОЛОНКА: Чат -->
            <div class="h-full flex flex-col bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden relative">

                <!-- Заголовок чата -->
                <div class="h-16 px-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between shrink-0 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md z-10">
                    <div class="flex items-center gap-3">
                        <!-- Аватар заглушка -->
                        <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-blue-500 to-purple-500 flex items-center justify-center text-white font-bold text-sm">
                            {{ activeThread ? 'S' : 'N' }}
                        </div>
                        <div>
                            <div class="font-bold text-slate-800 dark:text-slate-100">
                                {{ activeThread ? (activeThread.subject || 'Обращение #' + activeThread.id) : 'Новое обращение' }}
                            </div>
                            <div class="text-xs text-slate-500 flex items-center gap-1">
                 <span v-if="activeThread" class="flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full" :class="activeThread.status === 'open' ? 'bg-green-500' : 'bg-slate-400'"></span>
                    {{ activeThread.status === 'open' ? 'Открыто' : 'Закрыто' }}
                 </span>
                                <span v-else>Заполните форму ниже</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Область сообщений -->
                <div
                    ref="messagesContainer"
                    class="flex-1 overflow-y-auto p-4 space-y-4 bg-slate-50 dark:bg-[#0B1120] custom-scrollbar"
                >
                    <!-- Пустое состояние -->
                    <div v-if="!activeThread && messages.length === 0" class="h-full flex flex-col items-center justify-center text-slate-400 opacity-60">
                        <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                        <p>История сообщений пуста</p>
                    </div>

                    <!-- Спиннер загрузки сообщений -->
                    <div v-if="loadingMessages" class="flex justify-center py-10">
                        <svg class="animate-spin h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    </div>

                    <!-- Сообщения -->
                    <div
                        v-for="m in messages"
                        :key="m.id"
                        class="flex w-full"
                        :class="!m.is_support ? 'justify-end' : 'justify-start'"
                    >
                        <div
                            class="max-w-[80%] md:max-w-[70%] group relative"
                        >
                            <div
                                class="rounded-2xl px-4 py-3 shadow-sm text-[15px] leading-relaxed"
                                :class="[
                    !m.is_support
                        ? 'bg-blue-600 text-white rounded-tr-none'
                        : 'bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 border border-slate-100 dark:border-slate-700 rounded-tl-none'
                  ]"
                            >
                                <div v-if="m.body" class="whitespace-pre-wrap break-words">{{ m.body }}</div>

                                <!-- Вложения -->
                                <div v-if="m.attachments?.length" class="mt-3 flex flex-wrap gap-2">
                                    <div v-for="file in m.attachments" :key="file.id" class="relative group/file">
                                        <a :href="`/storage/${file.path}`" target="_blank" class="block overflow-hidden rounded-lg border border-white/20">
                                            <img
                                                v-if="file.mime_type?.startsWith('image')"
                                                :src="`/storage/${file.path}`"
                                                class="max-w-[200px] max-h-[150px] object-cover hover:scale-105 transition-transform"
                                            />
                                            <div v-else class="flex items-center gap-2 px-3 py-2 bg-black/10 rounded-lg">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                                <span class="text-xs truncate max-w-[150px]">{{ file.original_name }}</span>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Время сообщения -->
                            <div class="text-[10px] text-slate-400 mt-1 px-1 opacity-0 group-hover:opacity-100 transition-opacity"
                                 :class="!m.is_support ? 'text-right' : 'text-left'"
                            >
                                {{ formatTime(m.created_at) }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Форма ввода -->
                <div v-if="!activeThread || activeThread.status !== 'closed'" class="p-4 bg-white dark:bg-slate-900 border-t border-slate-100 dark:border-slate-800">

                    <!-- Если новый тикет - поле темы -->
                    <div v-if="!activeThread" class="mb-3 animate-fade-in">
                        <input
                            v-model="newSubject"
                            type="text"
                            placeholder="Тема обращения (кратко)"
                            class="w-full text-sm font-semibold px-4 py-2 bg-slate-50 dark:bg-slate-800 border-none rounded-lg focus:ring-1 focus:ring-blue-500 placeholder:font-normal"
                        >
                    </div>

                    <!-- Предпросмотр файлов -->
                    <div v-if="attachedFiles.length" class="flex flex-wrap gap-2 mb-3 p-2 bg-slate-50 dark:bg-slate-800 rounded-lg border border-slate-100 dark:border-slate-700">
                        <div v-for="(f, idx) in attachedFiles" :key="idx" class="flex items-center gap-2 bg-white dark:bg-slate-900 px-2 py-1 rounded-md shadow-sm border border-slate-200 dark:border-slate-700">
                            <span class="text-xs text-slate-600 dark:text-slate-300 max-w-[150px] truncate">{{ f.name }}</span>
                            <button @click="removeFile(idx)" class="text-slate-400 hover:text-red-500">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-end gap-3">
                        <!-- Кнопка скрепки -->
<!--                        <label class="p-2.5 text-slate-400 hover:text-blue-600 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full cursor-pointer transition-colors" title="Прикрепить файл">-->
<!--                            <input type="file" class="hidden" multiple @change="onFileChange" />-->
<!--                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>-->
<!--                        </label>-->

                        <!-- Textarea -->
                        <div class="flex-1 relative">
                <textarea
                    v-model="newMessage"
                    rows="1"
                    @input="e => { e.target.style.height = 'auto'; e.target.style.height = e.target.scrollHeight + 'px' }"
                    class="w-full max-h-32 min-h-[44px] py-2.5 pl-4 pr-4 bg-slate-100 dark:bg-slate-800 border-transparent focus:border-blue-500 focus:bg-white dark:focus:bg-slate-900 focus:ring-0 rounded-2xl text-sm resize-none overflow-hidden transition-all"
                    placeholder="Напишите сообщение... "
                    @paste="onPaste"
                    @keydown.enter.exact.prevent="activeThread ? sendMessage() : createThread()"
                ></textarea>
                        </div>

                        <!-- Кнопка отправить -->
                        <button
                            @click="activeThread ? sendMessage() : createThread()"
                            :disabled="sending || (!newMessage.trim() && !attachedFiles.length)"
                            class="p-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-full shadow-lg shadow-blue-600/30 disabled:opacity-50 disabled:shadow-none transition-all transform hover:scale-105 active:scale-95"
                        >
                            <svg v-if="sending" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <svg v-else class="w-5 h-5 translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                        </button>
                    </div>
                </div>

                <!-- Блок, если тикет закрыт -->
                <div v-if="activeThread?.status === 'closed'" class="p-4 bg-slate-50 dark:bg-slate-900/50 border-t border-slate-200 dark:border-slate-800 text-center text-sm text-slate-500">
                    Этот тикет закрыт. <button @click="resetToNew" class="text-blue-600 hover:underline">Создать новый?</button>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Скрываем скроллбар но оставляем прокрутку для чистоты */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: rgba(156, 163, 175, 0.3);
    border-radius: 20px;
}
</style>
