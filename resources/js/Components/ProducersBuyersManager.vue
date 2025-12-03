<script setup>
import { ref, computed, onMounted } from "vue";
import axios from "axios";

const emit = defineEmits(["close", "selectProducer", "selectBuyer"]);

const props = defineProps({
    companies: {
        type: Array,
        default: () => []
    }
});


/* ------------------ STATE ------------------ */

const activeTab = ref("producers"); // producers | buyers
const showModal = ref(true);

const loading = ref(true);

const producers = ref([]);
const buyers = ref([]);

const searchProducer = ref("");
const searchBuyer = ref("");

const editMode = ref(false);
const editItem = ref(null);




const form = ref({
    name: "",
    company_id: "",
});

/* ------------------ API ------------------ */

const loadProducers = async () => {
    loading.value = true;
    const { data } = await axios.get("/api/producers", {
        params: { search: searchProducer.value },
    });
    producers.value = data;
    loading.value = false;
};

const loadBuyers = async () => {
    loading.value = true;
    const { data } = await axios.get("/api/buyers", {
        params: { search: searchBuyer.value },
    });
    buyers.value = data;
    loading.value = false;
};

const createProducer = async () => {
    if (!form.value.name.trim()) return;

    await axios.post("/api/producers", { name: form.value.name, company_id: form.value.company_id });
    form.value.name = "";
    await loadProducers();
};

const createBuyer = async () => {
    if (!form.value.name.trim()) return;

    await axios.post("/api/buyers", { name: form.value.name, company_id: form.value.company_id });
    form.value.name = "";
    await loadBuyers();
};

const startEdit = (item) => {
    editMode.value = true;
    editItem.value = item;
    form.value.name = item.name;
};

const saveEdit = async () => {
    if (!editMode.value || !editItem.value) return;

    const id = editItem.value.id;

    if (activeTab.value === "producers") {
        await axios.put(`/api/producers/${id}`, { name: form.value.name, company_id: form.value.company_id });
        await loadProducers();
    } else {
        await axios.put(`/api/buyers/${id}`, { name: form.value.name, company_id: form.value.company_id });
        await loadBuyers();
    }

    cancelEdit();
};

const cancelEdit = () => {
    editMode.value = false;
    editItem.value = null;
    form.value.name = "";
};

const deleteItem = async (item) => {
    if (!confirm("Удалить запись?")) return;

    if (activeTab.value === "producers") {
        await axios.delete(`/api/producers/${item.id}`);
        await loadProducers();
    } else {
        await axios.delete(`/api/buyers/${item.id}`);
        await loadBuyers();
    }
};

/* ------------------ COMPUTED ------------------ */

const filteredProducers = computed(() => {
    return producers.value;
});

const filteredBuyers = computed(() => {
    return buyers.value;
});

/* ------------------ INIT ------------------ */

onMounted(async () => {
    await loadProducers();
    await loadBuyers();
});
</script>

<template>
    <div class="fixed inset-0 bg-black/50 flex justify-center items-center z-50">
        <div class="bg-white dark:bg-gray-900 w-full max-w-3xl rounded-2xl p-6 shadow-xl">

            <!-- Header -->
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold">Производители / Покупатели</h2>
                <button @click="emit('close')" class="text-gray-400 hover:text-red-500 text-xl">✕</button>
            </div>

            <!-- Tabs -->
            <div class="flex border-b mb-4">
                <button
                    class="flex-1 py-2 text-center"
                    :class="activeTab === 'producers' ? 'border-b-2 border-indigo-500 font-semibold' : 'text-gray-500'"
                    @click="activeTab = 'producers'"
                >
                    Производители ({{ producers.length }})
                </button>

                <button
                    class="flex-1 py-2 text-center"
                    :class="activeTab === 'buyers' ? 'border-b-2 border-indigo-500 font-semibold' : 'text-gray-500'"
                    @click="activeTab = 'buyers'"
                >
                    Покупатели ({{ buyers.length }})
                </button>
            </div>

            <!-- Search & Add Form -->
            <div class="mb-4">

                <!-- Search -->
                <input
                    v-if="activeTab === 'producers'"
                    v-model="searchProducer"
                    @input="loadProducers"
                    class="input mb-3"
                    placeholder="Поиск производителей..."
                />

                <input
                    v-if="activeTab === 'buyers'"
                    v-model="searchBuyer"
                    @input="loadBuyers"
                    class="input mb-3"
                    placeholder="Поиск покупателей..."
                />

                <!-- CREATE / EDIT FORM -->
                <div class="">
                    <input
                        v-model="form.name"
                        class="input flex-grow"
                        placeholder="Название"
                    />


                    <select v-model="form.company_id" class="input mb-3">
                        <option disabled value="">— Выберите компанию —</option>
                        <option v-for="c in props.companies" :key="c.id" :value="c.id">
                            {{ c.name }}
                        </option>
                    </select>


                    <button
                        v-if="!editMode"
                        @click="activeTab === 'producers' ? createProducer() : createBuyer()"
                        class="btn-blue"
                    >
                        Добавить
                    </button>

                    <button
                        v-if="editMode"
                        @click="saveEdit"
                        class="btn-green"
                    >
                        Сохранить
                    </button>

                    <button
                        v-if="editMode"
                        @click="cancelEdit"
                        class="btn-gray"
                    >
                        Отмена
                    </button>
                </div>

            </div>

            <!-- LIST -->
            <div class="max-h-80 overflow-auto border rounded-xl p-3">
                <!-- Producers list -->
                <div v-if="activeTab === 'producers'">
                    <div
                        v-for="p in filteredProducers"
                        :key="p.id"
                        class="flex justify-between items-center py-2 border-b last:border-0"
                    >
                        <span>{{ p.name }}
                        <small class="text-gray-500 ml-2" v-if="p.company">
    компания — {{ p.company.name }}
  </small>
                        </span>

                        <div class="flex gap-2">
                            <button @click="startEdit(p)" class="text-amber-600 hover:underline text-sm">
                                ✏ Редактировать
                            </button>
                            <button @click="deleteItem(p)" class="text-red-600 hover:underline text-sm">
                                🗑 Удалить
                            </button>
<!--                            <button @click="emit('selectProducer', p)" class="text-indigo-600 hover:underline text-sm">-->
<!--                                ➕ Добавить к задаче-->
<!--                            </button>-->
                        </div>
                    </div>
                </div>

                <!-- Buyers list -->
                <div v-if="activeTab === 'buyers'">
                    <div
                        v-for="b in filteredBuyers"
                        :key="b.id"
                        class="flex justify-between items-center py-2 border-b last:border-0"
                    >
                        <span>{{ b.name }}
                        <small class="text-gray-500">
                            компания — {{ b.company?.name }}
                        </small>
                        </span>

                        <div class="flex gap-2">
                            <button @click="startEdit(b)" class="text-amber-600 hover:underline text-sm">
                                ✏ Редактировать
                            </button>
                            <button @click="deleteItem(b)" class="text-red-600 hover:underline text-sm">
                                🗑 Удалить
                            </button>
<!--                            <button @click="emit('selectBuyer', b)" class="text-indigo-600 hover:underline text-sm">-->
<!--                                ➕ Добавить к задаче-->
<!--                            </button>-->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex justify-end mt-4">
                <button @click="emit('close')" class="btn-gray">Закрыть</button>
            </div>

        </div>
    </div>
</template>

<style scoped>
.input {
    @apply w-full border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-600 mb-2;
}
.btn-blue {
    @apply bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700;
}
.btn-green {
    @apply bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700;
}
.btn-gray {
    @apply bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600;
}
</style>
