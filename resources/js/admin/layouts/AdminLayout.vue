<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppIcon from '../components/AppIcon.vue'
import BrandMark from '../components/BrandMark.vue'
import { currentUser, logout } from '../auth'

const route = useRoute()
const router = useRouter()

const collapsed = ref(false)
const mobileOpen = ref(false)
const menuOpen = ref(false)

// close the avatar menu on outside click / Esc
const menuEl = ref(null)
function onDocClick(e) {
    if (menuOpen.value && menuEl.value && !menuEl.value.contains(e.target)) menuOpen.value = false
}
function onEsc(e) {
    if (e.key === 'Escape') menuOpen.value = false
}
onMounted(() => {
    document.addEventListener('click', onDocClick)
    document.addEventListener('keydown', onEsc)
})
onBeforeUnmount(() => {
    document.removeEventListener('click', onDocClick)
    document.removeEventListener('keydown', onEsc)
})

function goProfile() {
    menuOpen.value = false
    router.push('/admin/profile')
}

const nav = [
    { section: 'Overview', items: [
        { to: '/admin', name: 'Dashboard', icon: 'dashboard' },
        { to: '/admin/analytics', name: 'Analytics', icon: 'analytics' },
    ]},
    { section: 'Commerce', items: [
        { to: '/admin/orders', name: 'Orders', icon: 'orders' },
        { to: '/admin/products', name: 'Products', icon: 'products' },
        { to: '/admin/customers', name: 'Customers', icon: 'customers' },
        { to: '/admin/promo-codes', name: 'Promo Codes', icon: 'settings' },
        { to: '/admin/customer-groups', name: 'Customer Groups', icon: 'customers' },
        { to: '/admin/offers', name: 'Occasion Offers', icon: 'star' },
    ]},
    { section: 'Homepage', collapsible: true, icon: 'dashboard', items: [
        { to: '/admin/hero', name: 'Hero Banner', icon: 'dashboard' },
        { to: '/admin/marquee', name: 'Marquee', icon: 'edit' },
        { to: '/admin/story', name: 'Our Story', icon: 'edit' },
        { to: '/admin/collection', name: 'Collection', icon: 'products' },
        { to: '/admin/creations', name: 'Creations', icon: 'products' },
        { to: '/admin/promos', name: 'Promo Banners', icon: 'dashboard' },
        { to: '/admin/gift-box', name: 'Gift Box', icon: 'orders' },
        { to: '/admin/blend-finder', name: 'Blend Finder', icon: 'settings' },
        { to: '/admin/insta', name: 'Instagram', icon: 'star' },
        { to: '/admin/newsletter', name: 'Newsletter', icon: 'users' },
    ]},
    { section: 'Content', collapsible: true, icon: 'edit', items: [
        { to: '/admin/reviews', name: 'Reviews', icon: 'star' },
        { to: '/admin/faqs', name: 'FAQ', icon: 'settings' },
        { to: '/admin/blog', name: 'Journal', icon: 'edit' },
        { to: '/admin/process', name: 'Process', icon: 'leaf' },
        { to: '/admin/quotes', name: 'Quotes', icon: 'star' },
        { to: '/admin/founder', name: 'Founder Story', icon: 'users' },
    ]},
    { section: 'Site', collapsible: true, icon: 'settings', items: [
        { to: '/admin/navbar', name: 'Navbar', icon: 'menu' },
        { to: '/admin/footer', name: 'Footer', icon: 'dashboard' },
        { to: '/admin/seo', name: 'SEO & Meta', icon: 'analytics' },
    ]},
    { section: 'System', items: [
        { to: '/admin/settings', name: 'Settings', icon: 'settings' },
    ]},
]

/* collapsible nav groups (submenu dropdowns) */
const openGroups = ref({})
function toggleGroup(section) {
    openGroups.value = { ...openGroups.value, [section]: !openGroups.value[section] }
}
function groupHasActive(group) {
    return group.items.some((i) => i.to !== '/admin' && route.path.startsWith(i.to))
}
// auto-open the group that holds the current route
watch(
    () => route.path,
    () => {
        for (const g of nav) {
            if (g.collapsible && groupHasActive(g)) openGroups.value[g.section] = true
        }
    },
    { immediate: true }
)

const title = computed(() => route.meta.title || 'Dashboard')

const initials = computed(() =>
    (currentUser.value?.name || 'Admin')
        .split(' ')
        .map((w) => w[0])
        .slice(0, 2)
        .join('')
        .toUpperCase()
)

async function signOut() {
    await logout()
    router.push('/login')
}
</script>

<template>
    <div class="shell">
        <aside class="sidebar" :class="{ collapsed, 'mobile-open': mobileOpen }">
            <div class="sidebar-brand">
                <span class="mark"><BrandMark :size="21" /></span>
                <span class="word">
                    Cha<em>Kunjo</em>
                    <small>Admin Panel</small>
                </span>
            </div>

            <nav>
                <template v-for="group in nav" :key="group.section">
                    <!-- collapsible group: parent toggle + submenu dropdown -->
                    <template v-if="group.collapsible">
                        <button
                            class="nav-item nav-group-toggle"
                            :class="{ open: openGroups[group.section], 'has-active': groupHasActive(group) }"
                            :title="group.section"
                            @click="toggleGroup(group.section)"
                        >
                            <AppIcon :name="group.icon" :size="19" />
                            <span>{{ group.section }}</span>
                            <svg class="nav-chev" viewBox="0 0 24 24" width="15" height="15" aria-hidden="true">
                                <path fill="currentColor" d="M8.12 9.29 12 13.17l3.88-3.88L17.29 10.7l-5.29 5.3-5.29-5.3z" />
                            </svg>
                        </button>
                        <Transition name="submenu">
                            <div v-show="openGroups[group.section]" class="nav-sub">
                                <RouterLink
                                    v-for="item in group.items"
                                    :key="item.to"
                                    :to="item.to"
                                    class="nav-item nav-subitem"
                                    active-class="router-link-active"
                                    exact-active-class="router-link-active"
                                    :title="item.name"
                                    @click="mobileOpen = false"
                                >
                                    <span class="nav-sub-dot" />
                                    <span>{{ item.name }}</span>
                                </RouterLink>
                            </div>
                        </Transition>
                    </template>

                    <!-- flat group -->
                    <template v-else>
                        <p class="nav-label">{{ group.section }}</p>
                        <RouterLink
                            v-for="item in group.items"
                            :key="item.to"
                            :to="item.to"
                            class="nav-item"
                            :active-class="item.to === '/admin' ? 'nav-parent' : 'router-link-active'"
                            exact-active-class="router-link-active"
                            :title="item.name"
                            @click="mobileOpen = false"
                        >
                            <AppIcon :name="item.icon" :size="19" />
                            <span>{{ item.name }}</span>
                            <span v-if="item.badge" class="badge">{{ item.badge }}</span>
                        </RouterLink>
                    </template>
                </template>
            </nav>

            <div class="sidebar-foot">
                <button class="sidebar-logout" @click="signOut">
                    <AppIcon name="logout" :size="18" />
                    <span>Sign out</span>
                </button>
            </div>
        </aside>

        <div v-if="mobileOpen" class="scrim" @click="mobileOpen = false" />

        <div class="main" :class="{ wide: collapsed }">
            <header class="topbar">
                <button
                    class="icon-btn desktop-only"
                    aria-label="Collapse menu"
                    @click="collapsed = !collapsed"
                >
                    <AppIcon name="menu" :size="20" />
                </button>
                <button class="icon-btn mobile-only" aria-label="Open menu" @click="mobileOpen = true">
                    <AppIcon name="menu" :size="20" />
                </button>

                <div class="topbar-search">
                    <AppIcon name="search" :size="17" />
                    <input type="search" placeholder="Search orders, products, customers…" />
                </div>

                <span class="topbar-spacer" />

                <button class="icon-btn bell" aria-label="Notifications">
                    <AppIcon name="bell" :size="19" />
                    <span class="dot" />
                </button>

                <div class="avatar-wrap" ref="menuEl">
                    <button class="avatar" :class="{ open: menuOpen }" @click="menuOpen = !menuOpen">
                        <span class="ring">{{ initials }}</span>
                        <span class="who">
                            <strong>{{ currentUser?.name }}</strong>
                            <span>{{ currentUser?.role }}</span>
                        </span>
                        <AppIcon name="down" :size="16" class="chev" />
                    </button>

                    <Transition name="menu">
                        <div v-if="menuOpen" class="avatar-menu">
                            <div class="am-head">
                                <span class="ring">{{ initials }}</span>
                                <div>
                                    <strong>{{ currentUser?.name }}</strong>
                                    <em>{{ currentUser?.email }}</em>
                                </div>
                            </div>
                            <button class="am-item" @click="goProfile">
                                <AppIcon name="users" :size="17" />
                                Profile &amp; password
                            </button>
                            <button class="am-item danger" @click="signOut">
                                <AppIcon name="logout" :size="17" />
                                Sign out
                            </button>
                        </div>
                    </Transition>
                </div>
            </header>

            <RouterView v-slot="{ Component }">
                <Transition name="fade" mode="out-in">
                    <component :is="Component" />
                </Transition>
            </RouterView>
        </div>
    </div>
</template>

<style scoped>
.mobile-only {
    display: none;
}

@media (max-width: 860px) {
    .desktop-only {
        display: none;
    }
    .mobile-only {
        display: grid;
    }
}
</style>
