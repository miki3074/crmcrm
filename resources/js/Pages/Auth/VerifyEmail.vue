<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: String
});

const form = useForm({
    email: ''
});

const resendVerification = () => {
    form.post(route('verification.resend'));
};

const goToLogin = () => {
    window.location.href = '/login';
};
</script>

<template>
    <GuestLayout>
        <Head title="Подтверждение email" />

        <div class="mb-6 text-center">
            <div class="text-6xl mb-4">📧</div>
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white mb-2">
                Подтвердите ваш email
            </h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Мы отправили письмо со ссылкой для подтверждения
            </p>
        </div>

        <!-- Уведомления -->
        <div v-if="status" class="mb-4 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 text-emerald-800 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
            {{ status }}
        </div>

        <div v-if="form.errors.email" class="mb-4 p-4 rounded-xl bg-rose-50 dark:bg-rose-900/20 text-rose-800 dark:text-rose-400 border border-rose-200 dark:border-rose-800">
            {{ form.errors.email }}
        </div>

        <div class="text-center space-y-4">
            <p class="text-sm text-slate-600 dark:text-slate-300">
                Пожалуйста, проверьте вашу почту. Если письмо не пришло, проверьте папку "Спам".
            </p>

            <p class="text-xs text-slate-500">
                Не получили письмо? Вы можете запросить ссылку повторно
            </p>

            <div class="space-y-3">
                <input
                    type="email"
                    v-model="form.email"
                    placeholder="Введите ваш email"
                    class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-2 text-sm"
                />

                <button
                    @click="resendVerification"
                    :disabled="form.processing"
                    class="w-full inline-flex justify-center items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-4 py-2.5 text-sm font-semibold hover:shadow-lg transition-all disabled:opacity-60"
                >
                    <span v-if="form.processing" class="flex items-center gap-2">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Отправка...
                    </span>
                    <span v-else>
                        📧 Отправить повторно
                    </span>
                </button>
            </div>

            <div class="pt-4">
                <Link href="/login" class="text-sm text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 hover:underline">
                    ← Вернуться к входу
                </Link>
            </div>
        </div>
    </GuestLayout>
</template>
