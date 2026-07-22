import { useToastStore } from '@/stores/toast'

/**
 * Кастомные подсказки вместо браузерных tooltips.
 * Формы должны иметь атрибут novalidate.
 * Показывает toast рядом с первым незаполненным/невалидным полем.
 */

export function messageForInvalidField(field) {
  const label = resolveFieldLabel(field)

  if (field.validity.valueMissing) {
    return label ? `Заполните поле «${label}»` : 'Заполните обязательное поле'
  }

  if (field.validity.typeMismatch) {
    if (field.type === 'email') {
      return 'Введите корректный email'
    }
    return label ? `Некорректное значение в поле «${label}»` : 'Некорректное значение'
  }

  if (field.validity.patternMismatch) {
    return label ? `Неверный формат поля «${label}»` : 'Неверный формат'
  }

  if (field.validity.tooShort) {
    return label ? `Слишком короткое значение в поле «${label}»` : 'Слишком короткое значение'
  }

  if (field.validity.rangeUnderflow || field.validity.rangeOverflow) {
    return label ? `Недопустимое значение в поле «${label}»` : 'Недопустимое значение'
  }

  return field.validationMessage || 'Проверьте заполнение формы'
}

function resolveFieldLabel(field) {
  const fromAria = field.getAttribute('aria-label')?.trim()
  if (fromAria) return fromAria

  const label = field.closest('label')
  if (label) {
    const span = label.querySelector('span')
    const text = (span?.textContent || label.textContent || '')
      .replace(/\s+/g, ' ')
      .trim()
    if (text && text.length < 80) return text
  }

  const id = field.id
  if (id) {
    const byFor = document.querySelector(`label[for="${CSS.escape(id)}"]`)
    const text = byFor?.textContent?.replace(/\s+/g, ' ').trim()
    if (text) return text
  }

  return field.placeholder || field.name || ''
}

function collectValidatableFields(form) {
  return [...form.elements].filter(
    (el) =>
      el instanceof HTMLElement
      && 'checkValidity' in el
      && el.willValidate
      && !el.disabled
      && el.type !== 'hidden',
  )
}

/**
 * @param {HTMLFormElement | null | undefined} form
 * @returns {boolean}
 */
export function validateForm(form) {
  if (!form) return true

  const toast = useToastStore()
  const fields = collectValidatableFields(form)

  for (const field of fields) {
    if (field.checkValidity()) continue

    toast.warning(messageForInvalidField(field), {
      anchor: field,
      duration: 3800,
    })
    field.focus({ preventScroll: false })
    field.scrollIntoView({ block: 'nearest', behavior: 'smooth' })
    return false
  }

  return true
}
