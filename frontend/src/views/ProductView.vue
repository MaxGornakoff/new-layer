<script setup>
import { onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/api/client'
import { useCartStore } from '@/stores/cart'
import { useToastStore } from '@/stores/toast'
import AppLoader from '@/components/AppLoader.vue'

const route = useRoute()
const cart = useCartStore()
const toast = useToastStore()
const product = ref(null)
const quantity = ref(1)
const loading = ref(true)

async function loadProduct() {
  loading.value = true
  try {
    const { data } = await api.get(`/products/${route.params.slug}`)
    product.value = data.data
  } finally {
    loading.value = false
  }
}

function addToCart() {
  const result = cart.addItem(product.value, quantity.value)
  toast.show(result.message, result.success ? 'success' : 'error')
}

watch(
  () => route.params.slug,
  () => {
    product.value = null
    loadProduct()
  },
)

onMounted(loadProduct)
</script>

<template>
  <AppLoader v-if="loading" />

  <section v-else-if="product" class="card product-page">
    <span class="badge">{{ product.category?.name }}</span>
    <h1>{{ product.name }}</h1>
    <p class="muted">Артикул: {{ product.sku }}</p>
    <p>{{ product.description }}</p>

    <ul class="meta">
      <li>Цвет: {{ product.color }}</li>
      <li>Диаметр: {{ product.diameter }} мм</li>
      <li>Вес: {{ product.weight_grams }} г</li>
      <li>В наличии: {{ product.stock_quantity }}</li>
    </ul>

    <p class="price">{{ Number(product.price).toLocaleString('ru-RU') }} ₽</p>

    <div class="actions">
      <input v-model.number="quantity" type="number" min="1" :max="product.stock_quantity" />
      <button class="btn" :disabled="product.stock_quantity < 1" @click="addToCart">
        В корзину
      </button>
    </div>
  </section>

  <p v-else class="muted">Товар не найден.</p>
</template>

<style scoped>
.product-page {
  display: grid;
  gap: 0.75rem;
}

.meta {
  margin: 0;
  padding-left: 1.1rem;
}

.price {
  font-size: 1.5rem;
  font-weight: 700;
}

.actions {
  display: flex;
  gap: 0.75rem;
  align-items: center;
}

.actions input {
  width: 80px;
  padding: 0.5rem;
}
</style>
