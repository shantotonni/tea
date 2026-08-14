<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import AppIcon from '../components/AppIcon.vue'
import StatCard from '../components/StatCard.vue'
import Skeleton from '../components/Skeleton.vue'
import {
    fetchOfferCampaigns, fetchOfferCampaign, createOfferCampaign,
    updateOfferCampaign, deleteOfferCampaign, fetchProducts, fetchPromoCodes, asset,
} from '../data'
import { toast } from '../composables/useToast'

const campaigns = ref([])
const products = ref([])
const promos = ref([])
const loading = ref(true)

async function load() {
    loading.value = true
    try {
        const [c, p, pr] = await Promise.all([fetchOfferCampaigns(), fetchProducts(), fetchPromoCodes()])
        campaigns.value = c
        products.value = p
        promos.value = pr
    } finally { loading.value = false }
}
onMounted(load)

const liveCount = computed(() => campaigns.value.filter((c) => c.live).length)

/* ---- create / edit ---- */
const blank = () => ({
    title: '', subtitle: '', badge: '', discount_label: '', promo_code_id: null,
    starts_at: '', ends_at: '', is_active: true, sort_order: 0, product_ids: [],
})
const showForm = ref(false)
const editingId = ref(null)
const busy = ref(false)
const fieldErrors = ref({})
const form = reactive(blank())
const search = ref('')

const filteredProducts = computed(() => {
    const q = search.value.trim().toLowerCase()
    if (!q) return products.value
    return products.value.filter((p) => p.name.toLowerCase().includes(q) || (p.category || '').toLowerCase().includes(q))
})

// datetime-local wants "YYYY-MM-DDTHH:mm"; API returns ISO / null
function toLocalInput(v) {
    if (!v) return ''
    return String(v).slice(0, 16).replace(' ', 'T')
}

function openCreate() {
    editingId.value = null; fieldErrors.value = {}; search.value = ''
    Object.assign(form, blank())
    showForm.value = true
}
async function openEdit(c) {
    editingId.value = c.id; fieldErrors.value = {}; search.value = ''
    Object.assign(form, blank())
    try {
        const d = await fetchOfferCampaign(c.id)
        Object.assign(form, {
            title: d.title || '', subtitle: d.subtitle || '', badge: d.badge || '',
            discount_label: d.discount_label || '', promo_code_id: d.promo_code_id || null,
            starts_at: toLocalInput(d.starts_at), ends_at: toLocalInput(d.ends_at),
            is_active: !!d.is_active, sort_order: d.sort_order || 0,
            product_ids: [...(d.product_ids || [])],
        })
        showForm.value = true
    } catch (e) { toast.error('Could not load campaign.') }
}
function closeForm() { if (!busy.value) showForm.value = false }

function toggleProduct(id) {
    const i = form.product_ids.indexOf(id)
    if (i === -1) form.product_ids.push(id)
    else form.product_ids.splice(i, 1)
}

async function save() {
    if (!form.product_ids.length) { toast.error('Pick at least one product for the offer.'); return }
    busy.value = true; fieldErrors.value = {}
    // blank strings -> null so the API date rules pass
    const payload = {
        ...form,
        starts_at: form.starts_at || null,
        ends_at: form.ends_at || null,
    }
    try {
        if (editingId.value) { await updateOfferCampaign(editingId.value, payload); toast.success('Offer campaign updated.') }
        else { await createOfferCampaign(payload); toast.success('Offer campaign created.') }
        showForm.value = false; await load()
    } catch (e) {
        fieldErrors.value = e.data?.errors || {}
        if (e.status === 422) toast.error('Please fix the highlighted fields.')
        else toast.error('Could not save campaign.')
    } finally { busy.value = false }
}

/* ---- quick active toggle ---- */
async function toggleActive(c) {
    try {
        await updateOfferCampaign(c.id, { title: c.title, is_active: !c.is_active })
        toast.success(!c.is_active ? 'Offer switched on.' : 'Offer switched off.')
        await load()
    } catch (e) { toast.error('Could not update.') }
}

/* ---- delete ---- */
const confirming = ref(null)
async function remove() {
    if (!confirming.value) return
    busy.value = true
    try {
        await deleteOfferCampaign(confirming.value.id)
        toast.success('Offer campaign deleted.')
        confirming.value = null
        await load()
    } finally { busy.value = false }
}

function windowLabel(c) {
    const f = (v) => (v ? String(v).slice(0, 10) : null)
    const s = f(c.starts_at); const e = f(c.ends_at)
    if (!s && !e) return 'Always on'
    if (s && e) return `${s} → ${e}`
    if (s) return `From ${s}`
    return `Until ${e}`
}
</script>

<template>
    <div class="page">
        <div class="page-head">
            <div>
                <p class="eyebrow">Commerce</p>
                <h2>Occasion Offers</h2>
                <p>Run festive / seasonal offers (Eid, Pohela Boishakh, Winter…). Pick the products, set a date window, and the storefront shows a themed offer section. When nothing is live, the section disappears from the site automatically.</p>
            </div>
            <div class="head-actions">
                <button class="btn btn-primary" @click="openCreate"><AppIcon name="plus" :size="17" /> New Offer</button>
            </div>
        </div>

        <div class="stat-grid">
            <StatCard label="Total Campaigns" :value="String(campaigns.length)" icon="star" foot="live from database" />
            <StatCard label="Live Right Now" :value="String(liveCount)" icon="check" :tone="liveCount ? 'gold' : 'info'" :foot="liveCount ? 'showing on storefront' : 'storefront section hidden'" />
        </div>

        <section class="card">
            <div v-if="loading" style="padding: 1.4rem"><Skeleton :rows="4" /></div>
            <div v-else class="table-wrap">
                <table>
                    <thead><tr><th>Offer</th><th>Badge</th><th>Coupon</th><th>Window</th><th>Products</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="c in campaigns" :key="c.id">
                            <td>
                                <strong>{{ c.title }}</strong>
                                <div v-if="c.subtitle" class="sub">{{ c.subtitle }}</div>
                            </td>
                            <td><span v-if="c.badge" class="badge-chip">{{ c.badge }}</span><span v-else style="color: var(--muted)">—</span></td>
                            <td><span v-if="c.coupon" class="coupon-chip">🎟 {{ c.coupon }}</span><span v-else style="color: var(--muted)">—</span></td>
                            <td><span style="color: var(--muted); font-size: 0.85rem">{{ windowLabel(c) }}</span></td>
                            <td><span class="pill pending">{{ c.products_count }} item{{ c.products_count === 1 ? '' : 's' }}</span></td>
                            <td>
                                <span v-if="c.live" class="pill delivered">● Live</span>
                                <span v-else-if="c.is_active" class="pill pending">Scheduled / expired</span>
                                <span v-else class="pill cancelled">Off</span>
                            </td>
                            <td><div class="row-actions">
                                <button :title="c.is_active ? 'Switch off' : 'Switch on'" @click="toggleActive(c)"><AppIcon :name="c.is_active ? 'eye' : 'warn'" :size="16" /></button>
                                <button title="Edit" @click="openEdit(c)"><AppIcon name="edit" :size="16" /></button>
                                <button title="Delete" @click="confirming = c"><AppIcon name="trash" :size="16" /></button>
                            </div></td>
                        </tr>
                    </tbody>
                </table>
                <p v-if="!campaigns.length" class="empty">No offer campaigns yet. Create one for your next occasion.</p>
            </div>
        </section>

        <!-- create / edit -->
        <Transition name="modal">
            <div v-if="showForm" class="modal-wrap" @click.self="closeForm">
                <div class="modal">
                    <header class="modal-head">
                        <h3>{{ editingId ? 'Edit offer campaign' : 'New offer campaign' }}</h3>
                        <button class="modal-x" aria-label="Close" @click="closeForm">
                            <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                        </button>
                    </header>
                    <div class="modal-body">
                        <div class="grid-2">
                            <label class="field" :class="{ invalid: fieldErrors.title }">
                                <span>Offer title</span>
                                <input v-model="form.title" type="text" placeholder="Eid Special Collection" />
                                <em v-if="fieldErrors.title" class="field-msg">{{ fieldErrors.title[0] }}</em>
                            </label>
                            <label class="field" :class="{ invalid: fieldErrors.badge }">
                                <span>Festive badge <em>(optional)</em></span>
                                <input v-model="form.badge" type="text" placeholder="Eid Mubarak" maxlength="60" />
                            </label>
                        </div>
                        <label class="field" :class="{ invalid: fieldErrors.subtitle }">
                            <span>Subtitle <em>(optional)</em></span>
                            <input v-model="form.subtitle" type="text" placeholder="Celebrate with our finest festive blends" maxlength="240" />
                        </label>
                        <div class="grid-2">
                            <label class="field">
                                <span>Discount label <em>(optional)</em></span>
                                <input v-model="form.discount_label" type="text" placeholder="Up to 20% off" maxlength="60" />
                            </label>
                            <label class="field">
                                <span>Sort order</span>
                                <input v-model.number="form.sort_order" type="number" min="0" />
                            </label>
                        </div>
                        <label class="field">
                            <span>Attach a coupon <em>(optional)</em></span>
                            <select v-model="form.promo_code_id">
                                <option :value="null">— No coupon —</option>
                                <option v-for="p in promos" :key="p.id" :value="p.id">
                                    {{ p.code }} — {{ p.type === 'percent' ? p.value + '%' : '৳' + p.value }} off
                                </option>
                            </select>
                        </label>
                        <p v-if="form.promo_code_id" class="coupon-note">
                            🎟 On save this coupon is locked to the offer products only, and set to one use per customer (shopper must be signed in). One coupon per order — it can't be stacked with another.
                        </p>

                        <div class="grid-2">
                            <label class="field" :class="{ invalid: fieldErrors.starts_at }">
                                <span>Starts <em>(optional)</em></span>
                                <input v-model="form.starts_at" type="datetime-local" />
                            </label>
                            <label class="field" :class="{ invalid: fieldErrors.ends_at }">
                                <span>Ends <em>(optional)</em></span>
                                <input v-model="form.ends_at" type="datetime-local" />
                                <em v-if="fieldErrors.ends_at" class="field-msg">{{ fieldErrors.ends_at[0] }}</em>
                            </label>
                        </div>
                        <label class="switch-row">
                            <input v-model="form.is_active" type="checkbox" />
                            <span><strong>Active</strong> — only live within the date window (leave dates empty for always-on)</span>
                        </label>

                        <div class="picker-head">
                            <span>Offer products <b class="mem-n">{{ form.product_ids.length }}</b></span>
                            <div class="mem-search">
                                <AppIcon name="search" :size="15" />
                                <input v-model="search" type="search" placeholder="Search products…" />
                            </div>
                        </div>
                        <p v-if="!products.length" class="empty">No products yet.</p>
                        <div v-else class="prod-list">
                            <label v-for="p in filteredProducts" :key="p.id" class="prod-row" :class="{ on: form.product_ids.includes(p.id) }">
                                <input type="checkbox" :checked="form.product_ids.includes(p.id)" @change="toggleProduct(p.id)" />
                                <img :src="asset(p.image)" :alt="p.name" class="prod-thumb" />
                                <span class="prod-info"><strong>{{ p.name }}</strong><em>{{ p.category }}</em></span>
                                <span class="prod-price">৳{{ p.price }}</span>
                            </label>
                        </div>
                    </div>
                    <footer class="modal-foot">
                        <button class="btn btn-ghost" :disabled="busy" @click="closeForm">Cancel</button>
                        <button class="btn btn-primary" :disabled="busy" @click="save">{{ busy ? 'Saving…' : editingId ? 'Save changes' : 'Create offer' }}</button>
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
                        <h3>Delete “{{ confirming.title }}”?</h3>
                        <p>The offer is removed. If it was live, the storefront section disappears immediately.</p>
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
.sub { color: var(--muted); font-size: 0.82rem; margin-top: 0.15rem; }
.badge-chip { display: inline-block; font-size: 0.74rem; font-weight: 700; padding: 0.15rem 0.6rem; border-radius: 999px; background: var(--cream-2, #efe7d6); color: var(--green-800, #163024); }
.coupon-chip { display: inline-block; font-size: 0.74rem; font-weight: 700; letter-spacing: 0.03em; padding: 0.15rem 0.6rem; border-radius: 6px; border: 1px dashed var(--gold, #c8a24a); color: #8a6d1f; background: #fdf7e6; }
.coupon-note { font-size: 0.8rem; line-height: 1.5; color: var(--muted); background: #fdf7e6; border: 1px dashed var(--gold, #c8a24a); border-radius: 10px; padding: 0.6rem 0.8rem; margin: 0.1rem 0 0.6rem; }
select { width: 100%; padding: 0.55rem 0.7rem; border: 1px solid var(--line); border-radius: 10px; font: inherit; background: #fff; }
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 0.9rem; }
.field em { color: var(--muted); font-style: normal; }
.switch-row { display: flex; align-items: center; gap: 0.6rem; font-size: 0.85rem; color: var(--muted); margin: 0.2rem 0 0.4rem; cursor: pointer; }
.switch-row input { width: 16px; height: 16px; }
.picker-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin: 1rem 0 0.6rem; font-weight: 600; }
.mem-n { font-size: 0.72rem; background: var(--gold, #c8a24a); color: #fff; padding: 0.05rem 0.5rem; border-radius: 999px; margin-left: 0.4rem; }
.mem-search { display: flex; align-items: center; gap: 0.5rem; padding: 0.45rem 0.75rem; border: 1px solid var(--line); border-radius: 10px; color: var(--muted); min-width: 220px; }
.mem-search input { border: none; background: none; outline: none; width: 100%; font: inherit; }
.prod-list { display: grid; gap: 0.4rem; max-height: 320px; overflow-y: auto; }
.prod-row { display: flex; align-items: center; gap: 0.7rem; padding: 0.5rem 0.7rem; border: 1px solid var(--line); border-radius: 10px; cursor: pointer; transition: all 0.15s var(--ease); }
.prod-row.on { border-color: var(--green-600, #2c6b45); background: #f4f8f4; }
.prod-thumb { width: 38px; height: 38px; flex: none; border-radius: 8px; object-fit: cover; background: var(--cream-2, #efe7d6); }
.prod-info { display: flex; flex-direction: column; flex: 1; line-height: 1.25; }
.prod-info em { color: var(--muted); font-style: normal; font-size: 0.78rem; }
.prod-price { font-size: 0.82rem; font-weight: 700; color: var(--green-800, #163024); }
</style>
