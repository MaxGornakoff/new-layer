<script setup>
import { computed, ref, watch } from 'vue'
import api from '@/api/client'
import AppLoader from '@/components/AppLoader.vue'
import DeliveryAutocomplete from '@/components/DeliveryAutocomplete.vue'
import baikalLogo from '@/assets/icons/delivery/baikal.svg'
import dellinLogo from '@/assets/icons/delivery/dellin.svg'
import russianPostLogo from '@/assets/icons/delivery/russian_post.svg'
import zheldorLogo from '@/assets/icons/delivery/zheldor.svg'

const props = defineProps({
  totalQuantity: {
    type: Number,
    required: true,
  },
  canCalculate: {
    type: Boolean,
    default: true,
  },
})

const emit = defineEmits(['update:delivery'])

const providerLogos = {
  baikal: baikalLogo,
  dellin: dellinLogo,
  russian_post: russianPostLogo,
  zheldor: zheldorLogo,
}

const failedLogos = ref({})

function onLogoError(provider) {
  failedLogos.value = { ...failedLogos.value, [provider]: true }
}

function hasProviderLogo(provider) {
  return Boolean(providerLogos[provider] && !failedLogos.value[provider])
}

const providers = ref([])
const selectedCity = ref(null)
const selectedProvider = ref('')
const selectedQuote = ref(null)
const selectedPickup = ref(null)
const destinationPostalCode = ref('')
const quotes = ref([])
const loading = ref(false)
const error = ref('')

let postalDebounce = null

const availableQuotes = computed(() =>
  quotes.value.filter((quote) => quote.available && quote.price !== null),
)

const russianPostQuote = computed(() =>
  quotes.value.find((quote) => quote.provider === 'russian_post') ?? null,
)

const displayQuotes = computed(() => {
  const list = [...availableQuotes.value]
  const russian = russianPostQuote.value

  if (
    russian
    && !list.some((quote) => quote.provider === 'russian_post')
  ) {
    list.push(russian)
  }

  return list
})

const selectedProviderMeta = computed(
  () => providers.value.find((provider) => provider.key === selectedProvider.value) ?? null,
)

const showPostalField = computed(() => selectedProvider.value === 'russian_post')

const canSelectPickup = computed(
  () => Boolean(selectedProvider.value && selectedProviderMeta.value?.has_pickup_points && selectedCity.value?.id),
)

const deliveryReady = computed(
  () => Boolean(
    selectedQuote.value?.available
    && selectedQuote.value?.price != null
    && selectedCity.value
    && (
      !selectedProviderMeta.value?.has_pickup_points
      || selectedPickup.value
    ),
  ),
)

function formatPrice(price) {
  return Number(price).toLocaleString('ru-RU')
}

function formatDays(quote) {
  if (quote.delivery_days_min && quote.delivery_days_max) {
    if (quote.delivery_days_min === quote.delivery_days_max) {
      return `${quote.delivery_days_min} дн.`
    }

    return `${quote.delivery_days_min}–${quote.delivery_days_max} дн.`
  }

  if (quote.delivery_days_min) {
    return `от ${quote.delivery_days_min} дн.`
  }

  return null
}

function buildDeliveryAddress() {
  if (!selectedCity.value) return ''

  const parts = [`г. ${selectedCity.value.name}`]

  if (destinationPostalCode.value.trim()) {
    parts.push(`индекс ${destinationPostalCode.value.trim()}`)
  }

  if (selectedPickup.value) {
    parts.push(`ПВЗ: ${selectedPickup.value.label}`)
  }

  return parts.join(', ')
}

function emitDelivery() {
  emit('update:delivery', {
    ready: deliveryReady.value,
    quote: selectedQuote.value,
    city: selectedCity.value,
    pickupPoint: selectedPickup.value,
    deliveryProvider: selectedQuote.value?.name || '',
    deliveryAddress: buildDeliveryAddress(),
  })
}

async function loadProviders() {
  try {
    const { data } = await api.get('/delivery/providers')
    providers.value = data.data ?? []
    if (!providers.value.length) {
      error.value = 'Службы доставки пока не настроены. Обратитесь к администратору или заполните раздел «Доставка» в админке.'
    }
  } catch {
    providers.value = []
    error.value = 'Не удалось загрузить службы доставки. Попробуйте обновить страницу.'
  }
}

const cityGuidForPickup = computed(() => (
  selectedCity.value?.source === 'baikal' ? selectedCity.value.id : ''
))

const cityIdForPickup = computed(() => {
  if (selectedCity.value?.source === 'dellin') return selectedCity.value.id
  if (selectedCity.value?.source === 'yandex_delivery') return selectedCity.value.id
  if (selectedCity.value?.source === 'zheldor') return selectedCity.value.id
  return ''
})

function buildCalculatePayload(terminalId = null, terminalProvider = null) {
  const payload = {
    destination_city: selectedCity.value.name,
    total_quantity: props.totalQuantity,
  }

  if (destinationPostalCode.value.trim()) {
    payload.destination_postal_code = destinationPostalCode.value.trim()
  }

  if (selectedCity.value.source === 'dellin') {
    payload.destination_city_id = selectedCity.value.id
  } else if (selectedCity.value.source === 'yandex_delivery') {
    payload.destination_city_id = selectedCity.value.id
  } else if (selectedCity.value.source === 'zheldor') {
    payload.destination_city_id = selectedCity.value.id
  } else if (selectedCity.value.source === 'baikal') {
    payload.destination_city_guid = selectedCity.value.id
  } else if (selectedCity.value.id) {
    payload.destination_city_guid = selectedCity.value.id
  }

  if (terminalId && terminalProvider) {
    payload.destination_terminal_id = terminalId
    payload.destination_terminal_provider = terminalProvider
  }

  return payload
}

async function calculateQuotes(options = {}) {
  const terminalId = options.terminalId ?? null
  const terminalProvider = options.terminalProvider ?? null
  const resetSelection = options.resetSelection === true

  if (!props.canCalculate || !selectedCity.value?.name) return

  loading.value = true
  error.value = ''

  if (resetSelection) {
    selectedProvider.value = ''
    selectedQuote.value = null
    selectedPickup.value = null
    destinationPostalCode.value = ''
  }

  try {
    const { data } = await api.post(
      '/delivery/calculate',
      buildCalculatePayload(terminalId, terminalProvider),
    )

    quotes.value = data.data.quotes ?? []

    if (selectedProvider.value) {
      const updated = quotes.value.find((quote) => quote.provider === selectedProvider.value)
      if (updated?.available && updated.price !== null) {
        selectedQuote.value = updated
        if (
          updated.provider === 'russian_post'
          && updated.postal_code
          && !destinationPostalCode.value.trim()
        ) {
          destinationPostalCode.value = String(updated.postal_code)
        }
      } else if (updated) {
        selectedQuote.value = null
      }
    }

    emitDelivery()
  } catch (err) {
    error.value = err.response?.data?.message || 'Не удалось рассчитать доставку.'
    emitDelivery()
  } finally {
    loading.value = false
  }
}

function selectProvider(quote) {
  selectedProvider.value = quote.provider
  selectedPickup.value = null

  if (quote.available && quote.price !== null) {
    selectedQuote.value = quote
    if (quote.provider === 'russian_post' && quote.postal_code && !destinationPostalCode.value.trim()) {
      destinationPostalCode.value = String(quote.postal_code)
    }
  } else {
    selectedQuote.value = null
  }

  emitDelivery()
}

watch(selectedCity, (value) => {
  if (value?.id) {
    calculateQuotes({ resetSelection: true })
  } else {
    quotes.value = []
    selectedProvider.value = ''
    selectedQuote.value = null
    selectedPickup.value = null
    destinationPostalCode.value = ''
    emitDelivery()
  }
})

watch(selectedPickup, (pickup) => {
  if (
    (selectedProvider.value === 'dellin'
      || selectedProvider.value === 'yandex_delivery'
      || selectedProvider.value === 'zheldor')
    && pickup?.id
  ) {
    calculateQuotes({
      terminalId: pickup.id,
      terminalProvider: selectedProvider.value,
    })
    return
  }

  emitDelivery()
})

watch(
  () => props.totalQuantity,
  () => {
    if (!selectedCity.value?.id) return

    if (selectedPickup.value?.id && selectedProvider.value) {
      calculateQuotes({
        terminalId: selectedPickup.value.id,
        terminalProvider: selectedProvider.value,
      })
      return
    }

    calculateQuotes()
  },
)

function onPostalCodeInput() {
  if (!selectedCity.value?.id || selectedProvider.value !== 'russian_post') return

  clearTimeout(postalDebounce)
  postalDebounce = setTimeout(() => {
    calculateQuotes()
  }, 2000)
}

loadProviders()
</script>

<template>
  <div class="p-5 card grid gap-4">
    <h3 class="m-0 text-lg font-semibold">Доставка</h3>
    <p class="muted m-0 text-sm">
      Выберите город, службу доставки и пункт выдачи. Расчёт для {{ totalQuantity }} катушек.
    </p>

    <DeliveryAutocomplete
      v-model="selectedCity"
      mode="city"
      label="Город получения"
      placeholder="Начните вводить город"
      :disabled="!canCalculate"
    />

    <AppLoader v-if="loading" />

    <p v-else-if="error" class="text-red-600">{{ error }}</p>

    <div v-else-if="displayQuotes.length" class="grid gap-3">
      <p class="m-0 text-[16px] font-medium">Служба доставки:</p>

      <div class="grid grid-cols-3 gap-3 mb-5 max-md:grid-cols-2">
        <label
          v-for="quote in displayQuotes"
          :key="quote.provider"
          class="grid cursor-pointer gap-0.5 rounded-xl border border-slate-200 px-4 py-3.5 transition-[border-color,box-shadow] duration-150"
          :class="{
            'border-blue-600 shadow-[0_0_0_1px_#2563eb]': selectedProvider === quote.provider,
            'border-dashed': !quote.available || quote.price === null,
          }"
        >
          <input
            v-model="selectedProvider"
            class="sr-only"
            type="radio"
            name="delivery-provider"
            :value="quote.provider"
            @change="selectProvider(quote)"
          />
          <span
            v-if="hasProviderLogo(quote.provider)"
            class="mb-1 flex h-10 w-full items-center"
            aria-hidden="true"
          >
            <img
              :src="providerLogos[quote.provider]"
              alt=""
              :class="{'object-top': 'dellin' === quote.provider}"
              class="h-full w-auto max-w-full object-contain object-left"
              @error="onLogoError(quote.provider)"
            />
          </span>
          <span
            v-else
            class="font-semibold text-[14px] text-[#999999]"
          >{{ quote.name }}</span>
          <span v-if="quote.available && quote.price != null" class="text-lg font-bold">
            {{ formatPrice(quote.price) }} ₽
          </span>
          <span v-else class="text-[0.8125rem] leading-snug text-slate-500">
            {{ quote.message || 'Укажите индекс получателя' }}
          </span>
          <span v-if="formatDays(quote)" class="text-sm text-slate-500">{{ formatDays(quote) }}</span>
        </label>
      </div>

      <label v-if="showPostalField" class="field m-0">
        <span>Индекс получателя</span>
        <input
          v-model="destinationPostalCode"
          type="text"
          inputmode="numeric"
          maxlength="6"
          placeholder="190000"
          @input="onPostalCodeInput"
        />
        <span class="muted text-xs">
          Подставили по городу — при необходимости поправьте на более точный индекс отделения.
        </span>
      </label>

      <DeliveryAutocomplete
        v-if="canSelectPickup"
        v-model="selectedPickup"
        :provider="selectedProvider"
        mode="pickup"
        :city-guid="cityGuidForPickup"
        :city-id="cityIdForPickup"
        :city-name="selectedCity.name"
        label="Пункт выдачи"
        placeholder="Начните вводить адрес или название ПВЗ"
      />

      <p
        v-else-if="selectedProvider === 'russian_post' && deliveryReady"
        class="muted m-0 text-sm"
      >
        Доставка до отделения Почты России по индексу получателя.
      </p>

      <p
        v-else-if="selectedProvider && selectedProvider !== 'russian_post' && !selectedProviderMeta?.has_pickup_points"
        class="muted m-0 text-sm"
      >
        Выбор ПВЗ для этой службы подключим на следующем шаге.
      </p>

      <p v-if="deliveryReady" class="m-0 text-sm text-emerald-600">
        {{ buildDeliveryAddress() }}
      </p>
    </div>

    <p v-else-if="selectedCity && !loading" class="muted m-0 text-sm">
      Для выбранного города пока нет доступных тарифов.
    </p>
  </div>
</template>
