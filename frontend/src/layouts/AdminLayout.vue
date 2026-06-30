<script setup>
import { RouterLink, RouterView } from 'vue-router'
</script>

<template>
  <div class="admin-layout">
    <aside class="sidebar card">
      <h2>Админка</h2>
      <nav>
        <RouterLink to="/admin">Обзор</RouterLink>
        <RouterLink to="/admin/products">Товары</RouterLink>
        <RouterLink to="/admin/categories">Категории</RouterLink>
        <RouterLink to="/admin/orders">Заказы</RouterLink>
        <RouterLink to="/admin/clients">Контрагенты</RouterLink>
        <RouterLink to="/admin/menu">Меню</RouterLink>
        <RouterLink to="/admin/hero-slides">Слайдер</RouterLink>
        <RouterLink to="/admin/site">Настройки сайта</RouterLink>
      </nav>
      <RouterLink class="back" to="/">← На сайт</RouterLink>
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
  grid-template-columns: 220px 1fr;
  gap: 1rem;
}

.sidebar {
  display: grid;
  gap: 1rem;
  align-content: start;
  position: sticky;
  top: 1rem;
  height: fit-content;
}

.sidebar nav {
  display: grid;
  gap: 0.5rem;
}

.sidebar a.router-link-active {
  color: #2563eb;
  font-weight: 600;
}

.back {
  color: #64748b;
}

@media (max-width: 800px) {
  .admin-layout {
    grid-template-columns: 1fr;
  }
}
</style>
