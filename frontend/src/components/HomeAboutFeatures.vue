<script setup>
import AppIcon from '@/components/AppIcon.vue'

const features = [
  {
    icon: 'self-line',
    title: 'Собственная линия',
    text: 'Экструзия 24/7 под лазерным контролем диаметра. Не намотка из покупной нити — мы варим пластик из первичной гранулы.',
  },
  {
    icon: 'in-control',
    title: 'Входной контроль',
    text: 'Проверяем каждую партию сырья. Влажность, текучесть, чистота — не соответствующее норме в цех не попадает.',
  },
  {
    icon: 'test-print',
    title: 'Тестовая печать',
    text: 'Каждая партия проходит пробную печать на популярных принтерах. Знаем поведение материала до того, как его получите вы.',
  },
  {
    icon: 'for-biz',
    title: 'Для бизнеса и хобби',
    text: 'От катушки до паллеты. Спеццены для тиражей, контрактное производство, отсрочка платежа для постоянных.',
  },
]
</script>

<template>
  <section
    aria-label="Преимущества производства"
    class="mb-15"
  >
    <div class="flex w-full gap-4 max-lg:flex-col lg:gap-5">
      <article
        v-for="item in features"
        :key="item.icon"
        class="flex min-w-0 flex-1 items-center gap-4 rounded-[20px] bg-white px-4 py-4 sm:gap-5 sm:px-7 sm:py-7"
      >
        <div
          class="flex size-[88px] shrink-0 items-center justify-center rounded-full bg-[#3771FE] text-white"
          aria-hidden="true"
        >
          <AppIcon :name="item.icon" :size="40" />
        </div>

        <div class="min-w-0">
          <h3 class="m-0 text-[18px] leading-snug font-semibold text-[#222222]">
            {{ item.title }}
          </h3>
          <p class="m-0 mt-2.5 text-[16px] leading-[1.61] font-normal text-[#222222]">
            {{ item.text }}
          </p>
        </div>
      </article>
    </div>
  </section>
</template>
