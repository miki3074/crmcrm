<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'

const props = defineProps(['subtask', 'user'])
const emit = defineEmits(['refresh'])

// Состояния модального окна
const showModal = ref(false)
const creating = ref(false)
const errorMsg = ref('')
const employees = ref([])

// Форма создания
const form = ref({
    title: '',
    due_date: '',
    executor_ids: [],
    responsible_ids: []
})

// Права доступа (логика из вашего исходного кода)
const canCreateChild = computed(() => {
    const { subtask, user } = props
    if (!subtask || !user) return false
    const project = subtask.task?.project

    return (
        user.id === subtask.creator_id || // автор подзадачи
        user.id === project?.company?.user_id || // владелец
        (project?.managers || []).some(m => m.id === user.id) || // менеджер проекта
        (project?.executors || []).some(e => e.id === user.id) // участник проекта
    )
})

// Загрузка сотрудников (только при открытии модалки)
const fetchEmployees = async () => {
    if (employees.value.length > 0) return
    try {
        const projectId = props.subtask.task?.project?.id
        if (!projectId) return
        const { data } = await axios.get(`/api/projects/${projectId}/employees`)
        employees.value = data
    } catch (e) {
        console.error("Ошибка загрузки сотрудников", e)
    }
}

// Открытие модалки
const openModal = async () => {
    await fetchEmployees()
    form.value = { title: '', due_date: '', executor_ids: [], responsible_ids: [] }
    errorMsg.value = ''
    showModal.value = true
}

// Отправка формы
const createChild = async () => {
    creating.value = true
    errorMsg.value = ''

    try {
        await axios.post(`/api/subtasks/${props.subtask.id}/children`, form.value)
        emit('refresh') // Обновляем родителя
        showModal.value = false
    } catch (e) {
        errorMsg.value = e?.response?.data?.message || 'Ошибка при создании подзадачи'
    } finally {
        creating.value = false
    }
}
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-6 mt-6">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">🧩 Вложенные подзадачи</h3>

            <button
                v-if="canCreateChild"
                @click="openModal"
                class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-md text-sm transition"
            >
                ➕ Добавить подзадачу
            </button>
        </div>

        <!-- Список дочерних задач -->
        <ul v-if="subtask.children?.length" class="space-y-2">
            <li
                v-for="child in subtask.children"
                :key="child.id"
                class="flex justify-between items-center bg-gray-50 dark:bg-gray-700 p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition"
            >
                <div>
                    <span class="font-medium text-gray-800 dark:text-gray-200">{{ child.title }}</span>
                    <span
                        class="text-xs ml-2 px-2 py-0.5 rounded-full"
                        :class="{
                'bg-green-100 text-green-700': child.completed,
                'bg-gray-200 text-gray-600': !child.completed
            }"
                    >
            {{ child.completed ? 'Завершена' : (child.progress ?? 0) + '%' }}
          </span>
                </div>
                <a
                    :href="`/subtasks/${child.id}`"
                    class="text-blue-600 dark:text-blue-400 hover:underline text-sm font-medium"
                >
                    Открыть →
                </a>
            </li>
        </ul>

        <p v-else class="text-sm text-gray-500 dark:text-gray-400 italic">
            Нет дочерних подзадач
        </p>
    </div>

    <!-- === Модальное окно создания === -->
    <div v-if="showModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md shadow-xl flex flex-col max-h-[90vh]">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                ➕ Новая дочерняя подзадача
            </h3>

            <div class="space-y-4 overflow-y-auto pr-2 custom-scrollbar">
                <!-- Название -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Название
                    </label>
                    <input
                        v-model="form.title"
                        type="text"
                        class="w-full border rounded-lg p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-emerald-500 outline-none"
                        placeholder="Введите название..."
                    />
                </div>

                <!-- Дата -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Дата окончания
                    </label>
                    <input
                        v-model="form.due_date"
                        type="date"
                        class="w-full border rounded-lg p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    />
                </div>

                <!-- Выбор исполнителей -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Исполнители
                    </label>
                    <div class="max-h-32 overflow-y-auto border rounded-lg p-2 dark:border-gray-600 dark:bg-gray-700/50">
                        <label
                            v-for="emp in employees"
                            :key="emp.id"
                            class="flex items-center gap-2 py-1 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 rounded px-1"
                        >
                            <input
                                type="checkbox"
                                v-model="form.executor_ids"
                                :value="emp.id"
                                class="rounded text-emerald-600 focus:ring-emerald-500"
                            />
                            <span class="text-sm text-gray-700 dark:text-gray-200">{{ emp.name }}</span>
                        </label>
                    </div>
                </div>

                <!-- Выбор ответственных -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Ответственные
                    </label>
                    <div class="max-h-32 overflow-y-auto border rounded-lg p-2 dark:border-gray-600 dark:bg-gray-700/50">
                        <label
                            v-for="emp in employees"
                            :key="emp.id"
                            class="flex items-center gap-2 py-1 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 rounded px-1"
                        >
                            <input
                                type="checkbox"
                                v-model="form.responsible_ids"
                                :value="emp.id"
                                class="rounded text-amber-600 focus:ring-amber-500"
                            />
                            <span class="text-sm text-gray-700 dark:text-gray-200">{{ emp.name }}</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Ошибки -->
            <p v-if="errorMsg" class="text-rose-600 text-sm mt-3">{{ errorMsg }}</p>

            <!-- Кнопки -->
            <div class="flex justify-end gap-2 mt-5 pt-2 border-t dark:border-gray-700">
                <button
                    @click="showModal = false"
                    class="px-4 py-2 border rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                >
                    Отмена
                </button>
                <button
                    @click="createChild"
                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition disabled:opacity-50"
                    :disabled="creating || !form.title"
                >
                    <span v-if="!creating">Создать</span>
                    <span v-else>Создание...</span>
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Кастомный скроллбар для списков внутри модалки, чтобы было красиво */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 20px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #4b5563;
}
</style>
