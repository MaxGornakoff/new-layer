<script setup>
import { computed, ref, watch } from 'vue'
import api from '@/api/client'
import AppLoader from '@/components/AppLoader.vue'
import DeliveryAutocomplete from '@/components/DeliveryAutocomplete.vue'

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

const providers = ref([])
const selectedCity = ref(null)
const selectedProvider = ref('')
const selectedQuote = ref(null)
const selectedPickup = ref(null)
const quotes = ref([])
const loading = ref(false)
const error = ref('')


const availableQuotes = computed(() =>
  quotes.value.filter((quote) => quote.available && quote.price !== null),
)

const selectedProviderMeta = computed(
  () => providers.value.find((provider) => provider.key === selectedProvider.value) ?? null,
)

const canSelectPickup = computed(
  () => Boolean(selectedProvider.value && selectedProviderMeta.value?.has_pickup_points && selectedCity.value?.id),
)

const deliveryReady = computed(
  () => Boolean(
    selectedQuote.value?.price != null
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
  const { data } = await api.get('/delivery/providers')
  providers.value = data.data ?? []
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

function buildCalculatePayload(terminalId = null) {
  const payload = {
    destination_city: selectedCity.value.name,
    total_quantity: props.totalQuantity,
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

  if (terminalId) {
    payload.destination_terminal_id = terminalId
  }

  return payload
}

async function calculateQuotes(terminalId = null) {
  if (!props.canCalculate || !selectedCity.value?.name) return

  loading.value = true
  error.value = ''

  if (!terminalId) {
    quotes.value = []
    selectedProvider.value = ''
    selectedQuote.value = null
    selectedPickup.value = null
  }

  try {
    const { data } = await api.post('/delivery/calculate', buildCalculatePayload(terminalId))

    quotes.value = data.data.quotes ?? []

    if (terminalId && selectedProvider.value) {
      const updated = quotes.value.find((quote) => quote.provider === selectedProvider.value)
      if (updated?.available && updated.price !== null) {
        selectedQuote.value = updated
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
  if (!quote.available || quote.price === null) return

  selectedProvider.value = quote.provider
  selectedQuote.value = quote
  selectedPickup.value = null
  emitDelivery()
}

watch(selectedCity, (value) => {
  if (value?.id) {
    calculateQuotes()
  } else {
    quotes.value = []
    selectedProvider.value = ''
    selectedQuote.value = null
    selectedPickup.value = null
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
    calculateQuotes(pickup.id)
    return
  }

  emitDelivery()
})

watch(
  () => props.totalQuantity,
  () => {
    if (selectedCity.value?.id) {
      calculateQuotes()
    }
  },
)

loadProviders()
</script>

<template>
  <div class="card delivery-section">
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

    <p v-else-if="error" class="error">{{ error }}</p>

    <div v-else-if="availableQuotes.length" class="grid gap-3">
      <p class="m-0 text-sm font-medium">Служба доставки</p>

      <div class="providers-grid">
        <label
          v-for="quote in availableQuotes"
          :key="quote.provider"
          class="provider-option"
          :class="{ 'provider-option--active': selectedProvider === quote.provider }"
        >
          <input
            v-model="selectedProvider"
            class="sr-only"
            type="radio"
            name="delivery-provider"
            :value="quote.provider"
            @change="selectProvider(quote)"
          />
          <span class="provider-option__name">{{ quote.name }}</span>
          <span class="provider-option__price">{{ formatPrice(quote.price) }} ₽</span>
          <span v-if="formatDays(quote)" class="provider-option__days">{{ formatDays(quote) }}</span>
        </label>
      </div>

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
        v-else-if="selectedProvider && !selectedProviderMeta?.has_pickup_points"
        class="muted m-0 text-sm"
      >
        Выбор ПВЗ для этой службы подключим на следующем шаге.
      </p>

      <p v-if="deliveryReady" class="success m-0 text-sm">
        {{ buildDeliveryAddress() }}
      </p>
    </div>

    <p v-else-if="selectedCity && !loading" class="muted m-0 text-sm">
      Для выбранного города пока нет доступных тарифов.
    </p>
  </div>
</template>

<style scoped>
.delivery-section {
  display: grid;
  gap: 1rem;
}

.error {
  color: #dc2626;
}

.success {
  color: #059669;
}

.providers-grid {
  display: grid;
  gap: 0.75rem;
}

.provider-option {
  display: grid;
  gap: 0.2rem;
  padding: 0.875rem 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
  cursor: pointer;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.provider-option--active {
  border-color: #2563eb;
  box-shadow: 0 0 0 1px #2563eb;
}

.provider-option__name {
  font-weight: 600;
}

.provider-option__price {
  font-size: 1.125rem;
  font-weight: 700;
}

.provider-option__days {
  font-size: 0.875rem;
  color: #64748b;
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}
</style>
