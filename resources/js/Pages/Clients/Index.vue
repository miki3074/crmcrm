<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const clients = ref([])
const showModal = ref(false)
const form = ref({ name: '', phone: '', email: '', notes: '' })

const fetchClients = async () => {
  const { data } = await axios.get('/api/clients')
  clients.value = data
}

const createClient = async () => {
  await axios.post('/api/clients', form.value)
  form.value = { name: '', phone: '', email: '', notes: '' }
  showModal.value = false
  await fetchClients()
}

onMounted(fetchClients)
</script>

<template>
  <Head title="Клиенты" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl">Клиенты</h2>
    </template>

    <div class="max-w-6xl mx-auto p-6">
      <button @click="showModal = true" class="bg-blue-600 text-white px-4 py-2 rounded">+ Новый клиент</button>

      <!-- Список клиентов -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-6">
        <div v-for="c in clients" :key="c.id" class="p-4 bg-white rounded shadow cursor-pointer"
             @click="$inertia.visit(`/clients/${c.id}`)">
          <h3 class="font-bold text-lg">{{ c.name }}</h3>
          <p class="text-sm text-gray-600">{{ c.email }}</p>
          <p class="text-sm text-gray-600">{{ c.phone }}</p>
        </div>
      </div>
    </div>

    <!-- Модалка -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
      <div class="bg-white p-6 rounded shadow-lg w-full max-w-md">
        <h2 class="text-xl font-bold mb-4">Новый клиент</h2>
        <form @submit.prevent="createClient">
          <input v-model="form.name" placeholder="Имя" class="w-full border p-2 mb-2" required />
          <input v-model="form.phone" placeholder="Телефон" class="w-full border p-2 mb-2" />
          <input v-model="form.email" placeholder="Email" type="email" class="w-full border p-2 mb-2" />
          <textarea v-model="form.notes" placeholder="Заметки" class="w-full border p-2 mb-2"></textarea>
          <div class="flex justify-end gap-2">
            <button type="button" @click="showModal = false" class="bg-gray-400 text-white px-3 py-2 rounded">Отмена</button>
            <button type="submit" class="bg-green-600 text-white px-3 py-2 rounded">Сохранить</button>
          </div>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>