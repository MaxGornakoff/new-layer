<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import api from '@/api/client'
import AppIcon from '@/components/AppIcon.vue'
import { useSiteStore } from '@/stores/site'

const site = useSiteStore()
const categories = ref([])

const year = new Date().getFullYear()

const company = {
  name: 'ООО\n«НазваниеКомпании»',
  ogrn: 'ОГРНИП / ОГРН 1234567890123',
}

const serviceLinks = [
  { label: 'Доставка и оплата', to: '/delivery' },
  { label: 'Возврат и обмен', to: '/delivery' },
  { label: 'Гарантия качества', to: '/#about-us' },
  { label: 'Частые вопросы', to: '/delivery' },
]

const businessLinks = [
  { label: 'Оптовый прайс-лист', to: '/wholesale' },
  { label: 'Маркетплейсы', to: '/wholesale' },
  { label: 'Отсрочка платежа', to: '/wholesale' },
  { label: 'Реквизиты компании', to: '/wholesale' },
]

const contacts = {
  phone: '+7 (999) 123-45-67',
  phoneHref: 'tel:+79991234567',
  emails: [
    { address: 'opt@site.ru', note: '(для юрлиц)' },
    { address: 'help@site.ru', note: '(поддержка)' },
  ],
  socials: [
    { name: 'Telegram', icon: 'telegram', href: 'https://t.me/' },
    { name: 'MAX', icon: 'max', href: 'https://max.com/' },
  ],
}

const legalLinks = [
  { label: 'Политика обработки персональных данных', to: '/legal/privacy-policy' },
  { label: 'Политика использования cookie', to: '/legal/cookie-policy' },
  { label: 'Согласие на обработку персональных данных', to: '/legal/personal-data-consent' },
]

const categoryLinks = computed(() =>
  categories.value.map((category) => ({
    label: category.name,
    to: `/catalog?category=${category.slug}`,
  })),
)

onMounted(async () => {
  try {
    const { data } = await api.get('/categories')
    categories.value = data.data ?? []
  } catch {
    categories.value = []
  }
})
</script>

<template>
  <footer class="site-footer mt-7.5 bg-white rounded-[20px] pt-6 pb-8.5 px-5">
    <div class="box">
      <div
        class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 xl:gap-6"
      >
        <div class="min-w-0">
          <RouterLink to="/" class="inline-flex items-center no-underline text-[#222222]">
            <img
              v-if="site.logoUrl"
              :src="site.logoUrl"
              alt="Новый слой"
              class="h-10 w-auto max-w-[180px] object-contain object-left"
            />
            <span v-else class="text-lg font-semibold uppercase tracking-tight">Новый слой</span>
          </RouterLink>

          <p class="m-0 mt-4 whitespace-pre-line text-[13px] leading-snug text-[#222222]">
            © {{ year }} {{ company.name }}
          </p>
          <p class="m-0 mt-1 text-[13px] leading-snug text-[#222222]">
            {{ company.ogrn }}
          </p>
        </div>

        <nav aria-label="Категории" class="min-w-0">
          <ul class="m-0 flex list-none flex-col gap-2.5 p-0">
            <li v-for="link in categoryLinks" :key="link.to">
              <RouterLink
                :to="link.to"
                class="text-[14px] leading-snug text-[#222222] no-underline transition-opacity hover:opacity-70"
              >
                {{ link.label }}
              </RouterLink>
            </li>
          </ul>
        </nav>

        <nav aria-label="Покупателям" class="min-w-0">
          <ul class="m-0 flex list-none flex-col gap-2.5 p-0">
            <li v-for="link in serviceLinks" :key="link.label">
              <RouterLink
                :to="link.to"
                class="text-[14px] leading-snug text-[#222222] no-underline transition-opacity hover:opacity-70"
              >
                {{ link.label }}
              </RouterLink>
            </li>
          </ul>
        </nav>

        <nav aria-label="Для бизнеса" class="min-w-0">
          <ul class="m-0 flex list-none flex-col gap-2.5 p-0">
            <li v-for="link in businessLinks" :key="link.label">
              <RouterLink
                :to="link.to"
                class="text-[14px] leading-snug text-[#222222] no-underline transition-opacity hover:opacity-70"
              >
                {{ link.label }}
              </RouterLink>
            </li>
          </ul>
        </nav>

        <div class="min-w-0">
          <ul class="m-0 flex list-none flex-col gap-2.5 p-0 text-[14px] leading-snug text-[#222222]">
            <li>
              <a
                :href="contacts.phoneHref"
                class="text-inherit no-underline transition-opacity hover:opacity-70"
              >
                {{ contacts.phone }}
              </a>
            </li>
            <li v-for="email in contacts.emails" :key="email.address">
              <a
                :href="`mailto:${email.address}`"
                class="text-inherit no-underline transition-opacity hover:opacity-70"
              >
                {{ email.address }}
              </a>
              <span class="text-[#64748b]"> {{ email.note }}</span>
            </li>
          </ul>

          <div class="mt-4 flex items-center gap-2.5">
            <a
              v-for="social in contacts.socials"
              :key="social.name"
              :href="social.href"
              :aria-label="social.name"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex size-9 items-center justify-center rounded-full bg-[#222222] text-white transition-opacity hover:opacity-80"
            >
              <AppIcon :name="social.icon" :size="18" />
            </a>
          </div>
        </div>

        <nav aria-label="Документы" class="min-w-0">
          <ul class="m-0 flex list-none flex-col gap-2.5 p-0">
            <li v-for="link in legalLinks" :key="link.label">
              <RouterLink
                :to="link.to"
                class="text-[14px] leading-snug text-[#222222] no-underline transition-opacity hover:opacity-70"
              >
                {{ link.label }}
              </RouterLink>
            </li>
          </ul>
        </nav>
      </div>
    </div>
  </footer>
</template>
