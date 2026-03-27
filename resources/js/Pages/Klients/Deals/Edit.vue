<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    deal: Object,
    klient: Object,
    contacts: Array,
    availableResponsibles: Array,
    activeTasks: Array
});

const form = useForm({
    name: props.deal.name,
    contact_person_id: props.deal.contact_person_id,
    responsible_ids: props.deal.responsibles.map(u => u.id),
    task_ids: props.deal.tasks.map(t => t.id),
    deadline: props.deal.deadline ? props.deal.deadline.replace(' ', 'T') : '',
    status: props.deal.status,
    attribute: props.deal.attribute,
    description: props.deal.description,
    items: props.deal.items.map(i => ({ name: i.name, quantity: i.quantity, unit_price: i.unit_price })),
    new_files: []
});

const addItem = () => form.items.push({ name: '', quantity: 1, unit_price: 0 });
const removeItem = (index) => form.items.length > 1 && form.items.splice(index, 1);

const deleteExistingFile = (fileId) => {
    if (confirm('Удалить этот файл навсегда?')) {
        router.delete(route('klient-deal-files.destroy', fileId), { preserveScroll: true });
    }
};

const submit = () => {
    form.put(route('klient-deals.update', props.deal.id));
};

const grandTotal = computed(() => {
    return form.items.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);
});
</script>

<template>
    <Head title="Редактирование сделки" />
    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-6xl mx-auto px-4">
            <form @submit.prevent="submit" class="space-y-6">

                <div class="flex justify-between items-center">
                    <h1 class="text-2xl font-black">Редактировать: {{ deal.name }}</h1>
                    <div class="flex gap-3">
                        <Link :href="route('klient-deals.show', deal.id)" class="px-4 py-2 text-sm font-bold text-gray-500">Отмена</Link>
                        <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-xl font-bold shadow-lg">Сохранить</button>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Основное (как в Create) -->
                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                            <label class="block text-sm font-bold text-slate-700">Название сделки</label>
                            <input v-model="form.name" type="text" class="w-full mt-1 border-slate-200 rounded-xl">

                            <div class="grid grid-cols-2 gap-4 mt-4">
                                <select v-model="form.status" class="border-slate-200 rounded-xl">
                                    <option>Первичный контакт</option><option>Переговоры</option><option>Успешно</option><option>Отказ</option>
                                </select>
                                <select v-model="form.attribute" class="border-slate-200 rounded-xl">
                                    <option>Продажа товара</option><option>Услуга</option><option>Другое</option>
                                </select>
                            </div>
                        </div>

                        <!-- Товары (динамически) -->
                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                            <h3 class="font-bold mb-4">Позиции сделки</h3>
                            <div v-for="(item, index) in form.items" :key="index" class="flex gap-2 mb-2">
                                <input v-model="item.name" placeholder="Название" class="flex-1 border-slate-200 rounded-lg text-sm">
                                <input v-model.number="item.quantity" type="number" class="w-20 border-slate-200 rounded-lg text-sm text-center">
                                <input v-model.number="item.unit_price" type="number" class="w-32 border-slate-200 rounded-lg text-sm">
                                <button @click="removeItem(index)" type="button" class="text-rose-500 px-2 font-bold">×</button>
                            </div>
                            <button @click="addItem" type="button" class="mt-2 text-indigo-600 text-xs font-bold">+ Добавить строку</button>
                            <div class="mt-4 text-right font-black text-indigo-600">Итого: {{ grandTotal.toLocaleString() }} ₽</div>
                        </div>

                        <!-- Управление файлами -->
                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                            <h3 class="font-bold mb-4">Файлы</h3>
                            <!-- Существующие -->
                            <div class="space-y-2 mb-4">
                                <div v-for="file in deal.files" :key="file.id" class="flex justify-between items-center p-2 bg-slate-50 rounded-lg text-xs">
                                    <span>{{ file.original_name }}</span>
                                    <button @click="deleteExistingFile(file.id)" type="button" class="text-rose-500 font-bold hover:underline">Удалить</button>
                                </div>
                            </div>
                            <!-- Новые -->
                            <label class="block text-xs font-bold text-slate-400 mb-2 uppercase">Добавить новые файлы</label>
                            <input type="file" multiple @change="e => form.new_files = Array.from(e.target.files)" class="text-xs text-slate-500">
                        </div>
                    </div>

                    <div class="space-y-6">
                        <!-- Команда (Ответственные) -->
                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                            <h3 class="font-bold mb-4">Команда проекта</h3>
                            <div class="space-y-2 max-h-60 overflow-y-auto border p-3 rounded-xl">
                                <label v-for="user in availableResponsibles" :key="user.id" class="flex items-center gap-2 cursor-pointer py-1">
                                    <input type="checkbox" :value="user.id" v-model="form.responsible_ids" class="rounded text-indigo-600">
                                    <span class="text-sm text-slate-600">{{ user.name }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Задачи -->
                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                            <h3 class="font-bold mb-4">Связанные задачи</h3>
                            <div class="space-y-2">
                                <label v-for="task in activeTasks" :key="task.id" class="flex items-start gap-2 text-xs py-1 border-b border-slate-50 last:border-0">
                                    <input type="checkbox" :value="task.id" v-model="form.task_ids" class="mt-0.5 rounded text-indigo-600">
                                    <span class="text-slate-500">{{ task.title }}</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>
