import { api } from './api'

/* ------------------------------------------------------------------ *
 * Live API fetchers — every list below comes from the Laravel API.
 * ------------------------------------------------------------------ */

const fmtDate = (iso) => {
    if (!iso) return ''
    const d = new Date(iso)
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}

export async function fetchDashboard() {
    return api('dashboard')
}

export async function fetchAnalytics() {
    return api('analytics')
}

export async function fetchProducts(search = '') {
    const q = search ? `?search=${encodeURIComponent(search)}` : ''
    const { data } = await api(`products${q}`)
    return data // full rows incl. id, name, sku, category, price, old_price, stock, status, image, tag, blurb, weight
}

export async function createProduct(payload) {
    const { data } = await api('products', { method: 'POST', body: payload })
    return data
}

export async function updateProduct(id, payload) {
    const { data } = await api(`products/${id}`, { method: 'PUT', body: payload })
    return data
}

export async function deleteProduct(id) {
    return api(`products/${id}`, { method: 'DELETE' })
}

export async function fetchOrders({ status = 'All', search = '' } = {}) {
    const params = new URLSearchParams()
    if (status && status !== 'All') params.set('status', status)
    if (search) params.set('search', search)
    const qs = params.toString()
    const { data } = await api(`orders${qs ? '?' + qs : ''}`)
    return data.map((o) => ({
        key: o.id,            // numeric db id (for detail / status update)
        id: o.code,
        customer: o.customer_name,
        email: o.customer_email,
        phone: o.phone,
        city: o.city,
        items: o.items_count,
        total: o.total,
        status: o.status,
        channel: o.channel,
        date: fmtDate(o.created_at),
    }))
}

export async function fetchOrder(id) {
    const { data } = await api(`orders/${id}`)
    return data
}
export async function setOrderStatus(id, status) {
    const { data } = await api(`orders/${id}`, { method: 'PUT', body: { status } })
    return data
}

export async function fetchCustomers({ search = '', tier = '' } = {}) {
    const params = new URLSearchParams()
    if (search) params.set('search', search)
    if (tier) params.set('tier', tier)
    const qs = params.toString()
    const { data } = await api(`customers${qs ? '?' + qs : ''}`)
    return data.map((c) => ({
        id: c.id,
        name: c.name,
        email: c.email,
        phone: c.phone,
        city: c.city,
        orders: c.orders_count,
        spent: c.spent,
        tier: c.tier,
        joined: fmtDate(c.created_at),
    }))
}

export async function fetchCustomer(id) {
    const { data } = await api(`customers/${id}`)
    return data
}
export async function createCustomer(payload) {
    const { data } = await api('customers', { method: 'POST', body: payload })
    return data
}
export async function updateCustomer(id, payload) {
    const { data } = await api(`customers/${id}`, { method: 'PUT', body: payload })
    return data
}
export async function deleteCustomer(id) {
    return api(`customers/${id}`, { method: 'DELETE' })
}

export async function updateOrderStatus(code, status) {
    // code like "#CK-2841" — the API keys on the numeric id, so callers pass the id
    return api(`orders/${code}`, { method: 'PUT', body: { status } })
}

/* ---- reviews ---- */
export async function fetchReviews({ lang = '', search = '' } = {}) {
    const params = new URLSearchParams()
    if (lang) params.set('lang', lang)
    if (search) params.set('search', search)
    const qs = params.toString()
    const { data } = await api(`reviews${qs ? '?' + qs : ''}`)
    return data
}
export async function createReview(payload) {
    const { data } = await api('reviews', { method: 'POST', body: payload })
    return data
}
export async function updateReview(id, payload) {
    const { data } = await api(`reviews/${id}`, { method: 'PUT', body: payload })
    return data
}
export async function deleteReview(id) {
    return api(`reviews/${id}`, { method: 'DELETE' })
}

/* ---- process steps ---- */
export async function fetchProcess() {
    const { data } = await api('process')
    return data
}
export async function createStep(payload) {
    const { data } = await api('process', { method: 'POST', body: payload })
    return data
}
export async function updateStep(id, payload) {
    const { data } = await api(`process/${id}`, { method: 'PUT', body: payload })
    return data
}
export async function deleteStep(id) {
    return api(`process/${id}`, { method: 'DELETE' })
}

/* ---- quotes (wisdom / health) ---- */
export async function fetchQuotes(tab = '') {
    const q = tab ? `?tab=${encodeURIComponent(tab)}` : ''
    const { data } = await api(`quotes${q}`)
    return data
}
export async function createQuote(payload) {
    const { data } = await api('quotes', { method: 'POST', body: payload })
    return data
}
export async function updateQuote(id, payload) {
    const { data } = await api(`quotes/${id}`, { method: 'PUT', body: payload })
    return data
}
export async function deleteQuote(id) {
    return api(`quotes/${id}`, { method: 'DELETE' })
}

/* ---- faqs ---- */
export async function fetchFaqs(search = '') {
    const q = search ? `?search=${encodeURIComponent(search)}` : ''
    const { data } = await api(`faqs${q}`)
    return data
}
export async function createFaq(payload) {
    const { data } = await api('faqs', { method: 'POST', body: payload })
    return data
}
export async function updateFaq(id, payload) {
    const { data } = await api(`faqs/${id}`, { method: 'PUT', body: payload })
    return data
}
export async function deleteFaq(id) {
    return api(`faqs/${id}`, { method: 'DELETE' })
}

/* ---- blog ---- */
export async function fetchPosts({ category = '', search = '' } = {}) {
    const params = new URLSearchParams()
    if (category) params.set('category', category)
    if (search) params.set('search', search)
    const qs = params.toString()
    const { data } = await api(`blog${qs ? '?' + qs : ''}`)
    return data
}
export async function createPost(payload) {
    const { data } = await api('blog', { method: 'POST', body: payload })
    return data
}
export async function updatePost(id, payload) {
    const { data } = await api(`blog/${id}`, { method: 'PUT', body: payload })
    return data
}
export async function deletePost(id) {
    return api(`blog/${id}`, { method: 'DELETE' })
}

/* ---- founders + story points ---- */
export async function fetchFounders() {
    const { data } = await api('founders')
    return data
}
export async function createFounder(payload) {
    const { data } = await api('founders', { method: 'POST', body: payload })
    return data
}
export async function updateFounder(id, payload) {
    const { data } = await api(`founders/${id}`, { method: 'PUT', body: payload })
    return data
}
export async function deleteFounder(id) {
    return api(`founders/${id}`, { method: 'DELETE' })
}

export async function fetchFounderPoints() {
    const { data } = await api('founder-points')
    return data
}
export async function createFounderPoint(payload) {
    const { data } = await api('founder-points', { method: 'POST', body: payload })
    return data
}
export async function updateFounderPoint(id, payload) {
    const { data } = await api(`founder-points/${id}`, { method: 'PUT', body: payload })
    return data
}
export async function deleteFounderPoint(id) {
    return api(`founder-points/${id}`, { method: 'DELETE' })
}

/* ---- hero: slides / features / stats ---- */
export async function fetchHeroSlides() {
    const { data } = await api('hero-slides')
    return data
}
export async function createHeroSlide(payload) {
    const { data } = await api('hero-slides', { method: 'POST', body: payload })
    return data
}
export async function updateHeroSlide(id, payload) {
    const { data } = await api(`hero-slides/${id}`, { method: 'PUT', body: payload })
    return data
}
export async function deleteHeroSlide(id) {
    return api(`hero-slides/${id}`, { method: 'DELETE' })
}

export async function fetchHeroFeatures() {
    const { data } = await api('hero-features')
    return data
}
export async function createHeroFeature(payload) {
    const { data } = await api('hero-features', { method: 'POST', body: payload })
    return data
}
export async function updateHeroFeature(id, payload) {
    const { data } = await api(`hero-features/${id}`, { method: 'PUT', body: payload })
    return data
}
export async function deleteHeroFeature(id) {
    return api(`hero-features/${id}`, { method: 'DELETE' })
}

export async function fetchHeroStats() {
    const { data } = await api('hero-stats')
    return data
}
export async function createHeroStat(payload) {
    const { data } = await api('hero-stats', { method: 'POST', body: payload })
    return data
}
export async function updateHeroStat(id, payload) {
    const { data } = await api(`hero-stats/${id}`, { method: 'PUT', body: payload })
    return data
}
export async function deleteHeroStat(id) {
    return api(`hero-stats/${id}`, { method: 'DELETE' })
}

/* ---- marquee ---- */
export async function fetchMarquee() {
    const { data } = await api('marquee')
    return data
}
export async function createMarquee(payload) {
    const { data } = await api('marquee', { method: 'POST', body: payload })
    return data
}
export async function updateMarquee(id, payload) {
    const { data } = await api(`marquee/${id}`, { method: 'PUT', body: payload })
    return data
}
export async function deleteMarquee(id) {
    return api(`marquee/${id}`, { method: 'DELETE' })
}

/* ---- story points ---- */
export async function fetchStoryPoints() {
    const { data } = await api('story-points')
    return data
}
export async function createStoryPoint(payload) {
    const { data } = await api('story-points', { method: 'POST', body: payload })
    return data
}
export async function updateStoryPoint(id, payload) {
    const { data } = await api(`story-points/${id}`, { method: 'PUT', body: payload })
    return data
}
export async function deleteStoryPoint(id) {
    return api(`story-points/${id}`, { method: 'DELETE' })
}

/* ---- blend finder: questions + options ---- */
export async function fetchBlendQuestions() {
    const { data } = await api('blend-questions')
    return data
}
export async function createBlendQuestion(payload) {
    const { data } = await api('blend-questions', { method: 'POST', body: payload })
    return data
}
export async function updateBlendQuestion(id, payload) {
    const { data } = await api(`blend-questions/${id}`, { method: 'PUT', body: payload })
    return data
}
export async function deleteBlendQuestion(id) {
    return api(`blend-questions/${id}`, { method: 'DELETE' })
}
export async function createBlendOption(payload) {
    const { data } = await api('blend-options', { method: 'POST', body: payload })
    return data
}
export async function updateBlendOption(id, payload) {
    const { data } = await api(`blend-options/${id}`, { method: 'PUT', body: payload })
    return data
}
export async function deleteBlendOption(id) {
    return api(`blend-options/${id}`, { method: 'DELETE' })
}

/* ---- navbar links ---- */
export async function fetchNavLinks() {
    const { data } = await api('nav-links')
    return data
}
export async function createNavLink(payload) {
    const { data } = await api('nav-links', { method: 'POST', body: payload })
    return data
}
export async function updateNavLink(id, payload) {
    const { data } = await api(`nav-links/${id}`, { method: 'PUT', body: payload })
    return data
}
export async function deleteNavLink(id) {
    return api(`nav-links/${id}`, { method: 'DELETE' })
}

/* ---- footer links ---- */
export async function fetchFooterLinks() {
    const { data } = await api('footer-links')
    return data
}
export async function createFooterLink(payload) {
    const { data } = await api('footer-links', { method: 'POST', body: payload })
    return data
}
export async function updateFooterLink(id, payload) {
    const { data } = await api(`footer-links/${id}`, { method: 'PUT', body: payload })
    return data
}
export async function deleteFooterLink(id) {
    return api(`footer-links/${id}`, { method: 'DELETE' })
}

/* ---- social links ---- */
export async function fetchSocialLinks() {
    const { data } = await api('social-links')
    return data
}
export async function createSocialLink(payload) {
    const { data } = await api('social-links', { method: 'POST', body: payload })
    return data
}
export async function updateSocialLink(id, payload) {
    const { data } = await api(`social-links/${id}`, { method: 'PUT', body: payload })
    return data
}
export async function deleteSocialLink(id) {
    return api(`social-links/${id}`, { method: 'DELETE' })
}

/* ---- creation tiles ---- */
export async function fetchCreationTiles() {
    const { data } = await api('creation-tiles')
    return data
}
export async function createCreationTile(payload) {
    const { data } = await api('creation-tiles', { method: 'POST', body: payload })
    return data
}
export async function updateCreationTile(id, payload) {
    const { data } = await api(`creation-tiles/${id}`, { method: 'PUT', body: payload })
    return data
}
export async function deleteCreationTile(id) {
    return api(`creation-tiles/${id}`, { method: 'DELETE' })
}

/* ---- promo banners ---- */
export async function fetchPromoBanners() {
    const { data } = await api('promo-banners')
    return data
}
export async function createPromoBanner(payload) {
    const { data } = await api('promo-banners', { method: 'POST', body: payload })
    return data
}
export async function updatePromoBanner(id, payload) {
    const { data } = await api(`promo-banners/${id}`, { method: 'PUT', body: payload })
    return data
}
export async function deletePromoBanner(id) {
    return api(`promo-banners/${id}`, { method: 'DELETE' })
}

/* ---- instagram shots ---- */
export async function fetchInstaShots() {
    const { data } = await api('insta-shots')
    return data
}
export async function createInstaShot(payload) {
    const { data } = await api('insta-shots', { method: 'POST', body: payload })
    return data
}
export async function updateInstaShot(id, payload) {
    const { data } = await api(`insta-shots/${id}`, { method: 'PUT', body: payload })
    return data
}
export async function deleteInstaShot(id) {
    return api(`insta-shots/${id}`, { method: 'DELETE' })
}

/* ---- collection notes ---- */
export async function fetchCollectionNotes() {
    const { data } = await api('collection-notes')
    return data
}
export async function createCollectionNote(payload) {
    const { data } = await api('collection-notes', { method: 'POST', body: payload })
    return data
}
export async function updateCollectionNote(id, payload) {
    const { data } = await api(`collection-notes/${id}`, { method: 'PUT', body: payload })
    return data
}
export async function deleteCollectionNote(id) {
    return api(`collection-notes/${id}`, { method: 'DELETE' })
}

/* ---- subscribers ---- */
export async function fetchSubscribers() {
    const { data } = await api('subscribers')
    return data
}
export async function deleteSubscriber(id) {
    return api(`subscribers/${id}`, { method: 'DELETE' })
}

/* ---- promo codes ---- */
export async function fetchPromoCodes() {
    const { data } = await api('promo-codes')
    return data
}
export async function createPromoCode(payload) {
    const { data } = await api('promo-codes', { method: 'POST', body: payload })
    return data
}
export async function updatePromoCode(id, payload) {
    const { data } = await api(`promo-codes/${id}`, { method: 'PUT', body: payload })
    return data
}
export async function deletePromoCode(id) {
    return api(`promo-codes/${id}`, { method: 'DELETE' })
}

/* ---- customer groups ---- */
export async function fetchCustomerGroups() {
    const { data } = await api('customer-groups')
    return data
}
export async function fetchCustomerGroup(id) {
    const { data } = await api(`customer-groups/${id}`)
    return data
}
export async function createCustomerGroup(payload) {
    const { data } = await api('customer-groups', { method: 'POST', body: payload })
    return data
}
export async function updateCustomerGroup(id, payload) {
    const { data } = await api(`customer-groups/${id}`, { method: 'PUT', body: payload })
    return data
}
export async function deleteCustomerGroup(id) {
    return api(`customer-groups/${id}`, { method: 'DELETE' })
}
export async function syncGroupMembers(id, customerIds) {
    return api(`customer-groups/${id}/members`, { method: 'PUT', body: { customer_ids: customerIds } })
}

/* ---- offer campaigns (occasion / festival offers) ---- */
export async function fetchOfferCampaigns() {
    const { data } = await api('offer-campaigns')
    return data
}
export async function fetchOfferCampaign(id) {
    const { data } = await api(`offer-campaigns/${id}`)
    return data
}
export async function createOfferCampaign(payload) {
    const { data } = await api('offer-campaigns', { method: 'POST', body: payload })
    return data
}
export async function updateOfferCampaign(id, payload) {
    const { data } = await api(`offer-campaigns/${id}`, { method: 'PUT', body: payload })
    return data
}
export async function deleteOfferCampaign(id) {
    return api(`offer-campaigns/${id}`, { method: 'DELETE' })
}

export async function fetchSettings() {
    const { data } = await api('settings')
    return data // { store: {...}, notifications: {...}, ... }
}

export async function saveSettings(group, values) {
    const { data } = await api(`settings/${group}`, { method: 'PUT', body: values })
    return data
}

export async function testAiConnection(payload) {
    return api('settings/ai/test', { method: 'POST', body: payload })
}

/* ------------------------------------------------------------------ *
 * Static presentation data (no backing table yet)
 * ------------------------------------------------------------------ */

// 12-month revenue trend — chart scaffolding; the backend has no monthly
// time-series table, so this stays as a visual until orders accrue over time.
export const revenueSeries = {
    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
    series: [
        {
            name: 'Revenue 2026',
            color: '#2c6b45',
            values: [72, 81, 96, 92, 114, 124, 138, 133, 155, 169, 184, 203].map((v) => v * 1000),
        },
        {
            name: 'Revenue 2025',
            color: '#c8a24a',
            values: [55, 62, 70, 75, 83, 90, 97, 104, 110, 118, 127, 139].map((v) => v * 1000),
        },
    ],
}

const CAT_COLORS = ['#2c6b45', '#3f8a5c', '#c8a24a', '#e0c880']

// turn the API's category_split into the donut shape
export function toCategorySplit(rows = []) {
    return rows.map((r, i) => ({
        label: r.category ?? r.label,
        value: Number(r.value),
        color: CAT_COLORS[i % CAT_COLORS.length],
    }))
}

/* ------------------------------------------------------------------ *
 * Pure helpers
 * ------------------------------------------------------------------ */

export const statusClass = (status) =>
    ({
        Delivered: '',
        Shipped: 'shipped',
        Pending: 'pending',
        Cancelled: 'cancelled',
        Active: '',
        'Low stock': 'pending',
        'Out of stock': 'cancelled',
        Gold: 'pending',
        Silver: 'shipped',
        Bronze: '',
    }[status] ?? '')

export const initials = (name) =>
    (name || '')
        .split(' ')
        .map((w) => w[0])
        .slice(0, 2)
        .join('')
        .toUpperCase()

export const money = (n) => `৳${Number(n || 0).toLocaleString()}`

export const asset = (path) => {
    const p = String(path || '')
    if (/^https?:\/\//i.test(p)) return p // already absolute
    const base = (window.__ADMIN_BASE__ || '/').replace(/\/+$/, '')
    return base + '/' + p.replace(/^\/+/, '') // normalise: single slash, no leading-slash double
}
