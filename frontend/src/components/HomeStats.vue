<script setup>
const stats = [
  {
    index: 1,
    value: '>1 млн.',
    text: 'моделей напечатано нашим филаментом',
  },
  {
    index: 2,
    value: '>10 000',
    text: 'килограмм чистейшего сырья перерабатывается в нить',
  },
  {
    index: 3,
    value: '5',
    text: 'лет опыта в инженерии полимеров: мы знаем, как пластик ведет себя на печати',
  },
  {
    index: 4,
    value: '100%',
    text: 'сырья полностью высушено перед производством (обезвожено)',
  },
]
</script>

<template>
  <section aria-label="Показатели компании" class="my-10 lg:my-15">
    <div
      class="grid grid-cols-1 gap-8 rounded-[20px] bg-white px-6 py-4 sm:grid-cols-2 sm:gap-x-6 sm:gap-y-10 sm:px-6 sm:py-10 lg:flex lg:items-center lg:justify-between lg:gap-11 lg:rounded-[20px] lg:px-8 lg:py-5.5 xl:px-10"
    >
      <div
        v-for="(item, index) in stats"
        :key="index"
        class="stats__item flex min-w-0 items-center gap-4 sm:gap-3.5  xl:max-w-none" :class="`stats__item--${item.index}`"
      >
        <p
          class="shrink-0 text-[40px] leading-none font-regular tracking-tight text-[#222222] sm:text-[44px] lg:text-[56px]"
        >
          {{ item.value }}
        </p>
        <p
          class="stats__item-text max-w-[245px] text-xs leading-snug text-[#222222] sm:text-[14px]"
        >
          {{ item.text }}
        </p>
      </div>
    </div>
  </section>
</template>
<style scoped>

.stats__item-text--3{
  max-width: 170px;
}

</style>