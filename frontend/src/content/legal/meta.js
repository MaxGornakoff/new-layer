export const legalPages = {
  'privacy-policy': {
    title: 'Политика конфиденциальности',
  },
  'personal-data-consent': {
    title: 'Согласие на обработку персональных данных',
  },
  'public-offer': {
    title: 'Публичная оферта',
  },
  'user-agreement': {
    title: 'Пользовательское соглашение',
  },
  'cookie-policy': {
    title: 'Политика использования cookie',
  },
}

/** Подставляется в HTML вместо плейсхолдеров. Заполните перед публикацией. */
export const legalPlaceholders = {
  SITE_URL: 'https://new-layer.ru',
  EMAIL: '[указать e-mail]',
  PHONE: '[указать телефон]',
  INN: '[указать ИНН]',
  LEGAL_ADDRESS: '[указать юридический адрес]',
  FULL_NAME: '[указать Ф. И. О.]',
  REG_ADDRESS: '[указать адрес регистрации]',
  DATE: '[указать дату]',
}

export function applyLegalPlaceholders(html) {
  return Object.entries(legalPlaceholders).reduce(
    (result, [key, value]) => result.replaceAll(`{{${key}}}`, value),
    html,
  )
}
