<script setup>
import { onBeforeUnmount, watch } from 'vue';
import { Editor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';

const props = defineProps({
    modelValue: {
        type: Object,
        default: () => ({
            type: 'doc',
            content: [],
        }),
    },
});

const emit = defineEmits([
    'update:modelValue',
    'update:text',
]);

const editor = new Editor({
    extensions: [
        StarterKit,
        Underline,

        Link.configure({
            openOnClick: false,
            autolink: true,
            defaultProtocol: 'https',
        }),

        Placeholder.configure({
            placeholder: 'Начните писать статью...',
        }),
    ],

    content: props.modelValue,

    editorProps: {
        attributes: {
            class: [
                'prose',
                'prose-slate',
                'max-w-none',
                'min-h-[420px]',
                'px-6',
                'py-5',
                'focus:outline-none',
            ].join(' '),
        },
    },

    onUpdate({ editor }) {
        emit('update:modelValue', editor.getJSON());
        emit('update:text', editor.getText());
    },
});

watch(
    () => props.modelValue,
    (content) => {
        if (!editor || !content) {
            return;
        }

        const currentContent = JSON.stringify(
            editor.getJSON()
        );

        const newContent = JSON.stringify(content);

        if (currentContent !== newContent) {
            editor.commands.setContent(content, false);
        }
    },
    {
        deep: true,
    }
);

function setLink() {
    const previousUrl = editor.getAttributes('link').href;

    const url = window.prompt(
        'Введите адрес ссылки',
        previousUrl ?? ''
    );

    if (url === null) {
        return;
    }

    if (url === '') {
        editor
            .chain()
            .focus()
            .extendMarkRange('link')
            .unsetLink()
            .run();

        return;
    }

    editor
        .chain()
        .focus()
        .extendMarkRange('link')
        .setLink({
            href: url,
        })
        .run();
}

onBeforeUnmount(() => {
    editor.destroy();
});
</script>

<template>
    <div
        v-if="editor"
        class="overflow-hidden rounded-2xl
               border border-slate-200 bg-white"
    >
        <div
            class="flex flex-wrap items-center gap-1
                   border-b border-slate-200 bg-slate-50 p-2"
        >
            <button
                type="button"
                class="rounded-lg px-3 py-2 text-sm font-semibold"
                :class="
                    editor.isActive('bold')
                        ? 'bg-indigo-100 text-indigo-700'
                        : 'text-slate-600 hover:bg-white'
                "
                @click="editor.chain().focus().toggleBold().run()"
            >
                Ж
            </button>

            <button
                type="button"
                class="rounded-lg px-3 py-2 text-sm italic"
                :class="
                    editor.isActive('italic')
                        ? 'bg-indigo-100 text-indigo-700'
                        : 'text-slate-600 hover:bg-white'
                "
                @click="editor.chain().focus().toggleItalic().run()"
            >
                К
            </button>

            <button
                type="button"
                class="rounded-lg px-3 py-2 text-sm underline"
                :class="
                    editor.isActive('underline')
                        ? 'bg-indigo-100 text-indigo-700'
                        : 'text-slate-600 hover:bg-white'
                "
                @click="
                    editor.chain().focus().toggleUnderline().run()
                "
            >
                Ч
            </button>

            <div class="mx-1 h-6 w-px bg-slate-200" />

            <button
                type="button"
                class="rounded-lg px-3 py-2 text-sm"
                :class="
                    editor.isActive('heading', { level: 2 })
                        ? 'bg-indigo-100 text-indigo-700'
                        : 'text-slate-600 hover:bg-white'
                "
                @click="
                    editor
                        .chain()
                        .focus()
                        .toggleHeading({ level: 2 })
                        .run()
                "
            >
                H2
            </button>

            <button
                type="button"
                class="rounded-lg px-3 py-2 text-sm"
                :class="
                    editor.isActive('heading', { level: 3 })
                        ? 'bg-indigo-100 text-indigo-700'
                        : 'text-slate-600 hover:bg-white'
                "
                @click="
                    editor
                        .chain()
                        .focus()
                        .toggleHeading({ level: 3 })
                        .run()
                "
            >
                H3
            </button>

            <div class="mx-1 h-6 w-px bg-slate-200" />

            <button
                type="button"
                class="rounded-lg px-3 py-2 text-sm"
                :class="
                    editor.isActive('bulletList')
                        ? 'bg-indigo-100 text-indigo-700'
                        : 'text-slate-600 hover:bg-white'
                "
                @click="
                    editor
                        .chain()
                        .focus()
                        .toggleBulletList()
                        .run()
                "
            >
                • Список
            </button>

            <button
                type="button"
                class="rounded-lg px-3 py-2 text-sm"
                :class="
                    editor.isActive('orderedList')
                        ? 'bg-indigo-100 text-indigo-700'
                        : 'text-slate-600 hover:bg-white'
                "
                @click="
                    editor
                        .chain()
                        .focus()
                        .toggleOrderedList()
                        .run()
                "
            >
                1. Список
            </button>

            <button
                type="button"
                class="rounded-lg px-3 py-2 text-sm"
                :class="
                    editor.isActive('blockquote')
                        ? 'bg-indigo-100 text-indigo-700'
                        : 'text-slate-600 hover:bg-white'
                "
                @click="
                    editor
                        .chain()
                        .focus()
                        .toggleBlockquote()
                        .run()
                "
            >
                Цитата
            </button>

            <button
                type="button"
                class="rounded-lg px-3 py-2 text-sm"
                :class="
                    editor.isActive('codeBlock')
                        ? 'bg-indigo-100 text-indigo-700'
                        : 'text-slate-600 hover:bg-white'
                "
                @click="
                    editor
                        .chain()
                        .focus()
                        .toggleCodeBlock()
                        .run()
                "
            >
                Код
            </button>

            <button
                type="button"
                class="rounded-lg px-3 py-2 text-sm
                       text-slate-600 hover:bg-white"
                @click="setLink"
            >
                Ссылка
            </button>

            <div class="ml-auto flex items-center gap-1">
                <button
                    type="button"
                    class="rounded-lg px-3 py-2 text-sm
                           text-slate-600 hover:bg-white
                           disabled:opacity-40"
                    :disabled="!editor.can().undo()"
                    @click="editor.chain().focus().undo().run()"
                >
                    Назад
                </button>

                <button
                    type="button"
                    class="rounded-lg px-3 py-2 text-sm
                           text-slate-600 hover:bg-white
                           disabled:opacity-40"
                    :disabled="!editor.can().redo()"
                    @click="editor.chain().focus().redo().run()"
                >
                    Вперёд
                </button>
            </div>
        </div>

        <EditorContent :editor="editor" />
    </div>
</template>