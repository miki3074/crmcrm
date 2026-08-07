<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { computed, watch } from 'vue';

const props = defineProps({
    klient: Object,
    companies: Array,
    projects: Array,
    tasks: Array,
    colleagues: Array,
});

// Инициализация формы данными из props.klient
const form = useForm({
    name: props.klient.name || '',
    status: props.klient.status || '',

    company_id: props.klient.company_id ?? null,
    project_id: props.klient.project_id ?? null,
    task_id: props.klient.task_id ?? null,

    segment: props.klient.segment || '',
    rating: props.klient.rating || '',
    phone: props.klient.phone || '',
    email: props.klient.email || '',
    inn: props.klient.inn || '',
    kpp: props.klient.kpp || '',
    ogrn: props.klient.ogrn || '',
    legal_address: props.klient.legal_address || '',
    actual_address: props.klient.actual_address || '',
    industry: props.klient.industry || '',

    messengers: {
        telegram: props.klient.messengers?.telegram || '',
        whatsapp: props.klient.messengers?.whatsapp || '',
    },

    contact_persons:
        props.klient.contact_persons?.length > 0
            ? props.klient.contact_persons
            : [{
                full_name: '',
                position: '',
                role: '',
                phone: '',
                email: '',
                is_primary: false,
            }],

    allowed_users:
        props.klient.allowed_users?.map(user => user.id) || [],
});

const isPersonalClient = computed({
    get: () => !form.company_id,
    set: (value) => {
        if (value) {
            form.company_id = null;
            form.project_id = null;
            form.task_id = null;
        }
    },
});

const filteredColleagues = computed(() => {
    if (!form.company_id) {
        return [];
    }

    return props.colleagues.filter(user => {
        return user.company_ids?.some(
            companyId => Number(companyId) === Number(form.company_id)
        );
    });
});

const filteredProjects = computed(() => {
    if (!form.company_id) {
        return [];
    }

    return props.projects.filter(
        project => Number(project.company_id) === Number(form.company_id)
    );
});

const filteredTasks = computed(() => {
    if (!form.project_id) {
        return [];
    }

    return props.tasks.filter(
        task => Number(task.project_id) === Number(form.project_id)
    );
});

watch(
    () => form.company_id,
    (newCompanyId, oldCompanyId) => {
        if (newCompanyId === oldCompanyId) {
            return;
        }

        const projectStillAvailable = filteredProjects.value.some(
            project => Number(project.id) === Number(form.project_id)
        );

        if (!projectStillAvailable) {
            form.project_id = null;
            form.task_id = null;
        }
    }
);

watch(
    () => form.project_id,
    (newProjectId, oldProjectId) => {
        if (newProjectId === oldProjectId) {
            return;
        }

        const taskStillAvailable = filteredTasks.value.some(
            task => Number(task.id) === Number(form.task_id)
        );

        if (!taskStillAvailable) {
            form.task_id = null;
        }
    }
);

watch(
    () => form.company_id,
    () => {
        const availableUserIds = filteredColleagues.value.map(
            user => Number(user.id)
        );

        form.allowed_users = form.allowed_users.filter(
            userId => availableUserIds.includes(Number(userId))
        );
    }
);

// Функции для динамических строк контактных лиц
const addContactPerson = () => {
    form.contact_persons.push({ full_name: '', position: '', role: '', phone: '', email: '', is_primary: false });
};

const removeContactPerson = (index) => {
    if (form.contact_persons.length > 1) {
        form.contact_persons.splice(index, 1);
    }
};

const submit = () => {
    // Используем PUT метод для обновления
    form.put(route('klients.update', props.klient.id), {
        onSuccess: () => {
            // Можно добавить уведомление
        },
    });
};

const allStatuses = ['Действующий', 'Потенциальный', 'Партнёр', 'Проблемный', 'Архивный'];
const allRatings = ['A', 'B', 'C', 'D'];
</script>

<template>
    <Head :title="`Редактирование: ${klient.name}`" />

    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <form @submit.prevent="submit" class="space-y-8">
                <!-- Шапка формы -->
                <div class="flex items-center justify-between border-b pb-5">
                    <div>
                        <Link :href="route('klients.show', klient.id)" class="text-sm text-indigo-600 hover:underline">← Отмена</Link>
                        <h1 class="text-2xl font-bold text-gray-900 mt-1">Редактирование карточки клиента</h1>
                    </div>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="bg-indigo-600 text-white px-6 py-2 rounded-lg font-bold shadow hover:bg-indigo-700 disabled:opacity-50"
                    >
                        {{ form.processing ? 'Сохранение...' : 'Сохранить изменения' }}
                    </button>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                    <!-- ЛЕВАЯ КОЛОНКА: Основная информация -->
                    <div class="lg:col-span-2 space-y-6">

                        <!-- Базовые данные -->
                        <div class="bg-white p-6 rounded-xl shadow-sm border">
                            <h2 class="text-sm font-bold text-gray-400 uppercase mb-4">Основные данные</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Название организации / ФИО *</label>
                                    <input v-model="form.name" type="text" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Статус</label>
                                    <select v-model="form.status" class="mt-1 block w-full border-gray-300 rounded-md">
                                        <option v-for="s in allStatuses" :key="s" :value="s">{{ s }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Рейтинг</label>
                                    <select v-model="form.rating" class="mt-1 block w-full border-gray-300 rounded-md">
                                        <option value="">Без рейтинга</option>
                                        <option v-for="r in allRatings" :key="r" :value="r">{{ r }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Контакты -->
                        <div class="bg-white p-6 rounded-xl shadow-sm border">
                            <h2 class="text-sm font-bold text-gray-400 uppercase mb-4">Связь</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Основной телефон</label>
                                    <input v-model="form.phone" type="text" class="mt-1 block w-full border-gray-300 rounded-md">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Email</label>
                                    <input v-model="form.email" type="email" class="mt-1 block w-full border-gray-300 rounded-md">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Telegram</label>
                                    <input v-model="form.messengers.telegram" type="text" class="mt-1 block w-full border-gray-300 rounded-md" placeholder="@username">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">WhatsApp</label>
                                    <input v-model="form.messengers.whatsapp" type="text" class="mt-1 block w-full border-gray-300 rounded-md">
                                </div>
                            </div>
                        </div>

                        <!-- Динамические контактные лица -->
                        <div class="bg-white p-6 rounded-xl shadow-sm border">
                            <div class="flex justify-between items-center mb-4">
                                <h2 class="text-sm font-bold text-gray-400 uppercase">Контактные лица</h2>
                                <button type="button" @click="addContactPerson" class="text-xs text-indigo-600 font-bold">+ Добавить</button>
                            </div>
                            <div v-for="(person, index) in form.contact_persons" :key="index" class="p-4 border rounded-lg mb-4 bg-gray-50 relative">
                                <button v-if="form.contact_persons.length > 1" @click="removeContactPerson(index)" type="button" class="absolute top-2 right-2 text-gray-400 hover:text-red-500">×</button>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                    <div class="md:col-span-2">
                                        <label class="text-[10px] uppercase text-gray-400">ФИО</label>
                                        <input v-model="person.full_name" type="text" class="w-full text-sm border-gray-300 rounded">
                                    </div>
                                    <div>
                                        <label class="text-[10px] uppercase text-gray-400">Роль</label>
                                        <input v-model="person.role" type="text" class="w-full text-sm border-gray-300 rounded" placeholder="Напр: ЛПР">
                                    </div>
                                    <div>
                                        <label class="text-[10px] uppercase text-gray-400">Должность</label>
                                        <input v-model="person.position" type="text" class="w-full text-sm border-gray-300 rounded">
                                    </div>
                                    <div>
                                        <label class="text-[10px] uppercase text-gray-400">Телефон</label>
                                        <input v-model="person.phone" type="text" class="w-full text-sm border-gray-300 rounded">
                                    </div>
                                    <div>
                                        <label class="text-[10px] uppercase text-gray-400 font-bold block">Главный контакт?</label>
                                        <input v-model="person.is_primary" type="checkbox" class="rounded text-indigo-600 mt-2">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Реквизиты -->
                        <div class="bg-white p-6 rounded-xl shadow-sm border">
                            <h2 class="text-sm font-bold text-gray-400 uppercase mb-4">Юридические данные</h2>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700">ИНН</label>
                                    <input v-model="form.inn" type="text" class="mt-1 block w-full border-gray-300 rounded-md text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700">КПП</label>
                                    <input v-model="form.kpp" type="text" class="mt-1 block w-full border-gray-300 rounded-md text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700">ОГРН</label>
                                    <input v-model="form.ogrn" type="text" class="mt-1 block w-full border-gray-300 rounded-md text-sm">
                                </div>
                                <div class="md:col-span-3">
                                    <label class="block text-xs font-medium text-gray-700">Юридический адрес</label>
                                    <textarea v-model="form.legal_address" rows="2" class="mt-1 block w-full border-gray-300 rounded-md text-sm"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ПРАВАЯ КОЛОНКА: Связи и Доступы -->
                    <div class="space-y-6">

                        <!-- Связи -->
<div class="bg-white p-6 rounded-xl shadow-sm border">
    <h2 class="text-sm font-bold text-gray-400 uppercase mb-4">
        Тип клиента и связи
    </h2>

    <div class="space-y-5">
        <!-- Переключение на личного клиента -->
        <label
            class="flex items-start gap-3 p-3 border rounded-lg cursor-pointer"
            :class="isPersonalClient
                ? 'border-indigo-500 bg-indigo-50'
                : 'border-gray-200'"
        >
            <input
                v-model="isPersonalClient"
                type="checkbox"
                class="mt-1 rounded text-indigo-600 focus:ring-indigo-500"
            >

            <span>
                <span class="block text-sm font-semibold text-gray-800">
                    Личный клиент
                </span>

                <span class="block text-xs text-gray-500 mt-1">
                    Клиент не будет привязан к компании, проекту и задаче.
                </span>
            </span>
        </label>

        <!-- Компания -->
        <div>
            <label class="block text-sm font-medium text-gray-700">
                Компания
            </label>

            <select
                v-model="form.company_id"
                class="mt-1 block w-full border-gray-300 rounded-md text-sm"
            >
                <option :value="null">
                    Личный клиент
                </option>

                <option
                    v-for="company in companies"
                    :key="company.id"
                    :value="company.id"
                >
                    {{ company.name }}
                </option>
            </select>

            <p
                v-if="form.errors.company_id"
                class="mt-1 text-xs text-red-600"
            >
                {{ form.errors.company_id }}
            </p>
        </div>

        <!-- Проект -->
        <div>
            <label class="block text-sm font-medium text-gray-700">
                Проект
            </label>

            <select
                v-model="form.project_id"
                :disabled="!form.company_id"
                class="mt-1 block w-full border-gray-300 rounded-md text-sm disabled:bg-gray-100 disabled:text-gray-400"
            >
                <option :value="null">
                    Без проекта
                </option>

                <option
                    v-for="project in filteredProjects"
                    :key="project.id"
                    :value="project.id"
                >
                    {{ project.name }}
                </option>
            </select>

            <p
                v-if="form.errors.project_id"
                class="mt-1 text-xs text-red-600"
            >
                {{ form.errors.project_id }}
            </p>
        </div>

        <!-- Задача -->
        <div>
            <label class="block text-sm font-medium text-gray-700">
                Задача
            </label>

            <select
                v-model="form.task_id"
                :disabled="!form.project_id"
                class="mt-1 block w-full border-gray-300 rounded-md text-sm disabled:bg-gray-100 disabled:text-gray-400"
            >
                <option :value="null">
                    Без задачи
                </option>

                <option
                    v-for="task in filteredTasks"
                    :key="task.id"
                    :value="task.id"
                >
                    {{ task.title }}
                </option>
            </select>

            <p
                v-if="form.errors.task_id"
                class="mt-1 text-xs text-red-600"
            >
                {{ form.errors.task_id }}
            </p>
        </div>
    </div>
</div>

                        <!-- Доступ коллег -->
                        <div class="bg-white p-6 rounded-xl shadow-sm border border-indigo-100">
    <h2 class="text-sm font-bold text-indigo-400 uppercase mb-4">
        Доступ для коллег
    </h2>

    <p class="text-[10px] text-gray-500 mb-3">
        Отображаются только коллеги из выбранной компании.
    </p>

    <div
        v-if="!form.company_id"
        class="p-3 text-sm text-gray-500 bg-gray-50 rounded-lg"
    >
        Для личного клиента доступ коллег недоступен.
    </div>

    <div
        v-else-if="filteredColleagues.length === 0"
        class="p-3 text-sm text-gray-500 bg-gray-50 rounded-lg"
    >
        В выбранной компании нет доступных коллег.
    </div>

    <div
        v-else
        class="space-y-2 max-h-60 overflow-y-auto p-2 bg-indigo-50/30 rounded"
    >
        <div
            v-for="user in filteredColleagues"
            :key="user.id"
            class="flex items-center"
        >
            <input
                :id="`user-${user.id}`"
                v-model="form.allowed_users"
                type="checkbox"
                :value="user.id"
                class="rounded text-indigo-600 focus:ring-indigo-500"
            >

            <label
                :for="`user-${user.id}`"
                class="ml-2 text-sm text-gray-700"
            >
                {{ user.name }}
            </label>
        </div>
    </div>

    <p
        v-if="form.errors.allowed_users"
        class="mt-2 text-xs text-red-600"
    >
        {{ form.errors.allowed_users }}
    </p>
</div>

                        <!-- Инфо о создании -->
                        <div class="bg-gray-100 p-4 rounded-lg">
                            <p class="text-[10px] text-gray-400 uppercase">Создатель карточки:</p>
                            <p class="text-sm font-bold text-gray-700">{{ klient.creator?.name }}</p>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>
</template>
