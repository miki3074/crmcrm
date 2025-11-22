<script setup>
import { ref } from 'vue'
import axios from 'axios'

const open = ref(false)
const loading = ref(false)
const success = ref(false)
const error = ref('')

const form = ref({
  message: '',
  files: [], // ← тут будем хранить File[]
})

const handleFiles = (e) => {
  form.value.files = Array.from(e.target.files || [])
}

const sendMessage = async () => {
  const text = form.value.message.trim()

  if (!text) {
    error.value = 'Введите сообщение'
    return
  }

  if (text.length > 2000) {
    error.value = 'Сообщение слишком длинное (макс. 2000 символов)'
    return
  }

  if (loading.value) return

  loading.value = true
  error.value = ''
  success.value = false

  try {
    await axios.get('/sanctum/csrf-cookie')

    const fd = new FormData()
    fd.append('message', text)
    fd.append('page_url', window.location.href)

    // файлы
    if (form.value.files?.length) {
      form.value.files.forEach((file) => {
        fd.append('files[]', file)
      })
    }

    await axios.post('/api/support', fd, {
      headers: {
        'Content-Type': 'multipart/form-data',
        'X-Requested-With': 'XMLHttpRequest',
      },
    })

    success.value = true
    form.value.message = ''
    form.value.files = []
  } catch (e) {
    if (e.response?.status === 429) {
      error.value = 'Слишком часто. Подождите немного и попробуйте снова.'
    } else {
      error.value = e.response?.data?.message || 'Ошибка при отправке'
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="fixed bottom-6 right-6 z-50">
    <!-- кнопка -->
    <button
      @click="open = !open"
      class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-full w-14 h-14 flex items-center justify-center shadow-lg"
    >
      <span v-if="!open">💬</span>
      <span v-else>×</span>
    </button>

    <!-- модалка -->
    <transition name="fade">
      <div
        v-if="open"
        class="absolute bottom-16 right-0 bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-xl shadow-xl w-80 p-4"
      >
        <h3 class="font-semibold mb-2 text-gray-800 dark:text-gray-100">
          Техподдержка
        </h3>

        <textarea
          v-model="form.message"
          class="w-full border rounded-lg p-2 dark:bg-gray-700 dark:text-white"
          rows="4"
          placeholder="Опишите проблему..."
        ></textarea>

        <!-- файловый input -->
        <div class="mt-3" style="display: none;">
          <label class="block text-xs text-gray-500 mb-1">
            Прикрепить файлы (фото/видео)
          </label>
          <input
            type="file"
            multiple
            @change="handleFiles"
            class="w-full text-xs"
          />

          <!-- список выбранных файлов -->
          <ul v-if="form.files && form.files.length" class="mt-1 text-xs text-gray-600 dark:text-gray-300">
            <li v-for="(f, idx) in form.files" :key="idx">
              📎 {{ f.name }}
            </li>
          </ul>
        </div>

        <button
          @click="sendMessage"
          :disabled="loading || !form.message"
          class="mt-3 w-full bg-indigo-600 text-white py-2 rounded-lg hover:bg-indigo-700 disabled:opacity-50"
        >
          {{ loading ? 'Отправка...' : 'Отправить' }}
        </button>

        <p v-if="success" class="text-green-600 mt-2 text-sm">✅ Сообщение отправлено</p>
        <p v-if="error" class="text-red-600 mt-2 text-sm">{{ error }}</p>
      </div>
    </transition>
  </div>
</template>

