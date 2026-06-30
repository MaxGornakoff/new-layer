<script setup>
import { RouterLink, useRoute } from 'vue-router'
import HeroSlider from '@/components/HeroSlider.vue'

const route = useRoute()
</script>

<template>
  <div class="home">
    <div class="box home__hero h-[calc(100vh-72px)] pb-5 relative">
      <HeroSlider class="home__hero-slider" />
    </div>

    <div class="box home__content">
      <p v-if="route.query.error === 'admin_only'" class="home-alert">
        Нет доступа к админке. Войдите под учётной записью администратора
        (<RouterLink to="/login?redirect=/admin">admin@shop.local</RouterLink>).
      </p>

      <section id="about-us" class="home-about">
        <!-- Блок «О нас» — вёрстка будет позже -->
      </section>
    </div>
  </div>
</template>

<style scoped>
.home-alert {
  margin: 0 0 1rem;
  padding: 0.75rem 1rem;
  border-radius: 0.5rem;
  background: #fef2f2;
  color: #b91c1c;
}

.home__hero {
  position: relative;
  display: grid;
  margin-bottom: 0.5rem;
}

.home__hero-slider,
.home__hero-digits {
  grid-area: 1 / 1;
}

.home__hero-digits {
  align-self: end;
  z-index: 3;
  pointer-events: none;
}

.home__content {
  padding-left: 1.25rem;
  padding-right: 1.25rem;
}

.home-about {
  min-height: 120px;
}
</style>
