<script setup>
import { ref, watch } from 'vue'
import api from '@/api/client'

const props = defineProps({
  provider: {
    type: String,
    default: '',
  },
  mode: {
    type: String,
    default: 'city',
    validator: (value) => ['city', 'pickup'].includes(value),
  },
  cityGuid: {
    type: String,
    default: '',
  },
  cityId: {
    type: String,
    default: '',
  },
  cityName: {
    type: String,
    default: '',
  },
  modelValue: {
    type: Object,
    default: null,
  },
  label: {
    type: String,
    default: '',
  },
  placeholder: {
    type: String,
    default: '',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue'])

const query = ref(props.modelValue?.label ?? '')
const items = ref([])
const open = ref(false)
const loading = ref(false)
const error = ref('')
let debounceTimer = null

watch(
  () => props.modelValue,
  (value) => {
    query.value = value?.label ?? ''
  },
)

watch(
  () => [props.cityGuid, props.cityId, props.cityName],
  () => {
    if (props.mode === 'pickup') {
      items.value = []
      open.value = false
    }
  },
)

function hasPickupContext() {
  return Boolean(props.cityGuid || props.cityId || props.cityName)
}

function onInput() {
  emit('update:modelValue', null)
  scheduleSearch()
}

function scheduleSearch() {
  clearTimeout(debounceTimer)

  if (props.disabled) return

  const trimmed = query.value.trim()

  if (props.mode === 'city' && trimmed.length < 2) {
    items.value = []
    open.value = false
    return
  }

  debounceTimer = setTimeout(() => search(trimmed), 300)
}

async function search(trimmed) {
  loading.value = true
  error.value = ''

  try {
    if (props.mode === 'city') {
      if (trimmed.length < 2) {
        items.value = []
        open.value = false
        return
      }

      const params = { query: trimmed }

      if (props.provider) {
        params.provider = props.provider
      }

      const { data } = await api.get('/delivery/cities', { params })
      items.value = data.data
    } else {
      if (!hasPickupContext()) {
        items.value = []
        open.value = false
        return
      }

      const params = {
        provider: props.provider,
        query: trimmed,
      }

      if (props.cityGuid) params.city_guid = props.cityGuid
      if (props.cityId) params.city_id = props.cityId
      if (props.cityName) params.city_name = props.cityName

      const { data } = await api.get('/delivery/pickup-points', { params })
      items.value = data.data
    }

    open.value = items.value.length > 0
  } catch (err) {
    error.value = err.response?.data?.message || 'Не удалось загрузить подсказки.'
    items.value = []
    open.value = false
  } finally {
    loading.value = false
  }
}

function select(item) {
  emit('update:modelValue', item)
  query.value = item.label
  open.value = false
}

function onFocus() {
  if (props.mode === 'pickup' && hasPickupContext()) {
    search(query.value.trim())
    return
  }

  scheduleSearch()
}

function onBlur() {
  setTimeout(() => {
    open.value = false
  }, 150)
}
</script>

<template>
  <label class="field autocomplete">
    <span v-if="label">{{ label }}</span>
    <input
      v-model="query"
      type="text"
      :placeholder="placeholder"
      :disabled="disabled"
      autocomplete="off"
      @input="onInput"
      @focus="onFocus"
      @blur="onBlur"
    />
    <p v-if="loading" class="muted text-sm">Поиск...</p>
    <p v-if="error" class="error text-sm">{{ error }}</p>
    <ul v-if="open" class="suggestions">
      <li v-for="item in items" :key="item.id">
        <button type="button" class="suggestion" @mousedown.prevent="select(item)">
          {{ item.label }}
        </button>
      </li>
    </ul>
  </label>
</template>

<style scoped>
.autocomplete {
  position: relative;
}

.suggestions {
  position: absolute;
  z-index: 20;
  top: calc(100% - 0.25rem);
  left: 0;
  right: 0;
  margin: 0;
  padding: 0.35rem 0;
  list-style: none;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
  background: #fff;
  box-shadow: 0 12px 32px rgba(15, 23, 42, 0.12);
  max-height: 240px;
  overflow-y: auto;
}

.suggestion {
  width: 100%;
  border: 0;
  background: transparent;
  text-align: left;
  padding: 0.65rem 0.875rem;
  cursor: pointer;
}

.suggestion:hover {
  background: #f8fafc;
}

.error {
  color: #dc2626;
}
</style>
