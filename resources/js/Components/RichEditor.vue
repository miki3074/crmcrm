<script setup>
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css'

const props = defineProps({
    modelValue: {
        type: String,
        default: ''
    }
})

const emit = defineEmits(['update:modelValue'])

const modules = {
    toolbar: [
        [{ header: [1, 2, 3, false] }],
        ['bold', 'italic', 'underline', 'strike'],
        [{ list: 'ordered' }, { list: 'bullet' }],
        [{ align: [] }],
        ['link', 'image'],
        ['clean']
    ]
}
</script>

<template>
    <QuillEditor
        theme="snow"
        v-model:content="props.modelValue"
        contentType="html"
        :modules="modules"
        @update:content="emit('update:modelValue', $event)"
        style="min-height: 200px; border-radius: 8px;"
    />
</template>




<style scoped>
.quill-wrapper {
  border: 1px solid #d1d5db;
  border-radius: 8px;
  background: white;
}
</style>
