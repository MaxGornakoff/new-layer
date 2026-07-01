<script setup>
import { reactive } from 'vue'
import { RouterLink } from 'vue-router'
import { getOrderStatusLabel } from '@/lib/orderStatus'

defineProps({
  orders: {
    type: Array,
    required: true,
  },
})

const expanded = reactive({})

function toggle(orderId) {
  expanded[orderId] = !expanded[orderId]
}

function isExpanded(orderId) {
  return Boolean(expanded[orderId])
}

function formatMoney(value) {
  return Number(value).toLocaleString('ru-RU')
}

function formatDate(value) {
  return new Date(value).toLocaleString('ru-RU', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function lineTotal(item) {
  return Number(item.price) * item.quantity
}

function productRoute(item) {
  const slug = item.product?.slug
  if (!slug) return null

  return { name: 'product', params: { slug } }
}

function statusClass(status) {
  const classes = {
    new: 'bg-sky-100 text-sky-800',
    confirmed: 'bg-indigo-100 text-indigo-800',
    processing: 'bg-amber-100 text-amber-900',
    shipped: 'bg-violet-100 text-violet-800',
    completed: 'bg-emerald-100 text-emerald-800',
    cancelled: 'bg-slate-200 text-slate-600',
  }

  return classes[status] ?? 'bg-slate-100 text-slate-700'
}
</script>

<template>
  <ul class="grid gap-3">
    <li
      v-for="order in orders"
      :key="order.id"
      class="overflow-hidden rounded-xl border border-border bg-white"
    >
      <button
        type="button"
        class="flex w-full items-start gap-3 px-4 py-3 text-left transition-colors hover:bg-slate-50"
        :aria-expanded="isExpanded(order.id)"
        @click="toggle(order.id)"
      >
        <span
          class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-lg leading-none text-slate-600 transition-transform duration-200"
          :class="isExpanded(order.id) ? 'rotate-90' : ''"
          aria-hidden="true"
        >
          ›
        </span>

        <div class="grid min-w-0 flex-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
          <div>
            <p class="m-0 text-xs text-muted">Номер</p>
            <p class="m-0 font-semibold">{{ order.number }}</p>
          </div>

          <div>
            <p class="m-0 text-xs text-muted">Статус</p>
            <span
              class="mt-1 inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
              :class="statusClass(order.status)"
            >
              {{ getOrderStatusLabel(order.status) }}
            </span>
          </div>

          <div>
            <p class="m-0 text-xs text-muted">Сумма</p>
            <p class="m-0 font-semibold">{{ formatMoney(order.total) }} ₽</p>
          </div>

          <div>
            <p class="m-0 text-xs text-muted">Дата</p>
            <p class="m-0">{{ formatDate(order.created_at) }}</p>
          </div>
        </div>
      </button>

      <div
        v-show="isExpanded(order.id)"
        class="border-t border-border bg-slate-50/70 px-4 py-4"
      >
        <dl class="mb-4 grid gap-2 text-sm sm:grid-cols-2">
          <div>
            <dt class="text-muted">Адрес доставки</dt>
            <dd class="m-0">{{ order.delivery_address }}</dd>
          </div>
          <div v-if="order.comment">
            <dt class="text-muted">Комментарий</dt>
            <dd class="m-0">{{ order.comment }}</dd>
          </div>
        </dl>

        <div class="overflow-x-auto rounded-lg border border-border bg-white">
          <table class="table min-w-full">
            <thead>
              <tr>
                <th>Товар</th>
                <th>Артикул</th>
                <th>Кол-во</th>
                <th>Цена</th>
                <th>Сумма</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in order.items" :key="item.id">
                <td>
                  <RouterLink
                    v-if="productRoute(item)"
                    :to="productRoute(item)"
                    class="font-medium text-brand hover:underline"
                  >
                    {{ item.product_name }}
                  </RouterLink>
                  <span v-else>{{ item.product_name }}</span>
                </td>
                <td class="text-muted">{{ item.product_sku || '—' }}</td>
                <td>{{ item.quantity }}</td>
                <td>{{ formatMoney(item.price) }} ₽</td>
                <td>{{ formatMoney(lineTotal(item)) }} ₽</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </li>
  </ul>
</template>
