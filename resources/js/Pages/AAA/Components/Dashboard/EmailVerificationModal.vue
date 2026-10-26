<template>
    <Transition
        enter-active-class="transition duration-150 ease-out"
        enter-from-class="opacity-0 scale-[0.98]"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition duration-100 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-[0.98]"
    >
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-zinc-950/50 backdrop-blur-[2px]" @click="close"></div>

            <!-- Modal -->
            <div class="relative w-full max-w-md overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-xl dark:border-white/10 dark:bg-zinc-900">
                <!-- Декоративная полоса -->
                <div class="absolute left-0 top-0 h-px w-full bg-zinc-200 dark:bg-white/10"></div>

                <!-- Header -->
                <div class="border-b border-zinc-200 px-6 py-5 dark:border-white/[0.06]">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-md border border-zinc-200 text-zinc-600 dark:border-white/10 dark:text-zinc-300">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-50">
                                    Подтверждение email
                                </h3>
                                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                    Подтвердите ваш email адрес
                                </p>
                            </div>
                        </div>
                        <button @click="close"
                                aria-label="Закрыть"
                                class="flex h-8 w-8 items-center justify-center rounded-md text-zinc-500 transition-colors hover:bg-zinc-100 hover:text-zinc-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60 dark:text-zinc-400 dark:hover:bg-white/[0.06] dark:hover:text-zinc-100">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Body -->
                <div class="space-y-5 p-6">
                    <!-- Информация о текущем email -->
                    <div class="rounded-md border border-zinc-200 bg-zinc-50 p-4 dark:border-white/[0.06] dark:bg-white/[0.02]">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-md bg-white text-zinc-500 ring-1 ring-zinc-200 dark:bg-white/[0.04] dark:text-zinc-400 dark:ring-white/10">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-3.5 7.125" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Ваш email</p>
                                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ userEmail }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Статус верификации -->
                    <div v-if="isVerified" class="rounded-md border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-500/20 dark:bg-emerald-500/10">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-md bg-white text-emerald-600 ring-1 ring-emerald-200 dark:bg-transparent dark:text-emerald-400 dark:ring-emerald-500/30">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-emerald-700 dark:text-emerald-400">Статус</p>
                                <p class="text-sm font-medium text-emerald-800 dark:text-emerald-300">Email подтвержден</p>
                            </div>
                        </div>
                    </div>

                    <!-- Форма отправки кода -->
                    <div v-if="!isVerified" class="space-y-4">
                        <!-- Кнопка отправки кода -->
                        <button
                            @click="sendCode"
                            :disabled="loading || countdown > 0"
                            class="w-full rounded-md bg-blue-600 py-2.5 text-sm font-medium text-white transition-colors hover:bg-blue-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:focus-visible:ring-offset-zinc-900"
                        >
                            <span v-if="loading" class="flex items-center justify-center gap-2">
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Отправка...
                            </span>
                            <span v-else-if="countdown > 0" class="tabular-nums">
                                Отправить код повторно через {{ countdown }} сек
                            </span>
                            <span v-else>
                                <span class="flex items-center justify-center gap-2">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                    Отправить код подтверждения
                                </span>
                            </span>
                        </button>

                        <!-- Поле для ввода кода -->
                        <div v-if="showCodeInput" class="space-y-3">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    Введите код подтверждения
                                </label>
                                <div class="flex gap-2">
                                    <input
                                        v-model="verificationCode"
                                        type="text"
                                        maxlength="6"
                                        placeholder="000000"
                                        class="min-w-0 flex-1 rounded-md border border-zinc-300 bg-white px-3 py-2.5 text-center font-mono text-lg tracking-[0.3em] text-zinc-900 placeholder:text-zinc-300 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20 dark:border-white/10 dark:bg-zinc-950 dark:text-zinc-100 dark:placeholder:text-zinc-600"
                                        @keyup.enter="verifyCode"
                                    />
                                    <button
                                        @click="verifyCode"
                                        :disabled="verifying"
                                        class="shrink-0 rounded-md bg-zinc-900 px-5 py-2.5 text-sm font-medium text-white transition-colors hover:bg-zinc-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-white dark:focus-visible:ring-offset-zinc-900"
                                    >
                                        <span v-if="verifying" class="flex items-center gap-2">
                                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Проверка...
                                        </span>
                                        <span v-else>Проверить</span>
                                    </button>
                                </div>
                            </div>
                            <p class="text-center text-xs leading-5 text-zinc-500 dark:text-zinc-400">
                                Код отправлен на ваш email. Проверьте папку "Спам" если письмо не пришло.
                            </p>
                        </div>

                        <!-- Сообщение об ошибке -->
                        <div v-if="errorMessage" class="rounded-md border border-rose-200 bg-rose-50 p-3 dark:border-rose-500/20 dark:bg-rose-500/10">
                            <p class="text-sm text-rose-700 dark:text-rose-300">{{ errorMessage }}</p>
                        </div>

                        <!-- Успешное сообщение -->
                        <div v-if="successMessage" class="rounded-md border border-emerald-200 bg-emerald-50 p-3 dark:border-emerald-500/20 dark:bg-emerald-500/10">
                            <p class="text-sm text-emerald-700 dark:text-emerald-300">{{ successMessage }}</p>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex justify-end border-t border-zinc-200 bg-zinc-50 px-6 py-4 dark:border-white/[0.06] dark:bg-white/[0.02]">
                    <button
                        v-if="isVerified"
                        @click="close"
                        class="rounded-md bg-blue-600 px-5 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-zinc-900"
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
