<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';

const props = defineProps<{
    type: string;
}>();

const TYPE_LABELS: Record<string, string> = {
    simple: 'Simple',
    variable: 'Variable',
    bundled: 'Bundled',
};

const TYPE_VARIANTS: Record<string, string> = {
    bundled: 'default',
    variable: 'secondary',
};

const label = computed(() => TYPE_LABELS[props.type] ?? props.type);
const variant = computed(() => TYPE_VARIANTS[props.type] ?? 'outline');
</script>

<template>
    <Badge :variant="variant" class="text-xs">
        {{ label }}
    </Badge>
</template>
