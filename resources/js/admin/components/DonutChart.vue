<script setup>
import { computed } from 'vue'

const props = defineProps({
    data: { type: Array, required: true }, // [{ label, value, color }]
    size: { type: Number, default: 190 },
    thickness: { type: Number, default: 22 },
    caption: { type: String, default: 'Total' },
})

const total = computed(() => props.data.reduce((sum, d) => sum + d.value, 0))
const radius = computed(() => (props.size - props.thickness) / 2)
const circumference = computed(() => 2 * Math.PI * radius.value)

const segments = computed(() => {
    let offset = 0
    return props.data.map((d) => {
        const fraction = total.value ? d.value / total.value : 0
        const seg = {
            ...d,
            dash: `${fraction * circumference.value} ${circumference.value}`,
            offset: -offset * circumference.value,
            percent: Math.round(fraction * 100),
        }
        offset += fraction
        return seg
    })
})
</script>

<template>
    <div class="donut-wrap">
        <svg :width="size" :height="size" :viewBox="`0 0 ${size} ${size}`">
            <g :transform="`rotate(-90 ${size / 2} ${size / 2})`">
                <circle
                    :cx="size / 2"
                    :cy="size / 2"
                    :r="radius"
                    fill="none"
                    stroke="#eef5f0"
                    :stroke-width="thickness"
                />
                <circle
                    v-for="seg in segments"
                    :key="seg.label"
                    :cx="size / 2"
                    :cy="size / 2"
                    :r="radius"
                    fill="none"
                    :stroke="seg.color"
                    :stroke-width="thickness"
                    :stroke-dasharray="seg.dash"
                    :stroke-dashoffset="seg.offset"
                    stroke-linecap="butt"
                />
            </g>
            <text
                class="donut-center"
                :x="size / 2"
                :y="size / 2 - 2"
                text-anchor="middle"
                font-size="30"
                font-weight="600"
                fill="#223028"
            >
                {{ total.toLocaleString() }}
            </text>
            <text
                :x="size / 2"
                :y="size / 2 + 20"
                text-anchor="middle"
                font-size="11"
                letter-spacing="2"
                fill="#6f7d73"
            >
                {{ caption.toUpperCase() }}
            </text>
        </svg>

        <ul class="donut-list">
            <li v-for="seg in segments" :key="`l-${seg.label}`">
                <i :style="{ background: seg.color }" />
                {{ seg.label }}
                <b>{{ seg.percent }}%</b>
            </li>
        </ul>
    </div>
</template>
