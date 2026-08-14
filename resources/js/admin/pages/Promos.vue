<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import AppIcon from '../components/AppIcon.vue'
import StatCard from '../components/StatCard.vue'
import Skeleton from '../components/Skeleton.vue'
import { fetchPromoBanners, createPromoBanner, updatePromoBanner, deletePromoBanner } from '../data'
import { toast } from '../composables/useToast'

const banners = ref([])
const loading = ref(true)

async function load() {
    loading.value = true
    try { banners.value = await fetchPromoBanners() } finally { loading.value = false }
}
onMounted(load)

const published = computed(() => banners.value.filter((b) => b.is_published).length)

const blank = () => ({ image: '', badge: '', eyebrow: '', title: '', text: '', target: '', cta: '', is_published: true, sort_order: 0 })
const showForm = ref(false)
const editingId = ref(null)
const busy = ref(false)
const fieldErrors = ref({})
const form = reactive(blank())

function openCreate() { editingId.value = null; fieldErrors.value = {}; Object.assign(form, blank(), { sort_order: banners.value.length }); showForm.value = true }
function openEdit(b) {
    editingId.value = b.id; fieldErrors.value = {}
    Object.assign(form, { image: b.image, badge: b.badge || '', eyebrow: b.eyebrow || '', title: b.title, text: b.text || '', target: b.target || '', cta: b.cta || '', is_published: !!b.is_published, sort_order: b.sort_order || 0 })
    showForm.value = true
}
function closeForm() { if (!busy.value) showForm.value = false }

async function save() {
    busy.value = true; fieldErrors.value = {}
    try {
        if (editingId.value) { await updatePromoBanner(editingId.value, { ...form }); toast.success('Banner updated.') }
        else { await createPromoBanner({ ...form }); toast.success('Banner added.') }
        showForm.value = false; await load()
    } catch (e) {
        fieldErrors.value = e.data?.errors || {}
        if (e.status === 422) toast.error('Please fix the highlighted fields.')
    } finally { busy.value = false }
}
async function togglePublish(b) {
    try { await updatePromoBanner(b.id, { ...b, is_published: !b.is_published }); b.is_published = !b.is_published } catch (e) { /* toasts */ }
}
const confirming = ref(null)
async function remove() {
    if (!confirming.value) return
    busy.value = true
    try { await deletePromoBanner(confirming.value.id); toast.success('Banner deleted.'); confirming.value = null; await load() }
    finally { busy.value = false }
}
</script>

<template>
    <div class="page">
        <div class="page-head">
            <div>
                <p class="eyebrow">Storefront</p>
                <h2>Promo Banners</h2>
                <p>The two large promotional cards between the collection and gift box.</p>
            </div>
            <div class="head-actions">
                <button class="btn btn-primary" @click="openCreate"><AppIcon name="plus" :size="17" /> Add Banner</button>
            </div>
        </div>

        <div class="stat-grid">
            <StatCard label="Total Banners" :value="String(banners.length)" icon="dashboard" foot="live from database" />
            <StatCard label="Published" :value="String(published)" icon="check" tone="info" />
        </div>

        <section class="card">
            <div v-if="loading" style="padding: 1.4rem"><Skeleton :rows="3" /></div>
            <div v-else class="table-wrap">
                <table>
                    <thead><tr><th style="width: 90px">Image</th><th>Title</th><th>Badge</th><th>CTA</th><th>Live</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="b in banners" :key="b.id">
                            <td><img :src="b.image" alt="" class="thumb" /></td>
                            <td><strong>{{ (b.title || '').replace('\n', ' — ') }}</strong></td>
                            <td><span style="color: var(--muted)">{{ b.badge }}</span></td>
                            <td>{{ b.cta }}</td>
                            <td><button class="switch sm" :class="{ on: b.is_published }" @click="togglePublish(b)" /></td>
                            <td><div class="row-actions">
                                <button title="Edit" @click="openEdit(b)"><AppIcon name="edit" :size="16" /></button>
                                <button title="Delete" @click="confirming = b"><AppIcon name="trash" :size="16" /></button>
                            </div></td>
                        </tr>
                    </tbody>
                </table>
                <p v-if="!banners.length" class="empty">No banners yet.</p>
            </div>
        </section>

        <Transition name="modal">
            <div v-if="showForm" class="modal-wrap" @click.self="closeForm">
                <div class="modal">
                    <header class="modal-head">
                        <h3>{{ editingId ? 'Edit banner' : 'Add banner' }}</h3>
                        <button class="modal-x" aria-label="Close" @click="closeForm"><svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg></button>
                    </header>
                    <div class="modal-body">
                        <label class="field" :class="{ invalid: fieldErrors.image }"><span>Image path</span><input v-model="form.image" type="text" placeholder="/images/garden.jpg" /><em v-if="fieldErrors.image" class="field-msg">{{ fieldErrors.image[0] }}</em></label>
                        <div class="form-grid">
                            <label class="field"><span>Badge</span><input v-model="form.badge" type="text" placeholder="🌿 SINGLE ORIGIN" /></label>
                            <label class="field"><span>Eyebrow</span><input v-model="form.eyebrow" type="text" /></label>
                        </div>
                        <label class="field" :class="{ invalid: fieldErrors.title }"><span>Title <em class="fmt-hint">Enter for line break</em></span><textarea v-model="form.title" rows="2" /><em v-if="fieldErrors.title" class="field-msg">{{ fieldErrors.title[0] }}</em></label>
                        <label class="field"><span>Text</span><textarea v-model="form.text" rows="2" /></label>
                        <div class="form-grid">
                            <label class="field"><span>Link (product path)</span><input v-model="form.target" type="text" placeholder="/product/highland-green" /></label>
                            <label class="field"><span>CTA label</span><input v-model="form.cta" type="text" /></label>
                        </div>
                        <label class="field"><span>Sort order</span><input v-model.number="form.sort_order" type="number" min="0" /></label>
                        <div class="modal-toggles">
                            <label class="mini-check"><input v-model="form.is_published" type="checkbox" /> <span>Show on storefront</span></label>
                        </div>
                    </div>
                    <footer class="modal-foot">
                        <button class="btn btn-ghost" :disabled="busy" @click="closeForm">Cancel</button>
                        <button class="btn btn-primary" :disabled="busy" @click="save">{{ busy ? 'Saving…' : editingId ? 'Save changes' : 'Add banner' }}</button>
                    </footer>
                </div>
            </div>
        </Transition>

        <Transition name="modal">
            <div v-if="confirming" class="modal-wrap" @click.self="confirming = null">
                <div class="modal modal-sm">
                    <div class="modal-body confirm-body">
                        <span class="confirm-ico"><AppIcon name="trash" :size="22" /></span>
                        <h3>Delete this banner?</h3>
                        <p>This cannot be undone.</p>
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
.thumb { width: 72px; height: 44px; object-fit: cover; border-radius: 6px; }
.fmt-hint { font-weight: 400; font-style: normal; color: var(--muted); font-size: 0.78rem; margin-left: 0.3rem; }
</style>
