<!-- resources/js/Pages/Admin/Users/Index.vue -->
<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { PlusIcon, PencilIcon, TrashIcon, EyeIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    users: Object,
    roles: Array
})

// 🔥 Получаем данные текущего пользователя
const page = usePage()
const currentUser = computed(() => page.props.auth.user)
const userEmail = computed(() => currentUser.value?.email || '')

// 🔥 Проверяем права
const canCreate = computed(() => {
    return ['dir@npoenergoteh.ru', 'miki23074@gmail.com'].includes(userEmail.value)
})

const canEditDelete = computed(() => {
    return userEmail.value === 'miki23074@gmail.com'
})

const isCurrentUser = (userId) => {
    return userId === currentUser.value?.id
}

const deleteUser = (userId) => {
    if (confirm('Вы уверены, что хотите удалить этого пользователя?')) {
        router.delete(route('admin.users.destroy', userId))
    }
}
</script>

<template>
    <Head title="Управление пользователями" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Управление пользователями
                </h2>
                <Link
                    v-if="canCreate"
                    :href="route('admin.users.create')"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring focus:ring-indigo-300 disabled:opacity-25 transition"
                >
                    <PlusIcon class="w-4 h-4 mr-1" />
                    Новый пользователь
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead>
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Имя</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Телефон</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Роль</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Компании</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дата создания</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Действия</th>
                                </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="user in users.data" :key="user.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">{{ user.id }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">{{ user.name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">{{ user.email }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">{{ user.phone || '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span class="px-2 py-1 text-xs rounded-full"
                                                  :class="{
                                                    'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300': user.roles[0]?.name === 'admin',
                                                    'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300': user.roles[0]?.name === 'manager',
                                                    'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300': user.roles[0]?.name === 'employee'
                                                  }">
                                                {{ user.roles[0]?.name || 'Без роли' }}
                                            </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span v-for="company in user.companies" :key="company.id"
                                                  class="inline-block px-2 py-1 mr-1 text-xs bg-gray-100 dark:bg-gray-700 rounded">
                                                {{ company.name }}
                                            </span>
                                        <span v-if="!user.companies?.length" class="text-gray-400">-</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">{{ new Date(user.created_at).toLocaleDateString('ru-RU') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <div class="flex space-x-2">
                                            <Link :href="route('admin.users.show', user.id)"
                                                  class="text-blue-600 hover:text-blue-900 dark:text-blue-400">
                                                <EyeIcon class="w-5 h-5" />
                                            </Link>

                                            <!-- 🔥 Редактирование только для miki23074@gmail.com -->
                                            <Link v-if="canEditDelete && !isCurrentUser(user.id)"
                                                  :href="route('admin.users.edit', user.id)"
                                                  class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400">
                                                <PencilIcon class="w-5 h-5" />
                                            </Link>

                                            <!-- 🔥 Удаление только для miki23074@gmail.com -->
                                            <button v-if="canEditDelete && !isCurrentUser(user.id)"
                                                    @click="deleteUser(user.id)"
                                                    class="text-red-600 hover:text-red-900 dark:text-red-400">
                                                <TrashIcon class="w-5 h-5" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- 🔥 Компонент пагинации (если он у вас есть) -->
                        <div v-if="users.links" class="mt-4">
                            <Pagination :links="users.links" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
