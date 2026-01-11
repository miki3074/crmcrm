<script setup>
import { computed, ref } from 'vue' // Импортируем computed

const props = defineProps({
    task: Object,
    perms: Object
})

const emit = defineEmits([
    'edit', 'delete', 'description', 'back', 'finish',
    'changeExecutor', 'changeResponsible', 'addExecutor', 'addResponsible',
    'addWatcher', 'manageMembers',
    'startWork' // 👈 Добавляем новый эмит
])

const priorityBadge = (p) => {
    if (p === 'high') return 'bg-rose-100 text-rose-700 ring-1 ring-rose-200'
    if (p === 'medium') return 'bg-amber-100 text-amber-700 ring-1 ring-amber-200'
    return 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200'
}
const priorityLabel = (p) => (p === 'high' ? 'Высокая' : p === 'medium' ? 'Средняя' : 'Обычная')

// Логика отображения статуса
const statusBadge = computed(() => {
    if (props.task?.completed) return { text: '✅ Завершена', class: 'bg-emerald-100 text-emerald-700' }
    if (props.task?.status === 'in_work') return { text: '⚙️ В работе', class: 'bg-blue-100 text-blue-700 ring-1 ring-blue-200' }
    return { text: '🆕 Новая', class: 'bg-gray-100 text-gray-600 ring-1 ring-gray-200' }
})

const MAX_LENGTH = 100 // сколько символов показываем в карточке

const showDescriptionModal = ref(false)

const isLongDescription = computed(() => {
    return props.task?.description?.length > MAX_LENGTH
})

const shortDescription = computed(() => {
    if (!props.task?.description) return ''
    if (!isLongDescription.value) return props.task.description
    return props.task.description.slice(0, MAX_LENGTH) + '…'
})


</script>

<template>
    <div class="relative overflow-hidden rounded-b-3xl shadow-lg mb-8">
        <div class="absolute inset-0 bg-gradient-to-r from-sky-600 via-indigo-600 to-fuchsia-600"></div>
        <div class="relative max-w-7xl mx-auto px-6 py-10 text-white">
            <div class="flex flex-col lg:flex-row lg:justify-between lg:items-start gap-8">

                <!-- Левая часть: Инфо -->
                <div class="flex-1 space-y-4">
                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="text-3xl sm:text-4xl font-bold tracking-tight">{{ task?.title || 'Загрузка...' }}</h1>

                        <!-- Статус задачи -->
                        <span class="px-3 py-1 text-xs rounded-full shadow-sm font-bold bg-white/90" :class="statusBadge.class">
                            {{ statusBadge.text }}
                        </span>

                        <span v-if="task?.is_watcher" class="px-3 py-1 text-xs rounded-full bg-blue-100 text-blue-700 shadow-sm">👁 Вы наблюдаете</span>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 text-sm font-medium">
                        <span class="badge">📁 Проект: <b>{{ task?.project?.name }}</b></span>
                        <span class="badge">👤 Автор: <b>{{ task?.creator?.name }}</b></span>
                        <span class="badge" :class="priorityBadge(task?.priority)">⚡ {{ priorityLabel(task?.priority) }}</span>
                    </div>

                    <div
                        v-if="task?.description"
                        class="bg-white/10 rounded-xl p-4 mt-4 backdrop-blur-sm max-w-3xl border border-white/10"
                    >
                        <h3 class="text-xs font-bold uppercase opacity-70 mb-1">О задаче</h3>

                        <p class="text-gray-100 whitespace-pre-line text-sm">
                            {{ shortDescription }}
                        </p>

                        <button
                            v-if="isLongDescription"
                            @click="showDescriptionModal = true"
                            class="mt-2 text-xs font-semibold text-sky-300 hover:text-sky-200 transition"
                        >
                            Показать полностью →
                        </button>
                    </div>

                </div>

                <!-- Правая часть: Кнопки -->
                <div class="flex flex-col gap-4 w-full sm:w-auto">
                    <!-- Действия -->
                    <div class="flex flex-wrap justify-start sm:justify-end gap-2">
                        <button v-if="perms.canUpdate" @click="$emit('edit')" class="btn-action bg-blue-500 hover:bg-blue-600">✏️ Изменить</button>
                        <button v-if="perms.canDelete" @click="$emit('delete')" class="btn-action bg-rose-500 hover:bg-rose-600">🗑 Удалить</button>
                        <button @click="$emit('description')" class="btn-action bg-indigo-500 hover:bg-indigo-600">📝 Описание</button>
                        <button @click="$emit('back')" class="btn-action bg-white text-gray-900 hover:bg-gray-100">🔙 Назад</button>
                    </div>

                    <!-- Персонал -->
                    <div v-if="perms.canManageMembers" class="bg-white/10 rounded-2xl p-4 border border-white/20 backdrop-blur-sm">
                        <h4 class="text-xs font-bold uppercase opacity-70 mb-3">⚙️ Участники</h4>
                        <div class="grid grid-cols-2 gap-2">
                            <button @click="$emit('changeExecutor')" class="btn-grid bg-blue-500">👷 Сменить Исп.</button>
                            <button @click="$emit('changeResponsible')" class="btn-grid bg-indigo-500">👨‍💼 Сменить Отв.</button>
                            <button @click="$emit('addExecutor')" class="btn-grid bg-emerald-500">➕ Доб. Исп.</button>
                            <button @click="$emit('addResponsible')" class="btn-grid bg-teal-500">➕ Доб. Отв.</button>
                            <button @click="$emit('addWatcher')" class="btn-grid bg-purple-500 col-span-2">👁 Доб. Наблюдателя</button>
                            <button @click="$emit('manageMembers')" class="btn-grid bg-slate-500 col-span-2">⚙️ Управление</button>
                        </div>
                    </div>



                    <!-- Управление статусом -->
                    <div v-if="perms.canManageTask" class="flex flex-col gap-2 justify-end mt-2">

                        <!-- КНОПКА ВЗЯТЬ В РАБОТУ -->
                        <!-- Показываем, если задача не завершена и статус 'new' -->
                        <button
                            v-if="!task?.completed && task?.status === 'new'"
                            @click="$emit('startWork', task.id)"
                            class="w-full py-3 rounded-xl bg-sky-500 hover:bg-sky-600 text-white font-bold shadow-lg transition transform hover:scale-[1.02] flex items-center justify-center gap-2"
                        >
                            🚀 Взять в работу
                        </button>

                        <button v-if="perms.canFinish && !task?.completed" @click="$emit('finish')" class="w-full py-3 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-bold shadow-lg transition transform hover:scale-[1.02]">
                            ✅ Завершить задачу
                        </button>

                        <div v-else-if="task?.progress === 100 && !task?.completed" class="text-xs text-amber-200 text-right bg-amber-900/30 p-2 rounded">
                            ⚠️ Закройте подзадачи
                        </div>
                        <div v-if="task?.completed" class="text-sm font-bold text-emerald-200 bg-emerald-900/30 px-3 py-1 rounded text-center">
                            ✅ Завершена {{ new Date(task.completed_at).toLocaleDateString() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- МОДАЛЬНОЕ ОКНО ОПИСАНИЯ -->
    <div
        v-if="showDescriptionModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm"
    >
        <div
            class="bg-slate-900 text-white max-w-3xl w-full mx-4 rounded-2xl shadow-2xl border border-white/10"
        >
            <!-- Header -->
            <div class="flex justify-between items-center px-6 py-4 border-b border-white/10">
                <h3 class="text-sm font-bold uppercase opacity-70">
                    Полное описание задачи
                </h3>
                <button
                    @click="showDescriptionModal = false"
                    class="text-white/60 hover:text-white text-xl leading-none"
                >
                    ✕
                </button>
            </div>

            <!-- Content -->
            <div class="px-6 py-4 max-h-[70vh] overflow-y-auto">
                <p class="text-sm leading-relaxed whitespace-pre-line text-gray-100">
                    {{ task.description }}
                </p>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-white/10 text-right">
                <button
                    @click="showDescriptionModal = false"
                    class="px-4 py-2 rounded-xl bg-sky-500 hover:bg-sky-600 text-sm font-semibold"
                >
                    Закрыть
                </button>
            </div>
        </div>
    </div>



</template>

<style scoped>
.badge { @apply px-3 py-1.5 rounded-lg bg-white/20 backdrop-blur-sm border border-white/10; }
.btn-action { @apply px-4 py-2 rounded-xl font-semibold shadow-sm transition text-sm text-white; }
.btn-grid { @apply px-2 py-2 rounded-lg text-xs font-semibold text-white shadow-sm transition hover:scale-[1.02]; }
</style>
