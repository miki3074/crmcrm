<script setup>
import { ref, onMounted, computed } from 'vue'
import { usePage, Head } from '@inertiajs/vue3'
import axios from 'axios'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

import  SubtaskComments from '@/Components/SubtaskComments.vue'
import SubtaskChecklist from '@/Components/SubtaskChecklist.vue'

const { props } = usePage()
const subtaskId = props.id
const subtask = ref(null)
const user = props.auth?.user

const fetchSubtask = async () => {
  const { data } = await axios.get(`/api/subtasks/${subtaskId}`)
  subtask.value = data
}

const canUpdateProgress = computed(() => {
  if (!subtask.value || !user) return false

  const project = subtask.value.task?.project || {}

  const isExecutor = (subtask.value.executors || []).some(e => e.id === user.id)
  const isResponsible = (subtask.value.responsibles || []).some(r => r.id === user.id)
  const isProjectExecutor = (project.executors || []).some(e => e.id === user.id)
  const isProjectManager = (project.managers || []).some(m => m.id === user.id)
  const isCompanyOwner = project.company?.user_id === user.id
  const isCreator = subtask.value.creator_id === user.id

  return (
    isExecutor ||
    isResponsible ||
    isProjectExecutor ||
    isProjectManager ||
    isCompanyOwner ||
    isCreator
  )
})



const canComplete = computed(() => {
  if (!subtask.value) return false
  return canUpdateProgress.value && subtask.value.progress === 100 && !subtask.value.completed
})

const updateProgress = async (value) => {
  if (!canUpdateProgress.value) return
  const { data } = await axios.patch(`/api/subtasks/${subtaskId}/progress`, { progress: value })
  subtask.value.progress = data.progress
}

const completeSubtask = async () => {
  if (!canComplete.value) return
  if (!confirm('Завершить подзадачу?')) return
  const { data } = await axios.patch(`/api/subtasks/${subtaskId}/complete`)
  subtask.value.completed = data.completed
  subtask.value.completed_at = data.completed_at
}

const deleteSubtask = async (id) => {
  if (!confirm('Удалить подзадачу?')) return
  try {
    await axios.delete(`/api/subtasks/${id}`, { withCredentials: true })
    alert('Подзадача успешно удалена')
    // возвращаемся на страницу задачи
    window.history.back()
  } catch (e) {
    alert(e?.response?.data?.message || 'Ошибка при удалении подзадачи')
  }
}


const canDeleteSubtask = (subtask) => {
  const userId = user?.id
  if (!userId) return false

  return (
    userId === subtask.creator_id ||
    userId === subtask.task?.project?.company?.user_id ||
    (subtask.task?.project?.managers || []).some(m => m.id === userId)
  )
}



const showExecutorModal = ref(false)
const showResponsibleModal = ref(false)
const showAddExecutorModal = ref(false)
const showAddResponsibleModal = ref(false)

const employees = ref([])
const selectedUser = ref(null)
const selectedUsers = ref([])

const canManageMembers = computed(() => {
  if (!subtask.value || !user) return false
  return (
    user.id === subtask.value.task?.project?.company?.user_id ||
    (subtask.value.task?.project?.managers || []).some(m => m.id === user.id) ||
    (subtask.value.task?.project?.executors || []).some(e => e.id === user.id)
  )
})

const fetchEmployees = async () => {
  const { data } = await axios.get(`/api/projects/${subtask.value.task.project.id}/employees`)
  employees.value = data
}

// === Открытие модалок ===
const openChangeExecutor = async () => {
  await fetchEmployees()
  showExecutorModal.value = true
}

const openChangeResponsible = async () => {
  await fetchEmployees()
  showResponsibleModal.value = true
}

const openAddExecutor = async () => {
  await fetchEmployees()
  showAddExecutorModal.value = true
}

const openAddResponsible = async () => {
  await fetchEmployees()
  showAddResponsibleModal.value = true
}

// === Действия ===
const changeExecutor = async () => {
  await axios.patch(`/api/subtasks/${subtaskId}/executor`, { user_id: selectedUser.value })
  await fetchSubtask()
  showExecutorModal.value = false
}

const changeResponsible = async () => {
  await axios.patch(`/api/subtasks/${subtaskId}/responsible`, { user_id: selectedUser.value })
  await fetchSubtask()
  showResponsibleModal.value = false
}

const addExecutors = async () => {
  if (!selectedUsers.value.length) return alert('Выберите хотя бы одного сотрудника.')

  try {
    await axios.post(`/api/subtasks/${subtaskId}/executors/add`, {
      user_ids: selectedUsers.value,
    })
    await fetchSubtask() // обновляем данные подзадачи
    showAddExecutorModal.value = false
    selectedUsers.value = [] // ✅ очищаем выбор
  } catch (e) {
    alert(e?.response?.data?.message || 'Ошибка при добавлении исполнителей')
  }
}

const addResponsibles = async () => {
  if (!selectedUsers.value.length) return alert('Выберите хотя бы одного сотрудника.')

  try {
    await axios.post(`/api/subtasks/${subtaskId}/responsibles/add`, {
      user_ids: selectedUsers.value,
    })
    await fetchSubtask()
    showAddResponsibleModal.value = false
    selectedUsers.value = [] // ✅ очищаем выбор
  } catch (e) {
    alert(e?.response?.data?.message || 'Ошибка при добавлении ответственных')
  }
}

// состояния для модалок замены
const replaceExecutorId = ref(null)
const newExecutorId = ref(null)
const replaceResponsibleId = ref(null)
const newResponsibleId = ref(null)
const executorError = ref('')
const responsibleError = ref('')

// доступные сотрудники (исключаем уже существующих)
const availableExecutors = computed(() => {
  if (!employees.value.length || !subtask.value) return []
  const currentIds = (subtask.value.executors || []).map(e => e.id)
  return employees.value.filter(e => !currentIds.includes(e.id))
})

const availableResponsibles = computed(() => {
  if (!employees.value.length || !subtask.value) return []
  const currentIds = (subtask.value.responsibles || []).map(r => r.id)
  return employees.value.filter(e => !currentIds.includes(e.id))
})

// логика отправки
const replaceExecutor = async () => {
  executorError.value = ''
  if (!replaceExecutorId.value || !newExecutorId.value) {
    executorError.value = 'Выберите кого и на кого заменить.'
    return
  }

  try {
    await axios.patch(`/api/subtasks/${subtaskId}/executor/change`, {
      replace_user_id: replaceExecutorId.value,
      user_id: newExecutorId.value,
    })
    showExecutorModal.value = false
    await fetchSubtask()
  } catch (e) {
    executorError.value = e?.response?.data?.message || 'Ошибка при замене исполнителя'
  }
}

const replaceResponsible = async () => {
  responsibleError.value = ''
  if (!replaceResponsibleId.value || !newResponsibleId.value) {
    responsibleError.value = 'Выберите кого и на кого заменить.'
    return
  }

  try {
    await axios.patch(`/api/subtasks/${subtaskId}/responsible/change`, {
      replace_user_id: replaceResponsibleId.value,
      user_id: newResponsibleId.value,
    })
    showResponsibleModal.value = false
    await fetchSubtask()
  } catch (e) {
    responsibleError.value = e?.response?.data?.message || 'Ошибка при замене ответственного'
  }
}


const showManageMembers = ref(false)
const manageError = ref('')

const removeExecutor = async (id) => {
  manageError.value = ''
  try {
    await axios.delete(`/api/subtasks/${subtaskId}/executors`, { data: { user_id: id } })
    await fetchSubtask()
  } catch (e) {
    manageError.value = e?.response?.data?.message || 'Ошибка при удалении исполнителя'
  }
}

const removeResponsible = async (id) => {
  manageError.value = ''
  try {
    await axios.delete(`/api/subtasks/${subtaskId}/responsibles`, { data: { user_id: id } })
    await fetchSubtask()
  } catch (e) {
    manageError.value = e?.response?.data?.message || 'Ошибка при удалении ответственного'
  }
}


const showEditSubtaskModal = ref(false)
const savingSubtask = ref(false)
const editError = ref('')
const editSubtaskForm = ref({
  title: '',
  due_date: '',
})

// открыть модалку
const openEditSubtask = () => {
  if (!subtask.value) return
  editSubtaskForm.value.title = subtask.value.title
  editSubtaskForm.value.due_date = subtask.value.due_date
  showEditSubtaskModal.value = true
}

// обновить подзадачу
const updateSubtask = async () => {
  editError.value = ''
  savingSubtask.value = true

  try {
    await axios.patch(`/api/subtasks/${subtaskId}/update`, editSubtaskForm.value)
    await fetchSubtask()
    showEditSubtaskModal.value = false
  } catch (e) {
    editError.value = e?.response?.data?.message || 'Ошибка при обновлении подзадачи'
  } finally {
    savingSubtask.value = false
  }
}


const canEditSubtask = computed(() => {
  if (!subtask.value || !user) return false
  const project = subtask.value.task?.project

  return (
    user.id === subtask.value.creator_id || // автор подзадачи
    user.id === project?.company?.user_id || // владелец компании
    (project?.managers || []).some(m => m.id === user.id) || // руководитель проекта
    (project?.executors || []).some(e => e.id === user.id) // исполнитель проекта
  )
})


const canUploadFiles = computed(() => {
  if (!subtask.value || !user) return false
  const project = subtask.value.task?.project || {}

  return (
    user.id === subtask.value.creator_id ||
    (subtask.value.executors || []).some(e => e.id === user.id) ||
    (subtask.value.responsibles || []).some(r => r.id === user.id) ||
    (project.executors || []).some(e => e.id === user.id) ||
    (project.managers || []).some(m => m.id === user.id) ||
    project.company?.user_id === user.id
  )
})

const fileInput = ref(null)

const uploadFile = async (event) => {
  const file = event.target.files[0]
  if (!file) return

  const formData = new FormData()
  formData.append('file', file)

  try {
    await axios.post(`/api/subtasks/${subtaskId}/files`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    await fetchSubtask()
  } catch (e) {
    alert(e?.response?.data?.message || 'Ошибка при загрузке файла')
  }
}

const deleteFile = async (fileId) => {
  if (!confirm('Удалить файл?')) return
  try {
    await axios.delete(`/api/subtask-files/${fileId}`)
    await fetchSubtask()
  } catch (e) {
    alert(e?.response?.data?.message || 'Ошибка при удалении файла')
  }
}


// === Создание дочерней подзадачи ===
const showCreateChildModal = ref(false)
const creatingChild = ref(false)
const createChildError = ref('')
const createChildForm = ref({
  title: '',
  due_date: '',
  executor_ids: [],
  responsible_ids: [],
})

const openCreateChildModal = async () => {
  await fetchEmployees() // чтобы получить список всех сотрудников проекта
  showCreateChildModal.value = true
}

const createChildSubtask = async () => {
  if (!subtask.value) return
  createChildError.value = ''
  creatingChild.value = true

  try {
    await axios.post(`/api/subtasks/${subtaskId}/children`, createChildForm.value)
    await fetchSubtask()
    showCreateChildModal.value = false
    createChildForm.value = { title: '', due_date: '', executor_ids: [], responsible_ids: [] }
  } catch (e) {
    createChildError.value = e?.response?.data?.message || 'Ошибка при создании подзадачи'
  } finally {
    creatingChild.value = false
  }
}

const onCommentsUpdated = ({ type, comment, id }) => {
  if (!subtask.value.comments) {
    subtask.value.comments = []
  }

  if (type === "add") {
    subtask.value.comments.push(comment)
  }

  if (type === "update") {
    const index = subtask.value.comments.findIndex(c => c.id === comment.id)
    if (index !== -1) {
      subtask.value.comments[index] = comment
    }
  }

  if (type === "delete") {
    subtask.value.comments = subtask.value.comments.filter(c => c.id !== id)
  }
}

const canWriteComments = computed(() => {
  if (!subtask.value || !user) return false

  const project = subtask.value.task?.project || {}

  const isCreator = subtask.value.creator_id === user.id
  const isProjectManager = (project.managers || []).some(m => m.id === user.id)
  const isProjectExecutor = (project.executors || []).some(e => e.id === user.id)
  const isCompanyOwner = project.company?.user_id === user.id
  const isSubtaskExecutor = (subtask.value.executors || []).some(e => e.id === user.id)
  const isSubtaskResponsible = (subtask.value.responsibles || []).some(r => r.id === user.id)

  return (
    isCreator ||
    isProjectManager ||
    isProjectExecutor ||
    isCompanyOwner ||
    isSubtaskExecutor ||
    isSubtaskResponsible
  )
})



const onChecklistUpdated = (e) => {
  if (!subtask.value.checklist) subtask.value.checklist = []

  if (e.type === 'add') {
    subtask.value.checklist.push(e.item)
  }

  if (e.type === 'toggle') {
    const item = subtask.value.checklist.find(i => i.id === e.id)
    if (item) item.completed = e.completed
  }

  if (e.type === 'delete') {
    subtask.value.checklist = subtask.value.checklist.filter(i => i.id !== e.id)
  }
}

const showDescriptionModal = ref(false)
const descriptionText = ref('')

const openDescriptionModal = () => {
  descriptionText.value = subtask.value.description || ''
  showDescriptionModal.value = true
}

const saveDescription = async () => {
  try {
    const { data } = await axios.patch(`/api/subtasks/${subtaskId}/description`, {
      description: descriptionText.value
    })

    subtask.value.description = data.description
    showDescriptionModal.value = false
  } catch (e) {
    alert(e?.response?.data?.message || "Ошибка сохранения")
  }
}



onMounted(fetchSubtask)
</script>

<template>
  <Head title="Подзадача" />
  <AuthenticatedLayout>
    <template #header>
         <div class="flex items-center justify-between gap-3">
        <h2 class="text-xl font-bold text-gray-800 dark:text-gray-200">
          Подзадача: {{ subtask?.title ?? 'Загрузка...' }}
        </h2>

        <div v-if="subtask" class="flex items-center gap-2">
          <span
            class="inline-flex items-center gap-2 text-sm px-3 py-1 rounded-full ring-1 ring-gray-200 dark:ring-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300">
            <span class="w-2 h-2 rounded-full"
                  :class="{
                    'bg-red-500': (subtask.progress ?? 0) < 30,
                    'bg-amber-500': (subtask.progress ?? 0) >= 30 && (subtask.progress ?? 0) < 70,
                    'bg-green-500': (subtask.progress ?? 0) >= 70
                  }"/>
            {{ subtask.progress ?? 0 }}%
          </span>

          <!-- Кнопка завершить -->
          <button
            v-if="canComplete"
            @click="completeSubtask"
            class="px-3 py-1.5 rounded-md bg-emerald-600 text-white text-sm hover:bg-emerald-700"
          >
            Завершить
          </button>

          <button
  v-if="canEditSubtask"
  @click="openEditSubtask"
  class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-md"
>
  ✏️ Изменить подзадачу
</button>

<button
    v-if="user.id === subtask.creator_id"
    @click="openDescriptionModal"
    class="px-3 py-1 bg-indigo-600 text-white rounded-lg mt-3"
>
  ✏ Описание
</button>




          <button
  v-if="canDeleteSubtask(subtask)"
  @click="deleteSubtask(subtask.id)"
  class="px-3 py-1 bg-rose-600 hover:bg-rose-700 text-white text-sm rounded-md"
>
  🗑 Удалить
</button>







          <span v-else-if="subtask.completed"
                class="px-3 py-1.5 rounded-md bg-emerald-100 text-emerald-700 text-sm dark:bg-emerald-900/30 dark:text-emerald-300">
            Завершена • {{ subtask.completed_at?.slice(0, 16) ?? '' }}
          </span>
        </div>
      </div>
    </template>

    <div class="max-w-4xl mx-auto py-8 px-4">
      <div v-if="subtask" class="grid gap-6">
        <!-- карточка с краткой инфой -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-6">
          <div class="grid sm:grid-cols-2 gap-4 text-sm">
           
         

<div>

<p class="text-gray-500 dark:text-gray-400">Автор</p>
<p class="font-medium text-gray-900 dark:text-white">{{ subtask.creator?.name }}</p>
 <p class="text-gray-500 dark:text-gray-400 mt-4">Исполнитель</p>
  <p class="font-medium text-gray-900 dark:text-white">
    {{ subtask.executors?.length ? subtask.executors.map(e => e.name).join(', ') : '—' }}
  </p>
  <p class="text-gray-500 dark:text-gray-400 mt-4">Ответственный</p>
  <p class="font-medium text-gray-900 dark:text-white">
    {{ subtask.responsibles?.length ? subtask.responsibles.map(r => r.name).join(', ') : '—' }}
  </p>
</div> 

<div v-if="canManageMembers" class="flex flex-wrap gap-2 mt-4">
  <button
    @click="openChangeExecutor"
    class="px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded-md"
  >
    ✏️ Изменить исполнителя
  </button>
  <button
    @click="openChangeResponsible"
    class="px-3 py-1 bg-purple-500 hover:bg-purple-600 text-white rounded-md"
  >
    ✏️ Изменить ответственного
  </button>
  <button
    @click="openAddExecutor"
    class="px-3 py-1 bg-emerald-500 hover:bg-emerald-600 text-white rounded-md"
  >
    ➕ Добавить исполнителя
  </button>
  <button
    @click="openAddResponsible"
    class="px-3 py-1 bg-amber-500 hover:bg-amber-600 text-white rounded-md"
  >
    ➕ Добавить ответственного
  </button>

  <button
  @click="showManageMembers = true"
  class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg"
>
  👥 Управление участниками
</button>

</div>

          </div>
        </div>


   <div v-if="subtask.description" class="mt-4 p-3 bg-gray-100 dark:bg-gray-700 rounded-lg bg-white dark:bg-gray-800 rounded-2xl shadow p-6">
      <h3 class="text-base font-semibold text-gray-900 dark:text-white">Описание</h3>
  <p class="whitespace-pre-line">{{ subtask.description }}</p>
</div>
     

        <!-- Прогресс -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-6">
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Прогресс подзадачи</h3>
            <span class="text-sm text-gray-500 dark:text-gray-400">Выполнено {{ subtask.progress ?? 0 }}%</span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <p class="text-gray-500 dark:text-gray-400">Дата начала</p>
              <p class="font-medium text-gray-900 dark:text-white">{{ subtask.start_date }}</p>
            </div>
            <div>
              <p class="text-gray-500 dark:text-gray-400">Дата окончания</p>
              <p class="font-medium text-gray-900 dark:text-white">{{ subtask.due_date }}</p>
            </div>
          </div>

          <!-- «кирпичики» 10х10% -->
          <div class="flex mt-2 space-x-1 select-none">
            <div
              v-for="n in 10"
              :key="n"
              :title="(n*10) + '%'"
              @click="canUpdateProgress ? updateProgress(n*10) : null"
              class="h-4 sm:h-5 flex-1 rounded transition"
              :class="{
                'cursor-pointer hover:opacity-80': canUpdateProgress,
                'bg-green-600': (subtask.progress ?? 0) >= n * 10,
                'bg-gray-200 dark:bg-gray-700': (subtask.progress ?? 0) < n * 10,
                'pointer-events-none opacity-60': subtask?.completed
              }"
            />
          </div>

          <p v-if="!canUpdateProgress" class="text-xs text-gray-500 dark:text-gray-400 mt-2">
            Изменять прогресс могут только исполнитель и автор подзадачи.
          </p>
        </div>



<!-- === Блок файлов === -->
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-6 mt-6">
  <div class="flex items-center justify-between mb-3">
    <h3 class="text-base font-semibold text-gray-900 dark:text-white">📎 Файлы подзадачи</h3>

    <div v-if="canUploadFiles">
      <input
        type="file"
        @change="uploadFile"
        class="hidden"
        ref="fileInput"
      />
      <button
        @click="$refs.fileInput.click()"
        class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-sm"
      >
        ⬆️ Загрузить файл
      </button>
    </div>
  </div>

  <ul v-if="subtask.files?.length" class="space-y-2">
    <li
      v-for="file in subtask.files"
      :key="file.id"
      class="flex justify-between items-center bg-gray-50 dark:bg-gray-700 p-2 rounded-lg"
    >
      <a
        :href="`/api/subtask-files/${file.id}/download`"
        class="text-blue-600 dark:text-blue-400 hover:underline"
      >
        {{ file.filename }}
      </a>

      <button
        v-if="canUploadFiles"
        @click="deleteFile(file.id)"
        class="text-rose-500 hover:text-rose-700 text-sm"
      >
        ❌
      </button>
    </li>
  </ul>

  <p v-else class="text-sm text-gray-500 dark:text-gray-400">Нет прикреплённых файлов</p>
</div>










<!-- === Модалки для управления участниками === -->
<!-- Изменить исполнителя -->
<div
  v-if="showExecutorModal"
  class="fixed inset-0 bg-black/40 flex items-center justify-center z-50"
>
  <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md shadow-xl">
    <h3 class="text-lg font-semibold mb-3 text-gray-900 dark:text-white">
      ✏️ Изменить исполнителя
    </h3>

    <!-- Кого заменить -->
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
      Кого заменить:
    </label>
    <select
      v-model="replaceExecutorId"
      class="w-full border rounded-lg p-2 mb-4 dark:bg-gray-700 dark:text-white"
    >
      <option value="">Выберите исполнителя</option>
      <option
        v-for="exe in subtask?.executors || []"
        :key="exe.id"
        :value="exe.id"
      >
        {{ exe.name }}
      </option>
    </select>

    <!-- На кого заменить -->
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
      На кого заменить:
    </label>
    <select
      v-model="newExecutorId"
      class="w-full border rounded-lg p-2 dark:bg-gray-700 dark:text-white"
    >
      <option value="">Выберите нового исполнителя</option>
      <option
        v-for="emp in availableExecutors"
        :key="emp.id"
        :value="emp.id"
      >
        {{ emp.name }} ({{ emp.email }})
      </option>
    </select>

    <p v-if="executorError" class="text-rose-600 text-sm mt-2">{{ executorError }}</p>

    <div class="flex justify-end gap-2 mt-4">
      <button @click="showExecutorModal = false" class="px-4 py-2 border rounded-lg text-gray-600">
        Отмена
      </button>
      <button
        @click="replaceExecutor"
        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg"
      >
        Сохранить
      </button>
    </div>
  </div>
</div>


<!-- === Модалка создания дочерней подзадачи === -->
<div
  v-if="showCreateChildModal"
  class="fixed inset-0 bg-black/40 flex items-center justify-center z-50"
>
  <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md shadow-xl">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
      ➕ Новая дочерняя подзадача
    </h3>

    <div class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
          Название
        </label>
        <input
          v-model="createChildForm.title"
          type="text"
          class="w-full border rounded-lg p-2 dark:bg-gray-700 dark:text-white"
          placeholder="Введите название подзадачи"
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
          Дата окончания
        </label>
        <input
          v-model="createChildForm.due_date"
          type="date"
          class="w-full border rounded-lg p-2 dark:bg-gray-700 dark:text-white"
        />
      </div>

      <!-- Исполнители -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
          Исполнители
        </label>
        <div class="max-h-40 overflow-y-auto border rounded-lg p-2 dark:border-gray-600">
          <label
            v-for="emp in employees"
            :key="emp.id"
            class="flex items-center gap-2 py-1"
          >
            <input
              type="checkbox"
              v-model="createChildForm.executor_ids"
              :value="emp.id"
            />
            <span>{{ emp.name }} ({{ emp.email }})</span>
          </label>
        </div>
      </div>

      <!-- Ответственные -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
          Ответственные
        </label>
        <div class="max-h-40 overflow-y-auto border rounded-lg p-2 dark:border-gray-600">
          <label
            v-for="emp in employees"
            :key="emp.id"
            class="flex items-center gap-2 py-1"
          >
            <input
              type="checkbox"
              v-model="createChildForm.responsible_ids"
              :value="emp.id"
            />
            <span>{{ emp.name }} ({{ emp.email }})</span>
          </label>
        </div>
      </div>
    </div>

    <p v-if="createChildError" class="text-rose-600 text-sm mt-3">{{ createChildError }}</p>

    <div class="flex justify-end gap-2 mt-5">
      <button
        @click="showCreateChildModal = false"
        class="px-4 py-2 border rounded-lg text-gray-600"
      >
        Отмена
      </button>
      <button
        @click="createChildSubtask"
        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg"
        :disabled="creatingChild"
      >
        <span v-if="!creatingChild">Создать</span>
        <span v-else>Создаю...</span>
      </button>
    </div>
  </div>
</div>





<!-- Изменить ответственного -->
<div
  v-if="showResponsibleModal"
  class="fixed inset-0 bg-black/40 flex items-center justify-center z-50"
>
  <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md shadow-xl">
    <h3 class="text-lg font-semibold mb-3 text-gray-900 dark:text-white">
      ✏️ Изменить ответственного
    </h3>

    <!-- Кого заменить -->
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
      Кого заменить:
    </label>
    <select
      v-model="replaceResponsibleId"
      class="w-full border rounded-lg p-2 mb-4 dark:bg-gray-700 dark:text-white"
    >
      <option value="">Выберите ответственного</option>
      <option
        v-for="resp in subtask?.responsibles || []"
        :key="resp.id"
        :value="resp.id"
      >
        {{ resp.name }}
      </option>
    </select>

    <!-- На кого заменить -->
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
      На кого заменить:
    </label>
    <select
      v-model="newResponsibleId"
      class="w-full border rounded-lg p-2 dark:bg-gray-700 dark:text-white"
    >
      <option value="">Выберите нового ответственного</option>
      <option
        v-for="emp in availableResponsibles"
        :key="emp.id"
        :value="emp.id"
      >
        {{ emp.name }} ({{ emp.email }})
      </option>
    </select>

    <p v-if="responsibleError" class="text-rose-600 text-sm mt-2">{{ responsibleError }}</p>

    <div class="flex justify-end gap-2 mt-4">
      <button @click="showResponsibleModal = false" class="px-4 py-2 border rounded-lg text-gray-600">
        Отмена
      </button>
      <button
        @click="replaceResponsible"
        class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg"
      >
        Сохранить
      </button>
    </div>
  </div>
</div>


<!-- Добавить исполнителей -->
<div
  v-if="showAddExecutorModal"
  class="fixed inset-0 bg-black/40 flex items-center justify-center z-50"
>
  <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md shadow-xl">
    <h3 class="text-lg font-semibold mb-3 text-gray-900 dark:text-white">
      ➕ Добавить исполнителей
    </h3>

    <div class="max-h-60 overflow-y-auto border rounded-lg p-2 dark:border-gray-600">
      <label
        v-for="emp in employees"
        :key="emp.id"
        class="flex items-center gap-2 py-1"
      >
        <input type="checkbox" v-model="selectedUsers" :value="emp.id" />
        <span>{{ emp.name }} ({{ emp.email }})</span>
      </label>
    </div>

    <div class="flex justify-end gap-2 mt-4">
      <button @click="showAddExecutorModal = false" class="px-4 py-2 border rounded-lg text-gray-600">
        Отмена
      </button>
      <button
        @click="addExecutors"
        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg"
      >
        Добавить
      </button>
    </div>
  </div>
</div>

<!-- Добавить ответственных -->
<div
  v-if="showAddResponsibleModal"
  class="fixed inset-0 bg-black/40 flex items-center justify-center z-50"
>
  <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md shadow-xl">
    <h3 class="text-lg font-semibold mb-3 text-gray-900 dark:text-white">
      ➕ Добавить ответственных
    </h3>

    <div class="max-h-60 overflow-y-auto border rounded-lg p-2 dark:border-gray-600">
      <label
        v-for="emp in employees"
        :key="emp.id"
        class="flex items-center gap-2 py-1"
      >
        <input type="checkbox" v-model="selectedUsers" :value="emp.id" />
        <span>{{ emp.name }} ({{ emp.email }})</span>
      </label>
    </div>

    <div class="flex justify-end gap-2 mt-4">
      <button @click="showAddResponsibleModal = false" class="px-4 py-2 border rounded-lg text-gray-600">
        Отмена
      </button>
      <button
        @click="addResponsibles"
        class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg"
      >
        Добавить
      </button>
    </div>
  </div>
</div>


<!-- Управление участниками -->
<div
  v-if="showManageMembers"
  class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
>
  <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-3xl shadow-xl">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
      👥 Управление участниками
    </h3>

    <p v-if="manageError" class="text-sm text-rose-600 mb-3">{{ manageError }}</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
      <!-- Исполнители -->
      <div>
        <h4 class="text-gray-800 dark:text-gray-200 font-medium mb-2">Исполнители</h4>
        <ul class="space-y-2">
          <li
            v-for="exe in subtask?.executors || []"
            :key="exe.id"
            class="flex justify-between items-center bg-gray-50 dark:bg-gray-700 p-2 rounded-lg"
          >
            <span>{{ exe.name }}</span>
            <button
              @click="removeExecutor(exe.id)"
              class="text-rose-500 hover:text-rose-700"
              title="Удалить исполнителя"
            >
              ❌
            </button>
          </li>
          <li v-if="!subtask?.executors?.length" class="text-sm text-gray-500">Нет исполнителей</li>
        </ul>
      </div>

      <!-- Ответственные -->
      <div>
        <h4 class="text-gray-800 dark:text-gray-200 font-medium mb-2">Ответственные</h4>
        <ul class="space-y-2">
          <li
            v-for="resp in subtask?.responsibles || []"
            :key="resp.id"
            class="flex justify-between items-center bg-gray-50 dark:bg-gray-700 p-2 rounded-lg"
          >
            <span>{{ resp.name }}</span>
            <button
              @click="removeResponsible(resp.id)"
              class="text-rose-500 hover:text-rose-700"
              title="Удалить ответственного"
            >
              ❌
            </button>
          </li>
          <li v-if="!subtask?.responsibles?.length" class="text-sm text-gray-500">Нет ответственных</li>
        </ul>
      </div>
    </div>

    <div class="flex justify-end mt-6">
      <button
        @click="showManageMembers = false"
        class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-white rounded-lg"
      >
        Закрыть
      </button>
    </div>
  </div>
</div>



<!-- === Кнопка добавить подзадачу === -->
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-6 mt-6">
  <div class="flex items-center justify-between mb-3">
    <h3 class="text-base font-semibold text-gray-900 dark:text-white">🧩 Вложенные подзадачи</h3>

    <button
      v-if="canEditSubtask"
      @click="openCreateChildModal"
      class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-md text-sm"
    >
      ➕ Добавить подзадачу
    </button>
  </div>

  <ul v-if="subtask.children?.length" class="space-y-2">
    <li
      v-for="child in subtask.children"
      :key="child.id"
      class="flex justify-between items-center bg-gray-50 dark:bg-gray-700 p-2 rounded-lg"
    >
      <div>
        <span class="font-medium">{{ child.title }}</span>
        <span class="text-xs text-gray-500 ml-2">{{ child.progress ?? 0 }}%</span>
      </div>
      <a
        :href="`/subtasks/${child.id}`"
        class="text-blue-600 dark:text-blue-400 hover:underline text-sm"
      >
        Открыть →
      </a>
    </li>
  </ul>

  <p v-else class="text-sm text-gray-500 dark:text-gray-400">Нет дочерних подзадач</p>
</div>


<div data-v-585ee726="" class="grid grid-cols-1 sm:grid-cols-2 gap-3">

<SubtaskChecklist
    :subtask-id="subtask.id"
    :checklist="subtask.checklist"
    :executors="subtask.executors"
    :responsibles="subtask.responsibles"
    :can-write="canWriteComments" 
    @updated="onChecklistUpdated"
/>



<!-- <SubtaskComments
    :subtask-id="subtask.id"
    :comments="subtask.comments"
    :can-write="canWriteComments"
    @updated="onCommentsUpdated"
/> -->

<SubtaskComments
    :subtask-id="subtask.id"
    :comments="subtask.comments"
    :can-write="canWriteComments"
    :members="[...(subtask.executors ?? []), ...(subtask.responsibles ?? [])]"
    @updated="onCommentsUpdated"
/>


</div>



<!-- Модалка изменения подзадачи -->
<div
  v-if="showEditSubtaskModal"
  class="fixed inset-0 bg-black/40 flex items-center justify-center z-50"
>
  <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md shadow-xl">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
      ✏️ Изменить подзадачу
    </h3>

    <div class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
          Название
        </label>
        <input required
          v-model="editSubtaskForm.title"
          type="text"
          class="w-full border rounded-lg p-2 dark:bg-gray-700 dark:text-white"
          placeholder="Введите новое название"
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
          Дата окончания
        </label>
        <input
          v-model="editSubtaskForm.due_date"
          type="date"
          class="w-full border rounded-lg p-2 dark:bg-gray-700 dark:text-white"
        />
      </div>
    </div>

    <p v-if="editError" class="text-rose-600 text-sm mt-3">{{ editError }}</p>

    <div class="flex justify-end gap-2 mt-5">
      <button
        @click="showEditSubtaskModal = false"
        class="px-4 py-2 border rounded-lg text-gray-600"
      >
        Отмена
      </button>
      <button
        @click="updateSubtask"
        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg"
        :disabled="savingSubtask"
      >
        <span v-if="!savingSubtask">Сохранить</span>
        <span v-else>Сохраняю...</span>
      </button>
    </div>
  </div>
</div>


<div
  v-if="showDescriptionModal"
  class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
>
  <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-xl w-full max-w-lg">
    <h3 class="text-lg font-semibold mb-3">Описание подзадачи</h3>

    <textarea
      v-model="descriptionText"
      class="w-full border rounded-lg px-3 py-2 dark:bg-gray-700 dark:text-white"
      rows="6"
      placeholder="Введите описание..."
    ></textarea>

    <div class="mt-4 flex justify-end gap-2">
      <button
        @click="showDescriptionModal = false"
        class="px-4 py-2 bg-gray-500 text-white rounded-lg"
      >
        Закрыть
      </button>
      <button
        @click="saveDescription"
        class="px-4 py-2 bg-indigo-600 text-white rounded-lg"
      >
        Сохранить
      </button>
    </div>
  </div>
</div>



      </div>





      
      <div v-else class="text-gray-600 dark:text-gray-300">Загрузка...</div>
    </div>


  </AuthenticatedLayout>
</template>
