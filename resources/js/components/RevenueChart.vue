<script setup lang="ts">
import { useDark } from '@vueuse/core';
import {
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    LinearScale,
    Tooltip,
    type TooltipItem,
} from 'chart.js';
import { computed } from 'vue';
import { Bar } from 'vue-chartjs';

ChartJS.register(CategoryScale, LinearScale, BarElement, Tooltip);

interface RevenuePoint {
    label: string;
    revenue: number;
}

const props = defineProps<{
    data: RevenuePoint[];
}>();

const CURRENCY_FORMAT: Intl.NumberFormatOptions = {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
};

const isDark = useDark();

const tickColor = computed(() => (isDark.value ? '#9ca3af' : '#6b7280'));
const barColor = computed(() =>
    isDark.value ? 'rgba(139, 92, 246, 0.7)' : 'rgba(109, 40, 217, 0.75)',
);
const gridColor = computed(() =>
    isDark.value ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)',
);

const chartData = computed(() => ({
    labels: props.data.map((d) => d.label),
    datasets: [
        {
            data: props.data.map((d) => d.revenue / 100),
            backgroundColor: barColor.value,
            borderRadius: 4,
            borderSkipped: false as const,
        },
    ],
}));

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            callbacks: {
                label: (ctx: TooltipItem<'bar'>) =>
                    ` $${ctx.parsed.y.toLocaleString('en-US', CURRENCY_FORMAT)}`,
            },
        },
    },
    scales: {
        x: {
            grid: { display: false },
            ticks: { color: tickColor.value, font: { size: 11 } },
            border: { display: false },
        },
        y: {
            grid: { color: gridColor.value },
            ticks: {
                color: tickColor.value,
                font: { size: 11 },
                callback: (value: number | string) =>
                    `$${Number(value).toLocaleString()}`,
            },
            border: { display: false },
        },
    },
}));
</script>

<template>
    <div class="h-56">
        <Bar :data="chartData" :options="chartOptions" />
    </div>
</template>
