<script setup>
import { ref, onMounted } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head } from '@inertiajs/vue3'
import axios from 'axios'

const showForm = ref(false)
const form = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: 'employee',
  company_id: '',        // 👈 добавили
})

const employees = ref([])
const companies = ref([]) // 👈 список компаний владельца

const fetchEmployees = async () => {
  try {
    const { data } = await axios.get('/api/employees')
    employees.value = data
  } catch (e) {
    console.error('fetchEmployees error:', e.response?.data ?? e.message)
    alert('Не удалось загрузить сотрудников (смотри консоль).')
  }
}

const fetchCompanies = async () => {
  // если твой /api/companies уже возвращает только компании текущего владельца — ок
  const { data } = await axios.get('/api/companies')
  companies.value = data
}

const createEmployee = async () => {
  await axios.get('/sanctum/csrf-cookie')
  await axios.post('/api/employees', form.value)
  form.value = { name: '', email: '', password: '', password_confirmation: '', role: 'employee', company_id: '' }
  showForm.value = false
  await fetchEmployees()
}

onMounted(async () => {
  await Promise.all([fetchCompanies(), fetchEmployees()])
})
</script>

<template>
  <Head title="Сотрудники" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-xl font-bold text-gray-800 dark:text-white">Сотрудники</h2>
    </template>

    <div class="max-w-4xl mx-auto py-8 px-4">
      <button @click="showForm = !showForm"
              class="mb-4 bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded">
        Создать сотрудника
      </button>

      <div v-if="showForm" class="bg-white dark:bg-gray-800 p-6 rounded shadow-md">
        <form @submit.prevent="createEmployee">
          <div class="mb-4">
            <label>Имя</label>
            <input v-model="form.name" class="w-full p-2 border rounded" />
          </div>
          <div class="mb-4">
            <label>Email</label>
            <input v-model="form.email" type="email" class="w-full p-2 border rounded" />
          </div>
          <div class="mb-4">
            <label>Пароль</label>
            <input v-model="form.password" type="password" class="w-full p-2 border rounded" />
          </div>
          <div class="mb-4">
            <label>Подтверждение пароля</label>
            <input v-model="form.password_confirmation" type="password" class="w-full p-2 border rounded" />
          </div>

          <div class="mb-4">
            <label>Компания</label>
            <select v-model="form.company_id" class="w-full p-2 border rounded" required>
              <option disabled value="">Выберите компанию</option>
              <option v-for="c in companies" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>

          <div class="mb-4">
            <label>Роль</label>
            <select v-model="form.role" class="w-full p-2 border rounded">
              <option value="manager">Менеджер</option>
              <option value="employee">Сотрудник</option>
            </select>
          </div>

          <button type="submit" class="bg-green-600 text-white py-2 px-4 rounded hover:bg-green-700">
            Создать
          </button>
        </form>
      </div>

      <div v-if="employees.length" class="mt-6">
        <table class="w-full table-auto border-collapse">
          <thead>
          <tr class="bg-gray-200 dark:bg-gray-700">
            <th class="border px-4 py-2 text-left">Имя</th>
            <th class="border px-4 py-2 text-left">Email</th>
            <th class="border px-4 py-2 text-left">Компания</th>
            <th class="border px-4 py-2 text-left">Роль</th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="user in employees" :key="user.id" class="hover:bg-gray-100 dark:hover:bg-gray-800">
            <td class="border px-4 py-2">{{ user.name }}</td>
            <td class="border px-4 py-2">{{ user.email }}</td>
            <td class="border px-4 py-2">{{ user.company?.name ?? '—' }}</td>
            <td class="border px-4 py-2">
              {{ (user.roles?.[0]?.name === 'manager') ? 'Менеджер' : 'Сотрудник' }}
            </td>
          </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
