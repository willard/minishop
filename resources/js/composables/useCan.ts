import { usePage } from '@inertiajs/vue3';

export type UseCanReturn = {
    can: (permission: string) => boolean;
    hasRole: (role: string) => boolean;
};

export function useCan(): UseCanReturn {
    const page = usePage();

    function can(permission: string): boolean {
        // Super-admins bypass all permission checks via Gate::before on the backend,
        // which means their permissions array is empty (permissions are never explicitly
        // assigned to the super-admin role). Without this client-side bypass, the UI
        // would incorrectly hide all permission-gated elements for super-admins.
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
