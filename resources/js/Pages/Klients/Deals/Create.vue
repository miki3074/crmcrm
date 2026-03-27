<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    klient: Object,
    contacts: Array,
    availableResponsibles: Array,
    activeTasks: Array
});

const form = useForm({
    name: '',
    contact_person_id: '',
    responsible_ids: [], // Несколько ответственных
    task_ids: [],        // Связанные задачи
    deadline: '',
    status: 'Переговоры',
    attribute: 'Продажа товара',
    description: '',
    items: [
        { name: '', quantity: 1, unit_price: 0 }
    ],
    files: []
});

// Добавление/Удаление строк товаров
const addItem = () => form.items.push({ name: '', quantity: 1, unit_price: 0 });
const removeItem = (index) => form.items.length > 1 && form.items.splice(index, 1);

// Расчет итоговой суммы для интерфейса
const grandTotal = computed(() => {
    return form.items.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);
});

const handleFileChange = (e) => {
    form.files = Array.from(e.target.files);
};

const submit = () => {
    form.post(route('klient-deals.store', props.klient.id));
};
</script>

<template>
    <Head title="Новая сделка" />

    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

            <form @submit.prevent="submit" class="space-y-8">
                <!-- Заголовок -->
                <div class="flex items-center justify-between">
                    <div>
                        <Link :href="route('klients.show', klient.id)" class="text-indigo-600 text-sm hover:underline">← Назад к клиенту</Link>
                        <h1 class="text-2xl font-extrabold text-gray-900">Создание новой сделки</h1>
                        <p class="text-sm text-gray-500">Клиент: {{ klient.name }}</p>
                    </div>
                    <button type="submit" :disabled="form.processing" class="bg-indigo-600 text-white px-6 py-2 rounded-lg font-bold shadow-lg hover:bg-indigo-700 disabled:opacity-50">
                        {{ form.processing ? 'Сохранение...' : 'Создать сделку' }}
                    </button>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                    <!-- ЛЕВАЯ КОЛОНКА: Основные данные -->
                    <div class="lg:col-span-2 space-y-6">

                        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                            <h2 class="text-sm font-bold text-gray-400 uppercase mb-4 tracking-widest">Информация о сделке</h2>
                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Название сделки *</label>
                                    <input v-model="form.name" type="text" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Напр: Поставка оборудования">
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Этап / Статус</label>
                                        <select v-model="form.status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                            <option>Первичный контакт</option>
                                            <option>Переговоры</option>
                                            <option>КП отправлено</option>
                                            <option>Согласование договора</option>
                                            <option>Успешно</option>
                                            <option>Отказ</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Атрибут сделки</label>
                                        <select v-model="form.attribute" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                            <option>Продажа товара</option>
                                            <option>Услуга</option>
                                            <option>Сервисное обслуживание</option>
                                            <option>Перепродажа</option>
                                            <option>Другое</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ТОВАРЫ И УСЛУГИ -->
                        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                            <h2 class="text-sm font-bold text-gray-400 uppercase mb-4 tracking-widest">Товары и услуги</h2>

                            <div class="overflow-x-auto">
                                <table class="w-full text-left">
                                    <thead>
                                    <tr class="text-xs text-gray-400 uppercase border-b">
                                        <th class="pb-2 font-medium">Название</th>
                                        <th class="pb-2 font-medium w-24 text-center">Кол-во</th>
                                        <th class="pb-2 font-medium w-32">Цена (шт)</th>
                                        <th class="pb-2 font-medium w-32">Итого</th>
                                        <th class="pb-2 w-10"></th>
                                    </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                    <tr v-for="(item, index) in form.items" :key="index">
                                        <td class="py-3 pr-4">
                                            <input v-model="item.name" type="text" placeholder="Услуга или товар" class="w-full border-none p-0 focus:ring-0 text-sm font-medium">
                                        </td>
                                        <td class="py-3 px-2">
                                            <input v-model.number="item.quantity" type="number" step="0.1" class="w-full border-gray-200 rounded text-center text-sm">
                                        </td>
                                        <td class="py-3 px-2">
                                            <input v-model.number="item.unit_price" type="number" class="w-full border-gray-200 rounded text-sm">
                                        </td>
                                        <td class="py-3 px-2 text-sm font-bold text-gray-700">
                                            {{ (item.quantity * item.unit_price).toLocaleString() }}
                                        </td>
                                        <td class="py-3 text-right text-gray-300 hover:text-red-500 cursor-pointer" @click="removeItem(index)">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"/></svg>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-4 flex justify-between items-center">
                                <button type="button" @click="addItem" class="text-indigo-600 text-sm font-bold flex items-center hover:text-indigo-800">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 6v6m0 0v6m0-6h6m-6 0H6" stroke-width="2" stroke-linecap="round"/></svg>
                                    Добавить позицию
                                </button>
                                <div class="text-right">
                                    <span class="text-gray-400 text-sm uppercase mr-2">Общая сумма:</span>
                                    <span class="text-xl font-black text-indigo-700">{{ grandTotal.toLocaleString() }} ₽</span>
                                </div>
                            </div>
                        </div>

                        <!-- Описание -->
                        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Комментарии по сделке</label>
                            <textarea v-model="form.description" rows="4" class="block w-full border-gray-300 rounded-md" placeholder="Дополнительные детали сделки..."></textarea>
                        </div>
                    </div>

                    <!-- ПРАВАЯ КОЛОНКА: Связи -->
                    <div class="space-y-6">

                        <!-- Участники -->
                        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                            <h2 class="text-xs font-bold text-gray-400 uppercase mb-4 tracking-widest">Участники</h2>

                            <div class="mb-4">
                                <label class="block text-xs font-bold text-gray-500 mb-1 uppercase">Контактное лицо</label>
                                <select v-model="form.contact_person_id" class="w-full border-gray-300 rounded-md text-sm">
                                    <option value="">Не выбрано</option>
                                    <option v-for="c in contacts" :key="c.id" :value="c.id">{{ c.full_name }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-2 uppercase">Ответственные</label>
                                <div class="space-y-2 max-h-40 overflow-y-auto border p-3 rounded">
                                    <div v-for="user in availableResponsibles" :key="user.id" class="flex items-center">
                                        <input type="checkbox" :value="user.id" v-model="form.responsible_ids" :id="'user'+user.id" class="rounded text-indigo-600">
                                        <label :for="'user'+user.id" class="ml-2 text-sm text-gray-600">{{ user.name }}</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Срок -->
                        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                            <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Срок выполнения (Дедлайн)</label>
                            <input v-model="form.deadline" type="datetime-local" class="w-full border-gray-300 rounded-md text-sm">
                        </div>

                        <!-- Задачи -->
                        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                            <h2 class="text-xs font-bold text-gray-400 uppercase mb-2 tracking-widest">Связать с задачами</h2>
                            <div v-if="activeTasks.length" class="space-y-2 max-h-40 overflow-y-auto">
                                <div v-for="task in activeTasks" :key="task.id" class="flex items-start">
                                    <input type="checkbox" :value="task.id" v-model="form.task_ids" :id="'task'+task.id" class="mt-1 rounded text-indigo-600">
                                    <label :for="'task'+task.id" class="ml-2 text-xs text-gray-600 leading-tight">{{ task.title }}</label>
                                </div>
                            </div>
                            <p v-else class="text-xs text-gray-400">У клиента нет активных задач</p>
                        </div>

                        <!-- Файлы -->
                        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                            <h2 class="text-xs font-bold text-gray-400 uppercase mb-2 tracking-widest">Вложения</h2>
                            <input type="file" multiple @change="handleFileChange" class="text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:bg-indigo-50 file:text-indigo-700">
                        </div>

                    </div>
                </div>
            </form>
        </div>
    </div>
</template>
