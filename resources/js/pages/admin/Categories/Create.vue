<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import {
    index,
    create,
    store,
} from '@/actions/App/Http/Controllers/Admin/CategoryController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface ParentCategory {
    id: number;
    name: string;
}

defineProps<{
    parentCategories: ParentCategory[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Categories', href: index().url },
    { title: 'Add Category', href: create().url },
];
</script>

<template>
    <Head title="Add Category" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex max-w-2xl flex-col gap-6 p-4">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link :href="index().url">
                    <Button variant="ghost" size="sm">
                        <ArrowLeft class="size-4" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-2xl font-semibold">Add Category</h1>
                    <p class="text-sm text-muted-foreground">
                        Create a new product category
                    </p>
                </div>
            </div>

            <!-- Form -->
            <Form
                v-bind="store.form()"
                class="flex flex-col gap-6"
                v-slot="{ errors, processing }"
            >
                <!-- Name -->
                <div class="grid gap-2">
                    <Label for="name"
                        >Name <span class="text-destructive">*</span></Label
                    >
                    <Input
                        id="name"
                        name="name"
                        placeholder="Category name"
                        required
                    />
                    <InputError :message="errors.name" />
                </div>

                <!-- Description -->
                <div class="grid gap-2">
                    <Label for="description">Description</Label>
                    <textarea
                        id="description"
                        name="description"
                        rows="3"
                        placeholder="Category description"
                        class="flex w-full resize-none rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                    />
                    <InputError :message="errors.description" />
                </div>

                <!-- Parent Category -->
                <div class="grid gap-2" v-if="parentCategories.length > 0">
                    <Label for="parent_id">Parent Category</Label>
                    <select
                        id="parent_id"
                        name="parent_id"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                    >
                        <option value="">None (top-level category)</option>
                        <option
                            v-for="parent in parentCategories"
                            :key="parent.id"
                            :value="parent.id"
                        >
                            {{ parent.name }}
                        </option>
                    </select>
                    <InputError :message="errors.parent_id" />
                </div>

                <!-- Sort Order -->
                <div class="grid gap-2">
                    <Label for="sort_order">Sort Order</Label>
                    <Input
                        id="sort_order"
                        name="sort_order"
                        type="number"
                        min="0"
                        default-value="0"
                        placeholder="0"
                    />
                    <p class="text-xs text-muted-foreground">
                        Lower numbers appear first
                    </p>
                    <InputError :message="errors.sort_order" />
                </div>

                <!-- Active Status -->
                <div class="flex items-center gap-2">
                    <Checkbox
                        id="is_active"
                        name="is_active"
                        value="1"
                        :default-value="true"
                    />
                    <Label for="is_active">Active</Label>
                </div>

                <!-- Submit -->
                <div class="flex items-center gap-3">
                    <Button type="submit" :disabled="processing">
                        {{ processing ? 'Creating...' : 'Create Category' }}
                    </Button>
                    <Link :href="index().url">
                        <Button variant="ghost" type="button">Cancel</Button>
                    </Link>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
