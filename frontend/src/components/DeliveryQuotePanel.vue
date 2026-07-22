<script setup>
import { computed, ref, watch } from 'vue'
import api from '@/api/client'
import AppLoader from '@/components/AppLoader.vue'
import { useToastStore } from '@/stores/toast'

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

const emit = defineEmits(['select'])
const toast = useToastStore()

const destinationCity = ref('')
const destinationPostalCode = ref('')
const cityInput = ref(null)
const loading = ref(false)
const error = ref('')
const result = ref(null)
const selectedProvider = ref('')

const quotes = computed(() => result.value?.quotes ?? [])
const packagesCount = computed(() => result.value?.packages_count ?? 0)

const availableQuotes = computed(() =>
  quotes.value.filter((quote) => quote.available && quote.price !== null),
)

async function calculate() {
  if (!props.canCalculate) {
    toast.warning('Добавьте товары в корзину, чтобы рассчитать доставку')
    return
  }

  if (!destinationCity.value.trim()) {
    toast.warning('Укажите город получателя', {
      anchor: cityInput.value,
      duration: 3800,
    })
    cityInput.value?.focus()
    return
  }

  loading.value = true
  error.value = ''
  result.value = null
  selectedProvider.value = ''

  try {
    const { data } = await api.post('/delivery/calculate', {
      destination_city: destinationCity.value.trim(),
      destination_postal_code: destinationPostalCode.value.trim() || null,
      total_quantity: props.totalQuantity,
    })

    result.value = data.data

    if (availableQuotes.value.length === 1) {
      selectQuote(availableQuotes.value[0])
    }
  } catch (err) {
    const message = err.response?.data?.message || 'Не удалось рассчитать доставку.'
    error.value = message
    toast.error(message)
  } finally {
    loading.value = false
  }
}

function selectQuote(quote) {
  selectedProvider.value = quote.provider
  emit('select', quote)
}

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

watch(
  () => props.totalQuantity,
  () => {
    result.value = null
    selectedProvider.value = ''
    emit('select', null)
  },
)
</script>

<template>
  <div class="card delivery-panel">
    <h3 class="m-0 text-lg font-semibold">Доставка</h3>
    <p class="muted m-0 text-sm">
      Расчёт по количеству в корзине ({{ totalQuantity }} катушек). Грузомест: {{ packagesCount || '—' }}.
    </p>

    <div class="grid gap-3 sm:grid-cols-2">
      <label class="field">
        <span>Город получателя</span>
        <input
          ref="cityInput"
          v-model="destinationCity"
          type="text"
          placeholder="Санкт-Петербург"
        />
      </label>
      <label class="field">
        <span>Индекс</span>
        <input v-model="destinationPostalCode" type="text" placeholder="190000" />
      </label>
    </div>

    <button
      class="btn secondary"
      type="button"
      :disabled="loading"
      @click="calculate"
    >
      {{ loading ? 'Расчёт...' : 'Рассчитать доставку' }}
    </button>

    <AppLoader v-if="loading" />

    <p v-if="error" class="error">{{ error }}</p>

    <ul v-else-if="quotes.length" class="quotes">
      <li
        v-for="quote in quotes"
        :key="quote.provider"
        class="quote"
        :class="{
          'quote--selected': selectedProvider === quote.provider,
          'quote--disabled': !quote.available || quote.price === null,
        }"
      >
        <button
          type="button"
          class="quote__button"
          :disabled="!quote.available || quote.price === null"
          @click="selectQuote(quote)"
        >
          <span class="quote__name">{{ quote.name }}</span>
          <span v-if="quote.price !== null" class="quote__price">
            {{ formatPrice(quote.price) }} ₽
          </span>
          <span v-else class="quote__pending">Тариф уточняется</span>
          <span v-if="formatDays(quote)" class="quote__days">{{ formatDays(quote) }}</span>
        </button>
        <p v-if="quote.message" class="quote__message muted">{{ quote.message }}</p>
      </li>
    </ul>
  </div>
</template>

<style scoped>
.delivery-panel {
  display: grid;
  gap: 1rem;
}

.error {
  color: #dc2626;
}

.quotes {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: 0.75rem;
}

.quote {
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
  overflow: hidden;
  background: #fff;
}

.quote--selected {
  border-color: #2563eb;
  box-shadow: 0 0 0 1px #2563eb;
}

.quote--disabled {
  opacity: 0.85;
}

.quote__button {
  width: 100%;
  display: grid;
  gap: 0.25rem;
  padding: 0.875rem 1rem;
  border: 0;
  background: transparent;
  text-align: left;
  cursor: pointer;
}

.quote__button:disabled {
  cursor: default;
}

.quote__name {
  font-weight: 600;
}

.quote__price {
  font-size: 1.125rem;
  font-weight: 700;
}

.quote__pending {
  color: #64748b;
}

.quote__days {
  font-size: 0.875rem;
  color: #64748b;
}

.quote__message {
  margin: 0;
  padding: 0 1rem 0.875rem;
  font-size: 0.875rem;
}
</style>
