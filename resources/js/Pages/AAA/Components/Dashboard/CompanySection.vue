<script setup>
import {
    computed,
    ref,
} from 'vue'

import { router } from '@inertiajs/vue3'
import axios from 'axios'
import ProjectsSummary from './ProjectsSummary.vue'

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
    projects: {
        type: Array,
        default: () => [],
    },
})

const emit = defineEmits(['refresh'])

const showDeleteModal = ref(false)
const showAllMyCompaniesModal = ref(false)
const showAllOtherCompaniesModal = ref(false)

const deletePassword = ref('')
const selectedCompanyId = ref(null)
const deleting = ref(false)
const deleteError = ref('')

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
    deleteError.value = ''
    showDeleteModal.value = true
}

const closeDelete = () => {
    selectedCompanyId.value = null
    deletePassword.value = ''
    deleteError.value = ''
    showDeleteModal.value = false
}

const confirmDelete = async () => {
    if (!deletePassword.value) {
        return
    }

    deleting.value = true
    deleteError.value = ''

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
        deleteError.value =
            error.response?.data?.message ||
            'Не удалось удалить компанию.'
    } finally {
        deleting.value = false
    }
}
</script>

<template>
    <section
        class="grid grid-cols-1 gap-4
               xl:grid-cols-[minmax(0,1fr)_280px_320px]"
    >
        <!-- Мои компании -->
        <div
            class="overflow-hidden rounded-lg border
                   border-zinc-200 bg-white shadow-sm
                   dark:border-white/[0.08] dark:bg-zinc-900 dark:shadow-none"
        >
            <header
                class="flex items-center justify-between gap-3 border-b
                       border-zinc-200 px-4 py-3
                       dark:border-white/[0.06]"
            >
                <div class="flex items-center gap-3">
                    <span
                        class="flex h-8 w-8 items-center justify-center
                               rounded-md border border-zinc-200 text-zinc-500
                               dark:border-white/10
                               dark:text-zinc-400"
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
                                stroke-width="1.75"
                                d="M3 21h18M5 21V7l7-4 7 4v14M9 9h.01M15 9h.01M9 13h.01M15 13h.01M9 17h.01M15 17h.01"
                            />
                        </svg>
                    </span>

                    <div>
                        <h2
                            class="text-sm font-semibold
                                   text-zinc-900 dark:text-zinc-100"
                        >
                            Мои компании
                        </h2>

                        <p
                            class="text-xs tabular-nums text-zinc-500 dark:text-zinc-400"
                        >
                            {{ myCompanies.length }} организаций
                        </p>
                    </div>
                </div>

                <button
                    v-if="myCompanies.length > 8"
                    type="button"
                    class="rounded-md px-2 py-1 text-xs
                           font-medium text-blue-700 transition-colors
                           hover:bg-blue-50
                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60
                           dark:text-blue-400 dark:hover:bg-blue-500/10"
                    @click="showAllMyCompaniesModal = true"
                >
                    Все компании
                </button>
            </header>

            <div
                v-if="visibleMyCompanies.length"
                class="grid grid-cols-1 gap-2 p-3
                       sm:grid-cols-2"
            >
                <article
                    v-for="company in visibleMyCompanies"
                    :key="company.id"
                    class="group flex min-w-0 cursor-pointer
                           items-center gap-3 rounded-md border
                           border-zinc-200 bg-white p-2.5
                           transition-colors hover:border-zinc-300
                           hover:bg-zinc-50
                           dark:border-white/[0.06]
                           dark:bg-transparent
                           dark:hover:border-white/15
                           dark:hover:bg-white/[0.03]"
                    @click="visitCompany(company)"
                >
                    <img
                        v-if="company.logo"
                        :src="`/storage/${company.logo}`"
                        :alt="company.name"
                        class="h-10 w-10 shrink-0 rounded-md
                               object-cover ring-1 ring-zinc-200 dark:ring-white/10"
                    />

                    <div
                        v-else
                        class="flex h-10 w-10 shrink-0
                               items-center justify-center rounded-md
                               bg-zinc-100 text-xs font-semibold
                               text-zinc-600
                               dark:bg-white/[0.06]
                               dark:text-zinc-300"
                    >
                        {{ getInitials(company.name) }}
                    </div>

                    <div class="min-w-0 flex-1">
                        <h3
                            class="truncate text-sm font-medium
                                   text-zinc-900
                                   group-hover:text-blue-700
                                   dark:text-zinc-100
                                   dark:group-hover:text-blue-400"
                        >
                            {{ company.name }}
                        </h3>

                        <p
                            class="mt-0.5 text-xs tabular-nums
                                   text-zinc-500 dark:text-zinc-400"
                        >
                            {{ getProjectsCount(company) }}
                            проектов
                        </p>
                    </div>

                    <button
                        v-if="isAdmin"
                        type="button"
                        class="flex h-7 w-7 shrink-0 items-center
                               justify-center rounded-md
                               text-zinc-400 opacity-0 transition
                               hover:bg-rose-50 hover:text-rose-600
                               group-hover:opacity-100
                               focus-visible:opacity-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-500/60
                               dark:hover:bg-rose-500/10 dark:hover:text-rose-400"
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
                                stroke-width="1.75"
                                d="M6 18 18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </article>
            </div>

            <div
                v-else
                class="p-8 text-center text-sm text-zinc-500 dark:text-zinc-400"
            >
                У вас пока нет компаний
            </div>
        </div>

        <!-- Другие компании -->
        <div
            class="overflow-hidden rounded-lg border
                   border-zinc-200 bg-white shadow-sm
                   dark:border-white/[0.08] dark:bg-zinc-900 dark:shadow-none"
        >
            <header
                class="flex items-center justify-between gap-3 border-b
                       border-zinc-200 px-4 py-3
                       dark:border-white/[0.06]"
            >
                <div>
                    <h2
                        class="text-sm font-semibold
                               text-zinc-900 dark:text-zinc-100"
                    >
                        Другие компании
                    </h2>

                    <p
                        class="text-xs text-zinc-500 dark:text-zinc-400"
                    >
                        Доступные пространства
                    </p>
                </div>

                <button
                    v-if="otherCompanies.length > 5"
                    type="button"
                    class="rounded-md px-2 py-1 text-xs font-medium tabular-nums
                           text-blue-700 transition-colors hover:bg-blue-50
                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60
                           dark:text-blue-400 dark:hover:bg-blue-500/10"
                    @click="showAllOtherCompaniesModal = true"
                >
                    Все {{ otherCompanies.length }}
                </button>
            </header>

            <div
                v-if="visibleOtherCompanies.length"
                class="divide-y divide-zinc-100
                       dark:divide-white/[0.06]"
            >
                <button
                    v-for="company in visibleOtherCompanies"
                    :key="company.id"
                    type="button"
                    class="group flex w-full items-center gap-3 px-4
                           py-2.5 text-left transition-colors
                           hover:bg-zinc-50
                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-blue-500/60
                           dark:hover:bg-white/[0.03]"
                    @click="visitCompany(company)"
                >
                    <img
                        v-if="company.logo"
                        :src="`/storage/${company.logo}`"
                        class="h-8 w-8 rounded-md object-cover ring-1 ring-zinc-200 dark:ring-white/10"
                    />

                    <div
                        v-else
                        class="flex h-8 w-8 items-center
                               justify-center rounded-md
                               bg-zinc-100 text-[11px]
                               font-semibold text-zinc-600
                               dark:bg-white/[0.06]
                               dark:text-zinc-300"
                    >
                        {{ getInitials(company.name) }}
                    </div>

                    <span
                        class="min-w-0 flex-1 truncate text-sm
                               font-medium text-zinc-800
                               group-hover:text-blue-700
                               dark:text-zinc-200
                               dark:group-hover:text-blue-400"
                    >
                        {{ company.name }}
                    </span>

                    <span
                        class="text-xs tabular-nums text-zinc-500 dark:text-zinc-400"
                    >
                        {{ getProjectsCount(company) }}
                    </span>
                </button>
            </div>

            <div
                v-else
                class="p-8 text-center text-sm text-zinc-500 dark:text-zinc-400"
            >
                Других компаний нет
            </div>
        </div>

        <!-- Мои проекты -->
        <ProjectsSummary :projects="projects" />
    </section>

    <!-- Все мои компании -->
    <Teleport to="body">
        <div
            v-if="showAllMyCompaniesModal"
            class="fixed inset-0 z-[90] flex items-center
                   justify-center bg-zinc-950/50 p-4
                   backdrop-blur-[2px]"
            @click.self="showAllMyCompaniesModal = false"
        >
            <div
                class="flex max-h-[88vh] w-full max-w-5xl
                       flex-col overflow-hidden rounded-xl
                       border border-zinc-200 bg-white shadow-xl
                       dark:border-white/10 dark:bg-zinc-900"
            >
                <header
                    class="flex items-center justify-between
                           border-b border-zinc-200 px-5 py-4
                           dark:border-white/[0.06]"
                >
                    <div>
                        <h3
                            class="text-base font-semibold
                                   text-zinc-900 dark:text-zinc-50"
                        >
                            Все мои компании
                        </h3>

                        <p class="text-xs tabular-nums text-zinc-500 dark:text-zinc-400">
                            Всего {{ myCompanies.length }}
                        </p>
                    </div>

                    <button
                        type="button"
                        aria-label="Закрыть"
                        class="flex h-8 w-8 items-center justify-center rounded-md
                               text-zinc-500 transition-colors hover:bg-zinc-100 hover:text-zinc-900
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60
                               dark:text-zinc-400 dark:hover:bg-white/[0.06] dark:hover:text-zinc-100"
                        @click="showAllMyCompaniesModal = false"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M6 18 18 6M6 6l12 12" />
                        </svg>
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
                        class="group flex items-center gap-3 rounded-md
                               border border-zinc-200 p-3 text-left
                               transition-colors hover:border-zinc-300
                               hover:bg-zinc-50
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60
                               dark:border-white/[0.08]
                               dark:hover:border-white/15
                               dark:hover:bg-white/[0.03]"
                        @click="visitCompany(company)"
                    >
                        <img
                            v-if="company.logo"
                            :src="`/storage/${company.logo}`"
                            class="h-10 w-10 rounded-md object-cover ring-1 ring-zinc-200 dark:ring-white/10"
                        />

                        <div
                            v-else
                            class="flex h-10 w-10 items-center
                                   justify-center rounded-md
                                   bg-zinc-100 text-xs font-semibold
                                   text-zinc-600 dark:bg-white/[0.06] dark:text-zinc-300"
                        >
                            {{ getInitials(company.name) }}
                        </div>

                        <div class="min-w-0">
                            <p
                                class="truncate text-sm font-medium
                                       text-zinc-900 group-hover:text-blue-700
                                       dark:text-zinc-100 dark:group-hover:text-blue-400"
                            >
                                {{ company.name }}
                            </p>

                            <p class="text-xs tabular-nums text-zinc-500 dark:text-zinc-400">
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
                   justify-center bg-zinc-950/50 p-4
                   backdrop-blur-[2px]"
            @click.self="showAllOtherCompaniesModal = false"
        >
            <div
                class="flex max-h-[88vh] w-full max-w-3xl
                       flex-col overflow-hidden rounded-xl
                       border border-zinc-200 bg-white shadow-xl
                       dark:border-white/10 dark:bg-zinc-900"
            >
                <header
                    class="flex items-center justify-between
                           border-b border-zinc-200 px-5 py-4
                           dark:border-white/[0.06]"
                >
                    <div>
                        <h3
                            class="text-base font-semibold
                                   text-zinc-900 dark:text-zinc-50"
                        >
                            Другие компании
                        </h3>

                        <p class="text-xs tabular-nums text-zinc-500 dark:text-zinc-400">
                            Всего {{ otherCompanies.length }}
                        </p>
                    </div>

                    <button
                        type="button"
                        aria-label="Закрыть"
                        class="flex h-8 w-8 items-center justify-center rounded-md
                               text-zinc-500 transition-colors hover:bg-zinc-100 hover:text-zinc-900
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60
                               dark:text-zinc-400 dark:hover:bg-white/[0.06] dark:hover:text-zinc-100"
                        @click="showAllOtherCompaniesModal = false"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M6 18 18 6M6 6l12 12" />
                        </svg>
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
                        class="group flex items-center gap-3 rounded-md
                               border border-zinc-200 p-3 text-left
                               transition-colors hover:border-zinc-300 hover:bg-zinc-50
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60
                               dark:border-white/[0.08] dark:hover:border-white/15 dark:hover:bg-white/[0.03]"
                        @click="visitCompany(company)"
                    >
                        <img
                            v-if="company.logo"
                            :src="`/storage/${company.logo}`"
                            class="h-9 w-9 rounded-md object-cover ring-1 ring-zinc-200 dark:ring-white/10"
                        />

                        <div
                            v-else
                            class="flex h-9 w-9 items-center
                                   justify-center rounded-md
                                   bg-zinc-100 text-[11px]
                                   font-semibold text-zinc-600
                                   dark:bg-white/[0.06] dark:text-zinc-300"
                        >
                            {{ getInitials(company.name) }}
                        </div>

                        <p
                            class="min-w-0 truncate text-sm font-medium
                                   text-zinc-900 group-hover:text-blue-700
                                   dark:text-zinc-100 dark:group-hover:text-blue-400"
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
                   justify-center bg-zinc-950/50 p-4
                   backdrop-blur-[2px]"
            @click.self="closeDelete"
        >
            <div
                class="w-full max-w-sm rounded-xl border
                       border-zinc-200 bg-white p-5 shadow-xl
                       dark:border-white/10 dark:bg-zinc-900"
            >
                <h4
                    class="text-base font-semibold
                           text-zinc-900 dark:text-zinc-50"
                >
                    Удалить компанию?
                </h4>

                <p
                    class="mt-1 text-sm leading-6
                           text-zinc-600 dark:text-zinc-400"
                >
                    Для подтверждения введите пароль
                    от вашего аккаунта.
                </p>

                <input
                    v-model="deletePassword"
                    type="password"
                    class="mt-4 w-full rounded-md border
                           border-zinc-300 bg-white px-3 py-2.5
                           text-sm text-zinc-900 placeholder:text-zinc-400
                           outline-none focus:border-rose-500
                           focus:ring-2 focus:ring-rose-500/20
                           dark:border-white/10
                           dark:bg-zinc-950 dark:text-zinc-100"
                    placeholder="Пароль"
                    @keyup.enter="confirmDelete"
                />

                <p
                    v-if="deleteError"
                    class="mt-2 rounded-md border border-rose-200 bg-rose-50 px-3 py-2
                           text-xs font-medium text-rose-700
                           dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300"
                >
                    {{ deleteError }}
                </p>

                <div class="mt-5 flex gap-2">
                    <button
                        type="button"
                        class="flex-1 rounded-md border border-zinc-300 bg-white
                               px-4 py-2 text-sm font-medium text-zinc-700
                               transition-colors hover:bg-zinc-50
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60
                               dark:border-white/10 dark:bg-transparent dark:text-zinc-300
                               dark:hover:bg-white/[0.04]"
                        @click="closeDelete"
                    >
                        Отмена
                    </button>

                    <button
                        type="button"
                        :disabled="deleting || !deletePassword"
                        class="flex-1 rounded-md bg-rose-600
                               px-4 py-2 text-sm font-medium
                               text-white transition-colors hover:bg-rose-700
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-500/60 focus-visible:ring-offset-2
                               disabled:cursor-not-allowed disabled:opacity-50
                               dark:focus-visible:ring-offset-zinc-900"
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
    scrollbar-color: rgb(212 212 216) transparent;
}

.company-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.company-scrollbar::-webkit-scrollbar-thumb {
    border-radius: 3px;
    background: rgb(212 212 216);
}
</style>
