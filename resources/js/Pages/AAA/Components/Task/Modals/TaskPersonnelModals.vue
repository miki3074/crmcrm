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

const vAutofocus = {
    mounted: el => el.focus(),
}
</script>

<template>
    <!-- Change Executor/Responsible Modal -->
    <div v-if="modals.executor || modals.responsible" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div v-autofocus role="dialog" aria-modal="true" :aria-label="modals.executor ? 'Сменить исполнителя' : 'Сменить ответственного'" tabindex="-1" class="bg-white dark:bg-zinc-800 rounded-2xl p-6 w-full max-w-md shadow-xl" @keydown.esc="$emit('close', modals.executor ? 'executor' : 'responsible')">
            <h3 class="font-bold mb-4 dark:text-white">Сменить {{ modals.executor ? 'Исполнителя' : 'Ответственного' }}</h3>

            <label class="text-xs text-zinc-500">Кого заменить</label>
            <select v-model="form.oldId" class="w-full border rounded p-2 mb-3 dark:bg-zinc-700 dark:text-white">
                <option v-for="u in (modals.executor ? task.executors : task.responsibles)" :key="u.id" :value="u.id">{{ u.name }}</option>
            </select>

            <label class="text-xs text-zinc-500">На кого</label>
            <select v-model="form.newId" class="w-full border rounded p-2 mb-4 dark:bg-zinc-700 dark:text-white">
                <option v-for="u in employees" :key="u.id" :value="u.id">{{ u.name }}</option>
            </select>

            <div class="flex justify-end gap-2">
                <button type="button" @click="$emit('close', modals.executor ? 'executor' : 'responsible')" class="px-3 py-2 bg-zinc-200 rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400">Отмена</button>
                <button type="button" @click="handleChange(modals.executor ? 'executor' : 'responsible')" class="px-3 py-2 bg-cyan-600 text-white rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400">Сохранить</button>
            </div>
        </div>
    </div>

    <!-- Add Executor/Responsible Modal -->
    <div v-if="modals.addExecutor || modals.addResponsible" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div v-autofocus role="dialog" aria-modal="true" :aria-label="modals.addExecutor ? 'Добавить исполнителей' : 'Добавить ответственных'" tabindex="-1" class="bg-white dark:bg-zinc-800 rounded-2xl p-6 w-full max-w-md shadow-xl" @keydown.esc="$emit('close', modals.addExecutor ? 'addExecutor' : 'addResponsible')">
            <h3 class="font-bold mb-4 dark:text-white">Добавить {{ modals.addExecutor ? 'Исполнителей' : 'Ответственных' }}</h3>
            <div class="max-h-60 overflow-y-auto space-y-2 mb-4">
                <label v-for="u in employees" :key="u.id" class="flex items-center gap-2 dark:text-white">
                    <input type="checkbox" :value="u.id" v-model="form.ids" /> {{ u.name }}
                </label>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" @click="$emit('close', modals.addExecutor ? 'addExecutor' : 'addResponsible')" class="px-3 py-2 bg-zinc-200 rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400">Отмена</button>
                <button type="button" @click="handleAdd(modals.addExecutor ? 'executor' : 'responsible')" class="px-3 py-2 bg-emerald-600 text-white rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400">Добавить</button>
            </div>
        </div>
    </div>

    <!-- Add Watcher Modal -->
    <div v-if="modals.addWatcher" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div v-autofocus role="dialog" aria-modal="true" aria-label="Добавить наблюдателя" tabindex="-1" class="bg-white dark:bg-zinc-800 rounded-2xl p-6 w-full max-w-md shadow-xl" @keydown.esc="$emit('close', 'addWatcher')">
            <h3 class="font-bold mb-4 dark:text-white">Добавить Наблюдателя</h3>
            <select v-model="form.singleId" class="w-full border rounded p-2 mb-4 dark:bg-zinc-700 dark:text-white">
                <option v-for="u in employees" :key="u.id" :value="u.id">{{ u.name }}</option>
            </select>
            <div class="flex justify-end gap-2">
                <button type="button" @click="$emit('close', 'addWatcher')" class="px-3 py-2 bg-zinc-200 rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400">Отмена</button>
                <button type="button" @click="handleAdd('watcher')" class="px-3 py-2 bg-violet-600 text-white rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400">Добавить</button>
            </div>
        </div>
    </div>

    <!-- Manage Members Modal -->
    <div v-if="modals.manage" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div v-autofocus role="dialog" aria-modal="true" aria-label="Управление участниками" tabindex="-1" class="bg-white dark:bg-zinc-800 rounded-2xl p-6 w-full max-w-4xl shadow-xl" @keydown.esc="$emit('close', 'manage')">
            <h3 class="font-bold mb-4 dark:text-white">Управление участниками</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                <!-- Секции для Исполнителей, Ответственных, Наблюдателей -->
                <div v-for="role in ['executors', 'responsibles', 'watcherstask']" :key="role">
                    <h4 class="font-bold mb-2 capitalize dark:text-zinc-300">{{ role === 'watcherstask' ? 'Наблюдатели' : role }}</h4>
                    <div v-for="u in task[role]" :key="u.id" class="flex justify-between items-center bg-zinc-50 dark:bg-zinc-700 p-2 rounded mb-1">
                        <span class="text-sm dark:text-white">{{ u.name }}</span>
                        <button
                            type="button"
                            aria-label="Удалить участника"
                            class="text-rose-500 text-xs rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400"
                            @click="$emit('remove', { role, id: u.id })"
                        >
                            Удалить
                        </button>
                    </div>
                </div>
            </div>
            <div class="flex justify-end mt-4">
                <button type="button" @click="$emit('close', 'manage')" class="px-4 py-2 bg-zinc-500 text-white rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400">Закрыть</button>
            </div>
        </div>
    </div>
</template>
