<script setup>
import { onMounted, ref } from 'vue'
import api from '@/api/client'
import { useSiteStore } from '@/stores/site'

const site = useSiteStore()
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
  } catch (err) {
    error.value = err.response?.data?.message || 'Не удалось сохранить настройку каталога.'
  } finally {
    savingCatalog.value = false
  }
}

async function save() {
  if (!logoFile.value && !faviconFile.value) {
    error.value = 'Выберите логотип и/или фавикон для загрузки.'
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
  } catch (err) {
    error.value = err.response?.data?.message || 'Не удалось сохранить настройки.'
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<template>
  <section>
    <h1>Настройки сайта</h1>

    <form class="card form" @submit.prevent="save">
      <h3>Логотип и фавикон</h3>
      <p class="muted">Форматы: SVG или PNG. После загрузки сразу отображаются на сайте.</p>

      <label class="field">
        <span>Логотип сайта</span>
        <input type="file" accept="image/png,image/svg+xml" @change="onLogoChange" />
        <img
          v-if="logoPreview || site.logoUrl"
          :src="logoPreview || site.logoUrl"
          alt="Логотип"
          class="preview preview--logo"
        />
      </label>

      <label class="field">
        <span>Фавикон</span>
        <input type="file" accept="image/png,image/svg+xml" @change="onFaviconChange" />
        <img
          v-if="faviconPreview || site.faviconUrl"
          :src="faviconPreview || site.faviconUrl"
          alt="Фавикон"
          class="preview preview--favicon"
        />
      </label>

      <button class="btn" type="submit" :disabled="saving">
        {{ saving ? 'Сохранение...' : 'Загрузить' }}
      </button>
    </form>

    <div class="card form">
      <h3>Каталог</h3>
      <p class="muted">
        Если включено, фильтры в каталоге применяются сразу при изменении. Если выключено — нужно
        нажимать «Применить».
      </p>

      <label class="field checkbox">
        <input
          v-model="catalogAutoApplyFilters"
          type="checkbox"
          @change="saveCatalogSetting"
        />
        <span>Мгновенное применение фильтров в каталоге</span>
      </label>

      <p v-if="savingCatalog" class="muted">Сохранение...</p>
    </div>

    <p v-if="error" class="error">{{ error }}</p>
  </section>
</template>

<style scoped>
.form {
  max-width: 480px;
  margin-bottom: 1rem;
}

.preview {
  display: block;
  margin-top: 0.5rem;
  object-fit: contain;
}

.preview--logo {
  max-width: 240px;
  max-height: 80px;
}

.preview--favicon {
  width: 48px;
  height: 48px;
}

.checkbox {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.error {
  color: #dc2626;
}
</style>
