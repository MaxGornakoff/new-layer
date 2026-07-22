<script setup>
import { reactive } from 'vue'
import { RouterLink } from 'vue-router'
import { getOrderStatusLabel, getOrderStatusClass } from '@/lib/orderStatus'
import { resolveOrderDelivery } from '@/lib/orderDelivery'

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
</script>

<template>
  <div class="overflow-hidden rounded-xl border border-border bg-white">
    <div
      class="hidden gap-3 border-b border-border bg-slate-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500 lg:grid lg:grid-cols-[2rem_1fr_1fr_1fr_1fr]"
    >
      <span aria-hidden="true" />
      <span>Номер</span>
      <span>Статус</span>
      <span>Сумма</span>
      <span>Дата</span>
    </div>

    <ul class="m-0 list-none divide-y divide-border p-0">
      <li v-for="order in orders" :key="order.id">
        <button
          type="button"
          class="flex w-full items-start gap-3 px-4 py-3.5 text-left transition-colors hover:bg-slate-50"
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
              <p class="m-0 text-xs text-muted lg:hidden">Номер</p>
              <p class="m-0 font-semibold">{{ order.number }}</p>
            </div>

            <div>
              <p class="m-0 text-xs text-muted lg:hidden">Статус</p>
              <span
                class="mt-1 inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium lg:mt-0"
                :class="getOrderStatusClass(order.status)"
              >
                {{ getOrderStatusLabel(order.status) }}
              </span>
            </div>

            <div>
              <p class="m-0 text-xs text-muted lg:hidden">Сумма</p>
              <p class="m-0 font-semibold">{{ formatMoney(order.total) }} ₽</p>
            </div>

            <div>
              <p class="m-0 text-xs text-muted lg:hidden">Дата</p>
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
              <dt class="text-muted">ТК</dt>
              <dd class="m-0">{{ resolveOrderDelivery(order).provider }}</dd>
            </div>
            <div>
              <dt class="text-muted">Адрес ПВЗ</dt>
              <dd class="m-0">{{ resolveOrderDelivery(order).address }}</dd>
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
  </div>
</template>
