<script setup>
import { onMounted, ref } from 'vue'
import api from '@/api/client'
import { useAuthStore } from '@/stores/auth'
import AppLoader from '@/components/AppLoader.vue'

const auth = useAuthStore()
const orders = ref([])
const loading = ref(true)

async function loadOrders() {
  loading.value = true
  try {
    const { data } = await api.get('/orders')
    orders.value = data.data
  } finally {
    loading.value = false
  }
}

onMounted(loadOrders)
</script>

<template>
  <section>
    <h1>Личный кабинет</h1>
    <div class="card profile">
      <p><strong>{{ auth.user?.name }}</strong></p>
      <p class="muted">{{ auth.user?.email }}</p>
      <p v-if="auth.user?.phone" class="muted">{{ auth.user.phone }}</p>
      <button class="btn secondary" @click="auth.logout()">Выйти</button>
    </div>

    <h2 id="orders">Мои заказы</h2>

    <AppLoader v-if="loading" />

    <div v-else-if="orders.length" class="card">
      <table class="table">
        <thead>
          <tr>
            <th>Номер</th>
            <th>Статус</th>
            <th>Сумма</th>
            <th>Дата</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="order in orders" :key="order.id">
            <td>{{ order.number }}</td>
            <td>{{ order.status }}</td>
            <td>{{ Number(order.total).toLocaleString('ru-RU') }} ₽</td>
            <td>{{ new Date(order.created_at).toLocaleString('ru-RU') }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <p v-else class="muted">Заказов пока нет.</p>
  </section>
</template>

<style scoped>
.profile {
  margin-bottom: 1.5rem;
  display: grid;
  gap: 0.35rem;
  max-width: 420px;
}
</style>
