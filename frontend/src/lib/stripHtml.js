export function stripHtml(value) {
  if (!value) return ''
  return String(value).replace(/<[^>]*>/g, '').trim()
}
