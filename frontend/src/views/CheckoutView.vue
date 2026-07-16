<script setup>
import { computed, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/api/client'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import CartOrderPackNotice from '@/components/CartOrderPackNotice.vue'
import CheckoutDeliverySection from '@/components/CheckoutDeliverySection.vue'

const auth = useAuthStore()
const cart = useCartStore()
const router = useRouter()
const loading = ref(false)
const error = ref('')
const delivery = ref({
  ready: false,
  quote: null,
  city: null,
  pickupPoint: null,
  deliveryAddress: '',
})

const form = reactive({
  customer_name: auth.user?.name || '',
  customer_phone: auth.user?.phone || '',
  customer_email: auth.user?.email || '',
  comment: '',
})

const deliveryPrice = computed(() => delivery.value.quote?.price ?? 0)
const orderTotal = computed(() => cart.totalPrice + deliveryPrice.value)
const canSubmit = computed(() => cart.canCheckout && delivery.value.ready)

function onDeliveryUpdate(value) {
  delivery.value = value
}

async function submit() {
  if (!canSubmit.value) return

  loading.value = true
  error.value = ''

  try {
    await api.post('/orders', {
      ...form,
      delivery_address: delivery.value.deliveryAddress,
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

    <form class="grid min-w-0 gap-4 lg:grid-cols-2 lg:items-start" @submit.prevent="submit">
      <div class="card grid min-w-0 gap-4 self-start lg:row-span-2">
        <h3 class="m-0 text-lg font-semibold">Контактные данные</h3>

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
          <span>Комментарий</span>
          <textarea v-model="form.comment" rows="2" />
        </label>
      </div>

      <CheckoutDeliverySection
        v-if="cart.items.length"
        :total-quantity="cart.totalItems"
        :can-calculate="cart.canCheckout"
        @update:delivery="onDeliveryUpdate"
      />

      <div class="card grid min-w-0 gap-4 self-start">
        <div class="grid gap-1.5">
          <p>Товары: <strong>{{ cart.totalPrice.toLocaleString('ru-RU') }} ₽</strong></p>
          <p v-if="delivery.quote?.price != null">
            Доставка ({{ delivery.quote.name }}):
            <strong>{{ Number(delivery.quote.price).toLocaleString('ru-RU') }} ₽</strong>
          </p>
          <p><strong>Итого: {{ orderTotal.toLocaleString('ru-RU') }} ₽</strong></p>
        </div>

        <p v-if="error" class="error">{{ error }}</p>

        <button class="btn" type="submit" :disabled="loading || !canSubmit">
          {{
            loading
              ? 'Отправка...'
              : !cart.canCheckout
                ? `Нужно кратно ${cart.orderPackSize} катушек`
                : !delivery.ready
                  ? 'Выберите доставку и ПВЗ'
                  : 'Подтвердить заказ'
          }}
        </button>
      </div>
    </form>
  </section>
</template>

<style scoped>
.error {
  color: #dc2626;
}
</style>
