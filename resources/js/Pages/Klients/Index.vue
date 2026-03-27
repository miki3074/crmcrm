<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import ApplicationLogo from "@/Components/ApplicationLogo.vue";

const props = defineProps({
    stats: Object,
    recent_klients: Array,
    companies: Array,
    filters: Object
});

// Логика фильтрации
const selectedCompany = ref(props.filters.company_id || '');

watch(selectedCompany, (value) => {
    router.get(route('klients.index'), { company_id: value }, {
        preserveState: true,
        replace: true
    });
});

const funnelRows = [
    { label: 'Переговоры', key: 'Переговоры', color: 'bg-orange-400' },
    { label: 'КП отправлено', key: 'КП отправлено', color: 'bg-blue-500' },
    { label: 'Согласование', key: 'Согласование договора', color: 'bg-purple-500' },
    { label: 'Успешно', key: 'Успешно', color: 'bg-emerald-500' },
    { label: 'Отказ', key: 'Отказ', color: 'bg-rose-500' },
];

const formatPrice = (value) => {
    if (value >= 1000000) return (value / 1000000).toFixed(1) + 'M ₽';
    return (value / 1000).toFixed(0) + 'K ₽';
};
</script>

<template>
    <div class="min-h-screen bg-[#f8faff] p-8">
        <div class="max-w-5xl mx-auto">

            <!-- СТРОКА УПРАВЛЕНИЯ -->
            <div class="flex justify-between items-center mb-8">
                <div class="flex items-center gap-4">

                    <Link :href="route('dashboard')" class="flex-shrink-0 transition-transform duration-300 hover:scale-105">
                        <ApplicationLogo class="block h-8 w-auto fill-current " />
                    </Link>
                    <!-- Фильтр компаний -->
                    <select
                        v-model="selectedCompany"
                        class="bg-white border-none shadow-sm rounded-xl text-sm font-bold text-slate-600 focus:ring-blue-500 px-4 py-2"
                    >
                        <option value="">Все компании</option>
                        <option v-for="c in companies" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>

                <Link :href="route('klients.create')" class="bg-blue-600 text-white px-6 py-2 rounded-xl font-bold text-sm shadow-lg shadow-blue-100 hover:bg-blue-700 transition">
                    + Добавить клиента
                </Link>
            </div>

            <!-- ВЕРХНИЙ РЯД (Статистика) -->
            <div class="grid grid-cols-2 gap-6 mb-8">
                <div class="bg-white rounded-[2.5rem] p-8 shadow-sm border border-blue-50/50">
                    <div class="flex items-center text-blue-500 mb-4 font-bold text-xs uppercase tracking-widest">
                        <i class="far fa-user mr-2"></i> Активные клиенты
                    </div>
                    <div class="text-6xl font-black text-slate-800 mb-2">{{ stats.active_clients }}</div>
                    <div class="text-blue-500 text-sm font-bold">+{{ stats.new_this_month }} за месяц</div>
                </div>

                <div class="bg-white rounded-[2.5rem] p-8 shadow-sm border border-blue-50/50">
                    <div class="flex items-center text-blue-500 mb-4 font-bold text-xs uppercase tracking-widest">
                        <i class="fas fa-list-ul mr-2"></i> Сделок в работе
                    </div>
                    <div class="text-6xl font-black text-slate-800 mb-2">{{ stats.deals_count }}</div>
                    <div class="text-blue-400 text-sm font-bold uppercase tracking-tight">сумма ~ {{ formatPrice(stats.deals_sum) }}</div>
                </div>
            </div>

            <!-- НИЖНИЙ РЯД -->
            <div class="grid grid-cols-2 gap-6">

                <!-- Список клиентов -->
                <div class="bg-white rounded-[2.5rem] shadow-sm border border-blue-50/50 flex flex-col">
                    <div class="p-8 pb-4 flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <div class="bg-blue-50 p-2.5 rounded-2xl text-blue-600">
                                <i class="fas fa-users"></i>
                            </div>
                            <h2 class="text-xl font-extrabold text-slate-800 tracking-tight">Клиенты</h2>
                        </div>
                        <span class="bg-slate-50 text-slate-400 text-[10px] px-3 py-1 rounded-full font-black uppercase">новые {{ stats.new_this_month }}</span>
                    </div>

                    <div class="px-8 flex-1">
                        <div v-for="klient in recent_klients" :key="klient.id" class="flex items-center justify-between py-4 border-b border-slate-50 last:border-0">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-building text-blue-200"></i>
                                <Link :href="route('klients.show', klient.id)" class="text-sm font-bold text-slate-600 hover:text-blue-600 transition">
                                    {{ klient.name }}
                                </Link>
                            </div>
                            <span :class="[
                                'text-[10px] px-3 py-1 rounded-lg font-black uppercase tracking-tighter',
                                klient.status === 'Действующий' ? 'bg-emerald-50 text-emerald-600' : 'bg-blue-50 text-blue-600'
                            ]">
                                {{ klient.status === 'Действующий' ? 'активен' : 'новый' }}
                            </span>
                        </div>
                    </div>

                    <div class="p-8 pt-4">
                        <Link :href="route('klients.index')" class="text-blue-500 text-xs font-black flex items-center justify-end hover:text-blue-700 uppercase tracking-widest">
                            → все контакты ({{ stats.total_count }})
                        </Link>
                    </div>
                </div>

                <!-- Воронка сделок -->
                <div class="bg-white rounded-[2.5rem] shadow-sm border border-blue-50/50 p-8">
                    <div class="flex justify-between items-center mb-8">
                        <div class="flex items-center gap-3">
                            <div class="bg-blue-50 p-2.5 rounded-2xl text-blue-600">
                                <i class="fas fa-exchange-alt"></i>
                            </div>
                            <h2 class="text-xl font-extrabold text-slate-800 tracking-tight">Сделки</h2>

                            <Link :href="route('deals.index')" class="bg-slate-50 text-slate-400 text-[10px] px-3 py-1 rounded-full font-black uppercase tracking-wider hover:bg-blue-600 hover:text-white transition">
                                подробности
                            </Link>
                        </div>
                        <span class="bg-slate-50 text-slate-400 text-[10px] px-3 py-1 rounded-full font-black uppercase">воронка</span>
                    </div>

                    <div class="space-y-5">
                        <div v-for="row in funnelRows" :key="row.key" class="flex justify-between items-center group cursor-default">
                            <div class="flex items-center gap-3">
                                <div :class="['w-2.5 h-2.5 rounded-full shadow-sm', row.color]"></div>
                                <span class="text-sm font-bold text-slate-500 group-hover:text-slate-900 transition">{{ row.label }}</span>
                            </div>
                            <span class="text-sm font-black text-slate-800 bg-slate-50 px-2 py-1 rounded-lg">{{ stats.funnel[row.key] || 0 }}</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>
