<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/api/client'
import ProductCard from '@/components/ProductCard.vue'
import AppLoader from '@/components/AppLoader.vue'

const route = useRoute()
const products = ref([])
const loading = ref(false)

const searchQuery = computed(() => String(route.query.q || '').trim())

async function loadProducts() {
  if (!searchQuery.value) {
    products.value = []
    return
  }

  loading.value = true
  try {
    const { data } = await api.get('/products', {
      params: { search: searchQuery.value, per_page: 48 },
    })
    products.value = data.data
  } finally {
    loading.value = false
  }
}

watch(searchQuery, loadProducts)
onMounted(loadProducts)
</script>

<template>
  <section class="mx-auto w-full max-w-[1440px]">
    <h1 class="my-6 text-2xl font-semibold uppercase sm:text-[28px] lg:text-3xl" v-if="searchQuery">Результаты поиска: «{{ searchQuery }}»</h1>
    <h1 class="my-6 text-2xl font-semibold uppercase sm:text-[28px] lg:text-3xl" v-else>Поиск</h1>

    <p v-if="!searchQuery" class="muted">Введите запрос в строке поиска в шапке сайта.</p>
    <AppLoader v-else-if="loading" />
    <p v-else-if="!products.length" class="muted">По вашему запросу ничего не найдено.</p>

    <div v-if="!loading && products.length" class="grid products">
      <ProductCard v-for="product in products" :key="product.id" :product="product" />
    </div>
  </section>
</template>
