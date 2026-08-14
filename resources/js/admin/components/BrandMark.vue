<script setup>
// Two-leaf sprout — same mark as the storefront and the Cha Kunjo pouch.
defineProps({
    size: { type: [Number, String], default: 22 },
})
</script>

<template>
    <svg :width="size" :height="size" viewBox="0 0 24 24" aria-hidden="true">
        <path
            d="M12 21.6c0-4.2 0-7.2 0-9.6"
            fill="none"
            stroke="currentColor"
            stroke-width="1.7"
            stroke-linecap="round"
        />
        <path d="M11.9 12.4C6.6 12.1 3.9 8.8 4.3 3.7c5.3.3 8 3.6 7.6 8.7z" fill="currentColor" />
        <path
            d="M12.1 12.4c-.4-5.1 2.3-8.4 7.6-8.7.4 5.1-2.3 8.4-7.6 8.7z"
            fill="currentColor"
            opacity="0.72"
        />
    </svg>
</template>
