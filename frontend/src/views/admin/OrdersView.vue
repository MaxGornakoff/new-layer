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
  <section>
    <h1>Заказы</h1>

    <div class="card">
      <AppLoader v-if="loading" />

      <template v-else>
        <table v-if="orders.length" class="table">
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
                <select :value="order.status" @change="updateStatus(order, $event.target.value)">
                  <option v-for="status in statuses" :key="status" :value="status">
                    {{ status }}
                  </option>
                </select>
              </td>
            </tr>
          </tbody>
        </table>

        <p v-else class="muted">Заказов пока нет.</p>
      </template>
    </div>
  </section>
</template>
