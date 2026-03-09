<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import {
    index,
    update,
} from '@/actions/App/Http/Controllers/Admin/UserController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface StaffUser {
    id: number;
    name: string;
    email: string;
}

const props = defineProps<{
    user: StaffUser;
    currentRole: string;
    roles: string[];
}>();

const page = usePage();
const isEditingSelf = page.props.auth.user.id === props.user.id;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Users', href: index().url },
    { title: props.user.name, href: '#' },
    { title: 'Edit', href: '#' },
];
</script>

<template>
    <Head :title="`Edit ${user.name}`" />

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
                    <h1 class="text-2xl font-semibold">Edit User</h1>
                    <p class="text-sm text-muted-foreground">{{ user.name }}</p>
                </div>
            </div>

            <!-- Form -->
            <Form
                v-bind="update.form(user)"
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
                        :default-value="user.name"
                        placeholder="Full name"
                        required
                    />
                    <InputError :message="errors.name" />
                </div>

                <!-- Email -->
                <div class="grid gap-2">
                    <Label for="email"
                        >Email <span class="text-destructive">*</span></Label
                    >
                    <Input
                        id="email"
                        name="email"
                        type="email"
                        :default-value="user.email"
                        placeholder="email@example.com"
                        required
                    />
                    <InputError :message="errors.email" />
                </div>

                <!-- Password + Confirm side by side -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="password">Password</Label>
                        <Input
                            id="password"
                            name="password"
                            type="password"
                            placeholder="Leave empty to keep current"
                        />
                        <InputError :message="errors.password" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="password_confirmation"
                            >Confirm Password</Label
                        >
                        <Input
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            placeholder="Repeat new password"
                        />
                    </div>
                </div>

                <!-- Role -->
                <div class="grid gap-2">
                    <Label for="role"
                        >Role <span class="text-destructive">*</span></Label
                    >
                    <select
                        id="role"
                        name="role"
                        :value="currentRole"
                        :disabled="isEditingSelf"
                        class="flex h-10 w-full max-w-xs rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        required
                    >
                        <option v-for="role in roles" :key="role" :value="role">
                            {{ role }}
                        </option>
                    </select>
                    <p
                        v-if="isEditingSelf"
                        class="text-xs text-muted-foreground"
                    >
                        You cannot change your own role
                    </p>
                    <p v-else class="text-xs text-muted-foreground">
                        Determines what the user can access in the dashboard
                    </p>
                    <InputError :message="errors.role" />
                </div>

                <!-- Submit -->
                <div class="flex items-center gap-3">
                    <Button type="submit" :disabled="processing">
                        {{ processing ? 'Saving...' : 'Save Changes' }}
                    </Button>
                    <Link :href="index().url">
                        <Button variant="ghost" type="button">Cancel</Button>
                    </Link>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
