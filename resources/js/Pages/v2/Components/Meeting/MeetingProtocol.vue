<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    meeting: Object,
    canManage: Boolean,
    isResponsible: Boolean,
});

const form = useForm({
    minutes: props.meeting.minutes || '',
    status: props.meeting.status
});

const updateProtocol = () => {
    form.put(route('meetings.update', props.meeting.id), { preserveScroll: true, onSuccess: () => alert('Сохранено!') });
};

const sendForApproval = () => {
    if(!confirm('Отправить на согласование?')) return;
    form.status = 'on_approval';
    updateProtocol();
};

const approveProtocol = () => {
    if (!confirm('Утвердить протокол?')) return;
    router.post(route('meetings.review', props.meeting.id), { decision: 'approve' }, { preserveScroll: true });
};

// Доработка
const showRejectModal = ref(false);
const rejectReason = ref('');
const submitReject = () => {
    if (!rejectReason.value.trim()) return alert('Укажите причину');
    router.post(route('meetings.review', props.meeting.id), { decision: 'reject', reason: rejectReason.value }, { preserveScroll: true, onSuccess: () => showRejectModal.value = false });
};
</script>

<template>
    <div class="bg-white shadow sm:rounded-lg p-6 relative">
        <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Протокол совещания</h3>

        <div v-if="meeting.rejection_reason && meeting.status !== 'signed'" class="mb-4 bg-red-50 border-l-4 border-red-500 p-4">
            <p class="text-sm text-red-700"><span class="font-bold">Требуется доработка:</span> {{ meeting.rejection_reason }}</p>
        </div>

        <div v-if="canManage && ['scheduled', 'in_progress', 'completed'].includes(meeting.status)">
            <textarea v-model="form.minutes" placeholder="Результаты..." class="w-full border-gray-300 rounded-md shadow-sm h-48"></textarea>
            <div class="mt-4 flex gap-2">
                <button @click="updateProtocol" class="bg-blue-600 text-white px-4 py-2 rounded">Сохранить черновик</button>
                <button @click="sendForApproval" class="bg-green-600 text-white px-4 py-2 rounded">Отправить на согласование</button>
            </div>
        </div>
        <div v-else>
            <div class="prose bg-gray-50 p-4 rounded whitespace-pre-line border border-gray-200">
                {{ meeting.minutes || 'Протокол еще не сформирован' }}
            </div>
            <div v-if="meeting.status === 'on_approval' && isResponsible" class="mt-6 border-t pt-4 flex gap-3">
                <button @click="approveProtocol" class="flex-1 bg-green-600 text-white px-4 py-2 rounded">Утвердить и Подписать</button>
                <button @click="showRejectModal = true" class="flex-1 bg-red-100 text-red-700 px-4 py-2 rounded">На доработку</button>
            </div>
            <div v-if="meeting.status === 'signed'" class="mt-4 text-green-700 font-bold">Протокол подписан.</div>
        </div>

        <!-- Модалка доработки -->
        <div v-if="showRejectModal" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-500 bg-opacity-75">
            <div class="bg-white rounded-lg p-6 max-w-lg w-full">
                <h3 class="text-lg font-medium text-gray-900">На доработку</h3>
                <textarea v-model="rejectReason" class="w-full mt-2 border-gray-300 rounded" rows="4"></textarea>
                <div class="mt-4 flex justify-end gap-2">
                    <button @click="submitReject" class="bg-red-600 text-white px-4 py-2 rounded">Отправить</button>
                    <button @click="showRejectModal = false" class="bg-gray-200 text-gray-700 px-4 py-2 rounded">Отмена</button>
                </div>
            </div>
        </div>
    </div>
</template>
