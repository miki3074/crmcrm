<script setup>
import { ref, onMounted, computed } from 'vue'
import {Head, router, usePage} from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import axios from 'axios'

const { props } = usePage()

// роли пользователя
const roles = computed(() => props.auth?.roles ?? [])
const isAdmin = computed(() => roles.value.includes('admin'))

// данные компаний
const companies = ref([])
const filtered = ref([])
const loading = ref(true)
const err = ref('')
const q = ref('')

// modal создания компании
const showModal = ref(false)
const form = ref({ name: '', logo: null })
const submitting = ref(false)

const onFileChange = (e) => (form.value.logo = e.target.files?.[0] ?? null)

// текущий пользователь
const userId = computed(() => props.auth?.user?.id)

// фильтр компаний
const filterList = () => {
  const term = q.value.trim().toLowerCase()
  filtered.value = term
    ? companies.value.filter(c => (c.name || '').toLowerCase().includes(term))
    : companies.value
}

// мои и чужие компании
const myCompanies = computed(() =>
  filtered.value.filter(c => String(c.user_id) === String(userId.value))
)
const otherCompanies = computed(() =>
  filtered.value.filter(c => String(c.user_id) !== String(userId.value))
)

// fetch компаний
const fetchCompanies = async () => {
  loading.value = true
  err.value = ''
  try {
    await axios.get('/sanctum/csrf-cookie')
    const { data } = await axios.get('/api/companies', { withCredentials: true })
    companies.value = data
    filtered.value = data
  } catch (e) {
    err.value = e.response?.data?.message || 'Не удалось загрузить компании'
  } finally {
    loading.value = false
  }
}

// создание компании
const createCompany = async () => {
  if (!form.value.name.trim()) return
  submitting.value = true
  try {
    await axios.get('/sanctum/csrf-cookie')
    const payload = new FormData()
    payload.append('name', form.value.name)
    if (form.value.logo) payload.append('logo', form.value.logo)

    await axios.post('/api/companies', payload, {
      headers: { 'Content-Type': 'multipart/form-data' },
      withCredentials: true,
    })

    showModal.value = false
    form.value = { name: '', logo: null }
    await fetchCompanies()
  } catch (e) {
    alert(e.response?.data?.message || 'Ошибка при создании компании')
  } finally {
    submitting.value = false
  }
}

// summary для проектов и задач
const summary = ref({
  managing_projects: [],
  my_tasks: [],
  my_subtasks: [],
  due_today: [],
  overdue: [],
})
const loadingSummary = ref(true)

const fetchSummary = async () => {
  loadingSummary.value = true
  try {
    await axios.get('/sanctum/csrf-cookie')
    const { data } = await axios.get('/api/dashboard/summary', { withCredentials: true })
    summary.value = data
  } catch (e) {
    console.error('summary error', e.response?.data ?? e.message)
  } finally {
    loadingSummary.value = false
  }
}

// группировка проектов и задач по компаниям
const managingByCompany = computed(() => {
  return summary.value.managing_projects.reduce((acc, p) => {
    const companyName = p.company?.name || 'Без компании'
    if (!acc[companyName]) acc[companyName] = []
    acc[companyName].push(p)
    return acc
  }, {})
})

const allTasksByCompanyAndProject = computed(() => {
  return (summary.value.all_tasks || []).reduce((acc, t) => {
    const companyName = t.project?.company?.name || 'Без компании'
    const projectName = t.project?.name || 'Без проекта'

    if (!acc[companyName]) acc[companyName] = {}
    if (!acc[companyName][projectName]) acc[companyName][projectName] = []

    if (!acc[companyName][projectName].some(task => task.id === t.id)) {
      acc[companyName][projectName].push(t)
    }

    return acc
  }, {})
})


// приоритет для задач
const prioBadge = (p) => ({
  low:    'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
  medium: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
  high:   'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
}[p] || 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300')




const allSubtasksByCompany = computed(() => {
  const data = summary.value.all_subtasks

  // если это уже объект — просто возвращаем
  if (data && typeof data === 'object' && !Array.isArray(data)) {
    return data
  }

  // если это массив — собираем вручную
  if (Array.isArray(data)) {
    return data.reduce((acc, st) => {
      const companyName = st.task?.project?.company?.name || 'Без компании'
      const projectName = st.task?.project?.name || 'Без проекта'

      if (!acc[companyName]) acc[companyName] = {}
      if (!acc[companyName][projectName]) acc[companyName][projectName] = []

      if (!acc[companyName][projectName].some(s => s.id === st.id)) {
        acc[companyName][projectName].push(st)
      }

      return acc
    }, {})
  }

  return {}
})













const telegramId = ref(props.auth?.user?.telegram_chat_id ?? null)

const showInput = ref(false)
const chatId = ref('')
const saving = ref(false)

const saveChatId = async () => {
  if (!chatId.value.trim()) return alert('Введите Chat ID')
  try {
    saving.value = true
    await axios.get('/sanctum/csrf-cookie')
    const { data } = await axios.post('/api/user/save-chat-id', {
      chat_id: chatId.value,
    }, { withCredentials: true })

    // обновляем локальное состояние
    telegramId.value = data.chat_id

    alert(data.message)
    showInput.value = false
  } catch (e) {
    alert(e.response?.data?.message || 'Ошибка при сохранении')
  } finally {
    saving.value = false
  }
}


const deleteCompany = async (companyId) => {
  if (!confirm('Вы уверены, что хотите удалить эту компанию со всеми проектами и задачами?')) return

  try {
    await axios.delete(`/api/companies/${companyId}`, { withCredentials: true })
    alert('Компания успешно удалена.')
    await fetchCompanies() // перезагружаем список компаний
  } catch (e) {
    alert(e?.response?.data?.message || 'Ошибка при удалении компании')
  }
}

const showConfirmModal = ref(false)
const deletePassword = ref('')
const deleteCompanyId = ref(null)
const deleteError = ref('')

// открываем модалку
const confirmDelete = (id) => {
  deleteCompanyId.value = id
  deletePassword.value = ''
  deleteError.value = ''
  showConfirmModal.value = true
}

// подтверждение удаления
const deleteCompanyConfirm = async () => {
  if (!deletePassword.value.trim()) {
    deleteError.value = 'Введите пароль.'
    return
  }

  try {
    await axios.delete(`/api/companies/${deleteCompanyId.value}`, {
      data: { password: deletePassword.value }, // передаём пароль в тело запроса
      withCredentials: true,
    })

    alert('Компания успешно удалена.')
    showConfirmModal.value = false
    await fetchCompanies()
  } catch (e) {
    if (e.response?.status === 403) {
      deleteError.value = e.response?.data?.message || 'Неверный пароль.'
    } else {
      deleteError.value = e.response?.data?.message || 'Ошибка при удалении.'
    }
  }
}

const showAllMyCompanies = ref(false)
const showAllOtherCompanies = ref(false)

// поля поиска
const searchMyCompany = ref('')
const searchOtherCompany = ref('')

// фильтры
const filteredMyCompanies = computed(() => {
  if (!searchMyCompany.value.trim()) return myCompanies.value || []
  const q = searchMyCompany.value.toLowerCase()
  return (myCompanies.value || []).filter(c => c.name.toLowerCase().includes(q))
})

const filteredOtherCompanies = computed(() => {
  if (!searchOtherCompany.value.trim()) return otherCompanies.value || []
  const q = searchOtherCompany.value.toLowerCase()
  return (otherCompanies.value || []).filter(c => c.name.toLowerCase().includes(q))
})


const showAllProjectsModal = ref(false)
const selectedCompanyName = ref('')
const selectedProjects = ref([])
const projectSearch = ref('')

const openShowAllProjects = (companyName, projects) => {
  selectedCompanyName.value = companyName
  selectedProjects.value = projects
  showAllProjectsModal.value = true
  projectSearch.value = ''
}

const filteredProjects = computed(() => {
  if (!projectSearch.value.trim()) return selectedProjects.value
  const q = projectSearch.value.toLowerCase()
  return selectedProjects.value.filter(p => p.name.toLowerCase().includes(q))
})


// ===== Глобальный поиск =====
const showSearchModal = ref(false)

const getSubtasksArray = (data) => {
  if (Array.isArray(data)) return data
  if (data && typeof data === 'object') {
    const arr = []
    Object.values(data).forEach(projects =>
      Object.values(projects).forEach(tasks =>
        Object.values(tasks).forEach(subs => arr.push(...subs))
      )
    )
    return arr
  }
  return []
}

const globalResults = computed(() => {
  const query = q.value.trim().toLowerCase()
  if (!query) {
    return { companies: [], projects: [], tasks: [], subtasks: [] }
  }

  return {
    companies: companies.value.filter(c =>
      (c.name || '').toLowerCase().includes(query)
    ),
    projects: (summary.value.managing_projects || [])
      .filter(p => p.name?.toLowerCase().includes(query)),
    tasks: (summary.value.all_tasks || [])
      .filter(t => t.title?.toLowerCase().includes(query)),
    subtasks: getSubtasksArray(summary.value.all_subtasks)
      .filter(s => s.title?.toLowerCase().includes(query)),
  }
})


const hasResults = computed(() =>
  Object.values(globalResults.value).some(arr => arr.length > 0)
)

const onGlobalSearch = () => {
  if (q.value.trim().length >= 2) {
    showSearchModal.value = true
  } else {
    showSearchModal.value = false
  }
}

const closeSearchModal = () => {
  showSearchModal.value = false
}



// ====== Модалки и поиски ======
const showAllTasksModal = ref(false)
const showAllSubtasksModal = ref(false)
const selectedCompanyTasks = ref('')
const selectedCompanySubtasks = ref('')
const selectedTasks = ref([])
const selectedSubtasks = ref([])
const searchTasks = ref('')
const searchSubtasks = ref('')

// ====== Методы ======
const openAllTasks = (company, tasks) => {
  selectedCompanyTasks.value = company
  selectedTasks.value = tasks
  showAllTasksModal.value = true
  searchTasks.value = ''
}


// ====== Фильтрация ======
const filteredTasks = computed(() => {
  if (!searchTasks.value.trim()) return selectedTasks.value
  const q = searchTasks.value.toLowerCase()
  return selectedTasks.value.filter(t => t.title.toLowerCase().includes(q))
})




const showProjectTasksModal = ref(false)


const selectedProjectTasks = ref([])
const projectTasksSearch = ref('')

const openProjectTasks = (companyName, projectName, tasks) => {
  selectedCompanyName.value = companyName
  selectedProjectName.value = projectName
  selectedProjectTasks.value = tasks
  showProjectTasksModal.value = true
  projectTasksSearch.value = ''
}

const filteredProjectTasks = computed(() => {
  if (!projectTasksSearch.value.trim()) return selectedProjectTasks.value
  const q = projectTasksSearch.value.toLowerCase()
  return selectedProjectTasks.value.filter(t => t.title.toLowerCase().includes(q))
})


const selectedProjectName = ref('')
const selectedTaskTitle = ref('')


const openAllSubtasks = (company, project, task, subtasks) => {
  selectedCompanyName.value = company
  selectedProjectName.value = project
  selectedTaskTitle.value = task
  selectedSubtasks.value = subtasks
  showAllSubtasksModal.value = true
  searchSubtasks.value = ''
}

const filteredSubtasks = computed(() => {
  if (!searchSubtasks.value.trim()) return selectedSubtasks.value
  const q = searchSubtasks.value.toLowerCase()
  return selectedSubtasks.value.filter(st => st.title.toLowerCase().includes(q))
})

// onMounted
onMounted(async () => {
  await Promise.all([fetchCompanies(), fetchSummary()])
})
</script>


<template>




  <Head title="Планшет" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-200">Панель управления </h2>
        <div class="flex items-center gap-2">
          <span v-for="r in roles" :key="r"
            class="text-xs px-2 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
            {{ r }}
          </span>
        </div>
      </div>
    </template>

<div class="flex justify-end mt-6">
  <div class="flex items-center gap-3">
    <!-- Если Telegram уже привязан -->
    <template v-if="telegramId && !showInput">
      <div class="flex items-center gap-2 text-xs text-green-600">
        <span>✅ Telegram привязан</span>
        <span class="text-slate-500">ID: {{ telegramId }}</span>
      </div>
      <button
        @click="showInput = true"
        class="rounded bg-slate-900 text-white px-3 py-1 text-xs hover:bg-slate-700">
        Обновить
      </button>
    </template>

    <!-- Если Telegram не привязан -->
    <template v-else-if="!telegramId && !showInput">
      <a
        href="https://t.me/UserInfeBot"
        target="_blank"
        class="rounded bg-sky-600 text-white px-3 py-1 text-xs hover:bg-sky-700">
        Привязать Telegram
      </a>
      <button
        @click="showInput = true"
        class="rounded bg-slate-900 text-white px-3 py-1 text-xs hover:bg-slate-700">
        Вставить ID
      </button>

<a
        href="https://t.me/TrustCrmHelper_bot"
        target="_blank"
        class="rounded bg-sky-600 text-white px-3 py-1 text-xs hover:bg-sky-700">
        введите команду /start
      </a>


    </template>

    <div v-if="showInput" class="flex flex-col items-end gap-2">
      <a
        href="https://t.me/UserInfeBot"
        target="_blank"
        class="rounded bg-sky-600 text-white px-3 py-1 text-xs hover:bg-sky-700">
        Получить Chat ID
      </a>
      <div class="flex items-center gap-2">
        <input
          v-model="chatId"
          type="text"
          placeholder="Ваш Chat ID"
          class="border rounded px-2 py-1 text-xs"
        />
        <button
          @click="saveChatId"
          :disabled="saving"
          class="rounded bg-emerald-600 text-white px-3 py-1 text-xs hover:bg-emerald-700 disabled:opacity-50">
          {{ saving ? '...' : 'Сохранить' }}
        </button>
      </div>

      <a
        href="https://t.me/TrustCrmHelper_bot"
        target="_blank"
        class="rounded bg-sky-600 text-white px-3 py-1 text-xs hover:bg-sky-700">
        введите команду /start
      </a>
      <button
        @click="showInput = false"
        class="text-xs text-slate-500 hover:underline">
        Скрыть
      </button>
    </div>
  </div>
</div>

    <div class="max-w-7xl mx-auto px-4 py-8 space-y-8">

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <button
          class="group rounded-2xl border bg-white/80 dark:bg-slate-900/60 border-slate-200 dark:border-slate-800 px-5 py-4 text-left hover:shadow transition"
          @click="$inertia.visit('/calendar')">
          <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-xl bg-purple-500/10 ring-1 ring-purple-500/30 grid place-items-center">
              <span class="i">📅</span>
            </div>
            <div>
              <div class="font-semibold text-slate-500" >Календарь</div>
              <div class="text-xs text-slate-500">События и встречи</div>
            </div>
          </div>
        </button>


        <button
          class="group rounded-2xl border bg-white/80 dark:bg-slate-900/60 border-slate-200 dark:border-slate-800 px-5 py-4 text-left hover:shadow transition"
          @click="$inertia.visit('/file-storage')">
          <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-xl bg-purple-500/10 ring-1 ring-purple-500/30 grid place-items-center">
              <span class="i">📂</span>
            </div>
            <div>
              <div class="font-semibold text-slate-500">Хранилище</div>
              <div class="text-xs text-slate-500">файлы</div>
            </div>
          </div>
        </button>

        <button
         v-if="isAdmin"
          class="group rounded-2xl border bg-white/80 dark:bg-slate-900/60 border-slate-200 dark:border-slate-800 px-5 py-4 text-left hover:shadow transition"
          @click="$inertia.visit('/employees')">
          <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-xl bg-indigo-500/10 ring-1 ring-indigo-500/30 grid place-items-center">
              <span class="i">👥</span>
            </div>
            <div>
              <div class="font-semibold text-slate-500">Сотрудники</div>
              <div class="text-xs text-slate-500">Роли и доступы</div>
            </div>
          </div>
        </button>

        <button
         v-if="isAdmin"
          class="group rounded-2xl border bg-white/80 dark:bg-slate-900/60 border-slate-200 dark:border-slate-800 px-5 py-4 text-left hover:shadow transition"
          @click="$inertia.visit('/clients')">
          <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-xl bg-indigo-500/10 ring-1 ring-indigo-500/30 grid place-items-center">
              <span class="i">👥</span>
            </div>
            <div>
              <div class="font-semibold text-slate-500">Клиенты</div>

            </div>
          </div>
        </button>

        <button
          v-if="isAdmin"
          class="group rounded-2xl border bg-white/80 dark:bg-slate-900/60 border-slate-200 dark:border-slate-800 px-5 py-4 text-left hover:shadow transition"
          @click="showModal = true">
          <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-xl bg-emerald-500/10 ring-1 ring-emerald-500/30 grid place-items-center">
              <span class="i">➕</span>
            </div>
            <div>
              <div class="font-semibold  text-slate-500" >Новая компания</div>
              <div class="text-xs text-slate-500">Создать организацию</div>
            </div>
          </div>
        </button>

<button
  class="group rounded-2xl border bg-white/80 dark:bg-slate-900/60 border-slate-200 dark:border-slate-800 px-5 py-4 text-left hover:shadow transition"
  @click="$inertia.visit('/mapdiagram')"
>
  <div class="flex items-center gap-3">
    <div class="h-10 w-10 rounded-xl bg-blue-500/10 ring-1 ring-blue-500/30 grid place-items-center">
      <span class="i">🗺️</span>
    </div>
    <div>
      <div class="font-semibold text-slate-700 ">
        Визуальная схема
      </div>
      <div class="text-xs text-slate-500 ">

      </div>
    </div>
  </div>
</button>


      </div>


      <div class="flex items-center gap-3">
        <div class="relative flex-1">
  <input
    v-model="q"
    @input="onGlobalSearch"
    type="text"
    placeholder="Поиск по компаниям, проектам, задачам…"
    class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/60 px-4 py-2.5 text-sm outline-none focus:border-slate-300 dark:focus:border-slate-700 text-slate-800 dark:text-slate-100"
  />
  <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">⌘K</span>
</div>

        <button
          v-if="isAdmin"
          @click="showModal = true"
          class="hidden md:inline-flex items-center gap-2 rounded-xl bg-slate-900 text-white dark:bg-white dark:text-slate-900 px-4 py-2.5 text-sm font-semibold hover:opacity-90">
          Создать
        </button>
      </div>

      <!-- Скелетоны / Ошибка -->
      <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="i in 6" :key="i" class="h-28 rounded-2xl bg-slate-100 dark:bg-slate-800 animate-pulse"></div>
      </div>

      <div v-else-if="err" class="rounded-xl border border-red-200 bg-red-50 dark:border-red-900/50 dark:bg-red-900/20 p-4 text-sm text-red-700 dark:text-red-300">
        {{ err }}
      </div>

      <!-- Компании -->
  <div v-else class="flex flex-col lg:flex-row gap-6 mt-6">

  <!-- 🏢 Мои компании -->
  <div class="flex-1 bg-white/80 dark:bg-slate-900/60 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-sm">
    <div class="flex items-center justify-between mb-3">
      <h3 class="text-lg font-semibold text-slate-600 dark:text-slate-300 flex items-center gap-2">
        💼 Мои компании
      </h3>
      <button
        v-if="myCompanies.length > 6"
        @click="showAllMyCompanies = true"
        class="text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 font-medium transition">
        Показать все
      </button>
    </div>

    <div
      v-if="myCompanies.length"
      class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4"
    >
      <div
        v-for="company in myCompanies.slice(0, 6)"
        :key="company.id"
        class="group rounded-2xl border border-slate-200 dark:border-slate-800 bg-white/90 dark:bg-slate-900/80 p-4 hover:shadow-md transition cursor-pointer relative"
        @click="$inertia.visit(`/companies2/${company.id}`)"
      >
        <button
          v-if="company.user_id === userId"
          @click.stop="confirmDelete(company.id)"
          class="absolute top-2 right-2 text-rose-500 hover:text-rose-700 text-sm">
          ✕
        </button>

        <div class="flex items-center gap-3">
          <img
            v-if="company.logo"
            :src="`/storage/${company.logo}`"
            alt=""
            class="h-12 w-12 object-cover rounded-xl ring-1 ring-slate-200 dark:ring-slate-800"
          />
          <div
            v-else
            class="h-12 w-12 rounded-xl bg-slate-100 dark:bg-slate-800 grid place-items-center text-slate-400">
            🏢
          </div>
          <div class="min-w-0">
            <div class="font-semibold truncate text-slate-700 dark:text-slate-100 break-words">
              {{ company.name }}
            </div>
            <div class="text-xs text-slate-500">Проектов: {{ company.projects?.length ?? '—' }}</div>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="text-center text-slate-500 py-16 border border-dashed rounded-2xl dark:border-slate-700">
      <div class="text-3xl mb-2">🏙️</div>
      <div>Пока нет компаний</div>
      <button
        v-if="isAdmin"
        class="mt-4 inline-flex items-center gap-2 rounded-xl bg-slate-900 text-white dark:bg-white dark:text-slate-900 px-4 py-2.5 text-sm font-semibold hover:opacity-90"
        @click="showModal = true">
        Добавить компанию
      </button>
    </div>
  </div>

  <!-- 🤝 Другие компании -->
  <div class="flex-[0.8] bg-white/80 dark:bg-slate-900/60 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-sm">
    <div class="flex items-center justify-between mb-3">
      <h3 class="text-lg font-semibold text-slate-600 dark:text-slate-300 flex items-center gap-2">
        🤝 Другие компании
      </h3>

      <!-- кнопка показать все -->
      <button
        v-if="otherCompanies.length > 4"
        @click="showAllOtherCompanies = true"
        class="text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 font-medium transition">
        Показать все
      </button>
    </div>

    <div
      v-if="otherCompanies.length"
      class="grid grid-cols-1 sm:grid-cols-2 gap-4"
    >
      <div
        v-for="company in otherCompanies.slice(0, 4)"
        :key="company.id"
        class="group rounded-2xl border border-slate-200 dark:border-slate-800 bg-white/90 dark:bg-slate-900/80 p-4 hover:shadow-md transition cursor-pointer"
        @click="$inertia.visit(`/companies2/${company.id}`)"
      >
        <div class="flex items-center gap-3">
          <img
            v-if="company.logo"
            :src="`/storage/${company.logo}`"
            alt=""
            class="h-12 w-12 object-cover rounded-xl ring-1 ring-slate-200 dark:ring-slate-800"
          />
          <div
            v-else
            class="h-12 w-12 rounded-xl bg-slate-100 dark:bg-slate-800 grid place-items-center text-slate-400">
            🏢
          </div>
          <div class="min-w-0">
            <div class="font-semibold truncate text-slate-700 dark:text-slate-100 break-words">
              {{ company.name }}
            </div>
            <div class="text-xs text-slate-500">Проектов: {{ company.projects?.length ?? '—' }}</div>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="text-center text-slate-500 py-16 border border-dashed rounded-2xl dark:border-slate-700">
      <div class="text-3xl mb-2">🏗️</div>
      <div>Нет других компаний</div>
    </div>
  </div>
</div>

<!-- 🪟 Модальное окно "Все мои компании" -->
<div
  v-if="showAllMyCompanies"
  class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-6 backdrop-blur-sm transition">
  <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 w-full max-w-5xl max-h-[85vh] overflow-y-auto border border-slate-200 dark:border-slate-700 shadow-2xl relative">

    <button
      @click="showAllMyCompanies = false"
      class="absolute top-3 right-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 text-xl">
      ✕
    </button>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
      <h3 class="text-xl font-semibold text-slate-700 dark:text-slate-100">
        💼 Все мои компании ({{ myCompanies.length }})
      </h3>

      <div class="relative w-full sm:w-72">
        <input
          v-model="searchMyCompany"
          type="text"
          placeholder="🔍 Поиск компании..."
          class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
        />
      </div>
    </div>

    <div
      v-if="filteredMyCompanies.length"
      class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
      <div
        v-for="company in filteredMyCompanies"
        :key="company.id"
        class="group rounded-2xl border border-slate-200 dark:border-slate-800 bg-white/90 dark:bg-slate-900/80 p-4 hover:shadow-md transition cursor-pointer"
        @click="$inertia.visit(`/companies/${company.id}`)">
        <div class="flex items-center gap-3">
          <img v-if="company.logo" :src="`/storage/${company.logo}`" alt="" class="h-12 w-12 object-cover rounded-xl ring-1 ring-slate-200 dark:ring-slate-800"/>
          <div v-else class="h-12 w-12 rounded-xl bg-slate-100 dark:bg-slate-800 grid place-items-center text-slate-400">🏢</div>
          <div class="min-w-0">
            <div class="font-semibold truncate text-slate-700 dark:text-slate-100 break-words">{{ company.name }}</div>
            <div class="text-xs text-slate-500">Проектов: {{ company.projects?.length ?? '—' }}</div>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="text-center py-12 text-slate-500">
      <div class="text-3xl mb-2">🔍</div>
      Компаний не найдено
    </div>
  </div>
</div>

<!-- 🪟 Модальное окно "Все другие компании" -->
<div
  v-if="showAllOtherCompanies"
  class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-6 backdrop-blur-sm transition">
  <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 w-full max-w-5xl max-h-[85vh] overflow-y-auto border border-slate-200 dark:border-slate-700 shadow-2xl relative">

    <button
      @click="showAllOtherCompanies = false"
      class="absolute top-3 right-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 text-xl">
      ✕
    </button>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
      <h3 class="text-xl font-semibold text-slate-700 dark:text-slate-100">
        🤝 Все другие компании ({{ otherCompanies.length }})
      </h3>

      <div class="relative w-full sm:w-72">
        <input
          v-model="searchOtherCompany"
          type="text"
          placeholder="🔍 Поиск компании..."
          class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
        />
      </div>
    </div>

    <div
      v-if="filteredOtherCompanies.length"
      class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
      <div
        v-for="company in filteredOtherCompanies"
        :key="company.id"
        class="group rounded-2xl border border-slate-200 dark:border-slate-800 bg-white/90 dark:bg-slate-900/80 p-4 hover:shadow-md transition cursor-pointer"
        @click="$inertia.visit(`/companies/${company.id}`)">
        <div class="flex items-center gap-3">
          <img v-if="company.logo" :src="`/storage/${company.logo}`" alt="" class="h-12 w-12 object-cover rounded-xl ring-1 ring-slate-200 dark:ring-slate-800"/>
          <div v-else class="h-12 w-12 rounded-xl bg-slate-100 dark:bg-slate-800 grid place-items-center text-slate-400">🏢</div>
          <div class="min-w-0">
            <div class="font-semibold truncate text-slate-700 dark:text-slate-100 break-words">{{ company.name }}</div>
            <div class="text-xs text-slate-500">Проектов: {{ company.projects?.length ?? '—' }}</div>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="text-center py-12 text-slate-500">
      <div class="text-3xl mb-2">🔍</div>
      Компаний не найдено
    </div>
  </div>
</div>


<!-- 🪟 Модальное окно "Все компании" -->
<div
  v-if="showAllMyCompanies"
  class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-6 backdrop-blur-sm transition">
  <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 w-full max-w-5xl max-h-[85vh] overflow-y-auto border border-slate-200 dark:border-slate-700 shadow-2xl relative">

    <!-- ✕ Кнопка закрытия -->
    <button
      @click="showAllMyCompanies = false"
      class="absolute top-3 right-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 text-xl">
      ✕
    </button>

    <!-- Заголовок -->
    <div class="flex flex-col sm:flex-row sm:items-center  gap-3 mb-4">
      <h3 class="text-xl font-semibold text-slate-700 dark:text-slate-100">
        💼 Все мои компании ({{ myCompanies.length }})
      </h3>

      <!-- 🔍 Поле поиска -->
      <div class="relative w-full sm:w-72">
        <input
          v-model="searchCompanyQuery"
          type="text"
          placeholder="🔍 Поиск компании..."
          class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 px-3 py-2 text-sm text-slate-700 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 outline-none"
        />
      </div>
    </div>

    <!-- Список компаний -->
    <div
      v-if="filteredMyCompanies.length"
      class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
      <div
        v-for="company in filteredMyCompanies"
        :key="company.id"
        class="group rounded-2xl border border-slate-200 dark:border-slate-800 bg-white/90 dark:bg-slate-900/80 p-4 hover:shadow-md transition cursor-pointer"
        @click="$inertia.visit(`/companies/${company.id}`)">
        <div class="flex items-center gap-3">
          <img
            v-if="company.logo"
            :src="`/storage/${company.logo}`"
            alt=""
            class="h-12 w-12 object-cover rounded-xl ring-1 ring-slate-200 dark:ring-slate-800"
          />
          <div
            v-else
            class="h-12 w-12 rounded-xl bg-slate-100 dark:bg-slate-800 grid place-items-center text-slate-400">
            🏢
          </div>
          <div class="min-w-0">
            <div class="font-semibold truncate text-slate-700 dark:text-slate-100 break-words">
              {{ company.name }}
            </div>
            <div class="text-xs text-slate-500">Проектов: {{ company.projects?.length ?? '—' }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Если ничего не найдено -->
    <div v-else class="text-center py-12 text-slate-500 dark:text-slate-400">
      <div class="text-3xl mb-2">🔍</div>
      <p>Компаний не найдено по запросу “{{ searchCompanyQuery }}”</p>
    </div>
  </div>
</div>

<!-- 🔍 Глобальный поиск -->
<div
  v-if="showSearchModal"
  class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-6 backdrop-blur-sm transition"
>
  <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 w-full max-w-4xl max-h-[85vh] overflow-y-auto border border-slate-200 dark:border-slate-700 shadow-2xl relative">
    <button
      @click="closeSearchModal"
      class="absolute top-3 right-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 text-xl">
      ✕
    </button>

    <h3 class="text-xl font-semibold mb-4 text-slate-700 dark:text-slate-100">
      Результаты поиска: "{{ q }}"
    </h3>

    <template v-if="!q.trim()">
      <div class="text-center text-slate-500 py-10">Введите запрос для поиска…</div>
    </template>

    <template v-else>
      <!-- Компании -->
      <div v-if="globalResults.companies.length" class="mb-6">
        <h4 class="text-slate-500 font-semibold mb-2">🏢 Компании</h4>
        <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-3">
          <div
            v-for="c in globalResults.companies"
            :key="'company-'+c.id"
            class="p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white/80 dark:bg-slate-900/70 hover:shadow cursor-pointer transition"
            @click="$inertia.visit(`/companies/${c.id}`)">
            <div class="font-medium text-slate-700 dark:text-slate-100 truncate">{{ c.name }}</div>
          </div>
        </div>
      </div>

      <!-- Проекты -->
      <div v-if="globalResults.projects.length" class="mb-6">
        <h4 class="text-slate-500 font-semibold mb-2">📁 Проекты</h4>
        <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-3">
          <div
            v-for="p in globalResults.projects"
            :key="'project-'+p.id"
            class="p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white/80 dark:bg-slate-900/70 hover:shadow cursor-pointer transition"
            @click="$inertia.visit(`/projects/${p.id}`)">
            <div class="font-medium text-slate-700 dark:text-slate-100 truncate">{{ p.name }}</div>
            <div class="text-xs text-slate-500">{{ p.company?.name }}</div>
          </div>
        </div>
      </div>

      <!-- Задачи -->
      <div v-if="globalResults.tasks.length" class="mb-6">
        <h4 class="text-slate-500 font-semibold mb-2">✅ Задачи</h4>
        <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-3">
          <div
            v-for="t in globalResults.tasks"
            :key="'task-'+t.id"
            class="p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white/80 dark:bg-slate-900/70 hover:shadow cursor-pointer transition"
            @click="$inertia.visit(`/tasks/${t.id}`)">
            <div class="font-medium text-slate-700 dark:text-slate-100 truncate">{{ t.title }}</div>
            <div class="text-xs text-slate-500">{{ t.project?.name }}</div>
          </div>
        </div>
      </div>

      <!-- Подзадачи -->
      <div v-if="globalResults.subtasks.length" class="mb-6">
        <h4 class="text-slate-500 font-semibold mb-2">🧩 Подзадачи</h4>
        <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-3">
          <div
            v-for="s in globalResults.subtasks"
            :key="'sub-'+s.id"
            class="p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white/80 dark:bg-slate-900/70 hover:shadow cursor-pointer transition"
            @click="$inertia.visit(`/subtasks/${s.id}`)">
            <div class="font-medium text-slate-700 dark:text-slate-100 truncate">{{ s.title }}</div>
            <div class="text-xs text-slate-500">{{ s.task?.project?.name }}</div>
          </div>
        </div>
      </div>

      <div v-if="!hasResults" class="text-center text-slate-500 py-10">
        Ничего не найдено по запросу "{{ q }}"
      </div>
    </template>
  </div>
</div>






<!-- Модальное окно подтверждения -->
    <div v-if="showConfirmModal" class="fixed inset-0 flex items-center justify-center bg-black/50 z-50">
      <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-96">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Подтверждение удаления</h3>
        <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
          Введите пароль для подтверждения удаления компании. Это действие необратимо.
        </p>

        <input
  v-model="deletePassword"
  type="password"
  placeholder="Ваш пароль"
  class="w-full border rounded-lg px-3 py-2 mb-3 dark:bg-gray-700 dark:text-white"
  autocomplete="new-password"
/>


        <p v-if="deleteError" class="text-sm text-rose-600 mb-3">{{ deleteError }}</p>

        <div class="flex justify-end gap-2">
          <button @click="showConfirmModal = false" class="px-4 py-2 border rounded-lg">Отмена</button>
          <button @click="deleteCompanyConfirm" class="px-4 py-2 bg-rose-600 text-white rounded-lg hover:bg-rose-700">
            Удалить
          </button>
        </div>
      </div>
    </div>


      <!-- Модалка создания -->
      <div v-if="showModal" class="fixed inset-0 z-50 grid place-items-center">
        <div class="absolute inset-0 bg-black/50" @click="showModal=false"></div>
        <div class="relative w-full max-w-md rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-lg text-slate-500">Новая компания</h3>
            <button @click="showModal=false" class="text-slate-400 hover:text-slate-600">✕</button>
          </div>
          <form @submit.prevent="createCompany" class="space-y-4">
            <div>
              <label class="text-sm font-medium">Название *</label>
              <input
                v-model="form.name"
                required
                class="mt-1 w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900/60 px-3 py-2 text-sm outline-none focus:border-slate-400 dark:focus:border-slate-600" />
            </div>
            <div>
              <label class="text-sm font-medium" style="color: aliceblue;">Логотип</label>
              <input type="file" accept="image/*" @change="onFileChange"
                     class="mt-1 w-full text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-slate-900 file:text-white dark:file:bg-white dark:file:text-slate-900 file:px-3 file:py-2" />
            </div>
            <div class="flex justify-end gap-2 pt-2">
              <button  type="button" @click="showModal=false"
                      class=" text-slate-500 rounded-xl px-4 py-2 text-sm border border-slate-200 dark:border-slate-800 dark:text-slate-900" >
                Отмена
              </button>
              <button type="submit" :disabled="submitting || !form.name.trim()"
                      class="rounded-xl px-4 py-2 text-sm font-semibold bg-slate-900 text-white dark:bg-white dark:text-slate-900 disabled:opacity-60">
                {{ submitting ? 'Создание…' : 'Создать' }}
              </button>
            </div>
          </form>
        </div>
      </div>


<!-- ================= Я руковожу ================= -->
<div class="mt-12 space-y-6">
  <div class="flex items-center justify-between">
    <h3 class="text-lg font-semibold text-slate-600 dark:text-slate-300">
      🚀 Я руковожу
    </h3>
  </div>

  <!-- Загрузка -->
  <div v-if="loadingSummary">
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="i in 3" :key="'mp'+i" class="h-24 rounded-2xl bg-slate-100 dark:bg-slate-800 animate-pulse"/>
    </div>
  </div>

  <!-- Контент -->
  <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
    <div
      v-for="(projects, companyName) in managingByCompany"
      :key="companyName"
      class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-900/60 p-5 shadow-sm flex flex-col"
    >
      <div class="flex items-center justify-between mb-3">
        <h4 class="font-semibold text-slate-700 dark:text-slate-200 break-words">
          🏢 {{ companyName }}
        </h4>

        <button
          v-if="projects.length > 6"
          @click="openShowAllProjects(companyName, projects)"
          class="text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 font-medium transition">
          Показать все
        </button>
      </div>

      <!-- Сетка проектов -->
      <div class="grid grid-cols-2 gap-3 flex-1">
        <div
          v-for="p in projects.slice(0, 6)"
          :key="p.id"
          class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white/90 dark:bg-slate-900/70 p-3 hover:shadow-md transition cursor-pointer"
          @click="$inertia.visit(`/projects2/${p.id}`)"
        >
          <div class="font-semibold text-sm truncate text-slate-700 dark:text-slate-100">{{ p.name }}</div>
        </div>
      </div>

      <div v-if="!projects.length" class="text-sm text-slate-500 dark:text-slate-400 mt-4 text-center">
        Нет проектов
      </div>
    </div>
  </div>
</div>

<!-- 🪟 Модальное окно с проектами -->
<div
  v-if="showAllProjectsModal"
  class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-6 backdrop-blur-sm transition"
>
  <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 w-full max-w-5xl max-h-[85vh] overflow-y-auto border border-slate-200 dark:border-slate-700 shadow-2xl relative">

    <!-- Кнопка закрытия -->
    <button
      @click="showAllProjectsModal = false"
      class="absolute top-3 right-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 text-xl">
      ✕
    </button>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
      <h3 class="text-xl font-semibold text-slate-700 dark:text-slate-100">
        📁 Проекты компании: {{ selectedCompanyName }}
      </h3>

      <div class="relative w-full sm:w-72">
        <input
          v-model="projectSearch"
          type="text"
          placeholder="🔍 Поиск проекта..."
          class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
        />
      </div>
    </div>

    <!-- Список проектов -->
    <div
      v-if="filteredProjects.length"
      class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4"
    >
      <div
        v-for="p in filteredProjects"
        :key="p.id"
        class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white/90 dark:bg-slate-900/70 p-4 hover:shadow-md transition cursor-pointer"
        @click="$inertia.visit(`/projects/${p.id}`)"
      >
        <div class="font-semibold text-slate-700 dark:text-slate-100 break-words truncate">
          {{ p.name }}
        </div>
      </div>
    </div>

    <!-- Если нет проектов -->
    <div v-else class="text-center py-12 text-slate-500 dark:text-slate-400">
      <div class="text-3xl mb-2">🔍</div>
      <p>Проекты не найдены</p>
    </div>
  </div>
</div>





<!-- ================= Мои задачи ================= -->

<div class="mt-12 space-y-6">
  <div class="flex items-center justify-between">
    <h3 class="text-lg font-semibold text-slate-600 dark:text-slate-300">
      ✅ Мои задачи
    </h3>
  </div>

  <div v-if="loadingSummary">
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="i in 6" :key="'tsk'+i" class="h-28 rounded-2xl bg-slate-100 dark:bg-slate-800 animate-pulse"/>
    </div>
  </div>

  <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-2 gap-6">
    <!-- === Компании === -->
    <div
      v-for="(projects, companyName) in allTasksByCompanyAndProject"
      :key="companyName"
      class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-900/60 p-5 shadow-sm mb-6"
    >
      <h4 class="font-semibold text-lg text-slate-700 dark:text-slate-200 mb-4">
        🏢 {{ companyName }}
      </h4>

      <!-- === Проекты внутри компании === -->
      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-2 gap-6">
        <div
          v-for="(tasks, projectName) in projects"
          :key="projectName"
          class="border border-slate-100 dark:border-slate-800 rounded-2xl p-4 bg-slate-50/60 dark:bg-slate-800/40"
        >
          <div class="flex items-center justify-between mb-3">
            <h5 class="text-base font-semibold text-slate-600 dark:text-slate-300">
              📁 {{ projectName }}
            </h5>
            <button
              v-if="tasks.length > 6"
              @click="openProjectTasks(companyName, projectName, tasks)"
              class="text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 font-medium transition"
            >
              Показать все
            </button>
          </div>

          <!-- === Сетка задач внутри проекта === -->
          <div class="grid grid-cols-2 gap-3">
            <div
              v-for="t in tasks.slice(0, 6)"
              :key="t.id"
              class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white/90 dark:bg-slate-900/70 p-3 hover:shadow-md transition cursor-pointer group relative"
              @click="$inertia.visit(`/tasks2/${t.id}`)"
            >
                <div class="relative">
                    <div class="font-semibold text-sm truncate text-slate-700 dark:text-slate-100">
                        {{ t.title }}
                    </div>
                    <!-- Тултип, который появляется при наведении -->
                    <div
                        class="absolute invisible opacity-0 group-hover:visible group-hover:opacity-100 bottom-full left-0 mb-2 px-3 py-2 bg-gray-900 text-white text-sm rounded-lg z-50 transition-all duration-200 whitespace-normal break-words max-w-xs w-max shadow-lg"
                        style="word-break: break-word;"
                    >
                        {{ t.title }}
                        <!-- Стрелка тултипа -->
                        <div class="absolute top-full left-3 border-4 border-transparent border-t-slate-900"></div>
                    </div>
                </div>
              <div class="text-[11px] text-slate-400 mt-1 f truncate text-slate-700 dark:text-slate-100">
                {{ t.start_date }} → {{ t.due_date || 'без срока' }}
              </div>
              <div class="mt-1 h-1.5 rounded bg-slate-100 dark:bg-slate-800 overflow-hidden">
                <div class="h-full bg-slate-900 dark:bg-white" :style="{width: ((t.progress ?? 0) + '%')}"/>
              </div>
            </div>



          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- 🪟 Модалка “Все задачи проекта” -->
<div
  v-if="showProjectTasksModal"
  class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-6 backdrop-blur-sm transition"
>
  <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 w-full max-w-5xl max-h-[85vh] overflow-y-auto border border-slate-200 dark:border-slate-700 shadow-2xl relative">
    <button
      @click="showProjectTasksModal = false"
      class="absolute top-3 right-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 text-xl">
      ✕
    </button>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
      <h3 class="text-xl font-semibold text-slate-700 dark:text-slate-100">
        📁 {{ selectedProjectName }} ({{ selectedCompanyName }})
      </h3>

      <div class="relative w-full sm:w-72">
        <input
          v-model="projectTasksSearch"
          type="text"
          placeholder="🔍 Поиск задачи..."
          class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
        />
      </div>
    </div>

    <div
      v-if="filteredProjectTasks.length"
      class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4"
    >
      <div
        v-for="t in filteredProjectTasks"
        :key="t.id"
        class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white/90 dark:bg-slate-900/70 p-4 hover:shadow-md transition cursor-pointer"
        @click="$inertia.visit(`/tasks/${t.id}`)"
      >
        <div class="font-semibold text-slate-700 dark:text-slate-100 break-words truncate">
          {{ t.title }}
        </div>
        <div class="text-[11px] text-slate-400 mt-1">
          {{ t.start_date }} → {{ t.due_date || 'без срока' }}
        </div>
      </div>
    </div>

    <div v-else class="text-center py-12 text-slate-500 dark:text-slate-400">
      <div class="text-3xl mb-2">🔍</div>
      Задачи не найдены
    </div>
  </div>
</div>




<!-- ================= Мои подзадачи ================= -->

<div class="mt-12 space-y-6">
  <div class="flex items-center justify-between">
    <h3 class="text-lg font-semibold text-slate-600 dark:text-slate-300">
      🧩 Мои подзадачи
    </h3>
  </div>

  <!-- Скелетон -->
  <div v-if="loadingSummary">
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="i in 3" :key="'st'+i" class="h-24 rounded-2xl bg-slate-100 dark:bg-slate-800 animate-pulse"/>
    </div>
  </div>

  <!-- Контент -->
  <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-2 gap-6">
    <!-- === Компании === -->
    <div
      v-for="(projects, companyName) in allSubtasksByCompany"
      :key="companyName"
      class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-900/60 p-5 shadow-sm mb-6"
    >
      <h4 class="font-semibold text-lg text-slate-700 dark:text-slate-200 mb-4">
        🏢 {{ companyName }}
      </h4>

      <!-- === Проекты === -->
      <div class="space-y-6">
        <div
          v-for="(tasks, projectName) in projects"
          :key="projectName"
          class="border border-slate-100 dark:border-slate-800 rounded-2xl p-4 bg-slate-50/60 dark:bg-slate-800/40"
        >
          <h5 class="text-base font-semibold text-slate-600 dark:text-slate-300 mb-3">
            📁 {{ projectName }}
          </h5>

          <!-- === Задачи внутри проекта === -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-2 gap-6">
                <div
                    v-for="(subtasks, taskTitle) in tasks"
                    :key="taskTitle"
                    class="border border-slate-200 dark:border-slate-700 rounded-xl p-3 bg-white/90 dark:bg-slate-900/70"
                >
                    <div class="flex items-center justify-between mb-2">
                        <!-- Заголовок задачи с тултипом -->
                        <div class="relative group inline-block max-w-[70%]">
                            <div class="font-semibold text-slate-700 dark:text-slate-100 truncate">
                                ✅ {{ taskTitle }}
                            </div>

                            <!-- Тултип для заголовка задачи -->
                            <div class="absolute invisible opacity-0 group-hover:visible group-hover:opacity-100 bottom-full left-0 mb-2 px-3 py-2 bg-gray-900 text-white text-sm rounded-lg z-50 transition-all duration-200 whitespace-normal break-words max-w-xs w-max shadow-lg">
                                ✅ {{ taskTitle }}
                                <!-- Стрелка -->
                                <div class="absolute top-full left-3 border-4 border-transparent border-t-gray-900"></div>
                            </div>
                        </div>

                        <button
                            v-if="subtasks.length > 4"
                            @click="openAllSubtasks(companyName, projectName, taskTitle, subtasks)"
                            class="text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 font-medium transition"
                        >
                            Показать все
                        </button>
                    </div>

                    <!-- Сетка подзадач -->
                    <div class="grid grid-cols-2 gap-3">
                        <div
                            v-for="st in subtasks.slice(0, 4)"
                            :key="st.id"
                            class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-3 hover:shadow-md transition cursor-pointer group/subtask"
                            @click="router.visit(`/tasks2/${st.task_id}`)"
                        >
                            <!-- Подзадача с тултипом -->
                            <div class="relative">
                                <div class="font-medium text-sm text-slate-700 dark:text-slate-100 truncate">
                                    🧩 {{ st.title }}
                                </div>

                                <!-- Тултип для подзадачи -->
                                <div class="absolute invisible opacity-0 group-hover/subtask:visible group-hover/subtask:opacity-100 bottom-full left-0 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg z-50 transition-all duration-200 whitespace-normal break-words max-w-xs w-max shadow-lg">
                                    🧩 {{ st.title }}
                                    <!-- Стрелка -->
                                    <div class="absolute top-full left-3 border-4 border-transparent border-t-gray-900"></div>
                                </div>
                            </div>

                            <div class="text-[11px] text-slate-400 mt-1">
                                {{ st.start_date }} → {{ st.due_date || 'без срока' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- 🪟 Модалка для всех подзадач -->
<div
  v-if="showAllSubtasksModal"
  class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-6 backdrop-blur-sm transition"
>
  <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 w-full max-w-5xl max-h-[85vh] overflow-y-auto border border-slate-200 dark:border-slate-700 shadow-2xl relative">
    <button
      @click="showAllSubtasksModal = false"
      class="absolute top-3 right-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 text-xl">
      ✕
    </button>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
      <h3 class="text-xl font-semibold text-slate-700 dark:text-slate-100">
        🧩 Все подзадачи: {{ selectedTaskTitle }} <br class="sm:hidden"/> ({{ selectedProjectName }} / {{ selectedCompanyName }})
      </h3>

      <div class="relative w-full sm:w-72">
        <input
          v-model="searchSubtasks"
          type="text"
          placeholder="🔍 Поиск подзадачи..."
          class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
        />
      </div>
    </div>

    <div
      v-if="filteredSubtasks.length"
      class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4"
    >
      <div
        v-for="st in filteredSubtasks"
        :key="st.id"
        class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white/90 dark:bg-slate-900/70 p-4 hover:shadow-md transition cursor-pointer"
        @click="$inertia.visit(`/tasks/${st.task_id}`)"
      >
        <div class="font-semibold text-slate-700 dark:text-slate-100 break-words truncate">
          {{ st.title }}
        </div>
        <div class="text-[11px] text-slate-400 mt-1">
          {{ st.start_date }} → {{ st.due_date || 'без срока' }}
        </div>
      </div>
    </div>

    <div v-else class="text-center py-12 text-slate-500 dark:text-slate-400">
      <div class="text-3xl mb-2">🔍</div>
      Подзадачи не найдены
    </div>
  </div>
</div>




<!-- ================= Я наблюдатель ================= -->
<div class="mt-12 space-y-4">
  <div class="flex items-center justify-between">
    <h3 class="text-lg font-semibold text-slate-500">Я наблюдатель</h3>
  </div>

  <div v-if="loadingSummary">
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="i in 6" :key="'wt'+i" class="h-28 rounded-2xl bg-slate-100 dark:bg-slate-800 animate-pulse"/>
    </div>
  </div>

  <div v-else>
    <div v-for="t in summary.watching_tasks" :key="t.id"
         class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-900/60 p-4 hover:shadow transition cursor-pointer"
         @click="$inertia.visit(`/tasks2/${t.id}`)">
      <div class="flex items-center justify-between gap-2">
        <div class="font-semibold truncate text-slate-500">{{ t.title }}</div>
        <span class="text-[10px] px-2 py-0.5 rounded-full" :class="prioBadge(t.priority)">
          {{ t.priority ?? '—' }}
        </span>
      </div>
      <div class="text-xs text-slate-400 truncate mt-1">
        {{ t.project?.company?.name ?? '—' }} / {{ t.project?.name ?? '—' }}
      </div>
      <div class="mt-3 h-2 rounded bg-slate-100 dark:bg-slate-800 overflow-hidden">
        <div class="h-full bg-slate-900 dark:bg-white" :style="{width: ((t.progress ?? 0) + '%')}"/>
      </div>
      <div class="mt-1 text-[11px] text-slate-500">
        Срок: {{ t.due_date ?? '—' }}
      </div>
    </div>



  </div>
</div>

<!-- ================= Я наблюдатель проектов ================= -->
<div class="mt-12 space-y-4">
  <div class="flex items-center justify-between">
    <h3 class="text-lg font-semibold text-slate-500">Я наблюдатель проектов</h3>
  </div>

  <div v-if="loadingSummary">
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="i in 6" :key="'wp'+i" class="h-28 rounded-2xl bg-slate-100 dark:bg-slate-800 animate-pulse"/>
    </div>
  </div>

  <div v-else>
    <div v-if="summary.watching_projects?.length" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="p in summary.watching_projects"
        :key="p.id"
        class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-900/60 p-4 hover:shadow transition cursor-pointer"
        @click="$inertia.visit(`/projects2/${p.id}`)"
      >
        <div class="flex items-center justify-between">
          <div class="font-semibold text-slate-600 dark:text-slate-300 truncate">
            {{ p.name }}
          </div>
        </div>
        <div class="text-xs text-slate-400 truncate mt-1">
          Компания: {{ p.company?.name ?? '—' }}
        </div>

        <div v-if="p.managers?.length" class="text-xs text-slate-400 mt-2">
          Руководители:
          <b class="text-slate-600 dark:text-slate-200">
            {{ p.managers.map(m => m.name).join(', ') }}
          </b>
        </div>
      </div>
    </div>

    <div v-else class="text-sm text-slate-400 italic">

    </div>
  </div>
</div>




<!-- ================= Сроки сегодня ================= -->
<div class="mt-12 grid md:grid-cols-2 gap-6">
  <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-900/60 p-4">
    <div class="flex items-center justify-between mb-2">
      <h3 class="font-semibold text-slate-500" >Сроки сегодня</h3>
      <span class="text-xs text-slate-500">{{ summary.due_today.length }}</span>
    </div>
    <div v-if="loadingSummary" class="space-y-2">
      <div v-for="i in 4" :key="'td'+i" class="h-6 rounded bg-slate-100 dark:bg-slate-800 animate-pulse"/>
    </div>
    <ul v-else class="space-y-2">
      <li v-for="t in summary.due_today" :key="t.id" class="text-sm flex justify-between gap-3">
        <span class="truncate text-slate-500" >{{ t.title }}</span>
        <button class="text-xs text-slate-500 hover:text-slate-700"
                @click="$inertia.visit(`/tasks2/${t.id}`)">Открыть</button>
      </li>
      <li v-if="!summary.due_today.length" class="text-sm text-slate-500">Нет задач на сегодня 🎉</li>
    </ul>
  </div>

  <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-900/60 p-4">
    <div class="flex items-center justify-between mb-2">
      <h3 class="font-semibold text-slate-500" >Просрочено</h3>
      <span class="text-xs text-slate-500">{{ summary.overdue.length }}</span>
    </div>
    <div v-if="loadingSummary" class="space-y-2">
      <div v-for="i in 4" :key="'od'+i" class="h-6 rounded bg-slate-100 dark:bg-slate-800 animate-pulse"/>
    </div>
    <ul v-else class="space-y-2">
      <li v-for="t in summary.overdue" :key="t.id" class="text-sm flex justify-between gap-3">
        <span class="truncate text-slate-500" >⚠️ {{ t.title }}</span>
        <button class="text-xs text-slate-500 hover:text-slate-700"
                @click="$inertia.visit(`/tasks2/${t.id}`)">Открыть</button>
      </li>
      <li v-if="!summary.overdue.length" class="text-sm text-slate-500">Просроченных задач нет</li>
    </ul>
  </div>
</div>
    </div>



  </AuthenticatedLayout>
</template>
