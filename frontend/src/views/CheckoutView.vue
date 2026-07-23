<script setup>
import { computed, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/api/client'
import CartOrderPackNotice from '@/components/CartOrderPackNotice.vue'
import CheckoutDeliverySection from '@/components/CheckoutDeliverySection.vue'
import { validateForm } from '@/lib/formValidation'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import { useToastStore } from '@/stores/toast'

const auth = useAuthStore()
const cart = useCartStore()
const toast = useToastStore()
const router = useRouter()
const loading = ref(false)
const error = ref('')
const deliverySection = ref(null)
const delivery = ref({
  ready: false,
  quote: null,
  city: null,
  pickupPoint: null,
  deliveryProvider: '',
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

async function submit(event) {
  if (!validateForm(event?.target)) return

  if (!canSubmit.value) {
    toast.warning('Выберите способ доставки и пункт выдачи, чтобы оформить заказ', {
      anchor: deliverySection.value,
      duration: 4200,
    })
    return
  }

  loading.value = true
  error.value = ''

  try {
    await api.post('/orders', {
      ...form,
      delivery_provider: delivery.value.deliveryProvider || delivery.value.quote?.name || null,
      delivery_address: delivery.value.deliveryAddress,
      items: cart.toOrderPayload(),
    })
    cart.clear()
    router.push({ name: 'profile' })
  } catch (err) {
    const message = err.response?.data?.message || 'Не удалось оформить заказ.'
    error.value = message
    toast.error(message)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <section class="checkout mx-auto w-full max-w-[1440px]">
    <div class="title-container my-6 flex flex-col gap-2 sm:my-8 sm:gap-2.5 lg:my-10">
      <h1 class="m-0 text-2xl font-semibold uppercase sm:text-[28px] lg:text-3xl">Оформление заказа</h1>
      <p class="muted m-0 flex items-start gap-2 text-[14px] font-medium leading-snug text-[#222222] sm:items-center sm:gap-2.5 sm:text-[16px] lg:text-[18px]">
        <span
          class="mt-0.5 inline-flex size-5 min-w-5 shrink-0 items-center justify-center rounded-full border-[1.5px] border-[#222222] p-0 text-xs font-semibold sm:mt-0 sm:text-sm"
        >
          i
        </span>
        Оплата при получении.</p>
  </div>

    <CartOrderPackNotice v-if="cart.items.length" show-catalog-link class="mb-4" />

    <form class="grid min-w-0 gap-4 lg:grid-cols-2 lg:items-start" novalidate @submit.prevent="submit">
      <div class="p-5 card grid min-w-0 gap-4 self-start lg:row-span-2">
        <h3 class="m-0 text-lg font-semibold">Контактные данные</h3>

        <label class="field">
          <span>Имя</span>
          <input v-model="form.customer_name" name="customer_name" required />
        </label>
        <label class="field">
          <span>Телефон</span>
          <input v-model="form.customer_phone" name="customer_phone" required />
        </label>
        <label class="field">
          <span>Email</span>
          <input v-model="form.customer_email" name="customer_email" type="email" />
        </label>
        <label class="field">
          <span>Комментарий</span>
          <textarea v-model="form.comment" name="comment" rows="2" />
        </label>
      </div>

      <div ref="deliverySection" class="min-w-0">
        <CheckoutDeliverySection
          v-if="cart.items.length"
          :total-quantity="cart.totalItems"
          :can-calculate="cart.canCheckout"
          @update:delivery="onDeliveryUpdate"
        />
      </div>

      <div class="card grid min-w-0 gap-4 self-start">
        <div class="grid gap-1.5">
          <p>Товары: <strong>{{ cart.totalPrice.toLocaleString('ru-RU') }} ₽</strong></p>
          <p v-if="delivery.quote?.price != null">
            Доставка ({{ delivery.quote.name }}):
            <strong>{{ Number(delivery.quote.price).toLocaleString('ru-RU') }} ₽</strong>
          </p>
          <p><strong>Итого: {{ orderTotal.toLocaleString('ru-RU') }} ₽</strong></p>
        </div>

        <p v-if="error" class="m-0 text-sm text-red-600">{{ error }}</p>

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
