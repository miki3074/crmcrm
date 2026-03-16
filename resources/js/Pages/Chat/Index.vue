<template>
    <div class="flex h-screen bg-gray-100 overflow-hidden">

        <!-- Столбец 1: Список чатов (Группы и Коллеги) - ТЕПЕРЬ ОБЩИЙ -->
        <div class="w-72 bg-white border-r flex flex-col">
            <div class="p-4 font-bold text-lg border-b flex items-center gap-2">
                <Link :href="route('dashboard')">
                    <ApplicationLogo class="h-6 w-auto fill-current text-gray-600" />
                </Link>
                <span>Сообщения</span>
            </div>

            <div class="flex-1 overflow-y-auto p-2">
                <!-- Группы -->
<!--                <div class="flex justify-between items-center px-2 mt-4 mb-2 text-gray-500 uppercase text-xs font-bold">-->
<!--                    Группы-->
<!--                    <button @click="isModalOpen = true" class="text-blue-500 text-lg">+</button>-->
<!--                </div>-->
<!--                <div v-for="group in groups" :key="'g'+group.id"-->
<!--                     @click="openChat('group', group.id)"-->
<!--                     :class="['p-2 mb-1 rounded cursor-pointer transition',-->
<!--                              chatType === 'group' && targetId === group.id ? 'bg-blue-100 text-blue-700' : 'hover:bg-gray-100']">-->
<!--                    # {{ group.name }}-->
<!--                </div>-->

                <!-- Личные сообщения (без дублей) -->
                <div class="px-2 mt-6 mb-2 text-gray-500 uppercase text-xs font-bold">Личные сообщения</div>
                <div v-for="user in colleagues" :key="'u'+user.id"
                     @click="openChat('user', user.id)"
                     :class="['p-2 mb-1 rounded cursor-pointer transition',
                              chatType === 'user' && targetId === user.id ? 'bg-blue-100 text-blue-700' : 'hover:bg-gray-100']">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center text-xs text-white">
                            {{ user.name.substring(0, 1) }}
                        </div>
                        {{ user.name }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Столбец 2: Окно сообщений -->
        <div class="flex-1 flex flex-col bg-white">
            <template v-if="targetId">
                <!-- Заголовок чата -->
                <div class="p-4 border-b font-medium text-gray-700 bg-gray-50">
                    {{ chatType === 'user' ? colleagues.find(u => u.id === targetId)?.name : groups.find(g => g.id === targetId)?.name }}
                </div>

                <!-- Сообщения -->
                <div class="flex-1 overflow-y-auto p-6 space-y-4" ref="msgContainer">
                    <div v-for="msg in messages" :key="msg.id"
                         :class="['flex flex-col', msg.sender_id === $page.props.auth.user.id ? 'items-end' : 'items-start']">
                        <span class="text-[10px] text-gray-400 mb-1">{{ msg.sender.name }}</span>
                        <div :class="['max-w-md px-4 py-2 rounded-2xl',
                                      msg.sender_id === $page.props.auth.user.id ? 'bg-indigo-600 text-white rounded-tr-none' : 'bg-gray-100 text-gray-800 rounded-tl-none']">
                            {{ msg.message }}
                        </div>
                    </div>
                </div>

                <!-- Поле ввода -->
                <div class="p-4 border-t">
                    <form @submit.prevent="sendMessage" class="flex gap-2">
                        <input v-model="form.message" type="text" placeholder="Ваше сообщение..."
                               class="flex-1 border-gray-300 rounded-full px-4 focus:ring-indigo-500">
                        <button :disabled="form.processing || !form.message.trim()"
                                class="px-6 py-2 bg-indigo-600 text-white rounded-full disabled:opacity-50">
                            Отправить
                        </button>
                    </form>
                </div>
            </template>
            <div v-else class="flex-1 flex items-center justify-center text-gray-400 flex-col gap-2">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                Выберите чат для начала общения
            </div>
        </div>

        <!-- Модалка создания группы (нужно добавить выбор компании внутри или убрать привязку) -->
    </div>
</template>

<script setup>
import { ref, nextTick, watch } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import ApplicationLogo from "@/Components/ApplicationLogo.vue";

const props = defineProps({
    groups: Array, colleagues: Array, messages: Array,
    chatType: String, targetId: Number
});

const isModalOpen = ref(false);
const msgContainer = ref(null);

const form = useForm({
    message: '',
    type: props.chatType,
    target_id: props.targetId
});

const openChat = (type, id) => {
    // Теперь URL проще: /chat/user/5 или /chat/group/10
    router.get(`/chat/${type}/${id}`);
};

const sendMessage = () => {
    form.type = props.chatType;
    form.target_id = props.targetId;
    form.post('/chat/send', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('message');
            scrollToBottom();
        }
    });
};

const scrollToBottom = () => {
    nextTick(() => {
        if (msgContainer.value) msgContainer.value.scrollTop = msgContainer.value.scrollHeight;
    });
};

// Скроллим вниз при загрузке сообщений
watch(() => props.messages, () => {
    scrollToBottom();
}, { deep: true });
</script>
