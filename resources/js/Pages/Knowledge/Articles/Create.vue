<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
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
});

const form = useForm({
    title: props.article.title ?? '',

    content: props.article.content ?? {
        type: 'doc',
        content: [],
    },

    content_text: props.article.content_text ?? '',

    status: props.article.status ?? 'draft',
});

function save(status = 'draft') {
    form.status = status;

    form.post(
        `/companies/${props.company.id}/knowledge/folders/${props.folder.id}/articles`,
        {
            preserveScroll: true,
        }
    );
}
</script>

<template>
    <Head :title="`Новая статья — ${folder.name}`" />

    <div class="min-h-screen bg-slate-50">
        <header
            class="sticky top-0 z-30 border-b border-slate-200
                   bg-white/90 backdrop-blur-xl"
        >
            <div
                class="mx-auto flex h-20 max-w-7xl items-center
                       justify-between gap-4 px-4 sm:px-6 lg:px-8"
            >
                <div class="min-w-0">
                    <Link
                        :href="`/companies/${company.id}/knowledge/folders/${folder.id}`"
                        class="text-sm font-medium text-slate-500
                               hover:text-indigo-600"
                    >
                        ← {{ folder.name }}
                    </Link>

                    <p class="mt-1 text-sm font-semibold text-slate-900">
                        Новая статья
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="rounded-xl border border-slate-200
                               bg-white px-4 py-2.5 text-sm
                               font-semibold text-slate-700
                               hover:bg-slate-50"
                        :disabled="form.processing"
                        @click="save('draft')"
                    >
                        Сохранить черновик
                    </button>

                    <button
                        type="button"
                        class="rounded-xl bg-indigo-600 px-4 py-2.5
                               text-sm font-semibold text-white
                               hover:bg-indigo-700
                               disabled:opacity-50"
                        :disabled="form.processing"
                        @click="save('published')"
                    >
                        Опубликовать
                    </button>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
            <div
                v-if="Object.keys(form.errors).length"
                class="mb-5 rounded-2xl border border-red-200
                       bg-red-50 p-4 text-sm text-red-700"
            >
                <p
                    v-for="(error, field) in form.errors"
                    :key="field"
                >
                    {{ error }}
                </p>
            </div>

            <div class="mb-5">
                <input
                    v-model="form.title"
                    type="text"
                    placeholder="Название статьи"
                    class="w-full border-0 bg-transparent px-0
                           text-3xl font-bold tracking-tight
                           text-slate-900 outline-none
                           placeholder:text-slate-300
                           focus:ring-0 sm:text-4xl"
                >
            </div>

            <ArticleEditor
                v-model="form.content"
                @update:text="form.content_text = $event"
            />

            <section
                class="mt-6 rounded-2xl border border-slate-200
                       bg-white p-5"
            >
                <h2 class="text-sm font-semibold text-slate-900">
                    Документы и файлы
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    После первого сохранения статьи здесь можно будет
                    загружать документы и прикреплять их к статье.
                </p>
            </section>
        </main>
    </div>
</template>