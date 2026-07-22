<script setup>
import AppIcon from '@/components/AppIcon.vue'

defineProps({
  icon: {
    type: String,
    required: true,
  },
  label: {
    type: String,
    required: true,
  },
  /** secondary | primary | danger */
  variant: {
    type: String,
    default: 'secondary',
  },
  active: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
})

const variantClass = {
  secondary: 'bg-slate-100 text-slate-700 hover:bg-slate-200',
  primary: 'bg-[#3b72ff] text-white hover:bg-[#2f63ef]',
  danger: 'bg-red-50 text-red-600 hover:bg-red-100',
}
</script>

<template>
  <button
    type="button"
    class="inline-flex size-9 shrink-0 items-center justify-center rounded-lg border-0 transition-colors disabled:cursor-not-allowed disabled:opacity-50"
    :class="[
      active ? variantClass.primary : (variantClass[variant] || variantClass.secondary),
    ]"
    :aria-label="label"
    :title="label"
    :disabled="disabled"
  >
    <AppIcon :name="icon" :size="16" />
  </button>
</template>
