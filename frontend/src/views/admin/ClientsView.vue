<script setup>
import { nextTick, onMounted, reactive, ref } from 'vue'
import api from '@/api/client'
import AppLoader from '@/components/AppLoader.vue'
import AdminFormBanner from '@/components/AdminFormBanner.vue'
import { scrollAdminFormIntoView } from '@/lib/scrollAdminForm'

const clients = ref([])
const loading = ref(true)
const saving = ref(false)
const editingId = ref(null)
const formOpen = ref(false)
const formRef = ref(null)
const error = ref('')

const form = reactive({
  name: '',
  phone: '',
  email: '',
  address: '',
  notes: '',
})

function resetForm() {
  editingId.value = null
  formOpen.value = false
  Object.assign(form, {
    name: '',
    phone: '',
    email: '',
    address: '',
    notes: '',
  })
  error.value = ''
}

function openCreate() {
  resetForm()
  formOpen.value = true
  nextTick(() => scrollAdminFormIntoView(formRef.value))
}

function startEdit(client) {
  editingId.value = client.id
  form.name = client.name || ''
  form.phone = client.phone || ''
  form.email = client.email || ''
  form.address = client.address || ''
  form.notes = client.notes || ''
  error.value = ''
  formOpen.value = true
  nextTick(() => scrollAdminFormIntoView(formRef.value))
}

async function load() {
  loading.value = true
  try {
    const { data } = await api.get('/admin/clients')
    clients.value = data.data
  } finally {
    loading.value = false
  }
}

async function saveClient() {
  saving.value = true
  error.value = ''

  try {
    if (editingId.value) {
      await api.put(`/admin/clients/${editingId.value}`, form)
    } else {
      await api.post('/admin/clients', form)
    }

    resetForm()
    await load()
  } catch (err) {
    const validationErrors = err.response?.data?.errors
    if (validationErrors) {
      error.value = Object.values(validationErrors).flat().join(' ')
    } else {
      error.value = err.response?.data?.message || 'Не удалось сохранить контрагента.'
    }
  } finally {
    saving.value = false
  }
}

async function removeClient(client) {
  if (!confirm(`Удалить контрагента «${client.name}»?`)) return

  try {
    await api.delete(`/admin/clients/${client.id}`)
    if (editingId.value === client.id) resetForm()
    await load()
  } catch (err) {
    alert(err.response?.data?.message || 'Не удалось удалить контрагента.')
  }
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

    <div class="admin-toolbar">
      <p class="m-0 text-sm text-slate-500">
        {{ formOpen ? (editingId ? 'Открыта форма редактирования' : 'Открыта форма добавления') : 'Список контрагентов' }}
      </p>
      <button v-if="!formOpen" class="btn" type="button" @click="openCreate">
        Добавить контрагента
      </button>
      <button v-else class="btn secondary" type="button" @click="resetForm">
        Закрыть форму
      </button>
    </div>

    <form
      v-show="formOpen"
      ref="formRef"
      class="card admin-form"
      :class="{
        'admin-form--editing': editingId,
        'admin-form--creating': formOpen && !editingId,
      }"
      @submit.prevent="saveClient"
    >
      <header class="admin-form__header">
        <h3>{{ editingId ? 'Редактировать контрагента' : 'Добавить контрагента' }}</h3>
        <p v-if="editingId" class="admin-field-hint">ID {{ editingId }}</p>
      </header>

      <AdminFormBanner
        v-if="formOpen"
        :mode="editingId ? 'edit' : 'create'"
        :entity="editingId ? 'контрагента' : 'нового контрагента'"
        :title="form.name"
        @cancel="resetForm"
      />

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

      <p v-if="error" class="admin-error">{{ error }}</p>

      <div class="admin-actions">
        <button class="btn" type="submit" :disabled="saving">
          {{ saving ? 'Сохранение...' : editingId ? 'Обновить' : 'Добавить' }}
        </button>
        <button class="btn secondary" type="button" @click="resetForm">
          {{ editingId ? 'Отмена' : 'Закрыть' }}
        </button>
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
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="client in clients"
                :key="client.id"
                :class="{ 'admin-table-row--editing': editingId === client.id }"
              >
                <td>{{ client.name }}</td>
                <td>{{ client.phone || '—' }}</td>
                <td>{{ client.email || '—' }}</td>
                <td class="row-actions">
                  <button
                    class="btn"
                    :class="editingId === client.id ? '' : 'secondary'"
                    type="button"
                    @click="startEdit(client)"
                  >
                    {{ editingId === client.id ? 'Редактируется' : 'Изменить' }}
                  </button>
                  <button class="btn secondary" type="button" @click="removeClient(client)">
                    Удалить
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <p v-else class="muted">Клиентов пока нет.</p>
      </template>
    </div>
  </section>
</template>

<style scoped>
.row-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  justify-content: flex-end;
}
</style>
