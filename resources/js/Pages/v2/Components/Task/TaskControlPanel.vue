<script setup>
import { ref } from 'vue'
import axios from 'axios'

const props = defineProps(['show', 'task', 'employees', 'perms'])
const emit = defineEmits(['close', 'refresh'])

const activeTab = ref('general') // 'general' | 'team'

const updateField = async (field, value) => {
    try {
        await axios.patch(`/api/tasks/${props.task.id}/${field}`, { [field]: value })
        emit('refresh')
    } catch(e) { alert('Ошибка сохранения') }
}

const handlePersonnel = async (type, userId, action = 'add') => {
    try {
        const method = action === 'add' ? 'post' : 'delete'
        const url = `/api/tasks/${props.task.id}/${type}${action === 'add' ? '/add' : ''}`
        await axios[method](url, { user_ids: [userId], user_id: userId })
        emit('refresh')
    } catch(e) { alert('Ошибка изменения состава') }
}
</script>

<template>
    <Transition name="slide">
        <div v-if="show" class="fixed inset-0 z-[100] flex justify-end">
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="emit('close')"></div>

            <div class="relative w-full max-w-md bg-white dark:bg-slate-900 h-full shadow-2xl flex flex-col border-l border-slate-200 dark:border-slate-800">
                <div class="p-6 border-b dark:border-slate-800 flex justify-between items-center bg-slate-50 dark:bg-slate-800/50">
                    <h2 class="text-xl font-black uppercase tracking-tight">Параметры задачи</h2>
                    <button @click="emit('close')" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <div class="flex border-b dark:border-slate-800">
                    <button @click="activeTab = 'general'" :class="['flex-1 py-4 text-xs font-black uppercase tracking-widest', activeTab === 'general' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-slate-400']">Основное</button>
                    <button @click="activeTab = 'team'" :class="['flex-1 py-4 text-xs font-black uppercase tracking-widest', activeTab === 'team' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-slate-400']">Персонал</button>
                </div>

                <div class="flex-1 overflow-y-auto p-6 space-y-8 custom-scrollbar">
                    <!-- General Tab -->
                    <div v-if="activeTab === 'general'" class="space-y-6 animate-in slide-in-from-right-4 duration-300">
                        <div class="space-y-2">
                            <label class="label-tiny">Название задачи</label>
                            <input :value="task.title" @blur="e => updateField('title', e.target.value)" class="panel-input" />
                        </div>
                        <div class="space-y-2">
                            <label class="label-tiny">Приоритет</label>
                            <select :value="task.priority" @change="e => updateField('priority', e.target.value)" class="panel-input">
                                <option value="low">Низкий</option>
                                <option value="medium">Средний</option>
                                <option value="high">Высокий</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="label-tiny">Описание</label>
                            <textarea :value="task.description" rows="6" @blur="e => updateField('description', e.target.value)" class="panel-input"></textarea>
                        </div>
                    </div>

                    <!-- Team Tab -->
                    <div v-if="activeTab === 'team'" class="space-y-6 animate-in slide-in-from-right-4 duration-300">
                        <!-- Добавление Исполнителя -->
                        <div class="space-y-4">
                            <label class="label-tiny">Назначить исполнителя</label>
                            <div class="grid gap-2 max-h-60 overflow-y-auto custom-scrollbar pr-2">
                                <button v-for="emp in employees" :key="emp.id"
                                        @click="handlePersonnel('executors', emp.id)"
                                        class="flex items-center justify-between p-3 rounded-xl border border-slate-100 hover:border-indigo-500 hover:bg-indigo-50/30 transition-all group">
                                    <span class="text-sm font-bold text-slate-700">{{ emp.name }}</span>
                                    <span class="text-xs text-indigo-500 opacity-0 group-hover:opacity-100">+ Добавить</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-6 border-t dark:border-slate-800 bg-slate-50 dark:bg-slate-800/30">
                    <button v-if="perms.canDelete" class="w-full py-4 rounded-2xl border border-rose-200 text-rose-600 font-bold hover:bg-rose-600 hover:text-white transition-all">Удалить задачу</button>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.label-tiny { @apply text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1; }
.panel-input { @apply w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all text-sm font-bold; }
.slide-enter-active, .slide-leave-active { transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
.slide-enter-from, .slide-leave-to { transform: translateX(100%); }
</style>
