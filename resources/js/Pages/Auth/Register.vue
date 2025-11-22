<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

const form = useForm({
  name: '',
  email: '',
   phone: '',  
  password: '',
  password_confirmation: '',
})

const submit = () => {
  form.post(route('register'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}


const formatPhone = (e) => {
  let value = e.target.value
    .replace(/\D/g, "")      // удаляем всё, кроме цифр
    .replace(/^8/, "7");     // заменяем первую 8 → 7

  // ограничиваем максимум 11 цифр
  value = value.substring(0, 11);

  let formatted = "+7";

  if (value.length > 1) formatted += " (" + value.substring(1, 4);
  if (value.length >= 4) formatted += ") " + value.substring(4, 7);
  if (value.length >= 7) formatted += "-" + value.substring(7, 9);
  if (value.length >= 9) formatted += "-" + value.substring(9, 11);

  e.target.value = formatted;
  form.phone = formatted; // сохраняем в форму
};



const baseInput =
  'mt-1 block w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900/50 ' +
  'px-3 py-2 text-sm outline-none ring-0 focus:border-slate-400 dark:focus:border-slate-600'
</script>

<template>
  <GuestLayout>
    <Head title="Регистрация" />

    <div class="text-center space-y-2 mb-6">
      <h1 class="text-2xl font-bold tracking-tight text-slate-500" >Создать аккаунт</h1>
      <p class="text-sm text-slate-500">Пара минут — и готово 👍</p>
    </div>

    <form @submit.prevent="submit" class="space-y-4">
      <div>
        <label for="name" class="text-sm font-medium text-slate-500" >Имя</label>
        <input id="name" type="text" v-model="form.name" required autofocus autocomplete="name" :class="baseInput" />
        <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
      </div>

      <div>
        <label for="email" class="text-sm font-medium text-slate-500" >Email</label>
        <input id="email" type="email" v-model="form.email" required autocomplete="username" :class="baseInput" />
        <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
      </div>

      <div>
  <label for="phone" class="text-sm font-medium text-slate-500">Телефон</label>

  <input
    id="phone"
    type="tel"
    v-model="form.phone"
    @input="formatPhone"
    maxlength="18"
    autocomplete="tel"
    :class="baseInput"
    placeholder="+7 (___) ___-__-__"
  />

  <p v-if="form.errors.phone" class="mt-1 text-xs text-red-600">
    {{ form.errors.phone }}
  </p>
</div>



      <div>
        <label for="password" class="text-sm font-medium text-slate-500" >Пароль</label>
        <input id="password" type="password" v-model="form.password" required autocomplete="new-password" :class="baseInput" />
        <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>
      </div>

      <div>
        <label for="password_confirmation" class="text-sm font-medium text-slate-500" >Подтверждение пароля</label>
        <input id="password_confirmation" type="password" v-model="form.password_confirmation" required autocomplete="new-password" :class="baseInput" />
        <p v-if="form.errors.password_confirmation" class="mt-1 text-xs text-red-600">{{ form.errors.password_confirmation }}</p>
      </div>

      <button
        type="submit"
        :disabled="form.processing"
        class="w-full inline-flex justify-center items-center gap-2 rounded-xl bg-slate-900 text-white dark:bg-white dark:text-slate-900 px-4 py-2.5 text-sm font-semibold hover:opacity-90 disabled:opacity-60"
      >
        Зарегистрироваться
      </button>
    </form>

    <div class="mt-6 text-center text-sm text-slate-500">
      Уже есть аккаунт?
      <Link :href="route('login')" class="font-medium text-slate-900 dark:text-white underline">Войти</Link>
    </div>
  </GuestLayout>
</template>
