<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import api from '@/api/client'
import AppLoader from '@/components/AppLoader.vue'
import { getOrderStatusLabel } from '@/lib/orderStatus'
import { resolveOrderDelivery } from '@/lib/orderDelivery'

const orders = ref([])
const loading = ref(true)
const expanded = reactive({})
const details = reactive({})
const detailLoading = reactive({})

const sortKey = ref('created_at')
const sortDir = ref('desc')

const statuses = [
  'new',
  'confirmed',
  'processing',
  'shipped',
  'completed',
  'cancelled',
]

const sortColumns = [
  { key: 'created_at', label: 'Дата' },
  { key: 'total', label: 'Сумма' },
  { key: 'status', label: 'Статус' },
]

async function load() {
  loading.value = true
  try {
    const { data } = await api.get('/admin/orders', {
      params: {
        sort: sortKey.value,
        direction: sortDir.value,
      },
    })
    orders.value = data.data
  } finally {
    loading.value = false
  }
}

function toggleSort(key) {
  if (sortKey.value === key) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortKey.value = key
    sortDir.value = key === 'created_at' ? 'desc' : 'asc'
  }
}

function sortIndicator(key) {
  if (sortKey.value !== key) return ''
  return sortDir.value === 'asc' ? ' ↑' : ' ↓'
}

function isExpanded(orderId) {
  return Boolean(expanded[orderId])
}

async function toggle(order) {
  const next = !expanded[order.id]
  expanded[order.id] = next

  if (!next || details[order.id]) return

  detailLoading[order.id] = true
  try {
    const { data } = await api.get(`/admin/orders/${order.id}`)
    details[order.id] = data.data
  } finally {
    detailLoading[order.id] = false
  }
}

async function updateStatus(order, status) {
  await api.patch(`/admin/orders/${order.id}/status`, { status })

  const row = orders.value.find((item) => item.id === order.id)
  if (row) row.status = status

  if (details[order.id]) {
    details[order.id].status = status
  }
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

watch([sortKey, sortDir], load)

onMounted(load)
</script>

<template>
  <section class="admin-page">
    <header class="admin-page__header">
      <h1>Заказы</h1>
      <p class="admin-page__lead">
        Список заказов, детали и смена статусов обработки.
      </p>
    </header>

    <div class="card admin-list-card admin-form--wide">
      <header class="admin-form__header">
        <h3>Все заказы</h3>
        <p v-if="!loading && orders.length" class="admin-field-hint">{{ orders.length }} заказов</p>
      </header>

      <AppLoader v-if="loading" />

      <template v-else>
        <div v-if="orders.length" class="overflow-hidden rounded-xl border border-border bg-white">
          <div
            class="hidden gap-3 border-b border-border bg-slate-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500 lg:grid lg:grid-cols-[2rem_1.1fr_1fr_0.9fr_0.9fr_1.2fr]"
          >
            <span aria-hidden="true" />
            <span>Номер</span>
            <span>Клиент</span>
            <button
              type="button"
              class="sort-btn"
              :class="{ 'is-active': sortKey === 'total' }"
              @click="toggleSort('total')"
            >
              Сумма{{ sortIndicator('total') }}
            </button>
            <button
              type="button"
              class="sort-btn"
              :class="{ 'is-active': sortKey === 'created_at' }"
              @click="toggleSort('created_at')"
            >
              Дата{{ sortIndicator('created_at') }}
            </button>
            <button
              type="button"
              class="sort-btn"
              :class="{ 'is-active': sortKey === 'status' }"
              @click="toggleSort('status')"
            >
              Статус{{ sortIndicator('status') }}
            </button>
          </div>

          <div class="flex flex-wrap items-center gap-2 border-b border-border px-4 py-3 lg:hidden">
            <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Сортировка:</span>
            <button
              v-for="column in sortColumns"
              :key="column.key"
              type="button"
              class="sort-chip"
              :class="{ 'is-active': sortKey === column.key }"
              @click="toggleSort(column.key)"
            >
              {{ column.label }}{{ sortIndicator(column.key) }}
            </button>
          </div>

          <ul class="m-0 list-none divide-y divide-border p-0">
            <li v-for="order in orders" :key="order.id">
              <div class="flex items-start gap-3 px-4 py-3.5">
                <button
                  type="button"
                  class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-lg border-0 bg-slate-100 text-lg leading-none text-slate-600 transition-transform duration-200"
                  :class="isExpanded(order.id) ? 'rotate-90' : ''"
                  :aria-expanded="isExpanded(order.id)"
                  :aria-label="isExpanded(order.id) ? 'Свернуть заказ' : 'Развернуть заказ'"
                  @click="toggle(order)"
                >
                  ›
                </button>

                <button
                  type="button"
                  class="grid min-w-0 flex-1 gap-3 text-left sm:grid-cols-2 lg:grid-cols-5"
                  @click="toggle(order)"
                >
                  <div>
                    <p class="m-0 text-xs text-muted lg:hidden">Номер</p>
                    <p class="m-0 font-semibold text-[#2563eb]">{{ order.number }}</p>
                  </div>

                  <div>
                    <p class="m-0 text-xs text-muted lg:hidden">Клиент</p>
                    <p class="m-0 font-medium">{{ order.customer_name }}</p>
                    <p class="m-0 mt-1 text-sm text-slate-500">{{ order.customer_phone }}</p>
                  </div>

                  <div>
                    <p class="m-0 text-xs text-muted lg:hidden">Сумма</p>
                    <p class="m-0 font-semibold">{{ formatMoney(order.total) }} ₽</p>
                  </div>

                  <div>
                    <p class="m-0 text-xs text-muted lg:hidden">Дата</p>
                    <p class="m-0 text-sm">{{ formatDate(order.created_at) }}</p>
                  </div>

                  <div @click.stop>
                    <p class="m-0 mb-1 text-xs text-muted lg:hidden">Статус</p>
                    <select
                      class="status-select"
                      :value="order.status"
                      @change="updateStatus(order, $event.target.value)"
                    >
                      <option v-for="status in statuses" :key="status" :value="status">
                        {{ getOrderStatusLabel(status) }}
                      </option>
                    </select>
                  </div>
                </button>
              </div>

              <div
                v-show="isExpanded(order.id)"
                class="border-t border-border bg-slate-50/70 px-4 py-4"
              >
                <AppLoader v-if="detailLoading[order.id]" label="Загрузка заказа..." />

                <template v-else-if="details[order.id]">
                  <dl class="mb-4 grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                      <dt class="text-muted">Email</dt>
                      <dd class="m-0">{{ details[order.id].customer_email || '—' }}</dd>
                    </div>
                    <div>
                      <dt class="text-muted">ТК</dt>
                      <dd class="m-0">{{ resolveOrderDelivery(details[order.id]).provider }}</dd>
                    </div>
                    <div>
                      <dt class="text-muted">Адрес ПВЗ</dt>
                      <dd class="m-0">{{ resolveOrderDelivery(details[order.id]).address }}</dd>
                    </div>
                    <div v-if="details[order.id].comment">
                      <dt class="text-muted">Комментарий</dt>
                      <dd class="m-0">{{ details[order.id].comment }}</dd>
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
                        <tr v-for="item in details[order.id].items" :key="item.id">
                          <td>{{ item.product_name }}</td>
                          <td class="text-muted">{{ item.product_sku || '—' }}</td>
                          <td>{{ item.quantity }}</td>
                          <td>{{ formatMoney(item.price) }} ₽</td>
                          <td>{{ formatMoney(lineTotal(item)) }} ₽</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </template>
              </div>
            </li>
          </ul>
        </div>

        <p v-else class="muted">Заказов пока нет.</p>
      </template>
    </div>
  </section>
</template>

<style scoped>
.status-select {
  min-width: 10rem;
  padding: 0.4rem 0.6rem;
  border: 1px solid #cbd5e1;
  border-radius: 0.5rem;
  background: #fff;
}

.sort-btn {
  margin: 0;
  padding: 0;
  border: 0;
  background: none;
  color: inherit;
  font: inherit;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  text-align: left;
  cursor: pointer;
}

.sort-btn:hover,
.sort-btn.is-active {
  color: #2563eb;
}

.sort-chip {
  padding: 0.35rem 0.7rem;
  border: 1px solid #e2e8f0;
  border-radius: 999px;
  background: #fff;
  color: #475569;
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
}

.sort-chip.is-active {
  border-color: #2563eb;
  background: #eff6ff;
  color: #2563eb;
}
</style>
