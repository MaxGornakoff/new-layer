<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import api from '@/api/client'
import AppLoader from '@/components/AppLoader.vue'
import CartProductQuantityControl from '@/components/CartProductQuantityControl.vue'
import ProductImagesSlider from '@/components/ProductImagesSlider.vue'
import ProductPrice from '@/components/ProductPrice.vue'
import { ORDER_PACK_SIZE } from '@/lib/orderPack'

const route = useRoute()
const product = ref(null)
const loading = ref(true)

const stockBadge = computed(() => {
  const quantity = product.value?.stock_quantity ?? 0

  if (quantity <= 0) {
    return { label: 'Нет в наличии', className: 'bg-slate-400' }
  }

  if (quantity <= 5) {
    return { label: 'Мало', className: 'bg-amber-600' }
  }

  return { label: 'В наличии', className: 'bg-[#637C86]' }
})

const specs = computed(() => {
  if (!product.value) return []

  return [
    { label: 'Цвет', value: product.value.color },
    { label: 'Диаметр', value: `${product.value.diameter} мм` },
    { label: 'Вес', value: `${product.value.weight_grams} г` },
    {
      label: 'Остаток',
      value: `${product.value.stock_quantity} шт.`,
    },
  ].filter((item) => item.value != null && item.value !== '')
})

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

  <section v-else-if="product" class="product-page mx-auto my-8 w-full max-w-[1440px] lg:my-12">
    <div class="grid items-start gap-6 lg:grid-cols-2 lg:gap-10 xl:gap-14">
      <div class="min-w-0 rounded-[20px] bg-white p-5 sm:p-7 lg:p-8">
        <div class="relative">
          <span
            class="absolute left-0 top-0 z-10 rounded-full px-3 py-1 text-xs font-medium text-white"
            :class="stockBadge.className"
          >
            {{ stockBadge.label }}
          </span>

          <ProductImagesSlider
            :images="product.images"
            variant="thumbs"
            :reset-on-leave="false"
          />

        </div>

        <video
          v-if="product.video_url"
          :src="product.video_url"
          class="mt-5 block w-full max-h-[320px] rounded-[16px] bg-slate-100 object-contain"
          controls
          playsinline
        />
      </div>

      <div class="flex min-w-0 flex-col gap-5 lg:py-2">
        <div class="flex flex-wrap items-center gap-2">
          <RouterLink
            v-if="product.category"
            :to="`/catalog?category=${product.category.slug}`"
            class="rounded-full bg-[#E8EEF9] px-3 py-1 text-sm font-medium text-[#3B72FF] no-underline transition-opacity hover:opacity-80"
          >
            {{ product.category.name }}
          </RouterLink>
          <span class="text-sm text-slate-400">Артикул: {{ product.sku }}</span>
        </div>

        <h1 class="m-0 text-[28px] leading-tight font-semibold text-[#222222] sm:text-[32px] lg:text-[36px]">
          {{ product.name }}
        </h1>

        <ProductPrice :product="product" size="lg" />

        <dl class="m-0 grid grid-cols-2 gap-3 sm:grid-cols-4">
          <div
            v-for="spec in specs"
            :key="spec.label"
            class="rounded-[16px] bg-white px-3.5 py-3"
          >
            <dt class="m-0 text-[14px] text-slate-400">{{ spec.label }}</dt>
            <dd class="m-0 mt-1 text-[16px] font-semibold text-[#222222]">{{ spec.value }}</dd>
          </div>
        </dl>

        <p class="m-0 flex items-center gap-2 text-[15px] font-medium text-[#222222]">
          <span
            class="inline-flex size-5 shrink-0 items-center justify-center rounded-full border-[1.5px] border-[#222222] text-[11px] font-semibold leading-none"
            aria-hidden="true"
          >
            i
          </span>
          <span>Заказ от {{ ORDER_PACK_SIZE }} шт. (кратно {{ ORDER_PACK_SIZE }})</span>
        </p>

        <div class="max-w-sm">
          <CartProductQuantityControl :product="product" variant="shelf" />
        </div>

        <div
          v-if="product.description"
          class="rounded-[20px] bg-white px-5 py-5 sm:px-6"
        >
          <h2 class="m-0 text-[24px] font-semibold text-[#222222]">Описание</h2>
          <p class="m-0 mt-3 whitespace-pre-line text-[15px] leading-relaxed text-[#475569]">
            {{ product.description }}
          </p>
        </div>
      </div>
    </div>
  </section>

  <p v-else class="my-10 text-slate-500">Товар не найден.</p>
</template>
