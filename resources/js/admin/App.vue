<script setup>
import ToastHost from './components/ToastHost.vue'
</script>

<template>
    <div style="display: contents">
        <RouterView v-slot="{ Component }">
            <Transition name="fade" mode="out-in">
                <component :is="Component" />
            </Transition>
        </RouterView>

        <!-- global success/error notifications, available in every module -->
        <ToastHost />
    </div>
</template>
