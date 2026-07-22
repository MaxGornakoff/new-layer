<script setup>
import { RouterLink } from 'vue-router'
import { useCartStore } from '@/stores/cart'
import AppIcon from '@/components/AppIcon.vue'
import CartOrderPackNotice from '@/components/CartOrderPackNotice.vue'
import CartQuantityControl from '@/components/CartQuantityControl.vue'

const cart = useCartStore()
</script>

<template>
  <div class="group relative flex items-center">
    <RouterLink
      to="/cart"
      class="relative z-[2] inline-flex size-5 items-center justify-center text-inherit min-[992px]:size-[25px]"
      aria-label="Корзина"
    >
      <AppIcon name="cart" />
      <span
        v-if="cart.totalItems"
        class="absolute top-1 right-1 flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-[#2563eb] px-1 text-center text-[0.7rem] leading-[18px] text-white"
      >
        {{ cart.totalItems }}
      </span>
    </RouterLink>

    <!-- Невидимый мост шириной с модалку — комфортный переход курсора с иконки -->
    <span
      v-if="cart.items.length"
      class="absolute top-[-0.35rem] right-0 z-[1] hidden h-[calc(100%+1rem)] w-[min(360px,calc(100vw-2rem))] min-[992px]:group-hover:block"
      aria-hidden="true"
    />

    <div
      v-if="cart.items.length"
      class="absolute top-[calc(100%+0.5rem)] right-0 z-[120] hidden w-[min(360px,calc(100vw-2rem))] rounded-[20px] border border-slate-200 bg-white p-5 shadow-[0_12px_32px_rgba(15,23,42,0.12)] min-[992px]:group-hover:block"
    >
      <p class="mb-3 text-[18px] font-semibold">Корзина</p>

      <ul class="m-0 max-h-[280px] list-none overflow-y-auto p-0">
        <li
          v-for="item in cart.items"
          :key="item.product_id"
          class="grid gap-2 border-b border-slate-100 py-[0.65rem] last:border-b-0"
        >
          <div class="flex justify-between gap-3">
            <span class="min-w-0 truncate">{{ item.name }}</span>
            <span class="shrink-0 font-semibold">
              {{ (item.price * item.quantity).toLocaleString('ru-RU') }} ₽
            </span>
          </div>

          <div class="flex items-center justify-between gap-3">
            <div @click.stop>
              <CartQuantityControl
                compact
                :product-id="item.product_id"
                :quantity="item.quantity"
                :max-quantity="item.stock_quantity"
              />
            </div>

            <button
              type="button"
              class="flex size-7 cursor-pointer items-center justify-center rounded-lg border-0 bg-slate-100 text-[1.1rem] leading-none hover:bg-slate-200"
              aria-label="Удалить из корзины"
              @click.stop="cart.removeItem(item.product_id)"
            >
              ×
            </button>
          </div>
        </li>
      </ul>

      <div class="mt-3 border-t border-slate-100 pt-3">
        <CartOrderPackNotice compact class="mb-3" />

        <p class="mb-3">
          Итого: <strong>{{ cart.totalPrice.toLocaleString('ru-RU') }} ₽</strong>
        </p>
        <RouterLink
          :to="cart.canCheckout ? '/checkout' : '/cart'"
          class="btn flex w-full"
          @click.stop
        >
          {{ cart.canCheckout ? 'К оформлению' : 'Добрать в корзине' }}
        </RouterLink>
      </div>
    </div>
  </div>
</template>
