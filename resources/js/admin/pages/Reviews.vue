<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import AppIcon from '../components/AppIcon.vue'
import StatCard from '../components/StatCard.vue'
import Skeleton from '../components/Skeleton.vue'
import { fetchReviews, createReview, updateReview, deleteReview, initials } from '../data'
import { toast } from '../composables/useToast'

const reviews = ref([])
const loading = ref(true)
const query = ref('')
const langFilter = ref('') // '' | bn | en

async function load() {
    loading.value = true
    try {
        reviews.value = await fetchReviews()
    } finally {
        loading.value = false
    }
}
onMounted(load)

const rows = computed(() => {
    const q = query.value.trim().toLowerCase()
    return reviews.value.filter((r) => {
        const matchLang = !langFilter.value || r.lang === langFilter.value
        const matchQ = !q || r.name.toLowerCase().includes(q) || r.text.toLowerCase().includes(q)
        return matchLang && matchQ
    })
})

const total = computed(() => reviews.value.length)
const published = computed(() => reviews.value.filter((r) => r.is_published).length)
const bn = computed(() => reviews.value.filter((r) => r.lang === 'bn').length)

/* ---- modal ---- */
const blank = () => ({
    name: '', city: '', text: '', lang: 'en', product: '',
    rating: 5, verified: true, is_published: true, sort_order: 0,
})
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
function openEdit(r) {
    editingId.value = r.id
    fieldErrors.value = {}
    Object.assign(form, {
        name: r.name, city: r.city || '', text: r.text, lang: r.lang,
        product: r.product || '', rating: r.rating, verified: !!r.verified,
        is_published: !!r.is_published, sort_order: r.sort_order || 0,
    })
    showForm.value = true
}
function closeForm() {
    if (!busy.value) showForm.value = false
}

async function save() {
    busy.value = true
    fieldErrors.value = {}
    try {
        if (editingId.value) {
            await updateReview(editingId.value, { ...form })
            toast.success('Review updated.')
        } else {
            await createReview({ ...form })
            toast.success('Review added.')
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

/* ---- publish toggle (inline) ---- */
async function togglePublish(r) {
    try {
        await updateReview(r.id, { ...r, is_published: !r.is_published })
        r.is_published = !r.is_published
        toast.success(r.is_published ? 'Published.' : 'Hidden from storefront.')
    } catch (e) {
        // api.js toasts errors
    }
}

/* ---- delete ---- */
const confirming = ref(null)
async function remove() {
    const r = confirming.value
    if (!r) return
    busy.value = true
    try {
        await deleteReview(r.id)
        toast.success('Review deleted.')
        confirming.value = null
        await load()
    } finally {
        busy.value = false
    }
}
</script>

<template>
    <div class="page">
        <div class="page-head">
            <div>
                <p class="eyebrow">Storefront</p>
                <h2>Reviews</h2>
                <p>Customer testimonials shown on the storefront — Bangla &amp; English.</p>
            </div>
            <div class="head-actions">
                <button class="btn btn-primary" @click="openCreate"><AppIcon name="plus" :size="17" /> Add Review</button>
            </div>
        </div>

        <div class="stat-grid">
            <StatCard label="Total Reviews" :value="String(total)" icon="star" foot="live from database" />
            <StatCard label="Published" :value="String(published)" icon="check" tone="info" />
            <StatCard label="Bangla" :value="String(bn)" icon="users" tone="gold" />
            <StatCard label="English" :value="String(total - bn)" icon="users" tone="rose" />
        </div>

        <section class="card">
            <div class="toolbar">
                <button class="chip" :class="{ active: langFilter === '' }" @click="langFilter = ''">All</button>
                <button class="chip" :class="{ active: langFilter === 'bn' }" @click="langFilter = 'bn'">বাংলা</button>
                <button class="chip" :class="{ active: langFilter === 'en' }" @click="langFilter = 'en'">English</button>
                <div class="search">
                    <AppIcon name="search" :size="16" />
                    <input v-model="query" type="search" placeholder="Name or text…" />
                </div>
            </div>

            <div v-if="loading" style="padding: 1.4rem"><Skeleton :rows="6" /></div>

            <div v-else class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Review</th>
                            <th>Blend</th>
                            <th>Lang</th>
                            <th>Live</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="r in rows" :key="r.id">
                            <td>
                                <div class="cell-user">
                                    <span class="mini-avatar">{{ initials(r.name) }}</span>
                                    <span>
                                        <strong>{{ r.name }}</strong>
                                        <small>{{ r.city }}</small>
                                    </span>
                                </div>
                            </td>
                            <td><span class="rev-clip">{{ r.text }}</span></td>
                            <td>{{ r.product || '—' }}</td>
                            <td><span class="pill" :class="r.lang === 'bn' ? 'pending' : 'shipped'">{{ r.lang === 'bn' ? 'বাংলা' : 'EN' }}</span></td>
                            <td>
                                <button class="switch sm" :class="{ on: r.is_published }" @click="togglePublish(r)" />
                            </td>
                            <td>
                                <div class="row-actions">
                                    <button title="Edit" @click="openEdit(r)"><AppIcon name="edit" :size="16" /></button>
                                    <button title="Delete" @click="confirming = r"><AppIcon name="trash" :size="16" /></button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p v-if="!rows.length" class="empty">No reviews found.</p>
            </div>
        </section>

        <!-- create / edit -->
        <Transition name="modal">
            <div v-if="showForm" class="modal-wrap" @click.self="closeForm">
                <div class="modal">
                    <header class="modal-head">
                        <h3>{{ editingId ? 'Edit review' : 'Add review' }}</h3>
                        <button class="modal-x" aria-label="Close" @click="closeForm">
                            <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                        </button>
                    </header>
                    <div class="modal-body">
                        <div class="form-grid">
                            <label class="field" :class="{ invalid: fieldErrors.name }">
                                <span>Customer name</span>
                                <input v-model="form.name" type="text" />
                                <em v-if="fieldErrors.name" class="field-msg">{{ fieldErrors.name[0] }}</em>
                            </label>
                            <label class="field">
                                <span>City</span>
                                <input v-model="form.city" type="text" placeholder="ধানমন্ডি, ঢাকা" />
                            </label>
                            <label class="field">
                                <span>Blend</span>
                                <input v-model="form.product" type="text" placeholder="Signature Gold" />
                            </label>
                            <label class="field">
                                <span>Language</span>
                                <select v-model="form.lang">
                                    <option value="bn">বাংলা</option>
                                    <option value="en">English</option>
                                </select>
                            </label>
                            <label class="field">
                                <span>Rating (1–5)</span>
                                <input v-model.number="form.rating" type="number" min="1" max="5" />
                            </label>
                            <label class="field">
                                <span>Sort order</span>
                                <input v-model.number="form.sort_order" type="number" min="0" />
                            </label>
                        </div>
                        <label class="field" :class="{ invalid: fieldErrors.text }">
                            <span>Review text</span>
                            <textarea v-model="form.text" rows="4" />
                            <em v-if="fieldErrors.text" class="field-msg">{{ fieldErrors.text[0] }}</em>
                        </label>
                        <div class="modal-toggles" style="grid-auto-flow: column; justify-content: start; gap: 1.6rem">
                            <label class="mini-check"><input v-model="form.verified" type="checkbox" /> <span>Verified buyer</span></label>
                            <label class="mini-check"><input v-model="form.is_published" type="checkbox" /> <span>Show on storefront</span></label>
                        </div>
                    </div>
                    <footer class="modal-foot">
                        <button class="btn btn-ghost" :disabled="busy" @click="closeForm">Cancel</button>
                        <button class="btn btn-primary" :disabled="busy" @click="save">
                            {{ busy ? 'Saving…' : editingId ? 'Save changes' : 'Add review' }}
                        </button>
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
                        <h3>Delete this review?</h3>
                        <p>From {{ confirming.name }}. This cannot be undone.</p>
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
