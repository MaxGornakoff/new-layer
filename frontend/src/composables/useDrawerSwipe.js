import { computed, ref } from 'vue'

/**
 * Свайп для закрытия боковой шторки.
 * @param {object} options
 * @param {import('vue').Ref<HTMLElement|null>} options.panelRef
 * @param {import('vue').Ref<boolean>} options.isOpen
 * @param {() => void} options.onClose
 * @param {'left'|'right'} options.edge — с какой стороны прикреплена панель
 */
export function useDrawerSwipe({ panelRef, isOpen, onClose, edge }) {
  const dragOffset = ref(0)
  const isDragging = ref(false)

  let dragStartX = 0
  let dragStartY = 0
  let dragAxis = null
  let activePointerId = null

  const panelStyle = computed(() => {
    if (!isDragging.value || dragOffset.value === 0) {
      return undefined
    }

    return {
      transform: `translateX(${dragOffset.value}px)`,
      transition: 'none',
    }
  })

  function resetDrag() {
    isDragging.value = false
    dragOffset.value = 0
    dragAxis = null
    activePointerId = null
  }

  function onPointerDown(event) {
    if (!isOpen.value) return
    if (event.pointerType === 'mouse' && event.button !== 0) return

    isDragging.value = true
    dragStartX = event.clientX
    dragStartY = event.clientY
    dragAxis = null
    activePointerId = event.pointerId
    panelRef.value?.setPointerCapture(event.pointerId)
  }

  function onPointerMove(event) {
    if (!isDragging.value || event.pointerId !== activePointerId) return

    const deltaX = event.clientX - dragStartX
    const deltaY = event.clientY - dragStartY

    if (!dragAxis) {
      if (Math.abs(deltaX) < 8 && Math.abs(deltaY) < 8) return

      dragAxis = Math.abs(deltaX) >= Math.abs(deltaY) ? 'x' : 'y'

      if (dragAxis === 'y') {
        panelRef.value?.releasePointerCapture(event.pointerId)
        resetDrag()
        return
      }
    }

    if (edge === 'left') {
      dragOffset.value = Math.min(0, deltaX)
    } else {
      dragOffset.value = Math.max(0, deltaX)
    }
  }

  function finishDrag() {
    if (!isDragging.value) return

    const width = panelRef.value?.clientWidth ?? 300
    const threshold = Math.min(72, width * 0.22)

    const shouldClose =
      edge === 'left' ? dragOffset.value < -threshold : dragOffset.value > threshold

    if (shouldClose) {
      onClose()
    }

    resetDrag()
  }

  function onPointerUp(event) {
    if (!isDragging.value || event.pointerId !== activePointerId) return

    panelRef.value?.releasePointerCapture(event.pointerId)
    finishDrag()
  }

  function onPointerCancel(event) {
    if (!isDragging.value || event.pointerId !== activePointerId) return

    panelRef.value?.releasePointerCapture(event.pointerId)
    finishDrag()
  }

  return {
    panelStyle,
    isDragging,
    onPointerDown,
    onPointerMove,
    onPointerUp,
    onPointerCancel,
  }
}
