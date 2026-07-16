<script setup>
const title = 'Ваш пластик рождается здесь'

const paragraphs = [
  'Производство полного цикла в Новокубанске. Без посредников, без перекупов, без компромиссов по качеству. От первичной гранулы до готовой катушки — всё под одной крышей.',
  'Мы не полагаемся на импортные поставки и не ждём контейнеров месяцами. Собственная линия экструзии, лазерный контроль диаметра и тестовая печать каждой партии.',
]

const imageSrc = '/images/12324.jpg'
const imageAlt = 'Производство филамента: линия экструзии и сырьё'
</script>

<template>
  <section
    id="about-us"
    aria-label="О производстве"
    class="mt-30 mb-15 max-w-[1440px] mx-auto"
  >
    <div
      class="flex items-center gap-8 lg:gap-12 xl:gap-15"
    >
      <div class="min-w-0">
        <h2
          class="m-0 text-[28px] leading-tight font-semibold uppercase tracking-tight text-[#222222] sm:text-[18px] lg:text-[30px]"
        >
          {{ title }}
        </h2>

        <div class="mt-5 flex flex-col gap-4 sm:mt-6 sm:gap-5 ">
          <p
            v-for="(text, index) in paragraphs"
            :key="index"
            class="m-0 text-sm leading-relaxed text-[#222222] sm:text-[20px] sm:leading-[1.61]"
          >
            {{ text }}
          </p>
        </div>
      </div>

      <div class="min-w-0 max-h-[448px]">
        <img
          :src="imageSrc"
          :alt="imageAlt"
          class="block h-full w-full rounded-[20px] object-cover"
          loading="lazy"
        />
      </div>
    </div>
  </section>
</template>
