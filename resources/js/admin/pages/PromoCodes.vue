<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import AppIcon from '../components/AppIcon.vue'
import StatCard from '../components/StatCard.vue'
import Skeleton from '../components/Skeleton.vue'
import {
    fetchPromoCodes, createPromoCode, updatePromoCode, deletePromoCode,
    fetchProducts, fetchCustomerGroups, money,
} from '../data'
import { toast } from '../composables/useToast'

const codes = ref([])
const products = ref([])
const customerGroups = ref([])
const loading = ref(true)

async function load() {
    loading.value = true
    try {
        const [c, p, g] = await Promise.all([fetchPromoCodes(), fetchProducts(), fetchCustomerGroups()])
        codes.value = c
        products.value = p
        customerGroups.value = g
    } finally { loading.value = false }
}
onMounted(load)

const categories = computed(() => [...new Set(products.value.map((p) => p.category))].filter(Boolean))

const active = computed(() => codes.value.filter((c) => c.is_active).length)
const redemptions = computed(() => codes.value.reduce((s, c) => s + (c.used_count || 0), 0))

const fmtVal = (c) => {
    if (c.type === 'percent') return `${c.value}% off`
    if (c.value > 0) return `${money(c.value)} off`
    return c.free_shipping ? 'Free shipping' : '—'
}
const fmtDate = (iso) => (iso ? new Date(iso).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '')
const isExpired = (c) => c.expires_at && new Date(c.expires_at) < new Date()

// short rule summary chips for the table
function ruleChips(c) {
    const r = []
    if (c.min_subtotal) r.push(`min ${money(c.min_subtotal)}`)
    if (c.max_subtotal) r.push(`max ${money(c.max_subtotal)}`)
    if (c.min_customer_spend) r.push(`spent ≥ ${money(c.min_customer_spend)}`)
    if (c.new_customers_only) r.push('new buyers')
    if (c.group) r.push(`group: ${c.group.name}`)
    if (c.customer_emails?.length) r.push(`${c.customer_emails.length} customer${c.customer_emails.length > 1 ? 's' : ''}`)
    if (c.scope_products?.length) r.push(`${c.scope_products.length} product${c.scope_products.length > 1 ? 's' : ''}`)
    if (c.scope_categories?.length) r.push(c.scope_categories.join(', '))
    if (c.per_customer_limit) r.push(`${c.per_customer_limit}× per buyer`)
    if (c.free_shipping) r.push('free shipping')
    return r
}

const blank = () => ({
    code: '', description: '', type: 'percent', value: 10,
    min_subtotal: 0, max_subtotal: null, max_discount: null,
    usage_limit: null, per_customer_limit: null,
    customer_emails: '', customer_group_id: null, new_customers_only: false, min_customer_spend: null,
    scope_products: [], scope_categories: [],
    free_shipping: false, starts_at: '', expires_at: '', is_active: true,
})
const showForm = ref(false)
const editingId = ref(null)
const busy = ref(false)
const fieldErrors = ref({})
const form = reactive(blank())

const toLocal = (iso) => {
    if (!iso) return ''
    const d = new Date(iso)
    const p = (n) => String(n).padStart(2, '0')
    return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())}T${p(d.getHours())}:${p(d.getMinutes())}`
}

function openCreate() {
    editingId.value = null
    fieldErrors.value = {}
    Object.assign(form, blank())
    showForm.value = true
}
function openEdit(c) {
    editingId.value = c.id
    fieldErrors.value = {}
    Object.assign(form, {
        code: c.code, description: c.description || '', type: c.type, value: c.value,
        min_subtotal: c.min_subtotal || 0, max_subtotal: c.max_subtotal, max_discount: c.max_discount,
        usage_limit: c.usage_limit, per_customer_limit: c.per_customer_limit,
        customer_emails: (c.customer_emails || []).join('\n'),
        customer_group_id: c.customer_group_id || null,
        new_customers_only: !!c.new_customers_only, min_customer_spend: c.min_customer_spend,
        scope_products: [...(c.scope_products || [])], scope_categories: [...(c.scope_categories || [])],
        free_shipping: !!c.free_shipping,
        starts_at: toLocal(c.starts_at), expires_at: toLocal(c.expires_at), is_active: !!c.is_active,
    })
    showForm.value = true
}
function closeForm() { if (!busy.value) showForm.value = false }

function toggleIn(list, val) {
    const i = form[list].indexOf(val)
    if (i === -1) form[list].push(val)
    else form[list].splice(i, 1)
}

const nn = (v) => (v === '' || v === null || v === undefined ? null : v)

function payload() {
    return {
        code: (form.code || '').trim().toUpperCase(),
        description: form.description,
        type: form.type,
        value: Number(form.value) || 0,
        min_subtotal: Number(form.min_subtotal) || 0,
        max_subtotal: nn(form.max_subtotal),
        max_discount: nn(form.max_discount),
        usage_limit: nn(form.usage_limit),
        per_customer_limit: nn(form.per_customer_limit),
        customer_emails: form.customer_emails
            .split(/[\n,]+/).map((e) => e.trim().toLowerCase()).filter(Boolean),
        customer_group_id: nn(form.customer_group_id),
        new_customers_only: form.new_customers_only,
        min_customer_spend: nn(form.min_customer_spend),
        scope_products: form.scope_products,
        scope_categories: form.scope_categories,
        free_shipping: form.free_shipping,
        starts_at: form.starts_at || null,
        expires_at: form.expires_at || null,
        is_active: form.is_active,
    }
}

async function save() {
    busy.value = true
    fieldErrors.value = {}
    try {
        if (editingId.value) {
            await updatePromoCode(editingId.value, payload())
            toast.success('Promo code updated.')
        } else {
            await createPromoCode(payload())
            toast.success('Promo code created.')
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

async function toggleActive(c) {
    try {
        await updatePromoCode(c.id, {
            code: c.code, type: c.type, value: c.value, is_active: !c.is_active,
        })
        c.is_active = !c.is_active
    } catch (e) { /* api.js toasts */ }
}

const confirming = ref(null)
async function remove() {
    if (!confirming.value) return
    busy.value = true
    try {
        await deletePromoCode(confirming.value.id)
        toast.success('Promo code deleted.')
        confirming.value = null
        await load()
    } finally { busy.value = false }
}
</script>

<template>
    <div class="page">
        <div class="page-head">
            <div>
                <p class="eyebrow">Commerce</p>
                <h2>Promo Codes</h2>
                <p>Discount codes with full rules — customer targeting, spend gates, product scope, ranges — all enforced on the server.</p>
            </div>
            <div class="head-actions">
                <button class="btn btn-primary" @click="openCreate"><AppIcon name="plus" :size="17" /> New Code</button>
            </div>
        </div>

        <div class="stat-grid">
            <StatCard label="Total Codes" :value="String(codes.length)" icon="settings" foot="live from database" />
            <StatCard label="Active" :value="String(active)" icon="check" tone="info" />
            <StatCard label="Redemptions" :value="String(redemptions)" icon="orders" tone="gold" foot="times used" />
        </div>

        <section class="card">
            <div v-if="loading" style="padding: 1.4rem"><Skeleton :rows="5" /></div>
            <div v-else class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Code</th><th>Discount</th><th>Rules</th><th>Used</th><th>Expires</th><th>Live</th><th></th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="c in codes" :key="c.id">
                            <td>
                                <strong class="promo-code">{{ c.code }}</strong>
                                <em v-if="c.description" class="promo-desc">{{ c.description }}</em>
                            </td>
                            <td>
                                <span class="pill pending">{{ fmtVal(c) }}</span>
                                <em v-if="c.type === 'percent' && c.max_discount" class="promo-cap"> up to {{ money(c.max_discount) }}</em>
                            </td>
                            <td>
                                <div class="rule-chips">
                                    <span v-for="(r, i) in ruleChips(c)" :key="i" class="rule-chip">{{ r }}</span>
                                    <span v-if="!ruleChips(c).length" style="color: var(--muted)">Anyone</span>
                                </div>
                            </td>
                            <td>{{ c.used_count }}<span v-if="c.usage_limit" style="color: var(--muted)"> / {{ c.usage_limit }}</span></td>
                            <td :class="{ 'promo-expired': isExpired(c) }">{{ c.expires_at ? fmtDate(c.expires_at) : 'No expiry' }}</td>
                            <td><button class="switch sm" :class="{ on: c.is_active }" @click="toggleActive(c)" /></td>
                            <td><div class="row-actions">
                                <button title="Edit" @click="openEdit(c)"><AppIcon name="edit" :size="16" /></button>
                                <button title="Delete" @click="confirming = c"><AppIcon name="trash" :size="16" /></button>
                            </div></td>
                        </tr>
                    </tbody>
                </table>
                <p v-if="!codes.length" class="empty">No promo codes yet.</p>
            </div>
        </section>

        <!-- create / edit -->
        <Transition name="modal">
            <div v-if="showForm" class="modal-wrap" @click.self="closeForm">
                <div class="modal modal-lg">
                    <header class="modal-head">
                        <h3>{{ editingId ? 'Edit promo code' : 'New promo code' }}</h3>
                        <button class="modal-x" aria-label="Close" @click="closeForm">
                            <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                        </button>
                    </header>
                    <div class="modal-body pc-body">
                        <!-- discount -->
                        <div class="pc-sec">
                            <p class="pc-sec-h">Discount</p>
                            <div class="form-grid">
                                <label class="field" :class="{ invalid: fieldErrors.code }">
                                    <span>Code</span>
                                    <input v-model="form.code" type="text" placeholder="KUNJO10" style="text-transform: uppercase" />
                                    <em v-if="fieldErrors.code" class="field-msg">{{ fieldErrors.code[0] }}</em>
                                </label>
                                <label class="field">
                                    <span>Description</span>
                                    <input v-model="form.description" type="text" placeholder="10% off your order" />
                                </label>
                            </div>
                            <div class="form-grid form-grid-3">
                                <label class="field">
                                    <span>Type</span>
                                    <select v-model="form.type">
                                        <option value="percent">Percentage (%)</option>
                                        <option value="fixed">Fixed amount (৳)</option>
                                    </select>
                                </label>
                                <label class="field" :class="{ invalid: fieldErrors.value }">
                                    <span>{{ form.type === 'percent' ? 'Percent off' : 'Amount off ৳' }}</span>
                                    <input v-model.number="form.value" type="number" min="0" :max="form.type === 'percent' ? 100 : null" />
                                    <em v-if="fieldErrors.value" class="field-msg">{{ fieldErrors.value[0] }}</em>
                                </label>
                                <label v-if="form.type === 'percent'" class="field">
                                    <span>Max cap ৳ <em class="opt">optional</em></span>
                                    <input v-model.number="form.max_discount" type="number" min="0" placeholder="no cap" />
                                </label>
                            </div>
                            <label class="mini-check"><input v-model="form.free_shipping" type="checkbox" /> <span>Also waive delivery charge (free shipping)</span></label>
                        </div>

                        <!-- cart range + usage -->
                        <div class="pc-sec">
                            <p class="pc-sec-h">Cart &amp; usage limits</p>
                            <div class="form-grid form-grid-2">
                                <label class="field"><span>Minimum spend ৳</span><input v-model.number="form.min_subtotal" type="number" min="0" placeholder="0" /></label>
                                <label class="field"><span>Maximum cart ৳ <em class="opt">optional</em></span><input v-model.number="form.max_subtotal" type="number" min="0" placeholder="no limit" /></label>
                                <label class="field"><span>Total usage limit <em class="opt">optional</em></span><input v-model.number="form.usage_limit" type="number" min="0" placeholder="unlimited" /></label>
                                <label class="field"><span>Uses per customer <em class="opt">optional</em></span><input v-model.number="form.per_customer_limit" type="number" min="0" placeholder="unlimited" /></label>
                            </div>
                            <div class="form-grid form-grid-2">
                                <label class="field"><span>Starts <em class="opt">optional</em></span><input v-model="form.starts_at" type="datetime-local" /></label>
                                <label class="field"><span>Expires <em class="opt">optional</em></span><input v-model="form.expires_at" type="datetime-local" /></label>
                            </div>
                        </div>

                        <!-- customer targeting -->
                        <div class="pc-sec">
                            <p class="pc-sec-h">Customer targeting</p>
                            <div class="form-grid form-grid-2">
                                <label class="field">
                                    <span>Restrict to a customer group <em class="opt">optional</em></span>
                                    <select v-model="form.customer_group_id">
                                        <option :value="null">Everyone (no group)</option>
                                        <option v-for="g in customerGroups" :key="g.id" :value="g.id">{{ g.name }} ({{ g.customers_count }})</option>
                                    </select>
                                    <em class="opt" style="display:block;margin-top:0.3rem">Only members of this group can redeem. <RouterLink to="/admin/customer-groups">Manage groups</RouterLink></em>
                                </label>
                                <label class="field"><span>Unlocks after lifetime spend ৳ <em class="opt">optional</em></span><input v-model.number="form.min_customer_spend" type="number" min="0" placeholder="e.g. 2000 → next-order reward" /></label>
                            </div>
                            <label class="mini-check pc-flag"><input v-model="form.new_customers_only" type="checkbox" /> <span>First-time buyers only</span></label>
                            <label class="field" style="margin-top:0.6rem">
                                <span>Or restrict to specific emails <em class="opt">(one per line or comma-separated — leave blank for everyone)</em></span>
                                <textarea v-model="form.customer_emails" rows="2" placeholder="nusrat@mail.com, rafiq.h@mail.com" />
                            </label>
                        </div>

                        <!-- product scope -->
                        <div class="pc-sec">
                            <p class="pc-sec-h">Product scope <em class="opt">— leave all unchecked to apply to the whole cart</em></p>
                            <p class="pc-mini">Categories</p>
                            <div class="pc-chips">
                                <button v-for="cat in categories" :key="cat" type="button" class="pc-chip" :class="{ on: form.scope_categories.includes(cat) }" @click="toggleIn('scope_categories', cat)">{{ cat }}</button>
                                <span v-if="!categories.length" style="color: var(--muted); font-size: 0.82rem">No categories.</span>
                            </div>
                            <p class="pc-mini" style="margin-top: 0.7rem">Individual products</p>
                            <div class="pc-prod-list">
                                <label v-for="p in products" :key="p.slug || p.sku" class="pc-prod" :class="{ on: form.scope_products.includes(p.slug) }">
                                    <input type="checkbox" :checked="form.scope_products.includes(p.slug)" @change="toggleIn('scope_products', p.slug)" />
                                    <span>{{ p.name }}</span>
                                    <em>{{ p.category }}</em>
                                </label>
                            </div>
                        </div>

                        <label class="mini-check pc-flag"><input v-model="form.is_active" type="checkbox" /> <span>Active (usable at checkout)</span></label>
                    </div>
                    <footer class="modal-foot">
                        <button class="btn btn-ghost" :disabled="busy" @click="closeForm">Cancel</button>
                        <button class="btn btn-primary" :disabled="busy" @click="save">{{ busy ? 'Saving…' : editingId ? 'Save changes' : 'Create code' }}</button>
                    </footer>
                </div>
            </div>
        </Transition>

        <!-- delete -->
        <Transition name="modal">
            <div v-if="confirming" class="modal-wrap" @click.self="confirming = null">
                <div class="modal modal-sm">
                    <div class="modal-body confirm-body">
                        <span class="confirm-ico"><AppIcon name="trash" :size="22" /></span>
                        <h3>Delete this code?</h3>
                        <p>“{{ confirming.code }}” — shoppers will no longer be able to use it.</p>
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
.promo-code { font-family: ui-monospace, monospace; letter-spacing: 0.05em; display: block; }
.promo-desc { display: block; color: var(--muted); font-style: normal; font-size: 0.8rem; margin-top: 0.1rem; }
.promo-cap { color: var(--muted); font-style: normal; font-size: 0.78rem; }
.promo-expired { color: var(--danger, #c0492f); }
.field .opt { color: var(--muted); font-weight: 400; text-transform: none; letter-spacing: 0; }

.rule-chips { display: flex; flex-wrap: wrap; gap: 0.3rem; max-width: 320px; }
.rule-chip { font-size: 0.72rem; background: var(--cream-2, #efe7d6); color: #5a4e2e; padding: 0.1rem 0.5rem; border-radius: 999px; white-space: nowrap; }

.pc-body { display: grid; gap: 1.3rem; }
.pc-sec { border: 1px solid var(--line); border-radius: 12px; padding: 1.1rem 1.2rem; background: #fdfcf9; }
.pc-sec-h { font-size: 0.82rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--green-700, #1d4230); margin: 0 0 0.9rem; }
.pc-sec-h .opt { font-weight: 400; text-transform: none; letter-spacing: 0; color: var(--muted); }
.pc-mini { font-size: 0.75rem; font-weight: 600; color: #4a564e; margin: 0 0 0.45rem; }
.form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem; }
.form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.85rem; }
.pc-flag { margin-top: 0.2rem; }

.pc-chips { display: flex; flex-wrap: wrap; gap: 0.4rem; }
.pc-chip { font-size: 0.82rem; padding: 0.35rem 0.8rem; border-radius: 999px; border: 1.5px solid var(--line); background: var(--white); cursor: pointer; transition: all 0.15s var(--ease); }
.pc-chip.on { border-color: var(--green-600, #2c6b45); background: #eef5ee; color: var(--green-800, #163024); font-weight: 600; }

.pc-prod-list { display: grid; grid-template-columns: 1fr 1fr; gap: 0.4rem; max-height: 200px; overflow-y: auto; }
.pc-prod { display: flex; align-items: center; gap: 0.5rem; padding: 0.45rem 0.6rem; border: 1px solid var(--line); border-radius: 9px; background: var(--white); cursor: pointer; font-size: 0.84rem; }
.pc-prod.on { border-color: var(--green-600, #2c6b45); background: #f4f8f4; }
.pc-prod em { margin-left: auto; color: var(--muted); font-style: normal; font-size: 0.74rem; }

@media (max-width: 620px) {
    .form-grid-2, .form-grid-3, .pc-prod-list { grid-template-columns: 1fr; }
}
</style>
