<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
    index,
    store,
} from '@/actions/App/Http/Controllers/Admin/TaxZoneController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Tax Zones', href: index().url },
    { title: 'New Zone', href: '#' },
];

const canadianProvinces = [
    { code: 'AB', name: 'Alberta' },
    { code: 'BC', name: 'British Columbia' },
    { code: 'MB', name: 'Manitoba' },
    { code: 'NB', name: 'New Brunswick' },
    { code: 'NL', name: 'Newfoundland and Labrador' },
    { code: 'NS', name: 'Nova Scotia' },
    { code: 'NT', name: 'Northwest Territories' },
    { code: 'NU', name: 'Nunavut' },
    { code: 'ON', name: 'Ontario' },
    { code: 'PE', name: 'Prince Edward Island' },
    { code: 'QC', name: 'Quebec' },
    { code: 'SK', name: 'Saskatchewan' },
    { code: 'YT', name: 'Yukon' },
];

const form = useForm({
    name: '',
    country_code: 'CA',
    province_code: '',
    is_active: true,
    priority: 0,
});

function submit(): void {
    form.post(store().url);
}
</script>

<template>
    <Head title="New Tax Zone" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex max-w-xl flex-col gap-8 p-4">
            <div>
                <h1 class="text-2xl font-semibold">New Tax Zone</h1>
                <p class="text-sm text-muted-foreground">
                    Define a geographic region and assign tax rates to it.
                </p>
            </div>

            <form class="flex flex-col gap-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="name">Zone Name</Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        placeholder="e.g. Ontario, Canada, International"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid max-w-xs gap-2">
                    <Label for="country_code">Country Code</Label>
                    <Input
                        id="country_code"
                        v-model="form.country_code"
                        placeholder="e.g. CA"
                        maxlength="2"
                    />
                    <p class="text-xs text-muted-foreground">
                        Leave blank for global fallback zone.
                    </p>
                    <InputError :message="form.errors.country_code" />
                </div>

                <div class="grid max-w-xs gap-2">
                    <Label for="province_code">Province / State Code</Label>
                    <select
                        id="province_code"
                        v-model="form.province_code"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                    >
                        <option value="">— Country catch-all —</option>
                        <option
                            v-for="p in canadianProvinces"
                            :key="p.code"
                            :value="p.code"
                        >
                            {{ p.code }} — {{ p.name }}
                        </option>
                    </select>
                    <InputError :message="form.errors.province_code" />
                </div>

                <div class="grid max-w-xs gap-2">
                    <Label for="priority">Priority</Label>
                    <Input
                        id="priority"
                        v-model="form.priority"
                        type="number"
                        min="0"
                        placeholder="0"
                    />
                    <p class="text-xs text-muted-foreground">
                        Higher priority zones take precedence. Province-specific zones should use priority 10.
                    </p>
                    <InputError :message="form.errors.priority" />
                </div>

                <div class="flex items-center gap-2">
                    <input
                        id="is_active"
                        v-model="form.is_active"
                        type="checkbox"
                    />
                    <Label for="is_active">Active</Label>
                </div>

                <div class="flex gap-3">
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Creating...' : 'Create Zone' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
