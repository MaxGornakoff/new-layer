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

  <section
    v-else-if="product"
    class="product-page mx-auto my-5 w-full max-w-[1440px] sm:my-8 lg:my-12"
  >
    <div class="grid items-start gap-4 sm:gap-6 lg:grid-cols-2 lg:gap-10 xl:gap-14">
      <div class="min-w-0 rounded-[16px] bg-white p-3.5 sm:rounded-[20px] sm:p-6 lg:p-8">
        <div class="relative">
          <span
            class="absolute left-0 top-0 z-10 rounded-full px-2.5 py-1 text-[11px] font-medium text-white sm:px-3 sm:text-xs"
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
          class="mt-4 block max-h-[240px] w-full rounded-[12px] bg-slate-100 object-contain sm:mt-5 sm:max-h-[320px] sm:rounded-[16px]"
          controls
          playsinline
        />
      </div>

      <div class="flex min-w-0 flex-col gap-4 sm:gap-5 lg:py-2">
        <div class="flex flex-wrap items-center gap-2">
          <RouterLink
            v-if="product.category"
            :to="`/catalog?category=${product.category.slug}`"
            class="rounded-full bg-[#E8EEF9] px-3 py-1 text-xs font-medium text-[#3B72FF] no-underline transition-opacity hover:opacity-80 sm:text-sm"
          >
            {{ product.category.name }}
          </RouterLink>
          <span class="text-xs text-slate-400 sm:text-sm">Артикул: {{ product.sku }}</span>
        </div>

        <h1
          class="m-0 text-[22px] leading-tight font-semibold text-[#222222] sm:text-[28px] lg:text-[36px]"
        >
          {{ product.name }}
        </h1>

        <ProductPrice :product="product" size="lg" />

        <dl class="m-0 grid grid-cols-2 gap-2 sm:gap-3 sm:grid-cols-4">
          <div
            v-for="spec in specs"
            :key="spec.label"
            class="rounded-[12px] bg-white px-3 py-2.5 sm:rounded-[16px] sm:px-3.5 sm:py-3"
          >
            <dt class="m-0 text-[12px] text-slate-400 sm:text-[14px]">{{ spec.label }}</dt>
            <dd class="m-0 mt-0.5 text-[14px] font-semibold text-[#222222] sm:mt-1 sm:text-[16px]">
              {{ spec.value }}
            </dd>
          </div>
        </dl>

        <p
          class="m-0 flex items-start gap-2 text-[13px] font-medium leading-snug text-[#222222] sm:items-center sm:text-[15px]"
        >
          <span
            class="mt-0.5 inline-flex size-5 shrink-0 items-center justify-center rounded-full border-[1.5px] border-[#222222] text-[11px] font-semibold leading-none sm:mt-0"
            aria-hidden="true"
          >
            i
          </span>
          <span>Заказ от {{ ORDER_PACK_SIZE }} шт. (кратно {{ ORDER_PACK_SIZE }})</span>
        </p>

        <div class="w-full max-w-none sm:max-w-sm">
          <CartProductQuantityControl :product="product" variant="shelf" />
        </div>

        <div
          v-if="product.description"
          class="rounded-[16px] bg-white px-4 py-4 sm:rounded-[20px] sm:px-6 sm:py-5"
        >
          <h2 class="m-0 text-lg font-semibold text-[#222222] sm:text-[24px]">Описание</h2>
          <p
            class="m-0 mt-2.5 whitespace-pre-line text-[14px] leading-relaxed text-[#475569] sm:mt-3 sm:text-[15px]"
          >
            {{ product.description }}
          </p>
        </div>
      </div>
    </div>
  </section>

  <p v-else class="my-10 text-slate-500">Товар не найден.</p>
</template>
