import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      component: () => import('@/layouts/MainLayout.vue'),
      children: [
        { path: '', name: 'home', component: () => import('@/views/HomeView.vue'), meta: { fullWidth: true } },
        { path: 'catalog', name: 'catalog', component: () => import('@/views/CatalogView.vue') },
        { path: 'search', name: 'search', component: () => import('@/views/SearchView.vue') },
        { path: 'product/:slug', name: 'product', component: () => import('@/views/ProductView.vue') },
        { path: 'cart', name: 'cart', component: () => import('@/views/CartView.vue') },
        { path: 'checkout', name: 'checkout', component: () => import('@/views/CheckoutView.vue'), meta: { requiresAuth: true } },
        { path: 'login', name: 'login', component: () => import('@/views/LoginView.vue'), meta: { guestOnly: true } },
        { path: 'register', name: 'register', component: () => import('@/views/RegisterView.vue'), meta: { guestOnly: true } },
        { path: 'profile', name: 'profile', component: () => import('@/views/ProfileView.vue'), meta: { requiresAuth: true } },
        { path: 'delivery', name: 'delivery', component: () => import('@/views/DeliveryView.vue') },
        { path: 'wholesale', name: 'wholesale', component: () => import('@/views/WholesaleView.vue') },
        {
          path: 'legal/:slug',
          name: 'legal-document',
          component: () => import('@/views/LegalDocumentView.vue'),
        },
      ],
    },
    {
      path: '/admin',
      component: () => import('@/layouts/AdminLayout.vue'),
      meta: { requiresAuth: true, requiresAdmin: true },
      children: [
        { path: '', name: 'admin-dashboard', component: () => import('@/views/admin/DashboardView.vue'), meta: { requiresAuth: true, requiresAdmin: true } },
        { path: 'products', name: 'admin-products', component: () => import('@/views/admin/ProductsView.vue'), meta: { requiresAuth: true, requiresAdmin: true } },
        { path: 'categories', name: 'admin-categories', component: () => import('@/views/admin/CategoriesView.vue'), meta: { requiresAuth: true, requiresAdmin: true } },
        { path: 'orders', name: 'admin-orders', component: () => import('@/views/admin/OrdersView.vue'), meta: { requiresAuth: true, requiresAdmin: true } },
        { path: 'clients', name: 'admin-clients', component: () => import('@/views/admin/ClientsView.vue'), meta: { requiresAuth: true, requiresAdmin: true } },
        { path: 'menu', name: 'admin-menu', component: () => import('@/views/admin/MenuItemsView.vue'), meta: { requiresAuth: true, requiresAdmin: true } },
        { path: 'hero-slides', name: 'admin-hero-slides', component: () => import('@/views/admin/HeroSlidesView.vue'), meta: { requiresAuth: true, requiresAdmin: true } },
        { path: 'site', name: 'admin-site', component: () => import('@/views/admin/SiteSettingsView.vue'), meta: { requiresAuth: true, requiresAdmin: true } },
        { path: 'delivery', name: 'admin-delivery', component: () => import('@/views/admin/DeliverySettingsView.vue'), meta: { requiresAuth: true, requiresAdmin: true } },
      ],
    },
  ],
  scrollBehavior(to, from, savedPosition) {
    if (to.hash) {
      // На главной высота меняется после загрузки полок — точный скролл
      // дорабатывает useHashScroll. Здесь только мягкая ранняя попытка.
      return new Promise((resolve) => {
        setTimeout(() => {
          if (!document.querySelector(to.hash)) {
            resolve({ top: 0 })
            return
          }
          resolve({ el: to.hash, top: 80, behavior: 'smooth' })
        }, 50)
      })
    }

    if (savedPosition) {
      return savedPosition
    }

    return { top: 0 }
  },
})

function requiresAuth(to) {
  return to.matched.some((record) => record.meta.requiresAuth)
}

function requiresAdmin(to) {
  return to.matched.some((record) => record.meta.requiresAdmin)
}

router.beforeEach(async (to) => {
  const auth = useAuthStore()
  await auth.bootstrap()

  if (requiresAuth(to) && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.meta.guestOnly && auth.isAuthenticated) {
    return { name: 'home' }
  }

  if (requiresAdmin(to) && auth.isAuthenticated && !auth.isAdmin) {
    return { name: 'home', query: { error: 'admin_only' } }
  }
})

export default router
