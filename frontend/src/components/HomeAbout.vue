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
    class="mx-auto mt-12 mb-10 max-w-[1440px] sm:mt-20 sm:mb-12 lg:mt-30 lg:mb-15"
  >
    <div
      class="flex flex-col gap-6 sm:gap-8 lg:flex-row lg:items-center lg:gap-12 xl:gap-15"
    >
      <div class="min-w-0 flex-1">
        <h2
          class="m-0 text-[22px] leading-tight font-semibold uppercase tracking-tight text-[#222222] sm:text-[26px] lg:text-[30px]"
        >
          {{ title }}
        </h2>

        <div class="mt-4 flex flex-col gap-3.5 sm:mt-6 sm:gap-5">
          <p
            v-for="(text, index) in paragraphs"
            :key="index"
            class="m-0 text-[14px] leading-relaxed text-[#222222] sm:text-[18px] sm:leading-[1.61] lg:text-[20px]"
          >
            {{ text }}
          </p>
        </div>
      </div>

      <div class="min-w-0 flex-1 lg:max-h-[448px] lg:max-w-[52%]">
        <img
          :src="imageSrc"
          :alt="imageAlt"
          class="block aspect-[4/3] h-auto w-full rounded-[16px] object-cover sm:rounded-[20px] lg:aspect-auto lg:h-full lg:max-h-[448px]"
          loading="lazy"
        />
      </div>
    </div>
  </section>
</template>
