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
const searchQuery = ref('')

const showManagersModal = ref(false)
const managersAll = ref([])
const selectedManagerIds = ref([])
const managersSearchQuery = ref('')

const showUploadModal = ref(false)
const visibility = ref('company_all')
const allowedUsers = ref([])
const selectedAllowedUserIds = ref([])
const uploadFiles = ref(null)
const uploadSearchQuery = ref('')
const isUploading = ref(false)

const isOwner = computed(() => company.value?.user_id === auth.user.id)
const canUpload = computed(() => {
    if (!company.value) return false
    const managers = (company.value.storage_managers || []).map(u => u.id)
    return isOwner.value || managers.includes(auth.user.id)
})

// Фильтрация файлов по поиску
const filteredFiles = computed(() => {
    if (!searchQuery.value) return files.value
    return files.value.filter(f =>
        f.original_name.toLowerCase().includes(searchQuery.value.toLowerCase())
    )
})

// Фильтрация сотрудников для менеджеров
const filteredManagers = computed(() => {
    if (!managersSearchQuery.value) return managersAll.value
    return managersAll.value.filter(u =>
        u.name.toLowerCase().includes(managersSearchQuery.value.toLowerCase())
    )
})

// Фильтрация сотрудников для загрузки
const filteredAllowedUsers = computed(() => {
    if (!uploadSearchQuery.value) return allowedUsers.value
    return allowedUsers.value.filter(u =>
        u.name.toLowerCase().includes(uploadSearchQuery.value.toLowerCase())
    )
})

// Форматирование размера файла
const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 B'
    const k = 1024
    const sizes = ['B', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i]
}

const fetchCompany = async () => {
    const { data } = await axios.get(`/api/storage/companies/${companyId}`)
    company.value = data.company
    files.value = data.files
}
const fetchEmployees = async () => {
    const { data } = await axios.get(`/api/companies/${companyId}/employees`)
    managersAll.value = data
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
    if (!uploadFiles.value || uploadFiles.value.length === 0) {
        alert('Выберите файлы для загрузки')
        return
    }

    isUploading.value = true
    const fd = new FormData()
    fd.append('visibility', visibility.value)
    if (visibility.value === 'selected') {
        selectedAllowedUserIds.value.forEach(id => fd.append('allowed_user_ids[]', id))
    }
    for (let i = 0; i < uploadFiles.value.length; i++) {
        fd.append('files[]', uploadFiles.value[i])
    }

    try {
        await axios.post(`/api/storage/companies/${companyId}/files`, fd, {
            headers: { 'Content-Type': 'multipart/form-data' }
        })
        showUploadModal.value = false
        await fetchCompany()
    } finally {
        isUploading.value = false
    }
}

const download = (fileId) => {
    window.location = `/api/storage/files/${fileId}/download`
}

const removeFile = async (fileId) => {
    if (!confirm('Вы уверены, что хотите удалить этот файл?')) return
    await axios.delete(`/api/storage/files/${fileId}`)
    await fetchCompany()
}

onMounted(fetchCompany)
</script>

<template>
    <Head :title="company?.name ? `Хранилище: ${company.name}` : 'Хранилище'" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <button @click="$inertia.visit('/file-storage')"
                            class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-gray-600 dark:text-gray-300">
                        ←
                    </button>
                    <div>
                        <h2 class="text-2xl font-bold bg-gradient-to-r from-gray-900 to-gray-600 dark:from-white dark:to-gray-300 bg-clip-text text-transparent">
                            {{ company?.name ?? '...' }}
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Хранилище компании • {{ files.length }} {{ files.length === 1 ? 'файл' : 'файлов' }}
                        </p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button v-if="isOwner" @click="openManagers"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-amber-600 text-white
                         hover:bg-amber-700 transition-colors shadow-sm hover:shadow text-sm font-medium">
                        👥 <span class="hidden sm:inline">Менеджеры</span>
                    </button>
                    <button v-if="canUpload" @click="openUpload"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white
                         hover:bg-blue-700 transition-colors shadow-sm hover:shadow text-sm font-medium">
                        📤 <span class="hidden sm:inline">Загрузить</span>
                    </button>
                </div>
            </div>
        </template>

        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <!-- Поиск -->
            <div v-if="files.length > 0" class="mb-6">
                <div class="relative max-w-md">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">🔍</span>
                    <input type="text" v-model="searchQuery"
                           placeholder="Поиск файлов..."
                           class="w-full pl-10 pr-4 py-2 border border-gray-200 dark:border-gray-700 rounded-lg
                        bg-white dark:bg-gray-800 text-gray-900 dark:text-white
                        focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                </div>
            </div>

            <!-- Список файлов -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <!-- Заголовок -->
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                    <h3 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        📄 Файлы компании
                    </h3>
                </div>

                <!-- Пустое состояние -->
                <div v-if="!files.length" class="text-center py-16 px-6">
                    <div class="bg-gray-50 dark:bg-gray-800/50 rounded-2xl p-8 max-w-sm mx-auto">
                        <div class="text-6xl mb-4">📁</div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                            Файлов пока нет
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                            Загрузите первые файлы в хранилище компании
                        </p>
                        <button v-if="canUpload" @click="openUpload"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg
                           hover:bg-blue-700 transition-colors mx-auto text-sm font-medium">
                            📤 Загрузить файлы
                        </button>
                    </div>
                </div>

                <!-- Таблица файлов -->
                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Имя файла
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Размер
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Доступ
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Загрузил
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Действия
                            </th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="f in filteredFiles" :key="f.id"
                            class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <span class="text-gray-400">📄</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                      {{ f.original_name }}
                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ formatFileSize(f.size) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                  <span v-if="f.visibility === 'company_all'"
                        class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs
                               bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400">
                    🌐 Все сотрудники
                  </span>
                                <span v-else
                                      class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs
                               bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400">
                        🔒 Выбранные
                  </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ f.uploader?.name ?? '—' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right space-x-2">
                                <button @click="download(f.id)"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg
                                 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400
                                 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-colors text-sm">
                                    ⬇️ <span class="hidden sm:inline">Скачать</span>
                                </button>
                                <button v-if="canUpload" @click="removeFile(f.id)"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg
                                 bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400
                                 hover:bg-red-100 dark:hover:bg-red-900/50 transition-colors text-sm">
                                    🗑️ <span class="hidden sm:inline">Удалить</span>
                                </button>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Модалка менеджеров -->
        <Teleport to="body">
            <div v-if="showManagersModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-center justify-center min-h-screen p-4">
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="showManagersModal = false"></div>

                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-lg transform transition-all">
                        <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                            <div>
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white" id="modal-title">
                                    Менеджеры хранилища
                                </h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    Могут загружать и удалять файлы
                                </p>
                            </div>
                            <button @click="showManagersModal = false"
                                    class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-gray-500">
                                ✕
                            </button>
                        </div>

                        <div class="p-6">
                            <!-- Поиск сотрудников -->
                            <div class="relative mb-4">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">🔍</span>
                                <input type="text" v-model="managersSearchQuery"
                                       placeholder="Поиск сотрудников..."
                                       class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 dark:border-gray-700 rounded-lg
                              bg-white dark:bg-gray-800 text-gray-900 dark:text-white" />
                            </div>

                            <!-- Список сотрудников -->
                            <div class="space-y-2 max-h-64 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                                <label v-for="u in filteredManagers" :key="u.id"
                                       class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer">
                                    <input type="checkbox" :value="u.id" v-model="selectedManagerIds"
                                           class="w-4 h-4 text-amber-600 rounded border-gray-300 focus:ring-amber-500" />
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ u.name }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ u.email }}</p>
                                    </div>
                                </label>
                                <div v-if="filteredManagers.length === 0" class="text-center py-4 text-gray-500">
                                    Сотрудники не найдены
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 p-6 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                            <button @click="showManagersModal = false"
                                    class="px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700
                             text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                Отмена
                            </button>
                            <button @click="saveManagers"
                                    class="px-4 py-2 rounded-lg bg-amber-600 text-white hover:bg-amber-700
                             transition-colors shadow-sm hover:shadow flex items-center gap-2">
                                ✓ Сохранить
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Модалка загрузки -->
            <div v-if="showUploadModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-center justify-center min-h-screen p-4">
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="showUploadModal = false"></div>

                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-lg transform transition-all">
                        <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                            <div>
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white" id="modal-title">
                                    Загрузить файлы
                                </h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    Выберите файлы и настройте доступ
                                </p>
                            </div>
                            <button @click="showUploadModal = false"
                                    class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-gray-500">
                                ✕
                            </button>
                        </div>

                        <div class="p-6 space-y-4">
                            <!-- Настройка доступа -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Кто имеет доступ
                                </label>
                                <div class="grid grid-cols-2 gap-2">
                                    <button @click="visibility = 'company_all'"
                                            :class="[
                            'flex items-center justify-center gap-2 p-3 rounded-lg border transition-colors text-sm',
                            visibility === 'company_all'
                              ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400'
                              : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'
                          ]">
                                        🌐 Все сотрудники
                                    </button>
                                    <button @click="visibility = 'selected'"
                                            :class="[
                            'flex items-center justify-center gap-2 p-3 rounded-lg border transition-colors text-sm',
                            visibility === 'selected'
                              ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400'
                              : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'
                          ]">
                                        🔒 Выбранные
                                    </button>
                                </div>
                            </div>

                            <!-- Выбор сотрудников (если выбран режим "selected") -->
                            <div v-if="visibility === 'selected'">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Кому открыть доступ
                                </label>

                                <!-- Поиск сотрудников -->
                                <div class="relative mb-3">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">🔍</span>
                                    <input type="text" v-model="uploadSearchQuery"
                                           placeholder="Поиск сотрудников..."
                                           class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 dark:border-gray-700 rounded-lg
                                bg-white dark:bg-gray-800 text-gray-900 dark:text-white" />
                                </div>

                                <!-- Список сотрудников -->
                                <div class="space-y-2 max-h-48 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                                    <label v-for="u in filteredAllowedUsers" :key="u.id"
                                           class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer">
                                        <input type="checkbox" :value="u.id" v-model="selectedAllowedUserIds"
                                               class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500" />
                                        <span class="text-sm text-gray-900 dark:text-white">{{ u.name }}</span>
                                    </label>
                                    <div v-if="filteredAllowedUsers.length === 0" class="text-center py-4 text-gray-500">
                                        Сотрудники не найдены
                                    </div>
                                </div>
                            </div>

                            <!-- Загрузка файлов -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Выберите файлы
                                </label>
                                <div class="border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-lg p-6
                            hover:border-blue-500 dark:hover:border-blue-400 transition-colors">
                                    <input type="file" multiple @change="onUploadChange"
                                           class="w-full text-sm text-gray-500 dark:text-gray-400
                                file:mr-4 file:py-2 file:px-4
                                file:rounded-lg file:border-0
                                file:text-sm file:font-semibold
                                file:bg-blue-50 file:text-blue-700
                                dark:file:bg-blue-900/30 dark:file:text-blue-400
                                hover:file:bg-blue-100 dark:hover:file:bg-blue-900/50" />
                                    <p v-if="uploadFiles" class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                        Выбрано файлов: {{ uploadFiles.length }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 p-6 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                            <button @click="showUploadModal = false"
                                    class="px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700
                             text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                Отмена
                            </button>
                            <button @click="doUpload" :disabled="isUploading"
                                    class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700
                             transition-colors shadow-sm hover:shadow flex items-center gap-2
                             disabled:opacity-50 disabled:cursor-not-allowed">
                                <span v-if="isUploading" class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                <span v-else>📤</span>
                                {{ isUploading ? 'Загрузка...' : 'Загрузить' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Анимации для модальных окон */
.fixed {
    animation: fadeIn 0.2s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

/* Стили для скролла */
.overflow-y-auto {
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 #f1f5f9;
}

.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.dark .overflow-y-auto::-webkit-scrollbar-track {
    background: #1f2937;
}

.dark .overflow-y-auto::-webkit-scrollbar-thumb {
    background: #4b5563;
}
</style>
