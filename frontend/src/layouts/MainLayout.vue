<script setup>
import { computed } from 'vue'
import { RouterView, useRoute } from 'vue-router'
import AppHeader from '@/components/AppHeader.vue'
import AppFooter from '@/components/AppFooter.vue'
import CookieConsentBanner from '@/components/CookieConsentBanner.vue'

const route = useRoute()
const isFullWidth = computed(() => Boolean(route.meta.fullWidth))
</script>

<template>
  <div class="layout">
    <AppHeader />

    <main class="main">
      <div v-if="isFullWidth">
        <RouterView />
      </div>
      <div v-else class="box">
        <RouterView />
      </div>
    </main>

    <AppFooter />
    <CookieConsentBanner />
  </div>
</template>

<style scoped>
.layout {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.main {
  flex: 1;
}
</style>
