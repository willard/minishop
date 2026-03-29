import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import RevenueChart from '@/components/RevenueChart.vue';

vi.mock('vue-chartjs', () => ({
    Bar: {
        name: 'Bar',
        template: '<canvas data-testid="bar-chart" />',
        props: ['data', 'options'],
    },
}));

vi.mock('chart.js', () => ({
    Chart: { register: vi.fn() },
    BarElement: {},
    CategoryScale: {},
    LinearScale: {},
    Tooltip: {},
}));

const sampleData = [
    { label: 'Jan 2025', revenue: 10000 },
    { label: 'Feb 2025', revenue: 20000 },
    { label: 'Mar 2025', revenue: 0 },
];

describe('RevenueChart', () => {
    it('renders without errors', () => {
        const wrapper = mount(RevenueChart, { props: { data: sampleData } });
        expect(wrapper.exists()).toBe(true);
    });

    it('renders the Bar chart', () => {
        const wrapper = mount(RevenueChart, { props: { data: sampleData } });
        expect(wrapper.find('[data-testid="bar-chart"]').exists()).toBe(true);
    });

    it('passes correct labels to chart data', () => {
        const wrapper = mount(RevenueChart, { props: { data: sampleData } });
        const bar = wrapper.findComponent({ name: 'Bar' });
        expect(bar.props('data').labels).toEqual(['Jan 2025', 'Feb 2025', 'Mar 2025']);
    });

    it('converts revenue from cents to dollars', () => {
        const wrapper = mount(RevenueChart, { props: { data: sampleData } });
        const bar = wrapper.findComponent({ name: 'Bar' });
        expect(bar.props('data').datasets[0].data).toEqual([100, 200, 0]);
    });
});
