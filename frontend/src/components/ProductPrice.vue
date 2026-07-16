<script setup>
import { computed } from 'vue'
import { formatMoney, resolveCompareAtPrice } from '@/lib/productPrice'

const props = defineProps({
  product: {
    type: Object,
    required: true,
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value),
  },
})

const compareAtPrice = computed(() => resolveCompareAtPrice(props.product))
const currentPrice = computed(() => Number(props.product.price))
</script>

<template>
  <div
    class="product-price"
    :class="{
      'product-price--sm': size === 'sm',
      'product-price--md': size === 'md',
      'product-price--lg': size === 'lg',
    }"
  >
    <strong class="product-price__current">
      {{ formatMoney(currentPrice) }} ₽
    </strong>
    <span v-if="compareAtPrice" class="product-price__compare">
      {{ formatMoney(compareAtPrice) }} ₽
    </span>
  </div>
</template>

<style scoped>
.product-price {
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  gap: 0.5rem;
}

.product-price__current {
  color: #222222;
  font-weight: 600;
  line-height: 1;
}

.product-price__compare {
  color: #94a3b8;
  font-weight: 500;
  text-decoration: line-through;
}

.product-price--sm .product-price__current {
  font-size: 1rem;
}

.product-price--sm .product-price__compare {
  font-size: 0.8125rem;
}

.product-price--md .product-price__current {
  font-size: clamp(1.125rem, 2.5vw, 1.5rem);
}

.product-price--md .product-price__compare {
  font-size: clamp(0.875rem, 2vw, 1.125rem);
}

.product-price--lg .product-price__current {
  font-size: clamp(1.5rem, 4vw, 2rem);
}

.product-price--lg .product-price__compare {
  font-size: clamp(1rem, 2.5vw, 1.25rem);
}
</style>
