<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from 'axios'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, router } from '@inertiajs/vue3' // Link убрали

const loading = ref(true)
const archiveData = ref([])
const availableUsers = ref([])

const filters = ref({
    mode: 'my_tasks',
    user_id: '',
    type: 'all'
})

const fetchArchive = async () => {
    loading.value = true
    try {
        const params = {
            mode: filters.value.mode,
            user_id: filters.value.user_id || undefined,
            type: filters.value.type,
            status: 'completed' // Запрашиваем только завершенные
        }

        const { data } = await axios.get('/api/tasks/summary', { params })

        archiveData.value = data.summary
        if (!filters.value.user_id) {
            availableUsers.value = data.users
        }
    } catch (e) {
        console.error(e)
    } finally {
        loading.value = false
    }
}

watch(filters, () => fetchArchive(), { deep: true })
onMounted(fetchArchive)

// Функция восстановления
const restoreItem = (id, isSubtask) => {
    if (!confirm('Вернуть задачу в работу?')) return;

    // Формируем URL вручную, раз Ziggy выдает ошибки
    const url = isSubtask
        ? `/subtasks/${id}/restore`
        : `/tasks/${id}/restore`;

    router.post(url, {}, {
        preserveScroll: true,
        onSuccess: () => fetchArchive()
    });
};
</script>

<template>
    <Head title="Архив задач" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-bold text-2xl text-slate-800 dark:text-white flex items-center gap-2">
                🗄️ Архив завершенных задач
            </h2>
        </template>

        <div class="py-12 bg-slate-50 dark:bg-slate-900 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Фильтры -->
                <div class="bg-white dark:bg-slate-800 p-4 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 flex flex-wrap gap-4 items-center justify-between">
                    <div class="flex gap-2 bg-slate-100 dark:bg-slate-700 p-1 rounded-lg">
                        <button @click="filters.mode = 'my_tasks'" :class="filters.mode === 'my_tasks' ? 'bg-white shadow text-indigo-600 dark:text-indigo-400 dark:bg-slate-600' : 'text-slate-500'" class="px-3 py-1.5 text-sm font-bold rounded-md transition">Мои задачи</button>
                        <button @click="filters.mode = 'author'" :class="filters.mode === 'author' ? 'bg-white shadow text-indigo-600 dark:text-indigo-400 dark:bg-slate-600' : 'text-slate-500'" class="px-3 py-1.5 text-sm font-bold rounded-md transition">Я автор</button>
                        <button @click="filters.mode = 'owner'" :class="filters.mode === 'owner' ? 'bg-white shadow text-indigo-600 dark:text-indigo-400 dark:bg-slate-600' : 'text-slate-500'" class="px-3 py-1.5 text-sm font-bold rounded-md transition">Все</button>
                    </div>

                    <div class="flex gap-4">
                        <select v-model="filters.type" class="text-sm rounded-lg border-slate-300 dark:bg-slate-700 dark:text-white dark:border-slate-600">
                            <option value="all">Все типы</option>
                            <option value="task">Только задачи</option>
                            <option value="subtask">Только подзадачи</option>
                        </select>

                        <select v-model="filters.user_id" class="text-sm rounded-lg border-slate-300 dark:bg-slate-700 dark:text-white dark:border-slate-600">
                            <option value="">Все сотрудники</option>
                            <option v-for="u in availableUsers" :key="u.id" :value="u.id">{{ u.name }}</option>
                        </select>
                    </div>
                </div>

                <!-- Список -->
                <div v-if="loading" class="text-center py-10 text-slate-500">Загрузка...</div>

                <div v-else class="space-y-6">
                    <div v-if="archiveData.length === 0" class="text-center py-10 text-slate-400 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
                        В архиве пусто.
                    </div>

                    <!-- Карточка сотрудника -->
                    <div v-for="userData in archiveData" :key="userData.user.id" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">

                        <div class="bg-slate-50 dark:bg-slate-900/50 px-6 py-3 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-900 text-emerald-700 dark:text-emerald-300 flex items-center justify-center font-bold text-xs">
                                    {{ userData.user.name.charAt(0) }}
                                </div>
                                <h3 class="font-bold text-slate-700 dark:text-slate-200">{{ userData.user.name }}</h3>
                            </div>
                            <span class="text-xs font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300 px-2 py-1 rounded-full">
                                {{ userData.tasks.completed.length }} шт.
                            </span>
                        </div>

                        <div class="divide-y divide-slate-100 dark:divide-slate-700">
                            <div v-for="task in userData.tasks.completed" :key="task.id + (task.is_subtask ? 's' : 't')"
                                 class="p-4 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition flex items-center justify-between group">

                                <div class="flex items-start gap-3 overflow-hidden">
                                    <div class="mt-1 flex-shrink-0 text-emerald-500">
                                        <!-- Иконка задачи или подзадачи -->
                                        <svg v-if="!task.is_subtask" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        <svg v-else class="w-5 h-5 rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg>
                                    </div>

                                    <div>
                                        <div class="flex items-center gap-2">
                                            <!-- ИСПОЛЬЗУЕМ ОБЫЧНЫЙ ТЕГ A -->
                                            <!-- task.link приходит готовым с контроллера -->
                                            <a :href="task.link"
                                               class="font-bold text-slate-700 dark:text-slate-200 hover:text-emerald-600 transition truncate hover:underline">
                                                {{ task.title }}
                                            </a>

                                            <span v-if="task.is_subtask" class="text-[10px] uppercase bg-slate-100 dark:bg-slate-600 px-1.5 rounded text-slate-500">
                                                Подзадача
                                            </span>
                                        </div>
                                        <div class="text-xs text-slate-500 mt-1 flex gap-3">
                                            <span>📂 {{ task.project_name }}</span>
                                            <span>👤 {{ task.roles }}</span>
                                        </div>
                                    </div>
                                </div>

                                <button
                                    @click="restoreItem(task.id, task.is_subtask)"
                                    class="opacity-0 group-hover:opacity-100 transition-opacity px-3 py-1.5 text-xs font-bold text-indigo-600 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-900/30 dark:text-indigo-400 dark:hover:bg-indigo-900/50 rounded-lg border border-indigo-200 dark:border-indigo-800"
                                >
                                    Восстановить
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
