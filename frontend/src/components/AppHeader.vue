<script setup>
import { onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import api from '@/api/client'
import { useAuthStore } from '@/stores/auth'
import { useSiteStore } from '@/stores/site'
import AppIcon from '@/components/AppIcon.vue'
import HeaderSearch from '@/components/HeaderSearch.vue'
import HeaderCart from '@/components/HeaderCart.vue'
import HeaderProfile from '@/components/HeaderProfile.vue'

const auth = useAuthStore()
const site = useSiteStore()
const route = useRoute()

const categories = ref([])
const menuSections = ref([])
const mobileMenuOpen = ref(false)
const mobileExpanded = ref({})

onMounted(async () => {
  const [categoriesResponse, menuResponse] = await Promise.all([
    api.get('/categories'),
    api.get('/menu'),
  ])

  categories.value = categoriesResponse.data.data
  menuSections.value = menuResponse.data.data
})

watch(
  () => route.fullPath,
  () => {
    mobileMenuOpen.value = false
    mobileExpanded.value = {}
  },
)

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
    openInNewTab: item.open_in_new_tab,
  }))

  return [...fromCategories, ...fromChildren]
}

function isDropdown(section) {
  return section.type === 'dropdown'
}

function hasSubitems(section) {
  return isDropdown(section) && sectionSubitems(section).length > 0
}

function toggleMobileAccordion(key) {
  mobileExpanded.value[key] = !mobileExpanded.value[key]
}

function isExternalUrl(url) {
  return /^https?:\/\//i.test(url)
}

function closeMobileMenu() {
  mobileMenuOpen.value = false
  mobileExpanded.value = {}
}

</script>

<template>
  <header class="site-header rounded-[20px] z-100 relative h-18 flex items-center">
    <div class="box site-header__inner flex items-center justify-between">
      <RouterLink to="/" class="site-header__logo" @click="closeMobileMenu">
        <img
          v-if="site.logoUrl"
          :src="site.logoUrl"
          alt="Filament Shop"
          class="site-header__logo-image"
        />
        <span v-else>Filament Shop</span>
      </RouterLink>

      <nav class="site-header__nav" aria-label="Основное меню">
        <ul class="site-header__nav-list flex justify-center gap-12.5">
          <li
            v-for="section in menuSections"
            :key="section.id"
            class="site-header__nav-item cursor-pointer"
            :class="{ 'site-header__nav-item--dropdown': isDropdown(section) }"
          >
            <RouterLink
              v-if="section.type === 'link' && section.url"
              :to="section.url"
              class="site-header__nav-link cursor-pointer"
              :target="section.open_in_new_tab ? '_blank' : undefined"
              :rel="section.open_in_new_tab ? 'noopener noreferrer' : undefined"
            >
              {{ section.title }}
            </RouterLink>

            <template v-else-if="isDropdown(section)">
              <RouterLink
                v-if="section.url"
                :to="section.url"
                class="site-header__nav-link site-header__nav-link--catalog"
                :target="section.open_in_new_tab ? '_blank' : undefined"
                :rel="section.open_in_new_tab ? 'noopener noreferrer' : undefined"
              >
                {{ section.title }}
              </RouterLink>
              <span v-else class="site-header__nav-link site-header__nav-link--parent">
                {{ section.title }}
              </span>

              <ul v-if="hasSubitems(section)" class="site-header__dropdown">
                <li v-for="item in sectionSubitems(section)" :key="item.id">
                  <a
                    v-if="isExternalUrl(item.url)"
                    :href="item.url"
                    class="site-header__dropdown-link"
                    :target="item.openInNewTab ? '_blank' : undefined"
                    :rel="item.openInNewTab ? 'noopener noreferrer' : undefined"
                  >
                    {{ item.title }}
                  </a>
                  <RouterLink
                    v-else
                    :to="item.url"
                    class="site-header__dropdown-link"
                    @click="closeMobileMenu"
                  >
                    {{ item.title }}
                  </RouterLink>
                </li>
              </ul>
            </template>
          </li>
        </ul>
      </nav>

      <div class="site-header__actions flex items-center gap-10 max-[992px]:gap-8">
        <HeaderSearch />

        <HeaderCart />

        <HeaderProfile />

        <RouterLink
          v-if="auth.isAdmin"
          to="/admin"
          class="site-header__action site-header__action--text"
          aria-label="Админка"
        >
          Админка
        </RouterLink>

        <button
          type="button"
          class="site-header__burger"
          :aria-expanded="mobileMenuOpen"
          aria-controls="site-header-mobile-menu"
          aria-label="Меню"
          @click="mobileMenuOpen = !mobileMenuOpen"
        >
          <span></span>
          <span></span>
          <span></span>
        </button>
      </div>
    </div>

    <div
      id="site-header-mobile-menu"
      class="site-header__mobile"
      :class="{ 'site-header__mobile--open': mobileMenuOpen }"
    >
      <nav class="site-header__mobile-nav" aria-label="Мобильное меню">
        <ul class="site-header__mobile-list">
          <li v-for="section in menuSections" :key="section.id" class="site-header__mobile-item">
            <template v-if="isDropdown(section)">
              <button
                type="button"
                class="site-header__mobile-toggle"
                :aria-expanded="Boolean(mobileExpanded[section.key])"
                @click="toggleMobileAccordion(section.key)"
              >
                {{ section.title }}
              </button>
              <ul v-if="mobileExpanded[section.key]" class="site-header__mobile-sublist">
                <li v-for="item in sectionSubitems(section)" :key="item.id">
                  <a
                    v-if="isExternalUrl(item.url)"
                    :href="item.url"
                    class="site-header__mobile-sublink"
                    :target="item.openInNewTab ? '_blank' : undefined"
                    :rel="item.openInNewTab ? 'noopener noreferrer' : undefined"
                    @click="closeMobileMenu"
                  >
                    {{ item.title }}
                  </a>
                  <RouterLink
                    v-else
                    :to="item.url"
                    class="site-header__mobile-sublink"
                    @click="closeMobileMenu"
                  >
                    {{ item.title }}
                  </RouterLink>
                </li>
              </ul>
            </template>

            <RouterLink
              v-else-if="section.url"
              :to="section.url"
              class="site-header__mobile-link"
              :target="section.open_in_new_tab ? '_blank' : undefined"
              :rel="section.open_in_new_tab ? 'noopener noreferrer' : undefined"
              @click="closeMobileMenu"
            >
              {{ section.title }}
            </RouterLink>
          </li>
        </ul>
      </nav>
    </div>
  </header>
</template>

<style scoped>




.site-header__logo {
  font-weight: 700;
  font-size: 1.1rem;
  flex-shrink: 0;
  display: inline-flex;
  align-items: center;
}

.site-header__logo-image {
  display: block;
  max-height: 40px;
  max-width: 180px;
  object-fit: contain;
  @media (max-width: 992px) {
    max-height: 35px;
  }
}

.site-header__nav {
  flex: 1;
  display: none;
}

.site-header__nav-item {
  position: relative;
  cursor: pointer;
    span {
      cursor: pointer;
    }
  }


.site-header__nav-link,
.site-header__nav-link--parent {
  display: inline-flex;
  align-items: center;
  color: inherit;
  background: none;
  border: none;
  padding: 0.5rem 0;
  
}

.site-header__nav-link--catalog {
  cursor: pointer;
}

.site-header__nav-item--dropdown:hover .site-header__dropdown {
  display: block;
}

.site-header__dropdown {
  display: none;
  position: absolute;
  top: 100%;
  left: 0;
  min-width: 220px;
  margin: 0;
  padding: 0.5rem 0;
  list-style: none;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
}

.site-header__dropdown-link {
  display: block;
  padding: 0.5rem 1rem;
  color: inherit;
}

.site-header__dropdown-link:hover {
  background: #f8fafc;
}

.site-header__action {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 25px;
  height: 25px;
  color: inherit;
  background: none;
  border: none;
  border-radius: 0.5rem;
  cursor: pointer;
  @media (max-width: 992px) {
    width: 20px;
    height: 20px;
  }
}

.site-header__action--text {
  width: auto;
  padding: 0 0.5rem;
  font-size: 0.875rem;
  font-weight: 600;
}

.site-header__badge {
  position: absolute;
  top: 4px;
  right: 4px;
  min-width: 18px;
  height: 18px;
  padding: 0 4px;
  border-radius: 999px;
  background: #2563eb;
  color: #fff;
  font-size: 0.7rem;
  line-height: 18px;
  text-align: center;
}

.site-header__burger {
  display: inline-flex;
  flex-direction: column;
  justify-content: center;
  gap: 5px;
  padding: 0;
  border: none;
  background: none;
  cursor: pointer;
}

.site-header__burger span {
  display: block;
  width: 20px;
  height: 2px;
  border-radius: 4px;
  margin: 0 auto;
  background: #222222;
}

.site-header__mobile {
  display: none;
  border-top: 1px solid #e2e8f0;
  background: #fff;
}

.site-header__mobile--open {
  display: block;
}

.site-header__mobile-list {
  list-style: none;
  margin: 0;
  padding: 0.5rem 0;
}

.site-header__mobile-item {
  border-bottom: 1px solid #f1f5f9;
}

.site-header__mobile-link,
.site-header__mobile-toggle {
  display: block;
  width: 100%;
  padding: 0.9rem 1rem;
  text-align: left;
  color: inherit;
  background: none;
  border: none;
}

.site-header__mobile-sublist {
  list-style: none;
  margin: 0;
  padding: 0 0 0.5rem;
  background: #f8fafc;
}

.site-header__mobile-sublink {
  display: block;
  padding: 0.65rem 1.5rem;
  color: inherit;
}

@media (min-width: 992px) {
  .site-header__nav {
    display: block;
  }

  .site-header__burger,
  .site-header__mobile {
    display: none !important;
  }
}
</style>
