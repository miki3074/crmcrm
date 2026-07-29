<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    company: {
        type: Object,
        required: true,
    },

    folder: {
        type: Object,
        required: true,
    },

    folderUsers: {
        type: Array,
        default: () => [],
    },

    companyUsers: {
        type: Array,
        default: () => [],
    },

    roles: {
        type: Array,
        default: () => [
            'viewer',
            'editor',
            'knowledge_manager',
            'admin',
        ],
    },
});

const changingMode = ref(false);
const updatingUserId = ref(null);
const deletingUserId = ref(null);

const addUserForm = useForm({
    user_id: '',
    role: 'viewer',
});

const roleLabels = {
    viewer: 'Только просмотр',
    editor: 'Редактор',
    knowledge_manager: 'Менеджер базы знаний',
    admin: 'Администратор папки',
    owner: 'Владелец',
};

const availableCompanyUsers = computed(() => {
    const existingUserIds = new Set(
        props.folderUsers.map((user) => Number(user.id))
    );

    return props.companyUsers.filter(
        (user) => !existingUserIds.has(Number(user.id))
    );
});

function roleLabel(role) {
    return roleLabels[role] ?? role;
}

function updateAccessMode(event) {
    if (changingMode.value) {
        return;
    }

    changingMode.value = true;

    router.put(
        `/companies/${props.company.id}/knowledge/folders/${props.folder.id}/access/mode`,
        {
            access_type: event.target.value,
        },
        {
            preserveScroll: true,
            preserveState: false,

            onError: (errors) => {
                console.error(
                    'Не удалось изменить доступ:',
                    errors
                );
            },

            onFinish: () => {
                changingMode.value = false;
            },
        }
    );
}

function addUser() {
    if (
        !addUserForm.user_id ||
        addUserForm.processing
    ) {
        return;
    }

    addUserForm.post(
        `/companies/${props.company.id}/knowledge/folders/${props.folder.id}/access/users`,
        {
            preserveScroll: true,

            onSuccess: () => {
                addUserForm.reset();
                addUserForm.role = 'viewer';
            },
        }
    );
}

function updateRole(user, role) {
    if (updatingUserId.value !== null) {
        return;
    }

    updatingUserId.value = user.id;

    router.put(
        `/companies/${props.company.id}/knowledge/folders/${props.folder.id}/access/users/${user.id}`,
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

function removeUser(user) {
    if (deletingUserId.value !== null) {
        return;
    }

    const confirmed = window.confirm(
        `Удалить пользователя "${user.name}" из папки?`
    );

    if (!confirmed) {
        return;
    }

    deletingUserId.value = user.id;

    router.delete(
        `/companies/${props.company.id}/knowledge/folders/${props.folder.id}/access/users/${user.id}`,
        {
            preserveScroll: true,

            onFinish: () => {
                deletingUserId.value = null;
            },
        }
    );
}
</script>

<template>
    <Head :title="`Доступ к папке — ${folder.name}`" />

    <div class="min-h-screen bg-slate-50 text-slate-900">
        <header
            class="sticky top-0 z-30 border-b border-slate-200
                   bg-white/90 backdrop-blur-xl"
        >
            <div
                class="mx-auto flex min-h-20 max-w-7xl flex-col
                       justify-between gap-4 px-4 py-4
                       sm:flex-row sm:items-center sm:px-6
                       lg:px-8"
            >
                <div class="min-w-0">
                    <Link
                        :href="`/companies/${company.id}/knowledge/folders/${folder.id}`"
                        class="inline-flex items-center gap-2
                               text-sm font-medium text-slate-500
                               transition hover:text-indigo-600"
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

                        Вернуться в папку
                    </Link>

                    <div class="mt-2">
                        <h1
                            class="truncate text-xl font-bold
                                   tracking-tight text-slate-900"
                        >
                            Управление доступом
                        </h1>

                        <p class="mt-1 truncate text-sm text-slate-500">
                            {{ folder.name }}
                        </p>
                    </div>
                </div>

                <Link
                    :href="`/companies/${company.id}/knowledge/folders/${folder.id}`"
                    class="inline-flex items-center justify-center
                           rounded-xl border border-slate-200
                           bg-white px-4 py-2.5 text-sm
                           font-semibold text-slate-700
                           shadow-sm transition hover:bg-slate-50"
                >
                    Готово
                </Link>
            </div>
        </header>

        <main
            class="mx-auto max-w-5xl px-4 py-8
                   sm:px-6 lg:px-8"
        >
            <div
                v-if="$page.props.flash?.success"
                class="mb-6 rounded-2xl border border-emerald-200
                       bg-emerald-50 p-4 text-sm font-medium
                       text-emerald-700"
            >
                {{ $page.props.flash.success }}
            </div>

            <div
                v-if="Object.keys($page.props.errors ?? {}).length"
                class="mb-6 rounded-2xl border border-red-200
                       bg-red-50 p-4"
            >
                <p class="text-sm font-semibold text-red-800">
                    Не удалось выполнить действие
                </p>

                <ul class="mt-2 space-y-1 text-sm text-red-700">
                    <li
                        v-for="(error, field) in $page.props.errors"
                        :key="field"
                    >
                        {{ error }}
                    </li>
                </ul>
            </div>

            <section
                class="rounded-3xl border border-slate-200
                       bg-white p-5 shadow-sm sm:p-7"
            >
                <div
                    class="flex flex-col gap-5
                           sm:flex-row sm:items-start
                           sm:justify-between"
                >
                    <div class="max-w-2xl">
                        <h2 class="text-lg font-bold text-slate-900">
                            Режим доступа
                        </h2>

                        <p class="mt-2 text-sm leading-6 text-slate-500">
                            В общей папке доступ получают все пользователи
                            базы знаний. В ограниченной папке доступ имеют
                            только пользователи, добавленные ниже.
                        </p>
                    </div>

                    <div class="w-full sm:w-80">
                        <label
                            for="access-type"
                            class="text-sm font-semibold text-slate-700"
                        >
                            Кто может открыть папку
                        </label>

                        <div class="relative mt-2">
                            <select
    id="access-type"
    :value="folder.access_type"
    class="w-full appearance-none rounded-2xl
           border border-slate-200 bg-white
           px-4 py-3 pr-10 text-sm
           font-semibold text-slate-700
           outline-none transition
           focus:border-indigo-500
           focus:ring-4 focus:ring-indigo-100
           disabled:cursor-not-allowed
           disabled:opacity-50"
    :disabled="changingMode"
    @change="updateAccessMode"
>
    <option value="company">
        Все пользователи компании
    </option>

    <option value="private">
        Только выбранные пользователи
    </option>
</select>

                            <svg
                                class="pointer-events-none absolute
                                       right-4 top-1/2 h-4 w-4
                                       -translate-y-1/2 text-slate-400"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="m6 9 6 6 6-6"
                                />
                            </svg>
                        </div>
                    </div>
                </div>

                <div
                    class="mt-6 rounded-2xl border p-4"
                    :class="
                        folder.access_type === 'private'
                            ? 'border-amber-200 bg-amber-50'
                            : 'border-indigo-200 bg-indigo-50'
                    "
                >
                    <div class="flex items-start gap-3">
                        <svg
                            class="mt-0.5 h-5 w-5 shrink-0"
                            :class="
                                folder.access_type === 'private'
                                    ? 'text-amber-600'
                                    : 'text-indigo-600'
                            "
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <circle cx="12" cy="12" r="9" />

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 11v5m0-8h.01"
                            />
                        </svg>

                        <div>
                            <p
                                class="text-sm font-semibold"
                                :class="
                                    folder.access_type === 'private'
                                        ? 'text-amber-800'
                                        : 'text-indigo-800'
                                "
                            >
                                {{
                                    folder.access_type === 'private'
                                        ? 'Ограниченная папка'
                                        : 'Общая папка'
                                }}
                            </p>

                            <p
                                class="mt-1 text-sm leading-6"
                                :class="
                                    folder.access_type === 'private'
                                        ? 'text-amber-700'
                                        : 'text-indigo-700'
                                "
                            >
                                <template
                                    v-if="folder.access_type === 'private'"
                                >
                                    Пользователи без отдельной роли не смогут
                                    увидеть эту папку.
                                </template>

                                <template v-else>
                                    Отдельная роль пользователя в папке
                                    переопределяет его общую роль.
                                </template>
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section
                class="mt-6 rounded-3xl border border-slate-200
                       bg-white p-5 shadow-sm sm:p-7"
            >
                <div>
                    <h2 class="text-lg font-bold text-slate-900">
                        Добавить пользователя
                    </h2>

                    <p class="mt-2 text-sm text-slate-500">
                        Выберите пользователя компании и назначьте ему роль
                        внутри этой папки.
                    </p>
                </div>

                <form
                    class="mt-6 grid gap-4
                           lg:grid-cols-[minmax(0,1fr)_260px_auto]
                           lg:items-end"
                    @submit.prevent="addUser"
                >
                    <div>
                        <label
                            for="user-id"
                            class="text-sm font-semibold text-slate-700"
                        >
                            Пользователь
                        </label>

                        <select
                            id="user-id"
                            v-model="addUserForm.user_id"
                            class="mt-2 w-full rounded-2xl
                                   border border-slate-200 bg-white
                                   px-4 py-3 text-sm text-slate-700
                                   outline-none transition
                                   focus:border-indigo-500
                                   focus:ring-4 focus:ring-indigo-100"
                        >
                            <option value="">
                                Выберите пользователя
                            </option>

                            <option
                                v-for="user in availableCompanyUsers"
                                :key="user.id"
                                :value="user.id"
                            >
                                {{ user.name }} — {{ user.email }}
                            </option>
                        </select>

                        <p
                            v-if="addUserForm.errors.user_id"
                            class="mt-2 text-sm text-red-600"
                        >
                            {{ addUserForm.errors.user_id }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="user-role"
                            class="text-sm font-semibold text-slate-700"
                        >
                            Роль в папке
                        </label>

                        <select
                            id="user-role"
                            v-model="addUserForm.role"
                            class="mt-2 w-full rounded-2xl
                                   border border-slate-200 bg-white
                                   px-4 py-3 text-sm text-slate-700
                                   outline-none transition
                                   focus:border-indigo-500
                                   focus:ring-4 focus:ring-indigo-100"
                        >
                            <option
                                v-for="role in roles"
                                :key="role"
                                :value="role"
                            >
                                {{ roleLabel(role) }}
                            </option>
                        </select>

                        <p
                            v-if="addUserForm.errors.role"
                            class="mt-2 text-sm text-red-600"
                        >
                            {{ addUserForm.errors.role }}
                        </p>
                    </div>

                    <button
                        type="submit"
                        class="inline-flex min-h-12 items-center
                               justify-center gap-2 rounded-2xl
                               bg-indigo-600 px-5 py-3 text-sm
                               font-semibold text-white shadow-sm
                               transition hover:bg-indigo-700
                               disabled:cursor-not-allowed
                               disabled:opacity-50"
                        :disabled="
                            addUserForm.processing ||
                            !addUserForm.user_id
                        "
                    >
                        <svg
                            v-if="addUserForm.processing"
                            class="h-5 w-5 animate-spin"
                            viewBox="0 0 24 24"
                            fill="none"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            />

                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 0 1 8-8v4a4 4
                                   0 0 0-4 4H4Z"
                            />
                        </svg>

                        <svg
                            v-else
                            class="h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 5v14M5 12h14"
                            />
                        </svg>

                        Добавить
                    </button>
                </form>

                <p
                    v-if="!availableCompanyUsers.length"
                    class="mt-4 text-sm text-slate-500"
                >
                    Все пользователи компании уже добавлены в настройки папки.
                </p>
            </section>

            <section
                class="mt-6 rounded-3xl border border-slate-200
                       bg-white shadow-sm"
            >
                <div
                    class="flex flex-col gap-2 border-b
                           border-slate-200 p-5 sm:p-7"
                >
                    <h2 class="text-lg font-bold text-slate-900">
                        Пользователи папки
                    </h2>

                    <p class="text-sm text-slate-500">
                        Пользователей с отдельной ролью:
                        {{ folderUsers.length }}
                    </p>
                </div>

                <div
                    v-if="folderUsers.length"
                    class="divide-y divide-slate-100"
                >
                    <div
                        v-for="user in folderUsers"
                        :key="user.id"
                        class="flex flex-col gap-4 p-5
                               sm:flex-row sm:items-center sm:p-6"
                    >
                        <div class="flex min-w-0 flex-1 items-center gap-4">
                            <div
                                class="flex h-11 w-11 shrink-0
                                       items-center justify-center
                                       rounded-full bg-indigo-100
                                       text-sm font-bold text-indigo-700"
                            >
                                {{
                                    user.name
                                        ?.trim()
                                        ?.charAt(0)
                                        ?.toUpperCase() || '?'
                                }}
                            </div>

                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p
                                        class="truncate text-sm
                                               font-semibold text-slate-900"
                                    >
                                        {{ user.name }}
                                    </p>

                                    <span
                                        v-if="user.is_creator"
                                        class="rounded-full bg-violet-100
                                               px-2 py-0.5 text-xs
                                               font-semibold text-violet-700"
                                    >
                                        Создатель
                                    </span>

                                    <span
                                        v-if="user.is_company_owner"
                                        class="rounded-full bg-amber-100
                                               px-2 py-0.5 text-xs
                                               font-semibold text-amber-700"
                                    >
                                        Владелец компании
                                    </span>
                                </div>

                                <p
                                    class="mt-1 truncate text-sm
                                           text-slate-500"
                                >
                                    {{ user.email }}
                                </p>
                            </div>
                        </div>

                        <div
                            class="flex flex-col gap-3
                                   sm:flex-row sm:items-center"
                        >
                            <div class="relative sm:w-64">
                                <select
                                    :value="user.role"
                                    class="w-full appearance-none
                                           rounded-xl border
                                           border-slate-200 bg-white
                                           px-4 py-2.5 pr-10 text-sm
                                           font-semibold text-slate-700
                                           outline-none transition
                                           focus:border-indigo-500
                                           focus:ring-4
                                           focus:ring-indigo-100
                                           disabled:cursor-not-allowed
                                           disabled:bg-slate-50
                                           disabled:opacity-60"
                                    :disabled="
                                        updatingUserId !== null ||
                                        deletingUserId !== null ||
                                        user.role === 'owner'
                                    "
                                    @change="
                                        updateRole(
                                            user,
                                            $event.target.value
                                        )
                                    "
                                >
                                    <option
                                        v-if="user.role === 'owner'"
                                        value="owner"
                                    >
                                        Владелец
                                    </option>

                                    <option
                                        v-for="role in roles"
                                        :key="role"
                                        :value="role"
                                    >
                                        {{ roleLabel(role) }}
                                    </option>
                                </select>

                                <svg
                                    class="pointer-events-none absolute
                                           right-3 top-1/2 h-4 w-4
                                           -translate-y-1/2
                                           text-slate-400"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="m6 9 6 6 6-6"
                                    />
                                </svg>
                            </div>

                            <button
                                type="button"
                                class="inline-flex h-11 w-11
                                       shrink-0 items-center
                                       justify-center rounded-xl
                                       border border-red-200
                                       text-red-600 transition
                                       hover:bg-red-50
                                       disabled:cursor-not-allowed
                                       disabled:opacity-40"
                                :disabled="
                                    deletingUserId !== null ||
                                    updatingUserId !== null ||
                                    user.is_creator ||
                                    user.is_company_owner ||
                                    user.role === 'owner'
                                "
                                :title="
                                    user.is_creator
                                        ? 'Нельзя удалить создателя папки'
                                        : user.is_company_owner
                                            ? 'Нельзя удалить владельца компании'
                                            : 'Удалить пользователя'
                                "
                                @click="removeUser(user)"
                            >
                                <svg
                                    v-if="deletingUserId === user.id"
                                    class="h-5 w-5 animate-spin"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                >
                                    <circle
                                        class="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        stroke-width="4"
                                    />

                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 0 1 8-8v4
                                           a4 4 0 0 0-4 4H4Z"
                                    />
                                </svg>

                                <svg
                                    v-else
                                    class="h-5 w-5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M6 7h12m-10 0 .75 12h6.5
                                           L16 7M10 7V4h4v3"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    v-else
                    class="px-6 py-14 text-center"
                >
                    <div
                        class="mx-auto flex h-14 w-14
                               items-center justify-center
                               rounded-2xl bg-slate-100
                               text-slate-400"
                    >
                        <svg
                            class="h-7 w-7"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M16 21v-2a4 4 0 0 0-4-4H6
                                   a4 4 0 0 0-4 4v2"
                            />

                            <circle cx="9" cy="7" r="4" />

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M19 8v6m3-3h-6"
                            />
                        </svg>
                    </div>

                    <p class="mt-4 font-semibold text-slate-700">
                        Пользователи не добавлены
                    </p>

                    <p class="mt-1 text-sm text-slate-500">
                        Добавьте пользователей и назначьте им роли в папке.
                    </p>
                </div>
            </section>
        </main>
    </div>
</template>