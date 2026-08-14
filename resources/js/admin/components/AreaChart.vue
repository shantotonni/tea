<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
    series: { type: Array, required: true }, // [{ name, color, values: [] }]
    labels: { type: Array, required: true },
    height: { type: Number, default: 240 },
    prefix: { type: String, default: '৳' },
})

const W = 720
const H = props.height
const PAD = { top: 18, right: 12, bottom: 26, left: 46 }

const hover = ref(null)

const max = computed(() => {
    const all = props.series.flatMap((s) => s.values)
    const peak = Math.max(...all, 1)
    const step = Math.pow(10, Math.floor(Math.log10(peak))) / 2
    return Math.ceil(peak / step) * step
})

const plotW = W - PAD.left - PAD.right
const plotH = computed(() => H - PAD.top - PAD.bottom)

const x = (i) => PAD.left + (i * plotW) / Math.max(props.labels.length - 1, 1)
const y = (v) => PAD.top + plotH.value - (v / max.value) * plotH.value

// Catmull-Rom -> cubic bezier, so the curve stays smooth without a chart lib.
function smoothPath(values) {
    const pts = values.map((v, i) => [x(i), y(v)])
    if (pts.length < 2) return ''
    let d = `M ${pts[0][0]} ${pts[0][1]}`
    for (let i = 0; i < pts.length - 1; i++) {
        const p0 = pts[i - 1] || pts[i]
        const p1 = pts[i]
        const p2 = pts[i + 1]
        const p3 = pts[i + 2] || p2
        const c1x = p1[0] + (p2[0] - p0[0]) / 6
        const c1y = p1[1] + (p2[1] - p0[1]) / 6
        const c2x = p2[0] - (p3[0] - p1[0]) / 6
        const c2y = p2[1] - (p3[1] - p1[1]) / 6
        d += ` C ${c1x} ${c1y}, ${c2x} ${c2y}, ${p2[0]} ${p2[1]}`
    }
    return d
}

const lines = computed(() =>
    props.series.map((s, i) => ({
        ...s,
        id: `grad-${i}`,
        path: smoothPath(s.values),
        area: `${smoothPath(s.values)} L ${x(s.values.length - 1)} ${PAD.top + plotH.value} L ${x(
            0
        )} ${PAD.top + plotH.value} Z`,
    }))
)

const ticks = computed(() => {
    const out = []
    for (let i = 0; i <= 4; i++) out.push((max.value / 4) * i)
    return out
})

const fmt = (v) => (v >= 1000 ? `${(v / 1000).toFixed(v % 1000 === 0 ? 0 : 1)}k` : `${v}`)
</script>

<template>
    <div>
        <svg class="chart" :viewBox="`0 0 ${W} ${H}`" role="img">
            <defs>
                <linearGradient
                    v-for="line in lines"
                    :id="line.id"
                    :key="line.id"
                    x1="0"
                    y1="0"
                    x2="0"
                    y2="1"
                >
                    <stop offset="0%" :stop-color="line.color" stop-opacity="0.28" />
                    <stop offset="100%" :stop-color="line.color" stop-opacity="0" />
                </linearGradient>
            </defs>

            <!-- grid + y axis -->
            <g>
                <template v-for="(t, i) in ticks" :key="`t-${i}`">
                    <line
                        :x1="PAD.left"
                        :x2="W - PAD.right"
                        :y1="y(t)"
                        :y2="y(t)"
                        stroke="#e6e9e6"
                        stroke-width="1"
                        :stroke-dasharray="i === 0 ? '0' : '3 5'"
                    />
                    <text
                        :x="PAD.left - 10"
                        :y="y(t) + 4"
                        text-anchor="end"
                        font-size="11"
                        fill="#6f7d73"
                    >
                        {{ fmt(t) }}
                    </text>
                </template>
            </g>

            <!-- series -->
            <g v-for="line in lines" :key="`s-${line.name}`">
                <path :d="line.area" :fill="`url(#${line.id})`" />
                <path
                    :d="line.path"
                    fill="none"
                    :stroke="line.color"
                    stroke-width="2.5"
                    stroke-linecap="round"
                />
                <circle
                    v-for="(v, i) in line.values"
                    :key="`p-${line.name}-${i}`"
                    :cx="x(i)"
                    :cy="y(v)"
                    :r="hover === i ? 5 : 0"
                    :fill="line.color"
                    stroke="#fff"
                    stroke-width="2"
                />
            </g>

            <!-- x labels + hover targets -->
            <g>
                <text
                    v-for="(l, i) in labels"
                    :key="`x-${i}`"
                    :x="x(i)"
                    :y="H - 6"
                    text-anchor="middle"
                    font-size="11"
                    fill="#6f7d73"
                >
                    {{ l }}
                </text>
                <rect
                    v-for="(l, i) in labels"
                    :key="`h-${i}`"
                    :x="x(i) - plotW / (labels.length * 2)"
                    :y="PAD.top"
                    :width="plotW / labels.length"
                    :height="plotH"
                    fill="transparent"
                    @mouseenter="hover = i"
                    @mouseleave="hover = null"
                />
                <line
                    v-if="hover !== null"
                    :x1="x(hover)"
                    :x2="x(hover)"
                    :y1="PAD.top"
                    :y2="PAD.top + plotH"
                    stroke="#c8a24a"
                    stroke-width="1"
                    stroke-dasharray="4 4"
                />
            </g>
        </svg>

        <div class="chart-legend" style="margin-top: 0.9rem">
            <span v-for="s in series" :key="s.name">
                <i :style="{ background: s.color }" />{{ s.name }}
                <template v-if="hover !== null">
                    — <b>{{ prefix }}{{ s.values[hover].toLocaleString() }}</b> ({{ labels[hover] }})
                </template>
            </span>
        </div>
    </div>
</template>
