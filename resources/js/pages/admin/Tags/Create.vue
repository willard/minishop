<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { ref } from 'vue';
import {
    index,
    create,
    store,
} from '@/actions/App/Http/Controllers/Admin/TagController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Tags', href: index().url },
    { title: 'Add Tag', href: create().url },
];

const color = ref('');

function onColorPickerChange(event: Event): void {
    color.value = (event.target as HTMLInputElement).value;
}

function onColorInputChange(event: Event): void {
    color.value = (event.target as HTMLInputElement).value;
}
</script>

<template>
    <Head title="Add Tag" />

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
                    <h1 class="text-2xl font-semibold">Add Tag</h1>
                    <p class="text-sm text-muted-foreground">
                        Create a new product tag
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
                        placeholder="Tag name"
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
                        placeholder="Tag description"
                        class="flex w-full resize-none rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                    />
                    <InputError :message="errors.description" />
                </div>

                <!-- Color -->
                <div class="grid gap-2">
                    <Label for="color">Color</Label>
                    <div class="flex items-center gap-3">
                        <input
                            type="color"
                            :value="color || '#000000'"
                            class="size-10 cursor-pointer rounded border border-input"
                            @input="onColorPickerChange"
                        />
                        <Input
                            id="color"
                            name="color"
                            :value="color"
                            placeholder="#FF5733"
                            class="max-w-[8rem] font-mono"
                            @input="onColorInputChange"
                        />
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Optional hex color for tag badges
                    </p>
                    <InputError :message="errors.color" />
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
                        {{ processing ? 'Creating...' : 'Create Tag' }}
                    </Button>
                    <Link :href="index().url">
                        <Button variant="ghost" type="button">Cancel</Button>
                    </Link>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
