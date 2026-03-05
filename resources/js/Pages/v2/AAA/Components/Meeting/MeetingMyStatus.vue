<script setup>
import { router } from '@inertiajs/vue3';

const props = defineProps({
    meeting: Object,
    myParticipation: Object
});

const statusMap = { 'invited': 'Приглашен', 'accepted': 'Принято', 'declined': 'Отклонено' };

const changeMyStatus = (newStatus) => {
    router.put(route('meetings.participation.update', props.meeting.id), { status: newStatus }, { preserveScroll: true });
};
</script>

<template>
    <div v-if="myParticipation" class="bg-white shadow sm:rounded-lg p-6 border-l-4"
         :class="{
            'border-blue-500': myParticipation.pivot.status === 'invited',
            'border-green-500': myParticipation.pivot.status === 'accepted',
            'border-red-500': myParticipation.pivot.status === 'declined'
        }">
        <h3 class="text-lg font-medium mb-2">Мое участие</h3>

        <div v-if="myParticipation.pivot.status === 'invited'">
            <div v-if="meeting.status === 'scheduled'">
                <p class="text-sm text-gray-600 mb-3">Вас пригласили. Вы придете?</p>
                <div class="flex gap-2">
                    <button @click="changeMyStatus('accepted')" class="flex-1 bg-green-600 text-white px-3 py-2 rounded text-sm">✓ Принять</button>
                    <button @click="changeMyStatus('declined')" class="flex-1 bg-red-100 text-red-700 px-3 py-2 rounded text-sm">✕ Отклонить</button>
                </div>
            </div>
            <div v-else class="text-sm text-gray-500 italic bg-gray-50 p-3 rounded">
                Совещание началось или завершено.
            </div>
        </div>
        <div v-else>
            <p class="text-sm">Ваш статус: <span class="font-bold">{{ statusMap[myParticipation.pivot.status] }}</span></p>
            <button v-if="meeting.status === 'scheduled'" @click="changeMyStatus('invited')" class="text-xs text-gray-400 underline mt-2">Изменить решение</button>
        </div>
    </div>
</template>
