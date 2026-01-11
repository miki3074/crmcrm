<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps(['companies', 'userId', 'isAdmin'])
const emit = defineEmits(['refresh'])

// Состояния для модальных окон
const showDeleteModal = ref(false)
const showAllMyCompaniesModal = ref(false)
const showAllOtherCompaniesModal = ref(false)

const deletePassword = ref('')
const selectedCompanyId = ref(null)

// Фильтрация
const myCompanies = computed(() => props.companies.filter(c => String(c.user_id) === String(props.userId)))
const otherCompanies = computed(() => props.companies.filter(c => String(c.user_id) !== String(props.userId)))

// Логика удаления
const openDelete = (id) => {
    selectedCompanyId.value = id
    showDeleteModal.value = true
}

const confirmDelete = async () => {
    try {
        await axios.delete(`/api/companies/${selectedCompanyId.value}`, { data: { password: deletePassword.value } })
        showDeleteModal.value = false
        deletePassword.value = ''
        emit('refresh')
    } catch (e) {
        alert(e.response?.data?.message || 'Ошибка пароля')
    }
}
</script>

<template>
    <div class="grid grid-cols-1 lg:grid-cols-[7fr_3fr] gap-8 items-start">

        <!-- БЛОК: Мои компании -->
        <section class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200 dark:border-slate-800 shadow-sm h-full flex flex-col">
            <div class="flex justify-between items-center mb-6">
                <h3 class="flex items-center gap-2 text-lg font-bold text-slate-700 dark:text-slate-200">
                    <span class="p-2 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">💼</span> Мои компании
                </h3>
                <!-- Кнопка "Все" (появляется если > 8) -->
                <button
                    v-if="myCompanies.length > 8"
                    @click="showAllMyCompaniesModal = true"
                    class="text-sm font-bold text-indigo-600 hover:text-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 px-3 py-1.5 rounded-lg transition"
                >
                    Все ({{ myCompanies.length }})
                </button>
            </div>

            <!-- Сетка карточек (Ограничена по высоте ~ 2 ряда по 200px + отступы) -->
            <!-- h-[450px] обеспечивает одинаковую высоту с соседним блоком и прокрутку -->
            <div class="overflow-y-auto h-[450px] pr-2 custom-scrollbar">
                <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-4">
                    <div v-for="company in myCompanies" :key="company.id"
                         @click="router.visit(`/companies/${company.id}`)"
                         class="group relative h-[200px] flex flex-col items-center justify-center p-4 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-2xl hover:border-indigo-500 dark:hover:border-indigo-400 transition-all cursor-pointer shadow-sm overflow-hidden text-center">

                        <button @click.stop="openDelete(company.id)" class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 p-1 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-md transition z-10">✕</button>

                        <div class="mb-3">
                            <img v-if="company.logo" :src="`/storage/${company.logo}`" class="w-16 h-16 rounded-xl object-cover border dark:border-slate-600" />
                            <div v-else class="w-16 h-16 rounded-xl bg-white dark:bg-slate-700 grid place-items-center text-3xl shadow-sm">🏢</div>
                        </div>

                        <div class="w-full">
                            <p class="font-bold text-slate-800 dark:text-white truncate text-sm mb-1">{{ company.name }}</p>
                            <p class="text-xs text-slate-500">{{ company.projects?.length || 0 }} проектов</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- БЛОК: Другие компании -->
        <section class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200 dark:border-slate-800 shadow-sm h-full flex flex-col">
            <div class="flex justify-between items-center mb-6">
                <h3 class="flex items-center gap-2 text-lg font-bold text-slate-700 dark:text-slate-200">
                    <span class="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">🤝</span> Другие компании
                </h3>
                <!-- Кнопка "Все" (появляется если > 3) -->
                <button
                    v-if="otherCompanies.length > 3"
                    @click="showAllOtherCompaniesModal = true"
                    class="text-sm font-bold text-emerald-600 hover:text-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 px-3 py-1.5 rounded-lg transition"
                >
                    Все ({{ otherCompanies.length }})
                </button>
            </div>

            <!-- Вертикальный список -->
            <!-- h-[450px] для синхронизации высоты с левым блоком -->
            <div class="overflow-y-auto h-[450px] pr-2 custom-scrollbar flex flex-col gap-4">
                <div v-for="company in otherCompanies" :key="company.id"
                     @click="router.visit(`/companies/${company.id}`)"
                     class="flex-shrink-0 p-5 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-2xl hover:shadow-md hover:border-emerald-400 dark:hover:border-emerald-500 transition cursor-pointer flex items-center gap-4">
                    <img v-if="company.logo" :src="`/storage/${company.logo}`" class="w-14 h-14 rounded-xl object-cover bg-white" />
                    <div v-else class="w-14 h-14 rounded-xl bg-white dark:bg-slate-700 grid place-items-center text-xl shadow-sm">🏢</div>

                    <div>
                        <p class="font-bold text-lg text-slate-800 dark:text-slate-200 truncate">{{ company.name }}</p>

                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- МОДАЛКА: Все мои компании -->
    <div v-if="showAllMyCompaniesModal" class="fixed inset-0 z-[90] grid place-items-center bg-slate-950/60 backdrop-blur-sm p-4" @click.self="showAllMyCompaniesModal = false">
        <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl w-full max-w-5xl max-h-[90vh] flex flex-col border dark:border-slate-800 shadow-2xl">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-slate-800 dark:text-white">Все мои компании</h3>
                <button @click="showAllMyCompaniesModal = false" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full transition">✕</button>
            </div>
            <div class="overflow-y-auto p-2">
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
                    <div v-for="company in myCompanies" :key="company.id"
                         @click="router.visit(`/companies/${company.id}`)"
                         class="group relative h-[200px] flex flex-col items-center justify-center p-4 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl hover:border-indigo-500 cursor-pointer">
                        <div class="mb-3">
                            <img v-if="company.logo" :src="`/storage/${company.logo}`" class="w-16 h-16 rounded-xl object-cover" />
                            <div v-else class="w-16 h-16 rounded-xl bg-white dark:bg-slate-700 grid place-items-center text-3xl">🏢</div>
                        </div>
                        <p class="font-bold text-center text-slate-800 dark:text-white truncate w-full">{{ company.name }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- МОДАЛКА: Все другие компании -->
    <div v-if="showAllOtherCompaniesModal" class="fixed inset-0 z-[90] grid place-items-center bg-slate-950/60 backdrop-blur-sm p-4" @click.self="showAllOtherCompaniesModal = false">
        <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl w-full max-w-4xl max-h-[90vh] flex flex-col border dark:border-slate-800 shadow-2xl">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-slate-800 dark:text-white">Все другие компании</h3>
                <button @click="showAllOtherCompaniesModal = false" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full transition">✕</button>
            </div>
            <div class="overflow-y-auto p-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-for="company in otherCompanies" :key="company.id"
                     @click="router.visit(`/companies/${company.id}`)"
                     class="flex items-center gap-4 p-4 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl hover:border-emerald-500 cursor-pointer">
                    <img v-if="company.logo" :src="`/storage/${company.logo}`" class="w-12 h-12 rounded-xl object-cover" />
                    <div v-else class="w-12 h-12 rounded-xl bg-white dark:bg-slate-700 grid place-items-center text-xl">🏢</div>
                    <p class="font-bold text-slate-800 dark:text-white">{{ company.name }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Модалка удаления (Без изменений) -->
    <div v-if="showDeleteModal" class="fixed inset-0 z-[100] grid place-items-center bg-slate-950/60 backdrop-blur-sm p-4">
        <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl w-full max-w-sm border dark:border-slate-800 shadow-2xl animate-in fade-in zoom-in duration-200">
            <h4 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Подтверждение</h4>
            <p class="text-sm text-slate-500 mb-4">Для удаления введите ваш пароль от аккаунта.</p>
            <input v-model="deletePassword" type="password" class="w-full px-4 py-3 rounded-xl border dark:bg-slate-800 dark:border-slate-700 mb-4 outline-none focus:ring-2 focus:ring-rose-500" placeholder="Ваш пароль" />
            <div class="flex gap-2">
                <button @click="showDeleteModal = false" class="flex-1 py-3 text-sm font-bold text-slate-500">Отмена</button>
                <button @click="confirmDelete" class="flex-1 py-3 text-sm font-bold bg-rose-600 text-white rounded-xl shadow-lg shadow-rose-600/20">Удалить</button>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Стили для красивого скролла (опционально) */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 20px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #334155;
}
</style>
