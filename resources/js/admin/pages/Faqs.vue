<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import AppIcon from '../components/AppIcon.vue'
import StatCard from '../components/StatCard.vue'
import Skeleton from '../components/Skeleton.vue'
import { fetchFaqs, createFaq, updateFaq, deleteFaq } from '../data'
import { toast } from '../composables/useToast'

const faqs = ref([])
const loading = ref(true)
const query = ref('')

async function load() {
    loading.value = true
    try {
        faqs.value = await fetchFaqs()
    } finally {
        loading.value = false
    }
}
onMounted(load)

const rows = computed(() => {
    const q = query.value.trim().toLowerCase()
    if (!q) return faqs.value
    return faqs.value.filter(
        (f) => f.question.toLowerCase().includes(q) || f.answer.toLowerCase().includes(q)
    )
})

const published = computed(() => faqs.value.filter((f) => f.is_published).length)

/* ---- modal ---- */
const blank = () => ({ question: '', answer: '', is_published: true, sort_order: 0 })
const showForm = ref(false)
const editingId = ref(null)
const busy = ref(false)
const fieldErrors = ref({})
const form = reactive(blank())

function openCreate() {
    editingId.value = null
    fieldErrors.value = {}
    Object.assign(form, blank())
    showForm.value = true
}
function openEdit(f) {
    editingId.value = f.id
    fieldErrors.value = {}
    Object.assign(form, {
        question: f.question, answer: f.answer,
        is_published: !!f.is_published, sort_order: f.sort_order || 0,
    })
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
            await updateFaq(editingId.value, { ...form })
            toast.success('FAQ updated.')
        } else {
            await createFaq({ ...form })
            toast.success('FAQ added.')
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

async function togglePublish(f) {
    try {
        await updateFaq(f.id, { ...f, is_published: !f.is_published })
        f.is_published = !f.is_published
        toast.success(f.is_published ? 'Published.' : 'Hidden.')
    } catch (e) {
        // api.js toasts
    }
}

const confirming = ref(null)
async function remove() {
    const f = confirming.value
    if (!f) return
    busy.value = true
    try {
        await deleteFaq(f.id)
        toast.success('FAQ deleted.')
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
                <h2>FAQ</h2>
                <p>Questions and answers shown in the storefront FAQ section.</p>
            </div>
            <div class="head-actions">
                <button class="btn btn-primary" @click="openCreate"><AppIcon name="plus" :size="17" /> Add Question</button>
            </div>
        </div>

        <div class="stat-grid">
            <StatCard label="Total Questions" :value="String(faqs.length)" icon="settings" foot="live from database" />
            <StatCard label="Published" :value="String(published)" icon="check" tone="info" />
            <StatCard label="Hidden" :value="String(faqs.length - published)" icon="warn" tone="gold" />
        </div>

        <section class="card">
            <div class="toolbar">
                <span style="font-size: 0.85rem; color: var(--muted)">{{ rows.length }} question{{ rows.length === 1 ? '' : 's' }}</span>
                <div class="search">
                    <AppIcon name="search" :size="16" />
                    <input v-model="query" type="search" placeholder="Search questions…" />
                </div>
            </div>

            <div v-if="loading" style="padding: 1.4rem"><Skeleton :rows="6" /></div>

            <div v-else class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 46px">#</th>
                            <th>Question</th>
                            <th>Live</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(f, i) in rows" :key="f.id">
                            <td><strong>{{ String(i + 1).padStart(2, '0') }}</strong></td>
                            <td>
                                <strong>{{ f.question }}</strong>
                                <span class="rev-clip" style="max-width: 560px; margin-top: 0.2rem">{{ f.answer }}</span>
                            </td>
                            <td><button class="switch sm" :class="{ on: f.is_published }" @click="togglePublish(f)" /></td>
                            <td>
                                <div class="row-actions">
                                    <button title="Edit" @click="openEdit(f)"><AppIcon name="edit" :size="16" /></button>
                                    <button title="Delete" @click="confirming = f"><AppIcon name="trash" :size="16" /></button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p v-if="!rows.length" class="empty">No questions found.</p>
            </div>
        </section>

        <!-- create / edit -->
        <Transition name="modal">
            <div v-if="showForm" class="modal-wrap" @click.self="closeForm">
                <div class="modal">
                    <header class="modal-head">
                        <h3>{{ editingId ? 'Edit question' : 'Add question' }}</h3>
                        <button class="modal-x" aria-label="Close" @click="closeForm">
                            <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                        </button>
                    </header>
                    <div class="modal-body">
                        <label class="field" :class="{ invalid: fieldErrors.question }">
                            <span>Question</span>
                            <input v-model="form.question" type="text" />
                            <em v-if="fieldErrors.question" class="field-msg">{{ fieldErrors.question[0] }}</em>
                        </label>
                        <label class="field" :class="{ invalid: fieldErrors.answer }">
                            <span>Answer</span>
                            <textarea v-model="form.answer" rows="5" />
                            <em v-if="fieldErrors.answer" class="field-msg">{{ fieldErrors.answer[0] }}</em>
                        </label>
                        <div class="form-grid">
                            <label class="field">
                                <span>Sort order</span>
                                <input v-model.number="form.sort_order" type="number" min="0" />
                            </label>
                            <div class="modal-toggles" style="align-content: end">
                                <label class="mini-check"><input v-model="form.is_published" type="checkbox" /> <span>Show on storefront</span></label>
                            </div>
                        </div>
                    </div>
                    <footer class="modal-foot">
                        <button class="btn btn-ghost" :disabled="busy" @click="closeForm">Cancel</button>
                        <button class="btn btn-primary" :disabled="busy" @click="save">
                            {{ busy ? 'Saving…' : editingId ? 'Save changes' : 'Add question' }}
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
                        <h3>Delete this question?</h3>
                        <p>“{{ confirming.question }}” — this cannot be undone.</p>
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
