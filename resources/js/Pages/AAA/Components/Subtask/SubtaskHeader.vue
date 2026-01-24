<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'

const props = defineProps(['subtask', 'user'])
const emit = defineEmits(['refresh', 'startWork']) // 👈 Добавили startWork

const showEditSubtaskModal = ref(false)
const savingSubtask = ref(false)
const editError = ref('')
const editSubtaskForm = ref({ title: '', due_date: '' })

// Permissions
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

const canComplete = computed(() => {
    return canUpdateProgress.value && props.subtask.progress === 100 && !props.subtask.completed
})

// Можно ли взять в работу? (Только если статус 'new' и ты исполнитель/создатель)
const canStartWork = computed(() => {
    if (props.subtask.completed || props.subtask.status === 'in_work') return false

    // Проверка: является ли юзер исполнителем подзадачи
    const isExecutor = (props.subtask.executors || []).some(e => e.id === props.user?.id)
    return isExecutor
})

const canDeleteSubtask = computed(() => {
    const userId = props.user?.id
    if (!userId) return false
    return (
        userId === props.subtask.creator_id ||
        userId === props.subtask.task?.project?.company?.user_id ||
        (props.subtask.task?.project?.managers || []).some(m => m.id === userId)
    )
})

const canEditSubtask = computed(() => {
    return canDeleteSubtask.value || (props.subtask.task?.project?.executors || []).some(e => e.id === props.user.id)
})

// Статус бейдж
const statusBadge = computed(() => {
    if (props.subtask.completed) return { text: '✅ Завершена', class: 'bg-emerald-100 text-emerald-700' }
    if (props.subtask.status === 'in_work') return { text: '⚙️ В работе', class: 'bg-blue-100 text-blue-700 ring-1 ring-blue-200' }
    return { text: '🆕 Новая', class: 'bg-gray-100 text-gray-600 ring-1 ring-gray-200' }
})

// Actions
const completeSubtask = async () => {
    if (!confirm('Завершить подзадачу?')) return
    await axios.patch(`/api/subtasks/${props.subtask.id}/complete`)
    emit('refresh')
}

const deleteSubtask = async () => {
    if (!confirm('Удалить подзадачу?')) return
    try {
        await axios.delete(`/api/subtasks/${props.subtask.id}`, { withCredentials: true })
        alert('Подзадача удалена')
        window.history.back()
    } catch (e) {
        alert(e?.response?.data?.message || 'Ошибка')
    }
}

const openEditSubtask = () => {
    editSubtaskForm.value.title = props.subtask.title
    editSubtaskForm.value.due_date = props.subtask.due_date
    showEditSubtaskModal.value = true
}

const updateSubtask = async () => {
    savingSubtask.value = true; editError.value = ''
    try {
        await axios.patch(`/api/subtasks/${props.subtask.id}/update`, editSubtaskForm.value)
        emit('refresh')
        showEditSubtaskModal.value = false
    } catch (e) {
        editError.value = e?.response?.data?.message
    } finally {
        savingSubtask.value = false
    }
}
</script>

<template>
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b pb-4 mb-4 dark:border-gray-700">
        <div>
            <div class="flex flex-wrap items-center gap-3 mb-1" style="width: 70%;">
                <h2 class="text-xl font-bold text-gray-800 dark:text-gray-200 break-words flex-1 min-w-0">
                    {{ subtask.title }}
                </h2>
                <!-- Статус Бейдж -->
                <span class="px-2.5 py-0.5 text-xs rounded-full font-bold shadow-sm flex-shrink-0" :class="statusBadge.class">
        {{ statusBadge.text }}
    </span>
            </div>

            <div class="text-xs text-gray-500 dark:text-gray-400">
                Срок: {{ new Date(subtask.due_date).toLocaleDateString() }}
                <span v-if="subtask.completed_at">• Завершена: {{ new Date(subtask.completed_at).toLocaleString() }}</span>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- КНОПКА ВЗЯТЬ В РАБОТУ -->
            <button
                v-if="canStartWork"
                @click="$emit('startWork', subtask.id)"
                class="px-4 py-1.5 bg-sky-500 hover:bg-sky-600 text-white text-sm font-semibold rounded-md shadow-sm transition flex items-center gap-1"
            >
                🚀 Взять в работу
            </button>

            <!-- Прогресс (кружочек) -->
            <span v-if="!subtask.completed" class="inline-flex items-center gap-2 text-sm px-3 py-1 rounded-full ring-1 ring-gray-200 dark:ring-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                <span class="w-2 h-2 rounded-full"
                      :class="{
                        'bg-red-500': (subtask.progress ?? 0) < 30,
                        'bg-amber-500': (subtask.progress ?? 0) >= 30 && (subtask.progress ?? 0) < 70,
                        'bg-green-500': (subtask.progress ?? 0) >= 70
                      }"/>
                {{ subtask.progress ?? 0 }}%
            </span>

            <button v-if="canComplete" @click="completeSubtask" class="px-3 py-1.5 rounded-md bg-emerald-600 text-white text-sm hover:bg-emerald-700 font-medium">
                ✅ Завершить
            </button>

            <button v-if="canEditSubtask" @click="openEditSubtask" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-md">
                ✏️ Изм.
            </button>

            <button v-if="canDeleteSubtask" @click="deleteSubtask" class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white text-sm rounded-md">
                🗑
            </button>
        </div>
    </div>

    <!-- Edit Modal -->
    <div v-if="showEditSubtaskModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md shadow-xl">
            <h3 class="text-lg font-semibold dark:text-white mb-4">✏️ Изменить подзадачу</h3>
            <div class="space-y-4">
                <input v-model="editSubtaskForm.title" class="w-full border rounded-lg p-2 dark:bg-gray-700 dark:text-white" placeholder="Название"/>
                <input v-model="editSubtaskForm.due_date" type="date" class="w-full border rounded-lg p-2 dark:bg-gray-700 dark:text-white"/>
            </div>
            <p v-if="editError" class="text-rose-600 text-sm mt-3">{{ editError }}</p>
            <div class="flex justify-end gap-2 mt-5">
                <button @click="showEditSubtaskModal = false" class="px-4 py-2 border rounded-lg text-gray-600">Отмена</button>
                <button @click="updateSubtask" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">Сохранить</button>
            </div>
        </div>
    </div>
</template>
