import { usePage } from '@inertiajs/vue3';
import type { Auth } from '@/types';

export type UseCanReturn = {
    can: (permission: string) => boolean;
    hasRole: (role: string) => boolean;
};

export function useCan(): UseCanReturn {
    const page = usePage<{ auth: Auth }>();

    function can(permission: string): boolean {
        if (page.props.auth.roles.includes('super-admin')) {
            return true;
        }

        return page.props.auth.permissions.includes(permission);
    }

    function hasRole(role: string): boolean {
        return page.props.auth.roles.includes(role);
    }

    return { can, hasRole };
}
