<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
    modals: Object,
    task: Object,
    employees: Array,
    loading: Boolean,
    error: String
})

const emit = defineEmits(['close', 'update', 'saveDescription', 'deleteTask', 'createSubtask'])

// Локальные формы
const editForm = ref({})
const subtaskForm = ref({})
const descForm = ref('')

// Сброс форм при открытии модалок
watch(() => props.modals.edit, (val) => {
    if(val) editForm.value = {
        title: props.task?.title || '',
        start_date: props.task?.start_date || new Date().toISOString().slice(0,10),
        due_date: props.task?.due_date || ''
    }
})

watch(() => props.modals.description, (val) => {
    if(val) descForm.value = props.task?.description || ''
})

watch(() => props.modals.subtask, (val) => {
    if(val) subtaskForm.value = {
        title: '',
        executor_id: '',
        responsible_id: '',
        start_date: new Date().toISOString().slice(0,10),
        due_date: ''
    }
})

// Хелперы
const getInitials = (name) => {
    if (!name) return '?'
    return name.split(' ').map(p => p[0]).slice(0, 2).join('').toUpperCase()
}
</script>

<template>
    <!-- EDIT MODAL -->
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95">

        <div v-if="modals.edit" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <!-- Backdrop с эффектом стекла -->
            <div class="absolute inset-0 bg-slate-900/40 dark:bg-black/60 backdrop-blur-md" @click="$emit('close', 'edit')"></div>

            <div class="relative w-full max-w-md bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">

                <!-- Декоративная полоса сверху -->
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-500 to-indigo-500"></div>

                <!-- Header -->
                <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-500 flex items-center justify-center text-white text-lg shadow-lg">
                            ✏️
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Редактировать задачу</h3>
                            <p class="text-xs text-slate-500 mt-1">Измените основные параметры</p>
                        </div>
                    </div>
                </div>

                <!-- Body -->
                <div class="p-6">
                    <form @submit.prevent="$emit('update', editForm)" class="space-y-4">
                        <!-- Название -->
                        <div class="space-y-2">
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">Название задачи</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-slate-400">📋</span>
                                </div>
                                <input v-model="editForm.title"
                                       class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:border-indigo-300 focus:ring focus:ring-indigo-200/20 transition"
                                       placeholder="Введите название задачи"
                                       required />
                            </div>
                        </div>

                        <!-- Даты -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">Дата старта</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-slate-400">📅</span>
                                    </div>
                                    <input type="date" v-model="editForm.start_date"
                                           class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:border-indigo-300 focus:ring focus:ring-indigo-200/20 transition" />
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">Дедлайн</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-slate-400">⏰</span>
                                    </div>
                                    <input type="date" v-model="editForm.due_date"
                                           class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:border-indigo-300 focus:ring focus:ring-indigo-200/20 transition" />
                                </div>
                            </div>
                        </div>

                        <!-- Error message -->
                        <div v-if="error" class="p-3 bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800 rounded-xl text-sm text-rose-700 dark:text-rose-300">
                            {{ error }}
                        </div>

                        <!-- Footer -->
                        <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                            <button type="button" @click="$emit('close', 'edit')"
                                    class="px-4 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                                Отмена
                            </button>
                            <button type="submit" :disabled="loading"
                                    class="px-6 py-2 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-medium shadow-lg shadow-blue-500/30 hover:shadow-xl hover:scale-105 transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100">
                                {{ loading ? 'Сохранение...' : 'Сохранить' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </Transition>

    <!-- DESCRIPTION MODAL -->
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95">

        <div v-if="modals.description" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/40 dark:bg-black/60 backdrop-blur-md" @click="$emit('close', 'description')"></div>

            <div class="relative w-full max-w-lg bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">

                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-purple-500 to-pink-500"></div>

                <!-- Header -->
                <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white text-lg shadow-lg">
                            📄
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Редактировать описание</h3>
                            <p class="text-xs text-slate-500 mt-1">Добавьте подробности задачи</p>
                        </div>
                    </div>
                </div>

                <!-- Body -->
                <div class="p-6">
                    <textarea v-model="descForm"
                              class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:border-indigo-300 focus:ring focus:ring-indigo-200/20 transition h-48 resize-none"
                              placeholder="Введите описание задачи..."></textarea>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 flex justify-end gap-3">
                    <button @click="$emit('close', 'description')"
                            class="px-4 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                        Отмена
                    </button>
                    <button @click="$emit('saveDescription', descForm)" :disabled="loading"
                            class="px-6 py-2 rounded-xl bg-gradient-to-r from-purple-600 to-pink-600 text-white font-medium shadow-lg shadow-purple-500/30 hover:shadow-xl hover:scale-105 transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100">
                        {{ loading ? 'Сохранение...' : 'Сохранить' }}
                    </button>
                </div>
            </div>
        </div>
    </Transition>

    <!-- SUBTASK MODAL -->
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95">

        <div v-if="modals.subtask" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/40 dark:bg-black/60 backdrop-blur-md" @click="$emit('close', 'subtask')"></div>

            <div class="relative w-full max-w-md bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">

                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-emerald-500 to-teal-500"></div>

                <!-- Header -->
                <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-500 flex items-center justify-center text-white text-lg shadow-lg">
                            📌
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Новая подзадача</h3>
                            <p class="text-xs text-slate-500 mt-1">Создайте подзадачу для декомпозиции</p>
                        </div>
                    </div>
                </div>

                <!-- Body -->
                <div class="p-6">
                    <form @submit.prevent="$emit('createSubtask', subtaskForm)" class="space-y-4">
                        <!-- Название -->
                        <div class="space-y-2">
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">Название</label>
                            <input v-model="subtaskForm.title"
                                   class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:border-indigo-300 focus:ring focus:ring-indigo-200/20 transition"
                                   placeholder="Введите название подзадачи"
                                   required />
                        </div>

                        <!-- Исполнитель -->
                        <div class="space-y-2">
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">Исполнитель</label>
                            <select v-model="subtaskForm.executor_id"
                                    class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:border-indigo-300 focus:ring focus:ring-indigo-200/20 transition">
                                <option value="">Выберите исполнителя</option>
                                <option v-for="u in employees" :key="u.id" :value="u.id">{{ u.name }}</option>
                            </select>
                        </div>

                        <!-- Ответственный -->
                        <div class="space-y-2">
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">Ответственный</label>
                            <select v-model="subtaskForm.responsible_id"
                                    class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:border-indigo-300 focus:ring focus:ring-indigo-200/20 transition">
                                <option value="">Выберите ответственного</option>
                                <option v-for="u in employees" :key="u.id" :value="u.id">{{ u.name }}</option>
                            </select>
                        </div>

                        <!-- Даты -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">Старт</label>
                                <input type="date" v-model="subtaskForm.start_date"
                                       class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:border-indigo-300 focus:ring focus:ring-indigo-200/20 transition"
                                       required />
                            </div>
                            <div class="space-y-2">
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">Дедлайн</label>
                                <input type="date" v-model="subtaskForm.due_date"
                                       class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:border-indigo-300 focus:ring focus:ring-indigo-200/20 transition"
                                       required />
                            </div>
                        </div>

                        <!-- Error message -->
                        <div v-if="error" class="p-3 bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800 rounded-xl text-sm text-rose-700 dark:text-rose-300">
                            {{ error }}
                        </div>

                        <!-- Footer -->
                        <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                            <button type="button" @click="$emit('close', 'subtask')"
                                    class="px-4 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                                Отмена
                            </button>
                            <button type="submit" :disabled="loading"
                                    class="px-6 py-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-medium shadow-lg shadow-emerald-500/30 hover:shadow-xl hover:scale-105 transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100">
                                {{ loading ? 'Создание...' : 'Создать' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </Transition>

    <!-- DELETE MODAL -->
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95">

        <div v-if="modals.delete" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/40 dark:bg-black/60 backdrop-blur-md" @click="$emit('close', 'delete')"></div>

            <div class="relative w-full max-w-md bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">

                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-rose-500 to-pink-500"></div>

                <!-- Header -->
                <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-rose-500 to-pink-500 flex items-center justify-center text-white text-lg shadow-lg">
                            ⚠️
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Удалить задачу?</h3>
                            <p class="text-xs text-slate-500 mt-1">Это действие нельзя отменить</p>
                        </div>
                    </div>
                </div>

                <!-- Body -->
                <div class="p-6">
                    <div class="bg-rose-50 dark:bg-rose-950/30 rounded-xl p-4 border border-rose-200 dark:border-rose-800 mb-4">
                        <p class="text-sm text-rose-700 dark:text-rose-300">
                            Вы уверены, что хотите удалить задачу <span class="font-bold">"{{ task?.title }}"</span>?
                        </p>
                        <p class="text-xs text-rose-500 dark:text-rose-400 mt-2">
                            Все связанные подзадачи и файлы будут также удалены.
                        </p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 flex justify-end gap-3">
                    <button @click="$emit('close', 'delete')"
                            class="px-4 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                        Отмена
                    </button>
                    <button @click="$emit('deleteTask')" :disabled="loading"
                            class="px-6 py-2 rounded-xl bg-gradient-to-r from-rose-600 to-pink-600 text-white font-medium shadow-lg shadow-rose-500/30 hover:shadow-xl hover:scale-105 transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100">
                        {{ loading ? 'Удаление...' : 'Удалить' }}
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
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

/* Кастомный скроллбар */
::-webkit-scrollbar {
    width: 4px;
}

::-webkit-scrollbar-track {
    background: transparent;
}

::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 20px;
}

.dark ::-webkit-scrollbar-thumb {
    background: #475569;
}
</style>
