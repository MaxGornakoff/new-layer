export const ORDER_STATUS_LABELS = {
  new: 'Новый',
  confirmed: 'Подтверждён',
  processing: 'В обработке',
  shipped: 'Отправлен',
  completed: 'Выполнен',
  cancelled: 'Отменён',
}

export function getOrderStatusLabel(status) {
  return ORDER_STATUS_LABELS[status] ?? status
}
