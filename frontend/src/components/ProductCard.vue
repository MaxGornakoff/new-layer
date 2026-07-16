<script setup>
import HomeProductShelfCard from '@/components/HomeProductShelfCard.vue'

defineProps({
  product: {
    type: Object,
    required: true,
  },
})
</script>

<template>
  <HomeProductShelfCard :product="product" fluid />
</template>
