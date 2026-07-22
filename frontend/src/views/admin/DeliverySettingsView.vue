<script setup>
import { onMounted, reactive, ref } from 'vue'
import api from '@/api/client'
import AppLoader from '@/components/AppLoader.vue'
import { validateForm } from '@/lib/formValidation'
import { useToastStore } from '@/stores/toast'

const toast = useToastStore()
const loading = ref(true)
const saving = ref(false)
const testingBaikal = ref(false)
const testingDellin = ref(false)
const testingYandex = ref(false)
const testingZheldor = ref(false)
const testingRussianPost = ref(false)
const testResult = ref('')
const error = ref('')
const success = ref('')

const form = reactive({
  pack_units_count: 10,
  pack_width_cm: null,
  pack_length_cm: null,
  pack_height_cm: null,
  pack_weight_kg: null,
  sender_city: '',
  sender_postal_code: '',
  sender_address: '',
  baikal_enabled: false,
  dellin_enabled: false,
  yandex_delivery_enabled: false,
  zheldor_enabled: false,
  cdek_enabled: false,
  russian_post_enabled: false,
  russian_post_object_type: 4030,
  baikal_api_key: '',
  dellin_app_key: '',
  yandex_delivery_oauth_token: '',
  zheldor_login: '',
  zheldor_password: '',
  cdek_client_id: '',
  cdek_client_secret: '',
  cdek_use_test_api: true,
})

const providers = ref([])
const credentials = ref({
  baikal: {},
  dellin: {},
  yandex_delivery: {},
  zheldor: {},
  cdek: {},
  russian_post: {},
})

const providerSenders = reactive({
  baikal: emptySender(),
  dellin: emptySender(),
  yandex_delivery: emptySender(),
  zheldor: emptySender(),
  cdek: emptySender(),
  russian_post: emptySender(),
})

const PROVIDER_KEYS = ['baikal', 'dellin', 'yandex_delivery', 'zheldor', 'cdek', 'russian_post']

const expandedProviders = reactive(
  Object.fromEntries(PROVIDER_KEYS.map((key) => [key, false])),
)

function providerEnabledKey(key) {
  return `${key}_enabled`
}

function toggleProvider(key) {
  if (!form[providerEnabledKey(key)]) return
  expandedProviders[key] = !expandedProviders[key]
}

function providerMeta(key) {
  return providers.value.find((item) => item.key === key) ?? null
}

function emptySender() {
  return {
    city: '',
    postal_code: '',
    address: '',
    terminal_id: '',
  }
}

function applyProviderSenders(saved = {}) {
  for (const key of Object.keys(providerSenders)) {
    Object.assign(providerSenders[key], emptySender(), saved[key] ?? {})
  }
}

async function load() {
  loading.value = true
  error.value = ''

  try {
    const { data } = await api.get('/admin/delivery-settings')
    const settings = data.data

    Object.assign(form, {
      pack_units_count: settings.pack_units_count ?? 10,
      pack_width_cm: settings.pack_width_cm,
      pack_length_cm: settings.pack_length_cm,
      pack_height_cm: settings.pack_height_cm,
      pack_weight_kg: settings.pack_weight_kg,
      sender_city: settings.sender_city ?? '',
      sender_postal_code: settings.sender_postal_code ?? '',
      sender_address: settings.sender_address ?? '',
      baikal_enabled: settings.baikal_enabled ?? false,
      dellin_enabled: settings.dellin_enabled ?? false,
      yandex_delivery_enabled: settings.yandex_delivery_enabled ?? false,
      zheldor_enabled: settings.zheldor_enabled ?? false,
      cdek_enabled: settings.cdek_enabled ?? false,
      russian_post_enabled: settings.russian_post_enabled ?? false,
      russian_post_object_type: settings.russian_post_object_type ?? 4030,
      baikal_api_key: '',
      dellin_app_key: '',
      yandex_delivery_oauth_token: '',
      zheldor_login: settings.credentials?.zheldor?.login ?? '',
      zheldor_password: '',
      cdek_client_id: settings.credentials?.cdek?.client_id ?? '',
      cdek_client_secret: '',
      cdek_use_test_api: settings.credentials?.cdek?.use_test_api ?? true,
    })

    providers.value = settings.providers ?? []
    credentials.value = settings.credentials ?? credentials.value
    applyProviderSenders(settings.provider_senders)
  } catch (err) {
    error.value = err.response?.data?.message || 'Не удалось загрузить настройки доставки.'
  } finally {
    loading.value = false
  }
}

async function save(event) {
  if (!validateForm(event?.target)) return

  saving.value = true
  error.value = ''
  success.value = ''

  const payload = {
    ...form,
    provider_senders: JSON.parse(JSON.stringify(providerSenders)),
  }

  if (!payload.baikal_api_key) delete payload.baikal_api_key
  if (!payload.dellin_app_key) delete payload.dellin_app_key
  if (!payload.yandex_delivery_oauth_token) delete payload.yandex_delivery_oauth_token
  if (!payload.zheldor_password) delete payload.zheldor_password
  if (!payload.cdek_client_secret) delete payload.cdek_client_secret

  try {
    const { data } = await api.post('/admin/delivery-settings', payload)
    providers.value = data.data.providers ?? []
    credentials.value = data.data.credentials ?? credentials.value
    form.baikal_api_key = ''
    form.dellin_app_key = ''
    form.yandex_delivery_oauth_token = ''
    form.zheldor_password = ''
    form.cdek_client_secret = ''
    success.value = 'Настройки доставки сохранены.'
    toast.success(success.value)
  } catch (err) {
    error.value = err.response?.data?.message || 'Не удалось сохранить настройки доставки.'
    toast.error(error.value)
  } finally {
    saving.value = false
  }
}

async function testBaikal() {
  testingBaikal.value = true
  testResult.value = ''
  error.value = ''

  try {
    const { data } = await api.post('/admin/delivery-settings/test/baikal', {
      departure_city: providerSenders.baikal.city || form.sender_city || null,
      destination_city: 'Санкт-Петербург',
    })
    testResult.value = data.message
  } catch (err) {
    error.value = err.response?.data?.message || 'Не удалось проверить подключение к Байкал Сервис.'
  } finally {
    testingBaikal.value = false
  }
}

async function testDellin() {
  testingDellin.value = true
  testResult.value = ''
  error.value = ''

  try {
    const { data } = await api.post('/admin/delivery-settings/test/dellin', {
      departure_city: providerSenders.dellin.city || form.sender_city || null,
      destination_city: 'Санкт-Петербург',
    })
    testResult.value = data.message
  } catch (err) {
    error.value = err.response?.data?.message || 'Не удалось проверить подключение к Деловым линиям.'
  } finally {
    testingDellin.value = false
  }
}

async function testYandex() {
  testingYandex.value = true
  testResult.value = ''
  error.value = ''

  try {
    const { data } = await api.post('/admin/delivery-settings/test/yandex', {
      destination_city: 'Санкт-Петербург',
    })
    testResult.value = data.message
  } catch (err) {
    error.value = err.response?.data?.message || 'Не удалось проверить подключение к Яндекс Доставке.'
  } finally {
    testingYandex.value = false
  }
}

async function testZheldor() {
  testingZheldor.value = true
  testResult.value = ''
  error.value = ''

  try {
    const { data } = await api.post('/admin/delivery-settings/test/zheldor', {
      departure_city: providerSenders.zheldor.city || form.sender_city || null,
      destination_city: 'Санкт-Петербург',
    })
    testResult.value = data.message
  } catch (err) {
    error.value = err.response?.data?.message || 'Не удалось проверить подключение к Желдорэкспедиции.'
  } finally {
    testingZheldor.value = false
  }
}

async function testRussianPost() {
  testingRussianPost.value = true
  testResult.value = ''
  error.value = ''

  try {
    const { data } = await api.post('/admin/delivery-settings/test/russian-post', {
      from_postal_code: providerSenders.russian_post.postal_code || form.sender_postal_code || null,
      to_postal_code: '190000',
    })
    testResult.value = data.message
    if (data.data?.sample_price != null) {
      testResult.value += ` Пример: ${Number(data.data.sample_price).toLocaleString('ru-RU')} ₽.`
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Не удалось проверить тарификатор Почты России.'
  } finally {
    testingRussianPost.value = false
  }
}

onMounted(load)
</script>

<template>
  <section class="admin-page">
    <header class="admin-page__header">
      <h1>Доставка</h1>
      <p class="admin-page__lead">
        Параметры одного грузоместа на {{ form.pack_units_count }} катушек. Размеры в сантиметрах, вес в килограммах.
      </p>
    </header>

    <AppLoader v-if="loading" />

    <form v-else class="grid gap-4 admin-form--wide" novalidate @submit.prevent="save">
      <div class="card admin-panel">
        <h3 class="admin-card-title">Грузоместо</h3>

        <label class="field">
          <span>Количество катушек в одном грузоместе</span>
          <input v-model.number="form.pack_units_count" type="number" min="1" max="100" required />
        </label>

        <div class="grid gap-3 sm:grid-cols-2">
          <label class="field">
            <span>Ширина, см</span>
            <input v-model.number="form.pack_width_cm" type="number" min="0.1" step="0.1" />
          </label>
          <label class="field">
            <span>Длина, см</span>
            <input v-model.number="form.pack_length_cm" type="number" min="0.1" step="0.1" />
          </label>
          <label class="field">
            <span>Высота, см</span>
            <input v-model.number="form.pack_height_cm" type="number" min="0.1" step="0.1" />
          </label>
          <label class="field">
            <span>Вес, кг</span>
            <input v-model.number="form.pack_weight_kg" type="number" min="0.001" step="0.001" />
          </label>
        </div>
      </div>

      <div class="card admin-panel">
        <h3 class="admin-card-title">Склад по умолчанию</h3>
        <p class="admin-field-hint">
          Используется как запасной вариант, если для службы не указан свой пункт отправления.
        </p>

        <label class="field">
          <span>Город отправления</span>
          <input v-model="form.sender_city" type="text" placeholder="Москва" />
        </label>

        <label class="field">
          <span>Индекс</span>
          <input v-model="form.sender_postal_code" type="text" placeholder="101000" />
        </label>

        <label class="field">
          <span>Адрес склада</span>
          <textarea v-model="form.sender_address" rows="2" placeholder="Улица, дом" />
        </label>
      </div>

      <div class="card admin-panel admin-form--wide">
        <h3 class="admin-card-title">Службы доставки</h3>
        <p class="admin-field-hint">
          Ключи хранятся в базе в зашифрованном виде. Оставьте поле секрета пустым, чтобы не менять сохранённое значение.
        </p>

        <div class="providers">
          <section class="provider-card">
            <div class="provider-header">
              <button
                type="button"
                class="provider-toggle"
                :class="{ 'provider-toggle--disabled': !form.baikal_enabled }"
                :aria-expanded="expandedProviders.baikal"
                :disabled="!form.baikal_enabled"
                @click="toggleProvider('baikal')"
              >
                <span class="provider-chevron" :class="{ 'provider-chevron--open': expandedProviders.baikal }" />
              </button>

              <div class="provider-row">
                <label class="checkbox admin-checkbox">
                  <input v-model="form.baikal_enabled" type="checkbox" />
                  <span>Байкал Сервис</span>
                </label>
                <span class="status" :class="providerMeta('baikal')?.configured ? 'status--ok' : 'status--warn'">
                  {{ providerMeta('baikal')?.configured ? 'API настроен' : 'Нужны ключи' }}
                </span>
              </div>
            </div>

            <div v-show="form.baikal_enabled && expandedProviders.baikal" class="credentials">
              <label class="field">
                <span>API-ключ</span>
                <input
                  v-model="form.baikal_api_key"
                  type="password"
                  autocomplete="new-password"
                  :placeholder="credentials.baikal.api_key_hint || 'Введите новый ключ'"
                />
              </label>

              <div class="sender-block">
                <p class="sender-title">Пункт отправления</p>
                <label class="field">
                  <span>Город</span>
                  <input v-model="providerSenders.baikal.city" type="text" placeholder="Москва" />
                </label>
                <label class="field">
                  <span>Индекс</span>
                  <input v-model="providerSenders.baikal.postal_code" type="text" placeholder="101000" />
                </label>
                <label class="field">
                  <span>Адрес / терминал</span>
                  <textarea v-model="providerSenders.baikal.address" rows="2" />
                </label>
                <label class="field">
                  <span>ID терминала (если нужен API)</span>
                  <input v-model="providerSenders.baikal.terminal_id" type="text" />
                </label>
                <button
                  class="btn secondary"
                  type="button"
                  :disabled="testingBaikal || !form.baikal_enabled"
                  @click="testBaikal"
                >
                  {{ testingBaikal ? 'Проверка...' : 'Проверить подключение' }}
                </button>
              </div>
            </div>
          </section>

          <section class="provider-card">
            <div class="provider-header">
              <button
                type="button"
                class="provider-toggle"
                :class="{ 'provider-toggle--disabled': !form.dellin_enabled }"
                :aria-expanded="expandedProviders.dellin"
                :disabled="!form.dellin_enabled"
                @click="toggleProvider('dellin')"
              >
                <span class="provider-chevron" :class="{ 'provider-chevron--open': expandedProviders.dellin }" />
              </button>

              <div class="provider-row">
                <label class="checkbox admin-checkbox">
                  <input v-model="form.dellin_enabled" type="checkbox" />
                  <span>Деловые линии</span>
                </label>
                <span class="status" :class="providerMeta('dellin')?.configured ? 'status--ok' : 'status--warn'">
                  {{ providerMeta('dellin')?.configured ? 'API настроен' : 'Нужны ключи' }}
                </span>
              </div>
            </div>

            <div v-show="form.dellin_enabled && expandedProviders.dellin" class="credentials">
              <label class="field">
                <span>App Key</span>
                <input
                  v-model="form.dellin_app_key"
                  type="password"
                  autocomplete="new-password"
                  :placeholder="credentials.dellin.app_key_hint || 'Введите новый App Key'"
                />
              </label>

              <div class="sender-block">
                <p class="sender-title">Пункт отправления</p>
                <label class="field">
                  <span>Город</span>
                  <input v-model="providerSenders.dellin.city" type="text" placeholder="Москва" />
                </label>
                <label class="field">
                  <span>Индекс</span>
                  <input v-model="providerSenders.dellin.postal_code" type="text" placeholder="101000" />
                </label>
                <label class="field">
                  <span>Адрес терминала</span>
                  <textarea v-model="providerSenders.dellin.address" rows="2" />
                </label>
                <label class="field">
                  <span>ID терминала ДЛ</span>
                  <input v-model="providerSenders.dellin.terminal_id" type="text" placeholder="Если сдаёте на терминал" />
                </label>
                <button
                  class="btn secondary"
                  type="button"
                  :disabled="testingDellin || !form.dellin_enabled"
                  @click="testDellin"
                >
                  {{ testingDellin ? 'Проверка...' : 'Проверить подключение' }}
                </button>
              </div>
            </div>
          </section>

          <section class="provider-card">
            <div class="provider-header">
              <button
                type="button"
                class="provider-toggle"
                :class="{ 'provider-toggle--disabled': !form.yandex_delivery_enabled }"
                :aria-expanded="expandedProviders.yandex_delivery"
                :disabled="!form.yandex_delivery_enabled"
                @click="toggleProvider('yandex_delivery')"
              >
                <span class="provider-chevron" :class="{ 'provider-chevron--open': expandedProviders.yandex_delivery }" />
              </button>

              <div class="provider-row">
                <label class="checkbox admin-checkbox">
                  <input v-model="form.yandex_delivery_enabled" type="checkbox" />
                  <span>Яндекс Доставка</span>
                </label>
                <span class="status" :class="providerMeta('yandex_delivery')?.configured ? 'status--ok' : 'status--warn'">
                  {{ providerMeta('yandex_delivery')?.configured ? 'API настроен' : 'Нужны ключи' }}
                </span>
              </div>
            </div>

            <div v-show="form.yandex_delivery_enabled && expandedProviders.yandex_delivery" class="credentials">
              <label class="field">
                <span>OAuth-токен</span>
                <input
                  v-model="form.yandex_delivery_oauth_token"
                  type="password"
                  autocomplete="new-password"
                  :placeholder="credentials.yandex_delivery.oauth_token_hint || 'Введите новый токен'"
                />
              </label>

              <div class="sender-block">
                <p class="sender-title">Пункт отправления</p>
                <label class="field">
                  <span>Город</span>
                  <input v-model="providerSenders.yandex_delivery.city" type="text" />
                </label>
                <label class="field">
                  <span>Индекс</span>
                  <input v-model="providerSenders.yandex_delivery.postal_code" type="text" />
                </label>
                <label class="field">
                  <span>Адрес склада</span>
                  <textarea v-model="providerSenders.yandex_delivery.address" rows="2" />
                </label>
                <label class="field">
                  <span>ID склада (platform_station_id) *</span>
                  <input
                    v-model="providerSenders.yandex_delivery.terminal_id"
                    type="text"
                    placeholder="UUID склада отправки в личном кабинете Яндекс Доставки"
                  />
                </label>
                <p class="muted text-sm m-0">
                  Обязательно для расчёта тарифов на checkout. Без ID склада проверка подключения завершится ошибкой.
                </p>
                <button
                  class="btn secondary"
                  type="button"
                  :disabled="testingYandex || !form.yandex_delivery_enabled"
                  @click="testYandex"
                >
                  {{ testingYandex ? 'Проверка...' : 'Проверить подключение' }}
                </button>
              </div>
            </div>
          </section>

          <section class="provider-card">
            <div class="provider-header">
              <button
                type="button"
                class="provider-toggle"
                :class="{ 'provider-toggle--disabled': !form.zheldor_enabled }"
                :aria-expanded="expandedProviders.zheldor"
                :disabled="!form.zheldor_enabled"
                @click="toggleProvider('zheldor')"
              >
                <span class="provider-chevron" :class="{ 'provider-chevron--open': expandedProviders.zheldor }" />
              </button>

              <div class="provider-row">
                <label class="checkbox admin-checkbox">
                  <input v-model="form.zheldor_enabled" type="checkbox" />
                  <span>Желдорэкспедиция</span>
                </label>
                <span class="status" :class="providerMeta('zheldor')?.configured ? 'status--ok' : 'status--warn'">
                  {{ providerMeta('zheldor')?.configured ? 'API настроен' : 'Нужны ключи' }}
                </span>
              </div>
            </div>

            <div v-show="form.zheldor_enabled && expandedProviders.zheldor" class="credentials">
              <label class="field">
                <span>Логин</span>
                <input v-model="form.zheldor_login" type="text" autocomplete="off" />
              </label>
              <label class="field">
                <span>Пароль</span>
                <input
                  v-model="form.zheldor_password"
                  type="password"
                  autocomplete="new-password"
                  :placeholder="credentials.zheldor.password_hint || 'Введите новый пароль'"
                />
              </label>

              <div class="sender-block">
                <p class="sender-title">Пункт отправления</p>
                <label class="field">
                  <span>Город</span>
                  <input v-model="providerSenders.zheldor.city" type="text" />
                </label>
                <label class="field">
                  <span>Индекс</span>
                  <input v-model="providerSenders.zheldor.postal_code" type="text" />
                </label>
                <label class="field">
                  <span>Адрес / терминал</span>
                  <textarea v-model="providerSenders.zheldor.address" rows="2" />
                </label>
                <label class="field">
                  <span>ID терминала отправления</span>
                  <input
                    v-model="providerSenders.zheldor.terminal_id"
                    type="text"
                    placeholder="Код терминала из i.jde.ru, если сдаёте на станцию"
                  />
                </label>
              </div>

              <div class="actions">
                <button
                  type="button"
                  class="btn btn--secondary"
                  :disabled="testingZheldor || saving"
                  @click="testZheldor"
                >
                  {{ testingZheldor ? 'Проверка...' : 'Проверить подключение' }}
                </button>
              </div>
            </div>
          </section>

          <section class="provider-card provider-card--optional">
            <div class="provider-header">
              <button
                type="button"
                class="provider-toggle"
                :class="{ 'provider-toggle--disabled': !form.cdek_enabled }"
                :aria-expanded="expandedProviders.cdek"
                :disabled="!form.cdek_enabled"
                @click="toggleProvider('cdek')"
              >
                <span class="provider-chevron" :class="{ 'provider-chevron--open': expandedProviders.cdek }" />
              </button>

              <div class="provider-row">
                <label class="checkbox admin-checkbox">
                  <input v-model="form.cdek_enabled" type="checkbox" />
                  <span>СДЭК</span>
                </label>
                <span class="status muted">Когда появятся ключи</span>
              </div>
            </div>

            <div v-show="form.cdek_enabled && expandedProviders.cdek" class="credentials">
              <label class="field">
                <span>Client ID</span>
                <input v-model="form.cdek_client_id" type="text" autocomplete="off" />
              </label>
              <label class="field">
                <span>Client Secret</span>
                <input
                  v-model="form.cdek_client_secret"
                  type="password"
                  autocomplete="new-password"
                  :placeholder="credentials.cdek.client_secret_hint || 'Введите новый секрет'"
                />
              </label>
              <label class="checkbox admin-checkbox">
                <input v-model="form.cdek_use_test_api" type="checkbox" />
                <span>Тестовый API СДЭК (api.edu.cdek.ru)</span>
              </label>

              <div class="sender-block">
                <p class="sender-title">Пункт отправления</p>
                <label class="field">
                  <span>Город</span>
                  <input v-model="providerSenders.cdek.city" type="text" />
                </label>
                <label class="field">
                  <span>Индекс</span>
                  <input v-model="providerSenders.cdek.postal_code" type="text" />
                </label>
                <label class="field">
                  <span>Адрес ПВЗ / склада</span>
                  <textarea v-model="providerSenders.cdek.address" rows="2" />
                </label>
              </div>
            </div>
          </section>

          <section class="provider-card">
            <div class="provider-header">
              <button
                type="button"
                class="provider-toggle"
                :class="{ 'provider-toggle--disabled': !form.russian_post_enabled }"
                :aria-expanded="expandedProviders.russian_post"
                :disabled="!form.russian_post_enabled"
                @click="toggleProvider('russian_post')"
              >
                <span class="provider-chevron" :class="{ 'provider-chevron--open': expandedProviders.russian_post }" />
              </button>

              <div class="provider-row">
                <label class="checkbox admin-checkbox">
                  <input v-model="form.russian_post_enabled" type="checkbox" />
                  <span>Почта России</span>
                </label>
                <span class="status" :class="providerMeta('russian_post')?.configured ? 'status--ok' : 'status--warn'">
                  {{ providerMeta('russian_post')?.configured ? 'Индекс отправления задан' : 'Нужен индекс отправления' }}
                </span>
              </div>
            </div>

            <div v-show="form.russian_post_enabled && expandedProviders.russian_post" class="credentials">
              <p class="admin-field-hint">
                Публичный тарификатор Почты России (ключи не нужны). Расчёт по индексам отправителя и получателя.
              </p>

              <label class="field">
                <span>Тип отправления (код РТМ)</span>
                <input v-model.number="form.russian_post_object_type" type="number" min="1" step="1" />
                <span class="admin-field-hint">По умолчанию 4030 — «Посылка». 47030 — «Посылка 1 класса».</span>
              </label>

              <div class="sender-block">
                <p class="sender-title">Пункт отправления</p>
                <label class="field">
                  <span>Город</span>
                  <input v-model="providerSenders.russian_post.city" type="text" placeholder="Москва" />
                </label>
                <label class="field">
                  <span>Индекс</span>
                  <input
                    v-model="providerSenders.russian_post.postal_code"
                    type="text"
                    placeholder="101000"
                    required
                  />
                </label>
                <label class="field">
                  <span>Адрес отделения / склада</span>
                  <textarea v-model="providerSenders.russian_post.address" rows="2" />
                </label>
              </div>

              <div class="provider-test">
                <button
                  class="btn secondary"
                  type="button"
                  :disabled="testingRussianPost || !form.russian_post_enabled"
                  @click="testRussianPost"
                >
                  {{ testingRussianPost ? 'Проверка...' : 'Проверить расчёт' }}
                </button>
              </div>
            </div>
          </section>
        </div>
      </div>

      <div class="admin-actions admin-panel">
        <button class="btn" type="submit" :disabled="saving">
          {{ saving ? 'Сохранение...' : 'Сохранить' }}
        </button>
        <p v-if="success" class="admin-success">{{ success }}</p>
        <p v-if="testResult" class="admin-success">{{ testResult }}</p>
        <p v-if="error" class="admin-error">{{ error }}</p>
      </div>
    </form>
  </section>
</template>

<style scoped>
.providers {
  display: grid;
  gap: 1rem;
}

.provider-card {
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
  padding: 1rem;
  display: grid;
  gap: 0.75rem;
}

.provider-card--optional {
  opacity: 0.92;
}

.provider-header {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
}

.provider-toggle {
  flex-shrink: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.75rem;
  height: 1.75rem;
  margin-top: 0.1rem;
  border: 0;
  border-radius: 0.375rem;
  background: #f1f5f9;
  color: #475569;
  cursor: pointer;
  transition: background-color 0.15s ease;
}

.provider-toggle:hover:not(:disabled) {
  background: #e2e8f0;
}

.provider-toggle--disabled {
  opacity: 0.45;
  cursor: default;
}

.provider-chevron {
  display: block;
  width: 0.45rem;
  height: 0.45rem;
  border-right: 2px solid currentColor;
  border-bottom: 2px solid currentColor;
  transform: rotate(-45deg);
  transition: transform 0.15s ease;
}

.provider-chevron--open {
  transform: rotate(45deg);
  margin-top: -0.15rem;
}

.provider-row {
  flex: 1;
  min-width: 0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
}

.credentials {
  display: grid;
  gap: 0.75rem;
  padding-top: 0.25rem;
}

.sender-block {
  display: grid;
  gap: 0.75rem;
  padding: 0.875rem;
  border-radius: 0.65rem;
  background: #f8fafc;
  border: 1px dashed #cbd5e1;
}

.sender-title {
  margin: 0;
  font-size: 0.8125rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: #475569;
}

.checkbox {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.status {
  font-size: 0.875rem;
}

.status--ok {
  color: #059669;
}

.status--warn {
  color: #d97706;
}

.actions {
  display: grid;
  gap: 0.5rem;
}
</style>
