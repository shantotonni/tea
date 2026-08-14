<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import AppIcon from '../components/AppIcon.vue'
import StatCard from '../components/StatCard.vue'
import Skeleton from '../components/Skeleton.vue'
import { fetchPosts, createPost, updatePost, deletePost, asset } from '../data'
import { toast } from '../composables/useToast'

const CATS = [
    { id: 'brewing', label: 'Brewing Guide' },
    { id: 'health', label: 'Health & Wellness' },
    { id: 'garden', label: 'Sreemangal Notes' },
]
const catLabel = (id) => CATS.find((c) => c.id === id)?.label || id

const posts = ref([])
const loading = ref(true)
const query = ref('')
const catFilter = ref('')

async function load() {
    loading.value = true
    try {
        posts.value = await fetchPosts()
    } finally {
        loading.value = false
    }
}
onMounted(load)

const rows = computed(() => {
    const q = query.value.trim().toLowerCase()
    return posts.value.filter((p) => {
        const mc = !catFilter.value || p.category === catFilter.value
        const mq = !q || p.title.toLowerCase().includes(q) || (p.excerpt || '').toLowerCase().includes(q)
        return mc && mq
    })
})
const published = computed(() => posts.value.filter((p) => p.is_published).length)

/* ---- modal ---- */
const blank = () => ({
    category: 'garden', title: '', title_bn: '', excerpt: '', image: '',
    author: '', role: '', read_time: '4 min read',
    is_featured: false, is_published: true, sort_order: 0, published_at: '',
})
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
function openEdit(p) {
    editingId.value = p.id
    fieldErrors.value = {}
    Object.assign(form, {
        category: p.category, title: p.title, title_bn: p.title_bn || '',
        excerpt: p.excerpt, image: p.image || '', author: p.author || '',
        role: p.role || '', read_time: p.read_time || '4 min read',
        is_featured: !!p.is_featured, is_published: !!p.is_published,
        sort_order: p.sort_order || 0,
        published_at: p.published_at ? String(p.published_at).slice(0, 10) : '',
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
        const payload = { ...form, published_at: form.published_at || null }
        if (editingId.value) {
            await updatePost(editingId.value, payload)
            toast.success('Post updated.')
        } else {
            await createPost(payload)
            toast.success('Post published.')
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
        await updatePost(p.id, { ...p, is_published: !p.is_published, published_at: p.published_at ? String(p.published_at).slice(0, 10) : null })
        p.is_published = !p.is_published
        toast.success(p.is_published ? 'Published.' : 'Hidden.')
    } catch (e) {
        // api.js toasts
    }
}

const confirming = ref(null)
async function remove() {
    const p = confirming.value
    if (!p) return
    busy.value = true
    try {
        await deletePost(p.id)
        toast.success('Post deleted.')
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
                <h2>Journal</h2>
                <p>Blog posts shown in the storefront journal section.</p>
            </div>
            <div class="head-actions">
                <button class="btn btn-primary" @click="openCreate"><AppIcon name="plus" :size="17" /> New Post</button>
            </div>
        </div>

        <div class="stat-grid">
            <StatCard label="Total Posts" :value="String(posts.length)" icon="edit" foot="live from database" />
            <StatCard label="Published" :value="String(published)" icon="check" tone="info" />
            <StatCard label="Featured" :value="String(posts.filter((p) => p.is_featured).length)" icon="star" tone="gold" />
        </div>

        <section class="card">
            <div class="toolbar">
                <button class="chip" :class="{ active: catFilter === '' }" @click="catFilter = ''">All</button>
                <button v-for="c in CATS" :key="c.id" class="chip" :class="{ active: catFilter === c.id }" @click="catFilter = c.id">{{ c.label }}</button>
                <div class="search">
                    <AppIcon name="search" :size="16" />
                    <input v-model="query" type="search" placeholder="Search posts…" />
                </div>
            </div>

            <div v-if="loading" style="padding: 1.4rem"><Skeleton :rows="5" /></div>

            <div v-else class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Post</th>
                            <th>Category</th>
                            <th>Author</th>
                            <th>Date</th>
                            <th>Live</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="p in rows" :key="p.id">
                            <td>
                                <div class="cell-user">
                                    <img class="thumb" :src="asset(p.image)" :alt="p.title" />
                                    <span>
                                        <strong>{{ p.title }}</strong>
                                        <small v-if="p.is_featured" style="color: var(--gold)">★ Featured</small>
                                    </span>
                                </div>
                            </td>
                            <td><span class="pill shipped">{{ catLabel(p.category) }}</span></td>
                            <td>{{ p.author || '—' }}</td>
                            <td>{{ p.published_at ? String(p.published_at).slice(0, 10) : '—' }}</td>
                            <td><button class="switch sm" :class="{ on: p.is_published }" @click="togglePublish(p)" /></td>
                            <td>
                                <div class="row-actions">
                                    <button title="Edit" @click="openEdit(p)"><AppIcon name="edit" :size="16" /></button>
                                    <button title="Delete" @click="confirming = p"><AppIcon name="trash" :size="16" /></button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p v-if="!rows.length" class="empty">No posts found.</p>
            </div>
        </section>

        <!-- create / edit -->
        <Transition name="modal">
            <div v-if="showForm" class="modal-wrap" @click.self="closeForm">
                <div class="modal">
                    <header class="modal-head">
                        <h3>{{ editingId ? 'Edit post' : 'New post' }}</h3>
                        <button class="modal-x" aria-label="Close" @click="closeForm">
                            <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                        </button>
                    </header>
                    <div class="modal-body">
                        <label class="field" :class="{ invalid: fieldErrors.title }">
                            <span>Title (English)</span>
                            <input v-model="form.title" type="text" />
                            <em v-if="fieldErrors.title" class="field-msg">{{ fieldErrors.title[0] }}</em>
                        </label>
                        <label class="field">
                            <span>Title (বাংলা)</span>
                            <input v-model="form.title_bn" type="text" />
                        </label>
                        <label class="field" :class="{ invalid: fieldErrors.excerpt }">
                            <span>Excerpt</span>
                            <textarea v-model="form.excerpt" rows="3" />
                            <em v-if="fieldErrors.excerpt" class="field-msg">{{ fieldErrors.excerpt[0] }}</em>
                        </label>
                        <div class="form-grid">
                            <label class="field">
                                <span>Category</span>
                                <select v-model="form.category">
                                    <option v-for="c in CATS" :key="c.id" :value="c.id">{{ c.label }}</option>
                                </select>
                            </label>
                            <label class="field">
                                <span>Image path</span>
                                <input v-model="form.image" type="text" placeholder="/images/garden.jpg" />
                            </label>
                            <label class="field">
                                <span>Author</span>
                                <input v-model="form.author" type="text" />
                            </label>
                            <label class="field">
                                <span>Author role</span>
                                <input v-model="form.role" type="text" placeholder="Co-Founder" />
                            </label>
                            <label class="field">
                                <span>Read time</span>
                                <input v-model="form.read_time" type="text" placeholder="4 min read" />
                            </label>
                            <label class="field">
                                <span>Publish date</span>
                                <input v-model="form.published_at" type="date" />
                            </label>
                            <label class="field">
                                <span>Sort order</span>
                                <input v-model.number="form.sort_order" type="number" min="0" />
                            </label>
                        </div>
                        <div class="modal-toggles" style="grid-auto-flow: column; justify-content: start; gap: 1.6rem">
                            <label class="mini-check"><input v-model="form.is_featured" type="checkbox" /> <span>Featured (big card)</span></label>
                            <label class="mini-check"><input v-model="form.is_published" type="checkbox" /> <span>Show on storefront</span></label>
                        </div>
                    </div>
                    <footer class="modal-foot">
                        <button class="btn btn-ghost" :disabled="busy" @click="closeForm">Cancel</button>
                        <button class="btn btn-primary" :disabled="busy" @click="save">
                            {{ busy ? 'Saving…' : editingId ? 'Save changes' : 'Publish post' }}
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
                        <h3>Delete this post?</h3>
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
