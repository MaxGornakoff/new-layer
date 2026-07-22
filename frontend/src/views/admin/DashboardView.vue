<template>
  <section class="admin-page">
    <header class="admin-page__header">
      <h1>Обзор</h1>
      <p class="admin-page__lead">
        Быстрый доступ к разделам управления магазином.
      </p>
    </header>

    <div class="admin-stat-grid admin-form--wide">
      <RouterLink class="admin-stat-card" to="/admin/products">
        <strong>Товары</strong>
        <span>Каталог, цены, фото и остатки</span>
      </RouterLink>

      <RouterLink class="admin-stat-card" to="/admin/categories">
        <strong>Категории</strong>
        <span>Блоки на главной и преимущества</span>
      </RouterLink>

      <RouterLink class="admin-stat-card admin-stat-card--orders" to="/admin/orders">
        <strong class="admin-orders-title">
          Заказы
          <span
            v-if="adminOrders.hasNew"
            class="admin-orders-badge"
            aria-label="Новых заказов"
          >
            {{ adminOrders.newCount }}
          </span>
        </strong>
        <span class="admin-stat-card__hint">Статусы и суммы заказов</span>
      </RouterLink>

      <RouterLink class="admin-stat-card" to="/admin/clients">
        <strong>Контрагенты</strong>
        <span>Клиенты и контакты</span>
      </RouterLink>

      <RouterLink class="admin-stat-card" to="/admin/hero-slides">
        <strong>Слайдер</strong>
        <span>Баннеры на главной странице</span>
      </RouterLink>

      <RouterLink class="admin-stat-card" to="/admin/delivery">
        <strong>Доставка</strong>
        <span>Службы, ключи и грузоместа</span>
      </RouterLink>

      <RouterLink class="admin-stat-card" to="/admin/menu">
        <strong>Меню</strong>
        <span>Разделы и пункты навигации</span>
      </RouterLink>

      <RouterLink class="admin-stat-card" to="/admin/site">
        <strong>Настройки сайта</strong>
        <span>Логотип, фавикон и каталог</span>
      </RouterLink>
    </div>
  </section>
</template>

<script setup>
import { onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useAdminOrdersStore } from '@/stores/adminOrders'

const adminOrders = useAdminOrdersStore()

onMounted(() => {
  adminOrders.refresh()
})
</script>

<style scoped>
.admin-orders-title {
  display: inline-flex !important;
  align-items: center;
  gap: 0.5rem;
}

.admin-stat-card--orders .admin-orders-badge {
  display: inline-flex;
  min-width: 1.375rem;
  height: 1.375rem;
  margin-top: 0;
  padding: 0 0.35rem;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  background: #3b72ff;
  color: #fff;
  font-size: 0.75rem;
  font-weight: 700;
  line-height: 1;
}
</style>
