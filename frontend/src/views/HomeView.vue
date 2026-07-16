<script setup>
import { RouterLink, useRoute } from 'vue-router'
import HeroSlider from '@/components/HeroSlider.vue'
import HomeCategories from '@/components/HomeCategories.vue'
import HomeStats from '@/components/HomeStats.vue'
import HomeProductShelves from '@/components/HomeProductShelves.vue'
import HomeAbout from '@/components/HomeAbout.vue'
import HomeAboutFeatures from '@/components/HomeAboutFeatures.vue'

const route = useRoute()
</script>

<template>
  <div class="home">
    <div class="box home__hero">
      <HeroSlider class="home__hero-slider" />
    </div>

    <div class="box home__content">
      <p v-if="route.query.error === 'admin_only'" class="home-alert">
        Нет доступа к админке. Войдите под учётной записью администратора
        (<RouterLink to="/login?redirect=/admin">admin@shop.local</RouterLink>).
      </p>

      <HomeCategories />
      <HomeStats />
      <HomeProductShelves />
      <HomeAbout />
      <HomeAboutFeatures />
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
  width: 100%;
  height: min(68svh, 440px);
  min-height: 340px;
  margin-bottom: 1rem;
}

@media (min-width: 640px) {
  .home__hero {
    height: min(70svh, 520px);
    min-height: 420px;
    margin-bottom: 1.25rem;
  }
}

@media (min-width: 1024px) {
  .home__hero {
    height: calc(100svh - 72px - 1.25rem);
    min-height: 560px;
    margin-bottom: 1.25rem;
  }
}

.home__hero-slider {
  display: block;
  width: 100%;
  height: 100%;
}
</style>
