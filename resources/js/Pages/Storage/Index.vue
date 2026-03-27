<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { FolderIcon, DocumentIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'

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
            <div class="flex justify-between items-center">
                <h2 class="text-2xl font-bold bg-gradient-to-r from-gray-900 to-gray-600 dark:from-white dark:to-gray-300 bg-clip-text text-transparent">
                    Хранилище
                </h2>
                <span class="text-sm text-gray-500 dark:text-gray-400">
          {{ companies.length }} {{ companies.length === 1 ? 'компания' : 'компаний' }}
        </span>
            </div>
        </template>

        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <!-- Пустое состояние -->
            <div v-if="companies.length === 0" class="text-center py-12">
                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-2xl p-12 max-w-md mx-auto">
                    <FolderIcon class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-600 mb-4" />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                        Нет доступных компаний
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Компании появятся здесь после добавления в систему
                    </p>
                </div>
            </div>

            <!-- Сетка компаний -->
            <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                <div v-for="c in companies" :key="c.id"
                     class="group relative bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700
                    hover:border-blue-500 dark:hover:border-blue-400 hover:shadow-lg
                    transition-all duration-200 cursor-pointer overflow-hidden"
                     @click="$inertia.visit(`/file-storage/companies/${c.id}`)">

                    <!-- Декоративный элемент -->
                    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-blue-500 to-indigo-500
                      transform scale-x-0 group-hover:scale-x-100 transition-transform duration-300" />

                    <div class="p-5">
                        <div class="flex items-start gap-4">
                            <!-- Логотип или плейсхолдер -->
                            <div class="relative flex-shrink-0">
                                <div v-if="c.logo" class="w-14 h-14 rounded-lg overflow-hidden ring-2 ring-gray-100 dark:ring-gray-700">
                                    <img :src="`/storage/${c.logo}`"
                                         :alt="c.name"
                                         class="w-full h-full object-cover" />
                                </div>
                                <div v-else class="w-14 h-14 rounded-lg bg-gradient-to-br from-blue-50 to-indigo-50
                                  dark:from-gray-700 dark:to-gray-600 flex items-center justify-center
                                  ring-2 ring-gray-100 dark:ring-gray-700">
                                    <FolderIcon class="w-7 h-7 text-blue-500 dark:text-blue-400" />
                                </div>
                            </div>

                            <!-- Информация о компании -->
                            <div class="flex-1 min-w-0">
                                <h3 class="text-base font-semibold text-gray-900 dark:text-white truncate group-hover:text-blue-600
                          dark:group-hover:text-blue-400 transition-colors">
                                    {{ c.name }}
                                </h3>

                                <!-- Статистика (можно раскомментировать когда будет готова) -->
                                <!-- <div class="mt-2 flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                                  <span class="flex items-center gap-1">
                                    <DocumentIcon class="w-3.5 h-3.5" />
                                    {{ c.storage_files_count || 0 }} файлов
                                  </span>
                                </div> -->

                                <!-- Индикатор перехода -->
                                <div class="mt-3 flex items-center text-xs font-medium text-blue-600 dark:text-blue-400
                          opacity-0 group-hover:opacity-100 transition-opacity">
                                    <span>Перейти в хранилище</span>
                                    <ChevronRightIcon class="w-4 h-4 ml-1 transition-transform group-hover:translate-x-1" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Дополнительная информация (опционально) -->
            <div v-if="companies.length > 0" class="mt-8 text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Нажмите на карточку компании чтобы открыть её хранилище
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Дополнительные плавные анимации */
.group {
    transform: translateY(0);
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}

.group:hover {
    transform: translateY(-2px);
}

/* Скелетон загрузки (можно добавить позже) */
.skeleton {
    @apply animate-pulse bg-gray-200 dark:bg-gray-700 rounded;
}
</style>
