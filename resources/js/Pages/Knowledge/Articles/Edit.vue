<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import ArticleEditor from './component/ArticleEditor.vue';




const props = defineProps({
    company: {
        type: Object,
        required: true,
    },

    folder: {
        type: Object,
        required: true,
    },

    article: {
        type: Object,
        required: true,
    },

    permissions: {
    type: Object,
    default: () => ({
        delete: false,
    }),
},

});
const deleting = ref(false);

const fileInput = ref(null);
const uploadingFiles = ref(false);
const uploadErrors = ref([]);
const selectedFiles = ref([]);

function openFileDialog() {
    if (uploadingFiles.value) {
        return;
    }

    fileInput.value?.click();
}

function handleFileSelection(event) {
    selectedFiles.value = Array.from(event.target.files ?? []);

    if (!selectedFiles.value.length) {
        return;
    }

    uploadFiles();
}

function uploadFiles() {
    if (!selectedFiles.value.length || uploadingFiles.value) {
        return;
    }

    uploadingFiles.value = true;
    uploadErrors.value = [];

    router.post(
        `/companies/${props.company.id}/knowledge/articles/${props.article.id}/files`,
        {
            files: selectedFiles.value,
        },
        {
            forceFormData: true,
            preserveScroll: true,

            onError: (errors) => {
                uploadErrors.value = Object.values(errors);
            },

            onSuccess: () => {
                selectedFiles.value = [];

                if (fileInput.value) {
                    fileInput.value.value = '';
                }
            },

            onFinish: () => {
                uploadingFiles.value = false;
            },
        }
    );
}


function normalizeContent(content) {
    if (
        content &&
        typeof content === 'object' &&
        content.type === 'doc'
    ) {
        return {
            type: 'doc',
            content: Array.isArray(content.content)
                ? content.content
                : [],
        };
    }

    return {
        type: 'doc',
        content: [],
    };
}



const form = useForm({
    title: props.article.title ?? '',
    content: normalizeContent(props.article.content),
    content_text: props.article.content_text ?? '',
    status: props.article.status ?? 'draft',
});

function save(status = null) {
    if (status) {
        form.status = status;
    }

    form.put(
        `/companies/${props.company.id}/knowledge/articles/${props.article.id}`,
        {
            preserveScroll: true,
            preserveState: true,
        }
    );
}

function deleteArticle() {
    const confirmed = window.confirm(
        'Удалить статью без возможности восстановления?'
    );

    if (!confirmed || deleting.value) {
        return;
    }

    deleting.value = true;

    router.delete(
        `/companies/${props.company.id}/knowledge/articles/${props.article.id}`,
        {
            onFinish: () => {
                deleting.value = false;
            },
        }
    );
}

const deletingFile = ref(null);

function deleteFile(file) {
    const confirmed = window.confirm(
        `Удалить файл "${file.original_name}"?`
    );

    if (!confirmed || deletingFile.value !== null) {
        return;
    }

    deletingFile.value = file.id;

    router.delete(
        `/companies/${props.company.id}/knowledge/articles/${props.article.id}/files/${file.id}`,
        {
            preserveScroll: true,

            onError: (errors) => {
                console.error('Ошибка удаления файла:', errors);
            },

            onFinish: () => {
                deletingFile.value = null;
            },
        }
    );
}
</script>


<template>
    <Head :title="`${article.title} — редактирование`" />

    <div class="min-h-screen bg-slate-50 text-slate-900">
        <header
            class="sticky top-0 z-30 border-b border-slate-200
                   bg-white/90 backdrop-blur-xl"
        >
            <div
                class="mx-auto flex min-h-20 max-w-7xl flex-col
                       justify-between gap-4 px-4 py-4
                       sm:flex-row sm:items-center sm:px-6
                       lg:px-8"
            >
                <div class="min-w-0">
                    <Link
                        :href="`/companies/${company.id}/knowledge/folders/${folder.id}`"
                        class="inline-flex items-center gap-2
                               text-sm font-medium text-slate-500
                               transition hover:text-indigo-600"
                    >
                        <svg
                            class="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="m15 18-6-6 6-6"
                            />
                        </svg>

                        {{ folder.name }}
                    </Link>

                    <div class="mt-1 flex items-center gap-2">
                        <p class="truncate text-sm font-semibold text-slate-900">
                            Редактирование статьи
                        </p>

                        <span
                            class="rounded-full px-2 py-0.5
                                   text-xs font-semibold"
                            :class="
                                form.status === 'published'
                                    ? 'bg-emerald-100 text-emerald-700'
                                    : 'bg-amber-100 text-amber-700'
                            "
                        >
                            {{
                                form.status === 'published'
                                    ? 'Опубликована'
                                    : 'Черновик'
                            }}
                        </span>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <Link
                        :href="`/companies/${company.id}/knowledge/articles/${article.id}`"
                        class="inline-flex items-center justify-center
                               rounded-xl border border-slate-200
                               bg-white px-4 py-2.5 text-sm
                               font-semibold text-slate-700
                               shadow-sm transition hover:bg-slate-50"
                    >
                        Просмотр
                    </Link>

                    <button
                        type="button"
                        class="inline-flex items-center justify-center
                               rounded-xl border border-slate-200
                               bg-white px-4 py-2.5 text-sm
                               font-semibold text-slate-700
                               shadow-sm transition hover:bg-slate-50
                               disabled:cursor-not-allowed
                               disabled:opacity-50"
                        :disabled="form.processing"
                        @click="save('draft')"
                    >
                        Сохранить черновик
                    </button>

                    <button
                        type="button"
                        class="inline-flex items-center justify-center
                               gap-2 rounded-xl bg-indigo-600
                               px-4 py-2.5 text-sm font-semibold
                               text-white shadow-sm transition
                               hover:bg-indigo-700
                               disabled:cursor-not-allowed
                               disabled:opacity-50"
                        :disabled="form.processing"
                        @click="save('published')"
                    >
                        <svg
                            v-if="form.processing"
                            class="h-4 w-4 animate-spin"
                            viewBox="0 0 24 24"
                            fill="none"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            />

                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 0 1 8-8v4a4 4
                                   0 0 0-4 4H4Z"
                            />
                        </svg>

                        {{
                            form.status === 'published'
                                ? 'Сохранить'
                                : 'Опубликовать'
                        }}
                    </button>

                    
                    <button
    v-if="permissions.delete"
    type="button"
    class="inline-flex items-center justify-center
           gap-2 rounded-xl border border-red-200
           bg-white px-4 py-2.5 text-sm font-semibold
           text-red-600 shadow-sm transition
           hover:bg-red-50 disabled:cursor-not-allowed
           disabled:opacity-50"
    :disabled="form.processing || deleting"
    @click="deleteArticle"
>
    <svg
        v-if="deleting"
        class="h-4 w-4 animate-spin"
        viewBox="0 0 24 24"
        fill="none"
    >
        <circle
            class="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            stroke-width="4"
        />

        <path
            class="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"
        />
    </svg>

    <svg
        v-else
        class="h-4 w-4"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
    >
        <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M6 7h12m-10 0 .75 12h6.5L16 7M10 7V4h4v3"
        />
    </svg>

    {{ deleting ? 'Удаление...' : 'Удалить' }}
</button>
                </div>
            </div>
        </header>

        <main
            class="mx-auto max-w-5xl px-4 py-8
                   sm:px-6 lg:px-8"
        >
            <div
                v-if="Object.keys(form.errors).length"
                class="mb-6 rounded-2xl border border-red-200
                       bg-red-50 p-4"
            >
                <p class="text-sm font-semibold text-red-800">
                    Не удалось сохранить статью
                </p>

                <ul class="mt-2 space-y-1 text-sm text-red-700">
                    <li
                        v-for="(error, field) in form.errors"
                        :key="field"
                    >
                        {{ error }}
                    </li>
                </ul>
            </div>

            <div
                v-if="$page.props.flash?.success"
                class="mb-6 rounded-2xl border border-emerald-200
                       bg-emerald-50 p-4 text-sm font-medium
                       text-emerald-700"
            >
                {{ $page.props.flash.success }}
            </div>

            <section
                class="rounded-3xl border border-slate-200
                       bg-white p-5 shadow-sm sm:p-7"
            >
                <div>
                    <label
                        for="article-title"
                        class="text-sm font-semibold text-slate-700"
                    >
                        Название статьи
                    </label>

                    <input
                        id="article-title"
                        v-model="form.title"
                        type="text"
                        maxlength="255"
                        placeholder="Введите название статьи"
                        class="mt-2 w-full rounded-2xl
                               border border-slate-200 bg-white
                               px-4 py-3 text-2xl font-bold
                               tracking-tight text-slate-900
                               outline-none transition
                               placeholder:text-slate-300
                               focus:border-indigo-500
                               focus:ring-4 focus:ring-indigo-100"
                    >

                    <p
                        v-if="form.errors.title"
                        class="mt-2 text-sm text-red-600"
                    >
                        {{ form.errors.title }}
                    </p>
                </div>

                <div class="mt-6">
                    <label class="text-sm font-semibold text-slate-700">
                        Содержание
                    </label>

                    <div class="mt-2">
                        <ArticleEditor
                            v-model="form.content"
                            @update:text="form.content_text = $event"
                        />
                    </div>

                    <p
                        v-if="form.errors.content"
                        class="mt-2 text-sm text-red-600"
                    >
                        {{ form.errors.content }}
                    </p>
                </div>
            </section>

            <section
                class="mt-6 rounded-3xl border border-slate-200
                       bg-white p-5 shadow-sm sm:p-7"
            >


<div
    v-if="uploadErrors.length"
    class="mt-4 rounded-xl border border-red-200
           bg-red-50 p-4 text-sm text-red-700"
>
    <p
        v-for="(error, index) in uploadErrors"
        :key="index"
    >
        {{ error }}
    </p>
</div>

               <div>
    <input
        ref="fileInput"
        type="file"
        multiple
        class="hidden"
        accept="
    .pdf,
    .doc,.docx,.rtf,.odt,.txt,
    .xls,.xlsx,.ods,.csv,
    .ppt,.pptx,.odp,
    .jpg,.jpeg,.png,.webp,.gif,.svg,
    .mp3,.wav,.ogg,.m4a,.aac,.flac,
    .mp4,.mov,.avi,.mkv,.webm,.m4v,
    .zip,.rar,.7z
"
        @change="handleFileSelection"
    >

    <button
        type="button"
        class="inline-flex items-center justify-center
               gap-2 rounded-xl border border-slate-200
               bg-white px-4 py-2.5 text-sm
               font-semibold text-slate-700
               shadow-sm transition hover:bg-slate-50
               disabled:cursor-not-allowed
               disabled:opacity-50"
        :disabled="uploadingFiles"
        @click="openFileDialog"
    >
        <svg
            v-if="uploadingFiles"
            class="h-5 w-5 animate-spin"
            viewBox="0 0 24 24"
            fill="none"
        >
            <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
            />

            <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 0 1 8-8v4a4 4
                   0 0 0-4 4H4Z"
            />
        </svg>

        <svg
            v-else
            class="h-5 w-5"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M12 16V4m0 0L7 9m5-5 5 5M5 20h14"
            />
        </svg>

        {{ uploadingFiles ? 'Загрузка...' : 'Загрузить файл' }}
    </button>
</div>

                <div
                    v-if="article.files?.length"
                    class="mt-6 divide-y divide-slate-100
                           rounded-2xl border border-slate-200"
                >
                    <div
                        v-for="file in article.files"
                        :key="file.id"
                        class="flex items-center gap-4 p-4"
                    >
                        <div
                            class="flex h-11 w-11 shrink-0
                                   items-center justify-center
                                   rounded-xl bg-indigo-50
                                   text-indigo-600"
                        >
                            <svg
                                class="h-5 w-5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M6 3.75h9L19.5 8.25V20.25
                                       H6V3.75Z"
                                />
                            </svg>
                        </div>

                        <div class="min-w-0 flex-1">
                            <p
                                class="truncate text-sm font-semibold
                                       text-slate-900"
                            >
                                {{ file.original_name }}
                            </p>

                            <p class="mt-1 text-xs text-slate-500">
                                {{ file.mime_type ?? 'Файл' }}
                            </p>
                        </div>

                        <button
    type="button"
    class="inline-flex h-10 w-10 shrink-0
           items-center justify-center rounded-xl
           text-red-600 transition hover:bg-red-50
           disabled:cursor-not-allowed
           disabled:opacity-50"
    :disabled="deletingFile !== null"
    :title="`Удалить ${file.original_name}`"
    @click="deleteFile(file)"
>
    <svg
        v-if="deletingFile === file.id"
        class="h-5 w-5 animate-spin"
        viewBox="0 0 24 24"
        fill="none"
    >
        <circle
            class="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            stroke-width="4"
        />

        <path
            class="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"
        />
    </svg>

    <svg
        v-else
        class="h-5 w-5"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
    >
        <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M6 7h12m-10 0 .75 12h6.5L16 7M10 7V4h4v3"
        />
    </svg>
             </button>           
                    </div>

                    
                </div>

                <div
                    v-else
                    class="mt-6 rounded-2xl border
                           border-dashed border-slate-300
                           px-6 py-10 text-center"
                >
                    <p class="text-sm font-semibold text-slate-700">
                        Файлы пока не прикреплены
                    </p>

                    <p class="mt-1 text-sm text-slate-500">
                        Загрузка документов будет добавлена следующим шагом.
                    </p>
                </div>
            </section>

            <div
                class="mt-6 flex flex-col-reverse gap-3
                       sm:flex-row sm:justify-end"
            >
                <Link
                    :href="`/companies/${company.id}/knowledge/folders/${folder.id}`"
                    class="inline-flex items-center justify-center
                           rounded-xl border border-slate-200
                           bg-white px-5 py-3 text-sm
                           font-semibold text-slate-700
                           shadow-sm transition hover:bg-slate-50"
                >
                    Вернуться в папку
                </Link>

                <button
                    type="button"
                    class="inline-flex items-center justify-center
                           rounded-xl bg-indigo-600 px-5 py-3
                           text-sm font-semibold text-white
                           shadow-sm transition hover:bg-indigo-700
                           disabled:cursor-not-allowed
                           disabled:opacity-50"
                    :disabled="form.processing"
                    @click="save()"
                >
                    Сохранить изменения
                </button>
            </div>
        </main>
    </div>
</template>