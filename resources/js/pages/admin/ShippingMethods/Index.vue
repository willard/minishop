<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import {
    index,
    create,
    edit,
    destroy,
} from '@/actions/App/Http/Controllers/Admin/ShippingMethodController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface ShippingMethod {
    id: number;
    name: string;
    description: string | null;
    price: number;
    is_free: boolean;
    is_active: boolean;
    sort_order: number;
}

defineProps<{
    shippingMethods: ShippingMethod[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Shipping Methods', href: index().url },
];

function formatPrice(cents: number): string {
    return `₱${(cents / 100).toFixed(2)}`;
}

function confirmDelete(method: ShippingMethod): void {
    if (confirm(`Delete "${method.name}"? This cannot be undone.`)) {
        router.delete(destroy(method.id).url);
    }
}
</script>

<template>
    <Head title="Shipping Methods" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Shipping Methods</h1>
                    <p class="text-sm text-muted-foreground">
                        {{ shippingMethods.length }} method{{
                            shippingMethods.length === 1 ? '' : 's'
                        }}
                    </p>
                </div>
                <Link :href="create().url">
                    <Button>
                        <Plus class="mr-2 size-4" />
                        Add Method
                    </Button>
                </Link>
            </div>

            <!-- Table -->
            <div
                class="overflow-hidden rounded-lg border border-sidebar-border"
            >
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">
                                Order
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Name
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Description
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Price
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Status
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sidebar-border">
                        <tr v-if="shippingMethods.length === 0">
                            <td
                                colspan="6"
                                class="px-4 py-8 text-center text-muted-foreground"
                            >
                                No shipping methods yet.
                                <Link
                                    :href="create().url"
                                    class="ml-1 text-primary underline"
                                    >Add your first method</Link
                                >
                            </td>
                        </tr>
                        <tr
                            v-for="method in shippingMethods"
                            :key="method.id"
                            class="transition-colors hover:bg-muted/30"
                        >
                            <td
                                class="px-4 py-3 text-muted-foreground tabular-nums"
                            >
                                {{ method.sort_order }}
                            </td>
                            <td class="px-4 py-3 font-medium">
                                {{ method.name }}
                            </td>
                            <td
                                class="max-w-xs truncate px-4 py-3 text-muted-foreground"
                            >
                                {{ method.description ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    v-if="method.is_free"
                                    class="font-medium text-green-600"
                                    >Free</span
                                >
                                <span v-else>{{
                                    formatPrice(method.price)
                                }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <Badge
                                    :variant="
                                        method.is_active
                                            ? 'default'
                                            : 'secondary'
                                    "
                                >
                                    {{
                                        method.is_active ? 'Active' : 'Inactive'
                                    }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <Link :href="edit(method.id).url">
                                        <Button variant="ghost" size="sm">
                                            <Pencil class="size-4" />
                                        </Button>
                                    </Link>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        class="text-destructive hover:text-destructive"
                                        @click="confirmDelete(method)"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
