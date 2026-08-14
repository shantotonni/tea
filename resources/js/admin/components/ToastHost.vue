<script setup>
import { toasts, toast } from '../composables/useToast'

const icon = {
    success: 'M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z',
    error: 'M12 2 1 21h22L12 2zm1 15h-2v-2h2v2zm0-4h-2V9h2v4z',
    warn: 'M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z',
    info: 'M11 9h2V7h-2m1 13a8 8 0 110-16 8 8 0 010 16zm-1-4h2v-6h-2v6z',
}
</script>

<template>
    <div class="toast-host" aria-live="polite">
        <TransitionGroup name="toast">
            <div v-for="t in toasts.items" :key="t.id" class="toast" :class="t.type">
                <span class="toast-ico">
                    <svg viewBox="0 0 24 24" width="17" height="17">
                        <path :d="icon[t.type]" fill="currentColor" />
                    </svg>
                </span>
                <p>{{ t.message }}</p>
                <button class="toast-x" aria-label="Dismiss" @click="toast.dismiss(t.id)">
                    <svg viewBox="0 0 24 24" width="14" height="14">
                        <path
                            fill="currentColor"
                            d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"
                        />
                    </svg>
                </button>
                <span class="toast-bar" />
            </div>
        </TransitionGroup>
    </div>
</template>
