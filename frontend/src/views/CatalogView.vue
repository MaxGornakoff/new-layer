<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/api/client'
import { useSiteStore } from '@/stores/site'
import { useDrawerSwipe } from '@/composables/useDrawerSwipe'
import ProductCard from '@/components/ProductCard.vue'
import ProductFilters from '@/components/ProductFilters.vue'
import AppLoader from '@/components/AppLoader.vue'
import { isHexColor, isLightHexColor } from '@/lib/productColor'

const categories = ref([])
const products = ref([])
const filterGroups = ref([])
const loading = ref(true)
const filtersOpen = ref(false)
const filtersPanelRef = ref(null)
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
    items.push({
      key: 'color',
      value: color,
      label: isHexColor(color) ? 'Цвет' : `Цвет: ${optionLabel('color', color)}`,
      swatch: isHexColor(color) ? color : null,
    })
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

const activeFilterCount = computed(() => activeFilters.value.length)

function productsWord(count) {
  const mod10 = count % 10
  const mod100 = count % 100
  if (mod100 >= 11 && mod100 <= 14) return 'товаров'
  if (mod10 === 1) return 'товар'
  if (mod10 >= 2 && mod10 <= 4) return 'товара'
  return 'товаров'
}

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

function openFilters() {
  filtersOpen.value = true
}

function closeFilters() {
  filtersOpen.value = false
}

const {
  panelStyle: filtersPanelStyle,
  isDragging: filtersDragging,
  onPointerDown: onFiltersPointerDown,
  onPointerMove: onFiltersPointerMove,
  onPointerUp: onFiltersPointerUp,
  onPointerCancel: onFiltersPointerCancel,
} = useDrawerSwipe({
  panelRef: filtersPanelRef,
  isOpen: filtersOpen,
  onClose: closeFilters,
  edge: 'left',
})

function applyFilters({ close = true } = {}) {
  router.replace({ name: 'catalog', query: buildQuery(filters.value) })
  if (close) closeFilters()
}

function onFiltersApply() {
  // При авто-применении не закрываем шторку — пользователь ещё выбирает опции
  applyFilters({ close: !site.catalogAutoApplyFilters })
}

function resetFilters() {
  router.push({ name: 'catalog' })
  closeFilters()
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

function onKeydown(event) {
  if (event.key === 'Escape' && filtersOpen.value) {
    closeFilters()
  }
}

watch(
  () => route.query,
  async () => {
    syncFiltersFromRoute()
    await loadProducts()
  },
)

watch(filtersOpen, (open) => {
  document.body.style.overflow = open ? 'hidden' : ''
})

onMounted(async () => {
  syncFiltersFromRoute()
  window.addEventListener('keydown', onKeydown)
  await Promise.all([loadCategories(), loadFilterOptions(), loadProducts()])
})

onUnmounted(() => {
  window.removeEventListener('keydown', onKeydown)
  document.body.style.overflow = ''
})
</script>

<template>
  <section class="catalog mx-auto w-full max-w-[1440px]">
    <div class="title-container my-6 flex flex-col gap-2 sm:my-8 sm:gap-2.5 lg:my-10">
      <h1 class="m-0 text-2xl font-semibold uppercase sm:text-[28px] lg:text-3xl">
        {{ pageTitle }}
      </h1>
      <p
        class="muted m-0 flex items-start gap-2 text-[14px] font-medium leading-snug text-[#222222] sm:items-center sm:gap-2.5 sm:text-[16px] lg:text-[18px]"
      >
        <span
          class="mt-0.5 inline-flex size-5 min-w-5 shrink-0 items-center justify-center rounded-full border-[1.5px] border-[#222222] p-0 text-xs font-semibold sm:mt-0 sm:text-sm"
        >
          i
        </span>
        Заказ оформляется кратно 10 катушкам — в любом составе категорий.
      </p>
    </div>

    <div class="catalog-toolbar">
      <button
        type="button"
        class="catalog-toolbar__filters"
        @click="openFilters"
      >
        Фильтры
        <span v-if="activeFilterCount" class="catalog-toolbar__badge">
          {{ activeFilterCount }}
        </span>
      </button>

      <p v-if="!loading" class="catalog-toolbar__count">
        {{ products.length }} {{ productsWord(products.length) }}
      </p>
    </div>

    <div v-if="activeFilters.length" class="active-filters">
      <span
        v-for="filter in activeFilters"
        :key="`${filter.key}:${filter.value ?? ''}`"
        class="active-filters__chip"
      >
        <span
          v-if="filter.swatch"
          class="active-filters__swatch"
          :class="{ 'active-filters__swatch--light': isLightHexColor(filter.swatch) }"
          :style="{ backgroundColor: filter.swatch }"
          aria-hidden="true"
        />
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
        Показать все
      </button>
    </div>

    <div class="catalog-layout">
      <div
        class="catalog-filters-backdrop"
        :class="{ 'is-open': filtersOpen }"
        aria-hidden="true"
        @click="closeFilters"
      />

      <aside
        ref="filtersPanelRef"
        class="catalog-filters"
        :class="{ 'is-open': filtersOpen, 'is-dragging': filtersDragging }"
        :style="filtersPanelStyle"
        :aria-hidden="false"
        @pointerdown="onFiltersPointerDown"
        @pointermove="onFiltersPointerMove"
        @pointerup="onFiltersPointerUp"
        @pointercancel="onFiltersPointerCancel"
      >
        <div class="catalog-filters__header">
          <h2 class="catalog-filters__title">Фильтры</h2>
          <button
            type="button"
            class="catalog-filters__close"
            aria-label="Закрыть фильтры"
            @click="closeFilters"
          >
            ×
          </button>
        </div>

        <ProductFilters
          v-model="filters"
          :categories="categories"
          :groups="filterGroups"
          :auto-apply="site.catalogAutoApplyFilters"
          @apply="onFiltersApply"
          @reset="resetFilters"
        />
      </aside>

      <div class="catalog-results min-w-0">
        <AppLoader v-if="loading" />
        <div v-else-if="products.length" class="grid products">
          <ProductCard v-for="product in products" :key="product.id" :product="product" />
        </div>
        <p v-else class="muted py-8 text-center sm:text-left">Товары не найдены.</p>
      </div>
    </div>
  </section>
</template>

<style scoped>
.catalog-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}

.catalog-toolbar__filters {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  height: 2.5rem;
  padding: 0 1rem;
  border: 0;
  border-radius: 999px;
  background: #fff;
  color: #222;
  font-size: 0.9375rem;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 4px 16px rgba(15, 23, 42, 0.06);
}

.catalog-toolbar__badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 1.25rem;
  height: 1.25rem;
  padding: 0 0.35rem;
  border-radius: 999px;
  background: #3b72ff;
  color: #fff;
  font-size: 0.75rem;
  font-weight: 600;
}

.catalog-toolbar__count {
  margin: 0;
  color: #64748b;
  font-size: 0.875rem;
}

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
  max-width: 100%;
  padding: 0.35rem 0.65rem;
  border-radius: 999px;
  background: #e0f2fe;
  color: #0369a1;
  font-size: 0.8125rem;
}

.active-filters__swatch {
  width: 0.85rem;
  height: 0.85rem;
  border-radius: 999px;
  border: 1px solid rgba(15, 23, 42, 0.12);
  flex-shrink: 0;
}

.active-filters__swatch--light {
  border-color: #94a3b8;
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
  padding: 0.35rem 0.75rem;
  font-size: 0.8125rem;
}

.catalog-layout {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
  align-items: start;
}

.catalog-filters-backdrop {
  display: none;
}

.catalog-filters__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 0.75rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid #e2e8f0;
}

.catalog-filters__title {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 600;
  color: #222;
}

.catalog-filters__close {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  padding: 0;
  border: 0;
  border-radius: 999px;
  background: #f1f5f9;
  color: #222;
  font-size: 1.5rem;
  line-height: 1;
  cursor: pointer;
}

/* Mobile drawer */
@media (max-width: 899px) {
  .catalog-filters-backdrop {
    position: fixed;
    inset: 0;
    z-index: 40;
    display: block;
    background: rgba(15, 23, 42, 0.45);
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.2s ease;
  }

  .catalog-filters-backdrop.is-open {
    opacity: 1;
    pointer-events: auto;
  }

  .catalog-filters {
    position: fixed;
    top: 0;
    left: 0;
    z-index: 50;
    display: flex;
    flex-direction: column;
    width: min(100%, 360px);
    height: 100%;
    padding: 1rem 1rem 1.5rem;
    background: #f2f7f8;
    box-shadow: 8px 0 32px rgba(15, 23, 42, 0.18);
    transform: translateX(-105%);
    transition: transform 0.25s ease;
    overflow: auto;
    touch-action: pan-y;
  }

  .catalog-filters.is-open {
    transform: translateX(0);
    z-index: 999;
  }

  .catalog-filters.is-dragging {
    touch-action: none;
    cursor: grabbing;
    user-select: none;
  }
}

@media (min-width: 900px) {
  .catalog-toolbar {
    display: none;
  }

  .catalog-filters__header {
    display: none;
  }

  .catalog-layout {
    grid-template-columns: 280px 1fr;
    gap: 1.25rem;
  }

  .catalog-filters {
    position: sticky;
    top: 1rem;
  }

  .catalog-filters-backdrop {
    display: none !important;
  }
}
</style>
