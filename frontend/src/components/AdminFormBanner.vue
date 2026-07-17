<script setup>
defineProps({
  mode: {
    type: String,
    required: true,
    validator: (value) => ['create', 'edit'].includes(value),
  },
  entity: {
    type: String,
    required: true,
  },
  title: {
    type: String,
    default: '',
  },
})

defineEmits(['cancel'])
</script>

<template>
  <div
    class="admin-form-banner"
    :class="mode === 'edit' ? 'admin-form-banner--edit' : 'admin-form-banner--create'"
    role="status"
  >
    <div class="admin-form-banner__content">
      <p class="admin-form-banner__eyebrow">
        {{ mode === 'edit' ? 'Режим редактирования' : 'Новая запись' }}
      </p>
      <p class="admin-form-banner__title">
        <template v-if="mode === 'edit'">
          Редактируете {{ entity }}<template v-if="title">: <strong>{{ title }}</strong></template>
        </template>
        <template v-else>
          Добавление: {{ entity }}
        </template>
      </p>
      <p class="admin-form-banner__hint">
        <template v-if="mode === 'edit'">
          Форма подсвечена синим. Внесите правки и нажмите «Обновить», либо «Отмена».
        </template>
        <template v-else>
          Заполните поля ниже и нажмите «Добавить». Можно закрыть форму без сохранения.
        </template>
      </p>
    </div>

    <button class="btn secondary admin-form-banner__cancel" type="button" @click="$emit('cancel')">
      {{ mode === 'edit' ? 'Отмена' : 'Закрыть' }}
    </button>
  </div>
</template>

<style scoped>
.admin-form-banner {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem 1rem;
  margin-bottom: 1.25rem;
  padding: 0.9rem 1rem;
  border-radius: 16px;
}

.admin-form-banner--edit {
  border: 1px solid #bfdbfe;
  background: linear-gradient(135deg, #eff6ff 0%, #e8eef9 100%);
}

.admin-form-banner--create {
  border: 1px solid #bbf7d0;
  background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
}

.admin-form-banner__content {
  min-width: 0;
  flex: 1;
}

.admin-form-banner__eyebrow {
  margin: 0;
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.admin-form-banner--edit .admin-form-banner__eyebrow {
  color: #3b72ff;
}

.admin-form-banner--create .admin-form-banner__eyebrow {
  color: #15803d;
}

.admin-form-banner__title {
  margin: 0.25rem 0 0;
  font-size: 0.95rem;
  color: #1e293b;
}

.admin-form-banner__title strong {
  font-weight: 700;
}

.admin-form-banner__hint {
  margin: 0.35rem 0 0;
  font-size: 0.8125rem;
  color: #64748b;
}

.admin-form-banner__cancel {
  flex-shrink: 0;
}
</style>
