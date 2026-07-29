<script setup>
import { computed, ref } from 'vue';
import {
    Head,
    Link,
    router,
} from '@inertiajs/vue3';

const props = defineProps({
    company: {
        type: Object,
        required: true,
    },

    employees: {
        type: Array,
        default: () => [],
    },

    roles: {
        type: Array,
        default: () => [],
    },
});

const search = ref('');
const updatingUserId = ref(null);

const filteredEmployees = computed(() => {
    const query = search.value
        .trim()
        .toLowerCase();

    if (!query) {
        return props.employees;
    }

    return props.employees.filter((employee) => {
        return (
            employee.name
                ?.toLowerCase()
                .includes(query) ||
            employee.email
                ?.toLowerCase()
                .includes(query)
        );
    });
});

function roleLabel(role) {
    const item = props.roles.find(
        (availableRole) => availableRole.value === role
    );

    return item?.label ?? role;
}

function updateRole(employee, role) {
    updatingUserId.value = employee.id;

    router.put(
        `/companies/${props.company.id}/knowledge/access/${employee.id}`,
        {
            role,
        },
        {
            preserveScroll: true,

            onFinish: () => {
                updatingUserId.value = null;
            },
        }
    );
}

function resetRole(employee) {
    updatingUserId.value = employee.id;

    router.delete(
        `/companies/${props.company.id}/knowledge/access/${employee.id}`,
        {
            preserveScroll: true,

            onFinish: () => {
                updatingUserId.value = null;
            },
        }
    );
}

function initials(name) {
    return name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((word) => word.charAt(0).toUpperCase())
        .join('');
}
</script>

<template>
    <Head :title="`Доступ — ${company.name}`" />

    <div class="min-h-screen bg-slate-50">
        <main
            class="mx-auto w-full max-w-7xl px-4 py-8
                   sm:px-6 lg:px-8 lg:py-12"
        >
            <Link
                :href="`/companies/${company.id}/knowledge`"
                class="inline-flex items-center gap-2 text-sm
                       font-semibold text-slate-500 transition
                       hover:text-slate-900"
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
                        d="m15 18-6-6 6-6"
                    />
                </svg>

                Вернуться в базу знаний
            </Link>

            <header
                class="mt-6 flex flex-col gap-5
                       lg:flex-row lg:items-end
                       lg:justify-between"
            >
                <div>
                    <p
                        class="text-sm font-semibold
                               text-indigo-600"
                    >
                        {{ company.name }}
                    </p>

                    <h1
                        class="mt-2 text-3xl font-bold
                               tracking-tight text-slate-950"
                    >
                        Пользователи и доступ
                    </h1>

                    <p
                        class="mt-3 max-w-2xl text-sm
                               leading-6 text-slate-500"
                    >
                        Назначайте сотрудникам роли для работы
                        с базой знаний. Без назначенной роли
                        сотрудник имеет доступ только для просмотра.
                    </p>
                </div>

                <div
                    class="rounded-2xl border border-slate-200
                           bg-white px-5 py-3 shadow-sm"
                >
                    <p
                        class="text-xs font-medium uppercase
                               tracking-wide text-slate-400"
                    >
                        Сотрудников
                    </p>

                    <p
                        class="mt-1 text-2xl font-bold
                               text-slate-900"
                    >
                        {{ employees.length }}
                    </p>
                </div>
            </header>

            <section
                class="mt-8 overflow-hidden rounded-3xl
                       border border-slate-200 bg-white
                       shadow-sm"
            >
                <div
                    class="border-b border-slate-100 p-4
                           sm:p-6"
                >
                    <div class="relative max-w-md">
                        <svg
                            class="pointer-events-none absolute
                                   left-4 top-1/2 h-5 w-5
                                   -translate-y-1/2 text-slate-400"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <circle cx="11" cy="11" r="8" />
                            <path d="m21 21-4.35-4.35" />
                        </svg>

                        <input
                            v-model="search"
                            type="search"
                            placeholder="Найти сотрудника..."
                            class="h-11 w-full rounded-xl
                                   border-0 bg-slate-100
                                   pl-11 pr-4 text-sm
                                   text-slate-900 outline-none
                                   ring-1 ring-inset
                                   ring-transparent transition
                                   placeholder:text-slate-400
                                   focus:bg-white focus:ring-2
                                   focus:ring-indigo-500"
                        >
                    </div>
                </div>

                <div
                    v-if="filteredEmployees.length"
                    class="divide-y divide-slate-100"
                >
                    <article
                        v-for="employee in filteredEmployees"
                        :key="employee.id"
                        class="flex flex-col gap-5 p-5
                               transition hover:bg-slate-50/70
                               sm:flex-row sm:items-center
                               sm:justify-between sm:p-6"
                    >
                        <div
                            class="flex min-w-0 items-center
                                   gap-4"
                        >
                            <div
                                class="flex h-11 w-11 shrink-0
                                       items-center justify-center
                                       rounded-2xl bg-gradient-to-br
                                       from-slate-700 to-slate-900
                                       text-sm font-bold text-white"
                            >
                                {{ initials(employee.name) }}
                            </div>

                            <div class="min-w-0">
                                <h2
                                    class="truncate text-sm
                                           font-semibold text-slate-900"
                                >
                                    {{ employee.name }}
                                </h2>

                                <p
                                    class="mt-1 truncate text-sm
                                           text-slate-500"
                                >
                                    {{ employee.email }}
                                </p>
                            </div>
                        </div>

                        <div
                            class="flex flex-col gap-3
                                   sm:flex-row sm:items-center"
                        >
                            <div>
                                <label
                                    :for="`role-${employee.id}`"
                                    class="sr-only"
                                >
                                    Роль пользователя
                                </label>

                                <select
                                    :id="`role-${employee.id}`"
                                    :value="employee.role"
                                    :disabled="
                                        updatingUserId === employee.id
                                    "
                                    class="h-11 min-w-56 rounded-xl
                                           border-slate-200 bg-white
                                           px-3 text-sm font-medium
                                           text-slate-700 shadow-sm
                                           outline-none transition
                                           focus:border-indigo-500
                                           focus:ring-indigo-500
                                           disabled:cursor-wait
                                           disabled:opacity-60"
                                    @change="
                                        updateRole(
                                            employee,
                                            $event.target.value
                                        )
                                    "
                                >
                                    <option
                                        v-for="role in roles"
                                        :key="role.value"
                                        :value="role.value"
                                    >
                                        {{ role.label }}
                                    </option>
                                </select>
                            </div>

                            <button
                                v-if="employee.has_custom_role"
                                type="button"
                                :disabled="
                                    updatingUserId === employee.id
                                "
                                class="inline-flex h-11
                                       items-center justify-center
                                       rounded-xl border
                                       border-slate-200 bg-white
                                       px-4 text-sm font-semibold
                                       text-slate-600 transition
                                       hover:border-red-200
                                       hover:bg-red-50
                                       hover:text-red-600
                                       disabled:cursor-wait
                                       disabled:opacity-60"
                                @click="resetRole(employee)"
                            >
                                Снять роль
                            </button>
                        </div>
                    </article>
                </div>

                <div
                    v-else
                    class="px-6 py-20 text-center"
                >
                    <div
                        class="mx-auto flex h-14 w-14
                               items-center justify-center
                               rounded-2xl bg-slate-100
                               text-slate-500"
                    >
                        <svg
                            class="h-6 w-6"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <circle cx="11" cy="11" r="8" />
                            <path d="m21 21-4.35-4.35" />
                        </svg>
                    </div>

                    <h2
                        class="mt-4 text-base font-semibold
                               text-slate-900"
                    >
                        Сотрудники не найдены
                    </h2>

                    <p class="mt-2 text-sm text-slate-500">
                        Измените поисковый запрос.
                    </p>
                </div>
            </section>

            <section
                class="mt-6 grid gap-4 md:grid-cols-3"
            >
                <article
                    v-for="role in roles"
                    :key="role.value"
                    class="rounded-2xl border border-slate-200
                           bg-white p-5 shadow-sm"
                >
                    <div
                        class="inline-flex rounded-lg bg-indigo-50
                               px-2.5 py-1 text-xs font-semibold
                               text-indigo-700"
                    >
                        {{ role.label }}
                    </div>

                    <p
                        class="mt-3 text-sm leading-6
                               text-slate-500"
                    >
                        {{ role.description }}
                    </p>
                </article>
            </section>
        </main>
    </div>
</template>