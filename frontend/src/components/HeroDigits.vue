<script setup>
import { onMounted, onUnmounted, ref } from 'vue'
import AppIcon from '@/components/AppIcon.vue'

const digits = [
  { number: 20, text: 'микрон', subtitle: 'допуск диаметра' },
  { number: 5, text: 'лет', subtitle: 'полной гарантии' },
  { number: 100, text: 'процентов', subtitle: 'выходного теста' },
]

const sectionRef = ref(null)
const displayValues = ref(digits.map(() => 0))

const DURATION_MS = 900

let observer = null
let hasAnimated = false
let animationFrame = null

function runCountUp() {
  if (hasAnimated) return
  hasAnimated = true

  const start = performance.now()

  function tick(now) {
    const progress = Math.min((now - start) / DURATION_MS, 1)
    const eased = 1 - (1 - progress) ** 3

    displayValues.value = digits.map((digit) => Math.round(digit.number * eased))

    if (progress < 1) {
      animationFrame = requestAnimationFrame(tick)
    } else {
      displayValues.value = digits.map((digit) => digit.number)
    }
  }

  animationFrame = requestAnimationFrame(tick)
}

onMounted(() => {
  if (!sectionRef.value) return

  observer = new IntersectionObserver(
    ([entry]) => {
      if (entry.isIntersecting) {
        runCountUp()
        observer?.disconnect()
      }
    },
    { threshold: 0.2 },
  )

  observer.observe(sectionRef.value)
})

onUnmounted(() => {
  observer?.disconnect()
  if (animationFrame) cancelAnimationFrame(animationFrame)
})
</script>

<template>
  <section
    ref="sectionRef"
    class="hero-digits"
    aria-label="Ключевые показатели"
  >
    <div class="hero-digits__corner hero-digits__corner--bl" aria-hidden="true">
      <AppIcon name="rectangle" :size="25" />
    </div>

    <div
      v-for="(digit, index) in digits"
      :key="digit.number"
      class="hero-digits__item"
    >
      <div class="hero-digits__number tabular-nums">
        {{ displayValues[index] }}
      </div>
      <div class="hero-digits__text">
        <p class="hero-digits__label">{{ digit.text }}</p>
        <p class="hero-digits__subtitle">{{ digit.subtitle }}</p>
      </div>
    </div>

    <div class="hero-digits__corner hero-digits__corner--tr" aria-hidden="true">
      <AppIcon name="rectangle" :size="25" />
    </div>
  </section>
</template>

<style scoped>
.hero-digits {
  display: none;
}

@media (min-width: 1024px) {
  .hero-digits {
    position: absolute;
    right: 0;
    bottom: 0;
    z-index: 3;
    display: flex;
    gap: 1.25rem;
    box-sizing: border-box;
    padding: 1.25rem 0 0 1.25rem;
    border-top-left-radius: 20px;
    background: #f2f7f8;
    pointer-events: none;
  }
}

.hero-digits__item {
  min-width: 165px;
  padding: 1.5rem;
  border-radius: 12px;
  background: #fff;
}

.hero-digits__number {
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 56px;
  line-height: 1.1;
}

.hero-digits__text {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.25rem;
}

.hero-digits__label,
.hero-digits__subtitle {
  margin: 0;
  font-size: 11px;
  line-height: 1.2;
  text-align: center;
}

.hero-digits__label {
  font-weight: 700;
}

.hero-digits__corner {
  position: absolute;
  width: 25px;
  height: 26px;
  color: #f2f7f8;
}

.hero-digits__corner--bl {
  bottom: -1px;
  left: -24px;
}

.hero-digits__corner--tr {
  top: -25px;
  right: -1px;
}

@media (max-width: 1279px) {
  .hero-digits {
    gap: 0.75rem;
    padding: 0.85rem 0 0 0.85rem;
  }

  .hero-digits__item {
    min-width: 120px;
    padding: 0.85rem 0.75rem;
  }

  .hero-digits__number {
    font-size: 36px;
  }

  .hero-digits__label,
  .hero-digits__subtitle {
    font-size: 10px;
  }

  .hero-digits__corner {
    display: none;
  }
}
</style>
