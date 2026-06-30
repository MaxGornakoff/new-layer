<script setup>
import { RouterLink } from 'vue-router'
import { useCartStore } from '@/stores/cart'
import AppIcon from '@/components/AppIcon.vue'

const cart = useCartStore()

function increaseQuantity(productId) {
  const item = cart.items.find((entry) => entry.product_id === productId)
  if (!item) return

  const maxQuantity = item.stock_quantity ?? item.quantity
  if (item.quantity < maxQuantity) {
    cart.updateQuantity(productId, item.quantity + 1)
  }
}

function decreaseQuantity(productId) {
  const item = cart.items.find((entry) => entry.product_id === productId)
  if (!item) return

  if (item.quantity <= 1) {
    cart.removeItem(productId)
    return
  }

  cart.updateQuantity(productId, item.quantity - 1)
}
</script>

<template>
  <div class="header-cart flex items-center" :class="{ 'header-cart--has-items': cart.totalItems > 0 }">
    <RouterLink to="/cart" class="header-cart__link" aria-label="Корзина">
      <AppIcon name="cart" />
      <span v-if="cart.totalItems" class="header-cart__badge">{{ cart.totalItems }}</span>
    </RouterLink>

    <div v-if="cart.items.length" class="header-cart__preview">
      <p class="header-cart__title">Корзина</p>

      <ul class="header-cart__list">
        <li v-for="item in cart.items" :key="item.product_id" class="header-cart__item">
          <div class="header-cart__item-info">
            <span class="header-cart__item-name">{{ item.name }}</span>
            <span class="header-cart__item-price">
              {{ (item.price * item.quantity).toLocaleString('ru-RU') }} ₽
            </span>
          </div>

          <div class="header-cart__item-actions">
            <div class="header-cart__quantity">
              <button
                type="button"
                class="header-cart__quantity-btn"
                aria-label="Уменьшить количество"
                @click.stop="decreaseQuantity(item.product_id)"
              >
                −
              </button>
              <span class="header-cart__quantity-value">{{ item.quantity }}</span>
              <button
                type="button"
                class="header-cart__quantity-btn"
                aria-label="Увеличить количество"
                :disabled="item.quantity >= item.stock_quantity"
                @click.stop="increaseQuantity(item.product_id)"
              >
                +
              </button>
            </div>

            <button
              type="button"
              class="header-cart__remove"
              aria-label="Удалить из корзины"
              @click.stop="cart.removeItem(item.product_id)"
            >
              ×
            </button>
          </div>
        </li>
      </ul>

      <div class="header-cart__footer">
        <p class="header-cart__total">
          Итого: <strong>{{ cart.totalPrice.toLocaleString('ru-RU') }} ₽</strong>
        </p>
        <RouterLink to="/cart" class="btn header-cart__checkout" @click.stop>
          К оформлению
        </RouterLink>
      </div>
    </div>
  </div>
</template>

<style scoped>
.header-cart {
  position: relative;
}

.header-cart__link {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 25px;
  height: 25px;
  color: inherit;
}

@media (max-width: 992px) {
  .header-cart__link {
    width: 20px;
    height: 20px;
  }
}

.header-cart__badge {
  position: absolute;
  top: 4px;
  right: 4px;
  min-width: 18px;
  height: 18px;
  padding: 0 4px;
  border-radius: 999px;
  background: #2563eb;
  color: #fff;
  font-size: 0.7rem;
  line-height: 18px;
  text-align: center;
}

.header-cart__preview {
  display: none;
  position: absolute;
  top: calc(100% + 0.5rem);
  right: 0;
  width: min(360px, calc(100vw - 2rem));
  padding: 0.75rem;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
  box-shadow: 0 12px 32px rgba(15, 23, 42, 0.12);
  z-index: 120;
}

.header-cart__preview::before {
  content: '';
  position: absolute;
  top: -0.5rem;
  left: 0;
  right: 0;
  height: 0.5rem;
}

.header-cart--has-items:hover .header-cart__preview,
.header-cart__preview:hover {
  display: block;
}

.header-cart__title {
  margin: 0 0 0.75rem;
  font-weight: 600;
}

.header-cart__list {
  list-style: none;
  margin: 0;
  padding: 0;
  max-height: 280px;
  overflow-y: auto;
}

.header-cart__item {
  display: grid;
  gap: 0.5rem;
  padding: 0.65rem 0;
  border-bottom: 1px solid #f1f5f9;
}

.header-cart__item:last-child {
  border-bottom: none;
}

.header-cart__item-info {
  display: flex;
  justify-content: space-between;
  gap: 0.75rem;
}

.header-cart__item-name {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.header-cart__item-price {
  flex-shrink: 0;
  font-weight: 600;
}

.header-cart__item-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.75rem;
}

.header-cart__quantity {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  overflow: hidden;
}

.header-cart__quantity-btn {
  width: 1.75rem;
  height: 1.75rem;
  border: none;
  background: #f8fafc;
  cursor: pointer;
  font: inherit;
  line-height: 1;
}

.header-cart__quantity-btn:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

.header-cart__quantity-btn:hover:not(:disabled) {
  background: #e2e8f0;
}

.header-cart__quantity-value {
  min-width: 1.5rem;
  text-align: center;
  font-weight: 600;
}

.header-cart__remove {
  width: 1.75rem;
  height: 1.75rem;
  border: none;
  border-radius: 0.5rem;
  background: #f1f5f9;
  cursor: pointer;
  font-size: 1.1rem;
  line-height: 1;
}

.header-cart__remove:hover {
  background: #e2e8f0;
}

.header-cart__footer {
  margin-top: 0.75rem;
  padding-top: 0.75rem;
  border-top: 1px solid #f1f5f9;
}

.header-cart__total {
  margin: 0 0 0.75rem;
}

.header-cart__checkout {
  display: flex;
  width: 100%;
}

@media (max-width: 991px) {
  .header-cart__preview {
    display: none !important;
  }
}
</style>
