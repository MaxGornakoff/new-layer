<script setup>
defineProps({
  name: {
    type: String,
    required: true,
  },
  size: {
    type: [Number, String],
    default: 24,
  },
})
</script>

<template>
  <svg
    class="app-icon"
    :style="{ '--app-icon-size': `${size}px` }"
    aria-hidden="true"
    focusable="false"
  >
    <use :href="`#icon-${name}`" />
  </svg>
</template>

<style scoped>
.app-icon {
  display: block;
  fill: currentColor;
  width: var(--app-icon-size, 1.5rem);
  height: var(--app-icon-size, 1.5rem);
  flex-shrink: 0;
}
</style>
