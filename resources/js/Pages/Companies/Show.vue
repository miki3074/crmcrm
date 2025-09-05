<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import { Head, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const { props } = usePage()
const companyId = props.id

// state
const loading = ref(true)
const company  = ref(null)
const managers = ref([])
const showProjectModal = ref(false)
const submitLoading = ref(false)
const errorText = ref('')

const projectForm = ref({
  name: '',
  manager_id: '',
  start_date: new Date().toISOString().slice(0, 10),
  duration_days: '',
})

// perms
const isAdmin = computed(() => (props.auth?.roles || []).includes('admin'))
const isOwner = computed(() => company.value?.user_id === props.auth?.user?.id)
const canCreateProject = computed(() => isAdmin.value || isOwner.value)

// helpers
const today = new Date()
const daysLeft = (startDate, duration) => {
  if (!startDate || !duration) return '—'
  const start = new Date(startDate)
  const end   = new Date(start)
  end.setDate(start.getDate() + Number(duration))
  const diff  = Math.ceil((end - today) / (1000 * 60 * 60 * 24))
  return diff
}
const daysLeftBadge = (d) =>
  d > 7 ? 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200' :
  d >= 0 ? 'bg-amber-100 text-amber-700 ring-1 ring-amber-200' :
           'bg-rose-100 text-rose-700 ring-1 ring-rose-200'

const managerInitials = (name) =>
  (name || '')
    .split(' ')
    .map(p => p[0])
    .slice(0, 2)
    .join('')
    .toUpperCase()

// api
const fetchCompany = async () => {
  loading.value = true
  try {
    const { data } = await axios.get(`/api/companies/${companyId}`)
    company.value = data
  } finally {
    loading.value = false
  }
}

const fetchManagers = async () => {
  const { data } = await axios.get('/api/users/managers')
  managers.value = data
}

const openCreateModal = async () => {
  errorText.value = ''
  await fetchManagers()
  showProjectModal.value = true
}

const createProject = async () => {
  errorText.value = ''
  submitLoading.value = true
  try {
    await axios.get('/sanctum/csrf-cookie')
    await axios.post('/api/projects', {
      ...projectForm.value,
      company_id: companyId,
    })
    showProjectModal.value = false
    projectForm.value = {
      name: '',
      manager_id: '',
      start_date: new Date().toISOString().slice(0, 10),
      duration_days: '',
    }
    await fetchCompany()
  } catch (e) {
    errorText.value = e?.response?.data?.message || 'Не удалось создать проект'
  } finally {
    submitLoading.value = false
  }
}

onMounted(fetchCompany)
</script>

<template>
  <Head :title="company?.name ? `Компания — ${company.name}` : 'Компания'" />
  <AuthenticatedLayout>
    <!-- HERO -->
    <div class="relative overflow-hidden">
      <div class="absolute inset-0 bg-gradient-to-r from-indigo-600 via-purple-600 to-fuchsia-600 opacity-90"></div>
      <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 text-white">
        <div class="flex items-center gap-4">
          <img
            v-if="company?.logo"
            :src="`/storage/${company.logo}`"
            alt="Logo"
            class="w-16 h-16 rounded-xl object-cover ring-2 ring-white/30"
          />
          <div>
            <h1 class="text-2xl sm:text-3xl font-semibold">
              {{ company?.name ?? 'Компания' }}
            </h1>
            <p class="text-white/80 text-sm mt-1">
              ID: {{ companyId }}
              <span v-if="isOwner" class="ml-2 px-2 py-0.5 text-xs rounded-full bg-white/20">Владелец</span>
              <span v-else-if="isAdmin" class="ml-2 px-2 py-0.5 text-xs rounded-full bg-white/20">Администратор</span>
            </p>
          </div>

          <div class="ml-auto" v-if="canCreateProject">
            <button
              @click="openCreateModal"
              class="inline-flex items-center gap-2 rounded-xl bg-white text-gray-900 px-4 py-2.5 shadow/50 hover:shadow transition"
            >
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M11 11V6h2v5h5v2h-5v5h-2v-5H6v-2h5z"/></svg>
              Создать проект
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- BODY -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-6">
      <!-- Скелетон -->
      <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="i in 6" :key="i" class="rounded-2xl border bg-white dark:bg-gray-800 p-4 animate-pulse">
          <div class="h-5 w-1/2 bg-gray-200 dark:bg-gray-700 rounded mb-4"></div>
          <div class="space-y-2">
            <div class="h-3 w-3/4 bg-gray-200 dark:bg-gray-700 rounded"></div>
            <div class="h-3 w-2/3 bg-gray-200 dark:bg-gray-700 rounded"></div>
            <div class="h-3 w-1/2 bg-gray-200 dark:bg-gray-700 rounded"></div>
          </div>
        </div>
      </div>

      <!-- Пусто -->
      <div
        v-else-if="!company?.projects?.length"
        class="rounded-2xl border bg-white dark:bg-gray-800 p-10 text-center"
      >
        <div class="mx-auto w-16 h-16 rounded-2xl bg-indigo-100 text-indigo-600 flex items-center justify-center">
          <svg class="w-7 h-7" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3l4 4H8l4-4zm0 18l-4-4h8l-4 4zM3 12l4-4v8l-4-4zm18 0l-4 4V8l4 4z"/></svg>
        </div>
        <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">Проектов пока нет</h3>
        <p class="text-gray-600 dark:text-gray-300 mt-1">Создайте первый проект для этой компании.</p>
        <div class="mt-6" v-if="canCreateProject">
          <button
            @click="openCreateModal"
            class="rounded-xl bg-indigo-600 text-white px-5 py-2.5 hover:bg-indigo-700 transition"
          >
            Новый проект
          </button>
        </div>
      </div>

      <!-- Проекты -->
      <div v-else class="mt-6">
        <div class="flex items-center justify-between mb-3">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Проекты компании</h2>
          <div class="text-sm text-gray-500 dark:text-gray-400">
            Всего: {{ company.projects.length }}
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="project in company.projects"
            :key="project.id"
            class="group rounded-2xl border bg-white dark:bg-gray-800 p-5 hover:shadow-lg transition cursor-pointer"
            @click="$inertia.visit(`/projects/${project.id}`)"
          >
            <div class="flex items-start justify-between gap-3">
              <h3 class="text-base font-semibold text-gray-900 dark:text-white leading-snug">
                {{ project.name }}
              </h3>
              <span
                v-if="project.duration_days"
                :class="['px-2 py-1 text-xs rounded-full ring-1', daysLeftBadge(daysLeft(project.start_date, project.duration_days))]"
                class="shrink-0"
              >
                {{ daysLeft(project.start_date, project.duration_days) }} дн.
              </span>
            </div>

            <div class="mt-3 space-y-1.5 text-sm text-gray-600 dark:text-gray-300">
              <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-xs font-medium">
                  {{ managerInitials(project.manager?.name) || 'PM' }}
                </span>
                <span class="truncate">
                  Руководитель: <b class="text-gray-800 dark:text-white">{{ project.manager?.name ?? '—' }}</b>
                </span>
              </div>
              <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M7 11h5V6H7v5zm0 7h5v-5H7v5zm7 0h5v-5h-5v5zM14 6v5h5V6h-5z"/></svg>
                <span>Старт: <b class="text-gray-800 dark:text-white">{{ project.start_date }}</b></span>
              </div>
            </div>

            <div class="mt-4 flex items-center justify-end">
              <span class="text-indigo-600 group-hover:translate-x-0.5 transition inline-flex items-center gap-1 text-sm">
                Открыть
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M13 5l7 7-7 7v-4H4v-6h9V5z"/></svg>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Модалка создания проекта -->
    <div
      v-if="showProjectModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4"
    >
      <div class="absolute inset-0 bg-black/50" @click="showProjectModal=false"></div>
      <div class="relative w-full max-w-lg bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Создать проект</h3>
          <button @click="showProjectModal=false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
            ✕
          </button>
        </div>

        <p v-if="errorText" class="mt-3 text-sm text-rose-600">{{ errorText }}</p>

        <form class="mt-4 space-y-4" @submit.prevent="createProject">
          <div>
            <label class="block text-sm mb-1 text-gray-700 dark:text-gray-300">Название проекта</label>
            <input
              v-model="projectForm.name"
              class="w-full rounded-xl border px-3 py-2 bg-white dark:bg-gray-700 dark:text-white"
              required
              placeholder="Например, CRM v2"
            />
          </div>

          <div>
            <label class="block text-sm mb-1 text-gray-700 dark:text-gray-300">Руководитель</label>
            <select
              v-model="projectForm.manager_id"
              class="w-full rounded-xl border px-3 py-2 bg-white dark:bg-gray-700 dark:text-white"
              required
            >
              <option disabled value="">Выберите менеджера</option>
              <option v-for="m in managers" :key="m.id" :value="m.id">
                {{ m.name }}
              </option>
            </select>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm mb-1 text-gray-700 dark:text-gray-300">Дата начала</label>
              <input
                type="date"
                v-model="projectForm.start_date"
                class="w-full rounded-xl border px-3 py-2 bg-white dark:bg-gray-700 dark:text-white"
                required
              />
            </div>
            <div>
              <label class="block text-sm mb-1 text-gray-700 dark:text-gray-300">Длительность (дней)</label>
              <input
                type="number"
                min="1"
                v-model="projectForm.duration_days"
                class="w-full rounded-xl border px-3 py-2 bg-white dark:bg-gray-700 dark:text-white"
                required
                placeholder="Напр., 30"
              />
            </div>
          </div>

          <div class="flex justify-end gap-2 pt-2">
            <button
              type="button"
              @click="showProjectModal=false"
              class="px-4 py-2 rounded-xl border bg-white dark:bg-gray-700 dark:text-white"
            >
              Отмена
            </button>
            <button
              type="submit"
              :disabled="submitLoading"
              class="px-4 py-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-60"
            >
              <span v-if="!submitLoading">Создать</span>
              <span v-else>Сохраняю…</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
