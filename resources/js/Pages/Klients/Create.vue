<script setup>
import { computed } from 'vue';
import { useForm, Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    companies: Array,
    projects: Array,
    tasks: Array,
    colleagues: Array // Добавьте эту строку
});

// Инициализация формы
const form = useForm({
    // Связи
    company_id: null,
    project_id: null,
    task_id: null,

    // Основная информация
    name: '',
    status: 'Потенциальный',
    segment: '',
    rating: 'C',

    // Быстрые контакты
    phone: '',
    email: '',
    messengers: {
        telegram: '',
        whatsapp: ''
    },

    // Реквизиты и детали
    inn: '',
    kpp: '',
    ogrn: '',
    legal_address: '',
    actual_address: '',
    industry: '',

    allowed_users: [], // Массив ID выбранных пользователей

    // Список контактных лиц
    contact_persons: [
        {
            full_name: '',
            position: '',
            role: '',
            phone: '',
            email: '',
            is_primary: true
        }
    ]
});

// --- Логика фильтрации выпадающих списков ---

const availableProjects = computed(() => {
    if (!form.company_id) return [];
    return props.projects.filter(p => p.company_id === form.company_id);
});

const availableTasks = computed(() => {
    if (!form.project_id) return [];
    return props.tasks.filter(t => t.project_id === form.project_id);
});

const filteredColleagues = computed(() => {
    // Если компания не выбрана - список пуст
    if (!form.company_id) return [];

    // Преобразуем выбранный ID в число, так как из <select> часто летит строка
    const selectedId = Number(form.company_id);

    return props.colleagues.filter(user => {
        // Проверяем, есть ли ID выбранной компании в массиве этого пользователя
        return user.company_ids.map(Number).includes(selectedId);
    });
});

// Сброс зависимых полей
const handleCompanyChange = () => {
    form.project_id = null;
    form.task_id = null;
    form.allowed_users = []; // Сбрасываем доступ, так как в другой компании другие люди
};

const handleProjectChange = () => {
    form.task_id = null;
};

// --- Управление контактными лицами ---

const addContact = () => {
    form.contact_persons.push({
        full_name: '',
        position: '',
        role: '',
        phone: '',
        email: '',
        is_primary: false
    });
};

const removeContact = (index) => {
    if (form.contact_persons.length > 1) {
        form.contact_persons.splice(index, 1);
    }
};

const setPrimaryContact = (index) => {
    form.contact_persons.forEach((p, i) => {
        p.is_primary = i === index;
    });
};

// Отправка формы
const submit = () => {
    form.post(route('klients.store'), {
        onSuccess: () => {
            // Можно добавить уведомление
        }
    });
};
</script>

<template>
    <Head title="Создать клиента" />

    <div class="py-12 bg-gray-100 min-h-screen">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Новая карточка клиента (Klient)</h1>
                <Link :href="route('klients.index')" class="text-gray-600 hover:underline text-sm">
                    Назад к списку
                </Link>
            </div>

            <form @submit.prevent="submit" class="space-y-6">

                <!-- БЛОК 1: Привязка (Компания -> Проект -> Задача) -->
                <div class="bg-white p-6 rounded-lg shadow-sm border-t-4 border-indigo-500">
                    <h2 class="text-lg font-semibold mb-4 text-indigo-700">Привязка к сущностям</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Компания</label>
                            <select v-model="form.company_id" @change="handleCompanyChange" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option :value="null">Личный клиент (без компании)</option>
                                <option v-for="c in companies" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Проект</label>
                            <select v-model="form.project_id" @change="handleProjectChange" :disabled="!form.company_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100">
                                <option :value="null">-- Не выбран --</option>
                                <option v-for="p in availableProjects" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Задача</label>
                            <select v-model="form.task_id" :disabled="!form.project_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100">
                                <option :value="null">-- Не выбрана --</option>
                                <option v-for="t in availableTasks" :key="t.id" :value="t.id">{{ t.title }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <pre class="text-[10px] text-red-500">{{ props.colleagues }}</pre>

                <div class="bg-white p-6 rounded-lg shadow-sm mt-6 border-l-4 border-yellow-500">
                    <h2 class="text-lg font-semibold mb-4 text-gray-700">Настройка доступа</h2>

                    <!-- Выводим только отфильтрованных коллег -->
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        <div v-for="user in filteredColleagues" :key="user.id" class="flex items-center p-2 border rounded hover:bg-gray-50">
                            <input
                                type="checkbox"
                                :id="'user-' + user.id"
                                :value="user.id"
                                v-model="form.allowed_users"
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            >
                            <label :for="'user-' + user.id" class="ml-2 text-sm text-gray-700 cursor-pointer">
                                {{ user.name }}
                            </label>
                        </div>
                    </div>

                    <!-- Сообщение если коллег нет или компания не выбрана -->
                    <div v-if="!form.company_id" class="text-sm text-gray-400 italic">
                        Выберите компанию, чтобы настроить доступ для сотрудников.
                    </div>
                    <div v-else-if="filteredColleagues.length === 0" class="text-sm text-gray-400 italic">
                        В этой компании нет других сотрудников.
                    </div>
                </div>

                <!-- БЛОК 2: Основная информация -->
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <h2 class="text-lg font-semibold mb-4 border-b pb-2">Основная информация</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Название предприятия или ФИО *</label>
                            <input v-model="form.name" type="text" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="ООО 'Вектор' или Иванов Иван Иванович">
                            <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Статус</label>
                            <select v-model="form.status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option>Действующий</option>
                                <option>Потенциальный</option>
                                <option>Партнёр</option>
                                <option>Проблемный</option>
                                <option>Архивный</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Сегмент</label>
                                <input v-model="form.segment" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Ключевой / Розница">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Рейтинг</label>
                                <select v-model="form.rating" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option>A</option>
                                    <option>B</option>
                                    <option>C</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6 bg-blue-50 p-4 rounded-md">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 font-bold italic text-blue-800">Телефон (основной)</label>
                            <input v-model="form.phone" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500" placeholder="+7...">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 font-bold italic text-blue-800">Email (основной)</label>
                            <input v-model="form.email" type="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500" placeholder="mail@example.com">
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs font-medium text-gray-500">Telegram</label>
                                <input v-model="form.messengers.telegram" type="text" class="mt-1 block w-full rounded-md border-gray-200 shadow-sm text-sm" placeholder="@username">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500">WhatsApp</label>
                                <input v-model="form.messengers.whatsapp" type="text" class="mt-1 block w-full rounded-md border-gray-200 shadow-sm text-sm" placeholder="Номер">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- БЛОК 3: Контактные лица -->
                <div class="bg-white p-6 rounded-lg shadow-sm border-l-4 border-green-500">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold">Контактные лица</h2>
                        <button type="button" @click="addContact" class="text-sm bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700 transition">
                            + Добавить лицо
                        </button>
                    </div>

                    <div v-for="(person, index) in form.contact_persons" :key="index" class="relative border rounded-lg p-4 mb-4 bg-gray-50 transition-all">
                        <button v-if="form.contact_persons.length > 1" type="button" @click="removeContact(index)" class="absolute top-2 right-2 text-red-400 hover:text-red-600">
                            ✕
                        </button>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="md:col-span-1">
                                <label class="block text-xs font-medium text-gray-500">ФИО</label>
                                <input v-model="person.full_name" type="text" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500">Должность</label>
                                <input v-model="person.position" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500">Роль (ЛПР, Бухгалтер и т.д.)</label>
                                <input v-model="person.role" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500">Личный телефон</label>
                                <input v-model="person.phone" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500">Личный Email</label>
                                <input v-model="person.email" type="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                            </div>
                            <div class="flex items-end pb-2">
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" :checked="person.is_primary" @change="setPrimaryContact(index)" class="text-indigo-600 focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-600">Основной контакт</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- БЛОК 4: Реквизиты и адреса -->
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <h2 class="text-lg font-semibold mb-4 border-b pb-2 text-gray-700">Детальная информация и реквизиты</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">ИНН</label>
                            <input v-model="form.inn" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">КПП</label>
                            <input v-model="form.kpp" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">ОГРН</label>
                            <input v-model="form.ogrn" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500">
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">Юридический адрес</label>
                            <input v-model="form.legal_address" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500">
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">Фактический адрес</label>
                            <input v-model="form.actual_address" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500">
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">Сфера деятельности</label>
                            <input v-model="form.industry" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500" placeholder="Например: Оптовая торговля стройматериалами">
                        </div>
                    </div>
                </div>

                <!-- Кнопки действий -->
                <div class="flex items-center justify-end gap-4 bg-white p-6 rounded-lg shadow-sm">
                    <Link :href="route('klients.index')" class="text-sm font-medium text-gray-600 hover:text-gray-800 transition">
                        Отмена
                    </Link>
                    <button type="submit" :disabled="form.processing" class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-3 px-10 text-sm font-bold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all">
                        <span v-if="form.processing">Сохранение...</span>
                        <span v-else>СОЗДАТЬ КЛИЕНТА</span>
                    </button>
                </div>

            </form>
        </div>
    </div>
</template>

<style scoped>
/* Дополнительные стили если нужно */
input:focus, select:focus, textarea:focus {
    outline: none;
    border-color: #6366f1;
    ring-width: 1px;
}
</style>
