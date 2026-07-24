<script setup>
import {
    computed,
    ref,
} from 'vue'

import { router } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({
    companies: {
        type: Array,
        default: () => [],
    },
    userId: {
        type: [Number, String],
        default: null,
    },
    isAdmin: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits(['refresh'])

const showDeleteModal = ref(false)
const showAllMyCompaniesModal = ref(false)
const showAllOtherCompaniesModal = ref(false)

const deletePassword = ref('')
const selectedCompanyId = ref(null)
const deleting = ref(false)

const myCompanies = computed(() => {
    return props.companies.filter(
        company =>
            String(company.user_id) === String(props.userId),
    )
})

const otherCompanies = computed(() => {
    return props.companies.filter(
        company =>
            String(company.user_id) !== String(props.userId),
    )
})

const visibleMyCompanies = computed(() => {
    return myCompanies.value.slice(0, 8)
})

const visibleOtherCompanies = computed(() => {
    return otherCompanies.value.slice(0, 5)
})

const getProjectsCount = company => {
    return (
        company.projects_count ||
        company.projects?.length ||
        0
    )
}

const getInitials = name => {
    if (!name) {
        return 'CO'
    }

    return name
        .split(' ')
        .slice(0, 2)
        .map(part => part[0])
        .join('')
        .toUpperCase()
}

const visitCompany = company => {
    router.visit(`/companies/${company.id}`)
}

const openDelete = company => {
    selectedCompanyId.value = company.id
    deletePassword.value = ''
    showDeleteModal.value = true
}

const closeDelete = () => {
    selectedCompanyId.value = null
    deletePassword.value = ''
    showDeleteModal.value = false
}

const confirmDelete = async () => {
    if (!deletePassword.value) {
        return
    }

    deleting.value = true

    try {
        await axios.delete(
            `/api/companies/${selectedCompanyId.value}`,
            {
                data: {
                    password: deletePassword.value,
                },
            },
        )

        closeDelete()
        emit('refresh')
    } catch (error) {
        alert(
            error.response?.data?.message ||
            'Не удалось удалить компанию.',
        )
    } finally {
        deleting.value = false
    }
}
</script>

<template>
    <section
        class="grid grid-cols-1 gap-4
               xl:grid-cols-[minmax(0,1fr)_360px]"
    >
        <!-- Мои компании -->
        <div
            class="overflow-hidden rounded-2xl border
                   border-slate-200 bg-white shadow-sm
                   dark:border-slate-800 dark:bg-slate-900"
        >
            <header
                class="flex items-center justify-between border-b
                       border-slate-100 px-4 py-3
                       dark:border-slate-800"
            >
                <div class="flex items-center gap-3">
                    <span
                        class="flex h-9 w-9 items-center justify-center
                               rounded-xl bg-indigo-100 text-indigo-600
                               dark:bg-indigo-950/60
                               dark:text-indigo-300"
                    >
                        <svg
                            class="h-4.5 w-4.5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 21h18M5 21V7l7-4 7 4v14M9 9h.01M15 9h.01M9 13h.01M15 13h.01M9 17h.01M15 17h.01"
                            />
                        </svg>
                    </span>

                    <div>
                        <h2
                            class="text-sm font-bold
                                   text-slate-900 dark:text-white"
                        >
                            Мои компании
                        </h2>

                        <p
                            class="text-[11px] text-slate-400"
                        >
                            {{ myCompanies.length }} организаций
                        </p>
                    </div>
                </div>

                <button
                    v-if="myCompanies.length > 8"
                    type="button"
                    class="rounded-lg px-2.5 py-1.5 text-xs
                           font-bold text-indigo-600 transition
                           hover:bg-indigo-50
                           dark:hover:bg-indigo-950/40"
                    @click="showAllMyCompaniesModal = true"
                >
                    Все компании
                </button>
            </header>

            <div
                v-if="visibleMyCompanies.length"
                class="grid grid-cols-1 gap-2 p-3
                       sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4"
            >
                <article
                    v-for="company in visibleMyCompanies"
                    :key="company.id"
                    class="group flex min-w-0 cursor-pointer
                           items-center gap-3 rounded-xl border
                           border-slate-100 bg-slate-50/70 p-3
                           transition hover:border-indigo-300
                           hover:bg-white hover:shadow-sm
                           dark:border-slate-800
                           dark:bg-slate-800/40
                           dark:hover:border-indigo-800
                           dark:hover:bg-slate-800"
                    @click="visitCompany(company)"
                >
                    <img
                        v-if="company.logo"
                        :src="`/storage/${company.logo}`"
                        :alt="company.name"
                        class="h-11 w-11 shrink-0 rounded-xl
                               object-cover"
                    />

                    <div
                        v-else
                        class="flex h-11 w-11 shrink-0
                               items-center justify-center rounded-xl
                               bg-indigo-100 text-xs font-black
                               text-indigo-600
                               dark:bg-indigo-950/60
                               dark:text-indigo-300"
                    >
                        {{ getInitials(company.name) }}
                    </div>

                    <div class="min-w-0 flex-1">
                        <h3
                            class="truncate text-sm font-bold
                                   text-slate-800
                                   group-hover:text-indigo-600
                                   dark:text-white"
                        >
                            {{ company.name }}
                        </h3>

                        <p
                            class="mt-0.5 text-[11px]
                                   text-slate-400"
                        >
                            {{ getProjectsCount(company) }}
                            проектов
                        </p>
                    </div>

                    <button
                        v-if="isAdmin"
                        type="button"
                        class="flex h-7 w-7 shrink-0 items-center
                               justify-center rounded-lg
                               text-slate-300 opacity-0 transition
                               hover:bg-rose-50 hover:text-rose-500
                               group-hover:opacity-100
                               dark:hover:bg-rose-950/40"
                        title="Удалить компанию"
                        @click.stop="openDelete(company)"
                    >
                        <svg
                            class="h-4 w-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18 18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </article>
            </div>

            <div
                v-else
                class="p-8 text-center text-sm text-slate-400"
            >
                У вас пока нет компаний
            </div>
        </div>

        <!-- Другие компании -->
        <div
            class="overflow-hidden rounded-2xl border
                   border-slate-200 bg-white shadow-sm
                   dark:border-slate-800 dark:bg-slate-900"
        >
            <header
                class="flex items-center justify-between border-b
                       border-slate-100 px-4 py-3
                       dark:border-slate-800"
            >
                <div>
                    <h2
                        class="text-sm font-bold
                               text-slate-900 dark:text-white"
                    >
                        Другие компании
                    </h2>

                    <p
                        class="text-[11px] text-slate-400"
                    >
                        Доступные пространства
                    </p>
                </div>

                <button
                    v-if="otherCompanies.length > 5"
                    type="button"
                    class="text-xs font-bold text-emerald-600"
                    @click="showAllOtherCompaniesModal = true"
                >
                    Все {{ otherCompanies.length }}
                </button>
            </header>

            <div
                v-if="visibleOtherCompanies.length"
                class="divide-y divide-slate-100
                       dark:divide-slate-800"
            >
                <button
                    v-for="company in visibleOtherCompanies"
                    :key="company.id"
                    type="button"
                    class="flex w-full items-center gap-3 px-4
                           py-2.5 text-left transition
                           hover:bg-emerald-50/60
                           dark:hover:bg-emerald-950/20"
                    @click="visitCompany(company)"
                >
                    <img
                        v-if="company.logo"
                        :src="`/storage/${company.logo}`"
                        class="h-9 w-9 rounded-lg object-cover"
                    />

                    <div
                        v-else
                        class="flex h-9 w-9 items-center
                               justify-center rounded-lg
                               bg-emerald-100 text-[10px]
                               font-black text-emerald-600
                               dark:bg-emerald-950/60
                               dark:text-emerald-300"
                    >
                        {{ getInitials(company.name) }}
                    </div>

                    <span
                        class="min-w-0 flex-1 truncate text-sm
                               font-semibold text-slate-700
                               dark:text-slate-200"
                    >
                        {{ company.name }}
                    </span>

                    <span
                        class="text-[10px] text-slate-400"
                    >
                        {{ getProjectsCount(company) }}
                    </span>
                </button>
            </div>

            <div
                v-else
                class="p-8 text-center text-sm text-slate-400"
            >
                Других компаний нет
            </div>
        </div>
    </section>

    <!-- Все мои компании -->
    <Teleport to="body">
        <div
            v-if="showAllMyCompaniesModal"
            class="fixed inset-0 z-[90] flex items-center
                   justify-center bg-slate-950/60 p-4
                   backdrop-blur-sm"
            @click.self="showAllMyCompaniesModal = false"
        >
            <div
                class="flex max-h-[88vh] w-full max-w-5xl
                       flex-col overflow-hidden rounded-2xl
                       border border-slate-200 bg-white shadow-2xl
                       dark:border-slate-800 dark:bg-slate-900"
            >
                <header
                    class="flex items-center justify-between
                           border-b border-slate-100 px-5 py-4
                           dark:border-slate-800"
                >
                    <div>
                        <h3
                            class="text-lg font-bold
                                   text-slate-900 dark:text-white"
                        >
                            Все мои компании
                        </h3>

                        <p class="text-xs text-slate-400">
                            Всего {{ myCompanies.length }}
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg p-2 hover:bg-slate-100
                               dark:hover:bg-slate-800"
                        @click="showAllMyCompaniesModal = false"
                    >
                        ✕
                    </button>
                </header>

                <div
                    class="company-scrollbar grid
                           grid-cols-1 gap-2 overflow-y-auto
                           p-4 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <button
                        v-for="company in myCompanies"
                        :key="company.id"
                        type="button"
                        class="flex items-center gap-3 rounded-xl
                               border border-slate-200 p-3 text-left
                               transition hover:border-indigo-400
                               hover:bg-indigo-50/50
                               dark:border-slate-700
                               dark:hover:bg-indigo-950/20"
                        @click="visitCompany(company)"
                    >
                        <img
                            v-if="company.logo"
                            :src="`/storage/${company.logo}`"
                            class="h-12 w-12 rounded-xl object-cover"
                        />

                        <div
                            v-else
                            class="flex h-12 w-12 items-center
                                   justify-center rounded-xl
                                   bg-indigo-100 text-xs font-black
                                   text-indigo-600"
                        >
                            {{ getInitials(company.name) }}
                        </div>

                        <div class="min-w-0">
                            <p
                                class="truncate text-sm font-bold
                                       text-slate-800 dark:text-white"
                            >
                                {{ company.name }}
                            </p>

                            <p class="text-xs text-slate-400">
                                {{ getProjectsCount(company) }}
                                проектов
                            </p>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Все другие компании -->
    <Teleport to="body">
        <div
            v-if="showAllOtherCompaniesModal"
            class="fixed inset-0 z-[90] flex items-center
                   justify-center bg-slate-950/60 p-4
                   backdrop-blur-sm"
            @click.self="showAllOtherCompaniesModal = false"
        >
            <div
                class="flex max-h-[88vh] w-full max-w-3xl
                       flex-col overflow-hidden rounded-2xl
                       bg-white shadow-2xl dark:bg-slate-900"
            >
                <header
                    class="flex items-center justify-between
                           border-b border-slate-100 px-5 py-4
                           dark:border-slate-800"
                >
                    <div>
                        <h3
                            class="text-lg font-bold
                                   text-slate-900 dark:text-white"
                        >
                            Другие компании
                        </h3>

                        <p class="text-xs text-slate-400">
                            Всего {{ otherCompanies.length }}
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg p-2 hover:bg-slate-100
                               dark:hover:bg-slate-800"
                        @click="showAllOtherCompaniesModal = false"
                    >
                        ✕
                    </button>
                </header>

                <div
                    class="company-scrollbar grid grid-cols-1
                           gap-2 overflow-y-auto p-4 sm:grid-cols-2"
                >
                    <button
                        v-for="company in otherCompanies"
                        :key="company.id"
                        type="button"
                        class="flex items-center gap-3 rounded-xl
                               border border-slate-200 p-3 text-left
                               transition hover:border-emerald-400
                               dark:border-slate-700"
                        @click="visitCompany(company)"
                    >
                        <img
                            v-if="company.logo"
                            :src="`/storage/${company.logo}`"
                            class="h-10 w-10 rounded-lg object-cover"
                        />

                        <div
                            v-else
                            class="flex h-10 w-10 items-center
                                   justify-center rounded-lg
                                   bg-emerald-100 text-[10px]
                                   font-black text-emerald-600"
                        >
                            {{ getInitials(company.name) }}
                        </div>

                        <p
                            class="min-w-0 truncate text-sm font-bold
                                   text-slate-800 dark:text-white"
                        >
                            {{ company.name }}
                        </p>
                    </button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Удаление -->
    <Teleport to="body">
        <div
            v-if="showDeleteModal"
            class="fixed inset-0 z-[100] flex items-center
                   justify-center bg-slate-950/60 p-4
                   backdrop-blur-sm"
            @click.self="closeDelete"
        >
            <div
                class="w-full max-w-sm rounded-2xl
                       bg-white p-5 shadow-2xl
                       dark:bg-slate-900"
            >
                <h4
                    class="text-lg font-bold
                           text-slate-900 dark:text-white"
                >
                    Удалить компанию?
                </h4>

                <p
                    class="mt-1 text-sm leading-6
                           text-slate-500"
                >
                    Для подтверждения введите пароль
                    от вашего аккаунта.
                </p>

                <input
                    v-model="deletePassword"
                    type="password"
                    class="mt-4 w-full rounded-xl border
                           border-slate-200 bg-white px-4 py-3
                           text-sm outline-none focus:border-rose-400
                           focus:ring-4 focus:ring-rose-100
                           dark:border-slate-700
                           dark:bg-slate-800 dark:text-white"
                    placeholder="Пароль"
                    @keyup.enter="confirmDelete"
                />

                <div class="mt-4 flex gap-2">
                    <button
                        type="button"
                        class="flex-1 rounded-xl px-4 py-2.5
                               text-sm font-bold text-slate-500
                               hover:bg-slate-100
                               dark:hover:bg-slate-800"
                        @click="closeDelete"
                    >
                        Отмена
                    </button>

                    <button
                        type="button"
                        :disabled="deleting || !deletePassword"
                        class="flex-1 rounded-xl bg-rose-600
                               px-4 py-2.5 text-sm font-bold
                               text-white disabled:opacity-50"
                        @click="confirmDelete"
                    >
                        {{ deleting ? 'Удаление...' : 'Удалить' }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.company-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: rgb(203 213 225) transparent;
}

.company-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.company-scrollbar::-webkit-scrollbar-thumb {
    border-radius: 999px;
    background: rgb(203 213 225);
}
</style>