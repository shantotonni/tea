<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import AppIcon from '../components/AppIcon.vue'
import BrandMark from '../components/BrandMark.vue'
import { login, demoCredentials } from '../auth'

const router = useRouter()

const email = ref('')
const password = ref('')
const remember = ref(true)
const showPass = ref(false)
const error = ref('')
const busy = ref(false)
const shake = ref(false)
const capsLock = ref(false)
const touched = ref({ email: false, password: false })

const emailValid = computed(() => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim()))
const emailError = computed(() => touched.value.email && email.value && !emailValid.value)
const canSubmit = computed(() => emailValid.value && password.value.length > 0 && !busy.value)

// lockout countdown, driven by the API's Retry-After on 429
const lockedFor = ref(0)
let lockTimer = null

function startLock(seconds) {
    lockedFor.value = seconds
    clearInterval(lockTimer)
    lockTimer = setInterval(() => {
        lockedFor.value -= 1
        if (lockedFor.value <= 0) clearInterval(lockTimer)
    }, 1000)
}

function onKey(e) {
    // getModifierState is available on real keyboard events
    if (e.getModifierState) capsLock.value = e.getModifierState('CapsLock')
}

function fail(message) {
    error.value = message
    shake.value = false
    requestAnimationFrame(() => (shake.value = true))
}

async function submit() {
    touched.value = { email: true, password: true }
    if (!canSubmit.value || lockedFor.value > 0) return

    error.value = ''
    busy.value = true

    const result = await login({
        email: email.value.trim(),
        password: password.value,
        remember: remember.value,
    })

    busy.value = false

    if (!result.ok) {
        if (result.retryAfter) startLock(result.retryAfter)
        fail(result.message)
        return
    }
    router.push('/admin')
}

function fillDemo() {
    email.value = demoCredentials.email
    password.value = demoCredentials.password
    touched.value = { email: true, password: true }
}
</script>

<template>
    <div class="login">
        <aside class="login-aside">
            <div class="login-brand">
                <span class="mark"><BrandMark :size="22" /></span>
                <span class="word">Cha<em>Kunjo</em></span>
            </div>

            <div class="login-aside-content">
                <p class="eyebrow">Estate Control Room</p>
                <h2>Every leaf, every order — in one calm place.</h2>
                <p>
                    Track harvests, fulfil orders and watch the numbers move, all from the same
                    dashboard your gardens run on.
                </p>
                <ul class="login-perks">
                    <li><span>✓</span> Live revenue &amp; order intelligence</li>
                    <li><span>✓</span> Inventory alerts before stock runs dry</li>
                    <li><span>✓</span> Customer insight across every blend</li>
                </ul>
            </div>

            <div class="login-aside-foot">
                <span class="lock"><AppIcon name="settings" :size="14" /></span>
                Protected area · authorised staff only
            </div>
        </aside>

        <main class="login-main">
            <div class="login-card" :class="{ shake }">
                <span class="login-badge"><AppIcon name="check" :size="13" /> Encrypted sign-in</span>
                <h1>Welcome back</h1>
                <p class="sub">Sign in to the Cha Kunjo admin panel.</p>

                <Transition name="err">
                    <div v-if="error" class="login-error">
                        <AppIcon name="warn" :size="16" />
                        <span>{{ error }}</span>
                    </div>
                </Transition>

                <form @submit.prevent="submit" novalidate>
                    <label class="field" :class="{ invalid: emailError }">
                        <span>Email address</span>
                        <input
                            v-model="email"
                            type="email"
                            autocomplete="username"
                            inputmode="email"
                            placeholder="chakunjo@gmail.com"
                            :disabled="busy"
                            @blur="touched.email = true"
                        />
                        <em v-if="emailError" class="field-msg">Enter a valid email address.</em>
                    </label>

                    <label class="field">
                        <span>Password</span>
                        <div class="pass-wrap">
                            <input
                                v-model="password"
                                :type="showPass ? 'text' : 'password'"
                                autocomplete="current-password"
                                placeholder="••••••••"
                                :disabled="busy"
                                @keyup="onKey"
                                @keydown="onKey"
                                @blur="touched.password = true"
                            />
                            <button
                                type="button"
                                class="pass-toggle"
                                :class="{ on: showPass }"
                                :aria-label="showPass ? 'Hide password' : 'Show password'"
                                tabindex="-1"
                                @click="showPass = !showPass"
                            >
                                <AppIcon name="eye" :size="18" />
                            </button>
                        </div>
                        <em v-if="capsLock" class="field-msg warn">⇪ Caps Lock is on.</em>
                    </label>

                    <div class="field-row">
                        <label class="remember">
                            <input v-model="remember" type="checkbox" /> Keep me signed in
                        </label>
                        <a href="#" class="forgot" @click.prevent>Forgot password?</a>
                    </div>

                    <button
                        class="btn btn-primary btn-block login-submit"
                        type="submit"
                        :disabled="busy || lockedFor > 0"
                    >
                        <span v-if="busy" class="spinner" />
                        <template v-if="lockedFor > 0">Try again in {{ lockedFor }}s</template>
                        <template v-else>{{ busy ? 'Signing in…' : 'Sign in' }}</template>
                    </button>
                </form>

                <div class="login-hint">
                    Demo account — <b>{{ demoCredentials.email }}</b> /
                    <b>{{ demoCredentials.password }}</b>
                    <a href="#" class="link" style="margin-left: 0.4rem" @click.prevent="fillDemo">
                        fill it in
                    </a>
                </div>

                <p class="login-foot">© 2026 Cha Kunjo · Admin v1.0</p>
            </div>
        </main>
    </div>
</template>
