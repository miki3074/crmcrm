<script setup>
import { computed } from 'vue'
import VChart from 'vue-echarts'
import * as echarts from 'echarts'
// Те же импорты use(...), можно не дублировать если они глобальны, но лучше оставить для автономности

const props = defineProps({
    stats: Array,
    loading: Boolean,
    projectName: String
})

const option = computed(() => ({
    backgroundColor: 'transparent',
    tooltip: {
        trigger: 'item',
        backgroundColor: 'rgba(30, 41, 59, 0.9)',
        borderColor: '#334155',
        textStyle: { color: '#f8fafc' },
        formatter: (params) => {
            const t = props.stats[params.dataIndex]
            const status = t.is_overdue ? '❌ Просрочено' : t.subtasks_overdue > 0 ? '⚠️ Риск' : '✅ OK'
            return `<b>${t.title}</b><br/>Прогресс: ${t.progress}%<br/>${status}`
        }
    },
    grid: { left: '3%', right: '4%', bottom: '15%', containLabel: true },
    xAxis: {
        type: 'category',
        data: props.stats.map(t => t.title),
        axisLabel: { rotate: 25, color: '#64748b', formatter: v => v.length > 12 ? v.slice(0, 12) + '...' : v }
    },
    yAxis: { type: 'value', max: 100, splitLine: { lineStyle: { color: '#f1f5f9' } } },
    dataZoom: [{ type: 'slider', show: true, bottom: 5, height: 15 }],
    series: [{
        type: 'bar',
        barWidth: '50%',
        data: props.stats.map(t => {
            if (t.is_overdue) return { value: t.progress, itemStyle: { color: '#ef4444' } }
            if (t.subtasks_overdue > 0) return {
                value: t.progress,
                itemStyle: { color: new echarts.graphic.LinearGradient(0,0,0,1, [{offset:0, color:'#22c55e'}, {offset:1, color:'#f59e0b'}]) }
            }
            return { value: t.progress, itemStyle: { color: t.progress >= 80 ? '#22c55e' : '#3b82f6' } }
        }),
        itemStyle: { borderRadius: [4, 4, 0, 0] }
    }]
}))
</script>

<template>
    <div class="relative p-6 rounded-3xl bg-gradient-to-br from-white to-slate-50 dark:from-slate-800 dark:to-slate-900 shadow-lg border border-indigo-100 dark:border-slate-700">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-bold text-slate-800 dark:text-white">
                📈 {{ projectName || 'Выберите проект' }}
            </h3>
            <span v-if="stats.length" class="text-xs bg-slate-200 dark:bg-slate-700 px-2 py-1 rounded text-slate-600">
                Задач: {{ stats.length }}
            </span>
        </div>

        <div v-if="loading" class="h-[400px] flex items-center justify-center text-slate-400 animate-pulse">
            Загрузка статистики...
        </div>
        <div v-else-if="!stats.length" class="h-[400px] flex items-center justify-center text-slate-400 italic">
            Нет данных или проект не выбран
        </div>
        <div v-else class="h-[400px]">
            <v-chart :option="option" autoresize />
        </div>
    </div>
</template>
