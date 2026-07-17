/**
 * Прокрутка к форме редактирования в админке.
 * @param {HTMLElement|null|undefined} el
 */
export function scrollAdminFormIntoView(el) {
  if (!el || typeof el.scrollIntoView !== 'function') return

  requestAnimationFrame(() => {
    el.scrollIntoView({ behavior: 'smooth', block: 'start' })
  })
}
