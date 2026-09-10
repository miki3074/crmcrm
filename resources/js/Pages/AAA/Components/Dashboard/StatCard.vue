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
    violet: 'bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-300',
    blue: 'bg-cyan-50 text-cyan-600 dark:bg-cyan-500/10 dark:text-cyan-300',
    indigo: 'bg-sky-50 text-sky-600 dark:bg-sky-500/10 dark:text-sky-300',
    amber: 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300',
    emerald: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300',
    rose: 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-300',
}
</script>

<template>
    <button
        type="button"
        class="group flex min-h-[68px] min-w-0 items-center gap-3
               rounded-xl border border-zinc-200 bg-white
               p-3 text-left transition
               hover:border-zinc-300 hover:shadow-sm
               focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400 focus-visible:ring-offset-2
               dark:border-white/5 dark:bg-zinc-900/60
               dark:hover:border-white/10 dark:focus-visible:ring-offset-zinc-900"
    >
        <span
            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
            :class="colors[color]"
        >
            <svg v-if="icon === 'calendar'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M5 11h14M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z" />
            </svg>

            <svg v-else-if="icon === 'folder'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h5l2 2h7a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z" />
            </svg>

            <svg v-else-if="icon === 'users'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-5-4M9 20H2v-2a4 4 0 014-4h3m4-4a4 4 0 100-8 4 4 0 000 8zm6 0a3 3 0 100-6" />
            </svg>

            <svg v-else-if="icon === 'clients'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.5 14.5A5.5 5.5 0 1114 9a5.5 5.5 0 01-5.5 5.5zM20 21a8 8 0 00-16 0" />
            </svg>

            <svg v-else-if="icon === 'map'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 18l-6 3V6l6-3 6 3 6-3v15l-6 3-6-3zm0 0V3m6 18V6" />
            </svg>

            <svg v-else-if="icon === 'plus'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m7-7H5" />
            </svg>

            <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
        </span>

        <span class="min-w-0">
            <span class="block truncate text-[13px] font-semibold text-zinc-800 dark:text-zinc-100">
                {{ title }}
            </span>

            <span v-if="subtitle" class="mt-0.5 block truncate text-[11px] text-zinc-500 dark:text-zinc-400">
                {{ subtitle }}
            </span>
        </span>

        <svg
            class="ml-auto hidden h-4 w-4 shrink-0 text-zinc-300 transition
                   group-hover:translate-x-0.5 group-hover:text-zinc-500
                   dark:text-zinc-700 dark:group-hover:text-zinc-400 xl:block"
            fill="none" viewBox="0 0 24 24" stroke="currentColor"
        >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 18 6-6-6-6" />
        </svg>
    </button>
</template>
