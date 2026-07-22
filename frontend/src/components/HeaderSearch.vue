<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import api from '@/api/client'
import AppIcon from '@/components/AppIcon.vue'
import AppLoader from '@/components/AppLoader.vue'

const MOBILE_BREAKPOINT = 992

const router = useRouter()
const route = useRoute()

const placeholder = ref(null)
const inputRef = ref(null)
const isOpen = ref(false)
const query = ref('')
const results = ref([])
const loading = ref(false)
const hasSearched = ref(false)
const anchorStyle = ref({})
const isMobile = ref(false)

let debounceTimer = null

const trimmedQuery = computed(() => query.value.trim())
const showPanel = computed(() => isOpen.value && trimmedQuery.value.length >= 2)

function syncViewport() {
  isMobile.value = window.innerWidth <= MOBILE_BREAKPOINT
}

function updatePosition() {
  if (!placeholder.value) return

  syncViewport()
  const rect = placeholder.value.getBoundingClientRect()

  if (isMobile.value) {
    anchorStyle.value = {
      top: `${rect.top + rect.height / 2}px`,
      left: '0.75rem',
      right: '0.75rem',
      width: 'auto',
    }
    return
  }

  anchorStyle.value = {
    top: `${rect.top + rect.height / 2}px`,
    right: `${window.innerWidth - rect.right}px`,
    left: 'auto',
    width: 'auto',
  }
}

function openSearch() {
  isOpen.value = true
  nextTick(() => {
    updatePosition()
    inputRef.value?.focus()
  })
}

function closeSearch() {
  isOpen.value = false
  query.value = ''
  results.value = []
  hasSearched.value = false
}

function toggleSearch(event) {
  event.stopPropagation()
  if (isOpen.value) {
    closeSearch()
    return
  }
  openSearch()
}

async function fetchResults() {
  const term = trimmedQuery.value
  if (term.length < 2) {
    results.value = []
    hasSearched.value = false
    return
  }

  loading.value = true
  try {
    const { data } = await api.get('/products', {
      params: { search: term, per_page: 3 },
    })
    results.value = data.data
    hasSearched.value = true
  } finally {
    loading.value = false
  }
}

function goToAllResults() {
  const q = trimmedQuery.value
  if (!q) return
  closeSearch()
  router.push({ name: 'search', query: { q } })
}

watch(query, () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(fetchResults, 300)
})

watch(isOpen, (open) => {
  document.body.classList.toggle('header-search-active', open)
  if (open) {
    nextTick(updatePosition)
  }
})

watch(
  () => route.fullPath,
  () => closeSearch(),
)

onMounted(() => {
  syncViewport()
  window.addEventListener('resize', updatePosition)
  window.addEventListener('scroll', updatePosition, true)
})

onUnmounted(() => {
  document.body.classList.remove('header-search-active')
  window.removeEventListener('resize', updatePosition)
  window.removeEventListener('scroll', updatePosition, true)
  clearTimeout(debounceTimer)
})
</script>

<template>
  <div ref="placeholder" class="header-search">
    <button
      v-if="!isOpen"
      type="button"
      class="site-header__action header-search__toggle"
      aria-label="Поиск"
      aria-expanded="false"
      @click="toggleSearch"
    >
      <AppIcon name="search" />
    </button>
  </div>

  <Teleport to="body">
    <Transition name="header-search-backdrop">
      <button
        v-if="isOpen"
        type="button"
        class="header-search__backdrop"
        aria-label="Закрыть поиск"
        @click="closeSearch"
      />
    </Transition>

    <div
      v-if="isOpen"
      class="header-search__anchor"
      :class="{ 'header-search__anchor--mobile': isMobile }"
      :style="anchorStyle"
      @click.stop
    >
      <div
        class="header-search__bar header-search__bar--open"
        :class="{ 'header-search__bar--mobile': isMobile }"
      >
        <input
          ref="inputRef"
          v-model="query"
          type="text"
          inputmode="search"
          enterkeyhint="search"
          class="header-search__input"
          placeholder="Поиск филамента..."
          autocomplete="off"
          @keydown.esc.prevent="closeSearch"
          @keydown.enter.prevent="goToAllResults"
        />
        <button
          type="button"
          class="site-header__action header-search__toggle"
          aria-label="Закрыть"
          @click="closeSearch"
        >
          <AppIcon name="close" />
        </button>
      </div>

      <Transition name="header-search-panel">
        <div
          v-if="showPanel"
          class="header-search__panel"
          :class="{ 'header-search__panel--mobile': isMobile }"
        >
          <AppLoader v-if="loading" inline />

          <template v-else-if="results.length">
            <ul class="header-search__list">
              <li v-for="product in results" :key="product.id">
                <RouterLink
                  :to="{ name: 'product', params: { slug: product.slug } }"
                  class="header-search__item"
                  @click="closeSearch"
                >
                  <span class="header-search__item-name">{{ product.name }}</span>
                  <span class="header-search__item-price">
                    {{ Number(product.price).toLocaleString('ru-RU') }} ₽
                  </span>
                </RouterLink>
              </li>
            </ul>
            <button type="button" class="header-search__all" @click="goToAllResults">
              Показать все
            </button>
          </template>

          <p v-else-if="hasSearched" class="header-search__message">Ничего не найдено</p>
        </div>
      </Transition>
    </div>
  </Teleport>
</template>

<style scoped>
.header-search {
  position: relative;
  flex-shrink: 0;
  width: 25px;
  height: 25px;
}

@media (max-width: 992px) {
  .header-search {
    width: 20px;
    height: 20px;
  }
}

.header-search__backdrop {
  position: fixed;
  inset: 0;
  z-index: 190;
  border: none;
  padding: 0;
  background: rgba(15, 23, 42, 0.38);
  cursor: pointer;
}

.header-search__anchor {
  position: fixed;
  z-index: 200;
  transform: translateY(-50%);
}

.header-search__anchor--mobile {
  transform: translateY(-50%);
}

.header-search__bar {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  overflow: hidden;
  border-radius: 0.75rem;
  background: #fff;
  box-shadow: 0 12px 32px rgba(15, 23, 42, 0.16);
  transform-origin: right center;
  animation: header-search-bar-in 0.32s ease;
}

.header-search__bar--open {
  width: min(360px, calc(100vw - 2rem));
  height: 3rem;
}

.header-search__bar--open .header-search__toggle {
  position: relative;
  right: 5px;
}

.header-search__bar--mobile {
  width: 100%;
  transform-origin: right center;
  animation: header-search-bar-in-mobile 0.36s cubic-bezier(0.22, 1, 0.36, 1);
}

.header-search__input {
  flex: 1;
  min-width: 0;
  height: 100%;
  margin: 0;
  padding: 0 0.25rem 0 1rem;
  border: none;
  background: transparent;
  outline: none;
}

.header-search__toggle {
  flex-shrink: 0;
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
}

@media (max-width: 992px) {
  .header-search__toggle {
    width: 20px;
    height: 20px;
  }
}

.header-search__panel {
  position: absolute;
  top: calc(100% + 0.5rem);
  right: 0;
  width: min(360px, calc(100vw - 2rem));
  padding: 0.5rem;
  background: #fff;
  border-radius: 0.75rem;
  box-shadow: 0 12px 32px rgba(15, 23, 42, 0.16);
}

.header-search__panel--mobile {
  left: 0;
  right: 0;
  width: 100%;
}

.header-search__list {
  list-style: none;
  margin: 0;
  padding: 0;
}

.header-search__item {
  display: flex;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.65rem 0.5rem;
  border-radius: 0.5rem;
  color: inherit;
}

.header-search__item:hover {
  background: #f8fafc;
}

.header-search__item-name {
  flex: 1;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.header-search__item-price {
  flex-shrink: 0;
  font-weight: 600;
}

.header-search__all {
  display: block;
  width: 100%;
  margin-top: 0.25rem;
  padding: 0.65rem 0.75rem;
  border: none;
  border-radius: 0.5rem;
  background: #f1f5f9;
  cursor: pointer;
  font: inherit;
  font-weight: 600;
}

.header-search__all:hover {
  background: #e2e8f0;
}

.header-search__message {
  margin: 0;
  padding: 0.65rem 0.5rem;
  color: #64748b;
  font-size: 0.9rem;
}

@keyframes header-search-bar-in {
  from {
    width: 25px;
    opacity: 0.85;
  }

  to {
    width: min(360px, calc(100vw - 2rem));
    opacity: 1;
  }
}

@keyframes header-search-bar-in-mobile {
  from {
    width: 2.5rem;
    margin-left: auto;
    opacity: 0.9;
    transform: translateX(0.35rem);
  }

  to {
    width: 100%;
    margin-left: 0;
    opacity: 1;
    transform: translateX(0);
  }
}

.header-search-backdrop-enter-active,
.header-search-backdrop-leave-active {
  transition: opacity 0.25s ease;
}

.header-search-backdrop-enter-from,
.header-search-backdrop-leave-to {
  opacity: 0;
}

.header-search-panel-enter-active,
.header-search-panel-leave-active {
  transition:
    opacity 0.2s ease,
    transform 0.2s ease;
}

.header-search-panel-enter-from,
.header-search-panel-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}
</style>

<style>
body.header-search-active {
  overflow: hidden;
}
</style>
