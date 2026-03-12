<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import {
    index,
    create,
    edit,
    destroy,
} from '@/actions/App/Http/Controllers/Admin/CouponController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { usePrice } from '@/composables/usePrice';
import { type BreadcrumbItem } from '@/types';

interface Coupon {
    id: number;
    code: string;
    description: string | null;
    type: 'fixed' | 'percentage';
    value: number;
    minimum_order_amount: number | null;
    expiry_date: string | null;
    usage_limit: number | null;
    used_count: number;
    is_active: boolean;
}

interface Pagination {
    data: Coupon[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

defineProps<{
    coupons: Pagination;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Coupons', href: index().url },
];

const { formatPrice } = usePrice();

function formatValue(coupon: Coupon): string {
    if (coupon.type === 'percentage') {
        return `${coupon.value}%`;
    }
    return formatPrice(coupon.value);
}

function formatMinOrder(coupon: Coupon): string {
    if (coupon.minimum_order_amount === null) {
        return '—';
    }
    return formatPrice(coupon.minimum_order_amount);
}

function usageLabel(coupon: Coupon): string {
    if (coupon.usage_limit === null) {
        return `${coupon.used_count} / ∞`;
    }
    return `${coupon.used_count} / ${coupon.usage_limit}`;
}

function confirmDelete(coupon: Coupon): void {
    if (confirm(`Delete coupon "${coupon.code}"? This cannot be undone.`)) {
        router.delete(destroy(coupon).url);
    }
}
</script>

<template>
    <Head title="Coupons" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Coupons</h1>
                    <p class="text-sm text-muted-foreground">
                        {{ coupons.total }} total coupon{{
                            coupons.total === 1 ? '' : 's'
                        }}
                    </p>
                </div>
                <Link :href="create().url">
                    <Button>
                        <Plus class="mr-2 size-4" />
                        Add Coupon
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
                                Code
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Type
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Value
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Min. Order
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Expiry
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Usage
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
                        <tr v-if="coupons.data.length === 0">
                            <td
                                colspan="8"
                                class="px-4 py-8 text-center text-muted-foreground"
                            >
                                No coupons yet.
                                <Link
                                    :href="create().url"
                                    class="ml-1 text-primary underline"
                                    >Create your first coupon</Link
                                >
                            </td>
                        </tr>
                        <tr
                            v-for="coupon in coupons.data"
                            :key="coupon.id"
                            class="transition-colors hover:bg-muted/30"
                        >
                            <td
                                class="px-4 py-3 font-mono font-semibold tracking-wide"
                            >
                                {{ coupon.code }}
                            </td>
                            <td
                                class="px-4 py-3 text-muted-foreground capitalize"
                            >
                                {{ coupon.type }}
                            </td>
                            <td class="px-4 py-3 font-medium">
                                {{ formatValue(coupon) }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ formatMinOrder(coupon) }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ coupon.expiry_date ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ usageLabel(coupon) }}
                            </td>
                            <td class="px-4 py-3">
                                <Badge
                                    :variant="
                                        coupon.is_active
                                            ? 'default'
                                            : 'secondary'
                                    "
                                >
                                    {{
                                        coupon.is_active ? 'Active' : 'Inactive'
                                    }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <Link :href="edit(coupon).url">
                                        <Button variant="ghost" size="sm">
                                            <Pencil class="size-4" />
                                        </Button>
                                    </Link>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        class="text-destructive hover:text-destructive"
                                        @click="confirmDelete(coupon)"
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
            <div v-if="coupons.last_page > 1" class="flex justify-center gap-1">
                <template v-for="link in coupons.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="rounded border border-sidebar-border px-3 py-1.5 text-sm transition-colors hover:bg-muted/50"
                        :class="{
                            'border-primary bg-primary text-primary-foreground':
                                link.active,
                        }"
                        ><span v-html="link.label"
                    /></Link>
                    <span
                        v-else
                        class="rounded border border-sidebar-border px-3 py-1.5 text-sm text-muted-foreground opacity-50"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </AppLayout>
</template>
