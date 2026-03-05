<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const companies = ref([])

const fetchCompanies = async () => {
  const { data } = await axios.get('/api/storage/companies')
  companies.value = data
}

onMounted(fetchCompanies)
</script>

<template>
  <Head title="Хранилище" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-xl font-bold text-gray-800 dark:text-gray-200">Хранилище</h2>
    </template>

    <div class="max-w-6xl mx-auto py-8 px-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="c in companies" :key="c.id"
             class="p-4 rounded-lg bg-white dark:bg-gray-800 border hover:shadow cursor-pointer"
             @click="$inertia.visit(`/file-storage/companies/${c.id}`)">
          <div class="flex items-center gap-3">
            <img v-if="c.logo" :src="`/storage/${c.logo}`" class="w-12 h-12 object-cover rounded" />
            <div>
              <div class="font-semibold text-gray-900 dark:text-white">{{ c.name }}</div>
              <!-- <div class="text-xs text-gray-500" >Файлов: {{ c.storage_files_count }}</div> -->
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
