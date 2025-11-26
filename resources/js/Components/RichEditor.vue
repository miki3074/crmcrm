<script setup>
import { onMounted, ref, watch } from 'vue'
import Quill from 'quill'
import 'quill/dist/quill.snow.css'

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['update:modelValue'])

const editor = ref(null)
let quillInstance = null

onMounted(() => {
  quillInstance = new Quill(editor.value, {
    theme: 'snow',
    modules: {
      toolbar: [
        [{ header: [1, 2, 3, false] }],
        ['bold', 'italic', 'underline', 'strike'],
        [{ list: 'ordered' }, { list: 'bullet' }],
        [{ align: [] }],
        ['link', 'image'],
        ['clean']
      ]
    }
  })

  // начальный текст
  if (props.modelValue) {
    quillInstance.root.innerHTML = props.modelValue
  }

  // слушаем изменения
  quillInstance.on('text-change', () => {
    emit('update:modelValue', quillInstance.root.innerHTML)
  })
})

// обновляем редактор извне
watch(
  () => props.modelValue,
  (value) => {
    if (quillInstance && value !== quillInstance.root.innerHTML) {
      quillInstance.root.innerHTML = value
    }
  }
)
</script>

<template>
  <div class="quill-wrapper">
    <div ref="editor"></div>
  </div>
</template>

<style scoped>
.quill-wrapper {
  border: 1px solid #d1d5db;
  border-radius: 8px;
  background: white;
}
</style>
