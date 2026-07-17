<script setup>
import { useCartStore } from '@/stores/cart'
import QuantityControl from '@/components/QuantityControl.vue'

const props = defineProps({
  productId: {
    type: Number,
    required: true,
  },
  quantity: {
    type: Number,
    required: true,
  },
  maxQuantity: {
    type: Number,
    required: true,
  },
  compact: {
    type: Boolean,
    default: false,
  },
  variant: {
    type: String,
    default: 'shelf',
    validator: (value) => ['default', 'shelf'].includes(value),
  },
})

const cart = useCartStore()

function onUpdate(value) {
  cart.updateQuantity(props.productId, value)
}

function onDecreaseAtMin() {
  cart.removeItem(props.productId)
}
</script>

<template>
  <QuantityControl
    :model-value="quantity"
    :min-quantity="1"
    :max-quantity="maxQuantity"
    :compact="compact"
    :variant="variant"
    allow-remove-at-min
    @update:model-value="onUpdate"
    @decrease-at-min="onDecreaseAtMin"
  />
</template>
