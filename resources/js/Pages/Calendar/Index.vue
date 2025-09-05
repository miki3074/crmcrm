<script setup>
import { ref, onMounted, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

import { Calendar } from '@fullcalendar/core'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'
import ruLocale from '@fullcalendar/core/locales/ru'
import axios from 'axios'

// если ещё не глобально:
axios.defaults.withCredentials = true

const calendarEl = ref(null)
let calendar = null

const loading = ref(false)
const events = ref([])

// UI state
const showCreateModal = ref(false)
const showViewModal = ref(false)
const showMoreModal = ref(false)

const creatingRange = ref({ start: null, end: null })
const moreDay = ref({ date: null, items: [] })
const companies = ref([])
const companyEmployees = ref([])

const selectedEvent = ref(null)
const editing = ref(false)

// форма события
const form = ref({
  id: null,
  title: '',
  description: '',
  visibility: 'personal',      // personal | company_selected | company_all
  company_id: '',
  attendees: [],
  start_at: '',
  end_at: '',
})

const isCompanyEvent = computed(() => form.value.visibility !== 'personal')
const isSelectedCompany = computed(() => form.value.visibility === 'company_selected')

const colorFor = (ev) => (ev.visibility === 'personal' ? '#2563eb' : '#7c3aed') // blue / violet
const badgeFor = (vis) =>
  vis === 'personal'
    ? 'bg-blue-100 text-blue-700 ring-1 ring-blue-200'
    : vis === 'company_selected'
    ? 'bg-violet-100 text-violet-700 ring-1 ring-violet-200'
    : 'bg-fuchsia-100 text-fuchsia-700 ring-1 ring-fuchsia-200'

// API helpers
const fetchEvents = async (start, end) => {
  loading.value = true
  try {
    const { data } = await axios.get('/api/calendar/events', { params: { start, end } })
    const mapped = data.map(e => ({
      id: e.id,
      title: e.title || 'Событие',
      start: e.start_at,
      end: e.end_at,
      allDay: false,
      backgroundColor: colorFor(e),
      borderColor: colorFor(e),
      extendedProps: e,
    }))
    events.value = mapped
    calendar?.removeAllEvents()
    calendar?.addEventSource(mapped)
  } finally {
    loading.value = false
  }
}

const loadCompanies = async () => {
  const { data } = await axios.get('/api/companies')
  companies.value = data
}
const loadCompanyEmployees = async () => {
  if (!form.value.company_id) { companyEmployees.value = []; return }
  const { data } = await axios.get(`/api/companies/${form.value.company_id}/employees`)
  companyEmployees.value = data
}

// Create / Edit
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
  showCreateModal.value = true
}

const submitEvent = async () => {
  const payload = { ...form.value }
  if (payload.start_at?.length === 16) payload.start_at += ':00'
  if (payload.end_at?.length === 16) payload.end_at += ':00'

  if (editing.value && payload.id) {
    await axios.patch(`/api/calendar/events/${payload.id}`, payload)
  } else {
    await axios.post('/api/calendar/events', payload)
  }

  showCreateModal.value = false
  const view = calendar.view
  await fetchEvents(view.currentStart.toISOString(), view.currentEnd.toISOString())
}

const onEventClick = (info) => {
  const base = JSON.parse(JSON.stringify(info.event.extendedProps || {}))
  selectedEvent.value = {
    ...base,
    id: info.event.id,
    title: info.event.title || base.title || '',
    start_at: info.event.start ? info.event.start.toISOString() : null,
    end_at: info.event.end ? info.event.end.toISOString() : null,
  }
  showViewModal.value = true
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
}

// DnD updates
const patchEventDates = async ({ event }) => {
  const payload = {
    title: event.title,
    start_at: event.start?.toISOString(),
    end_at: (event.end ?? event.start)?.toISOString(),
    // extended props can be kept as-is on backend
  }
  await axios.patch(`/api/calendar/events/${event.id}`, payload)
}

// “+ ещё N”
const onMoreLinkClick = (arg) => {
  moreDay.value = {
    date: arg.date,
    items: arg.allSegs.map(seg => seg.event),
  }
  showMoreModal.value = true
  return 'none'
}

onMounted(async () => {
  await loadCompanies()

  calendar = new Calendar(calendarEl.value, {
    plugins: [dayGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    height: 'auto',
    locale: ruLocale,
    selectable: true,
    selectMirror: true,
    editable: true,         // drag & drop + resize
    dayMaxEventRows: true,
    moreLinkClick: onMoreLinkClick,
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,dayGridWeek,dayGridDay',
    },
    // выбор диапазона мышью
    select: (info) => {
      creatingRange.value = { start: info.startStr, end: info.endStr }
      openCreateModal(info.startStr, info.endStr)
    },
    // клик по дню — моментальное создание на точное время (08:00)
    dateClick: (info) => {
      const dt = new Date(info.dateStr)
      dt.setHours(8,0,0,0)
      openCreateModal(dt.toISOString(), dt.toISOString())
    },
    datesSet: async (info) => {
      await fetchEvents(info.startStr, info.endStr)
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
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Календарь событий</h2>
        <button
          class="rounded-xl bg-indigo-600 text-white px-4 py-2 hover:bg-indigo-700"
          @click="openCreateModal()"
        >
          + Событие
        </button>
      </div>
    </template>

    <div class="max-w-7xl mx-auto py-6 px-4">
      <div v-if="loading" class="mb-3 text-sm text-gray-500">Загрузка…</div>
      <div ref="calendarEl"></div>
    </div>

    <!-- Create/Edit modal -->
    <div v-if="showCreateModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-6 w-full max-w-2xl">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            {{ editing ? 'Редактировать событие' : 'Новое событие' }}
          </h3>
          <button @click="showCreateModal=false" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
          <div>
            <label class="block text-sm mb-1">Название</label>
            <input v-model="form.title" class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-white" />
          </div>
          <div>
            <label class="block text-sm mb-1">Тип</label>
            <select v-model="form.visibility" class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-white"
                    @change="() => { if (!isCompanyEvent) { form.company_id=''; form.attendees=[] } }">
              <option value="personal">Личный календарь</option>
              <option value="company_selected">Компания (выбор сотрудников)</option>
              <option value="company_all">Компания (всем)</option>
            </select>
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm mb-1">Описание</label>
            <textarea v-model="form.description" rows="3" class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-white"/>
          </div>

          <div>
            <label class="block text-sm mb-1">Начало</label>
            <input type="datetime-local" v-model="form.start_at" class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-white" />
          </div>
          <div>
            <label class="block text-sm mb-1">Окончание</label>
            <input type="datetime-local" v-model="form.end_at" class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-white" />
          </div>

          <div v-if="isCompanyEvent">
            <label class="block text-sm mb-1">Компания</label>
            <select v-model="form.company_id" class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-white" @change="loadCompanyEmployees">
              <option disabled value="">Выберите компанию</option>
              <option v-for="c in companies" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>

          <div v-if="isSelectedCompany">
            <label class="block text-sm mb-1">Участники</label>
            <select v-model="form.attendees" multiple class="w-full border rounded px-3 py-2 h-32 dark:bg-gray-700 dark:text-white">
              <option v-for="u in companyEmployees" :key="u.id" :value="u.id">{{ u.name }}</option>
            </select>
          </div>
        </div>

        <div class="mt-6 flex justify-end gap-2">
          <button @click="showCreateModal=false" class="px-4 py-2 rounded bg-gray-500 text-white">Отмена</button>
          <button @click="submitEvent" class="px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700">
            {{ editing ? 'Сохранить' : 'Создать' }}
          </button>
        </div>
      </div>
    </div>

    <!-- View modal -->
    <div v-if="showViewModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-6 w-full max-w-lg">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ selectedEvent?.title }}</h3>
          <button @click="showViewModal=false" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>

        <div class="mt-3 space-y-2 text-sm text-gray-700 dark:text-gray-300">
          <div>
            <span class="text-gray-500">Тип:</span>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs ml-2"
                  :class="badgeFor(selectedEvent?.visibility)">
              {{ selectedEvent?.visibility === 'personal' ? 'Личный' : selectedEvent?.visibility === 'company_selected' ? 'Компания (выбор)' : 'Компания (всем)' }}
            </span>
          </div>
          <div><span class="text-gray-500">Начало:</span> {{ new Date(selectedEvent?.start_at).toLocaleString('ru-RU') }}</div>
          <div><span class="text-gray-500">Окончание:</span> {{ new Date(selectedEvent?.end_at).toLocaleString('ru-RU') }}</div>
          <div><span class="text-gray-500">Описание:</span> {{ selectedEvent?.description || '—' }}</div>
        </div>

        <div class="mt-6 flex justify-between">
          <button @click="editEvent" class="px-4 py-2 rounded bg-amber-600 text-white hover:bg-amber-700">Редактировать</button>
          <div class="space-x-2">
            <button @click="deleteEvent" class="px-4 py-2 rounded bg-rose-600 text-white hover:bg-rose-700">Удалить</button>
            <button @click="showViewModal=false" class="px-4 py-2 rounded bg-gray-500 text-white">Закрыть</button>
          </div>
        </div>
      </div>
    </div>

    <!-- “Ещё N” modal -->
    <div v-if="showMoreModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          События на {{ moreDay.date?.toLocaleDateString('ru-RU') }}
        </h3>
        <ul class="mt-4 space-y-2 max-h-80 overflow-auto">
          <li v-for="ev in moreDay.items" :key="ev.id" class="rounded border p-2"
              :style="{ borderColor: ev.backgroundColor }">
            <div class="font-medium" :style="{ color: ev.backgroundColor }">{{ ev.title }}</div>
            <div class="text-xs text-gray-500">
              {{ new Date(ev.start).toLocaleString('ru-RU') }} — {{ new Date(ev.end ?? ev.start).toLocaleString('ru-RU') }}
            </div>
          </li>
        </ul>
        <div class="mt-4 text-right">
          <button @click="showMoreModal=false" class="px-4 py-2 rounded bg-indigo-600 text-white">Ок</button>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
