<script setup>
import {
    computed,
    ref,
} from 'vue'

import axios from 'axios'

const emit = defineEmits([
    'close',
    'created',
])

const form = ref({
    name: '',
    logo: null,
})

const errors = ref({})
const submitting = ref(false)
const fileInput = ref(null)

const logoPreview = ref(null)

const canSubmit = computed(() => {
    return (
        form.value.name.trim().length > 0 &&
        !submitting.value
    )
})

const selectFile = () => {
    fileInput.value?.click()
}

const onFile = event => {
    const file = event.target.files?.[0]

    if (!file) {
        return
    }

    form.value.logo = file
    errors.value.logo = null

    if (logoPreview.value) {
        URL.revokeObjectURL(logoPreview.value)
    }

    logoPreview.value = URL.createObjectURL(file)
}

const removeLogo = () => {
    form.value.logo = null

    if (logoPreview.value) {
        URL.revokeObjectURL(logoPreview.value)
        logoPreview.value = null
    }

    if (fileInput.value) {
        fileInput.value.value = ''
    }
}

const submit = async () => {
    if (!canSubmit.value) {
        return
    }

    submitting.value = true
    errors.value = {}

    const formData = new FormData()

    formData.append(
        'name',
        form.value.name.trim(),
    )

    if (form.value.logo) {
        formData.append(
            'logo',
            form.value.logo,
        )
    }

    try {
        await axios.post(
            '/api/companies',
            formData,
            {
                headers: {
                    'Content-Type':
                        'multipart/form-data',
                },
            },
        )

        emit('created')
        emit('close')
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value =
                error.response.data.errors || {}
        } else {
            alert(
                error.response?.data?.message ||
                'Не удалось создать компанию.',
            )
        }
    } finally {
        submitting.value = false
    }
}
</script>

<template>
    <Teleport to="body">
        <div
            class="fixed inset-0 z-[100] flex items-center
                   justify-center bg-slate-950/60 p-4
                   backdrop-blur-sm"
            @click.self="emit('close')"
        >
            <div
                class="w-full max-w-md overflow-hidden
                       rounded-2xl border border-slate-200
                       bg-white shadow-2xl
                       dark:border-slate-800
                       dark:bg-slate-900"
            >
                <header
                    class="flex items-center justify-between
                           border-b border-slate-100 px-5 py-4
                           dark:border-slate-800"
                >
                    <div>
                        <h3
                            class="text-lg font-bold
                                   text-slate-900 dark:text-white"
                        >
                            Новая компания
                        </h3>

                        <p class="text-xs text-slate-400">
                            Создайте новое рабочее пространство
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg p-2 text-slate-400
                               transition hover:bg-slate-100
                               hover:text-slate-700
                               dark:hover:bg-slate-800"
                        @click="emit('close')"
                    >
                        ✕
                    </button>
                </header>

                <form
                    class="space-y-4 p-5"
                    @submit.prevent="submit"
                >
                    <div>
                        <label
                            class="mb-1.5 block text-xs
                                   font-bold text-slate-600
                                   dark:text-slate-300"
                        >
                            Название компании
                        </label>

                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full rounded-xl border
                                   bg-white px-4 py-3 text-sm
                                   text-slate-900 outline-none
                                   transition focus:ring-4
                                   dark:bg-slate-800
                                   dark:text-white"
                            :class="
                                errors.name
                                    ? 'border-rose-400 focus:border-rose-400 focus:ring-rose-100'
                                    : 'border-slate-200 focus:border-indigo-400 focus:ring-indigo-100 dark:border-slate-700'
                            "
                            placeholder="Название организации"
                            @input="errors.name = null"
                        />

                        <p
                            v-if="errors.name"
                            class="mt-1.5 text-xs
                                   font-medium text-rose-500"
                        >
                            {{ errors.name[0] }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-xs
                                   font-bold text-slate-600
                                   dark:text-slate-300"
                        >
                            Логотип
                        </label>

                        <input
                            ref="fileInput"
                            type="file"
                            accept="image/*"
                            class="hidden"
                            @change="onFile"
                        />

                        <div
                            v-if="logoPreview"
                            class="flex items-center gap-3
                                   rounded-xl border border-slate-200
                                   p-3 dark:border-slate-700"
                        >
                            <img
                                :src="logoPreview"
                                class="h-14 w-14 rounded-xl
                                       object-cover"
                            />

                            <div class="min-w-0 flex-1">
                                <p
                                    class="truncate text-sm
                                           font-semibold
                                           text-slate-700
                                           dark:text-slate-200"
                                >
                                    {{ form.logo?.name }}
                                </p>

                                <p
                                    class="mt-0.5 text-xs
                                           text-slate-400"
                                >
                                    {{
                                        (
                                            form.logo?.size /
                                            1024 /
                                            1024
                                        ).toFixed(2)
                                    }}
                                    MB
                                </p>
                            </div>

                            <button
                                type="button"
                                class="rounded-lg p-2
                                       text-rose-500
                                       hover:bg-rose-50
                                       dark:hover:bg-rose-950/40"
                                @click="removeLogo"
                            >
                                ✕
                            </button>
                        </div>

                        <button
                            v-else
                            type="button"
                            class="flex w-full items-center gap-3
                                   rounded-xl border-2 border-dashed
                                   border-slate-200 px-4 py-5
                                   text-left transition
                                   hover:border-indigo-400
                                   hover:bg-indigo-50/30
                                   dark:border-slate-700
                                   dark:hover:bg-indigo-950/20"
                            :class="{
                                'border-rose-400':
                                    errors.logo,
                            }"
                            @click="selectFile"
                        >
                            <span
                                class="flex h-10 w-10 items-center
                                       justify-center rounded-xl
                                       bg-indigo-100
                                       text-indigo-600"
                            >
                                +
                            </span>

                            <span>
                                <span
                                    class="block text-sm
                                           font-semibold
                                           text-slate-700
                                           dark:text-slate-200"
                                >
                                    Выбрать изображение
                                </span>

                                <span
                                    class="mt-0.5 block text-xs
                                           text-slate-400"
                                >
                                    PNG, JPG или WebP
                                </span>
                            </span>
                        </button>

                        <p
                            v-if="errors.logo"
                            class="mt-1.5 text-xs
                                   font-medium text-rose-500"
                        >
                            {{ errors.logo[0] }}
                        </p>
                    </div>

                    <footer
                        class="flex gap-2 border-t
                               border-slate-100 pt-4
                               dark:border-slate-800"
                    >
                        <button
                            type="button"
                            class="flex-1 rounded-xl px-4 py-2.5
                                   text-sm font-bold text-slate-500
                                   transition hover:bg-slate-100
                                   dark:hover:bg-slate-800"
                            @click="emit('close')"
                        >
                            Отмена
                        </button>

                        <button
                            type="submit"
                            :disabled="!canSubmit"
                            class="flex flex-1 items-center
                                   justify-center rounded-xl
                                   bg-indigo-600 px-4 py-2.5
                                   text-sm font-bold text-white
                                   transition hover:bg-indigo-700
                                   disabled:cursor-not-allowed
                                   disabled:opacity-50"
                        >
                            {{
                                submitting
                                    ? 'Создание...'
                                    : 'Создать'
                            }}
                        </button>
                    </footer>
                </form>
            </div>
        </div>
    </Teleport>
</template>