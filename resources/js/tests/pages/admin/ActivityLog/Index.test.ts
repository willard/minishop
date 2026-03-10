import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import IndexPage from '@/pages/admin/ActivityLog/Index.vue';

vi.mock('@inertiajs/vue3', () => ({
    Head: { name: 'Head', template: '<div />', props: ['title'] },
    Link: {
        name: 'Link',
        template: '<a href="#"><slot /></a>',
        props: ['href'],
    },
}));

vi.mock('@/layouts/AppLayout.vue', () => ({
    default: {
        name: 'AppLayout',
        template: '<div><slot /></div>',
        props: ['breadcrumbs'],
    },
}));

vi.mock('@/components/ui/badge', () => ({
    Badge: {
        name: 'Badge',
        template: '<span class="badge"><slot /></span>',
        props: ['variant'],
    },
}));

vi.mock('@/actions/App/Http/Controllers/Admin/ActivityLogController', () => ({
    index: vi.fn(() => ({ url: '/dashboard/activity-log' })),
}));

const baseLogs = {
    data: [
        {
            id: 1,
            action: 'created',
            subject_type: 'Product',
            subject_id: 10,
            description: 'Created product "Test Widget"',
            properties: null,
            created_at: '2026-02-25T10:00:00.000Z',
            user: { id: 1, name: 'Admin User' },
        },
        {
            id: 2,
            action: 'updated',
            subject_type: 'Order',
            subject_id: 5,
            description: 'Updated order ORD-0005 status to Shipped',
            properties: { status: 'shipped' },
            created_at: '2026-02-25T11:00:00.000Z',
            user: { id: 1, name: 'Admin User' },
        },
        {
            id: 3,
            action: 'deleted',
            subject_type: 'Coupon',
            subject_id: 3,
            description: 'Deleted coupon SAVE20',
            properties: null,
            created_at: '2026-02-25T12:00:00.000Z',
            user: null,
        },
    ],
    current_page: 1,
    last_page: 1,
    per_page: 50,
    total: 3,
    links: [],
};

describe('admin/ActivityLog/Index', () => {
    let wrapper: ReturnType<typeof mount>;

    beforeEach(() => {
        wrapper = mount(IndexPage, {
            props: { logs: baseLogs },
        });
    });

    it('renders without errors', () => {
        expect(wrapper.exists()).toBe(true);
    });

    it('displays the total entries count', () => {
        expect(wrapper.text()).toContain('3 total entries');
    });

    it('displays log descriptions', () => {
        expect(wrapper.text()).toContain('Created product "Test Widget"');
        expect(wrapper.text()).toContain(
            'Updated order ORD-0005 status to Shipped',
        );
        expect(wrapper.text()).toContain('Deleted coupon SAVE20');
    });

    it('displays user names', () => {
        expect(wrapper.text()).toContain('Admin User');
    });

    it('shows "System" when user is null', () => {
        expect(wrapper.text()).toContain('System');
    });

    it('displays action badges for each log entry', () => {
        const badges = wrapper.findAll('.badge');
        const badgeTexts = badges.map((b) => b.text().trim().toLowerCase());
        expect(badgeTexts).toContain('created');
        expect(badgeTexts).toContain('updated');
        expect(badgeTexts).toContain('deleted');
    });

    it('shows empty state when no activity', () => {
        const emptyWrapper = mount(IndexPage, {
            props: { logs: { ...baseLogs, data: [], total: 0 } },
        });
        expect(emptyWrapper.text()).toContain('No activity recorded yet.');
    });

    it('does not render pagination when only one page', () => {
        expect(wrapper.findAll('a[href]').length).toBe(0);
    });

    it('renders pagination links when multiple pages exist', () => {
        const paginatedWrapper = mount(IndexPage, {
            props: {
                logs: {
                    ...baseLogs,
                    last_page: 3,
                    links: [
                        { url: null, label: '&laquo; Previous', active: false },
                        {
                            url: '/dashboard/activity-log?page=1',
                            label: '1',
                            active: true,
                        },
                        {
                            url: '/dashboard/activity-log?page=2',
                            label: '2',
                            active: false,
                        },
                        {
                            url: '/dashboard/activity-log?page=3',
                            label: '3',
                            active: false,
                        },
                        {
                            url: '/dashboard/activity-log?page=2',
                            label: 'Next &raquo;',
                            active: false,
                        },
                    ],
                },
            },
        });
        const links = paginatedWrapper.findAll('a');
        expect(links.length).toBeGreaterThanOrEqual(3);
    });
});
