<template>


    <div class="flex h-screen bg-gray-100 overflow-hidden">

        <!-- Столбец 1: Компании -->
        <div class="w-20 bg-slate-800 flex flex-col items-center py-4 gap-4 text-white">
            <Link :href="route('dashboard')" class="flex-shrink-0 transition-transform duration-300 hover:scale-105">
                <ApplicationLogo class="block h-8 w-auto fill-current text-gray-800 dark:text-gray-200" />
            </Link>
            <div v-for="company in companies" :key="company.id"
                 @click="router.get(`/chat/${company.id}`)"
                 :title="company.name"
                 :class="['w-12 h-12 rounded-2xl flex items-center justify-center cursor-pointer transition-all hover:rounded-xl',
                          selectedCompanyId === company.id ? 'bg-blue-600' : 'bg-slate-700']">
                {{ company.name.substring(0, 2) }}
            </div>
        </div>

        <!-- Столбец 2: Чаты (Группы и Коллеги) -->
        <div class="w-64 bg-white border-r flex flex-col">
            <div class="p-4 font-bold text-lg border-b">Чаты</div>
            <div class="flex-1 overflow-y-auto p-2" v-if="selectedCompanyId">

                <div class="flex justify-between items-center px-2 mt-4 mb-2 text-gray-500 uppercase text-xs font-bold">
                    Группы
                    <button @click="isModalOpen = true" class="text-blue-500 text-lg">+</button>
                </div>
                <div v-for="group in groups" :key="group.id"
                     @click="openChat('group', group.id)"
                     :class="['p-2 mb-1 rounded cursor-pointer', chatType === 'group' && targetId === group.id ? 'bg-blue-100 text-blue-700' : 'hover:bg-gray-100']">
                    # {{ group.name }}
                </div>

                <div class="px-2 mt-6 mb-2 text-gray-500 uppercase text-xs font-bold">Личные сообщения</div>
                <div v-for="user in colleagues" :key="user.id"
                     @click="openChat('user', user.id)"
                     :class="['p-2 mb-1 rounded cursor-pointer', chatType === 'user' && targetId === user.id ? 'bg-blue-100 text-blue-700' : 'hover:bg-gray-100']">
                    {{ user.name }}
                </div>
            </div>
        </div>

        <!-- Столбец 3: Окно сообщений -->
        <div class="flex-1 flex flex-col bg-white">
            <template v-if="targetId">
                <!-- Сообщения -->
                <div class="flex-1 overflow-y-auto p-6 space-y-4" ref="msgContainer">
                    <div v-for="msg in messages" :key="msg.id"
                         :class="['flex flex-col', msg.sender_id === $page.props.auth.user.id ? 'items-end' : 'items-start']">
                        <span class="text-[10px] text-gray-400 mb-1">{{ msg.sender.name }}</span>
                        <div :class="['max-w-md px-4 py-2 rounded-2xl',
                                      msg.sender_id === $page.props.auth.user.id ? 'bg-blue-600 text-white rounded-tr-none' : 'bg-gray-200 text-gray-800 rounded-tl-none']">
                            {{ msg.message }}
                        </div>
                    </div>
                </div>

                <!-- Поле ввода -->
                <div class="p-4 border-t">
                    <form @submit.prevent="sendMessage" class="flex gap-2">
                        <input v-model="form.message" type="text" placeholder="Ваше сообщение..."
                               class="flex-1 border-gray-300 rounded-full px-4 focus:ring-blue-500">
                        <button
                            @click="sendMessage"
                            :disabled="form.processing || !form.message.trim()"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="form.processing">Отправка...</span>
                            <span v-else>Отправить</span>
                        </button>
                    </form>
                </div>
            </template>
            <div v-else class="flex-1 flex items-center justify-center text-gray-400">
                Выберите, кому написать
            </div>
        </div>

        <!-- Модалка создания группы -->
        <div v-if="isModalOpen" class="fixed inset-0 bg-black/50 flex items-center justify-center p-4">
            <div class="bg-white p-6 rounded-xl w-96">
                <h3 class="font-bold mb-4">Создать группу</h3>
                <input v-model="groupForm.name" type="text" placeholder="Название группы" class="w-full border rounded mb-4">
                <div class="max-h-48 overflow-y-auto mb-4 border p-2">
                    <div v-for="user in colleagues" :key="user.id" class="flex items-center gap-2 mb-2">
                        <input type="checkbox" :value="user.id" v-model="groupForm.user_ids">
                        <span>{{ user.name }}</span>
                    </div>
                </div>
                <div class="flex justify-end gap-2">
                    <button @click="isModalOpen = false" class="px-4 py-2 text-gray-500">Отмена</button>
                    <button @click="submitCreateGroup" class="px-4 py-2 bg-blue-600 text-white rounded">Создать</button>
                </div>
            </div>
        </div>
    </div>

</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick, watch } from 'vue';
import {Link, router, useForm} from '@inertiajs/vue3';
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";

const props = defineProps({
    companies: Array, groups: Array, colleagues: Array, messages: Array,
    selectedCompanyId: Number, chatType: String, targetId: Number
});

const isModalOpen = ref(false);
const msgContainer = ref(null);
const form = useForm({ message: '', company_id: props.selectedCompanyId, type: props.chatType, target_id: props.targetId });
const groupForm = useForm({ name: '', company_id: props.selectedCompanyId, user_ids: [] });

const openChat = (type, id) => {
    router.get(`/chat/${props.selectedCompanyId}/${type}/${id}`);
};

const sendMessage = () => {
    form.type = props.chatType;
    form.target_id = props.targetId;
    form.company_id = props.selectedCompanyId;
    form.post('/chat/send', {
        preserveScroll: true,
        onSuccess: () => { form.reset('message'); scrollToBottom(); }
    });
};

const submitCreateGroup = () => {
    groupForm.company_id = props.selectedCompanyId;
    groupForm.post('/chat/create-group', {
        onSuccess: () => { isModalOpen.value = false; groupForm.reset(); }
    });
};

const scrollToBottom = () => {
    nextTick(() => { if (msgContainer.value) msgContainer.value.scrollTop = msgContainer.value.scrollHeight; });
};


</script>
