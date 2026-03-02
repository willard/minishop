import { usePage } from '@inertiajs/vue3';
import { describe, expect, it, vi } from 'vitest';
import { useCan } from '@/composables/useCan';

vi.mock('@inertiajs/vue3', () => ({
    usePage: vi.fn(),
}));

function mockPage(roles: string[], permissions: string[]) {
    (usePage as ReturnType<typeof vi.fn>).mockReturnValue({
        props: {
            auth: { roles, permissions },
        },
    });
}

describe('useCan', () => {
    it('grants permission when user has it', () => {
        mockPage(['admin'], ['products.view', 'products.create']);
        const { can } = useCan();

        expect(can('products.view')).toBe(true);
        expect(can('products.create')).toBe(true);
    });

    it('denies permission when user lacks it', () => {
        mockPage(['admin'], ['products.view']);
        const { can } = useCan();

        expect(can('products.delete')).toBe(false);
    });

    it('grants all permissions to super-admin', () => {
        mockPage(['super-admin'], []);
        const { can } = useCan();

        expect(can('products.view')).toBe(true);
        expect(can('settings.update')).toBe(true);
        expect(can('any.permission')).toBe(true);
    });

    it('checks role membership with hasRole', () => {
        mockPage(['admin'], ['products.view']);
        const { hasRole } = useCan();

        expect(hasRole('admin')).toBe(true);
        expect(hasRole('super-admin')).toBe(false);
        expect(hasRole('manager')).toBe(false);
    });

    it('returns false for empty permissions', () => {
        mockPage(['manager'], []);
        const { can } = useCan();

        expect(can('products.view')).toBe(false);
    });
});
