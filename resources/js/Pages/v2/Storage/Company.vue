<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import { usePage, Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const { props } = usePage()
const companyId = props.id
const auth = props.auth

const company = ref(null)
const files = ref([])

const showManagersModal = ref(false)
const managersAll = ref([]) // сотрудники для выбора менеджеров
const selectedManagerIds = ref([])

const showUploadModal = ref(false)
const visibility = ref('company_all')
const allowedUsers = ref([])
const selectedAllowedUserIds = ref([])
const uploadFiles = ref(null)

const isOwner = computed(() => company.value?.user_id === auth.user.id)
const canUpload = computed(() => {
  if (!company.value) return false
  const managers = (company.value.storage_managers || []).map(u => u.id)
  return isOwner.value || managers.includes(auth.user.id)
})

const fetchCompany = async () => {
  const { data } = await axios.get(`/api/storage/companies/${companyId}`)
  company.value = data.company
  files.value = data.files
}
const fetchEmployees = async () => {
  const { data } = await axios.get(`/api/companies/${companyId}/employees`)
  managersAll.value = data  // используем уже готовый эндпоинт сотрудников
  allowedUsers.value = data
}

const openManagers = async () => {
  if (!isOwner.value) return
  await fetchEmployees()
  selectedManagerIds.value = (company.value.storage_managers || []).map(u => u.id)
  showManagersModal.value = true
}
const saveManagers = async () => {
  await axios.post(`/api/storage/companies/${companyId}/managers`, {
    user_ids: selectedManagerIds.value
  })
  showManagersModal.value = false
  await fetchCompany()
}

const onUploadChange = (e) => { uploadFiles.value = e.target.files }
const openUpload = async () => {
  if (!canUpload.value) return
  await fetchEmployees()
  visibility.value = 'company_all'
  selectedAllowedUserIds.value = []
  uploadFiles.value = null
  showUploadModal.value = true
}
const doUpload = async () => {
  const fd = new FormData()
  fd.append('visibility', visibility.value)
  if (visibility.value === 'selected') {
    selectedAllowedUserIds.value.forEach(id => fd.append('allowed_user_ids[]', id))
  }
  if (uploadFiles.value) {
    for (let i=0; i<uploadFiles.value.length; i++) {
      fd.append('files[]', uploadFiles.value[i])
    }
  }
  await axios.post(`/api/storage/companies/${companyId}/files`, fd, {
    headers: { 'Content-Type': 'multipart/form-data' }
  })
  showUploadModal.value = false
  await fetchCompany()
}

const download = (fileId) => {
  window.location = `/api/storage/files/${fileId}/download`
}

const removeFile = async (fileId) => {
  if (!confirm('Удалить файл?')) return
  await axios.delete(`/api/storage/files/${fileId}`)
  await fetchCompany()
}

onMounted(fetchCompany)
</script>

<template>
  <Head :title="company?.name ? `Хранилище: ${company.name}` : 'Хранилище'" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-800 dark:text-gray-200">
          Хранилище: {{ company?.name ?? '...' }}
        </h2>
        <div class="flex gap-2">
          <button v-if="isOwner" @click="openManagers"
                  class="px-3 py-2 rounded bg-amber-600 text-white hover:bg-amber-700">
            Назначить менеджеров
          </button>
          <button v-if="canUpload" @click="openUpload"
                  class="px-3 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">
            Добавить файлы
          </button>
        </div>
      </div>
    </template>

    <div class="max-w-6xl mx-auto py-8 px-4">
      <div class="bg-white dark:bg-gray-800 rounded-lg border p-4">
        <h3 class="font-semibold text-gray-900 dark:text-white mb-3">Файлы</h3>

        <div v-if="!files.length" class="text-gray-500">Файлов пока нет</div>

        <div v-else class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="text-left text-gray-500 border-b">
                <th class="py-2 pr-4">Имя</th>
                <th class="py-2 pr-4">Размер</th>
                <th class="py-2 pr-4">Доступ</th>
                <th class="py-2 pr-4">Загрузил</th>
                <th class="py-2 pr-4"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="f in files" :key="f.id" class="border-b last:border-0">
                <td class="py-2 pr-4 text-gray-900 dark:text-white">{{ f.original_name }}</td>
                <td class="py-2 pr-4 text-gray-700 dark:text-gray-300">{{ (f.size/1024).toFixed(1) }} KB</td>
                <td class="py-2 pr-4">
                  <span v-if="f.visibility==='company_all'" class="px-2 py-1 rounded text-xs bg-green-100 text-green-700">
                    Все сотрудники
                  </span>
                  <span v-else class="px-2 py-1 rounded text-xs bg-purple-100 text-purple-700">
                    Выбранные пользователи
                  </span>
                </td>
                <td class="py-2 pr-4 text-gray-700 dark:text-gray-300">{{ f.uploader?.name ?? '—' }}</td>
                <td class="py-2 pr-0 flex gap-2">
                  <button @click="download(f.id)" class="px-3 py-1 rounded bg-indigo-600 text-white text-xs hover:bg-indigo-700">
                    Скачать
                  </button>
                  <button v-if="canUpload" @click="removeFile(f.id)"
                          class="px-3 py-1 rounded bg-red-600 text-white text-xs hover:bg-red-700">
                    Удалить
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Модалка менеджеров -->
    <div v-if="showManagersModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white dark:bg-gray-800 rounded p-6 w-full max-w-lg">
        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Менеджеры хранилища</h3>
        <p class="text-sm text-gray-500 mb-3">Могут загружать/удалять файлы и видеть раздел.</p>

        <div class="space-y-2 max-h-64 overflow-y-auto border rounded p-2">
          <label v-for="u in managersAll" :key="u.id" class="flex items-center gap-2">
            <input type="checkbox" :value="u.id" v-model="selectedManagerIds" />
            <span class="text-gray-800 dark:text-gray-200">{{ u.name }}</span>
          </label>
        </div>

        <div class="flex justify-end gap-2 mt-4">
          <button @click="showManagersModal=false" class="px-4 py-2 rounded bg-gray-500 text-white">Отмена</button>
          <button @click="saveManagers" class="px-4 py-2 rounded bg-amber-600 text-white hover:bg-amber-700">Сохранить</button>
        </div>
      </div>
    </div>

    <!-- Модалка загрузки -->
    <div v-if="showUploadModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white dark:bg-gray-800 rounded p-6 w-full max-w-lg">
        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Добавить файлы</h3>

        <div class="mb-3">
          <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Доступ</label>
          <select v-model="visibility" class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-white">
            <option value="company_all">Все сотрудники компании</option>
            <option value="selected">Только выбранные пользователи</option>
          </select>
        </div>

        <div v-if="visibility==='selected'" class="mb-3">
          <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Кому</label>
          <div class="space-y-2 max-h-48 overflow-y-auto border rounded p-2">
            <label v-for="u in allowedUsers" :key="u.id" class="flex items-center gap-2">
              <input type="checkbox" :value="u.id" v-model="selectedAllowedUserIds" />
              <span class="text-gray-800 dark:text-gray-200">{{ u.name }}</span>
            </label>
          </div>
        </div>

        <div class="mb-3">
          <input type="file" multiple @change="onUploadChange"
                 class="w-full text-sm border rounded px-3 py-2 bg-white dark:bg-gray-700 dark:text-white" />
        </div>

        <div class="flex justify-end gap-2">
          <button @click="showUploadModal=false" class="px-4 py-2 rounded bg-gray-500 text-white">Отмена</button>
          <button @click="doUpload" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">Загрузить</button>
        </div>
      </div>
    </div>

  </AuthenticatedLayout>
</template>
