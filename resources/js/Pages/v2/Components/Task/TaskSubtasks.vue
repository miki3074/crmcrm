<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
    subtasks: Array,
    loading: Boolean,
    canCreate: Boolean
})

defineEmits(['create'])

const showAll = ref(false)

// Логика: если "показать все" выключено, берем первые 4, иначе все
const visibleSubtasks = computed(() => {
    if (!props.subtasks) return []
    if (showAll.value) return props.subtasks
    return props.subtasks.slice(0, 4)
})

// Проверка: нужно ли вообще показывать кнопку (если задач > 4)
const hasMore = computed(() => props.subtasks?.length > 4)

// Хелперы
const getProgressColor = (progress) => {
    if (progress === 100) return 'bg-emerald-500'
    if (progress >= 50) return 'bg-amber-400'
    return 'bg-blue-500'
}

const getInitials = (name) => {
    if (!name) return '?'
    return name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase()
}

const formatDate = (dateStr) => {
    if (!dateStr) return '—'
    return new Date(dateStr).toLocaleDateString('ru-RU', { day: 'numeric', month: 'short' })
}
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 h-auto flex flex-col">

        <!-- Заголовок -->
        <div class="flex justify-between items-center mb-5">
            <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                Подзадачи <span class="text-xs text-gray-400 font-normal bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded-full">{{ subtasks?.length || 0 }}</span>
            </h3>
            <button
                v-if="canCreate"
                @click="$emit('create')"
                class="group flex items-center gap-1 text-xs bg-emerald-50 text-emerald-600 px-3 py-1.5 rounded-lg font-bold hover:bg-emerald-100 transition"
            >
                <span class="text-lg leading-none mb-0.5">+</span> Добавить
            </button>
        </div>

        <!-- Пустое состояние -->
        <div v-if="!subtasks?.length" class="flex-1 flex flex-col items-center justify-center py-8 text-gray-400 border-2 border-dashed border-gray-100 dark:border-gray-700 rounded-xl">
            <svg class="w-10 h-10 mb-2 opacity-20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            <span class="text-sm">Нет подзадач</span>
        </div>

        <!-- Сетка задач (2 колонки) -->
        <div v-else>
            <!-- grid-cols-1 для мобильных, sm:grid-cols-2 для планшетов и десктопов -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                <div
                    v-for="s in visibleSubtasks"
                    :key="s.id"
                    @click="router.visit(`/subtasks/${s.id}`)"
                    class="group relative bg-white dark:bg-gray-700 p-4 rounded-xl border border-gray-200 dark:border-gray-600 hover:border-blue-300 dark:hover:border-blue-500 hover:shadow-md transition-all cursor-pointer flex flex-col justify-between"
                >
                    <!-- Верх: Заголовок -->
                    <div class="flex justify-between items-start mb-3">
                        <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200 line-clamp-2 group-hover:text-blue-600 transition-colors pr-2 h-10 leading-5">
                            {{ s.title }}
                        </h4>
                        <!-- Стрелочка -->
                        <div class="opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0">
                            <svg class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </div>
                    </div>

                    <!-- Центр: Прогресс -->
                    <div class="mb-4">
                        <div class="h-1.5 w-full bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500"
                                 :class="getProgressColor(s.progress)"
                                 :style="{ width: s.progress + '%' }">
                            </div>
                        </div>
                    </div>

                    <!-- Низ: Аватары и Дата -->
                    <div class="flex items-center justify-between pt-2 border-t border-gray-50 dark:border-gray-700 mt-auto">
                        <!-- Аватары -->
                        <div class="flex -space-x-2 overflow-hidden pl-1">
                            <template v-if="s.executors?.length">
                                <div v-for="exe in s.executors.slice(0, 2)" :key="exe.id"
                                     class="inline-block h-6 w-6 rounded-full ring-2 ring-white dark:ring-gray-800 bg-gray-200 flex items-center justify-center text-[9px] font-bold text-gray-600"
                                     :title="exe.name">
                                    {{ getInitials(exe.name) }}
                                </div>
                                <div v-if="s.executors.length > 2" class="inline-block h-6 w-6 rounded-full ring-2 ring-white dark:ring-gray-800 bg-gray-100 flex items-center justify-center text-[9px] font-bold text-gray-500">
                                    +{{ s.executors.length - 2 }}
                                </div>
                            </template>
                            <span v-else class="text-[10px] text-gray-400">Нет исп.</span>
                        </div>

                        <!-- Дата -->
                        <div class="text-[10px] text-gray-400 flex items-center gap-1">
                            <span>{{ formatDate(s.due_date) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Кнопка Показать еще / Скрыть -->
            <div v-if="hasMore" class="flex justify-center pt-2 border-t border-gray-100 dark:border-gray-700">
                <button
                    @click="showAll = !showAll"
                    class="text-xs font-medium text-gray-500 hover:text-blue-600 flex items-center gap-1 transition-colors px-4 py-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700"
                >
                    <span v-if="!showAll">Показать еще ({{ subtasks.length - 4 }})</span>
                    <span v-else>Свернуть</span>

                    <svg :class="{'rotate-180': showAll}" class="w-3 h-3 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
            </div>
        </div>

    </div>
</template>
