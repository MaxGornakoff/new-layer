import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api/client'

function applyFavicon(url) {
  if (!url) return

  let link = document.querySelector("link[rel='icon']")

  if (!link) {
    link = document.createElement('link')
    link.rel = 'icon'
    document.head.appendChild(link)
  }

  link.href = url

  if (url.endsWith('.svg')) {
    link.type = 'image/svg+xml'
  } else if (url.endsWith('.png')) {
    link.type = 'image/png'
  }
}

export const useSiteStore = defineStore('site', () => {
  const logoUrl = ref(null)
  const faviconUrl = ref(null)
  const catalogAutoApplyFilters = ref(true)
  const loaded = ref(false)

  function applySettings(settings) {
    logoUrl.value = settings.logo_url
    faviconUrl.value = settings.favicon_url
    catalogAutoApplyFilters.value = settings.catalog_auto_apply_filters ?? true
    applyFavicon(faviconUrl.value)
  }

  async function fetchSettings() {
    const { data } = await api.get('/site-settings')
    applySettings(data.data)
    loaded.value = true
  }

  function setFromAdmin(settings) {
    applySettings(settings)
  }

  return {
    logoUrl,
    faviconUrl,
    catalogAutoApplyFilters,
    loaded,
    fetchSettings,
    setFromAdmin,
  }
})
