<script setup>
import { computed, onUnmounted, ref, watch } from 'vue'

const props = defineProps({
  categories: {
    type: Array,
    default: () => [],
  },
  colors: {
    type: Array,
    default: () => [],
  },
  modelValue: {
    type: Object,
    default: () => ({}),
  },
  autoApply: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue', 'apply', 'reset'])

const local = ref({ ...props.modelValue })
let debounceTimer = null

watch(
  () => props.modelValue,
  (value) => {
    local.value = { ...value }
  },
)

const hasFilters = computed(() =>
  Object.values(local.value).some((value) => value !== '' && value !== false),
)

function filtersEqual(left, right) {
  return JSON.stringify(left) === JSON.stringify(right)
}

function apply() {
  emit('update:modelValue', { ...local.value })
  emit('apply')
}

function reset() {
  local.value = {
    search: '',
    category: '',
    color: '',
    diameter: '',
    in_stock: false,
  }
  emit('update:modelValue', { ...local.value })
  emit('reset')
}

watch(
  local,
  (value) => {
    if (!props.autoApply || filtersEqual(value, props.modelValue)) {
      return
    }

    clearTimeout(debounceTimer)
    const delay = value.search ? 400 : 0
    debounceTimer = setTimeout(apply, delay)
  },
  { deep: true },
)

onUnmounted(() => {
  clearTimeout(debounceTimer)
})
</script>

<template>
  <form class="rounded-[20px] bg-white border-0 p-7 card filters" @submit.prevent="apply">

    <label class="field">
      <span>Поиск</span>
      <input v-model="local.search" type="search" placeholder="Название, артикул..." />
    </label>

    <label class="field">
      <span>Тип пластика</span>
      <select v-model="local.category">
        <option value="">Все</option>
        <option v-for="category in categories" :key="category.id" :value="category.slug">
          {{ category.name }}
        </option>
      </select>
    </label>

    <label class="field">
      <span>Цвет</span>
      <select v-model="local.color">
        <option value="">Все</option>
        <option v-for="color in colors" :key="color" :value="color">
          {{ color }}
        </option>
      </select>
    </label>

    <label class="field">
      <span>Диаметр, мм</span>
      <select v-model="local.diameter">
        <option value="">Все</option>
        <option value="1.75">1.75</option>
        <option value="2.85">2.85</option>
      </select>
    </label>

    <label class="field checkbox">
      <input v-model="local.in_stock" type="checkbox" />
      <span>Только в наличии</span>
    </label>

    <div class="actions">
      <button v-if="!autoApply" class="btn" type="submit">Применить</button>
      <button v-if="hasFilters" class="btn secondary" type="button" @click="reset">
        Сбросить
      </button>
    </div>
  </form>
</template>

<style scoped>
.filters {
  display: grid;
  gap: 0.25rem;
}

.checkbox {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}
</style>
