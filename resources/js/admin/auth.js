import { reactive, ref, computed } from 'vue'
import { api, getToken, setToken, clearToken } from './api'

// Real JWT auth against the Laravel API. The user object is cached so the
// UI can render immediately; the token in localStorage is the source of truth.
const USER_KEY = 'ck-admin-user'

// reactive mirror of the token — localStorage is NOT reactive, so isAuthenticated
// must track this ref, otherwise the router guard reads a stale (cached) value.
const token = ref(getToken())

const state = reactive({
    user: readUser(),
})

function saveToken(t) {
    setToken(t)
    token.value = t
}

function dropToken() {
    clearToken()
    token.value = null
}

function readUser() {
    if (!getToken()) return null
    try {
        const raw = localStorage.getItem(USER_KEY)
        return raw ? JSON.parse(raw) : null
    } catch (e) {
        return null
    }
}

function cacheUser(user) {
    state.user = user
    if (user) localStorage.setItem(USER_KEY, JSON.stringify(user))
    else localStorage.removeItem(USER_KEY)
}

export const isAuthenticated = computed(() => !!token.value)
export const currentUser = computed(() => state.user)

export async function login({ email, password, remember = false }) {
    try {
        const data = await api('login', {
            method: 'POST',
            auth: false,
            body: { email, password, remember },
        })
        saveToken(data.token)
        cacheUser(data.user)
        return { ok: true }
    } catch (e) {
        return {
            ok: false,
            message: e.message || 'Login failed.',
            retryAfter: e.data?.retry_after || 0, // seconds, when rate-limited (429)
        }
    }
}

// re-validate the token on app boot; refreshes the cached user
export async function fetchMe() {
    if (!getToken()) return null
    try {
        const { user } = await api('me')
        cacheUser(user)
        return user
    } catch (e) {
        cacheUser(null)
        dropToken()
        return null
    }
}

// PUT /api/profile — update own name/email/password, refresh cached user
export async function updateProfile(payload) {
    const { user } = await api('profile', { method: 'PUT', body: payload })
    cacheUser(user)
    return user
}

export async function logout() {
    try {
        await api('logout', { method: 'POST' })
    } catch (e) {
        // ignore — clearing locally is enough
    }
    dropToken()
    cacheUser(null)
}

export const demoCredentials = { email: 'chakunjo@gmail.com', password: 'admin123' }
