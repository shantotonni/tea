<script setup>
import { ref, reactive, onMounted } from 'vue'
import AppIcon from '../components/AppIcon.vue'
import Skeleton from '../components/Skeleton.vue'
import {
    fetchCreationTiles, createCreationTile, updateCreationTile, deleteCreationTile,
    fetchSettings, saveSettings, asset,
} from '../data'
import { uploadFile } from '../api'
import { toast } from '../composables/useToast'

const loading = ref(true)
const tiles = ref([])

const copy = reactive({ eyebrow: '', title: '', lead: '', stat1_value: '', stat1_label: '', stat2_value: '', stat2_label: '', cta_label: '' })
const copyBusy = ref(false)
const copyErrors = ref({})

async function load() {
    loading.value = true
    try {
        const [t, settings] = await Promise.all([fetchCreationTiles(), fetchSettings()])
        tiles.value = t
        Object.assign(copy, settings.creations || {})
    } finally {
        loading.value = false
    }
}
onMounted(load)

async function saveCopy() {
    copyBusy.value = true
    copyErrors.value = {}
    try {
        await saveSettings('creations', { ...copy })
        toast.success('Section copy saved.')
    } catch (e) {
        copyErrors.value = e.data?.errors || {}
        if (e.status === 422) toast.error('Please fix the highlighted fields.')
    } finally {
        copyBusy.value = false
    }
}

const blank = () => ({ image: '', label: '', meta: '', target: '', is_wide: false, is_published: true, sort_order: 0 })
const uploading = ref(false)
const showForm = ref(false)
const editingId = ref(null)
const busy = ref(false)
const fieldErrors = ref({})
const form = reactive(blank())
async function handleImageUpload(e) {
    const file = e.target.files?.[0]
    if (!file) return
    uploading.value = true
    try { form.image = await uploadFile(file); toast.success('Image uploaded.') }
    catch (err) { toast.error('Upload failed. Image must be under 10MB.') }
    finally { uploading.value = false; e.target.value = '' }
}

function openCreate() {
    editingId.value = null; fieldErrors.value = {}
    Object.assign(form, blank(), { sort_order: tiles.value.length })
    showForm.value = true
}
function openEdit(t) {
    editingId.value = t.id; fieldErrors.value = {}
    Object.assign(form, { image: t.image, label: t.label, meta: t.meta || '', target: t.target || '', is_wide: !!t.is_wide, is_published: !!t.is_published, sort_order: t.sort_order || 0 })
    showForm.value = true
}
function closeForm() { if (!busy.value) showForm.value = false }

async function save() {
    busy.value = true; fieldErrors.value = {}
    try {
        if (editingId.value) { await updateCreationTile(editingId.value, { ...form }); toast.success('Tile updated.') }
        else { await createCreationTile({ ...form }); toast.success('Tile added.') }
        showForm.value = false
        await load()
    } catch (e) {
        fieldErrors.value = e.data?.errors || {}
        if (e.status === 422) toast.error('Please fix the highlighted fields.')
    } finally {
        busy.value = false
    }
}
async function togglePublish(t) {
    try { await updateCreationTile(t.id, { ...t, is_published: !t.is_published }); t.is_published = !t.is_published } catch (e) { /* toasts */ }
}
const confirming = ref(null)
async function remove() {
    if (!confirming.value) return
    busy.value = true
    try { await deleteCreationTile(confirming.value.id); toast.success('Tile deleted.'); confirming.value = null; await load() }
    finally { busy.value = false }
}
</script>

<template>
    <div class="page">
        <div class="page-head">
            <div>
                <p class="eyebrow">Storefront</p>
                <h2>Creations Collage</h2>
                <p>The “Creations with purpose” image grid and its copy.</p>
            </div>
        </div>

        <div v-if="loading" class="card" style="padding: 1.4rem"><Skeleton :rows="6" /></div>

        <template v-else>
            <section class="card blk">
                <header class="blk-head">
                    <h3>Section copy</h3>
                    <button class="btn btn-primary" :disabled="copyBusy" @click="saveCopy">{{ copyBusy ? 'Saving…' : 'Save copy' }}</button>
                </header>
                <div class="blk-body">
                    <div class="form-grid">
                        <label class="field" :class="{ invalid: copyErrors.eyebrow }"><span>Eyebrow</span><input v-model="copy.eyebrow" type="text" /><em v-if="copyErrors.eyebrow" class="field-msg">{{ copyErrors.eyebrow[0] }}</em></label>
                        <label class="field"><span>CTA label</span><input v-model="copy.cta_label" type="text" /></label>
                    </div>
                    <label class="field" :class="{ invalid: copyErrors.title }"><span>Title <em class="fmt-hint">Enter for line break</em></span><textarea v-model="copy.title" rows="2" /><em v-if="copyErrors.title" class="field-msg">{{ copyErrors.title[0] }}</em></label>
                    <label class="field"><span>Lead</span><textarea v-model="copy.lead" rows="2" /></label>
                    <div class="form-grid">
                        <label class="field"><span>Stat 1 value</span><input v-model="copy.stat1_value" type="text" placeholder="07" /></label>
                        <label class="field"><span>Stat 1 label</span><input v-model="copy.stat1_label" type="text" placeholder="signature blends" /></label>
                        <label class="field"><span>Stat 2 value</span><input v-model="copy.stat2_value" type="text" placeholder="48h" /></label>
                        <label class="field"><span>Stat 2 label</span><input v-model="copy.stat2_label" type="text" placeholder="garden to pouch" /></label>
                    </div>
                </div>
            </section>

            <section class="card blk">
                <header class="blk-head">
                    <h3>Tiles <span class="count">{{ tiles.length }}</span></h3>
                    <button class="btn btn-primary" @click="openCreate"><AppIcon name="plus" :size="16" /> Add Tile</button>
                </header>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th style="width: 80px">Image</th><th>Label</th><th>Meta</th><th>Wide</th><th>Live</th><th></th></tr></thead>
                        <tbody>
                            <tr v-for="t in tiles" :key="t.id">
                                <td><img :src="asset(t.image)" alt="" class="thumb" /></td>
                                <td><strong>{{ t.label }}</strong></td>
                                <td><span style="color: var(--muted)">{{ t.meta }}</span></td>
                                <td><span v-if="t.is_wide" class="tag-cta">Wide</span></td>
                                <td><button class="switch sm" :class="{ on: t.is_published }" @click="togglePublish(t)" /></td>
                                <td><div class="row-actions">
                                    <button title="Edit" @click="openEdit(t)"><AppIcon name="edit" :size="16" /></button>
                                    <button title="Delete" @click="confirming = t"><AppIcon name="trash" :size="16" /></button>
                                </div></td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-if="!tiles.length" class="empty">No tiles yet.</p>
                </div>
            </section>
        </template>

        <Transition name="modal">
            <div v-if="showForm" class="modal-wrap" @click.self="closeForm">
                <div class="modal">
                    <header class="modal-head">
                        <h3>{{ editingId ? 'Edit tile' : 'Add tile' }}</h3>
                        <button class="modal-x" aria-label="Close" @click="closeForm"><svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg></button>
                    </header>
                    <div class="modal-body">
                        <div class="upload-card">
                            <label class="field" :class="{ invalid: fieldErrors.image }">
                                <span>Tile image</span>
                                <div class="upload-row">
                                    <label class="btn btn-primary file-btn"><span>{{ uploading ? '⏳ Uploading…' : '📁 Choose image' }}</span><input type="file" accept="image/*" @change="handleImageUpload" /></label>
                                    <span class="or-text">or paste path:</span>
                                    <input v-model="form.image" type="text" placeholder="/images/green.jpg" />
                                </div>
                                <em v-if="fieldErrors.image" class="field-msg">{{ fieldErrors.image[0] }}</em>
                            </label>
                            <div v-if="form.image" class="img-preview"><img :src="asset(form.image)" alt="preview" /></div>
                        </div>
                        <label class="field" :class="{ invalid: fieldErrors.label }"><span>Label</span><input v-model="form.label" type="text" /><em v-if="fieldErrors.label" class="field-msg">{{ fieldErrors.label[0] }}</em></label>
                        <div class="form-grid">
                            <label class="field"><span>Meta</span><input v-model="form.meta" type="text" placeholder="Limited · 100g" /></label>
                            <label class="field"><span>Link (product path)</span><input v-model="form.target" type="text" placeholder="/product/silver-white" /></label>
                        </div>
                        <label class="field"><span>Sort order</span><input v-model.number="form.sort_order" type="number" min="0" /></label>
                        <div class="modal-toggles">
                            <label class="mini-check"><input v-model="form.is_wide" type="checkbox" /> <span>Wide (tall) tile</span></label>
                            <label class="mini-check"><input v-model="form.is_published" type="checkbox" /> <span>Show on storefront</span></label>
                        </div>
                    </div>
                    <footer class="modal-foot">
                        <button class="btn btn-ghost" :disabled="busy" @click="closeForm">Cancel</button>
                        <button class="btn btn-primary" :disabled="busy" @click="save">{{ busy ? 'Saving…' : editingId ? 'Save changes' : 'Add tile' }}</button>
                    </footer>
                </div>
            </div>
        </Transition>

        <Transition name="modal">
            <div v-if="confirming" class="modal-wrap" @click.self="confirming = null">
                <div class="modal modal-sm">
                    <div class="modal-body confirm-body">
                        <span class="confirm-ico"><AppIcon name="trash" :size="22" /></span>
                        <h3>Delete this tile?</h3>
                        <p>“{{ confirming.label }}” — this cannot be undone.</p>
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
.upload-card { background: var(--cream, #f9f6f0); border: 1.5px dashed rgba(200, 162, 74, 0.4); border-radius: 12px; padding: 1rem 1.1rem; }
.upload-row { display: flex; align-items: center; gap: 0.8rem; flex-wrap: wrap; margin-top: 0.4rem; }
.file-btn { position: relative; overflow: hidden; cursor: pointer; }
.file-btn input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
.or-text { font-size: 0.8rem; color: var(--muted); }
.upload-row input[type="text"] { flex: 1; min-width: 180px; }
.img-preview { margin-top: 0.9rem; }
.img-preview img { max-width: 300px; width: 100%; border-radius: 10px; border: 2px solid var(--gold, #c8a24a); box-shadow: 0 6px 16px rgba(0,0,0,0.08); }
.blk { margin-bottom: 1.4rem; }
.blk-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.1rem 1.4rem; border-bottom: 1px solid var(--line, #eee); }
.blk-head h3 { margin: 0; font-size: 1.05rem; display: flex; align-items: center; gap: 0.5rem; }
.blk-body { padding: 1.2rem 1.4rem; display: grid; gap: 1rem; }
.count { font-size: 0.75rem; font-weight: 600; color: var(--muted); background: var(--soft, #f3f3f3); padding: 0.1rem 0.5rem; border-radius: 999px; }
.thumb { width: 56px; height: 40px; object-fit: cover; border-radius: 6px; }
.tag-cta { font-size: 0.72rem; font-weight: 600; color: var(--gold-ink, #8a6d1f); background: color-mix(in srgb, var(--gold) 18%, transparent); padding: 0.1rem 0.5rem; border-radius: 999px; }
.fmt-hint { font-weight: 400; font-style: normal; color: var(--muted); font-size: 0.78rem; margin-left: 0.3rem; }
</style>
