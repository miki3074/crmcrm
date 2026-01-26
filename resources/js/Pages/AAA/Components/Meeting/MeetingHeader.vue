<script setup>
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
    meeting: Object,
    auth_user_id: Number,
    canManage: Boolean,
});

const emit = defineEmits(['openSettings']);

const statusLabels = {
    'scheduled': 'Планирование',
    'in_progress': 'Начато (Идет)',
    'completed': 'Завершено (Протокол)',
    'on_approval': 'На согласовании',
    'signed': 'Подписано'
};

const changeMeetingStatus = (newStatus) => {
    if (!confirm('Вы уверены, что хотите изменить статус совещания?')) return;
    router.put(route('meetings.status.update', props.meeting.id), { status: newStatus }, { preserveScroll: true });
};

const deleteMeeting = () => {
    if (!confirm('Вы уверены, что хотите удалить совещание?')) return;
    router.delete(route('meetings.destroy', props.meeting.id));
};
</script>

<template>
    <div class="bg-white shadow sm:rounded-lg mb-6 p-6">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <!-- Инфо -->
            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ meeting.title }}</h2>
                <p class="text-gray-500 mb-2">{{ meeting.company.name }}</p>

                <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 mb-3 text-sm">
                    <div class="flex items-center gap-2 bg-gray-50 px-2 py-1 rounded border border-gray-200">
                        <span class="text-gray-500">Создатель:</span>
                        <span class="font-medium text-gray-900">{{ meeting.creator ? meeting.creator.name : 'Неизвестно' }}</span>
                    </div>
                    <div class="flex items-center gap-2 bg-gray-50 px-2 py-1 rounded border border-gray-200">
                        <span class="text-gray-500">Ответственный:</span>
                        <span class="font-medium text-gray-900">{{ meeting.responsible ? meeting.responsible.name : 'Не назначен' }}</span>
                    </div>
                </div>

                <div class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
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

            <!-- Панель управления -->
            <div v-if="canManage" class="flex gap-3">
                <button @click="$emit('openSettings')" class="bg-gray-100 text-gray-700 px-4 py-2 rounded shadow hover:bg-gray-200 transition flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" /></svg>
                    Изменить
                </button>

                <button v-if="meeting.creator_id === auth_user_id" @click="deleteMeeting" class="bg-red-100 text-red-700 px-4 py-2 rounded shadow hover:bg-red-200 transition flex items-center gap-2">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                    Удалить
                </button>

                <button v-if="meeting.status === 'scheduled'" @click="changeMeetingStatus('in_progress')" class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700 transition flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" /></svg>
                    Начать совещание
                </button>

                <button v-if="meeting.status === 'in_progress'" @click="changeMeetingStatus('completed')" class="bg-red-600 text-white px-4 py-2 rounded shadow hover:bg-red-700 transition flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8 7a1 1 0 00-1 1v4a1 1 0 001 1h4a1 1 0 001-1V8a1 1 0 00-1-1H8z" clip-rule="evenodd" /></svg>
                    Завершить совещание
                </button>

                <div v-if="meeting.status === 'completed'" class="text-sm text-gray-500 italic border p-2 rounded">
                    Совещание завершено.
                </div>
            </div>
        </div>
    </div>
</template>
