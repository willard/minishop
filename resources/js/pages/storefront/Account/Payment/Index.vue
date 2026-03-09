<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AccountLayout from '@/layouts/AccountLayout.vue';
</script>

<template>
    <AccountLayout title="Payment Methods">
        <Head title="Payment Methods" />

        <div class="max-w-lg">
            <div
                class="rounded-xl border p-8 text-center"
                style="
                    border-color: rgba(28, 26, 23, 0.12);
                    background-color: #fff;
                "
            >
                <p class="mb-1 text-sm font-medium" style="color: #1c1a17">
                    No saved payment methods
                </p>
                <p class="text-sm" style="color: rgba(28, 26, 23, 0.5)">
                    Saved payment methods will appear here once Stripe billing
                    is configured.
                </p>
            </div>
        </div>
    </AccountLayout>
</template>
