<script setup>
import { ref, watch, computed } from 'vue';
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
    project_id: '', // Вспомогательное поле для фильтрации задач
    task_id: '',
    subtask_id: '',
    title: '',
    start_time: '',
    responsible_id: '',
    agenda: '',
    participants: [], // Массив ID (для чекбоксов)
});

// Данные для списков
const projects = ref([]);
const tasks = ref([]);
const subtasks = ref([]);

// Пользователи
const allCompanyUsers = ref([]); // Полный список сотрудников компании
const filteredUsers = ref([]);   // Список, который отображается (может быть урезан задачей)
const isLoading = ref(false);

// --- 1. Смена Компании ---
watch(() => form.company_id, async (newId) => {
    resetFields(['project', 'task', 'subtask', 'users']);
    if (!newId) return;

    isLoading.value = true;
    try {
        // 1. Грузим пользователей компании
        const usersRes = await axios.get(`/api/companies/${newId}/users`);
        allCompanyUsers.value = usersRes.data;
        filteredUsers.value = usersRes.data; // По умолчанию показываем всех

        // 2. Грузим проекты компании
        const projectsRes = await axios.get(`/api/companies/${newId}/projects`);
        projects.value = projectsRes.data;
    } catch (e) {
        console.error(e);
    } finally {
        isLoading.value = false;
    }
});

// --- 2. Смена Проекта ---
watch(() => form.project_id, async (newId) => {
    resetFields(['task', 'subtask']);
    if (!newId) return;

    try {
        const res = await axios.get(`/api/projects/${newId}/tasks`);
        tasks.value = res.data;
    } catch (e) { console.error(e); }
});

// --- 3. Смена Задачи (Фильтрация людей) ---
watch(() => form.task_id, async (newId) => {
    form.subtask_id = '';
    subtasks.value = [];

    // Если задачу убрали - возвращаем полный список людей
    if (!newId) {
        filteredUsers.value = allCompanyUsers.value;
        return;
    }

    try {
        // Грузим подзадачи
        const subRes = await axios.get(`/api/tasks/${newId}/subtasks`);
        subtasks.value = subRes.data;

        // Грузим участников конкретной задачи для фильтрации списка
        // Вам нужно создать такой роут или возвращать users вместе с задачей
        const usersRes = await axios.get(`/api/tasks/${newId}/users`);

        // Обновляем список для чекбоксов
        filteredUsers.value = usersRes.data;

        // Очищаем выбранных участников, которых нет в новом списке, чтобы не было ошибок валидации
        const allowedIds = filteredUsers.value.map(u => u.id);
        form.participants = form.participants.filter(id => allowedIds.includes(id));

    } catch (e) { console.error(e); }
});

// --- 4. Смена Подзадачи ---
watch(() => form.subtask_id, async (newId) => {
    if (!newId && form.task_id) {
        // Если сняли подзадачу, но задача выбрана - возвращаем участников задачи
        // (Логику можно упростить, просто перезапросив участников задачи)
        const usersRes = await axios.get(`/api/tasks/${form.task_id}/users`);
        filteredUsers.value = usersRes.data;
        return;
    }

    if (newId) {
        try {
            const res = await axios.get(`/api/subtasks/${newId}/users`);
            filteredUsers.value = res.data;

            // Чистим выбранных
            const allowedIds = filteredUsers.value.map(u => u.id);
            form.participants = form.participants.filter(id => allowedIds.includes(id));
        } catch (e) { console.error(e); }
    }
});

// Хелпер для сброса данных
function resetFields(fields) {
    if (fields.includes('project')) { form.project_id = ''; projects.value = []; }
    if (fields.includes('task')) { form.task_id = ''; tasks.value = []; }
    if (fields.includes('subtask')) { form.subtask_id = ''; subtasks.value = []; }
    if (fields.includes('users')) {
        allCompanyUsers.value = [];
        filteredUsers.value = [];
        form.participants = [];
        form.responsible_id = '';
    }
}

const submit = () => {
    form.post(route('meetings.store'));
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="max-w-4xl mx-auto p-6 bg-white rounded shadow">
            <h1 class="text-2xl font-bold mb-6 text-gray-800">Создать совещание</h1>

            <form @submit.prevent="submit" class="space-y-6">

                <!-- Блок 1: Контекст (Компания -> Проект -> Задача) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50 p-4 rounded-lg border">

                    <!-- Компания -->
                    <div class="col-span-1 md:col-span-2">
                        <label class="block font-semibold text-gray-700 mb-1">Компания <span class="text-red-500">*</span></label>
                        <select v-model="form.company_id" class="w-full border-gray-300 rounded shadow-sm focus:ring-blue-500" required>
                            <option value="" disabled>Выберите компанию</option>
                            <option v-for="c in available_companies" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>

                    <!-- Проект (опционально) -->
                    <div v-if="form.company_id">
                        <label class="block font-semibold text-gray-700 mb-1">Проект</label>
                        <select v-model="form.project_id" class="w-full border-gray-300 rounded shadow-sm">
                            <option value="">-- Без проекта --</option>
                            <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                    </div>

                    <!-- Задача (опционально) -->
                    <div v-if="form.project_id">
                        <label class="block font-semibold text-gray-700 mb-1">Задача</label>
                        <select v-model="form.task_id" class="w-full border-gray-300 rounded shadow-sm">
                            <option value="">-- Без задачи --</option>
                            <option v-for="t in tasks" :key="t.id" :value="t.id">{{ t.title }} ({{ t.status }})</option>
                        </select>
                    </div>

                    <!-- Подзадача (опционально) -->
                    <div v-if="form.task_id && subtasks.length > 0">
                        <label class="block font-semibold text-gray-700 mb-1">Подзадача</label>
                        <select v-model="form.subtask_id" class="w-full border-gray-300 rounded shadow-sm">
                            <option value="">-- без участников подзадачи --</option>
                            <option v-for="s in subtasks" :key="s.id" :value="s.id">{{ s.title }}</option>
                        </select>
                    </div>
                </div>

                <!-- Блок 2: Основная инфо -->
                <div v-if="form.company_id">
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block font-semibold text-gray-700 mb-1">Тема совещания <span class="text-red-500">*</span></label>
                            <input v-model="form.title" type="text" class="w-full border-gray-300 rounded shadow-sm" required placeholder="Например: Обсуждение спринта">
                            <div v-if="form.errors.title" class="text-red-500 text-sm mt-1">{{ form.errors.title }}</div>
                        </div>

                        <div>
                            <label class="block font-semibold text-gray-700 mb-1">Дата и время <span class="text-red-500">*</span></label>
                            <input v-model="form.start_time" type="datetime-local" class="w-full border-gray-300 rounded shadow-sm" required>
                        </div>
                    </div>

                    <!-- Ответственный -->
                    <div class="mt-4">
                        <label class="block font-semibold text-gray-700 mb-1">Ответственный (Секретарь) <span class="text-red-500">*</span></label>
                        <select v-model="form.responsible_id" class="w-full border-gray-300 rounded shadow-sm" required>
                            <option value="" disabled>Выберите ответственного</option>
                            <!-- Здесь показываем ВСЕХ сотрудников компании, даже если выбрана задача,
                                 так как секретарь может быть извне, но в рамках валидации бэкенда лучше чтобы он был в задаче -->
                            <option v-for="user in filteredUsers" :key="user.id" :value="user.id">
                                {{ user.name }}
                            </option>
                        </select>
                        <p v-if="form.task_id" class="text-xs text-blue-600 mt-1">
                            ℹ️ Список ограничен участниками выбранной задачи.
                        </p>
                    </div>

                    <!-- Участники (CHECKBOX LIST) -->
                    <div class="mt-6">
                        <label class="block font-semibold text-gray-700 mb-2">Участники</label>

                        <div v-if="isLoading" class="text-gray-500 text-sm">Загрузка списка...</div>

                        <div v-else class="border border-gray-300 rounded-lg p-4 max-h-60 overflow-y-auto bg-gray-50">
                            <div v-if="filteredUsers.length === 0" class="text-gray-500 text-center">
                                Нет доступных сотрудников
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <label
                                    v-for="user in filteredUsers"
                                    :key="user.id"
                                    class="flex items-center space-x-3 p-2 hover:bg-white rounded cursor-pointer transition select-none"
                                    :class="{'bg-blue-50 border-blue-200 border': form.participants.includes(user.id)}"
                                >
                                    <input
                                        type="checkbox"
                                        :value="user.id"
                                        v-model="form.participants"
                                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                    >
                                    <div class="text-sm">
                                        <div class="font-medium text-gray-800">{{ user.name }}</div>
                                        <div class="text-gray-500 text-xs">{{ user.email }}</div>
                                    </div>
                                </label>
                            </div>
                        </div>
                        <div class="flex justify-between mt-2 text-sm text-gray-500">
                            <span>Выбрано: {{ form.participants.length }}</span>
                            <button type="button" @click="form.participants = filteredUsers.map(u => u.id)" class="text-blue-600 hover:underline">
                                Выбрать всех
                            </button>
                        </div>
                        <div v-if="form.errors.participants" class="text-red-500 text-sm mt-1">{{ form.errors.participants }}</div>
                    </div>

                    <!-- Повестка -->
                    <div class="mt-6">
                        <label class="block font-semibold text-gray-700 mb-1">Повестка дня</label>
                        <textarea v-model="form.agenda" class="w-full border-gray-300 rounded shadow-sm h-24" placeholder="Краткий план мероприятия..."></textarea>
                    </div>

                    <!-- Кнопка -->
                    <div class="mt-8">
                        <button
                            type="submit"
                            class="w-full bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition disabled:opacity-50 shadow-md"
                            :disabled="form.processing"
                        >
                            <span v-if="form.processing">Отправка...</span>
                            <span v-else>Создать совещание</span>
                        </button>
                    </div>
                </div>

                <div v-else class="text-center py-10 text-gray-400 bg-gray-50 rounded border border-dashed">
                    Выберите компанию, чтобы начать заполнение
                </div>

            </form>
        </div>
    </AuthenticatedLayout>
</template>
