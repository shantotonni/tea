<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import AppIcon from '../components/AppIcon.vue'
import StatCard from '../components/StatCard.vue'
import Skeleton from '../components/Skeleton.vue'
import { fetchQuotes, createQuote, updateQuote, deleteQuote } from '../data'
import { toast } from '../composables/useToast'

const allQuotes = ref([])
const loading = ref(true)
const tabFilter = ref('') // '', 'wisdom', 'health'

async function load() {
    loading.value = true
    try {
        allQuotes.value = await fetchQuotes()
    } finally {
        loading.value = false
    }
}
onMounted(load)

const quotes = computed(() =>
    tabFilter.value ? allQuotes.value.filter((q) => q.tab === tabFilter.value) : allQuotes.value
)
const wisdomCount = computed(() => allQuotes.value.filter((q) => q.tab === 'wisdom').length)
const healthCount = computed(() => allQuotes.value.filter((q) => q.tab === 'health').length)

/* ---- modal ---- */
const blank = () => ({ tab: 'wisdom', text: '', author: '', title: '', is_published: true, sort_order: 0 })
const showForm = ref(false)
const editingId = ref(null)
const busy = ref(false)
const fieldErrors = ref({})
const form = reactive(blank())

function openCreate() {
    editingId.value = null
    fieldErrors.value = {}
    Object.assign(form, blank(), { tab: tabFilter.value || 'wisdom', sort_order: quotes.value.length })
    showForm.value = true
}
function openEdit(q) {
    editingId.value = q.id
    fieldErrors.value = {}
    Object.assign(form, { tab: q.tab, text: q.text, author: q.author, title: q.title || '', is_published: !!q.is_published, sort_order: q.sort_order || 0 })
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
            await updateQuote(editingId.value, { ...form })
            toast.success('Quote updated.')
        } else {
            await createQuote({ ...form })
            toast.success('Quote added.')
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

async function togglePublish(q) {
    try {
        await updateQuote(q.id, { ...q, is_published: !q.is_published })
        q.is_published = !q.is_published
        toast.success(q.is_published ? 'Published.' : 'Hidden.')
    } catch (e) {
        // api.js toasts
    }
}

const confirming = ref(null)
async function remove() {
    const q = confirming.value
    if (!q) return
    busy.value = true
    try {
        await deleteQuote(q.id)
        toast.success('Quote deleted.')
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
                <h2>Quotes</h2>
                <p>The rotating quotes shown in the “Wisdom &amp; Health” feature band.</p>
            </div>
            <div class="head-actions">
                <button class="btn btn-primary" @click="openCreate"><AppIcon name="plus" :size="17" /> Add Quote</button>
            </div>
        </div>

        <div class="stat-grid">
            <StatCard label="Total Quotes" :value="String(allQuotes.length)" icon="edit" foot="live from database" />
            <StatCard label="Wisdom" :value="String(wisdomCount)" icon="star" tone="info" />
            <StatCard label="Health" :value="String(healthCount)" icon="check" tone="info" />
        </div>

        <div class="toolbar">
            <button class="chip" :class="{ active: tabFilter === '' }" @click="tabFilter = ''">All</button>
            <button class="chip" :class="{ active: tabFilter === 'wisdom' }" @click="tabFilter = 'wisdom'">📜 Wisdom</button>
            <button class="chip" :class="{ active: tabFilter === 'health' }" @click="tabFilter = 'health'">🩺 Health</button>
        </div>

        <section class="card">
            <div v-if="loading" style="padding: 1.4rem"><Skeleton :rows="4" /></div>

            <div v-else class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 110px">Category</th>
                            <th>Quote</th>
                            <th style="width: 220px">Author</th>
                            <th>Live</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="q in quotes" :key="q.id">
                            <td>
                                <span class="pill" :class="q.tab === 'health' ? 'pill-health' : 'pill-wisdom'">
                                    {{ q.tab === 'health' ? '🩺 Health' : '📜 Wisdom' }}
                                </span>
                            </td>
                            <td><span class="rev-clip" style="max-width: 460px">{{ q.text }}</span></td>
                            <td>
                                <strong>{{ q.author }}</strong>
                                <em v-if="q.title" style="display: block; color: var(--muted); font-style: normal; font-size: 0.8rem">{{ q.title }}</em>
                            </td>
                            <td><button class="switch sm" :class="{ on: q.is_published }" @click="togglePublish(q)" /></td>
                            <td>
                                <div class="row-actions">
                                    <button title="Edit" @click="openEdit(q)"><AppIcon name="edit" :size="16" /></button>
                                    <button title="Delete" @click="confirming = q"><AppIcon name="trash" :size="16" /></button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p v-if="!quotes.length" class="empty">No quotes yet.</p>
            </div>
        </section>

        <!-- create / edit -->
        <Transition name="modal">
            <div v-if="showForm" class="modal-wrap" @click.self="closeForm">
                <div class="modal">
                    <header class="modal-head">
                        <h3>{{ editingId ? 'Edit quote' : 'Add quote' }}</h3>
                        <button class="modal-x" aria-label="Close" @click="closeForm">
                            <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                        </button>
                    </header>
                    <div class="modal-body">
                        <div class="form-grid">
                            <label class="field" :class="{ invalid: fieldErrors.tab }">
                                <span>Category</span>
                                <select v-model="form.tab">
                                    <option value="wisdom">📜 Wisdom of Scholars &amp; Poets</option>
                                    <option value="health">🩺 Medical &amp; Health Insights</option>
                                </select>
                                <em v-if="fieldErrors.tab" class="field-msg">{{ fieldErrors.tab[0] }}</em>
                            </label>
                            <label class="field">
                                <span>Sort order</span>
                                <input v-model.number="form.sort_order" type="number" min="0" />
                            </label>
                        </div>
                        <label class="field" :class="{ invalid: fieldErrors.text }">
                            <span>Quote text</span>
                            <textarea v-model="form.text" rows="4" placeholder="Teaism is a religion of the art of life…" />
                            <em v-if="fieldErrors.text" class="field-msg">{{ fieldErrors.text[0] }}</em>
                        </label>
                        <label class="field" :class="{ invalid: fieldErrors.author }">
                            <span>Author</span>
                            <input v-model="form.author" type="text" placeholder="Okakura Kakuzo" />
                            <em v-if="fieldErrors.author" class="field-msg">{{ fieldErrors.author[0] }}</em>
                        </label>
                        <label class="field" :class="{ invalid: fieldErrors.title }">
                            <span>Author title / credential <em style="color: var(--muted)">(optional)</em></span>
                            <input v-model="form.title" type="text" placeholder="Author of &quot;The Book of Tea&quot; (1906)" />
                            <em v-if="fieldErrors.title" class="field-msg">{{ fieldErrors.title[0] }}</em>
                        </label>
                        <div class="modal-toggles">
                            <label class="mini-check"><input v-model="form.is_published" type="checkbox" /> <span>Show on storefront</span></label>
                        </div>
                    </div>
                    <footer class="modal-foot">
                        <button class="btn btn-ghost" :disabled="busy" @click="closeForm">Cancel</button>
                        <button class="btn btn-primary" :disabled="busy" @click="save">
                            {{ busy ? 'Saving…' : editingId ? 'Save changes' : 'Add quote' }}
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
                        <h3>Delete this quote?</h3>
                        <p>“{{ confirming.author }}” — this cannot be undone.</p>
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
.pill {
    display: inline-block;
    padding: 0.25rem 0.6rem;
    border-radius: 999px;
    font-size: 0.78rem;
    font-weight: 600;
    white-space: nowrap;
}
.pill-wisdom {
    background: color-mix(in srgb, var(--gold) 16%, transparent);
    color: var(--gold-ink, #8a6d1f);
}
.pill-health {
    background: color-mix(in srgb, var(--brand, #2c6b45) 14%, transparent);
    color: var(--brand, #2c6b45);
}
</style>
