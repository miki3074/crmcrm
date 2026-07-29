<script setup>
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    companies: {
        type: Array,
        default: () => [],
    },
});

const search = ref('');

const filteredCompanies = computed(() => {
    const query = search.value.trim().toLowerCase();

    if (!query) {
        return props.companies;
    }

    return props.companies.filter((company) => {
        return company.name.toLowerCase().includes(query);
    });
});

function companyInitials(name) {
    if (!name) {
        return 'К';
    }

    return name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((word) => word.charAt(0).toUpperCase())
        .join('');
}

function companyLogo(company) {
    if (!company.logo) {
        return null;
    }

    if (
        company.logo.startsWith('http://') ||
        company.logo.startsWith('https://') ||
        company.logo.startsWith('/')
    ) {
        return company.logo;
    }

    return `/storage/${company.logo}`;
}

function roleName(company) {
    if (company.is_owner) {
        return 'Владелец';
    }

    const roles = {
        admin: 'Администратор',
        manager: 'Руководитель',
        employee: 'Сотрудник',
        viewer: 'Наблюдатель',
    };

    return roles[company.role] ?? company.role ?? 'Сотрудник';
}

function knowledgeUrl(company) {
    /*
     * При наличии Ziggy можно заменить на:
     *
     * return route('companies.knowledge', company.id);
     */
    return `/companies/${company.id}/knowledge`;
}
</script>

<template>
    <Head title="База знаний" />

    <div class="min-h-screen bg-slate-50">
        <!-- Декоративный фон -->
         
        <div
            class="pointer-events-none fixed inset-x-0 top-0 h-[420px]
                   overflow-hidden"
        >
            <div
                class="absolute left-1/2 top-[-360px] h-[700px] w-[900px]
                       -translate-x-1/2 rounded-full bg-indigo-100/70 blur-3xl"
            />

            <div
                class="absolute right-[-100px] top-10 h-72 w-72
                       rounded-full bg-sky-100/70 blur-3xl"
            />
        </div>

        <main
            class="relative mx-auto w-full max-w-7xl px-4 py-10
                   sm:px-6 lg:px-8 lg:py-14"
        >
            <!-- Заголовок -->
            <header
                class="mb-8 flex flex-col gap-6 lg:mb-10
                       lg:flex-row lg:items-end lg:justify-between"
            >
                <div class="max-w-3xl">

  <Link :href="route('dashboard')" class="flex-shrink-0 transition-transform duration-300 hover:scale-105">
                        
                   

                    <div
                        class="mb-4 inline-flex items-center gap-2 rounded-full
                               border border-indigo-200 bg-white/80 px-3 py-1.5
                               text-sm font-medium text-indigo-700 shadow-sm
                               backdrop-blur"
                    >
                        <svg
                            class="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 6.253v13m0-13C10.832 5.477 9.246
                                   5 7.5 5A4.5 4.5 0 0 0 3 9.5v9.75c0
                                   .414.336.75.75.75 1.751 0 3.299.476
                                   4.632 1.306C9.648 22.094 10.735
                                   23 12 23m0-16.747C13.168 5.477
                                   14.754 5 16.5 5A4.5 4.5 0 0 1 21
                                   9.5v9.75a.75.75 0 0 1-.75.75c-1.751
                                   0-3.299.476-4.632 1.306C14.352
                                   22.094 13.265 23 12 23"
                            />
                        </svg>

                        назад в crm
                    </div>
                     </Link>

                    

                    <h1
                        class="text-3xl font-bold tracking-tight text-slate-950
                               sm:text-4xl lg:text-5xl"
                    >
                        База знаний
                    </h1>

                    <p
                        class="mt-4 max-w-2xl text-base leading-7 text-slate-600
                               sm:text-lg"
                    >
                        Выберите компанию, чтобы перейти к документации,
                        инструкциям, файлам и внутренним материалам команды.
                    </p>
                </div>

                <div
                    class="inline-flex w-fit items-center gap-3 rounded-2xl
                           border border-white/80 bg-white/80 px-4 py-3
                           shadow-sm backdrop-blur"
                >
                    <div
                        class="flex h-10 w-10 items-center justify-center
                               rounded-xl bg-indigo-50 text-indigo-600"
                    >
                        <svg
                            class="h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M3 21h18M6 18V9m4 9V5m4 13v-7m4
                                   7V3"
                            />
                        </svg>
                    </div>

                    <div>
                        <p class="text-xs font-medium uppercase text-slate-400">
                            Доступно компаний
                        </p>

                        <p class="text-lg font-bold text-slate-900">
                            {{ companies.length }}
                        </p>
                    </div>
                </div>
            </header>

            <!-- Поиск -->
            <section
                class="mb-7 rounded-2xl border border-white/80 bg-white/80
                       p-3 shadow-sm backdrop-blur"
            >
                <div class="relative">
                    <svg
                        class="pointer-events-none absolute left-4 top-1/2
                               h-5 w-5 -translate-y-1/2 text-slate-400"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <circle cx="11" cy="11" r="8" />
                        <path
                            stroke-linecap="round"
                            d="m21 21-4.35-4.35"
                        />
                    </svg>

                    <input
                        v-model="search"
                        type="search"
                        placeholder="Найти компанию..."
                        class="h-12 w-full rounded-xl border-0 bg-slate-50
                               pl-12 pr-4 text-sm text-slate-900 outline-none
                               ring-1 ring-inset ring-slate-200 transition
                               placeholder:text-slate-400
                               focus:bg-white focus:ring-2
                               focus:ring-indigo-500"
                    >
                </div>
            </section>

            <!-- Компании -->
            <section
                v-if="filteredCompanies.length"
                class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3"
            >
                <Link
                    v-for="company in filteredCompanies"
                    :key="company.id"
                    :href="knowledgeUrl(company)"
                    class="group relative overflow-hidden rounded-3xl
                           border border-slate-200/80 bg-white p-6
                           shadow-sm transition duration-300
                           hover:-translate-y-1 hover:border-indigo-200
                           hover:shadow-xl hover:shadow-slate-200/60"
                >
                    <!-- Верхняя часть карточки -->
                    <div class="flex items-start justify-between gap-4">
                        <div
                            class="flex h-14 w-14 shrink-0 items-center
                                   justify-center overflow-hidden rounded-2xl
                                   bg-gradient-to-br from-indigo-500
                                   to-violet-600 text-base font-bold text-white
                                   shadow-lg shadow-indigo-200"
                        >
                            <img
                                v-if="companyLogo(company)"
                                :src="companyLogo(company)"
                                :alt="company.name"
                                class="h-full w-full object-cover"
                            >

                            <span v-else>
                                {{ companyInitials(company.name) }}
                            </span>
                        </div>

                        <span
                            class="inline-flex items-center rounded-full px-2.5
                                   py-1 text-xs font-semibold"
                            :class="
                                company.is_owner
                                    ? 'bg-indigo-50 text-indigo-700'
                                    : 'bg-emerald-50 text-emerald-700'
                            "
                        >
                            {{ roleName(company) }}
                        </span>
                    </div>

                    <div class="mt-6">
                        <h2
                            class="truncate text-lg font-semibold
                                   text-slate-900 transition
                                   group-hover:text-indigo-700"
                        >
                            {{ company.name }}
                        </h2>

                        <p class="mt-2 text-sm leading-6 text-slate-500">
                            Документы, инструкции, регламенты и материалы
                            компании в одном месте.
                        </p>
                    </div>

                    <div
                        class="mt-6 flex items-center justify-between
                               border-t border-slate-100 pt-5"
                    >
                        <div class="flex items-center gap-2 text-sm text-slate-500">
                            <svg
                                class="h-4 w-4"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M4 19.5A2.5 2.5 0 0 1 6.5
                                       17H20M4 19.5A2.5 2.5 0 0 0
                                       6.5 22H20V2H6.5A2.5 2.5 0 0
                                       0 4 4.5v15Z"
                                />
                            </svg>

                            Открыть базу
                        </div>

                        <div
                            class="flex h-9 w-9 items-center justify-center
                                   rounded-xl bg-slate-100 text-slate-500
                                   transition group-hover:bg-indigo-600
                                   group-hover:text-white"
                        >
                            <svg
                                class="h-4 w-4 transition-transform
                                       group-hover:translate-x-0.5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="m9 18 6-6-6-6"
                                />
                            </svg>
                        </div>
                    </div>
                </Link>
            </section>

            <!-- Пустой результат поиска -->
            <section
                v-else-if="companies.length"
                class="rounded-3xl border border-dashed border-slate-300
                       bg-white px-6 py-20 text-center"
            >
                <div
                    class="mx-auto flex h-16 w-16 items-center justify-center
                           rounded-2xl bg-slate-100 text-slate-500"
                >
                    <svg
                        class="h-7 w-7"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.35-4.35" />
                    </svg>
                </div>

                <h2 class="mt-5 text-lg font-semibold text-slate-900">
                    Компания не найдена
                </h2>

                <p class="mt-2 text-sm text-slate-500">
                    Попробуйте изменить поисковый запрос.
                </p>

                <button
                    type="button"
                    class="mt-5 rounded-xl bg-slate-900 px-4 py-2.5
                           text-sm font-semibold text-white transition
                           hover:bg-slate-700"
                    @click="search = ''"
                >
                    Очистить поиск
                </button>
            </section>

            <!-- Нет компаний -->
            <section
                v-else
                class="rounded-3xl border border-dashed border-slate-300
                       bg-white px-6 py-20 text-center"
            >
                <div
                    class="mx-auto flex h-16 w-16 items-center justify-center
                           rounded-2xl bg-indigo-50 text-indigo-600"
                >
                    <svg
                        class="h-8 w-8"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M3 21h18M6 18V7.5L12 3l6 4.5V18M9
                               21v-6h6v6"
                        />
                    </svg>
                </div>

                <h2 class="mt-5 text-xl font-semibold text-slate-900">
                    У вас пока нет компаний
                </h2>

                <p
                    class="mx-auto mt-2 max-w-md text-sm leading-6
                           text-slate-500"
                >
                    База знаний станет доступна, когда вы создадите компанию
                    или будете добавлены в неё как сотрудник.
                </p>
            </section>
        </main>
    </div>
</template>