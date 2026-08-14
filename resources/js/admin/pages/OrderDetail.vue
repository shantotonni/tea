<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppIcon from '../components/AppIcon.vue'
import Skeleton from '../components/Skeleton.vue'
import BrandMark from '../components/BrandMark.vue'
import { fetchOrder, setOrderStatus, fetchSettings, statusClass, money } from '../data'
import { toast } from '../composables/useToast'

const route = useRoute()
const router = useRouter()

const order = ref(null)
const store = ref({})
const loading = ref(true)
const savingStatus = ref(false)

const STEPS = [
    { label: 'Placed', status: 'Pending' },
    { label: 'Packing', status: 'Pending' },
    { label: 'Shipped', status: 'Shipped' },
    { label: 'Delivered', status: 'Delivered' },
]
const rankOf = (status) => ({ Pending: 1, Shipped: 2, Delivered: 3 }[status] ?? 0)

async function load() {
    loading.value = true
    try {
        const [o, settings] = await Promise.all([fetchOrder(route.params.id), fetchSettings()])
        order.value = o
        store.value = settings.store || {}
    } catch (e) {
        order.value = null
    } finally {
        loading.value = false
    }
}
onMounted(load)

async function changeStatus(status) {
    if (!order.value || order.value.status === status) return
    savingStatus.value = true
    try {
        await setOrderStatus(order.value.id, status)
        order.value.status = status
        toast.success(`Order marked ${status}.`)
    } catch (e) { /* api.js toasts */ } finally {
        savingStatus.value = false
    }
}

const fmtDate = (iso) => (iso ? new Date(iso).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '')
const fmtDateTime = (iso) => (iso ? new Date(iso).toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '')

function printInvoice() {
    window.print()
}
</script>

<template>
    <div class="page">
        <!-- top controls (not printed) -->
        <div class="od-top no-print">
            <button class="od-back" @click="router.push('/admin/orders')">← Back to orders</button>
            <div class="od-top-actions">
                <button class="btn btn-primary" :disabled="loading || !order" @click="printInvoice"><AppIcon name="download" :size="16" /> Print invoice</button>
            </div>
        </div>

        <div v-if="loading" class="card no-print" style="padding: 1.6rem"><Skeleton :rows="8" /></div>

        <p v-else-if="!order" class="card no-print" style="padding: 1.6rem; color: var(--muted)">Order not found. <RouterLink to="/admin/orders">Go back</RouterLink>.</p>

        <template v-else>
            <!-- status control (not printed) -->
            <section class="card od-control no-print">
                <div class="od-control-head">
                    <div>
                        <h2>{{ order.code }}</h2>
                        <span class="pill" :class="statusClass(order.status)">{{ order.status }}</span>
                        <span class="od-dim">Placed {{ fmtDateTime(order.created_at) }} · {{ order.channel }}</span>
                    </div>
                </div>
                <p class="od-track-lbl">Order tracking <em>— click a stage to update the status</em></p>
                <template v-if="order.status !== 'Cancelled'">
                    <div class="od-track">
                        <button
                            v-for="(s, i) in STEPS"
                            :key="s.label"
                            class="od-tstep"
                            :class="{ done: i < rankOf(order.status), active: i === rankOf(order.status) }"
                            :disabled="savingStatus"
                            @click="changeStatus(s.status)"
                        >
                            <span class="od-tdot">{{ i < rankOf(order.status) ? '✓' : i + 1 }}</span>
                            <em>{{ s.label }}</em>
                            <i v-if="i < STEPS.length - 1" class="od-tbar" :class="{ fill: i < rankOf(order.status) }" />
                        </button>
                    </div>
                    <button class="od-cancel" :disabled="savingStatus" @click="changeStatus('Cancelled')">Cancel this order</button>
                </template>
                <div v-else class="od-cancelled">
                    <span>⛔ This order is cancelled.</span>
                    <button class="od-restore" :disabled="savingStatus" @click="changeStatus('Pending')">Restore to Pending</button>
                </div>
            </section>

            <!-- ===== INVOICE (printable) ===== -->
            <section class="invoice">
                <header class="inv-head">
                    <div class="inv-brand">
                        <span class="inv-mark"><BrandMark :size="26" /></span>
                        <div>
                            <strong>{{ store.name || 'Cha Kunjo' }}</strong>
                            <span>{{ store.address || 'Sreemangal, Moulvibazar, Sylhet' }}</span>
                            <span>{{ store.phone }}<template v-if="store.email"> · {{ store.email }}</template></span>
                        </div>
                    </div>
                    <div class="inv-meta">
                        <h1>INVOICE</h1>
                        <div class="inv-meta-row"><span>Invoice no.</span><b>{{ order.code }}</b></div>
                        <div class="inv-meta-row"><span>Date</span><b>{{ fmtDate(order.created_at) }}</b></div>
                        <div class="inv-meta-row"><span>Status</span><b>{{ order.status }}</b></div>
                    </div>
                </header>

                <div class="inv-parties">
                    <div>
                        <p class="inv-lbl">Bill to</p>
                        <strong>{{ order.customer_name }}</strong>
                        <span>{{ order.customer_email }}</span>
                        <span v-if="order.phone">{{ order.phone }}</span>
                    </div>
                    <div>
                        <p class="inv-lbl">Ship to</p>
                        <span>{{ order.address || '—' }}</span>
                        <span>{{ order.city }}</span>
                        <span class="inv-pay">Payment: {{ order.payment_method }}</span>
                    </div>
                </div>

                <table class="inv-table">
                    <thead>
                        <tr><th>Item</th><th class="r">Unit price</th><th class="c">Qty</th><th class="r">Amount</th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="(it, i) in order.items" :key="i">
                            <td>{{ it.product_name }}</td>
                            <td class="r">{{ money(it.price) }}</td>
                            <td class="c">{{ it.qty }}</td>
                            <td class="r">{{ money(it.price * it.qty) }}</td>
                        </tr>
                        <tr v-if="!order.items || !order.items.length"><td colspan="4" class="inv-empty">No line items recorded for this order.</td></tr>
                    </tbody>
                </table>

                <div class="inv-foot">
                    <div class="inv-note">
                        <p v-if="order.note"><b>Note:</b> {{ order.note }}</p>
                        <p class="inv-thanks">Thank you for choosing {{ store.name || 'Cha Kunjo' }} — fresh from the hills of Sreemangal.</p>
                    </div>
                    <div class="inv-totals">
                        <div><span>Subtotal</span><b>{{ money(order.subtotal) }}</b></div>
                        <div v-if="order.discount > 0" class="disc"><span>Discount<template v-if="order.promo_code"> ({{ order.promo_code }})</template></span><b>−{{ money(order.discount) }}</b></div>
                        <div><span>Delivery</span><b>{{ order.shipping ? money(order.shipping) : 'Free' }}</b></div>
                        <div class="inv-grand"><span>Total</span><b>{{ money(order.total) }}</b></div>
                        <p class="inv-cod">{{ order.payment_method }} — payable on delivery</p>
                    </div>
                </div>
            </section>
        </template>
    </div>
</template>

<style scoped>
.od-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.2rem; }
.od-back { display: inline-flex; align-items: center; gap: 0.4rem; background: none; border: none; cursor: pointer; font: inherit; color: var(--muted); font-weight: 500; }
.od-back:hover { color: var(--green-700, #1d4230); }

.od-control { padding: 1.4rem 1.6rem; margin-bottom: 1.4rem; }
.od-control-head > div { display: flex; align-items: center; gap: 0.8rem; flex-wrap: wrap; }
.od-control-head h2 { font-family: var(--serif); margin: 0; font-size: 1.5rem; }
.od-dim { color: var(--muted); font-size: 0.84rem; }
.od-track-lbl { font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--green-700, #1d4230); margin: 1.3rem 0 1rem; }
.od-track-lbl em { font-weight: 400; text-transform: none; letter-spacing: 0; color: var(--muted); }
.od-track { display: flex; align-items: flex-start; max-width: 560px; }
.od-tstep { position: relative; flex: 1; display: flex; flex-direction: column; align-items: center; gap: 0.4rem; border: none; background: none; cursor: pointer; font: inherit; padding: 0; }
.od-tstep:disabled { cursor: default; }
.od-tdot { width: 32px; height: 32px; display: grid; place-items: center; border-radius: 50%; background: #e4ddcc; color: var(--muted); font-size: 0.82rem; font-weight: 700; z-index: 2; transition: all 0.2s var(--ease); }
.od-tstep em { font-size: 0.76rem; font-style: normal; color: var(--muted); }
.od-tstep:hover .od-tdot { box-shadow: 0 0 0 4px rgba(44,107,69,0.12); }
.od-tstep.done .od-tdot { background: var(--green-600, #2c6b45); color: #fff; }
.od-tstep.active .od-tdot { background: var(--gold, #c8a24a); color: #fff; box-shadow: 0 0 0 5px rgba(200,162,74,0.2); }
.od-tstep.done em, .od-tstep.active em { color: var(--ink); font-weight: 600; }
.od-tbar { position: absolute; top: 16px; left: 50%; width: 100%; height: 3px; background: #e4ddcc; z-index: 1; }
.od-tbar.fill { background: var(--green-600, #2c6b45); }
.od-cancel { margin-top: 1.1rem; background: none; border: none; color: var(--danger, #c0492f); font-size: 0.82rem; font-weight: 600; cursor: pointer; padding: 0; }
.od-cancel:hover { text-decoration: underline; }
.od-cancelled { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; color: #b42318; font-weight: 600; }
.od-restore { background: #fff; border: 1.5px solid var(--green-600, #2c6b45); color: var(--green-700, #1d4230); padding: 0.4rem 0.9rem; border-radius: 999px; cursor: pointer; font-weight: 600; }

/* ===== invoice document ===== */
.invoice { background: #fff; border: 1px solid var(--line); border-radius: var(--radius, 16px); padding: 2.4rem; box-shadow: var(--shadow-lg, 0 24px 60px -24px rgba(16,38,28,0.25)); max-width: 820px; }
.inv-head { display: flex; justify-content: space-between; gap: 2rem; padding-bottom: 1.6rem; border-bottom: 2px solid var(--green-800, #163024); flex-wrap: wrap; }
.inv-brand { display: flex; gap: 0.9rem; }
.inv-mark { width: 48px; height: 48px; flex: none; display: grid; place-items: center; border-radius: 12px; background: var(--green-800, #163024); color: var(--gold-soft, #e0c880); }
.inv-brand > div { display: flex; flex-direction: column; }
.inv-brand strong { font-family: var(--serif); font-size: 1.4rem; color: var(--green-800, #163024); }
.inv-brand span { font-size: 0.82rem; color: var(--muted); }
.inv-meta { text-align: right; }
.inv-meta h1 { font-family: var(--serif); font-size: 1.9rem; letter-spacing: 0.1em; color: var(--gold, #c8a24a); margin: 0 0 0.6rem; }
.inv-meta-row { display: flex; justify-content: flex-end; gap: 0.8rem; font-size: 0.85rem; }
.inv-meta-row span { color: var(--muted); }

.inv-parties { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; padding: 1.6rem 0; }
.inv-parties > div { display: flex; flex-direction: column; gap: 0.15rem; }
.inv-lbl { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--gold, #c8a24a); margin: 0 0 0.35rem; }
.inv-parties strong { font-size: 1rem; }
.inv-parties span { font-size: 0.86rem; color: #4a564e; }
.inv-pay { margin-top: 0.35rem; font-weight: 600; color: var(--ink) !important; }

.inv-table { width: 100%; border-collapse: collapse; margin-bottom: 1.6rem; }
.inv-table th { text-align: left; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted); padding: 0.6rem 0.5rem; border-bottom: 1px solid var(--line); }
.inv-table td { padding: 0.7rem 0.5rem; border-bottom: 1px solid #f0ece2; font-size: 0.9rem; }
.inv-table .r { text-align: right; }
.inv-table .c { text-align: center; }
.inv-empty { text-align: center; color: var(--muted); font-style: italic; }

.inv-foot { display: grid; grid-template-columns: 1fr auto; gap: 2rem; }
.inv-note { align-self: end; font-size: 0.84rem; color: var(--muted); max-width: 320px; }
.inv-thanks { font-style: italic; margin-top: 0.6rem; }
.inv-totals { min-width: 240px; display: grid; gap: 0.45rem; }
.inv-totals > div { display: flex; justify-content: space-between; font-size: 0.9rem; color: #4a564e; }
.inv-totals .disc { color: var(--green-600, #2c6b45); }
.inv-grand { border-top: 2px solid var(--green-800, #163024); margin-top: 0.4rem; padding-top: 0.6rem; font-size: 1.25rem !important; font-weight: 700; color: var(--ink) !important; }
.inv-grand b { font-family: var(--serif); }
.inv-cod { text-align: right; font-size: 0.76rem; color: var(--muted); margin: 0.5rem 0 0; }

@media (max-width: 640px) {
    .inv-parties, .inv-foot { grid-template-columns: 1fr; }
    .inv-meta { text-align: left; }
    .inv-meta-row { justify-content: flex-start; }
}
</style>
