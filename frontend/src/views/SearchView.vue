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
  <section class="search-page mx-auto w-full max-w-[1440px]">
    <header class="my-6 sm:my-8 lg:my-10">
      <h1
        class="m-0 break-words text-2xl font-semibold uppercase leading-tight sm:text-[28px] lg:text-3xl"
      >
        <template v-if="searchQuery">Результаты поиска: «{{ searchQuery }}»</template>
        <template v-else>Поиск</template>
      </h1>
      <p
        v-if="searchQuery && !loading"
        class="muted m-0 mt-2 text-sm sm:text-base"
      >
        Найдено: {{ products.length }}
      </p>
    </header>

    <p v-if="!searchQuery" class="muted text-[15px] sm:text-base">
      Введите запрос в строке поиска в шапке сайта.
    </p>
    <AppLoader v-else-if="loading" />
    <p v-else-if="!products.length" class="muted text-[15px] sm:text-base">
      По вашему запросу ничего не найдено.
    </p>

    <div
      v-if="!loading && products.length"
      class="grid products pb-6 sm:pb-8 lg:pb-10"
    >
      <ProductCard v-for="product in products" :key="product.id" :product="product" />
    </div>
  </section>
</template>
