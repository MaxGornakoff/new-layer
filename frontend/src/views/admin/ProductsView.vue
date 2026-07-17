<script setup>
import { computed, nextTick, onMounted, reactive, ref, watch } from 'vue'
import api from '@/api/client'
import AppLoader from '@/components/AppLoader.vue'
import AdminFormBanner from '@/components/AdminFormBanner.vue'
import { formatMoney, resolveCompareAtPrice } from '@/lib/productPrice'
import { scrollAdminFormIntoView } from '@/lib/scrollAdminForm'

const products = ref([])
const categories = ref([])
const stockUpdating = ref({})
const stockDrafts = ref({})
const loading = ref(true)
const saving = ref(false)
const editingId = ref(null)
const formOpen = ref(false)
const formRef = ref(null)
const error = ref('')

const form = reactive({
  category_id: '',
  name: '',
  sku: '',
  description: '',
  price: 0,
  compare_at_price: null,
  compare_at_markup_percent: null,
  color: '',
  diameter: 1.75,
  weight_grams: 1000,
  stock_quantity: 0,
  is_active: true,
})

const gallery = ref([]) // { id, type: 'existing'|'new', path?, url, file? }[]
const imagesInputRef = ref(null)

const videoFile = ref(null)
const videoPreview = ref(null)
const existingVideoUrl = ref(null)
const remove_video = ref(false)

let galleryIdCounter = 0

function createGalleryId() {
  galleryIdCounter += 1
  return `gallery-${galleryIdCounter}`
}

function resetGallery() {
  for (const item of gallery.value) {
    if (item.type === 'new' && item.url) {
      URL.revokeObjectURL(item.url)
    }
  }
  gallery.value = []
  if (imagesInputRef.value) {
    imagesInputRef.value.value = ''
  }
}

function resetMedia() {
  resetGallery()
  videoFile.value = null
  if (videoPreview.value) URL.revokeObjectURL(videoPreview.value)
  videoPreview.value = null
  existingVideoUrl.value = null
  remove_video.value = false
}

function resetForm() {
  editingId.value = null
  formOpen.value = false

  Object.assign(form, {
    category_id: form.category_id, // keep selection if possible
    name: '',
    sku: '',
    description: '',
    price: 0,
    compare_at_price: null,
    compare_at_markup_percent: null,
    color: '',
    diameter: 1.75,
    weight_grams: 1000,
    stock_quantity: 0,
    is_active: true,
  })

  resetMedia()

  error.value = ''
}

function openCreate() {
  resetForm()
  formOpen.value = true
  nextTick(() => scrollAdminFormIntoView(formRef.value))
}

function syncStockDrafts() {
  const drafts = {}
  for (const product of products.value) {
    drafts[product.id] = product.stock_quantity
  }
  stockDrafts.value = drafts
}

async function load() {
  loading.value = true
  try {
    const [productsResponse, categoriesResponse] = await Promise.all([
      api.get('/admin/products'),
      api.get('/categories'),
    ])

    products.value = productsResponse.data.data
    categories.value = categoriesResponse.data.data

    syncStockDrafts()
    if (!form.category_id && categories.value.length) {
      form.category_id = categories.value[0].id
    }
  } finally {
    loading.value = false
  }
}

function onImagesChange(event) {
  const files = Array.from(event.target.files ?? [])
  for (const file of files) {
    gallery.value.push({
      id: createGalleryId(),
      type: 'new',
      url: URL.createObjectURL(file),
      file,
    })
  }
  if (imagesInputRef.value) {
    imagesInputRef.value.value = ''
  }
}

function removeGalleryItem(index) {
  const item = gallery.value[index]
  if (!item) return
  if (item.type === 'new' && item.url) {
    URL.revokeObjectURL(item.url)
  }
  gallery.value.splice(index, 1)
}

function moveGalleryItem(index, direction) {
  const targetIndex = index + direction
  if (targetIndex < 0 || targetIndex >= gallery.value.length) return
  const items = [...gallery.value]
  const [moved] = items.splice(index, 1)
  items.splice(targetIndex, 0, moved)
  gallery.value = items
}

function loadGalleryFromProduct(product) {
  resetGallery()
  gallery.value = (product.images ?? []).map((img) => ({
    id: createGalleryId(),
    type: 'existing',
    path: img.path,
    url: img.url,
  }))
}

function onVideoChange(event) {
  const file = event.target.files?.[0] ?? null
  videoFile.value = file
  remove_video.value = false

  if (videoPreview.value) URL.revokeObjectURL(videoPreview.value)
  videoPreview.value = file ? URL.createObjectURL(file) : null
}

function startEdit(product) {
  editingId.value = product.id

  Object.assign(form, {
    category_id: product.category_id,
    name: product.name,
    sku: product.sku,
    description: product.description || '',
    price: Number(product.price),
    compare_at_price: product.compare_at_price != null ? Number(product.compare_at_price) : null,
    compare_at_markup_percent:
      product.compare_at_markup_percent != null ? Number(product.compare_at_markup_percent) : null,
    color: product.color,
    diameter: Number(product.diameter),
    weight_grams: product.weight_grams,
    stock_quantity: product.stock_quantity,
    is_active: product.is_active,
  })

  resetMedia()

  loadGalleryFromProduct(product)
  videoFile.value = null
  videoPreview.value = null
  existingVideoUrl.value = product.video_url
  remove_video.value = false

  error.value = ''
  formOpen.value = true
  nextTick(() => scrollAdminFormIntoView(formRef.value))
}

function buildFormData() {
  const formData = new FormData()

  formData.append('category_id', String(form.category_id))
  formData.append('name', form.name)
  formData.append('sku', form.sku)
  formData.append('description', form.description || '')
  formData.append('price', String(form.price))
  formData.append(
    'compare_at_price',
    form.compare_at_price != null && form.compare_at_price !== '' ? String(form.compare_at_price) : '',
  )
  formData.append(
    'compare_at_markup_percent',
    form.compare_at_markup_percent != null && form.compare_at_markup_percent !== ''
      ? String(form.compare_at_markup_percent)
      : '',
  )
  formData.append('color', form.color)
  formData.append('diameter', String(form.diameter))
  formData.append('weight_grams', String(form.weight_grams))
  formData.append('stock_quantity', String(form.stock_quantity))
  formData.append('is_active', form.is_active ? '1' : '0')

  const existingPaths = gallery.value
    .filter((item) => item.type === 'existing')
    .map((item) => item.path)

  const originalPaths = editingId.value
    ? (products.value.find((p) => p.id === editingId.value)?.images ?? []).map((img) => img.path)
    : []

  for (const path of originalPaths) {
    if (!existingPaths.includes(path)) {
      formData.append('remove_images[]', path)
    }
  }

  let newIndex = 0
  for (const item of gallery.value) {
    if (editingId.value && item.type === 'existing') {
      formData.append('gallery_sequence[]', `existing:${item.path}`)
    } else if (item.type === 'new' && item.file) {
      if (editingId.value) {
        formData.append('gallery_sequence[]', `new:${newIndex}`)
      }
      formData.append('images[]', item.file)
      newIndex += 1
    }
  }

  // video
  if (videoFile.value) formData.append('video', videoFile.value)
  if (editingId.value && remove_video.value) formData.append('remove_video', '1')

  if (editingId.value) {
    formData.append('_method', 'PUT')
  }

  return formData
}

async function saveProduct() {
  saving.value = true
  error.value = ''

  try {
    const formData = buildFormData()

    if (editingId.value) {
      await api.post(`/admin/products/${editingId.value}`, formData)
    } else {
      await api.post('/admin/products', formData)
    }

    resetForm()
    await load()
  } catch (err) {
    const validationErrors = err.response?.data?.errors
    if (validationErrors) {
      error.value = Object.values(validationErrors).flat().join(' ')
    } else {
      error.value = err.response?.data?.message || 'Не удалось сохранить товар.'
    }
  } finally {
    saving.value = false
  }
}

async function removeProduct(product) {
  if (!confirm(`Удалить товар «${product.name}»?`)) return

  try {
    await api.delete(`/admin/products/${product.id}`)

    if (editingId.value === product.id) resetForm()
    await load()
  } catch (err) {
    alert(err.response?.data?.message || 'Не удалось удалить товар.')
  }
}

async function applyStockChange(product, delta) {
  if (!delta) return

  stockUpdating.value[product.id] = true

  try {
    const { data } = await api.post(`/admin/products/${product.id}/stock`, {
      quantity: delta,
    })
    product.stock_quantity = data.data.stock_quantity
    stockDrafts.value[product.id] = data.data.stock_quantity
  } catch (err) {
    stockDrafts.value[product.id] = product.stock_quantity
    alert(err.response?.data?.message || 'Не удалось обновить остаток.')
  } finally {
    stockUpdating.value[product.id] = false
  }
}

function commitStockInput(product) {
  const nextQuantity = Math.max(0, Number.parseInt(String(stockDrafts.value[product.id]), 10) || 0)
  if (nextQuantity === product.stock_quantity) return

  stockDrafts.value[product.id] = nextQuantity
  const delta = nextQuantity - product.stock_quantity
  applyStockChange(product, delta)
}

onMounted(load)

const comparePreview = computed(() =>
  resolveCompareAtPrice({
    price: form.price,
    compare_at_price: form.compare_at_price,
    compare_at_markup_percent: form.compare_at_markup_percent,
  }),
)

watch(
  () => editingId.value,
  (id) => {
    if (!id) resetMedia()
  },
)
</script>

<template>
  <section class="admin-page">
    <header class="admin-page__header">
      <h1>Товары</h1>
      <p class="admin-page__lead muted">
        Добавляйте и редактируйте позиции каталога, управляйте ценами, медиа и остатками.
      </p>
    </header>

    <div class="admin-toolbar">
      <p class="m-0 text-sm text-slate-500">
        {{ formOpen ? (editingId ? 'Открыта форма редактирования' : 'Открыта форма добавления') : 'Список товаров каталога' }}
      </p>
      <button
        v-if="!formOpen"
        class="btn"
        type="button"
        @click="openCreate"
      >
        Добавить товар
      </button>
      <button
        v-else
        class="btn secondary"
        type="button"
        @click="resetForm"
      >
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
      @submit.prevent="saveProduct"
    >
      <header class="admin-form__header">
        <h3>{{ editingId ? 'Редактировать товар' : 'Добавить товар' }}</h3>
        <p v-if="editingId" class="admin-field-hint">ID {{ editingId }}</p>
      </header>

      <AdminFormBanner
        v-if="formOpen"
        :mode="editingId ? 'edit' : 'create'"
        :entity="editingId ? 'товар' : 'нового товара'"
        :title="form.name"
        @cancel="resetForm"
      />

      <fieldset class="admin-form__section">
        <legend class="admin-form__section-title">Основное</legend>

        <label class="field">
          <span>Категория</span>
          <select v-model="form.category_id" required>
            <option v-for="category in categories" :key="category.id" :value="category.id">
              {{ category.name }}
            </option>
          </select>
        </label>

        <label class="field">
          <span>Название</span>
          <input v-model="form.name" required />
        </label>

        <label class="field">
          <span>Артикул</span>
          <input v-model="form.sku" required />
        </label>

        <label class="field">
          <span>Описание</span>
          <textarea v-model="form.description" rows="3" />
        </label>
      </fieldset>

      <fieldset class="admin-form__section">
        <legend class="admin-form__section-title">Цены</legend>

        <div class="admin-form-grid admin-form-grid--3">
          <label class="field">
            <span>Цена продажи, ₽</span>
            <input v-model.number="form.price" type="number" min="0" step="0.01" required />
          </label>

          <label class="field">
            <span>Старая цена, ₽</span>
            <input
              v-model.number="form.compare_at_price"
              type="number"
              min="0"
              step="0.01"
              placeholder="Необязательно"
            />
          </label>

          <label class="field">
            <span>Наценка, %</span>
            <input
              v-model.number="form.compare_at_markup_percent"
              type="number"
              min="0"
              step="0.01"
              placeholder="Необязательно"
            />
          </label>
        </div>

        <p class="admin-field-hint">
          Перечёркнутая цена на витрине: сначала берётся точная «старая цена», если она не задана —
          считается от наценки. Значение должно быть выше цены продажи.
        </p>

        <p v-if="comparePreview" class="price-preview">
          <span>На витрине:</span>
          <strong>{{ formatMoney(form.price) }} ₽</strong>
          <span class="price-preview__compare">{{ formatMoney(comparePreview) }} ₽</span>
        </p>
      </fieldset>

      <fieldset class="admin-form__section">
        <legend class="admin-form__section-title">Характеристики</legend>

        <div class="admin-form-grid">
          <label class="field">
            <span>Цвет</span>
            <input v-model="form.color" required />
          </label>

          <label class="field">
            <span>Диаметр, мм</span>
            <input v-model.number="form.diameter" type="number" min="0" step="0.01" required />
          </label>

          <label class="field">
            <span>Вес катушки, г</span>
            <input v-model.number="form.weight_grams" type="number" min="1" required />
          </label>

          <label class="field">
            <span>Остаток</span>
            <input v-model.number="form.stock_quantity" type="number" min="0" />
          </label>
        </div>
      </fieldset>

      <fieldset class="admin-form__section">
        <legend class="admin-form__section-title">Медиа</legend>

        <div class="field">
          <span>Фото товара</span>
          <p class="admin-field-hint">
            Можно выбрать несколько файлов сразу или добавлять партиями. Первое фото — главное в каталоге.
          </p>
          <input
            ref="imagesInputRef"
            type="file"
            accept="image/jpeg,image/png,image/webp"
            multiple
            @change="onImagesChange"
          />

          <div v-if="gallery.length" class="gallery-grid">
            <div
              v-for="(item, index) in gallery"
              :key="item.id"
              class="gallery-item"
              :class="{ 'gallery-item--main': index === 0 }"
            >
              <img :src="item.url" :alt="`Фото ${index + 1}`" />
              <span v-if="index === 0" class="gallery-item__badge">Главное</span>
              <span v-else-if="item.type === 'new'" class="gallery-item__badge gallery-item__badge--new">Новое</span>

              <div class="gallery-item__actions">
                <button
                  type="button"
                  class="gallery-item__btn"
                  :disabled="index === 0"
                  aria-label="Сдвинуть влево"
                  @click="moveGalleryItem(index, -1)"
                >
                  ←
                </button>
                <button
                  type="button"
                  class="gallery-item__btn"
                  :disabled="index === gallery.length - 1"
                  aria-label="Сдвинуть вправо"
                  @click="moveGalleryItem(index, 1)"
                >
                  →
                </button>
                <button
                  type="button"
                  class="gallery-item__btn gallery-item__btn--danger"
                  aria-label="Удалить фото"
                  @click="removeGalleryItem(index)"
                >
                  ×
                </button>
              </div>
            </div>
          </div>

          <p v-else class="admin-field-hint">Фото пока не добавлены.</p>
        </div>

        <label class="field">
          <span>Видео</span>
          <input
            type="file"
            accept="video/mp4,video/webm,video/quicktime"
            @change="onVideoChange"
          />
          <video
            v-if="videoPreview || (existingVideoUrl && !remove_video)"
            :src="videoPreview || existingVideoUrl"
            class="preview preview--video"
            controls
            muted
          />
          <label v-if="editingId && existingVideoUrl" class="field admin-checkbox">
            <input v-model="remove_video" type="checkbox" />
            <span>Удалить текущее видео</span>
          </label>
          <span class="admin-field-hint">MP4, WebM или MOV, до 50 МБ.</span>
        </label>
      </fieldset>

      <fieldset class="admin-form__section admin-form__section--last">
        <legend class="admin-form__section-title">Публикация</legend>

        <label class="field admin-checkbox">
          <input v-model="form.is_active" type="checkbox" />
          <span>Активен (виден в каталоге)</span>
        </label>
      </fieldset>

      <p v-if="error" class="admin-error">{{ error }}</p>

      <div class="admin-actions">
        <button class="btn" type="submit" :disabled="saving">
          {{ saving ? 'Сохранение...' : editingId ? 'Обновить' : 'Добавить' }}
        </button>
        <button v-if="formOpen" class="btn secondary" type="button" @click="resetForm">
          {{ editingId ? 'Отмена' : 'Закрыть' }}
        </button>
      </div>
    </form>

    <div class="card admin-list-card">
      <header class="admin-form__header">
        <h3>Текущие товары</h3>
        <p v-if="!loading && products.length" class="admin-field-hint">{{ products.length }} позиций</p>
      </header>

      <AppLoader v-if="loading" />

      <div v-else-if="products.length" class="products-list">
        <article
          v-for="product in products"
          :key="product.id"
          class="product-item admin-list-item"
          :class="{ 'admin-list-item--editing': editingId === product.id }"
        >
          <div class="product-item__media">
            <img
              v-if="product.images?.length"
              :src="product.images[0].url"
              :alt="product.name"
            />
            <div v-else class="product-item__thumb--empty">Нет фото</div>
          </div>

          <div class="product-item__info">
            <strong>{{ product.name }}</strong>
            <p class="muted m-0">{{ product.category?.name }} · {{ product.sku }}</p>
            <p class="muted m-0 product-item__meta">
              {{ product.color }} · {{ Number(product.price).toLocaleString('ru-RU') }} ₽
              <span v-if="product.compare_at_price_display">
                · <span class="product-item__compare">{{ Number(product.compare_at_price_display).toLocaleString('ru-RU') }} ₽</span>
              </span>
              <span v-if="product.images?.length"> · {{ product.images.length }} фото</span>
              <span v-if="product.video_url"> · есть видео</span>
              <span v-if="!product.is_active"> · скрыт</span>
            </p>

            <label class="stock-inline">
              <span class="muted">Остаток</span>
              <input
                v-model.number="stockDrafts[product.id]"
                type="number"
                min="0"
                step="1"
                class="stock-control__input"
                :disabled="stockUpdating[product.id]"
                @change="commitStockInput(product)"
                @keydown.enter.prevent="commitStockInput(product)"
              />
            </label>
          </div>

          <div class="product-item__actions">
            <button
              class="btn"
              :class="editingId === product.id ? '' : 'secondary'"
              type="button"
              @click="startEdit(product)"
            >
              {{ editingId === product.id ? 'Редактируется' : 'Изменить' }}
            </button>
            <button class="btn secondary" type="button" @click="removeProduct(product)">Удалить</button>
          </div>
        </article>
      </div>

      <p v-else class="muted">Товаров пока нет.</p>
    </div>
  </section>
</template>

<style scoped>
.price-preview {
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  gap: 0.5rem;
  margin: 0.75rem 0 0;
  padding: 0.75rem 0.9rem;
  border-radius: 0.75rem;
  background: #f8fafc;
  color: #334155;
  font-size: 0.875rem;
}

.price-preview strong {
  color: #0f172a;
}

.price-preview__compare {
  color: #94a3b8;
  text-decoration: line-through;
}

.preview {
  display: block;
  margin-top: 0.5rem;
  border-radius: 0.75rem;
}

.preview--video {
  width: 100%;
  max-width: 420px;
  max-height: 220px;
}

.gallery-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  gap: 0.75rem;
  margin-top: 0.75rem;
}

.gallery-item {
  position: relative;
  border-radius: 0.75rem;
  overflow: hidden;
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
}

.gallery-item--main {
  border-color: #2563eb;
  box-shadow: 0 0 0 1px #2563eb;
}

.gallery-item img {
  width: 100%;
  height: 110px;
  object-fit: cover;
  display: block;
}

.gallery-item__badge {
  position: absolute;
  top: 0.35rem;
  left: 0.35rem;
  padding: 0.15rem 0.45rem;
  border-radius: 999px;
  background: rgba(37, 99, 235, 0.92);
  color: #fff;
  font-size: 0.6875rem;
  font-weight: 600;
}

.gallery-item__badge--new {
  background: rgba(15, 23, 42, 0.75);
}

.gallery-item__actions {
  display: flex;
  gap: 0.25rem;
  padding: 0.35rem;
  background: linear-gradient(to top, rgba(15, 23, 42, 0.55), transparent);
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
}

.gallery-item__btn {
  flex: 1;
  border: 0;
  border-radius: 0.4rem;
  background: rgba(255, 255, 255, 0.92);
  color: #0f172a;
  font-size: 0.75rem;
  line-height: 1;
  padding: 0.3rem 0;
  cursor: pointer;
}

.gallery-item__btn:disabled {
  opacity: 0.35;
  cursor: not-allowed;
}

.gallery-item__btn--danger {
  color: #dc2626;
  font-weight: 700;
}

.products-list {
  display: grid;
  gap: 0;
}

.product-item {
  display: grid;
  grid-template-columns: 88px 1fr auto;
  gap: 1rem;
  align-items: start;
}

.product-item:first-of-type {
  padding-top: 0;
  border-top: none;
}

.product-item__media img {
  width: 88px;
  height: 88px;
  object-fit: cover;
  border-radius: 0.75rem;
  display: block;
}

.product-item__thumb--empty {
  width: 88px;
  height: 88px;
  border-radius: 0.75rem;
  background: #f8fafc;
  color: #94a3b8;
  font-size: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
}

.product-item__info {
  display: grid;
  gap: 0.35rem;
}

.product-item__compare {
  text-decoration: line-through;
}

.stock-inline {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 0.25rem;
}

.stock-control__input {
  width: 5rem;
  padding: 0.35rem 0.5rem;
  border: 1px solid #cbd5e1;
  border-radius: 0.5rem;
  text-align: center;
}

.product-item__actions {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.admin-list-card{
  max-width: none;
}

@media (max-width: 720px) {
  .product-item {
    grid-template-columns: 1fr;
  }

  .product-item__media img,
  .product-item__thumb--empty {
    width: 100%;
    height: 160px;
  }

  .product-item__actions {
    flex-direction: row;
    flex-wrap: wrap;
  }
}
</style>

