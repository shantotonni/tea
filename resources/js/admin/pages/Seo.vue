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
const form = reactive({ title: '', description: '', keywords: '', og_title: '', og_description: '', og_image: '' })

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
        </template>
    </div>
</template>

<style scoped>
.blk { margin-bottom: 1.4rem; }
.blk-head { padding: 1.1rem 1.4rem; border-bottom: 1px solid var(--line, #eee); }
.blk-head h3 { margin: 0; font-size: 1.05rem; }
.blk-body { padding: 1.2rem 1.4rem; display: grid; gap: 1.2rem; }
.fmt-hint { font-weight: 400; font-style: normal; color: var(--muted); font-size: 0.78rem; margin-left: 0.3rem; }

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
