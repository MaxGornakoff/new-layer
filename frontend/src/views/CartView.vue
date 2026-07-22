<script setup>
import { RouterLink } from 'vue-router'
import { useCartStore } from '@/stores/cart'
import CartOrderPackNotice from '@/components/CartOrderPackNotice.vue'
import CartQuantityControl from '@/components/CartQuantityControl.vue'

const cart = useCartStore()

function formatMoney(value) {
  return Number(value).toLocaleString('ru-RU')
}
</script>

<template>
  <section class="cart mx-auto w-full max-w-[1440px]">
    <div class="title-container my-6 flex flex-col gap-2 sm:my-8 sm:gap-2.5 lg:my-10">
      <h1 class="m-0 text-2xl font-semibold uppercase sm:text-[28px] lg:text-3xl">Корзина</h1>
      <p class="muted m-0 flex items-start gap-2 text-[14px] font-medium leading-snug text-[#222222] sm:items-center sm:gap-2.5 sm:text-[16px] lg:text-[18px]">
        <span
          class="mt-0.5 inline-flex size-5 min-w-5 shrink-0 items-center justify-center rounded-full border-[1.5px] border-[#222222] p-0 text-xs font-semibold sm:mt-0 sm:text-sm"
        >
          i
        </span>
        Заказ оформляется кратно {{ cart.orderPackSize }} катушкам — в любом составе категорий.
      </p>
    </div>

    <div v-if="cart.items.length" class="grid gap-4">
      <CartOrderPackNotice show-catalog-link />

      <div class="card cart-panel p-4 sm:p-6 lg:p-7">
        <!-- Mobile cards -->
        <ul class="cart-mobile m-0 list-none space-y-3 p-0 md:hidden">
          <li
            v-for="item in cart.items"
            :key="item.product_id"
            class="cart-mobile__item rounded-[16px] border border-border bg-slate-50/80 p-3.5"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0 flex-1">
                <p class="m-0 text-[15px] font-semibold leading-snug text-[#222]">{{ item.name }}</p>
                <p class="m-0 mt-1 text-sm text-slate-500">{{ formatMoney(item.price) }} ₽ / шт.</p>
              </div>
              <button
                type="button"
                class="btn secondary shrink-0 px-3 py-1.5"
                aria-label="Удалить товар"
                @click="cart.removeItem(item.product_id)"
              >
                ×
              </button>
            </div>

            <div class="mt-3 flex items-center justify-between gap-3">
              <CartQuantityControl
                :product-id="item.product_id"
                :quantity="item.quantity"
                :max-quantity="item.stock_quantity"
              />
              <p class="m-0 text-[16px] font-semibold text-[#222]">
                {{ formatMoney(item.price * item.quantity) }} ₽
              </p>
            </div>
          </li>
        </ul>

        <!-- Desktop table -->
        <div class="hidden overflow-x-auto md:block">
          <table class="table min-w-full">
            <thead>
              <tr>
                <th>Товар</th>
                <th>Цена</th>
                <th>Кол-во</th>
                <th>Сумма</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in cart.items" :key="item.product_id">
                <td>{{ item.name }}</td>
                <td>{{ formatMoney(item.price) }} ₽</td>
                <td>
                  <CartQuantityControl
                    :product-id="item.product_id"
                    :quantity="item.quantity"
                    :max-quantity="item.stock_quantity"
                  />
                </td>
                <td>{{ formatMoney(item.price * item.quantity) }} ₽</td>
                <td>
                  <button class="btn secondary" @click="cart.removeItem(item.product_id)">×</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mt-5 flex flex-col items-stretch gap-3 border-t border-border pt-5 sm:items-end">
          <p class="m-0 text-right text-[16px] sm:text-[18px]">
            <strong>Итого: {{ formatMoney(cart.totalPrice) }} ₽</strong>
          </p>

          <RouterLink
            v-if="cart.canCheckout"
            class="btn w-full justify-center sm:w-auto"
            to="/checkout"
          >
            Оформить заказ
          </RouterLink>
          <button v-else class="btn w-full justify-center sm:w-auto" type="button" disabled>
            Оформить заказ (нужно кратно {{ cart.orderPackSize }})
          </button>
        </div>
      </div>
    </div>

    <p v-else class="muted text-[15px] sm:text-base">
      Корзина пуста. <RouterLink to="/catalog">Перейти в каталог</RouterLink>
    </p>
  </section>
</template>
