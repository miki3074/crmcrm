<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';

const props = defineProps({
    token: String,
    email: String
});

const form = ref({
    email: props.email || '',
    token: props.token || '',
    password: '',
    password_confirmation: ''
});

const notification = ref(null);
const loading = ref(false);
const showPassword = ref(false);
const showPasswordConfirm = ref(false);

const submitReset = async () => {
    notification.value = null;

    // Валидация на клиенте
    if (!form.value.password) {
        notification.value = { type: 'error', text: 'Введите новый пароль' };
        return;
    }

    if (form.value.password.length < 6) {
        notification.value = { type: 'error', text: 'Пароль должен содержать минимум 6 символов' };
        return;
    }

    if (form.value.password !== form.value.password_confirmation) {
        notification.value = { type: 'error', text: 'Пароли не совпадают' };
        return;
    }

    loading.value = true;

    try {
        const res = await axios.post('/api/password/reset', {
            email: form.value.email,
            token: form.value.token,
            password: form.value.password,
            password_confirmation: form.value.password_confirmation
        });

        if (res.data.success) {
            notification.value = { type: 'success', text: res.data.message };
            // Перенаправляем на страницу входа через 2 секунды
            setTimeout(() => {
                window.location.href = '/login';
            }, 2000);
        } else {
            notification.value = { type: 'error', text: res.data.message };
        }
    } catch (error) {
        if (error.response && error.response.data) {
            notification.value = { type: 'error', text: error.response.data.message };
        } else if (error.response && error.response.status === 422) {
            const errors = error.response.data.errors;
            if (errors.email) {
                notification.value = { type: 'error', text: errors.email[0] };
            } else if (errors.password) {
                notification.value = { type: 'error', text: errors.password[0] };
            } else {
                notification.value = { type: 'error', text: 'Проверьте правильность введенных данных' };
            }
        } else {
            notification.value = { type: 'error', text: '❌ Ошибка при сбросе пароля. Попробуйте позже.' };
        }
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <GuestLayout>
        <Head title="Сброс пароля" />

        <div class="mb-6 text-center">
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white mb-2">
                Создание нового пароля
            </h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Придумайте надежный пароль для вашей учетной записи
            </p>
        </div>

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

        <form @submit.prevent="submitReset" class="space-y-6">
            <!-- Email -->
            <div>
                <InputLabel for="email" value="Email" class="text-slate-700 dark:text-slate-300" />
                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full bg-slate-50 dark:bg-slate-800"
                    v-model="form.email"
                    disabled
                />
            </div>

            <!-- Новый пароль -->
            <div>
                <InputLabel for="password" value="Новый пароль" class="text-slate-700 dark:text-slate-300" />
                <div class="relative">
                    <TextInput
                        id="password"
                        :type="showPassword ? 'text' : 'password'"
                        class="mt-1 block w-full pr-10"
                        v-model="form.password"
                        required
                        placeholder="Минимум 6 символов"
                    />
                    <button
                        type="button"
                        @click="showPassword = !showPassword"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                    >
                        {{ showPassword ? '🙈' : '👁️' }}
                    </button>
                </div>
            </div>

            <!-- Подтверждение пароля -->
            <div>
                <InputLabel for="password_confirmation" value="Подтверждение пароля" class="text-slate-700 dark:text-slate-300" />
                <div class="relative">
                    <TextInput
                        id="password_confirmation"
                        :type="showPasswordConfirm ? 'text' : 'password'"
                        class="mt-1 block w-full pr-10"
                        v-model="form.password_confirmation"
                        required
                        placeholder="Повторите пароль"
                    />
                    <button
                        type="button"
                        @click="showPasswordConfirm = !showPasswordConfirm"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                    >
                        {{ showPasswordConfirm ? '🙈' : '👁️' }}
                    </button>
                </div>
            </div>

            <!-- Требования к паролю -->
            <div class="text-xs text-slate-500 space-y-1">
                <p>Пароль должен:</p>
                <ul class="list-disc list-inside ml-2">
                    <li>Содержать минимум 6 символов</li>
                    <li>Не совпадать с email</li>
                </ul>
            </div>

            <div class="flex items-center justify-between">
                <a href="/login" class="text-sm text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 hover:underline">
                    ← Вернуться к входу
                </a>

                <PrimaryButton :disabled="loading" class="relative">
                    <span v-if="loading" class="flex items-center gap-2">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Сохранение...
                    </span>
                    <span v-else class="flex items-center gap-2">
                        <span>🔐</span>
                        Сбросить пароль
                    </span>
                </PrimaryButton>
            </div>
        </form>
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
