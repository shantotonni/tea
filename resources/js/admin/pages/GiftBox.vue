<script setup>
import { ref, reactive, onMounted } from 'vue'
import Skeleton from '../components/Skeleton.vue'
import { fetchSettings, saveSettings } from '../data'
import { toast } from '../composables/useToast'

const loading = ref(true)
const busy = ref(false)
const errors = ref({})
const form = reactive({ eyebrow: '', title: '', lead: '', note: '', discount_pct: 18 })

async function load() {
    loading.value = true
    try {
        const settings = await fetchSettings()
        Object.assign(form, settings.giftbox || {})
    } finally { loading.value = false }
}
onMounted(load)

async function save() {
    busy.value = true; errors.value = {}
    try { await saveSettings('giftbox', { ...form }); toast.success('Gift box settings saved.') }
    catch (e) { errors.value = e.data?.errors || {}; if (e.status === 422) toast.error('Please fix the highlighted fields.') }
    finally { busy.value = false }
}
</script>

<template>
    <div class="page">
        <div class="page-head">
            <div>
                <p class="eyebrow">Storefront</p>
                <h2>Gift / Discovery Box</h2>
                <p>The bundle section copy and discount. (Which blends go in the box is set by the “Include in gift box” toggle on each <RouterLink to="/admin/products">Product</RouterLink>.)</p>
            </div>
            <div class="head-actions">
                <button class="btn btn-primary" :disabled="busy || loading" @click="save">{{ busy ? 'Saving…' : 'Save changes' }}</button>
            </div>
        </div>

        <div v-if="loading" class="card" style="padding: 1.4rem"><Skeleton :rows="5" /></div>

        <section v-else class="card blk">
            <header class="blk-head"><h3>Box copy &amp; pricing</h3></header>
            <div class="blk-body">
                <div class="form-grid">
                    <label class="field" :class="{ invalid: errors.eyebrow }"><span>Eyebrow</span><input v-model="form.eyebrow" type="text" /><em v-if="errors.eyebrow" class="field-msg">{{ errors.eyebrow[0] }}</em></label>
                    <label class="field" :class="{ invalid: errors.discount_pct }"><span>Discount % <em class="fmt-hint">drives price + ribbon</em></span><input v-model.number="form.discount_pct" type="number" min="0" max="90" /><em v-if="errors.discount_pct" class="field-msg">{{ errors.discount_pct[0] }}</em></label>
                </div>
                <label class="field" :class="{ invalid: errors.title }"><span>Title <em class="fmt-hint">Enter for line break</em></span><textarea v-model="form.title" rows="2" /><em v-if="errors.title" class="field-msg">{{ errors.title[0] }}</em></label>
                <label class="field"><span>Lead</span><textarea v-model="form.lead" rows="3" /></label>
                <label class="field"><span>Note line</span><input v-model="form.note" type="text" placeholder="🎁 Free gift wrap · 🚚 Free delivery" /></label>
            </div>
        </section>
    </div>
</template>

<style scoped>
.blk { margin-bottom: 1.4rem; }
.blk-head { padding: 1.1rem 1.4rem; border-bottom: 1px solid var(--line, #eee); }
.blk-head h3 { margin: 0; font-size: 1.05rem; }
.blk-body { padding: 1.2rem 1.4rem; display: grid; gap: 1rem; }
.fmt-hint { font-weight: 400; font-style: normal; color: var(--muted); font-size: 0.78rem; margin-left: 0.3rem; }
</style>
