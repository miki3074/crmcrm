<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    deals: Object,
    companies: Array,
    stats: Object,
    filters: Object
});

const selectedCompany = ref(props.filters.company_id || '');

watch(selectedCompany, (val) => {
    router.get(route('deals.index'), { company_id: val }, { preserveState: true });
});

const statusClasses = (status) => {
    const map = {
        'Переговоры': 'bg-blue-50 text-blue-600',
        'Успешно': 'bg-emerald-50 text-emerald-600',
        'Отказ': 'bg-rose-50 text-rose-600',
        'КП отправлено': 'bg-indigo-50 text-indigo-600'
    };
    return map[status] || 'bg-slate-50 text-slate-500';
};
</script>

<template>
    <Head title="Все сделки" />
    <div class="min-h-screen bg-[#f8faff] p-8">
        <div class="max-w-6xl mx-auto">

            <!-- ЗАГОЛОВОК И ФИЛЬТР -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                <div>
                    <h1 class="text-3xl font-black text-slate-800 tracking-tight">Сделки</h1>
                    <p class="text-slate-400 text-sm font-bold">Всего на сумму: {{ new Intl.NumberFormat('ru-RU').format(stats.total_sum) }} ₽</p>
                </div>

                <div class="flex gap-4 w-full md:w-auto">
                    <select v-model="selectedCompany" class="bg-white border-none shadow-sm rounded-2xl text-sm font-bold text-slate-600 focus:ring-blue-500 px-6 py-3">
                        <option value="">Все компании</option>
                        <option v-for="c in companies" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>
            </div>

            <!-- СПИСОК СДЕЛОК (КАРТОЧКИ) -->
            <div class="grid grid-cols-1 gap-4">
                <div v-for="deal in deals.data" :key="deal.id" class="bg-white rounded-[2rem] p-6 shadow-sm border border-blue-50/50 hover:shadow-md transition group">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">

                        <!-- Инфо о сделке -->
                        <div class="flex items-center gap-4 flex-1">
                            <div class="h-12 w-12 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600 font-bold">
                                {{ deal.name.charAt(0) }}
                            </div>
                            <div>
                                <Link :href="route('klient-deals.show', deal.id)" class="text-lg font-black text-slate-800 hover:text-blue-600 transition">
                                    {{ deal.name }}
                                </Link>
                                <div class="flex items-center gap-2 text-xs text-slate-400 font-bold mt-0.5">
                                    <i class="fas fa-building text-[10px]"></i>
                                    {{ deal.klient?.name }}
                                    <span v-if="deal.klient?.company" class="opacity-50">• {{ deal.klient.company.name }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Статус и Сумма -->
                        <div class="flex items-center gap-8 w-full md:w-auto justify-between">
                            <div class="text-right hidden sm:block">
                                <div class="text-[10px] text-slate-300 font-black uppercase tracking-widest">Сумма</div>
                                <div class="text-xl font-black text-slate-800 tracking-tighter">
                                    {{ new Intl.NumberFormat('ru-RU').format(deal.total_amount) }} ₽
                                </div>
                            </div>

                            <div :class="['px-4 py-2 rounded-xl text-xs font-black uppercase tracking-tighter whitespace-nowrap', statusClasses(deal.status)]">
                                {{ deal.status }}
                            </div>

                            <Link :href="route('klient-deals.show', deal.id)" class="h-10 w-10 bg-slate-50 rounded-xl flex items-center justify-center text-slate-400 group-hover:bg-blue-600 group-hover:text-white transition">
                                <i class="fas fa-chevron-right"></i>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Пагинация -->
            <div v-if="deals.links.length > 3" class="mt-10 flex justify-center gap-2">
                <Link v-for="link in deals.links" :key="link.label" :href="link.url || '#'" v-html="link.label"
                      class="px-4 py-2 rounded-xl text-sm font-black transition"
                      :class="{'bg-blue-600 text-white shadow-lg shadow-blue-100': link.active, 'text-slate-400 hover:bg-white': link.url && !link.active, 'opacity-0': !link.url}" />
            </div>

        </div>
    </div>
</template>
