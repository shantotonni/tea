<script setup>
import { ref, reactive, onMounted } from 'vue'
import AppIcon from '../components/AppIcon.vue'
import Skeleton from '../components/Skeleton.vue'
import {
    fetchHeroSlides, createHeroSlide, updateHeroSlide, deleteHeroSlide,
    fetchHeroFeatures, createHeroFeature, updateHeroFeature, deleteHeroFeature,
    fetchHeroStats, createHeroStat, updateHeroStat, deleteHeroStat,
    fetchSettings, saveSettings, asset,
} from '../data'
import { uploadFile } from '../api'
import { toast } from '../composables/useToast'

const loading = ref(true)
const uploading = ref(false)

async function handleSlideUpload(e) {
    const file = e.target.files?.[0]
    if (!file) return
    uploading.value = true
    try {
        const url = await uploadFile(file)
        form.image = url
        toast.success('Image uploaded.')
    } catch (err) {
        toast.error('Upload failed. Image must be under 10MB.')
    } finally {
        uploading.value = false
        e.target.value = ''
    }
}
const slides = ref([])
const features = ref([])
const stats = ref([])

const copy = reactive({
    eyebrow: '', title: '', title_accent: '', subtitle: '',
    cta_primary_label: '', cta_primary_target: '', cta_ghost_label: '', cta_ghost_target: '',
})
const copyBusy = ref(false)
const copyErrors = ref({})

async function load() {
    loading.value = true
    try {
        const [sl, fe, st, settings] = await Promise.all([
            fetchHeroSlides(), fetchHeroFeatures(), fetchHeroStats(), fetchSettings(),
        ])
        slides.value = sl
        features.value = fe
        stats.value = st
        Object.assign(copy, settings.hero || {})
    } finally {
        loading.value = false
    }
}
onMounted(load)

async function saveCopy() {
    copyBusy.value = true
    copyErrors.value = {}
    try {
        await saveSettings('hero', { ...copy })
        toast.success('Hero copy saved.')
    } catch (e) {
        copyErrors.value = e.data?.errors || {}
        if (e.status === 422) toast.error('Please fix the highlighted fields.')
    } finally {
        copyBusy.value = false
    }
}

/* ---- generic list editor ---- *
 * kind: 'slide' | 'feature' | 'stat' — drives which api + fields.
 */
const CFG = {
    slide: { list: slides, create: createHeroSlide, update: updateHeroSlide, del: deleteHeroSlide, blank: () => ({ image: '', is_published: true, sort_order: 0 }) },
    feature: { list: features, create: createHeroFeature, update: updateHeroFeature, del: deleteHeroFeature, blank: () => ({ icon: '', label: '', is_published: true, sort_order: 0 }) },
    stat: { list: stats, create: createHeroStat, update: updateHeroStat, del: deleteHeroStat, blank: () => ({ value: '', label: '', is_published: true, sort_order: 0 }) },
}

const modal = reactive({ open: false, kind: null, id: null })
const busy = ref(false)
const errors = ref({})
const form = reactive({})

function openCreate(kind) {
    const c = CFG[kind]
    modal.open = true; modal.kind = kind; modal.id = null
    errors.value = {}
    Object.assign(form, c.blank(), { sort_order: c.list.value.length })
}
function openEdit(kind, row) {
    modal.open = true; modal.kind = kind; modal.id = row.id
    errors.value = {}
    Object.keys(form).forEach((k) => delete form[k])
    Object.assign(form, CFG[kind].blank(), row)
}
function closeModal() { if (!busy.value) modal.open = false }

async function save() {
    const c = CFG[modal.kind]
    busy.value = true; errors.value = {}
    try {
        if (modal.id) {
            await c.update(modal.id, { ...form })
            toast.success('Saved.')
        } else {
            await c.create({ ...form })
            toast.success('Added.')
        }
        modal.open = false
        await load()
    } catch (e) {
        errors.value = e.data?.errors || {}
        if (e.status === 422) toast.error('Please fix the highlighted fields.')
    } finally {
        busy.value = false
    }
}

async function toggle(kind, row) {
    try {
        await CFG[kind].update(row.id, { ...row, is_published: !row.is_published })
        row.is_published = !row.is_published
    } catch (e) { /* api.js toasts */ }
}

const confirming = reactive({ kind: null, row: null })
function askDelete(kind, row) { confirming.kind = kind; confirming.row = row }
async function remove() {
    if (!confirming.row) return
    busy.value = true
    try {
        await CFG[confirming.kind].del(confirming.row.id)
        toast.success('Deleted.')
        confirming.row = null
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
                <h2>Hero Banner</h2>
                <p>The top-of-homepage slider, headline copy, feature chips and stat counters.</p>
            </div>
        </div>

        <div v-if="loading" class="card" style="padding: 1.4rem"><Skeleton :rows="6" /></div>

        <template v-else>
            <!-- copy -->
            <section class="card blk">
                <header class="blk-head">
                    <h3>Headline copy</h3>
                    <button class="btn btn-primary" :disabled="copyBusy" @click="saveCopy">{{ copyBusy ? 'Saving…' : 'Save copy' }}</button>
                </header>
                <div class="blk-body">
                    <label class="field" :class="{ invalid: copyErrors.eyebrow }">
                        <span>Eyebrow</span>
                        <input v-model="copy.eyebrow" type="text" />
                        <em v-if="copyErrors.eyebrow" class="field-msg">{{ copyErrors.eyebrow[0] }}</em>
                    </label>
                    <div class="form-grid">
                        <label class="field" :class="{ invalid: copyErrors.title }">
                            <span>Title</span>
                            <input v-model="copy.title" type="text" />
                            <em v-if="copyErrors.title" class="field-msg">{{ copyErrors.title[0] }}</em>
                        </label>
                        <label class="field" :class="{ invalid: copyErrors.title_accent }">
                            <span>Accent phrase <em style="color: var(--muted)">(gold, must appear in title)</em></span>
                            <input v-model="copy.title_accent" type="text" />
                            <em v-if="copyErrors.title_accent" class="field-msg">{{ copyErrors.title_accent[0] }}</em>
                        </label>
                    </div>
                    <label class="field" :class="{ invalid: copyErrors.subtitle }">
                        <span>Subtitle</span>
                        <textarea v-model="copy.subtitle" rows="3" />
                        <em v-if="copyErrors.subtitle" class="field-msg">{{ copyErrors.subtitle[0] }}</em>
                    </label>
                    <div class="form-grid">
                        <label class="field" :class="{ invalid: copyErrors.cta_primary_label }">
                            <span>Primary button label</span>
                            <input v-model="copy.cta_primary_label" type="text" />
                            <em v-if="copyErrors.cta_primary_label" class="field-msg">{{ copyErrors.cta_primary_label[0] }}</em>
                        </label>
                        <label class="field" :class="{ invalid: copyErrors.cta_primary_target }">
                            <span>Primary scroll target <em style="color: var(--muted)">(section id)</em></span>
                            <input v-model="copy.cta_primary_target" type="text" placeholder="collection" />
                            <em v-if="copyErrors.cta_primary_target" class="field-msg">{{ copyErrors.cta_primary_target[0] }}</em>
                        </label>
                    </div>
                    <div class="form-grid">
                        <label class="field">
                            <span>Ghost button label</span>
                            <input v-model="copy.cta_ghost_label" type="text" />
                        </label>
                        <label class="field">
                            <span>Ghost scroll target</span>
                            <input v-model="copy.cta_ghost_target" type="text" placeholder="story" />
                        </label>
                    </div>
                </div>
            </section>

            <!-- slides -->
            <section class="card blk">
                <header class="blk-head">
                    <h3>Slides <span class="count">{{ slides.length }}</span></h3>
                    <button class="btn btn-primary" @click="openCreate('slide')"><AppIcon name="plus" :size="16" /> Add Slide</button>
                </header>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th style="width: 90px">Preview</th><th>Image path</th><th>Live</th><th></th></tr></thead>
                        <tbody>
                            <tr v-for="s in slides" :key="s.id">
                                <td><img :src="asset(s.image)" alt="" class="thumb" /></td>
                                <td><code>{{ s.image }}</code></td>
                                <td><button class="switch sm" :class="{ on: s.is_published }" @click="toggle('slide', s)" /></td>
                                <td><div class="row-actions">
                                    <button title="Edit" @click="openEdit('slide', s)"><AppIcon name="edit" :size="16" /></button>
                                    <button title="Delete" @click="askDelete('slide', s)"><AppIcon name="trash" :size="16" /></button>
                                </div></td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-if="!slides.length" class="empty">No slides yet.</p>
                </div>
            </section>

            <!-- features -->
            <section class="card blk">
                <header class="blk-head">
                    <h3>Feature chips <span class="count">{{ features.length }}</span></h3>
                    <button class="btn btn-primary" @click="openCreate('feature')"><AppIcon name="plus" :size="16" /> Add Chip</button>
                </header>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th style="width: 60px">Icon</th><th>Label</th><th>Live</th><th></th></tr></thead>
                        <tbody>
                            <tr v-for="f in features" :key="f.id">
                                <td style="font-size: 1.3rem">{{ f.icon }}</td>
                                <td><strong>{{ f.label }}</strong></td>
                                <td><button class="switch sm" :class="{ on: f.is_published }" @click="toggle('feature', f)" /></td>
                                <td><div class="row-actions">
                                    <button title="Edit" @click="openEdit('feature', f)"><AppIcon name="edit" :size="16" /></button>
                                    <button title="Delete" @click="askDelete('feature', f)"><AppIcon name="trash" :size="16" /></button>
                                </div></td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-if="!features.length" class="empty">No chips yet.</p>
                </div>
            </section>

            <!-- stats -->
            <section class="card blk">
                <header class="blk-head">
                    <h3>Stat counters <span class="count">{{ stats.length }}</span></h3>
                    <button class="btn btn-primary" @click="openCreate('stat')"><AppIcon name="plus" :size="16" /> Add Stat</button>
                </header>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th style="width: 100px">Value</th><th>Label</th><th>Live</th><th></th></tr></thead>
                        <tbody>
                            <tr v-for="s in stats" :key="s.id">
                                <td><strong style="font-family: var(--serif); font-size: 1.2rem; color: var(--gold)">{{ s.value }}</strong></td>
                                <td>{{ s.label }}</td>
                                <td><button class="switch sm" :class="{ on: s.is_published }" @click="toggle('stat', s)" /></td>
                                <td><div class="row-actions">
                                    <button title="Edit" @click="openEdit('stat', s)"><AppIcon name="edit" :size="16" /></button>
                                    <button title="Delete" @click="askDelete('stat', s)"><AppIcon name="trash" :size="16" /></button>
                                </div></td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-if="!stats.length" class="empty">No stats yet.</p>
                </div>
            </section>
        </template>

        <!-- add / edit modal -->
        <Transition name="modal">
            <div v-if="modal.open" class="modal-wrap" @click.self="closeModal">
                <div class="modal">
                    <header class="modal-head">
                        <h3>{{ modal.id ? 'Edit' : 'Add' }} {{ modal.kind }}</h3>
                        <button class="modal-x" aria-label="Close" @click="closeModal">
                            <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                        </button>
                    </header>
                    <div class="modal-body">
                        <!-- slide -->
                        <template v-if="modal.kind === 'slide'">
                            <div class="upload-card">
                                <label class="field" :class="{ invalid: errors.image }">
                                    <span>Slide image</span>
                                    <div class="upload-row">
                                        <label class="btn btn-primary file-btn">
                                            <span>{{ uploading ? '⏳ Uploading…' : '📁 Choose image' }}</span>
                                            <input type="file" accept="image/*" @change="handleSlideUpload" />
                                        </label>
                                        <span class="or-text">or paste path:</span>
                                        <input v-model="form.image" type="text" placeholder="/images/slider/1.jpeg" />
                                    </div>
                                    <em v-if="errors.image" class="field-msg">{{ errors.image[0] }}</em>
                                </label>
                                <div v-if="form.image" class="img-preview">
                                    <img :src="asset(form.image)" alt="slide preview" />
                                </div>
                            </div>
                        </template>
                        <!-- feature -->
                        <template v-else-if="modal.kind === 'feature'">
                            <div class="form-grid">
                                <label class="field" :class="{ invalid: errors.icon }">
                                    <span>Icon (emoji)</span>
                                    <input v-model="form.icon" type="text" maxlength="4" placeholder="🍃" />
                                    <em v-if="errors.icon" class="field-msg">{{ errors.icon[0] }}</em>
                                </label>
                                <label class="field" :class="{ invalid: errors.label }">
                                    <span>Label</span>
                                    <input v-model="form.label" type="text" placeholder="100% Pure Leaf" />
                                    <em v-if="errors.label" class="field-msg">{{ errors.label[0] }}</em>
                                </label>
                            </div>
                        </template>
                        <!-- stat -->
                        <template v-else-if="modal.kind === 'stat'">
                            <div class="form-grid">
                                <label class="field" :class="{ invalid: errors.value }">
                                    <span>Value</span>
                                    <input v-model="form.value" type="text" placeholder="48h" />
                                    <em v-if="errors.value" class="field-msg">{{ errors.value[0] }}</em>
                                </label>
                                <label class="field" :class="{ invalid: errors.label }">
                                    <span>Label</span>
                                    <input v-model="form.label" type="text" placeholder="Garden to Pack" />
                                    <em v-if="errors.label" class="field-msg">{{ errors.label[0] }}</em>
                                </label>
                            </div>
                        </template>

                        <label class="field">
                            <span>Sort order</span>
                            <input v-model.number="form.sort_order" type="number" min="0" />
                        </label>
                        <div class="modal-toggles">
                            <label class="mini-check"><input v-model="form.is_published" type="checkbox" /> <span>Show on storefront</span></label>
                        </div>
                    </div>
                    <footer class="modal-foot">
                        <button class="btn btn-ghost" :disabled="busy" @click="closeModal">Cancel</button>
                        <button class="btn btn-primary" :disabled="busy" @click="save">{{ busy ? 'Saving…' : modal.id ? 'Save changes' : 'Add' }}</button>
                    </footer>
                </div>
            </div>
        </Transition>

        <!-- delete -->
        <Transition name="modal">
            <div v-if="confirming.row" class="modal-wrap" @click.self="confirming.row = null">
                <div class="modal modal-sm">
                    <div class="modal-body confirm-body">
                        <span class="confirm-ico"><AppIcon name="trash" :size="22" /></span>
                        <h3>Delete this {{ confirming.kind }}?</h3>
                        <p>This cannot be undone.</p>
                    </div>
                    <footer class="modal-foot">
                        <button class="btn btn-ghost" :disabled="busy" @click="confirming.row = null">Cancel</button>
                        <button class="btn btn-danger" :disabled="busy" @click="remove">{{ busy ? 'Deleting…' : 'Delete' }}</button>
                    </footer>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.blk { margin-bottom: 1.4rem; }
.blk-head {
    display: flex; align-items: center; justify-content: space-between; gap: 1rem;
    padding: 1.1rem 1.4rem; border-bottom: 1px solid var(--line, #eee);
}
.blk-head h3 { margin: 0; font-size: 1.05rem; display: flex; align-items: center; gap: 0.5rem; }
.blk-body { padding: 1.2rem 1.4rem; display: grid; gap: 1rem; }
.count {
    font-size: 0.75rem; font-weight: 600; color: var(--muted);
    background: var(--soft, #f3f3f3); padding: 0.1rem 0.5rem; border-radius: 999px;
}
.thumb { width: 64px; height: 40px; object-fit: cover; border-radius: 6px; }
code { font-size: 0.82rem; color: var(--muted); }

/* image upload */
.upload-card { background: var(--cream, #f9f6f0); border: 1.5px dashed rgba(200, 162, 74, 0.4); border-radius: 12px; padding: 1rem 1.1rem; }
.upload-row { display: flex; align-items: center; gap: 0.8rem; flex-wrap: wrap; margin-top: 0.4rem; }
.file-btn { position: relative; overflow: hidden; cursor: pointer; }
.file-btn input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
.or-text { font-size: 0.8rem; color: var(--muted); }
.upload-row input[type="text"] { flex: 1; min-width: 180px; }
.img-preview { margin-top: 0.9rem; }
.img-preview img { max-width: 320px; width: 100%; border-radius: 10px; border: 2px solid var(--gold, #c8a24a); box-shadow: 0 6px 16px rgba(0,0,0,0.08); }
</style>
