<script setup>
import { nextTick, onMounted, reactive, ref } from 'vue'
import api from '@/api/client'
import AppLoader from '@/components/AppLoader.vue'
import AdminFormBanner from '@/components/AdminFormBanner.vue'
import { scrollAdminFormIntoView } from '@/lib/scrollAdminForm'

const categories = ref([])
const editingId = ref(null)
const formOpen = ref(false)
const formRef = ref(null)
const saving = ref(false)
const loading = ref(true)
const error = ref('')

const emptyAdvantages = () => [
  { text: '', iconFile: null, iconPreview: null, existingIconUrl: null },
  { text: '', iconFile: null, iconPreview: null, existingIconUrl: null },
  { text: '', iconFile: null, iconPreview: null, existingIconUrl: null },
]

const form = reactive({
  name: '',
  home_title: '',
  home_bg_color: '#ff8f6b',
  home_bg_color_end: '#ff6b3d',
  sort_order: 0,
  imageFile: null,
  imagePreview: null,
  existingImageUrl: null,
  advantages: emptyAdvantages(),
})

function resetForm() {
  editingId.value = null
  formOpen.value = false
  form.name = ''
  form.home_title = ''
  form.home_bg_color = '#ff8f6b'
  form.home_bg_color_end = '#ff6b3d'
  form.sort_order = 0
  form.imageFile = null
  form.imagePreview = null
  form.existingImageUrl = null
  form.advantages = emptyAdvantages()
  error.value = ''
}

function openCreate() {
  resetForm()
  formOpen.value = true
  nextTick(() => scrollAdminFormIntoView(formRef.value))
}

async function load() {
  loading.value = true
  try {
    const { data } = await api.get('/admin/categories')
    categories.value = data.data
  } finally {
    loading.value = false
  }
}

function onImageChange(event) {
  const file = event.target.files?.[0] ?? null
  form.imageFile = file
  form.imagePreview = file ? URL.createObjectURL(file) : null
}

function onAdvantageIconChange(index, event) {
  const file = event.target.files?.[0] ?? null
  form.advantages[index].iconFile = file
  form.advantages[index].iconPreview = file ? URL.createObjectURL(file) : null
}

function startEdit(category) {
  editingId.value = category.id
  form.name = category.name
  form.home_title = category.home_title || ''
  form.home_bg_color = category.home_bg_color || '#ff8f6b'
  form.home_bg_color_end = category.home_bg_color_end || '#ff6b3d'
  form.sort_order = category.sort_order
  form.imageFile = null
  form.imagePreview = null
  form.existingImageUrl = category.image_url
  form.advantages = [0, 1, 2].map((index) => ({
    text: category.advantages?.[index]?.text ?? '',
    iconFile: null,
    iconPreview: null,
    existingIconUrl: category.advantages?.[index]?.icon_url ?? null,
  }))
  error.value = ''
  formOpen.value = true
  nextTick(() => scrollAdminFormIntoView(formRef.value))
}

function buildFormData() {
  const formData = new FormData()
  formData.append('name', form.name)
  formData.append('home_title', form.home_title)
  formData.append('home_bg_color', form.home_bg_color)
  formData.append('home_bg_color_end', form.home_bg_color_end)
  formData.append('sort_order', String(form.sort_order))

  if (form.imageFile) {
    formData.append('image', form.imageFile)
  }

  form.advantages.forEach((advantage, index) => {
    formData.append(`advantages[${index}][text]`, advantage.text)
    if (advantage.iconFile) {
      formData.append(`advantages[${index}][icon]`, advantage.iconFile)
    }
  })

  return formData
}

async function save() {
  saving.value = true
  error.value = ''

  try {
    const formData = buildFormData()

    if (editingId.value) {
      formData.append('_method', 'PUT')
      await api.post(`/admin/categories/${editingId.value}`, formData)
    } else {
      await api.post('/admin/categories', formData)
    }

    resetForm()
    await load()
  } catch (err) {
    error.value = err.response?.data?.message || 'Не удалось сохранить категорию.'
  } finally {
    saving.value = false
  }
}

async function removeCategory(category) {
  if (!confirm(`Удалить категорию «${category.name}»?`)) return

  try {
    await api.delete(`/admin/categories/${category.id}`)
    if (editingId.value === category.id) {
      resetForm()
    }
    await load()
  } catch (err) {
    alert(err.response?.data?.message || 'Не удалось удалить категорию.')
  }
}

onMounted(load)
</script>

<template>
  <section class="admin-page">
    <header class="admin-page__header">
      <h1>Категории</h1>
      <p class="admin-page__lead">
        Название, изображение, настройки блока на главной и 3 преимущества (иконка SVG/PNG + текст).
      </p>
    </header>

    <div class="admin-toolbar">
      <p class="m-0 text-sm text-slate-500">
        {{ formOpen ? (editingId ? 'Открыта форма редактирования' : 'Открыта форма добавления') : 'Список категорий' }}
      </p>
      <button v-if="!formOpen" class="btn" type="button" @click="openCreate">
        Добавить категорию
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
      @submit.prevent="save"
    >
      <header class="admin-form__header">
        <h3>{{ editingId ? 'Редактировать категорию' : 'Добавить категорию' }}</h3>
        <p v-if="editingId" class="admin-field-hint">ID {{ editingId }}</p>
      </header>

      <AdminFormBanner
        v-if="formOpen"
        :mode="editingId ? 'edit' : 'create'"
        :entity="editingId ? 'категорию' : 'новой категории'"
        :title="form.name"
        @cancel="resetForm"
      />

      <fieldset class="admin-form__section">
        <legend class="admin-form__section-title">Основное</legend>

      <label class="field">
        <span>Название</span>
        <input v-model="form.name" required />
      </label>

      <label class="field">
        <span>Название на главной</span>
        <input v-model="form.home_title" placeholder="Второе название для блока категории" />
        <span class="admin-field-hint">Если пусто — на главной будет основное название.</span>
      </label>
      </fieldset>

      <fieldset class="admin-form__section">
        <legend class="admin-form__section-title">Оформление на главной</legend>

      <div class="color-fields">
        <label class="field">
          <span>Цвет фона (начало градиента)</span>
          <div class="color-input">
            <input v-model="form.home_bg_color" type="color" />
            <input v-model="form.home_bg_color" type="text" pattern="^#[0-9A-Fa-f]{6}$" />
          </div>
        </label>

        <label class="field">
          <span>Цвет фона (конец градиента)</span>
          <div class="color-input">
            <input v-model="form.home_bg_color_end" type="color" />
            <input v-model="form.home_bg_color_end" type="text" pattern="^#[0-9A-Fa-f]{6}$" />
          </div>
        </label>
      </div>

      <label class="field">
        <span>Порядок сортировки</span>
        <input v-model.number="form.sort_order" type="number" min="0" />
      </label>

      <label class="field">
        <span>Изображение категории</span>
        <input type="file" accept="image/png,image/jpeg,image/webp,image/svg+xml" @change="onImageChange" />
        <img
          v-if="form.imagePreview || form.existingImageUrl"
          :src="form.imagePreview || form.existingImageUrl"
          alt=""
          class="admin-preview preview"
        />
      </label>
      </fieldset>

      <fieldset class="admin-form__section admin-form__section--last">
        <legend class="admin-form__section-title">Преимущества (3 шт.)</legend>

      <div v-for="(advantage, index) in form.advantages" :key="index" class="advantage">
          <h4>Преимущество {{ index + 1 }}</h4>

          <label class="field">
            <span>Текст</span>
            <input v-model="advantage.text" required />
          </label>

          <label class="field">
            <span>Иконка (SVG или PNG)</span>
            <input
              type="file"
              accept="image/png,image/svg+xml"
              @change="onAdvantageIconChange(index, $event)"
            />
            <img
              v-if="advantage.iconPreview || advantage.existingIconUrl"
              :src="advantage.iconPreview || advantage.existingIconUrl"
              alt=""
              class="admin-preview preview preview--icon"
            />
          </label>
        </div>
      </fieldset>

      <p v-if="error" class="admin-error">{{ error }}</p>

      <div class="admin-actions">
        <button class="btn" type="submit" :disabled="saving">
          {{ saving ? 'Сохранение...' : editingId ? 'Сохранить' : 'Добавить' }}
        </button>
        <button v-if="formOpen" class="btn secondary" type="button" @click="resetForm">
          {{ editingId ? 'Отмена' : 'Закрыть' }}
        </button>
      </div>
    </form>

    <div class="card admin-list-card admin-form--wide">
      <header class="admin-form__header">
        <h3>Список категорий</h3>
        <p v-if="!loading && categories.length" class="admin-field-hint">{{ categories.length }} категорий</p>
      </header>

      <AppLoader v-if="loading" />

      <template v-else>
        <div v-if="categories.length" class="admin-table-wrap">
        <table class="table">
        <thead>
          <tr>
            <th>Изображение</th>
            <th>Название</th>
            <th>На главной</th>
            <th>Slug</th>
            <th>Порядок</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="category in categories"
            :key="category.id"
            :class="{ 'admin-table-row--editing': editingId === category.id }"
          >
            <td>
              <img v-if="category.image_url" :src="category.image_url" alt="" class="thumb" />
              <span v-else class="muted">—</span>
            </td>
            <td>{{ category.name }}</td>
            <td>
              <span v-if="category.home_title">{{ category.home_title }}</span>
              <span v-else class="muted">—</span>
              <div v-if="category.home_bg_color || category.home_bg_color_end" class="color-preview">
                <span
                  v-if="category.home_bg_color"
                  class="color-preview__swatch"
                  :style="{ backgroundColor: category.home_bg_color }"
                  :title="category.home_bg_color"
                />
                <span
                  v-if="category.home_bg_color_end"
                  class="color-preview__swatch"
                  :style="{ backgroundColor: category.home_bg_color_end }"
                  :title="category.home_bg_color_end"
                />
              </div>
            </td>
            <td>{{ category.slug }}</td>
            <td>{{ category.sort_order }}</td>
            <td class="row-actions">
              <button
                class="btn"
                :class="editingId === category.id ? '' : 'secondary'"
                type="button"
                @click="startEdit(category)"
              >
                {{ editingId === category.id ? 'Редактируется' : 'Изменить' }}
              </button>
              <button class="btn secondary" type="button" @click="removeCategory(category)">Удалить</button>
            </td>
          </tr>
        </tbody>
        </table>
        </div>

        <p v-else class="muted">Категорий пока нет.</p>
      </template>
    </div>
  </section>
</template>

<style scoped>
.advantage + .advantage {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #f1f5f9;
}

.advantage h4 {
  margin: 0 0 0.5rem;
  font-size: 0.9375rem;
  font-weight: 600;
}

.preview {
  max-width: 200px;
  max-height: 120px;
  object-fit: contain;
}

.preview--icon {
  max-width: 64px;
  max-height: 64px;
}

.thumb {
  width: 48px;
  height: 48px;
  object-fit: cover;
  border-radius: 0.35rem;
}

.row-actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.color-fields {
  display: grid;
  gap: 1rem;
}

.color-input {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.color-input input[type='color'] {
  width: 3rem;
  height: 2.5rem;
  padding: 0.15rem;
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  cursor: pointer;
}

.color-input input[type='text'] {
  flex: 1;
}

.color-preview {
  display: flex;
  gap: 0.35rem;
  margin-top: 0.35rem;
}

.color-preview__swatch {
  width: 1rem;
  height: 1rem;
  border-radius: 999px;
  border: 1px solid rgba(15, 23, 42, 0.12);
}
</style>
