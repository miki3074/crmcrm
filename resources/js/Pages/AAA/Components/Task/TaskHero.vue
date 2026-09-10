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

const vAutofocus = {
    mounted: el => el.focus(),
}

/* --- Хелперы для Аватаров --- */
const getInitials = (name) => {
    if (!name) return '?'
    const parts = name.trim().split(' ')
    if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase()
    return name.slice(0, 2).toUpperCase()
}

const getAvatarColor = (name) => {
    const colors = ['bg-red-100 text-red-600', 'bg-cyan-100 text-cyan-600', 'bg-emerald-100 text-emerald-600', 'bg-amber-100 text-amber-600', 'bg-violet-100 text-violet-600']
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
    if (props.task?.completed) return { text: 'Завершена', icon: 'check', class: 'bg-emerald-100 text-emerald-700 ring-emerald-600/20' }
    if (props.task?.status === 'in_work') return { text: 'В работе', icon: 'gear', class: 'bg-cyan-100 text-cyan-700 ring-cyan-600/20' }
    return { text: 'Новая', icon: 'sparkle', class: 'bg-zinc-100 text-zinc-600 ring-zinc-500/20' }
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
    <div class="group relative bg-white dark:bg-zinc-900 rounded-2xl shadow-xl border border-zinc-100 dark:border-zinc-800 overflow-hidden mb-4 transition-all hover:shadow-xl font-sans">

        <!-- Верхняя полоска -->
        <div class="h-1 w-full bg-gradient-to-r from-cyan-500 via-sky-500 to-violet-500"></div>

        <div class="p-4 sm:p-5">
            <div class="flex flex-col lg:flex-row gap-5">

                <!-- ЛЕВАЯ КОЛОНКА -->
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2 text-xs font-bold text-zinc-400 uppercase tracking-widest mb-3">
                        <button type="button" @click="$emit('back')" class="hover:text-cyan-600 transition flex items-center gap-1.5 rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7 7-7m-7 7h18" /></svg>
                            Назад
                        </button>
                        <span class="opacity-30">/</span>
                        <span class="truncate flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h5l2 2h7a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z" /></svg>
                            {{ task?.project?.name }}
                        </span>
                    </div>

                    <div class="mb-4">
                        <h1 class="text-2xl sm:text-2xl font-black text-zinc-800 dark:text-white tracking-tight break-words mb-3">
                            {{ task?.title }}
                        </h1>
                        <div class="flex flex-wrap gap-3">
                            <span class="badge px-4 py-1.5" :class="statusBadge.class">
                                <svg v-if="statusBadge.icon === 'check'" class="mr-1.5 h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                <svg v-else-if="statusBadge.icon === 'gear'" class="mr-1.5 h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                <svg v-else class="mr-1.5 h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                {{ statusBadge.text }}
                            </span>
                            <span class="badge px-4 py-1.5" :class="priorityBadge(task?.priority)">
                                <svg class="mr-1.5 h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                {{ task?.priority === 'high' ? 'Высокая' : 'Обычная' }}
                            </span>
                        </div>
                    </div>

                    <div v-if="task?.description" class="relative">
                        <div class="pl-3 border-l-2 border-zinc-100 dark:border-zinc-700 py-2">
                            <p class="text-zinc-600 dark:text-zinc-300 text-sm leading-6">
                                «{{ shortDescription }}»
                            </p>
                            <button
                                v-if="isLongDescription"
                                type="button"
                                @click="showDescriptionModal = true"
                                class="mt-3 text-sm font-black text-cyan-600 hover:text-cyan-700 uppercase tracking-wider rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400"
                            >
                                Читать полностью
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ПРАВАЯ КОЛОНКА -->
                <div class="w-full lg:w-72 flex flex-col gap-4 flex-shrink-0">
                    <!-- Lifecycle Buttons -->
                    <template v-if="!task?.completed">
                        <button v-if="task?.status === 'new'" type="button" @click="$emit('startWork', task.id)" class="main-btn bg-cyan-600 hover:bg-cyan-700 shadow-cyan-200">
                            <svg class="mr-1.5 inline h-4 w-4 align-[-2px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                            ВЗЯТЬ В РАБОТУ
                        </button>
                        <button v-else-if="perms.canFinish" type="button" @click="$emit('finish')" class="main-btn bg-emerald-500 hover:bg-emerald-600 shadow-emerald-200">
                            <svg class="mr-1.5 inline h-4 w-4 align-[-2px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            ЗАВЕРШИТЬ
                        </button>
                        <div v-else-if="task?.progress === 100" class="p-4 bg-amber-50 text-amber-700 rounded-2xl border border-amber-200 text-xs font-bold text-center uppercase tracking-tight">
                            Закройте подзадачи
                        </div>
                    </template>
                    <div v-else class="p-5 bg-emerald-50 text-emerald-700 rounded-2xl border border-emerald-100 text-center font-black uppercase tracking-widest text-xs">
                        Задача выполнена
                    </div>

                    <!-- КНОПКА ОТКРЫТИЯ МЕНЮ -->
                    <button
                        type="button"
                        @click="isSidebarOpen = true"
                        aria-haspopup="dialog"
                        :aria-expanded="isSidebarOpen"
                        class="w-full py-2.5 rounded-2xl bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 font-black text-xs uppercase tracking-wide hover:scale-[1.02] transition-all shadow-xl active:scale-95 flex items-center justify-center gap-3
                               focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400 focus-visible:ring-offset-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16m-7 6h7" /></svg>
                        Управление
                    </button>
                </div>
            </div>
        </div>

        <!-- SIDE OVER MENU -->
        <Transition name="slide">
            <div v-if="isSidebarOpen" class="fixed inset-0 z-[100] flex justify-end">
                <div class="absolute inset-0 bg-zinc-950/40 backdrop-blur-md transition-opacity" @click="isSidebarOpen = false"></div>

                <div
                    v-autofocus
                    role="dialog"
                    aria-modal="true"
                    aria-label="Параметры задачи"
                    tabindex="-1"
                    class="relative w-full max-w-sm bg-white dark:bg-zinc-900 h-full shadow-xl flex flex-col border-l border-zinc-100 dark:border-zinc-800 animate-in slide-in-from-right duration-500"
                    @keydown.esc="isSidebarOpen = false"
                >

                    <!-- Header -->
                    <div class="p-5 border-b border-zinc-50 dark:border-zinc-800 flex justify-between items-center bg-zinc-50/50 dark:bg-zinc-800/50">
                        <h2 class="text-2xl font-black uppercase tracking-tighter text-zinc-800 dark:text-white">Параметры</h2>
                        <button
                            type="button"
                            @click="isSidebarOpen = false"
                            aria-label="Закрыть панель управления"
                            class="w-10 h-10 flex items-center justify-center rounded-full hover:bg-white dark:hover:bg-zinc-700 transition-colors text-zinc-400
                                   focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto p-5 space-y-5 custom-scrollbar">

                        <!-- БЛОК: ДЕТАЛИ ЗАДАЧИ -->
                        <section class="space-y-3">
                            <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-cyan-500">Участники задачи</h3>

                            <!-- Исполнители -->
                            <div class="space-y-3">
                                <p class="text-xs font-bold text-zinc-400 uppercase tracking-widest">Исполнители</p>
                                <div v-if="task?.executors?.length" class="flex flex-wrap gap-2">
                                    <div v-for="user in task.executors" :key="user.id" class="flex items-center gap-2 px-3 py-1.5 rounded-xl border border-zinc-100 dark:border-zinc-700 bg-white dark:bg-zinc-800 shadow-sm">
                                        <div class="w-6 h-6 rounded-lg flex items-center justify-center text-[10px] font-bold" :class="getAvatarColor(user.name)">{{ getInitials(user.name) }}</div>
                                        <span class="text-sm font-bold text-zinc-700 dark:text-zinc-200">{{ user.name }}</span>
                                    </div>
                                </div>
                                <div v-else class="text-xs italic text-zinc-400 pl-1">Не назначены</div>
                            </div>

                            <!-- Ответственные и Наблюдатели -->
                            <div class="grid grid-cols-2 gap-4 pt-4">
                                <div class="space-y-3">
                                    <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">Ответственные</p>
                                    <div class="flex -space-x-2">
                                        <div v-for="user in task.responsibles" :key="user.id" :title="user.name" class="w-8 h-8 rounded-full border-2 border-white dark:border-zinc-900 flex items-center justify-center text-[10px] font-bold shadow-sm" :class="getAvatarColor(user.name)">{{ getInitials(user.name) }}</div>
                                        <span v-if="!task?.responsibles?.length" class="text-xs text-zinc-400 italic">—</span>
                                    </div>
                                </div>
                                <div class="space-y-3">
                                    <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">Наблюдатели</p>
                                    <div class="flex -space-x-2">
                                        <div v-for="user in task.watcherstask" :key="user.id" :title="user.name" class="w-8 h-8 rounded-full border-2 border-white dark:border-zinc-900 flex items-center justify-center text-[10px] font-bold bg-zinc-100 text-zinc-500 shadow-sm">{{ getInitials(user.name) }}</div>
                                        <span v-if="!task?.watcherstask?.length" class="text-xs text-zinc-400 italic">—</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Контрагенты -->
                            <div v-if="task?.producers?.length" class="pt-4 border-t border-zinc-50 dark:border-zinc-800">
                                <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-3">Контрагенты</p>
                                <div class="flex flex-wrap gap-2">
                                    <span v-for="p in task.producers" :key="p.id" class="px-3 py-1 bg-cyan-50 dark:bg-cyan-900/30 text-cyan-600 dark:text-cyan-300 text-[11px] font-bold rounded-lg border border-cyan-100 dark:border-cyan-800">{{ p.name }}</span>
                                </div>
                            </div>
                        </section>

                        <hr class="border-zinc-50 dark:border-zinc-800">

                        <!-- Группа: Действия -->
                        <section>
                            <h3 v-if="perms.canUpdate" class="text-[10px] font-black uppercase tracking-[0.3em] text-cyan-500 mb-4">Действия</h3>
                            <div class="grid gap-3">
                                <button v-if="perms.canUpdate" type="button" @click="doAction('edit')" class="side-menu-btn">Изменить задачу</button>
                                <button v-if="perms.canUpdate" type="button" @click="doAction('description')" class="side-menu-btn">Описание (полное)</button>
                                <button v-if="perms.canManageMembers" type="button" @click="doAction('manageMembers')" class="side-menu-btn border-cyan-100 text-cyan-600 bg-cyan-50/30 font-black">Настройка прав</button>
                            </div>
                        </section>

                        <!-- Группа: Изменение персонала -->
                        <section v-if="perms.canManageMembers">
                            <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-zinc-400 mb-4">Состав команды</h3>
                            <div class="grid grid-cols-2 gap-2">
                                <button type="button" @click="doAction('changeExecutor')" class="side-menu-btn text-[10px]">Сменить Исп.</button>
                                <button type="button" @click="doAction('changeResponsible')" class="side-menu-btn text-[10px]">Сменить Отв.</button>
                                <button type="button" @click="doAction('addExecutor')" class="side-menu-btn text-[10px] text-emerald-600">+ Исполнитель</button>
                                <button type="button" @click="doAction('addResponsible')" class="side-menu-btn text-[10px] text-teal-600">+ Отв.</button>
                            </div>
                            <button type="button" @click="doAction('addWatcher')" class="w-full side-menu-btn mt-2 text-[10px] text-violet-600">Добавить наблюдателя</button>
                        </section>

                        <!-- Опасная зона -->
                        <section v-if="perms.canDelete" class="pt-10 border-t border-zinc-50 dark:border-zinc-800">
                            <button type="button" @click="doAction('delete')" class="w-full py-2.5 rounded-2xl bg-rose-50 text-rose-600 font-black text-[10px] uppercase tracking-widest hover:bg-rose-600 hover:text-white transition-all">Удалить задачу безвозвратно</button>
                        </section>
                    </div>
                </div>
            </div>
        </Transition>
    </div>

    <!-- МОДАЛКА ОПИСАНИЯ -->
    <Transition name="fade">
        <div v-if="showDescriptionModal" class="fixed inset-0 z-[110] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-zinc-900/60 backdrop-blur-sm" @click="showDescriptionModal = false"></div>
            <div
                v-autofocus
                role="dialog"
                aria-modal="true"
                aria-label="Описание задачи"
                tabindex="-1"
                class="relative bg-white dark:bg-zinc-900 w-full max-w-2xl rounded-2xl shadow-xl flex flex-col max-h-[80vh] animate-in zoom-in-95"
                @keydown.esc="showDescriptionModal = false"
            >
                <div class="p-5 border-b border-zinc-50 dark:border-zinc-700 flex justify-between items-center">
                    <h3 class="font-black text-xl uppercase tracking-tighter">Описание</h3>
                    <button
                        type="button"
                        @click="showDescriptionModal = false"
                        aria-label="Закрыть"
                        class="text-zinc-300 hover:text-zinc-500 rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400"
                    >
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="p-5 overflow-y-auto custom-scrollbar">
                    <p class="text-zinc-700 dark:text-zinc-200 text-lg leading-relaxed whitespace-pre-line">{{ task.description }}</p>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.badge { @apply inline-flex items-center rounded-full text-[10px] font-black uppercase tracking-widest ring-1 shadow-sm transition-all; }
.main-btn { @apply w-full py-2.5 rounded-2xl text-white font-black text-[11px] uppercase tracking-wide transition-all transform hover:-translate-y-0.5 active:translate-y-0 shadow-xl active:scale-95; }
.side-menu-btn { @apply flex items-center justify-center gap-2 w-full px-3 py-2.5 bg-white dark:bg-zinc-800 border border-zinc-100 dark:border-zinc-700 rounded-2xl text-[11px] font-black uppercase tracking-wider text-zinc-600 dark:text-zinc-300 transition-all hover:border-cyan-500 hover:shadow-lg active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400; }
.slide-enter-active, .slide-leave-active { transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
.slide-enter-from, .slide-leave-to { transform: translateX(100%); }
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { @apply bg-zinc-200 dark:bg-zinc-700 rounded-full; }
</style>
