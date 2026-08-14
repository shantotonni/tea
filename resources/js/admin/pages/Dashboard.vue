<script setup>
import AppIcon from '../components/AppIcon.vue'
import StatCard from '../components/StatCard.vue'
import AreaChart from '../components/AreaChart.vue'
import DonutChart from '../components/DonutChart.vue'
import { ref, computed, onMounted } from 'vue'
import {
    revenueSeries,
    toCategorySplit,
    fetchOrders,
    statusClass,
    initials,
    money,
} from '../data'
import { fetchDashboard } from '../data'
import { currentUser } from '../auth'
import PageLoader from '../components/PageLoader.vue'

const stats = ref(null)
const recent = ref([])
const topProducts = ref([])
const categorySplit = ref([])
const loading = ref(true)

const dynamicRevenueSeries = ref({ series: [], labels: [] })

async function load() {
    loading.value = true
    try {
        const d = await fetchDashboard()
        stats.value = d.stats
        topProducts.value = d.top_products
        categorySplit.value = toCategorySplit(d.category_split)
        dynamicRevenueSeries.value = d.revenue_series || revenueSeries
        // reuse the orders fetcher's clean shape for the recent-orders table
        recent.value = (await fetchOrders()).slice(0, 6)
    } finally {
        loading.value = false
    }
}
onMounted(load)

const firstName = computed(() => (currentUser.value?.name || 'there').split(' ')[0])
</script>

<template>
    <div class="page">
        <div class="page-head">
            <div>
                <p class="eyebrow">Estate Overview</p>
                <h2>Good morning, {{ firstName }}</h2>
                <p>Here is how the gardens are trading this month.</p>
            </div>
            <div class="head-actions">
                <button class="btn btn-ghost"><AppIcon name="download" :size="17" /> Export</button>
                <button class="btn btn-primary"><AppIcon name="plus" :size="17" /> New Product</button>
            </div>
        </div>

        <PageLoader v-if="loading" label="Loading dashboard" />

        <template v-else>
        <div class="stat-grid">
            <StatCard
                label="Total Revenue"
                :value="money(stats?.revenue ?? 0)"
                icon="revenue"
                foot="excludes cancelled orders"
            />
            <StatCard
                label="Orders"
                :value="String(stats?.orders ?? 0)"
                icon="cart"
                tone="gold"
                :foot="`${stats?.pending ?? 0} awaiting fulfilment`"
            />
            <StatCard
                label="Customers"
                :value="String(stats?.customers ?? 0)"
                icon="users"
                tone="info"
                foot="live from database"
            />
            <StatCard
                label="Products"
                :value="String(stats?.products ?? 0)"
                icon="products"
                tone="rose"
                :foot="`${stats?.low_stock ?? 0} low · ${stats?.out_of_stock ?? 0} out`"
            />
        </div>

        <div class="grid-2">
            <section class="card">
                <div class="card-head">
                    <div>
                        <h3>Revenue Trend</h3>
                        <p>Monthly gross revenue, current year vs last</p>
                    </div>
                    <a href="#" class="link" @click.prevent>Full report →</a>
                </div>
                <div class="card-body">
                    <AreaChart :series="dynamicRevenueSeries.series" :labels="dynamicRevenueSeries.labels" />
                </div>
            </section>

            <section class="card">
                <div class="card-head">
                    <div>
                        <h3>Sales by Blend</h3>
                        <p>Units sold this quarter</p>
                    </div>
                </div>
                <div class="card-body">
                    <DonutChart :data="categorySplit" caption="Units" />
                </div>
            </section>
        </div>

        <div class="grid-2">
            <section class="card">
                <div class="card-head">
                    <div>
                        <h3>Recent Orders</h3>
                        <p>Latest activity across all channels</p>
                    </div>
                    <RouterLink to="/admin/orders" class="link">View all →</RouterLink>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="order in recent" :key="order.id">
                                <td><strong>{{ order.id }}</strong></td>
                                <td>
                                    <div class="cell-user">
                                        <span class="mini-avatar">{{ initials(order.customer) }}</span>
                                        <span>
                                            {{ order.customer }}
                                            <small>{{ order.email }}</small>
                                        </span>
                                    </div>
                                </td>
                                <td>{{ order.date }}</td>
                                <td><strong>{{ money(order.total) }}</strong></td>
                                <td>
                                    <span class="pill" :class="statusClass(order.status)">
                                        {{ order.status }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <div style="display: grid; gap: 1.1rem; align-content: start">
                <section class="card">
                    <div class="card-head"><h3>Top Blends</h3></div>
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
                        </div>
                    </div>
                </section>

                <section class="card">
                    <div class="card-head"><h3>Stock Watch</h3></div>
                    <div class="card-body">
                        <div class="timeline">
                            <div class="tl-item gold">
                                <strong>{{ stats?.low_stock ?? 0 }} blends low on stock</strong>
                                <time>below 20 units — reorder soon</time>
                            </div>
                            <div class="tl-item" :class="{ gold: (stats?.out_of_stock ?? 0) > 0 }">
                                <strong>{{ stats?.out_of_stock ?? 0 }} out of stock</strong>
                                <time>not available to customers</time>
                            </div>
                            <div class="tl-item">
                                <strong>{{ stats?.pending ?? 0 }} orders pending</strong>
                                <time>awaiting fulfilment</time>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
        </template>
    </div>
</template>
