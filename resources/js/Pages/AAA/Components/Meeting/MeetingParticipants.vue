<script setup>
defineProps({ participants: Array });
</script>

<template>
    <div class="bg-white shadow sm:rounded-lg p-6">
        <h3 class="text-lg font-medium mb-4">Список участников</h3>
        <ul class="space-y-3">
            <li v-for="user in participants" :key="user.id" class="flex items-center justify-between border-b pb-2 last:border-0">
                <div>
                    <div class="text-sm font-medium">{{ user.name }}</div>
                    <div class="text-xs">
                        <span v-if="user.pivot.status === 'invited'" class="text-gray-400">Ожидание</span>
                        <span v-else-if="user.pivot.status === 'accepted'" class="text-green-600">Подтвердил</span>
                        <span v-else-if="user.pivot.status === 'declined'" class="text-red-600">Отклонил</span>
                    </div>
                </div>
                <div v-if="user.pivot.status === 'accepted'" class="text-green-500">✓</div>
                <div v-if="user.pivot.status === 'declined'" class="text-red-500">✕</div>
            </li>
        </ul>
    </div>
</template>
