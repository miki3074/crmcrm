<script setup>
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps({
    show: Boolean,
    meeting: Object,
    available_users: Array
});

const emit = defineEmits(['close']);

const settingsForm = useForm({
    start_time: '',
    participants: [],
});

// Синхронизация данных при открытии
watch(() => props.show, (newVal) => {
    if (newVal) {
        let isoDate = props.meeting.start_time;
        if(isoDate && isoDate.indexOf('T') === -1) isoDate = isoDate.replace(' ', 'T').slice(0, 16);
        settingsForm.start_time = isoDate;
        settingsForm.participants = props.meeting.participants.map(u => u.id);
    }
});

const updateSettings = () => {
    settingsForm.put(route('meetings.update', props.meeting.id), {
        preserveScroll: true,
        onSuccess: () => emit('close')
    });
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-500 bg-opacity-75">
        <div class="bg-white rounded-lg overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full">
            <div class="px-4 pt-5 pb-4 sm:p-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Редактирование</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Дата начала</label>
                        <input type="datetime-local" v-model="settingsForm.start_time" class="mt-1 block w-full rounded border-gray-300 shadow-sm">
                        <div v-if="settingsForm.errors.start_time" class="text-red-500 text-xs mt-1">{{ settingsForm.errors.start_time }}</div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Участники</label>
                        <div class="border rounded-md max-h-60 overflow-y-auto p-2 bg-gray-50">
                            <div v-for="user in available_users" :key="user.id" class="flex items-center py-1">
                                <input type="checkbox" :id="'user-'+user.id" :value="user.id" v-model="settingsForm.participants" class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                                <label :for="'user-'+user.id" class="ml-2 block text-sm text-gray-900 cursor-pointer">{{ user.name }}</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 flex flex-row-reverse gap-2">
                <button @click="updateSettings" :disabled="settingsForm.processing" class="bg-blue-600 text-white px-4 py-2 rounded shadow">Сохранить</button>
                <button @click="$emit('close')" class="bg-white border px-4 py-2 rounded shadow-sm text-gray-700">Отмена</button>
            </div>
        </div>
    </div>
</template>
