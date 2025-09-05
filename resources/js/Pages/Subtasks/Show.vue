<script setup>
import { ref, onMounted, computed } from 'vue'
import { usePage, Head } from '@inertiajs/vue3'
import axios from 'axios'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const { props } = usePage()
const subtaskId = props.id
const subtask = ref(null)
const user = props.auth?.user

const fetchSubtask = async () => {
  const { data } = await axios.get(`/api/subtasks/${subtaskId}`)
  subtask.value = data
}

const canUpdateProgress = computed(() => {
  if (!subtask.value || !user) return false
  return user.id === subtask.value.executor_id || user.id === subtask.value.creator_id
})

const canComplete = computed(() => {
  if (!subtask.value) return false
  return canUpdateProgress.value && subtask.value.progress === 100 && !subtask.value.completed
})

const updateProgress = async (value) => {
  if (!canUpdateProgress.value) return
  const { data } = await axios.patch(`/api/subtasks/${subtaskId}/progress`, { progress: value })
  subtask.value.progress = data.progress
}

const completeSubtask = async () => {
  if (!canComplete.value) return
  if (!confirm('Завершить подзадачу?')) return
  const { data } = await axios.patch(`/api/subtasks/${subtaskId}/complete`)
  subtask.value.completed = data.completed
  subtask.value.completed_at = data.completed_at
}

onMounted(fetchSubtask)
</script>

<template>
  <Head title="Подзадача" />
  <AuthenticatedLayout>
    <template #header>
         <div class="flex items-center justify-between gap-3">
        <h2 class="text-xl font-bold text-gray-800 dark:text-gray-200">
          Подзадача: {{ subtask?.title ?? 'Загрузка...' }}
        </h2>

        <div v-if="subtask" class="flex items-center gap-2">
          <span
            class="inline-flex items-center gap-2 text-sm px-3 py-1 rounded-full ring-1 ring-gray-200 dark:ring-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300">
            <span class="w-2 h-2 rounded-full"
                  :class="{
                    'bg-red-500': (subtask.progress ?? 0) < 30,
                    'bg-amber-500': (subtask.progress ?? 0) >= 30 && (subtask.progress ?? 0) < 70,
                    'bg-green-500': (subtask.progress ?? 0) >= 70
                  }"/>
            {{ subtask.progress ?? 0 }}%
          </span>

          <!-- Кнопка завершить -->
          <button
            v-if="canComplete"
            @click="completeSubtask"
            class="px-3 py-1.5 rounded-md bg-emerald-600 text-white text-sm hover:bg-emerald-700"
          >
            Завершить
          </button>

          <span v-else-if="subtask.completed"
                class="px-3 py-1.5 rounded-md bg-emerald-100 text-emerald-700 text-sm dark:bg-emerald-900/30 dark:text-emerald-300">
            Завершена • {{ subtask.completed_at?.slice(0, 16) ?? '' }}
          </span>
        </div>
      </div>
    </template>

    <div class="max-w-4xl mx-auto py-8 px-4">
      <div v-if="subtask" class="grid gap-6">
        <!-- карточка с краткой инфой -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-6">
          <div class="grid sm:grid-cols-2 gap-4 text-sm">
            <div>
              <p class="text-gray-500 dark:text-gray-400">Автор</p>
              <p class="font-medium text-gray-900 dark:text-white">{{ subtask.creator?.name }}</p>
            </div>
            <div>
              <p class="text-gray-500 dark:text-gray-400">Исполнитель</p>
              <p class="font-medium text-gray-900 dark:text-white">{{ subtask.executor?.name }}</p>
            </div>
            <div>
              <p class="text-gray-500 dark:text-gray-400">Дата начала</p>
              <p class="font-medium text-gray-900 dark:text-white">{{ subtask.start_date }}</p>
            </div>
            <div>
              <p class="text-gray-500 dark:text-gray-400">Дата окончания</p>
              <p class="font-medium text-gray-900 dark:text-white">{{ subtask.due_date }}</p>
            </div>
          </div>

          <div class="mt-6 grid sm:grid-cols-3 gap-4">
            <div>
              <p class="text-gray-500 dark:text-gray-400 text-sm">Задача</p>
              <p class="font-medium text-gray-900 dark:text-white">
                {{ subtask.task?.title }}
              </p>
            </div>
            <div>
              <p class="text-gray-500 dark:text-gray-400 text-sm">Проект</p>
              <p class="font-medium text-gray-900 dark:text-white">
                {{ subtask.task?.project?.name }}
              </p>
            </div>
            <div>
              <p class="text-gray-500 dark:text-gray-400 text-sm">Компания</p>
              <p class="font-medium text-gray-900 dark:text-white">
                {{ subtask.task?.project?.company?.name }}
              </p>
            </div>
          </div>
        </div>

        <!-- Прогресс -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-6">
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Прогресс подзадачи</h3>
            <span class="text-sm text-gray-500 dark:text-gray-400">Выполнено {{ subtask.progress ?? 0 }}%</span>
          </div>

          <!-- «кирпичики» 10х10% -->
          <div class="flex mt-2 space-x-1 select-none">
            <div
              v-for="n in 10"
              :key="n"
              :title="(n*10) + '%'"
              @click="canUpdateProgress ? updateProgress(n*10) : null"
              class="h-4 sm:h-5 flex-1 rounded transition"
              :class="{
                'cursor-pointer hover:opacity-80': canUpdateProgress,
                'bg-green-600': (subtask.progress ?? 0) >= n * 10,
                'bg-gray-200 dark:bg-gray-700': (subtask.progress ?? 0) < n * 10,
                'pointer-events-none opacity-60': subtask?.completed
              }"
            />
          </div>

          <p v-if="!canUpdateProgress" class="text-xs text-gray-500 dark:text-gray-400 mt-2">
            Изменять прогресс могут только исполнитель и автор подзадачи.
          </p>
        </div>
      </div>

      <div v-else class="text-gray-600 dark:text-gray-300">Загрузка...</div>
    </div>
  </AuthenticatedLayout>
</template>
