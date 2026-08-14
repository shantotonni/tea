<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import AppIcon from '../components/AppIcon.vue'
import StatCard from '../components/StatCard.vue'
import Skeleton from '../components/Skeleton.vue'
import {
    fetchCustomers, fetchCustomer, createCustomer, updateCustomer, deleteCustomer,
    statusClass, initials, money,
} from '../data'
import { toast } from '../composables/useToast'

const query = ref('')
const tierFilter = ref('')
const customers = ref([])
const loading = ref(true)

const TIERS = ['Bronze', 'Silver', 'Gold']

async function load() {
    loading.value = true
    try {
        customers.value = await fetchCustomers({ tier: tierFilter.value })
    } finally {
        loading.value = false
    }
}
onMounted(load)
watch(tierFilter, load)

const rows = computed(() => {
    const q = query.value.trim().toLowerCase()
    if (!q) return customers.value
    return customers.value.filter(
        (c) => c.name.toLowerCase().includes(q) || (c.city || '').toLowerCase().includes(q) || (c.email || '').toLowerCase().includes(q)
    )
})

const avgSpend = computed(() =>
    customers.value.length ? Math.round(customers.value.reduce((s, c) => s + c.spent, 0) / customers.value.length) : 0
)
const gold = computed(() => customers.value.filter((c) => c.tier === 'Gold').length)
const totalSpent = computed(() => customers.value.reduce((s, c) => s + c.spent, 0))

/* ---------- add / edit ---------- */
const blank = () => ({ name: '', email: '', phone: '', city: '', tier: 'Bronze', password: '' })
const showForm = ref(false)
const editingId = ref(null)
const busy = ref(false)
const fieldErrors = ref({})
const form = reactive(blank())

function openCreate() {
    editingId.value = null
    fieldErrors.value = {}
    Object.assign(form, blank())
    showForm.value = true
}
function openEdit(c) {
    editingId.value = c.id
    fieldErrors.value = {}
    Object.assign(form, { name: c.name, email: c.email, phone: c.phone || '', city: c.city || '', tier: c.tier || 'Bronze', password: '' })
    showForm.value = true
}
function closeForm() { if (!busy.value) showForm.value = false }

async function save() {
    busy.value = true
    fieldErrors.value = {}
    try {
        const payload = { ...form }
        if (!payload.password) delete payload.password
        if (editingId.value) {
            await updateCustomer(editingId.value, payload)
            toast.success('Customer updated.')
        } else {
            await createCustomer(payload)
            toast.success('Customer added.')
        }
        showForm.value = false
        await load()
    } catch (e) {
        fieldErrors.value = e.data?.errors || {}
        if (e.status === 422) toast.error('Please fix the highlighted fields.')
    } finally {
        busy.value = false
    }
}

/* ---------- detail drawer ---------- */
const detail = ref(null)
const detailLoading = ref(false)
async function openDetail(c) {
    detail.value = { id: c.id, name: c.name } // placeholder while loading
    detailLoading.value = true
    try {
        detail.value = await fetchCustomer(c.id)
    } catch (e) {
        detail.value = null
    } finally {
        detailLoading.value = false
    }
}
const fmtDate = (iso) => (iso ? new Date(iso).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '')

function editFromDetail() {
    const d = detail.value
    detail.value = null
    openEdit(d)
}

/* ---------- delete ---------- */
const confirming = ref(null)
async function remove() {
    if (!confirming.value) return
    busy.value = true
    try {
        await deleteCustomer(confirming.value.id)
        toast.success('Customer deleted.')
        confirming.value = null
        detail.value = null
        await load()
    } finally {
        busy.value = false
    }
}

/* ---------- CSV export ---------- */
function exportCsv() {
    const head = ['Name', 'Email', 'Phone', 'City', 'Orders', 'Spent', 'Tier', 'Joined']
    const esc = (v) => `"${String(v ?? '').replace(/"/g, '""')}"`
    const lines = [head.join(',')]
    for (const c of rows.value) {
        lines.push([c.name, c.email, c.phone, c.city, c.orders, c.spent, c.tier, c.joined].map(esc).join(','))
    }
    const blob = new Blob(['﻿' + lines.join('\n')], { type: 'text/csv;charset=utf-8;' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `cha-kunjo-customers-${new Date().toISOString().slice(0, 10)}.csv`
    a.click()
    URL.revokeObjectURL(url)
    toast.success(`Exported ${rows.value.length} customers.`)
}
</script>

<template>
    <div class="page">
        <div class="page-head">
            <div>
                <p class="eyebrow">Audience</p>
                <h2>Customers</h2>
                <p>Who is drinking, how often, and how much they spend.</p>
            </div>
            <div class="head-actions">
                <button class="btn btn-ghost" :disabled="!rows.length" @click="exportCsv"><AppIcon name="download" :size="17" /> Export</button>
                <button class="btn btn-primary" @click="openCreate"><AppIcon name="plus" :size="17" /> Add Customer</button>
            </div>
        </div>

        <div class="stat-grid">
            <StatCard label="Total Customers" :value="String(customers.length)" icon="customers" foot="live from database" />
            <StatCard label="Gold Tier" :value="String(gold)" icon="star" tone="gold" />
            <StatCard label="Avg. Lifetime Value" :value="money(avgSpend)" icon="revenue" tone="info" />
            <StatCard label="Total Spent" :value="money(totalSpent)" icon="revenue" tone="rose" />
        </div>

        <section class="card">
            <div class="toolbar">
                <div class="toolbar-chips">
                    <button class="chip" :class="{ active: tierFilter === '' }" @click="tierFilter = ''">All</button>
                    <button v-for="t in TIERS" :key="t" class="chip" :class="{ active: tierFilter === t }" @click="tierFilter = t">{{ t }}</button>
                </div>
                <div class="search">
                    <AppIcon name="search" :size="16" />
                    <input v-model="query" type="search" placeholder="Name, email or city…" />
                </div>
            </div>

            <div v-if="loading" style="padding: 1.4rem"><Skeleton :rows="6" /></div>

            <div v-else class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Customer</th><th>Phone</th><th>City</th><th>Orders</th><th>Total Spent</th><th>Tier</th><th>Joined</th><th></th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="c in rows" :key="c.id" class="clickable" @click="openDetail(c)">
                            <td>
                                <div class="cell-user">
                                    <span class="mini-avatar">{{ initials(c.name) }}</span>
                                    <span><strong>{{ c.name }}</strong><small>{{ c.email }}</small></span>
                                </div>
                            </td>
                            <td>{{ c.phone || '—' }}</td>
                            <td>{{ c.city || '—' }}</td>
                            <td>{{ c.orders }}</td>
                            <td><strong>{{ money(c.spent) }}</strong></td>
                            <td><span class="pill" :class="statusClass(c.tier)">{{ c.tier }}</span></td>
                            <td>{{ c.joined }}</td>
                            <td @click.stop>
                                <div class="row-actions">
                                    <button title="View" @click="openDetail(c)"><AppIcon name="eye" :size="16" /></button>
                                    <button title="Edit" @click="openEdit(c)"><AppIcon name="edit" :size="16" /></button>
                                    <button title="Delete" @click="confirming = c"><AppIcon name="trash" :size="16" /></button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p v-if="!rows.length" class="empty">No customers match that search.</p>
            </div>
        </section>

        <!-- ===== detail drawer ===== -->
        <Transition name="modal">
            <div v-if="detail" class="modal-wrap" @click.self="detail = null">
                <div class="modal cust-detail">
                    <header class="modal-head">
                        <div class="cust-head">
                            <span class="cust-avatar">{{ initials(detail.name) }}</span>
                            <div>
                                <h3>{{ detail.name }}</h3>
                                <span v-if="detail.tier" class="pill" :class="statusClass(detail.tier)">{{ detail.tier }}</span>
                                <span v-if="detail.has_login" class="cust-badge">Registered account</span>
                            </div>
                        </div>
                        <button class="modal-x" aria-label="Close" @click="detail = null">
                            <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                        </button>
                    </header>

                    <div class="modal-body">
                        <div v-if="detailLoading" style="padding: 1rem"><Skeleton :rows="4" /></div>
                        <template v-else>
                            <div class="cust-grid">
                                <div class="cust-info">
                                    <div class="cust-row"><span>Email</span><b>{{ detail.email }}</b></div>
                                    <div class="cust-row"><span>Phone</span><b>{{ detail.phone || '—' }}</b></div>
                                    <div class="cust-row"><span>City</span><b>{{ detail.city || '—' }}</b></div>
                                    <div class="cust-row"><span>Joined</span><b>{{ fmtDate(detail.created_at) }}</b></div>
                                </div>
                                <div class="cust-stats">
                                    <div class="cust-stat"><b>{{ detail.real_orders ?? detail.orders_count }}</b><span>Orders</span></div>
                                    <div class="cust-stat"><b>{{ money(detail.real_spent ?? detail.spent) }}</b><span>Lifetime spend</span></div>
                                </div>
                            </div>

                            <p class="cust-sub">Order history</p>
                            <ul v-if="detail.orders && detail.orders.length" class="cust-orders">
                                <li v-for="o in detail.orders" :key="o.code" class="cust-order">
                                    <div class="cust-order-head">
                                        <strong>{{ o.code }}</strong>
                                        <span class="pill" :class="statusClass(o.status)">{{ o.status }}</span>
                                        <span class="cust-order-date">{{ fmtDate(o.created_at) }}</span>
                                        <span class="cust-order-total">{{ money(o.total) }}</span>
                                    </div>
                                    <p class="cust-order-items">{{ o.items.map((i) => `${i.product_name} ×${i.qty}`).join(' · ') }}</p>
                                </li>
                            </ul>
                            <p v-else class="empty" style="padding: 1rem 0">No orders yet.</p>
                        </template>
                    </div>

                    <footer class="modal-foot">
                        <button class="btn btn-danger" style="margin-right: auto" @click="confirming = detail; detail = null">Delete</button>
                        <button class="btn btn-ghost" @click="detail = null">Close</button>
                        <button class="btn btn-primary" @click="editFromDetail">Edit</button>
                    </footer>
                </div>
            </div>
        </Transition>

        <!-- ===== add / edit ===== -->
        <Transition name="modal">
            <div v-if="showForm" class="modal-wrap" @click.self="closeForm">
                <div class="modal">
                    <header class="modal-head">
                        <h3>{{ editingId ? 'Edit customer' : 'Add customer' }}</h3>
                        <button class="modal-x" aria-label="Close" @click="closeForm">
                            <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                        </button>
                    </header>
                    <div class="modal-body">
                        <div class="form-grid">
                            <label class="field" :class="{ invalid: fieldErrors.name }">
                                <span>Full name</span>
                                <input v-model="form.name" type="text" placeholder="Nusrat Ahmed" />
                                <em v-if="fieldErrors.name" class="field-msg">{{ fieldErrors.name[0] }}</em>
                            </label>
                            <label class="field" :class="{ invalid: fieldErrors.email }">
                                <span>Email</span>
                                <input v-model="form.email" type="email" placeholder="you@email.com" />
                                <em v-if="fieldErrors.email" class="field-msg">{{ fieldErrors.email[0] }}</em>
                            </label>
                            <label class="field">
                                <span>Phone</span>
                                <input v-model="form.phone" type="tel" placeholder="01XXX-XXXXXX" />
                            </label>
                            <label class="field">
                                <span>City</span>
                                <input v-model="form.city" type="text" placeholder="Dhaka" />
                            </label>
                            <label class="field">
                                <span>Tier</span>
                                <select v-model="form.tier">
                                    <option v-for="t in TIERS" :key="t">{{ t }}</option>
                                </select>
                            </label>
                            <label class="field" :class="{ invalid: fieldErrors.password }">
                                <span>{{ editingId ? 'Reset password' : 'Password' }} <em class="opt">optional</em></span>
                                <input v-model="form.password" type="password" placeholder="min 8 chars — lets them sign in" />
                                <em v-if="fieldErrors.password" class="field-msg">{{ fieldErrors.password[0] }}</em>
                            </label>
                        </div>
                    </div>
                    <footer class="modal-foot">
                        <button class="btn btn-ghost" :disabled="busy" @click="closeForm">Cancel</button>
                        <button class="btn btn-primary" :disabled="busy" @click="save">{{ busy ? 'Saving…' : editingId ? 'Save changes' : 'Add customer' }}</button>
                    </footer>
                </div>
            </div>
        </Transition>

        <!-- ===== delete ===== -->
        <Transition name="modal">
            <div v-if="confirming" class="modal-wrap" @click.self="confirming = null">
                <div class="modal modal-sm">
                    <div class="modal-body confirm-body">
                        <span class="confirm-ico"><AppIcon name="trash" :size="22" /></span>
                        <h3>Delete {{ confirming.name }}?</h3>
                        <p>This removes their account. Past orders are kept but no longer linked.</p>
                    </div>
                    <footer class="modal-foot">
                        <button class="btn btn-ghost" :disabled="busy" @click="confirming = null">Cancel</button>
                        <button class="btn btn-danger" :disabled="busy" @click="remove">{{ busy ? 'Deleting…' : 'Delete' }}</button>
                    </footer>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.clickable { cursor: pointer; }
.toolbar-chips { display: flex; gap: 0.4rem; flex-wrap: wrap; }
.opt { color: var(--muted); font-weight: 400; text-transform: none; letter-spacing: 0; }

.cust-detail { width: min(640px, 94vw); }
.cust-head { display: flex; align-items: center; gap: 0.9rem; }
.cust-avatar {
    width: 46px; height: 46px; flex: none; display: grid; place-items: center; border-radius: 50%;
    background: rgba(255,255,255,0.14); color: var(--cream); font-weight: 700; font-size: 0.95rem;
}
.cust-head h3 { margin: 0 0 0.25rem; }
.cust-badge { font-size: 0.7rem; background: rgba(255,255,255,0.16); color: var(--cream); padding: 0.1rem 0.5rem; border-radius: 999px; margin-left: 0.4rem; }

.cust-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 1rem; margin-bottom: 1.3rem; }
.cust-info { display: grid; gap: 0.5rem; }
.cust-row { display: flex; justify-content: space-between; font-size: 0.9rem; border-bottom: 1px solid var(--line); padding-bottom: 0.5rem; }
.cust-row span { color: var(--muted); }
.cust-stats { display: grid; gap: 0.6rem; align-content: start; }
.cust-stat { background: var(--cream, #f6f1e7); border-radius: 12px; padding: 0.7rem 0.9rem; text-align: center; }
.cust-stat b { display: block; font-family: var(--serif); font-size: 1.4rem; color: var(--green-800, #163024); }
.cust-stat span { font-size: 0.74rem; color: var(--muted); }

.cust-sub { font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--green-700, #1d4230); margin: 0 0 0.7rem; }
.cust-orders { list-style: none; margin: 0; padding: 0; display: grid; gap: 0.7rem; }
.cust-order { border: 1px solid var(--line); border-radius: 11px; padding: 0.8rem 0.95rem; }
.cust-order-head { display: flex; align-items: center; gap: 0.7rem; flex-wrap: wrap; }
.cust-order-head strong { font-size: 0.92rem; }
.cust-order-date { color: var(--muted); font-size: 0.82rem; }
.cust-order-total { margin-left: auto; font-weight: 700; }
.cust-order-items { margin: 0.5rem 0 0; color: var(--muted); font-size: 0.84rem; }

@media (max-width: 560px) { .cust-grid { grid-template-columns: 1fr; } }
</style>
