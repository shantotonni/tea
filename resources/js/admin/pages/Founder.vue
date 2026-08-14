<script setup>
import { ref, reactive, onMounted } from 'vue'
import AppIcon from '../components/AppIcon.vue'
import Skeleton from '../components/Skeleton.vue'
import {
    fetchFounders, createFounder, updateFounder, deleteFounder,
    fetchFounderPoints, createFounderPoint, updateFounderPoint, deleteFounderPoint,
    fetchSettings, saveSettings,
} from '../data'
import { toast } from '../composables/useToast'

const loading = ref(true)
const founders = ref([])
const points = ref([])

/* ---- section copy ---- */
const copy = reactive({ eyebrow: '', title: '', quote: '', badge: '' })
const copyBusy = ref(false)
const copyErrors = ref({})

async function load() {
    loading.value = true
    try {
        const [f, p, settings] = await Promise.all([fetchFounders(), fetchFounderPoints(), fetchSettings()])
        founders.value = f
        points.value = p
        Object.assign(copy, { eyebrow: '', title: '', quote: '', badge: '' }, settings.founder || {})
    } finally {
        loading.value = false
    }
}
onMounted(load)

async function saveCopy() {
    copyBusy.value = true
    copyErrors.value = {}
    try {
        await saveSettings('founder', { ...copy })
        toast.success('Section copy saved.')
    } catch (e) {
        copyErrors.value = e.data?.errors || {}
        if (e.status === 422) toast.error('Please fix the highlighted fields.')
    } finally {
        copyBusy.value = false
    }
}

/* ---- founder modal ---- */
const fBlank = () => ({ name: '', role: 'Co-Founder · Cha Kunjo', initials: '', is_published: true, sort_order: 0 })
const fShow = ref(false)
const fEditId = ref(null)
const fBusy = ref(false)
const fErrors = ref({})
const fForm = reactive(fBlank())

function fOpenCreate() {
    fEditId.value = null
    fErrors.value = {}
    Object.assign(fForm, fBlank(), { sort_order: founders.value.length })
    fShow.value = true
}
function fOpenEdit(f) {
    fEditId.value = f.id
    fErrors.value = {}
    Object.assign(fForm, { name: f.name, role: f.role, initials: f.initials || '', is_published: !!f.is_published, sort_order: f.sort_order || 0 })
    fShow.value = true
}
async function fSave() {
    fBusy.value = true
    fErrors.value = {}
    try {
        if (fEditId.value) {
            await updateFounder(fEditId.value, { ...fForm })
            toast.success('Founder updated.')
        } else {
            await createFounder({ ...fForm })
            toast.success('Founder added.')
        }
        fShow.value = false
        await load()
    } catch (e) {
        fErrors.value = e.data?.errors || {}
        if (e.status === 422) toast.error('Please fix the highlighted fields.')
    } finally {
        fBusy.value = false
    }
}
async function fToggle(f) {
    try {
        await updateFounder(f.id, { ...f, is_published: !f.is_published })
        f.is_published = !f.is_published
    } catch (e) { /* api.js toasts */ }
}
const fConfirm = ref(null)
async function fRemove() {
    if (!fConfirm.value) return
    fBusy.value = true
    try {
        await deleteFounder(fConfirm.value.id)
        toast.success('Founder deleted.')
        fConfirm.value = null
        await load()
    } finally {
        fBusy.value = false
    }
}

/* ---- point modal ---- */
const pBlank = () => ({ num: '', title: '', text: '', is_published: true, sort_order: 0 })
const pShow = ref(false)
const pEditId = ref(null)
const pBusy = ref(false)
const pErrors = ref({})
const pForm = reactive(pBlank())

function pOpenCreate() {
    pEditId.value = null
    pErrors.value = {}
    Object.assign(pForm, pBlank(), { num: String(points.value.length + 1).padStart(2, '0'), sort_order: points.value.length })
    pShow.value = true
}
function pOpenEdit(p) {
    pEditId.value = p.id
    pErrors.value = {}
    Object.assign(pForm, { num: p.num, title: p.title, text: p.text, is_published: !!p.is_published, sort_order: p.sort_order || 0 })
    pShow.value = true
}
async function pSave() {
    pBusy.value = true
    pErrors.value = {}
    try {
        if (pEditId.value) {
            await updateFounderPoint(pEditId.value, { ...pForm })
            toast.success('Point updated.')
        } else {
            await createFounderPoint({ ...pForm })
            toast.success('Point added.')
        }
        pShow.value = false
        await load()
    } catch (e) {
        pErrors.value = e.data?.errors || {}
        if (e.status === 422) toast.error('Please fix the highlighted fields.')
    } finally {
        pBusy.value = false
    }
}
async function pToggle(p) {
    try {
        await updateFounderPoint(p.id, { ...p, is_published: !p.is_published })
        p.is_published = !p.is_published
    } catch (e) { /* api.js toasts */ }
}
const pConfirm = ref(null)
async function pRemove() {
    if (!pConfirm.value) return
    pBusy.value = true
    try {
        await deleteFounderPoint(pConfirm.value.id)
        toast.success('Point deleted.')
        pConfirm.value = null
        await load()
    } finally {
        pBusy.value = false
    }
}
</script>

<template>
    <div class="page">
        <div class="page-head">
            <div>
                <p class="eyebrow">Storefront</p>
                <h2>Founder Story</h2>
                <p>The “Behind the pouch” section — copy, co-founders and story points.</p>
            </div>
        </div>

        <div v-if="loading" class="card" style="padding: 1.4rem"><Skeleton :rows="6" /></div>

        <template v-else>
            <!-- section copy -->
            <section class="card fs-card">
                <header class="fs-card-head">
                    <h3>Section copy</h3>
                    <button class="btn btn-primary" :disabled="copyBusy" @click="saveCopy">
                        {{ copyBusy ? 'Saving…' : 'Save copy' }}
                    </button>
                </header>
                <div class="card-body">
                    <div class="form-grid">
                        <label class="field" :class="{ invalid: copyErrors.eyebrow }">
                            <span>Eyebrow</span>
                            <input v-model="copy.eyebrow" type="text" placeholder="Behind the pouch" />
                            <em v-if="copyErrors.eyebrow" class="field-msg">{{ copyErrors.eyebrow[0] }}</em>
                        </label>
                        <label class="field" :class="{ invalid: copyErrors.badge }">
                            <span>Portrait badge</span>
                            <input v-model="copy.badge" type="text" placeholder="Cha Kunjo Co-Founders" />
                            <em v-if="copyErrors.badge" class="field-msg">{{ copyErrors.badge[0] }}</em>
                        </label>
                    </div>
                    <label class="field" :class="{ invalid: copyErrors.title }">
                        <span>Title</span>
                        <input v-model="copy.title" type="text" placeholder="Why we started Cha Kunjo" />
                        <em v-if="copyErrors.title" class="field-msg">{{ copyErrors.title[0] }}</em>
                    </label>
                    <label class="field" :class="{ invalid: copyErrors.quote }">
                        <span>Founder quote</span>
                        <textarea v-model="copy.quote" rows="4" />
                        <em v-if="copyErrors.quote" class="field-msg">{{ copyErrors.quote[0] }}</em>
                    </label>
                </div>
            </section>

            <!-- founders -->
            <section class="card">
                <header class="fs-card-head">
                    <h3>Co-Founders</h3>
                    <button class="btn btn-primary" @click="fOpenCreate"><AppIcon name="plus" :size="17" /> Add Founder</button>
                </header>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th style="width: 70px"></th><th>Name</th><th>Role</th><th>Live</th><th></th></tr>
                        </thead>
                        <tbody>
                            <tr v-for="f in founders" :key="f.id">
                                <td><span class="fs-init">{{ f.initials || f.name.slice(0, 2).toUpperCase() }}</span></td>
                                <td><strong>{{ f.name }}</strong></td>
                                <td>{{ f.role }}</td>
                                <td><button class="switch sm" :class="{ on: f.is_published }" @click="fToggle(f)" /></td>
                                <td>
                                    <div class="row-actions">
                                        <button title="Edit" @click="fOpenEdit(f)"><AppIcon name="edit" :size="16" /></button>
                                        <button title="Delete" @click="fConfirm = f"><AppIcon name="trash" :size="16" /></button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-if="!founders.length" class="empty">No founders yet.</p>
                </div>
            </section>

            <!-- points -->
            <section class="card">
                <header class="fs-card-head">
                    <h3>Story points</h3>
                    <button class="btn btn-primary" @click="pOpenCreate"><AppIcon name="plus" :size="17" /> Add Point</button>
                </header>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th style="width: 60px">#</th><th>Title</th><th>Description</th><th>Live</th><th></th></tr>
                        </thead>
                        <tbody>
                            <tr v-for="p in points" :key="p.id">
                                <td><strong style="font-family: var(--serif); font-size: 1.2rem; color: var(--gold)">{{ p.num }}</strong></td>
                                <td><strong>{{ p.title }}</strong></td>
                                <td><span class="rev-clip" style="max-width: 420px">{{ p.text }}</span></td>
                                <td><button class="switch sm" :class="{ on: p.is_published }" @click="pToggle(p)" /></td>
                                <td>
                                    <div class="row-actions">
                                        <button title="Edit" @click="pOpenEdit(p)"><AppIcon name="edit" :size="16" /></button>
                                        <button title="Delete" @click="pConfirm = p"><AppIcon name="trash" :size="16" /></button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-if="!points.length" class="empty">No story points yet.</p>
                </div>
            </section>
        </template>

        <!-- founder modal -->
        <Transition name="modal">
            <div v-if="fShow" class="modal-wrap" @click.self="!fBusy && (fShow = false)">
                <div class="modal">
                    <header class="modal-head">
                        <h3>{{ fEditId ? 'Edit founder' : 'Add founder' }}</h3>
                        <button class="modal-x" aria-label="Close" @click="!fBusy && (fShow = false)">
                            <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                        </button>
                    </header>
                    <div class="modal-body">
                        <label class="field" :class="{ invalid: fErrors.name }">
                            <span>Name</span>
                            <input v-model="fForm.name" type="text" placeholder="Shojibul Islam" />
                            <em v-if="fErrors.name" class="field-msg">{{ fErrors.name[0] }}</em>
                        </label>
                        <div class="form-grid">
                            <label class="field" :class="{ invalid: fErrors.role }">
                                <span>Role</span>
                                <input v-model="fForm.role" type="text" placeholder="Co-Founder · Cha Kunjo" />
                                <em v-if="fErrors.role" class="field-msg">{{ fErrors.role[0] }}</em>
                            </label>
                            <label class="field" :class="{ invalid: fErrors.initials }">
                                <span>Initials <em style="color: var(--muted)">(optional)</em></span>
                                <input v-model="fForm.initials" type="text" maxlength="4" placeholder="SI" />
                                <em v-if="fErrors.initials" class="field-msg">{{ fErrors.initials[0] }}</em>
                            </label>
                        </div>
                        <label class="field">
                            <span>Sort order</span>
                            <input v-model.number="fForm.sort_order" type="number" min="0" />
                        </label>
                        <div class="modal-toggles">
                            <label class="mini-check"><input v-model="fForm.is_published" type="checkbox" /> <span>Show on storefront</span></label>
                        </div>
                    </div>
                    <footer class="modal-foot">
                        <button class="btn btn-ghost" :disabled="fBusy" @click="fShow = false">Cancel</button>
                        <button class="btn btn-primary" :disabled="fBusy" @click="fSave">{{ fBusy ? 'Saving…' : fEditId ? 'Save changes' : 'Add founder' }}</button>
                    </footer>
                </div>
            </div>
        </Transition>

        <!-- point modal -->
        <Transition name="modal">
            <div v-if="pShow" class="modal-wrap" @click.self="!pBusy && (pShow = false)">
                <div class="modal">
                    <header class="modal-head">
                        <h3>{{ pEditId ? 'Edit story point' : 'Add story point' }}</h3>
                        <button class="modal-x" aria-label="Close" @click="!pBusy && (pShow = false)">
                            <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                        </button>
                    </header>
                    <div class="modal-body">
                        <div class="form-grid">
                            <label class="field" :class="{ invalid: pErrors.num }">
                                <span>Number</span>
                                <input v-model="pForm.num" type="text" placeholder="01" />
                                <em v-if="pErrors.num" class="field-msg">{{ pErrors.num[0] }}</em>
                            </label>
                            <label class="field">
                                <span>Sort order</span>
                                <input v-model.number="pForm.sort_order" type="number" min="0" />
                            </label>
                        </div>
                        <label class="field" :class="{ invalid: pErrors.title }">
                            <span>Title</span>
                            <input v-model="pForm.title" type="text" placeholder="We buy direct from growers" />
                            <em v-if="pErrors.title" class="field-msg">{{ pErrors.title[0] }}</em>
                        </label>
                        <label class="field" :class="{ invalid: pErrors.text }">
                            <span>Description</span>
                            <textarea v-model="pForm.text" rows="3" />
                            <em v-if="pErrors.text" class="field-msg">{{ pErrors.text[0] }}</em>
                        </label>
                        <div class="modal-toggles">
                            <label class="mini-check"><input v-model="pForm.is_published" type="checkbox" /> <span>Show on storefront</span></label>
                        </div>
                    </div>
                    <footer class="modal-foot">
                        <button class="btn btn-ghost" :disabled="pBusy" @click="pShow = false">Cancel</button>
                        <button class="btn btn-primary" :disabled="pBusy" @click="pSave">{{ pBusy ? 'Saving…' : pEditId ? 'Save changes' : 'Add point' }}</button>
                    </footer>
                </div>
            </div>
        </Transition>

        <!-- delete confirms -->
        <Transition name="modal">
            <div v-if="fConfirm" class="modal-wrap" @click.self="fConfirm = null">
                <div class="modal modal-sm">
                    <div class="modal-body confirm-body">
                        <span class="confirm-ico"><AppIcon name="trash" :size="22" /></span>
                        <h3>Delete this founder?</h3>
                        <p>“{{ fConfirm.name }}” — this cannot be undone.</p>
                    </div>
                    <footer class="modal-foot">
                        <button class="btn btn-ghost" :disabled="fBusy" @click="fConfirm = null">Cancel</button>
                        <button class="btn btn-danger" :disabled="fBusy" @click="fRemove">{{ fBusy ? 'Deleting…' : 'Delete' }}</button>
                    </footer>
                </div>
            </div>
        </Transition>
        <Transition name="modal">
            <div v-if="pConfirm" class="modal-wrap" @click.self="pConfirm = null">
                <div class="modal modal-sm">
                    <div class="modal-body confirm-body">
                        <span class="confirm-ico"><AppIcon name="trash" :size="22" /></span>
                        <h3>Delete this story point?</h3>
                        <p>“{{ pConfirm.title }}” — this cannot be undone.</p>
                    </div>
                    <footer class="modal-foot">
                        <button class="btn btn-ghost" :disabled="pBusy" @click="pConfirm = null">Cancel</button>
                        <button class="btn btn-danger" :disabled="pBusy" @click="pRemove">{{ pBusy ? 'Deleting…' : 'Delete' }}</button>
                    </footer>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.fs-card { margin-bottom: 1.4rem; }
.fs-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.1rem 1.4rem;
    border-bottom: 1px solid var(--line, #eee);
}
.fs-card-head h3 { margin: 0; font-size: 1.05rem; }
.card-body { padding: 1.2rem 1.4rem; }
.card { margin-bottom: 1.4rem; }
.fs-init {
    display: inline-grid;
    place-items: center;
    width: 40px; height: 40px;
    border-radius: 50%;
    background: color-mix(in srgb, var(--gold) 22%, transparent);
    color: var(--gold-ink, #8a6d1f);
    font-weight: 700;
    font-size: 0.85rem;
    letter-spacing: 0.02em;
}
</style>
