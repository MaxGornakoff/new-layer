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
    class="mb-10 sm:mb-12 lg:mb-15"
  >
    <div class="grid w-full grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:flex lg:gap-5">
      <article
        v-for="item in features"
        :key="item.icon"
        class="flex min-w-0 flex-1 items-start gap-3.5 rounded-[16px] bg-white px-4 py-4 sm:items-center sm:gap-5 sm:rounded-[20px] sm:px-6 sm:py-6 lg:px-7 lg:py-7"
      >
        <div
          class="flex size-14 shrink-0 items-center justify-center rounded-full bg-[#3771FE] text-white sm:size-[72px] lg:size-[88px]"
          aria-hidden="true"
        >
          <AppIcon :name="item.icon" class="feature-icon" :size="40" />
        </div>

        <div class="min-w-0">
          <h3 class="m-0 text-[16px] leading-snug font-semibold text-[#222222] sm:text-[18px]">
            {{ item.title }}
          </h3>
          <p class="m-0 mt-1.5 text-[14px] leading-[1.5] font-normal text-[#222222] sm:mt-2.5 sm:text-[15px] sm:leading-[1.61] lg:text-[16px]">
            {{ item.text }}
          </p>
        </div>
      </article>
    </div>
  </section>
</template>

<style scoped>
.feature-icon {
  width: 28px !important;
  height: 28px !important;
}

@media (min-width: 640px) {
  .feature-icon {
    width: 36px !important;
    height: 36px !important;
  }
}

@media (min-width: 1024px) {
  .feature-icon {
    width: 40px !important;
    height: 40px !important;
  }
}
</style>
