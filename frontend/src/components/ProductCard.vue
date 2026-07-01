<script setup>
import { useRouter } from 'vue-router'
import CartProductQuantityControl from '@/components/CartProductQuantityControl.vue'

const props = defineProps({
  product: {
    type: Object,
    required: true,
  },
})

const router = useRouter()

function openProduct() {
  router.push({ name: 'product', params: { slug: props.product.slug } })
}
</script>

<template>
  <article
    class="card grid cursor-pointer gap-3 transition-shadow duration-200 hover:shadow-[0_12px_32px_rgba(15,23,42,0.08)]"
    @click="openProduct"
  >
    <div class="flex items-center justify-between gap-2">
      <span class="badge">{{ product.category?.name }}</span>
      <span class="muted">{{ product.color }}</span>
    </div>

    <h3 class="m-0 text-base font-semibold leading-snug">{{ product.name }}</h3>

    <p class="muted m-0">{{ product.diameter }} мм · {{ product.weight_grams }} г</p>

    <div class="flex items-center justify-between gap-2">
      <strong>{{ Number(product.price).toLocaleString('ru-RU') }} ₽</strong>
      <span class="muted">В наличии: {{ product.stock_quantity }}</span>
    </div>

    <div @click.stop>
      <CartProductQuantityControl :product="product" compact />
    </div>
  </article>
</template>
