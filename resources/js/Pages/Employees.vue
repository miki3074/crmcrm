<script setup>
import { ref, computed, onMounted, watch } from 'vue'
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
        rows = rows.filter(u => (u.role ?? 'employee') === roleFilter.value)
    }
    if (companyFilter.value !== 'all') {
        // Фильтрация по company_id
        rows = rows.filter(u => String(u.company?.id) === String(companyFilter.value))
    }
    return rows
})

// статистика по ролям
const stats = computed(() => ({
    total: list.value.length,
    managers: list.value.filter(u => (u.role ?? 'employee') === 'manager').length,
    employees: list.value.filter(u => (u.role ?? 'employee') === 'employee').length
}))

const badgeClass = (role) => {
    return role === 'manager'
        ? 'bg-gradient-to-r from-indigo-50 to-indigo-100 dark:from-indigo-900/30 dark:to-indigo-900/50 text-indigo-700 dark:text-indigo-300 ring-1 ring-indigo-200 dark:ring-indigo-800'
        : 'bg-gradient-to-r from-emerald-50 to-emerald-100 dark:from-emerald-900/30 dark:to-emerald-900/50 text-emerald-700 dark:text-emerald-300 ring-1 ring-emerald-200 dark:ring-emerald-800'
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
        if (e.response?.status === 422) errors.value = e.response.data.errors ?? {}
        else alert('Не удалось создать сотрудника')
    } finally {
        saving.value = false
    }
}

const showAttachModal = ref(false)
const allUsers = ref([])
const ownerCompanies = ref([])
const attachForm = ref({
    user_id: '',
    role: 'employee',
    company_id: ''
})
const loadingUsers = ref(false)
const attaching = ref(false)
const qAttach = ref('')

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
    allUsers.value = []
}

const filteredAttachUsers = computed(() => {
    if (!qAttach.value) return allUsers.value
    const q = qAttach.value.toLowerCase()
    return allUsers.value.filter(u => (u.name || '').toLowerCase().includes(q) || (u.email || '').toLowerCase().includes(q))
})

const attachExisting = async () => {
    if (!attachForm.value.user_id || !attachForm.value.company_id || !attachForm.value.role) return
    attaching.value = true
    try {
        await axios.post('/api/employees/attach', {
            user_id: attachForm.value.user_id,
            company_id: attachForm.value.company_id,
            role: attachForm.value.role,
        })
        showAttachModal.value = false
        attachForm.value = { user_id: '', role: 'employee', company_id: attachForm.value.company_id }
        await fetchEmployees()
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
        showAttachCompanyModal.value = false
        await fetchEmployees()
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
        showUpdateModal.value = false
        await fetchEmployees()
    } catch (err) {
        console.error(err)
        alert(err?.response?.data?.message || 'Ошибка обновления')
    }
}

let searchTimeout
watch(qAttach, (val) => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        if (val.trim().length >= 2) {
            loadUsers(val.trim())
        } else {
            allUsers.value = []
        }
    }, 400)
})

const deleteEmployee = async (user) => {
    if (!confirm(`Удалить сотрудника «${user.name}»?`)) return;

    try {
        await axios.delete(`/api/employees/${user.id}`, {
            data: { company_id: user.company.id }
        });
        await fetchEmployees();
    } catch (err) {
        console.error(err);
        alert(err?.response?.data?.message || "Ошибка удаления");
    }
};

const showUnifiedModal = ref(false)
const activeEmployeeTab = ref('create')

const openUnifiedModal = async () => {
    showUnifiedModal.value = true
    activeEmployeeTab.value = 'create'
    await loadOwnerCompanies()
}

onMounted(async () => {
    // await Promise.all([fetchCompanies(), fetchEmployees()])
    await Promise.all([loadOwnerCompanies(), fetchEmployees()])
})
</script>

<template>
    <Head title="Сотрудники" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold bg-gradient-to-r from-gray-900 to-gray-600 dark:from-white dark:to-gray-300 bg-clip-text text-transparent">
                        Сотрудники
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Управление сотрудниками и их ролями
                    </p>
                </div>
                <button
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-700 text-white px-5 py-2.5 hover:from-indigo-700 hover:to-indigo-800 transition-all shadow-sm hover:shadow-md font-medium"
                    @click="openUnifiedModal"
                >
                    <span class="text-lg">+</span>
                    Добавить сотрудника
                </button>
            </div>
        </template>

        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <!-- Статистика -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <div class="flex items-center gap-3">
                        <div class="p-3 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
                            <span class="text-2xl">👥</span>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Всего</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <div class="flex items-center gap-3">
                        <div class="p-3 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg">
                            <span class="text-2xl">👔</span>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Менеджеры</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.managers }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <div class="flex items-center gap-3">
                        <div class="p-3 bg-emerald-50 dark:bg-emerald-900/30 rounded-lg">
                            <span class="text-2xl">💼</span>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Сотрудники</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.employees }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Фильтры и поиск -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 mb-6">
                <div class="grid gap-4 md:grid-cols-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Поиск</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">🔍</span>
                            <input
                                v-model="q"
                                placeholder="Поиск по имени или email..."
                                class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow"
                            />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Роль</label>
                        <select v-model="roleFilter"
                                class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-2.5 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <option value="all">Все роли</option>
                            <option value="manager">Менеджер</option>
                            <option value="employee">Сотрудник</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Компания</label>
                        <select v-model="companyFilter"
                                class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-2.5 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <option value="all">Все компании</option>
                            <!-- Изменено: используем ownerCompanies вместо companies -->
                            <option v-for="c in ownerCompanies" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
                        </select>
                    </div>
                </div>

                <!-- Активные фильтры -->
                <div v-if="q || roleFilter !== 'all' || companyFilter !== 'all'" class="flex flex-wrap gap-2 mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Активные фильтры:</span>
                    <span v-if="q" class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300">
            Поиск: {{ q }}
            <button @click="q = ''" class="hover:text-indigo-900">✕</button>
          </span>
                    <span v-if="roleFilter !== 'all'" class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300">
            Роль: {{ roleFilter === 'manager' ? 'Менеджер' : 'Сотрудник' }}
            <button @click="roleFilter = 'all'" class="hover:text-indigo-900">✕</button>
          </span>
                    <span v-if="companyFilter !== 'all'" class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300">
            Компания: {{ companies.find(c => String(c.id) === companyFilter)?.name }}
            <button @click="companyFilter = 'all'" class="hover:text-indigo-900">✕</button>
          </span>
                </div>
            </div>

            <!-- Таблица сотрудников -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="text-left px-6 py-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Сотрудник</th>
                            <th class="text-left px-6 py-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Компания</th>
                            <th class="text-left px-6 py-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Роль</th>
                            <th class="text-left px-6 py-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Тип связи</th>
                            <th class="text-right px-6 py-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Действия</th>
                        </tr>
                        </thead>

                        <tbody v-if="loading" class="divide-y divide-gray-100 dark:divide-gray-700">
                        <tr v-for="i in 5" :key="i" class="animate-pulse">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700"></div>
                                    <div>
                                        <div class="h-4 w-32 bg-gray-200 dark:bg-gray-700 rounded mb-2"></div>
                                        <div class="h-3 w-40 bg-gray-200 dark:bg-gray-700 rounded"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4"><div class="h-4 w-24 bg-gray-200 dark:bg-gray-700 rounded"></div></td>
                            <td class="px-6 py-4"><div class="h-6 w-20 bg-gray-200 dark:bg-gray-700 rounded-full"></div></td>
                            <td class="px-6 py-4"><div class="h-8 w-24 bg-gray-200 dark:bg-gray-700 rounded ml-auto"></div></td>
                        </tr>
                        </tbody>

                        <tbody v-else class="divide-y divide-gray-100 dark:divide-gray-700">
                        <tr
                            v-for="u in filtered"
                            :key="u.id"
                            class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
                        >
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-100 to-indigo-200 dark:from-indigo-900/30 dark:to-indigo-900/50 flex items-center justify-center text-indigo-700 dark:text-indigo-300 font-medium">
                                        {{ u.name?.charAt(0) || '?' }}
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-white">{{ u.name }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ u.email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                                <div class="flex items-center gap-2">
                                    <span class="text-gray-400">🏢</span>
                                    {{ u.company?.name ?? '—' }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                  <span
                      class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-medium ring-1"
                      :class="badgeClass(u.roles?.[0]?.name ?? 'employee')"
                  >
                    <span>{{ (u.role === 'manager') ? '👔' : '💼' }}</span>
                    {{ (u.role === 'manager') ? 'Менеджер' : 'Сотрудник' }}
                  </span>
                            </td>

                            <td class="px-6 py-4">
                                <div v-if="u.relation_type === 'created'" class="flex items-center gap-1.5">
                                    <span class="text-emerald-500">✨</span>
                                    <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/30 px-2 py-1 rounded-full">
                                    Создан
                                </span>
                                </div>
                                <div v-else class="flex items-center gap-1.5">
                                    <span class="text-blue-500">🔗</span>
                                    <span class="text-xs font-medium text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 px-2 py-1 rounded-full">
                                    Прикреплен
                                </span>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        v-if="!u.company"
                                        @click="openAttachCompany(u)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-colors text-xs font-medium"
                                        title="Присоединить к компании"
                                    >
                                        <span>🏢</span>
                                        <span class="hidden sm:inline">Присоединить</span>
                                    </button>

                                    <button
                                        @click="openUpdateModal(u)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 hover:bg-amber-100 dark:hover:bg-amber-900/50 transition-colors text-xs font-medium"
                                        title="Редактировать роль"
                                    >
                                        <span>✏️</span>
                                        <span class="hidden sm:inline">Роль</span>
                                    </button>

                                    <button
                                        @click="deleteEmployee(u)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/50 transition-colors text-xs font-medium"
                                        title="Удалить"
                                    >
                                        <span>🗑️</span>
                                        <span class="hidden sm:inline">Удалить</span>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="!filtered.length">
                            <td colspan="4" class="px-6 py-12 text-center">
                                <div class="text-5xl mb-3">🔍</div>
                                <p class="text-gray-500 dark:text-gray-400 font-medium">Ничего не найдено</p>
                                <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Попробуйте изменить параметры поиска</p>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Унифицированная модалка добавления сотрудника -->
        <Teleport to="body">
            <div v-if="showUnifiedModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-center justify-center min-h-screen p-4">
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="showUnifiedModal = false"></div>

                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-2xl transform transition-all">
                        <!-- Заголовок -->
                        <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                            <div>
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white" id="modal-title">
                                    Добавление сотрудника
                                </h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    Создайте нового или пригласите существующего пользователя
                                </p>
                            </div>
                            <button @click="showUnifiedModal = false"
                                    class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-gray-500">
                                ✕
                            </button>
                        </div>

                        <!-- Табы -->
                        <div class="flex border-b border-gray-200 dark:border-gray-700">
                            <button
                                @click="activeEmployeeTab = 'create'"
                                :class="[
                  'flex-1 py-4 text-sm font-medium transition-colors relative',
                  activeEmployeeTab === 'create'
                    ? 'text-indigo-600 dark:text-indigo-400'
                    : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                ]"
                            >
                <span class="relative z-10">
                  <span class="mr-2">✨</span>
                  Создать нового
                </span>
                                <span v-if="activeEmployeeTab === 'create'"
                                      class="absolute bottom-0 left-0 right-0 h-0.5 bg-indigo-600 dark:bg-indigo-400"></span>
                            </button>

                            <button
                                @click="activeEmployeeTab = 'attach'"
                                :class="[
                  'flex-1 py-4 text-sm font-medium transition-colors relative',
                  activeEmployeeTab === 'attach'
                    ? 'text-indigo-600 dark:text-indigo-400'
                    : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                ]"
                            >
                <span class="relative z-10">
                  <span class="mr-2">🔗</span>
                  Прикрепить существующего
                </span>
                                <span v-if="activeEmployeeTab === 'attach'"
                                      class="absolute bottom-0 left-0 right-0 h-0.5 bg-indigo-600 dark:bg-indigo-400"></span>
                            </button>
                        </div>

                        <!-- Tab 1: Создать нового -->
                        <div v-if="activeEmployeeTab === 'create'" class="p-6">
                            <form @submit.prevent="submit" class="space-y-5">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Имя <span class="text-red-500">*</span>
                                    </label>
                                    <input v-model="form.name"
                                           class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-2.5 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow"
                                           placeholder="Иван Иванов" />
                                    <p v-if="errors.name" class="mt-1.5 text-xs text-red-600">{{ errors.name[0] }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Email <span class="text-red-500">*</span>
                                    </label>
                                    <input v-model="form.email" type="email"
                                           class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-2.5 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                           placeholder="ivan@example.com" />
                                    <p v-if="errors.email" class="mt-1.5 text-xs text-red-600">{{ errors.email[0] }}</p>
                                </div>

                                <div class="grid md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Пароль <span class="text-red-500">*</span>
                                        </label>
                                        <input v-model="form.password" type="password"
                                               class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-2.5 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                               placeholder="••••••••" />
                                        <p v-if="errors.password" class="mt-1.5 text-xs text-red-600">{{ errors.password[0] }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Подтверждение
                                        </label>
                                        <input v-model="form.password_confirmation" type="password"
                                               class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-2.5 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                               placeholder="••••••••" />
                                    </div>
                                </div>

                                <div class="grid md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Компания <span class="text-red-500">*</span>
                                        </label>
                                        <select v-model="form.company_id" required
                                                class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-2.5 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                            <option disabled value="">Выберите компанию</option>
                                            <option v-for="c in ownerCompanies" :key="c.id" :value="c.id">{{ c.name }}</option>
                                        </select>
                                        <p v-if="errors.company_id" class="mt-1.5 text-xs text-red-600">{{ errors.company_id[0] }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Роль
                                        </label>
                                        <select v-model="form.role"
                                                class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-2.5 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                            <option value="employee">Сотрудник</option>
                                            <option value="manager">Менеджер</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                                    <button type="button"
                                            class="px-5 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                                            @click="showUnifiedModal = false">
                                        Отмена
                                    </button>
                                    <button
                                        type="submit"
                                        class="px-5 py-2.5 rounded-lg bg-gradient-to-r from-indigo-600 to-indigo-700 text-white hover:from-indigo-700 hover:to-indigo-800 transition-all shadow-sm hover:shadow-md font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                                        :disabled="saving"
                                    >
                                        <span v-if="saving" class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-2"></span>
                                        {{ saving ? 'Создание...' : 'Создать сотрудника' }}
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Tab 2: Прикрепить существующего -->
                        <div v-else class="p-6">
                            <div class="space-y-5">
                                <div class="grid md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Поиск пользователя
                                        </label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">🔍</span>
                                            <input v-model="qAttach"
                                                   class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                                   placeholder="Имя или email..." />
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Компания
                                        </label>
                                        <select v-model="attachForm.company_id"
                                                class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-2.5 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                            <option v-for="c in ownerCompanies" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Список пользователей -->
                                <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                                    <div v-if="loadingUsers" class="p-8 text-center">
                                        <div class="inline-block w-8 h-8 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin mb-3"></div>
                                        <p class="text-sm text-gray-500">Загрузка пользователей...</p>
                                    </div>

                                    <div v-else-if="qAttach.trim().length === 0" class="p-8 text-center">
                                        <div class="text-4xl mb-3">🔍</div>
                                        <p class="text-sm text-gray-500">Введите имя или email пользователя</p>
                                    </div>

                                    <div v-else-if="allUsers.length === 0" class="p-8 text-center">
                                        <div class="text-4xl mb-3">😕</div>
                                        <p class="text-sm text-gray-500">Пользователи не найдены</p>
                                    </div>

                                    <div v-else class="divide-y divide-gray-200 dark:divide-gray-700 max-h-80 overflow-y-auto">
                                        <div v-for="u in allUsers"
                                             :key="u.id"
                                             class="flex items-center justify-between p-4 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-100 to-indigo-200 dark:from-indigo-900/30 dark:to-indigo-900/50 flex items-center justify-center text-indigo-700 dark:text-indigo-300 font-medium">
                                                    {{ u.name?.charAt(0) || '?' }}
                                                </div>
                                                <div>
                                                    <div class="font-medium text-gray-900 dark:text-white">{{ u.name }}</div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ u.email }}</div>
                                                </div>
                                            </div>

                                            <div class="flex items-center gap-3">
                                                <select v-model="attachForm.role"
                                                        class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm px-3 py-1.5">
                                                    <option value="employee">Сотрудник</option>
                                                    <option value="manager">Менеджер</option>
                                                </select>

                                                <button @click="attachForm.user_id = u.id"
                                                        :class="[
                                  'px-4 py-1.5 rounded-lg text-sm font-medium transition-colors',
                                  attachForm.user_id === u.id
                                    ? 'bg-emerald-600 text-white'
                                    : 'bg-indigo-600 text-white hover:bg-indigo-700'
                                ]">
                                                    {{ attachForm.user_id === u.id ? '✓ Выбран' : 'Выбрать' }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                                    <button @click="showUnifiedModal = false"
                                            class="px-5 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        Отмена
                                    </button>
                                    <button @click="attachExisting" :disabled="attaching"
                                            class="px-5 py-2.5 rounded-lg bg-gradient-to-r from-emerald-600 to-emerald-700 text-white hover:from-emerald-700 hover:to-emerald-800 transition-all shadow-sm hover:shadow-md font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                                        <span v-if="attaching" class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-2"></span>
                                        {{ attaching ? 'Добавление...' : 'Добавить сотрудника' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Модалка присоединения к компании -->
        <Teleport to="body">
            <div v-if="showAttachCompanyModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-center justify-center min-h-screen p-4">
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="showAttachCompanyModal = false"></div>

                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md transform transition-all">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                Присоединить к компании
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                                Пользователь: <span class="font-medium text-gray-900 dark:text-white">{{ selectedUser?.name }}</span>
                            </p>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Компания</label>
                                    <select v-model="attachCompanyForm.company_id"
                                            class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-2.5 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                        <option value="">-- Выберите компанию --</option>
                                        <option v-for="c in ownerCompanies" :key="c.id" :value="c.id">{{ c.name }}</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Роль</label>
                                    <select v-model="attachCompanyForm.role"
                                            class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-2.5 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                        <option value="employee">Сотрудник</option>
                                        <option value="manager">Менеджер</option>
                                    </select>
                                </div>
                            </div>

                            <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <button @click="showAttachCompanyModal = false"
                                        class="px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    Отмена
                                </button>
                                <button
                                    @click="attachUserToCompany"
                                    class="px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition-colors shadow-sm hover:shadow">
                                    Сохранить
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Модалка обновления роли -->
        <Teleport to="body">
            <div v-if="showUpdateModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-center justify-center min-h-screen p-4">
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="showUpdateModal = false"></div>

                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md transform transition-all">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                Изменить роль
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                                Сотрудник: <span class="font-medium text-gray-900 dark:text-white">{{ selectedEmployee?.name }}</span>
                            </p>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Роль</label>
                                <select v-model="updateForm.role"
                                        class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-2.5 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                    <option value="employee">Сотрудник</option>
                                    <option value="manager">Менеджер</option>
                                </select>
                            </div>

                            <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <button @click="showUpdateModal = false"
                                        class="px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    Отмена
                                </button>
                                <button
                                    @click="updateEmployeeRole"
                                    class="px-4 py-2 rounded-lg bg-amber-600 text-white hover:bg-amber-700 transition-colors shadow-sm hover:shadow">
                                    Сохранить
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Анимации */
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

/* Анимация пульсации для скелетона */
@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
