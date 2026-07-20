<script setup>
import { ref } from 'vue'
import axios from 'axios'

const props = defineProps(['show', 'project', 'user', 'employees'])
const emit = defineEmits(['close', 'refresh'])

const activeSection = ref('settings') // 'settings' | 'team'
const form = ref({ name: '', budget: '', description: '' })

const initForm = () => {
    form.value.name = props.project.name
    form.value.budget = props.project.budget
    form.value.description = props.project.description
}

const updateProject = async (field) => {
    try {
        await axios.patch(`/api/projects/${props.project.id}/${field}`, { [field]: form.value[field] })
        emit('refresh')
    } catch (e) { alert('Ошибка сохранения') }
}

const deleteProject = async () => {
    if(!confirm('Удалить проект навсегда?')) return
    await axios.delete(`/api/projects/${props.project.id}`)
    window.location.href = '/'
}
</script>

<template>
    <Transition name="slide">
        <div v-if="show" class="fixed inset-0 z-[100] flex justify-end">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="emit('close')"></div>

            <!-- Panel -->
            <div class="relative w-full max-w-lg bg-white dark:bg-slate-900 h-full shadow-2xl flex flex-col border-l border-slate-200 dark:border-slate-800">
                <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/50">
                    <h2 class="text-xl font-black text-slate-800 dark:text-white uppercase tracking-tight">Центр управления</h2>
                    <button @click="emit('close')" class="p-2 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-full transition-colors">✕</button>
                </div>

                <!-- Tabs Navigation -->
                <div class="flex border-b border-slate-100 dark:border-slate-800">
                    <button @click="activeSection = 'settings'; initForm()"
                            :class="['flex-1 py-4 text-sm font-bold transition-all', activeSection === 'settings' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-slate-400']">
                        Настройки
                    </button>
                    <button @click="activeSection = 'team'"
                            :class="['flex-1 py-4 text-sm font-bold transition-all', activeSection === 'team' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-slate-400']">
                        Команда
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto p-6 space-y-8 custom-scrollbar">
                    <!-- Section: Settings -->
                    <div v-if="activeSection === 'settings'" class="space-y-6 animate-in slide-in-from-right-4 duration-300">
                        <div class="space-y-2">
                            <label class="text-xs font-black text-slate-400 uppercase tracking-widest">Название проекта</label>
                            <input v-model="form.name" @blur="updateProject('name')" class="panel-input" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-black text-slate-400 uppercase tracking-widest">Бюджет (₽)</label>
                            <input v-model="form.budget" type="number" @blur="updateProject('budget')" class="panel-input" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-black text-slate-400 uppercase tracking-widest">Описание</label>
                            <textarea v-model="form.description" rows="6" @blur="updateProject('description')" class="panel-input"></textarea>
                        </div>

                        <div class="pt-10 border-t border-slate-100 dark:border-slate-800">
                            <button @click="deleteProject" class="w-full py-4 bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white rounded-2xl font-bold transition-all border border-rose-100">
                                Удалить проект
                            </button>
                        </div>
                    </div>

                    <!-- Section: Team Management (Здесь ваша логика добавления/удаления) -->
                    <div v-if="activeSection === 'team'" class="space-y-6 animate-in slide-in-from-right-4 duration-300">
                        <!-- Сюда переносим логику из ProjectMenu для управления участниками -->
                        <p class="text-sm text-slate-500 italic">Используйте этот раздел для управления ролями в команде.</p>
                        <!-- ... (ваши кнопки добавления ролей) ... -->
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.panel-input {
    @apply w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all text-slate-800 dark:text-slate-200;
}
.slide-enter-active, .slide-leave-active { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
.slide-enter-from, .slide-leave-to { transform: translateX(100%); }

.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { @apply bg-slate-200 dark:bg-slate-700 rounded-full; }
</style>
