<script setup>
import {
    Head,
    Link,
    useForm,
} from '@inertiajs/vue3'

defineProps({
    status: {
        type: String,
        default: null,
    },
})

const form = useForm({
    code: '',
})

const resendForm = useForm({})

const submit = () => {
    if (
        form.processing
        || form.code.length !== 6
    ) {
        return
    }

    form.post(
        route('login.verify.store'),
        {
            preserveScroll: true,

            onError: () => {
                /*
                 * Я бы НЕ очищал код здесь.
                 *
                 * Пользователь может ошибиться
                 * только в одной цифре.
                 */
            },
        }
    )
}

const resend = () => {
    if (resendForm.processing) {
        return
    }

    resendForm.post(
        route('login.resend'),
        {
            preserveScroll: true,

            onSuccess: () => {
                /*
                 * Новый код отправлен —
                 * очищаем старый.
                 */
                form.code = ''
                form.clearErrors()
            },
        }
    )
}
</script>

<template>
    <div class="min-h-screen bg-slate-100 flex items-center justify-center px-4 py-10">
        <div class="w-full max-w-md">

            <!-- Карточка -->
            <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/70 border border-slate-200 overflow-hidden">

                <!-- Верхняя декоративная часть -->
                <div class="bg-gradient-to-br from-slate-900 via-slate-800 to-indigo-900 px-8 pt-10 pb-14 text-center relative overflow-hidden">

                    <!-- Декоративные круги -->
                    <div class="absolute -top-16 -right-16 w-40 h-40 rounded-full bg-white/5"></div>
                    <div class="absolute -bottom-20 -left-16 w-48 h-48 rounded-full bg-indigo-400/10"></div>

                    <!-- Иконка -->
                    <div class="relative mx-auto w-16 h-16 rounded-2xl bg-white/10 border border-white/10 backdrop-blur flex items-center justify-center shadow-lg">
                        <svg
                            class="w-8 h-8 text-white"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622C17.176 19.29 21 14.591 21 9c0-1.042-.133-2.052-.382-3.016z"
                            />
                        </svg>
                    </div>

                    <h1 class="relative mt-5 text-2xl font-bold text-white tracking-tight">
                        Подтвердите вход
                    </h1>

                    <p class="relative mt-2 text-sm leading-6 text-slate-300 max-w-xs mx-auto">
                        Мы отправили 6-значный код подтверждения на вашу электронную почту.
                    </p>
                </div>

                <!-- Основной контент -->
                <div class="px-6 sm:px-8 pb-8 -mt-6 relative">

                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">

                        <!-- Успешный статус -->
                        <div
                            v-if="status"
                            class="mb-5 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3"
                        >
                            <div class="mt-0.5 shrink-0">
                                <svg
                                    class="w-5 h-5 text-emerald-600"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M5 13l4 4L19 7"
                                    />
                                </svg>
                            </div>

                            <p class="text-sm font-medium text-emerald-700">
                                {{ status }}
                            </p>
                        </div>

                        <form
                            @submit.prevent="submit"
                            class="space-y-5"
                        >
                            <!-- Код -->
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <label
                                        for="code"
                                        class="text-sm font-semibold text-slate-700"
                                    >
                                        Код подтверждения
                                    </label>

                                    <span class="text-xs text-slate-400">
                                        6 цифр
                                    </span>
                                </div>

                                <input
                                    id="code"
                                    v-model="form.code"
                                    type="text"
                                    inputmode="numeric"
                                    maxlength="6"
                                    autocomplete="one-time-code"
                                    autofocus
                                    placeholder="000000"
                                    @input="
                                        form.code = form.code
                                            .replace(/\D/g, '')
                                            .slice(0, 6)
                                    "
                                    :class="[
                                        'w-full h-16 rounded-2xl border bg-slate-50 text-center text-3xl font-bold tracking-[0.45em] pl-[0.45em] outline-none transition',
                                        form.errors.code
                                            ? 'border-rose-300 focus:border-rose-500 focus:ring-4 focus:ring-rose-100'
                                            : 'border-slate-300 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100'
                                    ]"
                                >

                                <!-- Ошибка -->
                                <div
                                    v-if="form.errors.code"
                                    class="mt-2 flex items-start gap-2"
                                >
                                    <svg
                                        class="w-4 h-4 text-rose-500 mt-0.5 shrink-0"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 9v2m0 4h.01M5.07 19h13.86a2 2 0 001.74-3L13.74 4a2 2 0 00-3.48 0L3.33 16a2 2 0 001.74 3z"
                                        />
                                    </svg>

                                    <p class="text-xs text-rose-600">
                                        {{ form.errors.code }}
                                    </p>
                                </div>

                                <p
                                    v-else
                                    class="mt-2 text-xs text-slate-400"
                                >
                                    Код действует ограниченное время.
                                </p>
                            </div>

                            <!-- Кнопка подтверждения -->
                            <button
                                type="submit"
                                :disabled="
                                    form.processing
                                    || form.code.length !== 6
                                "
                                class="w-full h-12 inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 text-white text-sm font-semibold shadow-lg shadow-slate-900/10 hover:bg-slate-800 focus:outline-none focus:ring-4 focus:ring-slate-200 disabled:opacity-50 disabled:cursor-not-allowed transition"
                            >
                                <svg
                                    v-if="form.processing"
                                    class="w-5 h-5 animate-spin"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                >
                                    <circle
                                        class="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        stroke-width="4"
                                    />

                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                                    />
                                </svg>

                                <svg
                                    v-else
                                    class="w-5 h-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M5 13l4 4L19 7"
                                    />
                                </svg>

                                {{
                                    form.processing
                                        ? 'Проверяем код...'
                                        : 'Подтвердить вход'
                                }}
                            </button>
                        </form>

                        <!-- Разделитель -->
                        <div class="my-6 flex items-center gap-3">
                            <div class="h-px flex-1 bg-slate-200"></div>

                            <span class="text-[11px] uppercase tracking-wider font-semibold text-slate-400">
                                или
                            </span>

                            <div class="h-px flex-1 bg-slate-200"></div>
                        </div>

                        <!-- Повторная отправка -->
                        <div class="text-center">
                            <p class="text-sm text-slate-500">
                                Не получили письмо?
                            </p>

                            <button
                                type="button"
                                :disabled="resendForm.processing"
                                @click="resend"
                                class="mt-2 inline-flex items-center justify-center gap-2 text-sm font-semibold text-indigo-600 hover:text-indigo-800 disabled:opacity-50 disabled:cursor-not-allowed transition"
                            >
                                <svg
                                    v-if="resendForm.processing"
                                    class="w-4 h-4 animate-spin"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                >
                                    <circle
                                        class="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        stroke-width="4"
                                    />

                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                                    />
                                </svg>

                                <svg
                                    v-else
                                    class="w-4 h-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9M4 9H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                    />
                                </svg>

                                {{
                                    resendForm.processing
                                        ? 'Отправляем новый код...'
                                        : 'Отправить код повторно'
                                }}
                            </button>
                        </div>
                    </div>

                    <!-- Назад -->
                    <div class="mt-5 text-center">
                        <Link
                            :href="route('login')"
                            class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-slate-900 transition"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M15 19l-7-7 7-7"
                                />
                            </svg>

                            Вернуться ко входу
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Нижняя подсказка -->
            <div class="mt-5 flex items-center justify-center gap-2 text-xs text-slate-400">
                <svg
                    class="w-4 h-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 11c0-1.105.895-2 2-2s2 .895 2 2v1h1a2 2 0 012 2v4a2 2 0 01-2 2h-6a2 2 0 01-2-2v-4a2 2 0 012-2h1v-1z"
                    />
                </svg>

                Защищённый вход в систему
            </div>
        </div>
    </div>
</template>