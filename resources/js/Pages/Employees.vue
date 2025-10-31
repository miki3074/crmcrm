<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import axios from 'axios'
import { usePage } from '@inertiajs/vue3'

// базовые настройки
axios.defaults.withCredentials = true

// ===== state
const { props } = usePage()

const loading = ref(false)
const saving  = ref(false)
const list    = ref([])          // сотрудники
const companies = ref([])        // компании владельца

// фильтры / поиск
const q = ref('')
const roleFilter = ref('all')
const companyFilter = ref('all')

// модалка Create
const showModal = ref(false)
const form = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: 'employee',
  company_id: '',
})
const errors = ref({})

// ===== computed
const filtered = computed(() => {
  let rows = [...list.value]
  if (q.value.trim()) {
    const needle = q.value.toLowerCase()
    rows = rows.filter(u =>
      u.name?.toLowerCase().includes(needle) ||
      u.email?.toLowerCase().includes(needle)
    )
  }
  if (roleFilter.value !== 'all') {
    rows = rows.filter(u => (u.roles?.[0]?.name ?? 'employee') === roleFilter.value)
  }
  if (companyFilter.value !== 'all') {
    rows = rows.filter(u => String(u.company?.id) === String(companyFilter.value))
  }
  return rows
})

const badgeClass = (role) => {
  return role === 'manager'
    ? 'bg-indigo-100 text-indigo-700 ring-1 ring-indigo-200'
    : 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200'
}

// ===== api
const fetchCompanies = async () => {
  const { data } = await axios.get('/api/companies')
  companies.value = data
}
const fetchEmployees = async () => {
  loading.value = true
  try {
    const { data } = await axios.get('/api/employees')
    list.value = data
  } finally {
    loading.value = false
  }
}

const resetForm = () => {
  form.value = {
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'employee',
    company_id: '',
  }
  errors.value = {}
}

const submit = async () => {
  errors.value = {}
  saving.value = true
  try {
    await axios.get('/sanctum/csrf-cookie')
    await axios.post('/api/employees', form.value)
    showModal.value = false
    resetForm()
    await fetchEmployees()
  } catch (e) {
    // ожидаем 422 с ошибками валидации
    if (e.response?.status === 422) errors.value = e.response.data.errors ?? {}
    else alert('Не удалось создать сотрудника')
  } finally {
    saving.value = false
  }
}

const showAttachModal = ref(false)
const allUsers = ref([])        // все зарегистрированные пользователи
const ownerCompanies = ref([])  // компании текущего владельца
const attachForm = ref({
  user_id: '',
  role: 'employee',
  company_id: ''
})
const loadingUsers = ref(false)
const attaching = ref(false)
const qAttach = ref('') // поиск в модалке

// Загрузить все зарегистрированные пользователи (исключим текущего владельца)
const loadUsers = async (query = '') => {
  loadingUsers.value = true
  try {
    const { data } = await axios.get('/api/users/for-attach', {
      params: { q: query, company_id: attachForm.value.company_id }
    })
    allUsers.value = data
  } finally {
    loadingUsers.value = false
  }
}



// Загрузить компании текущего владельца (или используемые ранее)
// const loadOwnerCompanies = async () => {
//   const { data } = await axios.get('/api/companies') // у тебя уже есть такой метод; он возвращает только компании владельца
//   ownerCompanies.value = data
//   if (data.length && !attachForm.value.company_id) {
//     attachForm.value.company_id = String(data[0].id)
//   }
// }

const loadOwnerCompanies = async () => {
  const { data } = await axios.get('/api/owner-companies')
  ownerCompanies.value = data
  if (data.length && !attachForm.value.company_id) {
    attachForm.value.company_id = String(data[0].id)
  }
}


const openAttach = async () => {
  showAttachModal.value = true
  await loadOwnerCompanies()
  allUsers.value = [] // пустой список до поиска
}


// Фильтр в модалке
const filteredAttachUsers = computed(() => {
  if (!qAttach.value) return allUsers.value
  const q = qAttach.value.toLowerCase()
  return allUsers.value.filter(u => (u.name || '').toLowerCase().includes(q) || (u.email || '').toLowerCase().includes(q))
})

// Отправка
const attachExisting = async () => {
  if (!attachForm.value.user_id || !attachForm.value.company_id || !attachForm.value.role) return
  attaching.value = true
  try {
    const { data } = await axios.post('/api/employees/attach', {
      user_id: attachForm.value.user_id,
      company_id: attachForm.value.company_id,
      role: attachForm.value.role,
    })
    // Успех — можно закрыть модалку и обновить список сотрудников
    showAttachModal.value = false
    // Обновляем таблицу (если есть метод loadEmployees() — вызови его)
    // await loadEmployees()
    // Сброс формы
    attachForm.value = { user_id: '', role: 'employee', company_id: attachForm.value.company_id }
    alert('Пользователь добавлен')
  } catch (err) {
    console.error(err)
    alert(err?.response?.data?.message || 'Ошибка')
  } finally {
    attaching.value = false
  }
}

const showAttachCompanyModal = ref(false)
const selectedUser = ref(null)
const attachCompanyForm = ref({
  company_id: '',
  role: 'employee'
})


const attachUserToCompany = async () => {
  if (!selectedUser.value?.id || !attachCompanyForm.value.company_id) {
    alert('Выберите компанию')
    return
  }

  try {
    await axios.post('/api/employees/attach', {
      user_id: selectedUser.value.id,
      company_id: attachCompanyForm.value.company_id,
      role: attachCompanyForm.value.role,
    })
    alert('Пользователь присоединён')
    showAttachCompanyModal.value = false
    await fetchEmployees() // обновить список
  } catch (err) {
    console.error(err)
    alert(err?.response?.data?.message || 'Ошибка')
  }
}


const openAttachCompany = async (user) => {
  selectedUser.value = user
  attachCompanyForm.value = { company_id: '', role: 'employee' }
  showAttachCompanyModal.value = true
  await loadOwnerCompanies()
}


const showUpdateModal = ref(false)
const selectedEmployee = ref(null)
const updateForm = ref({
  role: 'employee'
})

const openUpdateModal = (user) => {
  selectedEmployee.value = user
  updateForm.value = {
    role: user.role || 'employee'
  }
  showUpdateModal.value = true
}

const updateEmployeeRole = async () => {
  if (!selectedEmployee.value?.id) return

  try {
    await axios.put(`/api/employees/${selectedEmployee.value.id}/update-role`, {
  role: updateForm.value.role,
  company_id: selectedEmployee.value.company?.id
})
    alert('Роль обновлена')
    showUpdateModal.value = false
    await fetchEmployees()
  } catch (err) {
    console.error(err)
    alert(err?.response?.data?.message || 'Ошибка обновления')
  }
}

import { watch } from 'vue'

let searchTimeout
watch(qAttach, (val) => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    if (val.trim().length >= 2) {
      loadUsers(val.trim())
    } else {
      allUsers.value = [] // если пусто — очищаем
    }
  }, 400)
})



onMounted(async () => {
  await Promise.all([fetchCompanies(), fetchEmployees()])
})
</script>

<template>
  <Head title="Сотрудники" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Сотрудники</h2>
        <button
          class="rounded-xl bg-indigo-600 text-white px-4 py-2 hover:bg-indigo-700"
          @click="showModal = true"
        >
          + Сотрудник
        </button>

<button
  class="ms-3 rounded-xl bg-green-600 text-white px-4 py-2 hover:bg-green-700"
  @click="openAttach"
>
  Пригласить сотрудника
</button>

      </div>
    </template>

    <div class="max-w-6xl mx-auto py-8 px-4">
      <!-- toolbar -->
      <div class="grid gap-3 md:grid-cols-3 mb-4">
        <div>
          <input
            v-model="q"
            placeholder="Поиск по имени или email…"
            class="w-full rounded-lg border px-3 py-2 dark:bg-gray-800 dark:text-white"
          />
        </div>
        <div>
          <select v-model="roleFilter" class="w-full rounded-lg border px-3 py-2 dark:bg-gray-800 dark:text-white">
            <option value="all">Все роли</option>
            <option value="manager">Менеджер</option>
            <option value="employee">Сотрудник</option>
          </select>
        </div>
        <div>
          <select v-model="companyFilter" class="w-full rounded-lg border px-3 py-2 dark:bg-gray-800 dark:text-white">
            <option value="all">Все компании</option>
            <option v-for="c in companies" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
          </select>
        </div>
      </div>

      <!-- table -->
      <div class="overflow-hidden rounded-2xl border dark:border-gray-700 bg-white dark:bg-gray-900">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 dark:bg-gray-800">
            <tr>
              <th class="text-left px-4 py-3">Имя</th>
              <th class="text-left px-4 py-3">Email</th>
              <th class="text-left px-4 py-3">Компания</th>
              <th class="text-left px-4 py-3">Роль</th>
            </tr>
          </thead>

          <tbody v-if="loading">
            <tr v-for="i in 5" :key="i" class="animate-pulse">
              <td class="px-4 py-3"><div class="h-4 w-40 bg-gray-200 dark:bg-gray-700 rounded" /></td>
              <td class="px-4 py-3"><div class="h-4 w-56 bg-gray-200 dark:bg-gray-700 rounded" /></td>
              <td class="px-4 py-3"><div class="h-4 w-40 bg-gray-200 dark:bg-gray-700 rounded" /></td>
              <td class="px-4 py-3"><div class="h-5 w-20 bg-gray-200 dark:bg-gray-700 rounded-full" /></td>
            </tr>
          </tbody>

          <tbody v-else>
            <tr
              v-for="u in filtered"
              :key="u.id"
              class="border-t dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/60"
            >
              <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">{{ u.name }}</td>
              <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ u.email }}</td>
              <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ u.company?.name ?? '—' }}</td>
              <td class="px-4 py-3">
                <span
                  class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-medium ring-1"
                  :class="badgeClass(u.roles?.[0]?.name ?? 'employee')"
                >
                  {{ (u.role === 'manager') ? 'Менеджер' : 'Сотрудник' }}
                </span>
              </td>

             <td class="px-4 py-3 text-right flex gap-2 justify-end">
  <button
    @click="openAttachCompany(u)"
    class="text-xs rounded bg-indigo-600 text-white px-3 py-1 hover:bg-indigo-700">
    Присоединить к компании
  </button>

  <button
    @click="openUpdateModal(u)"
    class="text-xs rounded bg-yellow-500 text-white px-3 py-1 hover:bg-yellow-600">
    Обновить
  </button>
</td>



              
            </tr>
            <tr v-if="!filtered.length">
              <td colspan="4" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                Ничего не найдено
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- modal create -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
      <div class="w-full max-w-xl rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Новый сотрудник</h3>
          <button class="text-gray-400 hover:text-gray-600" @click="showModal=false">✕</button>
        </div>

        <form class="mt-4 space-y-4" @submit.prevent="submit">
          <div>
            <label class="block text-sm mb-1">Имя</label>
            <input v-model="form.name" class="w-full rounded-lg border px-3 py-2 dark:bg-gray-800 dark:text-white" />
            <p v-if="errors.name" class="mt-1 text-xs text-rose-600">{{ errors.name[0] }}</p>
          </div>

          <div>
            <label class="block text-sm mb-1">Email</label>
            <input v-model="form.email" type="email" class="w-full rounded-lg border px-3 py-2 dark:bg-gray-800 dark:text-white" />
            <p v-if="errors.email" class="mt-1 text-xs text-rose-600">{{ errors.email[0] }}</p>
          </div>

          <div class="grid md:grid-cols-2 gap-3">
            <div>
              <label class="block text-sm mb-1">Пароль</label>
              <input v-model="form.password" type="password" class="w-full rounded-lg border px-3 py-2 dark:bg-gray-800 dark:text-white" />
              <p v-if="errors.password" class="mt-1 text-xs text-rose-600">{{ errors.password[0] }}</p>
            </div>
            <div>
              <label class="block text-sm mb-1">Подтверждение</label>
              <input v-model="form.password_confirmation" type="password" class="w-full rounded-lg border px-3 py-2 dark:bg-gray-800 dark:text-white" />
            </div>
          </div>

          <div class="grid md:grid-cols-2 gap-3">
            <div>
              <label class="block text-sm mb-1">Компания</label>
              <select v-model="form.company_id" required class="w-full rounded-lg border px-3 py-2 dark:bg-gray-800 dark:text-white">
                <option disabled value="">Выберите компанию</option>
                <option v-for="c in companies" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
              <p v-if="errors.company_id" class="mt-1 text-xs text-rose-600">{{ errors.company_id[0] }}</p>
            </div>
            <div>
              <label class="block text-sm mb-1">Роль</label>
              <select v-model="form.role" class="w-full rounded-lg border px-3 py-2 dark:bg-gray-800 dark:text-white">
                <option value="manager">Менеджер</option>
                <option value="employee">Сотрудник</option>
              </select>
            </div>
          </div>

          <div class="flex justify-end gap-2 pt-2">
            <button type="button" class="rounded-lg bg-gray-500 px-4 py-2 text-white" @click="showModal=false">Отмена</button>
            <button
              type="submit"
              class="rounded-lg bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700 disabled:opacity-50"
              :disabled="saving"
            >
              {{ saving ? 'Создаю…' : 'Создать' }}
            </button>
          </div>
        </form>
      </div>
    </div>


    <div v-if="showAttachModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
  <div class="relative w-full max-w-3xl rounded-2xl shadow-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700">
    <!-- Заголовок -->
    <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-700 px-6 py-4">
      <h3 class="text-lg font-semibold text-slate-800 dark:text-slate-100">
        пригласить сотрудника
      </h3>
      <button @click="showAttachModal = false"
              class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
        ✕
      </button>
    </div>

    <!-- Форма поиска -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 px-6 py-4">
      <div>
        <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-1">Поиск</label>
        <input v-model="qAttach"
               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
               placeholder="Имя или email" />
      </div>

      <div>
        <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-1">Компания</label>
        <select v-model="attachForm.company_id"
                class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
          <option v-for="c in ownerCompanies" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
        </select>
      </div>
    </div>

    <!-- Список пользователей -->
    <div class="px-6 pb-4">
  <div class="mb-3 max-h-64 overflow-auto border border-slate-200 dark:border-slate-700 rounded-xl divide-y divide-slate-100 dark:divide-slate-700">
    <template v-if="loadingUsers">
      <div class="p-4 text-sm text-slate-500 text-center">Загрузка пользователей...</div>
    </template>

    <template v-else>
      <div v-for="u in allUsers"
           :key="u.id"
           class="flex items-center justify-between gap-3 p-3 hover:bg-slate-50 dark:hover:bg-slate-800/40 transition rounded">
        <div>
          <div class="font-medium text-slate-800 dark:text-slate-100">{{ u.name }}</div>
          <div class="text-xs text-slate-500">{{ u.email }}</div>
        </div>
        <div class="flex items-center gap-2">
          <label class="text-xs text-slate-500">Роль</label>
          <select v-model="attachForm.role"
                  class="rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-sm px-2 py-1">
            <option value="employee">Сотрудник</option>
            <option value="manager">Менеджер</option>
          </select>

          <button @click="attachForm.user_id = u.id"
                  class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">
            Выбрать
          </button>
        </div>
      </div>
    </template>
  </div>

  <!-- Добавленные сообщения -->
  <template v-if="!loadingUsers && qAttach.trim().length === 0">
    <div class="p-4 text-sm text-slate-500 text-center">
      🔍 Введите имя или email пользователя
    </div>
  </template>

  <template v-else-if="!loadingUsers && qAttach.trim().length >= 2 && allUsers.length === 0">
    <div class="p-4 text-sm text-slate-500 text-center">
      Пользователи не найдены
    </div>
  </template>
</div>


    <!-- Кнопки -->
    <div class="flex justify-end gap-2 px-6 py-4 border-t border-slate-200 dark:border-slate-700">
      <button @click="showAttachModal = false"
              class="px-4 py-2 rounded-xl bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200 hover:opacity-80 transition">
        Отмена
      </button>
      <button @click="attachExisting" :disabled="attaching"
              class="px-4 py-2 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700 disabled:opacity-60 transition">
        {{ attaching ? 'Добавление...' : 'Добавить' }}
      </button>
    </div>
  </div>
</div>

<div v-if="showAttachCompanyModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
  <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-96">
    <h3 class="text-lg font-semibold mb-4">
      Присоединить: {{ selectedUser?.name }}
    </h3>

    <label class="block text-sm mb-2">Компания</label>
    <select v-model="attachCompanyForm.company_id" class="w-full border rounded p-2 mb-3">
      <option value="">-- Выберите компанию --</option>
      <option v-for="c in ownerCompanies" :key="c.id" :value="c.id">{{ c.name }}</option>
    </select>

    <label class="block text-sm mb-2">Роль</label>
    <select v-model="attachCompanyForm.role" class="w-full border rounded p-2 mb-3">
      <option value="manager">Менеджер</option>
      <option value="employee">Сотрудник</option>
    </select>

    <div class="flex justify-end gap-2 mt-4">
      <button @click="showAttachCompanyModal = false" class="px-3 py-1 text-sm rounded bg-gray-200">Отмена</button>
      <button
        @click="attachUserToCompany"
        class="px-3 py-1 text-sm rounded bg-emerald-600 text-white hover:bg-emerald-700">
        Сохранить
      </button>
    </div>
  </div>
</div>



<div v-if="showUpdateModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
  <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-96">
    <h3 class="text-lg font-semibold mb-4">
      Обновить роль: {{ selectedEmployee?.name }}
    </h3>

    <label class="block text-sm mb-2">Роль</label>
    <select v-model="updateForm.role" class="w-full border rounded p-2 mb-3">
      <option value="manager">Менеджер</option>
      <option value="employee">Сотрудник</option>
    </select>

    <div class="flex justify-end gap-2 mt-4">
      <button @click="showUpdateModal = false" class="px-3 py-1 text-sm rounded bg-gray-200">Отмена</button>
      <button
        @click="updateEmployeeRole"
        class="px-3 py-1 text-sm rounded bg-emerald-600 text-white hover:bg-emerald-700">
        Сохранить
      </button>
    </div>
  </div>
</div>


    
  </AuthenticatedLayout>
</template>
