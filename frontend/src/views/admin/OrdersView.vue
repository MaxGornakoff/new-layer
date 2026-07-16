<script setup>
import { onMounted, ref } from 'vue'
import api from '@/api/client'
import AppLoader from '@/components/AppLoader.vue'

const orders = ref([])
const loading = ref(true)

const statuses = [
  'new',
  'confirmed',
  'processing',
  'shipped',
  'completed',
  'cancelled',
]

const statusLabels = {
  new: 'Новый',
  confirmed: 'Подтверждён',
  processing: 'В обработке',
  shipped: 'Отправлен',
  completed: 'Завершён',
  cancelled: 'Отменён',
}

async function load() {
  loading.value = true
  try {
    const { data } = await api.get('/admin/orders')
    orders.value = data.data
  } finally {
    loading.value = false
  }
}

async function updateStatus(order, status) {
  await api.patch(`/admin/orders/${order.id}/status`, { status })
  await load()
}

onMounted(load)
</script>

<template>
  <section class="admin-page">
    <header class="admin-page__header">
      <h1>Заказы</h1>
      <p class="admin-page__lead">
        Список заказов и смена статусов обработки.
      </p>
    </header>

    <div class="card admin-list-card admin-form--wide">
      <header class="admin-form__header">
        <h3>Все заказы</h3>
        <p v-if="!loading && orders.length" class="admin-field-hint">{{ orders.length }} заказов</p>
      </header>

      <AppLoader v-if="loading" />

      <template v-else>
        <div v-if="orders.length" class="admin-table-wrap">
          <table class="table">
            <thead>
              <tr>
                <th>Номер</th>
                <th>Клиент</th>
                <th>Сумма</th>
                <th>Статус</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="order in orders" :key="order.id">
                <td>{{ order.number }}</td>
                <td>{{ order.customer_name }}</td>
                <td>{{ Number(order.total).toLocaleString('ru-RU') }} ₽</td>
                <td>
                  <select
                    class="status-select"
                    :value="order.status"
                    @change="updateStatus(order, $event.target.value)"
                  >
                    <option v-for="status in statuses" :key="status" :value="status">
                      {{ statusLabels[status] || status }}
                    </option>
                  </select>
                </td>
              </tr>
            </tbody>
          </table>
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
</style>
