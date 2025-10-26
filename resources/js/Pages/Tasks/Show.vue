<script setup>
import { ref, onMounted, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import TaskChat from '@/Components/TaskChat.vue'
import TaskChecklists from '@/Components/TaskChecklists.vue'

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
  responsible_id: '',
  start_date: new Date().toISOString().slice(0, 10),
  due_date: '',
})

// modal: edit task
const showEditModal = ref(false)
const editForm = ref({
  title: '',
  start_date: '',
  due_date: '',
})

// permissions
const canCreateSubtask = computed(() => {
  if (!task.value || !user) return false
  return (
    (task.value.responsibles || []).some(r => r.id === user.id) ||
    (task.value.project?.managers || []).some(m => m.id === user.id)||
    isProjectExecutor.value
  )
})

const priorityBadge = (p) =>
  p === 'high'
    ? 'bg-rose-100 text-rose-700 ring-1 ring-rose-200'
    : p === 'medium'
    ? 'bg-amber-100 text-amber-700 ring-1 ring-amber-200'
    : 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200'

const priorityLabel = (p) => (p === 'high' ? 'Высокая' : p === 'medium' ? 'Средняя' : 'Обычная')

// === API ===
const fetchTask = async () => {
  loading.value = true
  try {
    const { data } = await axios.get(`/api/tasks/${taskId}`, { withCredentials: true })
    task.value = data
    editForm.value = {
      title: data.title || '',
      start_date: data.start_date || '',
      due_date: data.due_date || '',
    }
  } finally {
    loading.value = false
  }
}

const updateTask = async () => {
  try {
    const { data } = await axios.put(`/api/tasks/${taskId}`, editForm.value, { withCredentials: true })
    task.value = data.task
    showEditModal.value = false
  } catch (e) {
    alert(e?.response?.data?.message || 'Ошибка при обновлении задачи')
  }
}

const updateProgress = async (value) => {
  try {
    const { data } = await axios.patch(`/api/tasks/${taskId}/progress`, { progress: value }, { withCredentials: true })
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
    await axios.post(`/api/tasks/${taskId}/files`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
      withCredentials: true,
    })
    selectedFiles.value = null
    await fetchTask()
  } catch (e) {
    alert('Ошибка при загрузке файлов')
  }
}

const openSubtaskModal = async () => {
  const { data } = await axios.get(`/api/projects/${task.value.project.id}/employees`, { withCredentials: true })
  companyEmployees.value = data
  showSubtaskModal.value = true
}

// const createSubtask = async () => {
//   submitLoading.value = true
//   errorText.value = ''
//   try {
//     await axios.post(`/api/tasks/${taskId}/subtasks`, { ...subtaskForm.value, task_id: taskId }, { withCredentials: true })
//     showSubtaskModal.value = false
//     subtaskForm.value = {
//       title: '',
//       executor_id: '',
//       responsible_id: '',
//       start_date: new Date().toISOString().slice(0, 10),
//       due_date: '',
//     }
//     await fetchTask()
//   } catch (e) {
//     errorText.value = e?.response?.data?.message || 'Ошибка при создании подзадачи'
//   } finally {
//     submitLoading.value = false
//   }
// }

const createSubtask = async () => {
  submitLoading.value = true
  errorText.value = ''

  try {
    const payload = {
      title: subtaskForm.value.title,
      executor_id: Array.isArray(subtaskForm.value.executor_id)
        ? subtaskForm.value.executor_id
        : [subtaskForm.value.executor_id],
      responsible_id: Array.isArray(subtaskForm.value.responsible_id)
        ? subtaskForm.value.responsible_id
        : [subtaskForm.value.responsible_id],
      start_date: subtaskForm.value.start_date,
      due_date: subtaskForm.value.due_date,
    }

    await axios.post(`/api/tasks/${taskId}/subtasks`, payload)
    showSubtaskModal.value = false
    await fetchTask()
  } catch (e) {
    errorText.value = e?.response?.data?.message || 'Ошибка при создании подзадачи'
  } finally {
    submitLoading.value = false
  }
}

const isProjectExecutor = computed(() =>
  task.value?.project?.executors?.some(e => e.id === user?.id)
)


const hasOpenSubtasks = computed(() =>
  (task.value?.subtasks || []).some(st => !st.completed)
)

const canFinish = computed(() =>
  (task.value?.progress === 100) && !task.value?.completed && !hasOpenSubtasks.value
)

const finishTask = async () => {
  try {
    const { data } = await axios.patch(`/api/tasks/${taskId}/complete`, {}, { withCredentials: true })
    task.value = data.task
  } catch (e) {
    const msg = e?.response?.data?.message || 'Не удалось завершить задачу'
    alert(msg)
  }
}

const canManageTask = computed(() => {
  const userId = props.auth?.user?.id
  if (!userId || !task.value) return false
  return (
    (task.value.executors || []).some(e => e.id === userId) ||
    (task.value.responsibles || []).some(r => r.id === userId)
  )
})

const canUploadFiles = computed(() => {
  if (!task.value || !user) return false
  return (
    (task.value.executors || []).some(e => e.id === user.id) ||
    (task.value.responsibles || []).some(r => r.id === user.id) ||
    (task.value.project?.executors || []).some(e => e.id === user.id) ||
    user.id === task.value.project?.company?.user_id
  )
})

const canDeleteTask = computed(() => {
  if (!task.value || !user) return false
  return (
    user.id === task.value.project?.company?.user_id || // владелец компании
    (task.value.project?.managers || []).some(m => m.id === user.id) ||// менеджер проекта
    isProjectExecutor.value  
  
  )
})


const canUpdate = computed(() => {
  if (!task.value || !user) return false
  return (
    user.id === task.value.project?.company?.user_id || // владелец компании
    (task.value.project?.managers || []).some(m => m.id === user.id)||
    isProjectExecutor.value  // менеджер проекта
  
  )
})



// const deleteTask = async () => {
//   if (!confirm('Удалить задачу и все связанные подзадачи и файлы?')) return

//   try {
//     await axios.delete(`/api/tasks/${taskId}`, { withCredentials: true })
//     alert('Задача успешно удалена.')
//     window.history.back() // вернуться на предыдущую страницу
//   } catch (e) {
//     alert(e?.response?.data?.message || 'Ошибка при удалении задачи')
//   }
// }


const showDeleteModal = ref(false)
const deleting = ref(false)
const deleteError = ref('')

const confirmDeleteTask = async () => {
  deleteError.value = ''
  deleting.value = true
  try {
    await axios.delete(`/api/tasks/${taskId}`, { withCredentials: true })
    showDeleteModal.value = false
    alert('Задача успешно удалена.')
    window.history.back() // вернуться на предыдущую страницу
  } catch (e) {
    deleteError.value = e?.response?.data?.message || 'Ошибка при удалении задачи'
  } finally {
    deleting.value = false
  }
}



const showWatcherModal = ref(false)
const selectedWatcher = ref(null)

const openWatcherModal = async () => {
  const { data } = await axios.get(`/api/projects/${task.value.project.id}/employees`, { withCredentials: true })
  companyEmployees.value = data
  showWatcherModal.value = true
}

const addWatcher = async () => {
  if (!selectedWatcher.value) return
  try {
    const { data } = await axios.post(`/api/tasks/${taskId}/watchers`, {
      user_id: selectedWatcher.value
    }, { withCredentials: true })

    task.value.watchers = data.watchers
    showWatcherModal.value = false
  } catch (e) {
    alert(e?.response?.data?.message || 'Ошибка при добавлении наблюдателя')
  }
}


// управление участниками
const showExecutorModal = ref(false)
const showResponsibleModal = ref(false)


const selectedUser = ref(null)


const canManageMembers = computed(() => {
  if (!task.value || !user) return false
  return (
    user.id === task.value.project?.company?.user_id ||
    (task.value.project?.managers || []).some(m => m.id === user.id) ||
    isProjectExecutor.value // 🆕
  )
})

// загрузка сотрудников компании
const fetchEmployees = async () => {
  const { data } = await axios.get(`/api/projects/${task.value.project.id}/employees`)
  companyEmployees.value = data
}

// открыть модалки
const openChangeExecutor = async () => {
  await fetchEmployees()
  selectedUser.value = null
  showExecutorModal.value = true
}

const openChangeResponsible = async () => {
  await fetchEmployees()
  selectedUser.value = null
  showResponsibleModal.value = true
}



const changeExecutor = async () => {
  if (!selectedUser.value) return alert('Выберите сотрудника')
  await axios.patch(`/api/tasks/${taskId}/executor`, { user_id: selectedUser.value })
  await fetchTask()
  showExecutorModal.value = false
}

const changeResponsible = async () => {
  if (!selectedUser.value) return alert('Выберите сотрудника')
  await axios.patch(`/api/tasks/${taskId}/responsible`, { user_id: selectedUser.value })
  await fetchTask()
  showResponsibleModal.value = false
}


const showAddExecutorModal = ref(false)
const showAddResponsibleModal = ref(false)
const selectedExecutors = ref([])
const selectedResponsibles = ref([])
const employees = ref([]) // список доступных сотрудников

// 🔹 открыть выбор исполнителей
const openAddExecutor = async () => {
  const { data } = await axios.get(`/api/projects/${task.value.project.id}/employees`)
  employees.value = data
  showAddExecutorModal.value = true
}

// 🔹 открыть выбор ответственных
const openAddResponsible = async () => {
  const { data } = await axios.get(`/api/projects/${task.value.project.id}/employees`)
  employees.value = data
  showAddResponsibleModal.value = true
}

// 🔹 добавить выбранных исполнителей
const addExecutors = async () => {
  if (!selectedExecutors.value.length) return
  await axios.post(`/api/tasks/${taskId}/executors/add`, {
    user_ids: selectedExecutors.value
  })
  await fetchTask() // обновляем задачу
  showAddExecutorModal.value = false
  selectedExecutors.value = []
}

// 🔹 добавить выбранных ответственных
const addResponsibles = async () => {
  if (!selectedResponsibles.value.length) return
  await axios.post(`/api/tasks/${taskId}/responsibles/add`, {
    user_ids: selectedResponsibles.value
  })
  await fetchTask()
  showAddResponsibleModal.value = false
  selectedResponsibles.value = []
}

const showManageMembers = ref(false)
const manageError = ref('')

// Удалить исполнителя
const removeExecutor = async (id) => {
  manageError.value = ''
  try {
    await axios.delete(`/api/tasks/${taskId}/executors`, { data: { user_id: id } })
    await fetchTask()
  } catch (e) {
    manageError.value =
      e.response?.data?.message ||
      Object.values(e.response?.data?.errors || {})[0]?.[0] ||
      'Ошибка при удалении исполнителя'
  }
}

// Удалить ответственного
const removeResponsible = async (id) => {
  manageError.value = ''
  try {
    await axios.delete(`/api/tasks/${taskId}/responsibles`, { data: { user_id: id } })
    await fetchTask()
  } catch (e) {
    manageError.value =
      e.response?.data?.message ||
      Object.values(e.response?.data?.errors || {})[0]?.[0] ||
      'Ошибка при удалении ответственного'
  }
}

// Удалить наблюдателя
const removeWatcher = async (id) => {
  manageError.value = ''
  try {
    await axios.delete(`/api/tasks/${taskId}/watchers`, { data: { user_id: id } })
    await fetchTask()
  } catch (e) {
    manageError.value =
      e.response?.data?.message ||
      Object.values(e.response?.data?.errors || {})[0]?.[0] ||
      'Ошибка при удалении наблюдателя'
  }
}



const replaceExecutorId = ref(null)
const newExecutorId = ref(null)
const replaceResponsibleId = ref(null)
const newResponsibleId = ref(null)
const executorError = ref('')
const responsibleError = ref('')

const availableExecutors = computed(() => {
  if (!companyEmployees.value.length || !task.value) return []
  const currentIds = (task.value.executors || []).map(e => e.id)
  return companyEmployees.value.filter(e => !currentIds.includes(e.id))
})

const availableResponsibles = computed(() => {
  if (!companyEmployees.value.length || !task.value) return []
  const currentIds = (task.value.responsibles || []).map(r => r.id)
  return companyEmployees.value.filter(e => !currentIds.includes(e.id))
})

const replaceExecutor = async () => {
  executorError.value = ''
  if (!replaceExecutorId.value || !newExecutorId.value) {
    executorError.value = 'Выберите кого и на кого заменить.'
    return
  }

  try {
    await axios.patch(`/api/tasks/${taskId}/executor`, {
      replace_user_id: replaceExecutorId.value,
      user_id: newExecutorId.value,
    })
    await fetchTask()
    showExecutorModal.value = false
  } catch (e) {
    executorError.value = e?.response?.data?.message || 'Ошибка при замене исполнителя.'
  }
}

const replaceResponsible = async () => {
  responsibleError.value = ''
  if (!replaceResponsibleId.value || !newResponsibleId.value) {
    responsibleError.value = 'Выберите кого и на кого заменить.'
    return
  }

  try {
    await axios.patch(`/api/tasks/${taskId}/responsible`, {
      replace_user_id: replaceResponsibleId.value,
      user_id: newResponsibleId.value,
    })
    await fetchTask()
    showResponsibleModal.value = false
  } catch (e) {
    responsibleError.value = e?.response?.data?.message || 'Ошибка при замене ответственного.'
  }
}




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
          <div >
            <h1 class="text-2xl sm:text-3xl font-semibold">
              {{ task?.title ?? 'Загрузка…' }}
            </h1>


<span
  v-if="task?.watcherstask?.some(w => w.id === $page.props.auth.user.id)"
  class="px-3 py-1 text-xs rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300"
>
  👁 Вы наблюдатель этой задачи
</span>


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
  Кому:
  <b>
    {{ task?.executors?.length
        ? task.executors.map(e => e.name).join(', ')
        : '—'
    }}
  </b>
</span>

<span class="px-2 py-1 rounded-full bg-white/20">
  Ответственный:
  <b>
    {{ task?.responsibles?.length
        ? task.responsibles.map(r => r.name).join(', ')
        : '—'
    }}
  </b>
</span>



<span v-if="task && task.watcherstask" class="px-2 py-1 rounded-full bg-white/20">
  Наблюдатели:
  <b>{{ task.watcherstask.map(w => w.name).join(', ') || '—' }}</b>
</span>





              <span v-if="task" class="px-2 py-1 rounded-full ring-1 bg-white text-gray-900" :class="priorityBadge(task.priority)">
                Приоритет: <b>{{ priorityLabel(task.priority) }}</b>
              </span>
            </div>
          </div>

          <!-- <div class="hidden sm:flex items-center gap-3">
            <a v-if="task?.project?.id" :href="`/projects/${task.project.id}`"
               class="rounded-xl bg-white text-gray-900 hover:bg-white/90 px-4 py-2 font-medium">
              К проекту
            </a>
          </div> -->

          


<!-- Кнопки действий -->
<div class="flex flex-col sm:flex-row flex-wrap gap-3 mt-6 sm:mt-0 sm:ml-auto w-full sm:w-auto">

  <!-- Первая строка: основные действия -->
  <div class="flex flex-wrap justify-end gap-2 w-full sm:w-auto">
    <button
      v-if="canUpdate"
      @click="showEditModal = true"
      class="flex items-center gap-1 rounded-xl bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 font-medium shadow-sm transition"
    >
      ✏️ Изменить
    </button>

   <button
  v-if="canDeleteTask"
  @click="showDeleteModal = true"
  class="flex items-center gap-1 rounded-xl bg-rose-500/90 hover:bg-rose-600 text-white px-4 py-2 font-medium shadow-sm transition"
>
  🗑 Удалить задачу
</button>


<!-- Модалка подтверждения удаления -->
<div
  v-if="showDeleteModal"
  class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
>
  <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md shadow-xl">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
      Удалить задачу?
    </h3>
    <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
      Это действие <span class="font-semibold text-rose-600">необратимо</span>.<br>
      Задача и все связанные подзадачи и файлы будут безвозвратно удалены.
    </p>

    <p v-if="deleteError" class="text-sm text-rose-600 mb-3">{{ deleteError }}</p>

    <div class="flex justify-end gap-2">
      <button
      style="color: gray;"
        @click="showDeleteModal = false"
        class="px-4 py-2 rounded-lg border dark:border-gray-600"
      >
        Отмена
      </button>

      <button
        @click="confirmDeleteTask"
        class="px-4 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white"
        :disabled="deleting"
      >
        <span v-if="!deleting">Удалить</span>
        <span v-else>Удаляю…</span>
      </button>
    </div>
  </div>
</div>



    <a
      v-if="task?.project?.id"
      :href="`/projects/${task.project.id}`"
      class="flex items-center gap-1 rounded-xl bg-white hover:bg-gray-100 text-gray-900 px-4 py-2 font-medium shadow-sm transition"
    >
      🔙 К проекту
    </a>
  </div>

  <!-- Вторая строка: управление участниками -->
  <div
    v-if="canManageMembers"
    class="flex flex-wrap  gap-2 w-full sm:w-auto border-t border-white/20 sm:border-t-0 sm:border-l sm:pl-3 sm:ml-3 pt-3 sm:pt-0 mt-3 sm:mt-0"
  >
    <button
      @click="openChangeExecutor"
      class="px-3 py-1.5 bg-blue-500 hover:bg-blue-600 text-white text-sm rounded-md transition"
    >
      👷 Изменить исполнителя
    </button>

    <button
      @click="openChangeResponsible"
      class="px-3 py-1.5 bg-indigo-500 hover:bg-indigo-600 text-white text-sm rounded-md transition"
    >
      👨‍💼 Изменить ответственного
    </button>

    <!-- Новые кнопки -->
<button
  v-if="canManageMembers"
  @click="openAddExecutor"
  class="px-3 py-1.5 bg-emerald-500 hover:bg-emerald-600 text-white text-sm rounded-md transition"
>
  ➕ Добавить исполнителя
</button>

<button
  v-if="canManageMembers"
  @click="openAddResponsible"
  class="px-3 py-1.5 bg-teal-500 hover:bg-teal-600 text-white text-sm rounded-md transition"
>
  ➕ Добавить ответственного
</button>


    <button
      v-if="canUpdate"
      @click="openWatcherModal"
      class="px-3 py-1.5 bg-emerald-500 hover:bg-emerald-600 text-white text-sm rounded-md transition"
    >
      👁 Добавить наблюдателя
    </button>

<button
  v-if="canManageMembers"
  @click="showManageMembers = true"
  class="px-3 py-1.5 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-white text-sm rounded-md"
>
  ⚙️ Управление участниками
</button>

  </div>

  <!-- Третья строка: завершение -->
  <div
    v-if="canManageTask"
    class="flex justify-end gap-2 w-full sm:w-auto border-t border-white/20 sm:border-t-0 sm:border-l sm:pl-3 sm:ml-3 pt-3 sm:pt-0 mt-3 sm:mt-0"
  >
    <button
      v-if="canFinish"
      @click="finishTask"
      class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-medium shadow-sm transition"
    >
      ✅ Завершить задачу
    </button>

    <div v-else-if="(task?.progress === 100) && !task?.completed" class="text-xs text-amber-200">
      <span v-if="hasOpenSubtasks">
        Есть незавершённые подзадачи — завершите их, чтобы закрыть задачу.
      </span>
    </div>

    <div v-if="task?.completed" class="text-sm text-emerald-200">
      Завершена {{ task?.completed_at || '' }}
    </div>
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
           <div  v-if="canUploadFiles">
  <input type="file" multiple @change="handleFileChange" accept=".pdf,.doc,.docx,.xls,.xlsx"
         class="text-sm text-gray-600 dark:text-gray-300" />
  <button @click="uploadFiles" class="rounded-xl bg-indigo-600 text-white px-4 py-2 hover:bg-indigo-700 mt-4">
    Загрузить
  </button>
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
  :href="`/api/tasks/files/${f.id}`"
  class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-100"
>
  <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor">
    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12V8l-4-6zM6 22V4h7v5h5v13H6z"/>
  </svg>
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
    @click="$inertia.visit(`/subtasks/${s.id}`)"
    class="group rounded-xl border p-4 bg-white dark:bg-gray-800 hover:shadow-md transition cursor-pointer"
    :class="[
      s.progress === 100
        ? 'border-emerald-500 ring-1 ring-emerald-300 bg-emerald-50 dark:bg-emerald-900/20' // 💚 выполнено
        : s.progress >= 50
          ? 'border-amber-400 ring-1 ring-amber-300 bg-amber-50 dark:bg-amber-900/20'      // ⚠️ в процессе
          : 'border-gray-400 ring-1 ring-gray-300 bg-gray-50 dark:bg-rose-900/20'          // 🔴 почти не начато
    ]"
  >
    <div class="flex items-start justify-between gap-3">
      <h3 class="text-base font-semibold text-gray-900 dark:text-white leading-snug">
        {{ s.title }}
      </h3>
      <span
        class="text-sm px-2 py-1 rounded-full"
        :class="{
          'bg-emerald-100 text-emerald-700': s.progress === 100,
          'bg-amber-100 text-amber-700': s.progress >= 50 && s.progress < 100,
          'bg-rose-100 text-rose-700': s.progress < 50
        }"
      >
        {{ s.progress ?? 0 }}%
      </span>
    </div>

    <div class="mt-2 text-sm text-gray-600 dark:text-gray-300 space-y-1.5">
      <div>
        Исполнитель:
        <b>{{ s.executors?.map(e => e.name).join(', ') || '—' }}</b>
      </div>
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
              <div class="flex justify-between"><dt>Исполнитель</dt><dd class="font-medium"> {{ task?.executors?.length
        ? task.executors.map(e => e.name).join(', ')
        : '—'
    }}</dd></div>
              <div class="flex justify-between"><dt>Ответственный</dt><dd class="font-medium">
                 {{ task?.responsibles?.length
        ? task.responsibles.map(e => e.name).join(', ')
        : '—'
    }}
              </dd></div>
              <div class="flex justify-between"><dt>Проект</dt><dd class="font-medium">{{ task?.project?.name ?? '—' }}</dd></div>
              <div class="flex justify-between"><dt>Компания</dt><dd class="font-medium">{{ task?.project?.company?.name ?? '—' }}</dd></div>
              <div class="flex justify-between"><dt>Приоритет</dt><dd>
                <span v-if="task" class="px-2 py-1 text-xs rounded-full ring-1" :class="priorityBadge(task.priority)">
                  {{ priorityLabel(task.priority) }}
                </span>
              </dd></div>
            </dl>
          </div>

<div v-if="task" class="rounded-2xl border bg-white dark:bg-gray-800 p-5">
  <TaskChecklists
  :task-id="task.id"
  :executors="task.executors"
  :responsibles="task.responsibles"
  :creator="task.creator"
/>

</div>


        </div>


<div class="space-y-4" v-if="task">
  <div class="rounded-2xl border bg-white dark:bg-gray-800 p-5">
    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Чат</h3>

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

          <div>
  <label class="block text-sm mb-1 text-gray-700 dark:text-gray-300">Ответственный</label>
  <select v-model="subtaskForm.responsible_id"
          class="w-full rounded-xl border px-3 py-2 bg-white dark:bg-gray-700 dark:text-white"
          required>
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


<div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
  <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg w-full max-w-md p-6">
    <h2 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Редактировать задачу</h2>

    <form @submit.prevent="updateTask">
      <div class="mb-3">
        <label class="block text-sm text-gray-700 dark:text-gray-300">Название</label>
        <input required v-model="editForm.title" type="text" class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-white"/>
      </div>

      <div class="mb-3">
        <label class="block text-sm">Дата начала</label>
        <input v-model="editForm.start_date" type="date" class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-white"/>
      </div>

      <div class="mb-3">
        <label class="block text-sm">Дата окончания</label>
        <input v-model="editForm.due_date" type="date" class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-white"/>
      </div>

      <div class="flex justify-end gap-2 mt-4">
        <button type="button" @click="showEditModal = false" class="px-4 py-2 border rounded">Отмена</button>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Сохранить</button>
      </div>
    </form>
  </div>
</div>

<div v-if="showWatcherModal" class="fixed inset-0 flex items-center justify-center bg-black/50">
  <div class="bg-white p-4 rounded w-96">
    <h3 class="font-semibold mb-3">Выберите наблюдателя</h3>
    <select v-model="selectedWatcher" class="w-full border p-2 rounded">
      <option v-for="u in companyEmployees" :key="u.id" :value="u.id">
        {{ u.name }}
      </option>
    </select>
    <div class="flex justify-end gap-2 mt-4">
      <button @click="showWatcherModal=false" class="px-3 py-1 border rounded">Отмена</button>
      <button @click="addWatcher" class="px-3 py-1 bg-blue-600 text-white rounded">Добавить</button>
    </div>
  </div>
</div>


<!-- Модалка: Изменить исполнителя -->
<!-- Модалка: Изменить исполнителя -->
<div v-if="showExecutorModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
  <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-md">
    <h3 class="text-lg font-semibold mb-3 text-gray-900 dark:text-white">Изменить исполнителя</h3>

    <!-- кого заменить -->
    <label class="text-sm text-gray-600 dark:text-gray-300">Кого заменить:</label>
    <select v-model="replaceExecutorId" class="w-full rounded-md border-gray-300 dark:bg-gray-700 dark:text-white mb-4">
      <option value="">Выберите текущего исполнителя</option>
      <option v-for="exe in task.executors" :key="exe.id" :value="exe.id">{{ exe.name }}</option>
    </select>

    <!-- на кого заменить -->
    <label class="text-sm text-gray-600 dark:text-gray-300">На кого заменить:</label>
    <select v-model="newExecutorId" class="w-full rounded-md border-gray-300 dark:bg-gray-700 dark:text-white">
      <option value="">Выберите нового исполнителя</option>
      <option v-for="emp in availableExecutors" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
    </select>

    <p v-if="executorError" class="text-rose-600 text-sm mt-2">{{ executorError }}</p>

    <div class="mt-4 flex justify-end gap-2">
      <button @click="showExecutorModal = false" class="px-3 py-1.5 bg-gray-200 dark:bg-gray-700 rounded-md text-sm">Отмена</button>
      <button @click="replaceExecutor" class="px-3 py-1.5 bg-blue-600 text-white rounded-md text-sm">Сохранить</button>
    </div>
  </div>
</div>


<!-- Модалка: Изменить ответственного -->
<!-- Модалка: Изменить ответственного -->
<div v-if="showResponsibleModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
  <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-md">
    <h3 class="text-lg font-semibold mb-3 text-gray-900 dark:text-white">Изменить ответственного</h3>

    <label class="text-sm text-gray-600 dark:text-gray-300">Кого заменить:</label>
    <select v-model="replaceResponsibleId" class="w-full rounded-md border-gray-300 dark:bg-gray-700 dark:text-white mb-4">
      <option value="">Выберите текущего ответственного</option>
      <option v-for="resp in task.responsibles" :key="resp.id" :value="resp.id">{{ resp.name }}</option>
    </select>

    <label class="text-sm text-gray-600 dark:text-gray-300">На кого заменить:</label>
    <select v-model="newResponsibleId" class="w-full rounded-md border-gray-300 dark:bg-gray-700 dark:text-white">
      <option value="">Выберите нового ответственного</option>
      <option v-for="emp in availableResponsibles" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
    </select>

    <p v-if="responsibleError" class="text-rose-600 text-sm mt-2">{{ responsibleError }}</p>

    <div class="mt-4 flex justify-end gap-2">
      <button @click="showResponsibleModal = false" class="px-3 py-1.5 bg-gray-200 dark:bg-gray-700 rounded-md text-sm">Отмена</button>
      <button @click="replaceResponsible" class="px-3 py-1.5 bg-indigo-600 text-white rounded-md text-sm">Сохранить</button>
    </div>
  </div>
</div>


<!-- Модалка: добавить исполнителей -->
<div v-if="showAddExecutorModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
  <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-md">
    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Добавить исполнителей</h3>

    <div class="max-h-60 overflow-y-auto space-y-2 mb-4">
      <label
        v-for="u in employees"
        :key="u.id"
        class="flex items-center gap-2"
      >
        <input type="checkbox" :value="u.id" v-model="selectedExecutors" />
        <span>{{ u.name }}</span>
      </label>
    </div>

    <div class="flex justify-end gap-2">
      <button @click="showAddExecutorModal = false" class="px-4 py-2 border rounded-lg">Отмена</button>
      <button @click="addExecutors" class="px-4 py-2 bg-emerald-600 text-white rounded-lg">Добавить</button>
    </div>
  </div>
</div>

<!-- Модалка: добавить ответственных -->
<div v-if="showAddResponsibleModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
  <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-md">
    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Добавить ответственных</h3>

    <div class="max-h-60 overflow-y-auto space-y-2 mb-4">
      <label
        v-for="u in employees"
        :key="u.id"
        class="flex items-center gap-2"
      >
        <input type="checkbox" :value="u.id" v-model="selectedResponsibles" />
        <span>{{ u.name }}</span>
      </label>
    </div>

    <div class="flex justify-end gap-2">
      <button @click="showAddResponsibleModal = false" class="px-4 py-2 border rounded-lg">Отмена</button>
      <button @click="addResponsibles" class="px-4 py-2 bg-teal-600 text-white rounded-lg">Добавить</button>
    </div>
  </div>
</div>

<!-- Модалка управления -->
<div v-if="showManageMembers" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
  <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-4xl shadow-xl">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
      Управление участниками задачи
    </h3>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
      <!-- Исполнители -->
      <div>
        <h4 class="text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">Исполнители</h4>
        <div v-for="u in task.executors" :key="u.id" class="flex justify-between items-center bg-gray-50 dark:bg-gray-700 p-2 rounded mb-2">
          <span>{{ u.name }}</span>
          <button
            @click="removeExecutor(u.id)"
            class="text-rose-600 hover:text-rose-700 text-xs font-medium"
          >
            Удалить
          </button>
        </div>
      </div>

      <!-- Ответственные -->
      <div>
        <h4 class="text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">Ответственные</h4>
        <div v-for="u in task.responsibles" :key="u.id" class="flex justify-between items-center bg-gray-50 dark:bg-gray-700 p-2 rounded mb-2">
          <span>{{ u.name }}</span>
          <button
            @click="removeResponsible(u.id)"
            class="text-rose-600 hover:text-rose-700 text-xs font-medium"
          >
            Удалить
          </button>
        </div>
      </div>

      <!-- Наблюдатели -->
      <div>
        <h4 class="text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">Наблюдатели</h4>
        <div v-for="u in task.watcherstask" :key="u.id" class="flex justify-between items-center bg-gray-50 dark:bg-gray-700 p-2 rounded mb-2">
          <span>{{ u.name }}</span>
          <button
            @click="removeWatcher(u.id)"
            class="text-rose-600 hover:text-rose-700 text-xs font-medium"
          >
            Удалить
          </button>
        </div>
      </div>
    </div>

    <p v-if="manageError" class="mt-3 text-sm text-rose-600">{{ manageError }}</p>

    <div class="flex justify-end mt-5">
      <button
        @click="showManageMembers = false"
        class="px-4 py-2 rounded-lg border dark:border-gray-600"
      >
        Закрыть
      </button>
    </div>
  </div>
</div>





  </AuthenticatedLayout>
</template>
