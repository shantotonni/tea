import { reactive } from 'vue'

// Global toast queue. Import anywhere (even non-component modules like api.js):
//   import { toast } from '../composables/useToast'
//   toast.success('Saved'); toast.error('Failed')

let seq = 0
const state = reactive({ items: [] })

function push(type, message, timeout = 3500) {
    if (!message) return
    const id = ++seq
    state.items.push({ id, type, message })
    if (timeout) setTimeout(() => dismiss(id), timeout)
    return id
}

function dismiss(id) {
    const i = state.items.findIndex((t) => t.id === id)
    if (i !== -1) state.items.splice(i, 1)
}

export const toasts = state
export const toast = {
    success: (m, t) => push('success', m, t),
    error: (m, t) => push('error', m, t ?? 5000),
    info: (m, t) => push('info', m, t),
    warn: (m, t) => push('warn', m, t),
    dismiss,
}
