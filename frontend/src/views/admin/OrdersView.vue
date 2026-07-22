<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import api from '@/api/client'
import AdminIconButton from '@/components/AdminIconButton.vue'
import AppLoader from '@/components/AppLoader.vue'
import { getOrderStatusLabel, getOrderStatusClass, ORDER_STATUS_LABELS } from '@/lib/orderStatus'
import { resolveOrderDelivery } from '@/lib/orderDelivery'
import { useAdminOrdersStore } from '@/stores/adminOrders'
import { useToastStore } from '@/stores/toast'

const adminOrders = useAdminOrdersStore()
const toast = useToastStore()

const orders = ref([])
const loading = ref(true)
const deletingId = ref(null)
const expanded = reactive({})
const details = reactive({})
const detailLoading = reactive({})

const sortKey = ref('created_at')
const sortDir = ref('desc')
const statusFilter = ref('')

const statuses = Object.keys(ORDER_STATUS_LABELS)

const statusTabs = computed(() => [
  { value: '', label: 'Все' },
  ...statuses.map((status) => ({
    value: status,
    label: getOrderStatusLabel(status),
  })),
])

const sortColumns = [
  { key: 'created_at', label: 'Дата' },
  { key: 'total', label: 'Сумма' },
  { key: 'status', label: 'Статус' },
]

const listTitle = computed(() => {
  if (!statusFilter.value) return 'Все заказы'
  return getOrderStatusLabel(statusFilter.value)
})

async function load() {
  loading.value = true
  try {
    const params = {
      sort: sortKey.value,
      direction: sortDir.value,
    }
    if (statusFilter.value) {
      params.status = statusFilter.value
    }

    const { data } = await api.get('/admin/orders', { params })
    orders.value = data.data
  } finally {
    loading.value = false
  }
}

function setStatusFilter(value) {
  statusFilter.value = value
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

  if (statusFilter.value && statusFilter.value !== status) {
    orders.value = orders.value.filter((item) => item.id !== order.id)
    delete expanded[order.id]
    delete details[order.id]
  } else {
    const row = orders.value.find((item) => item.id === order.id)
    if (row) row.status = status
    if (details[order.id]) details[order.id].status = status
  }

  adminOrders.refresh()
}

async function removeOrder(order) {
  if (!confirm(`Удалить заказ ${order.number}?`)) return

  deletingId.value = order.id
  try {
    await api.delete(`/admin/orders/${order.id}`)
    orders.value = orders.value.filter((item) => item.id !== order.id)
    delete expanded[order.id]
    delete details[order.id]
    toast.success(`Заказ ${order.number} удалён`)
    adminOrders.refresh()
  } catch (err) {
    toast.error(err.response?.data?.message || 'Не удалось удалить заказ.')
  } finally {
    deletingId.value = null
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

watch([sortKey, sortDir, statusFilter], load)

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

    <div class="mb-4 flex flex-wrap gap-2">
      <button
        v-for="tab in statusTabs"
        :key="tab.value || 'all'"
        type="button"
        class="rounded-full border px-3 py-1.5 text-sm font-semibold transition-colors"
        :class="
          statusFilter === tab.value
            ? 'border-[#2563eb] bg-sky-50 text-[#2563eb]'
            : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'
        "
        @click="setStatusFilter(tab.value)"
      >
        {{ tab.label }}
      </button>
    </div>

    <div class="card admin-list-card admin-form--wide">
      <header class="admin-form__header">
        <h3>{{ listTitle }}</h3>
        <p v-if="!loading && orders.length" class="admin-field-hint">{{ orders.length }} заказов</p>
      </header>

      <AppLoader v-if="loading" />

      <template v-else>
        <div v-if="orders.length" class="overflow-hidden rounded-xl border border-border bg-white">
          <div
            class="hidden gap-3 border-b border-border bg-slate-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500 lg:grid lg:grid-cols-[2rem_1.1fr_1fr_0.9fr_0.9fr_1.2fr_2.5rem]"
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
            <span class="sr-only">Действия</span>
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
                      class="status-select inline-flex appearance-none rounded-full border-0 px-2.5 py-0.5 text-xs font-medium outline-none"
                      :class="getOrderStatusClass(order.status)"
                      :value="order.status"
                      :aria-label="`Статус заказа ${order.number}`"
                      @change="updateStatus(order, $event.target.value)"
                    >
                      <option v-for="status in statuses" :key="status" :value="status">
                        {{ getOrderStatusLabel(status) }}
                      </option>
                    </select>
                  </div>
                </button>

                <div class="mt-0.5 shrink-0" @click.stop>
                  <AdminIconButton
                    icon="trash"
                    label="Удалить заказ"
                    variant="danger"
                    :disabled="deletingId === order.id"
                    @click="removeOrder(order)"
                  />
                </div>
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

        <p v-else class="muted">
          {{ statusFilter ? 'Заказов с этим статусом пока нет.' : 'Заказов пока нет.' }}
        </p>
      </template>
    </div>
  </section>
</template>

<style scoped>
.status-select {
  min-width: 8.5rem;
  max-width: 100%;
  cursor: pointer;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12' fill='none'%3E%3Cpath d='M3 4.5L6 7.5L9 4.5' stroke='%23475569' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 0.55rem center;
  padding-right: 1.6rem;
}

.status-select:focus-visible {
  box-shadow: 0 0 0 2px rgba(59, 114, 255, 0.35);
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
