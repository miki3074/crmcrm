<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
    task: Object,
    user: Object,
    klient: Object,
    users: Array,
    canEdit: Boolean
});

const showEditModal = ref(false);
const updating = ref(false);
const errors = ref({});
const existingFiles = ref([]);
const newFiles = ref([]);

const showUploadModal = ref(false);
const selectedFiles = ref([]);
const uploading = ref(false);
const uploadProgress = ref(0);

const canManageFiles = computed(() => {
    const currentUserId = props.user?.id;
    return currentUserId === props.task.responsible_id ||
        currentUserId === props.task.creator_id;
});

//Файлы

const getFileIcon = (mimeType) => {
    if (!mimeType) return 'fas fa-file-alt';

    if (mimeType.startsWith('image/')) return 'fas fa-image';
    if (mimeType.startsWith('video/')) return 'fas fa-video';
    if (mimeType === 'application/pdf') return 'fas fa-file-pdf';
    if (mimeType.includes('word')) return 'fas fa-file-word';
    if (mimeType.includes('excel')) return 'fas fa-file-excel';
    if (mimeType.includes('zip') || mimeType.includes('rar')) return 'fas fa-file-archive';

    return 'fas fa-file-alt';
};

// Форматирование размера файла
const formatFileSize = (bytes) => {
    if (!bytes) return '---';
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
};

// Форматирование даты
const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('ru-RU');
};

const openFileUploadModal = () => {
    showUploadModal.value = true;
    selectedFiles.value = [];
};

const closeUploadModal = () => {
    if (uploading.value) return;
    showUploadModal.value = false;
    selectedFiles.value = [];
    uploadProgress.value = 0;
};

const handleFileSelect = (event) => {
    const files = Array.from(event.target.files);
    selectedFiles.value.push(...files);
};

const handleDrop = (event) => {
    const files = Array.from(event.dataTransfer.files);
    selectedFiles.value.push(...files);
};

const removeSelectedFile = (index) => {
    selectedFiles.value.splice(index, 1);
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
        const response = await axios.post(
            route('klient-task-files.upload', props.task.id),
            formData,
            {
                headers: {
                    'Content-Type': 'multipart/form-data'
                },
                onUploadProgress: (progressEvent) => {
                    if (progressEvent.total) {
                        uploadProgress.value = Math.round(
                            (progressEvent.loaded * 100) / progressEvent.total
                        );
                    }
                }
            }
        );

        // Обновляем список файлов
        if (response.data.files) {
            props.task.files = [...(props.task.files || []), ...response.data.files];
        }

        closeUploadModal();

        // Обновляем страницу для отображения новых файлов
        router.reload({ only: ['task'] });

    } catch (error) {
        console.error('Ошибка загрузки:', error);
        alert(error.response?.data?.message || 'Ошибка при загрузке файлов');
    } finally {
        uploading.value = false;
        uploadProgress.value = 0;
    }
};

const deleteFile = async (fileId) => {
    if (!confirm('Удалить этот файл?')) return;

    try {
        await axios.delete(route('klient-task-files.destroy', fileId));

        // Удаляем файл из списка
        props.task.files = props.task.files.filter(f => f.id !== fileId);

        // Обновляем страницу
        router.reload({ only: ['task'] });

    } catch (error) {
        console.error('Ошибка удаления:', error);
        alert(error.response?.data?.message || 'Ошибка при удалении файла');
    }
};

// Форма редактирования
const editForm = ref({
    title: '',
    description: '',
    responsible_id: '',
    deadline: '',
    priority: '',
    type: ''
});

const openEditModal = () => {
    // Дополнительная проверка на клиенте
    if (!props.canEdit) {
        alert('У вас нет прав на редактирование этой задачи');
        return;
    }

    // Заполняем форму текущими данными задачи
    editForm.value = {
        title: props.task.title,
        description: props.task.description || '',
        responsible_id: props.task.responsible_id,
        deadline: props.task.deadline ? props.task.deadline.slice(0, 16) : '',
        priority: props.task.priority,
        type: props.task.type
    };
    existingFiles.value = props.task.files || [];
    newFiles.value = [];
    errors.value = {};
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    errors.value = {};
};

const handleFiles = (event) => {
    newFiles.value = Array.from(event.target.files);
};

const removeFile = async (fileId) => {
    if (!confirm('Удалить этот файл?')) return;

    try {
        await axios.delete(`/api/tasks/${props.task.id}/files/${fileId}`);
        existingFiles.value = existingFiles.value.filter(f => f.id !== fileId);
    } catch (err) {
        alert('Ошибка при удалении файла');
    }
};

const updateTask = async () => {
    updating.value = true;
    errors.value = {};

    // Подготавливаем FormData
    const formData = new FormData();
    formData.append('_method', 'PUT');
    formData.append('title', editForm.value.title);
    formData.append('description', editForm.value.description);
    formData.append('responsible_id', editForm.value.responsible_id);
    formData.append('deadline', editForm.value.deadline);
    formData.append('priority', editForm.value.priority);
    formData.append('type', editForm.value.type);

    // Добавляем новые файлы
    newFiles.value.forEach(file => {
        formData.append('files[]', file);
    });

    try {
        await router.post(route('klient-tasks.update', {
            klient: props.klient.id,
            task: props.task.id
        }), formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            },
            onSuccess: () => {
                closeEditModal();
                router.reload();
            },
            onError: (errors) => {
                errors.value = errors;
            }
        });
    } catch (err) {
        console.error('Ошибка обновления:', err);
        alert('Ошибка при обновлении задачи');
    } finally {
        updating.value = false;
    }
};


const canChangeStatus = computed(() => {
    // Проверяем, является ли текущий пользователь ответственным или создателем
    const currentUserId = props.user?.id;
    return currentUserId === props.task.responsible_id ||
        currentUserId === props.task.created_by;
});

const priorityBadge = (priority) => {
    if (priority === 'high') return 'bg-rose-50 text-rose-700 border-rose-200';
    if (priority === 'medium') return 'bg-amber-50 text-amber-700 border-amber-200';
    return 'bg-sky-50 text-sky-700 border-sky-200';
};

const statusConfig = {
    'pending': { label: 'Ожидает', class: 'bg-slate-100 text-slate-700', icon: 'fa-clock' },
    'processing': { label: 'В работе', class: 'bg-blue-100 text-blue-700', icon: 'fa-spinner' },
    'completed': { label: 'Завершена', class: 'bg-emerald-100 text-emerald-700', icon: 'fa-check-circle' },
    'cancelled': { label: 'Отменена', class: 'bg-rose-100 text-rose-700', icon: 'fa-times-circle' }
};

const updateStatus = (newStatus) => {
    // Дополнительная проверка на клиенте
    if (!canChangeStatus.value) {
        alert('У вас нет прав на изменение статуса этой задачи');
        return;
    }

    router.patch(route('klient-tasks.update-status', props.task.id), {
        status: newStatus
    });
};

// Иконка для типа задачи
const typeIcon = (type) => {
    const icons = {
        'call': 'fa-phone',
        'meeting': 'fa-handshake',
        'email': 'fa-envelope',
        'task': 'fa-list-check',
        'reminder': 'fa-bell'
    };
    return icons[type] || 'fa-tasks';
};
</script>

<template>
    <Head :title="`Задача: ${task.title}`" />

    <div class="min-h-screen bg-slate-50 py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Навигация обратно (обновленный стиль) -->
            <div class="mb-6">
                <Link :href="route('klients.show', task.klient_id)" class="inline-flex items-center text-sm text-slate-500 hover:text-indigo-600 transition-colors group">
                    <i class="fas fa-arrow-left mr-2 text-xs group-hover:-translate-x-1 transition-transform"></i>
                    <span>Вернуться к клиенту:</span>
                    <span class="ml-1 font-medium text-slate-700 group-hover:text-indigo-600">{{ task.klient?.name }}</span>
                </Link>
            </div>

            <!-- Основная карточка -->
            <div class="bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden">

                <!-- Шапка задачи (новая) -->
                <div class="relative">
                    <!-- Верхняя полоса с приоритетом и статусом -->
                    <div class="flex justify-between items-center px-8 py-4 bg-slate-50 border-b border-slate-100">
                        <div class="flex items-center gap-3">
                            <span :class="['px-3 py-1.5 rounded-full text-xs font-bold border flex items-center gap-1.5', priorityBadge(task.priority)]">
                                <i class="fas fa-flag"></i>
                                {{ task.priority === 'high' ? 'Высокий' : task.priority === 'medium' ? 'Средний' : 'Низкий' }} приоритет
                            </span>
                            <span :class="['px-3 py-1.5 rounded-full text-xs font-bold flex items-center gap-1.5', statusConfig[task.status]?.class]">
                                <i :class="['fas', statusConfig[task.status]?.icon]"></i>
                                {{ statusConfig[task.status]?.label || task.status }}
                            </span>
                        </div>

                        <!-- Кнопки действий (обновленные) -->
                        <div class="flex gap-2">
                            <!-- Кнопка "В работу" показываем только если статус не processing
                                 и пользователь имеет права -->
                            <button
                                v-if="task.status !== 'processing' && canChangeStatus"
                                @click="updateStatus('processing')"
                                class="px-4 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-xl text-sm font-bold transition flex items-center gap-2 border border-blue-200"
                            >
                                <i class="fas fa-play"></i>
                                В работу
                            </button>

                            <!-- Кнопка "Завершить" показываем только если статус не completed
                                 и пользователь имеет права -->
                            <button
                                v-if="task.status !== 'completed' && canChangeStatus"
                                @click="updateStatus('completed')"
                                class="px-4 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-xl text-sm font-bold transition flex items-center gap-2 border border-emerald-200"
                            >
                                <i class="fas fa-check"></i>
                                Завершить
                            </button>
                        </div>
                    </div>

                    <!-- Заголовок задачи -->
                    <div class="px-8 pt-6 pb-4">
                        <div class="flex items-start gap-4">
                            <div class="w-14 h-14 bg-indigo-100 rounded-2xl flex items-center justify-center text-indigo-600 text-2xl">
                                <i :class="['fas', typeIcon(task.type)]"></i>
                            </div>
                            <div class="flex-1">
                                <h1 class="text-3xl font-bold text-slate-800 mb-2">{{ task.title }}</h1>
                                <div class="flex items-center gap-4 text-sm text-slate-500">
                                    <span class="flex items-center gap-1.5">
                                        <i class="fas fa-tag"></i>
                                        Тип: <span class="font-medium text-slate-700">{{ task.type }}</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Основной контент: 2 колонки -->
                <div class="grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-slate-100">

                    <!-- ЛЕВАЯ КОЛОНКА (описание + файлы) - 2/3 -->
                    <div class="md:col-span-2 p-8 space-y-8">
                        <!-- Описание -->
                        <div>
                            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                                <i class="fas fa-align-left text-indigo-400"></i>
                                Описание задачи
                            </h3>
                            <div class="bg-slate-50 rounded-xl p-6 border border-slate-100 prose max-w-none text-slate-700 whitespace-pre-wrap">
                                {{ task.description || 'Описание отсутствует' }}
                            </div>
                        </div>

                        <!-- Файлы задачи (обновленный дизайн) -->
                        <div>
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider flex items-center gap-2">
                                    <i class="fas fa-paperclip text-indigo-400"></i>
                                    Прикрепленные файлы ({{ task.files?.length || 0 }})
                                </h3>

                                <!-- Кнопка добавления файлов - только для ответственного и создателя -->
                                <button
                                    v-if="canManageFiles"
                                    @click="openFileUploadModal"
                                    class="px-3 py-1.5 text-xs bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg transition flex items-center gap-2"
                                >
                                    <i class="fas fa-plus"></i>
                                    Добавить файлы
                                </button>
                            </div>

                            <!-- Список файлов -->
                            <div v-if="task.files?.length" class="space-y-2">
                                <div v-for="file in task.files" :key="file.id"
                                     class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-100 hover:bg-white hover:shadow-sm transition group">
                                    <div class="flex items-center gap-3 overflow-hidden">
                                        <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center text-indigo-600 shrink-0">
                                            <i :class="getFileIcon(file.mime_type)"></i>
                                        </div>
                                        <div class="overflow-hidden">
                                            <div class="font-medium text-slate-800 truncate max-w-[200px] sm:max-w-xs" :title="file.original_name">
                                                {{ file.original_name }}
                                            </div>
                                            <div class="text-xs text-slate-400 mt-1 flex items-center gap-2">
                                                <span>{{ formatFileSize(file.file_size) }}</span>
                                                <span>•</span>
                                                <span>{{ formatDate(file.created_at) }}</span>
                                                <span v-if="file.user" class="text-indigo-500">
                                {{ file.user.name }}
                            </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <a :href="route('klient-task-files.download', file.id)"
                                           class="w-9 h-9 rounded-full bg-white border border-slate-200 flex items-center justify-center text-indigo-600 hover:bg-indigo-50 transition shrink-0"
                                           title="Скачать">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <button
                                            v-if="canManageFiles"
                                            @click="deleteFile(file.id)"
                                            class="w-9 h-9 rounded-full bg-white border border-slate-200 flex items-center justify-center text-red-500 hover:bg-red-50 transition shrink-0"
                                            title="Удалить">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="py-10 text-center border border-dashed border-slate-200 rounded-xl">
                                <i class="fas fa-cloud-upload-alt text-3xl text-slate-300 mb-3"></i>
                                <p class="text-sm text-slate-400">Файлы не прикреплены</p>
                                <p v-if="canManageFiles" class="text-xs text-slate-400 mt-2">
                                    Нажмите кнопку "Добавить файлы" чтобы прикрепить
                                </p>
                            </div>

                            <!-- Модальное окно загрузки файлов -->
                            <div
                                v-if="showUploadModal"
                                class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
                                @click.self="closeUploadModal"
                            >
                                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-lg w-full">
                                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Загрузка файлов</h3>
                                        <button
                                            @click="closeUploadModal"
                                            class="text-gray-400 hover:text-gray-600"
                                        >
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>

                                    <div class="p-6">
                                        <div
                                            class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center hover:border-indigo-500 transition cursor-pointer"
                                            @dragover.prevent
                                            @drop.prevent="handleDrop"
                                            @click="$refs.fileInput.click()"
                                        >
                                            <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-3"></i>
                                            <p class="text-gray-600 dark:text-gray-300 mb-2">
                                                Перетащите файлы сюда или кликните для выбора
                                            </p>
                                            <p class="text-xs text-gray-400">
                                                Максимальный размер: 10MB
                                            </p>
                                            <input
                                                ref="fileInput"
                                                type="file"
                                                multiple
                                                @change="handleFileSelect"
                                                class="hidden"
                                                accept="*/*"
                                            />
                                        </div>

                                        <!-- Список выбранных файлов -->
                                        <div v-if="selectedFiles.length" class="mt-4 space-y-2">
                                            <div v-for="(file, index) in selectedFiles" :key="index"
                                                 class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                                <div class="flex items-center gap-2">
                                                    <i class="fas fa-file text-gray-400"></i>
                                                    <span class="text-sm text-gray-700 dark:text-gray-300 truncate max-w-[200px]">
                                    {{ file.name }}
                                </span>
                                                    <span class="text-xs text-gray-400">
                                    ({{ formatFileSize(file.size) }})
                                </span>
                                                </div>
                                                <button
                                                    @click="removeSelectedFile(index)"
                                                    class="text-red-500 hover:text-red-700"
                                                >
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Прогресс загрузки -->
                                        <div v-if="uploading" class="mt-4">
                                            <div class="flex justify-between text-sm text-gray-600 mb-1">
                                                <span>Загрузка...</span>
                                                <span>{{ uploadProgress }}%</span>
                                            </div>
                                            <div class="w-full bg-gray-200 rounded-full h-2">
                                                <div
                                                    class="bg-indigo-600 h-2 rounded-full transition-all duration-300"
                                                    :style="{ width: uploadProgress + '%' }"
                                                ></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                                        <button
                                            @click="closeUploadModal"
                                            class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition"
                                            :disabled="uploading"
                                        >
                                            Отмена
                                        </button>
                                        <button
                                            @click="uploadFiles"
                                            :disabled="selectedFiles.length === 0 || uploading"
                                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            <i v-if="uploading" class="fas fa-spinner fa-spin mr-2"></i>
                                            {{ uploading ? 'Загрузка...' : 'Загрузить (' + selectedFiles.length + ')' }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ПРАВАЯ КОЛОНКА (информация) - 1/3 -->
                    <div class="p-8 bg-slate-50/50 space-y-6">
                        <!-- Ответственный -->
                        <div class="bg-white rounded-xl p-5 border border-slate-100">
                            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                                <i class="fas fa-user-check text-indigo-400"></i>
                                Ответственный
                            </h4>
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center text-indigo-700 font-bold text-lg">
                                    {{ task.responsible?.name?.charAt(0) || '?' }}
                                </div>
                                <div>
                                    <div class="font-bold text-slate-800">{{ task.responsible?.name || 'Не назначен' }}</div>
                                    <div class="text-xs text-slate-400">Исполнитель</div>
                                </div>
                            </div>
                        </div>

                        <!-- Дедлайн -->
                        <div class="bg-white rounded-xl p-5 border border-slate-100">
                            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                                <i class="fas fa-calendar-alt text-indigo-400"></i>
                                Срок выполнения
                            </h4>
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center text-lg"
                                     :class="task.deadline && new Date(task.deadline) < new Date() ? 'bg-rose-100 text-rose-600' : 'bg-slate-100 text-slate-600'">
                                    <i class="fas" :class="task.deadline && new Date(task.deadline) < new Date() ? 'fa-exclamation-triangle' : 'fa-clock'"></i>
                                </div>
                                <div>
                                    <div class="font-bold text-slate-800" :class="task.deadline && new Date(task.deadline) < new Date() ? 'text-rose-600' : ''">
                                        {{ task.deadline ? new Date(task.deadline).toLocaleString('ru-RU') : 'Не задан' }}
                                    </div>
                                    <div class="text-xs text-slate-400">
                                        {{ task.deadline && new Date(task.deadline) < new Date() ? 'Просрочено' : 'Осталось время' }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Информация о создателе -->
                        <div class="bg-white rounded-xl p-5 border border-slate-100">
                            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                                <i class="fas fa-user-plus text-indigo-400"></i>
                                Поставил задачу
                            </h4>
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-10 bg-slate-100 rounded-lg flex items-center justify-center text-slate-600 font-bold">
                                    {{ task.creator?.name?.charAt(0) || '?' }}
                                </div>
                                <div>
                                    <div class="font-medium text-slate-700">{{ task.creator?.name || 'Система' }}</div>
                                </div>
                            </div>
                            <div class="text-xs text-slate-400 flex items-center gap-2 pt-2 border-t border-slate-100">
                                <i class="far fa-calendar"></i>
                                {{ new Date(task.created_at).toLocaleString('ru-RU') }}
                            </div>
                        </div>

                        <!-- Быстрые действия -->
                        <div class="bg-indigo-50 rounded-xl p-5 border border-indigo-100">
                            <h4 class="text-xs font-bold text-indigo-400 uppercase tracking-wider mb-3">Действия</h4>
                            <div class="space-y-2">
                                <Link :href="route('klients.show', task.klient_id)"
                                      class="flex items-center gap-3 p-2 hover:bg-white rounded-lg transition text-sm text-indigo-700">
                                    <i class="fas fa-user w-5"></i>
                                    <span>Перейти к клиенту</span>
                                </Link>
                                <a
                                    v-if="canEdit"
                                    @click="openEditModal"
                                    class="flex items-center gap-3 p-2 hover:bg-white rounded-lg transition text-sm text-indigo-700 cursor-pointer"
                                >
                                    <i class="fas fa-edit w-5"></i>
                                    <span>Редактировать задачу</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div
        v-if="showEditModal"
        class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
        @click.self="closeEditModal"
    >
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Редактирование задачи</h3>
                <button
                    @click="closeEditModal"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                >
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form @submit.prevent="updateTask" class="p-6 space-y-5">
                <!-- Название задачи -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Название задачи <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="editForm.title"
                        type="text"
                        required
                        class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-2.5 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        placeholder="Введите название задачи"
                    />
                    <p v-if="errors.title" class="mt-1.5 text-xs text-red-600">{{ errors.title }}</p>
                </div>

                <!-- Тип задачи -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Тип задачи <span class="text-red-500">*</span>
                    </label>
                    <select
                        v-model="editForm.type"
                        required
                        class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-2.5 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    >
                        <option value="call">Звонок</option>
                        <option value="meeting">Встреча</option>
                        <option value="email">Письмо / КП</option>
                        <option value="task">Подготовка документа</option>
                        <option value="reminder">Другое</option>
                    </select>
                    <p v-if="errors.type" class="mt-1.5 text-xs text-red-600">{{ errors.type }}</p>
                </div>

                <!-- Приоритет -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Приоритет <span class="text-red-500">*</span>
                    </label>
                    <select
                        v-model="editForm.priority"
                        required
                        class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-2.5 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    >
                        <option value="high">Высокий</option>
                        <option value="medium">Средний</option>
                        <option value="low">Низкий</option>
                    </select>
                    <p v-if="errors.priority" class="mt-1.5 text-xs text-red-600">{{ errors.priority }}</p>
                </div>

                <!-- Ответственный -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Ответственный <span class="text-red-500">*</span>
                    </label>
                    <select
                        v-model="editForm.responsible_id"
                        required
                        class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-2.5 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    >
                        <option v-for="user in users" :key="user.id" :value="user.id">
                            {{ user.name }} ({{ user.email }})
                        </option>
                    </select>
                    <p v-if="errors.responsible_id" class="mt-1.5 text-xs text-red-600">{{ errors.responsible_id }}</p>
                </div>

                <!-- Срок выполнения -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Срок выполнения
                    </label>
                    <input
                        v-model="editForm.deadline"
                        type="datetime-local"
                        class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-2.5 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    />
                    <p v-if="errors.deadline" class="mt-1.5 text-xs text-red-600">{{ errors.deadline }}</p>
                </div>

                <!-- Описание -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Описание
                    </label>
                    <textarea
                        v-model="editForm.description"
                        rows="4"
                        class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-2.5 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        placeholder="Подробное описание задачи..."
                    ></textarea>
                    <p v-if="errors.description" class="mt-1.5 text-xs text-red-600">{{ errors.description }}</p>
                </div>

                <!-- Файлы (опционально) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Файлы
                    </label>
                    <input
                        type="file"
                        multiple
                        @change="handleFiles"
                        class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-2.5 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    />
                    <div v-if="existingFiles.length" class="mt-2 space-y-1">
                        <div v-for="file in existingFiles" :key="file.id" class="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-2">
                            <i class="fas fa-paperclip"></i>
                            <span>{{ file.original_name }}</span>
                            <button
                                type="button"
                                @click="removeFile(file.id)"
                                class="text-red-500 hover:text-red-700 ml-auto"
                            >
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <p v-if="errors.files" class="mt-1.5 text-xs text-red-600">{{ errors.files }}</p>
                </div>

                <!-- Кнопки -->
                <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button
                        type="button"
                        @click="closeEditModal"
                        class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition"
                        :disabled="updating"
                    >
                        Отмена
                    </button>
                    <button
                        type="submit"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="updating"
                    >
                        <i v-if="updating" class="fas fa-spinner fa-spin mr-2"></i>
                        {{ updating ? 'Сохранение...' : 'Сохранить изменения' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

</template>
