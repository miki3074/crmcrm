<script setup>
import { ref, onMounted, provide, computed } from 'vue'
import { usePage, Head } from '@inertiajs/vue3'
import axios from 'axios'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

// Импорт новых компонентов
import SubtaskHeader from '../AAA/Components/Subtask/SubtaskHeader.vue'
import SubtaskMembers from '../AAA/Components/Subtask/SubtaskMembers.vue'
import SubtaskProgress from '../AAA/Components/Subtask/SubtaskProgress.vue'
import SubtaskDescription from '../AAA/Components/Subtask/SubtaskDescription.vue'
import SubtaskFiles from '../AAA/Components/Subtask/SubtaskFiles.vue'
import SubtaskChildren from '../AAA/Components/Subtask/SubtaskChildren.vue'
import SubtaskComments from '@/Components/SubtaskComments.vue'
import SubtaskChecklist from '@/Components/SubtaskChecklist.vue'

const { props } = usePage()
const subtaskId = props.id
const user = props.auth?.user
const subtask = ref(null)
const loading = ref(true)
const error = ref(null)

// Вычисляемые свойства для статусов
const isCreator = computed(() => subtask.value?.creator_id === user?.id)
const canEdit = computed(() => {
    if (!subtask.value || !user) return false
    const project = subtask.value.task?.project || {}
    return (
        isCreator.value ||
        project.managers?.some(m => m.id === user.id) ||
        project.company?.user_id === user.id
    )
})

const fetchSubtask = async () => {
    loading.value = true
    error.value = null
    try {
        const { data } = await axios.get(`/api/subtasks/${subtaskId}`)
        subtask.value = data
    } catch (e) {
        console.error("Ошибка загрузки подзадачи", e)
        error.value = e.response?.data?.message || 'Не удалось загрузить подзадачу'
    } finally {
        loading.value = false
    }
}

const onRefresh = async () => {
    await fetchSubtask()
}

// Логика комментариев
const onCommentsUpdated = ({ type, comment, id }) => {
    if (!subtask.value.comments) subtask.value.comments = []
    if (type === "add") subtask.value.comments.push(comment)
    if (type === "update") {
        const index = subtask.value.comments.findIndex(c => c.id === comment.id)
        if (index !== -1) subtask.value.comments[index] = comment
    }
    if (type === "delete") subtask.value.comments = subtask.value.comments.filter(c => c.id !== id)
}

// Логика чеклиста
const onChecklistUpdated = (e) => {
    if (!subtask.value.checklist) subtask.value.checklist = []
    if (e.type === 'add') subtask.value.checklist.push(e.item)
    if (e.type === 'toggle') {
        const item = subtask.value.checklist.find(i => i.id === e.id)
        if (item) item.completed = e.completed
    }
    if (e.type === 'delete') subtask.value.checklist = subtask.value.checklist.filter(i => i.id !== e.id)
}

// Проверка прав на комментарии
const canWriteComments = computed(() => {
    if (!subtask.value || !user) return false
    const project = subtask.value.task?.project || {}
    return (
        subtask.value.creator_id === user.id ||
        project.managers?.some(m => m.id === user.id) ||
        project.executors?.some(e => e.id === user.id) ||
        project.company?.user_id === user.id ||
        subtask.value.executors?.some(e => e.id === user.id) ||
        subtask.value.responsibles?.some(r => r.id === user.id)
    )
})

const onStartWork = async (id) => {
    const targetId = id || subtaskId
    if(!targetId) return

    try {
        const { data } = await axios.post(`/api/subtasks/${targetId}/start`)
        // Показываем уведомление
        const event = new CustomEvent('show-notification', {
            detail: { type: 'success', message: 'Вы взяли подзадачу в работу!' }
        })
        window.dispatchEvent(event)
        await fetchSubtask()
    } catch (e) {
        console.error(e)
        const event = new CustomEvent('show-notification', {
            detail: { type: 'error', message: e.response?.data?.message || 'Ошибка' }
        })
        window.dispatchEvent(event)
    }
}

// Provide для дочерних компонентов
provide('canEdit', canEdit)
provide('isCreator', isCreator)
provide('onRefresh', onRefresh)

onMounted(fetchSubtask)
</script>

<template>
    <Head title="Подзадача" />
    <AuthenticatedLayout>
        <template #header>
            <SubtaskHeader
                v-if="subtask"
                :subtask="subtask"
                :user="user"
                @refresh="onRefresh"
                @startWork="onStartWork"
            />
        </template>

        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <!-- Состояние загрузки -->
            <div v-if="loading" class="flex justify-center items-center min-h-[400px]">
                <div class="relative">
                    <div class="animate-spin rounded-full h-16 w-16 border-4 border-indigo-200 border-t-indigo-600"></div>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <svg class="w-6 h-6 text-indigo-600 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Ошибка -->
            <div v-else-if="error" class="flex flex-col items-center justify-center min-h-[400px] text-center">
                <div class="bg-red-50 dark:bg-red-900/20 rounded-2xl p-8 max-w-md">
                    <svg class="w-16 h-16 text-red-500 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Ошибка загрузки</h3>
                    <p class="text-gray-600 dark:text-gray-400">{{ error }}</p>
                    <button @click="fetchSubtask" class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                        Попробовать снова
                    </button>
                </div>
            </div>

            <!-- Контент -->
            <div v-else-if="subtask" class="space-y-6">
                <!-- Основная информация -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Левая колонка - основная информация -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Участники и прогресс -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow">
                            <SubtaskMembers
                                :subtask="subtask"
                                :user="user"
                                @refresh="onRefresh"
                            />
                            <div class="border-t border-gray-200 dark:border-gray-700">
                                <SubtaskProgress
                                    :subtask="subtask"
                                    :user="user"
                                    @refresh="onRefresh"
                                />
                            </div>
                        </div>

                        <!-- Описание -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow">
                            <SubtaskDescription
                                :subtask="subtask"
                                :user="user"
                                @refresh="onRefresh"
                            />
                        </div>

                        <!-- Файлы -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow">
                            <SubtaskFiles
                                :subtask="subtask"
                                :user="user"
                                @refresh="onRefresh"
                            />
                        </div>

                        <!-- Дочерние подзадачи -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow">
                            <SubtaskChildren
                                :subtask="subtask"
                                :user="user"
                                @refresh="onRefresh"
                            />
                        </div>
                    </div>

                    <!-- Правая колонка - чеклист и комментарии -->
                    <div class="space-y-6">
                        <!-- Чеклист -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow">
                            <div class="p-5 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-indigo-50 to-transparent dark:from-indigo-900/20">
                                <div class="flex items-center gap-2">
                                    <div class="p-2 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Чек-лист</h3>
                                    <span v-if="subtask.checklist?.length" class="ml-auto text-sm text-gray-500">
                                        {{ subtask.checklist.filter(i => i.completed).length }}/{{ subtask.checklist.length }}
                                    </span>
                                </div>
                            </div>
                            <div class="p-5">
                                <SubtaskChecklist
                                    :subtask-id="subtask.id"
                                    :checklist="subtask.checklist"
                                    :user-id="$page.props.auth.user.id"
                                    :executors="subtask.executors"
                                    :responsibles="subtask.responsibles"
                                    :can-write="canWriteComments"
                                    @updated="onChecklistUpdated"
                                />
                            </div>
                        </div>

                        <!-- Комментарии -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow">
                            <div class="p-5 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-indigo-50 to-transparent dark:from-indigo-900/20">
                                <div class="flex items-center gap-2">
                                    <div class="p-2 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                        </svg>
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Комментарии</h3>
                                    <span v-if="subtask.comments?.length" class="ml-auto text-sm text-gray-500">
                                        {{ subtask.comments.length }} {{ subtask.comments.length === 1 ? 'комментарий' :
                                        subtask.comments.length >= 2 && subtask.comments.length <= 4 ? 'комментария' : 'комментариев' }}
                                    </span>
                                </div>
                            </div>
                            <div class="p-5">
                                <SubtaskComments
                                    :subtask-id="subtask.id"
                                    :comments="subtask.comments"
                                    :can-write="canWriteComments"
                                    :members="[...(subtask.executors ?? []), ...(subtask.responsibles ?? [])]"
                                    @updated="onCommentsUpdated"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Нет доступа -->
            <div v-else class="flex flex-col items-center justify-center min-h-[400px] text-center">
                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-2xl p-8 max-w-md">
                    <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Нет доступа</h3>
                    <p class="text-gray-600 dark:text-gray-400">У вас нет прав для просмотра этой подзадачи</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Плавные переходы */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* Анимация для карточек */
.bg-white, .dark\:bg-gray-800 {
    transition: all 0.2s ease-in-out;
}

.bg-white:hover, .dark\:bg-gray-800:hover {
    transform: translateY(-2px);
}

/* Градиентные бордеры для выделения */
.gradient-border {
    position: relative;
    border: double 1px transparent;
    background-image: linear-gradient(white, white),
    radial-gradient(circle at top left, #4f46e5, #818cf8);
    background-origin: border-box;
    background-clip: padding-box, border-box;
}

.dark .gradient-border {
    background-image: linear-gradient(#1f2937, #1f2937),
    radial-gradient(circle at top left, #6366f1, #a78bfa);
}

/* Анимация для загрузки */
@keyframes pulse-ring {
    0% {
        transform: scale(0.8);
        opacity: 0.5;
    }
    50% {
        transform: scale(1);
        opacity: 0.2;
    }
    100% {
        transform: scale(0.8);
        opacity: 0.5;
    }
}

.loading-ring {
    animation: pulse-ring 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Кастомный скроллбар */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

.dark .custom-scrollbar::-webkit-scrollbar-track {
    background: #374151;
}

.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #4b5563;
}

.dark .custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #6b7280;
}
</style>
