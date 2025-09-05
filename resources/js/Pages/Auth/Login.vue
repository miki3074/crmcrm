<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

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

const baseInput =
  'mt-1 block w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900/50 ' +
  'px-3 py-2 text-sm outline-none ring-0 focus:border-slate-400 dark:focus:border-slate-600'
</script>

<template>
  <GuestLayout>
    <Head title="Вход" />

    <div class="text-center space-y-2 mb-6">
      <h1 class="text-2xl font-bold tracking-tight" style="color: aliceblue;">С возвращением</h1>
      <p class="text-sm text-slate-500">Войдите в аккаунт, чтобы продолжить</p>
    </div>

    <div v-if="status" class="mb-4 text-sm font-medium text-emerald-600">
      {{ status }}
    </div>

    <form @submit.prevent="submit" class="space-y-4">
      <div>
        <label for="email" class="text-sm font-medium" style="color: aliceblue;">Email</label>
        <input id="email" type="email" v-model="form.email" required autofocus autocomplete="username" :class="baseInput" />
        <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
      </div>

      <div>
        <label for="password" class="text-sm font-medium" style="color: aliceblue;">Пароль</label>
        <input id="password" type="password" v-model="form.password" required autocomplete="current-password" :class="baseInput" />
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
