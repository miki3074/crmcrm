<script setup>
import { ref, onMounted, computed } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
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
  return (summary.value.all_subtasks || []).reduce((acc, st) => {
    const companyName = st.task?.project?.company?.name || 'Без компании'
    const projectName = st.task?.project?.name || 'Без проекта'

    if (!acc[companyName]) acc[companyName] = {}
    if (!acc[companyName][projectName]) acc[companyName][projectName] = []

    // исключаем дубликаты
    if (!acc[companyName][projectName].some(s => s.id === st.id)) {
      acc[companyName][projectName].push(st)
    }

    return acc
  }, {})
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


// onMounted
onMounted(async () => {
  await Promise.all([fetchCompanies(), fetchSummary()])
})
</script>


<template>
  <Head title="Панель" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-200">Панель управления</h2>
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
      </div>

     
      <div class="flex items-center gap-3">
        <div class="relative flex-1">
          <input
            v-model="q"
            @input="filterList"
            type="text"
            placeholder="Поиск компаний…"
            style="color: aliceblue;"
            class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/60 px-4 py-2.5 text-sm outline-none focus:border-slate-300 dark:focus:border-slate-700" />
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
      <div v-else>
  <!-- Проверка, есть ли вообще компании -->
  <div v-if="!filtered.length" class="text-center py-16 border border-dashed rounded-2xl dark:border-slate-800">
    <div class="text-4xl mb-2">🏢</div>
    <div class="font-medium text-slate-500">Пока нет компаний</div>
    <p class="text-sm text-slate-500 mt-1">Создайте первую компанию, чтобы начать работу.</p>
    <button
      v-if="isAdmin"
      class="mt-4 inline-flex items-center gap-2 rounded-xl bg-slate-900 text-white dark:bg-white dark:text-slate-900 px-4 py-2.5 text-sm font-semibold hover:opacity-90"
      @click="showModal = true">
      Добавить компанию
    </button>
  </div>

  <!-- Мои компании -->
  <div v-if="myCompanies.length">
    <h3 class="text-lg font-semibold mb-2 text-slate-500">Мои компании</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="company in myCompanies"
        :key="company.id"
        class="group rounded-2xl border border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-900/60 p-4 hover:shadow transition cursor-pointer"
        @click="$inertia.visit(`/companies/${company.id}`)">
        <div class="flex items-center gap-3">
          <img
            v-if="company.logo"
            :src="`/storage/${company.logo}`"
            alt=""
            class="h-12 w-12 object-cover rounded-xl ring-1 ring-slate-200 dark:ring-slate-800" />
          <div
            v-else
            class="h-12 w-12 rounded-xl bg-slate-100 dark:bg-slate-800 grid place-items-center text-slate-400">
            🏢
          </div>
          <div class="min-w-0">
            <div class="font-semibold truncate text-slate-500">{{ company.name }}</div>
            <div class="text-xs text-slate-500">Проектов: {{ company.projects?.length ?? '—' }}</div>
            <button
    v-if="company.user_id === userId"
    @click.stop="deleteCompany(company.id)"
    class=" top-0 right-0 text-rose-500 hover:text-rose-700 text-sm"
    title="Удалить компанию"
  >
    удалить
  </button>
          </div>

      
        </div>

          

      </div>
    </div>
  </div>

  <!-- Другие компании -->
  <div v-if="otherCompanies.length" class="mt-8">
    <h3 class="text-lg font-semibold mb-2 text-slate-500">Другие компании</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="company in otherCompanies"
        :key="company.id"
        class="group rounded-2xl border border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-900/60 p-4 hover:shadow transition cursor-pointer"
        @click="$inertia.visit(`/companies/${company.id}`)">
        <div class="flex items-center gap-3">
          <img
            v-if="company.logo"
            :src="`/storage/${company.logo}`"
            alt=""
            class="h-12 w-12 object-cover rounded-xl ring-1 ring-slate-200 dark:ring-slate-800" />
          <div
            v-else
            class="h-12 w-12 rounded-xl bg-slate-100 dark:bg-slate-800 grid place-items-center text-slate-400">
            🏢
          </div>
          <div class="min-w-0">
            <div class="font-semibold truncate text-slate-500">{{ company.name }}</div>
            <div class="text-xs text-slate-500">Проектов: {{ company.projects?.length ?? '—' }}</div>
          </div>
        </div>
      </div>
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
<div class="mt-12 space-y-4">
  <div class="flex items-center justify-between">
    <h3 class="text-lg font-semibold text-slate-500" >Я руковожу</h3>
  </div>

  <div v-if="loadingSummary">
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="i in 3" :key="'mp'+i" class="h-24 rounded-2xl bg-slate-100 dark:bg-slate-800 animate-pulse"/>
    </div>
  </div>

  <div v-else>
    <div v-for="(projects, companyName) in managingByCompany" :key="companyName" class="mb-6">
      <h4 class="font-semibold mb-2 text-slate-500" >Компания: {{ companyName }}</h4>
      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="p in projects" :key="p.id"
             class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-900/60 p-4 hover:shadow transition cursor-pointer"
             @click="$inertia.visit(`/projects/${p.id}`)">
          <div class="font-semibold truncate text-slate-500">{{ p.name }}</div>
          
        </div>
      </div>
    </div>
  </div>
</div>




<!-- ================= Мои задачи ================= -->
<div class="mt-12 space-y-4">
  <div class="flex items-center justify-between">
    <h3 class="text-lg font-semibold text-slate-500">Мои задачи</h3>
  </div>

  <div v-if="loadingSummary">
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="i in 6" :key="'tsk'+i" class="h-28 rounded-2xl bg-slate-100 dark:bg-slate-800 animate-pulse"/>
    </div>
  </div>

  <div v-else>
    <div v-for="(projects, companyName) in allTasksByCompanyAndProject" :key="companyName" class="mb-6">
      <h4 class="font-semibold mb-2 text-slate-500">Компания: {{ companyName }}</h4>

      <div v-for="(tasks, projectName) in projects" :key="projectName" class="mb-4">
        <h5 class="text-sm text-slate-400 mb-2">Проект: {{ projectName }}</h5>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="t in tasks" :key="t.id"
               class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-900/60 p-4 hover:shadow transition cursor-pointer"
               @click="$inertia.visit(`/tasks/${t.id}`)">
            <div class="flex items-center justify-between gap-2">
              <div class="font-semibold truncate text-slate-500">{{ t.title }}</div>
              <span class="text-[10px] px-2 py-0.5 rounded-full" :class="prioBadge(t.priority)">
                {{ t.priority ?? '—' }}
              </span>
            </div>
            <div class="text-xs text-slate-400 truncate mt-1">
              {{ t.start_date }} → {{ t.due_date || 'без срока' }}
            </div>
            <div class="mt-2 h-2 rounded bg-slate-100 dark:bg-slate-800 overflow-hidden">
              <div class="h-full bg-slate-900 dark:bg-white" :style="{width: ((t.progress ?? 0) + '%')}"/>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>


<!-- ================= Мои подзадачи ================= -->
<div class="mt-12 space-y-4">
  <div class="flex items-center justify-between">
    <h3 class="text-lg font-semibold text-slate-500">Мои подзадачи</h3>
  </div>

  <!-- Скелетоны -->
  <div v-if="loadingSummary">
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="i in 3" :key="'st'+i" class="h-24 rounded-2xl bg-slate-100 dark:bg-slate-800 animate-pulse"/>
    </div>
  </div>

  <!-- Список -->
  <div v-else>
    <div v-for="(projects, companyName) in allSubtasksByCompany" :key="companyName" class="mb-6">
      <h4 class="font-semibold mb-2 text-slate-500">Компания: {{ companyName }}</h4>

      <div v-for="(subtasks, projectName) in projects" :key="projectName" class="mb-4">
        <h5 class="text-sm text-slate-400 mb-2">Проект: {{ projectName }}</h5>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="st in subtasks" :key="st.id"
               class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-900/60 p-4 hover:shadow transition cursor-pointer"
               @click="$inertia.visit(`/tasks/${st.task_id}`)">
            <div class="font-semibold truncate text-slate-500">{{ st.title }}</div>
            <div class="mt-2 text-xs text-slate-400">
              {{ st.start_date }} → {{ st.due_date || 'без срока' }}
            </div>
          </div>
        </div>
      </div>
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
         @click="$inertia.visit(`/tasks/${t.id}`)">
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
                @click="$inertia.visit(`/tasks/${t.id}`)">Открыть</button>
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
                @click="$inertia.visit(`/tasks/${t.id}`)">Открыть</button>
      </li>
      <li v-if="!summary.overdue.length" class="text-sm text-slate-500">Просроченных задач нет</li>
    </ul>
  </div>
</div>
    </div>


    
  </AuthenticatedLayout>
</template>
