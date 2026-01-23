<script setup>
import { ref } from 'vue';
import { computed } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3'; // Добавил router
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    meeting: Object,
    auth_user_id: Number,
});

// Является ли текущий юзер Создателем или Ответственным?
const canManage = computed(() => {
    return props.meeting.creator_id === props.auth_user_id ||
        props.meeting.responsible_id === props.auth_user_id;
});

// Проверяем роль текущего юзера
const isResponsible = props.meeting.responsible_id === props.auth_user_id;

const changeMeetingStatus = (newStatus) => {
    if (!confirm('Вы уверены, что хотите изменить статус совещания?')) return;

    router.put(route('meetings.status.update', props.meeting.id), {
        status: newStatus
    }, {
        preserveScroll: true
    });
};

// Форма для обновления данных (повестка / протокол)
const form = useForm({
    agenda: props.meeting.agenda || '',
    minutes: props.meeting.minutes || '',
    status: props.meeting.status,
});

// Сохранение изменений (для ответственного)
const updateMeeting = () => {
    form.put(route('meetings.update', props.meeting.id), {
        preserveScroll: true,
        onSuccess: () => alert('Сохранено!')
    });
};

// Отправка на согласование
const sendForApproval = () => {
    if(!confirm('Отправить протокол на согласование? Редактирование будет заблокировано.')) return;
    form.status = 'on_approval';
    updateMeeting();
};

// Подписание (для участников)
const signProtocol = () => {
    if(!confirm('Вы подтверждаете подписание протокола?')) return;
    // Здесь нужен отдельный роут для подписи, но для примера используем update или axios
    // Лучше сделать отдельный метод в контроллере sign()
    alert('Функционал подписи нужно реализовать через отдельный контроллер');
};

// Находим данные текущего пользователя в списке участников
const myParticipation = computed(() => {
    return props.meeting.participants.find(p => p.id === props.auth_user_id);
});

// Функция смены статуса (Принять / Отклонить)
const changeMyStatus = (newStatus) => {
    router.put(route('meetings.participation.update', props.meeting.id), {
        status: newStatus
    }, {
        preserveScroll: true,
        onSuccess: () => {
            // Опционально: уведомление
        }
    });
};

const statusMap = {
    'invited': 'Приглашен',
    'accepted': 'Принято',
    'declined': 'Отклонено'
};

const statusLabels = {
    'scheduled': 'Планирование',
    'in_progress': 'Начато (Идет)',
    'completed': 'Завершено (Протокол)',
    'on_approval': 'На согласовании',
    'signed': 'Подписано'
};

const formatDate = (date) => new Date(date).toLocaleString('ru-RU');
</script>

<template>
    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <!-- Шапка -->
            <div class="bg-white shadow sm:rounded-lg mb-6 p-6">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">

                    <!-- Заголовок и Инфо -->
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">{{ meeting.title }}</h2>
                        <p class="text-gray-500">{{ meeting.company.name }}</p>

                        <!-- Бейдж текущего статуса -->
                        <div class="mt-2 inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                             :class="{
                                 'bg-yellow-100 text-yellow-800': meeting.status === 'scheduled',
                                 'bg-blue-100 text-blue-800 animate-pulse': meeting.status === 'in_progress',
                                 'bg-purple-100 text-purple-800': meeting.status === 'completed',
                                 'bg-orange-100 text-orange-800': meeting.status === 'on_approval',
                                 'bg-green-100 text-green-800': meeting.status === 'signed',
                             }">
                            {{ statusLabels[meeting.status] || meeting.status }}
                        </div>
                    </div>

                    <!-- ПАНЕЛЬ УПРАВЛЕНИЯ (Только для Создателя и Ответственного) -->
                    <div v-if="canManage" class="flex gap-3">

                        <!-- Кнопка НАЧАТЬ (если Планирование) -->
                        <button
                            v-if="meeting.status === 'scheduled'"
                            @click="changeMeetingStatus('in_progress')"
                            class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700 transition flex items-center gap-2"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" /></svg>
                            Начать совещание
                        </button>

                        <!-- Кнопка ЗАВЕРШИТЬ (если Идет) -->
                        <button
                            v-if="meeting.status === 'in_progress'"
                            @click="changeMeetingStatus('completed')"
                            class="bg-red-600 text-white px-4 py-2 rounded shadow hover:bg-red-700 transition flex items-center gap-2"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8 7a1 1 0 00-1 1v4a1 1 0 001 1h4a1 1 0 001-1V8a1 1 0 00-1-1H8z" clip-rule="evenodd" /></svg>
                            Завершить совещание
                        </button>

                        <!-- Если ЗАВЕРШЕНО -> кнопка перехода к протоколу (визуальная подсказка) -->
                        <div v-if="meeting.status === 'completed'" class="text-sm text-gray-500 italic border p-2 rounded">
                            Совещание завершено. <br> Ответственный заполняет протокол.
                        </div>

                    </div>
                </div>
            </div>



            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                <!-- Левая колонка: Повестка и Протокол -->
                <div class="md:col-span-2 space-y-6">

                    <!-- Повестка -->
                    <div class="bg-white shadow sm:rounded-lg p-6">
                        <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Повестка дня</h3>

                        <div v-if="isResponsible && meeting.status === 'scheduled'">
                            <textarea v-model="form.agenda" class="w-full border-gray-300 rounded-md shadow-sm h-32"></textarea>
                            <button @click="updateMeeting" class="mt-2 bg-gray-600 text-white px-3 py-1 rounded text-sm">Сохранить повестку</button>
                        </div>
                        <div v-else class="prose bg-gray-50 p-4 rounded">
                            {{ meeting.agenda || 'Повестка не заполнена' }}
                        </div>
                    </div>

                    <!-- Протокол -->
                    <div class="bg-white shadow sm:rounded-lg p-6">
                        <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Протокол совещания</h3>

                        <!-- Если ответственный и статус позволяет редактировать -->
                        <div v-if="isResponsible && ['scheduled', 'draft'].includes(meeting.status)">
                            <textarea v-model="form.minutes" placeholder="Заполните результаты совещания..." class="w-full border-gray-300 rounded-md shadow-sm h-48"></textarea>

                            <div class="mt-4 flex gap-2">
                                <button @click="updateMeeting" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                                    Сохранить черновик
                                </button>
                                <button @click="sendForApproval" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                                    Отправить на согласование
                                </button>
                            </div>
                        </div>

                        <!-- Режим чтения -->
                        <div v-else>
                            <div class="prose bg-gray-50 p-4 rounded whitespace-pre-line">
                                {{ meeting.minutes || 'Протокол еще не сформирован' }}
                            </div>

                            <!-- Кнопка подписать (если статус "на согласовании") -->
                            <div v-if="meeting.status === 'on_approval' && !isResponsible" class="mt-4">
                                <button @click="signProtocol" class="bg-green-600 text-white px-4 py-2 rounded">
                                    Подписать протокол
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Правая колонка: Участники -->
                <div class="md:col-span-1 space-y-6">

                    <!-- БЛОК 1: МОЁ УЧАСТИЕ (Новое!) -->
                    <div v-if="myParticipation" class="bg-white shadow sm:rounded-lg p-6 border-l-4"
                         :class="{
                            'border-blue-500': myParticipation.pivot.status === 'invited',
                            'border-green-500': myParticipation.pivot.status === 'accepted',
                            'border-red-500': myParticipation.pivot.status === 'declined'
                        }">
                        <h3 class="text-lg font-medium mb-2">Мое участие</h3>

                        <!-- Если статус "Приглашен" - показываем кнопки -->
                        <div v-if="myParticipation.pivot.status === 'invited'">
                            <p class="text-sm text-gray-600 mb-3">Вас пригласили на это совещание. Вы придете?</p>
                            <div class="flex gap-2">
                                <button
                                    @click="changeMyStatus('accepted')"
                                    class="flex-1 bg-green-600 text-white px-3 py-2 rounded text-sm hover:bg-green-700 transition"
                                >
                                    ✓ Принять
                                </button>
                                <button
                                    @click="changeMyStatus('declined')"
                                    class="flex-1 bg-red-100 text-red-700 px-3 py-2 rounded text-sm hover:bg-red-200 transition"
                                >
                                    ✕ Отклонить
                                </button>
                            </div>
                        </div>

                        <!-- Если уже ответил - показываем текущий статус -->
                        <div v-else>
                            <p class="text-sm">
                                Ваш статус:
                                <span class="font-bold" :class="{
                                    'text-green-600': myParticipation.pivot.status === 'accepted',
                                    'text-red-600': myParticipation.pivot.status === 'declined'
                                }">
                                    {{ statusMap[myParticipation.pivot.status] }}
                                </span>
                            </p>
                            <!-- Можно добавить маленькую кнопку "Изменить решение", если нужно -->
                            <button v-if="meeting.status === 'scheduled'" @click="changeMyStatus('invited')" class="text-xs text-gray-400 underline mt-2">
                                Изменить решение
                            </button>
                        </div>
                    </div>

                    <!-- БЛОК 2: СПИСОК УЧАСТНИКОВ -->
                    <div class="bg-white shadow sm:rounded-lg p-6">
                        <h3 class="text-lg font-medium mb-4">Список участников</h3>
                        <ul class="space-y-3">
                            <li v-for="user in meeting.participants" :key="user.id" class="flex items-center justify-between border-b pb-2 last:border-0">
                                <div>
                                    <div class="text-sm text-gray-900 font-medium">{{ user.name }}</div>
                                    <!-- Показываем статус участника -->
                                    <div class="text-xs">
                                        <span v-if="user.pivot.status === 'invited'" class="text-gray-400">Ожидание ответа</span>
                                        <span v-else-if="user.pivot.status === 'accepted'" class="text-green-600">Подтвердил</span>
                                        <span v-else-if="user.pivot.status === 'declined'" class="text-red-600">Отклонил</span>
                                    </div>
                                </div>

                                <!-- Иконка статуса -->
                                <div v-if="user.pivot.status === 'accepted'" class="text-green-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                </div>
                                <div v-if="user.pivot.status === 'declined'" class="text-red-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                </div>
                            </li>
                        </ul>
                    </div>

                </div> <!-- Конец правой колонки -->

            </div>
        </div>
    </AuthenticatedLayout>
</template>
