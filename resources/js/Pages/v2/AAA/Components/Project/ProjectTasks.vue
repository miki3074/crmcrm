<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'
import { Link } from '@inertiajs/vue3'

const props = defineProps(['project', 'user', 'employees'])
const emit = defineEmits(['refresh'])

const showModal = ref(false)
const submitting = ref(false)
const errorText = ref('')
const searchQuery = ref('')
const filterPriority = ref('all')
const sortBy = ref('deadline')

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

// Filtered and sorted tasks
const filteredTasks = computed(() => {
    let tasks = props.project.tasks || []

    // Search
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        tasks = tasks.filter(t =>
            t.title.toLowerCase().includes(query) ||
            t.responsibles?.some(r => r.name.toLowerCase().includes(query))
        )
    }

    // Priority filter
    if (filterPriority.value !== 'all') {
        tasks = tasks.filter(t => t.priority === filterPriority.value)
    }

    // Sort
    tasks.sort((a, b) => {
        switch(sortBy.value) {
            case 'deadline':
                return new Date(a.due_date) - new Date(b.due_date)
            case 'priority':
                const priorityWeight = { high: 3, medium: 2, low: 1 }
                return priorityWeight[b.priority] - priorityWeight[a.priority]
            case 'progress':
                return b.progress - a.progress
            default:
                return 0
        }
    })

    return tasks
})

// Helpers
const getPriorityStyles = (p) => {
    if (p === 'high') return {
        text: 'Высокий',
        class: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300 border-rose-200 dark:border-rose-800',
        icon: '🔴'
    }
    if (p === 'medium') return {
        text: 'Средний',
        class: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300 border-amber-200 dark:border-amber-800',
        icon: '🟡'
    }
    return {
        text: 'Низкий',
        class: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
        icon: '🟢'
    }
}

const getStatusIcon = (status) => {
    const icons = {
        new: '🆕',
        in_work: '⚡',
        review: '🔍',
        completed: '✅'
    }
    return icons[status] || '📋'
}

const formatDate = (d) => {
    if (!d) return '—'
    return new Date(d).toLocaleDateString('ru-RU', { day: 'numeric', month: 'short', year: 'numeric' })
}

const getInitials = (name) => name?.split(' ').map(p => p[0]).slice(0, 2).join('').toUpperCase() || '?'

const isOverdue = (dueDate) => {
    return dueDate && new Date(dueDate) < new Date()
}

// File Handler
const handleFiles = (e) => { form.value.files = e.target.files }

const downloadFile = (fileUrl, fileName) => {
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
    fd.append('title', form.value.title)
    fd.append('priority', form.value.priority)
    fd.append('start_date', form.value.start_date)
    fd.append('due_date', form.value.due_date)
    fd.append('project_id', props.project.id)
    fd.append('company_id', props.project.company.id)
    form.value.executor_ids.forEach(id => fd.append('executor_ids[]', id))
    form.value.responsible_ids.forEach(id => fd.append('responsible_ids[]', id))
    if(form.value.files) {
        for(let i=0; i<form.value.files.length; i++) fd.append('files[]', form.value.files[i])
    }

    try {
        await axios.post('/api/tasks', fd)
        emit('refresh')
        showModal.value = false
        form.value = {
            title: '',
            priority: 'low',
            start_date: new Date().toISOString().slice(0,10),
            due_date: '',
            executor_ids: [],
            responsible_ids: [],
            files: null
        }
    } catch(e) {
        errorText.value = 'Ошибка при создании задачи'
    } finally {
        submitting.value = false
    }
}
</script>

<template>
    <div class="space-y-6">

        <!-- Header с фильтрами и поиском -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
<!--            <div class="flex items-center gap-3">-->
<!--                <div class="w-1 h-8 bg-gradient-to-b from-indigo-500 to-purple-500 rounded-full"></div>-->
<!--                <h2 class="text-xl font-semibold text-slate-800 dark:text-white">-->
<!--                    Задачи проекта-->
<!--                    <span class="ml-2 text-sm font-medium text-slate-500 bg-slate-100 dark:bg-slate-700 px-2 py-0.5 rounded-full">-->
<!--                        {{ filteredTasks.length }}-->
<!--                    </span>-->
<!--                </h2>-->
<!--            </div>-->

            <div class="flex flex-wrap items-center gap-3">
                <!-- Поиск -->
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text"
                           v-model="searchQuery"
                           placeholder="Поиск задач..."
                           class="pl-10 pr-4 py-2 w-48 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200/20 transition">
                </div>

                <!-- Фильтр по приоритету -->
                <select v-model="filterPriority"
                        class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200/20 transition">
                    <option value="all">Все приоритеты</option>
                    <option value="high">Высокий</option>
                    <option value="medium">Средний</option>
                    <option value="low">Низкий</option>
                </select>

                <!-- Сортировка -->
                <select v-model="sortBy"
                        class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200/20 transition">
                    <option value="deadline">По сроку</option>
                    <option value="priority">По приоритету</option>
                    <option value="progress">По прогрессу</option>
                </select>

                <!-- Кнопка создания -->
                <button v-if="canCreate" @click="showModal=true"
                        class="group relative px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-medium shadow-lg shadow-indigo-500/30 hover:shadow-xl hover:scale-105 transition-all overflow-hidden">
                    <span class="relative flex items-center gap-2">
                        <svg class="w-4 h-4 group-hover:rotate-90 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Новая задача
                    </span>
                </button>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="!filteredTasks.length"
             class="bg-white dark:bg-slate-800 rounded-3xl p-12 text-center border-2 border-dashed border-slate-200 dark:border-slate-700">
            <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-gradient-to-br from-indigo-100 to-purple-100 dark:from-indigo-900/30 dark:to-purple-900/30 flex items-center justify-center text-4xl">
                📭
            </div>
            <h3 class="text-lg font-semibold text-slate-700 dark:text-white mb-2">
                {{ searchQuery ? 'Задачи не найдены' : 'Задач пока нет' }}
            </h3>
            <p class="text-sm text-slate-500 mb-4">
                {{ searchQuery ? 'Попробуйте изменить параметры поиска' : 'Создайте первую задачу, чтобы начать работу над проектом' }}
            </p>
            <button v-if="canCreate && !searchQuery" @click="showModal=true"
                    class="text-indigo-600 font-medium hover:underline flex items-center gap-1 mx-auto">
                <span>Создать задачу</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </button>
        </div>

        <!-- Task Grid -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            <Link v-for="t in filteredTasks" :key="t.id"
                  :href="`/tasks/${t.id}`"
                  class="group relative bg-white dark:bg-slate-800 rounded-2xl border-2 transition-all duration-300 overflow-hidden hover:shadow-xl hover:-translate-y-1"
                  :class="[
                      t.progress === 100
                          ? 'border-emerald-400 dark:border-emerald-600 bg-gradient-to-br from-emerald-50/50 to-transparent dark:from-emerald-900/10'
                          : 'border-slate-200 dark:border-slate-700 hover:border-indigo-300 dark:hover:border-indigo-700'
                  ]">

                <!-- Декоративная полоса сверху по приоритету -->
                <div class="absolute top-0 left-0 w-full h-1"
                     :class="{
                         'bg-gradient-to-r from-rose-500 to-pink-500': t.priority === 'high',
                         'bg-gradient-to-r from-amber-500 to-orange-500': t.priority === 'medium',
                         'bg-gradient-to-r from-emerald-500 to-teal-500': t.priority === 'low'
                     }">
                </div>

                <!-- Индикатор просрочки -->
                <div v-if="isOverdue(t.due_date) && t.progress < 100"
                     class="absolute top-3 right-3">
                    <span class="flex items-center gap-1 px-2 py-1 bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-300 rounded-lg text-[10px] font-bold">
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                        ПРОСРОЧЕНО
                    </span>
                </div>

                <div class="p-5">
                    <!-- Верхний ряд: приоритет и прогресс -->
                    <div class="flex items-start justify-between mb-3">
                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold border flex items-center gap-1"
                              :class="getPriorityStyles(t.priority).class">
                            <span>{{ getPriorityStyles(t.priority).icon }}</span>
                            {{ getPriorityStyles(t.priority).text }}
                        </span>

                        <!-- Круговой прогресс (мини) -->
                        <div class="relative w-10 h-10">
                            <svg class="w-10 h-10 transform -rotate-90">
                                <circle cx="20" cy="20" r="16" stroke="currentColor" stroke-width="2" fill="none"
                                        class="text-slate-100 dark:text-slate-700"/>
                                <circle cx="20" cy="20" r="16" stroke="currentColor" stroke-width="2" fill="none"
                                        :stroke-dasharray="100.53"
                                        :stroke-dashoffset="100.53 - (100.53 * t.progress / 100)"
                                        class="text-indigo-600 dark:text-indigo-400 transition-all duration-1000"/>
                            </svg>
                            <span class="absolute inset-0 flex items-center justify-center text-[8px] font-bold text-slate-600 dark:text-slate-300">
                                {{ t.progress }}%
                            </span>
                        </div>
                    </div>

                    <!-- Название задачи -->
                    <h3 class="font-bold text-slate-800 dark:text-white text-base leading-snug mb-2 group-hover:text-indigo-600 transition-colors line-clamp-2">
                        {{ t.title }}
                    </h3>

                    <!-- Даты -->
                    <div class="flex items-center gap-2 text-xs text-slate-500 mb-4">
                        <div class="flex items-center gap-1 bg-slate-50 dark:bg-slate-700/50 px-2 py-1 rounded-lg">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            {{ formatDate(t.start_date) }}
                        </div>
                        <span class="text-slate-300">→</span>
                        <div class="flex items-center gap-1 px-2 py-1 rounded-lg"
                             :class="isOverdue(t.due_date) && t.progress < 100 ? 'bg-rose-50 dark:bg-rose-900/20 text-rose-600' : 'bg-slate-50 dark:bg-slate-700/50 text-slate-500'">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ formatDate(t.due_date) }}
                        </div>
                    </div>

                    <!-- Файлы -->
                    <div v-if="t.files?.length" class="mb-3 flex flex-wrap gap-1.5">
                        <div v-for="file in t.files.slice(0, 2)" :key="file.id"
                             @click.prevent.stop="downloadFile(file.file_path, file.file_path.split('/').pop())"
                             class="flex items-center gap-1 px-2 py-1 bg-slate-50 dark:bg-slate-700/50 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 border border-slate-200 dark:border-slate-600 hover:border-indigo-300 rounded-lg text-[10px] text-slate-600 hover:text-indigo-700 transition cursor-pointer group/file">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                            </svg>
                            <span class="truncate max-w-[60px]">{{ file.file_path.split('/').pop() }}</span>
                            <svg class="w-3 h-3 opacity-0 group-hover/file:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                        </div>
                        <span v-if="t.files.length > 2"
                              class="text-[10px] px-2 py-1 bg-slate-50 dark:bg-slate-700/50 rounded-lg text-slate-500">
                            +{{ t.files.length - 2 }}
                        </span>
                    </div>

                    <!-- Футер с аватарками -->
                    <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <!-- Иконка статуса -->
                                <span class="text-sm">{{ getStatusIcon(t.status) }}</span>

                                <!-- Аватарки ответственных -->
                                <div class="flex -space-x-2">
                                    <div v-for="r in t.responsibles?.slice(0, 3)" :key="r.id"
                                         class="relative w-6 h-6 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white text-[8px] font-bold border-2 border-white dark:border-slate-800"
                                         :title="r.name">
                                        {{ getInitials(r.name) }}
                                    </div>
                                    <div v-if="t.responsibles?.length > 3"
                                         class="w-6 h-6 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center text-[8px] font-bold text-slate-600 dark:text-slate-400 border-2 border-white dark:border-slate-800">
                                        +{{ t.responsibles.length - 3 }}
                                    </div>
                                </div>
                            </div>

                            <!-- Индикатор перехода -->
                            <div class="flex items-center gap-1 text-xs text-slate-400 group-hover:text-indigo-600 transition-colors">
                                <span>Подробнее</span>
                                <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </Link>
        </div>

        <!-- Create Task Modal (Современный дизайн) -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95">

            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <!-- Backdrop с эффектом стекла -->
                <div class="absolute inset-0 bg-slate-900/40 dark:bg-black/60 backdrop-blur-md" @click="showModal=false"></div>

                <!-- Modal Content -->
                <div class="relative w-full max-w-2xl bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden flex flex-col max-h-[90vh]">

                    <!-- Декоративная полоса сверху -->
                    <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500"></div>

                    <!-- Header -->
                    <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white text-lg shadow-lg">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Создание новой задачи</h3>
                                    <p class="text-xs text-slate-500 mt-1">Заполните информацию о задаче</p>
                                </div>
                            </div>
                            <button @click="showModal=false"
                                    class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 flex items-center justify-center text-slate-500 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="flex-1 overflow-y-auto custom-scrollbar p-6">
                        <form @submit.prevent="createTask" class="space-y-5">
                            <div v-if="errorText"
                                 class="p-4 bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800 rounded-xl text-sm text-rose-700 dark:text-rose-300">
                                {{ errorText }}
                            </div>

                            <!-- Название -->
                            <div class="space-y-2">
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">
                                    Название задачи <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-slate-400">📋</span>
                                    </div>
                                    <input v-model="form.title"
                                           class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:border-indigo-300 focus:ring focus:ring-indigo-200/20 transition"
                                           placeholder="Например: Разработка макета главной страницы"
                                           required />
                                </div>
                            </div>

                            <!-- Приоритет и даты -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div class="space-y-2">
                                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">Приоритет</label>
                                    <select v-model="form.priority"
                                            class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:border-indigo-300 focus:ring focus:ring-indigo-200/20 transition">
                                        <option value="low">🟢 Низкий</option>
                                        <option value="medium">🟡 Средний</option>
                                        <option value="high">🔴 Высокий</option>
                                    </select>
                                </div>
                                <div class="space-y-2">
                                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">Дата начала</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-slate-400">📅</span>
                                        </div>
                                        <input type="date" v-model="form.start_date"
                                               class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:border-indigo-300 focus:ring focus:ring-indigo-200/20 transition">
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">Дедлайн</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-slate-400">⏰</span>
                                        </div>
                                        <input type="date" v-model="form.due_date"
                                               class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:border-indigo-300 focus:ring focus:ring-indigo-200/20 transition">
                                    </div>
                                </div>
                            </div>

                            <!-- Исполнители и ответственные -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Исполнители -->
                                <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
                                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">
                                        Исполнители
                                    </label>
                                    <div class="max-h-40 overflow-y-auto space-y-2 pr-2 custom-scrollbar">
                                        <label v-for="u in employees" :key="u.id"
                                               class="flex items-center gap-3 p-2 rounded-lg hover:bg-white dark:hover:bg-slate-700 cursor-pointer transition">
                                            <input type="checkbox" v-model="form.executor_ids" :value="u.id"
                                                   class="w-4 h-4 text-indigo-600 rounded focus:ring-indigo-500">
                                            <div class="flex items-center gap-2 flex-1">
                                                <div class="w-6 h-6 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white text-[8px] font-bold">
                                                    {{ getInitials(u.name) }}
                                                </div>
                                                <span class="text-sm text-slate-700 dark:text-slate-200">{{ u.name }}</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Ответственные -->
                                <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
                                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">
                                        Ответственные
                                    </label>
                                    <div class="max-h-40 overflow-y-auto space-y-2 pr-2 custom-scrollbar">
                                        <label v-for="u in employees" :key="u.id"
                                               class="flex items-center gap-3 p-2 rounded-lg hover:bg-white dark:hover:bg-slate-700 cursor-pointer transition">
                                            <input type="checkbox" v-model="form.responsible_ids" :value="u.id"
                                                   class="w-4 h-4 text-indigo-600 rounded focus:ring-indigo-500">
                                            <div class="flex items-center gap-2 flex-1">
                                                <div class="w-6 h-6 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white text-[8px] font-bold">
                                                    {{ getInitials(u.name) }}
                                                </div>
                                                <span class="text-sm text-slate-700 dark:text-slate-200">{{ u.name }}</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Файлы -->
                            <div class="space-y-2">
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">
                                    Прикрепить файлы
                                </label>
                                <div class="relative">
                                    <input type="file" multiple @change="handleFiles"
                                           class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition cursor-pointer" />
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Footer -->
                    <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 flex justify-end gap-3">
                        <button type="button" @click="showModal=false"
                                class="px-6 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-400 font-medium hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                            Отмена
                        </button>
                        <button type="button" @click="createTask" :disabled="submitting"
                                class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-medium shadow-lg shadow-indigo-500/30 hover:shadow-xl hover:scale-105 transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100">
                            <span class="flex items-center gap-2">
                                <svg v-if="submitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ submitting ? 'Создание...' : 'Создать задачу' }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
/* Кастомный скроллбар */
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 20px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #475569;
}

/* Анимации */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.fade-enter-active,
.fade-leave-active {
    transition: all 0.3s ease;
}

.fade-enter-from {
    opacity: 0;
    transform: translateY(10px);
}

.fade-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}

/* Ограничение текста */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Эффект стекла */
.backdrop-blur-md {
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}
</style>
