<script setup>
import { computed, onUnmounted, reactive, ref, watch } from 'vue'
import { isHexColor, isLightHexColor } from '@/lib/productColor'

const VISIBLE_LIMIT = 5

const props = defineProps({
  categories: {
    type: Array,
    default: () => [],
  },
  groups: {
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

const ARRAY_KEYS = ['category', 'color', 'diameter', 'weight']

function emptyFilters() {
  return {
    search: '',
    category: [],
    color: [],
    diameter: [],
    weight: [],
    in_stock: false,
  }
}

function cloneFilters(value = {}) {
  return {
    search: value.search ?? '',
    category: [...(value.category ?? [])],
    color: [...(value.color ?? [])],
    diameter: [...(value.diameter ?? [])],
    weight: [...(value.weight ?? [])],
    in_stock: Boolean(value.in_stock),
  }
}

const local = ref(cloneFilters(props.modelValue))
const expanded = reactive({})

let debounceTimer = null

const filterGroups = computed(() => {
  const groups = []

  if (props.categories.length) {
    groups.push({
      key: 'category',
      title: 'Тип пластика',
      options: props.categories.map((category) => ({
        value: category.slug,
        label: category.name,
      })),
    })
  }

  for (const group of props.groups) {
    if (!group?.key || !Array.isArray(group.options) || group.options.length === 0) {
      continue
    }

    groups.push({
      key: group.key,
      title: group.title || group.key,
      display: group.display || 'list',
      options: group.options.map((option) => ({
        value: String(option.value),
        label: option.label ?? String(option.value),
        hex: option.hex || null,
      })),
    })
  }

  return groups
})

const hasFilters = computed(() => {
  const value = local.value
  return (
    Boolean(value.search) ||
    ARRAY_KEYS.some((key) => value[key]?.length > 0) ||
    value.in_stock
  )
})

watch(
  () => props.modelValue,
  (value) => {
    local.value = cloneFilters(value)
  },
)

function filtersEqual(left, right) {
  return JSON.stringify(cloneFilters(left)) === JSON.stringify(cloneFilters(right))
}

function apply() {
  emit('update:modelValue', cloneFilters(local.value))
  emit('apply')
}

function reset() {
  local.value = emptyFilters()
  Object.keys(expanded).forEach((key) => {
    expanded[key] = false
  })
  emit('update:modelValue', emptyFilters())
  emit('reset')
}

function toggleValue(group, value) {
  const list = local.value[group]
  if (!Array.isArray(list)) return

  const index = list.indexOf(value)
  if (index === -1) {
    list.push(value)
  } else {
    list.splice(index, 1)
  }
}

function isChecked(group, value) {
  return local.value[group]?.includes(value) ?? false
}

function visibleOptions(options, groupKey) {
  if (expanded[groupKey] || options.length <= VISIBLE_LIMIT) {
    return options
  }

  return options.slice(0, VISIBLE_LIMIT)
}

function toggleExpanded(groupKey) {
  expanded[groupKey] = !expanded[groupKey]
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
  <form class="filters card rounded-[16px] border-0 bg-white p-4 sm:rounded-[20px] sm:p-6 lg:p-7" @submit.prevent="apply">
    <label class="m-0 field">
      <span>Поиск</span>
      <input v-model="local.search" type="search" placeholder="Название, артикул..." />
    </label>

    <fieldset
      v-for="group in filterGroups"
      :key="group.key"
      class="filter-group"
      :class="{ 'filter-group--swatches': group.display === 'swatches' || group.key === 'color' }"
    >
      <legend class=" font-semibold text-[16px] pb-2.5">{{ group.title }}</legend>

      <div
        v-if="group.display === 'swatches' || group.key === 'color'"
        class="color-swatches"
      >
        <label
          v-for="option in visibleOptions(group.options, group.key)"
          :key="option.value"
          class="color-swatch"
          :class="{ 'color-swatch--active': isChecked(group.key, option.value) }"
          :title="option.label"
        >
          <input
            class="filter-option__input"
            type="checkbox"
            :checked="isChecked(group.key, option.value)"
            @change="toggleValue(group.key, option.value)"
          />
          <span
            class="color-swatch__circle"
            :class="{
              'color-swatch__circle--light': isLightHexColor(option.hex || option.value),
            }"
            :style="{
              backgroundColor: isHexColor(option.hex || option.value)
                ? (option.hex || option.value)
                : '#cbd5e1',
            }"
            aria-hidden="true"
          />
          <span class="sr-only">{{ option.label }}</span>
        </label>
      </div>

      <template v-else>
        <label
          v-for="option in visibleOptions(group.options, group.key)"
          :key="option.value"
          class="filter-option"
        >
          <input
            class="filter-option__input"
            type="checkbox"
            :checked="isChecked(group.key, option.value)"
            @change="toggleValue(group.key, option.value)"
          />
          <span class="filter-option__box" aria-hidden="true" />
          <span class="filter-option__text">{{ option.label }}</span>
        </label>
      </template>

      <button
        v-if="group.options.length > VISIBLE_LIMIT"
        type="button"
        class="filter-group__toggle"
        @click="toggleExpanded(group.key)"
      >
        {{ expanded[group.key] ? 'Свернуть' : 'Развернуть' }}
      </button>
    </fieldset>

    <label class="filter-option filter-option--stock">
      <input v-model="local.in_stock" class="filter-option__input" type="checkbox" />
      <span class="filter-option__box" aria-hidden="true" />
      <span class="filter-option__text">Только в наличии</span>
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
  gap: 1rem;
}

.filter-group {
  margin: 0;
  padding: 0;
  border: 0;
  display: grid;
  gap: 0.55rem;
}

.filter-group__title {
  padding: 0;
  font-size: 0.875rem;
  font-weight: 600;
  color: #222222;
}

.filter-option {
  position: relative;
  display: flex;
  align-items: flex-start;
  gap: 0.55rem;
  font-size: 0.875rem;
  line-height: 1.35;
  color: #222222;
  cursor: pointer;
}

.filter-option__input {
  position: absolute;
  opacity: 0;
  width: 1px;
  height: 1px;
  margin: 0;
  padding: 0;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

.filter-option__box {
  position: relative;
  flex-shrink: 0;
  width: 1.25rem;
  height: 1.25rem;
  margin-top: 0.05rem;
  border: 1.5px solid #cbd5e1;
  border-radius: 0.35rem;
  background: #fff;
  transition:
    border-color 0.15s ease,
    background-color 0.15s ease,
    box-shadow 0.15s ease;
}

.filter-option__box::after {
  content: '';
  position: absolute;
  inset: 0;
  margin: auto;
  width: 0.35rem;
  height: 0.65rem;
  border: solid #fff;
  border-width: 0 2px 2px 0;
  opacity: 0;
  transform: rotate(45deg) translate(-1px, -1px);
  transition: opacity 0.15s ease;
}

.filter-option__input:checked + .filter-option__box {
  border-color: #3b72ff;
  background: #3b72ff;
}

.filter-option__input:checked + .filter-option__box::after {
  opacity: 1;
}

.filter-option__input:focus-visible + .filter-option__box {
  box-shadow: 0 0 0 3px rgba(59, 114, 255, 0.25);
}

.filter-option:hover .filter-option__box {
  border-color: #94a3b8;
}

.filter-option__text {
  min-width: 0;
}

.filter-option--stock {
  padding-top: 0.25rem;
}

.filter-group__toggle {
  justify-self: start;
  margin: 0;
  padding: 0;
  border: 0;
  background: transparent;
  color: #3b72ff;
  font-size: 0.8125rem;
  font-weight: 500;
  cursor: pointer;
}

.filter-group__toggle:hover {
  text-decoration: underline;
}

.color-swatches {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.color-swatch {
  position: relative;
  display: inline-flex;
  cursor: pointer;
}

.color-swatch__circle {
  display: block;
  width: 1.75rem;
  height: 1.75rem;
  border-radius: 999px;
  border: 1px solid rgba(15, 23, 42, 0.12);
  transition: box-shadow 0.15s ease, transform 0.15s ease;
}

.color-swatch__circle--light {
  border-color: #cbd5e1;
}

.color-swatch--active .color-swatch__circle,
.color-swatch:has(.filter-option__input:checked) .color-swatch__circle {
  box-shadow: 0 0 0 2px #fff, 0 0 0 4px #3b72ff;
}

.color-swatch:hover .color-swatch__circle {
  transform: scale(1.06);
}

.color-swatch .filter-option__input:focus-visible + .color-swatch__circle {
  box-shadow: 0 0 0 2px #fff, 0 0 0 4px rgba(59, 114, 255, 0.55);
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

.actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}
</style>
