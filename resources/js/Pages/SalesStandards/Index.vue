<script setup>
import { ref, computed, reactive } from 'vue'
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { useToast } from '@/Composables/useToast'
import ToastContainer from '@/Components/ToastContainer.vue'

import {
    salesStages,
    activeListening,
    incomingCallSteps,
    incomingCallRules,
    secretaryObjections,
    secretaryBypassTricks,
    outgoingCallStandard,
    outgoingCallSteps,
    coldCallTemplate,
    questionTypes,
    needsQuestions,
    propertiesAndBenefits,
    clientFaq,
    objectionRule,
    objectionSystemSteps,
    objections,
    priceTechniques,
    complaintDefinition,
    complaintSteps,
    intakeQuestions,
    dealChecklist,
    adTrackChecklist,
} from '@/Data/salesStandards.js'

const toast = useToast()

/* ---------------------------------------------------------------------- */
/* Живой поиск — главный инструмент: печатаете фразу клиента, сразу видите
   готовый ответ, не листая разделы.                                      */
/* ---------------------------------------------------------------------- */
const query = ref('')

const normalizedQuery = computed(() => query.value.trim().toLowerCase())

const searchResults = computed(() => {
    const q = normalizedQuery.value
    if (!q) return []

    const results = []

    objections.forEach((o, i) => {
        if (o.text.toLowerCase().includes(q) || o.responses.some(r => r.toLowerCase().includes(q))) {
            results.push({ kind: 'objection', key: `obj-${i}`, title: o.text, bodies: o.responses, color: 'rose' })
        }
    })

    secretaryObjections.forEach((s, i) => {
        if (s.objection.toLowerCase().includes(q) || s.response.toLowerCase().includes(q)) {
            results.push({ kind: 'secretary', key: `sec-${i}`, title: s.objection, bodies: [s.response], color: 'violet' })
        }
    })

    clientFaq.forEach((f, i) => {
        if (f.question.toLowerCase().includes(q) || f.answer.toLowerCase().includes(q)) {
            results.push({ kind: 'faq', key: `faq-${i}`, title: f.question, bodies: [f.answer], color: 'cyan' })
        }
    })

    return results
})

const KIND_LABEL = { objection: 'Возражение', secretary: 'Секретарь', faq: 'Вопрос клиента' }

/* ---------------------------------------------------------------------- */
/* Копирование скрипта в буфер — чтобы вставить в чат/письмо клиенту       */
/* ---------------------------------------------------------------------- */
const copyText = async (text) => {
    try {
        await navigator.clipboard.writeText(text)
        toast.success('Скопировано')
    } catch (e) {
        toast.error('Не удалось скопировать')
    }
}

/* ---------------------------------------------------------------------- */
/* Вкладки раздела                                                        */
/* ---------------------------------------------------------------------- */
const tabs = [
    { id: 'calls', label: 'Звонки' },
    { id: 'objections', label: 'Возражения' },
    { id: 'secretary', label: 'Обход секретаря' },
    { id: 'faq', label: 'Вопросы клиентов' },
    { id: 'needs', label: 'Выявление потребностей' },
    { id: 'complaints', label: 'Рекламации' },
    { id: 'deal', label: 'Чек-лист сделки' },
    { id: 'stages', label: 'Этапы продаж' },
]
const activeTab = ref('calls')

/* ---------------------------------------------------------------------- */
/* Живые чек-листы — состояние сохраняется в localStorage, чтобы не       */
/* потерять прогресс на середине звонка/сделки при обновлении страницы.   */
/* ---------------------------------------------------------------------- */
const STORAGE_PREFIX = 'sales-standards:'

const loadState = (key, fallback) => {
    try {
        const raw = localStorage.getItem(STORAGE_PREFIX + key)
        return raw ? JSON.parse(raw) : fallback
    } catch (e) {
        return fallback
    }
}

const saveState = (key, value) => {
    try {
        localStorage.setItem(STORAGE_PREFIX + key, JSON.stringify(value))
    } catch (e) {
        // localStorage недоступен (приватный режим и т.п.) — тихо игнорируем
    }
}

const callType = ref('incoming')

const checkedNeeds = reactive(new Set(loadState('needs-checked', [])))
const toggleNeed = (index) => {
    if (checkedNeeds.has(index)) checkedNeeds.delete(index)
    else checkedNeeds.add(index)
    saveState('needs-checked', Array.from(checkedNeeds))
}
const resetNeeds = () => {
    checkedNeeds.clear()
    saveState('needs-checked', [])
}

const checkedCallSteps = reactive(new Set(loadState('call-steps-checked', [])))
const toggleCallStep = (id) => {
    if (checkedCallSteps.has(id)) checkedCallSteps.delete(id)
    else checkedCallSteps.add(id)
    saveState('call-steps-checked', Array.from(checkedCallSteps))
}
const resetCallSteps = () => {
    checkedCallSteps.clear()
    saveState('call-steps-checked', [])
}

const checkedDeal = reactive(new Set(loadState('deal-checked', [])))
const toggleDeal = (id) => {
    if (checkedDeal.has(id)) checkedDeal.delete(id)
    else checkedDeal.add(id)
    saveState('deal-checked', Array.from(checkedDeal))
}
const resetDeal = () => {
    checkedDeal.clear()
    saveState('deal-checked', [])
}
const dealProgress = computed(() => Math.round((checkedDeal.size / dealChecklist.length) * 100))

const checkedTrack = reactive(new Set(loadState('track-checked', [])))
const toggleTrack = (index) => {
    if (checkedTrack.has(index)) checkedTrack.delete(index)
    else checkedTrack.add(index)
    saveState('track-checked', Array.from(checkedTrack))
}
const resetTrack = () => {
    checkedTrack.clear()
    saveState('track-checked', [])
}

const showTrackChecklist = ref(false)
</script>

<template>
    <Head title="Стандарты продаж" />

    <AuthenticatedLayout>
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-5">

            <!-- Заголовок -->
            <header>
                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-zinc-500 dark:text-zinc-400">Помощник продавца</p>
                <h1 class="mt-0.5 text-xl font-bold text-zinc-900 dark:text-white">Стандарты продаж</h1>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    Не курс для изучения — шпаргалка на время звонка. Введите фразу клиента и получите готовый ответ.
                </p>
            </header>

            <!-- Живой поиск -->
            <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-white/5 dark:bg-zinc-900/60">
                <div class="relative">
                    <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                    </svg>
                    <input
                        v-model="query"
                        type="search"
                        placeholder="Что говорит клиент? Например: «дорого», «вышлите КП», «нет времени»…"
                        class="w-full rounded-xl border border-zinc-200 bg-zinc-50 py-3.5 pl-12 pr-4 text-base text-zinc-900 outline-none transition
                               focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100
                               dark:border-white/10 dark:bg-white/5 dark:text-white"
                    />
                </div>

                <!-- Результаты поиска -->
                <div v-if="normalizedQuery" class="mt-4 space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-zinc-400">Найдено: {{ searchResults.length }}</p>

                    <div v-if="!searchResults.length" class="rounded-xl border border-dashed border-zinc-200 py-8 text-center text-sm text-zinc-400 dark:border-white/10">
                        Ничего не нашлось — попробуйте другое слово или загляните во вкладки ниже.
                    </div>

                    <article
                        v-for="r in searchResults"
                        :key="r.key"
                        class="rounded-xl border p-4"
                        :class="{
                            rose: 'border-rose-200 bg-rose-50/60 dark:border-rose-900/50 dark:bg-rose-500/5',
                            violet: 'border-violet-200 bg-violet-50/60 dark:border-violet-900/50 dark:bg-violet-500/5',
                            cyan: 'border-cyan-200 bg-cyan-50/60 dark:border-cyan-900/50 dark:bg-cyan-500/5',
                        }[r.color]"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <span
                                    class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide"
                                    :class="{
                                        rose: 'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300',
                                        violet: 'bg-violet-100 text-violet-700 dark:bg-violet-500/10 dark:text-violet-300',
                                        cyan: 'bg-cyan-100 text-cyan-700 dark:bg-cyan-500/10 dark:text-cyan-300',
                                    }[r.color]"
                                >
                                    {{ KIND_LABEL[r.kind] }}
                                </span>
                                <h3 class="mt-1.5 font-bold text-zinc-800 dark:text-white">«{{ r.title }}»</h3>
                            </div>
                        </div>

                        <div v-for="(body, bi) in r.bodies" :key="bi" class="mt-2.5 flex items-start gap-2 rounded-lg bg-white/70 p-3 text-sm leading-relaxed text-zinc-700 dark:bg-black/20 dark:text-zinc-300">
                            <span class="flex-1">{{ body }}</span>
                            <button
                                type="button"
                                title="Скопировать"
                                class="shrink-0 rounded-lg p-1.5 text-zinc-400 transition hover:bg-white hover:text-cyan-600 dark:hover:bg-white/10"
                                @click="copyText(body)"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                            </button>
                        </div>
                    </article>
                </div>
            </div>

            <!-- Вкладки -->
            <div v-if="!normalizedQuery">
                <div class="flex flex-wrap gap-1 rounded-lg bg-zinc-100/70 p-1 dark:bg-black/20">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        type="button"
                        @click="activeTab = tab.id"
                        :class="[
                            'flex-1 min-w-[120px] rounded-md px-3 py-2 text-xs font-semibold transition whitespace-nowrap',
                            'focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400',
                            activeTab === tab.id ? 'bg-white text-zinc-900 shadow-sm dark:bg-white/10 dark:text-white' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300'
                        ]"
                    >
                        {{ tab.label }}
                    </button>
                </div>

                <div class="mt-5 space-y-5">

                    <!-- ============== ЗВОНКИ ============== -->
                    <template v-if="activeTab === 'calls'">
                        <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/5 dark:bg-zinc-900/60">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="flex rounded-lg bg-zinc-100 p-1 dark:bg-white/5">
                                    <button
                                        type="button"
                                        class="rounded-md px-4 py-2 text-xs font-bold transition"
                                        :class="callType === 'incoming' ? 'bg-white text-cyan-600 shadow-sm dark:bg-white/10' : 'text-zinc-400'"
                                        @click="callType = 'incoming'"
                                    >
                                        Входящий звонок
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-md px-4 py-2 text-xs font-bold transition"
                                        :class="callType === 'outgoing' ? 'bg-white text-cyan-600 shadow-sm dark:bg-white/10' : 'text-zinc-400'"
                                        @click="callType = 'outgoing'"
                                    >
                                        Исходящий (холодный) звонок
                                    </button>
                                </div>
                                <button type="button" class="text-xs font-semibold text-zinc-400 hover:text-rose-500" @click="resetCallSteps">
                                    Сбросить отметки
                                </button>
                            </div>

                            <!-- Входящий -->
                            <div v-if="callType === 'incoming'" class="mt-5 space-y-2">
                                <label
                                    v-for="s in incomingCallSteps"
                                    :key="'in-' + s.step"
                                    class="flex cursor-pointer items-start gap-3 rounded-xl border p-3 transition"
                                    :class="checkedCallSteps.has('in-' + s.step) ? 'border-emerald-200 bg-emerald-50/60 dark:border-emerald-900/50 dark:bg-emerald-500/5' : 'border-zinc-100 hover:bg-zinc-50 dark:border-white/5 dark:hover:bg-white/5'"
                                >
                                    <input
                                        type="checkbox"
                                        class="mt-1 rounded text-cyan-600 focus:ring-cyan-400"
                                        :checked="checkedCallSteps.has('in-' + s.step)"
                                        @change="toggleCallStep('in-' + s.step)"
                                    />
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">{{ s.step }}. {{ s.text }}</p>
                                        <p v-if="s.phrase" class="mt-1 rounded-lg bg-zinc-50 px-3 py-2 text-sm italic text-zinc-600 dark:bg-black/20 dark:text-zinc-400">«{{ s.phrase }}»</p>
                                        <p v-if="s.note" class="mt-1 text-xs text-zinc-400">{{ s.note }}</p>
                                    </div>
                                </label>

                                <div class="mt-3 space-y-1.5 rounded-xl border border-amber-200 bg-amber-50/70 p-3 text-xs font-semibold text-amber-700 dark:border-amber-900/50 dark:bg-amber-500/10 dark:text-amber-300">
                                    <p v-for="rule in incomingCallRules" :key="rule">⚠ {{ rule }}</p>
                                </div>
                            </div>

                            <!-- Исходящий -->
                            <div v-else class="mt-5 space-y-5">
                                <p class="text-xs font-semibold text-cyan-600 dark:text-cyan-300">
                                    Норма: {{ outgoingCallStandard.dailyNewClients }} «холодных» звонков новым клиентам в день. {{ outgoingCallStandard.note }}
                                </p>

                                <div class="space-y-2">
                                    <label
                                        v-for="s in outgoingCallSteps"
                                        :key="'out-' + s.step"
                                        class="flex cursor-pointer items-start gap-3 rounded-xl border p-3 transition"
                                        :class="checkedCallSteps.has('out-' + s.step) ? 'border-emerald-200 bg-emerald-50/60 dark:border-emerald-900/50 dark:bg-emerald-500/5' : 'border-zinc-100 hover:bg-zinc-50 dark:border-white/5 dark:hover:bg-white/5'"
                                    >
                                        <input
                                            type="checkbox"
                                            class="mt-1 rounded text-cyan-600 focus:ring-cyan-400"
                                            :checked="checkedCallSteps.has('out-' + s.step)"
                                            @change="toggleCallStep('out-' + s.step)"
                                        />
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">{{ s.step }}. {{ s.text }}</p>
                                            <p v-if="s.phrase" class="mt-1 rounded-lg bg-zinc-50 px-3 py-2 text-sm italic text-zinc-600 dark:bg-black/20 dark:text-zinc-400">«{{ s.phrase }}»</p>
                                            <p v-if="s.note" class="mt-1 text-xs text-zinc-400">{{ s.note }}</p>
                                        </div>
                                    </label>
                                </div>

                                <div class="rounded-xl border border-zinc-100 bg-zinc-50/70 p-4 dark:border-white/5 dark:bg-white/[0.03]">
                                    <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-400">Типовой шаблон холодного звонка</h3>
                                    <p class="mt-2 text-sm font-semibold text-zinc-700 dark:text-zinc-200">Цель: {{ coldCallTemplate.goal }}</p>

                                    <div class="mt-3 rounded-lg bg-white p-3 dark:bg-zinc-900/60">
                                        <p class="text-xs font-bold text-cyan-600">{{ coldCallTemplate.secretary.title }}</p>
                                        <p class="mt-1 text-sm italic text-zinc-600 dark:text-zinc-300">«{{ coldCallTemplate.secretary.phrase }}»</p>
                                        <p class="mt-1 text-xs text-zinc-400">{{ coldCallTemplate.secretary.note }}</p>
                                    </div>

                                    <div class="mt-3 rounded-lg bg-white p-3 dark:bg-zinc-900/60">
                                        <p class="text-xs font-bold text-cyan-600">{{ coldCallTemplate.director.title }}</p>
                                        <p class="mt-1 text-sm italic text-zinc-600 dark:text-zinc-300">«{{ coldCallTemplate.director.opening }}»</p>

                                        <div v-for="v in coldCallTemplate.director.variants" :key="v.label" class="mt-2 rounded-lg bg-zinc-50 p-2.5 dark:bg-black/20">
                                            <p class="text-[11px] font-bold uppercase tracking-wide text-zinc-400">{{ v.label }}</p>
                                            <p v-for="(line, li) in v.lines" :key="li" class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ line }}</p>
                                            <p v-if="v.extra" class="mt-1 text-xs italic text-zinc-400">{{ v.extra }}</p>
                                        </div>

                                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ coldCallTemplate.director.closing }}</p>
                                    </div>

                                    <div class="mt-3">
                                        <p class="text-xs font-bold uppercase tracking-wider text-zinc-400">Как обосновать встречу</p>
                                        <ul class="mt-1.5 space-y-1">
                                            <li v-for="(reason, ri) in coldCallTemplate.meetingReasons" :key="ri" class="flex gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                                                <span class="text-cyan-500">{{ ri + 1 }}.</span>{{ reason }}
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/5 dark:bg-zinc-900/60">
                            <h2 class="text-sm font-bold text-zinc-900 dark:text-white">Активное слушание</h2>
                            <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                <div v-for="t in activeListening" :key="t.name" class="rounded-xl border border-zinc-100 bg-zinc-50/70 p-3 dark:border-white/5 dark:bg-white/[0.03]">
                                    <p class="text-sm font-bold text-zinc-700 dark:text-zinc-200">{{ t.name }}</p>
                                    <p v-if="t.example" class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ t.example }}</p>
                                </div>
                            </div>
                        </section>
                    </template>

                    <!-- ============== ВОЗРАЖЕНИЯ ============== -->
                    <template v-if="activeTab === 'objections'">
                        <section class="rounded-2xl border border-amber-200 bg-amber-50/60 p-4 text-sm font-semibold text-amber-800 dark:border-amber-900/50 dark:bg-amber-500/10 dark:text-amber-300">
                            ⚠ {{ objectionRule }}
                        </section>

                        <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/5 dark:bg-zinc-900/60">
                            <h2 class="text-sm font-bold text-zinc-900 dark:text-white">Система ответа на возражение</h2>
                            <ol class="mt-3 space-y-2">
                                <li v-for="(s, i) in objectionSystemSteps" :key="i" class="flex gap-3 text-sm text-zinc-600 dark:text-zinc-300">
                                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-cyan-100 text-[11px] font-bold text-cyan-700 dark:bg-cyan-500/10 dark:text-cyan-300">{{ i + 1 }}</span>
                                    {{ s }}
                                </li>
                            </ol>
                        </section>

                        <section class="space-y-3">
                            <article v-for="(o, i) in objections" :key="i" class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/5 dark:bg-zinc-900/60">
                                <h3 class="font-bold text-zinc-800 dark:text-white">«{{ o.text }}»</h3>
                                <div v-for="(resp, ri) in o.responses" :key="ri" class="mt-2.5 flex items-start gap-2 rounded-lg bg-zinc-50 p-3 text-sm leading-relaxed text-zinc-700 dark:bg-black/20 dark:text-zinc-300">
                                    <span class="flex-1">{{ resp }}</span>
                                    <button type="button" title="Скопировать" class="shrink-0 rounded-lg p-1.5 text-zinc-400 transition hover:bg-white hover:text-cyan-600 dark:hover:bg-white/10" @click="copyText(resp)">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </button>
                                </div>
                            </article>
                        </section>

                        <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/5 dark:bg-zinc-900/60">
                            <h2 class="text-sm font-bold text-zinc-900 dark:text-white">Как представить цену</h2>
                            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div v-for="t in priceTechniques" :key="t.name" class="rounded-xl border border-zinc-100 bg-zinc-50/70 p-3 dark:border-white/5 dark:bg-white/[0.03]">
                                    <p class="text-sm font-bold text-zinc-700 dark:text-zinc-200">{{ t.name }}</p>
                                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ t.description }}</p>
                                    <p v-if="t.example" class="mt-1.5 text-xs italic text-cyan-600 dark:text-cyan-300">«{{ t.example }}»</p>
                                </div>
                            </div>
                        </section>
                    </template>

                    <!-- ============== ОБХОД СЕКРЕТАРЯ ============== -->
                    <template v-if="activeTab === 'secretary'">
                        <section class="space-y-3">
                            <article v-for="(s, i) in secretaryObjections" :key="i" class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/5 dark:bg-zinc-900/60">
                                <h3 class="font-bold text-zinc-800 dark:text-white">«{{ s.objection }}»</h3>
                                <div class="mt-2.5 flex items-start gap-2 rounded-lg bg-zinc-50 p-3 text-sm leading-relaxed text-zinc-700 dark:bg-black/20 dark:text-zinc-300">
                                    <span class="flex-1">{{ s.response }}</span>
                                    <button type="button" title="Скопировать" class="shrink-0 rounded-lg p-1.5 text-zinc-400 transition hover:bg-white hover:text-cyan-600 dark:hover:bg-white/10" @click="copyText(s.response)">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </button>
                                </div>
                            </article>
                        </section>

                        <section class="rounded-2xl border border-violet-200 bg-violet-50/50 p-5 dark:border-violet-900/40 dark:bg-violet-500/5">
                            <h2 class="text-sm font-bold text-violet-800 dark:text-violet-300">Обходные пути (если секретарь сопротивляется)</h2>
                            <div class="mt-3 space-y-2">
                                <p v-for="(t, i) in secretaryBypassTricks" :key="i" class="rounded-lg bg-white p-3 text-sm text-zinc-700 dark:bg-zinc-900/60 dark:text-zinc-300">{{ t }}</p>
                            </div>
                        </section>
                    </template>

                    <!-- ============== ВОПРОСЫ КЛИЕНТОВ (FAQ) ============== -->
                    <template v-if="activeTab === 'faq'">
                        <section class="space-y-3">
                            <article v-for="(f, i) in clientFaq" :key="i" class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/5 dark:bg-zinc-900/60">
                                <h3 class="font-bold text-zinc-800 dark:text-white">{{ f.question }}</h3>
                                <div class="mt-2.5 flex items-start gap-2 rounded-lg bg-zinc-50 p-3 text-sm leading-relaxed text-zinc-700 dark:bg-black/20 dark:text-zinc-300">
                                    <span class="flex-1">{{ f.answer }}</span>
                                    <button type="button" title="Скопировать" class="shrink-0 rounded-lg p-1.5 text-zinc-400 transition hover:bg-white hover:text-cyan-600 dark:hover:bg-white/10" @click="copyText(f.answer)">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </button>
                                </div>
                            </article>
                        </section>
                    </template>

                    <!-- ============== ВЫЯВЛЕНИЕ ПОТРЕБНОСТЕЙ ============== -->
                    <template v-if="activeTab === 'needs'">
                        <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/5 dark:bg-zinc-900/60">
                            <h2 class="text-sm font-bold text-zinc-900 dark:text-white">Типы вопросов</h2>
                            <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                <div v-for="qt in questionTypes" :key="qt.type" class="rounded-xl border border-zinc-100 bg-zinc-50/70 p-3 dark:border-white/5 dark:bg-white/[0.03]">
                                    <p class="text-sm font-bold text-zinc-700 dark:text-zinc-200">{{ qt.type }}</p>
                                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ qt.purpose }}</p>
                                    <p v-if="qt.example" class="mt-1.5 text-xs italic text-cyan-600 dark:text-cyan-300">{{ qt.example }}</p>
                                </div>
                            </div>
                        </section>

                        <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/5 dark:bg-zinc-900/60">
                            <div class="flex items-center justify-between">
                                <h2 class="text-sm font-bold text-zinc-900 dark:text-white">Вопросы на выявление потребностей</h2>
                                <button type="button" class="text-xs font-semibold text-zinc-400 hover:text-rose-500" @click="resetNeeds">Сбросить отметки</button>
                            </div>
                            <p class="mt-1 text-xs text-zinc-400">Отмечайте по ходу разговора, что уже спросили.</p>
                            <div class="mt-3 space-y-2">
                                <label
                                    v-for="(q, i) in needsQuestions"
                                    :key="i"
                                    class="flex cursor-pointer items-start gap-3 rounded-xl border p-3 text-sm transition"
                                    :class="checkedNeeds.has(i) ? 'border-emerald-200 bg-emerald-50/60 text-zinc-500 line-through dark:border-emerald-900/50 dark:bg-emerald-500/5' : 'border-zinc-100 text-zinc-700 hover:bg-zinc-50 dark:border-white/5 dark:text-zinc-300 dark:hover:bg-white/5'"
                                >
                                    <input type="checkbox" class="mt-0.5 rounded text-cyan-600 focus:ring-cyan-400" :checked="checkedNeeds.has(i)" @change="toggleNeed(i)" />
                                    {{ q }}
                                </label>
                            </div>
                        </section>

                        <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/5 dark:bg-zinc-900/60">
                            <h2 class="text-sm font-bold text-zinc-900 dark:text-white">Свойства и выгоды</h2>
                            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ propertiesAndBenefits.property }}</p>
                            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ propertiesAndBenefits.benefit }}</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span v-for="ex in propertiesAndBenefits.examples" :key="ex" class="rounded-full bg-cyan-50 px-3 py-1 text-xs font-bold text-cyan-700 dark:bg-cyan-500/10 dark:text-cyan-300">{{ ex }}</span>
                            </div>
                        </section>

                        <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/5 dark:bg-zinc-900/60">
                            <h2 class="text-sm font-bold text-zinc-900 dark:text-white">Вопросы для заявки (анкета клиента)</h2>
                            <ol class="mt-3 space-y-1.5">
                                <li v-for="(q, i) in intakeQuestions" :key="i" class="flex gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                                    <span class="text-cyan-500">{{ i + 1 }}.</span>{{ q }}
                                </li>
                            </ol>
                        </section>
                    </template>

                    <!-- ============== РЕКЛАМАЦИИ ============== -->
                    <template v-if="activeTab === 'complaints'">
                        <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/5 dark:bg-zinc-900/60">
                            <h2 class="text-sm font-bold text-zinc-900 dark:text-white">Что такое рекламация</h2>
                            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ complaintDefinition }}</p>
                        </section>

                        <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/5 dark:bg-zinc-900/60">
                            <h2 class="text-sm font-bold text-zinc-900 dark:text-white">Техника работы с рекламацией</h2>
                            <ol class="mt-3 space-y-2">
                                <li v-for="(s, i) in complaintSteps" :key="i" class="flex gap-3 text-sm text-zinc-600 dark:text-zinc-300">
                                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-rose-100 text-[11px] font-bold text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">{{ i + 1 }}</span>
                                    {{ s }}
                                </li>
                            </ol>
                        </section>
                    </template>

                    <!-- ============== ЧЕК-ЛИСТ СДЕЛКИ ============== -->
                    <template v-if="activeTab === 'deal'">
                        <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/5 dark:bg-zinc-900/60">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <h2 class="text-sm font-bold text-zinc-900 dark:text-white">Сделка достигнута — что дальше</h2>
                                    <p class="mt-0.5 text-xs text-zinc-400">От согласования медиаплана до эфирной справки.</p>
                                </div>
                                <button type="button" class="shrink-0 text-xs font-semibold text-zinc-400 hover:text-rose-500" @click="resetDeal">Сбросить</button>
                            </div>

                            <div class="mt-4">
                                <div class="mb-1 flex items-center justify-between text-[11px] font-bold text-zinc-400">
                                    <span>Прогресс</span>
                                    <span>{{ dealProgress }}%</span>
                                </div>
                                <div class="h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-white/10">
                                    <div class="h-full rounded-full bg-cyan-500 transition-all" :style="{ width: dealProgress + '%' }" />
                                </div>
                            </div>

                            <div class="mt-4 space-y-2">
                                <div v-for="(item, i) in dealChecklist" :key="item.id" class="rounded-xl border p-3 transition" :class="checkedDeal.has(item.id) ? 'border-emerald-200 bg-emerald-50/60 dark:border-emerald-900/50 dark:bg-emerald-500/5' : 'border-zinc-100 dark:border-white/5'">
                                    <label class="flex cursor-pointer items-start gap-3">
                                        <input type="checkbox" class="mt-0.5 rounded text-cyan-600 focus:ring-cyan-400" :checked="checkedDeal.has(item.id)" @change="toggleDeal(item.id)" />
                                        <span class="text-sm font-semibold" :class="checkedDeal.has(item.id) ? 'text-zinc-500 line-through' : 'text-zinc-800 dark:text-zinc-100'">{{ i + 1 }}. {{ item.title }}</span>
                                    </label>
                                    <p v-if="item.note" class="mt-1.5 ml-7 text-xs text-zinc-500 dark:text-zinc-400">{{ item.note }}</p>
                                    <ul v-if="item.subItems" class="mt-1.5 ml-7 space-y-1">
                                        <li v-for="(sub, si) in item.subItems" :key="si" class="text-xs text-zinc-500 dark:text-zinc-400">— {{ sub }}</li>
                                    </ul>
                                </div>
                            </div>
                        </section>

                        <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/5 dark:bg-zinc-900/60">
                            <button type="button" class="flex w-full items-center justify-between text-left" @click="showTrackChecklist = !showTrackChecklist">
                                <div>
                                    <h2 class="text-sm font-bold text-zinc-900 dark:text-white">Если нет рекламного трека — изготовление РТ</h2>
                                    <p class="mt-0.5 text-xs text-zinc-400">10 шагов от брифа до готового ролика.</p>
                                </div>
                                <svg class="h-4 w-4 shrink-0 text-zinc-400 transition-transform" :class="{ 'rotate-180': showTrackChecklist }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
                                </svg>
                            </button>

                            <div v-if="showTrackChecklist" class="mt-4">
                                <div class="flex justify-end">
                                    <button type="button" class="text-xs font-semibold text-zinc-400 hover:text-rose-500" @click="resetTrack">Сбросить</button>
                                </div>
                                <div class="mt-2 space-y-2">
                                    <label
                                        v-for="(step, i) in adTrackChecklist"
                                        :key="i"
                                        class="flex cursor-pointer items-start gap-3 rounded-xl border p-3 text-sm transition"
                                        :class="checkedTrack.has(i) ? 'border-emerald-200 bg-emerald-50/60 text-zinc-500 line-through dark:border-emerald-900/50 dark:bg-emerald-500/5' : 'border-zinc-100 text-zinc-700 hover:bg-zinc-50 dark:border-white/5 dark:text-zinc-300 dark:hover:bg-white/5'"
                                    >
                                        <input type="checkbox" class="mt-0.5 rounded text-cyan-600 focus:ring-cyan-400" :checked="checkedTrack.has(i)" @change="toggleTrack(i)" />
                                        {{ i + 1 }}. {{ step }}
                                    </label>
                                </div>
                            </div>
                        </section>
                    </template>

                    <!-- ============== ЭТАПЫ ПРОДАЖ (карта) ============== -->
                    <template v-if="activeTab === 'stages'">
                        <section class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div v-for="(stage, i) in salesStages" :key="stage.title" class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/5 dark:bg-zinc-900/60">
                                <div class="flex items-center gap-2.5">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-cyan-100 text-xs font-bold text-cyan-700 dark:bg-cyan-500/10 dark:text-cyan-300">{{ i + 1 }}</span>
                                    <h3 class="font-bold text-zinc-800 dark:text-white">{{ stage.title }}</h3>
                                </div>
                                <ul class="mt-3 space-y-1.5">
                                    <li v-for="(p, pi) in stage.points" :key="pi" class="flex gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                                        <span class="text-cyan-400">·</span>{{ p }}
                                    </li>
                                </ul>
                            </div>
                        </section>
                    </template>

                </div>
            </div>
        </div>

        <ToastContainer />
    </AuthenticatedLayout>
</template>
