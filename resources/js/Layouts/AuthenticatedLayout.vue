<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import {Link, router, usePage} from '@inertiajs/vue3' // usePage пригодится для проверки роутов, если нужно

// Импорт компонентов (предполагаем, что они у тебя есть)
import ApplicationLogo from '@/Components/ApplicationLogo.vue'
import Dropdown from '@/Components/Dropdown.vue'
import DropdownLink from '@/Components/DropdownLink.vue'
import NavLink from '@/Components/NavLink.vue' // Примечание: NavLink обычно стилизован под горизонтальное меню, возможно придется подправить CSS
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue'
import SupportButton from '@/Components/SupportButton.vue'
import DevtoolsGuard from '@/Components/DevtoolsGuard.vue'

const showingNavigationDropdown = ref(false)
const isDark = ref(false)

/* === 🔵 Уведомления тех.поддержки === */
const unreadSupport = ref(0)

const loadUnread = async () => {
    try {
        const { data } = await axios.get('/api/support/history')
        unreadSupport.value = data.data.filter(m => m.has_unread).length
    } catch (err) {
        console.error('Не удалось загрузить непрочитанные сообщения:', err)
    }
}

onMounted(() => {
    // Темная тема
    if (
        localStorage.theme === 'dark' ||
        (!('theme' in localStorage) &&
            window.matchMedia('(prefers-color-scheme: dark)').matches)
    ) {
        document.documentElement.classList.add('dark')
        isDark.value = true
    }
    loadUnread()
})

const toggleTheme = () => {
    isDark.value = !isDark.value
    if (isDark.value) {
        document.documentElement.classList.add('dark')
        localStorage.theme = 'dark'
    } else {
        document.documentElement.classList.remove('dark')
        localStorage.theme = 'light'
    }
}
</script>

<template>
    <!-- Главный контейнер: flex и высота во весь экран -->
    <div class="flex h-screen bg-gray-100 dark:bg-gray-900 overflow-hidden">

        <!-- ================= САЙДБАР (СЛЕВА) ================= -->
        <!-- hidden md:flex означает: скрыт на мобильных, виден на экранах md и выше -->
        <aside class="w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 hidden md:flex flex-col flex-shrink-0 transition-width duration-200">

            <!-- Логотип -->
            <div class="h-16 flex items-center justify-center border-b border-gray-100 dark:border-gray-700 px-4">
                <Link :href="route('dashboard')">
                    <ApplicationLogo class="block h-10 w-auto fill-current text-gray-800 dark:text-gray-200" />
                </Link>
            </div>

            <!-- Ссылки меню (Вертикальный список) -->
            <div class="flex-1 overflow-y-auto py-4 flex flex-col space-y-2 px-2">

                <!-- Обычный NavLink в Laravel Breeze имеет стили border-b (для горизонтального меню).
                     Для сайдбара лучше использовать блок. Я добавлю inline стили или классы для наглядности -->

                <Link
                    :href="route('dashboard')"
                    class="flex items-center px-4 py-2 text-sm font-medium rounded-md transition-colors duration-150"
                    :class="route().current('dashboard')
                    ? 'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-white'
                    : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white'"
                >
                    <svg class="mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                    Главная
                </Link>

                <Link
                    :href="route('support.history')"
                    class="flex items-center px-4 py-2 text-sm font-medium rounded-md transition-colors duration-150 relative"
                    :class="route().current('support.history')
                    ? 'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-white'
                    : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white'"
                >
                    <svg class="mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                    Техподдержка
                    <!-- 🔵 индикатор -->
                    <span v-if="unreadSupport > 0" class="ml-auto w-2.5 h-2.5 bg-blue-500 rounded-full animate-pulse"></span>
                </Link>

                <Link
                    :href="route('meeting-documents.index')"
                    class="flex items-center px-4 py-2 text-sm font-medium rounded-md transition-colors duration-150"
                    :class="route().current('meeting-documents.index')
                    ? 'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-white'
                    : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white'"
                >
                    <svg class="mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    Повестки
                </Link>

                <Link
                    :href="route('tasks.summary')"
                    class="flex items-center px-4 py-2 text-sm font-medium rounded-md transition-colors duration-150"
                    :class="route().current('tasks.summary')
        ? 'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-white'
        : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white'"
                >
                    <!-- Иконка (Clipboard/Список задач) -->
                    <svg class="mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    Пул задач
                </Link>

                <Link
                    :href="route('tasks.completed')"
                    class="flex items-center px-4 py-2 text-sm font-medium rounded-md transition-colors duration-150"
                    :class="route().current('tasks.completed')
    ? 'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-white'
    : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white'"
                >
                    <!-- Иконка Флага или Архива -->
                    <svg class="mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Завершенные
                </Link>

            </div>

            <!-- Нижняя часть сайдбара: Переключатель темы -->
            <div class="p-4 border-t border-gray-200 dark:border-gray-700 flex justify-center">
                <button
                    @click="toggleTheme"
                    class="relative inline-flex items-center justify-center w-10 h-10 rounded-full
                    bg-gradient-to-r from-yellow-300 via-orange-400 to-pink-500
                    dark:from-gray-600 dark:via-gray-700 dark:to-gray-800
                    text-white shadow-lg transition-all duration-500 ease-in-out
                    hover:scale-110 hover:rotate-12 focus:outline-none"
                >
                    <transition name="fade" mode="out-in">
                        <span v-if="!isDark" key="sun" class="text-xl">🌻</span>
                        <span v-else key="moon" class="text-xl">🌘</span>
                    </transition>
                </button>
            </div>
        </aside>

        <!-- ================= ПРАВАЯ ЧАСТЬ (КОНТЕНТ) ================= -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

            <!-- Верхняя шапка (Header) -->
            <nav class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm flex-shrink-0">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">

                        <!-- Левая часть шапки: Гамбургер (только мобильный) и Заголовок -->
                        <div class="flex items-center">

                            <!-- Hamburger (Mobile) -->
                            <div class="-ml-2 mr-2 flex items-center md:hidden">
                                <button
                                    @click="showingNavigationDropdown = !showingNavigationDropdown"
                                    class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none transition duration-150 ease-in-out"
                                >
                                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                        <path
                                            :class="{ 'hidden': showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }"
                                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"
                                        />
                                        <path
                                            :class="{ 'hidden': !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }"
                                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"
                                        />
                                    </svg>
                                </button>
                            </div>

                            <!-- Лого для мобильных (чтобы было видно, если сайдбар скрыт) -->
                            <div class="flex-shrink-0 flex items-center md:hidden">
                                <Link :href="route('dashboard')">
                                    <ApplicationLogo class="block h-8 w-auto fill-current text-gray-800 dark:text-gray-200" />
                                </Link>
                            </div>

                            <div @click="router.visit('/dashboardold')" style="color: gold;
    background: black;
    padding: 8px;
    border-radius: 9px; cursor: pointer">Старая версия</div>

                            <!-- Сюда можно вывести Заголовок текущей страницы, если нужно -->
                            <div class="hidden md:block ml-4 text-xl font-semibold text-gray-800 dark:text-gray-200">
                                <!-- Можно оставить пустым или вывести $slots.header здесь -->
                            </div>
                        </div>

                        <!-- Правая часть шапки: Профиль -->
                        <div class="flex items-center">

                            <div class="ml-3 relative">
                                <Dropdown align="right" width="48">
                                    <template #trigger>



                                    <span class="inline-flex rounded-md">
                                        <button
                                            type="button"
                                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150"
                                        >
                                            {{ $page.props.auth.user.name }}
                                            <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </span>


                                    </template>

                                    <template #content>
                                        <DropdownLink :href="route('profile.edit')"> Профиль </DropdownLink>
                                        <DropdownLink :href="route('logout')" method="post" as="button"> Выйти </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- МОБИЛЬНОЕ МЕНЮ (Появляется под шапкой при клике на гамбургер) -->
                <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }" class="md:hidden border-t border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800">
                    <div class="pt-2 pb-3 space-y-1">
                        <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">
                            Главная
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('support.history')" :active="route().current('support.history')">
                            Техподдержка
                            <span v-if="unreadSupport > 0" class="ml-2 text-xs bg-blue-500 text-white px-2 py-0.5 rounded-full">New</span>
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('meeting-documents.index')" :active="route().current('meeting-documents.index')">
                            Повестки
                        </ResponsiveNavLink>
                    </div>

                    <!-- Мобильные настройки профиля -->
                    <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
                        <div class="px-4">
                            <div class="font-medium text-base text-gray-800 dark:text-gray-200">{{ $page.props.auth.user.name }}</div>
                            <div class="font-medium text-sm text-gray-500">{{ $page.props.auth.user.email }}</div>
                        </div>
                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')"> Профиль </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('logout')" method="post" as="button"> Выйти </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- ЗАГОЛОВОК СТРАНИЦЫ (Слот header) -->
            <header class="bg-white dark:bg-gray-800 shadow flex-shrink-0" v-if="$slots.header">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- ОСНОВНОЙ КОНТЕНТ (Скроллится только эта область) -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 dark:bg-gray-900 p-6">
                <slot />
            </main>

        </div>

        <!-- Кнопка поддержки и DevTools (плавающие элементы) -->
        <SupportButton />
        <!-- <DevtoolsGuard :enabled="true" /> -->

    </div>
</template>
