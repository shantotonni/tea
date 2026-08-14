<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import AppIcon from '../components/AppIcon.vue'
import StatCard from '../components/StatCard.vue'
import Skeleton from '../components/Skeleton.vue'
import { fetchProcess, createStep, updateStep, deleteStep } from '../data'
import { toast } from '../composables/useToast'

const steps = ref([])
const loading = ref(true)

async function load() {
    loading.value = true
    try {
        steps.value = await fetchProcess()
    } finally {
        loading.value = false
    }
}
onMounted(load)

const published = computed(() => steps.value.filter((s) => s.is_published).length)

/* ---- modal ---- */
const blank = () => ({ num: '', title: '', text: '', is_published: true, sort_order: 0 })
const showForm = ref(false)
const editingId = ref(null)
const busy = ref(false)
const fieldErrors = ref({})
const form = reactive(blank())

function openCreate() {
    editingId.value = null
    fieldErrors.value = {}
    Object.assign(form, blank(), { num: String(steps.value.length + 1).padStart(2, '0'), sort_order: steps.value.length })
    showForm.value = true
}
function openEdit(s) {
    editingId.value = s.id
    fieldErrors.value = {}
    Object.assign(form, { num: s.num, title: s.title, text: s.text, is_published: !!s.is_published, sort_order: s.sort_order || 0 })
    showForm.value = true
}
function closeForm() {
    if (!busy.value) showForm.value = false
}

async function save() {
    busy.value = true
    fieldErrors.value = {}
    try {
        if (editingId.value) {
            await updateStep(editingId.value, { ...form })
            toast.success('Step updated.')
        } else {
            await createStep({ ...form })
            toast.success('Step added.')
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

async function togglePublish(s) {
    try {
        await updateStep(s.id, { ...s, is_published: !s.is_published })
        s.is_published = !s.is_published
        toast.success(s.is_published ? 'Published.' : 'Hidden.')
    } catch (e) {
        // api.js toasts
    }
}

const confirming = ref(null)
async function remove() {
    const s = confirming.value
    if (!s) return
    busy.value = true
    try {
        await deleteStep(s.id)
        toast.success('Step deleted.')
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
                <h2>Process</h2>
                <p>The “From Leaf to Cup” steps shown on the storefront.</p>
            </div>
            <div class="head-actions">
                <button class="btn btn-primary" @click="openCreate"><AppIcon name="plus" :size="17" /> Add Step</button>
            </div>
        </div>

        <div class="stat-grid">
            <StatCard label="Total Steps" :value="String(steps.length)" icon="leaf" foot="live from database" />
            <StatCard label="Published" :value="String(published)" icon="check" tone="info" />
        </div>

        <section class="card">
            <div v-if="loading" style="padding: 1.4rem"><Skeleton :rows="4" /></div>

            <div v-else class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 60px">Step</th>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Live</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="s in steps" :key="s.id">
                            <td><strong style="font-family: var(--serif); font-size: 1.2rem; color: var(--gold)">{{ s.num }}</strong></td>
                            <td><strong>{{ s.title }}</strong></td>
                            <td><span class="rev-clip" style="max-width: 500px">{{ s.text }}</span></td>
                            <td><button class="switch sm" :class="{ on: s.is_published }" @click="togglePublish(s)" /></td>
                            <td>
                                <div class="row-actions">
                                    <button title="Edit" @click="openEdit(s)"><AppIcon name="edit" :size="16" /></button>
                                    <button title="Delete" @click="confirming = s"><AppIcon name="trash" :size="16" /></button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p v-if="!steps.length" class="empty">No steps yet.</p>
            </div>
        </section>

        <!-- create / edit -->
        <Transition name="modal">
            <div v-if="showForm" class="modal-wrap" @click.self="closeForm">
                <div class="modal">
                    <header class="modal-head">
                        <h3>{{ editingId ? 'Edit step' : 'Add step' }}</h3>
                        <button class="modal-x" aria-label="Close" @click="closeForm">
                            <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                        </button>
                    </header>
                    <div class="modal-body">
                        <div class="form-grid">
                            <label class="field" :class="{ invalid: fieldErrors.num }">
                                <span>Step number</span>
                                <input v-model="form.num" type="text" placeholder="01" />
                                <em v-if="fieldErrors.num" class="field-msg">{{ fieldErrors.num[0] }}</em>
                            </label>
                            <label class="field">
                                <span>Sort order</span>
                                <input v-model.number="form.sort_order" type="number" min="0" />
                            </label>
                        </div>
                        <label class="field" :class="{ invalid: fieldErrors.title }">
                            <span>Title</span>
                            <input v-model="form.title" type="text" placeholder="Hand Plucking" />
                            <em v-if="fieldErrors.title" class="field-msg">{{ fieldErrors.title[0] }}</em>
                        </label>
                        <label class="field" :class="{ invalid: fieldErrors.text }">
                            <span>Description</span>
                            <textarea v-model="form.text" rows="3" />
                            <em v-if="fieldErrors.text" class="field-msg">{{ fieldErrors.text[0] }}</em>
                        </label>
                        <div class="modal-toggles">
                            <label class="mini-check"><input v-model="form.is_published" type="checkbox" /> <span>Show on storefront</span></label>
                        </div>
                    </div>
                    <footer class="modal-foot">
                        <button class="btn btn-ghost" :disabled="busy" @click="closeForm">Cancel</button>
                        <button class="btn btn-primary" :disabled="busy" @click="save">
                            {{ busy ? 'Saving…' : editingId ? 'Save changes' : 'Add step' }}
                        </button>
                    </footer>
                </div>
            </div>
        </Transition>

        <!-- delete -->
        <Transition name="modal">
            <div v-if="confirming" class="modal-wrap" @click.self="confirming = null">
                <div class="modal modal-sm">
                    <div class="modal-body confirm-body">
                        <span class="confirm-ico"><AppIcon name="trash" :size="22" /></span>
                        <h3>Delete this step?</h3>
                        <p>“{{ confirming.title }}” — this cannot be undone.</p>
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
