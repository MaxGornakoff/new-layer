<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()

const pages = {
  'personal-data-consent': {
    title: 'Согласие на обработку персональных данных',
    lead: 'Текст согласия будет размещён здесь после согласования с заказчиком.',
  },
  'privacy-policy': {
    title: 'Политика обработки персональных данных',
    lead: 'Текст политики будет размещён здесь после согласования с заказчиком.',
  },
  'cookie-policy': {
    title: 'Политика использования cookie',
    lead: 'Текст политики cookie будет размещён здесь после согласования с заказчиком.',
  },
}

const page = computed(() => pages[route.params.slug] ?? null)
</script>

<template>
  <section class="mx-auto my-8 w-full max-w-[800px] sm:my-10 lg:my-12">
    <template v-if="page">
      <h1 class="m-0 text-2xl font-semibold uppercase leading-tight sm:text-[28px] lg:text-3xl">
        {{ page.title }}
      </h1>
      <div class="card mt-6 rounded-[20px] border-0 p-5 sm:p-7">
        <p class="m-0 text-[15px] leading-relaxed text-[#475569] sm:text-base">
          {{ page.lead }}
        </p>
      </div>
    </template>
    <p v-else class="muted">Документ не найден.</p>
  </section>
</template>
