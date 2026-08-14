<script setup>
import { ref, reactive, onMounted } from 'vue'
import AppIcon from '../components/AppIcon.vue'
import Skeleton from '../components/Skeleton.vue'
import {
    fetchInstaShots, createInstaShot, updateInstaShot, deleteInstaShot,
    fetchSettings, saveSettings,
} from '../data'
import { toast } from '../composables/useToast'

const loading = ref(true)
const shots = ref([])
const copy = reactive({ eyebrow: '', handle: '' })
const copyBusy = ref(false)
const copyErrors = ref({})

async function load() {
    loading.value = true
    try {
        const [s, settings] = await Promise.all([fetchInstaShots(), fetchSettings()])
        shots.value = s
        Object.assign(copy, settings.insta || {})
    } finally { loading.value = false }
}
onMounted(load)

async function saveCopy() {
    copyBusy.value = true; copyErrors.value = {}
    try { await saveSettings('insta', { ...copy }); toast.success('Section copy saved.') }
    catch (e) { copyErrors.value = e.data?.errors || {}; if (e.status === 422) toast.error('Please fix the highlighted fields.') }
    finally { copyBusy.value = false }
}

const blank = () => ({ image: '', caption: '', likes: 0, is_published: true, sort_order: 0 })
const showForm = ref(false)
const editingId = ref(null)
const busy = ref(false)
const fieldErrors = ref({})
const form = reactive(blank())

function openCreate() { editingId.value = null; fieldErrors.value = {}; Object.assign(form, blank(), { sort_order: shots.value.length }); showForm.value = true }
function openEdit(s) { editingId.value = s.id; fieldErrors.value = {}; Object.assign(form, { image: s.image, caption: s.caption || '', likes: s.likes || 0, is_published: !!s.is_published, sort_order: s.sort_order || 0 }); showForm.value = true }
function closeForm() { if (!busy.value) showForm.value = false }

async function save() {
    busy.value = true; fieldErrors.value = {}
    try {
        if (editingId.value) { await updateInstaShot(editingId.value, { ...form }); toast.success('Shot updated.') }
        else { await createInstaShot({ ...form }); toast.success('Shot added.') }
        showForm.value = false; await load()
    } catch (e) { fieldErrors.value = e.data?.errors || {}; if (e.status === 422) toast.error('Please fix the highlighted fields.') }
    finally { busy.value = false }
}
async function togglePublish(s) { try { await updateInstaShot(s.id, { ...s, is_published: !s.is_published }); s.is_published = !s.is_published } catch (e) { /* toasts */ } }
const confirming = ref(null)
async function remove() {
    if (!confirming.value) return
    busy.value = true
    try { await deleteInstaShot(confirming.value.id); toast.success('Shot deleted.'); confirming.value = null; await load() }
    finally { busy.value = false }
}
</script>

<template>
    <div class="page">
        <div class="page-head">
            <div>
                <p class="eyebrow">Storefront</p>
                <h2>Instagram Strip</h2>
                <p>The “@chakunjo” photo grid near the footer.</p>
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
                        <label class="field" :class="{ invalid: copyErrors.handle }"><span>Handle</span><input v-model="copy.handle" type="text" placeholder="@chakunjo" /><em v-if="copyErrors.handle" class="field-msg">{{ copyErrors.handle[0] }}</em></label>
                    </div>
                </div>
            </section>

            <section class="card blk">
                <header class="blk-head">
                    <h3>Photos <span class="count">{{ shots.length }}</span></h3>
                    <button class="btn btn-primary" @click="openCreate"><AppIcon name="plus" :size="16" /> Add Photo</button>
                </header>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th style="width: 70px">Photo</th><th>Caption</th><th style="width: 90px">Likes</th><th>Live</th><th></th></tr></thead>
                        <tbody>
                            <tr v-for="s in shots" :key="s.id">
                                <td><img :src="s.image" alt="" class="thumb" /></td>
                                <td>{{ s.caption }}</td>
                                <td>♥ {{ s.likes }}</td>
                                <td><button class="switch sm" :class="{ on: s.is_published }" @click="togglePublish(s)" /></td>
                                <td><div class="row-actions">
                                    <button title="Edit" @click="openEdit(s)"><AppIcon name="edit" :size="16" /></button>
                                    <button title="Delete" @click="confirming = s"><AppIcon name="trash" :size="16" /></button>
                                </div></td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-if="!shots.length" class="empty">No photos yet.</p>
                </div>
            </section>
        </template>

        <Transition name="modal">
            <div v-if="showForm" class="modal-wrap" @click.self="closeForm">
                <div class="modal">
                    <header class="modal-head">
                        <h3>{{ editingId ? 'Edit photo' : 'Add photo' }}</h3>
                        <button class="modal-x" aria-label="Close" @click="closeForm"><svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg></button>
                    </header>
                    <div class="modal-body">
                        <label class="field" :class="{ invalid: fieldErrors.image }"><span>Image path</span><input v-model="form.image" type="text" placeholder="/images/garden.jpg" /><em v-if="fieldErrors.image" class="field-msg">{{ fieldErrors.image[0] }}</em></label>
                        <label class="field"><span>Caption</span><input v-model="form.caption" type="text" /></label>
                        <div class="form-grid">
                            <label class="field"><span>Likes</span><input v-model.number="form.likes" type="number" min="0" /></label>
                            <label class="field"><span>Sort order</span><input v-model.number="form.sort_order" type="number" min="0" /></label>
                        </div>
                        <div class="modal-toggles">
                            <label class="mini-check"><input v-model="form.is_published" type="checkbox" /> <span>Show on storefront</span></label>
                        </div>
                    </div>
                    <footer class="modal-foot">
                        <button class="btn btn-ghost" :disabled="busy" @click="closeForm">Cancel</button>
                        <button class="btn btn-primary" :disabled="busy" @click="save">{{ busy ? 'Saving…' : editingId ? 'Save changes' : 'Add photo' }}</button>
                    </footer>
                </div>
            </div>
        </Transition>

        <Transition name="modal">
            <div v-if="confirming" class="modal-wrap" @click.self="confirming = null">
                <div class="modal modal-sm">
                    <div class="modal-body confirm-body">
                        <span class="confirm-ico"><AppIcon name="trash" :size="22" /></span>
                        <h3>Delete this photo?</h3>
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
.blk { margin-bottom: 1.4rem; }
.blk-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.1rem 1.4rem; border-bottom: 1px solid var(--line, #eee); }
.blk-head h3 { margin: 0; font-size: 1.05rem; display: flex; align-items: center; gap: 0.5rem; }
.blk-body { padding: 1.2rem 1.4rem; display: grid; gap: 1rem; }
.count { font-size: 0.75rem; font-weight: 600; color: var(--muted); background: var(--soft, #f3f3f3); padding: 0.1rem 0.5rem; border-radius: 999px; }
.thumb { width: 52px; height: 52px; object-fit: cover; border-radius: 6px; }
</style>
