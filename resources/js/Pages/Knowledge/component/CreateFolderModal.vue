<script setup>
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },

    company: {
        type: Object,
        required: true,
    },

    members: {
        type: Array,
        default: () => [],
    },

    parentFolder: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits([
    'close',
]);

const search = ref('');

const form = useForm({
    name: '',
    parent_id: null,
    access_type: 'company',
    users: [],
});

const filteredMembers = computed(() => {
    const query = search.value
        .trim()
        .toLowerCase();

    if (!query) {
        return props.members;
    }

    return props.members.filter((member) => {
        return (
            member.name
                ?.toLowerCase()
                .includes(query) ||
            member.email
                ?.toLowerCase()
                .includes(query)
        );
    });
});

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) {
            return;
        }

        form.reset();

        form.parent_id =
            props.parentFolder?.id ?? null;

        form.access_type = 'company';

        search.value = '';
    }
);

function isSelected(userId) {
    return form.users.some(
        (item) => item.user_id === userId
    );
}

function selectedRole(userId) {
    return form.users.find(
        (item) => item.user_id === userId
    )?.role ?? 'viewer';
}

function toggleUser(member) {
    const index = form.users.findIndex(
        (item) => item.user_id === member.id
    );

    if (index >= 0) {
        form.users.splice(index, 1);
        return;
    }

    form.users.push({
        user_id: member.id,
        role: 'viewer',
    });
}

function changeRole(userId, role) {
    const item = form.users.find(
        (selectedUser) =>
            selectedUser.user_id === userId
    );

    if (item) {
        item.role = role;
    }
}

function submit() {
    form.post(
        `/companies/${props.company.id}/knowledge/folders`,
        {
            preserveScroll: true,

            onSuccess: () => {
                form.reset();
                emit('close');
            },
        }
    );
}

function close() {
    if (!form.processing) {
        emit('close');
    }
}

function initials(name) {
    return name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join('');
}
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-[100]
                       overflow-y-auto"
                @keydown.esc="close"
            >
                <div
                    class="fixed inset-0 bg-slate-950/50
                           backdrop-blur-sm"
                    @click="close"
                />

                <div
                    class="relative flex min-h-full
                           items-center justify-center
                           p-4 sm:p-6"
                >
                    <div
                        class="relative w-full max-w-2xl
                               overflow-hidden rounded-3xl
                               bg-white shadow-2xl"
                        role="dialog"
                        aria-modal="true"
                    >
                        <header
                            class="flex items-start
                                   justify-between gap-4
                                   border-b border-slate-100
                                   px-6 py-5"
                        >
                            <div>
                                <h2
                                    class="text-xl font-bold
                                           text-slate-950"
                                >
                                    Создать папку
                                </h2>

                                <p
                                    class="mt-1 text-sm
                                           text-slate-500"
                                >
                                    <template v-if="parentFolder">
                                        Внутри папки
                                        «{{ parentFolder.name }}»
                                    </template>

                                    <template v-else>
                                        В корне базы знаний
                                    </template>
                                </p>
                            </div>

                            <button
                                type="button"
                                class="rounded-xl p-2
                                       text-slate-400
                                       transition
                                       hover:bg-slate-100
                                       hover:text-slate-700"
                                @click="close"
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
                        </header>

                        <form @submit.prevent="submit">
                            <div
                                class="max-h-[70vh]
                                       space-y-6
                                       overflow-y-auto
                                       px-6 py-6"
                            >
                                <div>
                                    <label
                                        for="folder-name"
                                        class="text-sm
                                               font-semibold
                                               text-slate-800"
                                    >
                                        Название папки
                                    </label>

                                    <input
                                        id="folder-name"
                                        v-model="form.name"
                                        type="text"
                                        autofocus
                                        placeholder="Например, Финансы"
                                        class="mt-2 h-12 w-full
                                               rounded-xl
                                               border-slate-200
                                               bg-white px-4
                                               text-sm
                                               text-slate-900
                                               shadow-sm
                                               focus:border-indigo-500
                                               focus:ring-indigo-500"
                                    >

                                    <p
                                        v-if="form.errors.name"
                                        class="mt-2 text-sm
                                               text-red-600"
                                    >
                                        {{ form.errors.name }}
                                    </p>
                                </div>

                                <fieldset>
                                    <legend
                                        class="text-sm
                                               font-semibold
                                               text-slate-800"
                                    >
                                        Кто может открыть папку
                                    </legend>

                                    <div
                                        class="mt-3 grid gap-3
                                               sm:grid-cols-2"
                                    >
                                        <label
                                            class="cursor-pointer
                                                   rounded-2xl border
                                                   p-4 transition"
                                            :class="
                                                form.access_type ===
                                                'company'
                                                    ? 'border-indigo-500 bg-indigo-50 ring-1 ring-indigo-500'
                                                    : 'border-slate-200 hover:bg-slate-50'
                                            "
                                        >
                                            <input
                                                v-model="
                                                    form.access_type
                                                "
                                                type="radio"
                                                value="company"
                                                class="sr-only"
                                            >

                                            <div
                                                class="flex
                                                       items-start
                                                       gap-3"
                                            >
                                                <div
                                                    class="flex h-10
                                                           w-10
                                                           shrink-0
                                                           items-center
                                                           justify-center
                                                           rounded-xl
                                                           bg-white
                                                           text-indigo-600
                                                           shadow-sm"
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
                                                            d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM22 21v-2a4 4 0 0 0-3-3.87"
                                                        />
                                                    </svg>
                                                </div>

                                                <div>
                                                    <p
                                                        class="text-sm
                                                               font-semibold
                                                               text-slate-900"
                                                    >
                                                        Все сотрудники
                                                    </p>

                                                    <p
                                                        class="mt-1
                                                               text-xs
                                                               leading-5
                                                               text-slate-500"
                                                    >
                                                        Папку смогут
                                                        просматривать
                                                        все сотрудники
                                                        компании.
                                                    </p>
                                                </div>
                                            </div>
                                        </label>

                                        <label
                                            class="cursor-pointer
                                                   rounded-2xl border
                                                   p-4 transition"
                                            :class="
                                                form.access_type ===
                                                'private'
                                                    ? 'border-indigo-500 bg-indigo-50 ring-1 ring-indigo-500'
                                                    : 'border-slate-200 hover:bg-slate-50'
                                            "
                                        >
                                            <input
                                                v-model="
                                                    form.access_type
                                                "
                                                type="radio"
                                                value="private"
                                                class="sr-only"
                                            >

                                            <div
                                                class="flex
                                                       items-start
                                                       gap-3"
                                            >
                                                <div
                                                    class="flex h-10
                                                           w-10
                                                           shrink-0
                                                           items-center
                                                           justify-center
                                                           rounded-xl
                                                           bg-white
                                                           text-indigo-600
                                                           shadow-sm"
                                                >
                                                    <svg
                                                        class="h-5 w-5"
                                                        viewBox="0 0 24 24"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        stroke-width="2"
                                                    >
                                                        <rect
                                                            x="3"
                                                            y="11"
                                                            width="18"
                                                            height="10"
                                                            rx="2"
                                                        />
                                                        <path
                                                            d="M7 11V7a5 5 0 0 1 10 0v4"
                                                        />
                                                    </svg>
                                                </div>

                                                <div>
                                                    <p
                                                        class="text-sm
                                                               font-semibold
                                                               text-slate-900"
                                                    >
                                                        Выбранные
                                                        пользователи
                                                    </p>

                                                    <p
                                                        class="mt-1
                                                               text-xs
                                                               leading-5
                                                               text-slate-500"
                                                    >
                                                        Доступ получат
                                                        только выбранные
                                                        сотрудники.
                                                    </p>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </fieldset>

                                <div
                                    v-if="
                                        form.access_type ===
                                        'private'
                                    "
                                >
                                    <div
                                        class="flex items-center
                                               justify-between"
                                    >
                                        <label
                                            class="text-sm
                                                   font-semibold
                                                   text-slate-800"
                                        >
                                            Пользователи
                                        </label>

                                        <span
                                            class="text-xs
                                                   text-slate-500"
                                        >
                                            Выбрано:
                                            {{ form.users.length }}
                                        </span>
                                    </div>

                                    <div class="relative mt-3">
                                        <svg
                                            class="pointer-events-none
                                                   absolute left-4
                                                   top-1/2 h-4 w-4
                                                   -translate-y-1/2
                                                   text-slate-400"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <circle
                                                cx="11"
                                                cy="11"
                                                r="8"
                                            />
                                            <path
                                                d="m21 21-4.35-4.35"
                                            />
                                        </svg>

                                        <input
                                            v-model="search"
                                            type="search"
                                            placeholder="Найти сотрудника..."
                                            class="h-11 w-full
                                                   rounded-xl
                                                   border-slate-200
                                                   pl-10 pr-4
                                                   text-sm
                                                   focus:border-indigo-500
                                                   focus:ring-indigo-500"
                                        >
                                    </div>

                                    <div
                                        class="mt-3 max-h-64
                                               space-y-2
                                               overflow-y-auto
                                               rounded-2xl
                                               border
                                               border-slate-200
                                               p-2"
                                    >
                                        <div
                                            v-for="member in filteredMembers"
                                            :key="member.id"
                                            class="flex items-center
                                                   gap-3 rounded-xl
                                                   p-2 transition
                                                   hover:bg-slate-50"
                                        >
                                            <button
                                                type="button"
                                                class="flex min-w-0
                                                       flex-1
                                                       items-center
                                                       gap-3 text-left"
                                                @click="
                                                    toggleUser(
                                                        member
                                                    )
                                                "
                                            >
                                                <span
                                                    class="flex h-9
                                                           w-9
                                                           shrink-0
                                                           items-center
                                                           justify-center
                                                           rounded-xl
                                                           bg-slate-900
                                                           text-xs
                                                           font-bold
                                                           text-white"
                                                >
                                                    {{
                                                        initials(
                                                            member.name
                                                        )
                                                    }}
                                                </span>

                                                <span class="min-w-0">
                                                    <span
                                                        class="block
                                                               truncate
                                                               text-sm
                                                               font-semibold
                                                               text-slate-900"
                                                    >
                                                        {{
                                                            member.name
                                                        }}
                                                    </span>

                                                    <span
                                                        class="block
                                                               truncate
                                                               text-xs
                                                               text-slate-500"
                                                    >
                                                        {{
                                                            member.email
                                                        }}
                                                    </span>
                                                </span>
                                            </button>

                                            <select
                                                v-if="
                                                    isSelected(
                                                        member.id
                                                    )
                                                "
                                                :value="
                                                    selectedRole(
                                                        member.id
                                                    )
                                                "
                                                class="h-9
                                                       rounded-lg
                                                       border-slate-200
                                                       py-0 pl-3
                                                       pr-8 text-xs
                                                       font-semibold
                                                       focus:border-indigo-500
                                                       focus:ring-indigo-500"
                                                @change="
                                                    changeRole(
                                                        member.id,
                                                        $event.target
                                                            .value
                                                    )
                                                "
                                            >
                                                <option
                                                    value="viewer"
                                                >
                                                    Просмотр
                                                </option>

                                                <option
                                                    value="editor"
                                                >
                                                    Редактор
                                                </option>

                                                <option
                                                    value="knowledge_manager"
                                                >
                                                    Менеджер
                                                </option>
                                            </select>

                                            <button
                                                type="button"
                                                class="flex h-6 w-6
                                                       shrink-0
                                                       items-center
                                                       justify-center
                                                       rounded-md border"
                                                :class="
                                                    isSelected(
                                                        member.id
                                                    )
                                                        ? 'border-indigo-600 bg-indigo-600 text-white'
                                                        : 'border-slate-300 bg-white text-transparent'
                                                "
                                                @click="
                                                    toggleUser(
                                                        member
                                                    )
                                                "
                                            >
                                                <svg
                                                    class="h-4 w-4"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="3"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="m5 12 4 4L19 6"
                                                    />
                                                </svg>
                                            </button>
                                        </div>

                                        <p
                                            v-if="
                                                !filteredMembers.length
                                            "
                                            class="px-4 py-8
                                                   text-center
                                                   text-sm
                                                   text-slate-500"
                                        >
                                            Пользователи не найдены
                                        </p>
                                    </div>

                                    <p
                                        v-if="form.errors.users"
                                        class="mt-2 text-sm
                                               text-red-600"
                                    >
                                        {{ form.errors.users }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl
                                           bg-amber-50 p-4"
                                >
                                    <div
                                        class="flex items-start
                                               gap-3"
                                    >
                                        <svg
                                            class="mt-0.5 h-5 w-5
                                                   shrink-0
                                                   text-amber-600"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M12 9v4m0 4h.01M10.3 3.7 2.6 17a2 2 0 0 0 1.73 3h15.34a2 2 0 0 0 1.73-3L13.7 3.7a2 2 0 0 0-3.4 0Z"
                                            />
                                        </svg>

                                        <p
                                            class="text-xs
                                                   leading-5
                                                   text-amber-800"
                                        >
                                            Права этой папки
                                            автоматически
                                            наследуются всеми
                                            вложенными папками.
                                            Вы станете
                                            администратором
                                            созданной папки.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <footer
                                class="flex items-center
                                       justify-end gap-3
                                       border-t
                                       border-slate-100
                                       bg-slate-50/70
                                       px-6 py-4"
                            >
                                <button
                                    type="button"
                                    class="rounded-xl
                                           border
                                           border-slate-200
                                           bg-white px-4
                                           py-2.5 text-sm
                                           font-semibold
                                           text-slate-700
                                           transition
                                           hover:bg-slate-50"
                                    @click="close"
                                >
                                    Отмена
                                </button>

                                <button
                                    type="submit"
                                    :disabled="
                                        form.processing ||
                                        !form.name.trim()
                                    "
                                    class="inline-flex
                                           items-center
                                           gap-2 rounded-xl
                                           bg-indigo-600
                                           px-5 py-2.5
                                           text-sm
                                           font-semibold
                                           text-white
                                           shadow-sm
                                           transition
                                           hover:bg-indigo-700
                                           disabled:cursor-not-allowed
                                           disabled:opacity-50"
                                >
                                    <svg
                                        v-if="form.processing"
                                        class="h-4 w-4
                                               animate-spin"
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
                                            d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"
                                        />
                                    </svg>

                                    Создать папку
                                </button>
                            </footer>
                        </form>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>