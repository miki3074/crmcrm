<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    meeting: Object,
    auth_user_id: Number,
    canManage: Boolean,
    myParticipation: Object
});

const formatDate = (date) => new Date(date).toLocaleString('ru-RU');

const docForm = useForm({ file: null, name: '' });
const uploadDocument = () => {
    if (!docForm.file) return alert('Выберите файл');
    docForm.post(route('meetings.documents.store', props.meeting.id), {
        preserveScroll: true,
        onSuccess: () => { docForm.reset(); document.getElementById('fileInput').value = ''; }
    });
};

const deleteDocument = (docId) => {
    if (!confirm('Удалить этот файл?')) return;
    router.delete(route('meetings.documents.destroy', [props.meeting.id, docId]), { preserveScroll: true });
};

// Редактирование
const editingDocId = ref(null);
const editDocForm = useForm({ name: '', file: null, _method: 'PUT' });

const startEdit = (doc) => {
    editingDocId.value = doc.id;
    editDocForm.name = doc.name;
    editDocForm.file = null;
};
const updateDocument = () => {
    router.post(route('meetings.documents.update', [props.meeting.id, editingDocId.value]), {
        _method: 'PUT',
        name: editDocForm.name,
        file: editDocForm.file,
    }, { preserveScroll: true, onSuccess: () => editingDocId.value = null });
};
</script>

<template>
    <div class="bg-white shadow sm:rounded-lg p-6 mt-6">
        <h3 class="text-lg font-medium mb-4">Документы ({{ meeting.documents ? meeting.documents.length : 0 }})</h3>
        <ul class="space-y-4 mb-6">
            <template v-if="meeting.documents">
                <li v-for="doc in meeting.documents" :key="doc.id" class="border-b pb-2 last:border-0">
                    <div v-if="editingDocId !== doc.id" class="flex justify-between">
                        <div class="min-w-0">
                            <a :href="route('meetings.documents.download', [meeting.id, doc.id])" class="text-blue-600 hover:underline font-medium block truncate" target="_blank">{{ doc.name }}</a>
                            <p class="text-xs text-gray-500">{{ doc.human_size }} • {{ doc.uploader.name }} • {{ formatDate(doc.created_at) }}</p>
                        </div>
                        <div v-if="doc.user_id === auth_user_id" class="flex gap-2">
                            <button @click="startEdit(doc)" class="text-gray-400 hover:text-blue-500">✎</button>
                            <button @click="deleteDocument(doc.id)" class="text-gray-400 hover:text-red-500">✕</button>
                        </div>
                    </div>
                    <div v-else class="bg-gray-50 p-3 rounded space-y-2">
                        <input v-model="editDocForm.name" class="w-full text-xs border rounded" placeholder="Имя">
                        <input type="file" @input="editDocForm.file = $event.target.files[0]" class="w-full text-xs">
                        <div class="flex gap-2 justify-end">
                            <button @click="updateDocument" class="bg-blue-600 text-white px-2 py-1 rounded text-xs">Сохранить</button>
                            <button @click="editingDocId = null" class="bg-gray-200 px-2 py-1 rounded text-xs">Отмена</button>
                        </div>
                    </div>
                </li>
            </template>
        </ul>

        <div v-if="myParticipation || canManage" class="border-t pt-4">
            <input v-model="docForm.name" type="text" placeholder="Название (опционально)" class="w-full text-sm border rounded mb-2 px-2 py-1">
            <input id="fileInput" type="file" @input="docForm.file = $event.target.files[0]" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:bg-blue-50 file:text-blue-700">
            <button @click="uploadDocument" :disabled="docForm.processing || !docForm.file" class="mt-2 w-full bg-gray-800 text-white px-4 py-2 rounded text-sm hover:bg-gray-700">Загрузить</button>
        </div>
    </div>
</template>
