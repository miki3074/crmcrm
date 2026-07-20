<script setup>
import { computed, ref } from 'vue'
import CompanyDetailsPanel from './compontnsother/CompanyDetailsPanel.vue'

const props = defineProps({
    companies: {
        type: Array,
        default: () => [],
    },
    userId: {
        type: [String, Number],
        required: true,
    },
})

const activeFilter = ref('all')
const selectedCompany = ref(null)

const filters = [
    { value: 'mine', label: 'Мои компании' },
    { value: 'other', label: 'Другие компании' },
    { value: 'all', label: 'Все компании' },
]

const isMyCompany = (company) =>
    String(company.user_id) === String(props.userId)

const filteredCompanies = computed(() => {
    if (activeFilter.value === 'mine') {
        return props.companies.filter(isMyCompany)
    }

    if (activeFilter.value === 'other') {
        return props.companies.filter((company) => !isMyCompany(company))
    }

    return props.companies
})

const selectCompany = (company) => {
    selectedCompany.value = company
}

const closeDetails = () => {
    selectedCompany.value = null
}

const formatDate = (value) => {
    if (!value) return '—'

    return new Intl.DateTimeFormat('ru-RU', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(value))
}

const getOwnerName = (company) =>
    company.owner?.name ||
    company.user?.name ||
    company.owner_name ||
    company.user_name ||
    `Пользователь #${company.user_id ?? '—'}`
</script>

<template>
    <div class="min-h-[680px] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
        <div
            class="grid min-h-[680px] transition-[grid-template-columns] duration-300"
            :class="selectedCompany
                ? 'lg:grid-cols-[minmax(0,1fr)_360px]'
                : 'grid-cols-1'"
        >
            <section class="min-w-0">
                <header class="border-b border-slate-200 px-4 py-4 dark:border-slate-800 md:px-6">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                        <div>
                            <h1 class="text-xl font-semibold text-slate-950 dark:text-white">
                                Компании
                            </h1>
                            <p class="mt-1 text-sm text-slate-500">
                                {{ filteredCompanies.length }} из {{ companies.length }}
                            </p>
                        </div>

                        <div class="inline-flex w-full rounded-xl bg-slate-100 p-1 dark:bg-slate-900 xl:w-auto">
                            <button
                                v-for="filter in filters"
                                :key="filter.value"
                                type="button"
                                class="flex-1 rounded-lg px-4 py-2 text-sm font-medium transition xl:flex-none"
                                :class="activeFilter === filter.value
                                    ? 'bg-white text-slate-950 shadow-sm dark:bg-slate-800 dark:text-white'
                                    : 'text-slate-500 hover:text-slate-900 dark:hover:text-slate-200'"
                                @click="activeFilter = filter.value"
                            >
                                {{ filter.label }}
                            </button>
                        </div>
                    </div>
                </header>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] border-collapse">
                        <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/70 dark:border-slate-800 dark:bg-slate-900/50">
                            <th class="w-12 px-4 py-3"></th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Название
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Владелец компании
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Дата создания
                            </th>
                        </tr>
                        </thead>

                        <tbody>
                        <tr
                            v-for="company in filteredCompanies"
                            :key="company.id"
                            class="cursor-pointer border-b border-slate-100 transition last:border-b-0 dark:border-slate-900"
                            :class="selectedCompany?.id === company.id
                                    ? 'bg-indigo-50 dark:bg-indigo-950/25'
                                    : 'hover:bg-slate-50 dark:hover:bg-slate-900/60'"
                            @click="selectCompany(company)"
                        >
                            <td class="px-4 py-3">
                                <div
                                    class="h-9 w-9 overflow-hidden rounded-lg border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800"
                                >
                                    <img
                                        v-if="company.logo"
                                        :src="`/storage/${company.logo}`"
                                        :alt="company.name"
                                        class="h-full w-full object-cover"
                                    />
                                    <div
                                        v-else
                                        class="grid h-full w-full place-items-center text-sm font-bold text-slate-500"
                                    >
                                        {{ company.name?.slice(0, 1)?.toUpperCase() || 'C' }}
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-900 dark:text-white">
                                    {{ company.name }}
                                </div>
                            </td>

                            <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                                {{ getOwnerName(company) }}
                            </td>

                            <td class="px-4 py-3 text-sm text-slate-500">
                                {{ formatDate(company.created_at) }}
                            </td>
                        </tr>

                        <tr v-if="!filteredCompanies.length">
                            <td colspan="4" class="px-6 py-16 text-center">
                                <div class="text-base font-medium text-slate-700 dark:text-slate-200">
                                    Компании не найдены
                                </div>
                                <p class="mt-1 text-sm text-slate-500">
                                    В выбранной категории пока нет компаний.
                                </p>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <CompanyDetailsPanel
                v-if="selectedCompany"
                :company="selectedCompany"
                @close="closeDetails"
            />
        </div>
    </div>
</template>
