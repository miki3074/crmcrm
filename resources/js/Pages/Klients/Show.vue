<script setup>
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

import CreateTaskDrawer from '../AAA/Components/Klients/CreateTaskDrawer.vue';
import ApplicationLogo from "@/Components/ApplicationLogo.vue";

const props = defineProps({
    klient: Object,
    availableResponsibles: Array,
    projects: Array, // список всех проектов компании
    allTasks: Array,  // список всех задач компании
});

const isEditingConnections = ref(false);

const connectionsForm = useForm({
    project_id: props.klient.project_id,
    task_id: props.klient.task_id,
    // Мы передаем только те поля, которые меняем, но метод update в контроллере
    // должен уметь обрабатывать частичные данные или мы передаем весь объект
    name: props.klient.name,
    status: props.klient.status,
});

const saveConnections = () => {
    connectionsForm.put(route('klients.update', props.klient.id), {
        preserveScroll: true,
        onSuccess: () => isEditingConnections.value = false,
    });
};

const page = usePage();
// Получаем ID текущего пользователя из глобальных пропсов Inertia
const authId = computed(() => page.props.auth.user.id);

// Форма для загрузки файла
const fileForm = useForm({
    file: null,
});

const uploadFile = () => {
    if (!fileForm.file) return;

    fileForm.post(route('klient-files.store', props.klient.id), {
        onSuccess: () => {
            fileForm.reset();
            // Используем более элегантное уведомление (можно заменить на toast)
            alert('Файл успешно загружен');
        },
    });
};

const deleteFile = (fileId) => {
    if (confirm('Вы уверены, что хотите удалить этот файл?')) {
        router.delete(route('klient-files.destroy', fileId));
    }
};

// Вспомогательная функция для размера файла
const formatFileSize = (bytes) => {
    if (!bytes) return '---';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

// Функция для красивого отображения статусов (обновленные цвета)
const statusClasses = (status) => {
    const map = {
        'Действующий': 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'Потенциальный': 'bg-blue-50 text-blue-700 border-blue-200',
        'Партнёр': 'bg-purple-50 text-purple-700 border-purple-200',
        'Проблемный': 'bg-rose-50 text-rose-700 border-rose-200',
        'Архивный': 'bg-slate-100 text-slate-600 border-slate-300',
    };
    return map[status] || 'bg-gray-100 text-gray-800';
};

const dealStatusClasses = (status) => {
    const map = {
        'Первичный контакт': 'bg-gray-100 text-gray-700',
        'Переговоры': 'bg-blue-100 text-blue-800',
        'КП отправлено': 'bg-indigo-100 text-indigo-800',
        'Согласование договора': 'bg-purple-100 text-purple-800',
        'Успешно': 'bg-emerald-100 text-emerald-800',
        'Отказ': 'bg-rose-100 text-rose-800',
    };
    return map[status] || 'bg-gray-50 text-gray-500';
};

const isTaskDrawerOpen = ref(false);

// Фильтр: Активные задачи (все, кроме 'completed' и 'cancelled')
const activeTasks = computed(() => {
    return props.klient.tasks?.filter(task => task.status !== 'completed' && task.status !== 'cancelled') || [];
});

// Фильтр: Последние взаимодействия (только 'completed')
const completedTasks = computed(() => {
    return props.klient.tasks?.filter(task => task.status === 'completed') || [];
});

// Функция для цветов приоритета
const priorityBadge = (priority) => {
    if (priority === 'high') return 'bg-rose-100 text-rose-800 border-rose-200';
    if (priority === 'medium') return 'bg-amber-100 text-amber-800 border-amber-200';
    return 'bg-sky-100 text-sky-800 border-sky-200';
};

// Иконка для типа задачи
const taskTypeIcon = (type) => {
    const icons = {
        'call': 'fa-phone',
        'meeting': 'fa-handshake',
        'email': 'fa-envelope',
        'task': 'fa-check-circle',
    };
    return icons[type] || 'fa-tasks';
};


// Текущая вкладка задач: 'my' (Мои) или 'all' (Все)
const taskTab = ref('my');

// Фильтр: Мои активные задачи (Я создатель ИЛИ Я ответственный)
const myTasks = computed(() => {
    return activeTasks.value.filter(task =>
        task.creator_id === authId.value || task.responsible_id === authId.value
    );
});

// Фильтр: Все остальные активные задачи (где я не участвую)
const otherTasks = computed(() => {
    return activeTasks.value.filter(task =>
        task.creator_id !== authId.value && task.responsible_id !== authId.value
    );
});

const dealTab = ref('my');

// Функция проверки: является ли пользователь создателем или ответственным в сделке
const isMyDeal = (deal) => {
    const isCreator = deal.creator_id === authId.value;
    // Проверяем, есть ли мой ID в массиве ответственных (responsibles)
    const isResponsible = deal.responsibles?.some(user => user.id === authId.value);
    return isCreator || isResponsible;
};

// Фильтр: Мои сделки
const myDeals = computed(() => {
    return props.klient.deals?.filter(deal => isMyDeal(deal)) || [];
});

// Фильтр: Все остальные сделки (где я не участвую)
const otherDeals = computed(() => {
    return props.klient.deals?.filter(deal => !isMyDeal(deal)) || [];
});


</script>

<template>
    <Head :title="`Клиент: ${klient.name}`" />

    <div class="min-h-screen bg-slate-50 py-8">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div style="margin-bottom: 1%">
            <Link :href="route('dashboard')" class="flex-shrink-0 transition-transform duration-300 hover:scale-105" >
                <ApplicationLogo class="block h-8 w-auto fill-current " />
            </Link>
        </div>
            <!-- Хлебные крошки (обновленный стиль) -->
            <div class="flex justify-between items-center mb-6">
                <nav class="flex items-center text-sm">

                    <Link :href="route('klients.index')" class="text-slate-500 hover:text-indigo-600 transition-colors">
                         Клиенты
                    </Link>
                    <span class="mx-2 text-slate-300">/</span>
                    <span class="text-slate-900 font-medium">Карточка клиента</span>
                </nav>
                <div class="flex space-x-3">
                    <!-- Кнопка видна только если ID текущего юзера совпадает с создателем клиента -->
                    <Link
                        v-if="klient.user_id === authId"
                        :href="route('klients.edit', klient.id)"
                        class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-50 text-sm font-medium transition"
                    >
                        Редактировать
                    </Link>
                </div>
            </div>

            <!-- ========== ШАПКА КАРТОЧКИ (НОВЫЙ ДИЗАЙН) ========== -->
            <div class="bg-white rounded-3xl shadow-lg border border-slate-100 overflow-hidden mb-6">
                <div class="p-8 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="flex items-center gap-6">
                        <!-- Аватар с градиентом (как в примере) -->
                        <div class="w-24 h-24 bg-gradient-to-br from-indigo-600 to-blue-500 rounded-2xl flex items-center justify-center text-white text-3xl font-bold shadow-lg shadow-indigo-200">
                            {{ klient.name.charAt(0).toUpperCase() }}
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-slate-800">{{ klient.name }}</h1>
                            <div class="flex items-center mt-3 flex-wrap gap-2">
                                <span :class="['px-3 py-1.5 rounded-full text-xs font-bold border', statusClasses(klient.status)]">
                                    <i class="fas fa-circle mr-1 text-[0.5rem] align-middle"></i> {{ klient.status }}
                                </span>
                                <span class="bg-amber-50 text-amber-700 px-3 py-1.5 rounded-full text-xs font-bold border border-amber-200">
                                    <i class="fas fa-star mr-1 text-[0.7rem]"></i> Рейтинг: {{ klient.rating }}
                                </span>
                                <span class="bg-slate-100 text-slate-600 px-3 py-1.5 rounded-full text-xs font-medium border border-slate-200">
                                    {{ klient.segment || 'Без сегмента' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Быстрые контакты (как чипсы) -->
                    <div class="flex gap-3 flex-wrap">
                        <div v-if="klient.phone" class="flex items-center gap-2 bg-slate-50 px-4 py-2 rounded-full border border-slate-200">
                            <i class="fas fa-phone-alt text-indigo-500 text-sm"></i>
                            <a :href="`tel:${klient.phone}`" class="text-sm font-medium text-slate-700 hover:text-indigo-600">{{ klient.phone }}</a>
                        </div>
                        <div v-if="klient.email" class="flex items-center gap-2 bg-slate-50 px-4 py-2 rounded-full border border-slate-200">
                            <i class="fas fa-envelope text-indigo-500 text-sm"></i>
                            <a :href="`mailto:${klient.email}`" class="text-sm font-medium text-slate-700 hover:text-indigo-600">{{ klient.email }}</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Основной грид: 2 колонки (левая широкая, правая узкая) -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- ========== ЛЕВАЯ КОЛОНКА (2/3) ========== -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- БЛОК: Документы и файлы (обновленный дизайн) -->
                    <div class="bg-white rounded-2xl shadow-md border border-slate-100 p-6">
                        <div class="flex justify-between items-center mb-5">
                            <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                                <i class="fas fa-folder-open text-indigo-500"></i>
                                Документы и файлы
                            </h2>

                            <!-- Форма загрузки -->
                            <form @submit.prevent="uploadFile" class="flex items-center gap-2">
                                <label class="cursor-pointer">
                                    <input
                                        type="file"
                                        @input="fileForm.file = $event.target.files[0]"
                                        class="hidden"
                                    />
                                    <span class="bg-slate-100 hover:bg-slate-200 text-slate-600 px-4 py-2 rounded-xl text-sm font-medium transition flex items-center gap-2">
                                        <i class="fas fa-paperclip"></i> Выбрать файл
                                    </span>
                                </label>
                                <button
                                    type="submit"
                                    :disabled="fileForm.processing || !fileForm.file"
                                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-xl text-sm font-bold disabled:opacity-50 disabled:cursor-not-allowed transition shadow-md shadow-indigo-200 flex items-center gap-2"
                                >
                                    <i class="fas fa-upload"></i> {{ fileForm.processing ? '...' : 'Загрузить' }}
                                </button>
                            </form>
                        </div>

                        <!-- Список файлов в виде карточек (более современно, чем таблица) -->
                        <div v-if="klient.files?.length" class="space-y-2">
                            <div v-for="file in klient.files" :key="file.id" class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-100 hover:bg-white hover:shadow-sm transition group">
                                <div class="flex items-center gap-3 overflow-hidden">
                                    <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center text-indigo-600">
                                        <i class="fas fa-file-alt"></i>
                                    </div>
                                    <div class="overflow-hidden">
                                        <div class="font-medium text-slate-800 truncate max-w-[200px] sm:max-w-xs" :title="file.original_name">
                                            {{ file.original_name }}
                                        </div>
                                        <div class="flex items-center gap-3 text-xs text-slate-500 mt-1">
                                            <span><i class="far fa-circle"></i> {{ formatFileSize(file.file_size) }}</span>
                                            <span><i class="far fa-user"></i> {{ file.user?.name || 'Система' }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <a
                                        :href="route('klient-files.download', file.id)"
                                        class="w-8 h-8 rounded-full bg-white border border-slate-200 flex items-center justify-center text-indigo-600 hover:bg-indigo-50 transition"
                                        title="Скачать"
                                    >
                                        <i class="fas fa-download text-sm"></i>
                                    </a>
                                    <button
                                        v-if="file.user_id === authId"
                                        @click="deleteFile(file.id)"
                                        class="w-8 h-8 rounded-full bg-white border border-slate-200 flex items-center justify-center text-rose-500 hover:bg-rose-50 transition"
                                        title="Удалить"
                                    >
                                        <i class="fas fa-trash-alt text-sm"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div v-else class="py-12 text-center border-2 border-dashed border-slate-200 rounded-xl">
                            <i class="fas fa-cloud-upload-alt text-4xl text-slate-300 mb-3"></i>
                            <p class="text-slate-400 text-sm">Файлы еще не загружены</p>
                        </div>
                    </div>

                    <!-- БЛОК: Контактные лица (карточки вместо таблицы) -->
                    <div class="bg-white rounded-2xl shadow-md border border-slate-100 p-6">
                        <h2 class="text-lg font-bold text-slate-800 mb-5 flex items-center gap-2">
                            <i class="fas fa-user-friends text-indigo-500"></i>
                            Контактные лица
                        </h2>

                        <div v-if="klient.contact_persons?.length" class="space-y-3">
                            <div v-for="person in klient.contact_persons" :key="person.id" class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border" :class="person.is_primary ? 'border-amber-200 bg-amber-50/30' : 'border-slate-100'">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center text-indigo-700 font-bold text-lg">
                                        {{ person.full_name.charAt(0) }}
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-slate-800">{{ person.full_name }}</span>
                                            <span v-if="person.is_primary" class="text-[0.6rem] bg-amber-200 text-amber-800 px-2 py-0.5 rounded-full font-bold">ОСНОВНОЙ</span>
                                        </div>
                                        <div class="text-sm text-slate-500">{{ person.position }}</div>
                                        <div class="flex items-center gap-3 mt-1 text-xs">
                                            <span v-if="person.phone" class="text-slate-600"><i class="fas fa-phone-alt mr-1 text-indigo-400"></i>{{ person.phone }}</span>
                                            <span v-if="person.email" class="text-slate-600"><i class="fas fa-envelope mr-1 text-indigo-400"></i>{{ person.email }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-xs text-slate-400">{{ person.role }}</div>
                            </div>
                        </div>
                        <div v-else class="py-8 text-center text-slate-400 text-sm border border-dashed rounded-xl">
                            Контактные лица не добавлены
                        </div>
                    </div>

                    <!-- БЛОК: Реквизиты и адреса (сетка) -->
                    <div class="bg-white rounded-2xl shadow-md border border-slate-100 p-6">
                        <h2 class="text-lg font-bold text-slate-800 mb-5 flex items-center gap-2">
                            <i class="fas fa-building text-indigo-500"></i>
                            Детальная информация
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="bg-slate-50 p-5 rounded-xl">
                                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Реквизиты</h3>
                                <dl class="space-y-2">
                                    <div class="flex justify-between border-b border-dashed border-slate-200 pb-2">
                                        <dt class="text-sm text-slate-500">ИНН</dt>
                                        <dd class="text-sm font-mono font-medium text-slate-800">{{ klient.inn || '—' }}</dd>
                                    </div>
                                    <div class="flex justify-between border-b border-dashed border-slate-200 pb-2">
                                        <dt class="text-sm text-slate-500">КПП</dt>
                                        <dd class="text-sm font-mono font-medium text-slate-800">{{ klient.kpp || '—' }}</dd>
                                    </div>
                                    <div class="flex justify-between border-b border-dashed border-slate-200 pb-2">
                                        <dt class="text-sm text-slate-500">ОГРН</dt>
                                        <dd class="text-sm font-mono font-medium text-slate-800">{{ klient.ogrn || '—' }}</dd>
                                    </div>
                                </dl>
                            </div>

                            <div class="bg-slate-50 p-5 rounded-xl">
                                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Деятельность</h3>
                                <p class="text-sm text-slate-700">{{ klient.industry || 'Сфера не указана' }}</p>
                            </div>

                            <div class="md:col-span-2 bg-slate-50 p-5 rounded-xl">
                                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Юридический адрес</h3>
                                <p class="text-sm text-slate-700 flex items-start gap-2">
                                    <i class="fas fa-map-marker-alt text-indigo-400 mt-0.5"></i>
                                    {{ klient.legal_address || 'Не указан' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- БЛОК: Сделки (обновленный) -->
                    <div class="bg-white shadow rounded-lg p-6 mt-6 border-t-4 border-indigo-500">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-lg font-bold text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                Сделки
                            </h2>

                            <!-- ПЕРЕКЛЮЧАТЕЛЬ ВКЛАДОК СДЕЛКИ -->
                            <div class="flex bg-slate-100 p-1 rounded-lg">
                                <button
                                    @click="dealTab = 'my'"
                                    :class="['px-3 py-1 text-[10px] font-bold rounded-md transition', dealTab === 'my' ? 'bg-white shadow text-indigo-600' : 'text-slate-500']"
                                >
                                    МОИ ({{ myDeals.length }})
                                </button>
                                <button
                                    @click="dealTab = 'all'"
                                    :class="['px-3 py-1 text-[10px] font-bold rounded-md transition', dealTab === 'all' ? 'bg-white shadow text-indigo-600' : 'text-slate-500']"
                                >
                                    ВСЕ ({{ klient.deals?.length || 0 }})
                                </button>
                            </div>
                        </div>

                        <!-- КОНТЕНТ ВКЛАДОК -->
                        <div class="space-y-3">
                            <!-- Определяем какой массив показывать -->
                            <template v-for="deal in (dealTab === 'my' ? myDeals : klient.deals)" :key="deal.id">

                                <!-- ВАРИАНТ 1: МОЯ СДЕЛКА (Кликабельная) -->
                                <Link
                                    v-if="isMyDeal(deal)"
                                    :href="route('klient-deals.show', deal.id)"
                                    class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-100 hover:border-indigo-300 hover:bg-white hover:shadow-md transition group"
                                >
                                    <div>
                                        <div class="font-bold text-slate-800 group-hover:text-indigo-700 flex items-center">
                                            {{ deal.name }}
                                            <span v-if="deal.creator_id === authId" class="ml-2 text-[8px] bg-indigo-100 text-indigo-600 px-1 rounded">АВТОР</span>
                                        </div>
                                        <div class="text-xs text-slate-400 mt-1">
                                            <i class="fas fa-ruble-sign mr-1"></i> {{ new Intl.NumberFormat('ru-RU').format(deal.total_amount) }} ₽
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                    <span :class="['text-[10px] px-2 py-1 rounded-full font-bold uppercase', dealStatusClasses(deal.status)]">
                        {{ deal.status }}
                    </span>
                                        <i class="fas fa-chevron-right text-slate-300 group-hover:text-indigo-500"></i>
                                    </div>
                                </Link>

                                <!-- ВАРИАНТ 2: ЧУЖАЯ СДЕЛКА (Недоступна) -->
                                <div
                                    v-else
                                    class="flex items-center justify-between p-4 bg-slate-50/50 rounded-xl border border-dashed border-slate-200 opacity-60 grayscale-[0.5] cursor-not-allowed"
                                >
                                    <div>
                                        <div class="font-bold text-slate-500 flex items-center">
                                            <i class="fas fa-lock mr-2 text-[10px]"></i>
                                            {{ deal.name }}
                                        </div>
                                        <div class="text-[10px] text-slate-400 mt-1">Сумма скрыта (только чтение)</div>
                                    </div>
                                    <div>
                    <span class="text-[9px] px-2 py-1 rounded-full bg-slate-100 text-slate-400 font-bold uppercase">
                        {{ deal.status }}
                    </span>
                                    </div>
                                </div>
                            </template>

                            <!-- Если список пуст -->
                            <div v-if="(dealTab === 'my' && !myDeals.length) || (dealTab === 'all' && !klient.deals?.length)" class="text-center py-8">
                                <p class="text-xs text-slate-400 italic">Сделок не найдено</p>
                            </div>
                        </div>

                        <!-- Кнопка создания -->
                        <Link
                            :href="route('klient-deals.create', klient.id)"
                            class="mt-4 block w-full text-center py-2 border-2 border-dashed border-slate-200 rounded-xl text-slate-400 text-xs font-bold hover:border-indigo-300 hover:text-indigo-500 transition"
                        >
                            + Создать новую сделку
                        </Link>
                    </div>

                    <!-- БЛОК ЗАДАЧ И ВЗАИМОДЕЙСТВИЙ (2 колонки внутри левой) -->
                    <div class="flex justify-end mb-4">
                        <button
                            @click="isTaskDrawerOpen = true"
                            class="inline-flex items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-sm shadow-md shadow-indigo-200 transition-all hover:shadow-lg"
                        >
                            <i class="fas fa-plus mr-2"></i> Поставить задачу
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Активные задачи -->
                        <div class="bg-white shadow rounded-lg p-6 mt-6">
                            <div class="flex justify-between items-center mb-4">
                                <h2 class="text-lg font-bold text-gray-900 flex items-center">
                                    <span class="flex h-3 w-3 rounded-full bg-green-500 mr-2"></span>
                                    Задачи
                                </h2>

                                <!-- ПЕРЕКЛЮЧАТЕЛЬ ВКЛАДОК -->
                                <div class="flex bg-slate-100 p-1 rounded-lg">
                                    <button
                                        @click="taskTab = 'my'"
                                        :class="['px-3 py-1 text-[10px] font-bold rounded-md transition', taskTab === 'my' ? 'bg-white shadow text-indigo-600' : 'text-slate-500']"
                                    >
                                        МОИ ({{ myTasks.length }})
                                    </button>
                                    <button
                                        @click="taskTab = 'all'"
                                        :class="['px-3 py-1 text-[10px] font-bold rounded-md transition', taskTab === 'all' ? 'bg-white shadow text-indigo-600' : 'text-slate-500']"
                                    >
                                        ВСЕ ({{ activeTasks.length }})
                                    </button>
                                </div>
                            </div>

                            <!-- Список задач -->
                            <div v-if="taskTab === 'my'">
                                <div v-if="myTasks.length" class="space-y-3">
                                    <!-- КЛИКАБЕЛЬНЫЕ ЗАДАЧИ (Link) -->
                                    <Link
                                        v-for="task in myTasks"
                                        :key="task.id"
                                        :href="route('klient-tasks.show', task.id)"
                                        class="block p-4 bg-slate-50 rounded-xl border-l-4 hover:bg-white hover:shadow-sm transition border-l-indigo-400"
                                        :class="task.priority === 'high' ? 'border-l-rose-500' : 'border-l-indigo-400'"
                                    >
                                        <!-- Содержимое карточки (заголовок, приоритет, описание и т.д.) -->
                                        <div class="flex justify-between items-start">
                                            <h4 class="font-bold text-sm text-slate-800">{{ task.title }}</h4>
                                            <span :class="['text-[0.6rem] px-2 py-1 rounded-full font-bold', priorityBadge(task.priority)]">
                        {{ task.priority }}
                    </span>
                                        </div>
                                        <p class="text-xs text-slate-500 mt-1">{{ task.description }}</p>
                                        <div class="mt-3 flex justify-between items-center text-xs text-slate-400">
                                            <span><i class="far fa-user mr-1"></i> {{ task.responsible?.name }}</span>
                                            <span><i class="far fa-calendar-alt mr-1"></i> {{ task.deadline }}</span>
                                        </div>
                                    </Link>
                                </div>
                                <div v-else class="text-center py-10 text-slate-400 text-xs italic">У вас нет активных задач</div>
                            </div>

                            <div v-if="taskTab === 'all'">
                                <div v-if="activeTasks.length" class="space-y-3">
                                    <template v-for="task in activeTasks" :key="task.id">

                                        <!-- ЕСЛИ МОЯ ЗАДАЧА - ДЕЛАЕМ ССЫЛКОЙ -->
                                        <Link
                                            v-if="task.creator_id === authId || task.responsible_id === authId"
                                            :href="route('klient-tasks.show', task.id)"
                                            class="block p-4 bg-slate-50 rounded-xl border-l-4 hover:bg-white transition border-l-indigo-400"
                                        >
                                            <div class="flex justify-between items-start">
                                                <h4 class="font-bold text-sm text-indigo-600">{{ task.title }} <span class="text-[10px] font-normal text-slate-400">(моя)</span></h4>
                                            </div>
                                            <!-- ... остальной контент задачи ... -->
                                        </Link>

                                        <!-- ЕСЛИ ЧУЖАЯ - ДЕЛАЕМ ОБЫЧНЫМ DIV (НЕКЛИКАБЕЛЬНО) -->
                                        <div
                                            v-else
                                            class="block p-4 bg-slate-50/50 rounded-xl border-l-4 border-l-slate-300 opacity-60 grayscale-[0.5]"
                                        >
                                            <div class="flex justify-between items-start">
                                                <div class="flex items-center gap-2">
                                                    <i class="fas fa-lock text-[10px] text-slate-400"></i>
                                                    <h4 class="font-bold text-sm text-slate-500">{{ task.title }}</h4>
                                                </div>
                                                <span class="text-[0.6rem] px-2 py-1 rounded-full bg-slate-200 text-slate-500 font-bold uppercase">Только просмотр</span>
                                            </div>
                                            <p class="text-xs text-slate-400 mt-1 line-clamp-1">{{ task.description }}</p>
                                            <div class="mt-3 flex justify-between items-center text-[10px] text-slate-400">
                                                <span>Ответственный: {{ task.responsible?.name }}</span>
                                            </div>
                                        </div>

                                    </template>
                                </div>
                                <div v-else class="text-center py-10 text-slate-400 text-xs italic">Задач нет</div>
                            </div>
                        </div>

                        <!-- Последние взаимодействия -->
                        <div class="bg-white rounded-2xl shadow-md border border-slate-100 p-5">
                            <h3 class="font-bold text-slate-800 mb-4 flex items-center gap-2">
                                <i class="fas fa-history text-indigo-400"></i>
                                Последние взаимодействия
                            </h3>

                            <div v-if="completedTasks.length" class="space-y-3">
                                <Link
                                    v-for="task in completedTasks"
                                    :key="task.id"
                                    :href="route('klient-tasks.show', task.id)"
                                    class="flex items-start gap-3 p-3 bg-slate-50 rounded-xl hover:bg-white transition border border-slate-100"
                                >
                                    <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 shrink-0">
                                        <i class="fas fa-check text-sm"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex justify-between">
                                            <h4 class="font-bold text-sm text-slate-700 truncate">{{ task.title }}</h4>
                                            <span class="text-[0.6rem] text-slate-400 font-mono whitespace-nowrap ml-2">
                                                {{ new Date(task.updated_at).toLocaleDateString() }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-500 truncate">{{ task.description }}</p>
                                    </div>
                                </Link>
                            </div>
                            <div v-else class="py-8 text-center text-slate-400 text-sm border border-dashed rounded-xl">
                                История взаимодействий пуста
                            </div>
                        </div>
                    </div>

                </div> <!-- Конец левой колонки -->

                <!-- ========== ПРАВАЯ КОЛОНКА (1/3) ========== -->
                <div class="space-y-6">
                    <!-- Карточка связей -->
                    <div class="bg-white rounded-2xl shadow-md border border-slate-100 p-6">
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Связи</h3>

                        <div class="space-y-4">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                                <span class="text-sm text-slate-500"><i class="fas fa-building mr-2 text-indigo-400"></i>Компания</span>
                                <span class="text-sm font-semibold text-slate-800">{{ klient.company?.name || 'Личный клиент' }}</span>
                            </div>
                            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                                <span class="text-sm text-slate-500"><i class="fas fa-project-diagram mr-2 text-indigo-400"></i>Проект</span>
                                <span class="text-sm font-semibold text-slate-800">{{ klient.project?.name || 'Без проекта' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-slate-500"><i class="fas fa-tasks mr-2 text-indigo-400"></i>Задача</span>
                                <span class="text-sm font-semibold text-slate-800">{{ klient.task?.title || 'Без задачи' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Мессенджеры (стильный блок) -->
                    <div v-if="klient.messengers?.telegram || klient.messengers?.whatsapp" class="bg-gradient-to-br from-indigo-600 to-indigo-800 rounded-2xl shadow-lg p-6 text-white">
                        <h3 class="text-sm font-bold uppercase opacity-80 mb-4 tracking-wider">Мессенджеры</h3>
                        <div class="space-y-4">
                            <div v-if="klient.messengers.telegram" class="flex items-center gap-3 bg-white/10 p-3 rounded-xl backdrop-blur-sm">
                                <i class="fab fa-telegram-plane text-xl"></i>
                                <span class="text-sm font-medium">{{ klient.messengers.telegram }}</span>
                            </div>
                            <div v-if="klient.messengers.whatsapp" class="flex items-center gap-3 bg-white/10 p-3 rounded-xl backdrop-blur-sm">
                                <i class="fab fa-whatsapp text-xl"></i>
                                <span class="text-sm font-medium">{{ klient.messengers.whatsapp }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Системная информация -->
                    <div class="bg-white rounded-2xl shadow-md border border-slate-100 p-6">
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Системная информация</h3>
                        <div class="space-y-4">
                            <!-- Создатель -->
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600">
                                    <i class="fas fa-user-plus text-xs"></i>
                                </div>
                                <div>
                                    <div class="text-xs text-slate-400">Создал</div>
                                    <div class="text-sm font-medium text-slate-700">{{ klient.creator?.name }}</div>
                                </div>
                            </div>

                            <!-- Дата создания -->
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-500">
                                    <i class="fas fa-calendar-alt text-xs"></i>
                                </div>
                                <div>
                                    <div class="text-xs text-slate-400">Дата создания</div>
                                    <div class="text-sm font-medium text-slate-700">{{ new Date(klient.created_at).toLocaleString() }}</div>
                                </div>
                            </div>

                            <!-- КТО ИМЕЕТ ДОСТУП -->
                            <!-- Заменили allowedUsers на allowed_users -->
                            <div v-if="klient.allowed_users?.length" class="pt-3 border-t border-slate-50">
                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600">
                                        <i class="fas fa-users text-xs"></i>
                                    </div>
                                    <div>
                                        <!-- Заменили allowedUsers на allowed_users -->
                                        <div class="text-xs text-slate-400 mb-1">Доступ предоставлен ({{ klient.allowed_users.length }})</div>
                                        <div class="flex flex-wrap gap-1">
                                            <!-- Заменили allowedUsers на allowed_users -->

                                            <span
                                                v-for="user in klient.allowed_users"
                                                :key="user.id"
                                                class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-100"
                                            >
                    {{ user.name }}
                </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Если доступ есть только у создателя -->
                            <div v-else class="pt-3 border-t border-slate-50 flex items-center gap-3 text-slate-400 italic">
                                <i class="fas fa-lock text-[10px] ml-2"></i>
                                <span class="text-[10px]">Приватная карточка</span>
                            </div>

                            <!-- Если доступ есть только у создателя -->
                            <div v-else class="pt-3 border-t border-slate-50 flex items-center gap-3 text-slate-400 italic">
                                <i class="fas fa-lock text-[10px] ml-2"></i>
                                <span class="text-[10px]">Приватная карточка</span>
                            </div>
                        </div>
                    </div>
                </div> <!-- Конец правой колонки -->
            </div> <!-- Конец грида -->
        </div> <!-- Конец контейнера -->
    </div> <!-- Конец фона -->

    <!-- Боковой компонент создания задачи -->
    <CreateTaskDrawer
        :isOpen="isTaskDrawerOpen"
        :klientId="klient.id"
        :responsibles="availableResponsibles"
        @close="isTaskDrawerOpen = false"
    />
</template>
