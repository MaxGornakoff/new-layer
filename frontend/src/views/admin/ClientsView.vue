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
  <section class="admin-page">
    <header class="admin-page__header">
      <h1>Контрагенты</h1>
      <p class="admin-page__lead">
        Клиенты магазина: контакты и адреса для заказов.
      </p>
    </header>

    <form class="card admin-form" @submit.prevent="createClient">
      <header class="admin-form__header">
        <h3>Добавить клиента</h3>
      </header>

      <fieldset class="admin-form__section admin-form__section--last">
        <legend class="admin-form__section-title">Контактные данные</legend>

        <div class="admin-form-grid">
          <label class="field">
            <span>Имя</span>
            <input v-model="form.name" required />
          </label>

          <label class="field">
            <span>Телефон</span>
            <input v-model="form.phone" />
          </label>

          <label class="field">
            <span>Email</span>
            <input v-model="form.email" type="email" />
          </label>
        </div>

        <label class="field">
          <span>Адрес</span>
          <textarea v-model="form.address" rows="2" />
        </label>

        <label class="field">
          <span>Заметки</span>
          <textarea v-model="form.notes" rows="2" />
        </label>
      </fieldset>

      <div class="admin-actions">
        <button class="btn" type="submit">Сохранить</button>
      </div>
    </form>

    <div class="card admin-list-card admin-form--wide">
      <header class="admin-form__header">
        <h3>Список клиентов</h3>
        <p v-if="!loading && clients.length" class="admin-field-hint">{{ clients.length }} записей</p>
      </header>

      <AppLoader v-if="loading" />

      <template v-else>
        <div v-if="clients.length" class="admin-table-wrap">
          <table class="table">
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
        </div>

        <p v-else class="muted">Клиентов пока нет.</p>
      </template>
    </div>
  </section>
</template>
