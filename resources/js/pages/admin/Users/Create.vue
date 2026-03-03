<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type BreadcrumbItem } from '@/types';
import { index, create, store } from '@/actions/App/Http/Controllers/Admin/UserController';

defineProps<{
    roles: string[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Users', href: index().url },
    { title: 'Add User', href: create().url },
];
</script>

<template>
    <Head title="Add User" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 max-w-2xl">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link :href="index().url">
                    <Button variant="ghost" size="sm">
                        <ArrowLeft class="size-4" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-2xl font-semibold">Add User</h1>
                    <p class="text-sm text-muted-foreground">Create a new staff member</p>
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
                    <Label for="name">Name <span class="text-destructive">*</span></Label>
                    <Input
                        id="name"
                        name="name"
                        placeholder="Full name"
                        required
                    />
                    <InputError :message="errors.name" />
                </div>

                <!-- Email -->
                <div class="grid gap-2">
                    <Label for="email">Email <span class="text-destructive">*</span></Label>
                    <Input
                        id="email"
                        name="email"
                        type="email"
                        placeholder="email@example.com"
                        required
                    />
                    <InputError :message="errors.email" />
                </div>

                <!-- Password + Confirm side by side -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="password">Password <span class="text-destructive">*</span></Label>
                        <Input
                            id="password"
                            name="password"
                            type="password"
                            placeholder="Min. 8 characters"
                            required
                        />
                        <InputError :message="errors.password" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="password_confirmation">Confirm Password <span class="text-destructive">*</span></Label>
                        <Input
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            placeholder="Repeat password"
                            required
                        />
                    </div>
                </div>

                <!-- Role -->
                <div class="grid gap-2">
                    <Label for="role">Role <span class="text-destructive">*</span></Label>
                    <select
                        id="role"
                        name="role"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 max-w-xs"
                        required
                    >
                        <option value="" disabled selected>Select a role</option>
                        <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
                    </select>
                    <p class="text-xs text-muted-foreground">Determines what the user can access in the dashboard</p>
                    <InputError :message="errors.role" />
                </div>

                <!-- Submit -->
                <div class="flex items-center gap-3">
                    <Button type="submit" :disabled="processing">
                        {{ processing ? 'Creating...' : 'Create User' }}
                    </Button>
                    <Link :href="index().url">
                        <Button variant="ghost" type="button">Cancel</Button>
                    </Link>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
