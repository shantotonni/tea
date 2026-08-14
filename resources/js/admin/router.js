import { createRouter, createWebHistory } from 'vue-router'
import { isAuthenticated } from './auth'

import Login from './pages/Login.vue'
import AdminLayout from './layouts/AdminLayout.vue'
import Dashboard from './pages/Dashboard.vue'
import Orders from './pages/Orders.vue'
import OrderDetail from './pages/OrderDetail.vue'
import Products from './pages/Products.vue'
import Customers from './pages/Customers.vue'
import Analytics from './pages/Analytics.vue'
import Settings from './pages/Settings.vue'
import Profile from './pages/Profile.vue'
import Reviews from './pages/Reviews.vue'
import Faqs from './pages/Faqs.vue'
import Blog from './pages/Blog.vue'
import Process from './pages/Process.vue'
import Quotes from './pages/Quotes.vue'
import Founder from './pages/Founder.vue'
import Hero from './pages/Hero.vue'
import Marquee from './pages/Marquee.vue'
import Story from './pages/Story.vue'
import BlendFinder from './pages/BlendFinder.vue'
import Navbar from './pages/Navbar.vue'
import Footer from './pages/Footer.vue'
import Seo from './pages/Seo.vue'
import Collection from './pages/Collection.vue'
import Creations from './pages/Creations.vue'
import Promos from './pages/Promos.vue'
import Insta from './pages/Insta.vue'
import Newsletter from './pages/Newsletter.vue'
import GiftBox from './pages/GiftBox.vue'
import PromoCodes from './pages/PromoCodes.vue'
import CustomerGroups from './pages/CustomerGroups.vue'
import Offers from './pages/Offers.vue'

const routes = [
    { path: '/login', name: 'login', component: Login, meta: { guestOnly: true } },
    {
        path: '/admin',
        component: AdminLayout,
        meta: { requiresAuth: true },
        children: [
            { path: '', name: 'dashboard', component: Dashboard, meta: { title: 'Dashboard' } },
            { path: 'orders', name: 'orders', component: Orders, meta: { title: 'Orders' } },
            { path: 'orders/:id', name: 'order-detail', component: OrderDetail, meta: { title: 'Order' } },
            { path: 'products', name: 'products', component: Products, meta: { title: 'Products' } },
            { path: 'customers', name: 'customers', component: Customers, meta: { title: 'Customers' } },
            { path: 'promo-codes', name: 'promo-codes', component: PromoCodes, meta: { title: 'Promo Codes' } },
            { path: 'customer-groups', name: 'customer-groups', component: CustomerGroups, meta: { title: 'Customer Groups' } },
            { path: 'offers', name: 'offers', component: Offers, meta: { title: 'Occasion Offers' } },
            { path: 'reviews', name: 'reviews', component: Reviews, meta: { title: 'Reviews' } },
            { path: 'faqs', name: 'faqs', component: Faqs, meta: { title: 'FAQ' } },
            { path: 'blog', name: 'blog', component: Blog, meta: { title: 'Journal' } },
            { path: 'process', name: 'process', component: Process, meta: { title: 'Process' } },
            { path: 'quotes', name: 'quotes', component: Quotes, meta: { title: 'Quotes' } },
            { path: 'founder', name: 'founder', component: Founder, meta: { title: 'Founder Story' } },
            { path: 'hero', name: 'hero', component: Hero, meta: { title: 'Hero Banner' } },
            { path: 'marquee', name: 'marquee', component: Marquee, meta: { title: 'Marquee Strip' } },
            { path: 'story', name: 'story', component: Story, meta: { title: 'Our Story' } },
            { path: 'blend-finder', name: 'blend-finder', component: BlendFinder, meta: { title: 'Blend Finder' } },
            { path: 'collection', name: 'collection', component: Collection, meta: { title: 'Collection Section' } },
            { path: 'creations', name: 'creations', component: Creations, meta: { title: 'Creations Collage' } },
            { path: 'promos', name: 'promos', component: Promos, meta: { title: 'Promo Banners' } },
            { path: 'gift-box', name: 'gift-box', component: GiftBox, meta: { title: 'Gift Box' } },
            { path: 'insta', name: 'insta', component: Insta, meta: { title: 'Instagram Strip' } },
            { path: 'newsletter', name: 'newsletter', component: Newsletter, meta: { title: 'Newsletter' } },
            { path: 'navbar', name: 'navbar', component: Navbar, meta: { title: 'Navbar' } },
            { path: 'footer', name: 'footer', component: Footer, meta: { title: 'Footer' } },
            { path: 'seo', name: 'seo', component: Seo, meta: { title: 'SEO & Social Meta' } },
            { path: 'analytics', name: 'analytics', component: Analytics, meta: { title: 'Analytics' } },
            { path: 'settings', name: 'settings', component: Settings, meta: { title: 'Settings' } },
            { path: 'profile', name: 'profile', component: Profile, meta: { title: 'My Profile' } },
        ],
    },
    { path: '/:pathMatch(.*)*', redirect: '/admin' },
]

const router = createRouter({
    history: createWebHistory(window.__ADMIN_BASE__ || '/'),
    routes,
    scrollBehavior: () => ({ top: 0 }),
})

router.beforeEach((to) => {
    if (to.meta.requiresAuth && !isAuthenticated.value) return { name: 'login' }
    if (to.meta.guestOnly && isAuthenticated.value) return { name: 'dashboard' }
    return true
})

export default router
