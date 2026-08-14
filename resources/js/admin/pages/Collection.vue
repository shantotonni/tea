<script setup>
import { ref, reactive, onMounted } from 'vue'
import AppIcon from '../components/AppIcon.vue'
import Skeleton from '../components/Skeleton.vue'
import {
    fetchCollectionNotes, createCollectionNote, updateCollectionNote, deleteCollectionNote,
    fetchSettings, saveSettings,
} from '../data'
import { toast } from '../composables/useToast'

const loading = ref(true)
const notes = ref([])
const copy = reactive({ eyebrow: '', title: '', lead: '' })
const copyBusy = ref(false)
const copyErrors = ref({})

async function load() {
    loading.value = true
    try {
        const [n, settings] = await Promise.all([fetchCollectionNotes(), fetchSettings()])
        notes.value = n
        Object.assign(copy, settings.collection || {})
    } finally { loading.value = false }
}
onMounted(load)

async function saveCopy() {
    copyBusy.value = true; copyErrors.value = {}
    try { await saveSettings('collection', { ...copy }); toast.success('Section copy saved.') }
    catch (e) { copyErrors.value = e.data?.errors || {}; if (e.status === 422) toast.error('Please fix the highlighted fields.') }
    finally { copyBusy.value = false }
}

const blank = () => ({ icon: '', label: '', is_published: true, sort_order: 0 })
const showForm = ref(false)
const editingId = ref(null)
const busy = ref(false)
const fieldErrors = ref({})
const form = reactive(blank())

function openCreate() { editingId.value = null; fieldErrors.value = {}; Object.assign(form, blank(), { sort_order: notes.value.length }); showForm.value = true }
function openEdit(n) { editingId.value = n.id; fieldErrors.value = {}; Object.assign(form, { icon: n.icon || '', label: n.label, is_published: !!n.is_published, sort_order: n.sort_order || 0 }); showForm.value = true }
function closeForm() { if (!busy.value) showForm.value = false }

async function save() {
    busy.value = true; fieldErrors.value = {}
    try {
        if (editingId.value) { await updateCollectionNote(editingId.value, { ...form }); toast.success('Note updated.') }
        else { await createCollectionNote({ ...form }); toast.success('Note added.') }
        showForm.value = false; await load()
    } catch (e) { fieldErrors.value = e.data?.errors || {}; if (e.status === 422) toast.error('Please fix the highlighted fields.') }
    finally { busy.value = false }
}
async function togglePublish(n) { try { await updateCollectionNote(n.id, { ...n, is_published: !n.is_published }); n.is_published = !n.is_published } catch (e) { /* toasts */ } }
const confirming = ref(null)
async function remove() {
    if (!confirming.value) return
    busy.value = true
    try { await deleteCollectionNote(confirming.value.id); toast.success('Note deleted.'); confirming.value = null; await load() }
    finally { busy.value = false }
}
</script>

<template>
    <div class="page">
        <div class="page-head">
            <div>
                <p class="eyebrow">Storefront</p>
                <h2>Collection Section</h2>
                <p>The “Signature Collection” heading and the trust-note strip below the products. (Products themselves are managed under <RouterLink to="/admin/products">Products</RouterLink>.)</p>
            </div>
        </div>

        <div v-if="loading" class="card" style="padding: 1.4rem"><Skeleton :rows="5" /></div>

        <template v-else>
            <section class="card blk">
                <header class="blk-head">
                    <h3>Section copy</h3>
                    <button class="btn btn-primary" :disabled="copyBusy" @click="saveCopy">{{ copyBusy ? 'Saving…' : 'Save copy' }}</button>
                </header>
                <div class="blk-body">
                    <label class="field" :class="{ invalid: copyErrors.eyebrow }"><span>Eyebrow</span><input v-model="copy.eyebrow" type="text" /><em v-if="copyErrors.eyebrow" class="field-msg">{{ copyErrors.eyebrow[0] }}</em></label>
                    <label class="field" :class="{ invalid: copyErrors.title }"><span>Title</span><input v-model="copy.title" type="text" /><em v-if="copyErrors.title" class="field-msg">{{ copyErrors.title[0] }}</em></label>
                    <label class="field"><span>Lead</span><textarea v-model="copy.lead" rows="2" /></label>
                </div>
            </section>

            <section class="card blk">
                <header class="blk-head">
                    <h3>Trust notes <span class="count">{{ notes.length }}</span></h3>
                    <button class="btn btn-primary" @click="openCreate"><AppIcon name="plus" :size="16" /> Add Note</button>
                </header>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th style="width: 60px">Icon</th><th>Label</th><th>Live</th><th></th></tr></thead>
                        <tbody>
                            <tr v-for="n in notes" :key="n.id">
                                <td style="font-size: 1.3rem">{{ n.icon }}</td>
                                <td><strong>{{ n.label }}</strong></td>
                                <td><button class="switch sm" :class="{ on: n.is_published }" @click="togglePublish(n)" /></td>
                                <td><div class="row-actions">
                                    <button title="Edit" @click="openEdit(n)"><AppIcon name="edit" :size="16" /></button>
                                    <button title="Delete" @click="confirming = n"><AppIcon name="trash" :size="16" /></button>
                                </div></td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-if="!notes.length" class="empty">No notes yet.</p>
                </div>
            </section>
        </template>

        <Transition name="modal">
            <div v-if="showForm" class="modal-wrap" @click.self="closeForm">
                <div class="modal modal-sm">
                    <header class="modal-head">
                        <h3>{{ editingId ? 'Edit note' : 'Add note' }}</h3>
                        <button class="modal-x" aria-label="Close" @click="closeForm"><svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg></button>
                    </header>
                    <div class="modal-body">
                        <div class="form-grid">
                            <label class="field"><span>Icon (emoji)</span><input v-model="form.icon" type="text" maxlength="4" placeholder="🚚" /></label>
                            <label class="field"><span>Sort order</span><input v-model.number="form.sort_order" type="number" min="0" /></label>
                        </div>
                        <label class="field" :class="{ invalid: fieldErrors.label }"><span>Label</span><input v-model="form.label" type="text" placeholder="Cash on delivery" /><em v-if="fieldErrors.label" class="field-msg">{{ fieldErrors.label[0] }}</em></label>
                        <div class="modal-toggles">
                            <label class="mini-check"><input v-model="form.is_published" type="checkbox" /> <span>Show on storefront</span></label>
                        </div>
                    </div>
                    <footer class="modal-foot">
                        <button class="btn btn-ghost" :disabled="busy" @click="closeForm">Cancel</button>
                        <button class="btn btn-primary" :disabled="busy" @click="save">{{ busy ? 'Saving…' : editingId ? 'Save changes' : 'Add note' }}</button>
                    </footer>
                </div>
            </div>
        </Transition>

        <Transition name="modal">
            <div v-if="confirming" class="modal-wrap" @click.self="confirming = null">
                <div class="modal modal-sm">
                    <div class="modal-body confirm-body">
                        <span class="confirm-ico"><AppIcon name="trash" :size="22" /></span>
                        <h3>Delete this note?</h3>
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
.blk { margin-bottom: 1.4rem; }
.blk-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.1rem 1.4rem; border-bottom: 1px solid var(--line, #eee); }
.blk-head h3 { margin: 0; font-size: 1.05rem; display: flex; align-items: center; gap: 0.5rem; }
.blk-body { padding: 1.2rem 1.4rem; display: grid; gap: 1rem; }
.count { font-size: 0.75rem; font-weight: 600; color: var(--muted); background: var(--soft, #f3f3f3); padding: 0.1rem 0.5rem; border-radius: 999px; }
</style>
