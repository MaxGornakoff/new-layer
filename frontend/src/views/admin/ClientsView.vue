<script setup>
import { onMounted, reactive, ref } from 'vue'
import api from '@/api/client'
import AppLoader from '@/components/AppLoader.vue'

const clients = ref([])
const loading = ref(true)
const form = reactive({
  name: '',
  phone: '',
  email: '',
  address: '',
  notes: '',
})

async function load() {
  loading.value = true
  try {
    const { data } = await api.get('/admin/clients')
    clients.value = data.data
  } finally {
    loading.value = false
  }
}

async function createClient() {
  await api.post('/admin/clients', form)
  Object.assign(form, { name: '', phone: '', email: '', address: '', notes: '' })
  await load()
}

onMounted(load)
</script>

<template>
  <section>
    <h1>Контрагенты (клиенты)</h1>

    <form class="card form" @submit.prevent="createClient">
      <h3>Добавить клиента</h3>
      <label class="field"><span>Имя</span><input v-model="form.name" required /></label>
      <label class="field"><span>Телефон</span><input v-model="form.phone" /></label>
      <label class="field"><span>Email</span><input v-model="form.email" type="email" /></label>
      <label class="field"><span>Адрес</span><textarea v-model="form.address" rows="2" /></label>
      <button class="btn" type="submit">Сохранить</button>
    </form>

    <div class="card">
      <AppLoader v-if="loading" />

      <template v-else>
        <table v-if="clients.length" class="table">
        <thead>
          <tr>
            <th>Имя</th>
            <th>Телефон</th>
            <th>Email</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="client in clients" :key="client.id">
            <td>{{ client.name }}</td>
            <td>{{ client.phone || '—' }}</td>
            <td>{{ client.email || '—' }}</td>
          </tr>
        </tbody>
        </table>

        <p v-else class="muted">Клиентов пока нет.</p>
      </template>
    </div>
  </section>
</template>

<style scoped>
.form {
  margin-bottom: 1rem;
  max-width: 480px;
}
</style>
