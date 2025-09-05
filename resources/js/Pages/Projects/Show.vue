<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import { Head, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const { props } = usePage()
const projectId = props.id

// state
const loading = ref(true)
const project = ref(null)
const employees = ref([])

const showTaskModal = ref(false)
const showBudgetModal = ref(false)
const showDescriptionModal = ref(false)
const submitLoading = ref(false)
const errorText = ref('')

const budgetForm = ref({ budget: '' })
const descriptionForm = ref({ description: '' })

const taskForm = ref({
  title: '',
  executor_id: '',
  responsible_id: '',
  priority: 'low', // low|medium|high — соответствует контроллеру
  start_date: new Date().toISOString().slice(0, 10),
  due_date: '',
  files: null,
})

// perms
const roles = props.auth?.roles || []
const user = props.auth?.user
const isAdmin = computed(() => roles.includes('admin'))
const isCompanyOwner = computed(() => project.value?.company?.user_id === user?.id)
const isProjectManager = computed(() => project.value?.manager_id === user?.id)
const canCreateTask = computed(() => isAdmin.value || isCompanyOwner.value || isProjectManager.value)
const canEditBudget = computed(() => isAdmin.value || isCompanyOwner.value)
const canEditDescription = computed(() => isAdmin.value || isCompanyOwner.value || isProjectManager.value)

// helpers
const daysLeft = (startDate, duration) => {
  if (!startDate || !duration) return '—'
  const start = new Date(startDate)
  const end = new Date(start)
  end.setDate(start.getDate() + Number(duration))
  const diff = Math.ceil((end - new Date()) / (1000 * 60 * 60 * 24))
  return diff
}
const daysBadge = (n) =>
  n === '—'
    ? 'bg-gray-100 text-gray-600 ring-1 ring-gray-200'
    : n > 7
    ? 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200'
    : n >= 0
    ? 'bg-amber-100 text-amber-700 ring-1 ring-amber-200'
    : 'bg-rose-100 text-rose-700 ring-1 ring-rose-200'

const priorityBadge = (p) =>
  p === 'high'
    ? 'bg-rose-100 text-rose-700 ring-1 ring-rose-200'
    : p === 'medium'
    ? 'bg-amber-100 text-amber-700 ring-1 ring-amber-200'
    : 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200'

// api
const fetchProject = async () => {
  loading.value = true
  try {
    const { data } = await axios.get(`/api/projects/${projectId}`)
    project.value = data
    budgetForm.value.budget = data?.budget ?? ''
    descriptionForm.value.description = data?.description ?? ''
  } finally {
    loading.value = false
  }
}

const fetchEmployees = async () => {
  const { data } = await axios.get(`/api/projects/${projectId}/employees`)
  employees.value = data
}

const openCreateTask = async () => {
  if (!canCreateTask.value) return alert('Недостаточно прав для создания задачи.')
  errorText.value = ''
  await fetchEmployees()
  showTaskModal.value = true
}

const handleFileUpload = (e) => { taskForm.value.files = e.target.files }

const createTask = async () => {
  errorText.value = ''
  submitLoading.value = true
  const formData = new FormData()
  formData.append('title', taskForm.value.title)
  formData.append('executor_id', taskForm.value.executor_id)
  formData.append('responsible_id', taskForm.value.responsible_id)
  formData.append('priority', taskForm.value.priority) // low|medium|high
  formData.append('start_date', taskForm.value.start_date)
  formData.append('due_date', taskForm.value.due_date)
  formData.append('project_id', projectId)
  formData.append('company_id', project.value.company.id)
  if (taskForm.value.files) {
    for (let i = 0; i < taskForm.value.files.length; i++) {
      formData.append('files[]', taskForm.value.files[i])
    }
  }
  try {
    await axios.get('/sanctum/csrf-cookie')
    await axios.post('/api/tasks', formData, { headers: { 'Content-Type': 'multipart/form-data' } })
    showTaskModal.value = false
    taskForm.value = { title: '', executor_id: '', responsible_id: '', priority: 'low', start_date: new Date().toISOString().slice(0, 10), due_date: '', files: null }
    await fetchProject()
  } catch (e) {
    errorText.value = e?.response?.data?.message || 'Не удалось создать задачу'
  } finally {
    submitLoading.value = false
  }
}

const saveBudget = async () => {
  await axios.patch(`/api/projects/${projectId}/budget`, { budget: budgetForm.value.budget })
  showBudgetModal.value = false
  await fetchProject()
}

const saveDescription = async () => {
  await axios.patch(`/api/projects/${projectId}/description`, { description: descriptionForm.value.description })
  showDescriptionModal.value = false
  await fetchProject()
}

onMounted(fetchProject)
</script>

<template>
  <Head :title="project?.name ? `Проект — ${project.name}` : 'Проект'" />
  <AuthenticatedLayout>
    <!-- HERO -->
    <div class="relative overflow-hidden">
      <div class="absolute inset-0 bg-gradient-to-r from-indigo-600 via-purple-600 to-fuchsia-600 opacity-90"></div>
      <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 text-white">
        <div class="flex items-start gap-4">
          <div class="flex-1">
            <h1 class="text-2xl sm:text-3xl font-semibold">
              {{ project?.name ?? 'Загрузка…' }}
            </h1>
            <div class="mt-2 flex flex-wrap items-center gap-2 text-sm">
              <span class="px-2 py-1 rounded-full bg-white/20">
                Компания: <b>{{ project?.company?.name ?? '—' }}</b>
              </span>
              <span class="px-2 py-1 rounded-full bg-white/20">
                Руководитель: <b>{{ project?.manager?.name ?? '—' }}</b>
              </span>
              <span class="px-2 py-1 rounded-full bg-white/20">
                Старт: <b>{{ project?.start_date ?? '—' }}</b>
              </span>
              <span class="px-2 py-1 rounded-full bg-white/20">
                Длительность: <b>{{ project?.duration_days ?? '—' }}</b> дн.
              </span>
              <span
                class="px-2 py-1 rounded-full bg-white text-gray-900"
                v-if="project"
                :class="daysBadge(daysLeft(project.start_date, project.duration_days))"
              >
                Осталось: <b>{{ daysLeft(project.start_date, project.duration_days) }}</b> дн.
              </span>
              <span class="px-2 py-1 rounded-full bg-white/20" v-if="project?.budget">
                Бюджет: <b>{{ Number(project.budget).toLocaleString('ru-RU') }} ₽</b>
              </span>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <button
              v-if="canEditBudget"
              @click="showBudgetModal = true"
              class="rounded-xl bg-amber-400/90 hover:bg-amber-400 text-gray-900 px-4 py-2 font-medium"
            >
              Бюджет
            </button>
            <button
              v-if="canEditDescription"
              @click="showDescriptionModal = true"
              class="rounded-xl bg-white text-gray-900 hover:bg-white/90 px-4 py-2 font-medium"
            >
              Описание
            </button>
            <button
              v-if="canCreateTask"
              @click="openCreateTask"
              class="rounded-xl bg-emerald-400/95 hover:bg-emerald-400 text-gray-900 px-4 py-2 font-medium"
            >
              + Задача
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- BODY -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-4 pb-10" style="    margin-top: 3%;">
      <!-- Описание -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2">
          <!-- Задачи -->
          <div class="rounded-2xl border bg-white dark:bg-gray-800 p-5">
            <div class="flex items-center justify-between mb-3">
              <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Задачи проекта</h2>
              <div class="text-sm text-gray-500 dark:text-gray-400">
                Всего: {{ project?.tasks?.length || 0 }}
              </div>
            </div>

            <!-- Скелетоны -->
            <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div v-for="i in 4" :key="i" class="rounded-xl border p-4 animate-pulse">
                <div class="h-4 w-1/2 bg-gray-200 dark:bg-gray-700 rounded mb-3"></div>
                <div class="h-3 w-2/3 bg-gray-200 dark:bg-gray-700 rounded mb-2"></div>
                <div class="h-3 w-1/2 bg-gray-200 dark:bg-gray-700 rounded"></div>
              </div>
            </div>

            <!-- Пусто -->
            <div v-else-if="!project?.tasks?.length" class="text-center py-10">
              <div class="mx-auto w-14 h-14 rounded-2xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                <svg class="w-6 h-6 text-gray-500 dark:text-gray-300" viewBox="0 0 24 24" fill="currentColor"><path d="M7 11h10v2H7zM7 7h10v2H7zM7 15h7v2H7z"/></svg>
              </div>
              <p class="mt-3 text-gray-700 dark:text-gray-300">Задач пока нет.</p>
              <button
                v-if="canCreateTask"
                @click="openCreateTask"
                class="mt-4 rounded-xl bg-indigo-600 text-white px-4 py-2 hover:bg-indigo-700"
              >
                Создать задачу
              </button>
            </div>

            <!-- Список задач -->
            <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div
                v-for="t in project.tasks"
                :key="t.id"
                class="group rounded-xl border p-4 bg-white dark:bg-gray-800 hover:shadow-md transition cursor-pointer"
                @click="$inertia.visit(`/tasks/${t.id}`)"
              >
                <div class="flex items-start justify-between gap-3">
                  <h3 class="text-base font-semibold text-gray-900 dark:text-white leading-snug">{{ t.title }}</h3>
                  <span class="px-2 py-1 text-xs rounded-full ring-1" :class="priorityBadge(t.priority)">
                    {{ t.priority === 'high' ? 'Высокая' : t.priority === 'medium' ? 'Средняя' : 'Обычная' }}
                  </span>
                </div>

                <div class="mt-2 text-sm text-gray-600 dark:text-gray-300 space-y-1.5">
                  <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M7 11h5V6H7v5zm0 7h5v-5H7v5zm7 0h5v-5h-5v5zM14 6v5h5V6h-5z"/></svg>
                    <span>С {{ t.start_date }} по {{ t.due_date }}</span>
                  </div>
                  <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.2 0 4-1.79 4-4s-1.8-4-4-4-4 1.79-4 4 1.8 4 4 4zm0 2c-3.33 0-10 1.67-10 5v3h20v-3c0-3.33-6.67-5-10-5z"/></svg>
                    <span>От: <b>{{ t.creator?.name ?? '—' }}</b> → Кому: <b>{{ t.executor?.name ?? '—' }}</b></span>
                  </div>
                  <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l4 7H8l4-7zm0 20l-4-7h8l-4 7zM2 12l7-4v8l-7-4zm20 0l-7 4V8l7 4z"/></svg>
                    <span>Ответственный: <b>{{ t.responsible?.name ?? '—' }}</b></span>
                  </div>
                </div>

                <div v-if="t.files?.length" class="mt-3 pt-3 border-t">
                  <div class="text-xs font-medium text-gray-500 mb-1">Файлы:</div>
                  <div class="flex flex-wrap gap-2">
                    <a
                      v-for="f in t.files"
                      :key="f.id"
                      :href="`/storage/${f.file_path}`"
                      target="_blank"
                      class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-100"
                      @click.stop
                    >
                      <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12V8l-4-6zM6 22V4h7v5h5v13H6z"/></svg>
                      {{ f.file_path.split('/').pop() }}
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Боковая панель -->
        <div class="space-y-4">
          <div class="rounded-2xl border bg-white dark:bg-gray-800 p-5">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Кратко о проекте</h3>
            <dl class="mt-3 space-y-2 text-sm text-gray-700 dark:text-gray-300">
              <div class="flex justify-between">
                <dt>Компания</dt><dd class="font-medium text-gray-900 dark:text-white">{{ project?.company?.name ?? '—' }}</dd>
              </div>
              <div class="flex justify-between">
                <dt>Инициатор</dt><dd class="font-medium">{{ project?.initiator?.name ?? '—' }}</dd>
              </div>
              <div class="flex justify-between">
                <dt>Старт</dt><dd class="font-medium">{{ project?.start_date ?? '—' }}</dd>
              </div>
              <div class="flex justify-between">
                <dt>Длительность</dt><dd class="font-medium">{{ project?.duration_days ?? '—' }} дн.</dd>
              </div>
              <div class="flex justify-between">
                <dt>Бюджет</dt><dd class="font-medium">{{ project?.budget ? Number(project.budget).toLocaleString('ru-RU') + ' ₽' : '—' }}</dd>
              </div>
            </dl>

            <div class="mt-4">
              <div class="text-xs font-semibold text-gray-500 mb-1">Описание</div>
              <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">
                {{ project?.description || 'Описание не задано.' }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Модалка: Новая задача -->
    <div v-if="showTaskModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/50" @click="showTaskModal = false"></div>
      <div class="relative w-full max-w-lg bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Создание задачи</h3>
          <button @click="showTaskModal=false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">✕</button>
        </div>

        <p v-if="errorText" class="mt-3 text-sm text-rose-600">{{ errorText }}</p>

        <form class="mt-4 space-y-4" @submit.prevent="createTask">
          <div>
            <label class="block text-sm mb-1 text-gray-700 dark:text-gray-300">Название</label>
            <input v-model="taskForm.title" class="w-full rounded-xl border px-3 py-2 bg-white dark:bg-gray-700 dark:text-white" required />
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm mb-1 text-gray-700 dark:text-gray-300">Исполнитель</label>
              <select v-model="taskForm.executor_id" class="w-full rounded-xl border px-3 py-2 bg-white dark:bg-gray-700 dark:text-white" required>
                <option disabled value="">Выберите сотрудника</option>
                <option v-for="u in employees" :key="u.id" :value="u.id">{{ u.name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm mb-1 text-gray-700 dark:text-gray-300">Ответственный</label>
              <select v-model="taskForm.responsible_id" class="w-full rounded-xl border px-3 py-2 bg-white dark:bg-gray-700 dark:text-white" required>
                <option disabled value="">Выберите сотрудника</option>
                <option v-for="u in employees" :key="u.id" :value="u.id">{{ u.name }}</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm mb-1 text-gray-700 dark:text-gray-300">Приоритет</label>
              <select v-model="taskForm.priority" class="w-full rounded-xl border px-3 py-2 bg-white dark:bg-gray-700 dark:text-white">
                <option value="low">Обычная</option>
                <option value="medium">Средняя</option>
                <option value="high">Высокая</option>
              </select>
            </div>
            <div>
              <label class="block text-sm mb-1 text-gray-700 dark:text-gray-300">Начало</label>
              <input type="date" v-model="taskForm.start_date" class="w-full rounded-xl border px-3 py-2 bg-white dark:bg-gray-700 dark:text-white" required />
            </div>
            <div>
              <label class="block text-sm mb-1 text-gray-700 dark:text-gray-300">Окончание</label>
              <input type="date" v-model="taskForm.due_date" class="w-full rounded-xl border px-3 py-2 bg-white dark:bg-gray-700 dark:text-white" required />
            </div>
          </div>

          <div>
            <label class="block text-sm mb-1 text-gray-700 dark:text-gray-300">Файлы (pdf, excel, word)</label>
            <input type="file" multiple @change="handleFileUpload" accept=".pdf,.doc,.docx,.xls,.xlsx"
                   class="w-full rounded-xl border px-3 py-2 bg-white dark:bg-gray-700 dark:text-white" />
          </div>

          <div class="flex justify-end gap-2 pt-2">
            <button type="button" @click="showTaskModal=false" class="px-4 py-2 rounded-xl border bg-white dark:bg-gray-700 dark:text-white">Отмена</button>
            <button type="submit" :disabled="submitLoading" class="px-4 py-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-60">
              <span v-if="!submitLoading">Создать</span>
              <span v-else>Сохраняю…</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Модалка: Бюджет -->
    <div v-if="showBudgetModal && canEditBudget" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/50" @click="showBudgetModal=false"></div>
      <div class="relative w-full max-w-md bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Установить бюджет</h3>
          <button @click="showBudgetModal=false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">✕</button>
        </div>
        <form class="mt-4 space-y-4" @submit.prevent="saveBudget">
          <div>
            <label class="block text-sm mb-1 text-gray-700 dark:text-gray-300">Сумма (₽)</label>
            <input type="number" step="0.01" min="0" v-model="budgetForm.budget" class="w-full rounded-xl border px-3 py-2 bg-white dark:bg-gray-700 dark:text-white" required />
          </div>
          <div class="flex justify-end gap-2">
            <button type="button" @click="showBudgetModal=false" class="px-4 py-2 rounded-xl border bg-white dark:bg-gray-700 dark:text-white">Отмена</button>
            <button type="submit" class="px-4 py-2 rounded-xl bg-amber-600 text-white hover:bg-amber-700">Сохранить</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Модалка: Описание -->
    <div v-if="showDescriptionModal && canEditDescription" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/50" @click="showDescriptionModal=false"></div>
      <div class="relative w-full max-w-xl bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Описание проекта</h3>
          <button @click="showDescriptionModal=false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">✕</button>
        </div>
        <form class="mt-4 space-y-4" @submit.prevent="saveDescription">
          <div>
            <label class="block text-sm mb-1 text-gray-700 dark:text-gray-300">Описание</label>
            <textarea rows="6" v-model="descriptionForm.description" class="w-full rounded-xl border px-3 py-2 bg-white dark:bg-gray-700 dark:text-white" required />
          </div>
          <div class="flex justify-end gap-2">
            <button type="button" @click="showDescriptionModal=false" class="px-4 py-2 rounded-xl border bg-white dark:bg-gray-700 dark:text-white">Отмена</button>
            <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700">Сохранить</button>
          </div>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
