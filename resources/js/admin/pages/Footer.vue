<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import AppIcon from '../components/AppIcon.vue'
import Skeleton from '../components/Skeleton.vue'
import {
    fetchFooterLinks, createFooterLink, updateFooterLink, deleteFooterLink,
    fetchSocialLinks, createSocialLink, updateSocialLink, deleteSocialLink,
    fetchSettings, saveSettings,
} from '../data'
import { toast } from '../composables/useToast'

const loading = ref(true)
const footerLinks = ref([])
const socials = ref([])

const copy = reactive({ about: '', copyright: '', bottom_note: '' })
const copyBusy = ref(false)
const copyErrors = ref({})

const COLS = [
    { key: 'explore', title: 'Explore' },
    { key: 'support', title: 'Support' },
]
const SOCIAL_NAMES = ['Facebook', 'Instagram', 'YouTube', 'TikTok', 'WhatsApp', 'X', 'LinkedIn']

const byCol = (c) => footerLinks.value.filter((l) => l.col === c)

async function load() {
    loading.value = true
    try {
        const [fl, so, settings] = await Promise.all([fetchFooterLinks(), fetchSocialLinks(), fetchSettings()])
        footerLinks.value = fl
        socials.value = so
        Object.assign(copy, settings.footer || {})
    } finally {
        loading.value = false
    }
}
onMounted(load)

async function saveCopy() {
    copyBusy.value = true
    copyErrors.value = {}
    try {
        await saveSettings('footer', { ...copy })
        toast.success('Footer copy saved.')
    } catch (e) {
        copyErrors.value = e.data?.errors || {}
        if (e.status === 422) toast.error('Please fix the highlighted fields.')
    } finally {
        copyBusy.value = false
    }
}

/* ---- footer link modal ---- */
const lModal = reactive({ open: false, id: null })
const lBusy = ref(false)
const lErrors = ref({})
const lForm = reactive({ col: 'explore', label: '', target: '', is_published: true, sort_order: 0 })

function lCreate(col) {
    lModal.open = true; lModal.id = null
    lErrors.value = {}
    Object.assign(lForm, { col, label: '', target: '', is_published: true, sort_order: byCol(col).length })
}
function lEdit(l) {
    lModal.open = true; lModal.id = l.id
    lErrors.value = {}
    Object.assign(lForm, { col: l.col, label: l.label, target: l.target || '', is_published: !!l.is_published, sort_order: l.sort_order || 0 })
}
async function lSave() {
    lBusy.value = true; lErrors.value = {}
    try {
        if (lModal.id) { await updateFooterLink(lModal.id, { ...lForm }); toast.success('Link updated.') }
        else { await createFooterLink({ ...lForm }); toast.success('Link added.') }
        lModal.open = false
        await load()
    } catch (e) {
        lErrors.value = e.data?.errors || {}
        if (e.status === 422) toast.error('Please fix the highlighted fields.')
    } finally {
        lBusy.value = false
    }
}
async function lToggle(l) {
    try {
        await updateFooterLink(l.id, { ...l, is_published: !l.is_published })
        l.is_published = !l.is_published
    } catch (e) { /* api.js toasts */ }
}
const lConfirm = ref(null)
async function lRemove() {
    if (!lConfirm.value) return
    lBusy.value = true
    try {
        await deleteFooterLink(lConfirm.value.id)
        toast.success('Link deleted.')
        lConfirm.value = null
        await load()
    } finally {
        lBusy.value = false
    }
}

/* ---- social modal ---- */
const sModal = reactive({ open: false, id: null })
const sBusy = ref(false)
const sErrors = ref({})
const sForm = reactive({ name: 'Facebook', href: '', is_published: true, sort_order: 0 })

function sCreate() {
    sModal.open = true; sModal.id = null
    sErrors.value = {}
    Object.assign(sForm, { name: 'Facebook', href: '', is_published: true, sort_order: socials.value.length })
}
function sEdit(s) {
    sModal.open = true; sModal.id = s.id
    sErrors.value = {}
    Object.assign(sForm, { name: s.name, href: s.href, is_published: !!s.is_published, sort_order: s.sort_order || 0 })
}
async function sSave() {
    sBusy.value = true; sErrors.value = {}
    try {
        if (sModal.id) { await updateSocialLink(sModal.id, { ...sForm }); toast.success('Social updated.') }
        else { await createSocialLink({ ...sForm }); toast.success('Social added.') }
        sModal.open = false
        await load()
    } catch (e) {
        sErrors.value = e.data?.errors || {}
        if (e.status === 422) toast.error('Please fix the highlighted fields.')
    } finally {
        sBusy.value = false
    }
}
async function sToggle(s) {
    try {
        await updateSocialLink(s.id, { ...s, is_published: !s.is_published })
        s.is_published = !s.is_published
    } catch (e) { /* api.js toasts */ }
}
const sConfirm = ref(null)
async function sRemove() {
    if (!sConfirm.value) return
    sBusy.value = true
    try {
        await deleteSocialLink(sConfirm.value.id)
        toast.success('Social deleted.')
        sConfirm.value = null
        await load()
    } finally {
        sBusy.value = false
    }
}
</script>

<template>
    <div class="page">
        <div class="page-head">
            <div>
                <p class="eyebrow">Storefront</p>
                <h2>Footer</h2>
                <p>The site footer — about blurb, link columns, contact details and social icons.</p>
            </div>
        </div>

        <div v-if="loading" class="card" style="padding: 1.4rem"><Skeleton :rows="6" /></div>

        <template v-else>
            <!-- copy -->
            <section class="card blk">
                <header class="blk-head">
                    <h3>Footer copy</h3>
                    <button class="btn btn-primary" :disabled="copyBusy" @click="saveCopy">{{ copyBusy ? 'Saving…' : 'Save copy' }}</button>
                </header>
                <div class="blk-body">
                    <p class="footer-note">
                        The footer <strong>brand blurb</strong> and <strong>contact details</strong> (address, email, phone) now come from
                        <RouterLink to="/admin/settings">Settings → Store Profile</RouterLink> so they stay in sync everywhere.
                    </p>
                    <div class="form-grid">
                        <label class="field" :class="{ invalid: copyErrors.copyright }">
                            <span>Copyright line</span>
                            <input v-model="copy.copyright" type="text" />
                            <em v-if="copyErrors.copyright" class="field-msg">{{ copyErrors.copyright[0] }}</em>
                        </label>
                        <label class="field" :class="{ invalid: copyErrors.bottom_note }">
                            <span>Bottom note</span>
                            <input v-model="copy.bottom_note" type="text" placeholder="Privacy · Terms" />
                            <em v-if="copyErrors.bottom_note" class="field-msg">{{ copyErrors.bottom_note[0] }}</em>
                        </label>
                    </div>
                </div>
            </section>

            <!-- link columns -->
            <section v-for="col in COLS" :key="col.key" class="card blk">
                <header class="blk-head">
                    <h3>{{ col.title }} <span class="count">{{ byCol(col.key).length }}</span></h3>
                    <button class="btn btn-primary" @click="lCreate(col.key)"><AppIcon name="plus" :size="16" /> Add</button>
                </header>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Label</th><th>{{ col.key === 'contact' ? 'Detail' : 'Target' }}</th><th>Live</th><th></th></tr></thead>
                        <tbody>
                            <tr v-for="l in byCol(col.key)" :key="l.id">
                                <td><strong>{{ l.label }}</strong></td>
                                <td><code v-if="l.target">{{ l.target }}</code><span v-else style="color: var(--muted)">—</span></td>
                                <td><button class="switch sm" :class="{ on: l.is_published }" @click="lToggle(l)" /></td>
                                <td><div class="row-actions">
                                    <button title="Edit" @click="lEdit(l)"><AppIcon name="edit" :size="16" /></button>
                                    <button title="Delete" @click="lConfirm = l"><AppIcon name="trash" :size="16" /></button>
                                </div></td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-if="!byCol(col.key).length" class="empty">Nothing here yet.</p>
                </div>
            </section>

            <!-- socials -->
            <section class="card blk">
                <header class="blk-head">
                    <h3>Social links <span class="count">{{ socials.length }}</span></h3>
                    <button class="btn btn-primary" @click="sCreate"><AppIcon name="plus" :size="16" /> Add Social</button>
                </header>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th style="width: 140px">Platform</th><th>URL</th><th>Live</th><th></th></tr></thead>
                        <tbody>
                            <tr v-for="s in socials" :key="s.id">
                                <td><strong>{{ s.name }}</strong></td>
                                <td><code>{{ s.href }}</code></td>
                                <td><button class="switch sm" :class="{ on: s.is_published }" @click="sToggle(s)" /></td>
                                <td><div class="row-actions">
                                    <button title="Edit" @click="sEdit(s)"><AppIcon name="edit" :size="16" /></button>
                                    <button title="Delete" @click="sConfirm = s"><AppIcon name="trash" :size="16" /></button>
                                </div></td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-if="!socials.length" class="empty">No socials yet.</p>
                </div>
            </section>
        </template>

        <!-- footer link modal -->
        <Transition name="modal">
            <div v-if="lModal.open" class="modal-wrap" @click.self="!lBusy && (lModal.open = false)">
                <div class="modal">
                    <header class="modal-head">
                        <h3>{{ lModal.id ? 'Edit' : 'Add' }} {{ lForm.col }} link</h3>
                        <button class="modal-x" aria-label="Close" @click="!lBusy && (lModal.open = false)">
                            <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                        </button>
                    </header>
                    <div class="modal-body">
                        <label class="field" :class="{ invalid: lErrors.label }">
                            <span>Label</span>
                            <input v-model="lForm.label" type="text" placeholder="Our Story" />
                            <em v-if="lErrors.label" class="field-msg">{{ lErrors.label[0] }}</em>
                        </label>
                        <label class="field" :class="{ invalid: lErrors.target }">
                            <span>{{ lForm.col === 'contact' ? 'Detail (optional link/text)' : 'Target (section id / url)' }}</span>
                            <input v-model="lForm.target" type="text" :placeholder="lForm.col === 'contact' ? 'leave blank for plain text' : 'story or https://…'" />
                            <em v-if="lErrors.target" class="field-msg">{{ lErrors.target[0] }}</em>
                        </label>
                        <label class="field">
                            <span>Sort order</span>
                            <input v-model.number="lForm.sort_order" type="number" min="0" />
                        </label>
                        <div class="modal-toggles">
                            <label class="mini-check"><input v-model="lForm.is_published" type="checkbox" /> <span>Show in footer</span></label>
                        </div>
                    </div>
                    <footer class="modal-foot">
                        <button class="btn btn-ghost" :disabled="lBusy" @click="lModal.open = false">Cancel</button>
                        <button class="btn btn-primary" :disabled="lBusy" @click="lSave">{{ lBusy ? 'Saving…' : lModal.id ? 'Save changes' : 'Add link' }}</button>
                    </footer>
                </div>
            </div>
        </Transition>

        <!-- social modal -->
        <Transition name="modal">
            <div v-if="sModal.open" class="modal-wrap" @click.self="!sBusy && (sModal.open = false)">
                <div class="modal">
                    <header class="modal-head">
                        <h3>{{ sModal.id ? 'Edit social' : 'Add social' }}</h3>
                        <button class="modal-x" aria-label="Close" @click="!sBusy && (sModal.open = false)">
                            <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                        </button>
                    </header>
                    <div class="modal-body">
                        <label class="field" :class="{ invalid: sErrors.name }">
                            <span>Platform</span>
                            <select v-model="sForm.name">
                                <option v-for="n in SOCIAL_NAMES" :key="n" :value="n">{{ n }}</option>
                            </select>
                            <em v-if="sErrors.name" class="field-msg">{{ sErrors.name[0] }}</em>
                            <em class="fmt-hint">icon is chosen automatically from the platform name</em>
                        </label>
                        <label class="field" :class="{ invalid: sErrors.href }">
                            <span>Profile URL</span>
                            <input v-model="sForm.href" type="text" placeholder="https://facebook.com/chakunjo" />
                            <em v-if="sErrors.href" class="field-msg">{{ sErrors.href[0] }}</em>
                        </label>
                        <label class="field">
                            <span>Sort order</span>
                            <input v-model.number="sForm.sort_order" type="number" min="0" />
                        </label>
                        <div class="modal-toggles">
                            <label class="mini-check"><input v-model="sForm.is_published" type="checkbox" /> <span>Show in footer</span></label>
                        </div>
                    </div>
                    <footer class="modal-foot">
                        <button class="btn btn-ghost" :disabled="sBusy" @click="sModal.open = false">Cancel</button>
                        <button class="btn btn-primary" :disabled="sBusy" @click="sSave">{{ sBusy ? 'Saving…' : sModal.id ? 'Save changes' : 'Add social' }}</button>
                    </footer>
                </div>
            </div>
        </Transition>

        <!-- delete confirms -->
        <Transition name="modal">
            <div v-if="lConfirm" class="modal-wrap" @click.self="lConfirm = null">
                <div class="modal modal-sm">
                    <div class="modal-body confirm-body">
                        <span class="confirm-ico"><AppIcon name="trash" :size="22" /></span>
                        <h3>Delete this link?</h3>
                        <p>“{{ lConfirm.label }}” — this cannot be undone.</p>
                    </div>
                    <footer class="modal-foot">
                        <button class="btn btn-ghost" :disabled="lBusy" @click="lConfirm = null">Cancel</button>
                        <button class="btn btn-danger" :disabled="lBusy" @click="lRemove">{{ lBusy ? 'Deleting…' : 'Delete' }}</button>
                    </footer>
                </div>
            </div>
        </Transition>
        <Transition name="modal">
            <div v-if="sConfirm" class="modal-wrap" @click.self="sConfirm = null">
                <div class="modal modal-sm">
                    <div class="modal-body confirm-body">
                        <span class="confirm-ico"><AppIcon name="trash" :size="22" /></span>
                        <h3>Delete this social link?</h3>
                        <p>“{{ sConfirm.name }}” — this cannot be undone.</p>
                    </div>
                    <footer class="modal-foot">
                        <button class="btn btn-ghost" :disabled="sBusy" @click="sConfirm = null">Cancel</button>
                        <button class="btn btn-danger" :disabled="sBusy" @click="sRemove">{{ sBusy ? 'Deleting…' : 'Delete' }}</button>
                    </footer>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.footer-note {
    background: var(--gold-050, #faf4e6);
    border: 1px solid rgba(200, 162, 74, 0.3);
    border-radius: 10px;
    padding: 0.75rem 0.95rem;
    font-size: 0.85rem;
    color: #6b5a2e;
    line-height: 1.5;
}
.footer-note a { color: var(--green-700, #1d4230); font-weight: 600; text-decoration: underline; }

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
.fmt-hint { font-weight: 400; font-style: normal; color: var(--muted); font-size: 0.78rem; }
code { font-size: 0.82rem; color: var(--muted); background: var(--soft, #f3f3f3); padding: 0.05rem 0.4rem; border-radius: 5px; }
</style>
