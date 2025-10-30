<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { VueFlow, useVueFlow } from '@vue-flow/core'
import { Controls } from '@vue-flow/controls'
import dagre from 'dagre'

import '@vue-flow/core/dist/style.css'
import '@vue-flow/controls/dist/style.css'

// ==== реактивные переменные ====
const companies = ref([])
const selectedCompany = ref(null)
const elements = ref([])
const loading = ref(false)
const modalOpen = ref(false)
const selectedItem = ref(null)
const { fitView } = useVueFlow()

// ==== форматирование прогресса ====
const formatProgress = (value) => {
  if (value == null || isNaN(value)) return '0%'
  const num = Number(value)
  return `${num.toFixed(0)}%`
}

// ==== получаем компании ====
const fetchCompanies = async () => {
  loading.value = true
  const { data } = await axios.get('/api/mapdiagram')
  companies.value = data
  loading.value = false
}

// ==== получаем карту ====
const fetchMap = async (companyId) => {
  loading.value = true
  const { data } = await axios.get(`/api/mapdiagram/${companyId}/map`)
  selectedCompany.value = data
  buildGraph(data)
  loading.value = false
}

// ==== обработка клика по узлу ====
const onNodeClick = async (eventOrNode) => {
  const node = eventOrNode.node || eventOrNode
  if (!node || !node.id) return

  const id = node.id
  let type = null
  let label = null

  if (id.startsWith('p-')) {
    type = 'project'
    label = 'Проект'
  } else if (id.startsWith('t-')) {
    type = 'task'
    label = 'Задача'
  } else if (id.startsWith('s-')) {
    type = 'subtask'
    label = 'Подзадача'
  } else if (id.startsWith('c-')) {
    type = 'company'
    label = 'Компания'
  } else {
    return
  }

  try {
    const { data } = await axios.get(`/api/${type}s/${id.replace(/\D/g, '')}`)
    selectedItem.value = { type, label, ...data } // добавляем label (русское)
    modalOpen.value = true
  } catch (error) {
    console.error('Ошибка при получении данных:', error)
  }
}


// ==== авто-раскладка ====
const getLayoutedElements = (nodes, edges) => {
  const g = new dagre.graphlib.Graph()
  g.setGraph({ rankdir: 'TB', nodesep: 100, ranksep: 120 })
  g.setDefaultEdgeLabel(() => ({}))
  nodes.forEach((n) => g.setNode(n.id, { width: 200, height: 60 }))
  edges.forEach((e) => g.setEdge(e.source, e.target))
  dagre.layout(g)
  nodes.forEach((n) => {
    const pos = g.node(n.id)
    n.position = { x: pos.x - 100, y: pos.y - 30 }
  })
  return { nodes, edges }
}

// ==== построение дерева ====
const buildGraph = (company) => {
  let nodes = []
  let edges = []

  const colors = {
    company: '#dbeafe',
    project: '#dcfce7',
    task: '#fef9c3',
    subtask: '#ffedd5',
  }

  const edgeColors = ['#f59e0b', '#f97316', '#ef4444', '#8b5cf6', '#0ea5e9']
  const getEdgeColor = (depth) => edgeColors[depth] || '#6b7280'

  const addNode = (id, label, type, parentId = null, depth = 0) => {
    nodes.push({
      id: id.toString(),
      data: { label },
      style: {
        background: colors[type],
        borderRadius: '10px',
        padding: '10px 16px',
        border: '1px solid rgba(0,0,0,0.05)',
        boxShadow: '0 3px 8px rgba(0,0,0,0.08)',
        fontWeight: 500,
        fontSize: '14px',
        textAlign: 'center',
        maxWidth: '240px',
        overflow: 'hidden',
        textOverflow: 'ellipsis',
        whiteSpace: 'nowrap',
        cursor: 'pointer',
      },
      title: label,
    })

    if (parentId) {
      edges.push({
        id: `e-${parentId}-${id}`,
        source: parentId.toString(),
        target: id.toString(),
        type: 'smoothstep',
        animated: false,
        style: {
          stroke: getEdgeColor(depth),
          strokeWidth: 2,
          opacity: 0.9,
        },
      })
    }
  }

  const renderSubtree = (sub, parentId, depth = 0) => {
    addNode(`s-${sub.id}`, sub.title, 'subtask', parentId, depth)
    if (sub.children && sub.children.length) {
      sub.children.forEach((child) =>
        renderSubtree(child, `s-${sub.id}`, depth + 1)
      )
    }
  }

  const traverse = (project, parentId) => {
    addNode(`p-${project.id}`, project.name, 'project', parentId)
    project.tasks?.forEach((task) => {
      addNode(`t-${task.id}`, task.title, 'task', `p-${project.id}`)
      task.subtasks?.forEach((sub) => renderSubtree(sub, `t-${task.id}`, 0))
    })
  }

  addNode(`c-${company.id}`, company.name, 'company')
  company.projects.forEach((p) => traverse(p, `c-${company.id}`))

  const layouted = getLayoutedElements(nodes, edges)
  elements.value = [...layouted.nodes, ...layouted.edges]
  setTimeout(() => fitView(), 300)
}

onMounted(fetchCompanies)
</script>

<template>
  <AuthenticatedLayout>
    <div class="flex h-screen overflow-hidden bg-gray-50 text-gray-900">
      <!-- Левая панель -->
      <aside class="w-72 border-r border-gray-200 bg-white shadow-sm flex flex-col">
        <div class="p-4 border-b border-gray-100">
          <h2 class="font-semibold text-lg mb-3 text-gray-800 flex items-center gap-2">
            🏢 Мои компании
          </h2>
        </div>

        <div class="flex-1 overflow-y-auto p-2">
          <div v-if="loading" class="text-center text-gray-400 mt-6">Загрузка...</div>
          <ul v-else>
            <li
              v-for="c in companies"
              :key="c.id"
              class="mb-1 p-2 rounded-lg cursor-pointer hover:bg-blue-50 transition"
              :class="selectedCompany?.id === c.id ? 'bg-blue-100 font-medium' : ''"
              @click="fetchMap(c.id)"
            >
              🏙️ {{ c.name }}
            </li>
          </ul>
        </div>
      </aside>

      <!-- Правая часть -->
      <main class="flex-1 relative flex flex-col">
        <div class="flex items-center justify-between px-6 py-3 bg-white border-b border-gray-200 shadow-sm">
          <h3 class="font-semibold text-lg text-gray-700">
            {{ selectedCompany ? selectedCompany.name : 'Выберите компанию' }}
          </h3>
          <button
            @click="fitView"
            class="px-3 py-1.5 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700 transition"
          >
            🔍 приблизить
          </button>
        </div>

        <div class="flex-1 relative">
          <VueFlow
            v-model="elements"
            class="w-full h-full bg-gradient-to-b from-gray-50 to-gray-100"
            @node-click="onNodeClick"
          >
            <Controls />
          </VueFlow>
        </div>
      </main>
    </div>

    <!-- Модалка -->
    <div v-if="modalOpen" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
      <div class="bg-white rounded-xl shadow-xl w-[500px] max-h-[90vh] overflow-y-auto p-6 relative animate-scaleIn">
        <button class="absolute top-3 right-3 text-gray-400 hover:text-gray-600" @click="modalOpen = false">✕</button>

        <h2 class="text-xl font-semibold mb-4">{{ selectedItem.label }}</h2>


        <div v-if="selectedItem">
          <!-- Проект -->
          <template v-if="selectedItem.type === 'project'">
            <p><b>Название:</b> {{ selectedItem.name }}</p>
            <p><b>Дата начала:</b> {{ selectedItem.start_date }}</p>
            
          </template>

          <!-- Задача -->
          <template v-else-if="selectedItem.type === 'task'">
            <p><b>Название:</b> {{ selectedItem.title }}</p>
            
           <p>
  <b>Исполнители:</b>
  <span v-for="(e, i) in selectedItem.executors" :key="e.id">
    {{ e.name }}<span v-if="i < selectedItem.executors.length - 1">, </span>
  </span>
</p>

<p>
  <b>Ответственные:</b>
  <span v-for="(r, i) in selectedItem.responsibles" :key="r.id">
    {{ r.name }}<span v-if="i < selectedItem.responsibles.length - 1">, </span>
  </span>
</p>


            <!-- Прогресс -->
            <div class="mt-2">
              <b>Прогресс:</b>
              <div class="w-full bg-gray-200 rounded-full h-3 mt-1 relative overflow-hidden">
                <div
                  class="h-3 rounded-full transition-all duration-500"
                  :style="{
                    width: formatProgress(selectedItem.progress),
                    background:
                      selectedItem.progress >= 100
                        ? 'linear-gradient(90deg, #16a34a, #4ade80)'
                        : 'linear-gradient(90deg, #3b82f6, #60a5fa)',
                  }"
                ></div>
              </div>
              <div class="text-sm text-gray-500 mt-1">{{ formatProgress(selectedItem.progress) }}</div>
            </div>
          </template>

          <!-- Подзадачи и дочерние -->
          <template v-else>
            <p><b>Название:</b> {{ selectedItem.title }}</p>
            <p>
  <b>Исполнители:</b>
  <span v-for="(e, i) in selectedItem.executors" :key="e.id">
    {{ e.name }}<span v-if="i < selectedItem.executors.length - 1">, </span>
  </span>
</p>

<p>
  <b>Ответственные:</b>
  <span v-for="(r, i) in selectedItem.responsibles" :key="r.id">
    {{ r.name }}<span v-if="i < selectedItem.responsibles.length - 1">, </span>
  </span>
</p>


            <div class="mt-2">
              <b>Прогресс:</b>
              <div class="w-full bg-gray-200 rounded-full h-3 mt-1 relative overflow-hidden">
                <div
                  class="h-3 rounded-full transition-all duration-500"
                  :style="{
                    width: formatProgress(selectedItem.progress),
                    background:
                      selectedItem.progress >= 100
                        ? 'linear-gradient(90deg, #16a34a, #4ade80)'
                        : 'linear-gradient(90deg, #3b82f6, #60a5fa)',
                  }"
                ></div>
              </div>
              <div class="text-sm text-gray-500 mt-1">{{ formatProgress(selectedItem.progress) }}</div>
            </div>
          </template>
        </div>

        <div class="mt-6 text-right">
          <a
            :href="`/${selectedItem?.type}s/${selectedItem?.id}`"
            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
          >
            Перейти →
          </a>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
.vue-flow__node:hover {
  transform: scale(1.03);
  box-shadow: 0 6px 14px rgba(0, 0, 0, 0.15);
  transition: 0.15s ease;
}

/* Анимация появления модалки */
@keyframes scaleIn {
  from {
    opacity: 0;
    transform: scale(0.9);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}
.animate-scaleIn {
  animation: scaleIn 0.25s ease forwards;
}
</style>
