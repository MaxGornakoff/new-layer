<script setup>
import { computed, onMounted, onUnmounted } from 'vue'
import { RouterLink, RouterView, useRoute } from 'vue-router'
import { useAdminOrdersStore } from '@/stores/adminOrders'

const route = useRoute()
const adminOrders = useAdminOrdersStore()

const hideNewOrdersBanner = computed(() => (
  route.name === 'admin-dashboard' || route.name === 'admin-orders'
))

const showNewOrdersBanner = computed(() => (
  adminOrders.hasNew && !hideNewOrdersBanner.value
))

onMounted(() => {
  adminOrders.startPolling()
})

onUnmounted(() => {
  adminOrders.stopPolling()
})
</script>

<template>
  <div class="admin-layout">
    <aside class="sidebar card">
      <h2 class="admin-sidebar__title">Админка</h2>
      <nav class="admin-sidebar__nav">
        <RouterLink class="admin-nav-link" to="/admin">Обзор</RouterLink>
        <RouterLink class="admin-nav-link" to="/admin/products">Товары</RouterLink>
        <RouterLink class="admin-nav-link" to="/admin/categories">Категории</RouterLink>
        <RouterLink class="admin-nav-link" to="/admin/orders">Заказы</RouterLink>
        <RouterLink class="admin-nav-link" to="/admin/clients">Контрагенты</RouterLink>
        <RouterLink class="admin-nav-link" to="/admin/menu">Меню</RouterLink>
        <RouterLink class="admin-nav-link" to="/admin/hero-slides">Слайдер</RouterLink>
        <RouterLink class="admin-nav-link" to="/admin/site">Настройки сайта</RouterLink>
        <RouterLink class="admin-nav-link" to="/admin/delivery">Доставка</RouterLink>
      </nav>

      <RouterLink
        v-if="showNewOrdersBanner"
        class="admin-new-orders-banner"
        to="/admin/orders"
      >
        {{ adminOrders.bannerText }}
      </RouterLink>

      <RouterLink class="admin-sidebar__back" to="/">← На сайт</RouterLink>
    </aside>
    <section class="content">
      <RouterView />
    </section>
  </div>
</template>

<style scoped>
.admin-layout {
  width: min(1200px, calc(100% - 2rem));
  margin: 1rem auto 2rem;
  display: grid;
  grid-template-columns: 240px 1fr;
  gap: 1rem;
  align-items: start;
}

.sidebar {
  display: grid;
  gap: 1rem;
  align-content: start;
  position: sticky;
  top: 1rem;
  height: fit-content;
}

.admin-sidebar__title {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 700;
  color: #0f172a;
}

.admin-sidebar__nav {
  display: grid;
  gap: 0.25rem;
}

.admin-new-orders-banner {
  display: block;
  margin-top: 0.25rem;
  padding: 0.75rem 0.875rem;
  border-radius: 0.75rem;
  background: #3b72ff;
  color: #fff;
  font-size: 0.875rem;
  font-weight: 600;
  line-height: 1.35;
  text-decoration: none;
  transition: background-color 0.15s ease;
}

.admin-new-orders-banner:hover {
  background: #2f63ef;
  color: #fff;
}

.admin-sidebar__back {
  margin-top: 0.25rem;
  padding: 0.5rem 0.75rem;
  color: #64748b;
  font-size: 0.875rem;
}

@media (max-width: 800px) {
  .admin-layout {
    grid-template-columns: 1fr;
  }

  .sidebar {
    position: static;
  }
}
</style>
