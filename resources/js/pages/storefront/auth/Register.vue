<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { store } from '@/routes/register';
</script>

<template>
    <div class="flex min-h-screen flex-col" style="background-color: #f9f6f0; font-family: 'Instrument Sans', sans-serif">
        <Head title="Create Account" />

        <!-- Header -->
        <header class="border-b px-6 py-4" style="border-color: rgba(28, 26, 23, 0.1)">
            <div class="mx-auto flex max-w-7xl items-center justify-between">
                <Link
                    href="/"
                    class="text-2xl font-semibold tracking-wide transition-opacity hover:opacity-70"
                    style="font-family: 'Cormorant Garamond', serif; color: #1c1a17"
                >
                    Minishop
                </Link>
            </div>
        </header>

        <!-- Form -->
        <main class="flex flex-1 items-center justify-center px-6 py-16">
            <div class="w-full max-w-sm">
                <div class="mb-8 text-center">
                    <h1
                        class="mb-2 text-3xl font-semibold"
                        style="font-family: 'Cormorant Garamond', serif; color: #1c1a17"
                    >
                        Create an account
                    </h1>
                    <p class="text-sm" style="color: rgba(28, 26, 23, 0.55)">Join Minishop to track your orders</p>
                </div>

                <Form
                    v-bind="store.form()"
                    :reset-on-success="['password', 'password_confirmation']"
                    v-slot="{ errors, processing }"
                    class="flex flex-col gap-5"
                >
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium" style="color: #1c1a17" for="name">Full name</label>
                        <input
                            id="name"
                            type="text"
                            name="name"
                            required
                            autofocus
                            autocomplete="name"
                            placeholder="Jane Doe"
                            class="rounded-lg border px-4 py-3 text-sm outline-none transition-colors"
                            style="border-color: rgba(28, 26, 23, 0.2); background-color: #fff; color: #1c1a17"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium" style="color: #1c1a17" for="email">Email address</label>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            required
                            autocomplete="email"
                            placeholder="you@example.com"
                            class="rounded-lg border px-4 py-3 text-sm outline-none transition-colors"
                            style="border-color: rgba(28, 26, 23, 0.2); background-color: #fff; color: #1c1a17"
                        />
                        <InputError :message="errors.email" />
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium" style="color: #1c1a17" for="password">Password</label>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            required
                            autocomplete="new-password"
                            placeholder="Password"
                            class="rounded-lg border px-4 py-3 text-sm outline-none transition-colors"
                            style="border-color: rgba(28, 26, 23, 0.2); background-color: #fff; color: #1c1a17"
                        />
                        <InputError :message="errors.password" />
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium" style="color: #1c1a17" for="password_confirmation">Confirm password</label>
                        <input
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            required
                            autocomplete="new-password"
                            placeholder="Confirm password"
                            class="rounded-lg border px-4 py-3 text-sm outline-none transition-colors"
                            style="border-color: rgba(28, 26, 23, 0.2); background-color: #fff; color: #1c1a17"
                        />
                        <InputError :message="errors.password_confirmation" />
                    </div>

                    <button
                        type="submit"
                        :disabled="processing"
                        class="mt-1 rounded-lg px-6 py-3 text-sm font-semibold uppercase tracking-widest text-white transition-opacity hover:opacity-80 disabled:opacity-50"
                        style="background-color: #1c1a17"
                    >
                        {{ processing ? 'Creating account…' : 'Create account' }}
                    </button>
                </Form>

                <p class="mt-6 text-center text-sm" style="color: rgba(28, 26, 23, 0.55)">
                    Already have an account?
                    <Link
                        href="/login?from=storefront"
                        class="font-medium underline underline-offset-4 transition-opacity hover:opacity-70"
                        style="color: #1c1a17"
                    >
                        Sign in
                    </Link>
                </p>
            </div>
        </main>
    </div>
</template>
