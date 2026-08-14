<script setup>
import { ref, reactive, onMounted } from 'vue'
import AppIcon from '../components/AppIcon.vue'
import Skeleton from '../components/Skeleton.vue'
import {
    fetchStoryPoints, createStoryPoint, updateStoryPoint, deleteStoryPoint,
    fetchSettings, saveSettings,
} from '../data'
import { toast } from '../composables/useToast'

const loading = ref(true)
const points = ref([])

const copy = reactive({ eyebrow: '', title: '', body1: '', body2: '', badge_year: '', cta_label: '' })
const copyBusy = ref(false)
const copyErrors = ref({})

async function load() {
    loading.value = true
    try {
        const [p, settings] = await Promise.all([fetchStoryPoints(), fetchSettings()])
        points.value = p
        Object.assign(copy, settings.story || {})
    } finally {
        loading.value = false
    }
}
onMounted(load)

async function saveCopy() {
    copyBusy.value = true
    copyErrors.value = {}
    try {
        await saveSettings('story', { ...copy })
        toast.success('Section copy saved.')
    } catch (e) {
        copyErrors.value = e.data?.errors || {}
        if (e.status === 422) toast.error('Please fix the highlighted fields.')
    } finally {
        copyBusy.value = false
    }
}

/* ---- point modal ---- */
const blank = () => ({ text: '', is_published: true, sort_order: 0 })
const showForm = ref(false)
const editingId = ref(null)
const busy = ref(false)
const fieldErrors = ref({})
const form = reactive(blank())

function openCreate() {
    editingId.value = null
    fieldErrors.value = {}
    Object.assign(form, blank(), { sort_order: points.value.length })
    showForm.value = true
}
function openEdit(p) {
    editingId.value = p.id
    fieldErrors.value = {}
    Object.assign(form, { text: p.text, is_published: !!p.is_published, sort_order: p.sort_order || 0 })
    showForm.value = true
}
function closeForm() { if (!busy.value) showForm.value = false }

async function save() {
    busy.value = true
    fieldErrors.value = {}
    try {
        if (editingId.value) {
            await updateStoryPoint(editingId.value, { ...form })
            toast.success('Point updated.')
        } else {
            await createStoryPoint({ ...form })
            toast.success('Point added.')
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

async function togglePublish(p) {
    try {
        await updateStoryPoint(p.id, { ...p, is_published: !p.is_published })
        p.is_published = !p.is_published
    } catch (e) { /* api.js toasts */ }
}

const confirming = ref(null)
async function remove() {
    if (!confirming.value) return
    busy.value = true
    try {
        await deleteStoryPoint(confirming.value.id)
        toast.success('Point deleted.')
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
                <h2>Our Story</h2>
                <p>The “Five Years in the Gardens” heritage section — copy and checklist.</p>
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
                        <label class="field" :class="{ invalid: copyErrors.eyebrow }">
                            <span>Eyebrow</span>
                            <input v-model="copy.eyebrow" type="text" />
                            <em v-if="copyErrors.eyebrow" class="field-msg">{{ copyErrors.eyebrow[0] }}</em>
                        </label>
                        <label class="field" :class="{ invalid: copyErrors.badge_year }">
                            <span>Badge year</span>
                            <input v-model="copy.badge_year" type="text" placeholder="2021" />
                            <em v-if="copyErrors.badge_year" class="field-msg">{{ copyErrors.badge_year[0] }}</em>
                        </label>
                    </div>
                    <label class="field" :class="{ invalid: copyErrors.title }">
                        <span>Title <em class="fmt-hint">press Enter for a line break</em></span>
                        <textarea v-model="copy.title" rows="2" />
                        <em v-if="copyErrors.title" class="field-msg">{{ copyErrors.title[0] }}</em>
                    </label>
                    <label class="field" :class="{ invalid: copyErrors.body1 }">
                        <span>Paragraph 1 <em class="fmt-hint">wrap a word in *asterisks* for italic</em></span>
                        <textarea v-model="copy.body1" rows="3" />
                        <em v-if="copyErrors.body1" class="field-msg">{{ copyErrors.body1[0] }}</em>
                    </label>
                    <label class="field" :class="{ invalid: copyErrors.body2 }">
                        <span>Paragraph 2 <em class="fmt-hint">wrap a word in *asterisks* for italic</em></span>
                        <textarea v-model="copy.body2" rows="3" />
                        <em v-if="copyErrors.body2" class="field-msg">{{ copyErrors.body2[0] }}</em>
                    </label>
                    <label class="field">
                        <span>Button label</span>
                        <input v-model="copy.cta_label" type="text" placeholder="Discover Our Teas" />
                    </label>
                </div>
            </section>

            <section class="card blk">
                <header class="blk-head">
                    <h3>Checklist points <span class="count">{{ points.length }}</span></h3>
                    <button class="btn btn-primary" @click="openCreate"><AppIcon name="plus" :size="16" /> Add Point</button>
                </header>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th style="width: 40px"></th><th>Text</th><th>Live</th><th></th></tr></thead>
                        <tbody>
                            <tr v-for="p in points" :key="p.id">
                                <td><span style="color: var(--gold); font-weight: 700">✓</span></td>
                                <td>{{ p.text }}</td>
                                <td><button class="switch sm" :class="{ on: p.is_published }" @click="togglePublish(p)" /></td>
                                <td><div class="row-actions">
                                    <button title="Edit" @click="openEdit(p)"><AppIcon name="edit" :size="16" /></button>
                                    <button title="Delete" @click="confirming = p"><AppIcon name="trash" :size="16" /></button>
                                </div></td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-if="!points.length" class="empty">No points yet.</p>
                </div>
            </section>
        </template>

        <Transition name="modal">
            <div v-if="showForm" class="modal-wrap" @click.self="closeForm">
                <div class="modal">
                    <header class="modal-head">
                        <h3>{{ editingId ? 'Edit point' : 'Add point' }}</h3>
                        <button class="modal-x" aria-label="Close" @click="closeForm">
                            <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                        </button>
                    </header>
                    <div class="modal-body">
                        <label class="field" :class="{ invalid: fieldErrors.text }">
                            <span>Text</span>
                            <textarea v-model="form.text" rows="3" />
                            <em v-if="fieldErrors.text" class="field-msg">{{ fieldErrors.text[0] }}</em>
                        </label>
                        <label class="field">
                            <span>Sort order</span>
                            <input v-model.number="form.sort_order" type="number" min="0" />
                        </label>
                        <div class="modal-toggles">
                            <label class="mini-check"><input v-model="form.is_published" type="checkbox" /> <span>Show on storefront</span></label>
                        </div>
                    </div>
                    <footer class="modal-foot">
                        <button class="btn btn-ghost" :disabled="busy" @click="closeForm">Cancel</button>
                        <button class="btn btn-primary" :disabled="busy" @click="save">{{ busy ? 'Saving…' : editingId ? 'Save changes' : 'Add point' }}</button>
                    </footer>
                </div>
            </div>
        </Transition>

        <Transition name="modal">
            <div v-if="confirming" class="modal-wrap" @click.self="confirming = null">
                <div class="modal modal-sm">
                    <div class="modal-body confirm-body">
                        <span class="confirm-ico"><AppIcon name="trash" :size="22" /></span>
                        <h3>Delete this point?</h3>
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
.fmt-hint {
    font-weight: 400; font-style: normal; color: var(--muted); font-size: 0.78rem; margin-left: 0.4rem;
}
</style>
