<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import AppIcon from '../components/AppIcon.vue'
import StatCard from '../components/StatCard.vue'
import Skeleton from '../components/Skeleton.vue'
import { fetchSubscribers, deleteSubscriber, fetchSettings, saveSettings } from '../data'
import { toast } from '../composables/useToast'

const loading = ref(true)
const subscribers = ref([])
const copy = reactive({ title: '', lead: '', button_label: '', success_label: '', fine: '' })
const copyBusy = ref(false)
const copyErrors = ref({})

const fmtDate = (iso) => (iso ? new Date(iso).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '')

async function load() {
    loading.value = true
    try {
        const [subs, settings] = await Promise.all([fetchSubscribers(), fetchSettings()])
        subscribers.value = subs
        Object.assign(copy, settings.newsletter || {})
    } finally { loading.value = false }
}
onMounted(load)

const total = computed(() => subscribers.value.length)

async function saveCopy() {
    copyBusy.value = true; copyErrors.value = {}
    try { await saveSettings('newsletter', { ...copy }); toast.success('Newsletter copy saved.') }
    catch (e) { copyErrors.value = e.data?.errors || {}; if (e.status === 422) toast.error('Please fix the highlighted fields.') }
    finally { copyBusy.value = false }
}

const confirming = ref(null)
const busy = ref(false)
async function remove() {
    if (!confirming.value) return
    busy.value = true
    try { await deleteSubscriber(confirming.value.id); toast.success('Subscriber removed.'); confirming.value = null; await load() }
    finally { busy.value = false }
}
</script>

<template>
    <div class="page">
        <div class="page-head">
            <div>
                <p class="eyebrow">Storefront</p>
                <h2>Newsletter</h2>
                <p>The “Join the Tea Ritual” signup band — copy and collected subscribers.</p>
            </div>
        </div>

        <div v-if="loading" class="card" style="padding: 1.4rem"><Skeleton :rows="6" /></div>

        <template v-else>
            <div class="stat-grid">
                <StatCard label="Subscribers" :value="String(total)" icon="users" foot="live from database" />
            </div>

            <section class="card blk">
                <header class="blk-head">
                    <h3>Signup copy</h3>
                    <button class="btn btn-primary" :disabled="copyBusy" @click="saveCopy">{{ copyBusy ? 'Saving…' : 'Save copy' }}</button>
                </header>
                <div class="blk-body">
                    <label class="field" :class="{ invalid: copyErrors.title }"><span>Title</span><input v-model="copy.title" type="text" /><em v-if="copyErrors.title" class="field-msg">{{ copyErrors.title[0] }}</em></label>
                    <label class="field"><span>Lead</span><textarea v-model="copy.lead" rows="2" /></label>
                    <div class="form-grid">
                        <label class="field"><span>Button label</span><input v-model="copy.button_label" type="text" /></label>
                        <label class="field"><span>Success label</span><input v-model="copy.success_label" type="text" /></label>
                    </div>
                    <label class="field"><span>Fine print</span><input v-model="copy.fine" type="text" /></label>
                </div>
            </section>

            <section class="card blk">
                <header class="blk-head">
                    <h3>Subscribers <span class="count">{{ total }}</span></h3>
                </header>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Email</th><th>Source</th><th>Joined</th><th></th></tr></thead>
                        <tbody>
                            <tr v-for="s in subscribers" :key="s.id">
                                <td><strong>{{ s.email }}</strong></td>
                                <td><span style="color: var(--muted)">{{ s.source }}</span></td>
                                <td>{{ fmtDate(s.created_at) }}</td>
                                <td><div class="row-actions">
                                    <button title="Remove" @click="confirming = s"><AppIcon name="trash" :size="16" /></button>
                                </div></td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-if="!subscribers.length" class="empty">No subscribers yet.</p>
                </div>
            </section>
        </template>

        <Transition name="modal">
            <div v-if="confirming" class="modal-wrap" @click.self="confirming = null">
                <div class="modal modal-sm">
                    <div class="modal-body confirm-body">
                        <span class="confirm-ico"><AppIcon name="trash" :size="22" /></span>
                        <h3>Remove this subscriber?</h3>
                        <p>“{{ confirming.email }}” — this cannot be undone.</p>
                    </div>
                    <footer class="modal-foot">
                        <button class="btn btn-ghost" :disabled="busy" @click="confirming = null">Cancel</button>
                        <button class="btn btn-danger" :disabled="busy" @click="remove">{{ busy ? 'Removing…' : 'Remove' }}</button>
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
