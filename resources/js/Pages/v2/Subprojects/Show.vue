<template>
  <div class="p-6">
    <!-- Инфо о подпроекте -->
    <h1 class="text-2xl font-bold mb-2">{{ subproject.title }}</h1>
    <p class="text-gray-600 mb-4">
      Ответственный: {{ subproject.responsible?.name ?? 'не назначен' }}
    </p>

    <!-- Форма добавления задачи -->
    <div class="bg-white shadow rounded p-4 mb-6">
      <h2 class="text-lg font-semibold mb-3">Добавить задачу</h2>
      <form @submit.prevent="createTask" class="space-y-3">
        <div>
          <input
            v-model="taskForm.title"
            type="text"
            placeholder="Название задачи"
            class="border p-2 rounded w-full"
            required
          />
        </div>

        <div>
          <label class="block text-sm font-medium">Приоритет</label>
          <select v-model="taskForm.priority" class="border p-2 rounded w-full">
            <option value="low">Низкий</option>
            <option value="medium">Средний</option>
            <option value="high">Высокий</option>
          </select>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium">Дата начала</label>
            <input
              v-model="taskForm.start_date"
              type="date"
              class="border p-2 rounded w-full"
              required
            />
          </div>
          <div>
            <label class="block text-sm font-medium">Дедлайн</label>
            <input
              v-model="taskForm.due_date"
              type="date"
              class="border p-2 rounded w-full"
              required
            />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium">Исполнитель</label>
            <select v-model="taskForm.executor_id" class="border p-2 rounded w-full" required>
              <option v-for="user in users" :key="user.id" :value="user.id">
                {{ user.name }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium">Ответственный</label>
            <select v-model="taskForm.responsible_id" class="border p-2 rounded w-full" required>
              <option v-for="user in users" :key="user.id" :value="user.id">
                {{ user.name }}
              </option>
            </select>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium">Файлы</label>
          <input type="file" multiple @change="handleFiles" />
        </div>

        <button
          type="submit"
          class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
        >
          Создать задачу
        </button>
      </form>
    </div>

    <!-- Список задач -->
    <div>
      <h2 class="text-lg font-semibold mb-3">Задачи подпроекта</h2>
      <ul v-if="subproject.tasks.length" class="space-y-2">
        <li
          v-for="task in subproject.tasks"
          :key="task.id"
          class="border rounded p-3 bg-white shadow"
        >
          <h3 class="font-medium">{{ task.title }}</h3>
          <p class="text-sm text-gray-500">
            Исполнитель: {{ task.executor?.name }},
            Ответственный: {{ task.responsible?.name }}
          </p>
          <p class="text-sm">Приоритет: {{ task.priority }}</p>
          <p class="text-sm">
            {{ task.start_date }} → {{ task.due_date }}
          </p>
        </li>
      </ul>
      <p v-else class="text-gray-500">Задач пока нет</p>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
  subproject: Object,
  users: Array, 
});

const taskForm = ref({
  title: "",
  priority: "low",
  start_date: "",
  due_date: "",
  executor_id: null,
  responsible_id: null,
  files: [],
});

const handleFiles = (e) => {
  taskForm.value.files = Array.from(e.target.files);
};

const createTask = () => {
  const formData = new FormData();
  for (const key in taskForm.value) {
    if (key === "files") {
      taskForm.value.files.forEach((file) => formData.append("files[]", file));
    } else {
      formData.append(key, taskForm.value[key] ?? "");
    }
  }

  router.post(`/api/subprojects/${props.subproject.id}/tasks`, formData, {
    forceFormData: true,
    onSuccess: () => {
      taskForm.value = {
        title: "",
        priority: "low",
        start_date: "",
        due_date: "",
        executor_id: null,
        responsible_id: null,
        files: [],
      };
    },
  });
};
</script>
