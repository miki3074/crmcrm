<script setup>
import { computed } from 'vue'
import VChart from 'vue-echarts'
// (Импорты echarts можно вынести в отдельный плагин, но оставим тут для ясности)
import { use } from 'echarts/core'
import { CanvasRenderer } from 'echarts/renderers'
import { BarChart } from 'echarts/charts'
import { GridComponent, TooltipComponent, DataZoomComponent } from 'echarts/components'

use([CanvasRenderer, BarChart, GridComponent, TooltipComponent, DataZoomComponent])

const props = defineProps({
    projects: Array
})

const emit = defineEmits(['projectClick'])

const option = computed(() => ({
    tooltip: {
        trigger: 'axis',
        axisPointer: { type: 'shadow' },
        backgroundColor: 'rgba(255, 255, 255, 0.95)',
        borderRadius: 12,
        padding: 12,
        textStyle: { color: '#1e293b' },
        extraCssText: 'box-shadow: 0 4px 20px rgba(0,0,0,0.1); backdrop-filter: blur(4px);'
    },
    grid: { left: '3%', right: '4%', bottom: '15%', containLabel: true },
    xAxis: {
        type: 'category',
        data: props.projects?.map(p => p.name) || [],
        axisLabel: {
            rotate: 20,
            interval: 0,
            formatter: v => v.length > 10 ? v.slice(0, 10) + '...' : v,
            color: '#64748b'
        },
        axisLine: { lineStyle: { color: '#e2e8f0' } }
    },
    yAxis: {
        type: 'value',
        name: 'Дней',
        splitLine: { lineStyle: { type: 'dashed', color: '#f1f5f9' } }
    },
    dataZoom: [{ type: 'slider', show: true, bottom: 10, height: 20, borderColor: 'transparent', handleStyle: { color: '#6366f1' } }],
    series: [{
        type: 'bar',
        barWidth: '40%',
        itemStyle: { borderRadius: [6, 6, 0, 0], color: '#6366f1' },
        data: props.projects?.map(p => p.duration_days) || [],
        label: { show: true, position: 'top', formatter: '{c}д' }
    }]
}))

const onClick = (params) => emit('projectClick', params.name)
</script>

<template>
    <div class="p-6 bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-700">
        <h3 class="text-lg font-bold text-slate-700 dark:text-slate-200 mb-4">📊 Длительность проектов</h3>
        <div class="h-[350px]">
            <v-chart :option="option" autoresize @click="onClick" />
        </div>
    </div>
</template>
