import { defineStore } from 'pinia'
import { computed, ref, watch } from 'vue'
import { getOrderPackStatus, ORDER_PACK_SIZE } from '@/lib/orderPack'

const STORAGE_KEY = 'filament-shop-cart'

function loadFromStorage() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    if (!raw) return []

    const parsed = JSON.parse(raw)
    return Array.isArray(parsed) ? parsed : []
  } catch {
    return []
  }
}

export const useCartStore = defineStore('cart', () => {
  const items = ref(loadFromStorage())

  const totalItems = computed(() =>
    items.value.reduce((sum, item) => sum + item.quantity, 0),
  )

  const totalPrice = computed(() =>
    items.value.reduce((sum, item) => sum + item.price * item.quantity, 0),
  )

  const packStatus = computed(() => getOrderPackStatus(totalItems.value))

  const canCheckout = computed(
    () => items.value.length > 0 && packStatus.value.canCheckout,
  )

  function addItem(product, quantity = 1) {
    if (!product?.id) {
      return { success: false, message: 'Товар недоступен' }
    }

    if (product.stock_quantity < 1) {
      return { success: false, message: 'Товар закончился на складе' }
    }

    const safeQuantity = Math.max(1, Number(quantity) || 1)
    const existing = items.value.find((item) => item.product_id === product.id)
    const currentQuantity = existing?.quantity ?? 0
    const nextQuantity = Math.min(currentQuantity + safeQuantity, product.stock_quantity)
    const addedQuantity = nextQuantity - currentQuantity

    if (addedQuantity <= 0) {
      return {
        success: false,
        message: `Доступно только ${product.stock_quantity} шт.`,
      }
    }

    if (existing) {
      existing.quantity = nextQuantity
      existing.price = Number(product.price)
      existing.stock_quantity = product.stock_quantity
      existing.name = product.name
    } else {
      items.value.push({
        product_id: product.id,
        slug: product.slug,
        name: product.name,
        price: Number(product.price),
        quantity: nextQuantity,
        stock_quantity: product.stock_quantity,
      })
    }

    return {
      success: true,
      message:
        addedQuantity < safeQuantity
          ? `Добавлено ${addedQuantity} шт. — больше нет в наличии`
          : 'Товар добавлен в корзину',
      productName: product.name,
      addedQuantity,
    }
  }

  function updateQuantity(productId, quantity) {
    const item = items.value.find((entry) => entry.product_id === productId)
    if (!item) return

    const maxQuantity = Math.max(1, item.stock_quantity ?? quantity)
    item.quantity = Math.min(Math.max(1, quantity), maxQuantity)
  }

  function removeItem(productId) {
    items.value = items.value.filter((entry) => entry.product_id !== productId)
  }

  function clear() {
    items.value = []
  }

  function toOrderPayload() {
    return items.value.map((item) => ({
      product_id: item.product_id,
      quantity: item.quantity,
    }))
  }

  watch(
    items,
    (value) => {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(value))
    },
    { deep: true },
  )

  return {
    items,
    totalItems,
    totalPrice,
    packStatus,
    canCheckout,
    orderPackSize: ORDER_PACK_SIZE,
    addItem,
    updateQuantity,
    removeItem,
    clear,
    toOrderPayload,
  }
})
