<script setup>
import { Head, Link } from '@inertiajs/vue3'

defineProps({
  canLogin: Boolean,
  canRegister: Boolean,
  laravelVersion: { type: String, required: true },
  phpVersion:     { type: String, required: true },
})
</script>

<template>
  <Head title="Планшет" />

  <!-- Page wrapper -->
  <div class="min-h-screen bg-gradient-to-br from-slate-50 to-white dark:from-slate-900 dark:to-slate-950 text-slate-800 dark:text-slate-100">
    <!-- Top bar -->
    <header class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="h-9 w-9 rounded-xl bg-red-500/10 ring-1 ring-red-500/30 flex items-center justify-center">
          <!-- simple logo -->
          <svg viewBox="0 0 24 24" class="h-5 w-5 text-red-600"><path fill="currentColor" d="M12 2 2 7l10 5 10-5-10-5Zm0 7L2 4v13l10 5 10-5V4l-10 5Z"/></svg>
        </div>
        <span class="font-semibold tracking-tight">Планшет</span>
      </div>

      <nav v-if="canLogin" class="flex items-center gap-3">
        <Link
          v-if="$page.props.auth?.user"
          :href="route('dashboard')"
          class="px-3 py-2 rounded-lg text-sm font-medium hover:bg-slate-100 dark:hover:bg-slate-800 transition"
        >Панель</Link>

        <template v-else>
          <Link
            :href="route('login')"
            class="px-3 py-2 rounded-lg text-sm font-medium hover:bg-slate-100 dark:hover:bg-slate-800 transition"
          >Войти</Link>

          <Link
            v-if="canRegister"
            :href="route('register')"
            class="px-4 py-2 rounded-xl text-sm font-semibold bg-slate-900 text-white dark:bg-white dark:text-slate-900 hover:opacity-90 transition"
          >Регистрация</Link>
        </template>
      </nav>
    </header>

    <!-- Hero -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 lg:py-16">
      <div class="grid lg:grid-cols-2 gap-10 items-center">
        <div>
          <div class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-medium ring-1 ring-slate-300 dark:ring-slate-700">
            🔥 Новый проект CRM
          </div>

          <h1 class="mt-5 text-3xl sm:text-4xl lg:text-5xl font-bold leading-tight tracking-tight">
            Лёгкий старт для вашего
            <span class="text-red-600">CRM</span> проекта
          </h1>

          <p class="mt-4 text-slate-600 dark:text-slate-300 max-w-xl">
           Получите прозрачный контроль исполнения поручений
          </p>

          <div class="mt-6 flex flex-wrap items-center gap-3">
            <Link
              :href="$page.props.auth?.user ? route('dashboard') : route('login')"
              class="px-5 py-3 rounded-xl text-sm font-semibold bg-red-600 text-white hover:bg-red-700 transition shadow-sm"
            >
              Начать
            </Link>

             <Link
            v-if="canRegister"
            :href="route('register')"
            class="px-5 py-3 rounded-xl text-sm font-semibold ring-1 ring-slate-300 dark:ring-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
              target="_blank" rel="noreferrer"
          >Создать компанию</Link>

          </div>

        
        </div>

        <!-- Illustrative card -->
        <div class="relative">
          <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white/70 dark:bg-slate-900/50 backdrop-blur p-6 shadow-xl">
            <div class="grid grid-cols-2 gap-4">
              <div class="rounded-2xl bg-slate-50 dark:bg-slate-800 p-4">
                <div class="text-xs text-slate-500 mb-2">Проекты</div>
                <div class="h-24 rounded-xl bg-gradient-to-br from-red-500/20 to-red-500/5"></div>
              </div>
              <div class="rounded-2xl bg-slate-50 dark:bg-slate-800 p-4">
                <div class="text-xs text-slate-500 mb-2">Задачи</div>
                <div class="h-24 rounded-xl bg-gradient-to-br from-purple-500/20 to-purple-500/5"></div>
              </div>
              <div class="rounded-2xl bg-slate-50 dark:bg-slate-800 p-4">
                <div class="text-xs text-slate-500 mb-2">Календарь</div>
                <div class="h-24 rounded-xl bg-gradient-to-br from-blue-500/20 to-blue-500/5"></div>
              </div>
              <div class="rounded-2xl bg-slate-50 dark:bg-slate-800 p-4">
                <div class="text-xs text-slate-500 mb-2">Файлы</div>
                <div class="h-24 rounded-xl bg-gradient-to-br from-emerald-500/20 to-emerald-500/5"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Features -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-16">
      <div class="grid md:grid-cols-3 gap-6">
        <div class="rounded-2xl p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
          <div class="h-10 w-10 rounded-xl bg-red-500/10 text-red-600 flex items-center justify-center mb-3">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 1 0 10 10A10.011 10.011 0 0 0 12 2Zm1 15h-2v-2h2Zm0-4h-2V7h2Z"/></svg>
          </div>
          <h3 class="font-semibold text-lg">Легко внедрить</h3>
          <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Вы сможете подключить сотрудников и начать работать в CRMIQ всего за пару часов</p>
        </div>

        <div class="rounded-2xl p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
          <div class="h-10 w-10 rounded-xl bg-blue-500/10 text-blue-600 flex items-center justify-center mb-3">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M3 6h18v2H3Zm0 5h18v2H3Zm0 5h18v2H3Z"/></svg>
          </div>
          <h3 class="font-semibold text-lg">Работает повсюду</h3>
          <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Контролируйте ваши задачи в любой точке мира</p>
        </div>

        <div class="rounded-2xl p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
          <div class="h-10 w-10 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center mb-3">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="m9 16.17-3.59-3.58L4 14l5 5 11-11-1.41-1.42Z"/></svg>
          </div>
          <h3 class="font-semibold text-lg">Без установок</h3>
          <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Ничего не нужно скачивать и устанавливать. Нужны доступ в Интернет и современный браузер</p>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <!-- <footer class="border-t border-slate-200 dark:border-slate-800 py-8 text-center text-sm text-slate-500">
      © {{ new Date().getFullYear() }} crmiq. Laravel v{{ laravelVersion }} • PHP v{{ phpVersion }}
    </footer> -->
  </div>
</template>
