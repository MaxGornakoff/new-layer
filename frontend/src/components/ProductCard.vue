<script setup>
import { RouterLink } from 'vue-router'
import { useCartStore } from '@/stores/cart'
import { useToastStore } from '@/stores/toast'

const props = defineProps({
  product: {
    type: Object,
    required: true,
  },
})

const cart = useCartStore()
const toast = useToastStore()

function addToCart() {
  const result = cart.addItem(props.product)
  toast.show(result.message, result.success ? 'success' : 'error')
}
</script>

<template>
  <article class="card product-card">
    <div class="top">
      <span class="badge">{{ product.category?.name }}</span>
      <span class="muted">{{ product.color }}</span>
    </div>
    <h3>{{ product.name }}</h3>
    <p class="muted">{{ product.diameter }} мм · {{ product.weight_grams }} г</p>
    <div class="bottom">
      <strong>{{ Number(product.price).toLocaleString('ru-RU') }} ₽</strong>
      <span class="muted">В наличии: {{ product.stock_quantity }}</span>
    </div>
    <div class="actions">
      <RouterLink class="btn secondary" :to="{ name: 'product', params: { slug: product.slug } }">
        Подробнее
      </RouterLink>
      <button class="btn" type="button" :disabled="product.stock_quantity < 1" @click="addToCart">
        В корзину
      </button>
    </div>
  </article>
</template>

<style scoped>
.product-card {
  display: grid;
  gap: 0.75rem;
}

.top,
.bottom {
  display: flex;
  justify-content: space-between;
  gap: 0.5rem;
  align-items: center;
}

h3 {
  margin: 0;
  font-size: 1rem;
}

.actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.5rem;
}
</style>
