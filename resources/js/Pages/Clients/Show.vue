<script setup>
import { ref, onMounted, computed } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import axios from 'axios'

const { props } = usePage()
const clientId = props.id

const client = ref(null)
const activeTab = ref('profile') // profile | interactions | deals

// forms
const editForm = ref({ name:'', phone:'', email:'', notes:'' })
const interactionForm = ref({ type:'call', content:'', interaction_date: new Date().toISOString().slice(0,16) })
const showInteractionModal = ref(false)

const showDealModal = ref(false)
const dealForm = ref({ title:'', amount:'' })

const deals = ref([])

const fetchClient = async () => {
  const { data } = await axios.get(`/api/clients/${clientId}`)
  client.value = data
  // префил для редактирования
  editForm.value = {
    name: data.name || '',
    phone: data.phone || '',
    email: data.email || '',
    notes: data.notes || ''
  }
}

const fetchDeals = async () => {
  const { data } = await axios.get(`/api/clients/${clientId}/deals`)
  deals.value = data
}

const saveClient = async () => {
  await axios.put(`/api/clients/${clientId}`, editForm.value)
  await fetchClient()
}

const createInteraction = async () => {
  await axios.post(`/api/clients/${clientId}/interactions`, interactionForm.value)
  showInteractionModal.value = false
  interactionForm.value = { type:'call', content:'', interaction_date: new Date().toISOString().slice(0,16) }
  await fetchClient()
  activeTab.value = 'interactions'
}

const createDeal = async () => {
  await axios.post(`/api/clients/${clientId}/deals`, dealForm.value)
  showDealModal.value = false
  dealForm.value = { title:'', amount:'' }
  await fetchDeals()
  activeTab.value = 'deals'
}

const pipeline = computed(() => ({
  new: deals.value.filter(d => d.status === 'new'),
  in_progress: deals.value.filter(d => d.status === 'in_progress'),
  won: deals.value.filter(d => d.status === 'won'),
  lost: deals.value.filter(d => d.status === 'lost'),
}))

const moveDeal = async (deal, status) => {
  if (deal.status === status) return
  await axios.patch(`/api/deals/${deal.id}/status`, { status })
  await fetchDeals()
}

onMounted(async () => {
  await fetchClient()
  await fetchDeals()
})
</script>

<template>
  <Head :title="client?.name ? `Клиент — ${client.name}` : 'Клиент'" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">
          Клиент: {{ client?.name ?? '...' }}
        </h2>
        <div v-if="client?.company" class="text-sm text-gray-500 dark:text-gray-400">
          Компания: <span class="font-medium">{{ client.company.name }}</span>
        </div>
      </div>
    </template>

    <div class="max-w-7xl mx-auto p-6 space-y-6">
      <!-- Tabs -->
      <div class="flex gap-2 border-b dark:border-gray-700">
        <button
          class="px-4 py-2 -mb-px"
          :class="activeTab==='profile' ? 'border-b-2 border-indigo-600 text-indigo-600' : 'text-gray-600 dark:text-gray-400'"
          @click="activeTab='profile'">Профиль</button>
        <button
          class="px-4 py-2 -mb-px"
          :class="activeTab==='interactions' ? 'border-b-2 border-indigo-600 text-indigo-600' : 'text-gray-600 dark:text-gray-400'"
          @click="activeTab='interactions'">Взаимодействия</button>
        <button
          class="px-4 py-2 -mb-px"
          :class="activeTab==='deals' ? 'border-b-2 border-indigo-600 text-indigo-600' : 'text-gray-600 dark:text-gray-400'"
          @click="activeTab='deals'">Сделки</button>
      </div>

      <!-- PROFILE -->
      <div v-if="activeTab==='profile'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl shadow p-6">
          <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-100">Карточка клиента</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="text-sm text-gray-500 dark:text-gray-400">Имя</label>
              <input v-model="editForm.name" class="mt-1 w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-white" />
            </div>
            <div>
              <label class="text-sm text-gray-500 dark:text-gray-400">Телефон</label>
              <input v-model="editForm.phone" class="mt-1 w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-white" />
            </div>
            <div>
              <label class="text-sm text-gray-500 dark:text-gray-400">Email</label>
              <input v-model="editForm.email" type="email" class="mt-1 w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-white" />
            </div>
            <div class="md:col-span-2">
              <label class="text-sm text-gray-500 dark:text-gray-400">Заметки</label>
              <textarea v-model="editForm.notes" rows="5" class="mt-1 w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-white" />
            </div>
          </div>
          <div class="mt-4 flex justify-end">
            <button @click="saveClient" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
              Сохранить
            </button>
          </div>
        </div>

        <!-- Quick stats -->
        <div class="space-y-4">
          <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-5">
            <div class="text-sm text-gray-500 dark:text-gray-400">Взаимодействий</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ client?.interactions?.length ?? 0 }}</div>
          </div>
          <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-5">
            <div class="text-sm text-gray-500 dark:text-gray-400">Сделок</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ client?.deals?.length ?? 0 }}</div>
          </div>
        </div>
      </div>

      <!-- INTERACTIONS -->
      <div v-if="activeTab==='interactions'">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">История взаимодействий</h3>
          <button @click="showInteractionModal = true" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            + Добавить
          </button>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow divide-y dark:divide-gray-700">
          <div v-for="it in client?.interactions || []" :key="it.id" class="p-4 flex items-start gap-3">
            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700">
              <span class="text-xs uppercase">{{ it.type[0] }}</span>
            </span>
            <div class="flex-1">
              <div class="flex justify-between">
                <div class="font-medium text-gray-900 dark:text-white">
                  {{ it.user?.name || '—' }}
                  <span class="ml-2 text-xs px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700">{{ it.type }}</span>
                </div>
                <div class="text-xs text-gray-500">{{ new Date(it.interaction_date).toLocaleString() }}</div>
              </div>
              <div class="text-sm text-gray-700 dark:text-gray-300 mt-1 whitespace-pre-line">{{ it.content }}</div>
            </div>
          </div>

          <div v-if="(client?.interactions || []).length === 0" class="p-6 text-sm text-gray-500 dark:text-gray-400">
            Пока нет взаимодействий.
          </div>
        </div>
      </div>

      <!-- DEALS (PIPELINE) -->
      <div v-if="activeTab==='deals'">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Сделки</h3>
          <button @click="showDealModal = true" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
            + Новая сделка
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <PipelineCol title="Новые" :items="pipeline.new" color="border-sky-500">
            <template #item="{ item }">
              <DealCard :deal="item" @move="moveDeal(item, $event)" />
            </template>
          </PipelineCol>

          <PipelineCol title="В работе" :items="pipeline.in_progress" color="border-amber-500">
            <template #item="{ item }">
              <DealCard :deal="item" @move="moveDeal(item, $event)" />
            </template>
          </PipelineCol>

          <PipelineCol title="Успешные" :items="pipeline.won" color="border-emerald-500">
            <template #item="{ item }">
              <DealCard :deal="item" @move="moveDeal(item, $event)" />
            </template>
          </PipelineCol>

          <PipelineCol title="Проваленные" :items="pipeline.lost" color="border-rose-500">
            <template #item="{ item }">
              <DealCard :deal="item" @move="moveDeal(item, $event)" />
            </template>
          </PipelineCol>
        </div>
      </div>
    </div>

    <!-- Модалка: взаимодействие -->
    <div v-if="showInteractionModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 w-full max-w-lg">
        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Новое взаимодействие</h3>
        <form @submit.prevent="createInteraction" class="space-y-3">
          <div>
            <label class="text-sm text-gray-500 dark:text-gray-400">Тип</label>
            <select v-model="interactionForm.type" class="mt-1 w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-white">
              <option value="call">Звонок</option>
              <option value="meeting">Встреча</option>
              <option value="email">Письмо</option>
              <option value="comment">Комментарий</option>
            </select>
          </div>
          <div>
            <label class="text-sm text-gray-500 dark:text-gray-400">Дата/время</label>
            <input type="datetime-local" v-model="interactionForm.interaction_date" class="mt-1 w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-white"/>
          </div>
          <div>
            <label class="text-sm text-gray-500 dark:text-gray-400">Содержание</label>
            <textarea v-model="interactionForm.content" rows="5" class="mt-1 w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-white"/>
          </div>
          <div class="flex justify-end gap-2">
            <button type="button" @click="showInteractionModal=false" class="px-3 py-2 bg-gray-500 text-white rounded">Отмена</button>
            <button type="submit" class="px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Сохранить</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Модалка: сделка -->
    <div v-if="showDealModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Новая сделка</h3>
        <form @submit.prevent="createDeal" class="space-y-3">
          <div>
            <label class="text-sm text-gray-500 dark:text-gray-400">Название</label>
            <input v-model="dealForm.title" class="mt-1 w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-white" required/>
          </div>
          <div>
            <label class="text-sm text-gray-500 dark:text-gray-400">Сумма (₽)</label>
            <input type="number" min="0" step="0.01" v-model="dealForm.amount" class="mt-1 w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-white"/>
          </div>
          <div class="flex justify-end gap-2">
            <button type="button" @click="showDealModal=false" class="px-3 py-2 bg-gray-500 text-white rounded">Отмена</button>
            <button type="submit" class="px-3 py-2 bg-green-600 text-white rounded hover:bg-green-700">Создать</button>
          </div>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script>
// Лёгкие UI-компоненты внутри файла для колонок и карточек сделок
export default {
  components: {
    PipelineCol: {
      props: { title: String, items: Array, color: String },
      template: `
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-4 border-t-4" :class="color">
          <div class="flex items-center justify-between mb-3">
            <h4 class="font-semibold text-gray-900 dark:text-white">{{ title }}</h4>
            <span class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-0.5 rounded">{{ items.length }}</span>
          </div>
          <div class="space-y-3">
            <slot v-for="i in items" name="item" :item="i" :key="i.id"></slot>
          </div>
        </div>
      `
    },
    DealCard: {
      emits: ['move'],
      props: { deal: Object },
      template: `
        <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/40 border dark:border-gray-700">
          <div class="flex justify-between items-start">
            <div class="font-medium text-gray-900 dark:text-white">{{ deal.title }}</div>
            <div class="text-xs text-gray-500">#{{ deal.id }}</div>
          </div>
          <div class="text-sm text-gray-600 dark:text-gray-300 mt-1" v-if="deal.amount">≈ {{ Number(deal.amount).toLocaleString() }} ₽</div>
          <div class="mt-3 flex flex-wrap gap-1">
            <button class="text-xs px-2 py-1 bg-sky-600 text-white rounded" @click="$emit('move','new')">Новый</button>
            <button class="text-xs px-2 py-1 bg-amber-600 text-white rounded" @click="$emit('move','in_progress')">В работе</button>
            <button class="text-xs px-2 py-1 bg-emerald-600 text-white rounded" @click="$emit('move','won')">Успешно</button>
            <button class="text-xs px-2 py-1 bg-rose-600 text-white rounded" @click="$emit('move','lost')">Провал</button>
          </div>
        </div>
      `
    }
  }
}
</script>
