<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/api/client'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import CartOrderPackNotice from '@/components/CartOrderPackNotice.vue'

const auth = useAuthStore()
const cart = useCartStore()
const router = useRouter()
const loading = ref(false)
const error = ref('')

const form = reactive({
  customer_name: auth.user?.name || '',
  customer_phone: auth.user?.phone || '',
  customer_email: auth.user?.email || '',
  delivery_address: '',
  comment: '',
})

async function submit() {
  if (!cart.items.length || !cart.canCheckout) return

  loading.value = true
  error.value = ''

  try {
    await api.post('/orders', {
      ...form,
      items: cart.toOrderPayload(),
    })
    cart.clear()
    router.push({ name: 'profile' })
  } catch (err) {
    error.value = err.response?.data?.message || 'Не удалось оформить заказ.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <section>
    <h1>Оформление заказа</h1>
    <p class="muted">Оплата при получении. Онлайн-оплата будет добавлена позже.</p>

    <CartOrderPackNotice v-if="cart.items.length" show-catalog-link class="mb-4" />

    <form class="card checkout" @submit.prevent="submit">
      <label class="field">
        <span>Имя</span>
        <input v-model="form.customer_name" required />
      </label>
      <label class="field">
        <span>Телефон</span>
        <input v-model="form.customer_phone" required />
      </label>
      <label class="field">
        <span>Email</span>
        <input v-model="form.customer_email" type="email" />
      </label>
      <label class="field">
        <span>Адрес доставки</span>
        <textarea v-model="form.delivery_address" rows="3" required />
      </label>
      <label class="field">
        <span>Комментарий</span>
        <textarea v-model="form.comment" rows="2" />
      </label>

      <p><strong>Сумма заказа: {{ cart.totalPrice.toLocaleString('ru-RU') }} ₽</strong></p>
      <p v-if="error" class="error">{{ error }}</p>

      <button class="btn" type="submit" :disabled="loading || !cart.canCheckout">
        {{ loading ? 'Отправка...' : cart.canCheckout ? 'Подтвердить заказ' : `Нужно кратно ${cart.orderPackSize} катушек` }}
      </button>
    </form>
  </section>
</template>

<style scoped>
.checkout {
  max-width: 560px;
}

.error {
  color: #dc2626;
}
</style>
