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
    class="hero-slider"
  >
    <AppLoader v-if="loading" label="Загрузка слайдера..." />

    <template v-else-if="hasSlides">
      <div
        ref="viewportRef"
        class="hero-slider__viewport"
        :class="{ 'hero-slider__viewport--dragging': isDragging }"
        @pointerdown="onPointerDown"
        @pointermove="onPointerMove"
        @pointerup="onPointerUp"
        @pointercancel="onPointerCancel"
      >
        <div class="hero-slider__track" :style="trackStyle">
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

            <div class="hero-slider__content">
              <div class="hero-slider__title uppercase font-semibold" v-html="slide.title" />
              <div
                v-if="slide.subtitle"
                class="hero-slider__subtitle uppercase"
                v-html="slide.subtitle"
              />

              <component
                :is="isExternalUrl(slide.button_url) ? 'a' : RouterLink"
                v-if="slide.button_url"
                class="hero-slider__button btn"
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

      <HeroDigits />
    </template>
  </section>
</template>

<style scoped>
.hero-slider {
  position: relative;
  width: 100%;
  height: 100%;
  min-height: inherit;
}

.hero-slider__viewport {
  position: absolute;
  inset: 0;
  overflow: hidden;
  border-radius: 16px;
  cursor: grab;
  touch-action: pan-y;
}

@media (min-width: 768px) {
  .hero-slider__viewport {
    border-radius: 20px;
  }
}

.hero-slider__viewport--dragging {
  cursor: grabbing;
  touch-action: none;
  user-select: none;
}

.hero-slider__track {
  display: flex;
  height: 100%;
  will-change: transform;
}

.hero-slider__slide {
  position: relative;
  flex: 0 0 100%;
  width: 100%;
  height: 100%;
  overflow: hidden;
}

.hero-slider__image {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center;
  pointer-events: none;
  user-select: none;
}

.hero-slider__overlay {
  position: absolute;
  inset: 0;
  pointer-events: none;
  background: linear-gradient(
    180deg,
    rgba(15, 23, 42, 0.25) 0%,
    rgba(15, 23, 42, 0.55) 55%,
    rgba(15, 23, 42, 0.62) 100%
  );
}

@media (min-width: 768px) {
  .hero-slider__overlay {
    background: linear-gradient(
      90deg,
      rgba(15, 23, 42, 0.55) 0%,
      rgba(15, 23, 42, 0.2) 55%,
      transparent 100%
    );
  }
}

.hero-slider__content {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  align-items: flex-start;
  gap: 0.75rem;
  box-sizing: border-box;
  width: 100%;
  height: 100%;
  padding: 1.25rem 1.25rem 1.5rem;
  color: #fff;
}

@media (min-width: 640px) {
  .hero-slider__content {
    gap: 0.85rem;
    padding: 1.75rem 2rem 2rem;
  }
}

@media (min-width: 1024px) {
  .hero-slider__content {
    justify-content: center;
    gap: 0.75rem;
    padding: 2rem 3.75rem 3rem 3.75rem;
  }
}

.hero-slider__title {
  margin: 0;
  max-width: 14ch;
  font-size: clamp(1.75rem, 6.5vw, 3.25rem);
  line-height: 1.08;
  text-wrap: balance;
}

.hero-slider__title :deep(*) {
  margin: 0;
  font-size: inherit;
  line-height: inherit;
  font-weight: inherit;
}

.hero-slider__title :deep(p) {
  margin-top: 0.35em;
  font-size: clamp(0.875rem, 2.2vw, 1.25rem);
  line-height: 1.3;
  font-weight: 500;
}

@media (min-width: 768px) {
  .hero-slider__title {
    max-width: 15ch;
    font-size: clamp(2.5rem, 5.5vw, 4.5rem);
  }

  .hero-slider__title :deep(p) {
    font-size: clamp(1.05rem, 2vw, 1.5rem);
  }
}

@media (min-width: 1280px) {
  .hero-slider__title {
    max-width: 16ch;
    font-size: 6.125rem;
  }

  .hero-slider__title :deep(p) {
    font-size: 1.875rem;
  }
}

.hero-slider__subtitle {
  max-width: 36ch;
  font-size: clamp(0.8125rem, 1.8vw, 1.125rem);
  line-height: 1.35;
}

@media (min-width: 1024px) {
  .hero-slider__subtitle {
    max-width: 51ch;
    margin-top: 0.5rem;
  }
}

.hero-slider__button {
  height: 2.75rem;
  margin-top: 0.25rem;
  border-radius: 999px;
  padding-inline: 1.25rem;
  font-size: 0.9375rem;
}

@media (min-width: 640px) {
  .hero-slider__button {
    height: 3.125rem;
    margin-top: 0.5rem;
    padding-inline: 1.5rem;
    font-size: 1rem;
  }
}

@media (min-width: 1024px) {
  .hero-slider__button {
    margin-top: 1rem;
  }
}

.hero-slider__button-icon {
  width: 13px;
  height: 13px;
  flex-shrink: 0;
}

.hero-slider__pagination {
  position: absolute;
  top: 0.875rem;
  right: 0.875rem;
  z-index: 2;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

@media (min-width: 640px) {
  .hero-slider__pagination {
    top: 1.25rem;
    right: 1.25rem;
  }
}

@media (min-width: 1024px) {
  .hero-slider__pagination {
    top: 2.75rem;
    right: 3.125rem;
  }
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
</style>
