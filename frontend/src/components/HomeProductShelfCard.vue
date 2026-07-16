<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import ProductImagesSlider from '@/components/ProductImagesSlider.vue'
import CartProductQuantityControl from '@/components/CartProductQuantityControl.vue'
import ProductPrice from '@/components/ProductPrice.vue'
import { ORDER_PACK_SIZE } from '@/lib/orderPack'

const props = defineProps({
  product: {
    type: Object,
    required: true,
  },
  fluid: {
    type: Boolean,
    default: false,
  },
})

const router = useRouter()

const stockBadge = computed(() => {
  const quantity = props.product.stock_quantity ?? 0

  if (quantity <= 0) {
    return { label: 'Нет', className: 'bg-slate-400' }
  }

  if (quantity <= 5) {
    return { label: 'Мало', className: 'bg-amber-600' }
  }

  return { label: 'Много', className: 'bg-[#637C86]' }
})

function openProduct() {
  router.push({ name: 'product', params: { slug: props.product.slug } })
}
</script>

<template>
  <article
    class="flex cursor-pointer flex-col rounded-[16px] bg-white p-3.5 transition-shadow duration-200 hover:shadow-[0_12px_32px_rgba(15,23,42,0.1)] sm:rounded-[20px] sm:p-5 lg:p-7"
    :class="fluid ? 'min-w-0 w-full' : 'w-[168px] shrink-0 sm:w-[200px] lg:w-[232px]'"
    @click="openProduct"
  >
    <div class="relative mb-2 sm:mb-3">
      <span
        class="absolute left-0 top-0 z-10 rounded-full px-2 py-0.5 text-[11px] font-medium text-white sm:px-2.5 sm:py-1 sm:text-[12px]"
        :class="stockBadge.className"
      >
        {{ stockBadge.label }}
      </span>

      <ProductImagesSlider :images="product.images" />
    </div>

    <div class="mt-auto flex flex-col gap-1.5 sm:gap-2">
      <ProductPrice :product="product" size="md" />

      <h3 class="m-0 line-clamp-2 text-[13px] leading-snug font-normal text-[#222222] sm:text-[15px] lg:text-[16px]">
        {{ product.name }}
      </h3>

      <p
        class="m-0 flex items-center gap-1 text-[12px] text-slate-400 sm:text-[14px] lg:text-[16px]"
        :title="`Минимальный заказ кратен ${ORDER_PACK_SIZE} катушкам`"
      >
        <span>Заказ от {{ ORDER_PACK_SIZE }} шт.</span>
        <span
          class="inline-flex size-3.5 items-center justify-center rounded-full border border-slate-400 text-[9px] leading-none sm:size-4 sm:text-[10px]"
          aria-hidden="true"
        >
          i
        </span>
      </p>

      <CartProductQuantityControl :product="product" variant="shelf" />
    </div>
  </article>
</template>
