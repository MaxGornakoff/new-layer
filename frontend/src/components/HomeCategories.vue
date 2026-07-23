<script setup>
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import api from '@/api/client'
import AppLoader from '@/components/AppLoader.vue'

const categories = ref([])
const loading = ref(true)

const DEFAULT_BG_START = '#ff8f6b'
const DEFAULT_BG_END = '#ff6b3d'

onMounted(async () => {
  try {
    const { data } = await api.get('/categories')
    categories.value = data.data
  } finally {
    loading.value = false
  }
})

function categoryLabel(category) {
  return category.home_title || category.name
}

function categoryStyle(category) {
  const start = category.home_bg_color || DEFAULT_BG_START
  const end = category.home_bg_color_end || category.home_bg_color || DEFAULT_BG_END

  return {
    background: `linear-gradient(to top right, ${start}, ${end})`,
  }
}

function categoryAdvantages(category) {
  return (category.advantages || []).filter((item) => item.text?.trim())
}
</script>

<template>
  <section id="categories">
    <h2 class="my-6 text-2xl font-semibold uppercase sm:my-8 sm:text-[28px] lg:my-10 lg:text-3xl">
      Категории пластика
    </h2>
    <AppLoader v-if="loading" label="Загрузка категорий..." />
    <div v-else-if="categories.length" class="flex gap-4 max-md:flex-col justify-between sm:gap-5">
      <RouterLink
        v-for="category in categories"
        :key="category.id"
        :to="`/catalog?category=${category.slug}`"
        class="relative flex min-h-[240px] flex-1 items-stretch overflow-hidden rounded-[16px] text-white no-underline transition duration-200 ease-out hover:-translate-y-1 hover:shadow-[0_12px_32px_rgba(15,23,42,0.12)] max-md:min-h-0 sm:rounded-[20px] sm:min-h-[280px]"
        :style="categoryStyle(category)"
      >
        <span
          class="pointer-events-none absolute top-[7%] right-[-40%] z-1 size-[500px] rounded-full bg-white/20 max-md:top-auto max-md:-right-12 max-md:bottom-16 max-md:size-48"
          aria-hidden="true"
        />

        <div class="z-4 flex flex-col items-start justify-between gap-6 p-5 sm:p-7">
          <ul
            v-if="categoryAdvantages(category).length"
            class="m-0 flex list-none flex-col gap-3 p-0 sm:gap-[15px]"
          >
            <li
              v-for="(advantage, index) in categoryAdvantages(category)"
              :key="index"
              class="flex items-center gap-3 text-[14px] leading-[1.2] sm:gap-3.5 sm:text-[15px]"
            >
              <img
                v-if="advantage.icon_url"
                :src="advantage.icon_url"
                alt=""
                class="size-5 shrink-0 object-contain sm:size-[25px]"
              />
              <span class="text-[14px] sm:text-base">{{ advantage.text }}</span>
            </li>
          </ul>

          <span
            class="inline-flex rounded-full bg-white px-5 py-2 text-sm text-[#222222] sm:px-7.5 sm:text-base"
          >
            {{ categoryLabel(category) }}
          </span>
        </div>

        <div
          v-if="category.image_url"
          class="pointer-events-none absolute top-[17%] right-[-20%] z-3 flex h-[330px] max-w-[280px] items-end justify-center max-md:right-[5%] max-md:top-[10%] max-md:mx-auto max-md:h-auto max-md:max-h-[180px] max-md:w-full max-md:max-w-[130px]"
        >
          <img
            :src="category.image_url"
            :alt="category.name"
            class="block h-full max-h-full w-auto max-w-full object-contain object-bottom"
            loading="lazy"
          />
        </div>
      </RouterLink>
    </div>
  </section>
</template>
