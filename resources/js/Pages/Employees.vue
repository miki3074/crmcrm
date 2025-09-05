<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import axios from 'axios'

// базовые настройки
axios.defaults.withCredentials = true

// ===== state
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
                  {{ (u.roles?.[0]?.name === 'manager') ? 'Менеджер' : 'Сотрудник' }}
                </span>
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
  </AuthenticatedLayout>
</template>
