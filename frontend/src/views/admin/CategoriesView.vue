<script setup>
import { onMounted, reactive, ref } from 'vue'
import api from '@/api/client'
import AppLoader from '@/components/AppLoader.vue'

const categories = ref([])
const editingId = ref(null)
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
  sort_order: 0,
  imageFile: null,
  imagePreview: null,
  existingImageUrl: null,
  advantages: emptyAdvantages(),
})

function resetForm() {
  editingId.value = null
  form.name = ''
  form.sort_order = 0
  form.imageFile = null
  form.imagePreview = null
  form.existingImageUrl = null
  form.advantages = emptyAdvantages()
  error.value = ''
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
}

function buildFormData() {
  const formData = new FormData()
  formData.append('name', form.name)
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
  <section>
    <h1>Категории</h1>
    <p class="muted">
      Название, изображение и 3 преимущества (иконка SVG/PNG + текст). Файлы хранятся на сервере.
    </p>

    <form class="card form" @submit.prevent="save">
      <h3>{{ editingId ? 'Редактировать категорию' : 'Добавить категорию' }}</h3>

      <label class="field">
        <span>Название</span>
        <input v-model="form.name" required />
      </label>

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
          class="preview"
        />
      </label>

      <fieldset class="advantages">
        <legend>Преимущества (3 шт.)</legend>

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
              class="preview preview--icon"
            />
          </label>
        </div>
      </fieldset>

      <p v-if="error" class="error">{{ error }}</p>

      <div class="actions">
        <button class="btn" type="submit" :disabled="saving">
          {{ saving ? 'Сохранение...' : editingId ? 'Сохранить' : 'Добавить' }}
        </button>
        <button v-if="editingId" class="btn secondary" type="button" @click="resetForm">
          Отмена
        </button>
      </div>
    </form>

    <div class="card">
      <AppLoader v-if="loading" />

      <template v-else>
        <table v-if="categories.length" class="table">
        <thead>
          <tr>
            <th>Изображение</th>
            <th>Название</th>
            <th>Slug</th>
            <th>Порядок</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="category in categories" :key="category.id">
            <td>
              <img v-if="category.image_url" :src="category.image_url" alt="" class="thumb" />
              <span v-else class="muted">—</span>
            </td>
            <td>{{ category.name }}</td>
            <td>{{ category.slug }}</td>
            <td>{{ category.sort_order }}</td>
            <td class="row-actions">
              <button class="btn secondary" type="button" @click="startEdit(category)">Изменить</button>
              <button class="btn secondary" type="button" @click="removeCategory(category)">Удалить</button>
            </td>
          </tr>
        </tbody>
        </table>

        <p v-else class="muted">Категорий пока нет.</p>
      </template>
    </div>
  </section>
</template>

<style scoped>
.form {
  margin-bottom: 1rem;
  max-width: 640px;
}

.advantages {
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
  padding: 1rem;
  margin: 0 0 1rem;
}

.advantage + .advantage {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #f1f5f9;
}

.advantage h4 {
  margin: 0 0 0.5rem;
}

.preview {
  display: block;
  margin-top: 0.5rem;
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

.actions {
  display: flex;
  gap: 0.5rem;
}

.error {
  color: #dc2626;
}
</style>
