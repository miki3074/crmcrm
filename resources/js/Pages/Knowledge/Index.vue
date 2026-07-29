<script setup>
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import CreateFolderModal from './component/CreateFolderModal.vue';

const props = defineProps({
    company: {
        type: Object,
        required: true,
    },

    currentUserRole: {
        type: String,
        default: 'viewer',
    },

    permissions: {
        type: Object,
        default: () => ({
            view: false,
            create: false,
            edit: false,
            delete: false,
            manage_users: false,
            manage_settings: false,
            create_root_folder: false,
        }),
    },

    knowledge: {
        type: Object,
        default: () => ({
            folders: [],
            articles: [],
            files: [],
        }),
    },

    members: {
        type: Array,
        default: () => [],
    },
});

const search = ref('');
const sidebarOpen = ref(false);

const isOwner = computed(() => {
    return props.currentUserRole === 'owner';
});

const canCreate = computed(() => {
    return props.permissions?.create === true;
});

const canEdit = computed(() => {
    return props.permissions?.edit === true;
});

const canDelete = computed(() => {
    return props.permissions?.delete === true;
});

const canManageUsers = computed(() => {
    return props.permissions?.manage_users === true;
});

const canManageSettings = computed(() => {
    return props.permissions?.manage_settings === true;
});

/*
 * Временно оставляем canManage, потому что он ещё используется
 * в старых местах шаблона.
 *
 * Позже лучше заменить его на конкретные разрешения.
 */
const canManage = computed(() => {
    return canCreate.value;
});

const showManagementBlock = computed(() => {
    return (
        canManageUsers.value ||
        canManageSettings.value
    );
});

const totalMaterials = computed(() => {
    const foldersCount = Array.isArray(props.knowledge?.folders)
        ? props.knowledge.folders.length
        : 0;

    const articlesCount = Array.isArray(props.knowledge?.articles)
        ? props.knowledge.articles.length
        : 0;

    const filesCount = Array.isArray(props.knowledge?.files)
        ? props.knowledge.files.length
        : 0;

    return foldersCount + articlesCount + filesCount;
});

const roleLabel = computed(() => {
    const roles = {
        owner: 'Владелец',
        knowledge_manager: 'Менеджер базы знаний',
        editor: 'Редактор',
        viewer: 'Просмотр',
    };

    return roles[props.currentUserRole]
        ?? props.currentUserRole
        ?? 'Просмотр';
});

const createFolderModalOpen = ref(false);
const createFolderParent = ref(null);

const filteredFolders = computed(() => {
    const folders = Array.isArray(props.knowledge?.folders)
        ? props.knowledge.folders
        : [];

    const query = search.value.trim().toLowerCase();

    if (!query) {
        return folders;
    }

    return folders.filter((folder) => {
        return folder.name
            ?.toLowerCase()
            .includes(query);
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

function companyLogo() {
    const logo = props.company?.logo;

    if (!logo) {
        return null;
    }

    if (
        logo.startsWith('http://') ||
        logo.startsWith('https://') ||
        logo.startsWith('/')
    ) {
        return logo;
    }

    return `/storage/${logo}`;
}

function openCreateFolderModal(parent = null) {
    createFolderParent.value = parent;
    createFolderModalOpen.value = true;
}

function closeCreateFolderModal() {
    createFolderModalOpen.value = false;
    createFolderParent.value = null;
}
</script>

<template>
    <Head :title="`База знаний — ${company.name}`" />

    <div class="min-h-screen bg-slate-50 text-slate-900">
        <!-- Мобильный затемнённый фон -->
        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-40 bg-slate-950/40 backdrop-blur-sm
                   lg:hidden"
            @click="sidebarOpen = false"
        />

        <!-- Боковое меню -->
        <aside
            class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col
                   border-r border-slate-200 bg-white transition-transform
                   duration-300 lg:translate-x-0"
            :class="
                sidebarOpen
                    ? 'translate-x-0'
                    : '-translate-x-full'
            "
        >
            <!-- Логотип компании -->
            <div
                class="flex h-20 items-center justify-between
                       border-b border-slate-100 px-5"
            >
                <Link
                    href="/knowledge"
                    class="flex min-w-0 items-center gap-3"
                >
                    <div
                        class="flex h-10 w-10 shrink-0 items-center
                               justify-center overflow-hidden rounded-xl
                               bg-gradient-to-br from-indigo-500
                               to-violet-600 text-sm font-bold text-white"
                    >
                        <img
                            v-if="companyLogo()"
                            :src="companyLogo()"
                            :alt="company.name"
                            class="h-full w-full object-cover"
                        >

                        <span v-else>
                            {{ companyInitials(company.name) }}
                        </span>
                    </div>

                    <div class="min-w-0">
                        <p
                            class="truncate text-sm font-semibold
                                   text-slate-900"
                        >
                            {{ company.name }}
                        </p>

                        <p class="truncate text-xs text-slate-500">
                            База знаний
                        </p>
                    </div>
                </Link>

                <button
                    type="button"
                    class="rounded-lg p-2 text-slate-400 hover:bg-slate-100
                           hover:text-slate-700 lg:hidden"
                    @click="sidebarOpen = false"
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
                            d="M6 6l12 12M18 6 6 18"
                        />
                    </svg>
                </button>
            </div>

            <!-- Навигация -->
            <nav class="flex-1 overflow-y-auto px-3 py-5">
                <p
                    class="mb-2 px-3 text-xs font-semibold uppercase
                           tracking-wider text-slate-400"
                >
                    Рабочее пространство
                </p>

                <div class="space-y-1">
 <button
                        type="button"
                        class="flex w-full items-center gap-3 rounded-xl
                               bg-indigo-50 px-3 py-2.5 text-left text-sm
                               font-semibold text-indigo-700"
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
                                d="M3 9.75 12 3l9 6.75V21a.75.75
                                   0 0 1-.75.75H3.75A.75.75 0 0 1
                                   3 21V9.75Z"
                            />
                        </svg>

                        Главная
                    </button>

<Link :href="route('dashboard')" class="flex-shrink-0 transition-transform duration-300 hover:scale-105">
                    <button
                        type="button"
                        class="flex w-full items-center gap-3 rounded-xl
                               px-3 py-2.5 text-left text-sm font-medium
                               text-slate-600 transition hover:bg-slate-100
                               hover:text-slate-900"
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
                                d="M3 9.75 12 3l9 6.75V21a.75.75
                                   0 0 1-.75.75H3.75A.75.75 0 0 1
                                   3 21V9.75Z"
                            />
                        </svg>

                        назад в crm
                    </button>
                </Link>

                    <button
                        type="button"
                        class="flex w-full items-center gap-3 rounded-xl
                               px-3 py-2.5 text-left text-sm font-medium
                               text-slate-600 transition hover:bg-slate-100
                               hover:text-slate-900"
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
                                d="M3.75 5.25A2.25 2.25 0 0 1 6
                                   3h3.75l2.25 2.25H18A2.25 2.25
                                   0 0 1 20.25 7.5v9A2.25 2.25 0
                                   0 1 18 18.75H6a2.25 2.25 0 0
                                   1-2.25-2.25V5.25Z"
                            />
                        </svg>

                        Все материалы

                        <span
                            class="ml-auto rounded-full bg-slate-100 px-2
                                   py-0.5 text-xs text-slate-500"
                        >
                            {{ totalMaterials }}
                        </span>
                    </button>

                    <button style="display: none;"
                        type="button"
                        class="flex w-full items-center gap-3 rounded-xl
                               px-3 py-2.5 text-left text-sm font-medium
                               text-slate-600 transition hover:bg-slate-100
                               hover:text-slate-900"
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
                                d="m12 3 2.82 5.715 6.305.916-4.563
                                   4.447 1.077 6.28L12 17.392l-5.639
                                   2.965 1.077-6.279-4.563-4.447
                                   6.305-.916L12 3Z"
                            />
                        </svg>

                        Избранное
                    </button>

                    <button style="display: none;"
                        type="button"
                        class="flex w-full items-center gap-3 rounded-xl
                               px-3 py-2.5 text-left text-sm font-medium
                               text-slate-600 transition hover:bg-slate-100
                               hover:text-slate-900"
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
                                d="M12 6v6l4 2M21 12a9 9 0 1
                                   1-18 0 9 9 0 0 1 18 0Z"
                            />
                        </svg>

                        Недавние
                    </button>
                </div>

                <template v-if="showManagementBlock">
    <p
        class="mb-2 mt-7 px-3 text-xs font-semibold uppercase
               tracking-wider text-slate-400"
    >
        Управление
    </p>

    <div class="space-y-1">
        <Link
            v-if="canManageUsers"
            :href="`/companies/${company.id}/knowledge/access`"
            class="flex w-full items-center gap-3 rounded-xl
                   px-3 py-2.5 text-left text-sm font-medium
                   text-slate-600 transition hover:bg-slate-100
                   hover:text-slate-900"
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
                    d="M16.5 18.75h-9m9 0a3.75 3.75
                       0 0 0 0-7.5m-9 7.5a3.75 3.75
                       0 0 1 0-7.5m9 7.5v1.5m-9-1.5
                       v1.5M12 7.5a3 3 0 1 0 0-6
                       3 3 0 0 0 0 6Z"
                />
            </svg>

            Пользователи и доступ
        </Link>

        <button style="display: none;"
            v-if="canManageSettings"
            type="button"
            class="flex w-full items-center gap-3 rounded-xl
                   px-3 py-2.5 text-left text-sm font-medium
                   text-slate-600 transition hover:bg-slate-100
                   hover:text-slate-900"
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
                    d="M12 15.75A3.75 3.75 0 1 0
                       12 8.25a3.75 3.75 0 0 0
                       0 7.5Z"
                />
            </svg>

            Настройки
        </button>
    </div>
</template>
            </nav>

            <!-- Профиль роли -->
            <div class="border-t border-slate-100 p-4">
                <div class="rounded-2xl bg-slate-50 p-3">
                    <p class="text-xs text-slate-500">
                        Ваша роль
                    </p>

                    <div class="mt-1 flex items-center justify-between gap-2">
                        <span class="text-sm font-semibold text-slate-800">
                            {{ roleLabel }}
                        </span>

                        <span
                            v-if="isOwner"
                            class="rounded-full bg-indigo-100 px-2 py-0.5
                                   text-[11px] font-semibold text-indigo-700"
                        >
                            Полный доступ
                        </span>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Основная часть -->
        <div class="lg:pl-72">
            <!-- Верхняя панель -->
            <header
                class="sticky top-0 z-30 border-b border-slate-200/80
                       bg-white/90 backdrop-blur-xl"
            >
                <div
                    class="flex h-20 items-center gap-3 px-4 sm:px-6
                           lg:px-8"
                >
                    <button
                        type="button"
                        class="rounded-xl border border-slate-200 p-2.5
                               text-slate-600 hover:bg-slate-50 lg:hidden"
                        @click="sidebarOpen = true"
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
                                d="M4 6h16M4 12h16M4 18h16"
                            />
                        </svg>
                    </button>

                    <!-- Поиск -->
                    <div class="relative max-w-xl flex-1">
                        <svg
                            class="pointer-events-none absolute left-4 top-1/2
                                   h-5 w-5 -translate-y-1/2 text-slate-400"
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
                            placeholder="Поиск по базе знаний..."
                            class="h-11 w-full rounded-xl border-0
                                   bg-slate-100 pl-11 pr-4 text-sm
                                   text-slate-900 outline-none ring-1
                                   ring-inset ring-transparent transition
                                   placeholder:text-slate-400 focus:bg-white
                                   focus:ring-2 focus:ring-indigo-500"
                        >
                    </div>

                    <Link
                        href="/knowledge"
                        class="hidden items-center gap-2 rounded-xl
                               border border-slate-200 bg-white px-4 py-2.5
                               text-sm font-semibold text-slate-700
                               shadow-sm transition hover:bg-slate-50 sm:flex"
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

                        Все компании
                    </Link>

                    <button
                        v-if="permissions.create_root_folder"
                        type="button"
                        class="inline-flex h-11 items-center gap-2
                               rounded-xl bg-indigo-600 px-4 text-sm
                               font-semibold text-white shadow-sm transition
                               hover:bg-indigo-700 focus:outline-none
                               focus:ring-2 focus:ring-indigo-500
                               focus:ring-offset-2"
                        @click="openCreateFolderModal()"
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
                                d="M12 5v14M5 12h14"
                            />
                        </svg>

                        <span class="hidden sm:inline">
                            Создать папку
                        </span>
                    </button>
                </div>
            </header>

            <main class="px-4 py-8 sm:px-6 lg:px-8 lg:py-10">
                <div class="mx-auto max-w-7xl">
                    <!-- Приветственный блок -->
                    <section
                        class="relative overflow-hidden rounded-3xl
                               bg-slate-950 px-6 py-8 text-white shadow-xl
                               sm:px-8 lg:px-10 lg:py-10"
                    >
                        <div
                            class="pointer-events-none absolute -right-20
                                   -top-28 h-80 w-80 rounded-full
                                   bg-indigo-500/30 blur-3xl"
                        />

                        <div
                            class="pointer-events-none absolute -bottom-28
                                   left-1/3 h-64 w-64 rounded-full
                                   bg-sky-400/20 blur-3xl"
                        />

                        <div
                            class="relative flex flex-col gap-7
                                   lg:flex-row lg:items-end
                                   lg:justify-between"
                        >
                            <div class="max-w-2xl">
                                <div
                                    class="inline-flex items-center gap-2
                                           rounded-full bg-white/10 px-3
                                           py-1.5 text-xs font-semibold
                                           text-slate-200 ring-1
                                           ring-inset ring-white/10"
                                >
                                    <span
                                        class="h-2 w-2 rounded-full
                                               bg-emerald-400"
                                    />

                                    Рабочее пространство компании
                                </div>

                                <h1
                                    class="mt-5 text-3xl font-bold
                                           tracking-tight sm:text-4xl"
                                >
                                    {{ company.name }}
                                </h1>

                                <p
                                    class="mt-3 max-w-xl text-sm leading-6
                                           text-slate-300 sm:text-base"
                                >
                                    Храните инструкции, документы, файлы
                                    и корпоративные знания в едином
                                    организованном пространстве.
                                </p>
                            </div>

                            <button
                                v-if="permissions.create_root_folder"
                                type="button"
                                class="inline-flex w-fit items-center gap-2
                                       rounded-xl bg-white px-4 py-3 text-sm
                                       font-semibold text-slate-900 shadow-sm
                                       transition hover:bg-slate-100"
                                @click="openCreateFolderModal()"
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
                                        d="M3.75 5.25A2.25 2.25 0 0 1
                                           6 3h3.75L12 5.25h6A2.25
                                           2.25 0 0 1 20.25 7.5v9A2.25
                                           2.25 0 0 1 18 18.75H6a2.25
                                           2.25 0 0 1-2.25-2.25V5.25Z
                                           M12 9v6m-3-3h6"
                                    />
                                </svg>

                                Создать папку
                            </button>

                 
                        </div>
                    </section>

                    <!-- Статистика -->
                    <!-- <section
                        class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3"
                    >
                        <div
                            class="rounded-2xl border border-slate-200
                                   bg-white p-5 shadow-sm"
                        >
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-slate-500">
                                        Папки
                                    </p>

                                    <p
                                        class="mt-2 text-3xl font-bold
                                               text-slate-900"
                                    >
                                        {{ knowledge.folders?.length ?? 0 }}
                                    </p>
                                </div>

                                <div
                                    class="flex h-12 w-12 items-center
                                           justify-center rounded-2xl
                                           bg-amber-50 text-amber-600"
                                >
                                    <svg
                                        class="h-6 w-6"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M3.75 5.25A2.25 2.25 0 0 1
                                               6 3h3.75L12 5.25h6A2.25
                                               2.25 0 0 1 20.25 7.5v9A2.25
                                               2.25 0 0 1 18 18.75H6a2.25
                                               2.25 0 0 1-2.25-2.25V5.25Z"
                                        />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <div
                            class="rounded-2xl border border-slate-200
                                   bg-white p-5 shadow-sm"
                        >
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-slate-500">
                                        Статьи
                                    </p>

                                    <p
                                        class="mt-2 text-3xl font-bold
                                               text-slate-900"
                                    >
                                        {{ knowledge.articles?.length ?? 0 }}
                                    </p>
                                </div>

                                <div
                                    class="flex h-12 w-12 items-center
                                           justify-center rounded-2xl
                                           bg-indigo-50 text-indigo-600"
                                >
                                    <svg
                                        class="h-6 w-6"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M6 3.75h9L19.5 8.25V20.25
                                               H6V3.75Z"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            d="M9 12h7M9 15h7"
                                        />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <div
                            class="rounded-2xl border border-slate-200
                                   bg-white p-5 shadow-sm"
                        >
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-slate-500">
                                        Файлы
                                    </p>

                                    <p
                                        class="mt-2 text-3xl font-bold
                                               text-slate-900"
                                    >
                                        {{ knowledge.files?.length ?? 0 }}
                                    </p>
                                </div>

                                <div
                                    class="flex h-12 w-12 items-center
                                           justify-center rounded-2xl
                                           bg-emerald-50 text-emerald-600"
                                >
                                    <svg
                                        class="h-6 w-6"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M18.375 12.739 11.693
                                               19.42a4.5 4.5 0 0 1-6.364
                                               -6.364l8.04-8.04a3 3 0 0
                                               1 4.243 4.243l-8.04
                                               8.04a1.5 1.5 0 0 1-2.122
                                               -2.122l7.425-7.425"
                                        />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </section> -->

                    <!-- Пустая база -->
                    <section
                        v-if="totalMaterials === 0"
                        class="mt-6 overflow-hidden rounded-3xl
                               border border-slate-200 bg-white shadow-sm"
                    >
                        <div
                            class="grid items-center gap-8 px-6 py-12
                                   sm:px-10 lg:grid-cols-[1fr_360px]
                                   lg:px-12 lg:py-16"
                        >
                            <div>
                                <div
                                    class="flex h-14 w-14 items-center
                                           justify-center rounded-2xl
                                           bg-indigo-50 text-indigo-600"
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
                                            d="M3.75 5.25A2.25 2.25 0 0 1
                                               6 3h3.75L12 5.25h6A2.25
                                               2.25 0 0 1 20.25 7.5v9A2.25
                                               2.25 0 0 1 18 18.75H6a2.25
                                               2.25 0 0 1-2.25-2.25V5.25Z"
                                        />
                                    </svg>
                                </div>

                                <h2
                                    class="mt-6 text-2xl font-bold
                                           tracking-tight text-slate-900"
                                >
                                    Создайте первую папку
                                </h2>

                                <p
                                    class="mt-3 max-w-xl text-sm leading-6
                                           text-slate-500 sm:text-base"
                                >
                                    Организуйте знания компании по отделам,
                                    проектам или направлениям. Внутри папок
                                    можно будет создавать вложенные папки,
                                    статьи и загружать файлы.
                                </p>

                                <div
                                    v-if="permissions.create_root_folder"
                                    class="mt-7 flex flex-col gap-3 sm:flex-row"
                                >
                                    <button
                                        type="button"
                                        class="inline-flex items-center
                                               justify-center gap-2 rounded-xl
                                               bg-indigo-600 px-5 py-3
                                               text-sm font-semibold text-white
                                               shadow-sm transition
                                               hover:bg-indigo-700"
                                        @click="openCreateFolderModal()"
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
                                                d="M12 5v14M5 12h14"
                                            />
                                        </svg>

                                        Создать папку
                                    </button>
                                </div>
                            </div>

                            <!-- Иллюстрация структуры -->
                            <div
                                class="relative hidden min-h-64
                                       rounded-3xl bg-slate-50 p-6
                                       lg:block"
                            >
                                <div
                                    class="absolute left-8 top-8 w-52
                                           rounded-2xl border border-slate-200
                                           bg-white p-4 shadow-sm"
                                >
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-9 w-9 items-center
                                                   justify-center rounded-xl
                                                   bg-amber-50 text-amber-500"
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
                                                    d="M3.75 5.25A2.25 2.25
                                                       0 0 1 6 3h3.75L12
                                                       5.25h6A2.25 2.25 0
                                                       0 1 20.25 7.5v9A2.25
                                                       2.25 0 0 1 18
                                                       18.75H6a2.25 2.25
                                                       0 0 1-2.25-2.25V5.25Z"
                                                />
                                            </svg>
                                        </div>

                                        <div>
                                            <div
                                                class="h-2.5 w-24
                                                       rounded bg-slate-800"
                                            />
                                            <div
                                                class="mt-2 h-2 w-14
                                                       rounded bg-slate-200"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div
                                    class="absolute bottom-8 right-8 w-52
                                           rounded-2xl border border-slate-200
                                           bg-white p-4 shadow-sm"
                                >
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-9 w-9 items-center
                                                   justify-center rounded-xl
                                                   bg-indigo-50
                                                   text-indigo-500"
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
                                                    d="M6 3.75h9L19.5
                                                       8.25V20.25H6V3.75Z"
                                                />
                                            </svg>
                                        </div>

                                        <div>
                                            <div
                                                class="h-2.5 w-24
                                                       rounded bg-slate-800"
                                            />
                                            <div
                                                class="mt-2 h-2 w-16
                                                       rounded bg-slate-200"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <svg
                                    class="absolute inset-0 h-full w-full
                                           text-slate-300"
                                    viewBox="0 0 360 260"
                                    fill="none"
                                >
                                    <path
                                        d="M180 75v45c0 20 20 20 40 20h30"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-dasharray="6 6"
                                    />
                                </svg>
                            </div>
                        </div>
                    </section>

                    <!-- Здесь позже будет список материалов -->
                   <section
    v-else
    class="mt-6 rounded-3xl border border-slate-200
           bg-white p-6 shadow-sm"
>
    <div
        class="flex flex-col gap-4 sm:flex-row
               sm:items-center sm:justify-between"
    >
        <div>
            <h2 class="text-lg font-semibold text-slate-900">
                Папки
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Доступные вам разделы базы знаний.
            </p>
        </div>

        <button
            v-if="permissions.create_root_folder"
            type="button"
            class="inline-flex items-center justify-center gap-2
                   rounded-xl bg-indigo-600 px-4 py-2.5
                   text-sm font-semibold text-white
                   transition hover:bg-indigo-700"
            @click="openCreateFolderModal()"
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
                    d="M12 5v14M5 12h14"
                />
            </svg>

            Создать папку
        </button>
    </div>

    <div
        v-if="filteredFolders.length"
        class="mt-6 grid grid-cols-1 gap-4
               sm:grid-cols-2 xl:grid-cols-3"
    >
        <Link
            v-for="folder in filteredFolders"
            :key="folder.id"
            :href="`/companies/${company.id}/knowledge/folders/${folder.id}`"
            class="group rounded-2xl border border-slate-200
                   bg-white p-5 transition
                   hover:-translate-y-0.5
                   hover:border-indigo-200
                   hover:shadow-lg"
        >
            <div class="flex items-start gap-4">
                <div
                    class="flex h-12 w-12 shrink-0 items-center
                           justify-center rounded-2xl
                           bg-amber-50 text-amber-600
                           transition group-hover:bg-amber-100"
                >
                    <svg
                        class="h-6 w-6"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M3.75 5.25A2.25 2.25 0 0 1
                               6 3h3.75L12 5.25h6A2.25
                               2.25 0 0 1 20.25 7.5v9A2.25
                               2.25 0 0 1 18 18.75H6a2.25
                               2.25 0 0 1-2.25-2.25V5.25Z"
                        />
                    </svg>
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex items-start justify-between gap-3">
                        <h3
                            class="truncate text-base font-semibold
                                   text-slate-900
                                   group-hover:text-indigo-700"
                        >
                            {{ folder.name }}
                        </h3>

                        <svg
                            class="h-5 w-5 shrink-0 text-slate-300
                                   transition group-hover:translate-x-0.5
                                   group-hover:text-indigo-500"
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

                    <p class="mt-2 text-sm text-slate-500">
                        {{
                            folder.access_type === 'company'
                                ? 'Доступна всей компании'
                                : 'Ограниченный доступ'
                        }}
                    </p>

                    <div
                        class="mt-4 flex flex-wrap items-center
                               gap-x-4 gap-y-2 text-xs text-slate-400"
                    >
                        <span v-if="folder.children_count !== undefined">
                            Вложенных папок:
                            {{ folder.children_count }}
                        </span>

                        <span v-if="folder.articles_count !== undefined">
                            Статей:
                            {{ folder.articles_count }}
                        </span>

                        <span v-if="folder.files_count !== undefined">
                            Файлов:
                            {{ folder.files_count }}
                        </span>
                    </div>
                </div>
            </div>
        </Link>
    </div>

    <div
        v-else
        class="mt-6 rounded-2xl border border-dashed
               border-slate-300 px-6 py-12 text-center"
    >
        <div
            class="mx-auto flex h-12 w-12 items-center
                   justify-center rounded-2xl
                   bg-slate-100 text-slate-400"
        >
            <svg
                class="h-6 w-6"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M3.75 5.25A2.25 2.25 0 0 1
                       6 3h3.75L12 5.25h6A2.25
                       2.25 0 0 1 20.25 7.5v9A2.25
                       2.25 0 0 1 18 18.75H6a2.25
                       2.25 0 0 1-2.25-2.25V5.25Z"
                />
            </svg>
        </div>

        <h3 class="mt-4 text-sm font-semibold text-slate-900">
            Папки не найдены
        </h3>

        <p class="mt-1 text-sm text-slate-500">
            Попробуйте изменить поисковый запрос.
        </p>
    </div>
</section>
                </div>
            </main>
        </div>

        <CreateFolderModal
            :open="createFolderModalOpen"
            :company="company"
            :members="members"
            :parent-folder="createFolderParent"
            @close="closeCreateFolderModal"
        />
    </div>
</template>