<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    isOpen: Boolean,
    klientId: Number,
    responsibles: Array, // Список: Создатель + те, у кого есть доступ
});

const emit = defineEmits(['close']);

const form = useForm({
    title: '',
    description: '',
    responsible_id: '',
    deadline: '',
    priority: 'medium',
    type: 'Звонок',
    files: []
});

const fileInput = ref(null);

const handleFileChange = (e) => {
    form.files = Array.from(e.target.files);
};

const submit = () => {
    form.post(route('klient-tasks.store', props.klientId), {
        onSuccess: () => {
            form.reset();
            emit('close');
        },
    });
};

// Сброс формы при закрытии
watch(() => props.isOpen, (newVal) => {
    if (!newVal) form.reset();
});
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 overflow-hidden">
        <!-- Overlay (Затемнение) -->
        <div class="absolute inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="emit('close')"></div>

        <div class="fixed inset-y-0 right-0 pl-10 max-w-full flex">
            <!-- Панель меню -->
            <div class="w-screen max-w-md">
                <div class="h-full flex flex-col bg-white shadow-xl overflow-y-scroll">
                    <div class="py-6 px-4 bg-indigo-700 sm:px-6">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-medium text-white">Новая задача</h2>
                            <button @click="emit('close')" class="text-indigo-200 hover:text-white">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <p class="mt-1 text-sm text-indigo-300">Заполните детали задачи для клиента.</p>
                    </div>

                    <form @submit.prevent="submit" class="flex-1 flex flex-col justify-between">
                        <div class="px-4 sm:px-6 py-6 space-y-6">
                            <!-- Название -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Название задачи *</label>
                                <input v-model="form.title" type="text" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>

                            <!-- Тип и Приоритет -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Тип</label>
                                    <select v-model="form.type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        <option>Звонок</option>
                                        <option>Встреча</option>
                                        <option>Письмо / КП</option>
                                        <option>Подготовка документа</option>
                                        <option>Другое</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Приоритет</label>
                                    <select v-model="form.priority" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        <option value="low">Низкий</option>
                                        <option value="medium">Средний</option>
                                        <option value="high">Высокий</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Ответственный -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Ответственный *</label>
                                <select v-model="form.responsible_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="" disabled>Выберите сотрудника</option>
                                    <option v-for="user in responsibles" :key="user.id" :value="user.id">
                                        {{ user.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- Дедлайн -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Срок (дедлайн)</label>
                                <input v-model="form.deadline" type="datetime-local" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>

                            <!-- Описание -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Описание / Комментарий</label>
                                <textarea v-model="form.description" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                            </div>

                            <!-- Файлы -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Прикрепить файлы</label>
                                <input
                                    type="file"
                                    multiple
                                    @change="handleFileChange"
                                    class="mt-2 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                                >
                            </div>
                        </div>

                        <!-- Кнопки в футере -->
                        <div class="flex-shrink-0 px-4 py-4 flex justify-end space-x-3 border-t border-gray-200 bg-gray-50">
                            <button type="button" @click="emit('close')" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Отмена
                            </button>
                            <button type="submit" :disabled="form.processing" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none disabled:opacity-50">
                                {{ form.processing ? 'Создание...' : 'Создать задачу' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
