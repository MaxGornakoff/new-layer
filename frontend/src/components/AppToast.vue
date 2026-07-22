<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useToastStore } from '@/stores/toast'

const toast = useToastStore()
const layoutTick = ref(0)

const typeLabel = {
  success: 'Готово',
  error: 'Ошибка',
  warning: 'Внимание',
  info: 'Подсказка',
}

const typeClasses = {
  success: 'border-emerald-300 bg-emerald-50',
  error: 'border-red-300 bg-red-50',
  warning: 'border-amber-300 bg-amber-50',
  info: 'border-sky-300 bg-sky-50',
}

const toastCardClass =
  'pointer-events-auto flex max-w-[min(360px,calc(100vw-1rem))] items-start gap-3 rounded-2xl border px-4 py-3.5 shadow-[0_12px_32px_rgba(15,23,42,0.14)]'

function bumpLayout() {
  layoutTick.value += 1
}

onMounted(() => {
  window.addEventListener('scroll', bumpLayout, true)
  window.addEventListener('resize', bumpLayout)
})

onUnmounted(() => {
  window.removeEventListener('scroll', bumpLayout, true)
  window.removeEventListener('resize', bumpLayout)
})

function anchorStyle(item) {
  // зависимость от layoutTick, чтобы пересчитывать при скролле/ресайзе
  void layoutTick.value

  const el = item.anchor
  if (!(el instanceof Element) || !el.isConnected) {
    return { display: 'none' }
  }

  const rect = el.getBoundingClientRect()
  const gap = 8
  const maxWidth = Math.min(360, window.innerWidth - 16)

  let left = rect.left
  left = Math.min(left, window.innerWidth - maxWidth - 8)
  left = Math.max(8, left)

  return {
    top: `${Math.round(rect.bottom + gap)}px`,
    left: `${Math.round(left)}px`,
    width: `${Math.round(Math.min(Math.max(rect.width, 220), maxWidth))}px`,
  }
}

const anchoredWithStyle = computed(() =>
  toast.anchoredItems.map((item) => ({
    item,
    style: anchorStyle(item),
  })),
)
</script>

<template>
  <!-- Глобальные тосты — правый нижний угол -->
  <div
    class="pointer-events-none fixed right-4 bottom-4 z-[400] grid w-[min(380px,calc(100vw-2rem))] gap-3"
    aria-live="polite"
    aria-relevant="additions"
  >
    <TransitionGroup
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="translate-y-2 opacity-0"
      enter-to-class="translate-y-0 opacity-100"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="translate-y-0 opacity-100"
      leave-to-class="translate-y-2 opacity-0"
    >
      <div
        v-for="item in toast.stackedItems"
        :key="item.id"
        :class="[toastCardClass, typeClasses[item.type] || 'border-slate-200 bg-white']"
        role="status"
      >
        <div class="min-w-0 flex-1">
          <p class="m-0 mb-0.5 text-[15px] font-semibold text-slate-900">
            {{ typeLabel[item.type] || 'Уведомление' }}
          </p>
          <p class="m-0 text-[14px] leading-snug text-slate-600">
            {{ item.message }}
          </p>
        </div>
        <button
          type="button"
          class="flex size-6 shrink-0 cursor-pointer items-center justify-center rounded-full border-0 bg-slate-900/5 text-base leading-none text-slate-600 hover:bg-slate-900/10"
          aria-label="Закрыть"
          @click="toast.remove(item.id)"
        >
          ×
        </button>
      </div>
    </TransitionGroup>
  </div>

  <!-- Тосты у полей формы -->
  <div class="pointer-events-none" aria-live="polite">
    <TransitionGroup
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="translate-y-1 opacity-0"
      enter-to-class="translate-y-0 opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-for="{ item, style } in anchoredWithStyle"
        :key="item.id"
        :class="[toastCardClass, 'fixed z-[400]', typeClasses[item.type] || 'border-slate-200 bg-white']"
        :style="style"
        role="status"
      >
        <div class="min-w-0 flex-1">
          <p class="m-0 mb-0.5 text-[15px] font-semibold text-slate-900">
            {{ typeLabel[item.type] || 'Уведомление' }}
          </p>
          <p class="m-0 text-[14px] leading-snug text-slate-600">
            {{ item.message }}
          </p>
        </div>
        <button
          type="button"
          class="pointer-events-auto flex size-6 shrink-0 cursor-pointer items-center justify-center rounded-full border-0 bg-slate-900/5 text-base leading-none text-slate-600 hover:bg-slate-900/10"
          aria-label="Закрыть"
          @click="toast.remove(item.id)"
        >
          ×
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>
