import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

export const useToastStore = defineStore('toast', () => {
  const items = ref([])
  let nextId = 0

  /**
   * @param {string} message
   * @param {'success'|'error'|'warning'|'info'} [type]
   * @param {number|{ duration?: number, anchor?: Element|null }} [durationOrOptions]
   */
  function show(message, type = 'success', durationOrOptions = {}) {
    let duration = type === 'error' || type === 'warning' ? 4200 : 3200
    let anchor = null

    if (typeof durationOrOptions === 'number') {
      duration = durationOrOptions
    } else if (durationOrOptions && typeof durationOrOptions === 'object') {
      if (typeof durationOrOptions.duration === 'number') {
        duration = durationOrOptions.duration
      }
      if (durationOrOptions.anchor) {
        anchor = durationOrOptions.anchor
      }
    }

    const id = ++nextId
    items.value.push({ id, message, type, anchor })

    window.setTimeout(() => {
      remove(id)
    }, duration)

    return id
  }

  function success(message, durationOrOptions) {
    return show(message, 'success', durationOrOptions)
  }

  function error(message, durationOrOptions) {
    return show(message, 'error', durationOrOptions)
  }

  function warning(message, durationOrOptions) {
    return show(message, 'warning', durationOrOptions)
  }

  function info(message, durationOrOptions) {
    return show(message, 'info', durationOrOptions)
  }

  function remove(id) {
    items.value = items.value.filter((item) => item.id !== id)
  }

  const stackedItems = computed(() => items.value.filter((item) => !item.anchor))
  const anchoredItems = computed(() => items.value.filter((item) => item.anchor))

  return {
    items,
    stackedItems,
    anchoredItems,
    show,
    success,
    error,
    warning,
    info,
    remove,
  }
})
