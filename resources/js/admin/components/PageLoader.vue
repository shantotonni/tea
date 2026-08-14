<script setup>
// Branded loading overlay. Drop into any module:
//   <PageLoader v-if="loading" />
defineProps({
    label: { type: String, default: 'Loading…' },
    inline: { type: Boolean, default: false }, // inline = fills its card, not the viewport
})
</script>

<template>
    <div class="ldr" :class="{ inline }">
        <div class="ldr-mark">
            <span class="ldr-ring" />
            <svg viewBox="0 0 24 24" width="26" height="26" aria-hidden="true">
                <path
                    d="M17 8C8 10 5.9 16.17 3.82 21.34l1.89.66.95-2.3c.48.17.98.3 1.34.3C19 20 22 3 22 3c-1 2-8 2.25-13 3.25S2 11.5 2 13.5s1.75 3.75 1.75 3.75C7 8 17 8 17 8z"
                    fill="currentColor"
                />
            </svg>
        </div>
        <p class="ldr-label">{{ label }}</p>
    </div>
</template>
