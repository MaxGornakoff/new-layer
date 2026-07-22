/** Предустановленная палитра для выбора цвета филамента */
export const PRODUCT_COLOR_PRESETS = [
  '#EF4444',
  '#F97316',
  '#EAB308',
  '#84CC16',
  '#22C55E',
  '#14B8A6',
  '#0EA5E9',
  '#3B82F6',
  '#6366F1',
  '#A855F7',
  '#EC4899',
  '#78716C',
  '#64748B',
  '#FFFFFF',
  '#111827',
]

const HEX_RE = /^#[0-9A-Fa-f]{6}$/

export function isHexColor(value) {
  return typeof value === 'string' && HEX_RE.test(value.trim())
}

export function normalizeHexColor(value) {
  if (!isHexColor(value)) return ''
  return `#${value.trim().slice(1).toUpperCase()}`
}

/** Светлый цвет — нужна обводка на белом фоне */
export function isLightHexColor(value) {
  const hex = normalizeHexColor(value)
  if (!hex) return false

  const r = Number.parseInt(hex.slice(1, 3), 16)
  const g = Number.parseInt(hex.slice(3, 5), 16)
  const b = Number.parseInt(hex.slice(5, 7), 16)
  const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255

  return luminance > 0.85
}
