<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import AppIcon from '../components/AppIcon.vue'
import StatCard from '../components/StatCard.vue'
import Skeleton from '../components/Skeleton.vue'
import {
    fetchCustomerGroups, fetchCustomerGroup, createCustomerGroup, updateCustomerGroup,
    deleteCustomerGroup, syncGroupMembers, fetchCustomers, initials,
} from '../data'
import { toast } from '../composables/useToast'

const groups = ref([])
const customers = ref([])
const loading = ref(true)

async function load() {
    loading.value = true
    try {
        const [g, c] = await Promise.all([fetchCustomerGroups(), fetchCustomers()])
        groups.value = g
        customers.value = c
    } finally { loading.value = false }
}
onMounted(load)

const totalMembers = computed(() => groups.value.reduce((s, g) => s + (g.customers_count || 0), 0))

/* ---- create / edit group ---- */
const blank = () => ({ name: '', description: '' })
const showForm = ref(false)
const editingId = ref(null)
const busy = ref(false)
const fieldErrors = ref({})
const form = reactive(blank())

function openCreate() { editingId.value = null; fieldErrors.value = {}; Object.assign(form, blank()); showForm.value = true }
function openEdit(g) { editingId.value = g.id; fieldErrors.value = {}; Object.assign(form, { name: g.name, description: g.description || '' }); showForm.value = true }
function closeForm() { if (!busy.value) showForm.value = false }

async function save() {
    busy.value = true; fieldErrors.value = {}
    try {
        if (editingId.value) { await updateCustomerGroup(editingId.value, { ...form }); toast.success('Group updated.') }
        else { await createCustomerGroup({ ...form }); toast.success('Group created.') }
        showForm.value = false; await load()
    } catch (e) {
        fieldErrors.value = e.data?.errors || {}
        if (e.status === 422) toast.error('Please fix the highlighted fields.')
    } finally { busy.value = false }
}

/* ---- members manager ---- */
const mModal = reactive({ open: false, group: null })
const mBusy = ref(false)
const selected = ref([])
const memberSearch = ref('')

async function openMembers(g) {
    mModal.open = true; mModal.group = g
    selected.value = []
    try {
        const detail = await fetchCustomerGroup(g.id)
        selected.value = [...(detail.member_ids || [])]
    } catch (e) { /* toasts */ }
}
const filteredCustomers = computed(() => {
    const q = memberSearch.value.trim().toLowerCase()
    if (!q) return customers.value
    return customers.value.filter((c) => c.name.toLowerCase().includes(q) || (c.email || '').toLowerCase().includes(q))
})
function toggleMember(id) {
    const i = selected.value.indexOf(id)
    if (i === -1) selected.value.push(id)
    else selected.value.splice(i, 1)
}
async function saveMembers() {
    mBusy.value = true
    try {
        await syncGroupMembers(mModal.group.id, selected.value)
        toast.success(`${selected.value.length} member${selected.value.length === 1 ? '' : 's'} in “${mModal.group.name}”.`)
        mModal.open = false
        await load()
    } finally { mBusy.value = false }
}

/* ---- delete ---- */
const confirming = ref(null)
async function remove() {
    if (!confirming.value) return
    busy.value = true
    try {
        await deleteCustomerGroup(confirming.value.id)
        toast.success('Group deleted.')
        confirming.value = null
        await load()
    } finally { busy.value = false }
}
</script>

<template>
    <div class="page">
        <div class="page-head">
            <div>
                <p class="eyebrow">Commerce</p>
                <h2>Customer Groups</h2>
                <p>Group customers (e.g. “Friends”, “VIP”) and target promo codes at a group so only its members can redeem.</p>
            </div>
            <div class="head-actions">
                <button class="btn btn-primary" @click="openCreate"><AppIcon name="plus" :size="17" /> New Group</button>
            </div>
        </div>

        <div class="stat-grid">
            <StatCard label="Total Groups" :value="String(groups.length)" icon="customers" foot="live from database" />
            <StatCard label="Grouped Members" :value="String(totalMembers)" icon="users" tone="info" />
        </div>

        <section class="card">
            <div v-if="loading" style="padding: 1.4rem"><Skeleton :rows="4" /></div>
            <div v-else class="table-wrap">
                <table>
                    <thead><tr><th>Group</th><th>Description</th><th>Members</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="g in groups" :key="g.id">
                            <td><strong>{{ g.name }}</strong></td>
                            <td><span style="color: var(--muted)">{{ g.description || '—' }}</span></td>
                            <td><span class="pill pending">{{ g.customers_count }} member{{ g.customers_count === 1 ? '' : 's' }}</span></td>
                            <td><div class="row-actions">
                                <button title="Manage members" @click="openMembers(g)"><AppIcon name="users" :size="16" /></button>
                                <button title="Edit" @click="openEdit(g)"><AppIcon name="edit" :size="16" /></button>
                                <button title="Delete" @click="confirming = g"><AppIcon name="trash" :size="16" /></button>
                            </div></td>
                        </tr>
                    </tbody>
                </table>
                <p v-if="!groups.length" class="empty">No groups yet. Create one, then add members.</p>
            </div>
        </section>

        <!-- create / edit -->
        <Transition name="modal">
            <div v-if="showForm" class="modal-wrap" @click.self="closeForm">
                <div class="modal modal-sm">
                    <header class="modal-head">
                        <h3>{{ editingId ? 'Edit group' : 'New customer group' }}</h3>
                        <button class="modal-x" aria-label="Close" @click="closeForm">
                            <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                        </button>
                    </header>
                    <div class="modal-body">
                        <label class="field" :class="{ invalid: fieldErrors.name }">
                            <span>Group name</span>
                            <input v-model="form.name" type="text" placeholder="Friends" />
                            <em v-if="fieldErrors.name" class="field-msg">{{ fieldErrors.name[0] }}</em>
                        </label>
                        <label class="field">
                            <span>Description <em style="color: var(--muted)">(optional)</em></span>
                            <input v-model="form.description" type="text" placeholder="Close friends & family discount" />
                        </label>
                    </div>
                    <footer class="modal-foot">
                        <button class="btn btn-ghost" :disabled="busy" @click="closeForm">Cancel</button>
                        <button class="btn btn-primary" :disabled="busy" @click="save">{{ busy ? 'Saving…' : editingId ? 'Save changes' : 'Create group' }}</button>
                    </footer>
                </div>
            </div>
        </Transition>

        <!-- members manager -->
        <Transition name="modal">
            <div v-if="mModal.open" class="modal-wrap" @click.self="!mBusy && (mModal.open = false)">
                <div class="modal">
                    <header class="modal-head">
                        <h3>Members of “{{ mModal.group?.name }}” <span class="mem-n">{{ selected.length }}</span></h3>
                        <button class="modal-x" aria-label="Close" @click="!mBusy && (mModal.open = false)">
                            <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                        </button>
                    </header>
                    <div class="modal-body">
                        <div class="mem-search">
                            <AppIcon name="search" :size="15" />
                            <input v-model="memberSearch" type="search" placeholder="Search customers…" />
                        </div>
                        <p v-if="!customers.length" class="empty">No customers yet.</p>
                        <div v-else class="mem-list">
                            <label v-for="c in filteredCustomers" :key="c.id" class="mem-row" :class="{ on: selected.includes(c.id) }">
                                <input type="checkbox" :checked="selected.includes(c.id)" @change="toggleMember(c.id)" />
                                <span class="mem-avatar">{{ initials(c.name) }}</span>
                                <span class="mem-info"><strong>{{ c.name }}</strong><em>{{ c.email }}</em></span>
                                <span class="mem-tier">{{ c.tier }}</span>
                            </label>
                        </div>
                    </div>
                    <footer class="modal-foot">
                        <button class="btn btn-ghost" :disabled="mBusy" @click="mModal.open = false">Cancel</button>
                        <button class="btn btn-primary" :disabled="mBusy" @click="saveMembers">{{ mBusy ? 'Saving…' : `Save ${selected.length} member${selected.length === 1 ? '' : 's'}` }}</button>
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
                        <h3>Delete “{{ confirming.name }}”?</h3>
                        <p>Members are unassigned and any promo codes targeting this group become open to everyone.</p>
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
.mem-n { font-size: 0.75rem; background: var(--gold, #c8a24a); color: #fff; padding: 0.05rem 0.5rem; border-radius: 999px; margin-left: 0.4rem; }
.mem-search { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.8rem; border: 1px solid var(--line); border-radius: 10px; margin-bottom: 0.9rem; color: var(--muted); }
.mem-search input { border: none; background: none; outline: none; width: 100%; font: inherit; }
.mem-list { display: grid; gap: 0.4rem; max-height: 360px; overflow-y: auto; }
.mem-row { display: flex; align-items: center; gap: 0.7rem; padding: 0.55rem 0.7rem; border: 1px solid var(--line); border-radius: 10px; cursor: pointer; transition: all 0.15s var(--ease); }
.mem-row.on { border-color: var(--green-600, #2c6b45); background: #f4f8f4; }
.mem-avatar { width: 32px; height: 32px; flex: none; display: grid; place-items: center; border-radius: 50%; background: var(--cream-2, #efe7d6); color: var(--green-800, #163024); font-size: 0.72rem; font-weight: 700; }
.mem-info { display: flex; flex-direction: column; flex: 1; line-height: 1.25; }
.mem-info em { color: var(--muted); font-style: normal; font-size: 0.78rem; }
.mem-tier { font-size: 0.72rem; color: var(--muted); }
</style>
