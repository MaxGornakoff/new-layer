<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useCartStore } from '@/stores/cart'

const props = defineProps({
  compact: {
    type: Boolean,
    default: false,
  },
  showCatalogLink: {
    type: Boolean,
    default: false,
  },
})

const cart = useCartStore()

const progressPercent = computed(() => {
  if (cart.canCheckout) return 100
  if (!cart.packStatus.nextPackTotal) return 0

  return Math.min(
    100,
    Math.round((cart.totalItems / cart.packStatus.nextPackTotal) * 100),
  )
})
</script>

<template>
  <div
    v-if="cart.totalItems > 0"
    class="rounded-xl border px-5 py-4"
    :class="cart.canCheckout ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50'"
  >
    <div class="mb-2 flex items-center justify-between gap-3 text-sm">
      <span class="font-medium text-slate-800">
        В корзине: {{ cart.totalItems }} {{ cart.totalItems === 1 ? 'катушка' : cart.totalItems < 5 ? 'катушки' : 'катушек' }}
      </span>
      <span v-if="!compact" class="text-slate-500">Заказ кратно {{ cart.packStatus.packSize }}</span>
    </div>

    <div class="mb-2 h-2 overflow-hidden rounded-full bg-white/80">
      <div
        class="h-full rounded-full transition-all duration-300"
        :class="cart.canCheckout ? 'bg-emerald-500' : 'bg-amber-500'"
        :style="{ width: `${progressPercent}%` }"
      />
    </div>

    <p
      v-if="cart.canCheckout"
      class="m-0 text-sm font-medium text-emerald-800"
    >
      {{ cart.packStatus.successMessage }}
    </p>
    <p v-else class="m-0 text-sm text-amber-900">
      {{ cart.packStatus.message }}
    </p>

    <RouterLink
      v-if="showCatalogLink && !cart.canCheckout"
      to="/catalog"
      class="mt-2 inline-block text-sm font-medium text-brand"
    >
      Перейти в каталог
    </RouterLink>
  </div>
</template>
