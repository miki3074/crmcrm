<script setup>
import { ref, onMounted, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import axios from 'axios'
import ProducersBuyersManager from '@/Components/ProducersBuyersManager.vue'

/* ------------------------- STATE ------------------------- */

const loading = ref(true)
const templates = ref([])

const producers = ref([])
const buyers = ref([])

const loadProducers = async () => {
    if (!templateForm.value.company_id) return

    const { data } = await axios.get(
        `/api/task-templates/companies/${templateForm.value.company_id}/producers`
    )

    producers.value = data
}

const loadBuyers = async () => {
    if (!templateForm.value.company_id) return

    const { data } = await axios.get(
        `/api/task-templates/companies/${templateForm.value.company_id}/buyers`
    )

    buyers.value = data
}


// справочники
const companiesOwned = ref([])
const companiesMember = ref([])
const projects = ref([])
const employees = ref([])

// модалки
const showTemplateModal = ref(false)
const showCreateTaskModal = ref(false)

// выбранный шаблон
const activeTemplate = ref(null)

// форма шаблона
const templateForm = ref({
    company_id: '',
    project_id: '',
    title: '',
    description: '',
    executor_ids: [],
    responsible_ids: [],
    watcher_ids: [],
    due_in_days: '',
    priority: 'low',
    files: null,
})

// форма создания задачи по шаблону
const taskForm = ref({
    title: '',
    description: '',
    start_date: new Date().toISOString().slice(0, 10),
    due_date: '',
    executor_ids: [],
    responsible_ids: [],
    watcher_ids: [],
    priority: '',
})

// ошибки
const errorText = ref('')

/* ------------------------- API ------------------------- */

const loadTemplates = async () => {
    const { data } = await axios.get('/api/task-templates')
    templates.value = data
}

const loadCompanies = async () => {
    const { data } = await axios.get('/api/task-templates/companies')
    companiesOwned.value = data.owned
    companiesMember.value = data.member
}

const loadProjects = async () => {
    if (!templateForm.value.company_id) return
    const { data } = await axios.get(`/api/task-templates/companies/${templateForm.value.company_id}/projects`)
    projects.value = data
}

const loadEmployees = async () => {
    if (!templateForm.value.company_id) return
    const { data } = await axios.get(`/api/task-templates/companies/${templateForm.value.company_id}/employees`)
    employees.value = data
}

/* ------------------------- CREATE TEMPLATE ------------------------- */

const openCreateTemplate = () => {
    errorText.value = ''
    showTemplateModal.value = true
}

const saveTemplate = async () => {
    errorText.value = ''

    const fd = new FormData()
    Object.entries(templateForm.value).forEach(([key, value]) => {
        if (Array.isArray(value)) {
            value.forEach(v => fd.append(`${key}[]`, v))
        } else {
            fd.append(key, value ?? '')
        }
    })

    if (templateForm.value.files) {
        for (let i = 0; i < templateForm.value.files.length; i++) {
            fd.append(`files[]`, templateForm.value.files[i])
        }
    }

    try {
        await axios.post('/api/task-templates', fd)
        showTemplateModal.value = false
        await loadTemplates()
    } catch (e) {
        errorText.value = e.response?.data?.message || 'Ошибка создания шаблона'
    }
}

/* ------------------------- CREATE TASK FROM TEMPLATE ------------------------- */

const openCreateTaskModal = (template) => {
    activeTemplate.value = template

    // загружаем сотрудников компании
    axios.get(`/api/task-templates/companies/${template.company_id}/employees`)
        .then(res => employees.value = res.data)

    taskForm.value = {
        title: template.title,
        description: template.description,
        start_date: new Date().toISOString().slice(0, 10),
        executor_ids: [...(template.executor_ids ?? [])],
        responsible_ids: [...(template.responsible_ids ?? [])],
        watcher_ids: [...(template.watcher_ids ?? [])],
        priority: template.priority,
    }

    showCreateTaskModal.value = true
}

const createTaskFromTemplate = async () => {
    try {
        await axios.post(`/api/task-templates/${activeTemplate.value.id}/create-task`, taskForm.value)
        showCreateTaskModal.value = false
        alert('Задача успешно создана!')
    } catch (e) {
        errorText.value = e.response?.data?.message || 'Ошибка при создании задачи'
    }
}

//РЕДАКТИРОВАНИЯ ШАБЛОНА
const showEditTemplateModal = ref(false)
const editForm = ref({})

const openEditTemplate = async (tpl) => {
    activeTemplate.value = tpl

    // шаг 1: загрузить компании, проекты, сотрудников
    await loadCompanies()

    await loadProducersForEdit()
    await loadBuyersForEdit()

    templateForm.value.company_id = tpl.company_id

    // загрузить проекты для выбранной компании
    await axios
        .get(`/api/task-templates/companies/${tpl.company_id}/projects`)
        .then(res => projects.value = res.data)

    // загрузить сотрудников для выбранной компании
    await axios
        .get(`/api/task-templates/companies/${tpl.company_id}/employees`)
        .then(res => employees.value = res.data)

    editForm.value = {
        id: tpl.id,
        company_id: tpl.company_id,
        project_id: tpl.project_id,
        title: tpl.title,
        description: tpl.description,
        executor_ids: [...tpl.executor_ids ?? []],
        responsible_ids: [...tpl.responsible_ids ?? []],
        watcher_ids: [...tpl.watcher_ids ?? []],
        due_in_days: tpl.due_in_days,
        priority: tpl.priority,

        producer_id: tpl.producer_id ?? "",
        buyer_id: tpl.buyer_id ?? "",
    }

    showEditTemplateModal.value = true
}

const loadProducersForEdit = async () => {
    if (!editForm.value.company_id) return;
    const { data } = await axios.get(
        `/api/task-templates/companies/${editForm.value.company_id}/producers`
    );
    producers.value = data;

    // Если выбранный producer больше не принадлежит компании → очищаем
    if (!producers.value.some(p => p.id === editForm.value.producer_id)) {
        editForm.value.producer_id = "";
    }
};

const loadBuyersForEdit = async () => {
    if (!editForm.value.company_id) return;
    const { data } = await axios.get(
        `/api/task-templates/companies/${editForm.value.company_id}/buyers`
    );
    buyers.value = data;

    if (!buyers.value.some(b => b.id === editForm.value.buyer_id)) {
        editForm.value.buyer_id = "";
    }
};


//удаление файлов
const deleteTemplateFile = async (fileId) => {
    if (!confirm('Удалить файл?')) return

    try {
        await axios.delete(`/api/task-template-files/${fileId}`)
        // перезагружаем шаблон
        await loadTemplates()
        // переоткрываем модалку — чтобы обновилось
        openEditTemplate(activeTemplate.value)
    } catch (e) {
        alert("Ошибка при удалении файла")
    }
}


const saveEditedTemplate = async () => {
    try {
        const fd = new FormData()

        Object.entries(editForm.value).forEach(([key, value]) => {
            if (Array.isArray(value)) {
                value.forEach(v => fd.append(`${key}[]`, v))
            } else if (key !== 'files') {
                fd.append(key, value ?? '')
            }
        })

        if (editForm.value.files) {
            for (let i = 0; i < editForm.value.files.length; i++) {
                fd.append('files[]', editForm.value.files[i])
            }
        }

        fd.append('_method', 'PUT')

        await axios.post(`/api/task-templates/${editForm.value.id}`, fd)

        showEditTemplateModal.value = false
        await loadTemplates()
    } catch (e) {
        alert("Ошибка при сохранении шаблона")
    }
}

const loadProjectsForEdit = async () => {
    if (!editForm.value.company_id) return
    const { data } = await axios.get(`/api/task-templates/companies/${editForm.value.company_id}/projects`)
    projects.value = data
}

const loadEmployeesForEdit = async () => {
    if (!editForm.value.company_id) return
    const { data } = await axios.get(`/api/task-templates/companies/${editForm.value.company_id}/employees`)
    employees.value = data

    // очистка выбранных пользователей, если они не принадлежат новой компании
    editForm.value.executor_ids = editForm.value.executor_ids.filter(id => employees.value.some(e => e.id === id))
    editForm.value.responsible_ids = editForm.value.responsible_ids.filter(id => employees.value.some(e => e.id === id))
    editForm.value.watcher_ids = editForm.value.watcher_ids.filter(id => employees.value.some(e => e.id === id))
}



//Удаление
const confirmDeleteTemplate = async (tpl) => {
    if (!confirm(`Удалить шаблон "${tpl.title}"?`)) return;

    await axios.delete(`/api/task-templates/${tpl.id}`);
    await loadTemplates();
};

//Дублирование
const showDuplicateModal = ref(false)
const duplicateForm = ref({})

const openDuplicateTemplate = async (tpl) => {
    activeTemplate.value = tpl

    await loadCompanies()

    duplicateForm.value = {
        company_id: tpl.company_id,
        project_id: tpl.project_id,
        title: tpl.title + " (копия)",
        description: tpl.description,
        executor_ids: [...(tpl.executor_ids ?? [])],
        responsible_ids: [...(tpl.responsible_ids ?? [])],
        watcher_ids: [...(tpl.watcher_ids ?? [])],
        due_in_days: tpl.due_in_days,
        priority: tpl.priority,

        producer_id: tpl.producer_id ?? null,
        buyer_id: tpl.buyer_id ?? null,

        copy_files: true,  // ⬅️ по умолчанию копируем файлы
        files: null,
    }

    // загрузить проекты выбранной компании
    await axios
        .get(`/api/task-templates/companies/${tpl.company_id}/projects`)
        .then(res => projects.value = res.data)

    // загрузить сотрудников выбранной компании
    await axios
        .get(`/api/task-templates/companies/${tpl.company_id}/employees`)
        .then(res => employees.value = res.data)

    await Promise.all([
        loadProjectsForDuplicate(),
        loadEmployeesForDuplicate(),
        loadProducersForDuplicate(),
        loadBuyersForDuplicate(),
    ]);

    showDuplicateModal.value = true
}

const loadProducersForDuplicate = async () => {
    if (!duplicateForm.value.company_id) return;
    const { data } = await axios.get(`/api/task-templates/companies/${duplicateForm.value.company_id}/producers`);
    producers.value = data;

    // если выбранный producer не принадлежит компании — очищаем
    if (duplicateForm.value.producer_id && !producers.value.some(p => p.id === duplicateForm.value.producer_id)) {
        duplicateForm.value.producer_id = null;
    }
}

const loadBuyersForDuplicate = async () => {
    if (!duplicateForm.value.company_id) return;
    const { data } = await axios.get(`/api/task-templates/companies/${duplicateForm.value.company_id}/buyers`);
    buyers.value = data;

    if (duplicateForm.value.buyer_id && !buyers.value.some(p => p.id === duplicateForm.value.buyer_id)) {
        duplicateForm.value.buyer_id = null;
    }
}


const saveDuplicateTemplate = async () => {
    try {
        const fd = new FormData()

        Object.entries(duplicateForm.value).forEach(([key, value]) => {
            if (Array.isArray(value)) {
                value.forEach(v => fd.append(`${key}[]`, v))
            } else if (key !== 'files') {
                fd.append(key, value ?? '')
            }
        })

        if (duplicateForm.value.files) {
            for (let i = 0; i < duplicateForm.value.files.length; i++) {
                fd.append('files[]', duplicateForm.value.files[i])
            }
        }

        await axios.post(`/api/task-templates/${activeTemplate.value.id}/duplicate`, fd)

        showDuplicateModal.value = false
        await loadTemplates()
    } catch (e) {
        console.log(e)
        alert("Ошибка при дублировании шаблона")
    }
}

const loadProjectsForDuplicate = async () => {
    if (!duplicateForm.value.company_id) return
    const { data } = await axios.get(`/api/task-templates/companies/${duplicateForm.value.company_id}/projects`)
    projects.value = data
}

const loadEmployeesForDuplicate = async () => {
    if (!duplicateForm.value.company_id) return
    const { data } = await axios.get(`/api/task-templates/companies/${duplicateForm.value.company_id}/employees`)
    employees.value = data

    duplicateForm.value.executor_ids = duplicateForm.value.executor_ids.filter(id => employees.value.some(e => e.id === id))
    duplicateForm.value.responsible_ids = duplicateForm.value.responsible_ids.filter(id => employees.value.some(e => e.id === id))
    duplicateForm.value.watcher_ids = duplicateForm.value.watcher_ids.filter(id => employees.value.some(e => e.id === id))
}

const showProducerBuyerModal = ref(false)

const allCompanies = computed(() => [
    ...companiesOwned.value,
    ...companiesMember.value
])


/* ------------------------- INIT ------------------------- */

onMounted(async () => {
    await Promise.all([loadCompanies(), loadTemplates()])
    loading.value = false
})
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Шаблоны задач" />

        <div class="max-w-6xl mx-auto p-6">
            <h1 class="text-2xl font-bold mb-4">📑 Шаблоны задач</h1>

            <button
                @click="openCreateTemplate"
                class="px-4 py-2 bg-indigo-600 text-white rounded-lg mb-6"
            >
                ➕ Создать шаблон
            </button>
<br/>
            <button
                @click="showProducerBuyerModal = true"
                class="px-4 py-2 bg-indigo-600 text-white rounded-lg mb-5"
            >
                ➕ Добавить производителя / покупателя
            </button>


            <ProducersBuyersManager
                :companies="allCompanies"
                v-if="showProducerBuyerModal"
                @close="showProducerBuyerModal = false"
                @selectProducer="producer => console.log('Выбран производитель:', producer)"
                @selectBuyer="buyer => console.log('Выбран покупатель:', buyer)"
            />







            <!-- СПИСОК ШАБЛОНОВ -->
            <div class="space-y-4">
                <div
                    v-for="tpl in templates"
                    :key="tpl.id"
                    class="p-4 border rounded-xl bg-white dark:bg-slate-800 shadow"
                >
                    <h2 class="font-semibold text-lg">{{ tpl.title }}</h2>
                    <p class="text-sm text-gray-500">Компания: {{ tpl.company.name }}</p>
                    <p class="text-sm text-gray-500">Проект: {{ tpl.project.name }}</p>

                    <button
                        @click="openCreateTaskModal(tpl)"
                        class="mt-3 px-3 py-2 bg-emerald-600 text-white rounded-lg"
                    >
                        📝 Создать задачу по шаблону
                    </button>

                    <div class="flex gap-2 mt-3">

                        <!-- Редактировать — только создатель -->
                        <button
                            v-if="tpl.creator_id === $page.props.auth.user.id"
                            @click="openEditTemplate(tpl)"
                            class="px-3 py-1 bg-amber-500 text-white rounded"
                        >
                            ✏ Редактировать
                        </button>

                        <!-- Удалить — только создатель -->
                        <button
                            v-if="tpl.creator_id === $page.props.auth.user.id"
                            @click="confirmDeleteTemplate(tpl)"
                            class="px-3 py-1 bg-rose-600 text-white rounded"
                        >
                            🗑 Удалить
                        </button>

                        <!-- Дублировать — доступно всем -->
                        <button
                            @click="openDuplicateTemplate(tpl)"
                            class="px-3 py-1 bg-blue-500 text-white rounded"
                        >
                            📄 Дублировать
                        </button>

                    </div>


                </div>
            </div>
        </div>

        <!-- МОДАЛКА СОЗДАНИЯ ШАБЛОНА -->
        <div v-if="showTemplateModal" class="modal">
            <div class="modal-content max-w-lg">
                <h2 class="text-xl font-bold mb-4">Создать шаблон</h2>

                <select
                    v-model="templateForm.company_id"
                    @change="loadProjects(); loadEmployees(); loadProducers(); loadBuyers();"
                    class="input mb-3"
                >
                    <option disabled value="">Выберите компанию</option>

                    <optgroup label="Мои компании">
                        <option v-for="c in companiesOwned" :value="c.id">{{ c.name }}</option>
                    </optgroup>

                    <optgroup label="Компании, где я участник">
                        <option v-for="c in companiesMember" :value="c.id">{{ c.name }}</option>
                    </optgroup>
                </select>

                <select v-model="templateForm.project_id" class="input mb-3">
                    <option value="">Выберите проект</option>
                    <option v-for="p in projects" :value="p.id">{{ p.name }}</option>
                </select>

                <input v-model="templateForm.title" class="input mb-3" placeholder="Название шаблона" />


                <label class="font-semibold text-sm">Производитель</label>
                <select v-model="templateForm.producer_id" class="input mb-3">
                    <option value="">— Не ставить —</option>

                    <option
                        v-for="p in producers"
                        :key="p.id"
                        :value="p.id"
                    >
                        {{ p.name }}
                    </option>
                </select>



                <!-- ВЫБОР ПОКУПАТЕЛЯ -->
                <label class="font-semibold text-sm">Покупатель</label>
                <select v-model="templateForm.buyer_id" class="input mb-3">
                    <option value="">— Не ставить —</option>

                    <option
                        v-for="b in buyers"
                        :key="b.id"
                        :value="b.id"
                    >
                        {{ b.name }}
                    </option>
                </select>


                <textarea v-model="templateForm.description" class="input mb-3" placeholder="Описание"></textarea>

                <!-- исполнители -->
                <label class="font-semibold text-sm">Исполнители</label>

                <div class="border p-3 rounded-lg max-h-40 overflow-auto mb-3">
                    <div
                        v-for="e in employees"
                        :key="e.id"
                        class="flex items-center gap-2 mb-1"
                    >
                        <input
                            type="checkbox"
                            :value="e.id"
                            v-model="templateForm.executor_ids"
                            class="w-4 h-4 text-indigo-600 rounded border-gray-300"
                        />
                        <span>{{ e.name }}</span>
                    </div>
                </div>

                <!-- ответственные -->
                <label class="font-semibold text-sm">Ответственные</label>
                <div class="border p-3 rounded-lg max-h-40 overflow-auto mb-3">
                    <div
                        v-for="e in employees"
                        :key="e.id"
                        class="flex items-center gap-2 mb-1"
                    >
                        <input
                            type="checkbox"
                            :value="e.id"
                            v-model="templateForm.responsible_ids"
                            class="w-4 h-4 text-indigo-600 rounded border-gray-300"
                        />
                        <span>{{ e.name }}</span>
                    </div>
                </div>

                <!-- наблюдатели -->
                <label class="font-semibold text-sm">Наблюдатели</label>
                <div class="border p-3 rounded-lg max-h-40 overflow-auto mb-3">
                    <div
                        v-for="e in employees"
                        :key="e.id"
                        class="flex items-center gap-2 mb-1"
                    >
                        <input
                            type="checkbox"
                            :value="e.id"
                            v-model="templateForm.watcher_ids"
                            class="w-4 h-4 text-indigo-600 rounded border-gray-300"
                        />
                        <span>{{ e.name }}</span>
                    </div>
                </div>


                <input
                    type="number"
                    v-model="templateForm.due_in_days"
                    class="input mb-3"
                    placeholder="Крайний срок через N дней"
                />

                <select v-model="templateForm.priority" class="input mb-3">
                    <option value="low">Низкий</option>
                    <option value="medium">Средний</option>
                    <option value="high">Высокий</option>
                </select>

                <input type="file" multiple @change="e => templateForm.files = e.target.files" />

                <p v-if="errorText" class="text-red-600 text-sm mt-2">{{ errorText }}</p>

                <div class="flex justify-end gap-2 mt-4">
                    <button @click="showTemplateModal = false" class="btn-gray">Отмена</button>
                    <button @click="saveTemplate" class="btn-blue">Сохранить</button>
                </div>
            </div>
        </div>


        <!-- МОДАЛКА СОЗДАНИЯ ЗАДАЧИ ПО ШАБЛОНУ -->
        <div v-if="showCreateTaskModal" class="modal">
            <div class="modal-content max-w-2xl">

                <h2 class="text-2xl font-bold mb-4">
                    📝 Создать задачу по шаблону: "{{ activeTemplate.title }}"
                </h2>

                <!-- Компания -->
                <div>
                    <label class="font-semibold text-sm">Компания:</label>
                    <input class="input mb-3 bg-gray-100 dark:bg-gray-700" :value="activeTemplate.company.name" disabled />
                </div>

                <!-- Проект -->
                <div>
                    <label class="font-semibold text-sm">Проект:</label>
                    <input class="input mb-3 bg-gray-100 dark:bg-gray-700" :value="activeTemplate.project.name" disabled />
                </div>

                <!-- Название -->
                <div>
                    <label class="font-semibold text-sm">Название задачи</label>
                    <input v-model="taskForm.title" class="input mb-3" placeholder="Название" />
                </div>

                <div v-if="activeTemplate.producer">
                    <label class="font-semibold text-sm">Производитель:</label>
                    <input
                        class="input mb-3 bg-gray-100 dark:bg-gray-700"
                        :value="activeTemplate.producer.name"
                        disabled
                    />
                </div>

                <div v-if="activeTemplate.buyer">
                    <label class="font-semibold text-sm">Покупатель:</label>
                    <input
                        class="input mb-3 bg-gray-100 dark:bg-gray-700"
                        :value="activeTemplate.buyer.name"
                        disabled
                    />
                </div>


                <!-- Описание -->
                <div>
                    <label class="font-semibold text-sm">Описание</label>
                    <textarea v-model="taskForm.description" class="input mb-3" placeholder="Описание"></textarea>
                </div>

                <!-- Дата начала -->
                <div>
                    <label class="font-semibold text-sm">Дата начала</label>
                    <input type="date" v-model="taskForm.start_date" class="input mb-3" />
                </div>

                <!-- Дедлайн -->
                <div>
                    <label class="font-semibold text-sm">Дедлайн</label>
                    <input
                        class="input mb-3 bg-gray-100 dark:bg-gray-700"
                        :value="activeTemplate.due_in_days + ' дней после начала'"
                        disabled
                    />
                    <p class="text-xs text-gray-500">
                        Итоговая дата будет рассчитана автоматически.
                    </p>

                </div>

                <!-- Приоритет -->
                <div>
                    <label class="font-semibold text-sm">Приоритет</label>
                    <select v-model="taskForm.priority" class="input mb-3">
                        <option value="low">Низкий</option>
                        <option value="medium">Средний</option>
                        <option value="high">Высокий</option>
                    </select>
                </div>

                <!-- Исполнители -->
                <div>
                    <label class="font-semibold text-sm">Исполнители</label>
                    <div class="border p-3 rounded-lg max-h-40 overflow-auto mb-3">
                        <div
                            v-for="s in employees"
                            :key="s.id"
                            class="flex items-center gap-2 mb-1"
                        >
                            <input
                                type="checkbox"
                                :value="s.id"
                                v-model="taskForm.executor_ids"
                                class="w-4 h-4 text-indigo-600 rounded border-gray-300"
                            />
                            <span>{{ s.name }}</span>
                        </div>
                    </div>

                </div>

                <!-- Ответственные -->
                <div>
                    <label class="font-semibold text-sm">Ответственные</label>
                    <div class="border p-3 rounded-lg max-h-40 overflow-auto mb-3">
                        <div
                            v-for="s in employees"
                            :key="s.id"
                            class="flex items-center gap-2 mb-1"
                        >
                            <input
                                type="checkbox"
                                :value="s.id"
                                v-model="taskForm.responsible_ids"
                                class="w-4 h-4 text-indigo-600 rounded border-gray-300"
                            />
                            <span>{{ s.name }}</span>
                        </div>
                    </div>

                </div>

                <!-- Наблюдатели -->
                <div>
                    <label class="font-semibold text-sm">Наблюдатели</label>
                    <div class="border p-3 rounded-lg max-h-40 overflow-auto mb-3">
                        <div
                            v-for="s in employees"
                            :key="s.id"
                            class="flex items-center gap-2 mb-1"
                        >
                            <input
                                type="checkbox"
                                :value="s.id"
                                v-model="taskForm.watcher_ids"
                                class="w-4 h-4 text-indigo-600 rounded border-gray-300"
                            />
                            <span>{{ s.name }}</span>
                        </div>
                    </div>

                </div>

                <!-- Файлы шаблона -->
                <div v-if="activeTemplate.files?.length">
                    <label class="font-semibold text-sm">Файлы шаблона:</label>

                    <ul class="list-disc ml-6 mb-3">
                        <li
                            v-for="f in activeTemplate.files"
                            :key="f.id"
                        >
                            <a :href="`/storage/${f.file_path}`"
                               target="_blank"
                               class="text-blue-600 underline">
                                📎 {{ f.file_name }}
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Кнопки -->
                <div class="flex justify-end gap-2 mt-4">
                    <button @click="showCreateTaskModal = false" class="btn-gray">
                        Отмена
                    </button>

                    <button @click="createTaskFromTemplate" class="btn-blue">
                        Создать задачу
                    </button>
                </div>

            </div>
        </div>

<!--Редактирование-->

        <div v-if="showEditTemplateModal" class="modal">
            <div class="modal-content max-w-lg">

                <h2 class="text-xl font-bold mb-4">Редактировать шаблон</h2>
                <label class="font-semibold text-sm">Компания</label>
                <select
                    v-model="editForm.company_id"
                    @change="
        loadProjectsForEdit();
        loadEmployeesForEdit();
        loadProducersForEdit();
        loadBuyersForEdit();
    "
                    class="input mb-3"
                >
                    <optgroup label="Мои компании">
                        <option v-for="c in companiesOwned" :value="c.id">{{ c.name }}</option>
                    </optgroup>

                    <optgroup label="Компании, где я участник">
                        <option v-for="c in companiesMember" :value="c.id">{{ c.name }}</option>
                    </optgroup>
                </select>

                <label class="font-semibold text-sm">Проект</label>
                <select v-model="editForm.project_id" class="input mb-3">
                    <option disabled value="">Выберите проект</option>
                    <option v-for="p in projects" :value="p.id">{{ p.name }}</option>
                </select>


                <label class="font-semibold text-sm">Название</label>
                <input v-model="editForm.title" class="input mb-3" placeholder="Название" />

                <label class="font-semibold text-sm mt-3">Производитель</label>
                <select v-model="editForm.producer_id" class="input mb-3">
                    <option disabled value="">Выберите производителя</option>
                    <option v-for="p in producers" :value="p.id">{{ p.name }}</option>
                </select>

                <label class="font-semibold text-sm mt-3">Покупатель</label>
                <select v-model="editForm.buyer_id" class="input mb-3">
                    <option disabled value="">Выберите покупателя</option>
                    <option v-for="b in buyers" :value="b.id">{{ b.name }}</option>
                </select>


                <label class="font-semibold text-sm">Описание</label>
                <textarea v-model="editForm.description" class="input mb-3" placeholder="Описание"></textarea>

                <!-- Исполнители (чекбоксы) -->
                <label class="font-semibold text-sm">Исполнители</label>
                <div class="border p-3 rounded">
                    <div v-for="e in employees" :key="e.id" class="flex items-center gap-2">
                        <input type="checkbox" :value="e.id" v-model="editForm.executor_ids" />
                        <span>{{ e.name }}</span>
                    </div>
                </div>

                <!-- Ответственные -->
                <label class="font-semibold text-sm mt-3">Ответственные</label>
                <div class="border p-3 rounded">
                    <div v-for="e in employees" :key="e.id" class="flex items-center gap-2">
                        <input type="checkbox" :value="e.id" v-model="editForm.responsible_ids" />
                        <span>{{ e.name }}</span>
                    </div>
                </div>

                <!-- Наблюдатели -->
                <label class="font-semibold text-sm mt-3">Наблюдатели</label>
                <div class="border p-3 rounded">
                    <div v-for="e in employees" :key="e.id" class="flex items-center gap-2">
                        <input type="checkbox" :value="e.id" v-model="editForm.watcher_ids" />
                        <span>{{ e.name }}</span>
                    </div>
                </div>
                <label class="font-semibold text-sm mt-3">Срок в днях</label>
                <input type="number" v-model="editForm.due_in_days" class="input mt-3" placeholder="Срок в днях" />
                <label class="font-semibold text-sm mt-3">Приоритет</label>
                <select v-model="editForm.priority" class="input mb-3">
                    <option value="low">Низкий</option>
                    <option value="medium">Средний</option>
                    <option value="high">Высокий</option>
                </select>

<!--                 Текущие файлы-->
                <div v-if="activeTemplate.files?.length" class="mb-3">
                    <label class="font-semibold text-sm">Файлы шаблона:</label>

                    <ul class="ml-4 mt-1 space-y-1">
                        <li v-for="f in activeTemplate.files" :key="f.id" class="flex items-center gap-2">
                            <a :href="`/storage/${f.file_path}`" target="_blank" class="text-blue-600 underline">
                                📎 {{ f.file_name }}
                            </a>

                            <button
                                class="text-red-600 hover:text-red-800 text-sm"
                                @click="deleteTemplateFile(f.id)"
                            >
                                ✖
                            </button>
                        </li>
                    </ul>
                </div>

                <!-- Добавить новые файлы -->
                <label class="font-semibold text-sm mt-3">Добавить файлы</label>
                <input type="file" multiple @change="e => editForm.files = e.target.files" class="input mb-3" />

                <div class="flex justify-end gap-2 mt-4">
                    <button @click="showEditTemplateModal = false" class="btn-gray">Отмена</button>
                    <button @click="saveEditedTemplate" class="btn-blue">Сохранить</button>
                </div>

            </div>
        </div>

        <!--Дублирование-->

        <!-- МОДАЛКА ДУБЛИРОВАНИЯ -->
        <div v-if="showDuplicateModal" class="modal">
            <div class="modal-content max-w-lg">

                <h2 class="text-xl font-bold mb-4">Дублировать шаблон</h2>

                <label class="font-semibold text-sm">Компания</label>
                <select
                    v-model="duplicateForm.company_id"
                    @change="loadProjectsForDuplicate(); loadEmployeesForDuplicate()"
                    class="input mb-3"
                >
                    <optgroup label="Мои компании">
                        <option v-for="c in companiesOwned" :value="c.id">{{ c.name }}</option>
                    </optgroup>

                    <optgroup label="Компании, где я участник">
                        <option v-for="c in companiesMember" :value="c.id">{{ c.name }}</option>
                    </optgroup>
                </select>

                <label class="font-semibold text-sm">Проект</label>
                <select v-model="duplicateForm.project_id" class="input mb-3">
                    <option disabled value="">Выберите проект</option>
                    <option v-for="p in projects" :value="p.id">{{ p.name }}</option>
                </select>

                <input v-model="duplicateForm.title" class="input mb-3" placeholder="Название" />

                <label class="font-semibold text-sm">Производитель</label>
                <select v-model="duplicateForm.producer_id" class="input mb-3">
                    <option disabled value="">Выберите производителя</option>
                    <option v-for="p in producers" :value="p.id">{{ p.name }}</option>
                </select>

                <label class="font-semibold text-sm">Покупатель</label>
                <select v-model="duplicateForm.buyer_id" class="input mb-3">
                    <option disabled value="">Выберите покупателя</option>
                    <option v-for="b in buyers" :value="b.id">{{ b.name }}</option>
                </select>


                <textarea v-model="duplicateForm.description" class="input mb-3" placeholder="Описание"></textarea>

                <label class="font-semibold text-sm">Исполнители</label>
                <div class="border p-3 rounded max-h-40 overflow-auto mb-3">
                    <div v-for="e in employees" :key="e.id" class="flex items-center gap-2">
                        <input type="checkbox" :value="e.id" v-model="duplicateForm.executor_ids" />
                        <span>{{ e.name }}</span>
                    </div>
                </div>

                <label class="font-semibold text-sm">Ответственные</label>
                <div class="border p-3 rounded max-h-40 overflow-auto mb-3">
                    <div v-for="e in employees" :key="e.id" class="flex items-center gap-2">
                        <input type="checkbox" :value="e.id" v-model="duplicateForm.responsible_ids" />
                        <span>{{ e.name }}</span>
                    </div>
                </div>

                <label class="font-semibold text-sm">Наблюдатели</label>
                <div class="border p-3 rounded max-h-40 overflow-auto mb-3">
                    <div v-for="e in employees" :key="e.id" class="flex items-center gap-2">
                        <input type="checkbox" :value="e.id" v-model="duplicateForm.watcher_ids" />
                        <span>{{ e.name }}</span>
                    </div>
                </div>

                <input
                    type="number"
                    v-model="duplicateForm.due_in_days"
                    class="input mb-3"
                    placeholder="Срок (дней)"
                />

                <select v-model="duplicateForm.priority" class="input mb-3">
                    <option value="low">Низкий</option>
                    <option value="medium">Средний</option>
                    <option value="high">Высокий</option>
                </select>

                <!-- Файлы исходного шаблона -->
                <div v-if="activeTemplate.files?.length" class="mb-3">

                    <label class="flex items-center gap-2 mb-3">
                        <input
                            type="checkbox"
                            v-model="duplicateForm.copy_files"
                            class="w-4 h-4"
                        />
                        <span>Копировать файлы оригинального шаблона</span>
                    </label>

                    <div v-if="duplicateForm.copy_files && activeTemplate.files?.length">
                        <label class="font-semibold text-sm">Файлы оригинала:</label>

                        <ul class="ml-4 mt-1 space-y-1">
                            <li v-for="f in activeTemplate.files" :key="f.id">
                                <a :href="`/storage/${f.file_path}`"
                                   target="_blank"
                                   class="text-blue-600 underline">
                                    📎 {{ f.file_name }}
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Новые файлы -->
                <label class="font-semibold text-sm mt-3">Добавить свои файлы:</label>
                <input
                    type="file"
                    multiple
                    @change="e => duplicateForm.files = e.target.files"
                    class="input mb-3"
                />



                <div class="flex justify-end gap-2">
                    <button @click="showDuplicateModal = false" class="btn-gray">Отмена</button>
                    <button @click="saveDuplicateTemplate" class="btn-blue">Создать копию</button>
                </div>
            </div>
        </div>





    </AuthenticatedLayout>
</template>

<style>
.input {
    @apply w-full border border-gray-300 dark:border-gray-700 rounded-lg px-3 py-2 bg-white dark:bg-slate-800 text-sm mb-2;
}
.btn-blue {
    @apply bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg;
}
.btn-gray {
    @apply bg-gray-400 hover:bg-gray-500 text-white px-4 py-2 rounded-lg;
}
.modal {
    @apply fixed inset-0 bg-black/50 flex items-center justify-center z-50;
}
.modal-content {
    @apply bg-white dark:bg-slate-900 p-6 rounded-xl shadow-xl w-full;
}
</style>
