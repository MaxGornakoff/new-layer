import { onMounted, onUnmounted, watch } from 'vue'
import { useRoute } from 'vue-router'

/**
 * Прокрутка к hash после того, как асинхронный контент выше якоря
 * перестал менять высоту страницы (полки товаров, категории и т.п.).
 */
export function useHashScroll(rootRef, { offset = 80, watchMs = 5000 } = {}) {
  const route = useRoute()

  let resizeObserver = null
  let settleTimer = null
  let stopWatchTimer = null

  function clearTimers() {
    if (settleTimer) {
      clearTimeout(settleTimer)
      settleTimer = null
    }
    if (stopWatchTimer) {
      clearTimeout(stopWatchTimer)
      stopWatchTimer = null
    }
  }

  function disconnectObserver() {
    resizeObserver?.disconnect()
    resizeObserver = null
  }

  function scrollToHash(behavior = 'smooth') {
    const hash = route.hash
    if (!hash) return false

    const el = document.querySelector(hash)
    if (!el) return false

    const top = el.getBoundingClientRect().top + window.scrollY - offset
    window.scrollTo({ top: Math.max(0, top), behavior })
    return true
  }

  function arm() {
    if (!route.hash) return

    clearTimers()
    disconnectObserver()

    // Первая попытка после монтирования/смены hash
    requestAnimationFrame(() => {
      scrollToHash('smooth')
    })

    const root = rootRef?.value
    if (!root || typeof ResizeObserver === 'undefined') {
      // Fallback: несколько корректировок по таймеру
      let attempts = 0
      const id = setInterval(() => {
        attempts += 1
        scrollToHash('auto')
        if (attempts >= 20) clearInterval(id)
      }, 200)
      return
    }

    resizeObserver = new ResizeObserver(() => {
      clearTimeout(settleTimer)
      settleTimer = setTimeout(() => {
        // Корректируем без smooth — иначе «прыгает» при каждой подгрузке
        scrollToHash('auto')
      }, 120)
    })

    resizeObserver.observe(root)

    stopWatchTimer = setTimeout(() => {
      disconnectObserver()
      scrollToHash('auto')
      clearTimers()
    }, watchMs)
  }

  onMounted(() => {
    if (route.hash) {
      arm()
    }
  })

  watch(
    () => route.fullPath,
    () => {
      if (route.hash) {
        arm()
      } else {
        clearTimers()
        disconnectObserver()
      }
    },
  )

  onUnmounted(() => {
    clearTimers()
    disconnectObserver()
  })
}
