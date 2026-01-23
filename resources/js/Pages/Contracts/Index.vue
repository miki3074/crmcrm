<script setup>
import { ref, onMounted, watch } from "vue"
import { Head, Link } from "@inertiajs/vue3"
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue"
import axios from "axios"
import { debounce } from "lodash" // Убедитесь, что lodash установлен (обычно есть в Laravel)

// --- Состояние данных ---
const contracts = ref([])
const editingId = ref(null) // ID редактируемой строки

// --- Формы ---
// Форма для модального окна (создание)
const modalForm = ref({
    title: "",
    counterparty: "",
    amount: "",
    status: "new",
    task_id: null,
    subtask_id: null,
    files: [],
})

// Форма для редактирования строки
const editForm = ref({
    id: null,
    title: "",
    counterparty: "",
    amount: "",
    status: "",
    task_id: null,
    subtask_id: null,
})

// --- Состояние UI ---
const showModal = ref(false)
const modalMode = ref('create') // 'create' | 'files'
const currentContract = ref(null) // Для режима файлов

// --- Состояние Поиска (Autocomplete) ---
const searchQuery = ref("")       // Строка поиска (отображается в инпуте)
const searchResults = ref([])     // Результаты с бэкенда
const isSearching = ref(false)    // Индикатор загрузки
const showDropdown = ref(false)   // Видимость выпадающего списка
const activeSearchContext = ref('modal') // 'modal' или 'table' (чтобы понимать, куда писать результат)

// ==========================================
// ЛОГИКА ЗАГРУЗКИ И СОХРАНЕНИЯ
// ==========================================

const loadContracts = async () => {
    try {
        const { data } = await axios.get("/api/contracts")
        contracts.value = data
    } catch (e) {
        console.error("Ошибка загрузки договоров", e)
    }
}

// --- Создание ---
const openCreateModal = () => {
    modalMode.value = 'create'
    // Сброс формы
    modalForm.value = { title: "", counterparty: "", amount: "", status: "new", files: [], task_id: null, subtask_id: null }
    // Сброс поиска
    searchQuery.value = ""
    searchResults.value = []

    showModal.value = true
    activeSearchContext.value = 'modal'
}

const createContract = async () => {
    const fd = new FormData();
    fd.append("title", modalForm.value.title);
    fd.append("counterparty", modalForm.value.counterparty || '');
    fd.append("amount", modalForm.value.amount || '');
    fd.append("status", "new");

    if (modalForm.value.task_id) fd.append("task_id", modalForm.value.task_id);
    if (modalForm.value.subtask_id) fd.append("subtask_id", modalForm.value.subtask_id);

    for (let i = 0; i < modalForm.value.files.length; i++) {
        fd.append(`files[${i}]`, modalForm.value.files[i]);
    }

    try {
        await axios.post("/api/contracts", fd)
        showModal.value = false
        await loadContracts()
    } catch (e) {
        alert("Ошибка при создании. Проверьте поля.")
    }
}

// --- Редактирование строки ---
const startEditing = (contract) => {
    editingId.value = contract.id
    activeSearchContext.value = 'table'

    // Копируем данные
    editForm.value = { ...contract }

    // Инициализируем строку поиска, чтобы пользователь видел текущую привязку
    if (contract.task_id) {
        searchQuery.value = `Задача #${contract.task_id}` // Можно улучшить, если передавать title задачи
    } else if (contract.subtask_id) {
        searchQuery.value = `Подзадача #${contract.subtask_id}`
    } else {
        searchQuery.value = ""
    }
    searchResults.value = [] // Очищаем старые результаты
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
            // Принудительно null, если пустые
            task_id: editForm.value.task_id || null,
            subtask_id: editForm.value.subtask_id || null
        })
        await loadContracts()
        editingId.value = null
    } catch (e) {
        console.error(e)
        alert("Ошибка сохранения")
    }
}

// --- Удаление ---
const deleteContract = async (id) => {
    if (!confirm("Удалить договор?")) return
    await axios.delete(`/api/contracts/${id}`)
    await loadContracts()
}

// ==========================================
// ЛОГИКА ПОИСКА (Автокомплит)
// ==========================================

// Функция запроса к API (Debounced)
const fetchTasks = debounce(async (query) => {
    if (!query) {
        searchResults.value = []
        return
    }
    isSearching.value = true
    try {
        // Запрос к методу searchTasks, который мы создали ранее
        const { data } = await axios.get('/api/tasks/search', { params: { query } })
        searchResults.value = data.results
        showDropdown.value = true
    } catch (e) {
        console.error("Ошибка поиска", e)
    } finally {
        isSearching.value = false
    }
}, 300)

// Следим за вводом в инпут
watch(searchQuery, (newVal) => {
    // Если мы просто выбрали элемент (строка стала "Задача #123"), не искать снова
    if (newVal && (newVal.startsWith('Задача #') || newVal.startsWith('Подзадача #'))) return

    fetchTasks(newVal)
})

const selectSearchResult = (item) => {
    // Определяем, в какую форму записывать данные
    const targetForm = activeSearchContext.value === 'modal' ? modalForm : editForm

    if (item.type === 'task') {
        targetForm.value.task_id = item.id
        targetForm.value.subtask_id = null
        searchQuery.value = `Задача #${item.id}: ${item.label.split(':')[1] || ''}`
    } else {
        targetForm.value.subtask_id = item.id
        targetForm.value.task_id = null
        searchQuery.value = `Подзадача #${item.id}: ${item.label.split(':')[1] || ''}`
    }

    showDropdown.value = false
}

const clearSelection = () => {
    const targetForm = activeSearchContext.value === 'modal' ? modalForm : editForm
    targetForm.value.task_id = null
    targetForm.value.subtask_id = null
    searchQuery.value = ""
    searchResults.value = []
}

// ==========================================
// ФАЙЛЫ
// ==========================================
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
    // Нужно передать обязательные поля для валидации, берем текущие
    fd.append("status", currentContract.value.status)

    Array.from(modalForm.value.files).forEach((file, i) => {
        fd.append(`files[${i}]`, file)
    })

    await axios.post(`/api/contracts/${currentContract.value.id}`, fd)
    showModal.value = false
    await loadContracts()
}

const deleteFile = async (fileId) => {
    if (!confirm("Удалить файл?")) return
    await axios.delete(`/api/contracts/files/${fileId}`)
    // Обновляем список файлов локально
    const { data } = await axios.get("/api/contracts")
    contracts.value = data
    currentContract.value = data.find(c => c.id === currentContract.value.id)
}

// --- Хелперы ---
const getStatusColor = (s) => ({
    new: 'bg-blue-100 text-blue-800',
    negotiation: 'bg-yellow-100 text-yellow-800',
    signed: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800'
}[s] || 'bg-gray-100 text-gray-800')

const getStatusLabel = (s) => ({
    new: 'Новый', negotiation: 'Переговоры', signed: 'Заключен', rejected: 'Отказались'
}[s] || s)

const formatDate = (d) => d ? new Date(d).toLocaleDateString('ru-RU') : '—'

onMounted(loadContracts)
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Договоры" />

        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <h2 class="text-2xl font-bold text-slate-800 dark:text-slate-100">
                    📑 Реестр договоров
                </h2>
                <button
                    class="px-4 py-2 rounded-xl bg-blue-600 text-white hover:bg-blue-700 shadow-md transition-all flex items-center gap-2"
                    @click="openCreateModal"
                >
                    <span>+</span> Новый договор
                </button>
            </div>
        </template>

        <div class="max-w-8xl mx-auto py-6 sm:px-6 lg:px-8">

            <!-- Таблица -->
            <div class="bg-white dark:bg-slate-800 shadow-lg sm:rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="overflow-x-auto min-h-[400px]"> <!-- min-h чтобы дропдаун не обрезался снизу -->
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                        <thead class="bg-slate-50 dark:bg-slate-900/50">
                        <tr>
                            <th class="th-col w-24">ID / Дата</th>
                            <th class="th-col w-1/4">Название / Файлы</th>
                            <th class="th-col">Контрагент / Сумма</th>
                            <th class="th-col w-1/4">Привязка к задаче</th>
                            <th class="th-col w-32">Статус</th>
                            <th class="th-col text-right w-24">Действия</th>
                        </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">

                        <tr v-for="contract in contracts" :key="contract.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition">

                            <!-- 1. Дата -->
                            <td class="px-4 py-4 whitespace-nowrap align-top">
                                <div class="font-mono text-xs text-slate-400">#{{ contract.id }}</div>
                                <div class="text-xs font-medium text-slate-600 dark:text-slate-300 mt-1">{{ formatDate(contract.created_at) }}</div>
                            </td>

                            <!-- 2. Название -->
                            <td class="px-4 py-4 align-top">
                                <template v-if="editingId === contract.id">
                                    <input v-model="editForm.title" class="input-sm" placeholder="Название" />
                                </template>
                                <template v-else>
                                    <div class="text-sm font-bold text-slate-800 dark:text-white mb-1">{{ contract.title }}</div>
                                    <div v-if="contract.files?.length" class="flex flex-wrap gap-2">
                                        <div v-for="f in contract.files" :key="f.id" class="text-[10px] bg-slate-100 dark:bg-slate-700 px-1.5 py-0.5 rounded flex items-center gap-1 text-slate-600 dark:text-slate-300">
                                            📎 {{ f.file_name.length > 15 ? f.file_name.substring(0,12)+'...' : f.file_name }}
                                        </div>
                                    </div>
                                </template>
                            </td>

                            <!-- 3. Контрагент / Сумма -->
                            <td class="px-4 py-4 align-top">
                                <template v-if="editingId === contract.id">
                                    <input v-model="editForm.counterparty" class="input-sm mb-1" placeholder="Контрагент" />
                                    <input v-model="editForm.amount" type="number" class="input-sm" placeholder="Сумма" />
                                </template>
                                <template v-else>
                                    <div class="text-sm text-slate-700 dark:text-slate-200">{{ contract.counterparty || '—' }}</div>
                                    <div v-if="contract.amount" class="text-xs font-mono font-bold text-slate-500 mt-1">
                                        {{ Number(contract.amount).toLocaleString() }} ₽
                                    </div>
                                </template>
                            </td>

                            <!-- 4. Привязка (С ПОИСКОМ) -->
                            <td class="px-4 py-4 align-top relative"> <!-- relative нужен для дропдауна -->
                                <template v-if="editingId === contract.id">
                                    <!-- ИНПУТ ПОИСКА В ТАБЛИЦЕ -->
                                    <div class="relative w-full">
                                        <input
                                            v-model="searchQuery"
                                            @focus="showDropdown = true; activeSearchContext = 'table'"
                                            class="input-sm pr-6 w-full"
                                            placeholder="Поиск задачи..."
                                        />
                                        <button v-if="searchQuery" @click="clearSelection" class="absolute right-2 top-1.5 text-slate-400 hover:text-red-500">✕</button>

                                        <!-- Выпадающий список (Table Context) -->
                                        <div v-if="showDropdown && searchResults.length"
                                             class="absolute z-[100] left-0 mt-1 w-64 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-600 rounded-lg shadow-xl max-h-48 overflow-y-auto">
                                            <div v-for="res in searchResults" :key="res.type+res.id"
                                                 @click="selectSearchResult(res)"
                                                 class="px-3 py-2 text-xs cursor-pointer hover:bg-blue-50 dark:hover:bg-slate-700 border-b border-slate-100 dark:border-slate-700/50 last:border-0 text-left">
                                                <div class="font-bold text-slate-700 dark:text-slate-200">{{ res.label }}</div>
                                                <div class="text-[10px] text-slate-400">{{ res.project }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-[10px] text-slate-400 mt-1">
                                        Начните ввод для поиска...
                                    </div>
                                </template>
                                <template v-else>
                                    <div class="flex flex-col gap-1 text-xs">
                                        <div v-if="contract.task_id">
                                            <Link :href="`/tasks/${contract.task_id}`" class="flex items-center gap-1 text-blue-600 hover:underline font-bold">
                                                <span class="bg-blue-100 text-blue-700 px-1 rounded text-[10px]">T-{{ contract.task_id }}</span>
                                                Задача
                                            </Link>
                                        </div>
                                        <div v-if="contract.subtask_id">
                                            <Link :href="`/subtasks/${contract.subtask_id}`" class="flex items-center gap-1 text-purple-600 hover:underline font-bold">
                                                <span class="bg-purple-100 text-purple-700 px-1 rounded text-[10px]">S-{{ contract.subtask_id }}</span>
                                                Подзадача
                                            </Link>
                                        </div>
                                        <span v-if="!contract.task_id && !contract.subtask_id" class="text-slate-400 italic">—</span>
                                    </div>
                                </template>
                            </td>

                            <!-- 5. Статус -->
                            <td class="px-4 py-4 align-top">
                                <template v-if="editingId === contract.id">
                                    <select v-model="editForm.status" class="input-sm">
                                        <option value="new">Новый</option>
                                        <option value="negotiation">Переговоры</option>
                                        <option value="signed">Заключен</option>
                                        <option value="rejected">Отказались</option>
                                    </select>
                                </template>
                                <template v-else>
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-bold rounded-md uppercase tracking-wide"
                                          :class="getStatusColor(contract.status)">
                                        {{ getStatusLabel(contract.status) }}
                                    </span>
                                </template>
                            </td>

                            <!-- 6. Действия -->
                            <td class="px-4 py-4 align-top text-right">
                                <template v-if="editingId === contract.id">
                                    <div class="flex justify-end gap-1">
                                        <button @click="saveRow" class="p-1.5 bg-emerald-100 text-emerald-600 rounded hover:bg-emerald-200" title="Сохранить">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        </button>
                                        <button @click="cancelEditing" class="p-1.5 bg-slate-100 text-slate-600 rounded hover:bg-slate-200" title="Отмена">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </button>
                                    </div>
                                </template>
                                <template v-else>
                                    <div class="flex justify-end gap-2">
                                        <button @click="openFilesModal(contract)" class="text-slate-400 hover:text-blue-500 transition" title="Файлы">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                        </button>
                                        <button @click="startEditing(contract)" class="text-slate-400 hover:text-indigo-500 transition" title="Редактировать">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        </button>
                                        <button @click="deleteContract(contract.id)" class="text-slate-400 hover:text-rose-500 transition" title="Удалить">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </div>
                                </template>
                            </td>
                        </tr>

                        <tr v-if="!contracts.length">
                            <td colspan="6" class="p-12 text-center text-slate-400">
                                Список договоров пуст
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- === МОДАЛЬНОЕ ОКНО === -->
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showModal = false"></div>

                <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-lg p-6 animate-in fade-in zoom-in-95 duration-200">

                    <h3 class="text-xl font-bold mb-5 text-slate-800 dark:text-white">
                        {{ modalMode === 'create' ? 'Новый договор' : 'Файлы договора' }}
                    </h3>

                    <!-- MODE: CREATE -->
                    <div v-if="modalMode === 'create'" class="space-y-4">
                        <input v-model="modalForm.title" class="input-base" placeholder="Название договора *" />

                        <div class="grid grid-cols-2 gap-4">
                            <input v-model="modalForm.counterparty" class="input-base" placeholder="Контрагент" />
                            <input v-model="modalForm.amount" type="number" class="input-base" placeholder="Сумма" />
                        </div>

                        <!-- ПОИСК В МОДАЛКЕ -->
                        <div class="relative">
                            <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Привязка к задаче</label>
                            <input
                                v-model="searchQuery"
                                @focus="showDropdown = true; activeSearchContext = 'modal'"
                                class="input-base"
                                placeholder="Начните вводить название..."
                            />
                            <button v-if="searchQuery" @click="clearSelection" class="absolute right-3 top-8 text-slate-400 hover:text-red-500">✕</button>

                            <!-- Выпадающий список (Modal Context) -->
                            <div v-if="showDropdown && activeSearchContext === 'modal' && searchResults.length"
                                 class="absolute z-10 w-full mt-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-600 rounded-lg shadow-xl max-h-48 overflow-y-auto">
                                <div v-for="res in searchResults" :key="res.type+res.id"
                                     @click="selectSearchResult(res)"
                                     class="px-4 py-2 cursor-pointer hover:bg-blue-50 dark:hover:bg-slate-700 border-b border-slate-100 dark:border-slate-700/50 last:border-0">
                                    <div class="font-bold text-sm text-slate-700 dark:text-slate-200">{{ res.label }}</div>
                                    <div class="text-xs text-slate-400">{{ res.project }}</div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Файлы</label>
                            <input type="file" multiple @change="e => modalForm.files = e.target.files" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-slate-700 dark:file:text-slate-300"/>
                        </div>

                        <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-slate-100 dark:border-slate-700">
                            <button @click="showModal = false" class="btn-ghost">Отмена</button>
                            <button @click="createContract" class="btn-primary">Создать</button>
                        </div>
                    </div>

                    <!-- MODE: FILES -->
                    <div v-if="modalMode === 'files'" class="space-y-4">
                        <p class="text-sm text-slate-500 mb-2">Договор: <b class="text-slate-800 dark:text-slate-200">{{ currentContract?.title }}</b></p>

                        <div class="bg-slate-50 dark:bg-slate-900/50 rounded-xl p-2 max-h-60 overflow-y-auto border border-slate-200 dark:border-slate-700">
                            <div v-if="currentContract?.files?.length" class="space-y-2">
                                <div v-for="file in currentContract.files" :key="file.id" class="flex justify-between items-center bg-white dark:bg-slate-800 p-3 rounded-lg shadow-sm border border-slate-100 dark:border-slate-700">
                                    <div class="flex items-center gap-2 overflow-hidden">
                                        <span class="text-lg">📄</span>
                                        <span class="text-sm truncate max-w-[180px] font-medium text-slate-700 dark:text-slate-200" :title="file.file_name">{{ file.file_name }}</span>
                                    </div>
                                    <div class="flex gap-3 shrink-0">
                                        <a :href="`/api/contracts/files/${file.id}/download`" target="_blank" class="text-blue-500 hover:text-blue-700 text-xs font-bold flex items-center gap-1">Скачать</a>
                                        <button @click="deleteFile(file.id)" class="text-rose-500 hover:text-rose-700 text-xs font-bold">Удалить</button>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-8 text-sm text-slate-400">Нет файлов</div>
                        </div>

                        <div class="pt-4">
                            <label class="block text-xs font-bold text-slate-500 mb-2 uppercase">Добавить файлы</label>
                            <input type="file" multiple @change="e => modalForm.files = e.target.files" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-slate-700 dark:file:text-slate-300"/>
                        </div>

                        <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-slate-100 dark:border-slate-700">
                            <button @click="showModal = false" class="btn-ghost">Закрыть</button>
                            <button @click="uploadFiles" class="btn-primary">Загрузить</button>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Базовый стиль инпута для модалки */
.input-base {
    @apply w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none dark:bg-slate-900 dark:text-white transition shadow-sm;
}

/* Маленький инпут для таблицы */
.input-sm {
    @apply w-full text-xs border-slate-300 dark:border-slate-600 rounded-md focus:ring-blue-500 focus:border-blue-500 px-2 py-1.5 dark:bg-slate-900 dark:text-white bg-white;
}

/* Заголовки таблицы */
.th-col {
    @apply px-4 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider bg-slate-50 dark:bg-slate-800;
}

/* Кнопки */
.btn-primary {
    @apply bg-blue-600 text-white px-5 py-2.5 rounded-lg hover:bg-blue-700 font-medium transition shadow-sm hover:shadow active:scale-95;
}

.btn-ghost {
    @apply bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300 px-5 py-2.5 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 font-medium transition;
}
</style>
