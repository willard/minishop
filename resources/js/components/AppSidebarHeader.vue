<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Bell } from 'lucide-vue-next';
import { computed } from 'vue';
import { index } from '@/actions/App/Http/Controllers/Admin/ProductController';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage();
const lowStockCount = computed(() => (page.props.lowStockCount as number | null | undefined) ?? null);
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <div class="ml-auto flex items-center pr-2">
            <Link
                v-if="lowStockCount !== null"
                :href="index.url({ query: { stock: 'low_stock' } })"
                class="relative inline-flex items-center rounded-md p-2 hover:bg-accent"
            >
                <Bell class="size-4" />
                <span
                    v-if="lowStockCount > 0"
                    class="absolute -right-0.5 -top-0.5 flex size-4 items-center justify-center rounded-full bg-destructive text-[10px] font-bold text-white"
                    aria-hidden="true"
                >
                    {{ lowStockCount > 99 ? '99+' : lowStockCount }}
                </span>
                <span class="sr-only">
                    {{
                        lowStockCount > 0
                            ? `${lowStockCount} low stock alert${lowStockCount === 1 ? '' : 's'}`
                            : 'Low stock alerts'
                    }}
                </span>
            </Link>
        </div>
    </header>
</template>
