<script setup>
import { ref, reactive, watch } from 'vue'
import { currentUser, updateProfile } from '../auth'
import { toast } from '../composables/useToast'

const form = reactive({
    name: '',
    email: '',
    role: '',
    current_password: '',
    password: '',
    password_confirmation: '',
})

// seed the form from the signed-in user (and re-seed if it loads late)
watch(
    currentUser,
    (u) => {
        if (u) {
            form.name = u.name || ''
            form.email = u.email || ''
            form.role = u.role || ''
        }
    },
    { immediate: true }
)

const busy = ref(false)
const ok = ref(false)
const error = ref('')
const fieldErrors = ref({})

async function save() {
    busy.value = true
    ok.value = false
    error.value = ''
    fieldErrors.value = {}

    const payload = { name: form.name, email: form.email, role: form.role }
    if (form.password) {
        payload.current_password = form.current_password
        payload.password = form.password
        payload.password_confirmation = form.password_confirmation
    }

    try {
        await updateProfile(payload)
        ok.value = true
        toast.success('Profile updated.')
        form.current_password = ''
        form.password = ''
        form.password_confirmation = ''
        setTimeout(() => (ok.value = false), 2500)
    } catch (e) {
        error.value = e.message || 'Could not save.'
        fieldErrors.value = e.data?.errors || {}
        if (e.status === 422) toast.error('Please fix the highlighted fields.')
    } finally {
        busy.value = false
    }
}

const initials = () =>
    (form.name || 'A')
        .split(' ')
        .map((w) => w[0])
        .slice(0, 2)
        .join('')
        .toUpperCase()
</script>

<template>
    <div class="page">
        <div class="page-head">
            <div>
                <p class="eyebrow">Account</p>
                <h2>My Profile</h2>
                <p>Update your name, email and password.</p>
            </div>
        </div>

        <div class="settings-grid">
            <aside class="card pf-card">
                <span class="pf-avatar">{{ initials() }}</span>
                <strong>{{ form.name || '—' }}</strong>
                <em>{{ form.role || 'Administrator' }}</em>
                <p>{{ form.email }}</p>
            </aside>

            <section class="card">
                <div class="card-head"><h3>Edit details</h3></div>
                <div class="card-body">
                    <div v-if="ok" class="pf-alert ok">✓ Profile saved.</div>
                    <div v-if="error" class="pf-alert err">{{ error }}</div>

                    <form @submit.prevent="save">
                        <div class="form-grid">
                            <label class="field">
                                <span>Full name</span>
                                <input v-model="form.name" type="text" required />
                            </label>
                            <label class="field">
                                <span>Email</span>
                                <input v-model="form.email" type="email" required />
                            </label>
                        </div>
                        <label class="field">
                            <span>Role / title</span>
                            <input v-model="form.role" type="text" placeholder="Estate Administrator" />
                        </label>

                        <p class="pf-sub">Change password <em>(leave blank to keep current)</em></p>
                        <label class="field">
                            <span>Current password</span>
                            <input v-model="form.current_password" type="password" autocomplete="current-password" />
                            <em v-if="fieldErrors.current_password" class="field-msg">
                                {{ fieldErrors.current_password[0] }}
                            </em>
                        </label>
                        <div class="form-grid">
                            <label class="field">
                                <span>New password</span>
                                <input v-model="form.password" type="password" autocomplete="new-password" />
                                <em v-if="fieldErrors.password" class="field-msg">{{ fieldErrors.password[0] }}</em>
                            </label>
                            <label class="field">
                                <span>Confirm new password</span>
                                <input v-model="form.password_confirmation" type="password" autocomplete="new-password" />
                            </label>
                        </div>

                        <button class="btn btn-primary" type="submit" :disabled="busy">
                            {{ busy ? 'Saving…' : 'Save changes' }}
                        </button>
                    </form>
                </div>
            </section>
        </div>
    </div>
</template>
