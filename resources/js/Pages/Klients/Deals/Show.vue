<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
    deal: Object
});

const { props: pageProps } = usePage();
const currentUser = computed(() => pageProps.auth.user);
// Проверка прав на загрузку файлов
const canUploadFiles = computed(() => {
    const userId = currentUser.value?.id;
    const deal = props.deal;

    // Создатель сделки
    if (deal.creator_id === userId) return true;

    // Член команды сделки
    if (deal.responsibles?.some(r => r.id === userId)) return true;

    // Владелец компании клиента
    if (deal.klient?.company?.user_id === userId) return true;

    return false;
});

// Проверка прав на удаление файла
const canDeleteFile = (file) => {
    const userId = currentUser.value?.id;

    // Загрузил текущий пользователь
    if (file.user_id === userId) return true;



    return false;
};

// Состояние для загрузки
const showUploadModal = ref(false);
const uploading = ref(false);
const uploadProgress = ref(0);
const selectedFiles = ref([]);
const fileInput = ref(null);

// Форматирование размера файла
const formatFileSize = (bytes) => {
    if (!bytes) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const openFileUploadModal = () => {
    showUploadModal.value = true;
    selectedFiles.value = [];
    uploadProgress.value = 0;
};

const closeUploadModal = () => {
    showUploadModal.value = false;
    selectedFiles.value = [];
    uploadProgress.value = 0;
    uploading.value = false;
};

const handleFileSelect = (event) => {
    const files = Array.from(event.target.files);
    selectedFiles.value = files;
};

const removeFile = (index) => {
    selectedFiles.value.splice(index, 1);
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const uploadFiles = async () => {
    if (selectedFiles.value.length === 0) return;

    uploading.value = true;
    uploadProgress.value = 0;

    const formData = new FormData();
    selectedFiles.value.forEach(file => {
        formData.append('files[]', file);
    });

    try {
        // Используем axios для отслеживания прогресса
        const response = await axios.post(`/klient-deals/${props.deal.id}/upload-files`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            },
            onUploadProgress: (progressEvent) => {
                if (progressEvent.total) {
                    const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                    uploadProgress.value = percentCompleted;
                }
            }
        });

        if (response.data.success) {
            // Обновляем страницу для отображения новых файлов
            router.reload();
            closeUploadModal();
        }
    } catch (error) {
        console.error('Ошибка загрузки файлов:', error);
        alert(error.response?.data?.message || 'Ошибка при загрузке файлов');
    } finally {
        uploading.value = false;
        uploadProgress.value = 0;
    }
};

// const deleteFile = async (fileId) => {
//     if (!confirm('Удалить этот файл?')) return;
//
//     try {
//         await axios.delete(`/klient-deal-files/${fileId}`);
//         router.reload();
//     } catch (error) {
//         console.error('Ошибка удаления файла:', error);
//         alert(error.response?.data?.message || 'Ошибка при удалении файла');
//     }
// };


const page = usePage();
// Получаем ID текущего пользователя
const authId = computed(() => page.props.auth.user.id);


// Статусы, после которых нельзя менять статус
const lockedStatuses = ['Успешно', 'Отказ'];

// Проверяем, заблокирован ли статус
const isStatusLocked = computed(() => {
    return lockedStatuses.includes(props.deal.status);
});

// Массив всех доступных этапов сделки
const allStatuses = [
    'Первичный контакт',
    'Переговоры',
    'КП отправлено',
    'Согласование договора',
    'Успешно',
    'Отказ'
];

const canEdit = computed(() => props.deal.creator_id === authId.value);

// Функция удаления файла
const deleteFile = (fileId) => {
    if (confirm('Вы уверены, что хотите удалить этот файл?')) {
        router.delete(route('klient-deal-files.destroy', fileId), {
            preserveScroll: true,
            onSuccess: () => {
                // Можно добавить уведомление
            }
        });
    }
};


// Форматирование валюты
const formatMoney = (amount) => {
    return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB' }).format(amount);
};

// Цвета для этапов сделки
const statusClasses = (status) => {
    const map = {
        'Первичный контакт': 'bg-gray-100 text-gray-700 border-gray-200',
        'Переговоры': 'bg-blue-100 text-blue-700 border-blue-200',
        'КП отправлено': 'bg-indigo-100 text-indigo-700 border-indigo-200',
        'Согласование договора': 'bg-purple-100 text-purple-700 border-purple-200',
        'Успешно': 'bg-green-100 text-green-700 border-green-200',
        'Отказ': 'bg-red-100 text-red-700 border-red-200',
    };
    return map[status] || 'bg-gray-100 text-gray-700';
};

// Смена статуса (этапа)
// Функция смены статуса
const updateStatus = (newStatus) => {
    // Отправляем запрос на сервер
    router.patch(route('klient-deals.update-status', props.deal.id), {
        status: newStatus
    }, {
        preserveScroll: true,
        onSuccess: () => {
            // Можно добавить уведомление, если нужно
        }
    });
};


</script>

<template>
    <Head :title="`Сделка: ${deal.name}`" />

    <div class="py-8 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Хлебные крошки -->
            <nav class="flex text-sm font-medium text-gray-500 mb-6">
                <Link :href="route('klients.index')" class="hover:text-gray-700">Клиенты</Link>
                <span class="mx-2">/</span>
                <Link :href="route('klients.show', deal.klient_id)" class="hover:text-gray-700">{{ deal.klient?.name }}</Link>
                <span class="mx-2">/</span>
                <span class="text-gray-900">Сделка #{{ deal.id }}</span>
            </nav>


            <!-- ШАПКА -->
            <div class="bg-white shadow-sm rounded-xl border border-gray-200 p-6 mb-6">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">



                    <div class="flex items-center">
                        <div class="h-14 w-14 bg-indigo-100 rounded-lg flex items-center justify-center text-indigo-700 shadow-inner">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div class="flex items-center mt-1 space-x-3">
                            <!-- Выпадающий список для смены статуса -->
                            <div class="relative">
                                <select
                                    :value="deal.status"
                                    @change="updateStatus($event.target.value)"
                                    :class="[
                        'appearance-none pl-3 pr-8 py-1 rounded-full text-xs font-bold border cursor-pointer focus:outline-none transition-colors',
                        statusClasses(deal.status),
                        { 'opacity-60 cursor-not-allowed': isStatusLocked }
                    ]"
                                    :disabled="isStatusLocked"
                                >
                                    <option v-for="st in allStatuses" :key="st" :value="st">
                                        {{ st }}
                                    </option>
                                </select>
                                <!-- Иконка стрелочки для красоты -->
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                                    </svg>
                                </div>
                            </div>

                            <span class="text-sm text-gray-400 font-medium">
                Атрибут: <b class="text-gray-700">{{ deal.attribute }}</b>
            </span>
                        </div>
                    </div>

                    <Link
                        v-if="canEdit"
                        :href="route('klient-deals.edit', deal.id)"
                        class="inline-flex items-center px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg font-bold text-xs hover:bg-slate-50 transition shadow-sm"
                    >
                        <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                        Редактировать сделку
                    </Link>

                    <div class="text-right">
                        <p class="text-xs text-gray-400 uppercase font-bold tracking-widest">Сумма сделки</p>
                        <p class="text-3xl font-black text-indigo-600">{{ formatMoney(deal.total_amount) }}</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- ЛЕВАЯ КОЛОНКА (70%) -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Товары и услуги -->
                    <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                            <h2 class="font-bold text-gray-800">Состав сделки (Товары и услуги)</h2>
                        </div>
                        <table class="w-full text-left">
                            <thead class="text-[10px] text-gray-400 uppercase bg-gray-50/50">
                            <tr>
                                <th class="px-6 py-3 font-bold">Наименование</th>
                                <th class="px-6 py-3 font-bold text-center">Кол-во</th>
                                <th class="px-6 py-3 font-bold">Цена за ед.</th>
                                <th class="px-6 py-3 font-bold text-right">Итого</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                            <tr v-for="item in deal.items" :key="item.id" class="text-sm">
                                <td class="px-6 py-4 font-medium text-gray-900">{{ item.name }}</td>
                                <td class="px-6 py-4 text-center text-gray-600">{{ item.quantity }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ formatMoney(item.unit_price) }}</td>
                                <td class="px-6 py-4 text-right font-bold text-gray-900">{{ formatMoney(item.total_price) }}</td>
                            </tr>
                            </tbody>
                            <tfoot class="bg-indigo-50/30">
                            <tr>
                                <td colspan="3" class="px-6 py-4 text-right text-xs font-bold uppercase text-gray-500">Общий итог:</td>
                                <td class="px-6 py-4 text-right font-black text-indigo-700 text-lg">{{ formatMoney(deal.total_amount) }}</td>
                            </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Описание и задачи -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                            <h3 class="text-xs font-bold text-gray-400 uppercase mb-3 tracking-widest">Описание / Заметки</h3>
                            <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-wrap">{{ deal.description || 'Нет описания' }}</p>
                        </div>

                        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                            <h3 class="text-xs font-bold text-gray-400 uppercase mb-3 tracking-widest">Связанные задачи</h3>
                            <div v-if="deal.tasks?.length" class="space-y-3">
                                <Link v-for="task in deal.tasks" :key="task.id" :href="route('klient-tasks.show', task.id)"
                                      class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-indigo-50 transition border border-gray-100">
                                    <div>
                                        <p class="text-xs font-bold text-gray-900">{{ task.title }}</p>
                                        <p class="text-[10px] text-gray-500">Исполнитель: {{ task.responsible?.name }}</p>
                                    </div>
                                    <span class="text-[10px] font-bold uppercase text-indigo-600">{{ task.status }}</span>
                                </Link>
                            </div>
                            <p v-else class="text-xs text-gray-400 italic">Задач не привязано</p>
                        </div>
                    </div>

                    <!-- Вложения -->
                    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest">Документы и вложения</h3>

                            <!-- Кнопка добавления файлов - показываем только если есть права -->
                            <button
                                v-if="canUploadFiles"
                                @click="openFileUploadModal"
                                class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium text-indigo-600 hover:text-indigo-700 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Добавить файлы
                            </button>
                        </div>

                        <!-- Список файлов -->
                        <div v-if="deal.files?.length" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div v-for="file in deal.files" :key="file.id" class="flex items-center p-3 border rounded-lg bg-gray-50 group transition hover:border-indigo-200">
                                <div class="p-2 bg-white rounded shadow-sm mr-3">
                                    <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-bold text-gray-900 truncate" :title="file.original_name">{{ file.original_name }}</p>
                                    <p class="text-[10px] text-gray-400">Загрузил: {{ file.user?.name }}</p>
                                    <p class="text-[10px] text-gray-400">{{ formatFileSize(file.file_size) }}</p>
                                </div>

                                <div class="flex items-center space-x-3 ml-2">
                                    <a :href="`/klient-deal-files/${file.id}/download`"
                                       class="text-indigo-600 hover:text-indigo-900 text-[10px] font-bold uppercase">
                                        Скачать
                                    </a>

                                    <!-- Кнопка удаления - показываем если есть права -->
                                    <button
                                        v-if="canDeleteFile(file)"
                                        @click="deleteFile(file.id)"
                                        class="text-rose-500 hover:text-rose-700 p-1 rounded hover:bg-rose-50 transition"
                                        title="Удалить файл"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-xs text-gray-400 italic text-center py-4">Документы не прикреплены</p>

                        <!-- Модальное окно загрузки файлов -->
                        <div v-if="showUploadModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="closeUploadModal">
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-lg w-full">
                                <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex justify-between items-center">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Загрузка файлов</h3>
                                    <button @click="closeUploadModal" class="text-gray-400 hover:text-gray-600">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>

                                <form @submit.prevent="uploadFiles" class="p-6 space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Выберите файлы
                                        </label>
                                        <input
                                            type="file"
                                            ref="fileInput"
                                            multiple
                                            @change="handleFileSelect"
                                            accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.zip,.txt"
                                            class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-2.5 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                        />
                                        <p class="mt-1 text-xs text-gray-500">Максимальный размер файла: 20 МБ</p>
                                    </div>

                                    <!-- Список выбранных файлов -->
                                    <div v-if="selectedFiles.length" class="space-y-2">
                                        <div v-for="(file, index) in selectedFiles" :key="index"
                                             class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                            <div class="flex items-center gap-2">
                                                <svg class="w-4 h-4 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" />
                                                </svg>
                                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ file.name }}</span>
                                                <span class="text-xs text-gray-500">({{ formatFileSize(file.size) }})</span>
                                            </div>
                                            <button type="button" @click="removeFile(index)" class="text-red-500 hover:text-red-700">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Прогресс загрузки -->
                                    <div v-if="uploadProgress > 0 && uploadProgress < 100" class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-indigo-600 h-2 rounded-full transition-all duration-300" :style="{ width: uploadProgress + '%' }"></div>
                                        <p class="text-xs text-center mt-1">{{ uploadProgress }}% загружено</p>
                                    </div>

                                    <div class="flex justify-end gap-3 pt-4">
                                        <button type="button" @click="closeUploadModal"
                                                class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition">
                                            Отмена
                                        </button>
                                        <button type="submit"
                                                :disabled="uploading || selectedFiles.length === 0"
                                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
                                            <svg v-if="uploading" class="inline w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            {{ uploading ? 'Загрузка...' : 'Загрузить' }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ПРАВАЯ КОЛОНКА (30%) -->
                <div class="space-y-6">

                    <!-- Ответственные -->
                    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                        <h3 class="text-xs font-bold text-gray-400 uppercase mb-4 tracking-widest">Команда сделки</h3>
                        <div class="space-y-4">
                            <div v-for="user in deal.responsibles" :key="user.id" class="flex items-center">
                                <div class="h-8 w-8 bg-indigo-600 rounded-full flex items-center justify-center text-white font-bold text-xs">
                                    {{ user.name.charAt(0) }}
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-bold text-gray-900 leading-none">{{ user.name }}</p>
                                    <p class="text-[10px] text-gray-400 mt-1 uppercase">Ответственный</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Детали участника -->
                    <div class="bg-indigo-900 p-6 rounded-xl text-white shadow-lg">
                        <h3 class="text-[10px] font-bold opacity-60 uppercase mb-4 tracking-widest">Контактное лицо</h3>
                        <div v-if="deal.contact_person" class="space-y-3">
                            <p class="text-lg font-black leading-tight">{{ deal.contact_person.full_name }}</p>
                            <p class="text-xs opacity-80">{{ deal.contact_person.position }}</p>
                            <div class="pt-2 border-t border-white/10 space-y-1">
                                <p class="text-xs flex items-center"><span class="opacity-50 w-12 text-[10px]">TEL:</span> {{ deal.contact_person.phone }}</p>
                                <p class="text-xs flex items-center"><span class="opacity-50 w-12 text-[10px]">MAIL:</span> {{ deal.contact_person.email }}</p>
                            </div>
                        </div>
                        <p v-else class="text-xs opacity-50 italic">Не указано</p>
                    </div>

                    <!-- Сроки -->
                    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                        <h3 class="text-xs font-bold text-gray-400 uppercase mb-3 tracking-widest">Хронология</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="text-[10px] text-gray-400 block uppercase">Дедлайн</label>
                                <p :class="['text-sm font-bold', new Date(deal.deadline) < new Date() ? 'text-red-600' : 'text-gray-900']">
                                    {{ deal.deadline ? new Date(deal.deadline).toLocaleString() : 'Не задан' }}
                                </p>
                            </div>
                            <div class="pt-3 border-t">
                                <label class="text-[10px] text-gray-400 block uppercase">Дата открытия</label>
                                <p class="text-sm font-medium text-gray-700">{{ new Date(deal.created_at).toLocaleDateString() }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Быстрые действия со статусом -->
<!--                    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">-->
<!--                        <h3 class="text-xs font-bold text-gray-400 uppercase mb-3 tracking-widest">Быстрое завершение</h3>-->
<!--                        <div class="grid grid-cols-1 gap-2">-->
<!--                            <button-->
<!--                                v-if="deal.status !== 'Успешно'"-->
<!--                                @click="updateStatus('Успешно')"-->
<!--                                class="w-full py-2 bg-green-50 text-green-700 rounded-lg text-xs font-black hover:bg-green-100 uppercase transition"-->
<!--                            >-->
<!--                                Сделка выиграна-->
<!--                            </button>-->
<!--                            <button-->
<!--                                v-if="deal.status !== 'Отказ'"-->
<!--                                @click="updateStatus('Отказ')"-->
<!--                                class="w-full py-2 bg-red-50 text-red-700 rounded-lg text-xs font-black hover:bg-red-100 uppercase transition"-->
<!--                            >-->
<!--                                Сделка проиграна-->
<!--                            </button>-->
<!--                        </div>-->
<!--                    </div>-->

                </div>
            </div>
        </div>
    </div>
</template>
