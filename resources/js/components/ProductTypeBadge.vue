<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';

const props = defineProps<{
    type: string;
}>();

const label = computed(() => {
    switch (props.type) {
        case 'simple':
            return 'Simple';
        case 'variable':
            return 'Variable';
        case 'bundled':
            return 'Bundled';
        default:
            return props.type;
    }
});

const variant = computed(() => {
    switch (props.type) {
        case 'bundled':
            return 'default';
        case 'variable':
            return 'secondary';
        default:
            return 'outline';
    }
});
</script>

<template>
    <Badge :variant="variant" class="text-xs">
        {{ label }}
    </Badge>
</template>
