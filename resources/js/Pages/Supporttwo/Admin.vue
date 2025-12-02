<script setup>
import { ref, onMounted } from "vue";
import { Head } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import axios from "axios";

const threads = ref([]);
const activeThread = ref(null);
const messages = ref([]);

const filters = ref({
  status: "all",
  search: "",
});

const newMessage = ref("");
const sending = ref(false);
const files = ref([]);

// загрузка списка тикетов
const loadThreads = async () => {
  const { data } = await axios.get("/api/support/admin/threads", {
    params: filters.value,
  });
  threads.value = data;
};

// открыть тикет
const openThread = async (thread) => {
  activeThread.value = thread;

  const { data } = await axios.get(`/api/support/admin/threads/${thread.id}`);
  messages.value = data.messages;
};

// отправка сообщения саппорта
const sendMessage = async () => {
  if (!newMessage.value.trim() && !files.value.length) return;

  const fd = new FormData();
  fd.append("message", newMessage.value);
  files.value.forEach((f, i) => fd.append(`files[${i}]`, f));

  sending.value = true;
  const { data } = await axios.post(
    `/api/support/admin/threads/${activeThread.value.id}/messages`,
    fd,
    { headers: { "Content-Type": "multipart/form-data" } }
  );
  sending.value = false;

  messages.value.push(data);
  newMessage.value = "";
  files.value = [];
};

// выбрать файлы
const fileChange = (e) => {
  files.value.push(...Array.from(e.target.files));
  e.target.value = "";
};

// закрыть тикет
const closeThread = async () => {
  await axios.post(`/api/support/admin/threads/${activeThread.value.id}/close`);
  activeThread.value.status = "closed";
  await loadThreads();
};

// открыть тикет
const reopenThread = async () => {
  await axios.post(`/api/support/admin/threads/${activeThread.value.id}/reopen`);
  activeThread.value.status = "open";
  await loadThreads();
};

onMounted(loadThreads);
</script>

<template>
  <AuthenticatedLayout>
    <Head title="Панель техподдержки" />

    <template #header>
      <h2 class="text-2xl font-semibold">🛠 Панель техподдержки</h2>
    </template>

    <div class="max-w-7xl mx-auto p-6 grid grid-cols-[260px,1fr] gap-4">

      <!-- ЛЕВАЯ КОЛОНКА -->
      <div class="bg-white dark:bg-slate-900 p-3 rounded-xl border">
        <h3 class="font-semibold mb-4">Все тикеты</h3>

        <div class="space-y-2 mb-4">
          <select v-model="filters.status" class="input" @change="loadThreads">
            <option value="all">Все</option>
            <option value="open">Открытые</option>
            <option value="closed">Закрытые</option>
          </select>

          <input
            v-model="filters.search"
            @input="loadThreads"
            placeholder="Поиск..."
            class="input"
          />
        </div>

        <div class="overflow-y-auto max-h-[600px] space-y-2">
          <div
            v-for="t in threads"
            :key="t.id"
            class="p-2 rounded cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800"
            @click="openThread(t)"
          >
            <div class="font-semibold text-sm">
              {{ t.subject || "Без темы" }}
            </div>
            <div class="text-xs text-slate-500">
              {{ t.user.name }}
              — {{ t.status === "closed" ? "Закрыт" : "Открыт" }}
            </div>
          </div>
        </div>
      </div>

      <!-- ПРАВАЯ КОЛОНКА ЧАТ -->
      <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border flex flex-col">

        <div
          v-if="activeThread"
          class="flex justify-between items-center mb-3 border-b pb-3"
        >
          <div>
            <div class="font-semibold">
              {{ activeThread.subject || "Обращение" }}
            </div>
            <div class="text-sm text-slate-500">
              Пользователь: {{ activeThread.user.name }}
            </div>
          </div>

          <div>
            <button
              v-if="activeThread.status === 'open'"
              class="btn-red"
              @click="closeThread"
            >
              Закрыть
            </button>
            <button
              v-else
              class="btn-blue"
              @click="reopenThread"
            >
              Открыть
            </button>
          </div>
        </div>

        <div class="flex-1 overflow-y-auto space-y-3">
          <div
            v-for="m in messages"
            :key="m.id"
            class="flex"
            :class="m.is_support ? 'justify-end' : 'justify-start'"
          >
            <div
              class="max-w-[75%] px-3 py-2 rounded-xl"
              :class="
                m.is_support
                  ? 'bg-blue-600 text-white'
                  : 'bg-slate-100 dark:bg-slate-800'
              "
            >
              <div>{{ m.body }}</div>

              <div v-if="m.attachments.length" class="mt-2 space-y-1">
                <div
                  v-for="file in m.attachments"
                  :key="file.id"
                >
                  <img
                    v-if="file.mime_type.startsWith('image/')"
                    :src="`/storage/${file.path}`"
                    class="max-w-xs rounded border"
                  />
                  <video
                    v-else-if="file.mime_type.startsWith('video/')"
                    controls
                    class="max-w-xs rounded border"
                  >
                    <source :src="`/storage/${file.path}`" />
                  </video>
                  <a
                    v-else
                    :href="`/storage/${file.path}`"
                    target="_blank"
                    class="underline text-xs"
                  >
                    📎 {{ file.original_name }}
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ВВОД -->
        <div v-if="activeThread" class="border-t pt-3 space-y-2">
          <input
            type="file"
            multiple
            class="hidden"
            id="file-upload"
            @change="fileChange"
          />
          <label for="file-upload" class="btn-gray cursor-pointer">
            📎 Файлы
          </label>

          <textarea
            v-model="newMessage"
            class="w-full input"
            placeholder="Ваш ответ..."
          />

          <button
            class="btn-blue"
            :disabled="sending"
            @click="sendMessage"
          >
            Отправить
          </button>
        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>
