<script setup>
import { ref, onMounted, watch, computed } from "vue"
import { Head, Link, usePage } from "@inertiajs/vue3" // Добавили usePage
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue"
import axios from "axios"
import { debounce } from "lodash"

// --- Получение текущего пользователя (Inertia) ---
const page = usePage()
const currentUser = computed(() => page.props.auth.user)

// --- Константы статусов (вынесли из template для чистоты) ---
const STATUSES = {
    new: { label: 'Новый', class: 'bg-blue-100 text-blue-800 border-blue-200' },
    negotiation: { label: 'Переговоры', class: 'bg-yellow-100 text-yellow-800 border-yellow-200' },
    signed: { label: 'Заключен', class: 'bg-green-100 text-green-800 border-green-200' },
    rejected: { label: 'Отказались', class: 'bg-red-100 text-red-800 border-red-200' }
}
//  Добавьте справочник типов договоров (после STATUSES)
const CONTRACT_TYPES = {
    general: { label: 'Общий', class: 'bg-slate-100 text-slate-600' },
    dealer:  { label: 'Дилерский (Производитель)', class: 'bg-purple-100 text-purple-700 border-purple-200' },
    agency:  { label: 'Агентский (Комиссия)', class: 'bg-orange-100 text-orange-700 border-orange-200' },
    sale:    { label: 'Продажа', class: 'bg-emerald-100 text-emerald-700 border-emerald-200' },
    purchase:{ label: 'Закупка', class: 'bg-blue-100 text-blue-700 border-blue-200' }
}

const stats = ref(null)

// --- Состояние данных ---
const contracts = ref([])
const editingId = ref(null)

// --- Формы ---
const modalForm = ref({
    title: "",
    type: "general", // <-- Новое поле
    counterparty: "",
    amount: "",
    margin: "",      // <-- Новое поле
    valid_until: "", // <-- Новое поле
    status: "new",
    task_id: null,
    subtask_id: null,
    files: [],
})

const editForm = ref({
    id: null, title: "", type: "general", counterparty: "", amount: "", margin: "", valid_until: "", status: "", task_id: null, subtask_id: null,
})




// --- Состояние UI ---
const showModal = ref(false)
const modalMode = ref('create')
const currentContract = ref(null)

// --- Состояние Поиска ---
const searchQuery = ref("")
const searchResults = ref([])
const showDropdown = ref(false)
const activeSearchContext = ref('modal')

// ==========================================
// ЛОГИКА
// ==========================================

const loadContracts = async () => {
    try {
        const { data } = await axios.get("/api/contracts")
        contracts.value = data

        // Загружаем статистику (если добавили метод stats в контроллер)
        const statsRes = await axios.get("/api/contracts/stats")
        stats.value = statsRes.data
    } catch (e) {
        console.error("Ошибка загрузки", e)
    }
}

// Проверка прав (создатель ли текущий юзер?)
const isCreator = (contract) => {
    return contract.created_by === currentUser.value.id
}

// --- Быстрое обновление статуса (без режима редактирования) ---
const quickUpdateStatus = async (contract, newStatus) => {
    // Сохраняем старый статус на случай ошибки
    const oldStatus = contract.status
    contract.status = newStatus // Оптимистичное обновление интерфейса

    try {
        // Используем метод move (если есть в контроллере) или обычный update
        await axios.put(`/api/contracts/${contract.id}`, {
            status: newStatus,
            // Передаем остальные поля как есть, чтобы валидация update не ругалась (если она строгая)
            title: contract.title
        })
        // Успех (можно добавить уведомление)
    } catch (e) {
        contract.status = oldStatus // Откат при ошибке
        alert("Не удалось изменить статус. Возможно, у вас нет прав.")
    }
}

// --- Создание ---
const openCreateModal = () => {
    modalMode.value = 'create'
    modalForm.value = { title: "", counterparty: "", amount: "", status: "new", files: [], task_id: null, subtask_id: null }
    searchQuery.value = ""
    searchResults.value = []
    showModal.value = true
    activeSearchContext.value = 'modal'
}

const createContract = async () => {
    const fd = new FormData();
    Object.keys(modalForm.value).forEach(key => {
        if (key === 'files') {
            for (let i = 0; i < modalForm.value.files.length; i++) {
                fd.append(`files[${i}]`, modalForm.value.files[i]);
            }
        } else if (modalForm.value[key] !== null) {
            fd.append(key, modalForm.value[key]);
        }
    })

    try {
        await axios.post("/api/contracts", fd)
        showModal.value = false
        await loadContracts()
    } catch (e) {
        alert("Ошибка при создании.")
    }
}

// --- Редактирование ---
const startEditing = (contract) => {
    editingId.value = contract.id
    activeSearchContext.value = 'table'
    editForm.value = { ...contract }

    if (contract.task_id) searchQuery.value = `Задача #${contract.task_id}`
    else if (contract.subtask_id) searchQuery.value = `Подзадача #${contract.subtask_id}`
    else searchQuery.value = ""

    searchResults.value = []
}

const cancelEditing = () => {
    editingId.value = null
    editForm.value = {}
    searchQuery.value = ""
    showDropdown.value = false
}

const saveRow = async () => {
    try {
        await axios.put(`/api/contracts/${editForm.value.id}`, {
            ...editForm.value,
            task_id: editForm.value.task_id || null,
            subtask_id: editForm.value.subtask_id || null
        })
        await loadContracts()
        editingId.value = null
    } catch (e) {
        alert("Ошибка сохранения")
    }
}

const deleteContract = async (id) => {
    if (!confirm("Удалить договор?")) return
    await axios.delete(`/api/contracts/${id}`)
    await loadContracts()
}

// --- Поиск (Autocomplete) ---
const fetchTasks = debounce(async (query) => {
    if (!query) { searchResults.value = []; return }
    try {
        const { data } = await axios.get('/api/tasks/search', { params: { query } })
        searchResults.value = data.results
        showDropdown.value = true
    } catch (e) { console.error(e) }
}, 300)

watch(searchQuery, (newVal) => {
    if (newVal && (newVal.startsWith('Задача #') || newVal.startsWith('Подзадача #'))) return
    fetchTasks(newVal)
})

const selectSearchResult = (item) => {
    const targetForm = activeSearchContext.value === 'modal' ? modalForm : editForm
    if (item.type === 'task') {
        targetForm.value.task_id = item.id; targetForm.value.subtask_id = null
    } else {
        targetForm.value.subtask_id = item.id; targetForm.value.task_id = null
    }
    searchQuery.value = `${item.label}`
    showDropdown.value = false
}

const clearSelection = () => {
    const targetForm = activeSearchContext.value === 'modal' ? modalForm : editForm
    targetForm.value.task_id = null; targetForm.value.subtask_id = null
    searchQuery.value = ""; searchResults.value = []
}

// --- Файлы ---
const openFilesModal = (contract) => {
    modalMode.value = 'files'
    currentContract.value = contract
    modalForm.value.files = []
    showModal.value = true
}

const uploadFiles = async () => {
    if (!modalForm.value.files.length) return
    const fd = new FormData()
    fd.append("_method", "PUT")
    fd.append("status", currentContract.value.status) // Required by validation

    Array.from(modalForm.value.files).forEach((file, i) => fd.append(`files[${i}]`, file))

    await axios.post(`/api/contracts/${currentContract.value.id}`, fd)
    showModal.value = false
    await loadContracts()
}

const deleteFile = async (fileId) => {
    if (!confirm("Удалить файл?")) return
    await axios.delete(`/api/contracts/files/${fileId}`)
    const { data } = await axios.get("/api/contracts")
    contracts.value = data
    currentContract.value = data.find(c => c.id === currentContract.value.id)
}

const formatDate = (d) => d ? new Date(d).toLocaleDateString('ru-RU') : '—'

const showReportModal = ref(false)
const reportForm = ref({
    filter_type: 'all', // all, project, task
    target_id: null,
    target_label: '', // Для отображения выбранного названия
    start_date: '',
    end_date: ''
})

// Состояние поиска для отчета (проекты/задачи)
const reportSearchQuery = ref('')
const reportSearchResults = ref([])
const showReportDropdown = ref(false)

// Функция поиска Проектов или Задач (в зависимости от выбора radio)
const fetchReportTargets = debounce(async (query) => {
    if (!query) return
    let url = ''
    if (reportForm.value.filter_type === 'project') {
        url = '/api/projects/search'
    } else if (reportForm.value.filter_type === 'task') {
        url = '/api/tasks/search'
    } else {
        return
    }

    try {
        const { data } = await axios.get(url, { params: { query } })
        // Унификация ответа: для задач у нас 'results', для проектов массив
        reportSearchResults.value = data.results || data
        showReportDropdown.value = true
    } catch (e) { console.error(e) }
}, 300)

// Вотчер для инпута поиска в отчете
watch(reportSearchQuery, (val) => {
    if (reportForm.value.filter_type === 'all') return
    if (val && val !== reportForm.value.target_label) {
        fetchReportTargets(val)
    }
})

// Выбор из выпадающего списка
const selectReportTarget = (item) => {
    reportForm.value.target_id = item.id
    // Для задач label уже сформирован, для проектов берем name
    reportForm.value.target_label = item.label || item.name
    reportSearchQuery.value = reportForm.value.target_label
    showReportDropdown.value = false
}

// СКАЧИВАНИЕ ОТЧЕТА
const downloadReport = () => {
    // Формируем URL с параметрами
    const params = new URLSearchParams({
        filter_type: reportForm.value.filter_type,
        target_id: reportForm.value.target_id || '',
        start_date: reportForm.value.start_date,
        end_date: reportForm.value.end_date
    }).toString()

    // Открываем в новой вкладке (браузер сам начнет скачивание)
    window.open(`/api/contracts/report?${params}`, '_blank')
    showReportModal.value = false
}

// Сброс при переключении радио-кнопок
const onFilterTypeChange = () => {
    reportForm.value.target_id = null
    reportForm.value.target_label = ''
    reportSearchQuery.value = ''
    reportSearchResults.value = []
}

onMounted(loadContracts)
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Договоры" />

        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-2xl font-bold text-slate-800 dark:text-slate-100">📑 Реестр договоров</h2>
                <button class="btn-primary flex items-center gap-2" @click="openCreateModal">
                    <span>+</span> Новый договор
                </button>

                <button
                    class="px-4 py-2 rounded-xl bg-slate-800 text-white hover:bg-slate-700 shadow-md transition-all flex items-center gap-2 ml-2"
                    @click="showReportModal = true"
                >
                    <span>📊</span> Отчет
                </button>
            </div>
        </template>

        <!-- Блок статистики -->
<!--        <div v-if="stats" class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">-->
<!--            &lt;!&ndash; Оборот &ndash;&gt;-->
<!--            <div class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm">-->
<!--                <div class="text-xs text-slate-500 uppercase font-bold">Общий оборот</div>-->
<!--                <div class="text-2xl font-bold text-slate-800 dark:text-white mt-1">-->
<!--                    {{ Number(stats.summary.turnover).toLocaleString() }} ₽-->
<!--                </div>-->
<!--            </div>-->

<!--            &lt;!&ndash; Прибыль / Комиссия &ndash;&gt;-->
<!--            <div class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-emerald-200 dark:border-emerald-900 shadow-sm relative overflow-hidden">-->
<!--                <div class="absolute right-0 top-0 p-4 opacity-10 text-emerald-600">-->
<!--                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>-->
<!--                </div>-->
<!--                <div class="text-xs text-emerald-600 uppercase font-bold">Чистая прибыль (Комиссия)</div>-->
<!--                <div class="text-2xl font-bold text-emerald-600 mt-1">-->
<!--                    +{{ Number(stats.summary.profit).toLocaleString() }} ₽-->
<!--                </div>-->
<!--            </div>-->

<!--            &lt;!&ndash; Активные дилеры &ndash;&gt;-->
<!--            <div class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-purple-200 dark:border-purple-900 shadow-sm">-->
<!--                <div class="text-xs text-purple-600 uppercase font-bold">Активные дилерские договора</div>-->
<!--                <div class="text-2xl font-bold text-purple-600 mt-1">-->
<!--                    {{ stats.summary.dealers_count }} <span class="text-sm font-normal text-slate-400">шт.</span>-->
<!--                </div>-->
<!--            </div>-->
<!--        </div>-->

        <div class="max-w-8xl mx-auto py-6 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-slate-800 shadow-lg sm:rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="overflow-x-auto min-h-[400px]">



                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                        <thead class="bg-slate-50 dark:bg-slate-900/50">
                        <tr>
                            <th class="th-col w-24">ID / Дата</th>
                            <th class="th-col w-1/4">Название / Файлы</th>
                            <th class="th-col">Контрагент / Сумма</th>
                            <th class="th-col w-1/4">Привязка</th>
                            <th class="th-col w-40">Статус</th>
                            <th class="th-col text-right w-24">Действия</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        <tr v-for="contract in contracts" :key="contract.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition">

                            <!-- 1. Дата -->
                            <td class="px-4 py-4 align-top">
                                <div class="font-mono text-xs text-slate-400">#{{ contract.id }}</div>
                                <div class="text-xs font-medium text-slate-600 dark:text-slate-300 mt-1">{{ formatDate(contract.created_at) }}</div>
                                <!-- Отображаем, кто создал -->
                                <div class="mt-2 text-[10px] text-slate-400">
                                    Автор: {{ contract.creator?.name || 'Неизвестно' }}
                                </div>
                            </td>

                            <!-- 2. Название -->
                            <td class="px-4 py-4 align-top">
                                <template v-if="editingId === contract.id">
                                    <input v-model="editForm.title" class="input-sm mb-1" placeholder="Название" />
                                    <!-- Редактирование типа -->
                                    <select v-model="editForm.type" class="input-sm text-xs">
                                        <option v-for="(cfg, key) in CONTRACT_TYPES" :key="key" :value="key">{{ cfg.label }}</option>
                                    </select>
                                </template>
                                <template v-else>
                                    <div class="text-sm font-bold text-slate-800 dark:text-white mb-1">{{ contract.title }}</div>
                                    <!-- Бейдж типа -->
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium border mb-1"
                                          :class="CONTRACT_TYPES[contract.type]?.class || 'bg-gray-100'">
            {{ CONTRACT_TYPES[contract.type]?.label }}
        </span>
                                    <div v-if="contract.files?.length" class="flex flex-wrap gap-2">
                                        <div v-for="f in contract.files" :key="f.id" class="text-[10px] bg-slate-100 dark:bg-slate-700 px-1.5 py-0.5 rounded flex items-center gap-1 text-slate-600 dark:text-slate-300">
                                            📎 {{ f.file_name.length > 15 ? f.file_name.substring(0,12)+'...' : f.file_name }}
                                        </div>
                                    </div>
                                </template>
                            </td>

                            <!-- 3. Контрагент -->
                            <td class="px-4 py-4 align-top">
                                <template v-if="editingId === contract.id">
                                    <input v-model="editForm.counterparty" class="input-sm mb-1" placeholder="Контрагент" />
                                    <div class="grid grid-cols-2 gap-1">
                                        <input v-model="editForm.amount" type="number" class="input-sm" placeholder="Сумма" title="Общая сумма" />
                                        <input v-model="editForm.margin" type="number" class="input-sm" placeholder="Маржа" title="Наша прибыль" />
                                    </div>
                                </template>
                                <template v-else>
                                    <div class="text-sm text-slate-700 dark:text-slate-200 font-medium">{{ contract.counterparty || '—' }}</div>

                                    <!-- Сумма -->
                                    <div v-if="contract.amount" class="text-xs font-mono text-slate-500 mt-1">
                                        Общ: {{ Number(contract.amount).toLocaleString() }} ₽
                                    </div>

                                    <!-- Маржа (выделяем зеленым) -->
                                    <div v-if="contract.margin" class="text-xs font-mono font-bold text-emerald-600 mt-0.5">
                                        Приб: +{{ Number(contract.margin).toLocaleString() }} ₽
                                    </div>
                                </template>
                            </td>




                            <!-- 4. Привязка -->
                            <td class="px-4 py-4 align-top relative">
                                <template v-if="editingId === contract.id">
                                    <div class="relative w-full">
                                        <input v-model="searchQuery" @focus="showDropdown = true; activeSearchContext = 'table'" class="input-sm pr-6 w-full" placeholder="Поиск..." />
                                        <button v-if="searchQuery" @click="clearSelection" class="absolute right-2 top-1.5 text-slate-400 hover:text-red-500">✕</button>
                                        <div v-if="showDropdown && searchResults.length" class="dropdown-list">
                                            <div v-for="res in searchResults" :key="res.type+res.id" @click="selectSearchResult(res)" class="dropdown-item">
                                                <div class="font-bold">{{ res.label }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                <template v-else>
                                    <div class="flex flex-col gap-1 text-xs">
                                        <div v-if="contract.task_id">
                                            <Link :href="`/tasks/${contract.task_id}`" class="badge-link text-blue-600 bg-blue-50 hover:bg-blue-100">
                                                T-{{ contract.task_id }} Задача
                                            </Link>
                                        </div>
                                        <div v-if="contract.subtask_id">
                                            <Link :href="`/subtasks/${contract.subtask_id}`" class="badge-link text-purple-600 bg-purple-50 hover:bg-purple-100">
                                                S-{{ contract.subtask_id }} Подзадача
                                            </Link>
                                        </div>
                                        <span v-if="!contract.task_id && !contract.subtask_id" class="text-slate-400 italic">—</span>
                                    </div>
                                </template>
                            </td>

                            <!-- 5. Статус (ЖИВОЕ ИЗМЕНЕНИЕ) -->
                            <td class="px-4 py-4 align-top">
                                <!-- Если редактируем всю строку, статус меняется там же -->
                                <template v-if="editingId === contract.id">
                                    <select v-model="editForm.status" class="input-sm">
                                        <option v-for="(cfg, key) in STATUSES" :key="key" :value="key">{{ cfg.label }}</option>
                                    </select>
                                </template>
                                <!-- Если режим просмотра: селект работает сразу -->
                                <template v-else>
                                    <!-- Разрешаем менять статус ЛИБО создателю, ЛИБО можно настроить для всех участников -->
                                    <div class="relative">
                                        <select
                                            :value="contract.status"
                                            @change="quickUpdateStatus(contract, $event.target.value)"
                                            class="appearance-none cursor-pointer w-full pl-3 pr-8 py-1 rounded-md text-xs font-bold border shadow-sm focus:ring-2 focus:ring-blue-500 transition-all outline-none"
                                            :class="STATUSES[contract.status]?.class || 'bg-gray-100 text-gray-700'"
                                            :disabled="!isCreator(contract)"
                                        >
                                            <option v-for="(cfg, key) in STATUSES" :key="key" :value="key">
                                                {{ cfg.label }}
                                            </option>
                                        </select>
                                        <!-- Стрелочка кастомная для красоты -->
                                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-current opacity-60">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                        </div>
                                    </div>
                                </template>
                            </td>

                            <!-- 6. Действия -->
                            <td class="px-4 py-4 align-top text-right">
                                <!-- Режим редактирования -->
                                <template v-if="editingId === contract.id">
                                    <div class="flex justify-end gap-1">
                                        <button @click="saveRow" class="action-btn bg-emerald-100 text-emerald-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg></button>
                                        <button @click="cancelEditing" class="action-btn bg-slate-100 text-slate-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                                    </div>
                                </template>
                                <!-- Режим просмотра -->
                                <template v-else>
                                    <div class="flex justify-end gap-2">
                                        <!-- Файлы видят все участники -->
                                        <button @click="openFilesModal(contract)" class="text-slate-400 hover:text-blue-500 transition" title="Файлы">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                        </button>

                                        <!-- Редактировать и удалять только СОЗДАТЕЛЬ -->
                                        <template v-if="isCreator(contract)">
                                            <button @click="startEditing(contract)" class="text-slate-400 hover:text-indigo-500 transition" title="Редактировать">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                            </button>
                                            <button @click="deleteContract(contract.id)" class="text-slate-400 hover:text-rose-500 transition" title="Удалить">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </template>
                                    </div>
                                </template>
                            </td>
                        </tr>
                        <tr v-if="!contracts.length">
                            <td colspan="6" class="p-12 text-center text-slate-400">Список договоров пуст</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Модальное окно осталось практически без изменений (для экономии места тут скрыто, используйте старое) -->
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showModal = false"></div>
                <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-lg p-6 animate-in fade-in zoom-in-95 duration-200">
                    <h3 class="text-xl font-bold mb-5 text-slate-800 dark:text-white">
                        {{ modalMode === 'create' ? 'Новый договор' : 'Файлы договора' }}
                    </h3>

                    <!-- MODE: CREATE -->
                    <div v-if="modalMode === 'create'" class="space-y-4">
                        <div class="grid grid-cols-3 gap-4">
                            <div class="col-span-2">
                                <label class="block text-xs font-bold text-slate-500 mb-1">Название *</label>
                                <input v-model="modalForm.title" class="input-base" placeholder="Например: Договор поставки №123" />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Тип договора *</label>
                                <select v-model="modalForm.type" class="input-base py-2.5">
                                    <option v-for="(cfg, key) in CONTRACT_TYPES" :key="key" :value="key">
                                        {{ cfg.label }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Контрагент и Сроки -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Контрагент</label>
                                <input v-model="modalForm.counterparty" class="input-base" placeholder="ООО Ромашка" />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Действует до</label>
                                <input v-model="modalForm.valid_until" type="date" class="input-base" />
                            </div>
                        </div>

                        <!-- Финансы -->
                        <div class="grid grid-cols-2 gap-4 bg-slate-50 dark:bg-slate-700/30 p-3 rounded-lg border border-slate-100 dark:border-slate-700">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Общая сумма</label>
                                <input v-model="modalForm.amount" type="number" class="input-base" placeholder="0.00" />
                            </div>
                            <div>
                                <!-- Меняем лейбл в зависимости от типа -->
                                <label class="block text-xs font-bold text-emerald-600 mb-1">
                                    {{ modalForm.type === 'agency' ? 'Агентская комиссия' : 'Маржа / Прибыль' }}
                                </label>
                                <input v-model="modalForm.margin" type="number" class="input-base border-emerald-200 focus:ring-emerald-500" placeholder="0.00" />
                            </div>
                        </div>
                        <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-slate-100 dark:border-slate-700">
                            <button @click="showModal = false" class="btn-ghost">Отмена</button>
                            <button @click="createContract" class="btn-primary">Создать</button>
                        </div>
                    </div>

                    <!-- MODE: FILES (Упрощено для примера) -->
                    <div v-if="modalMode === 'files'" class="space-y-4">
                        <!-- ... код файлов как был ... -->
                        <div class="bg-slate-50 dark:bg-slate-900/50 rounded-xl p-2 max-h-60 overflow-y-auto border border-slate-200 dark:border-slate-700">
                            <div v-if="currentContract?.files?.length" class="space-y-2">
                                <div v-for="file in currentContract.files" :key="file.id" class="flex justify-between items-center bg-white dark:bg-slate-800 p-3 rounded-lg shadow-sm border border-slate-100 dark:border-slate-700">
                                    <div class="flex items-center gap-2 overflow-hidden">
                                        <span class="text-lg">📄</span>
                                        <span class="text-sm truncate max-w-[180px] font-medium text-slate-700 dark:text-slate-200" :title="file.file_name">{{ file.file_name }}</span>
                                    </div>
                                    <div class="flex gap-3 shrink-0">
                                        <a :href="`/api/contracts/files/${file.id}/download`" target="_blank" class="text-blue-500 hover:text-blue-700 text-xs font-bold flex items-center gap-1">Скачать</a>
                                        <!-- Удалять файл может только создатель -->
                                        <button v-if="isCreator(currentContract)" @click="deleteFile(file.id)" class="text-rose-500 hover:text-rose-700 text-xs font-bold">Удалить</button>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-8 text-sm text-slate-400">Нет файлов</div>
                        </div>

                        <div v-if="isCreator(currentContract)" class="pt-4">
                            <input type="file" multiple @change="e => modalForm.files = e.target.files" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:bg-blue-50 file:text-blue-700"/>
                            <div class="flex justify-end gap-3 mt-4">
                                <button @click="uploadFiles" class="btn-primary">Загрузить</button>
                            </div>
                        </div>
                        <button v-else @click="showModal = false" class="btn-ghost w-full mt-4">Закрыть</button>
                    </div>
                </div>
            </div>
        </div>


        <!-- === МОДАЛЬНОЕ ОКНО ОТЧЕТА === -->
        <div v-if="showReportModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showReportModal = false"></div>
            <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-md p-6">

                <h3 class="text-xl font-bold mb-4 text-slate-800 dark:text-white">Скачать отчет</h3>

                <!-- 1. Выбор типа фильтра -->
                <div class="mb-4 space-y-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" value="all" v-model="reportForm.filter_type" @change="onFilterTypeChange" class="accent-blue-600">
                        <span class="text-sm dark:text-slate-200">Все договоры</span>
                    </label>
<!--                    <label class="flex items-center gap-2 cursor-pointer">-->
<!--                        <input type="radio" value="project" v-model="reportForm.filter_type" @change="onFilterTypeChange" class="accent-blue-600">-->
<!--                        <span class="text-sm dark:text-slate-200">По Проекту (все задачи)</span>-->
<!--                    </label>-->
<!--                    <label class="flex items-center gap-2 cursor-pointer">-->
<!--                        <input type="radio" value="task" v-model="reportForm.filter_type" @change="onFilterTypeChange" class="accent-blue-600">-->
<!--                        <span class="text-sm dark:text-slate-200">По конкретной Задаче</span>-->
<!--                    </label>-->
                </div>

                <!-- 2. Поиск Проекта/Задачи (если выбрано не ALL) -->
                <div v-if="reportForm.filter_type !== 'all'" class="mb-4 relative">
                    <label class="block text-xs font-bold text-slate-500 mb-1">
                        {{ reportForm.filter_type === 'project' ? 'Выберите проект' : 'Выберите задачу' }}
                    </label>
                    <input
                        v-model="reportSearchQuery"
                        class="input-base"
                        :placeholder="reportForm.filter_type === 'project' ? 'Поиск проекта...' : 'Поиск задачи...'"
                    />

                    <!-- Выпадающий список -->
                    <div v-if="showReportDropdown && reportSearchResults.length"
                         class="absolute z-10 w-full mt-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-600 rounded-lg shadow-xl max-h-48 overflow-y-auto">
                        <div v-for="res in reportSearchResults" :key="res.id"
                             @click="selectReportTarget(res)"
                             class="px-4 py-2 cursor-pointer hover:bg-blue-50 dark:hover:bg-slate-700 border-b border-slate-100 dark:border-slate-700 last:border-0 text-sm">
                            <!-- Разное отображение для проекта и задачи -->
                            <div class="font-bold text-slate-700 dark:text-slate-200">
                                {{ res.label || res.name }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Даты -->
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">С даты</label>
                        <input v-model="reportForm.start_date" type="date" class="input-base" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">По дату</label>
                        <input v-model="reportForm.end_date" type="date" class="input-base" />
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-700">
                    <button @click="showReportModal = false" class="btn-ghost">Отмена</button>
                    <button
                        @click="downloadReport"
                        class="btn-primary flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700"
                        :disabled="reportForm.filter_type !== 'all' && !reportForm.target_id"
                    >
                        <span>📥</span> Скачать PDF
                    </button>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>

<style scoped>
.input-base { @apply w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none dark:bg-slate-900 dark:text-white transition shadow-sm; }
.input-sm { @apply w-full text-xs border-slate-300 dark:border-slate-600 rounded-md focus:ring-blue-500 focus:border-blue-500 px-2 py-1.5 dark:bg-slate-900 dark:text-white bg-white; }
.th-col { @apply px-4 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider bg-slate-50 dark:bg-slate-800; }
.btn-primary { @apply bg-blue-600 text-white px-5 py-2.5 rounded-lg hover:bg-blue-700 font-medium transition shadow-sm hover:shadow active:scale-95; }
.btn-ghost { @apply bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300 px-5 py-2.5 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 font-medium transition; }
.action-btn { @apply p-1.5 rounded hover:opacity-80 transition; }
.dropdown-list { @apply absolute z-[100] left-0 mt-1 w-64 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-600 rounded-lg shadow-xl max-h-48 overflow-y-auto; }
.dropdown-item { @apply px-3 py-2 text-xs cursor-pointer hover:bg-blue-50 dark:hover:bg-slate-700 border-b border-slate-100 dark:border-slate-700/50 last:border-0 text-left text-slate-700 dark:text-slate-200; }
.badge-link { @apply flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold w-fit transition; }
</style>
