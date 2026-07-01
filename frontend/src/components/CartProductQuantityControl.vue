<script setup>
import { computed } from 'vue'
import { useCartStore } from '@/stores/cart'
import { useToastStore } from '@/stores/toast'
import QuantityControl from '@/components/QuantityControl.vue'

const props = defineProps({
  product: {
    type: Object,
    required: true,
  },
  compact: {
    type: Boolean,
    default: false,
  },
})

const cart = useCartStore()
const toast = useToastStore()

const inCartQuantity = computed(
  () => cart.items.find((item) => item.product_id === props.product.id)?.quantity ?? 0,
)

const cartQuantity = computed({
  get: () => inCartQuantity.value,
  set: (value) => applyQuantity(value),
})

function applyQuantity(target) {
  if (props.product.stock_quantity < 1) return

  const quantity = Math.min(
    Math.max(0, Math.floor(Number(target) || 0)),
    props.product.stock_quantity,
  )

  if (quantity <= 0) {
    if (inCartQuantity.value > 0) {
      cart.removeItem(props.product.id)
    }
    return
  }

  if (inCartQuantity.value === 0) {
    const result = cart.addItem(props.product, quantity)

    if (result.message) {
      toast.show(result.message, result.success ? 'success' : 'error')
    }
    return
  }

  cart.updateQuantity(props.product.id, quantity)
}
</script>

<template>
  <QuantityControl
    v-model="cartQuantity"
    :min-quantity="0"
    :max-quantity="product.stock_quantity"
    :compact="compact"
    :disabled="product.stock_quantity < 1"
  />
</template>
