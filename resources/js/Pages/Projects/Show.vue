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
  executor_ids: [],
  responsible_ids: [],
  priority: 'low',
  start_date: new Date().toISOString().slice(0, 10),
  due_date: '',
  files: null,
})




// perms
const roles = props.auth?.roles || []
const user = props.auth?.user
const isAdmin = computed(() => roles.includes('admin'))
const isCompanyOwner = computed(() => project.value?.company?.user_id === user?.id)
const isProjectManager = computed(() =>
  project.value?.managers?.some(m => m.id === user?.id)
)

const canCreateTask = computed(() =>
  user?.id === project.value?.company?.user_id ||
  project.value?.managers?.some(m => m.id === user?.id)
)

const canEditName = computed(() =>
  user?.id === project.value?.company?.user_id ||
  project.value?.managers?.some(m => m.id === user?.id)
)

const canEditBudget = computed(() =>
  user?.id === project.value?.company?.user_id
)

const canEditDescription = computed(() =>
  user?.id === project.value?.company?.user_id ||
  project.value?.managers?.some(m => m.id === user?.id)
)


const canManageManagers = computed(() =>
  user?.id === project.value?.company?.user_id // ✅ только владелец компании
)


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

    // сразу загрузим сотрудников
    const res = await axios.get(`/api/projects/${projectId}/employees`)
    employees.value = res.data
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
  
taskForm.value.executor_ids.forEach(id => formData.append('executor_ids[]', id))
taskForm.value.responsible_ids.forEach(id => formData.append('responsible_ids[]', id))

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

const subprojectForm = ref({
  title: '',
  responsible_id: null,
})

const creatingSubproject = ref(false)
const subprojectError = ref('')

const createSubproject = async () => {
  subprojectError.value = ''
  creatingSubproject.value = true

  try {
    await axios.get('/sanctum/csrf-cookie')
    await axios.post(`/api/projects/${projectId}/subprojects`, subprojectForm.value)

    // сбрасываем форму
    subprojectForm.value = { title: '', responsible_id: null }

    // перезагрузим проект, чтобы сразу увидеть подпроекты
    await fetchProject()
  } catch (e) {
    subprojectError.value = e?.response?.data?.message || 'Не удалось создать подпроект'
  } finally {
    creatingSubproject.value = false
  }
}

const showNameModal = ref(false)
const nameForm = ref({ name: '' })
const nameError = ref('')
const savingName = ref(false)

const openNameModal = () => {
  nameForm.value.name = project.value?.name || ''
  showNameModal.value = true
}

const saveName = async () => {
  savingName.value = true
  nameError.value = ''
  try {
    await axios.patch(`/api/projects/${projectId}/name`, { name: nameForm.value.name })
    showNameModal.value = false
    await fetchProject() // обновим данные проекта
  } catch (e) {
    nameError.value = e?.response?.data?.message || 'Ошибка при обновлении названия'
  } finally {
    savingName.value = false
  }
}

// === МОДАЛКА: Добавить руководителя ===
const showAddManagerModal = ref(false)
const addManagerForm = ref({ user_id: null })
const addManagerError = ref('')
const addingManager = ref(false)

const openAddManager = async () => {
  await fetchEmployees()
  showAddManagerModal.value = true
}

const addManager = async () => {
  addManagerError.value = ''
  addingManager.value = true
  try {
    await axios.post(`/api/projects/${projectId}/add-manager`, addManagerForm.value)
    showAddManagerModal.value = false
    addManagerForm.value = { user_id: null }
    await fetchProject()
  } catch (e) {
    addManagerError.value = e?.response?.data?.message || 'Не удалось добавить руководителя'
  } finally {
    addingManager.value = false
  }
}


// === МОДАЛКА: Изменить руководителя ===
const showReplaceManagerModal = ref(false)
const replaceManagerForm = ref({ old_manager_id: null, new_manager_id: null })
const replaceManagerError = ref('')
const replacingManager = ref(false)

const openReplaceManager = async () => {
  await fetchEmployees()
  showReplaceManagerModal.value = true
}

const replaceManager = async () => {
  replaceManagerError.value = ''
  replacingManager.value = true
  try {
    await axios.post(`/api/projects/${projectId}/replace-manager`, replaceManagerForm.value)
    showReplaceManagerModal.value = false
    replaceManagerForm.value = { old_manager_id: null, new_manager_id: null }
    await fetchProject()
  } catch (e) {
    replaceManagerError.value = e?.response?.data?.message || 'Не удалось изменить руководителя'
  } finally {
    replacingManager.value = false
  }
}


const deleteProject = async () => {
  if (!confirm('Удалить проект и все связанные задачи и подзадачи?')) return;

  try {
    await axios.delete(`/api/projects/${projectId}`, { withCredentials: true });
    alert('Проект успешно удалён.');
    window.location.href = '/'; // возвращаемся на главную страницу
  } catch (e) {
    alert(e?.response?.data?.message || 'Ошибка при удалении проекта');
  }
};


const showWatcherModal = ref(false)
const selectedWatcher = ref(null)


const openAddWatcher = async () => {
  const { data } = await axios.get(`/api/projects/${projectId}/employees`)
  // ❌ исключаем владельца компании
  employees.value = data.filter(u => u.id !== project.value.company.user_id)
  showWatcherModal.value = true
}


const addWatcher = async () => {
  if (!selectedWatcher.value) return
  await axios.post(`/api/projects/${projectId}/watchers`, {
    user_id: selectedWatcher.value
  })
  showWatcherModal.value = false
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
    <div class="flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
      <!-- Левая часть -->
      <div class="flex-1">
        <h1 class="text-2xl sm:text-3xl font-semibold">
          {{ project?.name ?? 'Загрузка…' }}
        </h1>
        <div class="mt-3 flex flex-wrap items-center gap-2 text-sm">
          <span class="px-2 py-1 rounded-full bg-white/20">
            Компания: <b>{{ project?.company?.name ?? '—' }}</b>
          </span>




          <span
            v-for="m in project?.managers || []"
            :key="m.id"
            class="px-2 py-1 rounded-full bg-white/20"
          >
            Руководитель: <b>{{ m.name }}</b>
          </span>
          <span v-if="!project?.managers?.length" class="px-2 py-1 rounded-full bg-white/20">
            Руководители: <b>—</b>
          </span>

          <span
  v-for="w in project?.watchers || []"
  :key="w.id"
  class="px-2 py-1 rounded-full bg-white/20"
>
  Наблюдатель: <b>{{ w.name }}</b>
</span>

<span
  v-if="!project?.watchers?.length"
  class="px-2 py-1 rounded-full bg-white/20"
>
  Наблюдатели: <b>—</b>
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

      <!-- Правая часть — блок кнопок -->
      <div class="flex flex-col sm:items-end gap-3">
        <!-- Основные действия -->
        <div class="flex flex-wrap justify-end gap-2">
          <button
            v-if="canCreateTask"
            @click="openCreateTask"
            class="rounded-xl bg-emerald-400 hover:bg-emerald-500 text-gray-900 px-4 py-2 font-medium shadow-sm"
          >
            + Задача
          </button>
          <button
            v-if="canEditName"
            @click="showNameModal = true"
            class="rounded-xl bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 font-medium shadow-sm"
          >
            ✏️ Изменить название
          </button>
          <button
            v-if="isCompanyOwner"
            @click="deleteProject"
            class="rounded-xl bg-rose-500 hover:bg-rose-600 text-white px-4 py-2 font-medium shadow-sm"
          >
            🗑 Удалить проект
          </button>
        </div>

        <!-- Дополнительные настройки -->
        <div class="flex flex-wrap justify-end gap-2 text-sm">
          <button
            v-if="canEditBudget"
            @click="showBudgetModal = true"
            class="rounded-xl bg-amber-400/90 hover:bg-amber-500 text-gray-900 px-3 py-1.5"
          >
            💰 Бюджет
          </button>
          <button
            v-if="canEditDescription"
            @click="showDescriptionModal = true"
            class="rounded-xl bg-white/90 hover:bg-white text-gray-900 px-3 py-1.5"
          >
            📝 Описание
          </button>
          <button
            v-if="canManageManagers"
            @click="openAddManager"
            class="rounded-xl bg-emerald-500/90 hover:bg-emerald-600 text-white px-3 py-1.5"
          >
            ➕ Добавить руководителя
          </button>
          <button
            v-if="canManageManagers && (project?.managers?.length || 0) > 0"
            @click="openReplaceManager"
            class="rounded-xl bg-amber-500/90 hover:bg-amber-600 text-white px-3 py-1.5"
          >
            🔄 Изменить руководителя
          </button>
          <button
  v-if="canManageManagers"
  @click="openAddWatcher"
  class="rounded-xl bg-emerald-500/90 hover:bg-emerald-600 text-white px-4 py-2 font-medium"
>
  👁 Добавить наблюдателя
</button>

        </div>
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
    @click="$inertia.visit(`/tasks/${t.id}`)"
    class="group rounded-xl border p-4 bg-white dark:bg-gray-800 hover:shadow-md transition cursor-pointer"
    :class="[
      t.progress === 100
        ? 'border-emerald-500 ring-1 ring-emerald-300 bg-emerald-50 dark:bg-emerald-900/20' // ✅ завершено
        : t.progress >= 50
          ? 'border-amber-400 ring-1 ring-amber-300 bg-amber-50 dark:bg-amber-900/20'      // ⚠️ средний прогресс
          : 'border-gray-400 ring-1 ring-gray-300 bg-gray-50 dark:bg-rose-900/20'          // 🔴 низкий прогресс
    ]"
  >
    <div class="flex items-start justify-between gap-3">
      <h3 class="text-base font-semibold text-gray-900 dark:text-white leading-snug">
        {{ t.title }}
      </h3>
      <span
        class="px-2 py-1 text-xs rounded-full ring-1"
        :class="priorityBadge(t.priority)"
      >
        {{ t.priority === 'high' ? 'Высокая' : t.priority === 'medium' ? 'Средняя' : 'Обычная' }}
      </span>
    </div>

    <div class="mt-2 text-sm text-gray-600 dark:text-gray-300 space-y-1.5">
      <div class="flex items-center gap-2">
        <svg class="w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor">
          <path d="M7 11h5V6H7v5zm0 7h5v-5H7v5zm7 0h5v-5h-5v5zM14 6v5h5V6h-5z"/>
        </svg>
        <span>С {{ t.start_date }} по {{ t.due_date }}</span>
      </div>

      <div class="flex items-center gap-2">
        <svg class="w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor">
          <path d="M12 12c2.2 0 4-1.79 4-4s-1.8-4-4-4-4 1.79-4 4 1.8 4 4 4zm0 2c-3.33 0-10 1.67-10 5v3h20v-3c0-3.33-6.67-5-10-5z"/>
        </svg>
        <span>
          От: <b>{{ t.creator?.name ?? '—' }}</b> → 
          Кому: 
          <b v-if="t.executors?.length">
            {{ t.executors.map(e => e.name).join(', ') }}
          </b>
          <b v-else>—</b>
        </span>
      </div>

      <div class="flex items-center gap-2">
        <svg class="w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor">
          <path d="M12 2l4 7H8l4-7zm0 20l-4-7h8l-4 7zM2 12l7-4v8l-7-4zm20 0l-7 4V8l7 4z"/>
        </svg>
        <span>
          Ответственные:
          <b v-if="t.responsibles?.length">
            {{ t.responsibles.map(r => r.name).join(', ') }}
          </b>
          <b v-else>—</b>
        </span> <br/>
        
      </div>
      <span>выполнено: {{ t.progress }}%</span>
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
          <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor">
            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12V8l-4-6zM6 22V4h7v5h5v13H6z"/>
          </svg>
          {{ f.file_path.split('/').pop() }}
        </a>
      </div>
    </div>
  </div>
</div>

          </div>
        </div>


<!-- <div class="mt-12">
  <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
    Файлы задач
  </h2>

  <div v-if="project?.tasks?.length" class="space-y-6">
    <div v-for="t in project.tasks" :key="t.id" class="border rounded-xl p-4 bg-white dark:bg-gray-800">
      <h3 class="font-medium text-lg text-gray-800 dark:text-gray-200 mb-2">
        {{ t.title }}
      </h3>

      <ul v-if="t.files && t.files.length" class="space-y-1">
        <li v-for="f in t.files" :key="f.id" class="text-sm text-blue-600 dark:text-blue-400">
          <a :href="`/storage/${f.file_path}`" target="_blank" class="hover:underline">
            {{ f.file_path.split('/').pop() }}
          </a>
        </li>
      </ul>
      <p v-else class="text-sm text-gray-500">Нет файлов</p>
    </div>
  </div>

  <p v-else class="text-gray-500">Задачи пока не созданы</p>
</div> -->

    



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


<!-- Подпроекты -->
<!-- <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8 pb-10">
  <h2 class="text-lg font-bold mb-4 text-slate-500">Подпроекты <span style="color: red;">(доработка) </span></h2>

 
  <div
    v-if="isAdmin || isCompanyOwner || isProjectManager"
    class="flex gap-2 items-center mb-6"
  >
    <input
      v-model="subprojectForm.title"
      type="text"
      placeholder="Название подпроекта"
      class="border p-2 rounded flex-1 dark:bg-gray-800 dark:text-white"
    />

   <select
  v-model="subprojectForm.responsible_id"
  class="border p-2 rounded dark:bg-gray-800 dark:text-white"
>
  <option :value="null">--Без ответственного--</option>
  <option v-for="emp in employees" :key="emp.id" :value="emp.id">
    {{ emp.name }}
  </option>
</select>

    <button disabled
      @click="createSubproject"
      :disabled="creatingSubproject"
      class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
    >
      {{ creatingSubproject ? 'Создаём...' : 'Добавить подпроект' }}
    </button>
  </div>

  <p v-if="subprojectError" class="text-red-600 mb-4">{{ subprojectError }}</p>


  <ul v-if="project?.subprojects?.length" class="space-y-3">
    <li
      v-for="sub in project.subprojects"
      :key="sub.id"
      class="p-4 border rounded bg-white dark:bg-gray-800 shadow"
    >
      <div class="flex justify-between items-center">
        <div>
          <a
            :href="`/subprojects/${sub.id}`"
            class="text-blue-600 underline font-medium"
          >
            {{ sub.title }}
          </a>
          <p class="text-sm text-gray-500 dark:text-gray-400">
            Ответственный: {{ sub.responsible?.name ?? 'не назначен' }}
          </p>
        </div>
        <span
          class="text-xs px-2 py-1 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300"
        >
          {{ sub.tasks_count ?? 0 }} задач
        </span>
      </div>
    </li>
  </ul>
  <p v-else class="text-gray-500 dark:text-gray-400">Подпроектов пока нет</p>
</div> -->

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
  <!-- Исполнители -->
  <div>
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Исполнители</label>
    <div class="space-y-2 max-h-40 overflow-y-auto p-2 border rounded-xl bg-white dark:bg-gray-700">
      <div v-for="u in employees" :key="u.id" class="flex items-center space-x-2">
        <input
          type="checkbox"
          :id="`executor-${u.id}`"
          :value="u.id"
          v-model="taskForm.executor_ids"
          class="h-4 w-4 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500"
        />
        <label :for="`executor-${u.id}`" class="text-sm text-gray-700 dark:text-gray-300">
          {{ u.name }}
        </label>
      </div>
    </div>
  </div>

  <!-- Ответственные -->
  <div>
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ответственные</label>
    <div class="space-y-2 max-h-40 overflow-y-auto p-2 border rounded-xl bg-white dark:bg-gray-700">
      <div v-for="u in employees" :key="u.id" class="flex items-center space-x-2">
        <input
          type="checkbox"
          :id="`responsible-${u.id}`"
          :value="u.id"
          v-model="taskForm.responsible_ids"
          class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
        />
        <label :for="`responsible-${u.id}`" class="text-sm text-gray-700 dark:text-gray-300">
          {{ u.name }}
        </label>
      </div>
    </div>
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


<div v-if="showNameModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
  <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900">
    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Изменить название проекта</h3>

    <input
      v-model="nameForm.name"
      type="text"
      class="w-full border rounded-lg px-3 py-2 dark:bg-gray-800 dark:text-white"
    />
    <p v-if="nameError" class="mt-2 text-sm text-rose-600">{{ nameError }}</p>

    <div class="flex justify-end gap-2 mt-4">
      <button @click="showNameModal=false" class="px-4 py-2 rounded bg-gray-500 text-white">Отмена</button>
      <button @click="saveName" :disabled="savingName" class="px-4 py-2 rounded bg-blue-600 text-white">
        {{ savingName ? 'Сохраняю…' : 'Сохранить' }}
      </button>
    </div>
  </div>
</div>


<!-- === МОДАЛКА: Добавить руководителя === -->
<dialog v-if="showAddManagerModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
  <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 w-full max-w-md">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Добавить руководителя</h3>

    <label class="block text-sm mb-2 text-gray-700 dark:text-gray-300">Выберите сотрудника:</label>
    <select
      v-model="addManagerForm.user_id"
      class="w-full border rounded-md px-3 py-2 bg-gray-50 dark:bg-gray-700 dark:text-white"
    >
      <option disabled value="">-- Выберите --</option>
      <option v-for="e in employees" :key="e.id" :value="e.id">
        {{ e.name }} — {{ e.email }}
      </option>
    </select>

    <p v-if="addManagerError" class="text-red-500 text-sm mt-2">{{ addManagerError }}</p>

    <div class="flex justify-end gap-2 mt-5">
      <button @click="showAddManagerModal = false" class="px-4 py-2 rounded-md bg-gray-200 dark:bg-gray-700 dark:text-white">Отмена</button>
      <button
        @click="addManager"
        :disabled="addingManager"
        class="px-4 py-2 rounded-md bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-50"
      >
        {{ addingManager ? 'Добавляем...' : 'Добавить' }}
      </button>
    </div>
  </div>
</dialog>


<!-- === МОДАЛКА: Изменить руководителя === -->
<dialog v-if="showReplaceManagerModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
  <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 w-full max-w-md">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Изменить руководителя</h3>

    <label class="block text-sm mb-1 text-gray-700 dark:text-gray-300">Текущий руководитель:</label>
    <select
      v-model="replaceManagerForm.old_manager_id"
      class="w-full border rounded-md px-3 py-2 bg-gray-50 dark:bg-gray-700 dark:text-white mb-3"
    >
      <option disabled value="">-- Выберите --</option>
      <option v-for="m in project.managers" :key="m.id" :value="m.id">
        {{ m.name }}
      </option>
    </select>

    <label class="block text-sm mb-1 text-gray-700 dark:text-gray-300">Новый руководитель:</label>
    <select
      v-model="replaceManagerForm.new_manager_id"
      class="w-full border rounded-md px-3 py-2 bg-gray-50 dark:bg-gray-700 dark:text-white"
    >
      <option disabled value="">-- Выберите --</option>
      <option v-for="e in employees" :key="e.id" :value="e.id">
        {{ e.name }} — {{ e.email }}
      </option>
    </select>

    <p v-if="replaceManagerError" class="text-red-500 text-sm mt-2">{{ replaceManagerError }}</p>

    <div class="flex justify-end gap-2 mt-5">
      <button @click="showReplaceManagerModal = false" class="px-4 py-2 rounded-md bg-gray-200 dark:bg-gray-700 dark:text-white">Отмена</button>
      <button
        @click="replaceManager"
        :disabled="replacingManager"
        class="px-4 py-2 rounded-md bg-amber-600 text-white hover:bg-amber-700 disabled:opacity-50"
      >
        {{ replacingManager ? 'Сохраняем...' : 'Изменить' }}
      </button>
    </div>
  </div>
</dialog>

<!-- Добавить наблюдателя -->
<div v-if="showWatcherModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
  <div class="bg-white dark:bg-gray-800 p-6 rounded-xl w-full max-w-md">
    <h2 class="text-lg font-semibold mb-3">Добавить наблюдателя</h2>
    <select v-model="selectedWatcher" class="w-full border rounded p-2 mb-4">
      <option disabled value="">Выберите сотрудника</option>
      <option v-for="u in employees" :key="u.id" :value="u.id">
        {{ u.name }}
      </option>
    </select>
    <div class="flex justify-end gap-2">
      <button @click="showWatcherModal = false" class="px-3 py-1 rounded bg-gray-200 dark:bg-gray-700">Отмена</button>
      <button @click="addWatcher" class="px-3 py-1 rounded bg-emerald-600 text-white">Добавить</button>
    </div>
  </div>
</div>





  </AuthenticatedLayout>
</template>
