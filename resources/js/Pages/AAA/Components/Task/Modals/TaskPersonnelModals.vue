<script setup>
import { ref } from 'vue'

const props = defineProps({
    modals: Object,
    task: Object,
    employees: Array
})

const emit = defineEmits(['close', 'change', 'add', 'remove'])

// Локальные состояния для форм
const form = ref({ oldId: '', newId: '', ids: [], singleId: null })

// Методы-обертки
const handleChange = (type) => emit('change', { type, ...form.value })
const handleAdd = (type) => emit('add', { type, ids: form.value.ids, singleId: form.value.singleId })
</script>

<template>
    <!-- Change Executor/Responsible Modal -->
    <div v-if="modals.executor || modals.responsible" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md shadow-xl">
            <h3 class="font-bold mb-4 dark:text-white">Сменить {{ modals.executor ? 'Исполнителя' : 'Ответственного' }}</h3>

            <label class="text-xs text-gray-500">Кого заменить</label>
            <select v-model="form.oldId" class="w-full border rounded p-2 mb-3 dark:bg-gray-700 dark:text-white">
                <option v-for="u in (modals.executor ? task.executors : task.responsibles)" :key="u.id" :value="u.id">{{ u.name }}</option>
            </select>

            <label class="text-xs text-gray-500">На кого</label>
            <select v-model="form.newId" class="w-full border rounded p-2 mb-4 dark:bg-gray-700 dark:text-white">
                <option v-for="u in employees" :key="u.id" :value="u.id">{{ u.name }}</option>
            </select>

            <div class="flex justify-end gap-2">
                <button @click="$emit('close', modals.executor ? 'executor' : 'responsible')" class="px-3 py-2 bg-gray-200 rounded">Отмена</button>
                <button @click="handleChange(modals.executor ? 'executor' : 'responsible')" class="px-3 py-2 bg-blue-600 text-white rounded">Сохранить</button>
            </div>
        </div>
    </div>

    <!-- Add Executor/Responsible Modal -->
    <div v-if="modals.addExecutor || modals.addResponsible" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md shadow-xl">
            <h3 class="font-bold mb-4 dark:text-white">Добавить {{ modals.addExecutor ? 'Исполнителей' : 'Ответственных' }}</h3>
            <div class="max-h-60 overflow-y-auto space-y-2 mb-4">
                <label v-for="u in employees" :key="u.id" class="flex items-center gap-2 dark:text-white">
                    <input type="checkbox" :value="u.id" v-model="form.ids" /> {{ u.name }}
                </label>
            </div>
            <div class="flex justify-end gap-2">
                <button @click="$emit('close', modals.addExecutor ? 'addExecutor' : 'addResponsible')" class="px-3 py-2 bg-gray-200 rounded">Отмена</button>
                <button @click="handleAdd(modals.addExecutor ? 'executor' : 'responsible')" class="px-3 py-2 bg-emerald-600 text-white rounded">Добавить</button>
            </div>
        </div>
    </div>

    <!-- Add Watcher Modal -->
    <div v-if="modals.addWatcher" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md shadow-xl">
            <h3 class="font-bold mb-4 dark:text-white">Добавить Наблюдателя</h3>
            <select v-model="form.singleId" class="w-full border rounded p-2 mb-4 dark:bg-gray-700 dark:text-white">
                <option v-for="u in employees" :key="u.id" :value="u.id">{{ u.name }}</option>
            </select>
            <div class="flex justify-end gap-2">
                <button @click="$emit('close', 'addWatcher')" class="px-3 py-2 bg-gray-200 rounded">Отмена</button>
                <button @click="handleAdd('watcher')" class="px-3 py-2 bg-purple-600 text-white rounded">Добавить</button>
            </div>
        </div>
    </div>

    <!-- Manage Members Modal -->
    <div v-if="modals.manage" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-4xl shadow-xl">
            <h3 class="font-bold mb-4 dark:text-white">Управление участниками</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                <!-- Секции для Исполнителей, Ответственных, Наблюдателей -->
                <div v-for="role in ['executors', 'responsibles', 'watcherstask']" :key="role">
                    <h4 class="font-bold mb-2 capitalize dark:text-gray-300">{{ role === 'watcherstask' ? 'Наблюдатели' : role }}</h4>
                    <div v-for="u in task[role]" :key="u.id" class="flex justify-between items-center bg-gray-50 dark:bg-gray-700 p-2 rounded mb-1">
                        <span class="text-sm dark:text-white">{{ u.name }}</span>
                        <button @click="$emit('remove', { role, id: u.id })" class="text-red-500 text-xs">Удалить</button>
                    </div>
                </div>
            </div>
            <div class="flex justify-end mt-4">
                <button @click="$emit('close', 'manage')" class="px-4 py-2 bg-gray-500 text-white rounded">Закрыть</button>
            </div>
        </div>
    </div>
</template>
