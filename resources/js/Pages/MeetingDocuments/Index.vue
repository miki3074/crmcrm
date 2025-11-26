<script setup>
import { ref, onMounted, watch } from 'vue'
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import axios from 'axios'

import RichEditor from '@/Components/RichEditor.vue'

// -------------------------------
// State
// -------------------------------
const documents = ref([])
const loading = ref(true)

const tasks = ref([])
const subtasks = ref([])

// Модалки
const showCreate = ref(false)
const showEdit = ref(false)
const showView = ref(false)

// Выбранный документ
const selected = ref(null)

// -------------------------------
// Формы
// -------------------------------
const form = ref({
  type: 'agenda',
  title: '',
  body: '',
   link_type: 'none', // <-- NEW
  task_id: null,
  subtask_id: null,
})

const editForm = ref({
  id: null,
  title: '',
  body: '',
   link_type: 'none',
  task_id: null,
  subtask_id: null,
})

// -------------------------------
// API
// -------------------------------
const loadDocuments = async () => {
  const { data } = await axios.get('/api/meeting-documents', {
    params: filters.value
  });

  documents.value = data;
  loading.value = false;
};


const loadTasks = async () => {
  const { data } = await axios.get('/api/tasks-with-subtasks')
  tasks.value = data.tasks
  subtasks.value = data.subtasks
}

// -------------------------------
// Создание документа
// -------------------------------
const createDoc = async () => {
  const payload = { ...form.value }

  // Привязка "НЕ привязывать"
  if (payload.link_type === 'none') {
    payload.task_id = null
    payload.subtask_id = null
  }

  // Привязка к задаче
  if (payload.link_type === 'task') {
    if (!payload.task_id) {
      alert("Выберите задачу");
      return;
    }

    payload.subtask_id = null
  }

  // Привязка к подзадаче
  if (payload.link_type === 'subtask') {

    if (!payload.task_id) {
      alert("Сначала выберите задачу!");
      return;
    }

    if (!payload.subtask_id) {
      alert("Выберите подзадачу");
      return;
    }

    // Самое важное — НЕ затирать subtask_id
    // Обнуляем только task_id
    payload.task_id = null
  }

  // Пустые строки превращаем в null, иначе backend думает, что ты передал 0
  if (payload.task_id === "") payload.task_id = null
  if (payload.subtask_id === "") payload.subtask_id = null

  console.log("Отправка payload:", payload)

  await axios.post('/api/meeting-documents', payload)

  // Сброс формы
  form.value = {
    type: 'agenda',
    title: '',
    body: '',
    link_type: 'none',
    task_id: null,
    subtask_id: null,
  }

  showCreate.value = false
  await loadDocuments()
}



// -------------------------------
// Просмотр документа
// -------------------------------
const openView = (doc) => {
  selected.value = doc
  showView.value = true
}

// -------------------------------
// Редактирование документа
// -------------------------------
const openEdit = (doc) => {
  selected.value = doc
 editForm.value = {
  id: doc.id,
  title: doc.title,
  body: doc.body,
  link_type: doc.subtask_id ? 'subtask' : (doc.task_id ? 'task' : 'none'),
  task_id: doc.task_id,
  subtask_id: doc.subtask_id,
}
  showEdit.value = true
}

const updateDoc = async () => {
  const payload = { ...editForm.value }

  if (payload.link_type === 'none') {
    payload.task_id = null
    payload.subtask_id = null
  }

  if (payload.link_type === 'task') {
    payload.subtask_id = null
  }

  if (payload.link_type === 'subtask') {
    if (!payload.subtask_id) {
      alert("Выберите подзадачу")
      return
    }
    payload.task_id = null
  }

  if (payload.task_id === "") payload.task_id = null
  if (payload.subtask_id === "") payload.subtask_id = null

  await axios.put(`/api/meeting-documents/${editForm.value.id}`, payload)
}



// -------------------------------
// Удаление
// -------------------------------
const deleteDoc = async (id) => {
  if (!confirm('Удалить документ?')) return
  await axios.delete(`/api/meeting-documents/${id}`)
  await loadDocuments()
}

// -------------------------------
// Автосброс подзадачи если выбрана другая задача
// -------------------------------
// watch(() => form.value.task_id, () => {
//   form.value.subtask_id = null
// })

// watch(() => editForm.value.task_id, () => {
//   editForm.value.subtask_id = null
// })

watch(() => form.value.link_type, () => {
  form.value.task_id = null
  form.value.subtask_id = null
})

watch(() => editForm.value.link_type, () => {
  editForm.value.task_id = null
  editForm.value.subtask_id = null
})

const filters = ref({
  filter: 'all',       // my / others / all
  search: '',
  date_from: '',
  date_to: '',
})

const applyFilters = async () => {
  await loadDocuments()
}

const resetFilters = async () => {
  filters.value = {
    filter: 'all',
    search: '',
    date_from: '',
    date_to: '',
  }
  await loadDocuments()
}

const downloadPdf = async (id) => {
  const url = `/api/meeting-documents/${id}/pdf`;

  // Прямая загрузка файла
  window.open(url, '_blank');
};




onMounted(async () => {
  await loadDocuments()
  await loadTasks()
})
</script>

<template>
  <AuthenticatedLayout>
    <Head title="Документы встреч" />

    <template #header>
      <h2 class="font-semibold text-2xl text-slate-800 dark:text-slate-100">
        📄 Документы встреч
      </h2>
    </template>

    <div class="max-w-6xl mx-auto p-6">
      <button
        class="bg-blue-600 text-white px-4 py-2 rounded-lg shadow hover:bg-blue-700"
        @click="showCreate = true"
      >
        + Создать документ
      </button>

      <div class="mt-4 p-4 bg-white dark:bg-slate-900 border rounded-xl shadow space-y-4">

  <!-- Фильтр "Мой / Чужой / Все" -->
  <div class="flex gap-4">
    <select v-model="filters.filter" class="input w-48">
      <option value="all">Все документы</option>
      <option value="my">Мои документы</option>
      <option value="others">Чужие документы</option>
    </select>

    <!-- Поиск -->
    <input
      v-model="filters.search"
      placeholder="Поиск по названию..."
      class="input w-80"
    />
  </div>

  <!-- Дата от / до -->
  <div class="flex gap-4">
    <input type="date" v-model="filters.date_from" class="input w-48" /> 
    <p class="mt-2 "> по</p>
    <input type="date" v-model="filters.date_to" class="input w-48" />

    <button class="btn-blue" @click="applyFilters">Применить</button>
    <button class="btn-gray" @click="resetFilters">Сбросить</button>
  </div>

</div>


      <!-- Список документов -->
      <div class="mt-6">
        <div v-if="loading" class="text-slate-500">Загрузка...</div>

        <div
          v-for="doc in documents"
          :key="doc.id"
          class="p-4 bg-white dark:bg-slate-900 border rounded-xl shadow mt-4"
        >
          <div class="flex justify-between items-center">
            <div>
              <div class="font-semibold">
                {{ doc.type === 'agenda' ? 'Повестка дня' : 'Протокол' }} №{{ doc.number }}
              </div>
              <div class="text-sm text-slate-500">от {{ doc.document_date }}</div>
              <div class="text-sm mt-1">Автор: {{ doc.creator?.name }}</div>

              <div class="text-sm mt-1 text-slate-500" v-if="doc.task">
                📌 Задача: {{ doc.task.title }}
              </div>

              <div class="text-sm mt-1 text-slate-500" v-if="doc.subtask">
                ↳ Подзадача: {{ doc.subtask.title }}
              </div>
            </div>

            <div class="flex gap-2">
              <button class="px-3 py-1 bg-blue-500 text-white rounded" @click="openView(doc)">
                Просмотр
              </button>
              <!-- Только если документ мой -->
<button
  v-if="doc.created_by === $page.props.auth.user.id"
  class="px-3 py-1 bg-amber-500 text-white rounded"
  @click="openEdit(doc)"
>
  Редактировать
</button>

<button
  v-if="doc.created_by === $page.props.auth.user.id"
  class="px-3 py-1 bg-red-600 text-white rounded"
  @click="deleteDoc(doc.id)"
>
  Удалить
</button>

            </div>
          </div>

          <p class="mt-3 text-slate-700 dark:text-slate-300 line-clamp-3">
            {{ doc.title }}
          </p>
        </div>
      </div>
    </div>

    <!-- ==========================================
      FULLSCREEN — СОЗДАНИЕ
    =========================================== -->
    <div v-if="showCreate" class="fullscreen-modal">
      <div class="fullscreen-content">

        <div class="flex justify-between items-center pb-4 border-b">
          <h2 class="text-2xl font-bold">Создать документ</h2>
          <button class="close-btn" @click="showCreate = false">✕</button>
        </div>

        <div class="mt-6 space-y-4">

          <select v-model="form.type" class="input">
            <option value="agenda">Повестка</option>
            <option value="protocol">Протокол</option>
          </select>

          <input v-model="form.title" placeholder="Название" class="input" />

         <div class="space-y-2">
  <label class="flex gap-2 items-center">
    <input type="radio" value="none" v-model="form.link_type">
    <span>Не привязывать</span>
  </label>

  <label class="flex gap-2 items-center">
    <input type="radio" value="task" v-model="form.link_type">
    <span>Привязать к задаче</span>
  </label>

  <label class="flex gap-2 items-center">
    <input type="radio" value="subtask" v-model="form.link_type">
    <span>Привязать к подзадаче</span>
  </label>
</div>

<select
  v-if="form.link_type === 'task' || form.link_type === 'subtask'"
  v-model="form.task_id"
  class="input mt-4"
>
  <option value="">Выберите задачу</option>

  <option v-for="t in tasks" :value="t.id" :key="t.id">
    {{ t.title }}
  </option>
</select>

<select
  v-if="form.link_type === 'subtask' && subtasks[form.task_id]"
  v-model="form.subtask_id"
  class="input"
>
  <option value="">Выберите подзадачу</option>

  <option v-for="s in subtasks[form.task_id]" :key="s.id" :value="s.id">
    {{ s.title }}
  </option>
</select>



          <RichEditor v-model="form.body" />

          <div class="flex justify-end gap-2 pt-4">
            <button class="btn-gray" @click="showCreate = false">Отмена</button>
            <button class="btn-blue" @click="createDoc">Создать</button>
          </div>
        </div>

      </div>
    </div>


    <!-- ==========================================
      FULLSCREEN — ПРОСМОТР
    =========================================== -->
    <div v-if="showView" class="fullscreen-modal">
      <div class="fullscreen-content">
        <div class="flex justify-between items-center pb-4 border-b">
          <h2 class="text-2xl font-bold">
            {{ selected.type === 'agenda' ? 'Повестка' : 'Протокол' }}
            №{{ selected.number }} от {{ selected.document_date }} <br/>
            Название: {{ selected.title }}
          </h2>
 <!-- <button
  class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700"
  @click="downloadPdf(selected.id)"
>
  Скачать PDF
</button> -->
          <button class="close-btn" @click="showView = false">✕</button>
         
        </div>

        <div class="mt-6">

          <!-- <h3 class="text-xl font-semibold mb-4">{{ selected.title }}</h3> -->

          <div class="ql-editor text-lg leading-relaxed" v-html="selected.body"></div>

        </div>
      </div>
    </div>

    <!-- ==========================================
      FULLSCREEN — РЕДАКТИРОВАНИЕ
    =========================================== -->
    <div v-if="showEdit" class="fullscreen-modal">
      <div class="fullscreen-content">
        <div class="flex justify-between items-center pb-4 border-b">
          <h2 class="text-2xl font-bold">Редактировать документ</h2>
          <button class="close-btn" @click="showEdit = false">✕</button>
        </div>

        <div class="mt-6 space-y-4">

          <input v-model="editForm.title" class="input" />

          <select v-model="editForm.task_id" class="input">
            <option value="">Не привязывать к задаче</option>
            <option v-for="t in tasks" :key="t.id" :value="t.id">
              {{ t.title }}
            </option>
          </select>

          <select v-if="subtasks[editForm.task_id]" v-model="editForm.subtask_id" class="input">
            <option value="">Не привязывать к подзадаче</option>
            <option
              v-for="s in subtasks[editForm.task_id]"
              :key="s.id"
              :value="s.id"
            >
              {{ s.title }}
            </option>
          </select>

          <RichEditor v-model="editForm.body" />

          <div class="flex justify-end gap-2 pt-4">
            <button class="btn-gray" @click="showEdit = false">Отмена</button>
            <button class="btn-blue" @click="updateDoc">Сохранить</button>
          </div>

        </div>
      </div>
    </div>

  </AuthenticatedLayout>
</template>

<style scoped> .modal { @apply fixed inset-0 bg-black/50 flex items-center justify-center z-50; } .modal-content { @apply bg-white dark:bg-slate-900 p-6 rounded-2xl shadow-xl w-full max-w-lg; } .input { @apply w-full border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 bg-white dark:bg-slate-800; } .btn-blue { @apply bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700; } .btn-gray { @apply bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600; } .fullscreen-modal { position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 1000; display: flex; } .fullscreen-content { background: white; width: 100%; height: 100%; padding: 30px; overflow-y: auto; } .dark .fullscreen-content { background: #0f172a; /* bg-slate-900 */ } .close-btn { @apply text-slate-600 dark:text-slate-300 text-2xl hover:text-red-500 cursor-pointer; } .input { @apply w-full border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 bg-white dark:bg-slate-800; } .btn-blue { @apply bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700; } .btn-gray { @apply bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600; } </style>