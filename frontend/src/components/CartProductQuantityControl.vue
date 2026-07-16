<script setup>
import { computed, onUnmounted, ref, watch } from 'vue'
import { useCartStore } from '@/stores/cart'
import { useToastStore } from '@/stores/toast'
import QuantityControl from '@/components/QuantityControl.vue'
import AppIcon from '@/components/AppIcon.vue'

const COMMIT_DELAY_MS = 1000

const props = defineProps({
  product: {
    type: Object,
    required: true,
  },
  compact: {
    type: Boolean,
    default: false,
  },
  variant: {
    type: String,
    default: 'default',
    validator: (value) => ['default', 'shelf'].includes(value),
  },
})

const cart = useCartStore()
const toast = useToastStore()
const draft = ref('0')

let commitTimer = null

const inCartQuantity = computed(
  () => cart.items.find((item) => item.product_id === props.product.id)?.quantity ?? 0,
)

const isDisabled = computed(() => props.product.stock_quantity < 1)

const cartQuantity = computed({
  get: () => inCartQuantity.value,
  set: (value) => applyQuantity(value),
})

watch(inCartQuantity, (value) => {
  clearCommitTimer()
  draft.value = String(value)
}, { immediate: true })

function clearCommitTimer() {
  if (commitTimer !== null) {
    clearTimeout(commitTimer)
    commitTimer = null
  }
}

function scheduleCommit() {
  if (isDisabled.value || COMMIT_DELAY_MS <= 0) return

  clearCommitTimer()
  commitTimer = setTimeout(() => {
    commitTimer = null
    commitInput()
  }, COMMIT_DELAY_MS)
}

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
    draft.value = '0'
    return
  }

  if (inCartQuantity.value === 0) {
    const result = cart.addItem(props.product, quantity)

    if (result.message) {
      toast.show(result.message, result.success ? 'success' : 'error')
    }

    draft.value = String(
      cart.items.find((item) => item.product_id === props.product.id)?.quantity ?? quantity,
    )
    return
  }

  cart.updateQuantity(props.product.id, quantity)
  draft.value = String(quantity)
}

function addToCart() {
  applyQuantity(1)
}

function decrease() {
  clearCommitTimer()
  applyQuantity(inCartQuantity.value - 1)
}

function increase() {
  clearCommitTimer()
  applyQuantity(inCartQuantity.value + 1)
}

function commitInput() {
  const parsed = Number.parseInt(draft.value, 10)

  if (!Number.isFinite(parsed) || parsed <= 0) {
    if (inCartQuantity.value > 0) {
      cart.removeItem(props.product.id)
    }
    draft.value = '0'
    return
  }

  applyQuantity(parsed)
}

function onBlur() {
  clearCommitTimer()
  commitInput()
}

function onInputKeydown(event) {
  if (event.key === 'Enter') {
    event.target.blur()
  }
}

onUnmounted(clearCommitTimer)
</script>

<template>
  <QuantityControl
    v-if="variant === 'default'"
    v-model="cartQuantity"
    :min-quantity="0"
    :max-quantity="product.stock_quantity"
    :compact="compact"
    :disabled="isDisabled"
  />

  <div v-else class="w-full pt-[5px]" @click.stop>
    <button
      v-if="inCartQuantity === 0"
      type="button"
      class="flex h-11 cursor-pointer w-full items-center justify-center gap-2 rounded-full border-0 bg-[#3B72FF] text-[16px] font-medium text-white transition-colors hover:bg-[#2f63ef] disabled:cursor-not-allowed disabled:bg-slate-300"
      :disabled="isDisabled"
      @click="addToCart"
    >
      <span>В корзину</span>
      <AppIcon name="cart" :size="15" class="shrink-0 text-white" />
    </button>

    <div
      v-else
      class="flex h-11 w-full items-stretch overflow-hidden rounded-full bg-[#3B72FF] text-white"
    >
      <button
        type="button"
        class="flex w-10 shrink-0 cursor-pointer items-center justify-center border-0 bg-transparent text-xl leading-none transition-colors hover:bg-white/10"
        aria-label="Уменьшить количество"
        @click="decrease"
      >
        −
      </button>

      <input
        v-model="draft"
        type="number"
        min="0"
        :max="product.stock_quantity"
        class="min-w-0 m-1 bg-[rgba(128,163,254,0.5)] rounded-[10px] flex-1 border-0 text-center text-sm font-semibold text-white [appearance:textfield] outline-none [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
        aria-label="Количество"
        @input="scheduleCommit"
        @blur="onBlur"
        @keydown="onInputKeydown"
      />

      <button
        type="button"
        class="flex w-10 cursor-pointer shrink-0 items-center justify-center border-0 bg-transparent text-xl leading-none transition-colors hover:bg-white/10 disabled:cursor-not-allowed disabled:opacity-45"
        aria-label="Увеличить количество"
        :disabled="inCartQuantity >= product.stock_quantity"
        @click="increase"
      >
        +
      </button>
    </div>
  </div>
</template>
