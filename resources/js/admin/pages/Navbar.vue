<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import AppIcon from '../components/AppIcon.vue'
import StatCard from '../components/StatCard.vue'
import Skeleton from '../components/Skeleton.vue'
import { fetchNavLinks, createNavLink, updateNavLink, deleteNavLink } from '../data'
import { toast } from '../composables/useToast'

const links = ref([])
const loading = ref(true)

async function load() {
    loading.value = true
    try {
        links.value = await fetchNavLinks()
    } finally {
        loading.value = false
    }
}
onMounted(load)

const published = computed(() => links.value.filter((l) => l.is_published).length)

const blank = () => ({ label: '', target: '', is_cta: false, is_published: true, sort_order: 0 })
const showForm = ref(false)
const editingId = ref(null)
const busy = ref(false)
const fieldErrors = ref({})
const form = reactive(blank())

function openCreate() {
    editingId.value = null
    fieldErrors.value = {}
    Object.assign(form, blank(), { sort_order: links.value.length })
    showForm.value = true
}
function openEdit(l) {
    editingId.value = l.id
    fieldErrors.value = {}
    Object.assign(form, { label: l.label, target: l.target, is_cta: !!l.is_cta, is_published: !!l.is_published, sort_order: l.sort_order || 0 })
    showForm.value = true
}
function closeForm() { if (!busy.value) showForm.value = false }

async function save() {
    busy.value = true
    fieldErrors.value = {}
    try {
        if (editingId.value) {
            await updateNavLink(editingId.value, { ...form })
            toast.success('Link updated.')
        } else {
            await createNavLink({ ...form })
            toast.success('Link added.')
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

async function togglePublish(l) {
    try {
        await updateNavLink(l.id, { ...l, is_published: !l.is_published })
        l.is_published = !l.is_published
    } catch (e) { /* api.js toasts */ }
}

const confirming = ref(null)
async function remove() {
    if (!confirming.value) return
    busy.value = true
    try {
        await deleteNavLink(confirming.value.id)
        toast.success('Link deleted.')
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
                <h2>Navbar</h2>
                <p>The top navigation menu links. Target = a homepage section id (e.g. <code>collection</code>) or a path.</p>
            </div>
            <div class="head-actions">
                <button class="btn btn-primary" @click="openCreate"><AppIcon name="plus" :size="17" /> Add Link</button>
            </div>
        </div>

        <div class="stat-grid">
            <StatCard label="Total Links" :value="String(links.length)" icon="edit" foot="live from database" />
            <StatCard label="Published" :value="String(published)" icon="check" tone="info" />
        </div>

        <section class="card">
            <div v-if="loading" style="padding: 1.4rem"><Skeleton :rows="4" /></div>
            <div v-else class="table-wrap">
                <table>
                    <thead><tr><th style="width: 60px">#</th><th>Label</th><th>Target</th><th>CTA</th><th>Live</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="(l, idx) in links" :key="l.id">
                            <td><span style="color: var(--muted)">{{ idx + 1 }}</span></td>
                            <td><strong>{{ l.label }}</strong></td>
                            <td><code>{{ l.target }}</code></td>
                            <td><span v-if="l.is_cta" class="tag-cta">Button</span></td>
                            <td><button class="switch sm" :class="{ on: l.is_published }" @click="togglePublish(l)" /></td>
                            <td><div class="row-actions">
                                <button title="Edit" @click="openEdit(l)"><AppIcon name="edit" :size="16" /></button>
                                <button title="Delete" @click="confirming = l"><AppIcon name="trash" :size="16" /></button>
                            </div></td>
                        </tr>
                    </tbody>
                </table>
                <p v-if="!links.length" class="empty">No links yet.</p>
            </div>
        </section>

        <Transition name="modal">
            <div v-if="showForm" class="modal-wrap" @click.self="closeForm">
                <div class="modal">
                    <header class="modal-head">
                        <h3>{{ editingId ? 'Edit link' : 'Add link' }}</h3>
                        <button class="modal-x" aria-label="Close" @click="closeForm">
                            <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                        </button>
                    </header>
                    <div class="modal-body">
                        <div class="form-grid">
                            <label class="field" :class="{ invalid: fieldErrors.label }">
                                <span>Label</span>
                                <input v-model="form.label" type="text" placeholder="Collection" />
                                <em v-if="fieldErrors.label" class="field-msg">{{ fieldErrors.label[0] }}</em>
                            </label>
                            <label class="field" :class="{ invalid: fieldErrors.target }">
                                <span>Target <em style="color: var(--muted)">(section id / path)</em></span>
                                <input v-model="form.target" type="text" placeholder="collection" />
                                <em v-if="fieldErrors.target" class="field-msg">{{ fieldErrors.target[0] }}</em>
                            </label>
                        </div>
                        <label class="field">
                            <span>Sort order</span>
                            <input v-model.number="form.sort_order" type="number" min="0" />
                        </label>
                        <div class="modal-toggles">
                            <label class="mini-check"><input v-model="form.is_cta" type="checkbox" /> <span>Style as button (CTA)</span></label>
                            <label class="mini-check"><input v-model="form.is_published" type="checkbox" /> <span>Show in navbar</span></label>
                        </div>
                    </div>
                    <footer class="modal-foot">
                        <button class="btn btn-ghost" :disabled="busy" @click="closeForm">Cancel</button>
                        <button class="btn btn-primary" :disabled="busy" @click="save">{{ busy ? 'Saving…' : editingId ? 'Save changes' : 'Add link' }}</button>
                    </footer>
                </div>
            </div>
        </Transition>

        <Transition name="modal">
            <div v-if="confirming" class="modal-wrap" @click.self="confirming = null">
                <div class="modal modal-sm">
                    <div class="modal-body confirm-body">
                        <span class="confirm-ico"><AppIcon name="trash" :size="22" /></span>
                        <h3>Delete this link?</h3>
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
.tag-cta {
    font-size: 0.72rem; font-weight: 600; color: var(--gold-ink, #8a6d1f);
    background: color-mix(in srgb, var(--gold) 18%, transparent); padding: 0.1rem 0.5rem; border-radius: 999px;
}
code { font-size: 0.82rem; color: var(--muted); background: var(--soft, #f3f3f3); padding: 0.05rem 0.4rem; border-radius: 5px; }
</style>
