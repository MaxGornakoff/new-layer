<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import api from '@/api/client'
import AppLoader from '@/components/AppLoader.vue'
import { stripHtml } from '@/lib/stripHtml'
import HeroDigits from '@/components/HeroDigits.vue'

const slides = ref([])
const loading = ref(true)
const activeIndex = ref(0)
const autoplayEnabled = ref(true)
const autoplayIntervalSeconds = ref(6)
const viewportRef = ref(null)
const dragOffset = ref(0)
const isDragging = ref(false)

let autoplayTimer = null
let dragStartX = 0
let activePointerId = null

const hasSlides = computed(() => slides.value.length > 0)
const showControls = computed(() => slides.value.length > 1)

const trackStyle = computed(() => ({
  transform: `translateX(calc(-${activeIndex.value * 100}% + ${dragOffset.value}px))`,
  transition: isDragging.value ? 'none' : 'transform 0.45s ease',
}))

function isExternalUrl(url) {
  return /^https?:\/\//i.test(url)
}

function goTo(index) {
  if (!slides.value.length) return
  activeIndex.value = (index + slides.value.length) % slides.value.length
}

function next() {
  goTo(activeIndex.value + 1)
}

function applySliderMeta(meta) {
  autoplayEnabled.value = meta?.autoplay !== false && meta?.autoplay !== 0 && meta?.autoplay !== '0'
  const interval = Number(meta?.autoplay_interval_seconds)
  autoplayIntervalSeconds.value = Number.isFinite(interval) && interval >= 2 ? interval : 6
}

function getAutoplayDelayMs() {
  const interval = Number(autoplayIntervalSeconds.value)
  return Number.isFinite(interval) && interval >= 2 ? interval * 1000 : 6000
}

function startAutoplay() {
  stopAutoplay()
  if (!showControls.value || !autoplayEnabled.value || isDragging.value || document.hidden) return

  autoplayTimer = window.setTimeout(() => {
    next()
    startAutoplay()
  }, getAutoplayDelayMs())
}

function stopAutoplay() {
  if (autoplayTimer) {
    window.clearTimeout(autoplayTimer)
    autoplayTimer = null
  }
}

function finishDrag() {
  const width = viewportRef.value?.clientWidth ?? 1
  const threshold = Math.min(60, width * 0.12)

  if (dragOffset.value > threshold) {
    goTo(activeIndex.value - 1)
  } else if (dragOffset.value < -threshold) {
    goTo(activeIndex.value + 1)
  }

  isDragging.value = false
  dragOffset.value = 0
  activePointerId = null
  startAutoplay()
}

function onPointerDown(event) {
  if (!showControls.value) return
  if (event.pointerType === 'mouse' && event.button !== 0) return
  if (event.target.closest('a, button')) return

  isDragging.value = true
  dragStartX = event.clientX
  activePointerId = event.pointerId
  viewportRef.value?.setPointerCapture(event.pointerId)
  stopAutoplay()
}

function onPointerMove(event) {
  if (!isDragging.value || event.pointerId !== activePointerId) return

  dragOffset.value = event.clientX - dragStartX
}

function onPointerUp(event) {
  if (!isDragging.value || event.pointerId !== activePointerId) return

  viewportRef.value?.releasePointerCapture(event.pointerId)
  finishDrag()
}

function onPointerCancel(event) {
  if (!isDragging.value || event.pointerId !== activePointerId) return

  viewportRef.value?.releasePointerCapture(event.pointerId)
  finishDrag()
}

async function loadSlides() {
  loading.value = true
  try {
    const { data } = await api.get('/hero-slides')
    slides.value = data.data
    applySliderMeta(data.meta)
    activeIndex.value = 0
  } finally {
    loading.value = false
    startAutoplay()
  }
}

function onVisibilityChange() {
  if (document.hidden) {
    stopAutoplay()
  } else {
    startAutoplay()
  }
}

watch([autoplayEnabled, autoplayIntervalSeconds, showControls], () => {
  startAutoplay()
})

onMounted(() => {
  loadSlides()
  document.addEventListener('visibilitychange', onVisibilityChange)
})
onUnmounted(() => {
  stopAutoplay()
  document.removeEventListener('visibilitychange', onVisibilityChange)
})
</script>

<template>
  <section
    v-if="loading || hasSlides"
    class="hero-slider h-full"
  >
    <AppLoader v-if="loading" label="Загрузка слайдера..." />

    <template v-else-if="hasSlides">
      <div
        ref="viewportRef"
        class="hero-slider__viewport h-full"
        :class="{ 'hero-slider__viewport--dragging': isDragging }"
        @pointerdown="onPointerDown"
        @pointermove="onPointerMove"
        @pointerup="onPointerUp"
        @pointercancel="onPointerCancel"
      >
        <div class="hero-slider__track h-full" :style="trackStyle">
          <article
            v-for="slide in slides"
            :key="slide.id"
            class="hero-slider__slide"
          >
            <img
              :src="slide.image_url"
              :alt="stripHtml(slide.title)"
              class="hero-slider__image"
              draggable="false"
              loading="lazy"
            />

            <div class="hero-slider__overlay" />

            <div class="hero-slider__content box pl-15">
              <div class="hero-slider__title uppercase font-semibold" v-html="slide.title" />
              <div
                v-if="slide.subtitle"
                class="hero-slider__subtitle uppercase mt-9"
                v-html="slide.subtitle"
              />

              <component
                :is="isExternalUrl(slide.button_url) ? 'a' : RouterLink"
                v-if="slide.button_url"
                class="hero-slider__button btn h-12.5 mt-8 rounded-full"
                :href="isExternalUrl(slide.button_url) ? slide.button_url : undefined"
                :to="isExternalUrl(slide.button_url) ? undefined : slide.button_url"
                :target="isExternalUrl(slide.button_url) ? '_blank' : undefined"
                :rel="isExternalUrl(slide.button_url) ? 'noopener noreferrer' : undefined"
              >
                <span>{{ slide.button_text || 'Подробнее' }}</span>
                <svg
                  class="hero-slider__button-icon"
                  aria-hidden="true"
                  focusable="false"
                >
                  <use href="#arrow" />
                </svg>
              </component>
            </div>
          </article>
        </div>
      </div>

      <div v-if="showControls" class="hero-slider__pagination">
        <button
          v-for="(slide, index) in slides"
          :key="slide.id"
          type="button"
          class="hero-slider__dot"
          :class="{ 'hero-slider__dot--active': index === activeIndex }"
          :aria-label="`Слайд ${index + 1}`"
          @click="goTo(index); startAutoplay()"
        />
      </div>
      <HeroDigits class="home__hero-digits" />
    </template>
  </section>
</template>

<style scoped>
.hero-slider {
  position: relative;
  width: 100%;
  margin-bottom: 1.5rem;
}

.hero-slider__viewport {
  overflow: hidden;
  border-radius: 20px;
  cursor: grab;
  touch-action: pan-y;
}

.hero-slider__viewport--dragging {
  cursor: grabbing;
  touch-action: none;
  user-select: none;
}

.hero-slider__track {
  display: flex;
}

.hero-slider__slide {
  position: relative;
  flex: 0 0 100%;
  min-height: clamp(220px, 42vw, 520px);
  overflow: hidden;
}

.hero-slider__image {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  pointer-events: none;
  user-select: none;
}

.hero-slider__overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg, rgba(15, 23, 42, 0.55) 0%, rgba(15, 23, 42, 0.15) 55%, transparent 100%);
  pointer-events: none;
}

.hero-slider__content {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: flex-start;
  gap: 0.75rem;
  min-height: clamp(220px, 42vw, 520px);
  padding-top: 2rem;
  padding-bottom: 3rem;
  color: #fff;
}

.hero-slider__title {
  margin: 0;
  max-width: 16ch;
  font-size: 98px;
  line-height: 1.15;

}

.hero-slider__title :deep(h2) {
  font-size: 98px;
}

.hero-slider__title :deep(p) {
  font-size: 30px;
}

.hero-slider__subtitle {
  max-width: 51ch;
  font-size: clamp(0.95rem, 2vw, 1.125rem);
  line-height: 1.3;
}

.hero-slider__button-icon {
  width: 13px;
  height: 13px;
  flex-shrink: 0;
}

.hero-slider__pagination {
  position: absolute;
  left: auto;
  right: 50px;
  bottom: auto;
  z-index: 2;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transform: translateX(-50%);
  top: 44px;
}

.hero-slider__dot {
  width: 5px;
  height: 5px;
  padding: 0;
  border: none;
  border-radius: 999px;
  background: #fff;
  opacity: 0.5;
  cursor: pointer;
  transition:
    opacity 0.2s ease,
    width 0.2s ease,
    height 0.2s ease;
}

.hero-slider__dot--active {
  width: 10px;
  height: 10px;
  opacity: 1;
}

@media (max-width: 768px) {
  .hero-slider__viewport {
    border-radius: 16px;
  }

  .hero-slider__content {
    padding-top: 1.5rem;
    padding-bottom: 2.5rem;
  }
}
</style>
