<script setup>
import { computed } from 'vue'
import { RouterView, useRoute } from 'vue-router'
import AppHeader from '@/components/AppHeader.vue'

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
  </div>
</template>

<style scoped>
.layout {
  min-height: 100vh;
}

.main {
  padding-bottom: 2rem;
}
</style>
