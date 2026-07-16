<script setup>
import { computed, ref, watch } from 'vue'
import AppIcon from '@/components/AppIcon.vue'

const props = defineProps({
  images: {
    type: Array,
    default: () => [],
  },
})

const normalizedImages = computed(() => {
  return (props.images ?? [])
    .map((img) => {
      if (!img) return null
      if (typeof img === 'string') {
        return { url: img }
      }

      return {
        url: img.url ?? img.image_url ?? null,
      }
    })
    .filter((v) => v && v.url)
})

const singleImage = computed(() => normalizedImages.value[0] ?? null)
const hasMultipleImages = computed(() => normalizedImages.value.length > 1)

const activeIndex = ref(0)
const viewportRef = ref(null)
const dragOffset = ref(0)
const isDragging = ref(false)
const didSwipe = ref(false)

let dragStartX = 0
let activePointerId = null

watch(
  () => normalizedImages.value.length,
  () => {
    activeIndex.value = 0
  },
)

function prev(e) {
  e?.stopPropagation?.()
  const total = normalizedImages.value.length
  if (total <= 1) return
  activeIndex.value = (activeIndex.value - 1 + total) % total
}

function next(e) {
  e?.stopPropagation?.()
  const total = normalizedImages.value.length
  if (total <= 1) return
  activeIndex.value = (activeIndex.value + 1) % total
}

function setIndex(i) {
  activeIndex.value = i
}

function setIndexFromPointerX(clientX) {
  const viewport = viewportRef.value
  const total = normalizedImages.value.length

  if (!viewport || total <= 1) return

  const rect = viewport.getBoundingClientRect()
  if (rect.width <= 0) return

  const x = clientX - rect.left
  const nextIndex = Math.min(total - 1, Math.max(0, Math.floor((x / rect.width) * total)))

  if (nextIndex !== activeIndex.value) {
    activeIndex.value = nextIndex
  }
}

function onMouseMove(event) {
  if (!hasMultipleImages.value) return
  setIndexFromPointerX(event.clientX)
}

function finishTouchDrag() {
  const width = viewportRef.value?.clientWidth ?? 1
  const threshold = Math.min(48, width * 0.12)

  if (dragOffset.value > threshold) {
    prev()
    didSwipe.value = true
  } else if (dragOffset.value < -threshold) {
    next()
    didSwipe.value = true
  }

  isDragging.value = false
  dragOffset.value = 0
  activePointerId = null
}

function onPointerDown(event) {
  if (!hasMultipleImages.value) return
  if (event.pointerType !== 'touch') return
  if (event.target.closest('button')) return

  isDragging.value = true
  didSwipe.value = false
  dragStartX = event.clientX
  activePointerId = event.pointerId
  viewportRef.value?.setPointerCapture(event.pointerId)
  event.stopPropagation()
}

function onPointerMove(event) {
  if (!isDragging.value || event.pointerId !== activePointerId) return

  dragOffset.value = event.clientX - dragStartX
  event.stopPropagation()
}

function onPointerUp(event) {
  if (!isDragging.value || event.pointerId !== activePointerId) return

  viewportRef.value?.releasePointerCapture(event.pointerId)
  finishTouchDrag()
  event.stopPropagation()
}

function onPointerCancel(event) {
  if (!isDragging.value || event.pointerId !== activePointerId) return

  viewportRef.value?.releasePointerCapture(event.pointerId)
  finishTouchDrag()
  event.stopPropagation()
}

function onAreaClick(event) {
  if (didSwipe.value) {
    event.stopPropagation()
    didSwipe.value = false
  }
}

function imageTransform(index) {
  if (index !== activeIndex.value || !isDragging.value) {
    return 'translate(-50%, -50%)'
  }

  return `translate(calc(-50% + ${dragOffset.value}px), -50%)`
}
</script>

<template>
  <div>
    <div
      v-if="singleImage && !hasMultipleImages"
      class="overflow-hidden rounded-xl"
    >
      <div class="relative aspect-[4/3] sm:aspect-[1/1]">
        <img
          :src="singleImage.url"
          alt="Фото товара"
          class="absolute left-1/2 top-1/2 h-[85%] w-[85%] -translate-x-1/2 -translate-y-1/2 object-contain select-none"
        />
      </div>
    </div>

    <div
      v-else-if="hasMultipleImages"
      class="relative overflow-hidden rounded-xl "
      @click.capture="onAreaClick"
    >
      <div
        ref="viewportRef"
        class="relative aspect-[4/3] touch-pan-y sm:aspect-[1/1]"
        :class="isDragging ? 'touch-none select-none' : ''"
        @mousemove="onMouseMove"
        @pointerdown="onPointerDown"
        @pointermove="onPointerMove"
        @pointerup="onPointerUp"
        @pointercancel="onPointerCancel"
      >
        <img
          v-for="(img, i) in normalizedImages"
          :key="img.url + i"
          :src="img.url"
          :alt="`Фото товара ${i + 1}`"
          class="pointer-events-none absolute left-1/2 top-1/2 h-[85%] w-[85%] object-contain select-none"
          :class="[
            i === activeIndex ? 'opacity-100' : 'opacity-0',
            isDragging ? '' : 'transition-opacity duration-200',
          ]"
          :style="{ transform: imageTransform(i) }"
        />
      </div>

      <button
        type="button"
        class="absolute left-2 top-1/2 z-10 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full border-0 bg-white/90 text-slate-900 shadow-md sm:hidden"
        aria-label="Предыдущее фото"
        @click.stop="prev($event)"
      >
        <AppIcon name="chevron-left" :size="14" />
      </button>

      <button
        type="button"
        class="absolute right-2 top-1/2 z-10 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full border-0 bg-white/90 text-slate-900 shadow-md sm:hidden"
        aria-label="Следующее фото"
        @click.stop="next($event)"
      >
        <AppIcon name="chevron-right" :size="14" />
      </button>

      <div
        class="absolute bottom-[-3px] left-1/2 z-10 flex -translate-x-1/2 items-center gap-1.5 rounded-full bg-white/90 px-2 py-1 shadow-md"
      >
        <button
          v-for="(_, i) in normalizedImages"
          :key="i"
          type="button"
          class="h-[5px] w-[5px] rounded-full border-0 bg-slate-300 transition-transform transition-colors duration-200"
          :class="i === activeIndex ? 'scale-110 !bg-[#3771FE] h-[6px] w-[6px]' : ''"
          :aria-label="`Фото ${i + 1}`"
          @click.stop="setIndex(i)"
        />
      </div>
    </div>

    <div
      v-else
      class="flex aspect-[4/3] items-center justify-center rounded-xl bg-[#F2F7F8] sm:aspect-square"
    >
      <span class="text-xs text-slate-500">Нет фото</span>
    </div>
  </div>
</template>
