<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
defineProps({
  canResetPassword: Boolean,
  status: String,
})

const form = useForm({
  email: '',
  password: '',
  remember: false,
})

const submit = () => {
  form.post(route('login'), {
    onFinish: () => form.reset('password'),
  })
}

const showPassword = ref(false)

const baseInput =
  'mt-1 block w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900/50 ' +
  'px-3 py-2 text-sm outline-none ring-0 focus:border-slate-400 dark:focus:border-slate-600'
</script>

<template>
  <GuestLayout>
    <Head title="Вход" />

    <div class="text-center space-y-2 mb-6">
      <h1 class="text-2xl font-bold tracking-tight text-slate-500" >С возвращением</h1>
      <p class="text-sm text-slate-500">Войдите в аккаунт, чтобы продолжить</p>
    </div>

    <div v-if="status" class="mb-4 text-sm font-medium text-emerald-600">
      {{ status }}
    </div>

    <form @submit.prevent="submit" class="space-y-4">
      <div>
        <label for="email" class="text-sm font-medium text-slate-500" >Email</label>
        <input id="email" type="email" v-model="form.email" required autofocus autocomplete="username" :class="baseInput" />
        <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
      </div>

        <div>
            <label for="password" class="text-sm font-medium text-slate-500">Пароль</label>
            <div class="relative">
                <input
                    id="password"
                    :type="showPassword ? 'text' : 'password'"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    :class="['pr-10', baseInput]"
                />

                <!-- Кнопка "глазик" -->
                <button
                    type="button"
                    @click="showPassword = !showPassword"
                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300 transition-colors focus:outline-none"
                    :title="showPassword ? 'Скрыть пароль' : 'Показать пароль'"
                >
                    <!-- Иконка глаза (скрытый пароль) -->
                    <svg v-if="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>

                    <!-- Иконка перечеркнутого глаза (видимый пароль) -->
                    <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L6.59 6.59m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                    </svg>
                </button>
            </div>
            <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>
        </div>

      <div class="flex items-center justify-between">
        <label class="inline-flex items-center gap-2 text-sm" style="color: aliceblue;">
          <input type="checkbox" v-model="form.remember" class="rounded border-slate-300 text-slate-900 focus:ring-0" />
          Запомнить меня
        </label>

        <Link
          v-if="canResetPassword"
          :href="route('password.request')"
          class="text-sm text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 underline"
        >
          Забыли пароль?
        </Link>
      </div>

      <button
        type="submit"
        :disabled="form.processing"
        class="w-full inline-flex justify-center items-center gap-2 rounded-xl bg-slate-900 text-white dark:bg-white dark:text-slate-900 px-4 py-2.5 text-sm font-semibold hover:opacity-90 disabled:opacity-60"
      >
        Войти
      </button>
    </form>

    <div class="mt-6 text-center text-sm text-slate-500">
      Нет аккаунта?
      <Link :href="route('register')" class="font-medium text-slate-900 dark:text-white underline">Зарегистрироваться</Link>
    </div>
  </GuestLayout>
</template>
