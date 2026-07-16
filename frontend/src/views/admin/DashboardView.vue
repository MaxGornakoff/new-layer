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

      <RouterLink class="admin-stat-card" to="/admin/orders">
        <strong>Заказы</strong>
        <span>Статусы и суммы заказов</span>
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
import { RouterLink } from 'vue-router'
</script>
