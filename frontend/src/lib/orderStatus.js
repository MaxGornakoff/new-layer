export const ORDER_STATUS_LABELS = {
  new: 'Новый',
  confirmed: 'Подтверждён',
  processing: 'В обработке',
  shipped: 'Отправлен',
  completed: 'Выполнен',
  cancelled: 'Отменён',
}

const ORDER_STATUS_CLASSES = {
  new: 'bg-sky-100 text-sky-800',
  confirmed: 'bg-indigo-100 text-indigo-800',
  processing: 'bg-amber-100 text-amber-900',
  shipped: 'bg-violet-100 text-violet-800',
  completed: 'bg-emerald-100 text-emerald-800',
  cancelled: 'bg-slate-200 text-slate-600',
}

export function getOrderStatusLabel(status) {
  return ORDER_STATUS_LABELS[status] ?? status
}

export function getOrderStatusClass(status) {
  return ORDER_STATUS_CLASSES[status] ?? 'bg-slate-100 text-slate-700'
}
