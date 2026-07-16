<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/api/client'
import { useSiteStore } from '@/stores/site'
import ProductCard from '@/components/ProductCard.vue'
import ProductFilters from '@/components/ProductFilters.vue'
import AppLoader from '@/components/AppLoader.vue'

const categories = ref([])
const products = ref([])
const colors = ref([])
const loading = ref(true)
const route = useRoute()
const router = useRouter()
const site = useSiteStore()

const filters = ref({
  search: '',
  category: '',
  color: '',
  diameter: '',
  in_stock: false,
})

const pageTitle = computed(() => {
  if (filters.value.category) {
    const category = categories.value.find((item) => item.slug === filters.value.category)
    return category ? `Каталог: ${category.name}` : 'Каталог'
  }

  return 'Каталог'
})

const activeFilters = computed(() => {
  const items = []

  if (filters.value.search) {
    items.push({ key: 'search', label: `Поиск: ${filters.value.search}` })
  }

  if (filters.value.category) {
    const category = categories.value.find((item) => item.slug === filters.value.category)
    items.push({
      key: 'category',
      label: category?.name || filters.value.category,
    })
  }

  if (filters.value.color) {
    items.push({ key: 'color', label: `Цвет: ${filters.value.color}` })
  }

  if (filters.value.diameter) {
    items.push({ key: 'diameter', label: `Диаметр: ${filters.value.diameter} мм` })
  }

  if (filters.value.in_stock) {
    items.push({ key: 'in_stock', label: 'Только в наличии' })
  }

  return items
})

function buildQuery(nextFilters) {
  const query = {}

  if (nextFilters.search) query.search = nextFilters.search
  if (nextFilters.category) query.category = nextFilters.category
  if (nextFilters.color) query.color = nextFilters.color
  if (nextFilters.diameter) query.diameter = nextFilters.diameter
  if (nextFilters.in_stock) query.in_stock = '1'

  return query
}

function syncFiltersFromRoute() {
  filters.value = {
    search: route.query.search ? String(route.query.search) : '',
    category: route.query.category ? String(route.query.category) : '',
    color: route.query.color ? String(route.query.color) : '',
    diameter: route.query.diameter ? String(route.query.diameter) : '',
    in_stock: route.query.in_stock === '1' || route.query.in_stock === 'true',
  }
}

async function loadCategories() {
  const { data } = await api.get('/categories')
  categories.value = data.data
}

async function loadProducts() {
  loading.value = true
  try {
    const params = {
      search: filters.value.search || undefined,
      category: filters.value.category || undefined,
      color: filters.value.color || undefined,
      diameter: filters.value.diameter || undefined,
      in_stock: filters.value.in_stock || undefined,
    }
    const { data } = await api.get('/products', { params })
    products.value = data.data

    const uniqueColors = [...new Set(products.value.map((item) => item.color))]
    colors.value = uniqueColors.sort()
  } finally {
    loading.value = false
  }
}

function applyFilters() {
  router.replace({ name: 'catalog', query: buildQuery(filters.value) })
}

function resetFilters() {
  router.push({ name: 'catalog' })
}

function removeFilter(key) {
  const nextFilters = {
    ...filters.value,
    [key]: key === 'in_stock' ? false : '',
  }

  router.replace({ name: 'catalog', query: buildQuery(nextFilters) })
}

watch(
  () => route.query,
  async () => {
    syncFiltersFromRoute()
    await loadProducts()
  },
)

onMounted(async () => {
  syncFiltersFromRoute()
  await Promise.all([loadCategories(), loadProducts()])
})
</script>

<template>
  <section class="catalog">
    <div class="title-container inline-flex flex-col gap-2.5 my-10">
      <h1 class="text-3xl font-semibold uppercase">{{ pageTitle }}</h1>
      <p class="muted px-3 py-1.5 rounded-full bg-[#222222] text-white text-sm">Заказ оформляется кратно 10 катушкам — в любом составе категорий.</p>
    </div>
    <div v-if="activeFilters.length" class="active-filters">
      <span v-for="filter in activeFilters" :key="filter.key" class="active-filters__chip">
        {{ filter.label }}
        <button
          type="button"
          class="active-filters__remove"
          :aria-label="`Убрать фильтр ${filter.label}`"
          @click="removeFilter(filter.key)"
        >
          ×
        </button>
      </span>
      <button class="btn secondary active-filters__reset" type="button" @click="resetFilters">
        Показать все товары
      </button>
    </div>

    <div class="catalog-layout">
      <ProductFilters
        v-model="filters"
        :categories="categories"
        :colors="colors"
        :auto-apply="site.catalogAutoApplyFilters"
        @apply="applyFilters"
        @reset="resetFilters"
      />

      <div>
        <AppLoader v-if="loading" />
        <div v-else-if="products.length" class="grid products">
          <ProductCard v-for="product in products" :key="product.id" :product="product" />
        </div>
        <p v-else class="muted">Товары не найдены.</p>
      </div>
    </div>
  </section>
</template>

<style scoped>
.active-filters {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  align-items: center;
  margin-bottom: 1rem;
}

.active-filters__chip {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.35rem 0.65rem;
  border-radius: 999px;
  background: #e0f2fe;
  color: #0369a1;
  font-size: 0.875rem;
}

.active-filters__remove {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.25rem;
  height: 1.25rem;
  padding: 0;
  border: none;
  border-radius: 999px;
  background: transparent;
  color: inherit;
  cursor: pointer;
  font-size: 1rem;
  line-height: 1;
}

.active-filters__remove:hover {
  background: rgba(3, 105, 161, 0.12);
}

.active-filters__reset {
  margin-left: 0.25rem;
}

.catalog-layout {
  display: grid;
  grid-template-columns: 280px 1fr;
  gap: 1rem;
  align-items: start;
}

@media (max-width: 900px) {
  .catalog-layout {
    grid-template-columns: 1fr;
  }
}
</style>
