import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api/client'

function applyFavicon(url) {
  if (!url || typeof document === 'undefined') return

  const absoluteUrl = /^https?:\/\//i.test(url) || url.startsWith('data:')
    ? url
    : new URL(url, window.location.origin).href

  const cacheBusted = `${absoluteUrl}${absoluteUrl.includes('?') ? '&' : '?'}v=${Date.now()}`

  document
    .querySelectorAll("link[rel='icon'], link[rel=\"shortcut icon\"], link[rel='apple-touch-icon']")
    .forEach((el) => el.remove())

  let type
  if (/\.svg(\?|$)/i.test(absoluteUrl)) type = 'image/svg+xml'
  else if (/\.png(\?|$)/i.test(absoluteUrl)) type = 'image/png'
  else if (/\.ico(\?|$)/i.test(absoluteUrl)) type = 'image/x-icon'

  const link = document.createElement('link')
  link.rel = 'icon'
  if (type) link.type = type
  link.href = cacheBusted
  document.head.appendChild(link)
}

export function phoneToTelHref(phone) {
  if (!phone) return null
  const digits = String(phone).replace(/\D/g, '')
  if (!digits) return null
  return `tel:+${digits}`
}

export const useSiteStore = defineStore('site', () => {
  const logoUrl = ref(null)
  const faviconUrl = ref(null)
  const catalogAutoApplyFilters = ref(true)
  const contactPhone = ref(null)
  const contactEmailBusiness = ref(null)
  const contactEmailSupport = ref(null)
  const contactMessengers = ref([])
  const loaded = ref(false)

  function applySettings(settings) {
    logoUrl.value = settings.logo_url
    faviconUrl.value = settings.favicon_url
    catalogAutoApplyFilters.value = settings.catalog_auto_apply_filters ?? true
    contactPhone.value = settings.contact_phone || null
    contactEmailBusiness.value = settings.contact_email_business || null
    contactEmailSupport.value = settings.contact_email_support || null
    contactMessengers.value = Array.isArray(settings.contact_messengers)
      ? settings.contact_messengers
      : []
    // Tab icon = favicon from admin; if empty, fall back to logo
    applyFavicon(faviconUrl.value || logoUrl.value)
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
    contactPhone,
    contactEmailBusiness,
    contactEmailSupport,
    contactMessengers,
    loaded,
    fetchSettings,
    setFromAdmin,
  }
})
