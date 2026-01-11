<script setup>
import TaskChecklists from '@/Components/TaskChecklists.vue' // Ваш существующий компонент

defineProps({ task: Object })
</script>

<template>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
            <h3 class="font-bold text-gray-900 dark:text-white mb-4">ℹ️ Инфо</h3>
            <ul class="space-y-3 text-sm text-gray-600 dark:text-gray-300">
                <li class="flex justify-between"><span>Исполнители:</span> <span class="font-medium text-right">{{ task?.executors?.map(e=>e.name).join(', ') || '—' }}</span></li>
                <li class="flex justify-between"><span>Ответственные:</span> <span class="font-medium text-right">{{ task?.responsibles?.map(e=>e.name).join(', ') || '—' }}</span></li>
                <li class="flex justify-between"><span>Наблюдатели:</span> <span class="font-medium text-right">{{ task?.watcherstask?.map(e=>e.name).join(', ') || '—' }}</span></li>
            </ul>

            <!-- Производители / Покупатели (если есть) -->
            <div v-if="task?.producers?.length" class="mt-4 pt-4 border-t dark:border-gray-700">
                <div class="text-xs font-bold uppercase mb-2">Производители</div>
                <div class="flex flex-wrap gap-1">
                    <span v-for="p in task.producers" :key="p.id" class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs">{{ p.name }}</span>
                </div>
            </div>
        </div>

        <!-- Чеклисты -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
            <TaskChecklists
                :task-id="task.id"
                :executors="task.executors"
                :responsibles="task.responsibles"
                :creator="task.creator"
            />
        </div>
    </div>
</template>
