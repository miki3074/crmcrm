<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import { Head, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

import { use } from 'echarts/core'
import { CanvasRenderer } from 'echarts/renderers'
import { BarChart, LineChart } from 'echarts/charts'
import { GridComponent, TooltipComponent, TitleComponent, LegendComponent } from 'echarts/components'
import VChart from 'vue-echarts'

import * as echarts from 'echarts'

use([
  CanvasRenderer,
  BarChart,
  LineChart,
  GridComponent,
  TooltipComponent,
  TitleComponent,
  LegendComponent,
])

const { props } = usePage()
const companyId = props.id

// state
const loading = ref(true)
const company  = ref(null)
const managers = ref([])
const showProjectModal = ref(false)
const submitLoading = ref(false)
const errorText = ref('')

// const projectForm = ref({
//   name: '',
//   manager_id: '',
//   start_date: new Date().toISOString().slice(0, 10),
//   duration_days: '',
// })

const projectForm = ref({
  name: '',
  manager_ids: [], // массив id
  start_date: new Date().toISOString().slice(0, 10),
  duration_days: '',
  company_id: props.auth.user.company_id, // если есть
})

const resetProjectForm = () => {
  projectForm.value = {
    name: '',
    manager_ids: [],
    start_date: new Date().toISOString().slice(0, 10),
    duration_days: '',
    company_id: companyId,
  }
}




// perms
// const isAdmin = computed(() => (props.auth?.roles || []).includes('admin'))
const isOwner = computed(() => company.value?.user_id === props.auth?.user?.id)

// ДОБАВЛЯЕМ ПРОВЕРКУ: пользователь – менеджер компании
const isCompanyManager = computed(() =>
  company.value?.users?.some(u => u.id === props.auth?.user?.id && u.pivot?.role === 'manager')
)

// Теперь TRUE если владелец ИЛИ менеджер
const canCreateProject = computed(() => isOwner.value || isCompanyManager.value)


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
  const { data } = await axios.get(`/api/users/managers?company_id=${companyId}`)
  managers.value = data
}

const openCreateModal = async () => {
  errorText.value = ''
  resetProjectForm() // ← сбрасываем все данные формы
  await fetchManagers()
  // по желанию: автоматически добавить текущего пользователя как руководителя
  // projectForm.value.manager_ids.push(props.auth.user.id)
  showProjectModal.value = true
}


const createProject = async () => {
  errorText.value = ''
  submitLoading.value = true
  try {
    await axios.get('/sanctum/csrf-cookie')
    await axios.post('/api/projects', { ...projectForm.value, company_id: companyId })
showProjectModal.value = false
resetProjectForm()
await fetchCompany()
    await fetchCompany()
  } catch (e) {
    errorText.value = e?.response?.data?.message || 'Не удалось создать проект'
  } finally {
    submitLoading.value = false
  }
}


const managersWithOwnerFirst = computed(() => {
  if (!managers.value.length || !props.auth?.user) return managers.value

  const currentUserId = props.auth.user.id

  // выделяем владельца
  const sorted = [...managers.value].sort((a, b) => {
    if (a.id === currentUserId) return -1
    if (b.id === currentUserId) return 1
    return 0
  })

  return sorted
})



const deleteCompany = async (companyId) => {
  if (!confirm('Вы уверены, что хотите удалить эту компанию со всеми проектами и задачами?')) return

  try {
    await axios.delete(`/api/companies/${companyId}`, { withCredentials: true })
    alert('Компания успешно удалена.')
    await fetchCompanies() // перезагружаем список компаний
  } catch (e) {
    alert(e?.response?.data?.message || 'Ошибка при удалении компании')
  }
}

const showMembersModal = ref(false)
const members = ref([])
const loadingMembers = ref(false)

const fetchMembers = async () => {
  loadingMembers.value = true
  try {
    const { data } = await axios.get(`/api/companies/${companyId}/members`)
    members.value = data
  } finally {
    loadingMembers.value = false
  }
}

const openMembersModal = async () => {
  await fetchMembers()
  showMembersModal.value = true
}


const selectedProject = ref(null)
const projectTasks = ref([])
const loadingTasks = ref(false)




const chartOptions = computed(() => ({

  tooltip: {
    trigger: 'axis',
    axisPointer: { type: 'shadow' },
    formatter: function (params) {
      const project = params[0];
      const fullName = company.value?.projects?.[project.dataIndex]?.name || '';
      return `
        <b>${fullName}</b><br/>
        Длительность: ${project.value} дней
      `;
    },
  },
  grid: { left: '5%', right: '5%', bottom: 80, containLabel: true },

  xAxis: {
    type: 'category',
    data: company.value?.projects?.map(p => p.name) || [],
    axisLabel: {
      rotate: 25,
      fontSize: 11,
      interval: 0,
      // ✂️ сокращаем длинные названия проектов
      formatter: function (value) {
        const maxLength = 14;
        if (value.length > maxLength) {
          return value.slice(0, maxLength) + '…';
        }
        return value;
      },
    },
  },

  yAxis: {
    type: 'value',
    name: 'Дней',
  },

  // ✅ горизонтальная прокрутка
  dataZoom: [
    {
      type: 'slider', // нижний ползунок
      show: true,
      start: 0, // показываем первые 40%
      end: 40,
      height: 18,
      bottom: 15,
      handleSize: '90%',
      handleStyle: {
        color: '#4f46e5',
        borderColor: '#93c5fd',
      },
    },
    {
      type: 'inside', // прокрутка колесиком мыши
      zoomOnMouseWheel: true,
      moveOnMouseMove: true,
    },
  ],

  series: [
    {
      type: 'bar',
      barWidth: '45%', // чтобы не слипались
      data: company.value?.projects?.map(p => p.duration_days) || [],
      itemStyle: { color: '#4f46e5' },
      label: {
        show: true,
        position: 'top',
        formatter: (params) => `${params.value}д`,
      },
    },
  ],
}));



const fetchProjectTasks = async (projectId) => {
  loadingTasks.value = true
  try {
    const { data } = await axios.get(`/api/projects/${projectId}/tasks`)
    projectTasks.value = data
  } finally {
    loadingTasks.value = false
  }
}

const onProjectClick = async (params) => {
  const clickedProject = company.value.projects.find(p => p.name === params.name)
  if (clickedProject) {
    selectedProject.value = clickedProject
    await Promise.all([
      fetchProjectTasks(clickedProject.id),
      fetchTaskStats(clickedProject.id),
    ])
  }
}



const taskStatsChartOptions = computed(() => ({
  backgroundColor: 'transparent', // убираем серый фон по умолчанию
  title: {

    left: 'center',
    textStyle: {
      color: '#1e293b', // темно-серый
      fontWeight: 600,
      fontSize: 18,
    },
  },
  tooltip: {
    trigger: 'item',
    backgroundColor: '#1e293b',
    borderColor: '#334155',
    textStyle: { color: '#f8fafc' },
    borderWidth: 1,
    padding: 10,
     position: function (point, params, dom, rect, size) {
      // Центрируем подсказку относительно графика
      return [size.contentSize[0] / 2, size.contentSize[1] / 2];
    },
    formatter: (params) => {
      const task = taskStats.value[params.dataIndex]
      const status = task.is_overdue
        ? '<span style="color:#ef4444;">⚠️ Просрочена</span>'
        : task.subtasks_overdue > 0
        ? '<span style="color:#f59e0b;">⚠️ Частично просрочены</span>'
        : '<span style="color:#22c55e;">✅ В срок</span>'
      return `
        <b style="font-size:14px;">${task.title}</b><br/>
        Прогресс: ${task.progress}%<br/>
        Подзадач: ${task.subtasks_total}<br/>
        Просрочено подзадач: ${task.subtasks_overdue}<br/>
        ${status}
      `
    },
  },
  grid: { left: '6%', right: '4%', bottom: 90, containLabel: true },
  xAxis: {
    type: 'category',
    data: taskStats.value.map(t => t.title),
    axisLabel: {
      rotate: 25,
      fontSize: 11,
      color: '#475569',
      interval: 0,
      formatter: (value) => (value.length > 14 ? value.slice(0, 14) + '…' : value),
    },
    axisLine: { lineStyle: { color: '#cbd5e1' } },
  },
  yAxis: {
    type: 'value',
    name: '% выполнения',
    nameTextStyle: { color: '#64748b', fontSize: 12, padding: [0, 0, 5, 0] },
    min: 0,
    max: 100,
    splitLine: { lineStyle: { color: '#e2e8f0' } },
  },
  dataZoom: [
    {
      type: 'slider',
      show: true,
      start: 0,
      end: 40,
      height: 18,
      bottom: 10,
      handleSize: '90%',
      handleStyle: {
        color: '#6366f1',
        borderColor: '#93c5fd',
      },
      textStyle: { color: '#64748b' },
      borderColor: '#c7d2fe',
    },
    {
      type: 'inside',
      zoomOnMouseWheel: true,
      moveOnMouseMove: true,
    },
  ],
  series: [
    {
      name: 'Прогресс задач',
      type: 'bar',
      barWidth: '45%',
      data: taskStats.value.map(t => {
        const total = t.subtasks_total || 0
        const overdue = t.subtasks_overdue || 0
        const overdueRatio = total > 0 ? overdue / total : 0

        if (t.is_overdue) {
          return { value: t.progress, itemStyle: { color: '#ef4444' } }
        }

        if (overdueRatio > 0) {
          return {
            value: t.progress,
            itemStyle: {
              color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                { offset: 0, color: '#22c55e' },
                { offset: 1 - overdueRatio, color: '#22c55e' },
                { offset: 1 - overdueRatio + 0.001, color: '#f59e0b' },
                { offset: 1, color: '#f59e0b' },
              ]),
            },
          }
        }

        return {
          value: t.progress,
          itemStyle: { color: t.progress >= 80 ? '#22c55e' : '#3b82f6' },
        }
      }),
      label: {
        show: true,
        position: 'top',
        color: '#334155',
        fontSize: 11,
        formatter: (params) => {
          const task = taskStats.value[params.dataIndex]
          if (task.is_overdue) return '❌ Просрочена'
          if (task.subtasks_overdue > 0) return `${task.progress}% ⚠️`
          return `${params.value}%`
        },
      },
      emphasis: {
        focus: 'series',
        itemStyle: {
          shadowBlur: 10,
          shadowColor: 'rgba(0, 0, 0, 0.3)',
        },
      },
    },
  ],
}))







const taskChartOptions = computed(() => ({
  title: {
    text: selectedProject.value
      ? `Задачи проекта: ${selectedProject.value.name}`
      : 'Выберите проект',
  },
  tooltip: {},
  grid: { left: 120, right: 20 },
  xAxis: { type: 'category', data: projectTasks.value.map(t => t.title) },
  yAxis: { type: 'value' },
  series: [
    {
      type: 'bar',
      data: projectTasks.value.map(t => {
        const start = new Date(t.start_date)
        const end = new Date(t.due_date)
        return Math.ceil((end - start) / (1000 * 60 * 60 * 24)) // длительность в днях
      }),
      itemStyle: {
        color: '#6366f1',
      },
    },
  ],
}))


// === 📊 Статистика задач проекта ===
const taskStats = ref([])
const loadingStats = ref(false)

const fetchTaskStats = async (projectId) => {
  loadingStats.value = true
  try {
    const { data } = await axios.get(`/api/projects/${projectId}/task-stats`)
    taskStats.value = data
  } finally {
    loadingStats.value = false
  }
}


// Определяем, мобильное устройство или нет
const isMobile = ref(false)

onMounted(() => {
  // 1️⃣ Проверка ширины экрана
  if (window.innerWidth < 768) {
    isMobile.value = true
  }

  // 2️⃣ (опционально) более надёжная проверка по User-Agent
  if (/Mobi|Android|iPhone|iPad|iPod|Opera Mini|IEMobile/i.test(navigator.userAgent)) {
    isMobile.value = true
  }
})




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

          <div class="ml-auto" >
            <button
              @click="openCreateModal"
              class="inline-flex items-center gap-2 rounded-xl bg-white text-gray-900 px-4 py-2.5 shadow/50 hover:shadow transition"
            >
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M11 11V6h2v5h5v2h-5v5h-2v-5H6v-2h5z"/></svg>
              Создать проект
            </button>

             <button
    @click="openMembersModal"
    class="inline-flex items-center gap-2 rounded-xl bg-white/20 text-white px-4 py-2.5 hover:bg-white/30 transition"
  >
    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5C15 14.17 10.33 13 8 13zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5C23 14.17 18.33 13 16 13z"/></svg>
    Участники
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
            @click="$inertia.visit(`/projects2/${project.id}`)"
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
  <div class="flex items-start gap-2">
    <span class="truncate">Руководители:</span>
    <div class="flex flex-wrap gap-2">
      <div
        v-for="m in project.managers"
        :key="m.id"
        class="flex items-center gap-1"
      >
        <span
          class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-xs font-medium"
        >
          {{ managerInitials(m.name) }}
        </span>
        <span class="text-gray-800 dark:text-white text-sm">{{ m.name }}</span>
      </div>
    </div>
  </div>

  <div class="flex items-center gap-2">
    <svg class="w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor">
      <path d="M7 11h5V6H7v5zm0 7h5v-5H7v5zm7 0h5v-5h-5v5zM14 6v5h5V6h-5z"/>
    </svg>
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


    <!-- <pre v-if="!loadingStats">{{ taskStats }}</pre> -->

<!-- 📊 Первый график — проекты -->
<div v-if="!isMobile" class="my-8 bg-white dark:bg-gray-800 p-6 rounded-2xl shadow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 ">
  <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
    График проектов
  </h3>
  <v-chart :option="chartOptions" autoresize style="height: 400px" @click="onProjectClick" />
</div>

<!-- 📊 Второй график — задачи выбранного проекта -->
<!-- <div
  v-if="selectedProject"
  class="my-8 bg-white dark:bg-gray-800 p-6 rounded-2xl shadow transition-all duration-300"
>
  <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
    Задачи проекта "{{ selectedProject.name }}"
  </h3>

  <div v-if="loadingTasks" class="text-gray-500">Загрузка задач...</div>
  <v-chart
    v-else
    :option="taskChartOptions"
    autoresize
    style="height: 350px"
  />
</div> -->

<!-- 📊 Третий график — динамика задач -->

<!-- 📊 График прогресса задач -->
<div
  v-if="selectedProject && taskStats.length"
  class=" bg-gradient-to-br from-white via-slate-50 to-indigo-50 dark:from-gray-800 dark:via-gray-900 dark:to-indigo-950 p-8 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 transition-all duration-300 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"
>
  <div class="flex items-center justify-between mb-5">
    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
      📈 Прогресс задач проекта "{{ selectedProject.name }}"
    </h3>
    <span class="text-sm text-gray-500 dark:text-gray-400">
      Всего задач: {{ taskStats.length }}
    </span>
  </div>

  <div v-if="loadingStats" class="text-gray-500">Загрузка статистики...</div>

  <VChart
    v-else
    :option="taskStatsChartOptions"
    autoresize
    style="height: 420px; width: 100%"
  />
</div>


<!-- <div style="border-radius: 20px 20px 0 0;"
  v-else
  class="mt-5 bg-white dark:bg-gray-800 p-6  shadow text-center text-gray-500"
>
  📱 Графики недоступны на мобильных устройствах
</div> -->




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
  <label class="block text-sm mb-2 text-gray-700 dark:text-gray-300">Руководители</label>
  <div class="space-y-2 max-h-40 overflow-y-auto p-2 border rounded-xl bg-white dark:bg-gray-700">
    <div
      v-for="m in managersWithOwnerFirst"
      :key="m.id"
      class="flex items-center space-x-2"
    >
      <input
        type="checkbox"
        :id="`manager-${m.id}`"
        :value="m.id"
        v-model="projectForm.manager_ids"
        class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
      />
      <label
        :for="`manager-${m.id}`"
        class="text-sm text-gray-700 dark:text-gray-300"
      >
        {{ m.id === props.auth.user.id ? 'Я' : m.name }}
      </label>
    </div>
  </div>
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


<!-- Модалка участников -->
<div
  v-if="showMembersModal"
  class="fixed inset-0 z-50 flex items-center justify-center p-4"
>
  <div class="absolute inset-0 bg-black/50" @click="showMembersModal = false"></div>

  <div class="relative w-full max-w-lg bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Участники компании</h3>
      <button @click="showMembersModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">✕</button>
    </div>

    <div v-if="loadingMembers" class="text-center py-6 text-gray-500 dark:text-gray-300">
      Загрузка...
    </div>

    <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
      <div
        v-for="member in members"
        :key="member.id"
        class="flex items-center justify-between py-3"
      >
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 flex items-center justify-center rounded-full bg-indigo-100 text-indigo-700 font-medium">
            {{ managerInitials(member.name) }}
          </div>
          <div>
            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ member.name }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">{{ member.email }}</div>
          </div>
        </div>
        <span
          :class="{
            'px-2 py-1 text-xs rounded-full font-medium': true,
            'bg-amber-100 text-amber-700': member.pivot.role === 'manager',
            'bg-indigo-100 text-indigo-700': member.pivot.role === 'owner',
            'bg-gray-100 text-gray-600': member.pivot.role === 'employee',
          }"
        >
          {{ member.pivot.role === 'owner' ? 'Владелец' :
             member.pivot.role === 'manager' ? 'Менеджер' :
             'Сотрудник' }}
        </span>
      </div>
    </div>
  </div>
</div>


  </AuthenticatedLayout>
</template>
