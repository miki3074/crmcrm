<!-- resources/js/Pages/Meetings/Create.vue -->
<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

// Пропсы от контроллера
const props = defineProps({
    available_companies: Array,
});

// Форма
const form = useForm({
    company_id: '',
    title: '',
    start_time: '',
    responsible_id: '',
    agenda: '',
    participants: [], // Массив ID
});

// Локальное состояние для списка сотрудников выбранной компании
const companyUsers = ref([]);
const isLoadingUsers = ref(false);

// Следим за выбором компании
watch(() => form.company_id, async (newCompanyId) => {
    // Сбрасываем зависимые поля
    form.responsible_id = '';
    form.participants = [];
    companyUsers.value = [];

    if (!newCompanyId) return;

    isLoadingUsers.value = true;
    try {
        // Делаем запрос к нашему API методу
        const response = await axios.get(`/api/company/${newCompanyId}/users`);
        companyUsers.value = response.data;
    } catch (error) {
        console.error('Ошибка загрузки сотрудников', error);
    } finally {
        isLoadingUsers.value = false;
    }
});

const submit = () => {
    form.post(route('meetings.store'));
};
</script>

<template>
    <AuthenticatedLayout>
    <div class="p-6 bg-white rounded shadow">
        <h1 class="text-2xl mb-4">Создать совещание</h1>

        <form @submit.prevent="submit">

            <!-- 1. Выбор компании -->
            <div class="mb-4">
                <label class="block font-bold">Компания</label>
                <select v-model="form.company_id" class="border p-2 w-full" required>
                    <option value="" disabled>Выберите компанию</option>
                    <option v-for="company in available_companies" :key="company.id" :value="company.id">
                        {{ company.name }}
                    </option>
                </select>
            </div>

            <!-- Показываем остальное только если компания выбрана -->
            <div v-if="form.company_id">

                <!-- Тема -->
                <div class="mb-4">
                    <label class="block font-bold">Тема совещания</label>
                    <input v-model="form.title" type="text" class="border p-2 w-full" required>
                </div>

                <!-- Дата -->
                <div class="mb-4">
                    <label class="block font-bold">Дата и время</label>
                    <input v-model="form.start_time" type="datetime-local" class="border p-2 w-full" required>
                </div>

                <!-- Ответственный (выбор из companyUsers) -->
                <div class="mb-4">
                    <label class="block font-bold">Ответственный (Секретарь)</label>
                    <select v-model="form.responsible_id" class="border p-2 w-full" required :disabled="isLoadingUsers">
                        <option value="" disabled>Выберите ответственного</option>
                        <option v-for="user in companyUsers" :key="user.id" :value="user.id">
                            {{ user.name }} ({{ user.email }})
                        </option>
                    </select>
                    <p class="text-sm text-gray-500">Этот человек будет заполнять повестку и протокол.</p>
                </div>

                <!-- Участники (Multiple select) -->
                <div class="mb-4">
                    <label class="block font-bold">Участники</label>
                    <select v-model="form.participants" multiple class="border p-2 w-full h-32" :disabled="isLoadingUsers">
                        <option v-for="user in companyUsers" :key="user.id" :value="user.id">
                            {{ user.name }}
                        </option>
                    </select>
                    <p class="text-sm text-gray-500">Зажмите Ctrl (Cmd) для выбора нескольких.</p>
                </div>

                <!-- Повестка (Agenda) -->
                <div class="mb-4">
                    <label class="block font-bold">Повестка дня (План)</label>
                    <textarea v-model="form.agenda" class="border p-2 w-full h-24"></textarea>
                </div>

                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600" :disabled="form.processing">
                    Создать и отправить приглашения
                </button>
            </div>

            <div v-else class="text-gray-500 mt-4">
                Сначала выберите компанию, чтобы увидеть список сотрудников.
            </div>

        </form>
    </div>
    </AuthenticatedLayout>
</template>
