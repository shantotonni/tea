<script setup>
import AppIcon from './AppIcon.vue'

defineProps({
    label: { type: String, required: true },
    value: { type: String, required: true },
    delta: { type: String, default: '' },
    trend: { type: String, default: 'up' }, // 'up' | 'down'
    icon: { type: String, default: 'revenue' },
    tone: { type: String, default: '' }, // '' | gold | info | rose
    foot: { type: String, default: '' },
})
</script>

<template>
    <div class="stat">
        <div class="stat-top">
            <span class="stat-tile" :class="tone"><AppIcon :name="icon" :size="21" /></span>
            <span v-if="delta" class="delta" :class="{ down: trend === 'down' }">
                <AppIcon :name="trend === 'down' ? 'down' : 'up'" :size="14" />
                {{ delta }}
            </span>
        </div>
        <p class="stat-label">{{ label }}</p>
        <p class="stat-value">{{ value }}</p>
        <p v-if="foot" class="stat-foot">{{ foot }}</p>
    </div>
</template>
