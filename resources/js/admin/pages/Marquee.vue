<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import AppIcon from '../components/AppIcon.vue'
import StatCard from '../components/StatCard.vue'
import Skeleton from '../components/Skeleton.vue'
import { fetchMarquee, createMarquee, updateMarquee, deleteMarquee } from '../data'
import { toast } from '../composables/useToast'

const items = ref([])
const loading = ref(true)

async function load() {
    loading.value = true
    try {
        items.value = await fetchMarquee()
    } finally {
        loading.value = false
    }
}
onMounted(load)

const published = computed(() => items.value.filter((i) => i.is_published).length)

const blank = () => ({ label: '', is_published: true, sort_order: 0 })
const showForm = ref(false)
const editingId = ref(null)
const busy = ref(false)
const fieldErrors = ref({})
const form = reactive(blank())

function openCreate() {
    editingId.value = null
    fieldErrors.value = {}
    Object.assign(form, blank(), { sort_order: items.value.length })
    showForm.value = true
}
function openEdit(i) {
    editingId.value = i.id
    fieldErrors.value = {}
    Object.assign(form, { label: i.label, is_published: !!i.is_published, sort_order: i.sort_order || 0 })
    showForm.value = true
}
function closeForm() { if (!busy.value) showForm.value = false }

async function save() {
    busy.value = true
    fieldErrors.value = {}
    try {
        if (editingId.value) {
            await updateMarquee(editingId.value, { ...form })
            toast.success('Item updated.')
        } else {
            await createMarquee({ ...form })
            toast.success('Item added.')
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

async function togglePublish(i) {
    try {
        await updateMarquee(i.id, { ...i, is_published: !i.is_published })
        i.is_published = !i.is_published
    } catch (e) { /* api.js toasts */ }
}

const confirming = ref(null)
async function remove() {
    if (!confirming.value) return
    busy.value = true
    try {
        await deleteMarquee(confirming.value.id)
        toast.success('Item deleted.')
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
                <h2>Marquee Strip</h2>
                <p>The scrolling tea keywords ribbon beneath the hero.</p>
            </div>
            <div class="head-actions">
                <button class="btn btn-primary" @click="openCreate"><AppIcon name="plus" :size="17" /> Add Item</button>
            </div>
        </div>

        <div class="stat-grid">
            <StatCard label="Total Items" :value="String(items.length)" icon="edit" foot="live from database" />
            <StatCard label="Published" :value="String(published)" icon="check" tone="info" />
        </div>

        <section class="card">
            <div v-if="loading" style="padding: 1.4rem"><Skeleton :rows="4" /></div>
            <div v-else class="table-wrap">
                <table>
                    <thead><tr><th style="width: 60px">#</th><th>Label</th><th>Live</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="(i, idx) in items" :key="i.id">
                            <td><span style="color: var(--muted)">{{ idx + 1 }}</span></td>
                            <td><strong>{{ i.label }}</strong></td>
                            <td><button class="switch sm" :class="{ on: i.is_published }" @click="togglePublish(i)" /></td>
                            <td><div class="row-actions">
                                <button title="Edit" @click="openEdit(i)"><AppIcon name="edit" :size="16" /></button>
                                <button title="Delete" @click="confirming = i"><AppIcon name="trash" :size="16" /></button>
                            </div></td>
                        </tr>
                    </tbody>
                </table>
                <p v-if="!items.length" class="empty">No items yet.</p>
            </div>
        </section>

        <Transition name="modal">
            <div v-if="showForm" class="modal-wrap" @click.self="closeForm">
                <div class="modal modal-sm">
                    <header class="modal-head">
                        <h3>{{ editingId ? 'Edit item' : 'Add item' }}</h3>
                        <button class="modal-x" aria-label="Close" @click="closeForm">
                            <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                        </button>
                    </header>
                    <div class="modal-body">
                        <label class="field" :class="{ invalid: fieldErrors.label }">
                            <span>Label</span>
                            <input v-model="form.label" type="text" placeholder="Green Tea" />
                            <em v-if="fieldErrors.label" class="field-msg">{{ fieldErrors.label[0] }}</em>
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
                        <button class="btn btn-primary" :disabled="busy" @click="save">{{ busy ? 'Saving…' : editingId ? 'Save changes' : 'Add item' }}</button>
                    </footer>
                </div>
            </div>
        </Transition>

        <Transition name="modal">
            <div v-if="confirming" class="modal-wrap" @click.self="confirming = null">
                <div class="modal modal-sm">
                    <div class="modal-body confirm-body">
                        <span class="confirm-ico"><AppIcon name="trash" :size="22" /></span>
                        <h3>Delete this item?</h3>
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
