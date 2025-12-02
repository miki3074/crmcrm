<script setup>
import { ref, computed, onMounted } from "vue"
import { Head } from "@inertiajs/vue3"
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue"
import ContractCard from "@/Components/ContractCard.vue"
import axios from "axios"

import draggable from "vuedraggable"

// локальные списки колонок
const columns = ref({
    new: [],
    negotiation: [],
    signed: [],
    rejected: [],
})

// --- Список договоров ---
const contracts = ref([])

// --- Модалки ---
const showCreate = ref(false)
const showEdit = ref(false)

// --- Форма создания ---
const createForm = ref({
    title: "",
    counterparty: "",
    status: "new",
    files: [],
})

// --- Форма редактирования ---
const editForm = ref({
    id: null,
    title: "",
    counterparty: "",
    status: "",
    files: [],
})

// Загружаем все договоры
const loadContracts = async () => {
    const { data } = await axios.get("/api/contracts")
    contracts.value = data

    columns.value.new = data.filter(c => c.status === "new")

    columns.value.negotiation = data.filter(c => c.status === "negotiation")
    columns.value.signed      = data.filter(c => c.status === "signed")
    columns.value.rejected    = data.filter(c => c.status === "rejected")
}

const onDrop = async (evt, newStatus) => {
    if (!evt?.added?.element) return

    const item = evt.added.element

    // обновляем статус локально
    item.status = newStatus

    // отправка на сервер
    await axios.post(`/api/contracts/${item.id}/move`, {
        status: newStatus
    })
}



// --- 4 КОЛОНКИ ---
const contractsAll = computed(() => contracts.value)

const contractsNegotiation = computed(() =>
    contracts.value.filter(c => c.status === "negotiation")
)

const contractsSigned = computed(() =>
    contracts.value.filter(c => c.status === "signed")
)

const contractsRejected = computed(() =>
    contracts.value.filter(c => c.status === "rejected")
)

// --- Создание договора ---
const createContract = async () => {
    const fd = new FormData();
    fd.append("title", createForm.value.title);
    fd.append("counterparty", createForm.value.counterparty);
    fd.append("status", "new");

    for (let i = 0; i < createForm.value.files.length; i++) {
        fd.append(`files[${i}]`, createForm.value.files[i]);
    }

    await axios.post("/api/contracts", fd)

    showCreate.value = false
    createForm.value = {
        title: "",
        counterparty: "",
        status: "new",
        file: null
    }

    await loadContracts()
}

// --- Открытие редактирования ---
const openEdit = (c) => {
    showEdit.value = true
    editForm.value = {
        id: c.id,
        title: c.title,
        counterparty: c.counterparty,
        status: c.status,
        file: null,
    }
}

// --- Сохранение изменения ---
const updateContract = async () => {
    const fd = new FormData()
    fd.append("title", editForm.value.title)
    fd.append("counterparty", editForm.value.counterparty)
    fd.append("status", editForm.value.status)

    // 🔥 добавляем МНОГО файлов
    editForm.value.files.forEach((file, i) => {
        fd.append(`files[${i}]`, file)
    })

    fd.append('_method', 'PUT')

    await axios.post(`/api/contracts/${editForm.value.id}`, fd)

    showEdit.value = false
    await loadContracts()
}


// --- Удаление ---
const deleteContract = async (id) => {
    if (!confirm("Удалить договор?")) return
    await axios.delete(`/api/contracts/${id}`)
    await loadContracts()
}

const deleteFile = async (file) => {
    if (!confirm("Удалить файл?")) return;

    await axios.delete(`/api/contracts/files/${file.id}`);

    await loadContracts();
};


onMounted(loadContracts)
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Договоры" />

        <template #header>
            <h2 class="text-2xl font-bold text-slate-800 dark:text-slate-100">
                📑 Договоры
            </h2>
        </template>

        <div class="max-w-7xl mx-auto p-6">
            <button
                class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700"
                @click="showCreate = true"
            >
                + Новый договор
            </button>

            <!-- 4 КОЛОНКИ -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">

                <div class="col bg-white border rounded-xl p-4 shadow">
                    <h3 class="font-bold text-lg mb-3 text-blue-600">Новые</h3>

                    <draggable
                        v-model="columns.new"
                        item-key="id"
                        group="contracts"
                        @change="evt => onDrop(evt, 'new')"
                    >
                        <template #item="{ element }">
                            <ContractCard
                                :c="element"
                                @edit="openEdit"
                                @delete="deleteContract"
                                @delete-file="deleteFile"
                            />

                        </template>
                    </draggable>

                    <p v-if="!columns.new.length" class="text-xs text-slate-500">Пусто</p>
                </div>

                <!-- Переговоры -->
                <div class="col bg-white dark:bg-slate-900 border rounded-xl p-4 shadow">
                    <h3 class="font-bold text-lg mb-3 text-yellow-600">Переговоры</h3>

                    <draggable
                        v-model="columns.negotiation"
                        item-key="id"
                        group="contracts"
                        @change="evt => onDrop(evt, 'negotiation')"
                    >

                    <template #item="{ element }">
                        <ContractCard
                            :c="element"
                            @edit="openEdit"
                            @delete="deleteContract"
                            @delete-file="deleteFile"
                        />
                        </template>
                    </draggable>

                    <p v-if="!columns.negotiation.length" class="text-xs text-slate-500">Пусто</p>
                </div>

                <!-- Заключен -->
                <div class="col bg-white dark:bg-сlate-900 border rounded-xl p-4 shadow">
                    <h3 class="font-bold text-lg mb-3 text-green-600">Заключен</h3>

                    <draggable
                        v-model="columns.signed"
                        item-key="id"
                        group="contracts"
                        @change="evt => onDrop(evt, 'signed')"
                    >

                    <template #item="{ element }">
                        <ContractCard
                            :c="element"
                            @edit="openEdit"
                            @delete="deleteContract"
                            @delete-file="deleteFile"
                        />
                        </template>
                    </draggable>

                    <p v-if="!columns.signed.length" class="text-xs text-slate-500">Пусто</p>
                </div>

                <!-- Отказались -->
                <div class="col bg-white dark:bg-сlate-900 border rounded-xl p-4 shadow">
                    <h3 class="font-bold text-lg mb-3 text-red-600">Отказались</h3>

                    <draggable
                        v-model="columns.rejected"
                        item-key="id"
                        group="contracts"
                        @change="evt => onDrop(evt, 'rejected')"
                    >

                    <template #item="{ element }">
                        <ContractCard
                            :c="element"
                            @edit="openEdit"
                            @delete="deleteContract"
                            @delete-file="deleteFile"
                        />
                        </template>
                    </draggable>

                    <p v-if="!columns.rejected.length" class="text-xs text-slate-500">Пусто</p>
                </div>

            </div>


            <!-- === МОДАЛЬНОЕ ОКНО СОЗДАНИЯ === -->
            <div v-if="showCreate" class="modal">
                <div class="modal-content">

                    <h2 class="text-xl font-bold mb-4">Новый договор</h2>

                    <input v-model="createForm.title" class="input" placeholder="Название" />
                    <input v-model="createForm.counterparty" class="input" placeholder="Контрагент" />

                    <input type="hidden" v-model="createForm.status">

                    <input type="file" multiple @change="e => createForm.files = e.target.files" />


                    <div class="flex justify-end gap-2 mt-4">
                        <button class="btn-gray" @click="showCreate = false">Отмена</button>
                        <button class="btn-blue" @click="createContract">Создать</button>
                    </div>

                </div>
            </div>

            <!-- === МОДАЛЬНОЕ ОКНО РЕДАКТИРОВАНИЯ === -->
            <div v-if="showEdit" class="modal">
                <div class="modal-content">

                    <h2 class="text-xl font-bold mb-4">Редактировать договор</h2>

                    <input v-model="editForm.title" class="input" />
                    <input v-model="editForm.counterparty" class="input" />

                    <select v-model="editForm.status" class="input">
                        <option value="negotiation">Переговоры</option>
                        <option value="signed">Заключен</option>
                        <option value="rejected">Отказались</option>
                    </select>

                    <input type="file" multiple @change="e => editForm.files = [...e.target.files]" />


                    <div class="flex justify-end gap-2 mt-4">
                        <button class="btn-gray" @click="showEdit = false">Отмена</button>
                        <button class="btn-blue" @click="updateContract">Сохранить</button>
                    </div>

                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.input {
    @apply w-full border rounded-lg px-3 py-2 mb-3 dark:bg-slate-800 dark:border-slate-700;
}

.btn-blue {
    @apply bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700;
}

.btn-gray {
    @apply bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600;
}

.modal {
    @apply fixed inset-0 bg-black/50 flex items-center justify-center z-50;
}

.modal-content {
    @apply bg-white dark:bg-slate-900 p-6 rounded-xl w-full max-w-md;
}
</style>
