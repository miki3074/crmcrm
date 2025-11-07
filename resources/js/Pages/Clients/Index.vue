<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import axios from 'axios'
import { Head, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'


const { props } = usePage()
// === данные ===
const clients = ref([])
const showModal = ref(false)
const activeTab = ref('jur') // "jur" — юр лицо, "fiz" — физ лицо
const isInProject = ref(false)

const companies = ref([])

const form = ref({
  type: 'jur',
  name: '',
  organization_name: '',
  description: '',
  city: '',
  address: '',
  phone: '',
  email: '',
  project_id: null,
  responsible_id: null,
  company_id: null,
})

// === справочники ===
const projects = ref([])
const employees = ref([]) // общий список сотрудников
const responsibleOptions = ref([]) // список ответственных, который отображается в select
const loadingEmployees = ref(false)

// === загрузка клиентов ===
const fetchClients = async () => {
  const { data } = await axios.get('/api/clients')
  clients.value = data
}

// === загрузка проектов владельца (с компаниями) ===
const fetchProjects = async () => {
  const { data } = await axios.get('api/projects/grouped')
  companies.value = data // ← сохраняем как есть
 
}


// === загрузка всех сотрудников владельца ===
const fetchEmployees = async () => {
  const { data } = await axios.get('/api/employeesqw') // уникальные сотрудники (distinct)
  employees.value = data
  responsibleOptions.value = data
}

// === следим за выбором проекта ===
watch(() => form.value.project_id, async (pid) => {
  form.value.responsible_id = null

  // если клиент не в проекте или проект не выбран — показываем всех
  if (!isInProject.value || !pid) {
    responsibleOptions.value = employees.value
    return
  }

  // ищем компанию, которой принадлежит проект
  const project = companies.value.flatMap(c => c.projects).find(p => p.id === pid)
  if (!project?.company_id) {
    responsibleOptions.value = []
    return
  }

  // загружаем сотрудников конкретной компании
  loadingEmployees.value = true
  try {
    const { data } = await axios.get(`/api/companies/${project.company_id}/employees`)
    responsibleOptions.value = data
  } catch (err) {
    console.error('Ошибка при загрузке сотрудников компании:', err)
  } finally {
    loadingEmployees.value = false
  }
})

// === создание клиента ===
const createClient = async () => {
  const payload = {
    ...form.value,
    type: activeTab.value,
  }

  await axios.post('/api/clients', payload)

  showModal.value = false
  form.value = {
    type: activeTab.value,
    name: '',
    organization_name: '',
    description: '',
    city: '',
    address: '',
    phone: '',
    email: '',
    project_id: null,
    responsible_id: null,
    company_id: null,
  }
  isInProject.value = false
  await fetchClients()
}


const myClients = computed(() =>
  clients.value.filter(c => c.created_by === props.auth.user.id)
)

const responsibleClients = computed(() =>
  clients.value.filter(
    c => c.responsible_id === props.auth.user.id && c.created_by !== props.auth.user.id
  )
)


const uniqueEmployees = computed(() => {
  const seen = new Set()
  return employees.value.filter(e => {
    if (seen.has(e.id)) return false
    seen.add(e.id)
    return true
  })
})




onMounted(async () => {
  await Promise.all([fetchClients(), fetchProjects(), fetchEmployees()])
})
</script>


<template>
  <Head title="Клиенты" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-slate-700 dark:text-slate-200">Клиенты</h2>
    </template>

    <div class="max-w-7xl mx-auto p-6 space-y-6">
      <!-- кнопка -->
      <button
        @click="showModal = true"
        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl font-semibold shadow"
      >
        ➕ Новый клиент
      </button>

      <!-- список клиентов -->
     <div class="mt-8">
  <!-- Мои клиенты -->
  <h2 class="text-xl font-semibold text-slate-700 dark:text-slate-200 mb-4">
    👤 Клиенты
  </h2>

  <div v-if="myClients.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    <div
      v-for="c in myClients"
      :key="c.id"
      class="p-4 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-md cursor-pointer transition"
      @click="$inertia.visit(`/clients/${c.id}`)"
    >
      <div class="flex items-center gap-3 mb-2">
        <span
          class="px-3 py-1 text-xs rounded-full"
          :class="c.type === 'jur' ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700'"
        >
          {{ c.type === 'jur' ? 'Юридическое лицо' : 'Физическое лицо' }}
        </span>
      </div>
      <h3 class="font-bold text-lg text-slate-700 dark:text-slate-100">{{ c.name }}</h3>
    </div>
  </div>
  <p v-else class="text-slate-400 text-sm">Нет клиентов, которых вы создали.</p>
</div>

<!-- Клиенты, где я ответственный -->
<div class="mt-12">
  <h2 class="text-xl font-semibold text-slate-700 dark:text-slate-200 mb-4">
    💼 Клиенты, где я ответственный
  </h2>

  <div v-if="responsibleClients.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    <div
      v-for="c in responsibleClients"
      :key="c.id"
      class="p-4 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-md cursor-pointer transition"
      @click="$inertia.visit(`/clients/${c.id}`)"
    >
      <div class="flex items-center gap-3 mb-2">
        <span
          class="px-3 py-1 text-xs rounded-full"
          :class="c.type === 'jur' ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700'"
        >
          {{ c.type === 'jur' ? 'Юридическое лицо' : 'Физическое лицо' }}
        </span>
      </div>
      <h3 class="font-bold text-lg text-slate-700 dark:text-slate-100">{{ c.name }}</h3>
    </div>
  </div>
  <p v-else class="text-slate-400 text-sm">Нет клиентов, где вы ответственны.</p>
</div>

    </div>

    <!-- === модалка === -->
    <div
      v-if="showModal"
      class="fixed inset-0 bg-black/50 flex justify-center items-center z-50 px-4"
    >
      <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl shadow-xl w-full max-w-lg relative">
        <h2 class="text-xl font-bold mb-4 text-slate-800 dark:text-white">➕ Новый клиент</h2>

        <!-- табы -->
        <div class="flex mb-4 border-b border-slate-300 dark:border-slate-700">
          <button
            @click="activeTab = 'jur'"
            :class="[
              'flex-1 py-2 font-medium',
              activeTab === 'jur'
                ? 'border-b-2 border-blue-500 text-blue-600 dark:text-blue-400'
                : 'text-slate-500 hover:text-slate-700',
            ]"
          >
            Юр. лицо
          </button>
          <button
            @click="activeTab = 'fiz'"
            :class="[
              'flex-1 py-2 font-medium',
              activeTab === 'fiz'
                ? 'border-b-2 border-blue-500 text-blue-600 dark:text-blue-400'
                : 'text-slate-500 hover:text-slate-700',
            ]"
          >
            Физ. лицо
          </button>
        </div>

        <form @submit.prevent="createClient" class="space-y-3 max-h-[70vh] overflow-y-auto pr-2">

          <!-- ЮР ЛИЦО -->
          <template v-if="activeTab === 'jur'">
            <input
              v-model="form.organization_name"
              placeholder="Название организации *"
              class="input"
              required
            />
            <input v-model="form.name" placeholder="ФИО контактного лица *" class="input" required />
            <textarea v-model="form.description" placeholder="Описание" class="input"></textarea>
            <div class="flex items-center gap-2">
              <label class="text-sm text-slate-600 dark:text-slate-400">Клиент в проекте?</label>
              <input type="checkbox" v-model="isInProject" />
            </div>
            <div v-if="isInProject" class="space-y-2">
              <select v-model="form.project_id" class="input">
  <option value="">Выберите проект</option>
  <optgroup
    v-for="company in companies"
    :key="company.id"
    :label="company.name"
  >
    <option
      v-for="p in company.projects"
      :key="p.id"
      :value="p.id"
    >
      {{ p.name }}
    </option>
  </optgroup>
</select>

            <select v-model="form.responsible_id" class="input">
  <option value="">Ответственный</option>
  <option
    v-for="e in uniqueEmployees"
    :key="e.id"
    :value="e.id"
  >
    {{ e.name }}
  </option>
</select>



<div v-if="loadingEmployees" class="text-xs text-slate-400 mt-1">Загружаем сотрудников…</div>

            </div>
            <div v-else>
              <select v-model="form.responsible_id" class="input">
                <option value="">Ответственный</option>
                <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.name }}</option>
              </select>
            </div>
            <input v-model="form.city" placeholder="Город" class="input" />
            <input v-model="form.address" placeholder="Адрес" class="input" />
            <input v-model="form.email" placeholder="Email" type="email" class="input" />
            <input v-model="form.phone" placeholder="Телефон" class="input" />
          </template>

          <!-- ФИЗ ЛИЦО -->
          <template v-else>
            <input v-model="form.name" placeholder="ФИО *" class="input" required />
            <input v-model="form.phone" placeholder="Телефон" class="input" />
            <input v-model="form.email" placeholder="Email" type="email" class="input" />
            <div class="flex items-center gap-2">
              <label class="text-sm text-slate-600 dark:text-slate-400">Клиент в проекте?</label>
              <input type="checkbox" v-model="isInProject" />
            </div>
            <div v-if="isInProject" class="space-y-2">
             <select v-model="form.project_id" class="input">
  <option value="">Выберите проект</option>
  <optgroup
    v-for="company in companies"
    :key="company.id"
    :label="company.name"
  >
    <option
      v-for="p in company.projects"
      :key="p.id"
      :value="p.id"
    >
      {{ p.name }}
    </option>
  </optgroup>
</select>

              <select v-model="form.responsible_id" class="input">
  <option value="">Ответственный</option>
  <option
    v-for="e in responsibleOptions"
    :key="e.id"
    :value="e.id"
  >
    {{ e.name }}
  </option>
</select>

<div v-if="loadingEmployees" class="text-xs text-slate-400 mt-1">Загружаем сотрудников…</div>

            </div>
            <div v-else>
              <select v-model="form.responsible_id" class="input">
                <option value="">Ответственный</option>
                <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.name }}</option>
              </select>
            </div>
          </template>

          <div class="flex justify-end gap-2 pt-3 border-t border-slate-200 dark:border-slate-700 mt-4">
            <button type="button" @click="showModal = false" class="btn-gray">
              Отмена
            </button>
            <button type="submit" class="btn-green">
              💾 Сохранить
            </button>
          </div>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
.input {
  @apply w-full border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-800;
}
.btn-gray {
  @apply bg-gray-400 hover:bg-gray-500 text-white px-4 py-2 rounded-lg transition;
}
.btn-green {
  @apply bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg transition;
}
</style>
