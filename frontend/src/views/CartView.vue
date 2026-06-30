<script setup>
import { RouterLink } from 'vue-router'
import { useCartStore } from '@/stores/cart'

const cart = useCartStore()
</script>

<template>
  <section>
    <h1>Корзина</h1>

    <div v-if="cart.items.length" class="card">
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
              <input
                type="number"
                min="1"
                :max="item.stock_quantity"
                :value="item.quantity"
                @change="cart.updateQuantity(item.product_id, Number($event.target.value))"
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
      <RouterLink class="btn" to="/checkout">Оформить заказ</RouterLink>
    </div>

    <p v-else class="muted">Корзина пуста. <RouterLink to="/catalog">Перейти в каталог</RouterLink></p>
  </section>
</template>
