<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    meeting: Object,
    canManage: Boolean,
    myParticipation: Object
});

// Форма редактирования повестки
const form = useForm({
    agenda: props.meeting.agenda || '',
});

const updateAgenda = () => {
    form.put(route('meetings.update', props.meeting.id), {
        preserveScroll: true,
        onSuccess: () => alert('Повестка сохранена')
    });
};

// --- Логика отзывов ---
const agendaFeedbackForm = useForm({ status: '', comment: '' });
const showAgendaCommentInput = ref(false);

const openAgendaReject = () => {
    agendaFeedbackForm.status = 'rejected';
    showAgendaCommentInput.value = true;
};

const submitAgendaFeedback = (status) => {
    agendaFeedbackForm.status = status;
    if (status === 'approved') agendaFeedbackForm.comment = null;

    if (status === 'rejected' && !agendaFeedbackForm.comment) {
        alert('Пожалуйста, напишите, что именно нужно исправить в повестке.');
        return;
    }

    agendaFeedbackForm.post(route('meetings.agenda.feedback', props.meeting.id), {
        preserveScroll: true,
        onSuccess: () => { showAgendaCommentInput.value = false; }
    });
};

const markAsFixed = (participantId) => {
    router.post(route('meetings.agenda.fixed', [props.meeting.id, participantId]), {}, { preserveScroll: true });
};
</script>

<template>
    <div class="bg-white shadow sm:rounded-lg p-6">
        <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Повестка</h3>

        <!-- Редактирование или Просмотр -->
        <div v-if="canManage && ['scheduled', 'in_progress', 'completed'].includes(meeting.status)" class="mb-4">
            <textarea v-model="form.agenda" class="w-full border-gray-300 rounded-md shadow-sm h-32"></textarea>
            <button @click="updateAgenda" class="mt-2 bg-gray-600 text-white px-3 py-1 rounded text-sm">Сохранить повестку</button>
        </div>
        <div v-else class="prose bg-gray-50 p-4 rounded mb-4 whitespace-pre-line border">
            {{ meeting.agenda || 'Повестка не заполнена' }}
        </div>

        <!-- Отзывы (для менеджера) -->
        <div v-if="canManage" class="mt-6 border-t pt-4">
            <h4 class="text-sm font-bold text-gray-700 mb-2">Отзывы участников:</h4>
            <div v-if="meeting.participants.some(p => p.pivot.agenda_status !== 'pending')" class="space-y-3">
                <div v-for="p in meeting.participants" :key="p.id" class="text-sm">
                    <div v-if="p.pivot.agenda_status === 'rejected'" class="bg-red-50 p-3 rounded border border-red-100 flex justify-between items-start">
                        <div>
                            <div class="flex items-center gap-2 font-bold text-red-700"><span>✕ {{ p.name }}:</span></div>
                            <p class="text-gray-700 mt-1 italic">"{{ p.pivot.agenda_comment }}"</p>
                        </div>
                        <button @click="markAsFixed(p.id)" class="ml-4 bg-blue-600 text-white text-xs px-3 py-1 rounded">Исправлено</button>
                    </div>
                    <div v-else-if="p.pivot.agenda_status === 'fixed'" class="bg-yellow-50 p-3 rounded text-gray-600">
                        <span class="font-bold text-yellow-700">⏳ {{ p.name }}</span> - ждем подтверждения
                    </div>
                    <div v-else-if="p.pivot.agenda_status === 'approved'" class="text-green-700 px-2">
                        ✓ {{ p.name }} согласовал
                    </div>
                </div>
            </div>
            <div v-else class="text-sm text-gray-400 italic">Пока отзывов нет.</div>
        </div>

        <!-- Кнопки голосования (для участника) -->
        <div v-if="myParticipation && meeting.status === 'scheduled' && meeting.agenda" class="mt-6 border-t pt-4">
            <div v-if="['pending', 'fixed'].includes(myParticipation.pivot.agenda_status) || showAgendaCommentInput">
                <div v-if="myParticipation.pivot.agenda_status === 'fixed' && !showAgendaCommentInput" class="mb-3 bg-blue-50 text-blue-800 p-2 rounded text-sm">
                    <strong>Изменения внесены!</strong> Проверьте и согласуйте.
                </div>

                <div v-if="!showAgendaCommentInput" class="flex gap-3">
                    <button @click="submitAgendaFeedback('approved')" class="bg-green-100 text-green-800 px-4 py-2 rounded text-sm font-medium">✓ Согласовать</button>
                    <button @click="openAgendaReject" class="bg-gray-100 text-gray-700 px-4 py-2 rounded text-sm font-medium">? Есть замечания</button>
                </div>
                <div v-else class="mt-2 bg-gray-50 p-3 rounded">
                    <textarea v-model="agendaFeedbackForm.comment" class="w-full border-gray-300 rounded text-sm p-2 mb-2" rows="2" placeholder="Опишите замечания..."></textarea>
                    <div class="flex gap-2">
                        <button @click="submitAgendaFeedback('rejected')" class="bg-red-600 text-white px-3 py-1 rounded text-sm">Отправить</button>
                        <button @click="showAgendaCommentInput = false" class="text-gray-500 text-sm px-2">Отмена</button>
                    </div>
                </div>
            </div>
            <div v-else class="flex items-center justify-between bg-gray-50 p-3 rounded">
                <div>
                    <span v-if="myParticipation.pivot.agenda_status === 'approved'" class="text-green-700 font-bold text-sm">✓ Вы согласовали</span>
                    <span v-if="myParticipation.pivot.agenda_status === 'rejected'" class="text-red-700 font-bold text-sm">✕ Вы запросили изменения</span>
                </div>
                <button @click="openAgendaReject" class="text-xs text-blue-600 underline">Изменить решение</button>
            </div>
        </div>
    </div>
</template>
