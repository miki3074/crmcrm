<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

import { Calendar } from '@fullcalendar/core'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'
import ruLocale from '@fullcalendar/core/locales/ru'
import axios from 'axios'

axios.defaults.withCredentials = true

const calendarEl = ref(null)
let calendar = null

const loading = ref(false)
const events = ref([])
const tasksList = ref([])
const selectedTask = ref(null)

// UI state
const showCreateModal = ref(false)
const showViewModal = ref(false)
const showMoreModal = ref(false)
const showTaskModal = ref(false)

const creatingRange = ref({ start: null, end: null })
const moreDay = ref({ date: null, items: [] })
const companies = ref([])
const companyEmployees = ref([])

const selectedEvent = ref(null)
const editing = ref(false)

const errors = ref({})

// Фильтры
const taskFilter = ref('all')
const viewType = ref('all') // 'all', 'events', 'tasks'

// форма события
const form = ref({
    id: null,
    title: '',
    description: '',
    visibility: 'personal',
    company_id: '',
    attendees: [],
    start_at: '',
    end_at: '',
})

// Computed
const isCompanyEvent = computed(() => form.value.visibility !== 'personal')
const isSelectedCompany = computed(() => form.value.visibility === 'company_selected')

const colorFor = (ev) => {
    if (ev.event_type === 'task') {
        return ev.priority === 'high' ? '#dc2626' : ev.priority === 'medium' ? '#f59e0b' : '#16a34a'
    }
    return ev.visibility === 'personal' ? '#2563eb' : '#7c3aed'
}

const badgeFor = (vis) => {
    const badges = {
        personal: 'bg-blue-100 text-blue-700 border-blue-200',
        company_selected: 'bg-violet-100 text-violet-700 border-violet-200',
        company_all: 'bg-fuchsia-100 text-fuchsia-700 border-fuchsia-200'
    }
    return badges[vis] || 'bg-gray-100 text-gray-700 border-gray-200'
}

// API helpers
const fetchEvents = async (start, end) => {
    loading.value = true
    try {
        const { data } = await axios.get('/api/calendar/events', { params: { start, end } })
        events.value = data.map(e => ({
            id: e.id,
            title: e.title || 'Событие',
            start: e.start_at,
            end: e.end_at,
            allDay: false,
            backgroundColor: colorFor(e),
            borderColor: 'transparent',
            textColor: '#ffffff',
            extendedProps: { ...e, type: 'event' }
        }))
        applyFilters()
    } finally {
        loading.value = false
    }
}

const fetchTasks = async () => {
    const { data } = await axios.get('/api/calendar/tasks', {
        params: { filter: taskFilter.value }
    })
    tasksList.value = data

    const mappedTasks = data.map(t => ({
        id: `task-${t.id}`,
        title: t.title,
        start: t.start,
        end: t.end,
        allDay: true,
        backgroundColor: colorFor({ ...t, event_type: 'task' }),
        borderColor: 'transparent',
        textColor: '#ffffff',
        extendedProps: { ...t, event_type: 'task', type: 'task' }
    }))

    // Сохраняем задачи отдельно
    tasksListEvents.value = mappedTasks
    applyFilters()
}

const tasksListEvents = ref([])

// Применение фильтров
const applyFilters = () => {
    if (!calendar) return

    calendar.removeAllEvents()

    let sources = []

    if (viewType.value === 'all' || viewType.value === 'events') {
        sources = [...sources, ...events.value]
    }

    if (viewType.value === 'all' || viewType.value === 'tasks') {
        sources = [...sources, ...tasksListEvents.value]
    }

    calendar.addEventSource(sources)
}

// Следим за изменением фильтра
watch(viewType, () => {
    applyFilters()
})

const loadCompanies = async () => {
    const { data } = await axios.get('/api/my-calendar-companies')
    companies.value = data
}

const loadCompanyEmployees = async () => {
    if (!form.value.company_id) {
        companyEmployees.value = [];
        return
    }
    const { data } = await axios.get(`/api/companies/${form.value.company_id}/employees`)
    companyEmployees.value = data
}

// CRUD операции
const openCreateModal = (startISO, endISO) => {
    form.value = {
        id: null,
        title: '',
        description: '',
        visibility: 'personal',
        company_id: '',
        attendees: [],
        start_at: (startISO ?? new Date().toISOString()).slice(0,16),
        end_at: (endISO ?? startISO ?? new Date().toISOString()).slice(0,16),
    }
    editing.value = false
    errors.value = {}
    showCreateModal.value = true
}

const submitEvent = async () => {
    errors.value = {}
    const payload = { ...form.value }
    if (payload.start_at?.length === 16) payload.start_at += ':00'
    if (payload.end_at?.length === 16) payload.end_at += ':00'

    try {
        if (editing.value && payload.id) {
            await axios.patch(`/api/calendar/events/${payload.id}`, payload)
        } else {
            await axios.post('/api/calendar/events', payload)
        }

        showCreateModal.value = false
        const view = calendar.view
        await fetchEvents(view.currentStart.toISOString(), view.currentEnd.toISOString())
        await fetchTasks()
    } catch (err) {
        if (err.response?.status === 422) {
            errors.value = err.response.data.errors || {}
        }
    }
}

const editEvent = async () => {
    const ev = selectedEvent.value
    if (!ev) return
    form.value = {
        id: ev.id,
        title: ev.title ?? '',
        description: ev.description ?? '',
        visibility: ev.visibility ?? 'personal',
        company_id: ev.company_id || '',
        attendees: ev.attendees?.map(a => a.id) || [],
        start_at: (ev.start_at ?? '').slice(0,16),
        end_at: (ev.end_at ?? '').slice(0,16),
    }
    editing.value = true
    showViewModal.value = false
    showCreateModal.value = true
    if (form.value.company_id) await loadCompanyEmployees()
}

const deleteEvent = async () => {
    if (!selectedEvent.value) return
    if (!confirm('Удалить событие?')) return
    await axios.delete(`/api/calendar/events/${selectedEvent.value.id}`)
    showViewModal.value = false
    const view = calendar.view
    await fetchEvents(view.currentStart.toISOString(), view.currentEnd.toISOString())
    await fetchTasks()
}

const patchEventDates = async ({ event }) => {
    // Проверяем, что это событие, а не задача
    if (event.id.startsWith('task-')) return

    const payload = {
        title: event.title,
        start_at: event.start?.toISOString(),
        end_at: (event.end ?? event.start)?.toISOString(),
    }
    await axios.patch(`/api/calendar/events/${event.id}`, payload)
}

// Обработчики календаря
const onEventClick = (info) => {
    const ext = info.event.extendedProps || {}

    if (ext.event_type === 'task' || ext.type === 'task') {
        selectedTask.value = ext
        showTaskModal.value = true
        return
    }

    selectedEvent.value = {
        ...ext,
        id: info.event.id,
        title: info.event.title || ext.title || '',
        start_at: info.event.start ? info.event.start.toISOString() : null,
        end_at: info.event.end ? info.event.end.toISOString() : null,
    }
    showViewModal.value = true
}

const onMoreLinkClick = (arg) => {
    moreDay.value = {
        date: arg.date,
        items: arg.allSegs.map(seg => seg.event),
    }
    showMoreModal.value = true
    return 'none'
}

const goToTask = (t) => {
    calendar.gotoDate(t.start)
    const ev = calendar.getEventById(`task-${t.id}`)
    if (ev) {
        ev.setProp('backgroundColor', '#0ea5e9')
        setTimeout(() => {
            ev.setProp('backgroundColor', colorFor({ ...t, event_type: 'task' }))
        }, 1500)
    }
}

// Инициализация
onMounted(async () => {
    await loadCompanies()

    calendar = new Calendar(calendarEl.value, {
        plugins: [dayGridPlugin, interactionPlugin],
        initialView: 'dayGridMonth',
        height: 'auto',
        locale: ruLocale,
        selectable: true,
        selectMirror: true,
        editable: true,
        dayMaxEventRows: true,
        moreLinkClick: onMoreLinkClick,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,dayGridWeek,dayGridDay',
        },
        select: (info) => {
            creatingRange.value = { start: info.startStr, end: info.endStr }
            openCreateModal(info.startStr, info.endStr)
        },
        dateClick: (info) => {
            const dt = new Date(info.dateStr)
            dt.setHours(8,0,0,0)
            openCreateModal(dt.toISOString(), dt.toISOString())
        },
        datesSet: async (info) => {
            calendar.removeAllEvents()
            await fetchEvents(info.startStr, info.endStr)
            await fetchTasks()
        },
        eventClick: onEventClick,
        eventDidMount: (info) => { info.el.style.cursor = 'pointer' },
        eventDrop: patchEventDates,
        eventResize: patchEventDates,
    })

    calendar.render()
})
</script>

<template>
    <Head title="Календарь" />
    <AuthenticatedLayout>

        <!-- Header с современным дизайном -->
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-1 h-8 bg-gradient-to-b from-indigo-500 to-purple-500 rounded-full"></div>
                    <h2 class="text-2xl font-semibold text-slate-800 dark:text-white">Календарь событий</h2>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Фильтр по типу -->
                    <div class="flex gap-1 p-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm">
                        <button @click="viewType = 'all'"
                                class="px-4 py-2 rounded-lg text-sm font-medium transition-all"
                                :class="viewType === 'all'
                      ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-md'
                      : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700'">
              <span class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                </svg>
                Все
              </span>
                        </button>
                        <button @click="viewType = 'events'"
                                class="px-4 py-2 rounded-lg text-sm font-medium transition-all"
                                :class="viewType === 'events'
                      ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-md'
                      : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700'">
              <span class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                События
              </span>
                        </button>
                        <button @click="viewType = 'tasks'"
                                class="px-4 py-2 rounded-lg text-sm font-medium transition-all"
                                :class="viewType === 'tasks'
                      ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-md'
                      : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700'">
              <span class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                Задачи
              </span>
                        </button>
                    </div>

                    <!-- Фильтр задач (только для задач) -->
                    <!--                    <select v-if="viewType === 'tasks' || viewType === 'all'" v-model="taskFilter" @change="fetchTasks"-->
                    <!--                            class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200/20 transition">-->
                    <!--                        <option value="all">Все задачи</option>-->
                    <!--                        <option value="my">Мои задачи</option>-->
                    <!--                        <option value="project">По проекту</option>-->
                    <!--                        <option value="company">По компании</option>-->
                    <!--                    </select>-->

                    <!-- Кнопка создания (только для событий) -->
                    <button v-if="viewType !== 'tasks'" @click="openCreateModal()"
                            class="group relative px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-medium shadow-lg shadow-indigo-500/30 hover:shadow-xl hover:scale-105 transition-all overflow-hidden">
            <span class="relative flex items-center gap-2">
              <svg class="w-4 h-4 group-hover:rotate-90 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              Событие
            </span>
                    </button>
                </div>
            </div>
        </template>

        <!-- Основной контент -->
        <div class="max-w-7xl mx-auto py-6 px-4">

            <!-- Индикатор загрузки -->
            <div v-if="loading" class="mb-4 flex items-center gap-2 text-sm text-indigo-600">
                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Загрузка календаря...
            </div>

            <!-- Календарь -->
            <div ref="calendarEl" class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 p-4"></div>

            <!-- Список задач (показываем только когда включены задачи) -->
            <div v-if="viewType === 'tasks' || viewType === 'all'" class="mt-8 bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700">
                    <div class="flex items-center gap-3">
                        <div class="w-1 h-6 bg-gradient-to-b from-indigo-500 to-purple-500 rounded-full"></div>
                        <h3 class="text-lg font-semibold text-slate-800 dark:text-white">Список задач</h3>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300">
              {{ tasksList.length }}
            </span>
                    </div>
                </div>

                <div class="p-6">
                    <div v-if="tasksList.length === 0" class="text-center py-8 text-slate-400">
                        <span class="text-4xl mb-2 block opacity-30">📋</span>
                        <p class="text-sm">Нет задач в выбранном периоде</p>
                    </div>

                    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div v-for="t in tasksList" :key="t.id"
                             @click="goToTask(t)"
                             class="group relative bg-slate-50 dark:bg-slate-700/30 rounded-xl border-2 transition-all duration-300 cursor-pointer overflow-hidden hover:shadow-lg hover:-translate-y-1"
                             :class="t.is_overdue ? 'border-rose-300 dark:border-rose-700' : 'border-slate-200 dark:border-slate-700 hover:border-indigo-300'">

                            <!-- Декоративная полоса сверху по приоритету -->
                            <div class="absolute top-0 left-0 w-full h-1"
                                 :class="{
                     'bg-gradient-to-r from-rose-500 to-pink-500': t.priority === 'high',
                     'bg-gradient-to-r from-amber-500 to-orange-500': t.priority === 'medium',
                     'bg-gradient-to-r from-emerald-500 to-teal-500': t.priority === 'low'
                   }">
                            </div>

                            <div class="p-4">
                                <!-- Заголовок и статус -->
                                <div class="flex items-start justify-between gap-2 mb-2">
                                    <h4 class="text-sm font-semibold text-slate-800 dark:text-white group-hover:text-indigo-600 transition-colors line-clamp-2 flex-1">
                                        {{ t.title }}
                                    </h4>
                                    <span v-if="t.is_overdue"
                                          class="shrink-0 px-2 py-0.5 rounded-full bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-300 text-[8px] font-bold">
                    ❗ Просрочено
                  </span>
                                </div>

                                <!-- Компания и проект -->
                                <div class="space-y-1 mb-3">
                                    <div v-if="t.company" class="flex items-center gap-1 text-xs text-slate-500">
                                        <span>🏢</span>
                                        <span class="truncate">{{ t.company }}</span>
                                    </div>
                                    <div v-if="t.project" class="flex items-center gap-1 text-xs text-slate-500">
                                        <span>📁</span>
                                        <span class="truncate">{{ t.project }}</span>
                                    </div>
                                </div>

                                <!-- Даты -->
                                <div class="flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-1 text-slate-500">
                                        <span>📅</span>
                                        {{ new Date(t.start).toLocaleDateString() }}
                                    </div>
                                    <span class="text-slate-300">→</span>
                                    <div class="flex items-center gap-1 text-slate-500">
                                        <span>⏰</span>
                                        {{ new Date(t.end).toLocaleDateString() }}
                                    </div>
                                </div>

                                <!-- Приоритет -->
                                <div class="mt-3 pt-2 border-t border-slate-200 dark:border-slate-700">
                  <span class="text-xs font-medium"
                        :class="{
                          'text-rose-600': t.priority === 'high',
                          'text-amber-600': t.priority === 'medium',
                          'text-emerald-600': t.priority === 'low'
                        }">
                    ● {{ t.priority === 'high' ? 'Высокий' : t.priority === 'medium' ? 'Средний' : 'Низкий' }}
                  </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Сообщение когда только события -->
            <div v-if="viewType === 'events' && events.length === 0" class="mt-8 text-center py-12 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700">
                <span class="text-6xl mb-4 block opacity-30">📅</span>
                <p class="text-lg text-slate-500">Нет событий в выбранном периоде</p>
                <button @click="openCreateModal()" class="mt-4 px-6 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition">
                    Создать событие
                </button>
            </div>
        </div>

        <!-- Create/Edit Modal (без изменений) -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95">

            <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/40 dark:bg-black/60 backdrop-blur-md" @click="showCreateModal=false"></div>

                <div class="relative w-full max-w-2xl bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">

                    <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500"></div>

                    <!-- Header -->
                    <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white text-lg shadow-lg">
                                    📅
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                                        {{ editing ? 'Редактировать событие' : 'Новое событие' }}
                                    </h3>
                                    <p class="text-xs text-slate-500 mt-1">Заполните информацию о событии</p>
                                </div>
                            </div>
                            <button @click="showCreateModal=false"
                                    class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 flex items-center justify-center text-slate-500 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Body (без изменений) -->
                    <div class="p-6 max-h-[70vh] overflow-y-auto custom-scrollbar">
                        <!-- ... остальное содержимое формы ... -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Название -->
                            <div class="md:col-span-2 space-y-2">
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">Название</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-slate-400">📋</span>
                                    </div>
                                    <input v-model="form.title"
                                           class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:border-indigo-300 focus:ring focus:ring-indigo-200/20 transition"
                                           placeholder="Введите название события" />
                                </div>
                                <p v-if="errors.title" class="text-xs text-rose-500">{{ errors.title[0] }}</p>
                            </div>

                            <!-- Тип события -->
                            <div class="space-y-2">
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">Тип</label>
                                <select v-model="form.visibility"
                                        @change="() => { if (!isCompanyEvent) { form.company_id=''; form.attendees=[] } }"
                                        class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:border-indigo-300 focus:ring focus:ring-indigo-200/20 transition">
                                    <option value="personal">Личный календарь</option>
                                    <option value="company_selected">Компания (выбор сотрудников)</option>
                                    <option value="company_all">Компания (всем)</option>
                                </select>
                            </div>

                            <!-- Даты -->
                            <div class="space-y-2">
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">Начало</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-slate-400">📅</span>
                                    </div>
                                    <input type="datetime-local" v-model="form.start_at"
                                           class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:border-indigo-300 focus:ring focus:ring-indigo-200/20 transition" />
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">Окончание</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-slate-400">⏰</span>
                                    </div>
                                    <input type="datetime-local" v-model="form.end_at"
                                           class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:border-indigo-300 focus:ring focus:ring-indigo-200/20 transition" />
                                </div>
                            </div>

                            <!-- Компания (если выбрано) -->
                            <div v-if="isCompanyEvent" class="space-y-2">
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">Компания</label>
                                <select v-model="form.company_id" @change="loadCompanyEmployees"
                                        class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:border-indigo-300 focus:ring focus:ring-indigo-200/20 transition">
                                    <option value="" disabled>Выберите компанию</option>
                                    <option v-for="c in companies" :key="c.id" :value="c.id">{{ c.name }}</option>
                                </select>
                            </div>

                            <!-- Участники (если выбрано) -->
                            <div v-if="isSelectedCompany" class="md:col-span-2 space-y-2">
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">Участники</label>
                                <select v-model="form.attendees" multiple
                                        class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:border-indigo-300 focus:ring focus:ring-indigo-200/20 transition h-32">
                                    <option v-for="u in companyEmployees" :key="u.id" :value="u.id">{{ u.name }}</option>
                                </select>
                            </div>

                            <!-- Описание -->
                            <div class="md:col-span-2 space-y-2">
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">Описание</label>
                                <textarea v-model="form.description" rows="3"
                                          class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:border-indigo-300 focus:ring focus:ring-indigo-200/20 transition resize-none"
                                          placeholder="Введите описание события..."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 flex justify-end gap-3">
                        <button @click="showCreateModal=false"
                                class="px-4 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                            Отмена
                        </button>
                        <button @click="submitEvent"
                                class="px-6 py-2 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-medium shadow-lg shadow-indigo-500/30 hover:shadow-xl hover:scale-105 transition-all">
                            {{ editing ? 'Сохранить' : 'Создать' }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- View Modal (без изменений) -->
        <!-- Task Modal (без изменений) -->
        <!-- More Modal (без изменений) -->

    </AuthenticatedLayout>
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

/* Эффект стекла */
.backdrop-blur-md {
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

/* Ограничение текста */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Стили для FullCalendar (переопределение) */
:deep(.fc) {
    --fc-border-color: #e2e8f0;
    --fc-button-text-color: #1e293b;
    --fc-button-bg-color: #f8fafc;
    --fc-button-border-color: #e2e8f0;
    --fc-button-hover-bg-color: #f1f5f9;
    --fc-button-hover-border-color: #cbd5e1;
    --fc-button-active-bg-color: #e2e8f0;
    --fc-event-bg-color: #2563eb;
    --fc-event-border-color: #2563eb;
    --fc-event-text-color: #ffffff;
    --fc-page-bg-color: transparent;
}

.dark :deep(.fc) {
    --fc-border-color: #334155;
    --fc-button-text-color: #e2e8f0;
    --fc-button-bg-color: #1e293b;
    --fc-button-border-color: #334155;
    --fc-button-hover-bg-color: #0f172a;
    --fc-button-hover-border-color: #475569;
    --fc-button-active-bg-color: #0f172a;
}
</style>
