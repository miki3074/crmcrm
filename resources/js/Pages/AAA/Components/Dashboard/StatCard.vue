<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        default: '',
    },
    icon: {
        type: String,
        default: 'folder',
    },
    color: {
        type: String,
        default: 'blue',
    },
})

const colors = {
    violet:
        'bg-violet-100 text-violet-600 dark:bg-violet-950/60 dark:text-violet-300',
    blue:
        'bg-blue-100 text-blue-600 dark:bg-blue-950/60 dark:text-blue-300',
    indigo:
        'bg-indigo-100 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-300',
    amber:
        'bg-amber-100 text-amber-600 dark:bg-amber-950/60 dark:text-amber-300',
    emerald:
        'bg-emerald-100 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-300',
    rose:
        'bg-rose-100 text-rose-600 dark:bg-rose-950/60 dark:text-rose-300',
}
</script>

<template>
    <button
        type="button"
        class="group flex min-h-[74px] min-w-0 items-center gap-3
               rounded-2xl border border-slate-200 bg-white
               p-3 text-left shadow-sm transition
               hover:-translate-y-0.5 hover:border-indigo-300
               hover:shadow-md dark:border-slate-800
               dark:bg-slate-900 dark:hover:border-indigo-800"
    >
        <span
            class="flex h-10 w-10 shrink-0 items-center
                   justify-center rounded-xl transition-transform
                   group-hover:scale-105"
            :class="colors[color]"
        >
            <svg
                v-if="icon === 'calendar'"
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M8 7V3m8 4V3M5 11h14M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z"
                />
            </svg>

            <svg
                v-else-if="icon === 'folder'"
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M3 7a2 2 0 012-2h5l2 2h7a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"
                />
            </svg>

            <svg
                v-else-if="icon === 'users' || icon === 'clients'"
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M17 20h5v-2a4 4 0 00-5-4M9 20H2v-2a4 4 0 014-4h3m4-4a4 4 0 100-8 4 4 0 000 8zm6 0a3 3 0 100-6"
                />
            </svg>

            <svg
                v-else-if="icon === 'map'"
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 18l-6 3V6l6-3 6 3 6-3v15l-6 3-6-3zm0 0V3m6 18V6"
                />
            </svg>

            <svg
                v-else
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 5v14m7-7H5"
                />
            </svg>
        </span>

        <span class="min-w-0">
            <span
                class="block truncate text-sm font-bold
                       text-slate-800 dark:text-white"
            >
                {{ title }}
            </span>

            <span
                class="mt-0.5 block truncate text-[11px]
                       text-slate-400"
            >
                {{ subtitle }}
            </span>
        </span>

        <svg
            class="ml-auto hidden h-4 w-4 shrink-0
                   text-slate-300 transition
                   group-hover:translate-x-0.5
                   group-hover:text-indigo-500 xl:block"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="m9 18 6-6-6-6"
            />
        </svg>
    </button>
</template>