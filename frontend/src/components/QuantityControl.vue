<script setup>
import { onUnmounted, ref, watch } from 'vue'

const quantity = defineModel({
  type: Number,
  required: true,
})

const props = defineProps({
  maxQuantity: {
    type: Number,
    required: true,
  },
  minQuantity: {
    type: Number,
    default: 1,
  },
  compact: {
    type: Boolean,
    default: false,
  },
  allowRemoveAtMin: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  commitDelayMs: {
    type: Number,
    default: 1000,
  },
  variant: {
    type: String,
    default: 'default',
    validator: (value) => ['default', 'shelf'].includes(value),
  },
})

const emit = defineEmits(['decrease-at-min'])

const draft = ref(String(quantity.value))
let commitTimer = null

watch(quantity, (value) => {
  clearCommitTimer()
  draft.value = String(value)
})

function clearCommitTimer() {
  if (commitTimer !== null) {
    clearTimeout(commitTimer)
    commitTimer = null
  }
}

function scheduleCommit() {
  if (props.disabled || props.commitDelayMs <= 0) return

  clearCommitTimer()
  commitTimer = setTimeout(() => {
    commitTimer = null
    commitInput()
  }, props.commitDelayMs)
}

function decrease() {
  if (props.disabled) return

  clearCommitTimer()

  if (quantity.value <= props.minQuantity) {
    if (props.allowRemoveAtMin && quantity.value === props.minQuantity) {
      emit('decrease-at-min')
    }
    return
  }

  quantity.value -= 1
}

function increase() {
  if (props.disabled || quantity.value >= props.maxQuantity) return

  clearCommitTimer()
  quantity.value += 1
}

function commitInput() {
  if (props.disabled) return

  const parsed = Number.parseInt(draft.value, 10)

  if (!Number.isFinite(parsed) || parsed < props.minQuantity) {
    if (parsed <= 0 && props.minQuantity === 0) {
      quantity.value = 0
      return
    }

    if (props.allowRemoveAtMin && parsed <= 0) {
      emit('decrease-at-min')
      return
    }

    draft.value = String(quantity.value)
    return
  }

  quantity.value = Math.min(parsed, props.maxQuantity)
  draft.value = String(quantity.value)
}

function onBlur() {
  clearCommitTimer()
  commitInput()
}

function onInputKeydown(event) {
  if (event.key === 'Enter') {
    event.target.blur()
  }
}

onUnmounted(clearCommitTimer)
</script>

<template>
  <div
    v-if="variant === 'shelf'"
    class="inline-flex items-stretch overflow-hidden rounded-full bg-[#3B72FF] text-white"
    :class="[
      compact ? 'h-9' : 'h-10 sm:h-11',
      compact ? 'min-w-[120px]' : 'min-w-[140px]',
      disabled ? 'opacity-60' : '',
    ]"
  >
    <button
      type="button"
      class="flex shrink-0 cursor-pointer items-center justify-center border-0 bg-transparent text-xl leading-none transition-colors hover:bg-white/10 disabled:cursor-not-allowed disabled:opacity-45"
      :class="compact ? 'w-8' : 'w-10'"
      aria-label="Уменьшить количество"
      :disabled="disabled || (!allowRemoveAtMin && quantity <= minQuantity)"
      @click="decrease"
    >
      −
    </button>

    <input
      v-model="draft"
      type="number"
      :min="allowRemoveAtMin ? 0 : minQuantity"
      :max="maxQuantity"
      class="m-1 min-w-0 flex-1 rounded-[10px] border-0 bg-[rgba(128,163,254,0.5)] text-center font-semibold text-white outline-none [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none disabled:cursor-not-allowed"
      :class="compact ? 'text-xs' : 'text-sm'"
      aria-label="Количество"
      :disabled="disabled"
      @input="scheduleCommit"
      @blur="onBlur"
      @keydown="onInputKeydown"
    />

    <button
      type="button"
      class="flex shrink-0 cursor-pointer items-center justify-center border-0 bg-transparent text-xl leading-none transition-colors hover:bg-white/10 disabled:cursor-not-allowed disabled:opacity-45"
      :class="compact ? 'w-8' : 'w-10'"
      aria-label="Увеличить количество"
      :disabled="disabled || quantity >= maxQuantity"
      @click="increase"
    >
      +
    </button>
  </div>

  <div
    v-else
    class="inline-flex items-stretch overflow-hidden rounded-lg border border-border bg-white"
    :class="[compact ? 'text-sm' : '', disabled ? 'opacity-60' : '']"
  >
    <button
      type="button"
      class="flex items-center justify-center border-0 bg-slate-50 text-slate-800 transition-colors hover:bg-slate-200 disabled:cursor-not-allowed disabled:opacity-45"
      :class="compact ? 'size-7' : 'size-9'"
      aria-label="Уменьшить количество"
      :disabled="disabled || (!allowRemoveAtMin && quantity <= minQuantity)"
      @click="decrease"
    >
      −
    </button>

    <input
      v-model="draft"
      type="number"
      :min="minQuantity"
      :max="maxQuantity"
      class="border-x border-border bg-white text-center font-semibold text-slate-800 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none disabled:cursor-not-allowed"
      :class="compact ? 'w-9 px-1 py-1 text-sm' : 'w-12 px-1 py-2'"
      aria-label="Количество"
      :disabled="disabled"
      @input="scheduleCommit"
      @blur="onBlur"
      @keydown="onInputKeydown"
    />

    <button
      type="button"
      class="flex items-center justify-center border-0 bg-slate-50 text-slate-800 transition-colors hover:bg-slate-200 disabled:cursor-not-allowed disabled:opacity-45"
      :class="compact ? 'size-7' : 'size-9'"
      aria-label="Увеличить количество"
      :disabled="disabled || quantity >= maxQuantity"
      @click="increase"
    >
      +
    </button>
  </div>
</template>
