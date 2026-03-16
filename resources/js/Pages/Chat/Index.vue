<template>
    <div class="flex h-screen bg-slate-50 overflow-hidden font-sans">

        <!-- СТОЛБЕЦ 1: Сайдбар с чатами -->
        <div class="w-80 bg-white border-r border-slate-200 flex flex-col z-20 shadow-sm">
            <!-- Заголовок -->
            <div class="p-4 h-16 border-b border-slate-100 flex items-center justify-between bg-white">
                <div class="flex items-center gap-3">
                    <Link :href="route('dashboard')" class="group">
                        <div class="p-2 bg-indigo-600 rounded-lg group-hover:rotate-12 transition-transform shadow-indigo-200 shadow-lg">
                            <ApplicationLogo class="w-5 h-5 fill-current text-white" />
                        </div>
                    </Link>
                    <h1 class="font-bold text-xl text-slate-800 tracking-tight">Чаты</h1>
                </div>
            </div>

            <!-- Поиск (декоративный) -->
            <div class="px-4 py-3">
                <div class="relative">
                    <input type="text" placeholder="Поиск..." class="w-full bg-slate-100 border-none rounded-xl py-2 pl-9 text-sm focus:ring-2 focus:ring-indigo-500/20 transition-all">
                    <svg class="w-4 h-4 absolute left-3 top-2.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
            </div>

            <!-- Список -->
            <div class="flex-1 overflow-y-auto custom-scrollbar px-2 space-y-6 pb-4">
                <!-- Группы -->
                <div>
                    <div class="flex justify-between items-center px-3 mb-2">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Группы</span>
                        <button @click="isModalOpen = true" class="p-1 hover:bg-indigo-50 text-indigo-600 rounded-md transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        </button>
                    </div>
                    <div class="space-y-0.5">
                        <div v-for="group in groups" :key="'g' + group.id"
                             @click="openChat('group', group.id)"
                             :class="['flex items-center px-3 py-2.5 rounded-xl cursor-pointer transition-all relative group',
                                      chatType === 'group' && targetId === group.id ? 'bg-indigo-50' : 'hover:bg-slate-50']">
                            <div :class="['w-10 h-10 rounded-xl flex items-center justify-center mr-3 font-bold transition-colors',
                                          chatType === 'group' && targetId === group.id ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-500 group-hover:bg-indigo-100 group-hover:text-indigo-600']">
                                #
                            </div>
                            <div class="flex-1 min-w-0">
                                <div :class="['font-semibold text-sm truncate', chatType === 'group' && targetId === group.id ? 'text-indigo-900' : 'text-slate-700']">
                                    {{ group.name }}
                                </div>
                            </div>
                            <div v-if="chatType === 'group' && targetId === group.id" class="absolute left-0 w-1 h-6 bg-indigo-600 rounded-r-full"></div>
                        </div>
                    </div>
                </div>

                <!-- Личные чаты -->
                <div>
                    <div class="px-3 mb-2">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Коллеги</span>
                    </div>
                    <div class="space-y-0.5">
                        <div v-for="user in colleagues" :key="'u' + user.id"
                             @click="openChat('user', user.id)"
                             :class="['flex items-center px-3 py-2.5 rounded-xl cursor-pointer transition-all relative group',
                                      chatType === 'user' && targetId === user.id ? 'bg-indigo-50' : 'hover:bg-slate-50']">
                            <div :class="['w-10 h-10 rounded-full flex items-center justify-center mr-3 text-xs font-bold shadow-sm transition-colors',
                                          chatType === 'user' && targetId === user.id ? 'bg-indigo-600 text-white' : 'bg-white border border-slate-200 text-slate-500 group-hover:border-indigo-200 group-hover:text-indigo-600']">
                                {{ user.name.substring(0, 1).toUpperCase() }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div :class="['font-semibold text-sm truncate', chatType === 'user' && targetId === user.id ? 'text-indigo-900' : 'text-slate-700']">
                                    {{ user.name }}
                                </div>
                                <div class="text-[11px] text-slate-400">На связи</div>
                            </div>
                            <div v-if="chatType === 'user' && targetId === user.id" class="absolute left-0 w-1 h-6 bg-indigo-600 rounded-r-full"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- СТОЛБЕЦ 2: Окно чата -->
        <div class="flex-1 flex flex-col min-w-0 bg-white relative">
            <template v-if="targetId">
                <!-- Шапка чата -->
                <header class="h-16 border-b border-slate-100 px-6 flex items-center justify-between bg-white/80 backdrop-blur-md z-10">
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
                        <span class="font-bold text-slate-800 tracking-tight">{{ activeChatName }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button v-if="chatType === 'group'"
                                @click="showGroupInfo = !showGroupInfo"
                                :class="['p-2 rounded-lg transition-all', showGroupInfo ? 'bg-indigo-100 text-indigo-600' : 'text-slate-400 hover:bg-slate-100 hover:text-slate-600']"
                                title="Информация о группе">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </button>
                    </div>
                </header>

                <main class="flex-1 flex overflow-hidden bg-slate-50/50">
                    <!-- Область сообщений -->
                    <div class="flex-1 flex flex-col min-w-0 relative">
                        <div class="flex-1 overflow-y-auto p-6 space-y-4 custom-scrollbar" ref="msgContainer">
                            <div v-for="msg in messages" :key="msg.id"
                                 :class="['flex flex-col', msg.sender_id === authId ? 'items-end' : 'items-start']">

                                <div class="flex items-center gap-2 mb-1 px-1">
                                    <span v-if="msg.sender_id !== authId" class="text-[11px] font-bold text-slate-500">
                                        {{ msg.sender.name }}
                                    </span>
                                    <span class="text-[10px] text-slate-400 uppercase tracking-tighter">
                                        {{ formatTime(msg.created_at) }}
                                    </span>
                                </div>

                                <div :class="['max-w-[85%] sm:max-w-[70%] px-4 py-2.5 rounded-2xl shadow-sm text-sm leading-relaxed transition-all hover:shadow-md',
                                              msg.sender_id === authId
                                              ? 'bg-indigo-600 text-white rounded-tr-none'
                                              : 'bg-white text-slate-700 rounded-tl-none border border-slate-100']">
                                    {{ msg.message }}
                                </div>
                            </div>
                        </div>

                        <!-- Поле ввода -->
                        <footer class="p-4 bg-white border-t border-slate-100">
                            <form @submit.prevent="sendMessage" class="max-w-4xl mx-auto relative flex items-center gap-2">
                                <input v-model="form.message" type="text"
                                       placeholder="Напишите сообщение..."
                                       class="flex-1 border-none bg-slate-100 rounded-2xl px-5 py-3 text-sm focus:ring-2 focus:ring-indigo-500/20 outline-none transition-all"
                                       :disabled="form.processing">

                                <button type="submit"
                                        :disabled="form.processing || !form.message.trim()"
                                        class="p-3 bg-indigo-600 text-white rounded-xl shadow-indigo-200 shadow-lg hover:bg-indigo-700 transition-all active:scale-95 disabled:opacity-50 disabled:shadow-none disabled:active:scale-100">
                                    <svg v-if="!form.processing" class="w-5 h-5 rotate-90" fill="currentColor" viewBox="0 0 20 20"><path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"/></svg>
                                    <div v-else class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                                </button>
                            </form>
                        </footer>
                    </div>

                    <!-- ПРАВАЯ ПАНЕЛЬ: Участники группы -->
                    <transition name="panel">
                        <aside v-if="showGroupInfo && chatType === 'group' && activeGroup"
                               class="w-80 bg-white border-l border-slate-100 flex flex-col shadow-2xl z-10">
                            <div class="p-5 border-b border-slate-50 flex justify-between items-center">
                                <h4 class="font-bold text-slate-800">Информация</h4>
                                <button @click="showGroupInfo = false" class="text-slate-400 hover:text-slate-600 transition-colors">&times;</button>
                            </div>

                            <div class="flex-1 overflow-y-auto p-5 custom-scrollbar space-y-8">
                                <!-- Список участников -->
                                <div>
                                    <div class="flex items-center justify-between mb-4">
                                        <h5 class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Участники ({{ activeGroup.users.length }})</h5>
                                    </div>
                                    <div class="space-y-3">
                                        <div v-for="user in activeGroup.users" :key="user.id"
                                             class="flex items-center justify-between group/user">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-[10px] font-bold text-slate-500">
                                                    {{ user.name.substring(0,1).toUpperCase() }}
                                                </div>
                                                <span class="text-sm font-semibold text-slate-700">{{ user.name }}</span>
                                            </div>
                                            <button v-if="user.id !== authId"
                                                    @click="removeUser(user.id)"
                                                    class="p-1  hover:text-red-500 hover:bg-red-50 rounded transition-all  group-hover/user:opacity-100"
                                                    title="Удалить">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Добавление -->
                                <div>
                                    <h5 class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-4">Добавить коллегу</h5>
                                    <div v-if="usersToInvite.length > 0" class="space-y-2">
                                        <div v-for="user in usersToInvite" :key="user.id"
                                             @click="addUser(user.id)"
                                             class="flex items-center gap-3 p-2 rounded-xl border border-transparent hover:border-indigo-100 hover:bg-indigo-50/50 cursor-pointer transition-all group/add">
                                            <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs font-bold group-hover/add:bg-indigo-600 group-hover/add:text-white transition-colors">
                                                +
                                            </div>
                                            <span class="text-sm text-slate-600 font-medium">{{ user.name }}</span>
                                        </div>
                                    </div>
                                    <div v-else class="text-center py-6 bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                                        <p class="text-[11px] text-slate-400">Нет доступных коллег</p>
                                    </div>
                                </div>
                            </div>
                        </aside>
                    </transition>
                </main>
            </template>

            <!-- Пустой экран -->
            <div v-else class="flex-1 flex flex-col items-center justify-center bg-slate-50/30">
                <div class="w-24 h-24 bg-white rounded-3xl shadow-xl shadow-slate-200/50 flex items-center justify-center mb-6 animate-bounce transition-all">
                    <svg class="w-10 h-10 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-slate-800 mb-2">Ваши сообщения</h2>
                <p class="text-slate-400 text-sm max-w-xs text-center">Выберите чат или группу из списка слева, чтобы начать общение.</p>
            </div>
        </div>

        <!-- МОДАЛКА СОЗДАНИЯ ГРУППЫ -->
        <transition name="modal">
            <div v-if="isModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="isModalOpen = false"></div>
                <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-md overflow-hidden relative z-10">
                    <div class="p-8">
                        <h3 class="text-2xl font-black text-slate-800 mb-2">Новая группа</h3>
                        <p class="text-slate-400 text-sm mb-8">Создайте пространство для обсуждения рабочих вопросов.</p>

                        <div class="space-y-6">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1">Название</label>
                                <input v-model="groupForm.name" type="text" placeholder="Напр: Отдел дизайна"
                                       class="w-full bg-slate-100 border-none rounded-2xl py-3.5 px-5 text-sm focus:ring-2 focus:ring-indigo-500/20 transition-all">
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1">Компания</label>
                                <select v-model="groupForm.company_id"
                                        class="w-full bg-slate-100 border-none rounded-2xl py-3.5 px-5 text-sm focus:ring-2 focus:ring-indigo-500/20 transition-all appearance-none">
                                    <option :value="null" disabled>Выберите компанию</option>
                                    <option v-for="company in companies" :key="company.id" :value="company.id">{{ company.name }}</option>
                                </select>
                            </div>

                            <div v-if="groupForm.company_id">
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1">Участники</label>
                                <div class="max-h-48 overflow-y-auto bg-slate-50 rounded-2xl border border-slate-100 p-2 custom-scrollbar">
                                    <label v-for="user in filteredColleaguesForGroup" :key="user.id"
                                           class="flex items-center p-3 hover:bg-white rounded-xl cursor-pointer transition-all group">
                                        <input type="checkbox" :value="user.id" v-model="groupForm.user_ids" class="rounded text-indigo-600 focus:ring-indigo-500 h-5 w-5 border-slate-300 mr-3 transition-all">
                                        <span class="text-sm font-medium text-slate-700 group-hover:text-indigo-600">{{ user.name }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="flex gap-3 mt-10">
                            <button @click="isModalOpen = false" class="flex-1 py-4 text-sm font-bold text-slate-400 hover:text-slate-600 transition-colors">Отмена</button>
                            <button @click="submitCreateGroup"
                                    :disabled="!groupForm.name || !groupForm.company_id || groupForm.user_ids.length === 0"
                                    class="flex-[2] py-4 bg-indigo-600 text-white text-sm font-bold rounded-2xl shadow-lg shadow-indigo-200 hover:bg-indigo-700 disabled:opacity-30 disabled:shadow-none transition-all active:scale-95">
                                Создать группу
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

<script setup>
import { ref, computed, nextTick, watch } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import ApplicationLogo from "@/Components/ApplicationLogo.vue";

const props = defineProps({
    companies: Array,
    groups: Array,
    colleagues: Array,
    messages: Array,
    chatType: String,
    targetId: Number,
    activeGroup: Object,
});

const showGroupInfo = ref(false);
const isModalOpen = ref(false);
const msgContainer = ref(null);

const page = usePage();
const authId = computed(() => page.props.auth.user.id);

const form = useForm({
    message: '',
    type: props.chatType,
    target_id: props.targetId
});

const groupForm = useForm({
    name: '',
    company_id: null,
    user_ids: []
});

const filteredColleaguesForGroup = computed(() => {
    if (!groupForm.company_id) return [];
    const selectedComp = props.companies.find(c => c.id === groupForm.company_id);
    return selectedComp ? selectedComp.members.filter(m => m.id !== authId.value) : [];
});

const usersToInvite = computed(() => {
    if (!props.activeGroup) return [];
    const selectedComp = props.companies.find(c => c.id === props.activeGroup.company_id);
    if (!selectedComp) return [];
    const memberIds = props.activeGroup.users.map(u => u.id);
    return selectedComp.members.filter(m => !memberIds.includes(m.id));
});

const activeChatName = computed(() => {
    if (props.chatType === 'user') {
        return props.colleagues.find(u => u.id === props.targetId)?.name || 'Чат';
    }
    return props.groups.find(g => g.id === props.targetId)?.name || 'Группа';
});

const openChat = (type, id) => {
    showGroupInfo.value = false;
    router.get(`/chat/${type}/${id}`);
};

const sendMessage = () => {
    if (!form.message.trim()) return;
    form.type = props.chatType;
    form.target_id = props.targetId;
    form.post('/chat/send', {
        preserveScroll: true,
        onSuccess: () => { form.reset('message'); scrollToBottom(); }
    });
};

const submitCreateGroup = () => {
    groupForm.post('/chat/create-group', {
        onSuccess: () => { isModalOpen.value = false; groupForm.reset(); }
    });
};

const addUser = (userId) => {
    router.post(`/chat/groups/${props.activeGroup.id}/add`, { user_id: userId }, { preserveScroll: true });
};

const removeUser = (userId) => {
    if (confirm('Удалить пользователя из группы?')) {
        router.post(`/chat/groups/${props.activeGroup.id}/remove`, { user_id: userId }, { preserveScroll: true });
    }
};

const scrollToBottom = () => {
    nextTick(() => { if (msgContainer.value) msgContainer.value.scrollTop = msgContainer.value.scrollHeight; });
};

watch(() => groupForm.company_id, () => { groupForm.user_ids = []; });
watch(() => props.messages, scrollToBottom, { deep: true });
watch(() => props.targetId, scrollToBottom, { immediate: true });

const formatTime = (dateStr) => {
    return new Date(dateStr).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }

/* Анимация панели */
.panel-enter-active, .panel-leave-active { transition: all 0.3s ease-out; }
.panel-enter-from, .panel-leave-to { transform: translateX(100%); opacity: 0; }

/* Анимация модалки */
.modal-enter-active, .modal-leave-active { transition: all 0.2s ease-out; }
.modal-enter-from, .modal-leave-to { opacity: 0; transform: scale(0.95); }
</style>
