export const ORDER_PACK_SIZE = 10

function pluralSpools(count) {
  const value = Math.abs(Number(count)) || 0
  const mod10 = value % 10
  const mod100 = value % 100

  if (mod10 === 1 && mod100 !== 11) return 'катушку'
  if (mod10 >= 2 && mod10 <= 4 && (mod100 < 12 || mod100 > 14)) return 'катушки'

  return 'катушек'
}

export function getOrderPackStatus(totalItems, packSize = ORDER_PACK_SIZE) {
  if (totalItems <= 0) {
    return {
      canCheckout: false,
      remainder: 0,
      itemsToNextPack: packSize,
      itemsToRemoveForPrevPack: 0,
      nextPackTotal: packSize,
      prevPackTotal: 0,
      progressInPack: 0,
      message: '',
      successMessage: '',
    }
  }

  const remainder = totalItems % packSize
  const canCheckout = remainder === 0
  const itemsToNextPack = canCheckout ? 0 : packSize - remainder
  const itemsToRemoveForPrevPack = canCheckout ? 0 : remainder
  const nextPackTotal = totalItems + itemsToNextPack
  const prevPackTotal = totalItems - itemsToRemoveForPrevPack
  const progressInPack = canCheckout ? packSize : remainder

  let message = ''
  let successMessage = ''

  if (canCheckout) {
    successMessage = `${totalItems} ${pluralSpools(totalItems)} — можно оформить заказ`
  } else if (totalItems < packSize) {
    message = `Добавьте ещё ${itemsToNextPack} ${pluralSpools(itemsToNextPack)} для оформления заказа`
  } else {
    message = `Добавьте ${itemsToNextPack} ${pluralSpools(itemsToNextPack)} до ${nextPackTotal} или уберите ${itemsToRemoveForPrevPack} ${pluralSpools(itemsToRemoveForPrevPack)} до ${prevPackTotal}`
  }

  return {
    canCheckout,
    remainder,
    itemsToNextPack,
    itemsToRemoveForPrevPack,
    nextPackTotal,
    prevPackTotal,
    progressInPack,
    packSize,
    message,
    successMessage,
  }
}
