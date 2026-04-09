<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';

const props = defineProps<{
    name: string;
    color?: string | null;
}>();

/** Returns a text color class for contrast against the badge background color. */
const textColorClass = computed(() => {
    if (!props.color) return '';
    const hex = props.color.replace('#', '');
    const r = parseInt(hex.substring(0, 2), 16);
    const g = parseInt(hex.substring(2, 4), 16);
    const b = parseInt(hex.substring(4, 6), 16);
    // Perceived luminance formula
    const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
    return luminance > 0.5 ? 'text-gray-900' : 'text-white';
});
</script>

<template>
    <Badge
        v-if="color"
        class="text-xs"
        :class="textColorClass"
        :style="{ backgroundColor: color! }"
    >
        {{ name }}
    </Badge>
    <Badge v-else variant="outline" class="text-xs">
        {{ name }}
    </Badge>
</template>
