<script setup>
import { ref, onMounted, computed } from 'vue'
import { usePage, Head } from '@inertiajs/vue3'
import axios from 'axios'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

import TaskChat from '@/Components/TaskChat.vue'

const { props } = usePage()
const taskId = props.id
const user = props.auth?.user

// state
const loading = ref(true)
const task = ref(null)
const submitLoading = ref(false)
const errorText = ref('')

// files
const selectedFiles = ref(null)
const handleFileChange = (e) => { selectedFiles.value = e.target.files }

// modal: subtask
const showSubtaskModal = ref(false)
const companyEmployees = ref([])
const subtaskForm = ref({
  title: '',
  executor_id: '',
  start_date: new Date().toISOString().slice(0, 10),
  due_date: '',
})

// permissions
const canCreateSubtask = computed(() => {
  if (!task.value || !user) return false
  return task.value.responsible?.id === user.id || task.value.project?.manager?.id === user.id
})

// helpers
const priorityBadge = (p) =>
  p === 'high'
    ? 'bg-rose-100 text-rose-700 ring-1 ring-rose-200'
    : p === 'medium'
    ? 'bg-amber-100 text-amber-700 ring-1 ring-amber-200'
    : 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200'

const priorityLabel = (p) => (p === 'high' ? 'Высокая' : p === 'medium' ? 'Средняя' : 'Обычная')

// api
const fetchTask = async () => {
  loading.value = true
  try {
    const { data } = await axios.get(`/api/tasks/${taskId}`)
    task.value = data
  } finally {
    loading.value = false
  }
}

const updateProgress = async (value) => {
  try {
    const { data } = await axios.patch(`/api/tasks/${taskId}/progress`, { progress: value })
    task.value.progress = data.progress
  } catch (e) {
    alert('Недостаточно прав для обновления прогресса.')
  }
}

const uploadFiles = async () => {
  if (!selectedFiles.value?.length) return alert('Выберите файлы')
  const formData = new FormData()
  for (let i = 0; i < selectedFiles.value.length; i++) formData.append('files[]', selectedFiles.value[i])

  try {
    await axios.post(`/api/tasks/${taskId}/files`, formData, { headers: { 'Content-Type': 'multipart/form-data' } })
    selectedFiles.value = null
    await fetchTask()
  } catch (e) {
    alert('Ошибка при загрузке файлов')
  }
}

const openSubtaskModal = async () => {
  const { data } = await axios.get(`/api/projects/${task.value.project.id}/employees`)
  companyEmployees.value = data
  showSubtaskModal.value = true
}

const createSubtask = async () => {
  submitLoading.value = true
  errorText.value = ''
  try {
    await axios.post(`/api/tasks/${taskId}/subtasks`, { ...subtaskForm.value, task_id: taskId })
    showSubtaskModal.value = false
    subtaskForm.value = {
      title: '',
      executor_id: '',
      start_date: new Date().toISOString().slice(0, 10),
      due_date: '',
    }
    await fetchTask()
  } catch (e) {
    errorText.value = e?.response?.data?.message || 'Ошибка при создании подзадачи'
  } finally {
    submitLoading.value = false
  }
}

const hasOpenSubtasks = computed(() =>
  (task.value?.subtasks || []).some(st => !st.completed)
)

const canFinish = computed(() =>
  (task.value?.progress === 100) && !task.value?.completed && !hasOpenSubtasks.value
)

const finishTask = async () => {
  try {
    const { data } = await axios.patch(`/api/tasks/${taskId}/complete`)
    task.value = data.task
  } catch (e) {
    // покажем серверные причины (прогресс не 100%, есть незавершённые подзадачи, нет прав)
    const msg = e?.response?.data?.message || 'Не удалось завершить задачу'
    alert(msg)
  }
}


const canManageTask = computed(() => {
  const userId = props.auth?.user?.id
  if (!userId || !task.value) return false
  return (
    userId === task.value.executor_id ||
    userId === task.value.responsible_id
  )
})



onMounted(fetchTask)
</script>

<template>
  <Head :title="task?.title ? `Задача — ${task.title}` : 'Задача'" />
  <AuthenticatedLayout>
    <!-- HERO -->
    <div class="relative overflow-hidden">
      <div class="absolute inset-0 bg-gradient-to-r from-sky-600 via-indigo-600 to-fuchsia-600 opacity-90"></div>
      <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 text-white">
        <div class="flex items-start gap-4">
          <div class="flex-1">
            <h1 class="text-2xl sm:text-3xl font-semibold">
              {{ task?.title ?? 'Загрузка…' }}
            </h1>
            <div class="mt-2 flex flex-wrap items-center gap-2 text-sm">
              <span class="px-2 py-1 rounded-full bg-white/20">
                Проект: <b>{{ task?.project?.name ?? '—' }}</b>
              </span>
              <span class="px-2 py-1 rounded-full bg-white/20">
                Компания: <b>{{ task?.project?.company?.name ?? '—' }}</b>
              </span>
              <span class="px-2 py-1 rounded-full bg-white/20">
                От: <b>{{ task?.creator?.name ?? '—' }}</b>
              </span>
              <span class="px-2 py-1 rounded-full bg-white/20">
                Кому: <b>{{ task?.executor?.name ?? '—' }}</b>
              </span>
              <span class="px-2 py-1 rounded-full bg-white/20">
                Ответственный: <b>{{ task?.responsible?.name ?? '—' }}</b>
              </span>
              <span v-if="task" class="px-2 py-1 rounded-full ring-1 bg-white text-gray-900" :class="priorityBadge(task.priority)">
                Приоритет: <b>{{ priorityLabel(task.priority) }}</b>
              </span>
            </div>
          </div>

          <div class="hidden sm:flex items-center gap-3">
            <a v-if="task?.project?.id" :href="`/projects/${task.project.id}`"
               class="rounded-xl bg-white text-gray-900 hover:bg-white/90 px-4 py-2 font-medium">
              К проекту
            </a>
          </div>


<div v-if="canManageTask" class="mt-3" >
    <button
      v-if="canFinish"
      @click="finishTask"
      class="px-4 py-2 rounded bg-emerald-600 text-white hover:bg-emerald-700"
      title="Завершить задачу"
    >
      Завершить задачу
    </button>

    <!-- Подсказки, почему кнопки нет -->
    <div v-else-if="(task?.progress === 100) && !task?.completed" class="text-xs text-amber-600 mt-2">
      <span v-if="hasOpenSubtasks">
        Есть незавершённые подзадачи — завершите их, чтобы закрыть задачу.
      </span>
    </div>

    <div v-if="task?.completed" class="text-sm mt-2 text-emerald-700">
      Завершена {{ task?.completed_at || '' }}
    </div>
  </div>


        </div>
      </div>
    </div>

    <!-- BODY -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-4 pb-10" style="    margin-top: 3%;">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Основная колонка -->
        <div class="lg:col-span-2 space-y-4">

          <!-- Даты и прогресс -->
          <div class="rounded-2xl border bg-white dark:bg-gray-800 p-5">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div>
                <div class="text-xs text-gray-500">Дата начала</div>
                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ task?.start_date ?? '—' }}</div>
              </div>
              <div>
                <div class="text-xs text-gray-500">Дата окончания</div>
                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ task?.due_date ?? '—' }}</div>
              </div>
              <div>
                <div class="text-xs text-gray-500">Прогресс</div>
                <div class="mt-2">
                  <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div class="h-2 bg-emerald-500" :style="{ width: (task?.progress || 0) + '%' }"></div>
                  </div>
                  <div class="mt-1 text-xs text-gray-600 dark:text-gray-300">Выполнено {{ task?.progress ?? 0 }}%</div>
                </div>
                <div class="flex mt-3 gap-1">
                  <button
                    v-for="n in 11"
                    :key="n"
                    @click="updateProgress((n-1)*10)"
                    class="flex-1 h-5 rounded bg-gray-200 dark:bg-gray-700 hover:bg-gray-300"
                    :class="{ '!bg-emerald-500': (task?.progress || 0) >= (n-1)*10 }"
                    :title="`${(n-1)*10}%`"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Файлы -->
          <div class="rounded-2xl border bg-white dark:bg-gray-800 p-5">
            <div class="flex items-center justify-between">
              <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Файлы</h2>
              <div class="flex items-center gap-2">
                <input type="file" multiple @change="handleFileChange" accept=".pdf,.doc,.docx,.xls,.xlsx"
                       class="text-sm text-gray-600 dark:text-gray-300" />
                <button @click="uploadFiles" class="rounded-xl bg-indigo-600 text-white px-4 py-2 hover:bg-indigo-700">
                  Загрузить
                </button>
              </div>
            </div>

            <!-- Скелетон -->
            <div v-if="loading" class="mt-4 grid grid-cols-2 gap-2">
              <div v-for="i in 4" :key="i" class="h-8 rounded-lg bg-gray-200 dark:bg-gray-700 animate-pulse" />
            </div>

            <div v-else class="mt-3">
              <div v-if="!task?.files?.length" class="text-sm text-gray-600 dark:text-gray-300">
                Файлы не прикреплены.
              </div>
              <div v-else class="flex flex-wrap gap-2">
                <a
                  v-for="f in task.files"
                  :key="f.id"
                  :href="`/storage/${f.file_path}`"
                  target="_blank"
                  class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-100"
                >
                  <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12V8l-4-6zM6 22V4h7v5h5v13H6z"/></svg>
                  {{ f.file_path.split('/').pop() }}
                </a>
              </div>
            </div>
          </div>

          <!-- Подзадачи -->
          <div class="rounded-2xl border bg-white dark:bg-gray-800 p-5">
            <div class="flex items-center justify-between mb-3">
              <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Подзадачи</h2>
              <button
                v-if="canCreateSubtask"
                @click="openSubtaskModal"
                class="rounded-xl bg-emerald-600 text-white px-4 py-2 hover:bg-emerald-700"
              >
                + Подзадача
              </button>
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
            <div v-else-if="!task?.subtasks?.length" class="text-sm text-gray-600 dark:text-gray-300">
              Подзадач пока нет.
            </div>

            <!-- Список -->
            <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div
                v-for="s in task.subtasks"
                :key="s.id"
                class="group rounded-xl border p-4 bg-white dark:bg-gray-800 hover:shadow-md transition cursor-pointer"
                @click="$inertia.visit(`/subtasks/${s.id}`)"
              >
                <div class="flex items-start justify-between gap-3">
                  <h3 class="text-base font-semibold text-gray-900 dark:text-white leading-snug">{{ s.title }}</h3>
                </div>
                <div class="mt-2 text-sm text-gray-600 dark:text-gray-300 space-y-1.5">
                  <div>Исполнитель: <b>{{ s.executor?.name ?? '—' }}</b></div>
                  <div>Сроки: {{ s.start_date }} — {{ s.due_date }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Боковая панель -->
        <div class="space-y-4">
          <div class="rounded-2xl border bg-white dark:bg-gray-800 p-5">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Кратко о задаче</h3>
            <dl class="mt-3 space-y-2 text-sm text-gray-700 dark:text-gray-300">
              <div class="flex justify-between"><dt>Автор</dt><dd class="font-medium">{{ task?.creator?.name ?? '—' }}</dd></div>
              <div class="flex justify-between"><dt>Исполнитель</dt><dd class="font-medium">{{ task?.executor?.name ?? '—' }}</dd></div>
              <div class="flex justify-between"><dt>Ответственный</dt><dd class="font-medium">{{ task?.responsible?.name ?? '—' }}</dd></div>
              <div class="flex justify-between"><dt>Проект</dt><dd class="font-medium">{{ task?.project?.name ?? '—' }}</dd></div>
              <div class="flex justify-between"><dt>Компания</dt><dd class="font-medium">{{ task?.project?.company?.name ?? '—' }}</dd></div>
              <div class="flex justify-between"><dt>Приоритет</dt><dd>
                <span v-if="task" class="px-2 py-1 text-xs rounded-full ring-1" :class="priorityBadge(task.priority)">
                  {{ priorityLabel(task.priority) }}
                </span>
              </dd></div>
            </dl>
          </div>
        </div>


<div class="space-y-4" v-if="task">
  <div class="rounded-2xl border bg-white dark:bg-gray-800 p-5">
    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Кратко о задаче</h3>

    <!-- чат появится только когда task уже есть -->
    <TaskChat :task-id="task.id" :can-chat="true" />
  </div>
</div>

<!-- можно оставить запасной скелетон/лоадер -->
<div v-else class="text-sm text-gray-500 dark:text-gray-400">Загрузка…</div>

      </div>
    </div>

    <!-- Модалка: Новая подзадача -->
    <div v-if="showSubtaskModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/50" @click="showSubtaskModal=false"></div>
      <div class="relative w-full max-w-md bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Новая подзадача</h3>
          <button @click="showSubtaskModal=false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">✕</button>
        </div>

        <p v-if="errorText" class="mt-3 text-sm text-rose-600">{{ errorText }}</p>

        <form class="mt-4 space-y-4" @submit.prevent="createSubtask">
          <div>
            <label class="block text-sm mb-1 text-gray-700 dark:text-gray-300">Название</label>
            <input v-model="subtaskForm.title" class="w-full rounded-xl border px-3 py-2 bg-white dark:bg-gray-700 dark:text-white" required />
          </div>
          <div>
            <label class="block text-sm mb-1 text-gray-700 dark:text-gray-300">Исполнитель</label>
            <select v-model="subtaskForm.executor_id" class="w-full rounded-xl border px-3 py-2 bg-white dark:bg-gray-700 dark:text-white" required>
              <option disabled value="">Выберите сотрудника</option>
              <option v-for="u in companyEmployees" :key="u.id" :value="u.id">{{ u.name }}</option>
            </select>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm mb-1 text-gray-700 dark:text-gray-300">Начало</label>
              <input type="date" v-model="subtaskForm.start_date" class="w-full rounded-xl border px-3 py-2 bg-white dark:bg-gray-700 dark:text-white" required />
            </div>
            <div>
              <label class="block text-sm mb-1 text-gray-700 dark:text-gray-300">Окончание</label>
              <input type="date" v-model="subtaskForm.due_date" class="w-full rounded-xl border px-3 py-2 bg-white dark:bg-gray-700 dark:text-white" required />
            </div>
          </div>

          <div class="flex justify-end gap-2">
            <button type="button" @click="showSubtaskModal=false" class="px-4 py-2 rounded-xl border bg-white dark:bg-gray-700 dark:text-white">Отмена</button>
            <button type="submit" :disabled="submitLoading" class="px-4 py-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-60">
              <span v-if="!submitLoading">Создать</span>
              <span v-else>Сохраняю…</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
