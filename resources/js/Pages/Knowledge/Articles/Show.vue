<script setup>
import {
    computed,
    onBeforeUnmount,
    onMounted,
    ref,
} from 'vue';

import { Head, Link } from '@inertiajs/vue3';

import { Editor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import LinkExtension from '@tiptap/extension-link';

import { VueFilesPreview } from 'vue-files-preview';
import 'vue-files-preview/lib/style.css';

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
            edit: false,
        }),
    },
});

/*
|--------------------------------------------------------------------------
| Предпросмотр файлов
|--------------------------------------------------------------------------
*/

const previewFile = ref(null);
const previewSource = ref(null);

const previewOpen = ref(false);
const previewLoading = ref(false);
const previewError = ref('');

const previewAbortController = ref(null);

/*
|--------------------------------------------------------------------------
| Редактор статьи
|--------------------------------------------------------------------------
*/

function normalizeContent(content) {
    if (
        content
        && typeof content === 'object'
        && content.type === 'doc'
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

const editor = new Editor({
    editable: false,

    extensions: [
        StarterKit,
        Underline,

        LinkExtension.configure({
            openOnClick: true,
            autolink: true,
            defaultProtocol: 'https',

            HTMLAttributes: {
                class: 'text-indigo-600 underline hover:text-indigo-800',
                rel: 'noopener noreferrer',
                target: '_blank',
            },
        }),
    ],

    content: normalizeContent(props.article.content),

    editorProps: {
        attributes: {
            class: [
                'prose',
                'prose-slate',
                'max-w-none',
                'focus:outline-none',
            ].join(' '),
        },
    },
});

/*
|--------------------------------------------------------------------------
| Вычисляемые значения
|--------------------------------------------------------------------------
*/

const statusLabel = computed(() => {
    return props.article.status === 'published'
        ? 'Опубликована'
        : 'Черновик';
});

const formattedUpdatedAt = computed(() => {
    if (!props.article.updated_at) {
        return null;
    }

    return new Intl.DateTimeFormat('ru-RU', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(props.article.updated_at));
});

/*
|--------------------------------------------------------------------------
| Работа с файлами
|--------------------------------------------------------------------------
*/

function formatFileSize(size) {
    const bytes = Number(size);

    if (!Number.isFinite(bytes) || bytes <= 0) {
        return '';
    }

    const units = [
        'Б',
        'КБ',
        'МБ',
        'ГБ',
    ];

    let value = bytes;
    let unitIndex = 0;

    while (
        value >= 1024
        && unitIndex < units.length - 1
    ) {
        value /= 1024;
        unitIndex += 1;
    }

    const precision = unitIndex === 0 ? 0 : 1;

    return `${value.toFixed(precision)} ${units[unitIndex]}`;
}

function fileDownloadUrl(file) {
    return file?.download_url
        ?? `/companies/${props.company.id}`
        + `/knowledge/folders/${props.folder.id}`
        + `/files/${file.id}/download`;
}

function filePreviewUrl(file) {
    return file?.preview_url
        ?? `/companies/${props.company.id}`
        + `/knowledge/folders/${props.folder.id}`
        + `/files/${file.id}/preview`;
}

function getPreviewFileName(file) {
    const originalName = String(
        file?.original_name
        || file?.name
        || `file-${file?.id ?? Date.now()}`,
    ).trim();

    /*
     * Если исходное имя уже содержит расширение,
     * возвращаем его без изменений.
     */
    if (/\.[a-z0-9]+$/i.test(originalName)) {
        return originalName;
    }

    const extension = String(
        file?.extension || '',
    )
        .trim()
        .replace(/^\./, '');

    if (extension) {
        return `${originalName}.${extension}`;
    }

    return originalName;
}

async function openFilePreview(file) {
    /*
     * Отменяем предыдущую загрузку, если пользователь
     * быстро выбрал другой файл.
     */
    previewAbortController.value?.abort();

    previewFile.value = file;
    previewSource.value = null;

    previewOpen.value = true;
    previewLoading.value = true;
    previewError.value = '';

    document.body.classList.add('overflow-hidden');

    const controller = new AbortController();

    previewAbortController.value = controller;

    try {
        const response = await fetch(
            filePreviewUrl(file),
            {
                method: 'GET',
                credentials: 'same-origin',
                signal: controller.signal,

                headers: {
                    Accept:
                        file?.mime_type
                        || 'application/octet-stream',

                    'X-Requested-With': 'XMLHttpRequest',
                },
            },
        );

        if (!response.ok) {
            if (response.status === 403) {
                throw new Error(
                    'У вас нет доступа к этому файлу.',
                );
            }

            if (response.status === 404) {
                throw new Error(
                    'Файл не найден.',
                );
            }

            throw new Error(
                `Не удалось загрузить файл. Код: ${response.status}.`,
            );
        }

        const blob = await response.blob();

        if (!blob.size) {
            throw new Error(
                'Сервер вернул пустой файл.',
            );
        }

        const fileName = getPreviewFileName(file);

        previewSource.value = new File(
            [blob],
            fileName,
            {
                type:
                    blob.type
                    || file?.mime_type
                    || 'application/octet-stream',

                lastModified: Date.now(),
            },
        );

        /*
         * Файл загружен, но VueFilesPreview ещё может
         * выполнять обработку PDF, DOCX и других форматов.
         */
        previewLoading.value = true;
    } catch (error) {
        if (error?.name === 'AbortError') {
            return;
        }

        previewLoading.value = false;

        previewError.value =
            error?.message
            || 'Не удалось загрузить файл для просмотра.';

        console.error(
            'Ошибка загрузки файла для просмотра:',
            error,
        );
    } finally {
        if (
            previewAbortController.value
            === controller
        ) {
            previewAbortController.value = null;
        }
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

    document.body.classList.remove('overflow-hidden');
}

function handlePreviewRendered() {
    previewLoading.value = false;
}

function handlePreviewError(error) {
    previewLoading.value = false;

    previewError.value =
        error?.message
        || 'Формат файла не поддерживается для просмотра.';

    console.error(
        'Ошибка просмотра файла:',
        error,
    );
}

function handleKeydown(event) {
    if (
        event.key === 'Escape'
        && previewOpen.value
    ) {
        closeFilePreview();
    }
}

/*
|--------------------------------------------------------------------------
| Жизненный цикл
|--------------------------------------------------------------------------
*/

onMounted(() => {
    window.addEventListener(
        'keydown',
        handleKeydown,
    );
});

onBeforeUnmount(() => {
    editor.destroy();

    previewAbortController.value?.abort();

    window.removeEventListener(
        'keydown',
        handleKeydown,
    );

    document.body.classList.remove('overflow-hidden');
});
</script>

<template>
    <Head :title="`${article.title} — ${company.name}`" />

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
                        :href="
                            `/companies/${company.id}`
                            + `/knowledge/folders/${folder.id}`
                        "
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

                    <p class="mt-1 truncate text-sm text-slate-500">
                        {{ company.name }}
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        v-if="permissions.edit"
                        :href="
                            `/companies/${company.id}`
                            + `/knowledge/articles/${article.id}/edit`
                        "
                        class="inline-flex items-center justify-center
                               gap-2 rounded-xl bg-indigo-600
                               px-4 py-2.5 text-sm font-semibold
                               text-white shadow-sm transition
                               hover:bg-indigo-700"
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
                                d="m16.862 4.487 2.651 2.651M18.375
                                   2.975a1.875 1.875 0 0 1 2.65
                                   2.65L8.25 18.4 3.75 19.5l1.1-4.5
                                   13.525-12.025Z"
                            />
                        </svg>

                        Редактировать
                    </Link>
                </div>
            </div>
        </header>

        <main
            class="mx-auto max-w-5xl px-4 py-8
                   sm:px-6 lg:px-8 lg:py-12"
        >
            <article
                class="rounded-3xl border border-slate-200
                       bg-white p-6 shadow-sm sm:p-8 lg:p-12"
            >
                <div
                    class="flex flex-col gap-5 border-b
                           border-slate-100 pb-8"
                >
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            class="rounded-full px-3 py-1
                                   text-xs font-semibold"
                            :class="
                                article.status === 'published'
                                    ? 'bg-emerald-100 text-emerald-700'
                                    : 'bg-amber-100 text-amber-700'
                            "
                        >
                            {{ statusLabel }}
                        </span>

                        <span
                            v-if="article.creator?.name"
                            class="text-xs text-slate-500"
                        >
                            Автор: {{ article.creator.name }}
                        </span>
                    </div>

                    <h1
                        class="text-3xl font-bold tracking-tight
                               text-slate-950 sm:text-4xl"
                    >
                        {{ article.title }}
                    </h1>

                    <div
                        class="flex flex-wrap items-center gap-x-5
                               gap-y-2 text-sm text-slate-500"
                    >
                        <span v-if="article.updater?.name">
                            Изменил: {{ article.updater.name }}
                        </span>

                        <span v-if="formattedUpdatedAt">
                            Обновлено: {{ formattedUpdatedAt }}
                        </span>
                    </div>
                </div>

                <div class="mt-8">
                    <EditorContent :editor="editor" />
                </div>

                <div
                    v-if="
                        !article.content?.content?.length
                        && article.content_text
                    "
                    class="mt-8 whitespace-pre-wrap leading-7
                           text-slate-700"
                >
                    {{ article.content_text }}
                </div>

                <div
                    v-if="
                        !article.content_text
                        && !article.content?.content?.length
                    "
                    class="mt-8 rounded-2xl border
                           border-dashed border-slate-300
                           px-6 py-12 text-center"
                >
                    <p class="text-sm font-semibold text-slate-700">
                        В статье пока нет содержимого
                    </p>
                </div>
            </article>

            <section
                v-if="article.files?.length"
                class="mt-6 rounded-3xl border border-slate-200
                       bg-white p-6 shadow-sm sm:p-8"
            >
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">
                        Прикреплённые файлы
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Документы и материалы этой статьи.
                    </p>
                </div>

                <div
                    class="mt-5 divide-y divide-slate-100
                           overflow-hidden rounded-2xl
                           border border-slate-200"
                >
                    <div
                        v-for="file in article.files"
                        :key="file.id"
                        class="flex flex-col gap-4 p-4
                               sm:flex-row sm:items-center"
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
                                :title="file.original_name"
                            >
                                {{ file.original_name }}
                            </p>

                            <div
                                class="mt-1 flex flex-wrap gap-x-3
                                       text-xs text-slate-500"
                            >
                                <span v-if="file.extension">
                                    {{ file.extension.toUpperCase() }}
                                </span>

                                <span v-if="file.size">
                                    {{ formatFileSize(file.size) }}
                                </span>
                            </div>
                        </div>

                        <div
                            class="flex shrink-0 flex-wrap
                                   items-center gap-2"
                        >
                            <button
                                type="button"
                                class="rounded-xl bg-indigo-600
                                       px-3 py-2 text-sm font-semibold
                                       text-white transition
                                       hover:bg-indigo-700"
                                @click="openFilePreview(file)"
                            >
                                Просмотреть
                            </button>

                            <a
                                :href="fileDownloadUrl(file)"
                                class="rounded-xl border
                                       border-slate-200 bg-white
                                       px-3 py-2 text-sm font-semibold
                                       text-slate-700 transition
                                       hover:bg-slate-50"
                            >
                                Скачать
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <Teleport to="body">
            <div
                v-if="previewOpen && previewFile"
                class="fixed inset-0 z-[100]
                       flex items-center justify-center
                       bg-slate-950/75 p-3
                       backdrop-blur-sm sm:p-6"
                @click.self="closeFilePreview"
            >
                <div
                    class="flex h-[92vh] w-full max-w-7xl
                           flex-col overflow-hidden
                           rounded-2xl bg-white shadow-2xl"
                    role="dialog"
                    aria-modal="true"
                    :aria-label="
                        `Просмотр файла ${previewFile.original_name}`
                    "
                >
                    <header
                        class="flex shrink-0 items-center
                               justify-between gap-4
                               border-b border-slate-200
                               px-5 py-4"
                    >
                        <div class="min-w-0">
                            <h2
                                class="truncate font-semibold
                                       text-slate-900"
                            >
                                {{ previewFile.original_name }}
                            </h2>

                            <p class="mt-1 text-xs text-slate-500">
                                <span v-if="previewFile.extension">
                                    {{
                                        previewFile.extension.toUpperCase()
                                    }}
                                </span>

                                <span v-if="previewFile.size">
                                    ·
                                    {{
                                        formatFileSize(
                                            previewFile.size,
                                        )
                                    }}
                                </span>
                            </p>
                        </div>

                        <div
                            class="flex shrink-0 items-center gap-2"
                        >
                            <a
                                :href="fileDownloadUrl(previewFile)"
                                class="rounded-lg border
                                       border-slate-200 px-3 py-2
                                       text-sm font-semibold
                                       text-slate-700
                                       hover:bg-slate-50"
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

                    <div
                        class="relative min-h-0 flex-1
                               overflow-hidden bg-slate-100"
                    >
                        <!-- Ошибка -->
                        <div
                            v-if="previewError"
                            class="flex h-full items-center
                                   justify-center p-8"
                        >
                            <div class="max-w-md text-center">
                                <p
                                    class="font-semibold
                                           text-red-700"
                                >
                                    Не удалось показать файл
                                </p>

                                <p
                                    class="mt-2 text-sm
                                           text-slate-600"
                                >
                                    {{ previewError }}
                                </p>

                                <a
                                    :href="
                                        fileDownloadUrl(previewFile)
                                    "
                                    class="mt-5 inline-flex
                                           rounded-lg bg-indigo-600
                                           px-4 py-2 text-sm
                                           font-semibold text-white
                                           hover:bg-indigo-700"
                                >
                                    Скачать файл
                                </a>
                            </div>
                        </div>

                        <!-- Компонент предпросмотра -->
                        <VueFilesPreview
                            v-else-if="previewSource"
                            :key="
                                `${previewFile.id}`
                                + `-${previewSource.name}`
                            "
                            :file="previewSource"
                            :name="previewSource.name"
                            width="100%"
                            height="100%"
                            overflow="auto"
                            @rendered="handlePreviewRendered"
                            @error="handlePreviewError"
                        />

                        <!-- Первичная загрузка файла -->
                        <div
                            v-else
                            class="flex h-full items-center
                                   justify-center bg-white"
                        >
                            <div class="text-center">
                                <svg
                                    class="mx-auto h-8 w-8
                                           animate-spin
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

                                <p
                                    class="mt-3 text-sm
                                           text-slate-600"
                                >
                                    Загрузка файла…
                                </p>
                            </div>
                        </div>

                        <!-- Обработка загруженного файла -->
                        <div
                            v-if="
                                previewLoading
                                && previewSource
                                && !previewError
                            "
                            class="pointer-events-none
                                   absolute inset-0 z-10
                                   flex items-center justify-center
                                   bg-white/90"
                        >
                            <div class="text-center">
                                <svg
                                    class="mx-auto h-8 w-8
                                           animate-spin
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

                                <p
                                    class="mt-3 text-sm
                                           text-slate-600"
                                >
                                    Подготовка предпросмотра…
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>