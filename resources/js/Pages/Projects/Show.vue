<script setup>
import { ref, onMounted, computed } from 'vue' 
import axios from 'axios'
import { Head, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const { props } = usePage()
const projectId = props.id
const project = ref(null)
const showTaskModal = ref(false)
const employees = ref([])

const showBudgetModal = ref(false)
const showDescriptionModal = ref(false)

const budgetForm = ref({ budget: '' })
const descriptionForm = ref({ description: '' })

const taskForm = ref({
  title: '',
  executor_id: '',
  responsible_id: '',
  priority: 'low',
  start_date: new Date().toISOString().slice(0, 10),
  due_date: '',
  files: null,
})

const fetchProject = async () => {
  const { data } = await axios.get(`/api/projects/${projectId}`)
  project.value = data
  budgetForm.value.budget = data.budget ?? ''
  descriptionForm.value.description = data.description ?? ''
}

const fetchEmployees = async () => {
  const { data } = await axios.get(`/api/projects/${projectId}/employees`)
  employees.value = data
}

const handleFileUpload = (e) => {
  taskForm.value.files = e.target.files
}

// ✅ вычисляем право на создание задачи
const canCreateTask = computed(() => {
  const user = props.auth?.user
  const roles = props.auth?.roles || []
  if (!user || !project.value) return false

  const isAdmin = roles.includes('admin')
  const isCompanyOwner = project.value.company?.user_id === user.id
  const isProjectManager = project.value.manager_id === user.id

  return isAdmin || isCompanyOwner || isProjectManager
})

// удобный хэндлер кнопки
const openCreateTask = async () => {
  if (!canCreateTask.value) {
    alert('Недостаточно прав для создания задачи.')
    return
  }
  await fetchEmployees()
  showTaskModal.value = true
}

const createTask = async () => {
  // ⚠️ Починили имена полей под контроллер TaskController::store
  const formData = new FormData()
  formData.append('title', taskForm.value.title)
  formData.append('executor_id', taskForm.value.executor_id)
  formData.append('responsible_id', taskForm.value.responsible_id)
  formData.append('priority', taskForm.value.priority)
  formData.append('start_date', taskForm.value.start_date)
  formData.append('due_date', taskForm.value.due_date)
  formData.append('project_id', projectId)
  formData.append('company_id', project.value.company.id)

  if (taskForm.value.files) {
    for (let i = 0; i < taskForm.value.files.length; i++) {
      formData.append('files[]', taskForm.value.files[i])
    }
  }

  await axios.post('/api/tasks', formData, {
    headers: { 'Content-Type': 'multipart/form-data' }
  })

  showTaskModal.value = false
  taskForm.value = {
    title: '',
    executor_id: '',
    responsible_id: '',
    priority: 'low',
    start_date: new Date().toISOString().slice(0, 10),
    due_date: '',
    files: null,
  }

  await fetchProject()
}

const canEditBudget = computed(() => {
  if (!project.value || !props.auth?.user) return false
  return props.auth.roles?.includes('admin') || project.value.company?.user_id === props.auth.user.id
})
const canEditDescription = computed(() => {
  if (!project.value || !props.auth?.user) return false
  return props.auth.roles?.includes('admin')
      || project.value.company?.user_id === props.auth.user.id
      || project.value.manager_id === props.auth.user.id
})

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
    <Head title="Проект" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-200">
                Проект: {{ project?.name ?? '...' }}
            </h2>
        </template>

        <div class="max-w-4xl mx-auto py-10 px-4">
            <div v-if="project">
                <p style="color: aliceblue;"><strong>Компания:</strong> {{ project.company?.name }}</p>
                <p style="color: aliceblue;"><strong>Руководитель:</strong> {{ project.manager?.name }}</p>
                <p style="color: aliceblue;"><strong>Инициатор:</strong> {{ project.initiator?.name }}</p>
                <p style="color: aliceblue;"><strong>Дата начала:</strong> {{ project.start_date }}</p>
                <p style="color: aliceblue;"><strong>Длительность:</strong> {{ project.duration_days }} дней</p>
               <p style="color: aliceblue;"><strong>Описание:</strong> {{ project.description }} </p>
                <p style="color: aliceblue;"><strong>Бюджет:</strong> {{ project.budget }} Р</p>

                <div class="mt-6"
     v-if="props.auth.roles.includes('admin') 
        || project.company?.user_id === props.auth.user.id 
        || project.manager_id === props.auth.user.id">
    <button @click="() => { showTaskModal = true; fetchEmployees() }"
        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
        Создать задачу
    </button>
</div>

 <div class="flex gap-3 mt-4">
          <button
            v-if="canEditBudget"
            @click="showBudgetModal = true"
            class="bg-amber-600 text-white px-4 py-2 rounded hover:bg-amber-700"
          >Бюджет</button>

          <button
            v-if="canEditDescription"
            @click="showDescriptionModal = true"
            class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700"
          >Описание</button>
        </div>

<div v-if="project.tasks?.length" class="mt-10">
    <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">Задачи проекта</h3>
    <div class="space-y-4">
        <div
            v-for="task in project.tasks"
            :key="task.id"
            class="p-4 border rounded bg-white dark:bg-gray-800 shadow cursor-pointer hover:shadow-md transition"
            @click="$inertia.visit(`/tasks/${task.id}`)"
        >
            <h4 class="text-md font-bold text-gray-900 dark:text-white mb-1">{{ task.title }}</h4>

            <p class="text-sm text-gray-700 dark:text-gray-300">
                <strong>С:</strong> {{ task.start_date }} — <strong>по:</strong> {{ task.due_date }}
            </p>

            <p class="text-sm text-gray-700 dark:text-gray-300">
                <strong>От:</strong> {{ task.creator?.name ?? '—' }} →
                <strong>Кому:</strong> {{ task.executor?.name ?? '—' }}
            </p>

            <p class="text-sm text-gray-700 dark:text-gray-300">
                <strong>Ответственный:</strong> {{ task.responsible?.name ?? '—' }}
            </p>

            <p class="text-sm text-gray-700 dark:text-gray-300">
                <strong>Важность:</strong>
                <span :class="{
                    'text-green-600': task.priority === 'low',
                    'text-yellow-600': task.priority === 'medium',
                    'text-red-600': task.priority === 'high'
                }">
                    {{
                        task.priority === 'low' ? 'Обычная' :
                        task.priority === 'medium' ? 'Средняя' :
                        'Высокая'
                    }}
                </span>
            </p>

            <div v-if="task.files?.length" class="mt-2">
                <p class="text-sm text-gray-700 dark:text-gray-300 font-semibold">Файлы:</p>
                <ul class="list-disc list-inside">
                    <li v-for="file in task.files" :key="file.id">
                        <a :href="`/storage/${file.file_path}`" target="_blank" class="text-blue-600 hover:underline">
                            {{ file.file_path.split('/').pop() }}
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>




            </div>


            
            <div v-else>
                Загрузка данных проекта...
            </div>
        </div>

        <!-- Модалка создания задачи -->
        <div v-if="showTaskModal"
             class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
            <div class="bg-white dark:bg-gray-800 p-6 rounded shadow w-full max-w-lg">
                <h2 class="text-xl font-bold mb-4 text-gray-800 dark:text-white">Создание задачи</h2>
                <form @submit.prevent="createTask">
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1">Название задачи</label>
                        <input v-model="taskForm.title" class="w-full border rounded px-3 py-2" required />
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1">Исполнитель</label>
                        <select v-model="taskForm.executor_id" class="w-full border rounded px-3 py-2" required>
                            <option disabled value="">Выберите исполнителя</option>
                            <option v-for="user in employees" :key="user.id" :value="user.id">
                                {{ user.name }}
                            </option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1">Ответственный</label>
                        <select v-model="taskForm.responsible_id" class="w-full border rounded px-3 py-2" required>
                            <option disabled value="">Выберите ответственного</option>
                            <option v-for="user in employees" :key="user.id" :value="user.id">
                                {{ user.name }}
                            </option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1">Важность</label>
                        <select v-model="taskForm.priority" class="w-full border rounded px-3 py-2">
                            <option value="normal">Обычная</option>
                            <option value="medium">Средняя</option>
                            <option value="high">Высокая</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1">Дата начала</label>
                        <input type="date" v-model="taskForm.start_date"
                               class="w-full border rounded px-3 py-2" required />
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1">Дата окончания</label>
                        <input type="date" v-model="taskForm.due_date"
                               class="w-full border rounded px-3 py-2" required />
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1">Файлы (pdf, excel, word)</label>
                        <input type="file" multiple @change="handleFileUpload" accept=".pdf,.doc,.docx,.xls,.xlsx"
                               class="w-full border rounded px-3 py-2 bg-white dark:bg-gray-700" />
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="showTaskModal = false"
                                class="bg-gray-500 text-white px-4 py-2 rounded">
                            Отмена
                        </button>
                        <button type="submit"
                                class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                            Создать
                        </button>
                    </div>
                </form>
            </div>



  

            
        </div>


<div
      v-if="showBudgetModal && canEditBudget"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    >
      <div class="bg-white dark:bg-gray-800 p-6 rounded shadow-lg w-full max-w-md">
        <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">Установить бюджет</h3>
        <form @submit.prevent="saveBudget">
          <label class="block text-sm mb-1 text-gray-700 dark:text-gray-300">Сумма (₽)</label>
          <input
            v-model="budgetForm.budget"
            type="number" step="0.01" min="0"
            class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-white"
            required
          />
          <div class="flex justify-end gap-2 mt-4">
            <button type="button" @click="showBudgetModal = false" class="px-4 py-2 bg-gray-500 text-white rounded">
              Отмена
            </button>
            <button type="submit" class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700">
              Сохранить
            </button>
          </div>
        </form>
      </div>
    </div>

<div
      v-if="showDescriptionModal && canEditDescription"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    >
      <div class="bg-white dark:bg-gray-800 p-6 rounded shadow-lg w-full max-w-xl">
        <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">Описание проекта</h3>
        <form @submit.prevent="saveDescription">
          <label class="block text-sm mb-1 text-gray-700 dark:text-gray-300">Описание</label>
          <textarea
            v-model="descriptionForm.description"
            rows="6"
            class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-white"
            required />
          <div class="flex justify-end gap-2 mt-4">
            <button type="button" @click="showDescriptionModal = false" class="px-4 py-2 bg-gray-500 text-white rounded">
              Отмена
            </button>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
              Сохранить
            </button>
          </div>
        </form>
      </div>
    </div>

    </AuthenticatedLayout>
</template>
