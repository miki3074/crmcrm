<script setup>
import { computed, ref,  onBeforeUnmount } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { VueFilesPreview } from 'vue-files-preview';
import 'vue-files-preview/lib/style.css';

import CreateFolderModal from './component/CreateFolderModal.vue';





const props = defineProps({
    company: {
        type: Object,
        required: true,
    },
    folder: {
        type: Object,
        required: true,
    },
    breadcrumbs: {
        type: Array,
        default: () => [],
    },
    children: {
        type: Array,
        default: () => [],
    },
    articles: {
        type: Array,
        default: () => [],
    },
    files: {
        type: Array,
        default: () => [],
    },
    members: {
        type: Array,
        default: () => [],
    },
    permissions: {
        type: Object,
        default: () => ({
            view: false,
            create_content: false,
            manage_access: false,
            delete_folder: false,
        }),
    },
});

const search = ref('');
const createFolderModalOpen = ref(false);

const fileInput = ref(null);
const uploadingFiles = ref(false);
const uploadProgress = ref(0);
const fileUploadError = ref('');

const normalizedSearch = computed(() => search.value.trim().toLowerCase());

const filteredChildren = computed(() => {
    if (!normalizedSearch.value) return props.children;

    return props.children.filter((item) =>
        item.name.toLowerCase().includes(normalizedSearch.value),
    );
});

const filteredArticles = computed(() => {
    if (!normalizedSearch.value) return props.articles;

    return props.articles.filter((item) =>
        item.title.toLowerCase().includes(normalizedSearch.value),
    );
});

const filteredFiles = computed(() => {
    if (!normalizedSearch.value) return props.files;

    return props.files.filter((item) =>
        item.original_name.toLowerCase().includes(normalizedSearch.value),
    );
});

const totalItems = computed(() =>
    props.children.length + props.articles.length + props.files.length,
);

function openCreateFolderModal() {
    createFolderModalOpen.value = true;
}

function closeCreateFolderModal() {
    createFolderModalOpen.value = false;
}


const createArticleUrl = computed(() => {
    return `/companies/${props.company.id}/knowledge/folders/${props.folder.id}/articles/create`;
});

function fileSize(bytes) {
    if (!bytes) return '0 Б';

    const units = ['Б', 'КБ', 'МБ', 'ГБ'];
    const index = Math.min(
        Math.floor(Math.log(bytes) / Math.log(1024)),
        units.length - 1,
    );

    return `${(bytes / (1024 ** index)).toFixed(index === 0 ? 0 : 1)} ${units[index]}`;
}


const deletingFolder = ref(false);


function openFilePicker() {
    if (
        uploadingFiles.value ||
        !props.permissions.create_content
    ) {
        return;
    }

    fileInput.value?.click();
}

function uploadFiles(event) {
    const selectedFiles = Array.from(
        event.target.files ?? [],
    );

    if (selectedFiles.length === 0) {
        return;
    }

    fileUploadError.value = '';
    uploadingFiles.value = true;
    uploadProgress.value = 0;

    const formData = new FormData();

    selectedFiles.forEach((file) => {
        formData.append('files[]', file);
    });

    router.post(
        `/companies/${props.company.id}/knowledge/folders/${props.folder.id}/files`,
        formData,
        {
            forceFormData: true,
            preserveScroll: true,

            onProgress: (progress) => {
                uploadProgress.value = progress?.percentage ?? 0;
            },

            onSuccess: () => {
                if (fileInput.value) {
                    fileInput.value.value = '';
                }
            },

            onError: (errors) => {
                fileUploadError.value =
                    errors.files
                    || errors['files.0']
                    || 'Не удалось загрузить файлы.';
            },

            onFinish: () => {
                uploadingFiles.value = false;
                uploadProgress.value = 0;
            },
        },
    );
}

const previewFile = ref(null);
const previewSource = ref(null);

const previewOpen = ref(false);
const previewLoading = ref(false);
const previewError = ref('');

const previewAbortController = ref(null);

function getPreviewFileName(file) {
    let name = String(
        file?.original_name
        || file?.name
        || `file-${file?.id ?? Date.now()}`,
    ).trim();

    /*
     * Если в имени уже присутствует расширение,
     * оставляем имя без изменений.
     */
    if (/\.[a-z0-9]+$/i.test(name)) {
        return name;
    }

    const extension = String(
        file?.extension || '',
    )
        .trim()
        .replace(/^\./, '');

    if (extension) {
        name = `${name}.${extension}`;
    }

    return name;
}

async function openFilePreview(file) {
    previewAbortController.value?.abort();

    previewFile.value = file;
    previewSource.value = null;

    previewError.value = '';
    previewLoading.value = true;
    previewOpen.value = true;

    const controller = new AbortController();

    previewAbortController.value = controller;

    try {
        const response = await fetch(
            file.preview_url,
            {
                method: 'GET',
                credentials: 'same-origin',
                signal: controller.signal,

                headers: {
                    Accept:
                        file.mime_type
                        || 'application/octet-stream',

                    'X-Requested-With': 'XMLHttpRequest',
                },
            },
        );

        if (!response.ok) {
            if (response.status === 401) {
                throw new Error(
                    'Сессия истекла. Обновите страницу.',
                );
            }

            if (response.status === 403) {
                throw new Error(
                    'У вас нет доступа к этому файлу.',
                );
            }

            if (response.status === 404) {
                throw new Error(
                    'Файл не найден на сервере.',
                );
            }

            throw new Error(
                `Сервер вернул ошибку ${response.status}.`,
            );
        }

        const contentType =
            response.headers.get('content-type')
            || file.mime_type
            || 'application/octet-stream';

        /*
         * Laravel при ошибке иногда может вернуть HTML-страницу
         * с кодом 200. Такой ответ нельзя передавать как DOCX/XLSX.
         */
        if (
            contentType.includes('text/html')
            || contentType.includes('application/json')
        ) {
            const responseText = await response.text();

            console.error(
                'Вместо файла сервер вернул текст:',
                responseText.slice(0, 500),
            );

            throw new Error(
                'Сервер вернул страницу ошибки вместо файла.',
            );
        }

        const blob = await response.blob();

        console.log('Получен файл для предпросмотра:', {
            name: file.original_name,
            declaredSize: file.size,
            receivedSize: blob.size,
            contentType,
            previewUrl: file.preview_url,
        });

        /*
         * Именно это условие объясняет вашу ошибку:
         * data length = 0.
         */
        if (blob.size === 0) {
            throw new Error(
                'Сервер вернул пустой файл размером 0 байт.',
            );
        }

        /*
         * Дополнительная проверка: если в базе указан размер,
         * а сервер прислал существенно меньше данных.
         */
        if (
            Number(file.size) > 0
            && blob.size < Number(file.size)
        ) {
            console.warn(
                'Полученный файл меньше размера из базы:',
                {
                    expected: Number(file.size),
                    received: blob.size,
                },
            );
        }

        const fileName = getPreviewFileName(file);

        previewSource.value = new File(
            [blob],
            fileName,
            {
                type: contentType,
                lastModified: Date.now(),
            },
        );
    } catch (error) {
        if (error?.name === 'AbortError') {
            return;
        }

        previewError.value =
            error?.message
            || 'Не удалось загрузить файл для просмотра.';

        console.error(
            'Ошибка загрузки предпросмотра:',
            error,
        );
    } finally {
        if (
            previewAbortController.value
            === controller
        ) {
            previewAbortController.value = null;
        }

        previewLoading.value = false;
    }
}

function closeFilePreview() {
    previewAbortController.value?.abort();
    previewAbortController.value = null;

    previewOpen.value = false;
    previewLoading.value = false;
    previewError.value = '';

    previewFile.value = null;
    previewSource.value = null;
}

function handlePreviewRendered() {
    previewLoading.value = false;
}

function handlePreviewError(error) {
    previewLoading.value = false;

    previewError.value =
        error?.message
        || 'Формат файла не поддерживается или файл повреждён.';

    console.error(
        'Ошибка обработки предпросмотра:',
        error,
    );
}

onBeforeUnmount(() => {
    previewAbortController.value?.abort();
});


function deleteFolder() {
    if (
        deletingFolder.value ||
        !props.permissions.delete_folder
    ) {
        return;
    }

    const confirmed = window.confirm(
        `Удалить папку «${props.folder.name}» вместе со всем содержимым?`
    );

    if (!confirmed) {
        return;
    }

    deletingFolder.value = true;

    router.delete(
        `/companies/${props.company.id}/knowledge/folders/${props.folder.id}`,
        {
            preserveScroll: true,

            onError: (errors) => {
                console.error(
                    'Не удалось удалить папку:',
                    errors
                );
            },

            onFinish: () => {
                deletingFolder.value = false;
            },
        }
    );
}

function deleteFile(file) {
    const confirmed = window.confirm(
        `Удалить файл «${file.original_name}»?`,
    );

    if (!confirmed) {
        return;
    }

    router.delete(file.delete_url, {
        preserveScroll: true,

        onBefore: () => {
            /*
             * Необязательно, но можно здесь
             * показать состояние загрузки.
             */
        },

        onError: (errors) => {
            console.error(
                'Ошибка удаления файла:',
                errors,
            );
        },
    });
}


</script>

<template>
    <Head :title="`${folder.name} — ${company.name}`" />

    <div class="min-h-screen bg-slate-50 text-slate-900">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                <Link
                    :href="`/companies/${company.id}/knowledge`"
                    class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-indigo-700"
                >
                    <span aria-hidden="true">←</span>
                    База знаний
                </Link>

                <div class="flex items-center gap-2">
                    <button
                        v-if="permissions.create_content"
                        type="button"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        @click="openCreateFolderModal"
                    >
                        Новая папка
                    </button>

                    <Link
    v-if="permissions.create_content"
    :href="createArticleUrl"
    class="inline-flex items-center justify-center gap-2
           rounded-xl bg-indigo-600 px-4 py-2.5
           text-sm font-semibold text-white
           shadow-sm transition hover:bg-indigo-700"
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
            d="M12 5v14M5 12h14"
        />
    </svg>

    Создать статью
</Link>

<button
    v-if="permissions.create_content"
    type="button"
    class="inline-flex items-center justify-center gap-2
           rounded-xl border border-slate-200 bg-white
           px-4 py-2.5 text-sm font-semibold text-slate-700
           shadow-sm transition hover:bg-slate-50
           disabled:cursor-not-allowed disabled:opacity-50"
    :disabled="uploadingFiles"
    @click="openFilePicker"
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
            d="M4 12a8 8 0 0 1 8-8v4
               a4 4 0 0 0-4 4H4Z"
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
            d="M12 16V4m0 0L7 9m5-5 5 5"
        />

        <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M5 15v4h14v-4"
        />
    </svg>

    <span v-if="uploadingFiles">
        Загрузка {{ uploadProgress }}%
    </span>

    <span v-else>
        Добавить файлы
    </span>
</button>

<input
    ref="fileInput"
    type="file"
    multiple
    class="hidden"
    :disabled="uploadingFiles"
    @change="uploadFiles"
>

<Link
    v-if="permissions.manage_access"
    :href="`/companies/${company.id}/knowledge/folders/${folder.id}/access`"
    class="inline-flex items-center justify-center
           gap-2 rounded-xl border border-slate-200
           bg-white px-4 py-2.5 text-sm font-semibold
           text-slate-700 shadow-sm transition
           hover:bg-slate-50"
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
            d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"
        />

        <circle cx="9" cy="7" r="4" />

        <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M19 8v6m3-3h-6"
        />
    </svg>

    Доступ
</Link>


<button
    v-if="permissions.delete_folder"
    type="button"
    class="inline-flex items-center justify-center gap-2
           rounded-xl border border-red-200 bg-white
           px-4 py-2.5 text-sm font-semibold text-red-600
           transition hover:bg-red-50
           disabled:cursor-not-allowed disabled:opacity-50"
    :disabled="deletingFolder"
    @click="deleteFolder"
>
    <svg
        v-if="deletingFolder"
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
            d="M4 12a8 8 0 0 1 8-8v4
               a4 4 0 0 0-4 4H4Z"
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
            d="M6 7h12m-10 0 .75 12h6.5
               L16 7M10 7V4h4v3"
        />
    </svg>

    {{ deletingFolder ? 'Удаление...' : 'Удалить папку' }}
</button>

                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <nav class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
                <Link
                    :href="`/companies/${company.id}/knowledge`"
                    class="hover:text-indigo-700"
                >
                    База знаний
                </Link>

                <template v-for="crumb in breadcrumbs" :key="crumb.id">
                    <span>/</span>
                    <Link
                        :href="`/companies/${company.id}/knowledge/folders/${crumb.id}`"
                        class="max-w-48 truncate hover:text-indigo-700"
                    >
                        {{ crumb.name }}
                    </Link>
                </template>
            </nav>

            <section class="mt-5 rounded-3xl bg-slate-950 px-6 py-8 text-white shadow-xl sm:px-8">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-400/15 text-amber-300">
                                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 5.25A2.25 2.25 0 0 1 6 3h3.75L12 5.25h6A2.25 2.25 0 0 1 20.25 7.5v9A2.25 2.25 0 0 1 18 18.75H6a2.25 2.25 0 0 1-2.25-2.25V5.25Z" />
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold sm:text-3xl">{{ folder.name }}</h1>
                                <p class="mt-1 text-sm text-slate-300">
                                    {{ totalItems }} материалов · роль: {{ folder.effective_role_label }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="w-full max-w-md">
                        <input
                            v-model="search"
                            type="search"
                            placeholder="Поиск в папке..."
                            class="h-11 w-full rounded-xl border-0 bg-white/10 px-4 text-sm text-white outline-none ring-1 ring-inset ring-white/15 placeholder:text-slate-400 focus:ring-2 focus:ring-indigo-400"
                        >
                    </div>
                </div>
            </section>

            <section v-if="filteredChildren.length" class="mt-7">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold">Вложенные папки</h2>
                    <span class="text-sm text-slate-500">{{ filteredChildren.length }}</span>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <Link
                        v-for="child in filteredChildren"
                        :key="child.id"
                        :href="`/companies/${company.id}/knowledge/folders/${child.id}`"
                        class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-md"
                    >
                        <div class="flex items-start gap-4">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600">📁</div>
                            <div class="min-w-0">
                                <h3 class="truncate font-semibold group-hover:text-indigo-700">{{ child.name }}</h3>
                                <p class="mt-2 text-sm text-slate-500">
                                    {{ child.children_count ?? 0 }} папок · {{ child.articles_count ?? 0 }} статей · {{ child.files_count ?? 0 }} файлов
                                </p>
                            </div>
                        </div>
                    </Link>
                </div>
            </section>

            <section v-if="filteredArticles.length" class="mt-7">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold">Статьи</h2>
                    <span class="text-sm text-slate-500">{{ filteredArticles.length }}</span>
                </div>

                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <Link
                        v-for="article in filteredArticles"
                        :key="article.id"
                        :href="`/companies/${company.id}/knowledge/articles/${article.id}`"
                        class="flex items-center gap-4 border-b border-slate-100 px-5 py-4 last:border-b-0 hover:bg-slate-50"
                    >
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">📄</div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold">{{ article.title }}</p>
                            <p class="mt-1 truncate text-sm text-slate-500">{{ article.content_text || 'Без описания' }}</p>
                        </div>
                    </Link>
                </div>
            </section>

          <section
    v-if="filteredFiles.length"
    class="mt-7"
>
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-lg font-semibold">
            Файлы
        </h2>

        <span class="text-sm text-slate-500">
            {{ filteredFiles.length }}
        </span>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <article
            v-for="file in filteredFiles"
            :key="file.id"
            class="rounded-2xl border border-slate-200
                   bg-white p-5 shadow-sm transition
                   hover:border-indigo-200 hover:shadow-md"
        >
            <div class="flex items-start gap-4">
                <div
                    class="flex h-11 w-11 shrink-0 items-center
                           justify-center rounded-xl
                           bg-emerald-50 text-emerald-600"
                >
                    📎
                </div>

                <div class="min-w-0 flex-1">
                    <p
                        class="truncate font-semibold"
                        :title="file.original_name"
                    >
                        {{ file.original_name }}
                    </p>

                    <p class="mt-1 text-sm text-slate-500">
                        {{ file.extension?.toUpperCase() || 'Файл' }}
                        ·
                        {{ fileSize(file.size) }}
                    </p>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <button
                            type="button"
                            class="inline-flex items-center rounded-lg
                                   bg-indigo-600 px-3 py-2 text-xs
                                   font-semibold text-white
                                   transition hover:bg-indigo-700"
                            @click="openFilePreview(file)"
                        >
                            Просмотреть
                        </button>

                        <a
                            :href="file.download_url"
                            class="inline-flex items-center rounded-lg
                                   border border-slate-200 bg-white
                                   px-3 py-2 text-xs font-semibold
                                   text-slate-700 transition
                                   hover:bg-slate-50"
                        >
                            Скачать
                        </a>

                        <button
                            v-if="file.can_delete"
                            type="button"
                            class="inline-flex items-center rounded-lg
                                   border border-red-200 bg-white
                                   px-3 py-2 text-xs font-semibold
                                   text-red-600 transition
                                   hover:bg-red-50"
                            @click="deleteFile(file)"
                        >
                            Удалить
                        </button>
                    </div>
                </div>
            </div>
        </article>
    </div>
</section>

            <section
                v-if="!filteredChildren.length && !filteredArticles.length && !filteredFiles.length"
                class="mt-7 rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center"
            >
                <h2 class="text-lg font-semibold">В этой папке пока ничего нет</h2>
                <p class="mt-2 text-sm text-slate-500">
                    Создайте вложенную папку, статью или загрузите файл.
                </p>
            </section>
        </main>

        <CreateFolderModal
            :open="createFolderModalOpen"
            :company="company"
            :members="members"
            :parent-folder="folder"
            @close="closeCreateFolderModal"
        />
    </div>


<Teleport to="body">
    <div
        v-if="previewOpen && previewFile"
        class="fixed inset-0 z-[100] flex items-center
               justify-center bg-slate-950/75 p-3
               backdrop-blur-sm sm:p-6"
        @click.self="closeFilePreview"
    >
        <div
            class="flex h-[92vh] w-full max-w-7xl
                   flex-col overflow-hidden rounded-2xl
                   bg-white shadow-2xl"
        >
            <header
                class="flex shrink-0 items-center
                       justify-between gap-4 border-b
                       border-slate-200 px-5 py-4"
            >
                <div class="min-w-0">
                    <h2 class="truncate font-semibold text-slate-900">
                        {{ previewFile.original_name }}
                    </h2>

                    <p class="mt-1 text-xs text-slate-500">
                        {{ previewFile.extension?.toUpperCase() || 'Файл' }}
                        ·
                        {{ fileSize(previewFile.size) }}
                    </p>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <a
                        :href="previewFile.download_url"
                        class="rounded-lg border border-slate-200
                               px-3 py-2 text-sm font-semibold
                               text-slate-700 hover:bg-slate-50"
                    >
                        Скачать
                    </a>

                    <button
                        type="button"
                        class="flex h-10 w-10 items-center
                               justify-center rounded-lg
                               text-2xl text-slate-500
                               hover:bg-slate-100
                               hover:text-slate-900"
                        aria-label="Закрыть просмотр"
                        @click="closeFilePreview"
                    >
                        ×
                    </button>
                </div>
            </header>

            <div class="relative min-h-0 flex-1 bg-slate-100">
                <div
                    v-if="previewLoading"
                    class="absolute inset-0 z-10 flex
                           items-center justify-center bg-white"
                >
                    <div class="text-center">
                        <svg
                            class="mx-auto h-8 w-8 animate-spin
                                   text-indigo-600"
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
                                d="M4 12a8 8 0 0 1 8-8v4
                                   a4 4 0 0 0-4 4H4Z"
                            />
                        </svg>

                        <p class="mt-3 text-sm text-slate-600">
                            Подготовка предпросмотра…
                        </p>
                    </div>
                </div>

                <div
                    v-if="previewError"
                    class="flex h-full items-center
                           justify-center p-8"
                >
                    <div class="max-w-md text-center">
                        <p class="font-semibold text-red-700">
                            Не удалось показать файл
                        </p>

                        <p class="mt-2 text-sm text-slate-600">
                            {{ previewError }}
                        </p>

                        <a
                            :href="previewFile.download_url"
                            class="mt-5 inline-flex rounded-lg
                                   bg-indigo-600 px-4 py-2
                                   text-sm font-semibold text-white
                                   hover:bg-indigo-700"
                        >
                            Скачать файл
                        </a>
                    </div>
                </div>

               <VueFilesPreview
    v-else-if="previewSource"
    :key="`${previewFile.id}-${previewSource.name}`"
    :file="previewSource"
    :name="previewSource.name"
    width="100%"
    height="100%"
    overflow="auto"
    @rendered="handlePreviewRendered"
    @error="handlePreviewError"
/>

<div
    v-else
    class="flex h-full items-center justify-center p-8"
>
    <p class="text-sm text-slate-500">
        Загрузка файла…
    </p>
</div>
            </div>
        </div>
    </div>
</Teleport>

</template>
