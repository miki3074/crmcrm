<script setup>
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    task: Object
});

// Конфигурация статусов
const statuses = {
    pending: { label: 'Ожидает', class: 'bg-gray-100 text-gray-600 border-gray-200' },
    processing: { label: 'В работе', class: 'bg-blue-100 text-blue-700 border-blue-200' },
    completed: { label: 'Завершено', class: 'bg-green-100 text-green-700 border-green-200' },
    cancelled: { label: 'Отменено', class: 'bg-red-100 text-red-700 border-red-200' },
};

const changeStatus = (newStatus) => {
    router.patch(route('klient-tasks.update-status', props.task.id), {
        status: newStatus
    }, {
        preserveScroll: true
    });
};

const priorityBadge = (priority) => {
    const map = {
        high: 'bg-red-500 text-white',
        medium: 'bg-orange-400 text-white',
        low: 'bg-gray-400 text-white'
    };
    return map[priority] || 'bg-gray-400';
};
</script>

<template>
    <div class="bg-white border rounded-xl shadow-sm overflow-hidden hover:shadow-md transition-shadow">
        <div class="p-5">
            <!-- Шапка карточки -->
            <div class="flex justify-between items-start mb-3">
                <div class="flex items-center space-x-2">
                    <span :class="['text-[10px] px-2 py-0.5 rounded-full font-bold uppercase', priorityBadge(task.priority)]">
                        {{ task.priority }}
                    </span>
                    <span :class="['text-xs px-2 py-0.5 rounded border font-medium', statuses[task.status].class]">
                        {{ statuses[task.status].label }}
                    </span>
                </div>

                <!-- Выпадающий список смены статуса -->
                <div class="relative group">
                    <button class="text-gray-400 hover:text-gray-600 p-1">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" /></svg>
                    </button>
                    <div class="absolute right-0 mt-2 w-40 bg-white border rounded-md shadow-lg hidden group-hover:block z-10">
                        <div class="py-1 text-xs text-gray-700">
                            <button @click="changeStatus('pending')" class="block w-full text-left px-4 py-2 hover:bg-gray-50">Ожидание</button>
                            <button @click="changeStatus('processing')" class="block w-full text-left px-4 py-2 hover:bg-indigo-50 text-indigo-600 font-bold">В работу</button>
                            <button @click="changeStatus('completed')" class="block w-full text-left px-4 py-2 hover:bg-green-50 text-green-600 font-bold">Завершить</button>
                            <button @click="changeStatus('cancelled')" class="block w-full text-left px-4 py-2 hover:bg-red-50 text-red-600">Отменить</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Контент -->
            <h3 class="font-bold text-gray-900 mb-1">{{ task.title }}</h3>
            <p class="text-sm text-gray-600 line-clamp-2 mb-4">{{ task.description }}</p>

            <!-- Детали -->
            <div class="space-y-2 border-t pt-4">
                <div class="flex items-center text-xs text-gray-500">
                    <svg class="w-4 h-4 mr-2 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    Ответственный: <span class="ml-1 font-semibold text-gray-700">{{ task.responsible?.name }}</span>
                </div>
                <div v-if="task.deadline" class="flex items-center text-xs text-gray-500">
                    <svg class="w-4 h-4 mr-2 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    Срок: <span class="ml-1 font-bold text-red-600">{{ new Date(task.deadline).toLocaleString() }}</span>
                </div>
            </div>

            <!-- Файлы задачи -->
            <div v-if="task.files?.length" class="mt-4 flex flex-wrap gap-2">
                <div v-for="file in task.files" :key="file.id" class="flex items-center bg-gray-50 border rounded px-2 py-1 text-[10px] text-gray-600">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" /></svg>
                    <a :href="`/klient-task-files/${file.id}/download`" class="hover:underline truncate max-w-[100px]">{{ file.original_name }}</a>
                </div>
            </div>
        </div>

        <!-- Нижняя быстрая кнопка действия -->
        <div class="bg-gray-50 px-5 py-3 border-t">
            <button
                v-if="task.status !== 'completed'"
                @click="changeStatus('completed')"
                class="w-full text-center text-xs font-bold text-indigo-600 hover:text-indigo-800 uppercase tracking-wider"
            >
                Быстрое завершение
            </button>
            <div v-else class="text-center text-xs font-bold text-green-600 uppercase">
                Задача выполнена ✓
            </div>
        </div>
    </div>
</template>
