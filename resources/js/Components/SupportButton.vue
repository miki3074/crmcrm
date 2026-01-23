<script setup>
import { ref } from 'vue'
import axios from 'axios'

// Состояния
const isOpen = ref(false)
const isLoading = ref(false)
const isSuccess = ref(false) // Показать экран успеха
const error = ref('')

// Данные формы
const message = ref('')
const files = ref([])

// Открытие/закрытие
const toggleOpen = () => {
    isOpen.value = !isOpen.value
    if (!isOpen.value) {
        // Сброс таймеров или состояний при закрытии, если нужно
        setTimeout(() => {
            isSuccess.value = false
            error.value = ''
        }, 500)
    }
}

// Обработка файлов
const handleFiles = (e) => {
    const newFiles = Array.from(e.target.files || [])
    // Ограничение на кол-во файлов (например 5)
    if (files.value.length + newFiles.length > 5) {
        alert('Максимум 5 файлов')
        return
    }
    files.value.push(...newFiles)
    e.target.value = '' // сброс инпута
}

const removeFile = (index) => {
    files.value.splice(index, 1)
}

// Отправка
const sendMessage = async () => {
    if (!message.value.trim() && !files.value.length) return

    isLoading.value = true
    error.value = ''

    try {
        const fd = new FormData()

        // Добавляем URL страницы в конец сообщения для контекста
        const pageUrl = window.location.href
        const finalMessage = `${message.value}\n\n---\nОтправлено со страницы: ${pageUrl}`

        fd.append('message', finalMessage)

        // Формируем тему автоматически (или можно добавить поле subject)
        // Контроллер сам обрежет message для темы, если subject не передан

        files.value.forEach((file, index) => {
            fd.append(`files[${index}]`, file)
        })

        // Отправляем на endpoint создания тикета
        await axios.post('/api/support/threads', fd, {
            headers: { 'Content-Type': 'multipart/form-data' },
        })

        isSuccess.value = true
        message.value = ''
        files.value = []

    } catch (e) {
        console.error(e)
        if (e.response?.status === 429) {
            error.value = 'Слишком часто. Подождите немного.'
        } else {
            error.value = 'Ошибка отправки. Попробуйте позже.'
        }
    } finally {
        isLoading.value = false
    }
}
</script>

<template>
    <div class="fixed bottom-6 right-6 z-[9999] flex flex-col items-end gap-4 font-sans">

        <!-- Окно чата -->
        <transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 translate-y-4 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 translate-y-4 scale-95"
        >
            <div
                v-if="isOpen"
                class="w-[360px] max-w-[calc(100vw-40px)] bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden flex flex-col"
                style="max-height: 600px;"
            >
                <!-- Шапка -->
                <div class="bg-blue-600 p-4 text-white flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-lg">Техподдержка</h3>

                    </div>
                    <button @click="toggleOpen" class="text-white/80 hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <!-- Содержимое: Успех -->
                <div v-if="isSuccess" class="p-8 flex flex-col items-center justify-center text-center h-[300px]">
                    <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    </div>
                    <h4 class="text-xl font-bold text-slate-800 dark:text-white mb-2">Обращение создано!</h4>
                    <p class="text-sm text-slate-500 mb-6">Мы получили ваш запрос. Ответ придет сюда и на вашу почту.</p>

                    <a href="/supportmessages" class="text-blue-600 hover:underline text-sm font-medium">
                        Перейти ко всем диалогам →
                    </a>

                    <button @click="isSuccess = false" class="mt-4 text-xs text-slate-400 hover:text-slate-600">
                        Написать еще одно
                    </button>
                </div>

                <!-- Содержимое: Форма -->
                <div v-else class="flex flex-col p-4">

                    <!-- Список файлов -->
                    <div v-if="files.length" class="flex flex-wrap gap-2 mb-3">
                        <div v-for="(file, idx) in files" :key="idx" class="bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded text-xs flex items-center gap-2 border dark:border-slate-700">
                            <span class="truncate max-w-[150px] dark:text-slate-200">{{ file.name }}</span>
                            <button @click="removeFile(idx)" class="text-red-500 font-bold hover:scale-110 transition">×</button>
                        </div>
                    </div>

                    <textarea
                        v-model="message"
                        class="w-full bg-slate-50 dark:bg-slate-800 border-0 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 mb-3 resize-none dark:text-white"
                        rows="4"
                        placeholder="Опишите вашу проблему..."
                    ></textarea>

                    <div v-if="error" class="text-red-500 text-xs mb-3 text-center">
                        {{ error }}
                    </div>

                    <div class="flex items-center justify-between">
                        <!-- Скрепка -->
<!--                        <label class="cursor-pointer p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 hover:text-blue-600 transition-colors">-->
<!--                            <input type="file" multiple class="hidden" @change="handleFiles" />-->
<!--                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>-->
<!--                        </label>-->

                        <!-- Кнопка -->
                        <button
                            @click="sendMessage"
                            :disabled="isLoading || (!message && !files.length)"
                            class="bg-blue-600 hover:bg-blue-700 text-white rounded-lg px-6 py-2 text-sm font-medium transition-all shadow-lg shadow-blue-600/30 disabled:opacity-50 disabled:shadow-none flex items-center gap-2"
                        >
                            <span v-if="isLoading">Отправка...</span>
                            <span v-else>Отправить</span>
                            <svg v-if="!isLoading" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                        </button>
                    </div>
                </div>

                <!-- Подвал -->
                <div v-if="!isSuccess" class="bg-slate-50 dark:bg-slate-900/50 p-3 text-center text-[10px] text-slate-400 border-t border-slate-100 dark:border-slate-800">
                    Ваш ID: {{ $page.props.auth.user.id }} • Техподдержка NPO
                </div>
            </div>
        </transition>

        <!-- Кнопка-триггер (круглая) -->
        <button
            @click="toggleOpen"
            class="group w-14 h-14 rounded-full shadow-xl flex items-center justify-center transition-all duration-300 transform hover:scale-105 active:scale-95"
            :class="isOpen ? 'bg-slate-700 text-white rotate-90' : 'bg-blue-600 text-white hover:bg-blue-700'"
        >
            <svg v-if="!isOpen" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" /></svg>
            <svg v-else class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>

            <!-- Пульсация (если закрыто) -->

        </button>
    </div>
</template>
