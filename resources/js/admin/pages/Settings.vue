<script setup>
import { ref, reactive, onMounted } from 'vue'
import { currentUser, updateProfile } from '../auth'
import { fetchSettings, saveSettings, testAiConnection, asset } from '../data'
import { uploadFile } from '../api'
import { toast } from '../composables/useToast'
import PageLoader from '../components/PageLoader.vue'

// tab label → settings group name + toast label
const tabs = [
    { label: 'Store Profile', group: 'store' },
    { label: 'Payment Gateways', group: 'payments' },
    { label: 'AI Integration', group: 'ai' },
    { label: 'Shipping', group: 'shipping' },
    { label: 'Notifications', group: 'notifications' },
    { label: 'Security', group: 'security' },
]
const active = ref('Store Profile')
const activeGroup = () => tabs.find((t) => t.label === active.value)?.group

const loading = ref(true)
const busy = ref(false)
const uploading = ref(false)
const testingAi = ref(false)
const aiStatus = ref(null)
const fieldErrors = ref({})

async function runAiTest() {
    if (!ai.api_key) {
        toast.error('Please enter an API Key first.')
        return
    }
    testingAi.value = true
    aiStatus.value = null
    try {
        const res = await testAiConnection({
            provider: ai.provider,
            model: ai.model,
            api_key: ai.api_key,
        })
        aiStatus.value = { success: true, message: res.message }
        toast.success(res.message)
    } catch (err) {
        const msg = err.data?.message || err.message || 'Connection failed.'
        aiStatus.value = { success: false, message: msg }
        toast.error(msg)
    } finally {
        testingAi.value = false
    }
}

// every section, bound to the API
const store = reactive({ name: '', logo: '', email: '', phone: '', currency: 'BDT — Bangladeshi Taka (৳)', address: '', description: '' })

async function handleLogoUpload(e) {
    const file = e.target.files?.[0]
    if (!file) return
    uploading.value = true
    try {
        const url = await uploadFile(file)
        store.logo = url
        toast.success('Site Logo uploaded!')
    } catch (err) {
        toast.error('Failed to upload logo.')
    } finally {
        uploading.value = false
    }
}
const notifications = reactive({ notification_email: '', new_order: true, order_status: true, low_stock: true, new_customer: true, daily_digest: false, new_review: true })
const shipping = reactive({ inside_dhaka: 0, outside_dhaka: 0, free_above: 0, courier: 'Steadfast', note: '' })
const security = reactive({ two_factor: false, login_alerts: true })
const ai = reactive({
    enabled: true,
    provider: 'Google Gemini',
    api_key: '',
    model: 'gemini-2.0-flash',
    auto_generate_blurb: true,
    recommendation_assistant: true,
})
const payments = reactive({
    bkash_enabled: true,
    bkash_mode: 'Sandbox',
    bkash_app_key: '',
    bkash_app_secret: '',
    bkash_username: '',
    bkash_password: '',
    bkash_number: '01700-000000',

    nagad_enabled: true,
    nagad_mode: 'Sandbox',
    nagad_merchant_id: '',
    nagad_public_key: '',
    nagad_private_key: '',
    nagad_number: '01800-000000',

    cod_enabled: true,
})

const models = { store, notifications, shipping, security, ai, payments }

async function load() {
    loading.value = true
    try {
        const data = await fetchSettings()
        for (const g of Object.keys(models)) {
            if (data[g]) Object.assign(models[g], data[g])
        }
    } finally {
        loading.value = false
    }
}
onMounted(load)

/* ---- inline change password (Security tab) ---- */
const pw = reactive({ current_password: '', password: '', password_confirmation: '' })
const pwBusy = ref(false)
const pwErrors = ref({})
async function changePassword() {
    pwBusy.value = true
    pwErrors.value = {}
    if (!pw.password || pw.password !== pw.password_confirmation) {
        pwErrors.value = { password: ['New passwords do not match.'] }
        pwBusy.value = false
        return
    }
    try {
        await updateProfile({
            name: currentUser.value?.name,
            email: currentUser.value?.email,
            role: currentUser.value?.role,
            current_password: pw.current_password,
            password: pw.password,
            password_confirmation: pw.password_confirmation,
        })
        pw.current_password = pw.password = pw.password_confirmation = ''
        toast.success('Password updated.')
    } catch (e) {
        pwErrors.value = e.data?.errors || {}
        toast.error(e.data?.message || 'Could not update password.')
    } finally {
        pwBusy.value = false
    }
}

async function save() {
    const group = activeGroup()
    if (!group) return
    busy.value = true
    fieldErrors.value = {}
    try {
        const data = await saveSettings(group, { ...models[group] })
        if (data[group]) Object.assign(models[group], data[group])
        toast.success(`${active.value} saved.`)
    } catch (e) {
        fieldErrors.value = e.data?.errors || {}
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
                <p class="eyebrow">Configuration</p>
                <h2>Settings</h2>
                <p>Store identity, alerts and delivery rules.</p>
            </div>
            <div class="head-actions">
                <button class="btn btn-primary" :disabled="busy" @click="save">
                    {{ busy ? 'Saving…' : 'Save changes' }}
                </button>
            </div>
        </div>

        <div class="settings-grid">
            <nav class="card settings-nav">
                <button
                    v-for="t in tabs"
                    :key="t.label"
                    :class="{ active: active === t.label }"
                    @click="active = t.label"
                >
                    {{ t.label }}
                </button>
            </nav>

            <section class="card">
                <div class="card-head"><h3>{{ active }}</h3></div>

                <div v-if="loading" class="card-body"><PageLoader inline label="Loading settings" /></div>

                <div v-else-if="active === 'Store Profile'" class="card-body">
                        <div class="form-grid">
                            <label class="field" :class="{ invalid: fieldErrors.name }">
                                <span>Store name</span>
                                <input v-model="store.name" type="text" />
                                <em v-if="fieldErrors.name" class="field-msg">{{ fieldErrors.name[0] }}</em>
                            </label>
                            <label class="field" :class="{ invalid: fieldErrors.email }">
                                <span>Contact email</span>
                                <input v-model="store.email" type="email" />
                                <em v-if="fieldErrors.email" class="field-msg">{{ fieldErrors.email[0] }}</em>
                            </label>
                            <label class="field">
                                <span>Phone</span>
                                <input v-model="store.phone" type="tel" />
                            </label>
                            <label class="field">
                                <span>Currency</span>
                                <select v-model="store.currency">
                                    <option>BDT — Bangladeshi Taka (৳)</option>
                                    <option>USD — US Dollar ($)</option>
                                </select>
                            </label>
                        </div>
                        <div class="field" style="margin-bottom: 1.2rem">
                            <span>Site Logo (Header &amp; Footer Brand Image)</span>
                            <div class="upload-card">
                                <img v-if="store.logo" :src="asset(store.logo)" alt="Logo preview" class="upload-thumb" style="max-height: 48px; object-fit: contain" />
                                <div class="upload-meta">
                                    <strong>{{ store.logo ? 'Custom Logo Set' : 'Using default gold leaf sprout mark' }}</strong>
                                    <p>Upload SVG, PNG or transparent JPG for your website header logo</p>
                                    <label class="btn btn-ghost upload-btn">
                                        {{ uploading ? 'Uploading…' : '📁 Upload Site Logo' }}
                                        <input type="file" accept="image/*" @change="handleLogoUpload" />
                                    </label>
                                    <input v-model="store.logo" type="text" placeholder="or paste logo path: images/logo.png" class="pm-path" style="margin-top: 0.4rem" />
                                </div>
                            </div>
                        </div>

                        <label class="field">
                            <span>Estate address</span>
                            <input v-model="store.address" type="text" />
                        </label>
                        <label class="field">
                            <span>Store description</span>
                            <textarea v-model="store.description" rows="3" />
                        </label>
                </div>

                <!-- PAYMENT GATEWAYS -->
                <div v-else-if="active === 'Payment Gateways'" class="card-body">
                    <!-- bKash Gateway -->
                    <div style="margin-bottom: 2rem; border-bottom: 1px solid rgba(16,38,28,0.08); padding-bottom: 1.5rem">
                        <div class="switch-row" style="margin-bottom: 1.2rem">
                            <div>
                                <strong style="font-size: 1.1rem; color: #d12053">🌸 bKash Online Gateway</strong>
                                <span>Enable automated bKash Checkout & Tokenized Merchant API</span>
                            </div>
                            <button class="switch" :class="{ on: payments.bkash_enabled }" @click="payments.bkash_enabled = !payments.bkash_enabled" />
                        </div>

                        <div v-if="payments.bkash_enabled" class="form-grid">
                            <label class="field">
                                <span>Environment Mode</span>
                                <select v-model="payments.bkash_mode">
                                    <option>Sandbox (Testing)</option>
                                    <option>Production (Live)</option>
                                </select>
                            </label>
                            <label class="field">
                                <span>Merchant / Agent Phone</span>
                                <input v-model="payments.bkash_number" type="text" placeholder="017XX-XXXXXX" />
                            </label>
                            <label class="field">
                                <span>bKash App Key</span>
                                <input v-model="payments.bkash_app_key" type="password" placeholder="••••••••••••••••" />
                            </label>
                            <label class="field">
                                <span>bKash App Secret</span>
                                <input v-model="payments.bkash_app_secret" type="password" placeholder="••••••••••••••••" />
                            </label>
                            <label class="field">
                                <span>API Username</span>
                                <input v-model="payments.bkash_username" type="text" placeholder="merchant_user" />
                            </label>
                            <label class="field">
                                <span>API Password</span>
                                <input v-model="payments.bkash_password" type="password" placeholder="••••••••" />
                            </label>
                        </div>
                    </div>

                    <!-- Nagad Gateway -->
                    <div style="margin-bottom: 2rem; border-bottom: 1px solid rgba(16,38,28,0.08); padding-bottom: 1.5rem">
                        <div class="switch-row" style="margin-bottom: 1.2rem">
                            <div>
                                <strong style="font-size: 1.1rem; color: #f26522">⚡ Nagad Online Gateway</strong>
                                <span>Enable automated Nagad Direct Checkout Payment API</span>
                            </div>
                            <button class="switch" :class="{ on: payments.nagad_enabled }" @click="payments.nagad_enabled = !payments.nagad_enabled" />
                        </div>

                        <div v-if="payments.nagad_enabled" class="form-grid">
                            <label class="field">
                                <span>Environment Mode</span>
                                <select v-model="payments.nagad_mode">
                                    <option>Sandbox (Testing)</option>
                                    <option>Production (Live)</option>
                                </select>
                            </label>
                            <label class="field">
                                <span>Merchant Phone / Account</span>
                                <input v-model="payments.nagad_number" type="text" placeholder="018XX-XXXXXX" />
                            </label>
                            <label class="field">
                                <span>Merchant ID</span>
                                <input v-model="payments.nagad_merchant_id" type="text" placeholder="68000XXXXX" />
                            </label>
                            <label class="field">
                                <span>Nagad Public Key</span>
                                <textarea v-model="payments.nagad_public_key" rows="2" placeholder="-----BEGIN PUBLIC KEY-----" />
                            </label>
                        </div>
                    </div>

                    <!-- Cash on Delivery -->
                    <div class="switch-row">
                        <div>
                            <strong>🚚 Cash on Delivery (COD)</strong>
                            <span>Allow customers to pay upon receiving their tea pouch.</span>
                        </div>
                        <button class="switch" :class="{ on: payments.cod_enabled }" @click="payments.cod_enabled = !payments.cod_enabled" />
                    </div>
                </div>

                <!-- AI INTEGRATION -->
                <div v-else-if="active === 'AI Integration'" class="card-body">
                    <div class="switch-row" style="margin-bottom: 1.5rem">
                        <div>
                            <strong style="font-size: 1.1rem; color: var(--green-700)">🤖 AI Engine Integration</strong>
                            <span>Enable AI-powered product description generator &amp; Tea Recommendation assistant</span>
                        </div>
                        <button class="switch" :class="{ on: ai.enabled }" @click="ai.enabled = !ai.enabled" />
                    </div>

                    <template v-if="ai.enabled">
                        <div class="form-grid" style="margin-bottom: 1.5rem">
                            <label class="field">
                                <span>AI Provider</span>
                                <select v-model="ai.provider">
                                    <option>OpenAI (ChatGPT)</option>
                                    <option>Google Gemini</option>
                                    <option>Anthropic Claude</option>
                                </select>
                            </label>

                            <label class="field">
                                <span>Model Choice</span>
                                <select v-model="ai.model">
                                    <option value="gemini-2.0-flash">✨ gemini-2.0-flash (Recommended — fast &amp; free)</option>
                                    <option value="gemini-2.0-flash-lite">⚡ gemini-2.0-flash-lite (Ultra fast)</option>
                                    <option value="gemini-2.5-flash">🚀 gemini-2.5-flash (Newer, capable)</option>
                                    <option value="gemini-2.5-pro">👑 gemini-2.5-pro (Highest quality)</option>
                                    <option value="gemini-flash-latest">🔄 gemini-flash-latest (Always latest flash)</option>
                                    <option value="gpt-4o-mini">gpt-4o-mini (OpenAI Fast)</option>
                                    <option value="gpt-4o">gpt-4o (OpenAI High Performance)</option>
                                </select>
                            </label>

                            <div class="field" style="grid-column: span 2">
                                <span>API Secret Key</span>
                                <div style="display: flex; gap: 0.6rem">
                                    <input v-model="ai.api_key" type="password" placeholder="AI API Key (e.g. AIzaSy... or sk-proj-...)" style="flex: 1" />
                                    <button class="btn btn-ghost" type="button" :disabled="testingAi || !ai.api_key" @click="runAiTest">
                                        {{ testingAi ? '⏳ Testing…' : '🔌 Test Connection' }}
                                    </button>
                                </div>
                                <div v-if="aiStatus" style="margin-top: 0.6rem; padding: 0.6rem 0.9rem; border-radius: 8px; font-size: 0.85rem" :style="{ background: aiStatus.success ? 'rgba(44,107,69,0.08)' : 'rgba(209,32,83,0.08)', color: aiStatus.success ? '#2c6b45' : '#d12053' }">
                                    <strong>{{ aiStatus.success ? '✅ Connected' : '❌ Connection Failed' }}:</strong> {{ aiStatus.message }}
                                </div>
                            </div>
                        </div>

                        <div class="switch-row" style="margin-bottom: 1rem">
                            <div>
                                <strong>Auto-Generate Product Blurbs &amp; Stories</strong>
                                <span>Use AI to write sensory tasting blurbs for new tea products.</span>
                            </div>
                            <button class="switch" :class="{ on: ai.auto_generate_blurb }" @click="ai.auto_generate_blurb = !ai.auto_generate_blurb" />
                        </div>

                        <div class="switch-row">
                            <div>
                                <strong>Interactive Blend Finder AI Assistant</strong>
                                <span>Provide personalized tea recommendations based on user quiz answers.</span>
                            </div>
                            <button class="switch" :class="{ on: ai.recommendation_assistant }" @click="ai.recommendation_assistant = !ai.recommendation_assistant" />
                        </div>
                    </template>
                </div>

                <div v-else-if="active === 'Notifications'" class="card-body">
                    <label class="field" :class="{ invalid: fieldErrors.notification_email }" style="margin-bottom: 1.3rem">
                        <span>Notification email <em style="color: var(--muted); text-transform: none; letter-spacing: 0">— where alerts are sent</em></span>
                        <input v-model="notifications.notification_email" type="email" :placeholder="store.email || 'you@email.com'" />
                        <em v-if="fieldErrors.notification_email" class="field-msg">{{ fieldErrors.notification_email[0] }}</em>
                    </label>
                    <div class="switch-row">
                        <div>
                            <strong>New order alerts</strong>
                            <span>Notify the moment an order is placed.</span>
                        </div>
                        <button class="switch" :class="{ on: notifications.new_order }" @click="notifications.new_order = !notifications.new_order" />
                    </div>
                    <div class="switch-row">
                        <div>
                            <strong>Order status updates</strong>
                            <span>Notify when an order is shipped or delivered.</span>
                        </div>
                        <button class="switch" :class="{ on: notifications.order_status }" @click="notifications.order_status = !notifications.order_status" />
                    </div>
                    <div class="switch-row">
                        <div>
                            <strong>Low stock warnings</strong>
                            <span>Warn when a blend drops below 20 units.</span>
                        </div>
                        <button class="switch" :class="{ on: notifications.low_stock }" @click="notifications.low_stock = !notifications.low_stock" />
                    </div>
                    <div class="switch-row">
                        <div>
                            <strong>New customer sign-ups</strong>
                            <span>Notify when a new customer registers.</span>
                        </div>
                        <button class="switch" :class="{ on: notifications.new_customer }" @click="notifications.new_customer = !notifications.new_customer" />
                    </div>
                    <div class="switch-row">
                        <div>
                            <strong>Daily digest</strong>
                            <span>One summary each morning at 8:00.</span>
                        </div>
                        <button class="switch" :class="{ on: notifications.daily_digest }" @click="notifications.daily_digest = !notifications.daily_digest" />
                    </div>
                    <div class="switch-row">
                        <div>
                            <strong>New reviews</strong>
                            <span>Notify me when a customer leaves a review.</span>
                        </div>
                        <button class="switch" :class="{ on: notifications.new_review }" @click="notifications.new_review = !notifications.new_review" />
                    </div>
                </div>

                <div v-else-if="active === 'Shipping'" class="card-body">
                    <div class="form-grid">
                        <label class="field" :class="{ invalid: fieldErrors.inside_dhaka }">
                            <span>Inside Dhaka (৳)</span>
                            <input v-model.number="shipping.inside_dhaka" type="number" min="0" />
                            <em v-if="fieldErrors.inside_dhaka" class="field-msg">{{ fieldErrors.inside_dhaka[0] }}</em>
                        </label>
                        <label class="field" :class="{ invalid: fieldErrors.outside_dhaka }">
                            <span>Outside Dhaka (৳)</span>
                            <input v-model.number="shipping.outside_dhaka" type="number" min="0" />
                            <em v-if="fieldErrors.outside_dhaka" class="field-msg">{{ fieldErrors.outside_dhaka[0] }}</em>
                        </label>
                        <label class="field" :class="{ invalid: fieldErrors.free_above }">
                            <span>Free shipping above (৳)</span>
                            <input v-model.number="shipping.free_above" type="number" min="0" />
                            <em v-if="fieldErrors.free_above" class="field-msg">{{ fieldErrors.free_above[0] }}</em>
                        </label>
                        <label class="field">
                            <span>Default courier</span>
                            <select v-model="shipping.courier">
                                <option>Steadfast</option>
                                <option>Pathao</option>
                                <option>RedX</option>
                            </select>
                        </label>
                    </div>
                    <label class="field">
                        <span>Delivery note shown at checkout</span>
                        <input v-model="shipping.note" type="text" />
                    </label>
                </div>

                <div v-else class="card-body">
                    <div class="form-grid">
                        <label class="field">
                            <span>Signed in as</span>
                            <input type="email" :value="currentUser?.email" readonly />
                        </label>
                        <label class="field">
                            <span>Role</span>
                            <input type="text" :value="currentUser?.role" readonly />
                        </label>
                    </div>
                    <div class="switch-row">
                        <div>
                            <strong>Two-factor authentication</strong>
                            <span>Require a one-time code at sign-in.</span>
                        </div>
                        <button class="switch" :class="{ on: security.two_factor }" @click="security.two_factor = !security.two_factor" />
                    </div>
                    <div class="switch-row">
                        <div>
                            <strong>New login alerts</strong>
                            <span>Email me when a new device signs in.</span>
                        </div>
                        <button class="switch" :class="{ on: security.login_alerts }" @click="security.login_alerts = !security.login_alerts" />
                    </div>
                    <div class="sec-pw">
                        <h4>Change password</h4>
                        <div class="form-grid">
                            <label class="field" :class="{ invalid: pwErrors.current_password }">
                                <span>Current password</span>
                                <input v-model="pw.current_password" type="password" autocomplete="current-password" />
                                <em v-if="pwErrors.current_password" class="field-msg">{{ pwErrors.current_password[0] }}</em>
                            </label>
                            <span />
                            <label class="field" :class="{ invalid: pwErrors.password }">
                                <span>New password</span>
                                <input v-model="pw.password" type="password" minlength="8" autocomplete="new-password" />
                                <em v-if="pwErrors.password" class="field-msg">{{ pwErrors.password[0] }}</em>
                            </label>
                            <label class="field">
                                <span>Confirm new password</span>
                                <input v-model="pw.password_confirmation" type="password" autocomplete="new-password" />
                            </label>
                        </div>
                        <button class="btn btn-primary" :disabled="pwBusy" @click="changePassword">
                            {{ pwBusy ? 'Updating…' : 'Update password' }}
                        </button>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>

<style scoped>
.sec-pw {
    margin-top: 1.4rem;
    padding-top: 1.3rem;
    border-top: 1px solid var(--line, #e6e9e6);
}
.sec-pw h4 {
    font-size: 0.95rem;
    margin: 0 0 0.9rem;
    color: var(--green-800, #163024);
}
</style>
