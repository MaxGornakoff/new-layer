<script setup>
import { onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/api/client'
import AppLoader from '@/components/AppLoader.vue'
import CartProductQuantityControl from '@/components/CartProductQuantityControl.vue'
import ProductImagesSlider from '@/components/ProductImagesSlider.vue'
import ProductPrice from '@/components/ProductPrice.vue'

const route = useRoute()
const product = ref(null)
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

    <ProductImagesSlider :images="product.images" />

    <h1>{{ product.name }}</h1>
    <p class="muted">Артикул: {{ product.sku }}</p>
    <p>{{ product.description }}</p>

    <ul class="meta">
      <li>Цвет: {{ product.color }}</li>
      <li>Диаметр: {{ product.diameter }} мм</li>
      <li>Вес: {{ product.weight_grams }} г</li>
      <li>В наличии: {{ product.stock_quantity }}</li>
    </ul>

    <ProductPrice :product="product" size="lg" />

    <CartProductQuantityControl :product="product" />
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
</style>
