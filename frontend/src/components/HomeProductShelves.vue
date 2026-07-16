<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import api from '@/api/client'
import AppIcon from '@/components/AppIcon.vue'
import AppLoader from '@/components/AppLoader.vue'
import HomeProductShelfCard from '@/components/HomeProductShelfCard.vue'

const MOBILE_VISIBLE_COUNT = 2
const DESKTOP_VISIBLE_COUNT = 5

const categories = ref([])
const productsByCategory = ref({})
const loading = ref(true)
const trackRefs = ref({})
const isTabletOrDesktop = ref(false)

const shelves = computed(() =>
  categories.value
    .map((category) => ({
      category,
      products: productsByCategory.value[category.id] ?? [],
    }))
    .filter((shelf) => shelf.products.length > 0),
)

function updateViewport() {
  isTabletOrDesktop.value = window.matchMedia('(min-width: 640px)').matches
}

function visibleLimit() {
  return isTabletOrDesktop.value ? DESKTOP_VISIBLE_COUNT : MOBILE_VISIBLE_COUNT
}

function shelfIsCarousel(productCount) {
  return productCount > visibleLimit()
}

function setTrackRef(categoryId, element) {
  if (element) {
    trackRefs.value[categoryId] = element
  }
}

function scrollShelf(categoryId, direction) {
  const track = trackRefs.value[categoryId]
  if (!track) return

  const scrollAmount = Math.max(240, Math.round(track.clientWidth * 0.82))
  track.scrollBy({
    left: direction * scrollAmount,
    behavior: 'smooth',
  })
}

onMounted(async () => {
  updateViewport()
  window.addEventListener('resize', updateViewport)

  try {
    const { data: categoriesResponse } = await api.get('/categories')
    categories.value = categoriesResponse.data

    const productResponses = await Promise.all(
      categories.value.map((category) =>
        api.get('/products', {
          params: {
            category: category.slug,
            per_page: 12,
          },
        }),
      ),
    )

    const grouped = {}

    productResponses.forEach((response, index) => {
      const category = categories.value[index]
      grouped[category.id] = response.data.data ?? []
    })

    productsByCategory.value = grouped
  } finally {
    loading.value = false
  }
})

onUnmounted(() => {
  window.removeEventListener('resize', updateViewport)
})
</script>

<template>
  <section aria-label="Популярные товары по категориям" class=" max-w-[1440px] mx-auto">
    <AppLoader v-if="loading" label="Загрузка товаров..." />

    <template v-else>
      <div
        v-for="shelf in shelves"
        :key="shelf.category.id"
        class="space-y-10 mb-15"
      >
        <RouterLink
          :to="`/catalog?category=${shelf.category.slug}`"
          class="inline-flex w-fit max-w-full flex-wrap items-center gap-x-5 gap-y-2 rounded-full bg-white px-8.5 py-2.5 no-underline shadow-[0_4px_16px_rgba(15,23,42,0.06)] transition-shadow hover:shadow-[0_8px_24px_rgba(15,23,42,0.08)]"
        >
          <h2 class="m-0 text-[30px] leading-none font-semibold text-[#222222]">
            {{ shelf.category.name }}
          </h2>

          <span class="inline-flex items-center gap-2.5 text-[16px] font-medium text-[#3B72FF]">
            <span>Смотреть все</span>
            <svg
              class="shelf-header__arrow rotate-45 h-[9px] w-[9px] shrink-0 text-[#3B72FF]"
              aria-hidden="true"
              focusable="false"
            >
              <use href="#icon-arrow" />
            </svg>
          </span>
        </RouterLink>

        <div class="relative">
          <button
            v-if="shelfIsCarousel(shelf.products.length)"
            type="button"
            class="absolute -left-3 top-[38%] z-10 hidden size-10 -translate-y-1/2 items-center justify-center rounded-full border-0 bg-white text-[#3B72FF] shadow-[0_8px_24px_rgba(15,23,42,0.12)] transition-colors hover:bg-slate-50 sm:flex"
            aria-label="Прокрутить назад"
            @click="scrollShelf(shelf.category.id, -1)"
          >
            <AppIcon name="chevron-left" :size="18" />
          </button>

          <div
            :ref="(element) => setTrackRef(shelf.category.id, element)"
            class="pb-1"
            :class="
              shelfIsCarousel(shelf.products.length)
                ? 'flex gap-4 overflow-x-auto scroll-smooth [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden'
                : 'grid grid-cols-2 gap-4 sm:grid-cols-5'
            "
          >
            <HomeProductShelfCard
              v-for="product in shelf.products"
              :key="product.id"
              :product="product"
              :fluid="!shelfIsCarousel(shelf.products.length)"
            />
          </div>

          <button
            v-if="shelfIsCarousel(shelf.products.length)"
            type="button"
            class="absolute -right-3 top-[38%] z-10 hidden size-10 -translate-y-1/2 items-center justify-center rounded-full border-0 bg-white text-[#3B72FF] shadow-[0_8px_24px_rgba(15,23,42,0.12)] transition-colors hover:bg-slate-50 sm:flex"
            aria-label="Прокрутить вперёд"
            @click="scrollShelf(shelf.category.id, 1)"
          >
            <AppIcon name="chevron-right" :size="18" />
          </button>
        </div>
      </div>
    </template>
  </section>
</template>

<style scoped>
.shelf-header__arrow {
  fill: currentColor;
}
</style>
