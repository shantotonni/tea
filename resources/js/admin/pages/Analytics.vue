<script setup>
import { ref, computed, onMounted } from 'vue'
import StatCard from '../components/StatCard.vue'
import AreaChart from '../components/AreaChart.vue'
import DonutChart from '../components/DonutChart.vue'
import Skeleton from '../components/Skeleton.vue'
import { fetchAnalytics, toCategorySplit, money } from '../data'

const loading = ref(true)
const data = ref(null)

onMounted(async () => {
    try {
        data.value = await fetchAnalytics()
    } finally {
        loading.value = false
    }
})

const kpis = computed(() => data.value?.kpis || {})
const channels = computed(() => data.value?.channels || [])
const funnel = computed(() => data.value?.funnel || [])
const topProducts = computed(() => data.value?.top_products || [])
const categorySplit = computed(() => toCategorySplit(data.value?.category_split || []))

const revenueChart = computed(() => ({
    labels: data.value?.revenue_by_month?.labels || [],
    series: [
        { name: `Revenue ${new Date().getFullYear()}`, color: '#2c6b45', values: data.value?.revenue_by_month?.revenue || [] },
    ],
}))
const ordersChart = computed(() => ({
    labels: data.value?.revenue_by_month?.labels || [],
    series: [
        { name: 'Orders', color: '#c8a24a', values: data.value?.revenue_by_month?.orders || [] },
    ],
}))
</script>

<template>
    <div class="page">
        <div class="page-head">
            <div>
                <p class="eyebrow">Intelligence</p>
                <h2>Analytics</h2>
                <p>Live demand, channels and order performance — computed from your database.</p>
            </div>
        </div>

        <div v-if="loading" class="card" style="padding: 1.6rem"><Skeleton :rows="8" /></div>

        <template v-else>
            <div class="stat-grid">
                <StatCard label="Total Revenue" :value="money(kpis.revenue)" icon="revenue" tone="gold" foot="excl. cancelled" />
                <StatCard label="Orders" :value="String(kpis.orders || 0)" icon="orders" />
                <StatCard label="Avg. Order Value" :value="money(kpis.aov)" icon="analytics" tone="info" />
                <StatCard label="Units Sold" :value="String(kpis.units || 0)" icon="products" :foot="`avg ${kpis.avg_items || 0}/order`" />
            </div>

            <div class="grid-2">
                <section class="card">
                    <div class="card-head">
                        <div><h3>Revenue Trend</h3><p>This year, by month</p></div>
                    </div>
                    <div class="card-body">
                        <AreaChart :series="revenueChart.series" :labels="revenueChart.labels" />
                    </div>
                </section>

                <section class="card">
                    <div class="card-head"><h3>Orders by Channel</h3></div>
                    <div class="card-body">
                        <DonutChart v-if="channels.length" :data="channels" caption="Orders" />
                        <p v-else class="an-empty">No orders yet.</p>
                    </div>
                </section>
            </div>

            <div class="grid-2">
                <section class="card">
                    <div class="card-head">
                        <div><h3>Orders per Month</h3><p>Volume this year</p></div>
                    </div>
                    <div class="card-body">
                        <AreaChart :series="ordersChart.series" :labels="ordersChart.labels" prefix="" />
                    </div>
                </section>

                <div style="display: grid; gap: 1.1rem; align-content: start">
                    <section class="card">
                        <div class="card-head"><h3>Order Status</h3></div>
                        <div class="card-body">
                            <div class="prog">
                                <div v-for="f in funnel" :key="f.stage" class="prog-row">
                                    <div class="top">
                                        <strong>{{ f.stage }}</strong>
                                        <span>{{ f.value.toLocaleString() }}</span>
                                    </div>
                                    <div class="bar" :class="{ gold: f.stage === 'Cancelled' }">
                                        <i :style="{ width: f.share + '%' }" />
                                    </div>
                                </div>
                                <p v-if="!funnel.length" class="an-empty">No orders yet.</p>
                            </div>
                        </div>
                    </section>

                    <section class="card">
                        <div class="card-head"><h3>Best Sellers</h3></div>
                        <div class="card-body">
                            <div class="prog">
                                <div v-for="(p, i) in topProducts" :key="p.name" class="prog-row">
                                    <div class="top">
                                        <strong>{{ p.name }}</strong>
                                        <span>{{ p.sold }} sold · {{ money(p.revenue) }}</span>
                                    </div>
                                    <div class="bar" :class="{ gold: i % 2 === 1 }">
                                        <i :style="{ width: p.share + '%' }" />
                                    </div>
                                </div>
                                <p v-if="!topProducts.length" class="an-empty">No sales yet.</p>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <section class="card">
                <div class="card-head">
                    <div><h3>Blend Demand</h3><p>Units sold by category (live)</p></div>
                </div>
                <div class="card-body">
                    <DonutChart v-if="categorySplit.length" :data="categorySplit" caption="Units" :size="170" />
                    <p v-else class="an-empty">No data yet.</p>
                </div>
            </section>
        </template>
    </div>
</template>

<style scoped>
.an-empty { color: var(--muted); font-size: 0.88rem; padding: 1.5rem 0; text-align: center; }
</style>
