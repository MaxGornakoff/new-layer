<script setup>
import { RouterLink } from 'vue-router'
import { useCartStore } from '@/stores/cart'
import CartOrderPackNotice from '@/components/CartOrderPackNotice.vue'
import CartQuantityControl from '@/components/CartQuantityControl.vue'

const cart = useCartStore()
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
        Заказ оформляется кратно {{ cart.orderPackSize }} катушкам — в любом составе категорий.</p>
    </div>

    <div v-if="cart.items.length" class="grid gap-4">
      <CartOrderPackNotice show-catalog-link />

      <div class="card p-7 flex flex-col justify-end items-end">
        <table class="table">
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
              <td>{{ item.price.toLocaleString('ru-RU') }} ₽</td>
              <td>
                <CartQuantityControl
                  :product-id="item.product_id"
                  :quantity="item.quantity"
                  :max-quantity="item.stock_quantity"
                />
              </td>
              <td>{{ (item.price * item.quantity).toLocaleString('ru-RU') }} ₽</td>
              <td>
                <button class="btn secondary" @click="cart.removeItem(item.product_id)">×</button>
              </td>
            </tr>
          </tbody>
        </table>

        <p class="text-right py-5"><strong>Итого: {{ cart.totalPrice.toLocaleString('ru-RU') }} ₽</strong></p>

        <RouterLink
          v-if="cart.canCheckout"
          class="btn"
          to="/checkout"
        >
          Оформить заказ
        </RouterLink>
        <button v-else class="btn" type="button" disabled>
          Оформить заказ (нужно кратно {{ cart.orderPackSize }})
        </button>
      </div>
    </div>

    <p v-else class="muted">Корзина пуста. <RouterLink to="/catalog">Перейти в каталог</RouterLink></p>
  </section>
</template>
