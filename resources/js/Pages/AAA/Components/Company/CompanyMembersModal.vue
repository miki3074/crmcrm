<script setup>
import { ref, watch } from 'vue'
import axios from 'axios'

const props = defineProps({ show: Boolean, companyId: [Number, String] })
const emit = defineEmits(['close'])
const members = ref([])
const loading = ref(false)

watch(() => props.show, async (val) => {
    if (val) {
        loading.value = true
        try {
            const { data } = await axios.get(`/api/companies/${props.companyId}/members`)
            members.value = data
        } finally { loading.value = false }
    }
})
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="$emit('close')"></div>
        <div class="relative w-full max-w-md bg-white dark:bg-zinc-800 rounded-2xl p-6 shadow-xl max-h-[80vh] overflow-y-auto">
            <h3 class="text-lg font-bold mb-4 dark:text-white">Участники</h3>
            <div v-if="loading">Загрузка...</div>
            <ul v-else class="space-y-3">
                <li v-for="user in members" :key="user.id" class="flex justify-between items-center p-2 hover:bg-zinc-50 rounded-lg">
                    <span class="font-medium text-zinc-700">{{ user.name }}</span>
                    <span class="text-xs bg-cyan-100 text-cyan-700 px-2 py-1 rounded">{{ user.pivot.role }}</span>
                </li>
            </ul>
        </div>
    </div>
</template>
