<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import axios from 'axios'

// Шаг регистрации: 1 - форма, 2 - подтверждение кода
const step = ref(1)
const email = ref('')
const formData = ref({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: ''
})

const verificationCode = ref('')
const loading = ref(false)
const notification = ref(null)

const baseInput = 'mt-1 block w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900/50 px-3 py-2 text-sm outline-none ring-0 focus:border-slate-400 dark:focus:border-slate-600'

const formatPhone = (e) => {
    let value = e.target.value
        .replace(/\D/g, "")
        .replace(/^8/, "7");

    value = value.substring(0, 11);

    let formatted = "+7";

    if (value.length > 1) formatted += " (" + value.substring(1, 4);
    if (value.length >= 4) formatted += ") " + value.substring(4, 7);
    if (value.length >= 7) formatted += "-" + value.substring(7, 9);
    if (value.length >= 9) formatted += "-" + value.substring(9, 11);

    e.target.value = formatted;
    formData.value.phone = formatted;
};

const showNotification = (type, text) => {
    notification.value = { type, text }
    setTimeout(() => {
        notification.value = null
    }, 5000)
}

// Отправка формы и получение кода
const sendVerificationCode = async () => {
    loading.value = true
    notification.value = null

    try {
        const response = await axios.post('/api/register/send-code', formData.value)

        if (response.data.success) {
            email.value = formData.value.email
            step.value = 2
            showNotification('success', response.data.message)
        }
    } catch (error) {
        if (error.response?.data?.errors) {
            const errors = error.response.data.errors
            const firstError = Object.values(errors)[0]
            showNotification('error', firstError?.[0] || 'Ошибка валидации')
        } else if (error.response?.data?.message) {
            showNotification('error', error.response.data.message)
        } else {
            showNotification('error', 'Ошибка при отправке кода. Попробуйте позже.')
        }
    } finally {
        loading.value = false
    }
}

// Подтверждение кода и регистрация
const verifyAndRegister = async () => {
    if (!verificationCode.value || verificationCode.value.length !== 6) {
        showNotification('error', 'Введите 6-значный код подтверждения')
        return
    }

    loading.value = true
    notification.value = null

    try {
        const response = await axios.post('/api/register/verify', {
            email: email.value,
            code: verificationCode.value
        })

        if (response.data.success) {
            showNotification('success', response.data.message)
            // Перенаправляем на главную после успешной регистрации
            setTimeout(() => {
                window.location.href = response.data.redirect || '/dashboard'
            }, 1500)
        }
    } catch (error) {
        if (error.response?.data?.message) {
            showNotification('error', error.response.data.message)
        } else {
            showNotification('error', 'Неверный код подтверждения')
        }
    } finally {
        loading.value = false
    }
}

// Возврат к форме
const backToForm = () => {
    step.value = 1
    verificationCode.value = ''
    notification.value = null
}

// Повторная отправка кода
const resendCode = async () => {
    loading.value = true

    try {
        const response = await axios.post('/api/register/send-code', formData.value)

        if (response.data.success) {
            showNotification('success', 'Новый код отправлен на email')
        }
    } catch (error) {
        showNotification('error', 'Ошибка при отправке кода')
    } finally {
        loading.value = false
    }
}
</script>

<template>
    <GuestLayout>
        <Head title="Регистрация" />

        <!-- Уведомления -->
        <transition name="fade">
            <div v-if="notification" class="mb-4 p-4 rounded-xl"
                 :class="notification.type === 'success'
                          ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-800 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800'
                          : 'bg-rose-50 dark:bg-rose-900/20 text-rose-800 dark:text-rose-400 border border-rose-200 dark:border-rose-800'">
                <div class="flex items-center gap-2">
                    <span v-if="notification.type === 'success'">✅</span>
                    <span v-else>❌</span>
                    <span>{{ notification.text }}</span>
                </div>
            </div>
        </transition>

        <!-- Шаг 1: Форма регистрации -->
        <div v-if="step === 1">
            <div class="text-center space-y-2 mb-6">
                <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-white">Создать аккаунт</h1>
                <p class="text-sm text-slate-500">Пара минут — и готово 👍</p>
            </div>

            <form @submit.prevent="sendVerificationCode" class="space-y-4">
                <div>
                    <label for="name" class="text-sm font-medium text-slate-700 dark:text-slate-300">Имя</label>
                    <input id="name" type="text" v-model="formData.name" required autofocus autocomplete="name" :class="baseInput" />
                </div>

                <div>
                    <label for="email" class="text-sm font-medium text-slate-700 dark:text-slate-300">Email</label>
                    <input id="email" type="email" v-model="formData.email" required autocomplete="username" :class="baseInput" />
                </div>

                <div>
                    <label for="phone" class="text-sm font-medium text-slate-700 dark:text-slate-300">Телефон</label>
                    <input id="phone" type="tel" v-model="formData.phone" @input="formatPhone" maxlength="18" autocomplete="tel" :class="baseInput" placeholder="+7 (___) ___-__-__" />
                </div>

                <div>
                    <label for="password" class="text-sm font-medium text-slate-700 dark:text-slate-300">Пароль</label>
                    <input id="password" type="password" v-model="formData.password" required autocomplete="new-password" :class="baseInput" />
                </div>

                <div>
                    <label for="password_confirmation" class="text-sm font-medium text-slate-700 dark:text-slate-300">Подтверждение пароля</label>
                    <input id="password_confirmation" type="password" v-model="formData.password_confirmation" required autocomplete="new-password" :class="baseInput" />
                </div>

                <button type="submit" :disabled="loading" class="w-full inline-flex justify-center items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-4 py-2.5 text-sm font-semibold hover:shadow-lg transition-all disabled:opacity-60">
                    <span v-if="loading" class="flex items-center gap-2">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Отправка кода...
                    </span>
                    <span v-else>Продолжить →</span>
                </button>
            </form>

            <div class="mt-6 text-center text-sm text-slate-500">
                Уже есть аккаунт?
                <Link :href="route('login')" class="font-medium text-indigo-600 dark:text-indigo-400 hover:underline">Войти</Link>
            </div>
        </div>

        <!-- Шаг 2: Подтверждение кода -->
        <div v-else>
            <div class="text-center space-y-2 mb-6">
                <div class="text-4xl">✉️</div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-white">Подтверждение email</h1>
                <p class="text-sm text-slate-500">
                    Мы отправили код подтверждения на <strong>{{ email }}</strong>
                </p>
            </div>

            <form @submit.prevent="verifyAndRegister" class="space-y-6">
                <div>
                    <label for="code" class="text-sm font-medium text-slate-700 dark:text-slate-300">Код подтверждения</label>
                    <input
                        id="code"
                        type="text"
                        v-model="verificationCode"
                        maxlength="6"
                        placeholder="000000"
                        class="mt-1 block w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900/50 px-3 py-2 text-sm text-center text-2xl font-mono tracking-widest outline-none ring-0 focus:border-indigo-500"
                        autofocus
                        required
                    />
                    <p class="mt-2 text-xs text-slate-500 text-center">
                        Введите 6-значный код из письма
                    </p>
                </div>

                <button type="submit" :disabled="loading" class="w-full inline-flex justify-center items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 text-white px-4 py-2.5 text-sm font-semibold hover:shadow-lg transition-all disabled:opacity-60">
                    <span v-if="loading" class="flex items-center gap-2">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Проверка...
                    </span>
                    <span v-else>Подтвердить и зарегистрироваться</span>
                </button>

                <div class="flex items-center justify-between pt-2">
                    <button type="button" @click="backToForm" class="text-sm text-slate-500 hover:text-slate-700">
                        ← Назад к форме
                    </button>
                    <button type="button" @click="resendCode" :disabled="loading" class="text-sm text-indigo-600 hover:text-indigo-500">
                        Отправить код повторно
                    </button>
                </div>
            </form>
        </div>
    </GuestLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
    transition: opacity 0.3s ease, transform 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}
</style>
