<script setup>
const props = defineProps({
    c: Object
})

const emit = defineEmits(['edit', 'delete', 'delete-file'])

const deleteFile = async (file) => {
    if (!confirm("Удалить файл?")) return;

    await axios.delete(`/api/contracts/files/${file.id}`);

    // обновляем список договоров
    await loadContracts();
};

</script>

<template>
    <div class="p-3 mb-2 rounded-xl border bg-slate-50 dark:bg-slate-800 shadow-sm">

        <div class="font-semibold text-sm">
          Название  {{ c.title }}
        </div>

        <div class="text-xs text-slate-500">
           Контрагент {{ c.counterparty || "—" }}
        </div>

        <div class="mt-2 space-y-1" v-if="c.files?.length">
            <div
                v-for="f in c.files"
                :key="f.id"
                class="flex items-center justify-between bg-slate-100 dark:bg-slate-800 px-3 py-2 rounded"
            >
                <a
                    :href="`/api/contracts/files/${f.id}/download`"
                    class="text-blue-600 underline"
                >
                    ⬇ Скачать: {{ f.file_name }}
                </a>

                <button
                    class="text-red-500 hover:text-red-700 text-xs"
                    @click="$emit('delete-file', f)"
                >
                    Удалить
                </button>
            </div>
        </div>



        <div class="flex gap-2 mt-2">
            <button class="px-2 py-1 text-xs bg-amber-500 text-white rounded"
                    @click="$emit('edit', c)">
                ✏ Ред.
            </button>

            <button class="px-2 py-1 text-xs bg-red-600 text-white rounded"
                    @click="$emit('delete', c.id)">
                🗑 Удалить
            </button>
        </div>

    </div>
</template>
