<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
    modals: Object,
    task: Object,
    employees: Array
})

const emit = defineEmits(['close', 'change', 'add', 'remove'])

// Локальные состояния для форм
const form = ref({ oldId: '', newId: '', ids: [], singleId: null })
const searchQuery = ref('')

// Сброс формы при открытии модалки
const resetForm = () => {
    form.value = { oldId: '', newId: '', ids: [], singleId: null }
    searchQuery.value = ''
}

// Фильтрация сотрудников
const filteredEmployees = computed(() => {
    if (!searchQuery.value) return props.employees
    const query = searchQuery.value.toLowerCase()
    return props.employees.filter(e =>
        e.name.toLowerCase().includes(query) ||
        e.email?.toLowerCase().includes(query)
    )
})

// Методы-обертки
const handleChange = (type) => {
    emit('change', { type, ...form.value })
    resetForm()
}

const handleAdd = (type) => {
    emit('add', { type, ids: form.value.ids, singleId: form.value.singleId })
    resetForm()
}

const handleClose = (modalKey) => {
    emit('close', modalKey)
    resetForm()
}

// Визуальные хелперы
const getInitials = (name) => {
    if (!name) return '?'
    return name.split(' ').map(p => p[0]).slice(0, 2).join('').toUpperCase()
}

const getRoleIcon = (role) => {
    const icons = {
        executor: '🔨',
        responsible: '👨‍💼',
        watcher: '👁'
    }
    return icons[role] || '👤'
}
</script>

<template>
    <!-- Change Executor/Responsible Modal -->
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95">

        <div v-if="modals.executor || modals.responsible" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <!-- Backdrop с эффектом стекла -->
            <div class="absolute inset-0 bg-slate-900/40 dark:bg-black/60 backdrop-blur-md" @click="handleClose(modals.executor ? 'executor' : 'responsible')"></div>

            <div class="relative w-full max-w-md bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">

                <!-- Декоративная полоса сверху -->
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-indigo-500 to-purple-500"></div>

                <!-- Header -->
                <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white text-lg shadow-lg">
                            {{ getRoleIcon(modals.executor ? 'executor' : 'responsible') }}
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                                Сменить {{ modals.executor ? 'исполнителя' : 'ответственного' }}
                            </h3>
                            <p class="text-xs text-slate-500 mt-1">Выберите нового участника</p>
                        </div>
                    </div>
                </div>

                <!-- Body -->
                <div class="p-6 space-y-4">
                    <!-- Кого заменить -->
                    <div class="space-y-2">
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">Кого заменить</label>
                        <select v-model="form.oldId"
                                class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:border-indigo-300 focus:ring focus:ring-indigo-200/20 transition">
                            <option value="" disabled>Выберите участника</option>
                            <option v-for="u in (modals.executor ? task.executors : task.responsibles)"
                                    :key="u.id"
                                    :value="u.id">
                                {{ u.name }}
                            </option>
                        </select>
                    </div>

                    <!-- На кого -->
                    <div class="space-y-2">
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">На кого</label>
                        <select v-model="form.newId"
                                class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:border-indigo-300 focus:ring focus:ring-indigo-200/20 transition">
                            <option value="" disabled>Выберите сотрудника</option>
                            <option v-for="u in employees"
                                    :key="u.id"
                                    :value="u.id">
                                {{ u.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 flex justify-end gap-3">
                    <button @click="handleClose(modals.executor ? 'executor' : 'responsible')"
                            class="px-4 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                        Отмена
                    </button>
                    <button @click="handleChange(modals.executor ? 'executor' : 'responsible')"
                            :disabled="!form.oldId || !form.newId"
                            class="px-6 py-2 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-medium shadow-lg shadow-indigo-500/30 hover:shadow-xl hover:scale-105 transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100">
                        Сохранить
                    </button>
                </div>
            </div>
        </div>
    </Transition>

    <!-- Add Executor/Responsible Modal -->
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95">

        <div v-if="modals.addExecutor || modals.addResponsible" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/40 dark:bg-black/60 backdrop-blur-md" @click="handleClose(modals.addExecutor ? 'addExecutor' : 'addResponsible')"></div>

            <div class="relative w-full max-w-md bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">

                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-emerald-500 to-teal-500"></div>

                <!-- Header -->
                <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-500 flex items-center justify-center text-white text-lg shadow-lg">
                            {{ getRoleIcon(modals.addExecutor ? 'executor' : 'responsible') }}
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                                Добавить {{ modals.addExecutor ? 'исполнителей' : 'ответственных' }}
                            </h3>
                            <p class="text-xs text-slate-500 mt-1">Выберите участников из списка</p>
                        </div>
                    </div>
                </div>

                <!-- Body -->
                <div class="p-6">
                    <!-- Поиск -->
                    <div class="relative mb-4">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" v-model="searchQuery"
                               placeholder="Поиск сотрудников..."
                               class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:border-indigo-300 focus:ring focus:ring-indigo-200/20 transition">
                    </div>

                    <!-- Список сотрудников -->
                    <div class="max-h-60 overflow-y-auto space-y-2 pr-2 custom-scrollbar">
                        <label v-for="u in filteredEmployees" :key="u.id"
                               class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 hover:bg-white dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700 cursor-pointer transition group">
                            <input type="checkbox" :value="u.id" v-model="form.ids"
                                   class="w-4 h-4 text-indigo-600 rounded focus:ring-indigo-500">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white text-xs font-bold">
                                {{ getInitials(u.name) }}
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ u.name }}</p>
                                <p class="text-xs text-slate-500">{{ u.email || 'Нет email' }}</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 flex justify-end gap-3">
                    <button @click="handleClose(modals.addExecutor ? 'addExecutor' : 'addResponsible')"
                            class="px-4 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                        Отмена
                    </button>
                    <button @click="handleAdd(modals.addExecutor ? 'executor' : 'responsible')"
                            :disabled="!form.ids.length"
                            class="px-6 py-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-medium shadow-lg shadow-emerald-500/30 hover:shadow-xl hover:scale-105 transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100">
                        Добавить ({{ form.ids.length }})
                    </button>
                </div>
            </div>
        </div>
    </Transition>

    <!-- Add Watcher Modal -->
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95">

        <div v-if="modals.addWatcher" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/40 dark:bg-black/60 backdrop-blur-md" @click="handleClose('addWatcher')"></div>

            <div class="relative w-full max-w-md bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">

                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-purple-500 to-pink-500"></div>

                <!-- Header -->
                <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white text-lg shadow-lg">
                            👁
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Добавить наблюдателя</h3>
                            <p class="text-xs text-slate-500 mt-1">Выберите сотрудника для наблюдения</p>
                        </div>
                    </div>
                </div>

                <!-- Body -->
                <div class="p-6">
                    <!-- Поиск -->
                    <div class="relative mb-4">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" v-model="searchQuery"
                               placeholder="Поиск сотрудников..."
                               class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:border-indigo-300 focus:ring focus:ring-indigo-200/20 transition">
                    </div>

                    <!-- Список сотрудников -->
                    <div class="max-h-60 overflow-y-auto space-y-2 pr-2 custom-scrollbar">
                        <div v-for="u in filteredEmployees" :key="u.id"
                             @click="form.singleId = u.id"
                             class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 hover:bg-white dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700 cursor-pointer transition"
                             :class="{ 'ring-2 ring-purple-500': form.singleId === u.id }">
                            <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center"
                                 :class="form.singleId === u.id ? 'border-purple-500 bg-purple-500' : 'border-slate-300'">
                                <svg v-if="form.singleId === u.id" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white text-xs font-bold">
                                {{ getInitials(u.name) }}
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ u.name }}</p>
                                <p class="text-xs text-slate-500">{{ u.email || 'Нет email' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 flex justify-end gap-3">
                    <button @click="handleClose('addWatcher')"
                            class="px-4 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                        Отмена
                    </button>
                    <button @click="handleAdd('watcher')"
                            :disabled="!form.singleId"
                            class="px-6 py-2 rounded-xl bg-gradient-to-r from-purple-600 to-pink-600 text-white font-medium shadow-lg shadow-purple-500/30 hover:shadow-xl hover:scale-105 transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100">
                        Добавить
                    </button>
                </div>
            </div>
        </div>
    </Transition>

    <!-- Manage Members Modal -->
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95">

        <div v-if="modals.manage" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/40 dark:bg-black/60 backdrop-blur-md" @click="handleClose('manage')"></div>

            <div class="relative w-full max-w-4xl bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">

                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500"></div>

                <!-- Header -->
                <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white text-lg shadow-lg">
                                👥
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white">Управление участниками</h3>
                                <p class="text-xs text-slate-500 mt-1">Просмотр и удаление участников задачи</p>
                            </div>
                        </div>
                        <button @click="handleClose('manage')"
                                class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 flex items-center justify-center text-slate-500 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Body -->
                <div class="p-6 max-h-[60vh] overflow-y-auto custom-scrollbar">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                        <!-- Исполнители -->
                        <div class="space-y-3">
                            <div class="flex items-center gap-2">
                                <div class="w-1 h-6 bg-gradient-to-b from-emerald-500 to-teal-500 rounded-full"></div>
                                <h4 class="font-semibold text-slate-800 dark:text-white">Исполнители</h4>
                                <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300">
                                    {{ task.executors?.length || 0 }}
                                </span>
                            </div>

                            <div v-if="!task.executors?.length" class="text-sm text-slate-400 italic p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl text-center">
                                Нет исполнителей
                            </div>

                            <div v-for="u in task.executors" :key="u.id"
                                 class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 group hover:border-emerald-200 dark:hover:border-emerald-800 transition-all">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-emerald-500 to-teal-500 flex items-center justify-center text-white text-xs font-bold">
                                        {{ getInitials(u.name) }}
                                    </div>
                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ u.name }}</span>
                                </div>
                                <button @click="$emit('remove', { role: 'executor', id: u.id })"
                                        class="opacity-0 group-hover:opacity-100 p-1.5 rounded-lg hover:bg-rose-100 dark:hover:bg-rose-900/30 text-rose-600 transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Ответственные -->
                        <div class="space-y-3">
                            <div class="flex items-center gap-2">
                                <div class="w-1 h-6 bg-gradient-to-b from-blue-500 to-indigo-500 rounded-full"></div>
                                <h4 class="font-semibold text-slate-800 dark:text-white">Ответственные</h4>
                                <span class="text-xs px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">
                                    {{ task.responsibles?.length || 0 }}
                                </span>
                            </div>

                            <div v-if="!task.responsibles?.length" class="text-sm text-slate-400 italic p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl text-center">
                                Нет ответственных
                            </div>

                            <div v-for="u in task.responsibles" :key="u.id"
                                 class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 group hover:border-blue-200 dark:hover:border-blue-800 transition-all">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-indigo-500 flex items-center justify-center text-white text-xs font-bold">
                                        {{ getInitials(u.name) }}
                                    </div>
                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ u.name }}</span>
                                </div>
                                <button @click="$emit('remove', { role: 'responsible', id: u.id })"
                                        class="opacity-0 group-hover:opacity-100 p-1.5 rounded-lg hover:bg-rose-100 dark:hover:bg-rose-900/30 text-rose-600 transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Наблюдатели -->
                        <div class="space-y-3">
                            <div class="flex items-center gap-2">
                                <div class="w-1 h-6 bg-gradient-to-b from-purple-500 to-pink-500 rounded-full"></div>
                                <h4 class="font-semibold text-slate-800 dark:text-white">Наблюдатели</h4>
                                <span class="text-xs px-2 py-0.5 rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300">
                                    {{ task.watcherstask?.length || 0 }}
                                </span>
                            </div>

                            <div v-if="!task.watcherstask?.length" class="text-sm text-slate-400 italic p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl text-center">
                                Нет наблюдателей
                            </div>

                            <div v-for="u in task.watcherstask" :key="u.id"
                                 class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 group hover:border-purple-200 dark:hover:border-purple-800 transition-all">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white text-xs font-bold">
                                        {{ getInitials(u.name) }}
                                    </div>
                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ u.name }}</span>
                                </div>
                                <button @click="$emit('remove', { role: 'watcherstask', id: u.id })"
                                        class="opacity-0 group-hover:opacity-100 p-1.5 rounded-lg hover:bg-rose-100 dark:hover:bg-rose-900/30 text-rose-600 transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 flex justify-end">
                    <button @click="handleClose('manage')"
                            class="px-6 py-2 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-medium shadow-lg shadow-indigo-500/30 hover:shadow-xl transition-all">
                        Закрыть
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
/* Кастомный скроллбар */
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 20px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #475569;
}

/* Анимации */
.fade-enter-active,
.fade-leave-active {
    transition: all 0.3s ease;
}

.fade-enter-from {
    opacity: 0;
    transform: translateY(10px);
}

.fade-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}

/* Эффект стекла */
.backdrop-blur-md {
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}
</style>
