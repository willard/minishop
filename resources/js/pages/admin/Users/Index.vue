<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { type BreadcrumbItem } from '@/types';
import { index, create, edit, destroy } from '@/actions/App/Http/Controllers/Admin/UserController';
import { useCan } from '@/composables/useCan';

interface Role {
    id: number;
    name: string;
}

interface StaffUser {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    created_at: string;
    roles: Role[];
}

interface Pagination {
    data: StaffUser[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

defineProps<{
    users: Pagination;
}>();

const page = usePage();
const { can } = useCan();
const currentUserId = page.props.auth.user.id;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Users', href: index().url },
];

function roleBadgeVariant(roleName: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (roleName) {
        case 'super-admin':
            return 'destructive';
        case 'admin':
            return 'default';
        default:
            return 'secondary';
    }
}

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function confirmDelete(user: StaffUser): void {
    if (confirm(`Delete user "${user.name}"? This cannot be undone.`)) {
        router.delete(destroy(user).url);
    }
}
</script>

<template>
    <Head title="Users" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Users</h1>
                    <p class="text-sm text-muted-foreground">{{ users.total }} staff user{{ users.total === 1 ? '' : 's' }}</p>
                </div>
                <Link v-if="can('users.create')" :href="create().url">
                    <Button>
                        <Plus class="mr-2 size-4" />
                        Add User
                    </Button>
                </Link>
            </div>

            <!-- Table -->
            <div class="rounded-lg border border-sidebar-border overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Name</th>
                            <th class="px-4 py-3 text-left font-medium">Email</th>
                            <th class="px-4 py-3 text-left font-medium">Role</th>
                            <th class="px-4 py-3 text-left font-medium">Created</th>
                            <th class="px-4 py-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sidebar-border">
                        <tr v-if="users.data.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">
                                No users yet.
                                <Link :href="create().url" class="text-primary underline ml-1">Create your first user</Link>
                            </td>
                        </tr>
                        <tr
                            v-for="user in users.data"
                            :key="user.id"
                            class="hover:bg-muted/30 transition-colors"
                        >
                            <td class="px-4 py-3 font-medium">
                                {{ user.name }}
                                <span v-if="user.id === currentUserId" class="text-xs text-muted-foreground ml-1">(you)</span>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">{{ user.email }}</td>
                            <td class="px-4 py-3">
                                <Badge :variant="roleBadgeVariant(user.roles[0]?.name ?? '')">
                                    {{ user.roles[0]?.name ?? 'No role' }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">{{ formatDate(user.created_at) }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <Link v-if="can('users.update')" :href="edit(user).url">
                                        <Button variant="ghost" size="sm">
                                            <Pencil class="size-4" />
                                        </Button>
                                    </Link>
                                    <Button
                                        v-if="can('users.delete') && user.id !== currentUserId"
                                        variant="ghost"
                                        size="sm"
                                        class="text-destructive hover:text-destructive"
                                        @click="confirmDelete(user)"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="users.last_page > 1" class="flex justify-center gap-1">
                <template v-for="link in users.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="px-3 py-1.5 rounded text-sm border border-sidebar-border hover:bg-muted/50 transition-colors"
                        :class="{ 'bg-primary text-primary-foreground border-primary': link.active }"
                    ><span v-html="link.label" /></Link>
                    <span
                        v-else
                        class="px-3 py-1.5 rounded text-sm border border-sidebar-border text-muted-foreground opacity-50"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </AppLayout>
</template>
