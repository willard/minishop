<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { store } from '@/routes/login';

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();
</script>

<template>
    <div
        class="flex min-h-screen flex-col"
        style="
            background-color: #f9f6f0;
            font-family: 'Instrument Sans', sans-serif;
        "
    >
        <Head title="Sign In" />

        <!-- Header -->
        <header
            class="border-b px-6 py-4"
            style="border-color: rgba(28, 26, 23, 0.1)"
        >
            <div class="mx-auto flex max-w-7xl items-center justify-between">
                <Link
                    href="/"
                    class="text-2xl font-semibold tracking-wide transition-opacity hover:opacity-70"
                    style="
                        font-family: 'Cormorant Garamond', serif;
                        color: #1c1a17;
                    "
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
                        style="
                            font-family: 'Cormorant Garamond', serif;
                            color: #1c1a17;
                        "
                    >
                        Welcome back
                    </h1>
                    <p class="text-sm" style="color: rgba(28, 26, 23, 0.55)">
                        Sign in to your account
                    </p>
                </div>

                <div
                    v-if="status"
                    class="mb-4 rounded-lg p-3 text-center text-sm"
                    style="background-color: #e8f5e9; color: #2e7d32"
                >
                    {{ status }}
                </div>

                <Form
                    v-bind="store.form()"
                    :reset-on-success="['password']"
                    v-slot="{ errors, processing }"
                    class="flex flex-col gap-5"
                >
                    <div class="flex flex-col gap-1.5">
                        <label
                            class="text-sm font-medium"
                            style="color: #1c1a17"
                            for="email"
                            >Email address</label
                        >
                        <input
                            id="email"
                            type="email"
                            name="email"
                            required
                            autofocus
                            autocomplete="email"
                            placeholder="you@example.com"
                            class="rounded-lg border px-4 py-3 text-sm transition-colors outline-none focus:ring-2 focus:ring-black/20"
                            style="
                                border-color: rgba(28, 26, 23, 0.2);
                                background-color: #fff;
                                color: #1c1a17;
                            "
                        />
                        <InputError :message="errors.email" />
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <div class="flex items-center justify-between">
                            <label
                                class="text-sm font-medium"
                                style="color: #1c1a17"
                                for="password"
                                >Password</label
                            >
                            <Link
                                v-if="canResetPassword"
                                href="/forgot-password"
                                class="text-xs underline underline-offset-4 transition-opacity hover:opacity-60"
                                style="color: rgba(28, 26, 23, 0.6)"
                            >
                                Forgot password?
                            </Link>
                        </div>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            placeholder="Password"
                            class="rounded-lg border px-4 py-3 text-sm transition-colors outline-none focus:ring-2 focus:ring-black/20"
                            style="
                                border-color: rgba(28, 26, 23, 0.2);
                                background-color: #fff;
                                color: #1c1a17;
                            "
                        />
                        <InputError :message="errors.password" />
                    </div>

                    <button
                        type="submit"
                        :disabled="processing"
                        class="mt-1 rounded-lg px-6 py-3 text-sm font-semibold tracking-widest text-white uppercase transition-opacity hover:opacity-80 disabled:opacity-50"
                        style="background-color: #1c1a17"
                    >
                        {{ processing ? 'Signing in…' : 'Sign in' }}
                    </button>
                </Form>

                <p
                    v-if="canRegister"
                    class="mt-6 text-center text-sm"
                    style="color: rgba(28, 26, 23, 0.55)"
                >
                    Don't have an account?
                    <Link
                        href="/register/customer"
                        class="font-medium underline underline-offset-4 transition-opacity hover:opacity-70"
                        style="color: #1c1a17"
                    >
                        Create one
                    </Link>
                </p>
            </div>
        </main>
    </div>
</template>
