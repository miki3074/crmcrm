<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps(['companies', 'userId', 'isAdmin'])
const emit = defineEmits(['refresh'])

// Состояния
const showDeleteModal = ref(false)
const showAllMyCompaniesModal = ref(false)
const showAllOtherCompaniesModal = ref(false)
const deletePassword = ref('')
const selectedCompanyId = ref(null)
const deleteError = ref('')
const isLoading = ref(false)

// Фильтрация
const myCompanies = computed(() => props.companies.filter(c => String(c.user_id) === String(props.userId)))
const otherCompanies = computed(() => props.companies.filter(c => String(c.user_id) !== String(props.userId)))

// Количество отображаемых элементов
const DISPLAY_LIMIT = {
    MY_COMPANIES: 8,
    OTHER_COMPANIES: 3
}

// Логика удаления
const openDelete = (id, event) => {
    event.stopPropagation()
    selectedCompanyId.value = id
    showDeleteModal.value = true
    deleteError.value = ''
}

const confirmDelete = async () => {
    if (!deletePassword.value) {
        deleteError.value = 'Введите пароль'
        return
    }

    isLoading.value = true
    try {
        await axios.delete(`/api/companies/${selectedCompanyId.value}`, {
            data: { password: deletePassword.value }
        })
        showDeleteModal.value = false
        deletePassword.value = ''
        deleteError.value = ''
        emit('refresh')
    } catch (e) {
        deleteError.value = e.response?.data?.message || 'Неверный пароль'
    } finally {
        isLoading.value = false
    }
}

// Форматирование даты
const formatDate = (date) => {
    return new Date(date).toLocaleDateString('ru-RU', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    })
}
</script>

<template>
    <div class="space-y-8">

        <!-- БЛОК: Мои компании -->
        <section class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <!-- Заголовок -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl shadow-lg shadow-indigo-500/20">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Мои компании</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Управление вашими компаниями</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
          <span class="px-3 py-1 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 text-sm font-medium rounded-full">
            {{ myCompanies.length }} {{ myCompanies.length === 1 ? 'компания' : 'компаний' }}
          </span>

                    <button
                        v-if="myCompanies.length > DISPLAY_LIMIT.MY_COMPANIES"
                        @click="showAllMyCompaniesModal = true"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-indigo-600 hover:text-indigo-700 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-900/30 dark:text-indigo-400 dark:hover:bg-indigo-900/50 rounded-xl transition-colors"
                    >
                        <span>Все компании</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Сетка карточек -->
            <div class="p-6">
                <div v-if="myCompanies.length === 0" class="text-center py-12">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-2xl mb-4">
                        <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Нет компаний</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Создайте свою первую компанию</p>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                    <div
                        v-for="company in myCompanies.slice(0, DISPLAY_LIMIT.MY_COMPANIES)"
                        :key="company.id"
                        @click="router.visit(`/companies/${company.id}`)"
                        class="group relative bg-gradient-to-br from-slate-50 to-white dark:from-slate-800 dark:to-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl hover:border-indigo-300 dark:hover:border-indigo-600 hover:shadow-lg hover:shadow-indigo-500/5 transition-all cursor-pointer overflow-hidden"
                    >
                        <!-- Кнопка удаления -->
                        <button
                            v-if="isAdmin"
                            @click="openDelete(company.id, $event)"
                            class="absolute top-2 right-2 z-10 p-1.5 bg-white dark:bg-slate-800 rounded-lg opacity-0 group-hover:opacity-100 hover:bg-rose-50 dark:hover:bg-rose-900/30 text-rose-500 transition-all"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>

                        <!-- Контент -->
                        <div class="p-5 flex flex-col items-center text-center">
                            <div class="relative mb-4">
                                <div class="w-20 h-20 rounded-xl bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-700 dark:to-slate-800 flex items-center justify-center overflow-hidden ring-4 ring-white dark:ring-slate-800 shadow-lg">
                                    <img
                                        v-if="company.logo"
                                        :src="`/storage/${company.logo}`"
                                        class="w-full h-full object-cover"
                                        :alt="company.name"
                                    />
                                    <span v-else class="text-3xl">🏢</span>
                                </div>
                                <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-emerald-500 border-2 border-white dark:border-slate-800 rounded-full"></div>
                            </div>

                            <h3 class="font-semibold text-slate-900 dark:text-white mb-1 truncate max-w-full">
                                {{ company.name }}
                            </h3>

                            <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                <span class="flex items-center gap-1">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  {{ company.projects?.length || 0 }} проектов
                </span>
                                <span>•</span>
                                <span>{{ formatDate(company.created_at) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- БЛОК: Другие компании -->
        <section class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <!-- Заголовок -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl shadow-lg shadow-emerald-500/20">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Другие компании</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Компании других пользователей</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
          <span class="px-3 py-1 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 text-sm font-medium rounded-full">
            {{ otherCompanies.length }} {{ otherCompanies.length === 1 ? 'компания' : 'компаний' }}
          </span>

                    <button
                        v-if="otherCompanies.length > DISPLAY_LIMIT.OTHER_COMPANIES"
                        @click="showAllOtherCompaniesModal = true"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-emerald-600 hover:text-emerald-700 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-400 dark:hover:bg-emerald-900/50 rounded-xl transition-colors"
                    >
                        <span>Все компании</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Список компаний -->
            <div class="p-6">
                <div v-if="otherCompanies.length === 0" class="text-center py-12">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-2xl mb-4">
                        <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Нет других компаний</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Пока никто не создал компании</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="company in otherCompanies.slice(0, DISPLAY_LIMIT.OTHER_COMPANIES)"
                        :key="company.id"
                        @click="router.visit(`/companies/${company.id}`)"
                        class="group flex items-center gap-4 p-4 bg-gradient-to-br from-slate-50 to-white dark:from-slate-800 dark:to-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl hover:border-emerald-300 dark:hover:border-emerald-600 hover:shadow-lg hover:shadow-emerald-500/5 transition-all cursor-pointer"
                    >
                        <div class="relative flex-shrink-0">
                            <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-700 dark:to-slate-800 flex items-center justify-center overflow-hidden ring-2 ring-white dark:ring-slate-800">
                                <img
                                    v-if="company.logo"
                                    :src="`/storage/${company.logo}`"
                                    class="w-full h-full object-cover"
                                    :alt="company.name"
                                />
                                <span v-else class="text-2xl">🏢</span>
                            </div>
                        </div>

                        <div class="flex-1 min-w-0">
                            <h3 class="font-semibold text-slate-900 dark:text-white mb-1 truncate">
                                {{ company.name }}
                            </h3>
                            <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">


                                <span>{{ company.projects?.length || 0 }} проектов</span>
                            </div>
                        </div>

                        <svg class="w-5 h-5 text-slate-400 group-hover:text-emerald-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>
                </div>
            </div>
        </section>

        <!-- МОДАЛКА: Все мои компании -->
        <Teleport to="body">
            <div
                v-if="showAllMyCompaniesModal"
                class="fixed inset-0 z-50 overflow-y-auto"
                @click.self="showAllMyCompaniesModal = false"
            >
                <div class="flex items-center justify-center min-h-screen p-4">
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity"></div>

                    <div class="relative bg-white dark:bg-slate-900 rounded-2xl max-w-6xl w-full max-h-[90vh] overflow-hidden shadow-2xl">
                        <!-- Заголовок -->
                        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-800">
                            <h3 class="text-xl font-semibold text-slate-900 dark:text-white">Все мои компании</h3>
                            <button
                                @click="showAllMyCompaniesModal = false"
                                class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors"
                            >
                                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Содержимое -->
                        <div class="p-6 overflow-y-auto max-h-[calc(90vh-80px)]">
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                                <div
                                    v-for="company in myCompanies"
                                    :key="company.id"
                                    @click="router.visit(`/companies/${company.id}`)"
                                    class="group p-4 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl hover:border-indigo-300 dark:hover:border-indigo-600 hover:shadow-lg cursor-pointer transition-all"
                                >
                                    <div class="flex flex-col items-center text-center">
                                        <div class="w-16 h-16 rounded-xl bg-white dark:bg-slate-700 flex items-center justify-center mb-3 shadow-sm">
                                            <img
                                                v-if="company.logo"
                                                :src="`/storage/${company.logo}`"
                                                class="w-full h-full object-cover rounded-xl"
                                                :alt="company.name"
                                            />
                                            <span v-else class="text-2xl">🏢</span>
                                        </div>
                                        <h4 class="font-medium text-slate-900 dark:text-white text-sm mb-1 truncate max-w-full">
                                            {{ company.name }}
                                        </h4>
                                        <p class="text-xs text-slate-500">{{ company.projects?.length || 0 }} проектов</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- МОДАЛКА: Все другие компании -->
            <div
                v-if="showAllOtherCompaniesModal"
                class="fixed inset-0 z-50 overflow-y-auto"
                @click.self="showAllOtherCompaniesModal = false"
            >
                <div class="flex items-center justify-center min-h-screen p-4">
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity"></div>

                    <div class="relative bg-white dark:bg-slate-900 rounded-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden shadow-2xl">
                        <!-- Заголовок -->
                        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-800">
                            <h3 class="text-xl font-semibold text-slate-900 dark:text-white">Все другие компании</h3>
                            <button
                                @click="showAllOtherCompaniesModal = false"
                                class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors"
                            >
                                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Содержимое -->
                        <div class="p-6 overflow-y-auto max-h-[calc(90vh-80px)]">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div
                                    v-for="company in otherCompanies"
                                    :key="company.id"
                                    @click="router.visit(`/companies/${company.id}`)"
                                    class="flex items-center gap-4 p-4 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl hover:border-emerald-300 dark:hover:border-emerald-600 hover:shadow-lg cursor-pointer transition-all"
                                >
                                    <div class="w-12 h-12 rounded-xl bg-white dark:bg-slate-700 flex items-center justify-center shadow-sm flex-shrink-0">
                                        <img
                                            v-if="company.logo"
                                            :src="`/storage/${company.logo}`"
                                            class="w-full h-full object-cover rounded-xl"
                                            :alt="company.name"
                                        />
                                        <span v-else class="text-xl">🏢</span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-medium text-slate-900 dark:text-white mb-1 truncate">
                                            {{ company.name }}
                                        </h4>
                                        <p class="text-xs text-slate-500">{{ company.projects?.length || 0 }} проектов</p>
                                    </div>
                                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- МОДАЛКА: Удаление -->
            <div
                v-if="showDeleteModal"
                class="fixed inset-0 z-50 overflow-y-auto"
                @click.self="showDeleteModal = false"
            >
                <div class="flex items-center justify-center min-h-screen p-4">
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity"></div>

                    <div class="relative bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full shadow-2xl">
                        <div class="p-6">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="p-3 bg-rose-100 dark:bg-rose-900/30 rounded-xl">
                                    <svg class="w-6 h-6 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Подтверждение удаления</h3>
                                    <p class="text-sm text-slate-500 dark:text-slate-400">Это действие нельзя отменить</p>
                                </div>
                            </div>

                            <p class="text-sm text-slate-600 dark:text-slate-300 mb-4">
                                Для подтверждения удаления компании введите ваш пароль.
                            </p>

                            <div class="space-y-4">
                                <div>
                                    <input
                                        v-model="deletePassword"
                                        type="password"
                                        class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-rose-500 focus:border-transparent outline-none transition"
                                        placeholder="Введите пароль"
                                        @keyup.enter="confirmDelete"
                                    />
                                    <p v-if="deleteError" class="mt-2 text-sm text-rose-600 dark:text-rose-400">
                                        {{ deleteError }}
                                    </p>
                                </div>

                                <div class="flex gap-3">
                                    <button
                                        @click="showDeleteModal = false"
                                        class="flex-1 px-4 py-3 text-sm font-medium text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-xl transition-colors"
                                    >
                                        Отмена
                                    </button>
                                    <button
                                        @click="confirmDelete"
                                        :disabled="isLoading"
                                        class="flex-1 px-4 py-3 text-sm font-medium text-white bg-gradient-to-r from-rose-500 to-rose-600 hover:from-rose-600 hover:to-rose-700 rounded-xl shadow-lg shadow-rose-500/25 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                                    >
                    <span v-if="isLoading" class="flex items-center justify-center gap-2">
                      <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                      Удаление...
                    </span>
                                        <span v-else>Удалить компанию</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
/* Кастомный скроллбар */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 20px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #475569;
}

.dark .custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #64748b;
}

/* Анимации */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.slide-enter-active,
.slide-leave-active {
    transition: transform 0.3s ease, opacity 0.3s ease;
}

.slide-enter-from,
.slide-leave-to {
    transform: translateY(20px);
    opacity: 0;
}
</style>
