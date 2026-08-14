<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import AppIcon from '../components/AppIcon.vue'
import StatCard from '../components/StatCard.vue'
import Skeleton from '../components/Skeleton.vue'
import { fetchProducts, createProduct, updateProduct, deleteProduct, statusClass, money, asset } from '../data'
import { uploadFile } from '../api'
import { toast } from '../composables/useToast'

const query = ref('')
const statusFilter = ref('')
const products = ref([])
const loading = ref(true)

async function load() {
    loading.value = true
    try {
        products.value = await fetchProducts()
    } finally {
        loading.value = false
    }
}
onMounted(load)

const STATUSES_F = ['Active', 'Low stock', 'Out of stock']

const rows = computed(() => {
    const q = query.value.trim().toLowerCase()
    return products.value.filter((p) => {
        const matchQ = !q || p.name.toLowerCase().includes(q) || p.sku.toLowerCase().includes(q)
        const matchS = !statusFilter.value || p.status === statusFilter.value
        return matchQ && matchS
    })
})

const unitsInStock = computed(() => products.value.reduce((s, p) => s + p.stock, 0))
const lowStock = computed(() => products.value.filter((p) => p.stock > 0 && p.stock < 20).length)
const outOfStock = computed(() => products.value.filter((p) => p.stock === 0).length)

function exportCsv() {
    const head = ['Name', 'SKU', 'Category', 'Price', 'Old price', 'Stock', 'Status', 'Rating', 'Featured', 'Gift box']
    const esc = (v) => `"${String(v ?? '').replace(/"/g, '""')}"`
    const lines = [head.join(',')]
    for (const p of rows.value) {
        lines.push([p.name, p.sku, p.category, p.price, p.old_price, p.stock, p.status, p.rating, p.is_featured ? 'yes' : '', p.in_gift_box ? 'yes' : ''].map(esc).join(','))
    }
    const blob = new Blob(['﻿' + lines.join('\n')], { type: 'text/csv;charset=utf-8;' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `cha-kunjo-products-${new Date().toISOString().slice(0, 10)}.csv`
    a.click()
    URL.revokeObjectURL(url)
    toast.success(`Exported ${rows.value.length} products.`)
}

/* ---------- create / edit modal ---------- */
const STATUSES = ['Active', 'Low stock', 'Out of stock']
const NOTE_KEYS = ['Strength', 'Aroma', 'Sweetness', 'Astringency']
const FACT_KEYS = ['Leaf grade', 'Elevation', 'Harvest', 'Origin']

const activeTab = ref('basic')

const blank = () => ({
    name: '', sku: '', category: 'Green Tea', tag: 'Popular', weight: '250g',
    price: 500, old_price: 600, stock: 100, status: 'Active', image: 'images/green.jpg', blurb: '',
    rating: 4.8, is_featured: false, in_gift_box: false,
    tasting: { Strength: 4, Aroma: 4, Sweetness: 3, Astringency: 2 },
    facts: { 'Leaf grade': 'Golden Tips, whole leaf', Elevation: '900m+', Harvest: 'First Flush 2026', Origin: 'Sreemangal, Sylhet' },
    sizes: [
        { label: '100g', factor: 0.45, note: 'Try it' },
        { label: '250g', factor: 1, note: 'Most picked' },
        { label: '500g', factor: 1.85, note: 'Best value' },
    ],
    specs: [
        { k: 'Leaf grade', v: 'Golden Tips, whole leaf' },
        { k: 'Origin', v: 'Sreemangal, Moulvibazar, Sylhet' },
        { k: 'Harvest', v: 'First Flush, March 2026' },
        { k: 'Caffeine', v: 'Medium — roughly 40mg per cup' },
        { k: 'Shelf life', v: '18 months from pack date, sealed' },
    ],
    brewing: [
        { step: 'Measure', value: '2.5g', hint: 'one heaped teaspoon per cup' },
        { step: 'Water', value: '85°C', hint: 'just off the boil' },
        { step: 'Steep', value: '3 min', hint: 'longer for a bolder cup' },
        { step: 'Infusions', value: '3×', hint: 'the leaf keeps giving' },
    ],
    story: '',
    brew_note: '',
    ship_note: '',
    galleryText: '',
    seo: {
        meta_title: '',
        meta_description: '',
        meta_keywords: '',
        og_image: '',
    },
})

const showForm = ref(false)
const editingId = ref(null)
const busy = ref(false)
const uploading = ref(false)
const fieldErrors = ref({})
const form = reactive(blank())

function openCreate() {
    editingId.value = null
    fieldErrors.value = {}
    activeTab.value = 'basic'
    Object.assign(form, blank(), {
        sku: `CK-TEA-${Math.floor(100 + Math.random() * 900)}`,
    })
    showForm.value = true
}

function openEdit(p) {
    editingId.value = p.id
    fieldErrors.value = {}
    activeTab.value = 'basic'
    const d = p.details || {}
    Object.assign(form, {
        name: p.name, sku: p.sku, category: p.category, tag: p.tag || 'Popular',
        weight: p.weight || '250g', price: p.price, old_price: p.old_price || 0,
        stock: p.stock, status: p.status, image: p.image || 'images/green.jpg', blurb: p.blurb || '',
        rating: p.rating ?? 4.8, is_featured: !!p.is_featured, in_gift_box: !!p.in_gift_box,
        tasting: { ...blank().tasting, ...(d.tasting || {}) },
        facts: { ...blank().facts, ...(d.facts || {}) },
        galleryText: (d.gallery || []).join('\n'),
        sizes: (d.sizes && d.sizes.length) ? d.sizes.map((s) => ({ ...s })) : blank().sizes,
        specs: (d.specs && d.specs.length) ? d.specs.map((s) => ({ ...s })) : blank().specs,
        brewing: (d.brewing && d.brewing.length) ? d.brewing.map((b) => ({ ...b })) : blank().brewing,
        story: d.story || '',
        brew_note: d.brew_note || '',
        ship_note: d.ship_note || '',
        seo: {
            meta_title: d.seo?.meta_title || '',
            meta_description: d.seo?.meta_description || '',
            meta_keywords: d.seo?.meta_keywords || '',
            og_image: d.seo?.og_image || '',
        },
    })
    showForm.value = true
}

/* ---- variable-row editors (Details tab) ---- */
function addRow(list, row) { form[list].push({ ...row }) }
function removeRow(list, i) { form[list].splice(i, 1) }

function closeForm() {
    if (!busy.value) showForm.value = false
}

async function handleMainImageUpload(e) {
    const file = e.target.files?.[0]
    if (!file) return
    uploading.value = true
    try {
        const url = await uploadFile(file)
        form.image = url
        toast.success('Main product image uploaded successfully!')
    } catch (err) {
        toast.error('Failed to upload image. Ensure file is an image under 10MB.')
    } finally {
        uploading.value = false
    }
}

async function handleGalleryImageUpload(e) {
    const file = e.target.files?.[0]
    if (!file) return
    uploading.value = true
    try {
        const url = await uploadFile(file)
        form.galleryText = form.galleryText ? form.galleryText + '\n' + url : url
        toast.success('Gallery image uploaded!')
    } catch (err) {
        toast.error('Failed to upload gallery image.')
    } finally {
        uploading.value = false
    }
}

async function handleOgImageUpload(e) {
    const file = e.target.files?.[0]
    if (!file) return
    uploading.value = true
    try {
        const url = await uploadFile(file)
        form.seo.og_image = url
        toast.success('OG Share Image uploaded!')
    } catch (err) {
        toast.error('Failed to upload OG image.')
    } finally {
        uploading.value = false
    }
}

function payload() {
    const { galleryText, ...rest } = form
    return {
        ...rest,
        gallery: galleryText.split('\n').map((s) => s.trim()).filter(Boolean),
        sizes: form.sizes.filter((s) => s.label).map((s) => ({ label: s.label, factor: Number(s.factor) || 1, note: s.note || '' })),
        specs: form.specs.filter((s) => s.k && s.v),
        brewing: form.brewing.filter((b) => b.step),
    }
}

async function save() {
    busy.value = true
    fieldErrors.value = {}
    try {
        if (editingId.value) {
            await updateProduct(editingId.value, payload())
            toast.success(`${form.name} updated.`)
        } else {
            await createProduct(payload())
            toast.success(`${form.name} added.`)
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

/* ---------- delete ---------- */
const confirming = ref(null)
async function remove() {
    const p = confirming.value
    if (!p) return
    busy.value = true
    try {
        await deleteProduct(p.id)
        toast.success(`${p.name} deleted.`)
        confirming.value = null
        await load()
    } catch (e) {
    } finally {
        busy.value = false
    }
}
</script>

<template>
    <div class="page">
        <div class="page-head">
            <div>
                <p class="eyebrow">Catalogue</p>
                <h2>Products</h2>
                <p>Blends, pack sizes and live stock across the estate.</p>
            </div>
            <div class="head-actions">
                <button class="btn btn-ghost" :disabled="!rows.length" @click="exportCsv"><AppIcon name="download" :size="17" /> Export</button>
                <button class="btn btn-primary" @click="openCreate"><AppIcon name="plus" :size="17" /> Add Product</button>
            </div>
        </div>

        <div class="stat-grid">
            <StatCard label="Active Blends" :value="String(products.length)" icon="products" foot="live from database" />
            <StatCard label="Units in Stock" :value="unitsInStock.toLocaleString()" icon="truck" tone="info" />
            <StatCard label="Low Stock" :value="String(lowStock)" trend="down" icon="warn" tone="gold" foot="below 20 units" />
            <StatCard label="Out of Stock" :value="String(outOfStock)" icon="warn" tone="rose" foot="needs reorder" />
        </div>

        <section class="card">
            <div class="toolbar">
                <div class="toolbar-chips">
                    <button class="chip" :class="{ active: statusFilter === '' }" @click="statusFilter = ''">All</button>
                    <button v-for="s in STATUSES_F" :key="s" class="chip" :class="{ active: statusFilter === s }" @click="statusFilter = s">{{ s }}</button>
                </div>
                <div class="search">
                    <AppIcon name="search" :size="16" />
                    <input v-model="query" type="search" placeholder="Name or SKU…" />
                </div>
            </div>

            <div v-if="loading" style="padding: 1.4rem"><Skeleton :rows="6" /></div>

            <div v-else class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Placement</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="p in rows" :key="p.sku">
                            <td>
                                <div class="cell-user">
                                    <img class="thumb" :src="asset(p.image)" :alt="p.name" />
                                    <span><strong>{{ p.name }}</strong></span>
                                </div>
                            </td>
                            <td>{{ p.sku }}</td>
                            <td>{{ p.category }}</td>
                            <td>
                                <strong>{{ money(p.price) }}</strong>
                                <em v-if="p.old_price" class="prod-old">{{ money(p.old_price) }}</em>
                            </td>
                            <td><span :class="{ 'stock-low': p.stock > 0 && p.stock < 20, 'stock-out': p.stock === 0 }">{{ p.stock }}</span></td>
                            <td>
                                <div class="prod-tags">
                                    <span v-if="p.is_featured" class="prod-tag gold" title="Best Seller">⭐</span>
                                    <span v-if="p.in_gift_box" class="prod-tag" title="Gift Box">🎁</span>
                                    <span v-if="!p.is_featured && !p.in_gift_box" style="color: var(--muted)">—</span>
                                </div>
                            </td>
                            <td><span class="pill" :class="statusClass(p.status)">{{ p.status }}</span></td>
                            <td>
                                <div class="row-actions">
                                    <button title="Edit" @click="openEdit(p)"><AppIcon name="edit" :size="16" /></button>
                                    <button title="Delete" @click="confirming = p"><AppIcon name="trash" :size="16" /></button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p v-if="!rows.length" class="empty">Nothing found.</p>
            </div>
        </section>

        <!-- ===== product modal (compact / premium) ===== -->
        <Transition name="modal">
            <div v-if="showForm" class="modal-wrap" @click.self="closeForm">
                <div class="modal pm">
                    <header class="modal-head">
                        <div class="pm-head-txt">
                            <p class="pm-eyebrow">{{ editingId ? 'Editing product' : 'New product' }}</p>
                            <h3>{{ editingId ? form.name || 'Edit product' : 'Add a tea product' }}</h3>
                        </div>
                        <button class="modal-x" aria-label="Close" @click="closeForm">
                            <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                        </button>
                    </header>

                    <!-- tabs -->
                    <div class="pm-tabs">
                        <button :class="{ active: activeTab === 'basic' }" @click="activeTab = 'basic'">Basics</button>
                        <button :class="{ active: activeTab === 'images' }" @click="activeTab = 'images'">Images</button>
                        <button :class="{ active: activeTab === 'tasting' }" @click="activeTab = 'tasting'">Tasting</button>
                        <button :class="{ active: activeTab === 'facts' }" @click="activeTab = 'facts'">Garden facts</button>
                        <button :class="{ active: activeTab === 'details' }" @click="activeTab = 'details'">Detail page</button>
                        <button :class="{ active: activeTab === 'seo' }" @click="activeTab = 'seo'">🔍 SEO & Meta</button>
                    </div>

                    <div class="modal-body pm-body">
                        <!-- TAB 1: BASICS -->
                        <div v-show="activeTab === 'basic'" class="pm-stack">
                            <label class="field" :class="{ invalid: fieldErrors.name }">
                                <span>Tea name *</span>
                                <input v-model="form.name" type="text" placeholder="e.g. Kunjo Royal Black" />
                                <em v-if="fieldErrors.name" class="field-msg">{{ fieldErrors.name[0] }}</em>
                            </label>

                            <div class="pm-grid">
                                <label class="field" :class="{ invalid: fieldErrors.sku }">
                                    <span>SKU *</span>
                                    <input v-model="form.sku" type="text" placeholder="CK-XXX-000" />
                                    <em v-if="fieldErrors.sku" class="field-msg">{{ fieldErrors.sku[0] }}</em>
                                </label>
                                <label class="field" :class="{ invalid: fieldErrors.category }">
                                    <span>Category *</span>
                                    <input v-model="form.category" type="text" placeholder="Green Tea…" />
                                    <em v-if="fieldErrors.category" class="field-msg">{{ fieldErrors.category[0] }}</em>
                                </label>
                            </div>

                            <div class="pm-grid pm-grid-3">
                                <label class="field" :class="{ invalid: fieldErrors.price }">
                                    <span>Price ৳ *</span>
                                    <input v-model.number="form.price" type="number" min="0" />
                                    <em v-if="fieldErrors.price" class="field-msg">{{ fieldErrors.price[0] }}</em>
                                </label>
                                <label class="field">
                                    <span>Old price ৳</span>
                                    <input v-model.number="form.old_price" type="number" min="0" />
                                </label>
                                <label class="field">
                                    <span>Stock *</span>
                                    <input v-model.number="form.stock" type="number" min="0" />
                                </label>
                            </div>

                            <div class="pm-grid">
                                <label class="field">
                                    <span>Pack weight</span>
                                    <input v-model="form.weight" type="text" placeholder="250g" />
                                </label>
                                <label class="field">
                                    <span>Tag badge</span>
                                    <input v-model="form.tag" type="text" placeholder="Popular / Premium" />
                                </label>
                            </div>

                            <div class="pm-grid">
                                <label class="field">
                                    <span>Status</span>
                                    <select v-model="form.status">
                                        <option v-for="s in STATUSES" :key="s">{{ s }}</option>
                                    </select>
                                </label>
                                <label class="field">
                                    <span>Rating (0–5)</span>
                                    <input v-model.number="form.rating" type="number" min="0" max="5" step="0.1" />
                                </label>
                            </div>

                            <label class="field">
                                <span>Short description</span>
                                <textarea v-model="form.blurb" rows="2" placeholder="Grassy, bright, delicately sweet. A clean morning ritual." />
                            </label>

                            <div class="pm-flags">
                                <label class="pm-flag" :class="{ on: form.is_featured }">
                                    <input v-model="form.is_featured" type="checkbox" />
                                    <span class="pm-flag-ico">⭐</span>
                                    <span class="pm-flag-txt"><strong>Best Seller</strong><em>Feature on homepage</em></span>
                                </label>
                                <label class="pm-flag" :class="{ on: form.in_gift_box }">
                                    <input v-model="form.in_gift_box" type="checkbox" />
                                    <span class="pm-flag-ico">🎁</span>
                                    <span class="pm-flag-txt"><strong>Gift Box</strong><em>Include in discovery set</em></span>
                                </label>
                            </div>
                        </div>

                        <!-- TAB 2: IMAGES -->
                        <div v-show="activeTab === 'images'" class="pm-stack">
                            <div class="pm-upload">
                                <img :src="asset(form.image)" alt="preview" class="pm-preview" />
                                <div class="pm-upload-body">
                                    <strong>Main product image</strong>
                                    <label class="btn btn-primary pm-file">
                                        <span>{{ uploading ? 'Uploading…' : 'Choose image file' }}</span>
                                        <input type="file" accept="image/*" @change="handleMainImageUpload" />
                                    </label>
                                    <input v-model="form.image" type="text" placeholder="or paste path: images/green.jpg" class="pm-path" />
                                </div>
                            </div>

                            <label class="field">
                                <div class="pm-gal-head">
                                    <span>Gallery images <em class="pm-opt">(one path per line)</em></span>
                                    <label class="pm-gal-add">
                                        {{ uploading ? 'Uploading…' : '+ Upload' }}
                                        <input type="file" accept="image/*" @change="handleGalleryImageUpload" />
                                    </label>
                                </div>
                                <textarea v-model="form.galleryText" rows="4" placeholder="/images/pouch-gold.jpeg&#10;/images/chai.jpg" />
                            </label>
                        </div>

                        <!-- TAB 3: TASTING -->
                        <div v-show="activeTab === 'tasting'" class="pm-stack">
                            <p class="pm-hint">Tasting note intensity (0–5), shown on the product card.</p>
                            <div class="pm-grid">
                                <label v-for="k in NOTE_KEYS" :key="k" class="field">
                                    <span>{{ k }}</span>
                                    <input v-model.number="form.tasting[k]" type="number" min="0" max="5" />
                                </label>
                            </div>
                        </div>

                        <!-- TAB 4: FACTS -->
                        <div v-show="activeTab === 'facts'" class="pm-stack">
                            <p class="pm-hint">Garden origin details shown on the product detail page.</p>
                            <div class="pm-grid">
                                <label v-for="k in FACT_KEYS" :key="k" class="field">
                                    <span>{{ k }}</span>
                                    <input v-model="form.facts[k]" type="text" />
                                </label>
                            </div>
                        </div>

                        <!-- TAB 5: DETAIL PAGE (sizes / specs / brewing / story / notes) -->
                        <div v-show="activeTab === 'details'" class="pm-stack">
                            <p class="pm-hint">Everything on the product detail page — leave blank to use the site default.</p>

                            <!-- pack sizes -->
                            <div class="pm-rows">
                                <div class="pm-rows-head"><span>Pack sizes</span><button type="button" class="pm-add" @click="addRow('sizes', { label: '', factor: 1, note: '' })">+ Add</button></div>
                                <div v-for="(s, i) in form.sizes" :key="'sz'+i" class="pm-row">
                                    <input v-model="s.label" type="text" placeholder="100g" />
                                    <input v-model.number="s.factor" type="number" step="0.01" min="0" placeholder="factor" title="price ×" />
                                    <input v-model="s.note" type="text" placeholder="Try it" />
                                    <button type="button" class="pm-del" title="Remove" @click="removeRow('sizes', i)">✕</button>
                                </div>
                                <p class="pm-sub-hint">Label · price factor (1 = base price) · small note</p>
                            </div>

                            <!-- specs -->
                            <div class="pm-rows">
                                <div class="pm-rows-head"><span>Specification sheet</span><button type="button" class="pm-add" @click="addRow('specs', { k: '', v: '' })">+ Add</button></div>
                                <div v-for="(s, i) in form.specs" :key="'sp'+i" class="pm-row pm-row-2">
                                    <input v-model="s.k" type="text" placeholder="Leaf grade" />
                                    <input v-model="s.v" type="text" placeholder="Golden Tips, whole leaf" />
                                    <button type="button" class="pm-del" title="Remove" @click="removeRow('specs', i)">✕</button>
                                </div>
                            </div>

                            <!-- brewing -->
                            <div class="pm-rows">
                                <div class="pm-rows-head"><span>Brewing steps</span><button type="button" class="pm-add" @click="addRow('brewing', { step: '', value: '', hint: '' })">+ Add</button></div>
                                <div v-for="(b, i) in form.brewing" :key="'br'+i" class="pm-row">
                                    <input v-model="b.step" type="text" placeholder="Steep" />
                                    <input v-model="b.value" type="text" placeholder="3 min" />
                                    <input v-model="b.hint" type="text" placeholder="longer for a bolder cup" />
                                    <button type="button" class="pm-del" title="Remove" @click="removeRow('brewing', i)">✕</button>
                                </div>
                            </div>

                            <label class="field">
                                <span>Story of this blend</span>
                                <textarea v-model="form.story" rows="3" placeholder="Grown on the upper slopes… (leave blank for the default)" />
                            </label>
                            <div class="pm-grid">
                                <label class="field">
                                    <span>“How to brew it” note</span>
                                    <textarea v-model="form.brew_note" rows="3" placeholder="Warm the pot first…" />
                                </label>
                                <label class="field">
                                    <span>“Delivery & freshness” note</span>
                                    <textarea v-model="form.ship_note" rows="3" placeholder="Orders before 4pm ship same day…" />
                                </label>
                            </div>
                        </div>

                        <!-- TAB 6: SEO & META -->
                        <div v-show="activeTab === 'seo'" class="pm-stack">
                            <p class="pm-hint">Search engine optimization (Meta Title, Meta Description, Keywords, &amp; Open Graph Social Share Image) for this product.</p>

                            <label class="field">
                                <span>SEO Meta Title</span>
                                <input v-model="form.seo.meta_title" type="text" :placeholder="form.name ? `${form.name} — Cha Kunjo` : 'Sreemangal Organic Tea'" />
                            </label>

                            <label class="field">
                                <span>SEO Meta Description</span>
                                <textarea v-model="form.seo.meta_description" rows="3" :placeholder="form.blurb || 'Hand-plucked single-origin tea from Sreemangal...'" />
                            </label>

                            <label class="field">
                                <span>Meta Keywords <em class="pm-opt">(comma separated)</em></span>
                                <input v-model="form.seo.meta_keywords" type="text" placeholder="green tea, bd tea, sreemangal, organic tea" />
                            </label>

                            <div class="field">
                                <span>Social Share Image (Open Graph Image)</span>
                                <div class="upload-card">
                                    <img v-if="form.seo.og_image" :src="asset(form.seo.og_image)" alt="OG preview" class="upload-thumb" />
                                    <div class="upload-meta">
                                        <strong>{{ form.seo.og_image ? 'OG Image Set' : 'No OG Image selected' }}</strong>
                                        <p>Recommended size: 1200×630px JPG, PNG or WebP</p>
                                        <label class="btn btn-ghost upload-btn">
                                            {{ uploading ? 'Uploading…' : '📁 Choose OG Share Image' }}
                                            <input type="file" accept="image/*" @change="handleOgImageUpload" />
                                        </label>
                                        <input v-model="form.seo.og_image" type="text" placeholder="or paste path: /images/og-share.jpg" class="pm-path" style="margin-top:0.4rem" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <footer class="modal-foot">
                        <button class="btn btn-ghost" :disabled="busy" @click="closeForm">Cancel</button>
                        <button class="btn btn-primary" :disabled="busy" @click="save">
                            {{ busy ? 'Saving…' : editingId ? 'Save changes' : 'Add product' }}
                        </button>
                    </footer>
                </div>
            </div>
        </Transition>

        <!-- ===== delete confirm ===== -->
        <Transition name="modal">
            <div v-if="confirming" class="modal-wrap" @click.self="confirming = null">
                <div class="modal modal-sm">
                    <div class="modal-body confirm-body">
                        <span class="confirm-ico"><AppIcon name="trash" :size="22" /></span>
                        <h3>Delete {{ confirming.name }}?</h3>
                        <p>This removes it from the store and the catalogue. This cannot be undone.</p>
                    </div>
                    <footer class="modal-foot">
                        <button class="btn btn-ghost" :disabled="busy" @click="confirming = null">Cancel</button>
                        <button class="btn btn-danger" :disabled="busy" @click="remove">
                            {{ busy ? 'Deleting…' : 'Delete' }}
                        </button>
                    </footer>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
/* ---- list toolbar / badges ---- */
.toolbar-chips { display: flex; gap: 0.4rem; flex-wrap: wrap; }
.prod-old { color: var(--muted); text-decoration: line-through; font-size: 0.78rem; font-style: normal; margin-left: 0.35rem; }
.prod-tags { display: flex; gap: 0.3rem; font-size: 1rem; }
.prod-tag { line-height: 1; }
.stock-low { color: #a8862f; font-weight: 600; }
.stock-out { color: var(--danger, #c0492f); font-weight: 600; }

/* ---- compact premium product modal (scoped: won't affect other modals) ---- */
.pm { width: min(720px, 94vw) !important; max-height: 90vh !important; }

.pm-head-txt { display: flex; flex-direction: column; gap: 0.15rem; }
.pm-eyebrow {
    font-size: 0.68rem; letter-spacing: 0.16em; text-transform: uppercase;
    color: var(--gold-soft, #e0c880); font-weight: 600; margin: 0;
}
.pm .modal-head h3 { font-size: 1.2rem; margin: 0; }

/* tabs — pill-underline, tighter */
.pm-tabs {
    display: flex; gap: 0.2rem; padding: 0 0.6rem;
    background: var(--white); border-bottom: 1px solid var(--line);
}
.pm-tabs button {
    padding: 0.75rem 0.9rem; font-size: 0.82rem; font-weight: 500; color: var(--muted);
    border: none; background: none; cursor: pointer; border-bottom: 2px solid transparent;
    transition: color 0.18s var(--ease), border-color 0.18s var(--ease);
}
.pm-tabs button:hover { color: var(--green-700, #1d4230); }
.pm-tabs button.active { color: var(--green-800, #163024); border-bottom-color: var(--gold, #c8a24a); font-weight: 600; }

.pm-body { padding: 1.3rem 1.4rem; }
.pm-stack { display: grid; gap: 0.85rem; }
.pm-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem; }
.pm-grid-3 { grid-template-columns: 1fr 1fr 1fr; }

/* compact fields (override the global .field sizing, scoped to this modal only) */
.pm .field { margin-bottom: 0; }
.pm .field > span {
    font-size: 0.72rem; letter-spacing: 0.03em; text-transform: none;
    font-weight: 600; color: #4a564e; margin-bottom: 0.3rem;
}
.pm .field input,
.pm .field select,
.pm .field textarea {
    padding: 0.55rem 0.7rem; font-size: 0.88rem; font-weight: 400;
    border-radius: 9px; background: #fdfcf9;
}
.pm .field input:focus,
.pm .field select:focus,
.pm .field textarea:focus { background: var(--white); }
.pm-hint { font-size: 0.82rem; color: var(--muted); margin: 0 0 0.2rem; }
.pm-opt { font-weight: 400; text-transform: none; letter-spacing: 0; color: var(--muted); }

/* placement flags as selectable cards */
.pm-flags { display: grid; grid-template-columns: 1fr 1fr; gap: 0.7rem; margin-top: 0.2rem; }
.pm-flag {
    display: flex; align-items: center; gap: 0.65rem; padding: 0.7rem 0.85rem; cursor: pointer;
    border: 1.5px solid var(--line); border-radius: 11px; background: #fdfcf9; transition: all 0.16s var(--ease);
}
.pm-flag input { position: absolute; opacity: 0; pointer-events: none; }
.pm-flag-ico { font-size: 1.2rem; }
.pm-flag-txt { display: flex; flex-direction: column; line-height: 1.25; }
.pm-flag-txt strong { font-size: 0.86rem; }
.pm-flag-txt em { font-size: 0.74rem; font-style: normal; color: var(--muted); }
.pm-flag.on { border-color: var(--green-600, #2c6b45); background: #f4f8f4; box-shadow: 0 6px 16px -12px rgba(44,107,69,0.5); }

/* image upload */
.pm-upload {
    display: flex; gap: 1rem; align-items: center; padding: 1rem;
    background: #fdfcf9; border: 1.5px dashed rgba(200,162,74,0.45); border-radius: 13px;
}
.pm-preview { width: 92px; height: 92px; border-radius: 11px; object-fit: cover; border: 1px solid var(--line); flex: none; }
.pm-upload-body { display: grid; gap: 0.5rem; flex: 1; }
.pm-upload-body > strong { font-size: 0.9rem; }
.pm-file { align-self: flex-start; position: relative; cursor: pointer; font-size: 0.85rem; padding: 0.55rem 1rem; overflow: hidden; }
.pm-file input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
.pm-path { width: 100%; padding: 0.5rem 0.7rem; border: 1px solid var(--line); border-radius: 8px; font-size: 0.84rem; background: var(--white); }
.pm-gal-head { display: flex; align-items: center; justify-content: space-between; }
.pm-gal-add {
    position: relative; overflow: hidden; cursor: pointer; font-size: 0.76rem; font-weight: 600;
    color: var(--green-700, #1d4230); background: #eef5ee; padding: 0.3rem 0.7rem; border-radius: 999px;
}
.pm-gal-add input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }

/* variable-row editors (Detail page tab) */
.pm-rows { background: #fdfcf9; border: 1px solid var(--line); border-radius: 12px; padding: 0.85rem; }
.pm-rows-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.6rem; }
.pm-rows-head span { font-size: 0.78rem; font-weight: 600; color: #4a564e; }
.pm-add {
    font-size: 0.74rem; font-weight: 600; color: var(--green-700, #1d4230); background: #eef5ee;
    border: none; padding: 0.25rem 0.65rem; border-radius: 999px; cursor: pointer;
}
.pm-add:hover { background: #e2efe2; }
.pm-row { display: grid; grid-template-columns: 1fr 1fr 1.4fr auto; gap: 0.5rem; margin-bottom: 0.45rem; align-items: center; }
.pm-row-2 { grid-template-columns: 1fr 1.6fr auto; }
.pm-row input { padding: 0.45rem 0.6rem; font-size: 0.83rem; border: 1px solid var(--line); border-radius: 8px; background: var(--white); width: 100%; }
.pm-row input:focus { outline: none; border-color: var(--green-500, #3f8a5c); }
.pm-del {
    width: 26px; height: 26px; flex: none; border: none; border-radius: 7px; cursor: pointer;
    background: #f7ecec; color: #b42318; font-size: 0.8rem; display: grid; place-items: center;
}
.pm-del:hover { background: #f2dede; }
.pm-sub-hint { font-size: 0.72rem; color: var(--muted); margin: 0.35rem 0 0; }

@media (max-width: 560px) {
    .pm-grid, .pm-grid-3, .pm-flags { grid-template-columns: 1fr; }
    .pm-upload { flex-direction: column; align-items: stretch; text-align: center; }
    .pm-preview { align-self: center; }
    .pm-row, .pm-row-2 { grid-template-columns: 1fr 1fr auto; }
}
</style>
