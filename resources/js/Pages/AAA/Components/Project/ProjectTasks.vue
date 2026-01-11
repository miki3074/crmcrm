<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'
import { Link } from '@inertiajs/vue3'

const props = defineProps(['project', 'user', 'employees'])
const emit = defineEmits(['refresh'])

const showModal = ref(false)
const submitting = ref(false)
const errorText = ref('')

const form = ref({
    title: '',
    priority: 'low',
    start_date: new Date().toISOString().slice(0,10),
    due_date: '',
    executor_ids: [],
    responsible_ids: [],
    files: null
})

// Permissions
const canCreate = computed(() => {
    return props.project.company?.user_id === props.user.id ||
        props.project.managers?.some(m => m.id === props.user.id) ||
        props.project.executors?.some(e => e.id === props.user.id)
})

// Helpers
const getPriorityStyles = (p) => {
    if (p === 'high') return { text: 'Высокий', class: 'bg-rose-50 text-rose-600 border-rose-100' }
    if (p === 'medium') return { text: 'Средний', class: 'bg-amber-50 text-amber-600 border-amber-100' }
    return { text: 'Низкий', class: 'bg-emerald-50 text-emerald-600 border-emerald-100' }
}

const formatDate = (d) => d ? new Date(d).toLocaleDateString('ru-RU', {day: 'numeric', month: 'short'}) : '—'

const getInitials = (name) => name?.split(' ').map(p => p[0]).slice(0, 2).join('').toUpperCase() || '?'

// File Handler
const handleFiles = (e) => { form.value.files = e.target.files }

const downloadFile = (fileUrl, fileName) => {
    // Создаем временную ссылку для скачивания
    const link = document.createElement('a')
    link.href = fileUrl
    link.setAttribute('download', fileName)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
}

const createTask = async () => {
    submitting.value = true; errorText.value = ''
    const fd = new FormData()
    fd.append('title', form.value.title); fd.append('priority', form.value.priority)
    fd.append('start_date', form.value.start_date); fd.append('due_date', form.value.due_date)
    fd.append('project_id', props.project.id); fd.append('company_id', props.project.company.id)
    form.value.executor_ids.forEach(id => fd.append('executor_ids[]', id))
    form.value.responsible_ids.forEach(id => fd.append('responsible_ids[]', id))
    if(form.value.files) { for(let i=0; i<form.value.files.length; i++) fd.append('files[]', form.value.files[i]) }

    try {
        await axios.post('/api/tasks', fd)
        emit('refresh')
        showModal.value = false
        // Reset
        form.value = { title: '', priority: 'low', start_date: new Date().toISOString().slice(0,10), due_date: '', executor_ids: [], responsible_ids: [], files: null }
    } catch(e) { errorText.value = 'Ошибка при создании задачи' }
    finally { submitting.value = false }
}
</script>

<template>
    <div class="space-y-6">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white flex items-center gap-2">
                <span class="bg-indigo-100 text-indigo-600 p-1.5 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                </span>
                Задачи проекта
                <span class="text-sm font-medium text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full ml-2">{{ project.tasks?.length || 0 }}</span>
            </h2>
            <button v-if="canCreate" @click="showModal=true" class="btn-primary group">
                <svg class="w-4 h-4 mr-2 group-hover:rotate-90 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Новая задача
            </button>
        </div>

        <!-- Empty State -->
        <div v-if="!project.tasks?.length" class="bg-white dark:bg-slate-800 rounded-3xl p-10 text-center border-2 border-dashed border-slate-200 dark:border-slate-700">
            <div class="w-16 h-16 bg-slate-50 dark:bg-slate-700 rounded-full flex items-center justify-center mx-auto mb-4 text-4xl">📭</div>
            <h3 class="text-lg font-bold text-slate-700 dark:text-white mb-1">Задач пока нет</h3>
            <p class="text-slate-500 text-sm mb-4">Создайте первую задачу, чтобы начать работу над проектом</p>
            <button v-if="canCreate" @click="showModal=true" class="text-indigo-600 font-semibold hover:underline">Создать задачу</button>
        </div>

        <!-- Task Grid -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">

            <Link
                v-for="t in project.tasks"
                :key="t.id"
                :href="`/tasks/${t.id}`"
                class="group bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 hover:shadow-lg hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between"
                :class="{'border-emerald-400 ring-1 ring-emerald-400 bg-emerald-50/30': t.progress === 100}"
            >
                <!-- Top: Header -->
                <div>
                    <div class="flex justify-between items-start mb-3">
                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wide border"
                              :class="getPriorityStyles(t.priority).class">
                            {{ getPriorityStyles(t.priority).text }}
                        </span>

                        <!-- Progress Radial (Mini) -->
                        <div class="flex items-center gap-1.5" :title="`Прогресс: ${t.progress}%`">
                            <span class="text-xs font-bold text-slate-500">{{ t.progress }}%</span>
                            <div class="w-16 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-indigo-500 rounded-full transition-all duration-500" :style="`width: ${t.progress}%`"></div>
                            </div>
                        </div>
                    </div>

                    <h3 class="font-bold text-slate-800 dark:text-white text-lg leading-snug mb-2 group-hover:text-indigo-600 transition-colors line-clamp-2">
                        {{ t.title }}
                    </h3>

                    <!-- Dates -->
                    <div class="flex items-center gap-3 text-xs text-slate-500 mb-4 font-medium">
                        <div class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            {{ formatDate(t.start_date) }}
                        </div>
                        <span class="text-slate-300">•</span>
                        <div class="flex items-center gap-1" :class="{'text-rose-500': new Date(t.due_date) < new Date()}">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            {{ formatDate(t.due_date) }}
                        </div>
                    </div>
                </div>

                <!-- Bottom: Footer -->
                <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700">

                    <!-- Files Quick Access -->
                    <div v-if="t.files?.length" class="mb-3 flex flex-wrap gap-2">
                        <div
                            v-for="file in t.files.slice(0, 3)"
                            :key="file.id"
                            @click.prevent.stop="downloadFile(file.file_path, file.file_path.split('/').pop())"
                            class="flex items-center gap-1.5 px-2 py-1 bg-slate-50 hover:bg-indigo-50 border border-slate-200 hover:border-indigo-200 rounded text-[10px] text-slate-600 hover:text-indigo-700 transition cursor-pointer"
                            title="Скачать файл"
                        >
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                            <span class="truncate max-w-[80px]">файл</span>
                            <svg class="w-3 h-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        </div>
                        <span v-if="t.files.length > 3" class="text-[10px] text-slate-400 py-1">+{{ t.files.length - 3 }}</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <!-- Avatars -->
                        <div class="flex -space-x-2 overflow-hidden">
                            <div v-for="r in t.responsibles?.slice(0,3)" :key="r.id"
                                 class="w-7 h-7 rounded-full bg-white dark:bg-slate-800 border-2 border-white dark:border-slate-700 flex items-center justify-center text-[9px] font-bold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-700"
                                 :title="r.name">
                                {{ getInitials(r.name) }}
                            </div>
                        </div>
                        <span class="text-xs text-slate-400 font-medium">Перейти →</span>
                    </div>
                </div>
            </Link>
        </div>
    </div>

    <!-- Create Task Modal -->
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showModal=false"></div>

            <div class="relative bg-white dark:bg-slate-800 rounded-2xl w-full max-w-2xl shadow-2xl flex flex-col max-h-[90vh]">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-800 rounded-t-2xl">
                    <h3 class="text-lg font-bold text-slate-800 dark:text-white">Новая задача</h3>
                    <button @click="showModal=false" class="text-slate-400 hover:text-slate-600 transition">✕</button>
                </div>

                <div class="p-6 overflow-y-auto custom-scrollbar">
                    <form @submit.prevent="createTask" class="space-y-5">
                        <div v-if="errorText" class="p-3 bg-rose-50 text-rose-600 text-sm rounded-lg border border-rose-100">
                            {{ errorText }}
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">Название задачи <span class="text-rose-500">*</span></label>
                            <input v-model="form.title" class="input-primary" placeholder="Например: Разработка макета главной страницы" required />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Приоритет</label>
                                <select v-model="form.priority" class="input-primary">
                                    <option value="low">🟢 Низкий</option>
                                    <option value="medium">🟡 Средний</option>
                                    <option value="high">🔴 Высокий</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Дата начала</label>
                                <input type="date" v-model="form.start_date" class="input-primary">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Дедлайн</label>
                                <input type="date" v-model="form.due_date" class="input-primary">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="bg-slate-50 dark:bg-slate-700/50 p-3 rounded-xl border border-slate-100 dark:border-slate-600">
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Исполнители</label>
                                <div class="max-h-40 overflow-y-auto pr-1 custom-scrollbar space-y-1">
                                    <label v-for="u in employees" :key="u.id" class="flex items-center gap-2 p-1.5 hover:bg-white dark:hover:bg-slate-600 rounded cursor-pointer transition">
                                        <input type="checkbox" v-model="form.executor_ids" :value="u.id" class="rounded text-indigo-600 focus:ring-indigo-500">
                                        <span class="text-sm text-slate-700 dark:text-slate-200">{{u.name}}</span>
                                    </label>
                                </div>
                            </div>
                            <div class="bg-slate-50 dark:bg-slate-700/50 p-3 rounded-xl border border-slate-100 dark:border-slate-600">
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Ответственные</label>
                                <div class="max-h-40 overflow-y-auto pr-1 custom-scrollbar space-y-1">
                                    <label v-for="u in employees" :key="u.id" class="flex items-center gap-2 p-1.5 hover:bg-white dark:hover:bg-slate-600 rounded cursor-pointer transition">
                                        <input type="checkbox" v-model="form.responsible_ids" :value="u.id" class="rounded text-indigo-600 focus:ring-indigo-500">
                                        <span class="text-sm text-slate-700 dark:text-slate-200">{{u.name}}</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">Прикрепить файлы</label>
                            <input type="file" multiple @change="handleFiles" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition cursor-pointer" />
                        </div>
                    </form>
                </div>

                <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 rounded-b-2xl flex justify-end gap-3">
                    <button type="button" @click="showModal=false" class="px-5 py-2.5 rounded-xl border border-slate-300 text-slate-600 font-medium hover:bg-slate-100 transition">Отмена</button>
                    <button type="button" @click="createTask" :disabled="submitting" class="px-5 py-2.5 rounded-xl bg-indigo-600 text-white font-medium hover:bg-indigo-700 shadow-lg shadow-indigo-500/30 transition disabled:opacity-70 disabled:cursor-wait">
                        {{ submitting ? 'Создание...' : 'Создать задачу' }}
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.btn-primary {
    @apply flex items-center bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl font-medium shadow-md shadow-indigo-500/20 transition-all active:scale-95;
}
.input-primary {
    @apply w-full px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition-all;
}
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { @apply bg-slate-300 rounded-full; }
</style>
