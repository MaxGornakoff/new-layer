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
    :width="size"
    :height="size"
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
}
</style>
