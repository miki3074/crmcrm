<script setup>
import { ref, onMounted, computed } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

import { Calendar } from '@fullcalendar/core'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'
import axios from 'axios'

import ruLocale from '@fullcalendar/core/locales/ru'

const { props } = usePage()
const auth = props.auth

const calendarEl = ref(null)
let calendar

const events = ref([])

// модалка создания
const showModal = ref(false)
const creatingDate = ref(null) // дата кликнутой клетки
const companies = ref([])
const companyEmployees = ref([])
const selectedEvent = ref(null)
const showViewModal = ref(false)

const form = ref({
  title: '',
  description: '',
  visibility: 'personal', // personal | company_selected | company_all
  company_id: '',
  attendees: [],
  start_at: '',
  end_at: ''
})

const isCompanyEvent = computed(() => form.value.visibility !== 'personal')
const isSelectedCompany = computed(() => form.value.visibility === 'company_selected')

// цвета событий: личные/компании
const eventColor = (ev) => {
  if (ev.visibility === 'personal') return '#3b82f6' // синий
  return '#a21caf' // фиолетовый
}


const onEventClick = (info) => {
  // ГЛУБОКАЯ копия, чтобы отвязаться от внутренних прокси и readonly
  const base = JSON.parse(JSON.stringify(info.event.extendedProps || {}))

  selectedEvent.value = {
    ...base,
    // приведём к ISO-строке для удобства отображения/форматирования
    start_at: info.event.start ? info.event.start.toISOString() : null,
    end_at: info.event.end ? info.event.end.toISOString() : null,
    title: info.event.title ?? base.title ?? ''
  }

  showViewModal.value = true
}


const editEvent = (event) => {
  // Открываем модалку создания, но с уже заполненной формой
  form.value = {
    title: event.title,
    description: event.description,
    visibility: event.visibility,
    company_id: event.company_id || '',
    attendees: event.attendees?.map(a => a.id) || [],
    start_at: event.start_at.slice(0, 16), // для datetime-local
    end_at: event.end_at.slice(0, 16)
  }
  showModal.value = true
  showViewModal.value = false
}

const deleteEvent = async (id) => {
  if (!confirm('Удалить событие?')) return
  await axios.delete(`/api/calendar/events/${id}`)
  showViewModal.value = false
  const view = calendar.view
  await fetchEvents(view.currentStart.toISOString(), view.currentEnd.toISOString())
}



const loadCompanies = async () => {
  const { data } = await axios.get('/api/companies') // твой список компаний
  companies.value = data
}

const loadCompanyEmployees = async () => {
  if (!form.value.company_id) { companyEmployees.value = []; return }
  const { data } = await axios.get(`/api/companies/${form.value.company_id}/employees`)
  companyEmployees.value = data
}

const fetchEvents = async (start, end) => {
  const { data } = await axios.get('/api/calendar/events', { params: { start, end }})
  // FullCalendar ждёт [{ title, start, end, ... }]
  events.value = data.map(e => ({
    id: e.id,
    title: e.title || 'Событие',
    start: e.start_at,
    end: e.end_at,
    extendedProps: e,
    backgroundColor: eventColor(e),
    borderColor: eventColor(e),
  }))
  calendar?.removeAllEvents()
  calendar?.addEventSource(events.value)
}

const openCreateModal = (dateStr) => {
  creatingDate.value = dateStr
  const startISO = new Date(dateStr).toISOString().slice(0,16)
  form.value = {
    title: '',
    description: '',
    visibility: 'personal',
    company_id: '',
    attendees: [],
    start_at: startISO,
    end_at: startISO,
  }
  showModal.value = true
}

const submitEvent = async () => {
  const payload = { ...form.value }
  // ISO местное -> ISO с секундами
  if (payload.start_at && payload.start_at.length === 16) payload.start_at += ':00'
  if (payload.end_at && payload.end_at.length === 16) payload.end_at += ':00'

  await axios.post('/api/calendar/events', payload)
  showModal.value = false
  // перезагружаем текущий диапазон
  const view = calendar.view
  await fetchEvents(view.currentStart.toISOString(), view.currentEnd.toISOString())
}

// кастомная реакция на "+ ещё N": откроем модалку со списком
const showMoreModal = ref(false)
const moreDay = ref({ date: null, items: [] })

const onMoreLinkClick = (arg) => {
  // arg.date: Date; arg.allSegs: массив сегментов, вытащим события
  moreDay.value = {
    date: arg.date,
    items: arg.allSegs.map(seg => seg.event)
  }
  showMoreModal.value = true
  return 'none' // отменяем стандартный popover FullCalendar
}

onMounted(async () => {
  await loadCompanies()

 calendar = new Calendar(calendarEl.value, {
  plugins: [dayGridPlugin, interactionPlugin],
  initialView: 'dayGridMonth',
  locale: ruLocale, 
  height: 'auto',
  selectable: true,
  dayMaxEventRows: true,
  moreLinkClick: onMoreLinkClick,
  headerToolbar: {
    left: 'prev,next today',
    center: 'title',
    right: 'dayGridMonth,dayGridWeek,dayGridDay'
  },
  dateClick: (info) => openCreateModal(info.dateStr),
  datesSet: async (info) => {
    await fetchEvents(info.startStr, info.endStr)
  },
  eventClick: onEventClick,     // ← только один обработчик
  eventDidMount: (info) => { info.el.style.cursor = 'pointer' }
})

  calendar.render()
})
</script>

<template>
  <Head title="Календарь" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Календарь событий</h2>
    </template>

    <div class="max-w-7xl mx-auto py-6 px-4">
      <div ref="calendarEl"></div>
    </div>

    <!-- Модалка создания события -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 w-full max-w-xl">
        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Новое событие</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm mb-1">Событие</label>
            <input v-model="form.title" class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-white" />
          </div>
          <div>
            <label class="block text-sm mb-1">Тип</label>
            <select v-model="form.visibility" class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-white"
                    @change="() => { if (!isCompanyEvent) { form.company_id=''; form.attendees=[] } }">
              <option value="personal">Личный календарь</option>
              <option value="company_selected">Для компании (выбор сотрудников)</option>
              <option value="company_all">Для компании (всем)</option>
            </select>
          </div>

          <div v-if="isCompanyEvent">
            <label class="block text-sm mb-1">Компания</label>
            <select v-model="form.company_id" class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-white"
                    @change="loadCompanyEmployees">
              <option disabled value="">Выберите компанию</option>
              <option v-for="c in companies" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>

          <div v-if="isSelectedCompany">
            <label class="block text-sm mb-1">Участники (сотрудники компании)</label>
            <select v-model="form.attendees" multiple
                    class="w-full border rounded px-3 py-2 h-28 dark:bg-gray-700 dark:text-white">
              <option v-for="u in companyEmployees" :key="u.id" :value="u.id">{{ u.name }}</option>
            </select>
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm mb-1">Описание</label>
            <textarea v-model="form.description" rows="3"
                      class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-white"></textarea>
          </div>

          <div>
            <label class="block text-sm mb-1">Начало</label>
            <input type="datetime-local" v-model="form.start_at"
                   class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-white" />
          </div>
          <div>
            <label class="block text-sm mb-1">Окончание</label>
            <input type="datetime-local" v-model="form.end_at"
                   class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-white" />
          </div>
        </div>

        <div class="mt-6 flex justify-end gap-2">
          <button @click="showModal=false" class="px-4 py-2 rounded bg-gray-500 text-white">Отмена</button>
          <button @click="submitEvent" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">
            Создать
          </button>
        </div>
      </div>
    </div>


<!-- Модалка просмотра события -->
<div v-if="showViewModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 w-full max-w-lg">
    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">
      {{ selectedEvent?.title }}
    </h3>

    <p class="text-sm text-gray-700 dark:text-gray-300 mb-2">
      <strong>Описание:</strong> {{ selectedEvent?.description || '—' }}
    </p>

    <p class="text-sm text-gray-700 dark:text-gray-300 mb-2">
      <strong>Начало:</strong> {{ new Date(selectedEvent?.start_at).toLocaleString('ru-RU') }}
    </p>

    <p class="text-sm text-gray-700 dark:text-gray-300 mb-2">
      <strong>Окончание:</strong> {{ new Date(selectedEvent?.end_at).toLocaleString('ru-RU') }}
    </p>

    <p class="text-sm text-gray-700 dark:text-gray-300 mb-2">
      <strong>Тип:</strong>
      {{ selectedEvent?.visibility === 'personal' ? 'Личный' : 'Компания' }}
    </p>

<div class="flex justify-end gap-2">
      
      <button @click="editEvent(selectedEvent)" class="px-4 py-2 rounded bg-yellow-500 text-white">Редактировать</button>
      <button @click="deleteEvent(selectedEvent.id)" class="px-4 py-2 rounded bg-red-600 text-white">Удалить</button>
    </div>


    <div class="mt-6 flex justify-end gap-2">
      <button @click="showViewModal=false" class="px-4 py-2 rounded bg-gray-500 text-white">Закрыть</button>
    </div>



  </div>
</div>



    <!-- Модалка «ещё N» -->
    <div v-if="showMoreModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">
          События на {{ moreDay.date?.toLocaleDateString('ru-RU') }}
        </h3>
        <ul class="space-y-2 max-h-80 overflow-auto">
          <li v-for="ev in moreDay.items" :key="ev.id"
              class="border rounded p-2"
              :style="{borderColor: ev.backgroundColor}">
            <div class="font-medium" :style="{color: ev.backgroundColor}">{{ ev.title }}</div>
            <div class="text-xs text-gray-600 dark:text-gray-300">
              {{ new Date(ev.start).toLocaleString('ru-RU') }} — {{ new Date(ev.end).toLocaleString('ru-RU') }}
            </div>
          </li>
        </ul>
        <div class="mt-4 text-right">
          <button @click="showMoreModal=false" class="px-4 py-2 rounded bg-blue-600 text-white">Ок</button>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
