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
const generalError = ref('')
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
    generalError.value = ''

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
            generalError.value =
                error.response?.data?.message ||
                'Не удалось создать компанию.'
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
                   justify-center bg-zinc-950/50 p-4
                   backdrop-blur-[2px]"
            @click.self="emit('close')"
        >
            <div
                class="w-full max-w-md overflow-hidden
                       rounded-xl border border-zinc-200
                       bg-white shadow-xl
                       dark:border-white/10
                       dark:bg-zinc-900"
            >
                <header
                    class="flex items-center justify-between
                           border-b border-zinc-200 px-5 py-4
                           dark:border-white/[0.06]"
                >
                    <div>
                        <h3
                            class="text-base font-semibold
                                   text-zinc-900 dark:text-zinc-50"
                        >
                            Новая компания
                        </h3>

                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                            Создайте новое рабочее пространство
                        </p>
                    </div>

                    <button
                        type="button"
                        aria-label="Закрыть"
                        class="flex h-8 w-8 items-center justify-center rounded-md
                               text-zinc-500 transition-colors hover:bg-zinc-100
                               hover:text-zinc-900
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60
                               dark:text-zinc-400 dark:hover:bg-white/[0.06] dark:hover:text-zinc-100"
                        @click="emit('close')"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </header>

                <form
                    class="space-y-5 p-5"
                    @submit.prevent="submit"
                >
                    <div>
                        <label
                            class="mb-1.5 block text-sm
                                   font-medium text-zinc-700
                                   dark:text-zinc-300"
                        >
                            Название компании
                        </label>

                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full rounded-md border
                                   bg-white px-3 py-2.5 text-sm
                                   text-zinc-900 placeholder:text-zinc-400 outline-none
                                   transition-colors focus:ring-2
                                   dark:bg-zinc-950
                                   dark:text-zinc-100"
                            :class="
                                errors.name
                                    ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-500/20'
                                    : 'border-zinc-300 focus:border-blue-600 focus:ring-blue-600/20 dark:border-white/10'
                            "
                            placeholder="Название организации"
                            @input="errors.name = null"
                        />

                        <p
                            v-if="errors.name"
                            class="mt-1.5 text-xs
                                   font-medium text-rose-600 dark:text-rose-400"
                        >
                            {{ errors.name[0] }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-sm
                                   font-medium text-zinc-700
                                   dark:text-zinc-300"
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
                                   rounded-md border border-zinc-200
                                   p-3 dark:border-white/10"
                        >
                            <img
                                :src="logoPreview"
                                class="h-12 w-12 rounded-md
                                       object-cover ring-1 ring-zinc-200 dark:ring-white/10"
                            />

                            <div class="min-w-0 flex-1">
                                <p
                                    class="truncate text-sm
                                           font-medium
                                           text-zinc-800
                                           dark:text-zinc-200"
                                >
                                    {{ form.logo?.name }}
                                </p>

                                <p
                                    class="mt-0.5 text-xs tabular-nums
                                           text-zinc-500 dark:text-zinc-400"
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
                                aria-label="Удалить логотип"
                                class="flex h-8 w-8 items-center justify-center rounded-md
                                       text-zinc-500 transition-colors
                                       hover:bg-rose-50 hover:text-rose-600
                                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-500/60
                                       dark:text-zinc-400 dark:hover:bg-rose-500/10 dark:hover:text-rose-400"
                                @click="removeLogo"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <button
                            v-else
                            type="button"
                            class="flex w-full items-center gap-3
                                   rounded-md border border-dashed
                                   border-zinc-300 px-4 py-4
                                   text-left transition-colors
                                   hover:border-zinc-400
                                   hover:bg-zinc-50
                                   focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60
                                   dark:border-white/15
                                   dark:hover:border-white/25 dark:hover:bg-white/[0.03]"
                            :class="{
                                'border-rose-500':
                                    errors.logo,
                            }"
                            @click="selectFile"
                        >
                            <span
                                class="flex h-9 w-9 items-center
                                       justify-center rounded-md
                                       border border-zinc-200
                                       text-zinc-500 dark:border-white/10 dark:text-zinc-400"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 5v14m7-7H5" />
                                </svg>
                            </span>

                            <span>
                                <span
                                    class="block text-sm
                                           font-medium
                                           text-zinc-800
                                           dark:text-zinc-200"
                                >
                                    Выбрать изображение
                                </span>

                                <span
                                    class="mt-0.5 block text-xs
                                           text-zinc-500 dark:text-zinc-400"
                                >
                                    PNG, JPG или WebP
                                </span>
                            </span>
                        </button>

                        <p
                            v-if="errors.logo"
                            class="mt-1.5 text-xs
                                   font-medium text-rose-600 dark:text-rose-400"
                        >
                            {{ errors.logo[0] }}
                        </p>
                    </div>

                    <p
                        v-if="generalError"
                        class="rounded-md border border-rose-200 bg-rose-50 px-3 py-2
                               text-xs font-medium text-rose-700
                               dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300"
                    >
                        {{ generalError }}
                    </p>

                    <footer
                        class="flex gap-2 border-t
                               border-zinc-200 pt-4
                               dark:border-white/[0.06]"
                    >
                        <button
                            type="button"
                            class="flex-1 rounded-md border border-zinc-300 bg-white px-4 py-2
                                   text-sm font-medium text-zinc-700
                                   transition-colors hover:bg-zinc-50
                                   focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60
                                   dark:border-white/10 dark:bg-transparent dark:text-zinc-300 dark:hover:bg-white/[0.04]"
                            @click="emit('close')"
                        >
                            Отмена
                        </button>

                        <button
                            type="submit"
                            :disabled="!canSubmit"
                            class="flex flex-1 items-center
                                   justify-center rounded-md
                                   bg-blue-600 px-4 py-2
                                   text-sm font-medium text-white
                                   transition-colors hover:bg-blue-700
                                   focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60 focus-visible:ring-offset-2
                                   disabled:cursor-not-allowed
                                   disabled:opacity-50
                                   dark:focus-visible:ring-offset-zinc-900"
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
