<script setup>
import { ref, onMounted } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import axios from 'axios'

const users = ref([])
const loading = ref(true)
const errorText = ref('')
const editingUser = ref(null)
const editForm = ref({ name: '', email: '', password: '' })

// загрузка пользователей
const fetchUsers = async () => {
  loading.value = true
  errorText.value = ''
  try {
    const { data } = await axios.get('/api/users', { withCredentials: true })
    users.value = data
  } catch (e) {
    errorText.value = e.response?.data?.message || 'Ошибка загрузки пользователей'
  } finally {
    loading.value = false
  }
}

// открыть форму редактирования
const openEdit = (user) => {
  editingUser.value = user
  editForm.value = { name: user.name, email: user.email, password: '' }
}

// обновить пользователя
const saveUser = async () => {
  if (!editingUser.value) return
  try {
    await axios.put(`/api/users/${editingUser.value.id}`, editForm.value, { withCredentials: true })
    await fetchUsers()
    editingUser.value = null
    alert('Пользователь обновлён')
  } catch (e) {
    alert(e.response?.data?.message || 'Ошибка при обновлении')
  }
}

// удалить пользователя
const deleteUser = async (userId) => {
  if (!confirm('Удалить пользователя?')) return
  try {
    await axios.delete(`/api/users/${userId}`, { withCredentials: true })
    await fetchUsers()
    alert('Пользователь удалён')
  } catch (e) {
    alert(e.response?.data?.message || 'Ошибка при удалении')
  }
}

onMounted(fetchUsers)
</script>

<template>
  <AuthenticatedLayout>
    <div class="p-6">
      <h1 class="text-2xl font-semibold text-gray-700 dark:text-gray-100 mb-4">
        Управление пользователями
      </h1>

      <p v-if="errorText" class="text-red-600 mb-3">{{ errorText }}</p>
      <p v-if="loading" class="text-gray-500">Загрузка...</p>

      <table v-else class="w-full border-collapse border border-gray-200 dark:border-gray-700">
        <thead class="bg-gray-100 dark:bg-gray-800">
          <tr>
            <th class="border px-3 py-2 text-left">ID</th>
            <th class="border px-3 py-2 text-left">Имя</th>
            <th class="border px-3 py-2 text-left">Email</th>
            <th class="border px-3 py-2 text-left">Действия</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="u in users" :key="u.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
            <td class="border px-3 py-2">{{ u.id }}</td>
            <td class="border px-3 py-2">{{ u.name }}</td>
            <td class="border px-3 py-2">{{ u.email }}</td>
            <td class="border px-3 py-2 flex gap-2">
              <button @click="openEdit(u)" class="text-indigo-600 hover:underline">Редактировать</button>
              <button @click="deleteUser(u.id)" class="text-rose-600 hover:underline">Удалить</button>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- модальное окно редактирования -->
      <div v-if="editingUser" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-96">
          <h3 class="text-lg font-semibold mb-4">Редактировать пользователя</h3>
          <input v-model="editForm.name" type="text" placeholder="Имя"
                 class="w-full border rounded p-2 mb-2 dark:bg-gray-700 dark:text-white" />
          <input v-model="editForm.email" type="email" placeholder="Email"
                 class="w-full border rounded p-2 mb-2 dark:bg-gray-700 dark:text-white" />
          <input v-model="editForm.password" type="password" placeholder="Новый пароль (если нужно)"
                 autocomplete="new-password"
                 class="w-full border rounded p-2 mb-4 dark:bg-gray-700 dark:text-white" />

          <div class="flex justify-end gap-2">
            <button @click="editingUser = null" class="px-4 py-2 border rounded-lg">Отмена</button>
            <button @click="saveUser" class="px-4 py-2 bg-indigo-600 text-white rounded-lg">Сохранить</button>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
