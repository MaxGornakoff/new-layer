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
const filterGroups = ref([])
const loading = ref(true)
const route = useRoute()
const router = useRouter()
const site = useSiteStore()

const filters = ref({
  search: '',
  category: [],
  color: [],
  diameter: [],
  weight: [],
  in_stock: false,
})

function toArray(value) {
  if (value == null || value === '') return []
  if (Array.isArray(value)) {
    return value.map(String).map((item) => item.trim()).filter(Boolean)
  }

  return String(value)
    .split(',')
    .map((item) => item.trim())
    .filter(Boolean)
}

const pageTitle = computed(() => {
  if (filters.value.category.length === 1) {
    const category = categories.value.find((item) => item.slug === filters.value.category[0])
    return category ? `Каталог: ${category.name}` : 'Каталог'
  }

  return 'Каталог'
})

const activeFilters = computed(() => {
  const items = []

  if (filters.value.search) {
    items.push({ key: 'search', value: null, label: `Поиск: ${filters.value.search}` })
  }

  for (const slug of filters.value.category) {
    const category = categories.value.find((item) => item.slug === slug)
    items.push({
      key: 'category',
      value: slug,
      label: category?.name || slug,
    })
  }

  const optionLabel = (groupKey, value) => {
    const group = filterGroups.value.find((item) => item.key === groupKey)
    const option = group?.options?.find((item) => String(item.value) === String(value))
    return option?.label || value
  }

  for (const color of filters.value.color) {
    items.push({ key: 'color', value: color, label: `Цвет: ${optionLabel('color', color)}` })
  }

  for (const diameter of filters.value.diameter) {
    items.push({
      key: 'diameter',
      value: diameter,
      label: `Диаметр: ${optionLabel('diameter', diameter)}`,
    })
  }

  for (const weight of filters.value.weight) {
    items.push({
      key: 'weight',
      value: weight,
      label: `Вес: ${optionLabel('weight', weight)}`,
    })
  }

  if (filters.value.in_stock) {
    items.push({ key: 'in_stock', value: null, label: 'Только в наличии' })
  }

  return items
})

function buildQuery(nextFilters) {
  const query = {}

  if (nextFilters.search) query.search = nextFilters.search
  if (nextFilters.category.length) query.category = nextFilters.category
  if (nextFilters.color.length) query.color = nextFilters.color
  if (nextFilters.diameter.length) query.diameter = nextFilters.diameter
  if (nextFilters.weight.length) query.weight = nextFilters.weight
  if (nextFilters.in_stock) query.in_stock = '1'

  return query
}

function syncFiltersFromRoute() {
  filters.value = {
    search: route.query.search ? String(route.query.search) : '',
    category: toArray(route.query.category),
    color: toArray(route.query.color),
    diameter: toArray(route.query.diameter),
    weight: toArray(route.query.weight),
    in_stock: route.query.in_stock === '1' || route.query.in_stock === 'true',
  }
}

async function loadCategories() {
  const { data } = await api.get('/categories')
  categories.value = data.data
}

async function loadFilterOptions() {
  const { data } = await api.get('/products/filters')
  filterGroups.value = data.data.groups ?? []
}

async function loadProducts() {
  loading.value = true
  try {
    const params = {
      search: filters.value.search || undefined,
      category: filters.value.category.length ? filters.value.category : undefined,
      color: filters.value.color.length ? filters.value.color : undefined,
      diameter: filters.value.diameter.length ? filters.value.diameter : undefined,
      weight: filters.value.weight.length ? filters.value.weight : undefined,
      in_stock: filters.value.in_stock || undefined,
    }
    const { data } = await api.get('/products', { params })
    products.value = data.data
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

function removeFilter(filter) {
  const nextFilters = {
    ...filters.value,
    category: [...filters.value.category],
    color: [...filters.value.color],
    diameter: [...filters.value.diameter],
    weight: [...filters.value.weight],
  }

  if (filter.key === 'search') {
    nextFilters.search = ''
  } else if (filter.key === 'in_stock') {
    nextFilters.in_stock = false
  } else if (filter.value != null) {
    nextFilters[filter.key] = nextFilters[filter.key].filter((item) => item !== filter.value)
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
  await Promise.all([loadCategories(), loadFilterOptions(), loadProducts()])
})
</script>

<template>
  <section class="catalog">
    <div class="title-container inline-flex flex-col gap-2.5 my-10">
      <h1 class="text-3xl font-semibold uppercase">{{ pageTitle }}</h1>
      <p class="muted text-[#222222] text-[18px] flex items-center gap-2.5 font-medium">
        <span class="rounded-full text-sm font-semibold border-[1.5px] border-[#222222] p-2 size-5 min-w-5 inline-flex items-center justify-center">i</span>
        Заказ оформляется кратно 10 катушкам — в любом составе категорий.
      </p>
    </div>
    <div v-if="activeFilters.length" class="active-filters">
      <span
        v-for="filter in activeFilters"
        :key="`${filter.key}:${filter.value ?? ''}`"
        class="active-filters__chip"
      >
        {{ filter.label }}
        <button
          type="button"
          class="active-filters__remove"
          :aria-label="`Убрать фильтр ${filter.label}`"
          @click="removeFilter(filter)"
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
        :groups="filterGroups"
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
