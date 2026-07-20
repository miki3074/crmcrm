<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
    task: Object,
    perms: Object
})

const emit = defineEmits([
    'edit', 'delete', 'description', 'back', 'finish',
    'changeExecutor', 'changeResponsible', 'addExecutor', 'addResponsible',
    'addWatcher', 'manageMembers',
    'startWork'
])

// Состояние бокового меню
const isSidebarOpen = ref(false)

/* --- Хелперы для Аватаров --- */
const getInitials = (name) => {
    if (!name) return '?'
    const parts = name.trim().split(' ')
    if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase()
    return name.slice(0, 2).toUpperCase()
}

const getAvatarColor = (name) => {
    const colors = ['bg-red-100 text-red-600', 'bg-blue-100 text-blue-600', 'bg-emerald-100 text-emerald-600', 'bg-amber-100 text-amber-600', 'bg-indigo-100 text-indigo-600']
    if (!name) return colors[0]
    let hash = 0
    for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash)
    return colors[Math.abs(hash) % colors.length]
}

/* --- Логика цветов и описания --- */
const priorityBadge = (p) => {
    if (p === 'high') return 'bg-rose-50 text-rose-600 ring-rose-500/20'
    if (p === 'medium') return 'bg-amber-50 text-amber-600 ring-amber-500/20'
    return 'bg-emerald-50 text-emerald-600 ring-emerald-500/20'
}

const statusBadge = computed(() => {
    if (props.task?.completed) return { text: 'Завершена', icon: '✅', class: 'bg-emerald-100 text-emerald-700 ring-emerald-600/20' }
    if (props.task?.status === 'in_work') return { text: 'В работе', icon: '⚙️', class: 'bg-sky-100 text-sky-700 ring-sky-600/20' }
    return { text: 'Новая', icon: '🆕', class: 'bg-gray-100 text-gray-600 ring-gray-500/20' }
})

const MAX_LENGTH = 140
const showDescriptionModal = ref(false)
const isLongDescription = computed(() => props.task?.description?.length > MAX_LENGTH)
const shortDescription = computed(() => {
    if (!props.task?.description) return ''
    if (!isLongDescription.value) return props.task.description
    return props.task.description.slice(0, MAX_LENGTH) + '…'
})

const doAction = (event) => {
    isSidebarOpen.value = false
    emit(event)
}
</script>

<template>
    <div class="group relative bg-white dark:bg-gray-800 rounded-[2rem] shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden mb-8 transition-all hover:shadow-2xl font-sans">

        <!-- Верхняя полоска -->
        <div class="h-2 w-full bg-gradient-to-r from-sky-500 via-indigo-500 to-fuchsia-500"></div>

        <div class="p-6 sm:p-10">
            <div class="flex flex-col xl:flex-row gap-10">

                <!-- ЛЕВАЯ КОЛОНКА -->
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2 text-xs font-bold text-slate-400 uppercase tracking-widest mb-6">
                        <button @click="$emit('back')" class="hover:text-indigo-600 transition flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7 7-7m-7 7h18" /></svg>
                            Назад
                        </button>
                        <span class="opacity-30">/</span>
                        <span class="truncate">📁 {{ task?.project?.name }}</span>
                    </div>

                    <div class="mb-8">
                        <h1 class="text-3xl sm:text-5xl font-black text-slate-800 dark:text-white tracking-tight break-words mb-5">
                            {{ task?.title }}
                        </h1>
                        <div class="flex flex-wrap gap-3">
                            <span class="badge px-4 py-1.5" :class="statusBadge.class">
                                <span class="mr-2">{{ statusBadge.icon }}</span> {{ statusBadge.text }}
                            </span>
                            <span class="badge px-4 py-1.5" :class="priorityBadge(task?.priority)">
                                ⚡ {{ task?.priority === 'high' ? 'Высокая' : 'Обычная' }}
                            </span>
                        </div>
                    </div>

                    <div v-if="task?.description" class="relative">
                        <div class="pl-5 border-l-4 border-slate-100 dark:border-gray-700 py-2">
                            <p class="text-slate-600 dark:text-slate-300 text-lg leading-relaxed italic">
                                «{{ shortDescription }}»
                            </p>
                            <button v-if="isLongDescription" @click="showDescriptionModal = true" class="mt-3 text-sm font-black text-indigo-600 hover:text-indigo-700 uppercase tracking-wider">Читать полностью</button>
                        </div>
                    </div>
                </div>

                <!-- ПРАВАЯ КОЛОНКА -->
                <div class="w-full xl:w-80 flex flex-col gap-4 flex-shrink-0">
                    <!-- Lifecycle Buttons -->
                    <template v-if="!task?.completed">
                        <button v-if="task?.status === 'new'" @click="$emit('startWork', task.id)" class="main-btn bg-indigo-600 hover:bg-indigo-700 shadow-indigo-200">
                            🚀 ВЗЯТЬ В РАБОТУ
                        </button>
                        <button v-else-if="perms.canFinish" @click="$emit('finish')" class="main-btn bg-emerald-500 hover:bg-emerald-600 shadow-emerald-200">
                            ✅ ЗАВЕРШИТЬ
                        </button>
                        <div v-else-if="task?.progress === 100" class="p-4 bg-amber-50 text-amber-700 rounded-2xl border border-amber-200 text-xs font-bold text-center uppercase tracking-tight">
                            ⚠️ Закройте подзадачи
                        </div>
                    </template>
                    <div v-else class="p-5 bg-emerald-50 text-emerald-700 rounded-2xl border border-emerald-100 text-center font-black uppercase tracking-widest text-xs">
                        Задача выполнена
                    </div>

                    <!-- КНОПКА ОТКРЫТИЯ МЕНЮ -->
                    <button @click="isSidebarOpen = true" class="w-full py-4 rounded-2xl bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-black text-xs uppercase tracking-[0.2em] hover:scale-[1.02] transition-all shadow-xl active:scale-95 flex items-center justify-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16m-7 6h7" /></svg>
                        Управление
                    </button>
                </div>
            </div>
        </div>

        <!-- SIDE OVER MENU (УЛУЧШЕНО) -->
        <Transition name="slide">
            <div v-if="isSidebarOpen" class="fixed inset-0 z-[100] flex justify-end">
                <div class="absolute inset-0 bg-slate-950/40 backdrop-blur-md transition-opacity" @click="isSidebarOpen = false"></div>

                <div class="relative w-full max-w-md bg-white dark:bg-gray-900 h-full shadow-2xl flex flex-col border-l border-slate-100 dark:border-gray-800 animate-in slide-in-from-right duration-500">

                    <!-- Header -->
                    <div class="p-8 border-b border-slate-50 dark:border-gray-800 flex justify-between items-center bg-slate-50/50 dark:bg-gray-800/50">
                        <h2 class="text-2xl font-black uppercase tracking-tighter text-slate-800 dark:text-white">Параметры</h2>
                        <button @click="isSidebarOpen = false" class="w-10 h-10 flex items-center justify-center rounded-full hover:bg-white dark:hover:bg-gray-700 transition-colors text-2xl text-slate-400">&times;</button>
                    </div>

                    <div class="flex-1 overflow-y-auto p-8 space-y-10 custom-scrollbar">

                        <!-- БЛОК: ДЕТАЛИ ЗАДАЧИ (Вставлен сюда) -->
                        <section class="space-y-6">
                            <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-indigo-500">Участники задачи</h3>

                            <!-- Исполнители -->
                            <div class="space-y-3">
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest flex items-center gap-2">🔨 Исполнители</p>
                                <div v-if="task?.executors?.length" class="flex flex-wrap gap-2">
                                    <div v-for="user in task.executors" :key="user.id" class="flex items-center gap-2 px-3 py-1.5 rounded-xl border border-slate-100 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
                                        <div class="w-6 h-6 rounded-lg flex items-center justify-center text-[10px] font-bold" :class="getAvatarColor(user.name)">{{ getInitials(user.name) }}</div>
                                        <span class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ user.name }}</span>
                                    </div>
                                </div>
                                <div v-else class="text-xs italic text-slate-400 pl-1">Не назначены</div>
                            </div>

                            <!-- Ответственные и Наблюдатели -->
                            <div class="grid grid-cols-2 gap-6 pt-4">
                                <div class="space-y-3">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">👨‍💼 Ответственные</p>
                                    <div class="flex -space-x-2">
                                        <div v-for="user in task.responsibles" :key="user.id" :title="user.name" class="w-8 h-8 rounded-full border-2 border-white dark:border-gray-900 flex items-center justify-center text-[10px] font-bold shadow-sm" :class="getAvatarColor(user.name)">{{ getInitials(user.name) }}</div>
                                        <span v-if="!task?.responsibles?.length" class="text-xs text-slate-400 italic">—</span>
                                    </div>
                                </div>
                                <div class="space-y-3">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">👁 Наблюдатели</p>
                                    <div class="flex -space-x-2">
                                        <div v-for="user in task.watcherstask" :key="user.id" :title="user.name" class="w-8 h-8 rounded-full border-2 border-white dark:border-gray-900 flex items-center justify-center text-[10px] font-bold bg-slate-100 text-slate-500 shadow-sm">{{ getInitials(user.name) }}</div>
                                        <span v-if="!task?.watcherstask?.length" class="text-xs text-slate-400 italic">—</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Контрагенты -->
                            <div v-if="task?.producers?.length" class="pt-4 border-t border-slate-50 dark:border-gray-800">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">🏭 Контрагенты</p>
                                <div class="flex flex-wrap gap-2">
                                    <span v-for="p in task.producers" :key="p.id" class="px-3 py-1 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-300 text-[11px] font-bold rounded-lg border border-indigo-100 dark:border-indigo-800">{{ p.name }}</span>
                                </div>
                            </div>
                        </section>

                        <hr class="border-slate-50 dark:border-gray-800">

                        <!-- Группа: Действия -->
                        <section>
                            <h3  v-if="perms.canUpdate" class="text-[10px] font-black uppercase tracking-[0.3em] text-indigo-500 mb-4">Действия</h3>
                            <div class="grid gap-3">
                                <button v-if="perms.canUpdate" @click="doAction('edit')" class="side-menu-btn">✏️ Изменить задачу</button>
                                <button v-if="perms.canUpdate" @click="doAction('description')" class="side-menu-btn">📝 Описание (полное)</button>
                                <button v-if="perms.canManageMembers" @click="doAction('manageMembers')" class="side-menu-btn border-indigo-100 text-indigo-600 bg-indigo-50/30 font-black">⚙️ Настройка прав</button>
                            </div>
                        </section>

                        <!-- Группа: Изменение персонала -->
                        <section v-if="perms.canManageMembers">
                            <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400 mb-4">Состав команды</h3>
                            <div class="grid grid-cols-2 gap-2">
                                <button @click="doAction('changeExecutor')" class="side-menu-btn text-[10px]">👷 Сменить Исп.</button>
                                <button @click="doAction('changeResponsible')" class="side-menu-btn text-[10px]">👨‍💼 Сменить Отв.</button>
                                <button @click="doAction('addExecutor')" class="side-menu-btn text-[10px] text-emerald-600">+ Исполнитель</button>
                                <button @click="doAction('addResponsible')" class="side-menu-btn text-[10px] text-teal-600">+ Отв.</button>
                            </div>
                            <button @click="doAction('addWatcher')" class="w-full side-menu-btn mt-2 text-[10px] text-purple-600">👁 Добавить наблюдателя</button>
                        </section>

                        <!-- Опасная зона -->
                        <section v-if="perms.canDelete" class="pt-10 border-t border-slate-50 dark:border-gray-800">
                            <button @click="doAction('delete')" class="w-full py-4 rounded-2xl bg-rose-50 text-rose-600 font-black text-[10px] uppercase tracking-widest hover:bg-rose-600 hover:text-white transition-all">Удалить задачу безвозвратно</button>
                        </section>
                    </div>
                </div>
            </div>
        </Transition>
    </div>

    <!-- МОДАЛКА ОПИСАНИЯ (упрощенная для читабельности) -->
    <Transition name="fade">
        <div v-if="showDescriptionModal" class="fixed inset-0 z-[110] flex items-center justify-center p-6">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showDescriptionModal = false"></div>
            <div class="relative bg-white dark:bg-gray-800 w-full max-w-2xl rounded-[2.5rem] shadow-2xl flex flex-col max-h-[80vh] animate-in zoom-in-95">
                <div class="p-8 border-b border-slate-50 dark:border-gray-700 flex justify-between items-center">
                    <h3 class="font-black text-xl uppercase tracking-tighter">Описание</h3>
                    <button @click="showDescriptionModal = false" class="text-3xl text-slate-300 hover:text-slate-500">&times;</button>
                </div>
                <div class="p-10 overflow-y-auto custom-scrollbar">
                    <p class="text-slate-700 dark:text-slate-200 text-lg leading-relaxed whitespace-pre-line">{{ task.description }}</p>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.badge { @apply inline-flex items-center rounded-full text-[10px] font-black uppercase tracking-widest ring-1 shadow-sm transition-all; }
.main-btn { @apply w-full py-4 rounded-2xl text-white font-black text-[11px] uppercase tracking-[0.2em] transition-all transform hover:-translate-y-0.5 active:translate-y-0 shadow-xl active:scale-95; }
.side-menu-btn { @apply flex items-center justify-center gap-2 w-full px-4 py-3.5 bg-white dark:bg-gray-800 border border-slate-100 dark:border-gray-700 rounded-2xl text-[11px] font-black uppercase tracking-wider text-slate-600 dark:text-slate-300 transition-all hover:border-indigo-500 hover:shadow-lg active:scale-95; }
.slide-enter-active, .slide-leave-active { transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
.slide-enter-from, .slide-leave-to { transform: translateX(100%); }
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { @apply bg-slate-200 dark:bg-slate-700 rounded-full; }
</style>
