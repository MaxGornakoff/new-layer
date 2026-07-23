<script setup>
import { onMounted, reactive, ref } from 'vue'
import api from '@/api/client'
import AdminIconButton from '@/components/AdminIconButton.vue'
import AppIcon from '@/components/AppIcon.vue'
import { useSiteStore } from '@/stores/site'
import { useToastStore } from '@/stores/toast'

const site = useSiteStore()
const toast = useToastStore()
const saving = ref(false)
const savingCatalog = ref(false)
const savingContacts = ref(false)
const error = ref('')

const logoFile = ref(null)
const faviconFile = ref(null)
const logoPreview = ref(null)
const faviconPreview = ref(null)
const catalogAutoApplyFilters = ref(true)

const contactsForm = reactive({
  contact_phone: '',
  contact_email_business: '',
  contact_email_support: '',
  messengers: [],
})

const messengerIconOptions = [
  { value: 'telegram', label: 'telegram' },
  { value: 'max', label: 'max' },
  { value: 'vk', label: 'vk' },
]

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

function applyContactsFromSettings(settings) {
  contactsForm.contact_phone = settings.contact_phone || ''
  contactsForm.contact_email_business = settings.contact_email_business || ''
  contactsForm.contact_email_support = settings.contact_email_support || ''
  contactsForm.messengers = Array.isArray(settings.contact_messengers)
    ? settings.contact_messengers.map((item) => ({
        label: item.label || '',
        icon: item.icon || 'telegram',
        url: item.url || '',
      }))
    : []
}

function addMessenger() {
  contactsForm.messengers.push({
    label: '',
    icon: 'telegram',
    url: '',
  })
}

function removeMessenger(index) {
  contactsForm.messengers.splice(index, 1)
}

async function load() {
  const { data } = await api.get('/admin/site-settings')
  site.setFromAdmin(data.data)
  catalogAutoApplyFilters.value = data.data.catalog_auto_apply_filters ?? true
  applyContactsFromSettings(data.data)
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

async function saveContacts() {
  savingContacts.value = true
  error.value = ''

  try {
    const formData = new FormData()
    formData.append('contact_phone', contactsForm.contact_phone.trim())
    formData.append('contact_email_business', contactsForm.contact_email_business.trim())
    formData.append('contact_email_support', contactsForm.contact_email_support.trim())
    formData.append(
      'contact_messengers',
      JSON.stringify(
        contactsForm.messengers.map((item) => ({
          label: item.label.trim(),
          icon: item.icon.trim(),
          url: item.url.trim(),
        })),
      ),
    )

    const { data } = await api.post('/admin/site-settings', formData)
    site.setFromAdmin(data.data)
    applyContactsFromSettings(data.data)
    toast.success('Контакты футера сохранены')
  } catch (err) {
    const validationErrors = err.response?.data?.errors
    const message = validationErrors
      ? Object.values(validationErrors).flat().join(' ')
      : err.response?.data?.message || 'Не удалось сохранить контакты.'
    error.value = message
    toast.error(message)
  } finally {
    savingContacts.value = false
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
        Брендинг, контакты футера и поведение каталога на витрине.
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

    <form class="card admin-form" novalidate @submit.prevent="saveContacts">
      <header class="admin-form__header">
        <h3>Контакты в футере</h3>
        <p class="admin-field-hint">
          Телефон, почта и мессенджеры. Иконка — id из sprite без префикса
          <code>icon-</code> (например <code>telegram</code>, <code>max</code>, <code>vk</code>).
        </p>
      </header>

      <fieldset class="admin-form__section">
        <legend class="admin-form__section-title">Телефон и email</legend>

        <label class="field">
          <span>Телефон</span>
          <input v-model="contactsForm.contact_phone" type="text" placeholder="+7 (999) 123-45-67" />
        </label>

        <label class="field">
          <span>Email для юрлиц</span>
          <input
            v-model="contactsForm.contact_email_business"
            type="email"
            placeholder="opt@site.ru"
          />
        </label>

        <label class="field">
          <span>Email поддержки</span>
          <input
            v-model="contactsForm.contact_email_support"
            type="email"
            placeholder="help@site.ru"
          />
        </label>
      </fieldset>

      <fieldset class="admin-form__section admin-form__section--last">
        <legend class="admin-form__section-title">Мессенджеры</legend>

        <div v-if="contactsForm.messengers.length" class="messenger-list">
          <div
            v-for="(messenger, index) in contactsForm.messengers"
            :key="index"
            class="messenger-row"
          >
            <label class="field">
              <span>Название</span>
              <input v-model="messenger.label" type="text" placeholder="Telegram" required />
            </label>

            <label class="field">
              <span>Иконка (id)</span>
              <input
                v-model="messenger.icon"
                type="text"
                list="messenger-icon-options"
                placeholder="telegram"
                required
                pattern="[a-z0-9_-]+"
              />
            </label>

            <label class="field">
              <span>Ссылка</span>
              <input
                v-model="messenger.url"
                type="url"
                placeholder="https://t.me/..."
                required
              />
            </label>

            <div class="messenger-row__preview">
              <span class="messenger-row__icon" :title="messenger.icon || 'icon'">
                <AppIcon v-if="messenger.icon" :name="messenger.icon" :size="18" />
              </span>
              <AdminIconButton
                icon="trash"
                label="Удалить"
                variant="danger"
                @click="removeMessenger(index)"
              />
            </div>
          </div>
        </div>

        <p v-else class="admin-field-hint">Мессенджеры ещё не добавлены.</p>

        <datalist id="messenger-icon-options">
          <option v-for="option in messengerIconOptions" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </datalist>

        <div class="admin-actions">
          <button class="btn secondary" type="button" @click="addMessenger">
            Добавить мессенджер
          </button>
          <button class="btn" type="submit" :disabled="savingContacts">
            {{ savingContacts ? 'Сохранение...' : 'Сохранить контакты' }}
          </button>
        </div>
      </fieldset>
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

.messenger-list {
  display: grid;
  gap: 0.85rem;
  margin-bottom: 1rem;
}

.messenger-row {
  display: grid;
  grid-template-columns: 1fr 1fr 1.4fr auto;
  gap: 0.75rem;
  align-items: end;
  padding: 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  background: #f8fafc;
}

.messenger-row__preview {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding-bottom: 0.15rem;
}

.messenger-row__icon {
  display: inline-flex;
  width: 36px;
  height: 36px;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  background: #222222;
  color: #fff;
}

@media (max-width: 900px) {
  .messenger-row {
    grid-template-columns: 1fr;
  }
}
</style>
