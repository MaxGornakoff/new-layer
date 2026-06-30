<script setup>
import sprite from '@/assets/icons/sprite.svg?raw'
</script>

<template>
  <div class="icon-sprite" aria-hidden="true" v-html="sprite" />
</template>

<style scoped>
.icon-sprite {
  position: absolute;
  width: 0;
  height: 0;
  overflow: hidden;
}
</style>
