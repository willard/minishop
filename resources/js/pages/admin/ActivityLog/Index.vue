<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { type BreadcrumbItem } from '@/types';
import { index } from '@/actions/App/Http/Controllers/Admin/ActivityLogController';

interface User {
    id: number;
    name: string;
}

interface ActivityLog {
    id: number;
    action: string;
    subject_type: string;
    subject_id: number;
    description: string;
    properties: Record<string, unknown> | null;
    created_at: string;
    user: User | null;
}

interface Pagination {
    data: ActivityLog[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

defineProps<{
    logs: Pagination;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Activity Log', href: index().url },
];

function actionVariant(action: string): 'default' | 'secondary' | 'destructive' {
    switch (action) {
        case 'created':
            return 'default';
        case 'deleted':
            return 'destructive';
        default:
            return 'secondary';
    }
}

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleString();
}
</script>

<template>
    <Head title="Activity Log" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-semibold">Activity Log</h1>
                <p class="text-sm text-muted-foreground">{{ logs.total }} total entries</p>
            </div>

            <!-- Table -->
            <div class="rounded-lg border border-sidebar-border overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">When</th>
                            <th class="px-4 py-3 text-left font-medium">User</th>
                            <th class="px-4 py-3 text-left font-medium">Action</th>
                            <th class="px-4 py-3 text-left font-medium">Description</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sidebar-border">
                        <tr v-if="logs.data.length === 0">
                            <td colspan="4" class="px-4 py-8 text-center text-muted-foreground">
                                No activity recorded yet.
                            </td>
                        </tr>
                        <tr
                            v-for="log in logs.data"
                            :key="log.id"
                            class="hover:bg-muted/30 transition-colors"
                        >
                            <td class="px-4 py-3 text-xs text-muted-foreground whitespace-nowrap">
                                {{ formatDate(log.created_at) }}
                            </td>
                            <td class="px-4 py-3 text-xs">
                                {{ log.user?.name ?? 'System' }}
                            </td>
                            <td class="px-4 py-3">
                                <Badge :variant="actionVariant(log.action)" class="capitalize">
                                    {{ log.action }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3">
                                {{ log.description }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="logs.last_page > 1" class="flex justify-center gap-1">
                <template v-for="link in logs.links" :key="link.label">
                    <a
                        v-if="link.url"
                        :href="link.url"
                        class="px-3 py-1.5 rounded text-sm border border-sidebar-border hover:bg-muted/50 transition-colors"
                        :class="{ 'bg-primary text-primary-foreground border-primary': link.active }"
                    ><span v-html="link.label" /></a>
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
