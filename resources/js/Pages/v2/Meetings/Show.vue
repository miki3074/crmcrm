<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

// Импорт компонентов (подставьте правильные пути)
import MeetingHeader from '../AAA/Components/Meeting/MeetingHeader.vue';
import MeetingAgenda from '../AAA/Components/Meeting/MeetingAgenda.vue';
import MeetingProtocol from '../AAA/Components/Meeting/MeetingProtocol.vue';
import MeetingDocuments from '../AAA/Components/Meeting/ MeetingDocuments.vue';
import MeetingMyStatus from '../AAA/Components/Meeting/MeetingMyStatus.vue';
import MeetingParticipants from '../AAA/Components/Meeting/MeetingParticipants.vue';
import MeetingSettingsModal from '../AAA/Components/Meeting//MeetingSettingsModal.vue';

const props = defineProps({
    meeting: Object,
    auth_user_id: Number,
    available_users: Array,
});

// Общие вычисляемые свойства
const canManage = computed(() => {
    return props.meeting.creator_id === props.auth_user_id ||
        props.meeting.responsible_id === props.auth_user_id;
});

const isResponsible = computed(() => {
    return props.meeting.responsible_id === props.auth_user_id;
});

const myParticipation = computed(() => {
    return props.meeting.participants.find(p => p.id === props.auth_user_id);
});

// Управление модалкой настроек (открывается из Header)
const showSettingsModal = ref(false);
</script>

<template>
    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">

            <!-- 1. Шапка -->
            <MeetingHeader
                :meeting="meeting"
                :auth_user_id="auth_user_id"
                :canManage="canManage"
                @openSettings="showSettingsModal = true"
            />

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Левая колонка -->
                <div class="md:col-span-2 space-y-6">

                    <!-- 2. Повестка -->
                    <MeetingAgenda
                        :meeting="meeting"
                        :canManage="canManage"
                        :myParticipation="myParticipation"
                    />

                    <!-- 3. Документы -->
                    <MeetingDocuments
                        :meeting="meeting"
                        :auth_user_id="auth_user_id"
                        :canManage="canManage"
                        :myParticipation="myParticipation"
                    />

                    <!-- 4. Протокол -->
                    <MeetingProtocol
                        :meeting="meeting"
                        :canManage="canManage"
                        :isResponsible="isResponsible"
                    />
                </div>

                <!-- Правая колонка -->
                <div class="md:col-span-1 space-y-6">

                    <!-- 5. Мое участие -->
                    <MeetingMyStatus
                        :meeting="meeting"
                        :myParticipation="myParticipation"
                    />

                    <!-- 6. Список участников -->
                    <MeetingParticipants
                        :participants="meeting.participants"
                    />
                </div>
            </div>
        </div>

        <!-- 7. Модалка настроек -->
        <MeetingSettingsModal
            :show="showSettingsModal"
            :meeting="meeting"
            :available_users="available_users"
            @close="showSettingsModal = false"
        />

    </AuthenticatedLayout>
</template>
