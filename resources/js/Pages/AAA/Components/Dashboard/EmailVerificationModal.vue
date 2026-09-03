<template>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95"
    >
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="close"></div>

            <!-- Modal -->
            <div class="relative w-full max-w-md bg-white dark:bg-zinc-900/95 rounded-2xl shadow-2xl border border-zinc-200 dark:border-white/10 overflow-hidden">
                <!-- Декоративная полоса -->
                <div class="absolute top-0 left-0 w-full h-0.5 bg-gradient-to-r from-cyan-400 via-sky-400 to-fuchsia-400"></div>

                <!-- Header -->
                <div class="px-6 py-5 border-b border-zinc-100 dark:border-white/5">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-cyan-500 to-sky-500 flex items-center justify-center text-white text-lg shadow-lg shadow-cyan-500/20">
                                ✉️
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-zinc-900 dark:text-white">
                                    Подтверждение email
                                </h3>
                                <p class="text-xs text-zinc-500 mt-1">
                                    Подтвердите ваш email адрес
                                </p>
                            </div>
                        </div>
                        <button @click="close"
                                class="w-8 h-8 rounded-full bg-zinc-100 dark:bg-white/5 hover:bg-zinc-200 dark:hover:bg-white/10 flex items-center justify-center text-zinc-500 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Body -->
                <div class="p-6 space-y-6">
                    <!-- Информация о текущем email -->
                    <div class="p-4 rounded-xl bg-zinc-50 dark:bg-white/[0.03] border border-zinc-100 dark:border-white/5">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-cyan-100 dark:bg-cyan-500/10 flex items-center justify-center">
                                <span class="text-lg">📧</span>
                            </div>
                            <div>
                                <p class="text-xs text-zinc-500 uppercase tracking-wider">Ваш email</p>
                                <p class="text-sm font-medium text-zinc-800 dark:text-white">{{ userEmail }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Статус верификации -->
                    <div v-if="isVerified" class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center">
                                <span class="text-lg">✅</span>
                            </div>
                            <div>
                                <p class="text-xs text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Статус</p>
                                <p class="text-sm font-medium text-emerald-700 dark:text-emerald-300">Email подтвержден</p>
                            </div>
                        </div>
                    </div>

                    <!-- Форма отправки кода -->
                    <div v-if="!isVerified" class="space-y-4">
                        <!-- Кнопка отправки кода -->
                        <button
                            @click="sendCode"
                            :disabled="loading || countdown > 0"
                            class="w-full py-3 rounded-xl bg-gradient-to-r from-cyan-600 to-sky-600 text-white font-medium shadow-lg shadow-cyan-500/30 hover:shadow-xl hover:scale-105 transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100"
                        >
                            <span v-if="loading" class="flex items-center justify-center gap-2">
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Отправка...
                            </span>
                            <span v-else-if="countdown > 0">
                                Отправить код повторно через {{ countdown }} сек
                            </span>
                            <span v-else>
                                <span class="flex items-center justify-center gap-2">
                                    <span>📧</span>
                                    Отправить код подтверждения
                                </span>
                            </span>
                        </button>

                        <!-- Поле для ввода кода -->
                        <div v-if="showCodeInput" class="space-y-3">
                            <div>
                                <label class="block text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-2">
                                    Введите код подтверждения
                                </label>
                                <div class="flex gap-3">
                                    <input
                                        v-model="verificationCode"
                                        type="text"
                                        maxlength="6"
                                        placeholder="000000"
                                        class="flex-1 px-4 py-3 rounded-xl border border-zinc-200 dark:border-white/10 bg-zinc-50 dark:bg-white/5 focus:border-cyan-300 focus:ring focus:ring-cyan-200/20 transition text-center text-lg font-mono tracking-widest"
                                        @keyup.enter="verifyCode"
                                    />
                                    <button
                                        @click="verifyCode"
                                        :disabled="verifying"
                                        class="px-6 py-3 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-medium shadow-lg shadow-emerald-500/30 hover:shadow-xl hover:scale-105 transition-all disabled:opacity-50"
                                    >
                                        <span v-if="verifying" class="flex items-center gap-2">
                                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Проверка...
                                        </span>
                                        <span v-else>✅ Проверить</span>
                                    </button>
                                </div>
                            </div>
                            <p class="text-xs text-zinc-500 text-center">
                                Код отправлен на ваш email. Проверьте папку "Спам" если письмо не пришло.
                            </p>
                        </div>

                        <!-- Сообщение об ошибке -->
                        <div v-if="errorMessage" class="p-3 rounded-xl bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800">
                            <p class="text-sm text-rose-600 dark:text-rose-400">{{ errorMessage }}</p>
                        </div>

                        <!-- Успешное сообщение -->
                        <div v-if="successMessage" class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800">
                            <p class="text-sm text-emerald-600 dark:text-emerald-400">{{ successMessage }}</p>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t border-zinc-100 dark:border-white/5 bg-zinc-50 dark:bg-white/[0.03] flex justify-end">
                    <button
                        v-if="isVerified"
                        @click="close"
                        class="px-6 py-2 rounded-xl bg-gradient-to-r from-cyan-600 to-sky-600 text-white font-medium shadow-lg shadow-cyan-500/30 hover:shadow-xl hover:scale-105 transition-all"
                    >
                        Закрыть
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>

<script setup>
import { ref, watch } from 'vue'
import axios from 'axios'
import { usePage } from '@inertiajs/vue3'

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    userEmail: {
        type: String,
        required: true
    },
    isVerified: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['close', 'verified'])

const loading = ref(false)
const verifying = ref(false)
const showCodeInput = ref(false)
const verificationCode = ref('')
const countdown = ref(0)
const errorMessage = ref('')
const successMessage = ref('')

let countdownInterval = null

// Сброс состояния при открытии модалки
watch(() => props.show, (newVal) => {
    if (newVal) {
        resetState()
    }
})

const resetState = () => {
    showCodeInput.value = false
    verificationCode.value = ''
    errorMessage.value = ''
    successMessage.value = ''
    loading.value = false
    verifying.value = false
}

const close = () => {
    if (countdownInterval) clearInterval(countdownInterval)
    emit('close')
}

// Отправка кода подтверждения
const sendCode = async () => {
    if (loading.value || countdown.value > 0) return

    loading.value = true
    errorMessage.value = ''
    successMessage.value = ''

    try {
        const response = await axios.post('/api/email/verification/send', {
            email: props.userEmail
        })

        if (response.data.success) {
            showCodeInput.value = true
            successMessage.value = 'Код подтверждения отправлен на ваш email'
            startCountdown(60)
        } else {
            errorMessage.value = response.data.message || 'Ошибка при отправке кода'
        }
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Ошибка при отправке кода. Попробуйте позже.'
    } finally {
        loading.value = false
    }
}

// Таймер обратного отсчета
const startCountdown = (seconds) => {
    countdown.value = seconds
    if (countdownInterval) clearInterval(countdownInterval)

    countdownInterval = setInterval(() => {
        if (countdown.value > 0) {
            countdown.value--
        } else {
            clearInterval(countdownInterval)
        }
    }, 1000)
}

// Проверка кода
const verifyCode = async () => {
    if (!verificationCode.value || verificationCode.value.length !== 6) {
        errorMessage.value = 'Введите 6-значный код подтверждения'
        return
    }

    verifying.value = true
    errorMessage.value = ''

    try {
        const response = await axios.post('/api/email/verification/verify', {
            email: props.userEmail,
            code: verificationCode.value
        })

        if (response.data.success) {
            successMessage.value = 'Email успешно подтвержден!'
            emit('verified')
            setTimeout(() => {
                close()
            }, 1500)
        } else {
            errorMessage.value = response.data.message || 'Неверный код подтверждения'
        }
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Ошибка при проверке кода'
    } finally {
        verifying.value = false
    }
}
</script>
