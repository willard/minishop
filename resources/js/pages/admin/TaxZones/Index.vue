<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronDown, ChevronRight, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import {
    create,
    destroy,
    edit,
    index,
} from '@/actions/App/Http/Controllers/Admin/TaxZoneController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface TaxZoneRate {
    id: number;
    name: string;
    name_fr: string | null;
    rate: number;
    is_compound: boolean;
    sort_order: number;
}

interface TaxZone {
    id: number;
    name: string;
    country_code: string | null;
    province_code: string | null;
    is_active: boolean;
    priority: number;
    rates: TaxZoneRate[];
}

defineProps<{
    taxZones: {
        data: TaxZone[];
        links: unknown;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Tax Zones', href: index().url },
];

const expandedZones = ref<Set<number>>(new Set());

function toggleExpand(zoneId: number): void {
    if (expandedZones.value.has(zoneId)) {
        expandedZones.value.delete(zoneId);
    } else {
        expandedZones.value.add(zoneId);
    }
}

function confirmDelete(zone: TaxZone): void {
    if (confirm(`Delete "${zone.name}"? All rates will also be deleted. This cannot be undone.`)) {
        router.delete(destroy(zone.id).url);
    }
}
</script>

<template>
    <Head title="Tax Zones" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Tax Zones</h1>
                    <p class="text-sm text-muted-foreground">
                        Configure province and country-specific tax rates.
                    </p>
                </div>
                <Link :href="create().url">
                    <Button>
                        <Plus class="mr-2 size-4" />
                        New Zone
                    </Button>
                </Link>
            </div>

            <div class="rounded-lg border">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-muted/50 text-left">
                            <th class="px-4 py-3 font-medium">Zone</th>
                            <th class="px-4 py-3 font-medium">Country</th>
                            <th class="px-4 py-3 font-medium">Province</th>
                            <th class="px-4 py-3 font-medium">Priority</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="zone in taxZones.data" :key="zone.id">
                            <tr class="border-b hover:bg-muted/30">
                                <td class="px-4 py-3">
                                    <button
                                        class="flex items-center gap-2 font-medium"
                                        @click="toggleExpand(zone.id)"
                                    >
                                        <ChevronDown
                                            v-if="expandedZones.has(zone.id)"
                                            class="size-4 text-muted-foreground"
                                        />
                                        <ChevronRight
                                            v-else
                                            class="size-4 text-muted-foreground"
                                        />
                                        {{ zone.name }}
                                        <span class="text-xs text-muted-foreground">
                                            ({{ zone.rates.length }} rate{{ zone.rates.length === 1 ? '' : 's' }})
                                        </span>
                                    </button>
                                </td>
                                <td class="px-4 py-3">{{ zone.country_code ?? '—' }}</td>
                                <td class="px-4 py-3">{{ zone.province_code ?? '—' }}</td>
                                <td class="px-4 py-3">{{ zone.priority }}</td>
                                <td class="px-4 py-3">
                                    <Badge :variant="zone.is_active ? 'default' : 'secondary'">
                                        {{ zone.is_active ? 'Active' : 'Inactive' }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <Link :href="edit(zone.id).url">
                                            <Button size="sm" variant="ghost">
                                                <Pencil class="size-4" />
                                            </Button>
                                        </Link>
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            class="text-destructive hover:text-destructive"
                                            @click="confirmDelete(zone)"
                                        >
                                            <Trash2 class="size-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr
                                v-if="expandedZones.has(zone.id) && zone.rates.length > 0"
                                :key="`rates-${zone.id}`"
                            >
                                <td colspan="6" class="bg-muted/20 px-8 py-2">
                                    <table class="w-full text-xs">
                                        <thead>
                                            <tr class="text-left text-muted-foreground">
                                                <th class="py-1 pr-4 font-medium">Name</th>
                                                <th class="py-1 pr-4 font-medium">Rate (%)</th>
                                                <th class="py-1 pr-4 font-medium">Type</th>
                                                <th class="py-1 font-medium">Order</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr
                                                v-for="rate in zone.rates"
                                                :key="rate.id"
                                                class="border-t border-muted/40"
                                            >
                                                <td class="py-1 pr-4">
                                                    {{ rate.name }}
                                                    <span
                                                        v-if="rate.name_fr"
                                                        class="text-muted-foreground"
                                                    >
                                                        / {{ rate.name_fr }}
                                                    </span>
                                                </td>
                                                <td class="py-1 pr-4">{{ rate.rate }}%</td>
                                                <td class="py-1 pr-4">
                                                    {{ rate.is_compound ? 'Compound' : 'Simple' }}
                                                </td>
                                                <td class="py-1">{{ rate.sort_order }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </template>
                        <tr v-if="taxZones.data.length === 0">
                            <td
                                colspan="6"
                                class="px-4 py-8 text-center text-muted-foreground"
                            >
                                No tax zones configured yet.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
