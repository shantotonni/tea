<script setup>
import { ref, reactive, onMounted } from 'vue'
import Skeleton from '../components/Skeleton.vue'
import { fetchSettings, saveSettings, asset } from '../data'
import { uploadFile } from '../api'
import { toast } from '../composables/useToast'

const loading = ref(true)
const busy = ref(false)
const uploading = ref(false)
const errors = ref({})
const form = reactive({ title: '', description: '', keywords: '', og_title: '', og_description: '', og_image: '', site_name: '', logo: '', locality: '', business_phone: '', business_email: '', social_profiles: '', google_site_verification: '' })

async function load() {
    loading.value = true
    try {
        const settings = await fetchSettings()
        Object.assign(form, settings.seo || {})
    } finally {
        loading.value = false
    }
}
onMounted(load)

async function handleOgImageUpload(e) {
    const file = e.target.files?.[0]
    if (!file) return
    uploading.value = true
    try {
        const url = await uploadFile(file)
        form.og_image = url
        toast.success('Social share card image uploaded successfully!')
    } catch (err) {
        toast.error('Failed to upload share image. Ensure file is an image under 10MB.')
    } finally {
        uploading.value = false
    }
}

async function handleLogoUpload(e) {
    const file = e.target.files?.[0]
    if (!file) return
    uploading.value = true
    try {
        const url = await uploadFile(file)
        form.logo = url
        toast.success('Logo uploaded successfully!')
    } catch (err) {
        toast.error('Failed to upload logo. Ensure file is an image under 10MB.')
    } finally {
        uploading.value = false
    }
}

async function save() {
    busy.value = true
    errors.value = {}
    try {
        await saveSettings('seo', { ...form })
        toast.success('SEO settings saved successfully.')
    } catch (e) {
        errors.value = e.data?.errors || {}
        if (e.status === 422) toast.error('Please fix the highlighted fields.')
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
                <h2>SEO &amp; Social Meta</h2>
                <p>Controls the browser tab title, search-engine description and social share card.</p>
            </div>
            <div class="head-actions">
                <button class="btn btn-primary" :disabled="busy || loading" @click="save">{{ busy ? 'Saving…' : 'Save changes' }}</button>
            </div>
        </div>

        <div v-if="loading" class="card" style="padding: 1.4rem"><Skeleton :rows="6" /></div>

        <template v-else>
            <section class="card blk">
                <header class="blk-head"><h3>Search Engine Optimization (Google Search)</h3></header>
                <div class="blk-body">
                    <label class="field" :class="{ invalid: errors.title }">
                        <span>Page Title <em class="fmt-hint">shown on browser tab &amp; Google search results</em></span>
                        <input v-model="form.title" type="text" placeholder="Cha Kunjo — Sreemangal Hill Tea" />
                        <em v-if="errors.title" class="field-msg">{{ errors.title[0] }}</em>
                    </label>
                    <label class="field" :class="{ invalid: errors.description }">
                        <span>Meta Description</span>
                        <textarea v-model="form.description" rows="3" placeholder="Single-origin hand-plucked tea directly from Sreemangal hills." />
                        <em v-if="errors.description" class="field-msg">{{ errors.description[0] }}</em>
                    </label>
                    <label class="field" :class="{ invalid: errors.keywords }">
                        <span>Keywords <em class="fmt-hint">comma separated keywords</em></span>
                        <input v-model="form.keywords" type="text" placeholder="tea, sreemangal, green tea, black tea, cha kunjo" />
                        <em v-if="errors.keywords" class="field-msg">{{ errors.keywords[0] }}</em>
                    </label>
                </div>
            </section>

            <section class="card blk">
                <header class="blk-head"><h3>Social Share Card (Open Graph / Facebook / WhatsApp)</h3></header>
                <div class="blk-body">
                    <label class="field" :class="{ invalid: errors.og_title }">
                        <span>Social Share Title</span>
                        <input v-model="form.og_title" type="text" placeholder="Cha Kunjo — Sreemangal Tea Estate" />
                        <em v-if="errors.og_title" class="field-msg">{{ errors.og_title[0] }}</em>
                    </label>

                    <label class="field" :class="{ invalid: errors.og_description }">
                        <span>Social Share Description</span>
                        <textarea v-model="form.og_description" rows="2" placeholder="Experience the finest hand-picked artisan teas from Sreemangal." />
                        <em v-if="errors.og_description" class="field-msg">{{ errors.og_description[0] }}</em>
                    </label>

                    <div class="upload-section-card">
                        <label class="field" :class="{ invalid: errors.og_image }">
                            <span>Social Banner Image Upload</span>
                            <div class="upload-controls">
                                <label class="btn btn-primary file-btn">
                                    <span>{{ uploading ? '⏳ Uploading Image...' : '📁 Choose Image File to Upload' }}</span>
                                    <input type="file" accept="image/*" @change="handleOgImageUpload" />
                                </label>
                                <span class="or-text">or specify image path:</span>
                                <input v-model="form.og_image" type="text" placeholder="/images/slider/1.jpeg" />
                            </div>
                            <em v-if="errors.og_image" class="field-msg">{{ errors.og_image[0] }}</em>
                        </label>

                        <div v-if="form.og_image" class="og-preview-card">
                            <span>Social Preview Image:</span>
                            <img :src="asset(form.og_image)" alt="social share preview" />
                        </div>
                    </div>
                </div>
            </section>

            <section class="card blk">
                <header class="blk-head"><h3>Brand &amp; Business Info <em class="fmt-hint">powers Google's Organization / Knowledge panel</em></h3></header>
                <div class="blk-body">
                    <label class="field" :class="{ invalid: errors.site_name }">
                        <span>Site / Brand Name</span>
                        <input v-model="form.site_name" type="text" placeholder="Cha Kunjo" />
                        <em v-if="errors.site_name" class="field-msg">{{ errors.site_name[0] }}</em>
                    </label>
                    <div class="two-col">
                        <label class="field" :class="{ invalid: errors.business_email }">
                            <span>Business Email</span>
                            <input v-model="form.business_email" type="email" placeholder="chakunjo@gmail.com" />
                            <em v-if="errors.business_email" class="field-msg">{{ errors.business_email[0] }}</em>
                        </label>
                        <label class="field" :class="{ invalid: errors.business_phone }">
                            <span>Business Phone</span>
                            <input v-model="form.business_phone" type="text" placeholder="01313762119" />
                            <em v-if="errors.business_phone" class="field-msg">{{ errors.business_phone[0] }}</em>
                        </label>
                    </div>
                    <label class="field" :class="{ invalid: errors.locality }">
                        <span>Locality / City <em class="fmt-hint">for local search (e.g. Sreemangal)</em></span>
                        <input v-model="form.locality" type="text" placeholder="Sreemangal" />
                        <em v-if="errors.locality" class="field-msg">{{ errors.locality[0] }}</em>
                    </label>
                    <label class="field" :class="{ invalid: errors.social_profiles }">
                        <span>Social Profile URLs <em class="fmt-hint">comma separated full links (Facebook, Instagram, YouTube…)</em></span>
                        <textarea v-model="form.social_profiles" rows="2" placeholder="https://facebook.com/chakunjo, https://instagram.com/chakunjo" />
                        <em v-if="errors.social_profiles" class="field-msg">{{ errors.social_profiles[0] }}</em>
                    </label>

                    <div class="upload-section-card">
                        <label class="field" :class="{ invalid: errors.logo }">
                            <span>Brand Logo <em class="fmt-hint">used in search / structured data</em></span>
                            <div class="upload-controls">
                                <label class="btn btn-primary file-btn">
                                    <span>{{ uploading ? '⏳ Uploading…' : '📁 Choose Logo File' }}</span>
                                    <input type="file" accept="image/*" @change="handleLogoUpload" />
                                </label>
                                <span class="or-text">or specify image path:</span>
                                <input v-model="form.logo" type="text" placeholder="/images/logo.png" />
                            </div>
                            <em v-if="errors.logo" class="field-msg">{{ errors.logo[0] }}</em>
                        </label>
                        <div v-if="form.logo" class="og-preview-card">
                            <span>Logo Preview:</span>
                            <img :src="asset(form.logo)" alt="logo preview" style="max-width: 160px" />
                        </div>
                    </div>
                </div>
            </section>

            <section class="card blk">
                <header class="blk-head"><h3>🔎 Google Search Console Verification</h3></header>
                <div class="blk-body">
                    <div class="gsc-help">
                        <p><strong>How to verify your domain:</strong></p>
                        <ol>
                            <li>Go to <b>search.google.com/search-console</b> → Add property → URL prefix → <b>https://chakunjo.com</b></li>
                            <li>Pick the <b>“HTML tag”</b> method. You'll see: <code>&lt;meta name="google-site-verification" content="<b>xxxx</b>" /&gt;</code></li>
                            <li>Copy <b>only the token</b> (the <code>xxxx</code> part) and paste it below, then Save.</li>
                            <li>Back in Search Console, click <b>Verify</b>.</li>
                        </ol>
                    </div>
                    <label class="field" :class="{ invalid: errors.google_site_verification }">
                        <span>Google Site Verification Token</span>
                        <input v-model="form.google_site_verification" type="text" placeholder="aBcD1234... (token only, not the whole tag)" />
                        <em v-if="errors.google_site_verification" class="field-msg">{{ errors.google_site_verification[0] }}</em>
                    </label>
                </div>
            </section>
        </template>
    </div>
</template>

<style scoped>
.blk { margin-bottom: 1.4rem; }
.blk-head { padding: 1.1rem 1.4rem; border-bottom: 1px solid var(--line, #eee); }
.blk-head h3 { margin: 0; font-size: 1.05rem; }
.blk-body { padding: 1.2rem 1.4rem; display: grid; gap: 1.2rem; }
.fmt-hint { font-weight: 400; font-style: normal; color: var(--muted); font-size: 0.78rem; margin-left: 0.3rem; }
.two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 1.2rem; }
@media (max-width: 640px) { .two-col { grid-template-columns: 1fr; } }
.gsc-help { background: #f4f8f4; border: 1px solid #d9e6dc; border-radius: 12px; padding: 0.9rem 1.1rem; font-size: 0.85rem; color: var(--ink, #223028); }
.gsc-help p { margin: 0 0 0.5rem; }
.gsc-help ol { margin: 0; padding-left: 1.2rem; display: grid; gap: 0.35rem; line-height: 1.5; }
.gsc-help code { background: rgba(16,38,28,0.06); padding: 0.05rem 0.35rem; border-radius: 5px; font-size: 0.82em; }

.upload-section-card {
  background: var(--cream, #f9f6f0);
  border: 1.5px dashed rgba(200, 162, 74, 0.4);
  border-radius: 14px;
  padding: 1.2rem 1.4rem;
}
.upload-controls {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-top: 0.4rem;
  flex-wrap: wrap;
}
.file-btn {
  position: relative;
  overflow: hidden;
  cursor: pointer;
}
.file-btn input[type="file"] {
  position: absolute;
  inset: 0;
  opacity: 0;
  cursor: pointer;
}
.or-text {
  font-size: 0.8rem;
  color: var(--muted);
}
.og-preview-card {
  margin-top: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}
.og-preview-card span {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--muted);
}
.og-preview-card img {
  max-width: 380px;
  width: 100%;
  border-radius: 12px;
  border: 2px solid var(--gold, #c8a24a);
  box-shadow: 0 6px 16px rgba(0,0,0,0.08);
}
</style>
