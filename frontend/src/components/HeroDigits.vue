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
    class="hero-digits absolute right-0 bottom-0 flex bg-[#F2F7F8] rounded-tl-[20px] box-border pr-0 pb-0 p-5 gap-5"
  >
    <div class="hero-digits__item-rectangle absolute bottom-0 left-[-24px] w-[25px] h-[26px]">
      <AppIcon name="rectangle" :size="25" />
    </div>

    <div
      v-for="(digit, index) in digits"
      :key="digit.number"
      class="hero-digits__item p-6 rounded-[12px] bg-white min-w-[165px]"
    >
      <div
        class="hero-digits__item-number text-[56px] flex justify-center items-center leading-[1.1] tabular-nums"
      >
        {{ displayValues[index] }}
      </div>
      <div class="hero-digits__item-text flex flex-col gap-1 justify-center items-center ">
        <p class="text-[11px] font-bold">{{ digit.text }}</p>
        <p class="text-[11px]">{{ digit.subtitle }}</p>
      </div>
    </div>

    <div class="hero-digits__item-rectangle absolute top-[-26px] right-0 w-[25px] h-[26px]">
      <AppIcon name="rectangle" :size="25" />
    </div>
  </section>
</template>
