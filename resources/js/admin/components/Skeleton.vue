<script setup>
// Shimmer placeholder for tables/cards while data loads.
//   <Skeleton :rows="6" />
defineProps({
    rows: { type: Number, default: 5 },
})
</script>

<template>
    <div class="sk">
        <div v-for="r in rows" :key="r" class="sk-row">
            <span class="sk-cell sk-avatar" />
            <span class="sk-cell sk-grow" />
            <span class="sk-cell sk-sm" />
            <span class="sk-cell sk-sm" />
        </div>
    </div>
</template>
