<script setup>
import { RouterLink } from 'vue-router'
import { useCartStore } from '@/stores/cart'
import CartOrderPackNotice from '@/components/CartOrderPackNotice.vue'
import CartQuantityControl from '@/components/CartQuantityControl.vue'

const cart = useCartStore()
</script>

<template>
  <section>
    <h1>Корзина</h1>
    <p class="muted">Заказ оформляется кратно {{ cart.orderPackSize }} катушкам — в любом составе категорий.</p>

    <div v-if="cart.items.length" class="grid gap-4">
      <CartOrderPackNotice show-catalog-link />

      <div class="card">
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

        <p><strong>Итого: {{ cart.totalPrice.toLocaleString('ru-RU') }} ₽</strong></p>

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
