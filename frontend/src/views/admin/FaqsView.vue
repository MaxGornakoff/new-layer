<script setup>
import { nextTick, onMounted, reactive, ref } from 'vue'
import api from '@/api/client'
import AdminFormBanner from '@/components/AdminFormBanner.vue'
import AdminIconButton from '@/components/AdminIconButton.vue'
import AppLoader from '@/components/AppLoader.vue'
import { validateForm } from '@/lib/formValidation'
import { scrollAdminFormIntoView } from '@/lib/scrollAdminForm'
import { useToastStore } from '@/stores/toast'

const toast = useToastStore()
const items = ref([])
const loading = ref(true)
const saving = ref(false)
const editingId = ref(null)
const formOpen = ref(false)
const formRef = ref(null)
const error = ref('')

const form = reactive({
  question: '',
  answer: '',
  sort_order: 0,
  is_active: true,
})

function resetForm() {
  editingId.value = null
  formOpen.value = false
  Object.assign(form, {
    question: '',
    answer: '',
    sort_order: 0,
    is_active: true,
  })
  error.value = ''
}

function openCreate() {
  resetForm()
  formOpen.value = true
  nextTick(() => scrollAdminFormIntoView(formRef.value))
}

function startEdit(item) {
  editingId.value = item.id
  form.question = item.question
  form.answer = item.answer
  form.sort_order = item.sort_order ?? 0
  form.is_active = item.is_active !== false
  error.value = ''
  formOpen.value = true
  nextTick(() => scrollAdminFormIntoView(formRef.value))
}

async function load() {
  loading.value = true
  try {
    const { data } = await api.get('/admin/faq-items')
    items.value = data.data ?? []
  } finally {
    loading.value = false
  }
}

async function save(event) {
  if (!validateForm(event?.target)) return

  saving.value = true
  error.value = ''

  try {
    const payload = {
      question: form.question.trim(),
      answer: form.answer.trim(),
      sort_order: form.sort_order ?? 0,
      is_active: form.is_active,
    }

    if (editingId.value) {
      await api.put(`/admin/faq-items/${editingId.value}`, payload)
    } else {
      await api.post('/admin/faq-items', payload)
    }

    resetForm()
    await load()
    toast.success(editingId.value ? 'Вопрос обновлён' : 'Вопрос добавлен')
  } catch (err) {
    const validationErrors = err.response?.data?.errors
    const message = validationErrors
      ? Object.values(validationErrors).flat().join(' ')
      : err.response?.data?.message || 'Не удалось сохранить вопрос.'
    error.value = message
    toast.error(message)
  } finally {
    saving.value = false
  }
}

async function removeItem(item) {
  if (!confirm(`Удалить вопрос «${item.question}»?`)) return

  try {
    await api.delete(`/admin/faq-items/${item.id}`)
    if (editingId.value === item.id) resetForm()
    await load()
  } catch (err) {
    toast.error(err.response?.data?.message || 'Не удалось удалить вопрос.')
  }
}

async function toggleActive(item) {
  const nextActive = item.is_active === false

  try {
    await api.put(`/admin/faq-items/${item.id}`, {
      question: item.question,
      answer: item.answer,
      sort_order: item.sort_order ?? 0,
      is_active: nextActive,
    })
    if (editingId.value === item.id) {
      form.is_active = nextActive
    }
    await load()
    toast.success(nextActive ? 'Вопрос включён' : 'Вопрос выключен')
  } catch (err) {
    toast.error(err.response?.data?.message || 'Не удалось изменить статус.')
  }
}

onMounted(load)
</script>

<template>
  <section class="admin-page">
    <header class="admin-page__header">
      <h1>FAQ</h1>
      <p class="admin-page__lead">
        Вопросы и ответы на странице
        <a href="/faq" target="_blank" rel="noopener">/faq</a>.
      </p>
    </header>

    <div class="admin-toolbar">
      <p class="m-0 text-sm text-slate-500">
        {{ formOpen ? (editingId ? 'Редактирование вопроса' : 'Добавление вопроса') : 'Список вопросов' }}
      </p>
      <button v-if="!formOpen" class="btn" type="button" @click="openCreate">
        Добавить вопрос
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
      novalidate
      @submit.prevent="save"
    >
      <header class="admin-form__header">
        <h3>{{ editingId ? 'Редактировать вопрос' : 'Добавить вопрос' }}</h3>
      </header>

      <AdminFormBanner
        v-if="formOpen"
        :mode="editingId ? 'edit' : 'create'"
        :entity="editingId ? 'вопрос FAQ' : 'нового вопроса FAQ'"
        :title="form.question"
        @cancel="resetForm"
      />

      <fieldset class="admin-form__section admin-form__section--last">
        <legend class="admin-form__section-title">Параметры</legend>

        <label class="field">
          <span>Вопрос</span>
          <input v-model="form.question" type="text" required maxlength="500" />
        </label>

        <label class="field">
          <span>Ответ</span>
          <textarea v-model="form.answer" rows="5" required maxlength="10000" />
        </label>

        <label class="field">
          <span>Порядок</span>
          <input v-model.number="form.sort_order" type="number" min="0" />
        </label>

        <label class="field admin-checkbox">
          <input v-model="form.is_active" type="checkbox" />
          <span>Активен (показывать на сайте)</span>
        </label>
      </fieldset>

      <p v-if="error" class="admin-error">{{ error }}</p>

      <div class="admin-actions">
        <button class="btn" type="submit" :disabled="saving">
          {{ saving ? 'Сохранение...' : editingId ? 'Обновить' : 'Сохранить' }}
        </button>
        <button class="btn secondary" type="button" @click="resetForm">
          {{ editingId ? 'Отмена' : 'Закрыть' }}
        </button>
      </div>
    </form>

    <div class="card admin-list-card admin-form--wide">
      <header class="admin-form__header">
        <h3>Текущие вопросы</h3>
        <p v-if="!loading && items.length" class="admin-field-hint">{{ items.length }} шт.</p>
      </header>

      <AppLoader v-if="loading" />

      <ul v-else-if="items.length" class="faq-admin-list">
        <li
          v-for="item in items"
          :key="item.id"
          class="faq-admin-item admin-list-item"
          :class="{
            'admin-list-item--editing': editingId === item.id,
            'faq-admin-item--inactive': item.is_active === false,
          }"
        >
          <div class="faq-admin-item__body">
            <strong>
              {{ item.question }}
              <span v-if="item.is_active === false" class="faq-admin-badge">выкл</span>
            </strong>
            <p class="muted faq-admin-item__answer">{{ item.answer }}</p>
            <span class="muted faq-admin-item__meta">порядок: {{ item.sort_order }}</span>
          </div>
          <div class="faq-admin-item__actions">
            <button
              type="button"
              class="faq-admin-toggle"
              @click="toggleActive(item)"
            >
              {{ item.is_active === false ? 'Вкл' : 'Выкл' }}
            </button>
            <AdminIconButton
              icon="pencil"
              :label="editingId === item.id ? 'Редактируется' : 'Изменить'"
              :active="editingId === item.id"
              @click="startEdit(item)"
            />
            <AdminIconButton
              icon="trash"
              label="Удалить"
              variant="danger"
              @click="removeItem(item)"
            />
          </div>
        </li>
      </ul>

      <p v-else class="muted">Вопросов пока нет.</p>
    </div>
  </section>
</template>

<style scoped>
.faq-admin-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: 0;
}

.faq-admin-item {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  align-items: flex-start;
}

.faq-admin-item--inactive {
  opacity: 0.55;
}

.faq-admin-item__body {
  min-width: 0;
  flex: 1;
}

.faq-admin-item__answer {
  margin: 0.4rem 0 0;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.faq-admin-item__meta {
  display: block;
  margin-top: 0.35rem;
  font-size: 0.8rem;
}

.faq-admin-badge {
  display: inline-block;
  margin-left: 0.4rem;
  padding: 0.1rem 0.45rem;
  border-radius: 999px;
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
  background: #fee2e2;
  color: #b91c1c;
  vertical-align: middle;
}

.faq-admin-item__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  align-items: center;
}

.faq-admin-toggle {
  min-width: 3.25rem;
  height: 2.25rem;
  padding: 0 0.65rem;
  border: 0;
  border-radius: 0.5rem;
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
  background: #ecfdf5;
  color: #047857;
}

.faq-admin-item--inactive .faq-admin-toggle {
  background: #fef3c7;
  color: #b45309;
}

@media (max-width: 720px) {
  .faq-admin-item {
    flex-direction: column;
  }
}
</style>
