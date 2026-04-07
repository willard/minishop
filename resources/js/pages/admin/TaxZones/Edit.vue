<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
    edit,
    index,
    update,
} from '@/actions/App/Http/Controllers/Admin/TaxZoneController';
import {
    destroy as destroyRate,
    store as storeRate,
    update as updateRate,
} from '@/actions/App/Http/Controllers/Admin/TaxZoneRateController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface TaxZoneRate {
    id: number;
    name: string;
    name_fr: string | null;
    rate: number;
    is_compound: boolean;
    is_shipping_taxable: boolean;
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

const props = defineProps<{
    taxZone: TaxZone;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Tax Zones', href: index().url },
    { title: props.taxZone.name, href: edit(props.taxZone.id).url },
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
    name: props.taxZone.name,
    country_code: props.taxZone.country_code ?? '',
    province_code: props.taxZone.province_code ?? '',
    is_active: props.taxZone.is_active,
    priority: props.taxZone.priority,
});

const newRateForm = useForm({
    name: '',
    name_fr: '',
    rate: 0,
    is_compound: false,
    is_shipping_taxable: false,
    sort_order: 0,
});

const editingRateId = ref<number | null>(null);
const editRateForm = useForm({
    name: '',
    name_fr: '',
    rate: 0,
    is_compound: false,
    is_shipping_taxable: false,
    sort_order: 0,
});

function submit(): void {
    form.put(update(props.taxZone.id).url);
}

function addRate(): void {
    newRateForm.post(storeRate(props.taxZone.id).url, {
        onSuccess: () => newRateForm.reset(),
    });
}

function startEditRate(rate: TaxZoneRate): void {
    editingRateId.value = rate.id;
    editRateForm.name = rate.name;
    editRateForm.name_fr = rate.name_fr ?? '';
    editRateForm.rate = rate.rate;
    editRateForm.is_compound = rate.is_compound;
    editRateForm.is_shipping_taxable = rate.is_shipping_taxable;
    editRateForm.sort_order = rate.sort_order;
}

function saveRate(rate: TaxZoneRate): void {
    editRateForm.put(updateRate({ tax_zone: props.taxZone.id, rate: rate.id }).url, {
        onSuccess: () => {
            editingRateId.value = null;
        },
    });
}

function confirmDeleteRate(rate: TaxZoneRate): void {
    if (confirm(`Delete rate "${rate.name}"?`)) {
        router.delete(destroyRate({ tax_zone: props.taxZone.id, rate: rate.id }).url);
    }
}
</script>

<template>
    <Head :title="`Edit Tax Zone: ${taxZone.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex max-w-2xl flex-col gap-8 p-4">
            <!-- Zone form -->
            <div>
                <h1 class="text-2xl font-semibold">Edit Tax Zone</h1>
                <p class="text-sm text-muted-foreground">{{ taxZone.name }}</p>
            </div>

            <form class="flex flex-col gap-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="name">Zone Name</Label>
                    <Input id="name" v-model="form.name" />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid max-w-xs gap-2">
                    <Label for="country_code">Country Code</Label>
                    <Input id="country_code" v-model="form.country_code" maxlength="2" />
                    <InputError :message="form.errors.country_code" />
                </div>

                <div class="grid max-w-xs gap-2">
                    <Label for="province_code">Province / State</Label>
                    <select
                        id="province_code"
                        v-model="form.province_code"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
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
                    <Input id="priority" v-model="form.priority" type="number" min="0" />
                    <InputError :message="form.errors.priority" />
                </div>

                <div class="flex items-center gap-2">
                    <input id="is_active" v-model="form.is_active" type="checkbox" />
                    <Label for="is_active">Active</Label>
                </div>

                <div>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Save Zone' }}
                    </Button>
                </div>
            </form>

            <!-- Rates section -->
            <div>
                <h2 class="mb-3 border-b pb-2 text-base font-semibold">Tax Rates</h2>

                <div v-if="taxZone.rates.length > 0" class="mb-4 rounded-lg border">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b bg-muted/50 text-left">
                                <th class="px-4 py-2 font-medium">Name</th>
                                <th class="px-4 py-2 font-medium">Rate (%)</th>
                                <th class="px-4 py-2 font-medium">Type</th>
                                <th class="px-4 py-2 font-medium">Order</th>
                                <th class="px-4 py-2 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="rate in taxZone.rates" :key="rate.id">
                                <tr
                                    v-if="editingRateId !== rate.id"
                                    class="border-b"
                                >
                                    <td class="px-4 py-2">
                                        {{ rate.name }}
                                        <span v-if="rate.name_fr" class="text-xs text-muted-foreground">/ {{ rate.name_fr }}</span>
                                    </td>
                                    <td class="px-4 py-2">{{ rate.rate }}%</td>
                                    <td class="px-4 py-2">{{ rate.is_compound ? 'Compound' : 'Simple' }}</td>
                                    <td class="px-4 py-2">{{ rate.sort_order }}</td>
                                    <td class="px-4 py-2">
                                        <div class="flex gap-2">
                                            <Button size="sm" variant="ghost" @click="startEditRate(rate)">Edit</Button>
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                class="text-destructive"
                                                @click="confirmDeleteRate(rate)"
                                            >Delete</Button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-else class="border-b bg-muted/20">
                                    <td class="px-4 py-2">
                                        <Input v-model="editRateForm.name" class="h-8" />
                                        <Input v-model="editRateForm.name_fr" class="mt-1 h-8" placeholder="French name" />
                                    </td>
                                    <td class="px-4 py-2">
                                        <Input v-model="editRateForm.rate" type="number" step="0.0001" class="h-8" />
                                    </td>
                                    <td class="px-4 py-2">
                                        <label class="flex items-center gap-1 text-xs">
                                            <input v-model="editRateForm.is_compound" type="checkbox" />
                                            Compound
                                        </label>
                                    </td>
                                    <td class="px-4 py-2">
                                        <Input v-model="editRateForm.sort_order" type="number" class="h-8 w-16" />
                                    </td>
                                    <td class="px-4 py-2">
                                        <div class="flex gap-2">
                                            <Button size="sm" @click="saveRate(rate)">Save</Button>
                                            <Button size="sm" variant="ghost" @click="editingRateId = null">Cancel</Button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Add new rate form -->
                <div class="rounded-lg border p-4">
                    <h3 class="mb-3 text-sm font-medium">Add Rate</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="grid gap-1">
                            <Label class="text-xs">Name (e.g. HST, GST)</Label>
                            <Input v-model="newRateForm.name" class="h-8" placeholder="HST" />
                            <InputError :message="newRateForm.errors.name" />
                        </div>
                        <div class="grid gap-1">
                            <Label class="text-xs">French Name (optional)</Label>
                            <Input v-model="newRateForm.name_fr" class="h-8" placeholder="TVH" />
                        </div>
                        <div class="grid gap-1">
                            <Label class="text-xs">Rate (%)</Label>
                            <Input v-model="newRateForm.rate" type="number" step="0.0001" class="h-8" placeholder="13.0" />
                            <InputError :message="newRateForm.errors.rate" />
                        </div>
                        <div class="grid gap-1">
                            <Label class="text-xs">Sort Order</Label>
                            <Input v-model="newRateForm.sort_order" type="number" class="h-8" placeholder="1" />
                        </div>
                        <div class="flex items-center gap-2">
                            <input id="is_compound_new" v-model="newRateForm.is_compound" type="checkbox" />
                            <Label for="is_compound_new" class="text-xs">Compound rate (e.g. QST)</Label>
                        </div>
                    </div>
                    <Button class="mt-3" size="sm" :disabled="newRateForm.processing" @click="addRate">
                        Add Rate
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
