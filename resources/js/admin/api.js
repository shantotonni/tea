// Thin fetch wrapper around the Cha Kunjo JSON API.
// The SPA is served by Laravel itself, so the API lives at <base>api/.

import { toast } from './composables/useToast'

const BASE = (window.__ADMIN_BASE__ || '/') + 'api'
const TOKEN_KEY = 'ck-admin-token'

export const getToken = () => localStorage.getItem(TOKEN_KEY)
export const setToken = (t) => localStorage.setItem(TOKEN_KEY, t)
export const clearToken = () => localStorage.removeItem(TOKEN_KEY)

export class ApiError extends Error {
    constructor(message, status, data = null) {
        super(message)
        this.status = status
        this.data = data
    }
}

export async function api(path, { method = 'GET', body, auth = true, silent = false } = {}) {
    const headers = { Accept: 'application/json' }
    if (body) headers['Content-Type'] = 'application/json'
    if (auth && getToken()) headers.Authorization = `Bearer ${getToken()}`

    const res = await fetch(`${BASE}/${path.replace(/^\//, '')}`, {
        method,
        headers,
        body: body ? JSON.stringify(body) : undefined,
    })

    // 401 anywhere means the token is gone/expired — bounce to login
    if (res.status === 401 && path !== 'login') {
        clearToken()
        if (!location.pathname.endsWith('/login')) {
            location.href = (window.__ADMIN_BASE__ || '/') + 'login'
        }
    }

    let data = null
    try {
        data = await res.json()
    } catch (e) {
        data = null
    }

    if (!res.ok) {
        const message = data?.message || `Request failed (${res.status})`
        // auto error toast — skipped for login (inline UI), 401 (redirecting),
        // 422 validation (forms surface field errors), or explicit silent calls
        if (!silent && path !== 'login' && res.status !== 401 && res.status !== 422) {
            toast.error(message)
        }
        throw new ApiError(message, res.status, data)
    }

    return data
}

export async function uploadFile(file) {
    const formData = new FormData()
    formData.append('image', file)
    const headers = {}
    if (getToken()) headers.Authorization = `Bearer ${getToken()}`

    const res = await fetch(`${BASE}/upload`, {
        method: 'POST',
        headers,
        body: formData,
    })
    const data = await res.json()
    if (!res.ok) throw new ApiError(data?.message || 'Upload failed', res.status, data)
    return data.url
}
