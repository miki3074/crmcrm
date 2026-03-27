<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'

const props = defineProps(['subtask', 'user'])
const emit = defineEmits(['refresh', 'startWork'])

const showEditSubtaskModal = ref(false)
const savingSubtask = ref(false)
const editError = ref('')
const editSubtaskForm = ref({ title: '', due_date: '' })

// Permissions (Оставлены без изменений для сохранения логики)
const canUpdateProgress = computed(() => {
    const s = props.subtask; const u = props.user
    if (!s || !u) return false
    const p = s.task?.project || {}
    return (
        (s.executors || []).some(e => e.id === u.id) ||
        (s.responsibles || []).some(r => r.id === u.id) ||
        (p.executors || []).some(e => e.id === u.id) ||
        (p.managers || []).some(m => m.id === u.id) ||
        p.company?.user_id === u.id ||
        s.creator_id === u.id
    )
})

const canComplete = computed(() => canUpdateProgress.value && props.subtask.progress === 100 && !props.subtask.completed)
const canStartWork = computed(() => {
    if (props.subtask.completed || props.subtask.status === 'in_work') return false
    return (props.subtask.executors || []).some(e => e.id === props.user?.id)
})
const canDeleteSubtask = computed(() => {
    const userId = props.user?.id
    if (!userId) return false
    return userId === props.subtask.creator_id || userId === props.subtask.task?.project?.company?.user_id || (props.subtask.task?.project?.managers || []).some(m => m.id === userId)
})
const canEditSubtask = computed(() => canDeleteSubtask.value || (props.subtask.task?.project?.executors || []).some(e => e.id === props.user.id))

// Статус бейдж (Улучшено)
const statusBadge = computed(() => {
    if (props.subtask.completed) return { text: 'Готово', icon: '✅', class: 'bg-emerald-50 text-emerald-600 ring-emerald-500/20' }
    if (props.subtask.status === 'in_work') return { text: 'В работе', icon: '⚙️', class: 'bg-sky-50 text-sky-600 ring-sky-500/20 animate-pulse' }
    return { text: 'Новая', icon: '🆕', class: 'bg-slate-50 text-slate-500 ring-slate-500/10' }
})

// Хелпер даты
const formatDate = (d) => new Date(d).toLocaleDateString('ru-RU', { day: 'numeric', month: 'short' })

// Actions
const completeSubtask = async () => {
    if (!confirm('Завершить подзадачу?')) return
    await axios.patch(`/api/subtasks/${props.subtask.id}/complete`)
    emit('refresh')
}

const deleteSubtask = async () => {
    if (!confirm('Удалить подзадачу?')) return
    try {
        await axios.delete(`/api/subtasks/${props.subtask.id}`)
        window.history.back()
    } catch (e) { alert(e?.response?.data?.message || 'Ошибка') }
}

const openEditSubtask = () => {
    editSubtaskForm.value.title = props.subtask.title
    editSubtaskForm.value.due_date = props.subtask.due_date
    showEditSubtaskModal.value = true
}

const updateSubtask = async () => {
    savingSubtask.value = true
    try {
        await axios.patch(`/api/subtasks/${props.subtask.id}/update`, editSubtaskForm.value)
        emit('refresh'); showEditSubtaskModal.value = false
    } catch (e) { editError.value = e?.response?.data?.message }
    finally { savingSubtask.value = false }
}
</script>

<template>
    <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-[1.5rem] p-6 mb-6 shadow-sm transition-all hover:shadow-md">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">

            <!-- ЛЕВАЯ ЧАСТЬ: Информация -->
            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-3 mb-2">
                    <h2 class="text-xl font-black text-slate-800 dark:text-white tracking-tight truncate">
                        {{ subtask.title }}
                    </h2>
                    <span :class="['badge ring-1', statusBadge.class]">
                        <span class="mr-1">{{ statusBadge.icon }}</span> {{ statusBadge.text }}
                    </span>
                </div>

                <div class="flex flex-wrap items-center gap-4 text-[11px] font-bold uppercase tracking-widest text-slate-400">
                    <div class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        Дедлайн: <span class="text-slate-600 dark:text-slate-200">{{ formatDate(subtask.due_date) }}</span>
                    </div>
                    <div v-if="subtask.completed_at" class="flex items-center gap-1.5 text-emerald-500">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        Завершена: {{ new Date(subtask.completed_at).toLocaleDateString() }}
                    </div>
                </div>
            </div>

            <!-- ПРАВАЯ ЧАСТЬ: Действия и Прогресс -->
            <div class="flex flex-wrap items-center gap-3">

                <!-- Компактный прогресс -->
                <div v-if="!subtask.completed" class="flex items-center gap-3 px-4 py-2 bg-slate-50 dark:bg-slate-800 rounded-xl border border-slate-100 dark:border-slate-700">
                    <div class="relative w-8 h-8 flex items-center justify-center">
                        <svg class="w-full h-full transform -rotate-90">
                            <circle cx="16" cy="16" r="14" stroke="currentColor" stroke-width="3" fill="transparent" class="text-slate-200 dark:text-slate-700" />
                            <circle cx="16" cy="16" r="14" stroke="currentColor" stroke-width="3" fill="transparent"
                                    :stroke-dasharray="88"
                                    :stroke-dashoffset="88 - (88 * (subtask.progress || 0)) / 100"
                                    class="text-indigo-500 transition-all duration-700" />
                        </svg>
                        <span class="absolute text-[9px] font-black">{{ subtask.progress || 0 }}</span>
                    </div>
                    <span class="text-[10px] font-black uppercase text-slate-500 tracking-wider">Прогресс</span>
                </div>

                <!-- Группа кнопок -->
                <div class="flex items-center gap-2 ml-auto lg:ml-0">
                    <button v-if="canStartWork" @click="$emit('startWork', subtask.id)" class="btn-primary bg-indigo-600 hover:bg-indigo-700 shadow-indigo-200">
                        🚀 В работу
                    </button>

                    <button v-if="canComplete" @click="completeSubtask" class="btn-primary bg-emerald-500 hover:bg-emerald-600 shadow-emerald-200">
                        ✅ Завершить
                    </button>

                    <button v-if="canEditSubtask" @click="openEditSubtask" class="btn-icon hover:text-blue-500 hover:bg-blue-50" title="Изменить">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                    </button>

                    <button v-if="canDeleteSubtask" @click="deleteSubtask" class="btn-icon hover:text-rose-500 hover:bg-rose-50" title="Удалить">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal (Улучшенный дизайн) -->
    <Transition name="fade">
        <div v-if="showEditSubtaskModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showEditSubtaskModal = false"></div>
            <div class="relative w-full max-w-md bg-white dark:bg-slate-900 rounded-[2rem] shadow-2xl overflow-hidden p-8 animate-in zoom-in-95">
                <h3 class="text-2xl font-black text-slate-800 dark:text-white mb-6 uppercase tracking-tight">Редактировать</h3>

                <div class="space-y-4">
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Заголовок</label>
                        <input v-model="editSubtaskForm.title" class="modal-input" placeholder="Что нужно сделать?" />
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Срок исполнения</label>
                        <input v-model="editSubtaskForm.due_date" type="date" class="modal-input" />
                    </div>
                </div>

                <p v-if="editError" class="text-rose-500 text-[11px] font-bold mt-4 text-center">{{ editError }}</p>

                <div class="flex gap-3 mt-8">
                    <button @click="showEditSubtaskModal = false" class="flex-1 py-4 text-sm font-bold text-slate-400 hover:text-slate-600 transition">Отмена</button>
                    <button @click="updateSubtask" :disabled="savingSubtask" class="flex-[2] py-4 bg-indigo-600 text-white rounded-2xl font-bold shadow-lg shadow-indigo-200 transition active:scale-95 disabled:opacity-50">
                        {{ savingSubtask ? '...' : 'Сохранить изменения' }}
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.badge { @apply inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest shadow-sm transition-all; }

.btn-primary {
    @apply px-6 py-2.5 rounded-xl text-white text-xs font-black uppercase tracking-widest transition-all shadow-lg active:scale-95 disabled:opacity-50;
}

.btn-icon {
    @apply p-2.5 rounded-xl border border-slate-100 dark:border-slate-800 text-slate-400 transition-all active:scale-90;
}

.modal-input {
    @apply w-full px-4 py-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all text-sm font-bold text-slate-700 dark:text-slate-200;
}

.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
