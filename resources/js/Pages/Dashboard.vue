<script setup>
import { ref, onMounted, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head } from '@inertiajs/vue3'
import axios from 'axios'

const showModal = ref(false)
const companies = ref([])
const form = ref({ name: '', logo: null })

axios.defaults.withCredentials = true
axios.defaults.baseURL = 'http://127.0.0.1:8000'

const { props } = usePage()
// roles приходит массивом из HandleInertiaRequests
const isAdmin = computed(() => (props.auth?.roles || []).includes('admin'))

const fetchCompanies = async () => {
  await axios.get('/sanctum/csrf-cookie')
  const { data } = await axios.get('/api/companies')
  companies.value = data
}

const onFileChange = (e) => { form.value.logo = e.target.files[0] }

const createCompany = async () => {
  await axios.get('/sanctum/csrf-cookie')
  const payload = new FormData()
  payload.append('name', form.value.name)
  if (form.value.logo) payload.append('logo', form.value.logo)

  await axios.post('/api/companies', payload, {
    headers: { 'Content-Type': 'multipart/form-data' },
    withCredentials: true,
  })

  showModal.value = false
  form.value = { name: '', logo: null }
  await fetchCompanies()
}

onMounted(fetchCompanies)
</script>

<template>
  <Head title="Dashboard" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Dashboard</h2>
    </template>

    <div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8">

      <!-- Видно только admin -->
      <template v-if="isAdmin">
        <div class="mb-4">
          <button @click="showModal = true"
                  class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
            Создать компанию
          </button>
        </div>

        <button @click="$inertia.visit('/employees')"
                class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
          Сотрудники
        </button>

        <!-- Модалка тоже только для admin -->
        <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div class="bg-white dark:bg-gray-800 p-6 rounded shadow-lg w-full max-w-md">
            <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">Новая компания</h3>
            <form @submit.prevent="createCompany">
              <div class="mb-4">
                <label class="block text-sm mb-1 text-gray-700 dark:text-gray-300">Название *</label>
                <input v-model="form.name" type="text" required
                       class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-white" />
              </div>
              <div class="mb-4">
                <label class="block text-sm mb-1 text-gray-700 dark:text-gray-300">Логотип</label>
                <input type="file" @change="onFileChange"
                       class="w-full dark:bg-gray-700 text-white" />
              </div>
              <div class="flex justify-end gap-2">
                <button type="button" @click="showModal = false"
                        class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500">
                  Отмена
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                  Создать
                </button>
              </div>
            </form>
          </div>
        </div>
      </template>

      <button 
    @click="$inertia.visit('/calendar')" 
    class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 transition"
>
    Календарь
</button>

      <!-- Список компаний (виден всем, кого пускает API) -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-6">
        <div v-for="company in companies" :key="company.id"
             class="border rounded p-4 bg-white dark:bg-gray-700 hover:shadow transition cursor-pointer"
             @click="$inertia.visit(`/companies/${company.id}`)">
          <img v-if="company.logo" :src="`/storage/${company.logo}`" alt="logo"
               class="w-16 h-16 mb-2 object-cover" />
          <h4 class="text-lg font-semibold text-gray-800 dark:text-white">{{ company.name }}</h4>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
