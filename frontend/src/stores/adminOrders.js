import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import api from '@/api/client'

function pluralNewOrders(count) {
  const mod10 = count % 10
  const mod100 = count % 100

  if (mod10 === 1 && mod100 !== 11) {
    return `${count} новый заказ`
  }

  if (mod10 >= 2 && mod10 <= 4 && (mod100 < 12 || mod100 > 14)) {
    return `${count} новых заказа`
  }

  return `${count} новых заказов`
}

export const useAdminOrdersStore = defineStore('adminOrders', () => {
  const newCount = ref(0)
  const loading = ref(false)
  let pollTimer = null

  const hasNew = computed(() => newCount.value > 0)
  const bannerText = computed(() => `У вас ${pluralNewOrders(newCount.value)}`)

  async function refresh() {
    loading.value = true
    try {
      const { data } = await api.get('/admin/orders/new-count')
      newCount.value = Number(data.data?.count ?? 0)
    } catch {
      // тихо: бейдж не критичен для работы админки
    } finally {
      loading.value = false
    }
  }

  function startPolling(intervalMs = 30000) {
    stopPolling()
    refresh()
    pollTimer = window.setInterval(refresh, intervalMs)
  }

  function stopPolling() {
    if (pollTimer != null) {
      window.clearInterval(pollTimer)
      pollTimer = null
    }
  }

  return {
    newCount,
    loading,
    hasNew,
    bannerText,
    refresh,
    startPolling,
    stopPolling,
  }
})
