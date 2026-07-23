<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import api from '@/api/client'
import AppIcon from '@/components/AppIcon.vue'
import { phoneToTelHref, useSiteStore } from '@/stores/site'

const site = useSiteStore()
const categories = ref([])
const menuSections = ref([])

const year = new Date().getFullYear()

const company = {
  name: 'ООО\n«НазваниеКомпании»',
  ogrn: 'ОГРНИП / ОГРН 1234567890123',
}

const linkClass =
  'text-[14px] leading-snug text-[#222222] no-underline transition-opacity hover:opacity-70'

const titleClass =
  'block m-0 mb-2.5 text-[13px] font-semibold uppercase tracking-wide text-[#222222]'

const phoneHref = computed(() => phoneToTelHref(site.contactPhone))

const contactEmails = computed(() => {
  const emails = []
  if (site.contactEmailBusiness) {
    emails.push({ address: site.contactEmailBusiness, note: '(для юрлиц)' })
  }
  if (site.contactEmailSupport) {
    emails.push({ address: site.contactEmailSupport, note: '(поддержка)' })
  }
  return emails
})

const hasContacts = computed(
  () =>
    Boolean(site.contactPhone) ||
    contactEmails.value.length > 0 ||
    site.contactMessengers.length > 0,
)

function isExternalUrl(url) {
  return /^https?:\/\//i.test(url || '')
}

function isDropdown(section) {
  return section.type === 'dropdown'
}

function sectionSubitems(section) {
  const fromCategories = section.include_categories
    ? categories.value.map((category) => ({
        id: `cat-${category.id}`,
        title: category.name,
        url: `/catalog?category=${category.slug}`,
        openInNewTab: false,
      }))
    : []

  const fromChildren = (section.children || []).map((item) => ({
    id: `menu-${item.id}`,
    title: item.title,
    url: item.url,
    openInNewTab: Boolean(item.open_in_new_tab),
  }))

  return [...fromCategories, ...fromChildren]
}

function hasSubitems(section) {
  return isDropdown(section) && sectionSubitems(section).length > 0
}

/** Колонки/пункты футера в том же смысле, что разделы шапки. */
const footerSections = computed(() =>
  menuSections.value.filter((section) => {
    if (isDropdown(section)) {
      return hasSubitems(section) || Boolean(section.url)
    }
    return Boolean(section.url)
  }),
)

onMounted(async () => {
  try {
    const [categoriesResponse, menuResponse] = await Promise.all([
      api.get('/categories'),
      api.get('/menu', { params: { placement: 'footer' } }),
    ])
    categories.value = categoriesResponse.data.data ?? []
    menuSections.value = menuResponse.data.data ?? []
  } catch {
    categories.value = []
    menuSections.value = []
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

          <p class="m-0 mt-4 whitespace-pre-line text-[13px] leading-[2em] text-[#222222]">
            © {{ year }} {{ company.name }}
          </p>
          <p class="m-0 mt-2.5 text-[13px] leading-snug text-[#222222]">
            {{ company.ogrn }}
          </p>
        </div>

        <nav
          v-for="section in footerSections"
          :key="section.id"
          :aria-label="section.title"
          class="min-w-0"
        >
          <!-- Как в шапке: тип «ссылка» — один пункт верхнего уровня -->
          <template v-if="section.type === 'link' && section.url">
            <a
              v-if="isExternalUrl(section.url)"
              :href="section.url"
              :class="linkClass"
              :target="section.open_in_new_tab ? '_blank' : undefined"
              :rel="section.open_in_new_tab ? 'noopener noreferrer' : undefined"
            >
              {{ section.title }}
            </a>
            <RouterLink
              v-else
              :to="section.url"
              :class="linkClass"
              :target="section.open_in_new_tab ? '_blank' : undefined"
            >
              {{ section.title }}
            </RouterLink>
          </template>

          <!-- Тип «dropdown» — заголовок колонки + вложенные пункты -->
          <template v-else-if="isDropdown(section)">
            <a
              v-if="section.url && isExternalUrl(section.url)"
              :href="section.url"
              :class="titleClass"
              :target="section.open_in_new_tab ? '_blank' : undefined"
              :rel="section.open_in_new_tab ? 'noopener noreferrer' : undefined"
            >
              {{ section.title }}
            </a>
            <RouterLink
              v-else-if="section.url"
              :to="section.url"
              :class="[titleClass, 'no-underline transition-opacity hover:opacity-70']"
              :target="section.open_in_new_tab ? '_blank' : undefined"
            >
              {{ section.title }}
            </RouterLink>
            <p v-else :class="titleClass">{{ section.title }}</p>

            <ul
              v-if="hasSubitems(section)"
              class="m-0 flex list-none flex-col gap-2.5 p-0"
            >
              <li v-for="item in sectionSubitems(section)" :key="item.id">
                <a
                  v-if="isExternalUrl(item.url)"
                  :href="item.url"
                  :class="linkClass"
                  :target="item.openInNewTab ? '_blank' : undefined"
                  :rel="item.openInNewTab ? 'noopener noreferrer' : undefined"
                >
                  {{ item.title }}
                </a>
                <RouterLink
                  v-else
                  :to="item.url"
                  :class="linkClass"
                  :target="item.openInNewTab ? '_blank' : undefined"
                >
                  {{ item.title }}
                </RouterLink>
              </li>
            </ul>
          </template>
        </nav>

        <div v-if="hasContacts" class="min-w-0">
          <ul class="m-0 flex list-none flex-col gap-2.5 p-0 text-[14px] leading-snug text-[#222222]">
            <li v-if="site.contactPhone">
              <a
                :href="phoneHref || undefined"
                class="text-inherit no-underline transition-opacity hover:opacity-70"
              >
                {{ site.contactPhone }}
              </a>
            </li>
            <li v-for="email in contactEmails" :key="email.address">
              <a
                :href="`mailto:${email.address}`"
                class="text-inherit no-underline transition-opacity hover:opacity-70"
              >
                {{ email.address }}
              </a>
              <span class="text-[#64748b]"> {{ email.note }}</span>
            </li>
          </ul>

          <div v-if="site.contactMessengers.length" class="mt-4 flex items-center gap-2.5">
            <a
              v-for="social in site.contactMessengers"
              :key="`${social.icon}-${social.url}`"
              :href="social.url"
              :aria-label="social.label"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex size-9 items-center justify-center rounded-full bg-[#222222] text-white transition-opacity hover:opacity-80"
            >
              <AppIcon :name="social.icon" :size="18" />
            </a>
          </div>
        </div>
      </div>
    </div>
  </footer>
</template>
