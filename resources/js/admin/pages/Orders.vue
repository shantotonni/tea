<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import AppIcon from '../components/AppIcon.vue'
import StatCard from '../components/StatCard.vue'
import Skeleton from '../components/Skeleton.vue'
import { fetchOrders, statusClass, initials, money } from '../data'
import { toast } from '../composables/useToast'

const router = useRouter()
const filters = ['All', 'Pending', 'Shipped', 'Delivered', 'Cancelled']
const active = ref('All')
const query = ref('')
const orders = ref([])
const loading = ref(true)

async function load() {
    loading.value = true
    try {
        orders.value = await fetchOrders()
    } finally {
        loading.value = false
    }
}
onMounted(load)

const rows = computed(() =>
    orders.value.filter((o) => {
        const matchesStatus = active.value === 'All' || o.status === active.value
        const q = query.value.trim().toLowerCase()
        const matchesQuery = !q || o.id.toLowerCase().includes(q) || o.customer.toLowerCase().includes(q) || (o.email || '').toLowerCase().includes(q)
        return matchesStatus && matchesQuery
    })
)

const count = (status) => orders.value.filter((o) => o.status === status).length
const revenue = computed(() => orders.value.filter((o) => o.status !== 'Cancelled').reduce((s, o) => s + o.total, 0))

const openDetail = (o) => router.push(`/admin/orders/${o.key}`)

function exportCsv() {
    const head = ['Order', 'Customer', 'Email', 'Phone', 'City', 'Items', 'Total', 'Status', 'Channel', 'Date']
    const esc = (v) => `"${String(v ?? '').replace(/"/g, '""')}"`
    const lines = [head.join(',')]
    for (const o of rows.value) {
        lines.push([o.id, o.customer, o.email, o.phone, o.city, o.items, o.total, o.status, o.channel, o.date].map(esc).join(','))
    }
    const blob = new Blob(['﻿' + lines.join('\n')], { type: 'text/csv;charset=utf-8;' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `cha-kunjo-orders-${new Date().toISOString().slice(0, 10)}.csv`
    a.click()
    URL.revokeObjectURL(url)
    toast.success(`Exported ${rows.value.length} orders.`)
}
</script>

<template>
    <div class="page">
        <div class="page-head">
            <div>
                <p class="eyebrow">Commerce</p>
                <h2>Orders</h2>
                <p>Every order placed across web, phone and social channels.</p>
            </div>
            <div class="head-actions">
                <button class="btn btn-ghost" :disabled="!rows.length" @click="exportCsv"><AppIcon name="download" :size="17" /> Export CSV</button>
            </div>
        </div>

        <div class="stat-grid">
            <StatCard label="Total Orders" :value="String(orders.length)" icon="orders" foot="live from database" />
            <StatCard label="Pending" :value="String(count('Pending'))" icon="warn" tone="gold" />
            <StatCard label="Delivered" :value="String(count('Delivered'))" icon="check" tone="info" />
            <StatCard label="Revenue" :value="money(revenue)" icon="revenue" tone="rose" foot="excl. cancelled" />
        </div>

        <section class="card">
            <div class="toolbar">
                <div class="toolbar-chips">
                    <button v-for="f in filters" :key="f" class="chip" :class="{ active: active === f }" @click="active = f">{{ f }}</button>
                </div>
                <div class="search">
                    <AppIcon name="search" :size="16" />
                    <input v-model="query" type="search" placeholder="Order id, customer or email…" />
                </div>
            </div>

            <div v-if="loading" style="padding: 1.4rem"><Skeleton :rows="6" /></div>

            <div v-else class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Order</th><th>Customer</th><th>Channel</th><th>Items</th><th>Date</th><th>Total</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="order in rows" :key="order.key" class="clickable" @click="openDetail(order)">
                            <td><strong>{{ order.id }}</strong></td>
                            <td>
                                <div class="cell-user">
                                    <span class="mini-avatar">{{ initials(order.customer) }}</span>
                                    <span>{{ order.customer }}<small>{{ order.email }}</small></span>
                                </div>
                            </td>
                            <td>{{ order.channel }}</td>
                            <td>{{ order.items }}</td>
                            <td>{{ order.date }}</td>
                            <td><strong>{{ money(order.total) }}</strong></td>
                            <td><span class="pill" :class="statusClass(order.status)">{{ order.status }}</span></td>
                            <td @click.stop>
                                <div class="row-actions">
                                    <button title="View & invoice" @click="openDetail(order)"><AppIcon name="eye" :size="16" /></button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p v-if="!rows.length" class="empty">No orders match this filter.</p>
            </div>
        </section>
    </div>
</template>

<style scoped>
.clickable { cursor: pointer; }
.toolbar-chips { display: flex; gap: 0.4rem; flex-wrap: wrap; }
</style>
