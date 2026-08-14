import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import { fetchMe } from './auth'
import '../../css/admin.css'

// validate any stored token before the first render so an expired
// session lands on /login instead of a broken dashboard
fetchMe().finally(() => {
    createApp(App).use(router).mount('#admin-app')
})
