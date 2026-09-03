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
                   justify-center bg-black/70 p-4
                   backdrop-blur-sm"
            @click.self="emit('close')"
        >
            <div
                class="w-full max-w-md overflow-hidden
                       rounded-2xl border border-zinc-200
                       bg-white shadow-2xl
                       dark:border-white/5
                       dark:bg-zinc-900/60"
            >
                <header
                    class="flex items-center justify-between
                           border-b border-zinc-100 px-5 py-4
                           dark:border-white/5"
                >
                    <div>
                        <h3
                            class="text-lg font-bold
                                   text-zinc-900 dark:text-white"
                        >
                            Новая компания
                        </h3>

                        <p class="text-xs text-zinc-400">
                            Создайте новое рабочее пространство
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg p-2 text-zinc-400
                               transition hover:bg-zinc-100
                               hover:text-zinc-700
                               dark:hover:bg-white/5"
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
                                   font-bold text-zinc-600
                                   dark:text-zinc-500"
                        >
                            Название компании
                        </label>

                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full rounded-xl border
                                   bg-white px-4 py-3 text-sm
                                   text-zinc-900 outline-none
                                   transition focus:ring-4
                                   dark:bg-white/5
                                   dark:text-white"
                            :class="
                                errors.name
                                    ? 'border-rose-400 focus:border-rose-400 focus:ring-rose-100'
                                    : 'border-zinc-200 focus:border-cyan-400 focus:ring-cyan-100 dark:border-white/10'
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
                                   font-bold text-zinc-600
                                   dark:text-zinc-500"
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
                                   rounded-xl border border-zinc-200
                                   p-3 dark:border-white/10"
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
                                           text-zinc-700
                                           dark:text-zinc-300"
                                >
                                    {{ form.logo?.name }}
                                </p>

                                <p
                                    class="mt-0.5 text-xs
                                           text-zinc-400"
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
                                   border-zinc-200 px-4 py-5
                                   text-left transition
                                   hover:border-cyan-400
                                   hover:bg-cyan-50/30
                                   dark:border-white/10
                                   dark:hover:bg-cyan-500/10"
                            :class="{
                                'border-rose-400':
                                    errors.logo,
                            }"
                            @click="selectFile"
                        >
                            <span
                                class="flex h-10 w-10 items-center
                                       justify-center rounded-xl
                                       bg-cyan-100
                                       text-cyan-600"
                            >
                                +
                            </span>

                            <span>
                                <span
                                    class="block text-sm
                                           font-semibold
                                           text-zinc-700
                                           dark:text-zinc-300"
                                >
                                    Выбрать изображение
                                </span>

                                <span
                                    class="mt-0.5 block text-xs
                                           text-zinc-400"
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
                               border-zinc-100 pt-4
                               dark:border-white/5"
                    >
                        <button
                            type="button"
                            class="flex-1 rounded-xl px-4 py-2.5
                                   text-sm font-bold text-zinc-500
                                   transition hover:bg-zinc-100
                                   dark:hover:bg-white/5"
                            @click="emit('close')"
                        >
                            Отмена
                        </button>

                        <button
                            type="submit"
                            :disabled="!canSubmit"
                            class="flex flex-1 items-center
                                   justify-center rounded-xl
                                   bg-cyan-600 px-4 py-2.5
                                   text-sm font-bold text-white
                                   transition hover:bg-cyan-700
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