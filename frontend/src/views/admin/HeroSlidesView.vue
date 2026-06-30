<script setup>
import { onMounted, reactive, ref } from 'vue'
import api from '@/api/client'
import AppLoader from '@/components/AppLoader.vue'
import { stripHtml } from '@/lib/stripHtml'

const slides = ref([])
const loading = ref(true)
const saving = ref(false)
const savingSettings = ref(false)
const editingId = ref(null)
const error = ref('')
const settingsError = ref('')

const sliderSettings = reactive({
  autoplay: true,
  autoplayIntervalSeconds: 6,
})

const form = reactive({
  title: '',
  subtitle: '',
  button_text: '',
  button_url: '',
  sort_order: 0,
  is_active: true,
  imageFile: null,
  imagePreview: null,
  existingImageUrl: null,
})

function resetForm() {
  editingId.value = null
  form.title = ''
  form.subtitle = ''
  form.button_text = ''
  form.button_url = ''
  form.sort_order = 0
  form.is_active = true
  form.imageFile = null
  form.imagePreview = null
  form.existingImageUrl = null
  error.value = ''
}

function onImageChange(event) {
  const file = event.target.files?.[0] ?? null
  form.imageFile = file
  form.imagePreview = file ? URL.createObjectURL(file) : null
}

async function load() {
  loading.value = true
  try {
    const [slidesResponse, settingsResponse] = await Promise.all([
      api.get('/admin/hero-slides'),
      api.get('/admin/site-settings'),
    ])
    slides.value = slidesResponse.data.data
    sliderSettings.autoplay = settingsResponse.data.data.hero_slider_autoplay ?? true
    sliderSettings.autoplayIntervalSeconds =
      settingsResponse.data.data.hero_slider_autoplay_interval ?? 6
  } finally {
    loading.value = false
  }
}

function startEdit(slide) {
  editingId.value = slide.id
  form.title = slide.title
  form.subtitle = slide.subtitle || ''
  form.button_text = slide.button_text || ''
  form.button_url = slide.button_url || ''
  form.sort_order = slide.sort_order
  form.is_active = slide.is_active
  form.imageFile = null
  form.imagePreview = null
  form.existingImageUrl = slide.image_url
  error.value = ''
}

function buildFormData() {
  const formData = new FormData()
  formData.append('title', form.title)
  formData.append('subtitle', form.subtitle)
  formData.append('button_text', form.button_text)
  formData.append('button_url', form.button_url)
  formData.append('sort_order', String(form.sort_order))
  formData.append('is_active', form.is_active ? '1' : '0')

  if (form.imageFile) {
    formData.append('image', form.imageFile)
  }

  return formData
}

async function saveSliderSettings() {
  savingSettings.value = true
  settingsError.value = ''

  try {
    const formData = new FormData()
    formData.append('hero_slider_autoplay', sliderSettings.autoplay ? '1' : '0')
    formData.append(
      'hero_slider_autoplay_interval',
      String(sliderSettings.autoplayIntervalSeconds),
    )

    const { data } = await api.post('/admin/site-settings', formData)
    sliderSettings.autoplay = data.data.hero_slider_autoplay ?? true
    sliderSettings.autoplayIntervalSeconds = data.data.hero_slider_autoplay_interval ?? 6
  } catch (err) {
    settingsError.value =
      err.response?.data?.message || 'Не удалось сохранить настройки слайдера.'
  } finally {
    savingSettings.value = false
  }
}

async function save() {
  if (!editingId.value && !form.imageFile) {
    error.value = 'Выберите изображение для слайда.'
    return
  }

  saving.value = true
  error.value = ''

  try {
    const formData = buildFormData()

    if (editingId.value) {
      formData.append('_method', 'PUT')
      await api.post(`/admin/hero-slides/${editingId.value}`, formData)
    } else {
      await api.post('/admin/hero-slides', formData)
    }

    resetForm()
    await load()
  } catch (err) {
    error.value = err.response?.data?.message || 'Не удалось сохранить слайд.'
  } finally {
    saving.value = false
  }
}

async function removeSlide(slide) {
  if (!confirm(`Удалить слайд «${stripHtml(slide.title)}»?`)) return

  await api.delete(`/admin/hero-slides/${slide.id}`)

  if (editingId.value === slide.id) {
    resetForm()
  }

  await load()
}

onMounted(load)
</script>

<template>
  <section>
    <h1>Слайдер на главной</h1>
    <p class="muted">Слайды отображаются под шапкой на главной странице.</p>

    <form class="card form settings-form" @submit.prevent="saveSliderSettings">
      <h3>Настройки слайдера</h3>

      <label class="field checkbox">
        <input v-model="sliderSettings.autoplay" type="checkbox" />
        <span>Автоматическое пролистывание</span>
      </label>

      <label class="field">
        <span>Интервал, сек</span>
        <input
          v-model.number="sliderSettings.autoplayIntervalSeconds"
          type="number"
          min="2"
          max="120"
          :disabled="!sliderSettings.autoplay"
        />
      </label>

      <p v-if="settingsError" class="error">{{ settingsError }}</p>

      <div class="actions">
        <button class="btn" type="submit" :disabled="savingSettings">
          {{ savingSettings ? 'Сохранение...' : 'Сохранить настройки' }}
        </button>
      </div>
    </form>

    <form class="card form" @submit.prevent="save">
      <h3>{{ editingId ? 'Редактировать слайд' : 'Добавить слайд' }}</h3>

      <label class="field">
        <span>Изображение</span>
        <input type="file" accept="image/jpeg,image/png,image/webp" @change="onImageChange" />
        <img
          v-if="form.imagePreview || form.existingImageUrl"
          :src="form.imagePreview || form.existingImageUrl"
          alt=""
          class="preview"
        />
      </label>

      <label class="field">
        <span>Основной текст</span>
        <textarea v-model="form.title" rows="3" required />
        <span class="field-hint">Поддерживается HTML: &lt;br&gt;, &lt;span&gt;, &lt;strong&gt; и др.</span>
      </label>

      <label class="field">
        <span>Вспомогательный текст</span>
        <textarea v-model="form.subtitle" rows="3" />
        <span class="field-hint">Поддерживается HTML.</span>
      </label>

      <label class="field">
        <span>Текст кнопки</span>
        <input v-model="form.button_text" placeholder="Подробнее" />
      </label>

      <label class="field">
        <span>Ссылка кнопки</span>
        <input v-model="form.button_url" placeholder="/catalog или https://..." />
      </label>

      <label class="field">
        <span>Порядок</span>
        <input v-model.number="form.sort_order" type="number" min="0" />
      </label>

      <label class="field checkbox">
        <input v-model="form.is_active" type="checkbox" />
        <span>Активен</span>
      </label>

      <p v-if="error" class="error">{{ error }}</p>

      <div class="actions">
        <button class="btn" type="submit" :disabled="saving">
          {{ saving ? 'Сохранение...' : editingId ? 'Обновить' : 'Добавить' }}
        </button>
        <button v-if="editingId" class="btn secondary" type="button" @click="resetForm">
          Отмена
        </button>
      </div>
    </form>

    <div class="card">
      <h3>Текущие слайды</h3>

      <AppLoader v-if="loading" />

      <div v-else-if="slides.length" class="slides-list">
        <article v-for="slide in slides" :key="slide.id" class="slide-item">
          <img
            :src="slide.image_url"
            :alt="stripHtml(slide.title)"
            class="slide-item__thumb"
          />

          <div class="slide-item__info">
            <strong class="slide-item__title" v-html="slide.title" />
            <p v-if="slide.subtitle" class="muted slide-item__subtitle" v-html="slide.subtitle" />
            <p v-if="slide.button_url" class="muted">Кнопка: {{ slide.button_url }}</p>
            <p class="muted">
              Порядок: {{ slide.sort_order }}
              <span v-if="!slide.is_active"> · скрыт</span>
            </p>
          </div>

          <div class="slide-item__actions">
            <button class="btn secondary" type="button" @click="startEdit(slide)">Изменить</button>
            <button class="btn secondary" type="button" @click="removeSlide(slide)">Удалить</button>
          </div>
        </article>
      </div>

      <p v-else class="muted">Слайдов пока нет.</p>
    </div>
  </section>
</template>

<style scoped>
.form {
  margin-bottom: 1rem;
  max-width: 560px;
}

.settings-form {
  margin-bottom: 1.5rem;
}

.preview {
  display: block;
  margin-top: 0.5rem;
  width: 100%;
  max-width: 360px;
  max-height: 180px;
  object-fit: cover;
  border-radius: 0.75rem;
}

.checkbox {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.field-hint {
  font-size: 0.8125rem;
  color: #64748b;
}

.actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.error {
  color: #dc2626;
}

.slides-list {
  display: grid;
  gap: 1rem;
}

.slide-item {
  display: grid;
  grid-template-columns: 120px 1fr auto;
  gap: 1rem;
  align-items: start;
  padding-top: 1rem;
  border-top: 1px solid #f1f5f9;
}

.slide-item:first-child {
  padding-top: 0;
  border-top: none;
}

.slide-item__thumb {
  width: 120px;
  height: 72px;
  object-fit: cover;
  border-radius: 0.5rem;
}

.slide-item__info {
  display: grid;
  gap: 0.25rem;
}

.slide-item__info p {
  margin: 0;
}

.slide-item__actions {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

@media (max-width: 720px) {
  .slide-item {
    grid-template-columns: 1fr;
  }

  .slide-item__thumb {
    width: 100%;
    height: 160px;
  }

  .slide-item__actions {
    flex-direction: row;
    flex-wrap: wrap;
  }
}
</style>
