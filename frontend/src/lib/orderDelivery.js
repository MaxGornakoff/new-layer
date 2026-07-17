/**
 * Разделяет ТК и адрес доставки.
 * Поддерживает новые заказы (отдельные поля) и старые (одна строка «ТК, г. …, ПВЗ: …»).
 */
export function resolveOrderDelivery(order) {
  if (!order) {
    return { provider: '—', address: '—' }
  }

  if (order.delivery_provider) {
    return {
      provider: order.delivery_provider,
      address: order.delivery_address?.trim() || '—',
    }
  }

  const raw = String(order.delivery_address || '').trim()
  if (!raw) {
    return { provider: '—', address: '—' }
  }

  const parts = raw.split(', ').map((part) => part.trim()).filter(Boolean)

  if (parts.length >= 2 && !parts[0].startsWith('г.') && !parts[0].startsWith('ПВЗ:')) {
    return {
      provider: parts[0],
      address: parts.slice(1).join(', '),
    }
  }

  return {
    provider: '—',
    address: raw,
  }
}
