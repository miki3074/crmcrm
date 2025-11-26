<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

import ApplicationLogo from '@/Components/ApplicationLogo.vue'
import Dropdown from '@/Components/Dropdown.vue'
import DropdownLink from '@/Components/DropdownLink.vue'
import NavLink from '@/Components/NavLink.vue'
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue'
import { Link } from '@inertiajs/vue3'

import SupportButton from '@/Components/SupportButton.vue'
import DevtoolsGuard from '@/Components/DevtoolsGuard.vue'

const showingNavigationDropdown = ref(false)

const isDark = ref(false)

/* === 🔵 Новый код для уведомлений тех.поддержки === */
const unreadSupport = ref(0)

const loadUnread = async () => {
  try {
    const { data } = await axios.get('/api/support/history')
    unreadSupport.value = data.data.filter(m => m.has_unread).length
  } catch (err) {
    console.error('Не удалось загрузить непрочитанные сообщения:', err)
  }
}
/* === конец нового кода === */

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

  // 🔵 загрузить непрочитанные сообщения
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
    <div>
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            <nav class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
                <!-- Primary Navigation Menu -->
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="shrink-0 flex items-center">
                                <Link :href="route('dashboard')">
                                    <ApplicationLogo
                                        class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200"
                                    />
                                </Link>
                            </div>

                            <!-- Navigation Links -->
                            <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                                    Главная
                                </NavLink>
                            </div>

                             <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                <NavLink
  :href="route('support.history')"
  :active="route().current('support.history')"
  class="relative"
>
  Техподдержка

  <!-- 🔵 индикатор -->
  <span
    v-if="unreadSupport > 0"
    class=" ml-3 w-2.5 h-2.5 bg-blue-500 rounded-full animate-pulse"
  ></span>
</NavLink>
                            </div>

                             <!-- <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                <NavLink :href="route('ruk')" :active="route().current('ruk')">
                                    Руководство
                                </NavLink>
                            </div> -->



                            <div class="shrink-0 flex items-center" style="margin-left: 19%;">

                                <button
  @click="toggleTheme"
  class="relative inline-flex items-center justify-center w-10 h-10 rounded-full
         bg-gradient-to-r from-yellow-300 via-orange-400 to-pink-500
         dark:from-gray-600 dark:via-gray-700 dark:to-gray-800
         text-white shadow-lg transition-all duration-500 ease-in-out
         hover:scale-110 hover:rotate-12 focus:outline-none " 
>
  <transition name="fade" mode="out-in">
    <span v-if="!isDark" key="sun" class="text-xl">🌻</span>
    <span v-else key="moon" class="text-xl">🌘</span>
  </transition>
</button>
                            </div>
                           

      

                            
                        </div>

                        <div class="hidden sm:flex sm:items-center sm:ms-6">
                            <!-- Settings Dropdown -->
                            <div class="ms-3 relative">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150"
                                            >
                                                {{ $page.props.auth.user.name }}

                                                <svg
                                                    class="ms-2 -me-0.5 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <DropdownLink :href="route('profile.edit')"> Профиль </DropdownLink>
                                        <DropdownLink :href="route('logout')" method="post" as="button">
                                            Выйти
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                
   
    <SupportButton />


                        <!-- Hamburger -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button
                                @click="showingNavigationDropdown = !showingNavigationDropdown"
                                class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out"
                            >
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path
                                        :class="{
                                            hidden: showingNavigationDropdown,
                                            'inline-flex': !showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{
                                            hidden: !showingNavigationDropdown,
                                            'inline-flex': showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div
                    :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }"
                    class="sm:hidden"
                >
                    <div class="pt-2 pb-3 space-y-1">
                        <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">
                            Главаня1
                        </ResponsiveNavLink>

                        <ResponsiveNavLink :href="route('support.history')" :active="route().current('support.history')">
                                    Техподдержка
                                </ResponsiveNavLink>

                         
                                
                            

                     

                    </div>

                     <!-- <div class="pt-2 pb-3 space-y-1">
                        <ResponsiveNavLink :href="route('ruk')" :active="route().current('ruk')">
                            Руководство
                        </ResponsiveNavLink>

                    </div> -->


                    <!-- Responsive Settings Options -->
                    <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
                        <div class="px-4">
                            <div class="font-medium text-base text-gray-800 dark:text-gray-200">
                                {{ $page.props.auth.user.name }}
                            </div>
                            <div class="font-medium text-sm text-gray-500">{{ $page.props.auth.user.email }}</div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')"> Профиль </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('logout')" method="post" as="button">
                              Выйти
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header class="bg-white dark:bg-gray-800 shadow" v-if="$slots.header">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot />
            </main>
        </div>


<!-- <DevtoolsGuard :enabled="true" /> -->

    </div>


    
</template>
