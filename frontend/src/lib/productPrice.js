export function formatMoney(value) {
  return Number(value).toLocaleString('ru-RU')
}

export function resolveCompareAtPrice(product) {
  if (product?.compare_at_price_display != null && product.compare_at_price_display !== '') {
    const display = Number(product.compare_at_price_display)
    const price = Number(product?.price ?? 0)

    return Number.isFinite(display) && display > price ? display : null
  }

  const price = Number(product?.price ?? 0)
  if (!Number.isFinite(price) || price <= 0) return null

  const exact = product?.compare_at_price
  if (exact != null && exact !== '') {
    const value = Number(exact)
    return Number.isFinite(value) && value > price ? value : null
  }

  const percent = product?.compare_at_markup_percent
  if (percent != null && percent !== '' && Number(percent) > 0) {
    const value = Math.round(price * (1 + Number(percent) / 100) * 100) / 100
    return value > price ? value : null
  }

  return null
}
