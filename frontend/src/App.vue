<script setup>
import { onMounted } from 'vue'
import { RouterView } from 'vue-router'
import IconSprite from '@/components/IconSprite.vue'
import AppToast from '@/components/AppToast.vue'
import { useSiteStore } from '@/stores/site'

const site = useSiteStore()

onMounted(() => {
  if (!site.loaded) {
    site.fetchSettings()
  }
})
</script>

<template>
  <IconSprite />
  <RouterView />
  <AppToast />
</template>
