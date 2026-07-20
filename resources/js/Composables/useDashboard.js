import { computed, onMounted, ref } from 'vue'
import axios from 'axios'

export function useDashboard(pageProps) {
  const roles = computed(() => pageProps.auth?.roles ?? [])
  const isAdmin = computed(() => roles.value.includes('admin'))
  const userId = computed(() => pageProps.auth?.user?.id)

  const companies = ref([])
  const summary = ref({
    managing_projects: [],
    all_tasks: [],
    all_subtasks: [],
    due_today: [],
    overdue: [],
  })

  const loadingCompanies = ref(true)
  const loadingSummary = ref(true)
  const error = ref('')
  const query = ref('')

  const fetchCompanies = async () => {
    loadingCompanies.value = true
    error.value = ''
    try {
      await axios.get('/sanctum/csrf-cookie')
      const { data } = await axios.get('/api/companies', { withCredentials: true })
      companies.value = Array.isArray(data) ? data : []
    } catch (e) {
      error.value = e.response?.data?.message || 'Не удалось загрузить компании'
    } finally {
      loadingCompanies.value = false
    }
  }

  const fetchSummary = async () => {
    loadingSummary.value = true
    try {
      await axios.get('/sanctum/csrf-cookie')
      const { data } = await axios.get('/api/dashboard/summary', { withCredentials: true })
      summary.value = { ...summary.value, ...(data ?? {}) }
    } catch (e) {
      console.error('Dashboard summary error:', e.response?.data ?? e.message)
    } finally {
      loadingSummary.value = false
    }
  }

  const normalizedQuery = computed(() => query.value.trim().toLowerCase())

  const filteredCompanies = computed(() => {
    if (!normalizedQuery.value) return companies.value
    return companies.value.filter(company =>
      (company.name ?? '').toLowerCase().includes(normalizedQuery.value),
    )
  })

  const myCompanies = computed(() =>
    filteredCompanies.value.filter(company => String(company.user_id) === String(userId.value)),
  )

  const otherCompanies = computed(() =>
    filteredCompanies.value.filter(company => String(company.user_id) !== String(userId.value)),
  )

  const managingByCompany = computed(() =>
    (summary.value.managing_projects ?? []).reduce((result, project) => {
      const company = project.company?.name || 'Без компании'
      result[company] ||= []
      result[company].push(project)
      return result
    }, {}),
  )

  const tasksByCompanyAndProject = computed(() =>
    (summary.value.all_tasks ?? []).reduce((result, task) => {
      const company = task.project?.company?.name || 'Без компании'
      const project = task.project?.name || 'Без проекта'
      result[company] ||= {}
      result[company][project] ||= []
      if (!result[company][project].some(item => item.id === task.id)) {
        result[company][project].push(task)
      }
      return result
    }, {}),
  )

  const subtasksByCompany = computed(() => {
    const source = summary.value.all_subtasks
    if (source && typeof source === 'object' && !Array.isArray(source)) return source
    if (!Array.isArray(source)) return {}

    return source.reduce((result, subtask) => {
      const company = subtask.task?.project?.company?.name || 'Без компании'
      const project = subtask.task?.project?.name || 'Без проекта'
      const task = subtask.task?.title || 'Без задачи'
      result[company] ||= {}
      result[company][project] ||= {}
      result[company][project][task] ||= []
      if (!result[company][project][task].some(item => item.id === subtask.id)) {
        result[company][project][task].push(subtask)
      }
      return result
    }, {})
  })

  const flattenSubtasks = data => {
    if (Array.isArray(data)) return data
    if (!data || typeof data !== 'object') return []
    const result = []
    const walk = value => {
      if (Array.isArray(value)) result.push(...value)
      else if (value && typeof value === 'object') Object.values(value).forEach(walk)
    }
    walk(data)
    return result
  }

  const globalResults = computed(() => {
    const q = normalizedQuery.value
    if (q.length < 2) return { companies: [], projects: [], tasks: [], subtasks: [] }

    return {
      companies: companies.value.filter(item => (item.name ?? '').toLowerCase().includes(q)),
      projects: (summary.value.managing_projects ?? []).filter(item => (item.name ?? '').toLowerCase().includes(q)),
      tasks: (summary.value.all_tasks ?? []).filter(item => (item.title ?? '').toLowerCase().includes(q)),
      subtasks: flattenSubtasks(summary.value.all_subtasks).filter(item => (item.title ?? '').toLowerCase().includes(q)),
    }
  })

  const hasGlobalResults = computed(() => Object.values(globalResults.value).some(items => items.length))

  const createCompany = async ({ name, logo }) => {
    await axios.get('/sanctum/csrf-cookie')
    const payload = new FormData()
    payload.append('name', name)
    if (logo) payload.append('logo', logo)
    await axios.post('/api/companies', payload, {
      headers: { 'Content-Type': 'multipart/form-data' },
      withCredentials: true,
    })
    await fetchCompanies()
  }

  const deleteCompany = async ({ id, password }) => {
    await axios.delete(`/api/companies/${id}`, {
      data: { password },
      withCredentials: true,
    })
    await fetchCompanies()
  }

  onMounted(() => Promise.all([fetchCompanies(), fetchSummary()]))

  return {
    roles,
    isAdmin,
    userId,
    companies,
    summary,
    loadingCompanies,
    loadingSummary,
    error,
    query,
    myCompanies,
    otherCompanies,
    managingByCompany,
    tasksByCompanyAndProject,
    subtasksByCompany,
    globalResults,
    hasGlobalResults,
    fetchCompanies,
    fetchSummary,
    createCompany,
    deleteCompany,
  }
}
