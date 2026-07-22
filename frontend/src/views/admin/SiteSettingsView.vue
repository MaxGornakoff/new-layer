<script setup>
import { onMounted, ref } from 'vue'
import api from '@/api/client'
import { useSiteStore } from '@/stores/site'
import { useToastStore } from '@/stores/toast'

const site = useSiteStore()
const toast = useToastStore()
const saving = ref(false)
const savingCatalog = ref(false)
const error = ref('')

const logoFile = ref(null)
const faviconFile = ref(null)
const logoPreview = ref(null)
const faviconPreview = ref(null)
const catalogAutoApplyFilters = ref(true)

function onLogoChange(event) {
  const file = event.target.files?.[0] ?? null
  logoFile.value = file
  logoPreview.value = file ? URL.createObjectURL(file) : null
}

function onFaviconChange(event) {
  const file = event.target.files?.[0] ?? null
  faviconFile.value = file
  faviconPreview.value = file ? URL.createObjectURL(file) : null
}

async function load() {
  const { data } = await api.get('/admin/site-settings')
  site.setFromAdmin(data.data)
  catalogAutoApplyFilters.value = data.data.catalog_auto_apply_filters ?? true
}

async function saveCatalogSetting() {
  savingCatalog.value = true
  error.value = ''

  try {
    const formData = new FormData()
    formData.append('catalog_auto_apply_filters', catalogAutoApplyFilters.value ? '1' : '0')

    const { data } = await api.post('/admin/site-settings', formData)
    site.setFromAdmin(data.data)
    catalogAutoApplyFilters.value = data.data.catalog_auto_apply_filters ?? true
    toast.success('Настройка каталога сохранена')
  } catch (err) {
    const message = err.response?.data?.message || 'Не удалось сохранить настройку каталога.'
    error.value = message
    toast.error(message)
  } finally {
    savingCatalog.value = false
  }
}

async function save() {
  if (!logoFile.value && !faviconFile.value) {
    const message = 'Выберите логотип и/или фавикон для загрузки.'
    error.value = message
    toast.warning(message)
    return
  }

  saving.value = true
  error.value = ''

  try {
    const formData = new FormData()
    if (logoFile.value) formData.append('logo', logoFile.value)
    if (faviconFile.value) formData.append('favicon', faviconFile.value)

    const { data } = await api.post('/admin/site-settings', formData)
    site.setFromAdmin(data.data)

    logoFile.value = null
    faviconFile.value = null
    logoPreview.value = null
    faviconPreview.value = null
    toast.success('Настройки сайта сохранены')
  } catch (err) {
    const message = err.response?.data?.message || 'Не удалось сохранить настройки.'
    error.value = message
    toast.error(message)
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<template>
  <section class="admin-page">
    <header class="admin-page__header">
      <h1>Настройки сайта</h1>
      <p class="admin-page__lead">
        Брендинг и поведение каталога на витрине.
      </p>
    </header>

    <form class="card admin-form" novalidate @submit.prevent="save">
      <header class="admin-form__header">
        <h3>Логотип и фавикон</h3>
        <p class="admin-field-hint">Форматы: SVG или PNG. После загрузки сразу отображаются на сайте.</p>
      </header>

      <fieldset class="admin-form__section admin-form__section--last">
        <legend class="admin-form__section-title">Файлы</legend>

        <label class="field">
          <span>Логотип сайта</span>
          <input type="file" accept="image/png,image/svg+xml" @change="onLogoChange" />
          <img
            v-if="logoPreview || site.logoUrl"
            :src="logoPreview || site.logoUrl"
            alt="Логотип"
            class="admin-preview preview--logo"
          />
        </label>

        <label class="field">
          <span>Фавикон</span>
          <input type="file" accept="image/png,image/svg+xml" @change="onFaviconChange" />
          <img
            v-if="faviconPreview || site.faviconUrl"
            :src="faviconPreview || site.faviconUrl"
            alt="Фавикон"
            class="admin-preview preview--favicon"
          />
        </label>
      </fieldset>

      <div class="admin-actions">
        <button class="btn" type="submit" :disabled="saving">
          {{ saving ? 'Сохранение...' : 'Загрузить' }}
        </button>
      </div>
    </form>

    <div class="card admin-form">
      <header class="admin-form__header">
        <h3>Каталог</h3>
        <p class="admin-field-hint">
          Если включено, фильтры применяются сразу при изменении. Если выключено — нужно нажимать «Применить».
        </p>
      </header>

      <fieldset class="admin-form__section admin-form__section--last">
        <legend class="admin-form__section-title">Поведение фильтров</legend>

        <label class="field admin-checkbox">
          <input
            v-model="catalogAutoApplyFilters"
            type="checkbox"
            @change="saveCatalogSetting"
          />
          <span>Мгновенное применение фильтров в каталоге</span>
        </label>

        <p v-if="savingCatalog" class="admin-field-hint">Сохранение...</p>
      </fieldset>
    </div>

    <p v-if="error" class="admin-error">{{ error }}</p>
  </section>
</template>

<style scoped>
.preview--logo {
  max-width: 240px;
  max-height: 80px;
  object-fit: contain;
}

.preview--favicon {
  width: 48px;
  height: 48px;
  object-fit: contain;
}
</style>
